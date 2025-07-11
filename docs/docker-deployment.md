# Tài liệu triển khai dự án QLBV với Docker

## Tổng quan

Dự án QLBV (Quản lý Bảo hiểm Y tế) được containerized bằng Docker để đảm bảo tính nhất quán giữa các môi trường development, staging và production.

### Kiến trúc Docker

```
qlbv-docker/
├── docker-compose.yml          # Cấu hình chính cho tất cả services
├── docker-compose.override.yml # Override cho development
├── docker-compose.prod.yml     # Cấu hình production
├── .env.docker                 # Environment variables cho Docker
├── docker/
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── nginx.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── mysql/
│   │   └── init.sql
│   └── redis/
│       └── redis.conf
└── scripts/
    ├── docker-setup.sh
    ├── docker-deploy.sh
    └── docker-backup.sh
```

## 1. Chuẩn bị môi trường

### 1.1. Yêu cầu hệ thống

- **Docker**: 20.10+ 
- **Docker Compose**: 2.0+
- **RAM**: Tối thiểu 4GB (8GB khuyến nghị)
- **Storage**: 20GB trống
- **OS**: Linux, macOS, Windows 10/11

### 1.2. Cài đặt Docker

#### Ubuntu/Debian
```bash
# Cập nhật package index
sudo apt update

# Cài đặt dependencies
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release

# Thêm Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Thêm Docker repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Cài đặt Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Thêm user vào docker group
sudo usermod -aG docker $USER

# Khởi động Docker
sudo systemctl start docker
sudo systemctl enable docker
```

#### Windows
1. Tải Docker Desktop từ https://www.docker.com/products/docker-desktop
2. Cài đặt và khởi động Docker Desktop
3. Enable WSL 2 backend (khuyến nghị)

#### macOS
```bash
# Sử dụng Homebrew
brew install --cask docker

# Hoặc tải từ https://www.docker.com/products/docker-desktop
```

### 1.3. Kiểm tra cài đặt

```bash
# Kiểm tra Docker version
docker --version
docker-compose --version

# Test Docker
docker run hello-world
```

## 2. Cấu trúc Docker cho dự án QLBV

### 2.1. Dockerfile cho PHP Application

Tạo file `docker/php/Dockerfile`:

```dockerfile
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
```

### 2.2. Cấu hình PHP

Tạo file `docker/php/php.ini`:

```ini
[PHP]
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_vars = 3000
date.timezone = Asia/Ho_Chi_Minh

[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1

[redis]
extension=redis.so
```

### 2.3. Cấu hình Nginx

Tạo file `docker/nginx/Dockerfile`:

```dockerfile
FROM nginx:alpine

# Copy nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Create nginx user
RUN addgroup -g 1000 -S nginx \
    && adduser -S -D -H -u 1000 -h /var/cache/nginx -s /sbin/nologin -G nginx -g nginx nginx

# Create necessary directories
RUN mkdir -p /var/www/public \
    && chown -R nginx:nginx /var/www

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
```

Tạo file `docker/nginx/nginx.conf`:

```nginx
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    # Basic settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 64M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Upstream PHP-FPM
    upstream php-fpm {
        server app:9000;
    }

    # Main server block
    server {
        listen 80;
        server_name localhost;
        root /var/www/public;
        index index.php index.html index.htm;

        # Security
        location ~ /\. {
            deny all;
        }

        # Handle PHP files
        location ~ \.php$ {
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_pass php-fpm;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
        }

        # Handle Laravel routes
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Static files
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }

        # Deny access to sensitive files
        location ~ /\.(htaccess|htpasswd|env|git) {
            deny all;
        }
    }
}
```

### 2.4. Cấu hình MySQL

Tạo file `docker/mysql/init.sql`:

```sql
-- Tạo database
CREATE DATABASE IF NOT EXISTS qlbv CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Tạo user
CREATE USER IF NOT EXISTS 'qlbv_user'@'%' IDENTIFIED BY 'qlbv_password';
GRANT ALL PRIVILEGES ON qlbv.* TO 'qlbv_user'@'%';
FLUSH PRIVILEGES;
```

### 2.5. Cấu hình Redis

Tạo file `docker/redis/redis.conf`:

```conf
# Redis configuration for QLBV
bind 0.0.0.0
port 6379
timeout 0
tcp-keepalive 300
daemonize no
supervised no
pidfile /var/run/redis_6379.pid
loglevel notice
logfile ""
databases 16
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir ./
maxmemory 256mb
maxmemory-policy allkeys-lru
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
```

## 3. Docker Compose Configuration

### 3.1. Docker Compose chính

Tạo file `docker-compose.yml`:

```yaml
version: '3.8'

services:
  # PHP Application
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: qlbv_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    networks:
      - qlbv_network
    depends_on:
      - mysql
      - redis
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    command: php-fpm

  # Nginx Web Server
  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: qlbv_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./storage/logs/nginx:/var/log/nginx
    networks:
      - qlbv_network
    depends_on:
      - app

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: qlbv_mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: qlbv
      MYSQL_USER: qlbv_user
      MYSQL_PASSWORD: qlbv_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql
      - ./storage/mysql/logs:/var/log/mysql
    networks:
      - qlbv_network
    command: --default-authentication-plugin=mysql_native_password

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: qlbv_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
      - ./docker/redis/redis.conf:/usr/local/etc/redis/redis.conf
    networks:
      - qlbv_network
    command: redis-server /usr/local/etc/redis/redis.conf

  # Queue Worker
  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: qlbv_queue
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - qlbv_network
    depends_on:
      - mysql
      - redis
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

  # Scheduler
  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: qlbv_scheduler
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - qlbv_network
    depends_on:
      - mysql
      - redis
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    command: php artisan schedule:work

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local

networks:
  qlbv_network:
    driver: bridge
```

### 3.2. Docker Compose cho Development

Tạo file `docker-compose.override.yml`:

```yaml
version: '3.8'

services:
  app:
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    volumes:
      - ./:/var/www
      - ./storage:/var/www/storage
    command: php-fpm

  nginx:
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./storage/logs/nginx:/var/log/nginx

  mysql:
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: qlbv
      MYSQL_USER: qlbv_user
      MYSQL_PASSWORD: qlbv_password
      MYSQL_ROOT_PASSWORD: root_password

  redis:
    ports:
      - "6379:6379"

  queue:
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

  scheduler:
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    command: php artisan schedule:work
```

### 3.3. Docker Compose cho Production

Tạo file `docker-compose.prod.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/storage
      - ./bootstrap/cache:/var/www/bootstrap/cache
    command: php-fpm

  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./storage:/var/www/storage
      - ./public:/var/www/public
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: qlbv
      MYSQL_USER: qlbv_user
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./backups/mysql:/backups
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    command: redis-server /usr/local/etc/redis/redis.conf

  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/storage
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/storage
    command: php artisan schedule:work

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local

networks:
  qlbv_network:
    driver: bridge
```

## 4. Environment Configuration

### 4.1. Environment file cho Docker

Tạo file `.env.docker`:

```env
# Application
APP_NAME=QLBV
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=qlbv
DB_USERNAME=qlbv_user
DB_PASSWORD=qlbv_password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Broadcasting
BROADCAST_DRIVER=log
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

## 5. Scripts tự động hóa

### 5.1. Script setup Docker

Tạo file `scripts/docker-setup.sh`:

```bash
#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "Setting up QLBV Docker environment..."

# Create necessary directories
print_status "Creating directories..."
mkdir -p storage/logs/nginx
mkdir -p storage/logs/mysql
mkdir -p storage/logs/redis
mkdir -p storage/app/public
mkdir -p bootstrap/cache
mkdir -p backups/mysql
mkdir -p ssl

# Set permissions
print_status "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Copy environment file
if [ ! -f .env ]; then
    print_status "Creating .env file..."
    cp .env.docker .env
fi

# Generate application key
print_status "Generating application key..."
docker-compose run --rm app php artisan key:generate

# Build and start containers
print_status "Building Docker containers..."
docker-compose build

print_status "Starting containers..."
docker-compose up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Run migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force

# Install dependencies
print_status "Installing PHP dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

print_status "Installing Node.js dependencies..."
docker-compose exec app npm install
docker-compose exec app npm run production

# Cache configuration
print_status "Caching configuration..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Create storage link
print_status "Creating storage link..."
docker-compose exec app php artisan storage:link

print_status "Docker setup completed successfully!"
print_status "Application URL: http://localhost:8080"
print_status "MySQL: localhost:3306"
print_status "Redis: localhost:6379"

# Show container status
docker-compose ps
```

### 5.2. Script deploy production

Tạo file `scripts/docker-deploy.sh`:

```bash
#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check environment
if [ -z "$1" ]; then
    print_error "Usage: $0 <environment>"
    print_error "Example: $0 production"
    exit 1
fi

ENVIRONMENT=$1

print_status "Deploying QLBV to $ENVIRONMENT environment..."

# Backup current deployment
print_status "Creating backup..."
./scripts/docker-backup.sh

# Pull latest code
print_status "Pulling latest code..."
git pull origin main

# Build production images
print_status "Building production images..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

# Stop current containers
print_status "Stopping current containers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Start new containers
print_status "Starting new containers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Wait for services
print_status "Waiting for services to be ready..."
sleep 30

# Run migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear and cache configuration
print_status "Caching configuration..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache

# Restart queue workers
print_status "Restarting queue workers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart queue

print_status "Deployment completed successfully!"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### 5.3. Script backup

Tạo file `scripts/docker-backup.sh`:

```bash
#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
DATE=$(date +%Y%m%d_%H%M%S)

print_status "Creating backup directory: $BACKUP_DIR"
mkdir -p $BACKUP_DIR

# Backup database
print_status "Backing up database..."
docker-compose exec mysql mysqldump -u qlbv_user -pqlbv_password qlbv > $BACKUP_DIR/database_$DATE.sql

# Backup application files
print_status "Backing up application files..."
tar -czf $BACKUP_DIR/application_$DATE.tar.gz \
    --exclude=node_modules \
    --exclude=vendor \
    --exclude=.git \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    .

# Backup storage
print_status "Backing up storage..."
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz storage/

# Create backup info
cat > $BACKUP_DIR/backup_info.txt << EOF
Backup created: $(date)
Environment: $(docker-compose exec app php artisan env)
Git commit: $(git rev-parse HEAD)
Git branch: $(git branch --show-current)
EOF

print_status "Backup completed successfully!"
print_status "Backup location: $BACKUP_DIR"

# Clean old backups (keep last 7 days)
find backups -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null || true
```

## 6. Hướng dẫn sử dụng

### 6.1. Development

```bash
# Clone repository
git clone <repository_url> qlbv
cd qlbv

# Setup Docker environment
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh

# Access application
# URL: http://localhost:8080
# MySQL: localhost:3306
# Redis: localhost:6379

# View logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f redis

# Execute commands
docker-compose exec app php artisan migrate
docker-compose exec app composer install
docker-compose exec app npm install
```

### 6.2. Production

```bash
# Deploy to production
chmod +x scripts/docker-deploy.sh
./scripts/docker-deploy.sh production

# Monitor containers
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Backup
chmod +x scripts/docker-backup.sh
./scripts/docker-backup.sh
```

### 6.3. Maintenance

```bash
# Update containers
docker-compose pull
docker-compose build --no-cache
docker-compose up -d

# Clean up
docker system prune -f
docker volume prune -f

# Scale services
docker-compose up -d --scale queue=3
```

## 7. Monitoring và Logging

### 7.1. Logs

```bash
# Application logs
docker-compose logs -f app

# Nginx logs
docker-compose logs -f nginx

# MySQL logs
docker-compose logs -f mysql

# Redis logs
docker-compose logs -f redis

# Queue logs
docker-compose logs -f queue
```

### 7.2. Health checks

```bash
# Check container status
docker-compose ps

# Check resource usage
docker stats

# Check disk usage
docker system df
```

### 7.3. Database management

```bash
# Access MySQL
docker-compose exec mysql mysql -u qlbv_user -p qlbv

# Backup database
docker-compose exec mysql mysqldump -u qlbv_user -p qlbv > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u qlbv_user -p qlbv < backup.sql
```

## 8. Troubleshooting

### 8.1. Common issues

1. **Permission denied errors**:
   ```bash
   sudo chown -R $USER:$USER .
   chmod -R 775 storage bootstrap/cache
   ```

2. **Container won't start**:
   ```bash
   docker-compose logs container_name
   docker-compose down
   docker-compose up -d
   ```

3. **Database connection issues**:
   ```bash
   docker-compose exec app php artisan config:clear
   docker-compose exec app php artisan cache:clear
   ```

4. **Queue not working**:
   ```bash
   docker-compose restart queue
   docker-compose logs queue
   ```

### 8.2. Performance optimization

```bash
# Increase memory limits
docker-compose exec app php -i | grep memory_limit

# Optimize images
docker-compose build --no-cache

# Monitor performance
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
```

## 9. Security considerations

### 9.1. Production security

1. **Use secrets management**:
   ```bash
   # Create secrets
   echo "your_secret_password" | docker secret create db_password -
   
   # Use in docker-compose
   secrets:
     - db_password
   ```

2. **Network security**:
   ```yaml
   networks:
     qlbv_network:
       driver: bridge
       internal: true
   ```

3. **SSL/TLS**:
   ```bash
   # Generate SSL certificate
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout ssl/nginx.key -out ssl/nginx.crt
   ```

### 9.2. Regular maintenance

```bash
# Update base images
docker-compose pull

# Clean unused resources
docker system prune -f

# Monitor disk usage
docker system df

# Check for vulnerabilities
docker scan qlbv_app
```

## Kết luận

Tài liệu này cung cấp hướng dẫn đầy đủ để triển khai dự án QLBV sử dụng Docker. Docker giúp đảm bảo tính nhất quán giữa các môi trường và dễ dàng scale ứng dụng.

### Lưu ý quan trọng:

1. **Development**: Sử dụng `docker-compose.override.yml` cho development
2. **Production**: Sử dụng `docker-compose.prod.yml` cho production
3. **Backup**: Thực hiện backup định kỳ
4. **Monitoring**: Theo dõi logs và performance
5. **Security**: Cập nhật images và dependencies thường xuyên 