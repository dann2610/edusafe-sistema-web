<?php

/*
|--------------------------------------------------------------------------
| Parámetros del sistema TIC para Unidades Educativas Seguras
|--------------------------------------------------------------------------
| Todos los valores sensibles o dependientes del entorno se leen desde .env.
| Ningún valor de este archivo debe contener credenciales.
*/

return [

    'institucion' => [
        'nombre' => env('INSTITUCION_NOMBRE', 'Unidad Educativa Cardenal Richard Cushing'),
        'siglas' => env('INSTITUCION_SIGLAS', 'UECRC'),
    ],

    'sistema' => [
        'nombre' => 'TIC para Unidades Educativas Seguras',
        'subtitulo' => 'Monitoreo, comunicación y cultura de prevención',
    ],

    'qr' => [
        // URL pública definitiva (HTTPS). Vacía = el QR no se genera.
        'url_publica' => env('QR_URL_PUBLICA', ''),
        // 'bienvenida' o 'formulario'
        'destino' => env('QR_DESTINO', 'bienvenida'),
    ],

    'reportes_publicos' => [
        'habilitados' => (bool) env('REPORTES_PUBLICOS_HABILITADOS', true),
        'limite_por_hora' => (int) env('REPORTES_PUBLICOS_LIMITE_HORA', 5),
    ],

    'adjuntos' => [
        'disco' => 'local', // storage/app/private — nunca el disco "public"
        'directorio' => 'evidencias',
        'max_kb' => (int) env('ADJUNTOS_MAX_KB', 5120),
        'max_archivos' => (int) env('ADJUNTOS_MAX_ARCHIVOS', 3),
        'extensiones' => ['pdf', 'jpg', 'jpeg', 'png', 'docx'],
        'mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    'login' => [
        'max_intentos' => (int) env('LOGIN_MAX_INTENTOS', 5),
        'bloqueo_segundos' => 300,
    ],

    'auditoria' => [
        'registrar_ip' => (bool) env('AUDITORIA_REGISTRAR_IP', true),
    ],

    'demo' => [
        'password' => env('DEMO_PASSWORD', ''),
    ],

    // Canales oficiales de emergencia (Ecuador). Verificar con la institución.
    'emergencias' => [
        ['nombre' => 'ECU 911 — Emergencias', 'contacto' => '911'],
    ],
];
