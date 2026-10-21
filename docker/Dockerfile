# Images de StockManager : un seul fichier, une cible par usage
#   app : PHP-FPM de production (code, dépendances Composer, assets compilés), aussi utilisée par le worker et le scheduler
#   web : Nginx de production (fichiers de public/ et assets compilés)
#   dev : PHP-FPM de développement, avec Composer, Node et Xdebug à la demande ; le code est monté depuis l'hôte

# Même version de Node que sous Windows (Laragon) et dans la CI
FROM node:22-alpine AS node

# --- Base commune : PHP 8.4 et ses extensions
FROM php:8.4-fpm-alpine AS base

# install-php-extensions installe aussi les bibliothèques nécessaires et supprime les outils de compilation
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd intl pcntl pdo_pgsql redis zip \
    # pg_dump (sauvegardes) de la version du serveur PostgreSQL ; cgi-fcgi pour la sonde de santé
    && apk add --no-cache fcgi postgresql16-client

COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=755 docker/php-fpm-healthcheck /usr/local/bin/
WORKDIR /var/www

# --- Assets (Vite), compilés une fois pour les images app et web
FROM node AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Dépendances Composer, sans les paquets de développement
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Installées avant la copie du code : une modification du code ne relance pas le téléchargement
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --no-progress --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize

# --- Production : PHP-FPM, worker et scheduler
FROM base AS app
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Le code appartient à root : PHP le lit sans pouvoir le modifier, seuls storage et bootstrap/cache lui sont ouverts
COPY --from=vendor /var/www /var/www
COPY --from=assets /app/public/build public/build
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs \
    # Nginx traverse storage/app pour servir storage/app/public (/storage) ; l'amorçage de Laravel pendant le build
    # a créé storage/app/private et ses parents en 700
    && chmod 755 storage storage/app \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint
USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# --- Production : Nginx
FROM nginx:alpine AS web
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY public /var/www/public
COPY --from=assets /app/public/build /var/www/public/build

# --- Développement
FROM base AS dev
ARG UID=1000
ARG GID=1000
# Désactivé par défaut : il ralentit chaque requête
ARG XDEBUG_ENABLED=false
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && apk add --no-cache bash libstdc++ \
    && ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    # Utilisateur de l'hôte : les fichiers créés dans le projet (vendor, node_modules, caches) lui appartiennent
    && addgroup -g "$GID" dev && adduser -D -u "$UID" -G dev -s /bin/bash dev \
    # Point de montage du volume node_modules : un volume neuf reprend ce propriétaire (sinon root, et npm échoue)
    && mkdir -p /var/www/node_modules && chown dev:dev /var/www/node_modules \
    # Plus de processus PHP : les pages font plusieurs requêtes à la fois, lentes sur un disque Windows partagé
    && printf '[www]\npm.max_children = 20\n' > /usr/local/etc/php-fpm.d/zz-dev.conf \
    && if [ "$XDEBUG_ENABLED" = "true" ]; then \
        install-php-extensions xdebug \
        && printf '%s\n' 'xdebug.mode=develop,debug,coverage' 'xdebug.start_with_request=yes' \
            'xdebug.client_host=host.docker.internal' 'xdebug.log_level=0' > "$PHP_INI_DIR/conf.d/zz-xdebug.ini"; \
    fi
COPY --chmod=755 docker/entrypoint-dev.sh /usr/local/bin/entrypoint
USER dev
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
