FROM php:8.2-apache

# Install dependencies and PostgreSQL client
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

# Generate locales for RosarioSIS
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen && \
    echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# Install PHP extensions required by RosarioSIS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    gettext \
    zip

# Fix MPM conflict and enable Apache rewrite
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load \
 && a2enmod mpm_prefork rewrite

# Configure Apache to bind to Railway's dynamic PORT
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copy application files
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
