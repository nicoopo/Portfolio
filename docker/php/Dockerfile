FROM php:8.5-apache

# Activation mod_rewrite Apache (indispensable pour Symfony)
RUN a2enmod rewrite

# Dépendances système + extensions PHP (pas de pgsql : le portfolio n'a pas de base de données)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        zip \
        unzip \
        git \
    && docker-php-ext-install \
        intl \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Opcache (config dev) — intégré à PHP depuis la 8.5, pas besoin de docker-php-ext-install
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=1\n\
opcache.revalidate_freq=0" > /usr/local/etc/php/conf.d/opcache.ini

# Memory limit (Dompdf est gourmand)
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

# Copie du vhost Apache
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
