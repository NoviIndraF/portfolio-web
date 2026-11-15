# Production image: Vite assets built in a Node stage, app served by FrankenPHP (PHP 8.4).
# Used by compose.yaml (home server). See docs/deploy-home-server.md.

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
# Same npm as the one that wrote package-lock.json; newer npm rejects the lock (missing optional peer react).
RUN npm install -g npm@11.6.2 && npm ci
COPY . .
RUN npm run build

FROM dunglas/frankenphp:1-php8.4
WORKDIR /app

RUN install-php-extensions gd intl zip pdo_mysql opcache \
    && printf "upload_max_filesize=25M\npost_max_size=25M\nmemory_limit=256M\nexpose_php=Off\n" > "$PHP_INI_DIR/conf.d/app.ini" \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=assets /app/public/build public/build
COPY --from=assets /app/public/icons/simple public/icons/simple

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && php artisan storage:link

# Plain HTTP on :80 inside the container; HTTPS comes from the public proxy in front of it.
# Security headers, the www redirect and the trusted-proxy rules live in deploy/Caddyfile.
COPY deploy/Caddyfile /etc/frankenphp/Caddyfile
EXPOSE 80
