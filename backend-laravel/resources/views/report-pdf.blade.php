<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte PDF</title>
    <style>
        body {
            font-family: sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h1 { margin: 0; font-size: 24px; color: #3b82f6; }
        .date { font-size: 14px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 18px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; color: #111; }
        .metrics-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .metrics-grid td {
            width: 50%;
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .metric-label { font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px; }
        .metric-value { font-size: 24px; font-weight: bold; }
        .transcription-box {
            background: #f1f5f9;
            padding: 15px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte de la Sesión</h1>
        <div class="date">{{ $session->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Detalles del Módulo</div>
        <p><strong>Tema Base:</strong> {{ $session->learningModule->title }}</p>
        @php
            $keywords = $session->learningModule->keywords;
            if (is_string($keywords)) {
                $keywords = json_decode($keywords, true) ?? [];
            }
        @endphp
        @if(!empty($keywords))
            <p><strong>Palabras Clave Analizadas:</strong><br>
                <span style="display: block; margin-top: 5px;">
                    @foreach($keywords as $kw)
                        <span style="display: inline-block; background: #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-right: 5px; margin-bottom: 5px; border: 1px solid #cbd5e1;">{{ $kw }}</span>
                    @endforeach
                </span>
            </p>
        @endif
    </div>

    @php
        $analysis = $session->analysis_data;
        $score = $analysis['similarity_score'] ?? 0;
        $percent = number_format($score * 100, 1);
        $interpretation = $analysis['interpretation'] ?? 'N/A';
        $scoreColor = $score >= 0.8 ? '#10b981' : ($score >= 0.5 ? '#f59e0b' : '#ef4444');
    @endphp

    <table class="metrics-grid">
        <tr>
            <td>
                <div class="metric-label">Coherencia Pedagógica</div>
                <div class="metric-value" style="color: {{ $scoreColor }}">{{ $percent }}%</div>
            </td>
            <td>
                <div class="metric-label">Interpretación IA</div>
                <div style="font-size: 13px;">{{ $interpretation }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Transcripción de la Clase</div>
        <div class="transcription-box">{{ $session->transcription ?: 'Sin transcripción registrada.' }}</div>
    </div>

</body>
</html>
