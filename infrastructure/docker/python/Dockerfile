FROM python:3.11.9-slim

ENV PIP_DEFAULT_TIMEOUT=1000 \
    PIP_NO_CACHE_DIR=1 \
    PYTHONUNBUFFERED=1

# Dependencias del sistema (ffmpeg es vital para Whisper)
RUN apt-get update && apt-get install -y \
    ffmpeg \
    portaudio19-dev \
    gcc \
    g++ \
    python3-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .

RUN pip install --upgrade pip setuptools wheel
RUN pip install -r requirements.txt --retries 10 --timeout 1000

# Descarga de modelo spaCy
RUN python -m spacy download es_core_news_sm

COPY . .

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]