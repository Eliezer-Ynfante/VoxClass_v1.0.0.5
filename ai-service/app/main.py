"""
VoxClass AI Service — v3.0.0
Agrega WebSockets para streaming de transcripción y progreso de PDF.
Los endpoints HTTP se mantienen para compatibilidad con clientes simples.

Protocolo WebSocket — mensajes JSON enviados por el servidor:
  { "type": "segment",  "text": "...", "start": 0.0, "end": 1.5 }  ← fragmento de transcripción
  { "type": "language", "language": "es", "probability": 0.98 }     ← idioma detectado
  { "type": "page",     "page": 3, "total": 20, "text": "..." }     ← progreso PDF
  { "type": "keywords", "keywords": [...] }                          ← palabras clave PDF
  { "type": "done",     "full_text": "...", ... }                    ← proceso terminado
  { "type": "error",    "detail": "..." }                            ← error recuperable
"""

import asyncio
import io
import json
import logging
import os
import subprocess
import sys
from contextlib import asynccontextmanager
from typing import Annotated

import fitz
import spacy
from fastapi import (
    FastAPI,
    File,
    HTTPException,
    UploadFile,
    WebSocket,
    WebSocketDisconnect,
    status,
)
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field, field_validator
from sentence_transformers import SentenceTransformer, util
from faster_whisper import WhisperModel

# ---------------------------------------------------------------------------
# Configuración centralizada
# ---------------------------------------------------------------------------

WHISPER_MODEL_SIZE: str = os.getenv("WHISPER_MODEL_SIZE", "small")
WHISPER_DEVICE: str = os.getenv("WHISPER_DEVICE", "cpu")
WHISPER_COMPUTE_TYPE: str = os.getenv("WHISPER_COMPUTE_TYPE", "int8")
WHISPER_BEAM_SIZE: int = int(os.getenv("WHISPER_BEAM_SIZE", "5"))

SPACY_MODEL: str = os.getenv("SPACY_MODEL", "es_core_news_sm")
SEMANTIC_MODEL_NAME: str = os.getenv(
    "SEMANTIC_MODEL_NAME", "paraphrase-multilingual-MiniLM-L12-v2"
)

MAX_FILE_SIZE_MB: int = int(os.getenv("MAX_FILE_SIZE_MB", "20"))
MAX_FILE_SIZE_BYTES: int = MAX_FILE_SIZE_MB * 1024 * 1024

PDF_NLP_CHAR_LIMIT: int = int(os.getenv("PDF_NLP_CHAR_LIMIT", "10000"))
PDF_MAX_PAGES: int = int(os.getenv("PDF_MAX_PAGES", "100"))

_raw_origins = os.getenv("CORS_ORIGINS", "")
ALLOWED_ORIGINS: list[str] = (
    [o.strip() for o in _raw_origins.split(",") if o.strip()]
    if _raw_origins
    else ["http://localhost", "http://localhost:3000"]
)

SIM_HIGH: float = float(os.getenv("SIM_HIGH", "0.80"))
SIM_MID: float = float(os.getenv("SIM_MID", "0.50"))

ALLOWED_AUDIO_TYPES = {
    "audio/wav", "audio/mpeg", "audio/mp4",
    "audio/ogg", "audio/flac", "audio/x-flac",
    "application/octet-stream",  # clientes que no envían content-type correcto
}

# ---------------------------------------------------------------------------
# Logging estructurado
# ---------------------------------------------------------------------------

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(name)s | %(message)s",
    datefmt="%Y-%m-%dT%H:%M:%S",
    stream=sys.stdout,
)
logger = logging.getLogger("voxclass")

# ---------------------------------------------------------------------------
# Estado global de modelos
# ---------------------------------------------------------------------------

class _ModelStore:
    whisper: WhisperModel | None = None
    nlp: spacy.language.Language | None = None
    semantic: SentenceTransformer | None = None

_models = _ModelStore()


def _load_spacy_model(model_name: str) -> spacy.language.Language:
    try:
        return spacy.load(model_name)
    except OSError:
        logger.warning("Modelo spaCy '%s' no encontrado. Descargando...", model_name)
        result = subprocess.run(
            [sys.executable, "-m", "spacy", "download", model_name],
            check=True, capture_output=True, text=True,
        )
        logger.info(result.stdout)
        return spacy.load(model_name)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Carga los modelos al arrancar y libera recursos al apagar."""
    logger.info("Iniciando carga de modelos...")

    logger.info("Cargando Whisper (%s / %s / %s)…",
                WHISPER_MODEL_SIZE, WHISPER_DEVICE, WHISPER_COMPUTE_TYPE)
    _models.whisper = WhisperModel(
        WHISPER_MODEL_SIZE, device=WHISPER_DEVICE, compute_type=WHISPER_COMPUTE_TYPE,
    )

    logger.info("Cargando spaCy (%s)…", SPACY_MODEL)
    _models.nlp = _load_spacy_model(SPACY_MODEL)

    logger.info("Cargando modelo semántico (%s)…", SEMANTIC_MODEL_NAME)
    _models.semantic = SentenceTransformer(SEMANTIC_MODEL_NAME)

    logger.info("Sistema listo. CORS: %s", ALLOWED_ORIGINS)
    yield
    logger.info("Cerrando servicio.")

# ---------------------------------------------------------------------------
# Aplicación FastAPI
# ---------------------------------------------------------------------------

app = FastAPI(
    title="VoxClass AI Service",
    version="3.0.0",
    description=(
        "Transcripción de audio (streaming WS), análisis de PDF (streaming WS) "
        "y similitud semántica (HTTP)."
    ),
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["Content-Type", "Authorization"],
)

# ---------------------------------------------------------------------------
# Utilidades compartidas
# ---------------------------------------------------------------------------

def _validate_file_size(data: bytes, label: str = "archivo") -> None:
    """Lanza HTTP 413 si el archivo supera el límite configurado."""
    if len(data) > MAX_FILE_SIZE_BYTES:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"El {label} supera el límite de {MAX_FILE_SIZE_MB} MB.",
        )


def _extract_keywords(doc_nlp, max_keywords: int = 15) -> list[str]:
    return list({
        token.text.lower()
        for token in doc_nlp
        if token.pos_ in ("NOUN", "PROPN") and len(token.text) > 3
    })[:max_keywords]


def _calculate_semantic_similarity(text1: str, text2: str) -> float:
    if not text1.strip() or not text2.strip():
        return 0.0
    embeddings = _models.semantic.encode(
        [text1, text2], convert_to_tensor=True, batch_size=2
    )
    return float(util.cos_sim(embeddings[0], embeddings[1])[0][0])


def _interpret_similarity(score: float) -> str:
    if score >= SIM_HIGH:
        return "Excelente cobertura de conceptos"
    if score >= SIM_MID:
        return "Cobertura aceptable"
    return "Desviación significativa del tema"


async def _ws_send(ws: WebSocket, payload: dict) -> None:
    """Envía un mensaje JSON por WebSocket de forma segura."""
    await ws.send_text(json.dumps(payload, ensure_ascii=False))

# ---------------------------------------------------------------------------
# Modelos Pydantic
# ---------------------------------------------------------------------------

class SimilarityRequest(BaseModel):
    text1: Annotated[str, Field(min_length=1, max_length=5000, description="Primer texto")]
    text2: Annotated[str, Field(min_length=1, max_length=5000, description="Segundo texto")]

    @field_validator("text1", "text2")
    @classmethod
    def no_blank(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("El texto no puede estar vacío.")
        return v


class TranscriptionResponse(BaseModel):
    transcription: str
    language: str | None = None


class PdfAnalysisResponse(BaseModel):
    title: str
    keywords: list[str]
    full_text: str
    pages_processed: int


class SimilarityResponse(BaseModel):
    similarity: float
    interpretation: str

# ---------------------------------------------------------------------------
# Health
# ---------------------------------------------------------------------------

@app.get("/", tags=["Health"], summary="Estado del servicio")
def root():
    return {
        "status": "online",
        "version": app.version,
        "websockets": ["/ws/transcribe", "/ws/analyze-pdf"],
        "http": ["/transcribe-audio", "/analyze-pdf", "/similarity"],
    }

# ---------------------------------------------------------------------------
# WS /ws/transcribe — Streaming de transcripción en tiempo real
#
# Flujo:
#   1. Cliente conecta por WebSocket
#   2. Cliente envía el archivo de audio como mensaje BINARIO (único envío)
#   3. Servidor valida tamaño y lanza transcripción en executor (no bloquea)
#   4. Servidor emite { type:"language" } con idioma detectado
#   5. Servidor emite { type:"segment" } por cada fragmento de Whisper
#   6. Servidor emite { type:"done", full_text } al finalizar
#   7. Servidor cierra la conexión
# ---------------------------------------------------------------------------

@app.websocket("/ws/transcribe")
async def ws_transcribe(websocket: WebSocket):
    await websocket.accept()
    logger.info("WS /ws/transcribe — conexión abierta desde %s", websocket.client)

    try:
        audio_bytes: bytes = await websocket.receive_bytes()

        if len(audio_bytes) > MAX_FILE_SIZE_BYTES:
            await _ws_send(websocket, {
                "type": "error",
                "detail": f"Audio supera el límite de {MAX_FILE_SIZE_MB} MB.",
            })
            await websocket.close(code=1009)  # 1009 = Message Too Big
            return

        audio_stream = io.BytesIO(audio_bytes)
        loop = asyncio.get_event_loop()

        # Ejecutar Whisper en un thread separado para no bloquear el event loop
        segments_iter, info = await loop.run_in_executor(
            None,
            lambda: _models.whisper.transcribe(audio_stream, beam_size=WHISPER_BEAM_SIZE),
        )

        await _ws_send(websocket, {
            "type": "language",
            "language": info.language,
            "probability": round(info.language_probability, 4),
        })

        full_parts: list[str] = []
        for segment in segments_iter:
            text = segment.text.strip()
            if not text:
                continue
            full_parts.append(text)
            await _ws_send(websocket, {
                "type": "segment",
                "text": text,
                "start": round(segment.start, 2),
                "end": round(segment.end, 2),
            })
            await asyncio.sleep(0)  # ceder control al event loop entre segmentos

        full_text = " ".join(full_parts)
        await _ws_send(websocket, {"type": "done", "full_text": full_text})
        logger.info("WS /ws/transcribe — completado: %d chars, idioma=%s",
                    len(full_text), info.language)

    except WebSocketDisconnect:
        logger.info("WS /ws/transcribe — cliente desconectado antes de finalizar")
    except Exception as exc:
        logger.exception("WS /ws/transcribe — error inesperado")
        try:
            await _ws_send(websocket, {"type": "error", "detail": str(exc)})
        except Exception:
            pass
    finally:
        try:
            await websocket.close()
        except Exception:
            pass

# ---------------------------------------------------------------------------
# WS /ws/analyze-pdf — Progreso de análisis de PDF página a página
#
# Flujo:
#   1. Cliente conecta por WebSocket
#   2. Cliente envía el PDF como mensaje BINARIO (único envío)
#   3. Servidor valida tamaño y abre el documento
#   4. Servidor emite { type:"page", page, total, text } por cada página
#   5. Servidor emite { type:"keywords", keywords:[...] }
#   6. Servidor emite { type:"done", title, full_text, pages_processed }
#   7. Servidor cierra la conexión
# ---------------------------------------------------------------------------

@app.websocket("/ws/analyze-pdf")
async def ws_analyze_pdf(websocket: WebSocket):
    await websocket.accept()
    logger.info("WS /ws/analyze-pdf — conexión abierta desde %s", websocket.client)

    try:
        pdf_bytes: bytes = await websocket.receive_bytes()

        if len(pdf_bytes) > MAX_FILE_SIZE_BYTES:
            await _ws_send(websocket, {
                "type": "error",
                "detail": f"PDF supera el límite de {MAX_FILE_SIZE_MB} MB.",
            })
            await websocket.close(code=1009)
            return

        doc = fitz.open(stream=pdf_bytes, filetype="pdf")
        total_pages = min(len(doc), PDF_MAX_PAGES)

        if total_pages == 0:
            await _ws_send(websocket, {
                "type": "error",
                "detail": "El PDF no contiene páginas legibles.",
            })
            await websocket.close()
            return

        all_text_parts: list[str] = []
        for i, page in enumerate(list(doc)[:PDF_MAX_PAGES]):
            page_text = page.get_text()
            all_text_parts.append(page_text)
            await _ws_send(websocket, {
                "type": "page",
                "page": i + 1,
                "total": total_pages,
                "text": page_text,
            })
            await asyncio.sleep(0)  # no bloquear entre páginas

        full_text = "".join(all_text_parts)

        # spaCy puede ser lento: ejecutar en executor
        loop = asyncio.get_event_loop()
        doc_nlp = await loop.run_in_executor(
            None, lambda: _models.nlp(full_text[:PDF_NLP_CHAR_LIMIT])
        )
        keywords = _extract_keywords(doc_nlp)

        lines = [ln.strip() for ln in full_text.splitlines() if len(ln.strip()) > 5]
        title = lines[0] if lines else "Documento sin título"

        await _ws_send(websocket, {"type": "keywords", "keywords": keywords})
        await _ws_send(websocket, {
            "type": "done",
            "title": title,
            "full_text": full_text,
            "pages_processed": total_pages,
        })
        logger.info("WS /ws/analyze-pdf — '%s', %d páginas procesadas", title, total_pages)

    except WebSocketDisconnect:
        logger.info("WS /ws/analyze-pdf — cliente desconectado antes de finalizar")
    except Exception as exc:
        logger.exception("WS /ws/analyze-pdf — error inesperado")
        try:
            await _ws_send(websocket, {"type": "error", "detail": str(exc)})
        except Exception:
            pass
    finally:
        try:
            await websocket.close()
        except Exception:
            pass

# ---------------------------------------------------------------------------
# HTTP /transcribe-audio — Compatibilidad (respuesta completa, sin streaming)
# ---------------------------------------------------------------------------

@app.post("/transcribe-audio", tags=["Audio"], response_model=TranscriptionResponse,
          summary="Transcripción completa. Para tiempo real usar /ws/transcribe")
async def transcribe_audio(file: UploadFile = File(...)):
    if file.content_type and file.content_type not in ALLOWED_AUDIO_TYPES:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"Tipo de archivo no soportado: {file.content_type}",
        )
    try:
        audio_bytes = await file.read()
        _validate_file_size(audio_bytes, "audio")
        audio_stream = io.BytesIO(audio_bytes)

        loop = asyncio.get_event_loop()
        segments_iter, info = await loop.run_in_executor(
            None,
            lambda: _models.whisper.transcribe(audio_stream, beam_size=WHISPER_BEAM_SIZE),
        )
        text = " ".join(s.text for s in segments_iter).strip()

        logger.info("HTTP /transcribe-audio — %d chars, idioma=%s", len(text), info.language)
        return TranscriptionResponse(transcription=text, language=info.language)

    except HTTPException:
        raise
    except Exception as exc:
        logger.exception("Error en /transcribe-audio")
        raise HTTPException(status_code=500, detail="Error interno al transcribir.") from exc

# ---------------------------------------------------------------------------
# HTTP /analyze-pdf — Compatibilidad (respuesta completa, sin streaming)
# ---------------------------------------------------------------------------

@app.post("/analyze-pdf", tags=["PDF"], response_model=PdfAnalysisResponse,
          summary="Análisis de PDF completo. Para progreso en tiempo real usar /ws/analyze-pdf")
async def analyze_pdf(file: UploadFile = File(...)):
    if file.content_type and file.content_type != "application/pdf":
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail="Solo se aceptan archivos PDF.",
        )
    try:
        content = await file.read()
        _validate_file_size(content, "PDF")

        doc = fitz.open(stream=content, filetype="pdf")
        pages = list(doc)[:PDF_MAX_PAGES]
        full_text = "".join(p.get_text() for p in pages)

        if not full_text.strip():
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="El PDF no contiene texto extraíble.",
            )

        loop = asyncio.get_event_loop()
        doc_nlp = await loop.run_in_executor(
            None, lambda: _models.nlp(full_text[:PDF_NLP_CHAR_LIMIT])
        )
        keywords = _extract_keywords(doc_nlp)
        lines = [ln.strip() for ln in full_text.splitlines() if len(ln.strip()) > 5]
        title = lines[0] if lines else "Documento sin título"
        pages_processed = min(len(doc), PDF_MAX_PAGES)

        logger.info("HTTP /analyze-pdf — '%s', %d páginas", title, pages_processed)
        return PdfAnalysisResponse(
            title=title, keywords=keywords,
            full_text=full_text, pages_processed=pages_processed,
        )

    except HTTPException:
        raise
    except Exception as exc:
        logger.exception("Error en /analyze-pdf")
        raise HTTPException(status_code=500, detail="Error interno al procesar el PDF.") from exc

# ---------------------------------------------------------------------------
# HTTP /similarity — Respuesta instantánea, no requiere WebSocket
# ---------------------------------------------------------------------------

@app.post("/similarity", tags=["Texto"], response_model=SimilarityResponse,
          summary="Similitud semántica entre dos textos")
def similarity(body: SimilarityRequest):
    try:
        score = _calculate_semantic_similarity(body.text1, body.text2)
        logger.info("Similitud: %.4f", score)
        return SimilarityResponse(
            similarity=round(score, 4),
            interpretation=_interpret_similarity(score),
        )
    except Exception as exc:
        logger.exception("Error en /similarity")
        raise HTTPException(status_code=500, detail="Error interno al calcular similitud.") from exc