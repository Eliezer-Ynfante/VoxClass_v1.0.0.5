# 🧠 PROMPT PARA DESARROLLO DE MVP - SISTEMA DE ANÁLISIS DE CLASES CON IA

## 🎯 CONTEXTO GENERAL

Diseñar y desarrollar un sistema completo (MVP) capaz de capturar audio de clases presenciales mediante hardware en aula, procesarlo con inteligencia artificial y generar métricas pedagógicas en menos de 10 minutos, garantizando privacidad y eficiencia.

El sistema debe ser escalable, modular y preparado para evolucionar a microservicios.

---

# 🧩 1. ARQUITECTURA GENERAL

El sistema debe dividirse en 4 bloques principales:

## A. EDGE (DISPOSITIVO EN AULA)
- Dispositivo tipo Raspberry Pi o mini-PC equivalente
- Botón físico para iniciar grabación manual
- Micrófono ambiental para captura de audio
- Indicador visual de grabación (transparencia)

### Responsabilidades:
- Captura de audio
- Segmentación básica (chunks)
- Compresión de audio
- Envío al backend

---

## B. BACKEND EN LA NUBE
- API principal
- Gestión de usuarios y sesiones
- Orquestación de procesamiento
- Recepción de audio

---

## C. SERVICIOS DE IA
- Transcripción de audio (ASR)
- Procesamiento de lenguaje natural (NLP)
- Análisis pedagógico

---

## D. FRONTEND + ANALÍTICA
- Panel web para docentes
- Visualización de métricas
- Reportes institucionales agregados

⚠️ IMPORTANTE: El frontend debe implementarse en PHP (NO React).

---

# ⚙️ 2. MODELO DE ARQUITECTURA BACKEND

## Enfoque:

- Empezar con **monolito modular**
- Diseñar con posibilidad de migrar a microservicios

## Componentes:

- API Gateway (entrada principal)
- Servicio de audio
- Servicio de transcripción
- Servicio de análisis
- Servicio de reportes

---

# 💻 3. STACK TECNOLÓGICO

## Backend principal
- PHP 8+
- Framework: Laravel  

### Responsabilidades:
- API REST
- Autenticación
- Gestión de usuarios
- Panel web
- Reportes

---

## Servicio de IA
- Python 3
- Framework: FastAPI o Flask

### Librerías:
- PyTorch  
- Transformers  
- Whisper  

### Responsabilidades:
- Transcripción
- Extracción de conceptos
- Análisis pedagógico

---

## (Opcional)
- Node.js para WebSockets / tiempo real

---

# 🗄️ 4. BASE DE DATOS

## 🔹 Base principal
- PostgreSQL  

### Almacena:
- Usuarios
- Clases
- Transcripciones
- Métricas
- Reportes

---

## 🔹 Búsqueda y análisis
- Elasticsearch  

### Uso:
- Búsqueda en texto
- Análisis semántico rápido

---

## 🔹 Cache y colas
- Redis  

### Uso:
- Cache
- Colas de procesamiento

---

# 🔄 5. SISTEMA DE PROCESAMIENTO

## Requerimiento crítico:
Procesar resultados en menos de 10 minutos.

## Herramientas:
- RabbitMQ o
- Apache Kafka  

## Flujo:

1. Audio subido al backend
2. División en chunks
3. Envío a cola
4. Workers procesan en paralelo:
   - Transcripción
   - NLP
   - Métricas

---

# 🎤 6. TRANSCRIPCIÓN (ASR)

## Opción principal:
- Whisper  

## Alternativas:
- Google Speech-to-Text
- AWS Transcribe

---

# 🧩 7. NLP (PROCESAMIENTO DE TEXTO)

## Herramientas:
- spaCy  
- Transformers  

## Funcionalidades:
- Extracción de temas clave
- Clasificación de contenido
- Resumen automático
- Detección de estructura

---

# 📊 8. ANÁLISIS PEDAGÓGICO

## Funcionalidades:

### 1. Diarización de voz
- Separar docente vs estudiantes

### 2. Métricas:
- % tiempo de habla
- Número de preguntas
- Interacción

### 3. Estructura de clase:
- Inicio
- Desarrollo
- Cierre

### 4. Coherencia:
- Comparación con sílabo
- Uso de embeddings y similitud semántica

---

# 🌐 9. FRONTEND (PHP)

## Stack:
- Laravel Blade
- HTML + CSS (Bootstrap o Tailwind)

## Funcionalidades:

### Docente:
- Ver métricas
- Ver transcripción
- Historial de clases

### Institución:
- Ver datos agregados (sin datos individuales)

---

# 🔐 10. PRIVACIDAD Y SEGURIDAD

## Requisitos:

- Eliminación automática del audio (cron jobs)
- Solo almacenar texto y métricas
- HTTPS obligatorio
- Autenticación y roles
- Separación de datos:
  - Docente: acceso individual
  - Institución: datos agregados

---

# ⚡ 11. OPTIMIZACIÓN (OBJETIVO: < 10 MIN)

## Estrategias:

- Procesamiento paralelo
- Segmentación de audio
- Uso de colas
- Workers en Python
- GPU para Whisper (opcional)

## Pipeline:

captura → subida → transcripción parcial → NLP → métricas → reporte

---

# 🐳 12. INFRAESTRUCTURA

## Contenedores:
- Docker

## Servicios:
- app (Laravel)
- ia-service (Python)
- postgres
- redis
- cola (RabbitMQ/Kafka)

## Cloud:
- AWS / GCP / VPS

---

# 🧱 13. STACK FINAL

## Backend:
- PHP (Laravel)
- Python (FastAPI / Flask)

## IA:
- Whisper
- Transformers

## Base de datos:
- PostgreSQL
- Redis
- Elasticsearch

## Infraestructura:
- Docker
- Cloud provider

---

# 🚀 14. FLUJO FINAL DEL SISTEMA

Botón físico → Grabación → Segmentación → Subida → Cola → IA → Métricas → Dashboard

---

# 📌 15. RESTRICCIONES DEL MVP

- No tiempo real (procesamiento post-clase)
- Sin diarización avanzada compleja (fase futura)
- UI simple en PHP
- IA básica pero funcional

---

# 🎯 OBJETIVO DEL DESARROLLO

Generar un MVP funcional, escalable y demostrable que valide:

- Captura de audio
- Transcripción automática
- Generación de métricas
- Visualización en web
- Cumplimiento de privacidad

---

# Estructura del proyecto
project-root/
│
├── 📁 edge-device/                      # Código para el dispositivo en aula
│   ├── capture/
│   │   ├── audio_capture.py            # Captura de audio desde micrófono
│   │   ├── button_listener.py          # Manejo del botón físico
│   │   └── indicator.py                # Control LED / indicador visual
│   │
│   ├── processing/
│   │   ├── chunker.py                  # Segmentación de audio
│   │   └── compressor.py               # Compresión de audio
│   │
│   ├── transport/
│   │   ├── uploader.py                 # Envío al backend
│   │   └── api_client.py
│   │
│   └── main.py                         # Orquestador principal
│
│
├── 📁 backend-laravel/                 # Backend + frontend (PHP)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ClassController.php
│   │   │   │   ├── UploadController.php
│   │   │   │   └── MetricsController.php
│   │   │   │
│   │   │   └── Middleware/
│   │   │
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── ClassSession.php
│   │   │   ├── Transcript.php
│   │   │   └── Metric.php
│   │   │
│   │   ├── Services/
│   │   │   ├── AudioService.php       # Manejo de archivos de audio
│   │   │   ├── QueueService.php       # Envío a cola
│   │   │   └── ReportService.php
│   │   │
│   │   └── Jobs/
│   │       ├── ProcessAudioJob.php    # Dispatch a Python service
│   │
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   │
│   ├── resources/
│   │   ├── views/                     # Frontend en Blade (PHP)
│   │   │   ├── dashboard.blade.php
│   │   │   ├── metrics.blade.php
│   │   │   └── transcripts.blade.php
│   │   │
│   │   ├── css/
│   │   └── js/
│   │
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── create_users_table.php
│   │   │   ├── create_classes_table.php
│   │   │   ├── create_transcripts_table.php
│   │   │   └── create_metrics_table.php
│   │   │
│   │   └── seeders/
│   │
│   └── config/
│
│
├── 📁 ai-service/                      # Servicio Python (IA)
│   ├── app/
│   │   ├── main.py                    # API (FastAPI / Flask)
│   │   │
│   │   ├── routes/
│   │   │   └── process_audio.py       # Endpoint principal
│   │   │
│   │   ├── services/
│   │   │   ├── transcription.py       # Whisper
│   │   │   ├── nlp.py                 # NLP (spaCy / Transformers)
│   │   │   ├── analysis.py            # Métricas pedagógicas
│   │   │   └── diarization.py         # (opcional)
│   │   │
│   │   ├── utils/
│   │   │   ├── audio_utils.py
│   │   │   └── text_utils.py
│   │   │
│   │   └── workers/
│   │       └── worker.py              # Procesa colas
│   │
│   ├── models/                        # Modelos IA descargados/cache
│   │
│   ├── requirements.txt
│   └── Dockerfile
│
│
├── 📁 infrastructure/                 # Infraestructura y despliegue
│   ├── docker/
│   │   ├── nginx/
│   │   │   └── default.conf
│   │   │
│   │   ├── php/
│   │   │   └── Dockerfile
│   │   │
│   │   ├── python/
│   │   │   └── Dockerfile
│   │   │
│   │   └── postgres/
│   │
│   ├── docker-compose.yml
│   └── .env
│
│
├── 📁 storage/                        # Archivos temporales
│   ├── audio_uploads/
│   ├── processed/
│   └── logs/
│
│
├── 📁 docs/                           # Documentación
│   ├── architecture.md
│   ├── api-spec.md
│   └── setup.md
│
│
└── README.md