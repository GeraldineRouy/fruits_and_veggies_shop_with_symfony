FROM dunglas/frankenphp:php8.4

LABEL Description="Fruits & Veggies Shop" Vendor="Fruits & Veggies"

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install utilities and PostgreSQL client
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed for Symfony + PostgreSQL
RUN install-php-extensions \
    intl \
    zip \
    pdo_pgsql \
    pgsql \
    opcache \
    apcu

# Install Composer
RUN set -eux; \
    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    composer --version

# Configure PHP for development
RUN echo "date.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini
COPY docker-php.ini /usr/local/etc/php/conf.d/docker-php.ini

WORKDIR /app

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/docker-entrypoint.sh"]
