FROM php:8.4-cli-alpine

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

# Create non-root user in group 1000 (required by Render for secrets access)
RUN apk add shadow && \
    addgroup -g 1000 app && \
    adduser -D -u 1000 -G app app

# Create required directories and set permissions
RUN mkdir -p var/cache var/log var && \
    chown -R app:app /app && \
    chmod -R 775 var/

# Run Symfony post-install scripts as root (needs write access)
RUN composer dump-autoload --optimize && \
    php bin/console cache:clear --env=prod --no-debug && \
    chown -R app:app var/

# Switch to non-root user
USER app

# Expose port (Render uses $PORT env var)
EXPOSE ${PORT:-8080}

# Start: run migrations then start server
CMD php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration && \
    php -S 0.0.0.0:${PORT:-8080} -t public/
