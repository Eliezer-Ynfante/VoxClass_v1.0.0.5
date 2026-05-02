<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoxClass | Sesión de IA Integrada</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --success: #27ae60; --bg: #f4f7f6; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--primary); margin: 0; padding: 20px; }
        .dashboard { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .full-width { grid-column: 1 / -1; }
        h2 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 18px; }
        
        /* Audio & PDF UI */
        .drop-zone { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 15px; transition: 0.3s; }
        .drop-zone:hover { border-color: var(--accent); background: #f0f7ff; }
        .btn { background: var(--accent); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }

        /* Real-time transcription box */
        #transcription-box { background: #2d3436; color: #dfe6e9; padding: 15px; border-radius: 8px; height: 200px; overflow-y: auto; font-family: 'Courier New', monospace; line-height: 1.6; }
        
        /* Similarity Gauge */
        .gauge-container { text-align: center; padding: 20px; }
        .gauge-value { font-size: 48px; font-weight: 800; color: var(--success); }
        .gauge-label { color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
        
        /* Keywords List */
        .tag-cloud { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .tag { background: #e1f5fe; color: #0288d1; padding: 5px 12px; border-radius: 20px; font-size: 12px; border: 1px solid #b3e5fc; }
        
        /* Status Indicator */
        .pulse { display: inline-block; width: 10px; height: 10px; background: #e74c3c; border-radius: 50%; margin-right: 8px; }
        .pulse.active { background: #2ecc71; box-shadow: 0 0 0 rgba(46, 204, 113, 0.4); animation: pulse-animation 2s infinite; }
        @keyframes pulse-animation { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); } }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="card full-width" style="display: flex; justify-content: space-between; align-items: center;">
        <h1 style="margin:0; font-size: 24px;">VoxClass Session v1.0</h1>
        <div id="connection-status"><span class="pulse" id="status-led"></span> <span id="status-text">Sistema Offline</span></div>
    </div>

    <div class="card">
        <h2>1. Material de Referencia (PDF)</h2>
        <div class="drop-zone">
            <input type="file" id="pdfFile" accept="application/pdf" style="display: none;">
            <label for="pdfFile" style="cursor: pointer;">
                <strong>Haz clic para cargar el PDF de la clase</strong>
                <p id="pdfName" style="font-size: 12px; color: #666;">Ningún archivo seleccionado</p>
            </label>
        </div>
        <button class="btn" id="btnAnalyzePdf" onclick="processPdf()">Analizar Contenido</button>
        
        <div id="pdf-results" style="margin-top:20px; display:none;">
            <div style="font-size: 14px; margin-bottom: 10px;"><strong>Título:</strong> <span id="pdfTitle">...</span></div>
            <div style="font-size: 14px;"><strong>Conceptos Clave:</strong></div>
            <div id="pdfTags" class="tag-cloud"></div>
        </div>
    </div>

    <div class="card">
        <h2>2. Audio en Tiempo Real (Streaming)</h2>
        <div class="drop-zone" style="border-style: solid; background: #fff;">
            <input type="file" id="audioFile" accept="audio/*">
            <p style="font-size: 11px; margin-top: 5px;">Sube el audio del profesor o sesión para iniciar.</p>
        </div>
        <button class="btn" id="btnStartAudio" onclick="processAudio()" disabled>Iniciar Transcripción</button>
        
        <div id="transcription-box" style="margin-top:15px;">
            <span style="color: #636e72;">Esperando flujo de audio...</span>
        </div>
    </div>

    <div class="card full-width">
        <div style="display: flex; gap: 30px; align-items: center;">
            <div class="gauge-container" style="flex: 1; border-right: 1px solid #eee;">
                <div class="gauge-label">Coherencia con el Material</div>
                <div class="gauge-value" id="simScore">0%</div>
                <div id="simInterpretation" style="font-size: 13px; color: #7f8c8d;">Pendiente de datos...</div>
            </div>
            
            <div style="flex: 2;">
                <h2>Análisis de Coincidencia Semántica</h2>
                <p style="font-size: 14px; color: #636e72;">
                    Este motor compara el búfer acumulado del audio con las palabras clave extraídas del PDF mediante 
                    <strong>Sentence-BERT</strong> en el backend.
                </p>
                <div id="analysis-log" style="font-size: 12px; color: #2ecc71; font-family: monospace;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables de Estado Global
    let pdfContext = "";
    let transcriptionBuffer = "";
    let isPdfReady = false;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // UI Elements
    const statusLed = document.getElementById('status-led');
    const statusText = document.getElementById('status-text');
    const audioBtn = document.getElementById('btnStartAudio');

    // 1. PROCESAR PDF
    async function processPdf() {
        const file = document.getElementById('pdfFile').files[0];
        if (!file) return alert("Selecciona un PDF primero");

        const btn = document.getElementById('btnAnalyzePdf');
        btn.disabled = true;
        statusLed.className = "pulse active";
        statusText.innerText = "Analizando PDF...";

        const socket = new WebSocket('ws://' + window.location.host + '/ws/analyze-pdf');

        socket.onopen = () => {
            const reader = new FileReader();
            reader.onload = () => socket.send(reader.result);
            reader.readAsArrayBuffer(file);
        };

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'keywords') {
                pdfContext = data.keywords.join(" ");
                renderTags(data.keywords);
            }
            if (data.type === 'done') {
                document.getElementById('pdfTitle').innerText = data.title;
                document.getElementById('pdf-results').style.display = "block";
                isPdfReady = true;
                audioBtn.disabled = false;
                statusText.innerText = "Material Listo. Esperando Audio.";
                btn.innerText = "PDF Procesado";
            }
        };
    }

    function renderTags(tags) {
        const container = document.getElementById('pdfTags');
        container.innerHTML = "";
        tags.forEach(t => {
            const span = document.createElement('span');
            span.className = "tag";
            span.innerText = t;
            container.appendChild(span);
        });
    }

    // 2. PROCESAR AUDIO (STREAMING)
    async function processAudio() {
        const file = document.getElementById('audioFile').files[0];
        if (!file) return alert("Selecciona un audio");

        const display = document.getElementById('transcription-box');
        display.innerText = "";
        statusText.innerText = "Transcribiendo en vivo...";
        
        const socket = new WebSocket('ws://' + window.location.host + '/ws/transcribe');

        socket.onopen = () => {
            const reader = new FileReader();
            reader.onload = () => socket.send(reader.result);
            reader.readAsArrayBuffer(file);
        };

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'segment') {
                transcriptionBuffer += " " + data.text;
                display.innerText = transcriptionBuffer;
                display.scrollTop = display.scrollHeight;
                
                // Ejecutar similitud cada vez que llega un segmento nuevo
                runIntegratedSimilarity();
            }
            if (data.type === 'done') {
                statusText.innerText = "Sesión Finalizada";
                statusLed.className = "pulse";
            }
        };
    }

    // 3. MOTOR DE SIMILITUD INTEGRADA
    async function runIntegratedSimilarity() {
        if (!isPdfReady || transcriptionBuffer.length < 20) return;

        try {
            const response = await fetch('/ai/similarity', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ 
                    text1: transcriptionBuffer, // Lo que se va escuchando
                    text2: pdfContext          // Lo que dice el PDF
                })
            });

            const data = await response.json();
            
            if (data.similarity !== undefined) {
                const score = (data.similarity * 100).toFixed(0);
                document.getElementById('simScore').innerText = score + "%";
                document.getElementById('simInterpretation').innerText = data.interpretation;
                
                const log = document.getElementById('analysis-log');
                log.innerText = `[${new Date().toLocaleTimeString()}] Sync check: OK`;
            }
        } catch (e) {
            console.error("Error en comparación integrada", e);
        }
    }

    // Listener para nombre de archivo PDF
    document.getElementById('pdfFile').onchange = function() {
        document.getElementById('pdfName').innerText = this.files[0].name;
    };
</script>

</body>
</html>