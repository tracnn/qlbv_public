# Hướng dẫn triển khai QLBV trên Ubuntu/Rocky Linux

## Tổng quan

Tài liệu này hướng dẫn triển khai hệ thống QLBV (Quản lý Bệnh viện) trên Ubuntu 20.04/22.04 hoặc Rocky Linux 8/9. Hệ thống sử dụng Laravel 5.5 với PHP 7.4, MySQL, Redis và Nginx.

## Yêu cầu hệ thống

### Phần cứng tối thiểu
- CPU: 2 cores
- RAM: 4GB
- Storage: 20GB
- Network: 100Mbps

### Phần mềm yêu cầu
- Ubuntu 20.04/22.04 hoặc Rocky Linux 8/9
- PHP 7.4+
- MySQL 8.0+
- Redis 6.0+
- Nginx 1.18+
- Composer 2.0+
- Node.js 16+ (cho build assets)

## Phương pháp triển khai

### Phương pháp 1: Triển khai thủ công (Manual Deployment)

#### Bước 1: Chuẩn bị hệ thống

```bash
# Cập nhật hệ thống
sudo apt update && sudo apt upgrade -y  # Ubuntu
# hoặc
sudo dnf update -y  # Rocky Linux

# Cài đặt các package cần thiết
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release
# hoặc
sudo dnf install -y curl wget git unzip epel-release
```

#### Bước 2: Cài đặt PHP 7.4

```bash
# Ubuntu
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php7.4 php7.4-fpm php7.4-cli php7.4-mysql php7.4-xml php7.4-mbstring php7.4-curl php7.4-gd php7.4-zip php7.4-bcmath php7.4-intl php7.4-soap php7.4-ldap php7.4-redis php7.4-opcache

# Rocky Linux
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo dnf module enable php:remi-7.4
sudo dnf install -y php php-fpm php-cli php-mysqlnd php-xml php-mbstring php-curl php-gd php-zip php-bcmath php-intl php-soap php-ldap php-redis php-opcache
```

#### Bước 3: Cài đặt MySQL 8.0

```bash
# Ubuntu
wget https://dev.mysql.com/get/mysql-apt-config_0.8.24-1_all.deb
sudo dpkg -i mysql-apt-config_0.8.24-1_all.deb
sudo apt update
sudo apt install -y mysql-server

# Rocky Linux
sudo dnf install -y mysql-server
sudo systemctl enable mysqld
sudo systemctl start mysqld
```

#### Bước 4: Cài đặt Redis

```bash
# Ubuntu
sudo apt install -y redis-server

# Rocky Linux
sudo dnf install -y redis
sudo systemctl enable redis
sudo systemctl start redis
```

#### Bước 5: Cài đặt Nginx

```bash
# Ubuntu
sudo apt install -y nginx

# Rocky Linux
sudo dnf install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

#### Bước 6: Cài đặt Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Bước 7: Cài đặt Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs  # Ubuntu
# hoặc
sudo dnf install -y nodejs  # Rocky Linux
```

### Phương pháp 2: Triển khai bằng Docker

#### Bước 1: Cài đặt Docker và Docker Compose

```bash
# Cài đặt Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Cài đặt Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

#### Bước 2: Tạo docker-compose.yml

```yaml
version: '3.8'

services:
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

  nginx:
    image: nginx:alpine
    container_name: qlbv_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app
    networks:
      - qlbv_network

  db:
    image: mysql:8.0
    container_name: qlbv_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: qlbv
      MYSQL_USER: qlbv_user
      MYSQL_PASSWORD: qlbv_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"
    networks:
      - qlbv_network

  redis:
    image: redis:6-alpine
    container_name: qlbv_redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
      - ./docker/redis/redis.conf:/usr/local/etc/redis/redis.conf
    command: redis-server /usr/local/etc/redis/redis.conf
    ports:
      - "6379:6379"
    networks:
      - qlbv_network

volumes:
  mysql_data:
  redis_data:

networks:
  qlbv_network:
    driver: bridge
```

## Cấu hình hệ thống

### Cấu hình PHP

Tạo file `/etc/php/7.4/fpm/php.ini` (Ubuntu) hoặc `/etc/php.ini` (Rocky):

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
```

### Cấu hình Nginx

Tạo file `/etc/nginx/sites-available/qlbv`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/qlbv/public;
    index index.php index.html index.htm;

    # Security
    location ~ /\. {
        deny all;
    }

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
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
```

Kích hoạt site:
```bash
sudo ln -s /etc/nginx/sites-available/qlbv /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Cấu hình MySQL

```bash
sudo mysql_secure_installation
```

Tạo database và user:
```sql
CREATE DATABASE qlbv CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'qlbv_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON qlbv.* TO 'qlbv_user'@'localhost';
FLUSH PRIVILEGES;
```

## Triển khai ứng dụng

### Bước 1: Clone và cài đặt

```bash
# Clone project
git clone <repository_url> /var/www/qlbv
cd /var/www/qlbv

# Cài đặt dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run production
```

### Bước 2: Cấu hình môi trường

```bash
# Copy file môi trường
cp docs/.env_example .env

# Tạo application key
php artisan key:generate

# Cấu hình file .env
nano .env
```

Cấu hình `.env`:
```env
APP_NAME=qlbv
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_LOG_LEVEL=debug
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qlbv
DB_USERNAME=qlbv_user
DB_PASSWORD=your_password

BROADCAST_DRIVER=pusher
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=ssl

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret

GOOGLE_RECAPTCHA_KEY=your_recaptcha_key
GOOGLE_RECAPTCHA_SECRET=your_recaptcha_secret

NEXMO_API_KEY=your_nexmo_key
NEXMO_API_SECRET=your_nexmo_secret
NEXMO_FROM_SEND=your_phone_number

SMS_SEND=true
JWT_SECRET=your_jwt_secret

FTP_HOST=your_ftp_host
FTP_PORT=21
FTP_USERNAME=your_ftp_username
FTP_PASSWORD=your_ftp_password
FTP_SSL=false
FTP_PASV=true

Q_HIS_PLUS_URL=http://localhost
Q_HIS_PLUS_PORT=7111
```

### Bước 3: Cài đặt database

```bash
# Chạy migrations
php artisan migrate

# Chạy seeders
php artisan db:seed

# Tạo storage link
php artisan storage:link
```

### Bước 4: Cấu hình permissions

```bash
# Tạo user web
sudo useradd -r -s /bin/false www-data

# Set permissions
sudo chown -R www-data:www-data /var/www/qlbv
sudo chmod -R 755 /var/www/qlbv
sudo chmod -R 775 /var/www/qlbv/storage
sudo chmod -R 775 /var/www/qlbv/bootstrap/cache
```

### Bước 5: Cấu hình services

```bash
# Kích hoạt và start services
sudo systemctl enable php7.4-fpm
sudo systemctl start php7.4-fpm
sudo systemctl enable nginx
sudo systemctl start nginx
sudo systemctl enable mysql
sudo systemctl start mysql
sudo systemctl enable redis
sudo systemctl start redis
```

## Cấu hình SSL (HTTPS)

### Sử dụng Let's Encrypt

```bash
# Cài đặt Certbot
sudo apt install -y certbot python3-certbot-nginx

# Tạo SSL certificate
sudo certbot --nginx -d your-domain.com

# Tự động renew
sudo crontab -e
# Thêm dòng: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Monitoring và Logging

### Cấu hình log rotation

Tạo file `/etc/logrotate.d/qlbv`:
```
/var/www/qlbv/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### Cấu hình monitoring

```bash
# Cài đặt monitoring tools
sudo apt install -y htop iotop nethogs

# Cấu hình fail2ban
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## Backup và Recovery

### Tạo script backup

Tạo file `/usr/local/bin/qlbv-backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backup/qlbv"
DATE=$(date +%Y%m%d_%H%M%S)

# Tạo thư mục backup
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u qlbv_user -p'your_password' qlbv > $BACKUP_DIR/db_backup_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/qlbv

# Xóa backup cũ (giữ 30 ngày)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/qlbv-backup.sh
sudo crontab -e
# Thêm dòng: 0 2 * * * /usr/local/bin/qlbv-backup.sh
```

## Troubleshooting

### Kiểm tra logs

```bash
# Laravel logs
tail -f /var/www/qlbv/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### Kiểm tra services

```bash
# Kiểm tra status
sudo systemctl status nginx
sudo systemctl status php7.4-fpm
sudo systemctl status mysql
sudo systemctl status redis

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo systemctl restart mysql
sudo systemctl restart redis
```

## Bảo mật

### Firewall

```bash
# Cài đặt UFW
sudo apt install -y ufw

# Cấu hình firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Security headers

Thêm vào Nginx config:
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
```

## Performance Optimization

### PHP OPcache

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Nginx optimization

```nginx
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
```

## Kết luận

Tài liệu này cung cấp hướng dẫn chi tiết để triển khai hệ thống QLBV trên Ubuntu/Rocky Linux. Đảm bảo thực hiện đầy đủ các bước bảo mật và monitoring để hệ thống hoạt động ổn định trong môi trường production. 