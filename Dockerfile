FROM php:8.2-apache

# Habilitar mod_rewrite para amigabilidad de URLs en MVC
RUN a2enmod rewrite

# Instalar dependencias del sistema y extensiones de PostgreSQL (PDO)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar todo el código del proyecto al directorio web de Apache
COPY . /var/www/html/

# Configurar permisos adecuados para Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html