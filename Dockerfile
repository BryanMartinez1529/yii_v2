FROM php:8.2-cli-alpine

# Instalar dependencias del sistema y extensiones habituales
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip

# Copiar el binario oficial de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Exponer el puerto para el servidor de desarrollo
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "web"]