<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Sesión | VoxClass</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --panel-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --success: #10b981;
            --border: #334155;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        h1, h2, h3 { margin: 0; font-weight: 600; }
        h1 { font-size: 1.8rem; color: var(--accent); }
        .date { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }

        .btn-export {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-export:hover { filter: brightness(1.1); }

        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 1.2rem;
            color: var(--text-main);
            margin-bottom: 15px;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 5px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .metric-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
            margin: 10px 0;
        }
        .metric-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .transcription-box {
            background: #0f172a;
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 8px;
            color: #cbd5e1;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <h1>Reporte de la Sesión</h1>
                <div class="date">{{ $session->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <a href="/sessions/{{ $session->id }}/report/pdf" class="btn-export">Exportar a PDF</a>
        </div>

        <div class="section">
            <h2 class="section-title">Detalles del Módulo</h2>
            <p><strong>Tema Base:</strong> {{ $session->learningModule->title }}</p>
            @php
                $keywords = $session->learningModule->keywords;
                if (is_string($keywords)) {
                    $keywords = json_decode($keywords, true) ?? [];
                }
            @endphp
            @if(!empty($keywords))
                <div style="margin-top: 10px;">
                    <strong>Palabras Clave Analizadas:</strong><br>
                    <div style="margin-top: 5px;">
                        @foreach($keywords as $kw)
                            <span style="display: inline-block; background: rgba(59, 130, 246, 0.2); color: #60a5fa; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; margin: 0 5px 5px 0; border: 1px solid rgba(59, 130, 246, 0.3);">{{ $kw }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="section metrics-grid">
            @php
                $analysis = $session->analysis_data;
                $score = $analysis['similarity_score'] ?? 0;
                $percent = number_format($score * 100, 1);
                $interpretation = $analysis['interpretation'] ?? 'N/A';
            @endphp
            <div class="metric-card">
                <div class="metric-label">Coherencia Pedagógica</div>
                <div class="metric-value" style="color: {{ $score >= 0.8 ? 'var(--success)' : ($score >= 0.5 ? '#fbbf24' : 'var(--danger)') }}">{{ $percent }}%</div>
            </div>
            <div class="metric-card" style="text-align: left;">
                <div class="metric-label">Interpretación IA</div>
                <div style="margin-top:10px; font-size: 0.95rem; line-height: 1.5;">{{ $interpretation }}</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Transcripción de la Clase</h2>
            <div class="transcription-box">{{ $session->transcription ?: 'Sin transcripción registrada.' }}</div>
        </div>
    </div>

</body>
</html>
