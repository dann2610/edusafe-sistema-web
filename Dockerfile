#!/bin/sh
set -e
cd /var/www/html

if [ -n "$APP_KEY" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    export APP_KEY="base64:$APP_KEY"
fi

if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then export APP_URL="$RENDER_EXTERNAL_URL"; fi
if [ -z "$QR_URL_PUBLICA" ] && [ -n "$APP_URL" ]; then export QR_URL_PUBLICA="$APP_URL"; fi

PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear >/dev/null 2>&1 || true

php artisan migrate --force
php artisan db:seed --force

if [ "$CARGAR_DEMO" = "true" ]; then
    EXISTE=$(php artisan tinker --execute="echo \App\Models\User::where('email','superadmin.demo@uecrc.test')->exists() ? 'si' : 'no';" 2>/dev/null | tail -n 1)
    if [ "$EXISTE" != "si" ]; then
        APP_ENV=local php artisan db:seed --class=DemoDataSeeder --force
        APP_ENV=local php artisan db:seed --class=DemoIncidentsSeeder --force
    fi
fi

# Contraseña de los usuarios DEMO: se fija en cada arranque con DEMO_PASSWORD
if [ "$CARGAR_DEMO" = "true" ] && [ -n "$DEMO_PASSWORD" ]; then
    echo ">> Actualizando contraseña de usuarios DEMO"
    php artisan tinker --execute="\App\Models\User::where('email','like','%.test')->get()->each(function (\$u) { \$u->password = getenv('DEMO_PASSWORD'); \$u->must_change_password = false; \$u->is_active = true; \$u->save(); }); echo 'ok';"
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

su -s /bin/sh www-data -c "php artisan schedule:work" >/dev/null 2>&1 &

exec apache2-foreground