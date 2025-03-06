# Utilise une image officielle PHP avec FPM
FROM php:8.4-fpm

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    unzip \
    git \
    curl \
    libonig-dev


# Nettoyage des caches pour alléger l'image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer depuis l'image officielle Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers de l'application dans le container
COPY . .

# Installer les dépendances PHP via Composer (sans les packages de développement)
RUN composer install --optimize-autoloader --no-dev

# Donner les droits appropriés au répertoire de l'application
RUN chown -R www-data:www-data /var/www

# Exposer le port PHP-FPM (port 9000)
EXPOSE 9000

# Lancer PHP-FPM
CMD ["php-fpm"]
