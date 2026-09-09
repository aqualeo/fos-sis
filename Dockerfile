FROM php:8.2-apache

# 1. Install dependencies & PostgreSQL client
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

# 3. Install required PHP extensions
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

# 5. Tell Apache to pass PostgreSQL environment variables to PHP
RUN echo "PassEnv PGHOST PGPORT PGUSER PGPASSWORD PGDATABASE PORT" > /etc/apache2/conf-available/railway-env.conf \
 && a2enconf railway-env

# 6. Configure Apache to bind to Railway's dynamic PORT
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 7. Copy application code
COPY . /var/www/html

# 8. Set permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

EXPOSE 80

# 9. Startup script:
#   - Ensure single MPM (prefork)
#   - Check PostgreSQL and import rosariosis.sql if database is empty
#   - Launch Apache
CMD /bin/bash -c "\
  rm -f /etc/apache2/mods-enabled/mpm_* && \
  ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
  ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
  if [ -n \"\$PGHOST\" ]; then \
    echo 'Checking database status...'; \
    TABLE_COUNT=\$(PGPASSWORD=\$PGPASSWORD psql -h \"\$PGHOST\" -p \"\${PGPORT:-5432}\" -U \"\$PGUSER\" -d \"\$PGDATABASE\" -t -c \"SELECT count(*) FROM information_schema.tables WHERE table_schema='public';\" 2>/dev/null | tr -d '[:space:]' || echo '0'); \
    if [ \"\$TABLE_COUNT\" = '0' ] || [ -z \"\$TABLE_COUNT\" ]; then \
      echo 'Database is empty. Initializing rosariosis.sql...'; \
      PGPASSWORD=\$PGPASSWORD psql -h \"\$PGHOST\" -p \"\${PGPORT:-5432}\" -U \"\$PGUSER\" -d \"\$PGDATABASE\" -f /var/www/html/rosariosis.sql || true; \
      echo 'Database initialization complete.'; \
    else \
      echo \"Database already has \$TABLE_COUNT tables. Skipping import.\"; \
    fi; \
  fi && \
  exec apache2-foreground"
