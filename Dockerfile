# Blog do Clama — WordPress (Bedrock) sobre SQLite, para o Railway.
#
# Duas coisas neste arquivo não são convenção e sim requisito:
#
# 1. O docroot é `web/`, não a raiz. É assim que o Bedrock isola o core em
#    `web/wp` e mantém `vendor/`, `config/` e `.env` FORA do alcance do
#    servidor. Apontar o docroot para a raiz publicaria o `.env` com as salts.
# 2. Uploads e banco vivem no volume, nunca na imagem. Ver `docker-entrypoint.sh`.

FROM php:8.3-apache

# ext-gd: redimensionamento de imagem do WordPress (sem ela, upload de capa
#   entra sem thumbnail e sem aviso).
# ext-intl: usado pelo Rank Math na normalização de URL.
# ext-zip: instalação/atualização de plugin e tema pelo painel.
# pdo_sqlite e sqlite3 já vêm compilados na imagem oficial — é o nosso banco.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
        libicu-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd intl zip exif opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Story 2.10: o teto que morde primeiro não é o proxy, é o PHP da origem.
# Uma capa realista de 2400×1350 pesa ~9 MB; o padrão de 2M rejeitaria o
# upload na origem antes de o proxy ver qualquer coisa.
RUN { \
      echo 'upload_max_filesize = 16M'; \
      echo 'post_max_size = 20M'; \
      echo 'memory_limit = 256M'; \
      echo 'max_execution_time = 120'; \
      echo 'expose_php = Off'; \
      echo 'opcache.enable = 1'; \
      echo 'opcache.validate_timestamps = 0'; \
    } > /usr/local/etc/php/conf.d/clama.ini

RUN a2enmod rewrite headers expires

# WP-CLI é como se opera isto no Railway: instalar, atualizar core e plugin,
# limpar cache de sitemap. Sem ele, a única via é o wp-admin pelo navegador —
# e a instalação inicial precisa acontecer antes de o wp-admin existir.
ADD --chmod=755 https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Camada de dependências separada do código: mudar um arquivo do tema não
# reinstala o WordPress inteiro.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /app

COPY docker/apache-clama.conf /etc/apache2/sites-available/000-default.conf
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
