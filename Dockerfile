# Usar una imagen oficial de PHP con el servidor web Apache
FROM php:8.2-apache

# Instalar las dependencias del sistema (libpq-dev) y luego las
# extensiones de PHP para PostgreSQL, todo en un solo paso.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar todo el contenido de repositorio
COPY . /var/www/html/
