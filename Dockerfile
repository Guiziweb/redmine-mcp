FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libsodium-dev \
    postgresql-dev \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    sodium \
    pdo_pgsql

# Configure PHP-FPM to listen on TCP instead of socket
RUN sed -i 's/listen = \/run\/php\/php8.4-fpm.sock/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf || \
    echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/www.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application files
COPY . .

# Copy nginx and supervisor configurations
COPY docker/nginx/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Create required directories and set permissions
RUN mkdir -p var/cache var/log var /var/log/supervisor /run/nginx && \
    chown -R www-data:www-data /app /var/log/nginx /run/nginx && \
    chmod -R 775 var/

# Run Symfony post-install scripts
RUN composer dump-autoload --optimize && \
    chown -R www-data:www-data var/

# Expose port
EXPOSE 8080

# Start: clear cache, run migrations, then start supervisor (which manages nginx + php-fpm)
CMD php bin/console cache:clear --env=prod --no-debug && \
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration && \
    /usr/bin/supervisord -c /etc/supervisord.conf
