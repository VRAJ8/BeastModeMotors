# syntax=docker/dockerfile:1

############################################
# 1. Front-end assets (Vite + Tailwind)
############################################
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
# Tailwind also scans class names used in PHP (badges, tones, Filament-free helpers).
COPY app ./app
RUN npm run build

############################################
# 2. PHP dependencies
############################################
FROM serversideup/php:8.3-cli AS vendor
USER root
RUN install-php-extensions intl
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

############################################
# 3. Runtime: Nginx + PHP-FPM
############################################
FROM serversideup/php:8.3-fpm-nginx AS runtime

USER root
RUN install-php-extensions intl

ENV PHP_OPCACHE_ENABLE=1 \
    NGINX_LISTEN_IP_PROTOCOL=ipv4 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    QUEUE_CONNECTION=database

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Optimised autoloader + Filament/Livewire public assets.
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && touch database/database.sqlite \
    && chown -R www-data:www-data database storage bootstrap/cache public

# Seeds the demo showroom on first boot when DEMO_MODE=true (runs after migrations).
COPY --chmod=755 docker/entrypoint.d/60-demo-seed.sh /etc/entrypoint.d/60-demo-seed.sh

# A queue worker (emails, in-app notifications) and the scheduler (reminders, recalls, expiry) run next to
# Nginx and PHP-FPM under s6. Turn either off with RUN_QUEUE_WORKER=false / RUN_SCHEDULER=false.
COPY --chmod=755 docker/s6-rc.d/ /etc/s6-overlay/s6-rc.d/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/laravel-queue /etc/s6-overlay/s6-rc.d/user/contents.d/laravel-scheduler

USER www-data

EXPOSE 8080
