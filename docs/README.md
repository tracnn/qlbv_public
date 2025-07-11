# Tài liệu triển khai dự án QLBV

## Tổng quan

Thư mục này chứa tài liệu triển khai dự án QLBV (Quản lý Bảo hiểm Y tế) trên các môi trường khác nhau.

## Cấu trúc tài liệu

### 1. `deployment-ubuntu.md`
Tài liệu chi tiết hướng dẫn triển khai dự án trên Ubuntu Server, bao gồm:

- **Chuẩn bị hệ thống**: Cài đặt các package cần thiết
- **Cài đặt môi trường**: PHP, MySQL, Redis, Nginx, Node.js
- **Triển khai ứng dụng**: Clone code, cài đặt dependencies, cấu hình
- **Cấu hình web server**: Nginx virtual host, SSL
- **Cấu hình queue**: Supervisor, cron jobs
- **Bảo mật**: Firewall, security headers
- **Monitoring**: Log rotation, backup scripts
- **Troubleshooting**: Xử lý các lỗi thường gặp

### 2. `deploy.sh`
Script tự động hóa quá trình triển khai trên Ubuntu, bao gồm:

- Tự động cài đặt tất cả dependencies
- Cấu hình database và web server
- Thiết lập queue workers và cron jobs
- Tạo scripts backup và monitoring
- Cấu hình bảo mật cơ bản

## Hướng dẫn sử dụng

### Triển khai thủ công

1. **Đọc tài liệu**: Xem file `deployment-ubuntu.md` để hiểu rõ quy trình
2. **Chuẩn bị server**: Đảm bảo server đáp ứng yêu cầu hệ thống
3. **Thực hiện từng bước**: Làm theo hướng dẫn trong tài liệu
4. **Kiểm tra**: Verify các service hoạt động đúng

### Triển khai tự động

1. **Chuẩn bị thông tin**:
   ```bash
   DOMAIN="your-domain.com"
   DB_PASSWORD="your_secure_password"
   APP_KEY="base64:your_generated_key"
   ```

2. **Chạy script**:
   ```bash
   chmod +x docs/deploy.sh
   ./docs/deploy.sh $DOMAIN $DB_PASSWORD $APP_KEY
   ```

3. **Kiểm tra kết quả**:
   ```bash
   /usr/local/bin/qlbv-monitor.sh
   ```

## Yêu cầu hệ thống

### Tối thiểu
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB
- **OS**: Ubuntu 18.04 LTS trở lên

### Khuyến nghị
- **CPU**: 4 cores
- **RAM**: 8GB
- **Storage**: 50GB SSD
- **OS**: Ubuntu 20.04 LTS

## Cấu hình môi trường

### Development
- `APP_ENV=local`
- `APP_DEBUG=true`
- `CACHE_DRIVER=file`
- `QUEUE_CONNECTION=sync`

### Production
- `APP_ENV=production`
- `APP_DEBUG=false`
- `CACHE_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

## Database

### MySQL (Mặc định)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qlbv
DB_USERNAME=qlbv_user
DB_PASSWORD=your_password
```

### Oracle (Tùy chọn)
```env
DB_CONNECTION=oracle
DB_HOST=your_oracle_host
DB_PORT=1521
DB_DATABASE=your_service_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Services

### Web Server
- **Nginx**: Port 80/443
- **PHP-FPM**: Unix socket

### Database
- **MySQL**: Port 3306
- **Redis**: Port 6379

### Queue
- **Supervisor**: Quản lý queue workers
- **Cron**: Laravel scheduler

## Monitoring

### Scripts có sẵn
- `/usr/local/bin/qlbv-monitor.sh`: Kiểm tra trạng thái hệ thống
- `/usr/local/bin/qlbv-backup.sh`: Backup tự động

### Logs
- **Application**: `/var/www/qlbv/storage/logs/`
- **Nginx**: `/var/log/nginx/`
- **PHP-FPM**: `/var/log/php7.4-fpm.log`
- **Supervisor**: `/var/log/supervisor/`

## Bảo mật

### Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com
```

### Security Headers
Đã được cấu hình trong Nginx virtual host

## Backup

### Tự động
- **Database**: Hàng ngày lúc 2:00 AM
- **Files**: Backup toàn bộ application
- **Retention**: 30 ngày

### Thủ công
```bash
/usr/local/bin/qlbv-backup.sh
```

## Troubleshooting

### Lỗi thường gặp

1. **Permission denied**
   ```bash
   sudo chown -R www-data:www-data /var/www/qlbv
   sudo chmod -R 755 /var/www/qlbv
   ```

2. **Queue không hoạt động**
   ```bash
   sudo supervisorctl restart qlbv-worker:*
   sudo supervisorctl status
   ```

3. **Database connection**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Kiểm tra logs
```bash
# Laravel logs
tail -f /var/www/qlbv/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log
```

## Maintenance

### Cập nhật ứng dụng
```bash
cd /var/www/qlbv
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run production
php artisan migrate --force
php artisan config:cache
sudo systemctl restart php7.4-fpm
sudo supervisorctl restart qlbv-worker:*
```

### Kiểm tra performance
```bash
# Memory usage
free -h

# Disk usage
df -h

# CPU usage
top

# Network connections
netstat -tulpn
```

## Liên hệ

Nếu gặp vấn đề trong quá trình triển khai, vui lòng:

1. Kiểm tra logs để xác định nguyên nhân
2. Tham khảo phần Troubleshooting
3. Liên hệ team phát triển với thông tin chi tiết về lỗi

## Changelog

### Version 1.0.0
- Tạo tài liệu triển khai cơ bản
- Script tự động triển khai
- Cấu hình monitoring và backup
- Hướng dẫn troubleshooting 