# ÉTAPE 1: Dépendances PHP (Composer)
# Utilise une image PHP officielle correspondant à votre composer.json (^8.4)
FROM composer:2.7 as vendor

WORKDIR /app

# Copier uniquement les fichiers de dépendances pour optimiser le cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances de production uniquement
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist --ignore-platform-reqs


# ÉTAPE 2: Assets Frontend (NPM)
FROM node:20-alpine as frontend

WORKDIR /app

# Copier les fichiers de dépendances Node
COPY package.json package-lock.json ./

# Installer les dépendances
RUN npm install

# Copier le reste des fichiers nécessaires à la compilation
COPY . .

# Compiler les assets pour la production
RUN npm run build


# ÉTAPE 3: Image finale de production (Apache + PHP)
# Utilise une image officielle qui combine Apache et PHP
FROM php:8.4-apache

# Variables d'environnement pour l'utilisateur Apache
ENV APP_USER=www-data
ENV APP_GROUP=www-data
ENV APP_HOME=/var/www/html

# Installation des extensions PHP nécessaires pour Laravel et vos dépendances (dompdf, excel, backup)
# - pdo_pgsql: Pour PostgreSQL
# - bcmath, pcntl: Courant pour Laravel
# - zip, gd: Souvent requis par les packages de dépendances
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    bcmath \
    pcntl \
    zip \
    gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuration d'Apache
# Activer le module de réécriture d'URL pour les "pretty URLs" de Laravel
RUN a2enmod rewrite
# Remplacer la configuration du site par défaut par la nôtre
COPY docker/production/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR ${APP_HOME}

# Copier le code de l'application (sans les dépendances/assets déjà traités)
COPY --chown=${APP_USER}:${APP_GROUP} . .

# Copier les dépendances Composer depuis l'étape 'vendor'
COPY --from=vendor /app/vendor ./vendor

# Copier les assets compilés depuis l'étape 'frontend'
COPY --from=frontend /app/public/build ./public/build

# Définir les permissions correctes pour les dossiers de stockage et de cache de Laravel
# C'est une étape CRUCIALE pour que l'application puisse écrire des logs, sessions, etc.
RUN chown -R ${APP_USER}:${APP_GROUP} ${APP_HOME}/storage ${APP_HOME}/bootstrap/cache \
    && chmod -R 775 ${APP_HOME}/storage ${APP_HOME}/bootstrap/cache

# Exposer le port 80 (port standard d'Apache)
EXPOSE 80

# Script d'entrée qui s'exécute avant de lancer le serveur.
# Idéal pour exécuter les migrations de base de données.
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Commande par défaut pour démarrer le serveur Apache.
# Render exécutera cette commande pour lancer votre service.
CMD ["apache2-foreground"]
