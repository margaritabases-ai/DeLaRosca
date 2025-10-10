# Usar una imagen oficial de PHP con el servidor web Apache
FROM php:8.2-apache

# ---- LÍNEA AÑADIDA ----
# Instalar las extensiones de PHP necesarias para conectar con PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar todo el contenido de tu repositorio
COPY . /var/www/html/
