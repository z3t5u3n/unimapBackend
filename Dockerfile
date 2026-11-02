FROM php:8.3-apache

# 1. Instalar dependencias del sistema requeridas para PostgreSQL (libpq-dev) y extensiones de Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    bcmath \
    zip \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Habilitar mod_rewrite de Apache y copiar configuración de VirtualHost
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# 4. Establecer directorio de trabajo
WORKDIR /var/www/html

# 5. Copiar código fuente del backend
COPY . /var/www/html

# 6. Instalar dependencias de Composer para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 7. Configurar permisos de almacenamiento y caché
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/entrypoint.sh

# 8. Exponer puerto por defecto
EXPOSE 80

# 9. Script de inicio con migraciones automáticas y arranque de servidor
CMD ["/var/www/html/entrypoint.sh"]
