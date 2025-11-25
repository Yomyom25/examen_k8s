FROM php:7.4-apache

# Instalar extensiones de PHP necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Crear directorio y copiar los archivos de la aplicación
COPY src/ /var/www/html/

# Exponer el puerto 80
EXPOSE 80

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Salud check opcional
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/ || exit 1