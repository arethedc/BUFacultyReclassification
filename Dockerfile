FROM php:8.2-cli-bookworm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts \
    && npm ci --include=dev

COPY . .

RUN rm -f public/hot

RUN npm run build \
    && php artisan package:discover --ansi \
    && rm -rf node_modules

EXPOSE 10000

CMD ["sh", "-c", "if [ \"${RUN_DB_FRESH}\" = \"true\" ] || [ \"${RUN_DB_FRESH}\" = \"1\" ]; then echo 'RUN_DB_FRESH enabled: running fresh migration + baseline seed'; php artisan migrate:fresh --seed --seeder=DatabaseSeeder --force --no-interaction; else php artisan migrate --force --no-interaction && php artisan db:seed --force --class=ProductionBootstrapSeeder --no-interaction; fi && (php artisan optimize:clear || true) && (php artisan storage:link || true) && (php artisan schedule:work > /tmp/scheduler.log 2>&1 &) && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
