<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte institucional</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1c2733; }
        header { position: fixed; top: -20mm; left: 0; right: 0; border-bottom: 2px solid #12355b; padding-bottom: 4px; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8.5px; color: #56636f; border-top: 1px solid #dfe3e8; padding-top: 3px; }
        h1 { font-size: 16px; color: #12355b; margin: 0 0 4px; }
        h2 { font-size: 12px; color: #12355b; margin: 16px 0 6px; border-bottom: 1px solid #dfe3e8; padding-bottom: 2px; }
        .muted { color: #56636f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e9edf1; text-align: left; }
        th { background: #f2f5f8; font-weight: bold; }
        td.n { text-align: right; width: 22%; }
        .kpis td { border: 1px solid #dfe3e8; text-align: center; width: 16.6%; }
        .kpis .v { font-size: 16px; font-weight: bold; color: #12355b; display: block; }
        .cols { width: 100%; }
        .cols td { vertical-align: top; border: 0; padding: 0 8px 0 0; width: 50%; }
        .bar { background: #0b6e78; height: 7px; }
    </style>
</head>
<body>
<header>
    <strong>{{ config('seguridad_escolar.institucion.nombre') }}</strong>
    <span class="muted"> · {{ config('seguridad_escolar.sistema.nombre') }}</span>
</header>
<footer>
    Generado el {{ $generatedAt->format('d/m/Y H:i') }} · Datos agregados sin información personal · Uso institucional
</footer>

<h1>Reporte institucional de incidentes</h1>
<div class="muted">
    @foreach ($filterLabels as $k => $v){{ $k }}: {{ $v }}@if (! $loop->last) · @endif @endforeach
</div>

<h2>Indicadores generales</h2>
<table class="kpis"><tr>
    @foreach (['Total' => $summary['total'], 'Abiertos' => $summary['abiertos'], 'Pendientes' => $summary['pendientes'], 'Derivados' => $summary['derivados'], 'Cerrados' => $summary['cerrados'], 'Días prom. cierre' => $summary['dias_cierre'] ?? '—'] as $k => $v)
        <td><span class="v">{{ $v }}</span>{{ $k }}</td>
    @endforeach
</tr></table>

@php $max = max([1] + array_values($byCategory)); @endphp
<h2>Incidentes por categoría</h2>
<table>
    <tr><th>Categoría</th><th style="width:40%"></th><th class="n">Casos</th></tr>
    @forelse ($byCategory as $k => $v)
        <tr><td>{{ $k }}</td><td><div class="bar" style="width:{{ round($v / $max * 100) }}%"></div></td><td class="n">{{ $v }}</td></tr>
    @empty
        <tr><td colspan="3" class="muted">Sin datos</td></tr>
    @endforelse
</table>

<table class="cols"><tr>
    <td>
        <h2>Casos por estado</h2>
        <table>@foreach ($byStatus as $k => $v)<tr><td>{{ $k }}</td><td class="n">{{ $v }}</td></tr>@endforeach</table>
    </td>
    <td>
        <h2>Casos por curso</h2>
        <table>@forelse ($byCourse as $k => $v)<tr><td>{{ $k }}</td><td class="n">{{ $v }}</td></tr>@empty<tr><td class="muted">Sin datos</td></tr>@endforelse</table>
    </td>
</tr></table>

<h2>Evolución mensual</h2>
<table>
    <tr>@foreach ($monthly as $k => $v)<th style="text-align:center">{{ $k }}</th>@endforeach</tr>
    <tr>@foreach ($monthly as $v)<td style="text-align:center">{{ $v }}</td>@endforeach</tr>
</table>

<p class="muted" style="margin-top:18px">
    Este reporte presenta información agregada para apoyar la toma de decisiones institucionales. No identifica a estudiantes
    ni reemplaza los protocolos oficiales.
</p>
</body>
</html>
