# Usa una imagen base de PHP 8.3
FROM php:8.3-fpm

# Instala las extensiones necesarias de PHP y otras dependencias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
ENV PATH="/root/.symfony*/bin:${PATH}"

# Configura el directorio de trabajo
WORKDIR /var/www

# Copia el archivo composer.json y composer.lock
COPY composer.json composer.lock ./

# Instala las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Copia el resto de tu aplicación
COPY . .

# Expone el puerto que usa Symfony
EXPOSE 8000

# Comando por defecto para iniciar la aplicación
CMD ["symfony", "serve", "--no-tls"]
