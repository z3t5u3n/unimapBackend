#!/bin/bash
set -e

# Asignar puerto dinámico de Render (por defecto 80 o 10000)
PORT=${PORT:-80}
echo "=> Iniciando UniMap Backend en puerto: $PORT"

# Ajustar Apache para escuchar en el puerto indicado por Render
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Asegurar permisos de almacenamiento y caché
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} /var/www/html/storage/logs /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generar APP_KEY si no está configurada
if [ -z "$APP_KEY" ]; then
    echo "=> Generando APP_KEY para Laravel..."
    php artisan key:generate --force
fi

# Ejecutar migraciones automáticas en PostgreSQL (o MySQL)
echo "=> Ejecutando migraciones de base de datos..."
php artisan migrate --force

# Poblar catálogos esenciales y usuario admin
echo "=> Poblando catálogos y datos esenciales (seeders)..."
php artisan db:seed --force

# Optimizar caché de Laravel para producción
echo "=> Optimizando caché de rutas, configuración y vistas..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=> Servidor Apache listo para recibir peticiones HTTP."
exec apache2-foreground
