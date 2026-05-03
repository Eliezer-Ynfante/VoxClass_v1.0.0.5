<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoxClass | Sesión Integrada en Vivo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --bg-color: #0f172a;
            --panel-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --border: #334155;
            --glass: rgba(30, 41, 59, 0.7);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Layout */
        .sidebar {
            width: 350px;
            background: var(--panel-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
            gap: 20px;
            overflow: hidden;
        }

        /* Typography */
        h1, h2, h3, h4 { margin: 0 0 15px 0; font-weight: 600; }
        h1 { font-size: 1.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        p { margin: 0 0 10px 0; color: var(--text-muted); font-size: 0.9rem; }

        /* Controls */
        .card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: var(--bg-color);
            border: 1px dashed var(--border);
            border-radius: 8px;
            color: var(--text-muted);
            cursor: pointer;
        }

        button {
            width: 100%;
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        button:hover { background: var(--accent-hover); transform: translateY(-1px); }
        button:disabled { background: var(--border); color: var(--text-muted); cursor: not-allowed; transform: none; }
        button.danger { background: var(--danger); }
        button.danger:hover { background: #dc2626; }

        /* PDF Progress */
        .progress-bar-container { width: 100%; height: 6px; background: var(--bg-color); border-radius: 3px; margin-top: 10px; overflow: hidden; display: none; }
        .progress-bar { height: 100%; background: var(--success); width: 0%; transition: width 0.3s ease; }

        /* Keywords */
        .keyword-badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            margin: 0 5px 5px 0;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        /* Transcription Area */
        .transcription-box {
            flex: 1;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            overflow-y: auto;
            font-size: 1.05rem;
            line-height: 1.6;
        }
        .transcription-box span.chunk { color: var(--text-main); }
        .transcription-box span.pending { color: var(--text-muted); font-style: italic; }

        /* Metrics Strip */
        .metrics-strip {
            display: flex;
            gap: 15px;
        }
        .metric-card {
            flex: 1;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .metric-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .metric-value { font-size: 1.5rem; font-weight: bold; color: var(--success); margin-top: 5px; }

        /* Console */
        .console {
            height: 200px;
            background: #020617;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #38bdf8;
            overflow-y: auto;
        }
        .console p { margin: 0 0 5px 0; }
        .log-error { color: var(--danger); }
        .log-success { color: var(--success); }
        .log-warn { color: #fbbf24; }

        /* Animations */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .recording-indicator { display: inline-block; width: 10px; height: 10px; background: var(--danger); border-radius: 50%; margin-right: 8px; animation: pulse 1.5s infinite; display: none; }

    </style>
</head>
<body>

    <!-- SIDEBAR: Setup & Controles -->
    <div class="sidebar">
        <h1>VoxClass Live</h1>
        
        <!-- Módulo PDF -->
        <div class="card" id="pdf-section">
            <h3>1. Material de Clase (PDF)</h3>
            <p>Sube el contenido base para evaluar la coherencia.</p>
            <input type="file" id="pdfFile" accept="application/pdf">
            <button id="btnPdf" onclick="analyzePdf()">Analizar Documento</button>
            
            <div class="progress-bar-container" id="pdf-progress-cont">
                <div class="progress-bar" id="pdf-progress"></div>
            </div>
            
            <div id="pdf-results" style="display: none; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                <p><strong>Tema:</strong> <span id="pdf-title" style="color:var(--text-main)">-</span></p>
                <div id="pdf-keywords" style="margin-top: 10px;"></div>
            </div>
        </div>

        <!-- Módulo Grabación -->
        <div class="card" style="opacity: 0.5; pointer-events: none;" id="recording-section">
            <h3>2. Transcripción en Vivo</h3>
            <p>Inicia la grabación de la clase por micrófono. El audio se procesará en fragmentos iterativos.</p>
            <button id="btnRecord" onclick="toggleRecording()">
                <span class="recording-indicator" id="rec-dot"></span>
                <span id="btnRecordText">Iniciar Clase</span>
            </button>
        </div>

        <!-- Métrica: Similitud -->
        <div class="metric-card" style="margin-top: auto;">
            <span class="metric-label">Coherencia Pedagógica</span>
            <span class="metric-value" id="sim-score">--%</span>
            <p id="sim-interp" style="margin-top: 5px; font-size: 0.8rem;">Esperando datos...</p>
        </div>
    </div>

    <!-- MAIN CONTENT: Transcripción y Logs -->
    <div class="main-content">
        
        <h3>Transcripción de la Clase</h3>
        <div class="transcription-box" id="transcription-box">
            <span class="pending" id="placeholder-text">Esperando a que inicie la clase...</span>
        </div>

        <div class="console" id="console">
            <p style="color: var(--text-muted)">// Consola de Sistema Inicializada</p>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const WS_URL = 'ws://' + window.location.host;

        // Estado Global
        let pdfText = "";
        let fullTranscription = "";
        let isRecording = false;
        let mediaRecorder = null;
        let audioStream = null;
        let chunkInterval = null;
        
        let currentModuleId = null;
        let currentSessionId = null;
        let lastSimilarityData = null;
        let extractedKeywords = [];

        // Utilidades UI
        function log(msg, type = "info") {
            const cons = document.getElementById('console');
            const p = document.createElement('p');
            p.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
            if(type === 'error') p.className = 'log-error';
            if(type === 'success') p.className = 'log-success';
            if(type === 'warn') p.className = 'log-warn';
            cons.appendChild(p);
            cons.scrollTop = cons.scrollHeight;
        }

        // ---------------------------------------------------------
        // 1. ANÁLISIS DE PDF
        // ---------------------------------------------------------
        function analyzePdf() {
            const fileInput = document.getElementById('pdfFile');
            if (!fileInput.files[0]) return alert("Selecciona un PDF primero");

            const btn = document.getElementById('btnPdf');
            const progressCont = document.getElementById('pdf-progress-cont');
            const progressBar = document.getElementById('pdf-progress');
            const resultsDiv = document.getElementById('pdf-results');
            
            btn.disabled = true;
            progressCont.style.display = 'block';
            log("Conectando con motor IA para procesar PDF...");

            const socket = new WebSocket(`${WS_URL}/ws/analyze-pdf`);

            socket.onopen = () => {
                log("Enviando PDF...", "success");
                const reader = new FileReader();
                reader.onload = () => socket.send(reader.result);
                reader.readAsArrayBuffer(fileInput.files[0]);
            };

            socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'page') {
                    progressBar.style.width = `${(data.page / data.total) * 100}%`;
                }
                
                if (data.type === 'keywords') {
                    extractedKeywords = data.keywords; // Guardar temporalmente
                    const kwDiv = document.getElementById('pdf-keywords');
                    kwDiv.innerHTML = "";
                    data.keywords.forEach(kw => {
                        kwDiv.innerHTML += `<span class="keyword-badge">${kw}</span>`;
                    });
                }

                if (data.type === 'done') {
                    pdfText = data.full_text; // Guardar texto completo para similitud
                    document.getElementById('pdf-title').innerText = data.title;
                    resultsDiv.style.display = 'block';
                    
                    // -- NUEVO: GUARDAR EN BASE DE DATOS --
                    const formData = new FormData();
                    formData.append('title', data.title);
                    formData.append('expected_content', data.full_text);
                    extractedKeywords.forEach(kw => formData.append('keywords[]', kw));
                    formData.append('pdf_file', fileInput.files[0]);

                    fetch('/modules', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: formData
                    }).then(r => r.json()).then(res => {
                        currentModuleId = res.module_id;
                        log("PDF analizado y guardado en BD. Listo para grabar.", "success");
                        btn.innerText = "PDF Cargado";
                        
                        // Habilitar la sección de grabación
                        const recSection = document.getElementById('recording-section');
                        recSection.style.opacity = '1';
                        recSection.style.pointerEvents = 'auto';
                        socket.close();
                    }).catch(e => {
                        log("Error guardando PDF en BD", "error");
                        btn.disabled = false;
                        socket.close();
                    });
                }

                if (data.type === 'error') {
                    log(`Error PDF: ${data.detail}`, "error");
                    btn.disabled = false;
                }
            };

            socket.onerror = () => log("Error de red procesando PDF", "error");
        }

        // ---------------------------------------------------------
        // 2. GRABACIÓN EN CHUNKS ITERATIVOS
        // ---------------------------------------------------------
        async function toggleRecording() {
            const btn = document.getElementById('btnRecord');
            const dot = document.getElementById('rec-dot');
            const btnText = document.getElementById('btnRecordText');

            if (!isRecording) {
                try {
                    audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    isRecording = true;
                    btn.classList.add('danger');
                    dot.style.display = 'inline-block';
                    btnText.innerText = "Detener Clase";
                    document.getElementById('placeholder-text').style.display = 'none';
                    log("🎤 Grabación iniciada. Capturando fragmentos de 10s...", "warn");
                    
                    // -- NUEVO: INICIAR SESIÓN EN BASE DE DATOS --
                    fetch('/sessions/start', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ learning_module_id: currentModuleId })
                    }).then(r => r.json()).then(res => {
                        currentSessionId = res.session_id;
                        log(`Sesión #${currentSessionId} creada en BD.`);
                        // Iniciar bucle de grabación
                        recordNextChunk();
                    }).catch(e => {
                        log("Error creando sesión en BD", "error");
                        recordNextChunk(); // Fallback en caso de error
                    });
                } catch (err) {
                    log("Error accediendo al micrófono: " + err.message, "error");
                }
            } else {
                isRecording = false;
                if (audioStream) audioStream.getTracks().forEach(track => track.stop());
                btn.classList.remove('danger');
                dot.style.display = 'none';
                btnText.innerText = "Clase Finalizada";
                btn.disabled = true;
                log("⏹️ Clase finalizada.", "warn");
                
                // -- NUEVO: FINALIZAR SESIÓN EN BASE DE DATOS --
                if (currentSessionId) {
                    fetch(`/sessions/${currentSessionId}/finalize`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            transcription: fullTranscription,
                            similarity_score: lastSimilarityData ? lastSimilarityData.similarity : 0,
                            interpretation: lastSimilarityData ? lastSimilarityData.interpretation : 'N/A'
                        })
                    }).then(r => r.json()).then(res => {
                        log(res.message, "success");
                    }).catch(e => log("Error finalizando sesión en BD", "error"));
                }
            }
        }

        // Graba exactamente un bloque temporal y se reinicia
        function recordNextChunk() {
            if (!isRecording) return;

            mediaRecorder = new MediaRecorder(audioStream);
            const audioChunks = [];

            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) audioChunks.push(event.data);
            };

            mediaRecorder.onstop = () => {
                if (audioChunks.length > 0) {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    sendAudioToAI(audioBlob);
                }
                // Si la clase sigue activa, grabar el siguiente bloque inmediatamente
                if (isRecording) recordNextChunk();
            };

            mediaRecorder.start();

            // Detener el recorder a los 10 segundos para forzar la creación del Blob
            setTimeout(() => {
                if (mediaRecorder.state === 'recording') mediaRecorder.stop();
            }, 10000);
        }

        // Enviar chunk por WebSocket para transcripción
        function sendAudioToAI(blob) {
            log(`Enviando fragmento de audio (${(blob.size/1024).toFixed(1)} KB)...`);
            const socket = new WebSocket(`${WS_URL}/ws/transcribe`);
            
            // UI Element para este chunk
            const tBox = document.getElementById('transcription-box');
            const chunkSpan = document.createElement('span');
            chunkSpan.className = 'chunk';
            tBox.appendChild(chunkSpan);

            socket.onopen = () => socket.send(blob);

            socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'segment') {
                    chunkSpan.innerText += ` ${data.text}`;
                    tBox.scrollTop = tBox.scrollHeight;
                }

                if (data.type === 'done') {
                    fullTranscription += ` ${data.full_text}`;
                    log("Fragmento transcrito con éxito.", "success");
                    socket.close();
                    
                    // Gatillar cálculo de similitud si hay texto
                    if(fullTranscription.trim().length > 20 && pdfText.length > 0) {
                        calculateSimilarity();
                    }
                }

                if (data.type === 'error') {
                    log(`Error transcribiendo: ${data.detail}`, "error");
                }
            };

            socket.onerror = () => log("Fallo de red en envío de audio", "error");
        }

        // ---------------------------------------------------------
        // 3. SIMILITUD EN TIEMPO REAL (HTTP)
        // ---------------------------------------------------------
        let isCalculating = false;

        async function calculateSimilarity() {
            // Evitar múltiples llamadas concurrentes
            if (isCalculating) return;
            isCalculating = true;

            try {
                const response = await fetch('/ai/similarity', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        text1: fullTranscription.slice(-3000), // Enviar solo últimos 3000 chars para no saturar 
                        text2: pdfText.slice(0, 3000) 
                    })
                });

                if(response.ok) {
                    const data = await response.json();
                    lastSimilarityData = data;
                    const scoreObj = document.getElementById('sim-score');
                    const interpObj = document.getElementById('sim-interp');
                    
                    const percent = (data.similarity * 100).toFixed(1);
                    scoreObj.innerText = `${percent}%`;
                    interpObj.innerText = data.interpretation;
                    
                    // Colores según puntuación
                    if(data.similarity >= 0.8) scoreObj.style.color = 'var(--success)';
                    else if(data.similarity >= 0.5) scoreObj.style.color = '#fbbf24';
                    else scoreObj.style.color = 'var(--danger)';
                    
                    log(`Similitud actualizada: ${percent}%`);
                }
            } catch (err) {
                log("Fallo al calcular similitud: " + err.message, "error");
            } finally {
                isCalculating = false;
            }
        }
    </script>
</body>
</html>
