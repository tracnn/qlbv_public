# Triển khai QLBV trên Ubuntu/Rocky Linux

## Tổng quan

Hệ thống QLBV (Quản lý Bệnh viện) được xây dựng trên Laravel 5.5 với PHP 7.4, MySQL, Redis và Nginx.

## Phương pháp triển khai nhanh

### Sử dụng script tự động

```bash
# Triển khai thủ công trên Ubuntu
chmod +x scripts/deploy.sh
./scripts/deploy.sh ubuntu manual

# Triển khai bằng Docker trên Rocky Linux
./scripts/deploy.sh rocky docker
```

### Triển khai thủ công

1. **Cài đặt dependencies**
   ```bash
   # Ubuntu
   sudo apt update
   sudo apt install -y php7.4 php7.4-fpm php7.4-mysql nginx mysql-server redis-server
   
   # Rocky Linux
   sudo dnf install -y php php-fpm php-mysqlnd nginx mysql-server redis
   ```

2. **Cấu hình môi trường**
   ```bash
   cp docs/.env_example .env
   php artisan key:generate
   ```

3. **Cài đặt database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Khởi động services**
   ```bash
   sudo systemctl enable nginx php7.4-fpm mysql redis
   sudo systemctl start nginx php7.4-fpm mysql redis
   ```

### Triển khai bằng Docker

```bash
# Khởi động containers
docker-compose up -d

# Chạy migrations
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## Cấu hình SSL

```bash
# Cài đặt Certbot
sudo apt install -y certbot python3-certbot-nginx

# Tạo SSL certificate
sudo certbot --nginx -d your-domain.com
```

## Monitoring

```bash
# Kiểm tra logs
tail -f storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log

# Kiểm tra services
sudo systemctl status nginx php7.4-fpm mysql redis
```

## Backup

Script backup tự động được cấu hình và chạy hàng ngày lúc 2:00 AM.

```bash
# Backup thủ công
sudo /usr/local/bin/qlbv-backup.sh
```

## Troubleshooting

### Lỗi thường gặp

1. **Permission denied**
   ```bash
   sudo chown -R www-data:www-data /var/www/qlbv
   sudo chmod -R 775 /var/www/qlbv/storage
   ```

2. **Database connection failed**
   ```bash
   sudo mysql_secure_installation
   sudo mysql -u root -p
   ```

3. **Nginx không load**
   ```bash
   sudo nginx -t
   sudo systemctl restart nginx
   ```

### Kiểm tra logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log
```

## Tài liệu chi tiết

Xem file `docs/Deployment-Guide-Ubuntu-Rocky.md` để biết thêm chi tiết về:
- Cấu hình chi tiết từng service
- Tối ưu hóa hiệu suất
- Bảo mật hệ thống
- Monitoring và alerting
- Backup và recovery

## Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. Log files trong `/var/log/` và `storage/logs/`
2. Status của các services
3. Cấu hình firewall và network
4. Permissions của files và directories 