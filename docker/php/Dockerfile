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

# Set Oracle environment variables
ENV LD_LIBRARY_PATH="/opt/oracle/instantclient"

# Cài đặt Oracle OCI8 extension (nếu cần)
RUN apt-get update && apt-get install -y \
    libaio1 \
    wget \
    unzip \
    && mkdir -p /opt/oracle \
    && wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-basic-linuxx64.zip \
    && wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip \
    && unzip -o instantclient-basic-linuxx64.zip \
    && unzip -o instantclient-sdk-linuxx64.zip \
    && mv instantclient_23_8 /opt/oracle/instantclient \
    && ln -s /opt/oracle/instantclient/libclntsh.so.23.1 /usr/lib/libclntsh.so \
    && ln -s /opt/oracle/instantclient/libocci.so.23.1 /usr/lib/libocci.so \
    && chmod 755 /opt/oracle/instantclient/*.so \
    && echo "extension=oci8.so" > /usr/local/etc/php/conf.d/oci8.ini \
    && rm instantclient-basic-linuxx64.zip instantclient-sdk-linuxx64.zip

# Cài đặt Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Cài đặt OCI8 extension
RUN echo "instantclient,/opt/oracle/instantclient" | pecl install oci8-2.2.0

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cài đặt Node.js
RUN apt-get update && apt-get install -y python2.7 \
    && ln -s /usr/bin/python2.7 /usr/bin/python \
    && curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs \
    && export PYTHON=/usr/bin/python

# Tạo user và thư mục
RUN useradd -G www-data,root -u 1000 -d /home/qlbv qlbv \
    && mkdir -p /home/qlbv/.composer \
    && chown -R qlbv:qlbv /home/qlbv

# Cấu hình PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Cài đặt dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R qlbv:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"] 