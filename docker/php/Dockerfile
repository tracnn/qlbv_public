FROM php:7.4-fpm

# Cài đặt system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libmcrypt-dev \
    libicu-dev \
    libldap2-dev \
    libxml2-dev \
    libxslt1-dev \
    libgmp-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    soap \
    xmlrpc \
    ldap \
    xsl \
    gmp \
    zip \
    opcache

# Cài đặt Oracle OCI8 extension (nếu cần)
RUN apt-get update && apt-get install -y \
    libaio1 \
    wget \
    unzip \
    && wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-basic-linuxx64.zip \
    && unzip instantclient-basic-linuxx64.zip \
    && mv instantclient_21_1 /opt/oracle/instantclient \
    && echo "extension=oci8.so" > /usr/local/etc/php/conf.d/oci8.ini \
    && rm instantclient-basic-linuxx64.zip

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cài đặt Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs

# Tạo user cho application
RUN useradd -G www-data,root -u 1000 -d /home/qlbv qlbv \
    && mkdir -p /home/qlbv/.composer \
    && chown -R qlbv:qlbv /home/qlbv

# Cài đặt Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Cấu hình PHP
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Cài đặt dependencies
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run production

# Set permissions
RUN chown -R qlbv:qlbv /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"] 