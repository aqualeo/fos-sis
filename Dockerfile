FROM php:8.2-apache

# Install system dependencies required for PHP extensions and PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    postgresql-client \
    locales \
    unzip \
    git \
 && rm -rf /var/lib/apt/lists/*

# Configure and generate locales
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen && \
    echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# Configure and install PHP extensions needed by RosarioSIS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    gettext \
    zip \
    opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy codebase
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# Create entrypoint script for initial DB setup
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

# Fix MPM conflict by disabling event/worker and enabling prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load \
 && a2enmod mpm_prefork rewrite
