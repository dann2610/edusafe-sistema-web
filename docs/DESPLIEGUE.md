# Despliegue en producción con HTTPS y QR definitivo

Guía para un servidor Ubuntu 22.04/24.04 con Nginx, PHP-FPM y PostgreSQL. Adapte nombres y rutas a su infraestructura.

## 1. Paquetes

```bash
sudo apt update
sudo apt install -y nginx postgresql php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-intl php8.3-curl unzip git certbot python3-certbot-nginx
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

## 2. Base de datos

```bash
sudo -u postgres psql -c "CREATE ROLE tic_ues LOGIN PASSWORD 'CLAVE-LARGA-Y-UNICA';"
sudo -u postgres psql -c "CREATE DATABASE tic_ues OWNER tic_ues ENCODING 'UTF8' TEMPLATE template0;"
```

## 3. Aplicación

```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone <repositorio> tic-ues && cd tic-ues
sudo composer install --no-dev --optimize-autoloader
sudo cp .env.example .env && sudo php artisan key:generate
```

Edite `.env` con valores de producción:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seguridad.su-dominio.edu.ec
LOG_LEVEL=warning
DB_PASSWORD=CLAVE-LARGA-Y-UNICA
SESSION_SECURE_COOKIE=true
QR_URL_PUBLICA=https://seguridad.su-dominio.edu.ec
MAIL_MAILER=smtp        # y los datos SMTP institucionales
DEMO_PASSWORD=          # vacío: no se usan datos DEMO en producción
```

Luego:

```bash
sudo php artisan migrate --force
sudo php artisan db:seed --force          # solo roles, permisos y catálogos
sudo php artisan tic:crear-admin          # administrador inicial con contraseña oculta
sudo php artisan config:cache && sudo php artisan route:cache && sudo php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 750 storage bootstrap/cache
```

En producción, `db:seed` no crea usuarios ni estudiantes, y los seeders DEMO se niegan a ejecutarse.

## 4. Nginx

`/etc/nginx/sites-available/tic-ues`:

```nginx
server {
    listen 80;
    server_name seguridad.su-dominio.edu.ec;
    root /var/www/tic-ues/public;
    index index.php;
    client_max_body_size 20M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/tic-ues /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 5. HTTPS

```bash
sudo certbot --nginx -d seguridad.su-dominio.edu.ec --redirect
```

Certbot renueva el certificado automáticamente. Con `APP_ENV=production` la aplicación fuerza URLs `https://` y envía la cabecera HSTS.

## 6. Tareas programadas

```bash
echo '* * * * * www-data cd /var/www/tic-ues && php artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/tic-ues
```

## 7. QR definitivo

1. Verifique que `https://seguridad.su-dominio.edu.ec` abre la página pública desde un teléfono con datos móviles, es decir, fuera de la red institucional.
2. Confirme `QR_URL_PUBLICA` en `.env` y elija `QR_DESTINO=bienvenida` (recomendado) o `formulario`.
3. Ejecute `php artisan config:cache`.
4. Ingrese como administrador técnico → **Código QR**. La página no debe mostrar advertencias.
5. Escanee el QR en pantalla con al menos dos teléfonos distintos y compruebe el destino.
6. Descargue el **SVG** para imprenta (escala sin perder calidad) o el **PNG** para documentos digitales.
7. Si el dominio cambia, repita desde el paso 2 y reemplace los QR impresos.

## 8. Lista de verificación previa a la publicación

- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] HTTPS activo y redirección de HTTP a HTTPS
- [ ] Sin usuarios DEMO ni estudiantes ficticios en la base
- [ ] Administrador creado con `tic:crear-admin`
- [ ] Aviso de privacidad aprobado por la institución (`resources/views/public/privacy.blade.php`)
- [ ] Contactos de las entidades externas completados en Catálogos
- [ ] Respaldos programados y restauración probada
- [ ] Correo SMTP probado con la recuperación de contraseña
- [ ] QR verificado desde teléfonos externos
