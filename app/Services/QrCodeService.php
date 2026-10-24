<?php

namespace App\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Código QR institucional. Solo codifica la URL pública configurada en
 * QR_URL_PUBLICA (más la ruta de destino). Nunca contraseñas ni datos personales.
 */
class QrCodeService
{
    /**
     * URL que se codificará para la institución, o null si la configuración no es válida.
     * Formato: {QR_URL_PUBLICA}/i/{slug} (o /i/{slug}/reportar si QR_DESTINO=formulario).
     */
    public function targetUrl(?\App\Models\Institution $institution = null): ?string
    {
        $base = rtrim((string) config('seguridad_escolar.qr.url_publica'), '/');
        if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL)) {
            return null;
        }
        $scheme = parse_url($base, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }
        $institution ??= app(\App\Support\TenantContext::class)->institution() ?? \App\Models\Institution::default();
        $path = '/i/'.$institution->slug.(config('seguridad_escolar.qr.destino') === 'formulario' ? '/reportar' : '');

        return $base.$path;
    }

    /** Advertencias sobre la URL configurada (no bloquean la generación). */
    public function warnings(?\App\Models\Institution $institution = null): array
    {
        $url = $this->targetUrl($institution);
        if (! $url) {
            return [];
        }
        $host = parse_url($url, PHP_URL_HOST);
        $warnings = [];
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $warnings[] = 'La URL no usa HTTPS. El QR definitivo debe apuntar a una dirección HTTPS.';
        }
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with((string) $host, '.test') || str_ends_with((string) $host, '.local')) {
            $warnings[] = 'La dirección es local: los teléfonos de otras personas no podrán abrirla. Úsela solo para pruebas.';
        }

        return $warnings;
    }

    public function svg(string $url, int $size = 320): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }

    public function png(string $url, int $size = 800): string
    {
        return (new Writer(new GDLibRenderer($size, 4)))->writeString($url);
    }
}
