FROM php:8.2-apache

# 1. Install system packages and PostgreSQL client
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

# 2. Configure locales
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen && \
    echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# 3. Install PHP extensions for RosarioSIS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    gettext \
    zip

# 4. Enable mod_rewrite
RUN a2enmod rewrite

# 5. Bind Apache to Railway's dynamic PORT
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 6. Copy application code
COPY . /var/www/html

# 7. Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

EXPOSE 80

# 8. Clean conflicting MPMs right before starting Apache at runtime
CMD /bin/bash -c "\
  rm -f /etc/apache2/mods-enabled/mpm_* && \
  ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
  ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
  exec apache2-foreground"
