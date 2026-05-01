# 🧠 VoxClass AI - Sistema de Análisis de Clases con IA

VoxClass AI es una plataforma diseñada para capturar audio de clases presenciales, procesarlo mediante inteligencia artificial y generar métricas pedagógicas. La arquitectura se basa en un monorepo que integra un backend web robusto en PHP (Laravel), un motor de IA de alto rendimiento en Python (FastAPI) y contenedores administrados con Docker.

## 🚀 Características Principales

- **Transcripción en Tiempo Real (Streaming)**: Uso de WebSockets (`/ws/transcribe`) para enviar audio fragmentado procesándolo con modelos *Faster-Whisper* en el backend de Python sin bloquear la interfaz.
- **Análisis de PDFs Interactivo**: Procesamiento asíncrono de documentos de texto vía WebSockets (`/ws/analyze-pdf`), con extracción instantánea de palabras clave mediante procesamiento de lenguaje natural (*spaCy*).
- **Similitud Semántica y Coherencia**: Evaluación de la coherencia pedagógica entre textos usando redes neuronales (*sentence-transformers*), expuesto a través de peticiones HTTP en Laravel.
- **Arquitectura de Proxy Híbrido**: Uso de Nginx como proxy inverso inteligente. Redirige el tráfico de streaming (`/ws/`) directo al motor de IA para máxima latencia baja, mientras delega las rutas HTTP seguras (`/ai/`) a Laravel para preprocesamiento.

---

## 🏗 Arquitectura del Sistema

El proyecto opera bajo un modelo de **monolito modularizado en contenedores**:

1. **Servidor Nginx (`vox-nginx`)**: Puerta de entrada en el puerto 8000. Gestiona el enrutamiento principal.
2. **Backend Web (`vox-laravel`)**: Construido con Laravel y Blade. Gestiona las interfaces (frontend), seguridad y orquesta las peticiones tradicionales (`Http::post`) hacia la IA utilizando el servicio interno `AIService`.
3. **Motor de Inteligencia Artificial (`vox-ai`)**: Implementado en FastAPI. Levanta los modelos de IA localmente en el puerto 8000 interno y maneja todo el cómputo pesado sin bloquear la web.
4. **Base de Datos (`vox-db`)**: PostgreSQL dedicado a persistir métricas, reportes y usuarios institucionales.

---

## 💻 Stack Tecnológico

| Componente | Tecnologías |
| :--- | :--- |
| **Frontend & Backend Base** | PHP 8+, Laravel 11, Blade Templates, HTML5/CSS3 |
| **Motor de IA** | Python 3.11, FastAPI, Faster-Whisper, spaCy, Sentence-Transformers |
| **Infraestructura** | Docker, Docker Compose, Nginx, PostgreSQL |
| **Red** | REST APIs, WebSockets bidireccionales |

---

## 🛠 Instalación y Configuración (Entorno Local)

### Prerrequisitos
- [Docker](https://docs.docker.com/get-docker/) y [Docker Compose](https://docs.docker.com/compose/install/) instalados en tu sistema.

### Pasos para levantar el entorno

1. **Clonar el repositorio.**
2. **Configurar Entornos:**
   Debes preparar los `.env` de cada servicio. (Nota: Los archivos `.env` están excluidos del control de versiones por seguridad).
   - En la carpeta `/backend-laravel`, copia el archivo de ejemplo: `cp .env.example .env`
3. **Desplegar la Infraestructura:**
   ```bash
   cd infrastructure
   docker compose up -d --build
   ```
4. **Accesos:**
   - **Plataforma Web (Laravel)**: `http://localhost:8000`
   - **Conexión Directa a IA (Opcional)**: `http://localhost:8001`

---

## 🔐 Privacidad y Datos Sensibles

Este repositorio público está protegido por una directiva estricta a nivel raíz (`.gitignore`):
- **Cero Secretos**: Se ignoran todos los archivos `.env`, llaves privadas (`*.key`, `*.pem`) y bases de datos locales (`*.sqlite`).
- **Bloqueo de Datos Locales**: Los volúmenes persistentes de Docker (como la información de PostgreSQL en `/infrastructure/docker/postgres/data/`) nunca se suben al repositorio.
- **Limpieza**: Exclusión de carpetas pesadas de descargas de modelos de IA, librerías (`vendor`, `node_modules`, `venv`) y cachés del sistema operativo (`.DS_Store`).

---

## 📂 Estructura del Monorepo

```text
VoxClass_v1.0.0.5/
├── ai-service/          # Motor Python (FastAPI, Modelos Whisper/NLP, Utils)
├── backend-laravel/     # Aplicación Laravel (Rutas web, Controladores, Vistas Blade)
├── edge-device/         # (En desarrollo) Scripts de captura de hardware para aulas
├── infrastructure/      # Orquestación (docker-compose.yml, Nginx config, Dockerfiles)
├── storage/             # Almacenamiento temporal para pruebas
└── README.md            # Documentación del proyecto
```