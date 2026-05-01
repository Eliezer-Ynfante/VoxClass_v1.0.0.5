<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoxClass | Panel de Pruebas IA</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .online { background: #d4edda; color: #155724; }
        .offline { background: #f8d7da; color: #721c24; }
        textarea, input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        button:hover { background: #2980b9; }
        button:disabled { background: #ccc; }
        #log-area, #transcription-result { background: #2d3436; color: #dfe6e9; padding: 15px; border-radius: 4px; font-family: monospace; height: 150px; overflow-y: auto; white-space: pre-wrap; margin-top: 10px; }
        .progress-container { width: 100%; background: #eee; height: 10px; border-radius: 5px; margin: 10px 0; display: none; }
        #progress-bar { width: 0%; height: 100%; background: #27ae60; border-radius: 5px; transition: 0.3s; }
    </style>
</head>
<body>

<div class="container">
    <h1>VoxClass v3.0.0 — Laboratorio de IA</h1>

    <div class="section">
        <h3>1. Transcripción de Audio (Whisper Streaming)</h3>
        <p>Selecciona un archivo .wav o .mp3 para ver la transcripción en tiempo real.</p>
        <input type="file" id="audioFile" accept="audio/*">
        <button id="btnTranscribe" onclick="testAudioWS()">Iniciar Streaming</button>
        
        <div id="transcription-result">Esperando audio...</div>
    </div>

    <div class="section">
        <h3>2. Análisis de PDF (Streaming por Páginas)</h3>
        <input type="file" id="pdfFile" accept="application/pdf">
        <button id="btnPdf" onclick="testPdfWS()">Analizar PDF</button>
        
        <div class="progress-container" id="pdfProgressCont">
            <div id="progress-bar"></div>
        </div>
        <div id="pdf-status">Listo para procesar.</div>

        <div id="pdf-results-box" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-left: 4px solid #27ae60; border-radius: 4px;">
            <h4 style="margin-top: 0; color: #2c3e50;">Resultados del Análisis:</h4>
            <p><strong>Título:</strong> <span id="res-title">-</span></p>
            <p><strong>Páginas procesadas:</strong> <span id="res-pages">-</span></p>
            <p><strong>Total de palabras clave (Keywords):</strong> <span id="res-keywords-count">0</span></p>
            <div style="margin-top: 10px;">
                <strong>Palabras clave extraídas:</strong>
                <div id="res-keywords-list" style="margin-top: 5px; display: flex; flex-wrap: wrap; gap: 5px;"></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>3. Comparación Semántica (HTTP)</h3>
        <textarea id="text1" placeholder="Texto del alumno o audio transcripto..."></textarea>
        <textarea id="text2" placeholder="Texto base del PDF o material oficial..."></textarea>
        <button onclick="testSimilarity()">Calcular Similitud</button>
        <div id="sim-result" style="margin-top:10px; font-weight:bold;"></div>
    </div>

    <h3>Consola de Sistema</h3>
    <div id="log-area">Iniciado...</div>
</div>

<script>
    const logArea = document.getElementById('log-area');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function sysLog(msg) {
        const time = new Date().toLocaleTimeString();
        logArea.innerHTML += `[${time}] ${msg}\n`;
        logArea.scrollTop = logArea.scrollHeight;
    }

    // --- TEST AUDIO WEBSOCKET ---
    async function testAudioWS() {
        const fileInput = document.getElementById('audioFile');
        const display = document.getElementById('transcription-result');
        const btn = document.getElementById('btnTranscribe');

        if (!fileInput.files[0]) return alert("Selecciona un audio primero");

        display.innerText = ""; // Limpiar
        btn.disabled = true;
        sysLog("Abriendo WebSocket para audio...");

        // Conexión directa al puerto de la IA (mapeado en Docker)
        const socket = new WebSocket('ws://localhost:8001/ws/transcribe');

        socket.onopen = () => {
            sysLog("Conectado. Enviando archivo binario...");
            const reader = new FileReader();
            reader.onload = () => socket.send(reader.result);
            reader.readAsArrayBuffer(fileInput.files[0]);
        };

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            if (data.type === 'language') {
                sysLog(`Idioma detectado: ${data.language} (${Math.round(data.probability*100)}%)`);
            }
            if (data.type === 'segment') {
                display.innerText += ` ${data.text}`;
                display.scrollTop = display.scrollHeight;
            }
            if (data.type === 'done') {
                sysLog("Transcripción completa exitosa.");
                btn.disabled = false;
            }
            if (data.type === 'error') {
                sysLog(`ERROR IA: ${data.detail}`);
                btn.disabled = false;
            }
        };

        socket.onclose = () => sysLog("Conexión WebSocket cerrada.");
        socket.onerror = (e) => sysLog("Error crítico de conexión.");
    }

    // --- TEST PDF WEBSOCKET ACTUALIZADO ---
    async function testPdfWS() {
        const fileInput = document.getElementById('pdfFile');
        const bar = document.getElementById('progress-bar');
        const cont = document.getElementById('pdfProgressCont');
        const status = document.getElementById('pdf-status');
        const btn = document.getElementById('btnPdf');

        // Elementos de resultados
        const resultsBox = document.getElementById('pdf-results-box');
        const resTitle = document.getElementById('res-title');
        const resPages = document.getElementById('res-pages');
        const resKeywordsCount = document.getElementById('res-keywords-count');
        const resKeywordsList = document.getElementById('res-keywords-list');

        if (!fileInput.files[0]) return alert("Selecciona un PDF");

        // Reiniciar y preparar la UI
        cont.style.display = "block";
        bar.style.width = "0%";
        btn.disabled = true;
        resultsBox.style.display = "none";
        resKeywordsList.innerHTML = "";
        status.innerText = "Abriendo conexión...";
        sysLog("Iniciando análisis de PDF...");

        const socket = new WebSocket('ws://' + window.location.host + '/ws/analyze-pdf');

        socket.onopen = () => {
            sysLog("Conectado. Enviando PDF...");
            const reader = new FileReader();
            reader.onload = () => socket.send(reader.result);
            reader.readAsArrayBuffer(fileInput.files[0]);
        };

        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            // 1. Progreso de páginas
            if (data.type === 'page') {
                const percent = (data.page / data.total) * 100;
                bar.style.width = `${percent}%`;
                status.innerText = `Procesando página ${data.page} de ${data.total}...`;
                
                // Mostrar la caja de resultados desde el primer fragmento recibido
                resultsBox.style.display = "block";
                resPages.innerText = `${data.page} de ${data.total}`;
            }

            // 2. Extracción de Keywords
            if (data.type === 'keywords') {
                sysLog(`Keywords recibidas: ${data.keywords.join(', ')}`);
                resKeywordsCount.innerText = data.keywords.length;
                
                // Limpiar lista previa y pintar etiquetas visuales para cada keyword
                resKeywordsList.innerHTML = "";
                data.keywords.forEach(kw => {
                    const badge = document.createElement('span');
                    badge.innerText = kw;
                    badge.style.cssText = "background: #e1f5fe; color: #0288d1; border: 1px solid #b3e5fc; padding: 4px 10px; border-radius: 15px; font-size: 13px; font-weight: 500;";
                    resKeywordsList.appendChild(badge);
                });
            }

            // 3. Finalización
            if (data.type === 'done') {
                sysLog(`Análisis terminado con éxito.`);
                status.innerText = `¡Análisis completado!`;
                resTitle.innerText = data.title || "No detectado";
                btn.disabled = false;
                socket.close();
            }

            // 4. Errores
            if (data.type === 'error') {
                sysLog(`Error en el análisis: ${data.detail}`);
                status.innerText = `Error: ${data.detail}`;
                btn.disabled = false;
            }
        };

        socket.onerror = (e) => {
            sysLog("Error crítico de conexión.");
            status.innerText = "Error de conexión con la IA.";
            btn.disabled = false;
        };

        socket.onclose = () => sysLog("Conexión del WebSocket para PDF cerrada.");
    }

    // --- TEST SIMILARIDAD (HTTP vía Laravel) ---
    async function testSimilarity() {
        const text1 = document.getElementById('text1').value;
        const text2 = document.getElementById('text2').value;
        const resDiv = document.getElementById('sim-result');

        if (!text1 || !text2) return alert("Completa ambos textos");

        sysLog("Enviando petición de similitud a Laravel...");
        resDiv.innerText = "Calculando...";

        try {
            const response = await fetch('/ai/similarity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ text1, text2 })
            });

            const data = await response.json();
            
            if (data.similarity !== undefined) {
                const score = (data.similarity * 100).toFixed(2);
                resDiv.innerHTML = `Similitud: ${score}% <br> <small>${data.interpretation}</small>`;
                sysLog(`Resultado similitud: ${score}%`);
            } else {
                resDiv.innerText = "Error en la respuesta.";
            }
        } catch (e) {
            sysLog("Error de red o de servidor.");
        }
    }
</script>

</body>
</html>