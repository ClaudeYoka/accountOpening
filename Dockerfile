# Dockerfile pour l'application PHP
FROM php:8.1-apache

# Installer extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code source
COPY . /var/www/html

# Installer dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80