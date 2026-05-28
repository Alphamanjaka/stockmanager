# Stage 1: Composer Dependencies
# This stage is dedicated to installing PHP Composer dependencies.
# Using the official Composer image ensures Composer is readily available.
FROM composer:2 AS composer_dependencies

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install production Composer dependencies. This layer is cached as long as
# composer.lock doesn't change. --no-scripts is important to prevent
# execution of arbitrary commands during build.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs

# Stage 2: Node Assets
# This stage builds the frontend assets (CSS/JS) using Node.js.
FROM node:20-alpine AS node_assets
WORKDIR /app
# Copy only necessary files for asset building
COPY package.json package-lock.json vite.config.js ./
COPY resources/ ./resources/
RUN npm ci && npm run build

# Stage 3: Production environment (minimal footprint)
# This is the final image for production, containing only the runtime
# PHP environment and the application code.
FROM php:8.4-fpm-alpine AS production

WORKDIR /var/www

# Copy composer binary from the official image to the final production image
# This is required to run 'composer dump-autoload' later in this stage.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install build dependencies temporarily for PHP extensions, then remove them.
# This keeps the final image small by not including build tools.
RUN apk add --no-cache --virtual .build-deps \
    autoconf gcc g++ make \
    postgresql-dev oniguruma-dev libzip-dev icu-dev \
    openssl-dev libxml2-dev curl-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    linux-headers \
    # Install PECL extensions
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Configure and install core PHP extensions
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd bcmath zip intl soap opcache \
    # Remove build dependencies
    && apk del .build-deps \
    # Install only runtime dependencies (no build tools, no -dev packages)
    && apk add --no-cache \
    postgresql-client \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libzip \
    libxml2 \
    libsodium \
    git \
    unzip \
    curl

# Copy application code from the host (ensure .dockerignore is configured to exclude unnecessary files)
COPY . /var/www

# Copy Composer dependencies from the composer_dependencies stage
COPY --from=composer_dependencies /app/vendor /var/www/vendor

# Copy built assets from the node_assets stage
COPY --from=node_assets /app/public/build /var/www/public/build

# Set production PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for maximum performance
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Laravel optimizations
RUN composer dump-autoload --optimize --no-dev \
    && php artisan storage:link \
    && export APP_KEY="base64:$(openssl rand -base64 32)" \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Setup storage directories and permissions
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Entrypoint script
COPY ./docker/production/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# USER www-data (Commenté pour permettre à l'entrypoint de fixer les permissions en root)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]

# Stage 4: Development environment (extends production)
FROM production AS development

USER root

# Install development tools for Xdebug, then clean up
RUN apk add --no-cache --virtual .build-deps autoconf g++ make linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Install bash and shadow (for useradd/usermod) to support dev entrypoint scripts
RUN apk add --no-cache bash shadow sudo

# Configuration with ARGs
ARG XDEBUG_ENABLED=true
ARG XDEBUG_MODE=develop,coverage,debug,profile
ARG XDEBUG_HOST=host.docker.internal
ARG XDEBUG_IDE_KEY=DOCKER
ARG XDEBUG_LOG=/dev/stdout
ARG XDEBUG_LOG_LEVEL=0
ARG UID=1000
ARG GID=1000

# Configure Xdebug (only if enabled)
RUN if [ "${XDEBUG_ENABLED}" = "true" ]; then \
    echo "xdebug.mode=${XDEBUG_MODE}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=${XDEBUG_IDE_KEY}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=${XDEBUG_LOG}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log_level=${XDEBUG_LOG_LEVEL}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=${XDEBUG_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    fi

# Create dev user with sync'd UID/GID
RUN if getent group ${GID}; then \
    useradd -m -u ${UID} -g ${GID} -s /bin/bash dev; \
    else \
    groupadd -g ${GID} dev && \
    useradd -m -u ${UID} -g dev -s /bin/bash dev; \
    fi && \
    usermod -aG sudo dev 2>/dev/null || true && \
    echo 'dev ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers 2>/dev/null || true

# Update php-fpm config for development user
RUN sed -i "s/user = www-data/user = dev/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = dev/g" /usr/local/etc/php-fpm.d/www.conf

# Copy dev entrypoint
COPY ./docker/development/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER dev
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]
