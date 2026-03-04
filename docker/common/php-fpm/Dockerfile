# Stage 1: Build Stage
# This stage compiles PHP extensions and installs Composer dependencies.
# It contains build tools that will not be in the final production image.
FROM php:8.4-fpm AS build

WORKDIR /var/www

# Install build dependencies, PHP extensions, and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl git \
    libpq-dev libonig-dev libzip-dev libicu-dev \
    libssl-dev libxml2-dev libcurl4-openssl-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd bcmath zip intl soap \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

# Copy composer files first (better layer caching)
COPY composer.json composer.lock ./

# Install production Composer dependencies. This layer is cached as long as composer.lock doesn't change.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist

# Stage 2: Production environment (minimal footprint)
FROM php:8.4-fpm AS production

WORKDIR /var/www

# Copy pre-compiled extensions and PHP config from the build stage
COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Install only runtime dependencies (no build tools, no -dev packages)
RUN apt-get update && apt-get install -y --no-install-recommends \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer dependencies from the build stage
COPY --from=build /var/www/vendor /var/www/vendor

# Copy application code from the host
COPY . /var/www

# Set production PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Setup storage directories and permissions
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Entrypoint script
COPY ./docker/production/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]

# Stage 3: Development environment (extends production)
FROM production AS development

USER root

# Install development tools and Xdebug build deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    autoconf gcc g++ make \
    libpq-dev libonig-dev libzip-dev libicu-dev \
    libssl-dev libxml2-dev libcurl4-openssl-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y autoconf gcc g++ make \
    libpq-dev libonig-dev libzip-dev libicu-dev \
    libssl-dev libxml2-dev libcurl4-openssl-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

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
