# Tài liệu triển khai dự án QLBV trên Ubuntu

## Tổng quan dự án

Dự án QLBV (Quản lý Bảo hiểm Y tế) là một ứng dụng Laravel 5.5 được phát triển để quản lý và kiểm tra hồ sơ bảo hiểm y tế, tích hợp với hệ thống HIS (Hospital Information System).

### Yêu cầu hệ thống

- **Hệ điều hành**: Ubuntu 18.04 LTS trở lên
- **PHP**: 7.0.0 trở lên (khuyến nghị PHP 7.4)
- **Database**: MySQL 5.7+ hoặc Oracle Database
- **Web Server**: Nginx hoặc Apache
- **Redis**: 3.0+ (cho cache và queue)
- **Node.js**: 12+ (cho build assets)

## 1. Chuẩn bị hệ thống Ubuntu

### 1.1. Cập nhật hệ thống

```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2. Cài đặt các package cần thiết

```bash
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release
```

### 1.3. Cài đặt PHP 7.4

```bash
# Thêm repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Cài đặt PHP và các extension cần thiết
sudo apt install -y php7.4 php7.4-fpm php7.4-cli php7.4-mysql php7.4-pgsql php7.4-sqlite3 \
php7.4-bcmath php7.4-mbstring php7.4-xml php7.4-curl php7.4-json php7.4-tokenizer \
php7.4-zip php7.4-gd php7.4-intl php7.4-soap php7.4-xmlrpc php7.4-ldap \
php7.4-imap php7.4-redis php7.4-memcached php7.4-oci8

# Cài đặt Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 1.4. Cài đặt MySQL

```bash
sudo apt install -y mysql-server mysql-client

# Bảo mật MySQL
sudo mysql_secure_installation

# Tạo database và user
sudo mysql -u root -p
```

```sql
CREATE DATABASE qlbv CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'qlbv_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON qlbv.* TO 'qlbv_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 1.5. Cài đặt Redis

```bash
sudo apt install -y redis-server

# Cấu hình Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 1.6. Cài đặt Node.js và NPM

```bash
# Cài đặt Node.js 16.x
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# Kiểm tra phiên bản
node --version
npm --version
```

### 1.7. Cài đặt Nginx

```bash
sudo apt install -y nginx

# Cấu hình Nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

## 2. Cài đặt Oracle Client (nếu sử dụng Oracle Database)

### 2.1. Tải Oracle Instant Client

```bash
cd /tmp
wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-basic-linuxx64.zip
wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip

# Giải nén
unzip instantclient-basic-linuxx64.zip
unzip instantclient-sdk-linuxx64.zip

# Di chuyển vào thư mục system
sudo mv instantclient_21_1 /opt/oracle/instantclient

# Cấu hình environment variables
echo 'export ORACLE_HOME=/opt/oracle/instantclient' | sudo tee -a /etc/environment
echo 'export LD_LIBRARY_PATH=/opt/oracle/instantclient:$LD_LIBRARY_PATH' | sudo tee -a /etc/environment
echo 'export PATH=/opt/oracle/instantclient:$PATH' | sudo tee -a /etc/environment

# Reload environment
source /etc/environment
```

### 2.2. Cài đặt PHP OCI8 extension

```bash
sudo pecl install oci8-2.2.0

# Thêm extension vào PHP
echo "extension=oci8.so" | sudo tee -a /etc/php/7.4/fpm/conf.d/20-oci8.ini
echo "extension=oci8.so" | sudo tee -a /etc/php/7.4/cli/conf.d/20-oci8.ini
```

## 3. Triển khai ứng dụng

### 3.1. Tạo user cho ứng dụng

```bash
sudo adduser qlbv
sudo usermod -aG sudo qlbv
```

### 3.2. Clone dự án

```bash
cd /var/www
sudo git clone <repository_url> qlbv
sudo chown -R qlbv:qlbv qlbv
```

### 3.3. Cài đặt dependencies

```bash
cd /var/www/qlbv

# Cài đặt PHP dependencies
composer install --no-dev --optimize-autoloader

# Cài đặt Node.js dependencies
npm install
npm run production
```

### 3.4. Cấu hình môi trường

```bash
# Copy file environment
cp .env.example .env

# Tạo application key
php artisan key:generate

# Cấu hình file .env
sudo nano .env
```

Cấu hình file `.env`:

```env
APP_NAME=QLBV
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_URL=http://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qlbv
DB_USERNAME=qlbv_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 3.5. Cấu hình quyền thư mục

```bash
# Cấu hình quyền
sudo chown -R www-data:www-data /var/www/qlbv
sudo chmod -R 755 /var/www/qlbv
sudo chmod -R 775 /var/www/qlbv/storage
sudo chmod -R 775 /var/www/qlbv/bootstrap/cache

# Tạo symbolic link cho storage
php artisan storage:link
```

### 3.6. Chạy migrations và seeders

```bash
# Chạy migrations
php artisan migrate --force

# Chạy seeders (nếu cần)
php artisan db:seed --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Cấu hình Nginx

### 4.1. Tạo virtual host

```bash
sudo nano /etc/nginx/sites-available/qlbv
```

Nội dung cấu hình:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/qlbv/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.2. Kích hoạt site

```bash
sudo ln -s /etc/nginx/sites-available/qlbv /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 5. Cấu hình PHP-FPM

### 5.1. Tối ưu PHP-FPM

```bash
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
```

Cấu hình tối ưu:

```ini
[www]
user = www-data
group = www-data
listen = /run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 5.2. Tối ưu PHP

```bash
sudo nano /etc/php/7.4/fpm/php.ini
```

Các cấu hình quan trọng:

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_vars = 3000
```

### 5.3. Restart PHP-FPM

```bash
sudo systemctl restart php7.4-fpm
```

## 6. Cấu hình Queue và Cron Jobs

### 6.1. Cấu hình Supervisor cho Queue

```bash
sudo apt install -y supervisor
```

Tạo file cấu hình:

```bash
sudo nano /etc/supervisor/conf.d/qlbv-worker.conf
```

Nội dung:

```ini
[program:qlbv-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/qlbv/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/qlbv/storage/logs/worker.log
stopwaitsecs=3600
```

### 6.2. Cấu hình Cron Jobs

```bash
crontab -u www-data -e
```

Thêm các cron jobs:

```bash
# Laravel Scheduler
* * * * * cd /var/www/qlbv && php artisan schedule:run >> /dev/null 2>&1

# Queue monitoring
*/5 * * * * cd /var/www/qlbv && php artisan queue:monitor redis --max=100
```

### 6.3. Khởi động Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qlbv-worker:*
```

## 7. Cấu hình bảo mật

### 7.1. Cấu hình Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 7.2. Cấu hình SSL với Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 7.3. Tối ưu bảo mật Nginx

```bash
sudo nano /etc/nginx/conf.d/security.conf
```

Nội dung:

```nginx
# Security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

# Hide server version
server_tokens off;
```

## 8. Monitoring và Logging

### 8.1. Cấu hình log rotation

```bash
sudo nano /etc/logrotate.d/qlbv
```

Nội dung:

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

### 8.2. Cấu hình monitoring

```bash
# Cài đặt monitoring tools
sudo apt install -y htop iotop nethogs

# Cấu hình log monitoring
sudo nano /etc/rsyslog.d/qlbv.conf
```

## 9. Backup và Recovery

### 9.1. Script backup tự động

```bash
sudo nano /usr/local/bin/qlbv-backup.sh
```

Nội dung script:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/qlbv"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="qlbv"

# Tạo thư mục backup
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u qlbv_user -p'your_password' $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Backup application files
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz -C /var/www qlbv

# Xóa backup cũ hơn 30 ngày
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

### 9.2. Cấu hình cron cho backup

```bash
# Thêm vào crontab
0 2 * * * /usr/local/bin/qlbv-backup.sh >> /var/log/qlbv-backup.log 2>&1
```

## 10. Troubleshooting

### 10.1. Kiểm tra logs

```bash
# Laravel logs
tail -f /var/www/qlbv/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log

# Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log
```

### 10.2. Kiểm tra performance

```bash
# Kiểm tra memory usage
free -h

# Kiểm tra disk usage
df -h

# Kiểm tra CPU usage
top

# Kiểm tra network connections
netstat -tulpn
```

### 10.3. Common issues và solutions

1. **Permission denied errors**:
   ```bash
   sudo chown -R www-data:www-data /var/www/qlbv
   sudo chmod -R 755 /var/www/qlbv
   sudo chmod -R 775 /var/www/qlbv/storage
   ```

2. **Queue không hoạt động**:
   ```bash
   sudo supervisorctl restart qlbv-worker:*
   sudo supervisorctl status
   ```

3. **Database connection errors**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## 11. Maintenance

### 11.1. Cập nhật ứng dụng

```bash
cd /var/www/qlbv

# Backup trước khi update
php artisan backup:run

# Pull code mới
git pull origin main

# Cài đặt dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run production

# Chạy migrations
php artisan migrate --force

# Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php7.4-fpm
sudo supervisorctl restart qlbv-worker:*
```

### 11.2. Monitoring commands

```bash
# Kiểm tra queue status
php artisan queue:work --once

# Kiểm tra scheduled tasks
php artisan schedule:list

# Kiểm tra cache status
php artisan cache:table
php artisan config:cache

# Kiểm tra storage links
php artisan storage:link
```

## Kết luận

Tài liệu này cung cấp hướng dẫn chi tiết để triển khai dự án QLBV trên Ubuntu. Đảm bảo thay đổi các thông tin cấu hình phù hợp với môi trường thực tế của bạn.

### Lưu ý quan trọng:

1. **Bảo mật**: Luôn sử dụng mật khẩu mạnh và cập nhật thường xuyên
2. **Backup**: Thực hiện backup định kỳ và test restore
3. **Monitoring**: Theo dõi performance và logs thường xuyên
4. **Updates**: Cập nhật hệ thống và dependencies định kỳ
5. **Documentation**: Cập nhật tài liệu khi có thay đổi 