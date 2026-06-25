FROM php:8.3-cli

# Extensões: pdo_mysql (runtime), pdo_sqlite (testes em :memory:), e as usuais do Laravel.
RUN apt-get update && apt-get install -y \
        git curl unzip \
        libzip-dev libonig-dev libpng-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip bcmath exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências no BUILD (camada cacheável; preservada em runtime por um
# volume anônimo de vendor no docker-compose, mesmo com o bind mount do código).
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# Copia o restante do código e finaliza o autoload.
COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["sh", "entrypoint.sh"]
