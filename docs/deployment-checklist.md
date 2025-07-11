# Checklist triển khai dự án QLBV

## Trước khi triển khai

### ✅ Chuẩn bị server
- [ ] Server Ubuntu 18.04 LTS trở lên
- [ ] Tối thiểu 4GB RAM, 20GB storage
- [ ] Kết nối internet ổn định
- [ ] Domain name đã được cấu hình DNS
- [ ] SSH access với quyền sudo

### ✅ Chuẩn bị thông tin
- [ ] Domain name cho ứng dụng
- [ ] Mật khẩu database (mạnh, ít nhất 12 ký tự)
- [ ] Laravel application key
- [ ] Thông tin SMTP (nếu cần gửi email)
- [ ] Thông tin Oracle (nếu sử dụng Oracle Database)

## Cài đặt hệ thống

### ✅ Cập nhật hệ thống
- [ ] `sudo apt update && sudo apt upgrade -y`
- [ ] Kiểm tra không có lỗi sau khi update

### ✅ Cài đặt PHP 7.4
- [ ] Thêm repository PHP: `sudo add-apt-repository ppa:ondrej/php -y`
- [ ] Cài đặt PHP và extensions cần thiết
- [ ] Cài đặt Composer
- [ ] Kiểm tra PHP version: `php -v`
- [ ] Kiểm tra Composer: `composer --version`

### ✅ Cài đặt MySQL
- [ ] Cài đặt MySQL server và client
- [ ] Bảo mật MySQL: `sudo mysql_secure_installation`
- [ ] Tạo database `qlbv`
- [ ] Tạo user `qlbv_user` với quyền đầy đủ
- [ ] Test kết nối database

### ✅ Cài đặt Redis
- [ ] Cài đặt Redis server
- [ ] Enable và start Redis service
- [ ] Test kết nối Redis: `redis-cli ping`

### ✅ Cài đặt Node.js
- [ ] Cài đặt Node.js 16.x
- [ ] Cài đặt NPM
- [ ] Kiểm tra version: `node --version && npm --version`

### ✅ Cài đặt Nginx
- [ ] Cài đặt Nginx
- [ ] Enable và start Nginx service
- [ ] Kiểm tra Nginx status

### ✅ Cài đặt Oracle Client (nếu cần)
- [ ] Tải Oracle Instant Client
- [ ] Cài đặt PHP OCI8 extension
- [ ] Cấu hình environment variables
- [ ] Test kết nối Oracle

## Triển khai ứng dụng

### ✅ Chuẩn bị thư mục
- [ ] Tạo user `qlbv`
- [ ] Tạo thư mục `/var/www/qlbv`
- [ ] Clone/copy code vào thư mục
- [ ] Cấu hình quyền thư mục

### ✅ Cài đặt dependencies
- [ ] Chạy `composer install --no-dev --optimize-autoloader`
- [ ] Chạy `npm install`
- [ ] Chạy `npm run production`
- [ ] Kiểm tra không có lỗi

### ✅ Cấu hình môi trường
- [ ] Copy `.env.example` thành `.env`
- [ ] Cấu hình `APP_NAME`, `APP_ENV`, `APP_DEBUG`
- [ ] Cấu hình database connection
- [ ] Cấu hình Redis connection
- [ ] Cấu hình mail settings
- [ ] Generate application key: `php artisan key:generate`

### ✅ Cấu hình quyền
- [ ] `sudo chown -R www-data:www-data /var/www/qlbv`
- [ ] `sudo chmod -R 755 /var/www/qlbv`
- [ ] `sudo chmod -R 775 /var/www/qlbv/storage`
- [ ] `sudo chmod -R 775 /var/www/qlbv/bootstrap/cache`
- [ ] Tạo storage link: `php artisan storage:link`

### ✅ Database setup
- [ ] Chạy migrations: `php artisan migrate --force`
- [ ] Chạy seeders (nếu cần): `php artisan db:seed --force`
- [ ] Kiểm tra database có dữ liệu

### ✅ Cache configuration
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Kiểm tra cache files được tạo

## Cấu hình Web Server

### ✅ Nginx Virtual Host
- [ ] Tạo file cấu hình `/etc/nginx/sites-available/qlbv`
- [ ] Enable site: `sudo ln -s /etc/nginx/sites-available/qlbv /etc/nginx/sites-enabled/`
- [ ] Test cấu hình: `sudo nginx -t`
- [ ] Reload Nginx: `sudo systemctl reload nginx`
- [ ] Kiểm tra site hoạt động

### ✅ PHP-FPM Configuration
- [ ] Cấu hình pool `/etc/php/7.4/fpm/pool.d/www.conf`
- [ ] Tối ưu PHP settings trong `php.ini`
- [ ] Restart PHP-FPM: `sudo systemctl restart php7.4-fpm`
- [ ] Kiểm tra PHP-FPM status

## Cấu hình Queue và Cron

### ✅ Supervisor Setup
- [ ] Cài đặt Supervisor
- [ ] Tạo file cấu hình `/etc/supervisor/conf.d/qlbv-worker.conf`
- [ ] Reread và update supervisor: `sudo supervisorctl reread && sudo supervisorctl update`
- [ ] Start workers: `sudo supervisorctl start qlbv-worker:*`
- [ ] Kiểm tra workers status

### ✅ Cron Jobs
- [ ] Cấu hình Laravel scheduler: `* * * * * cd /var/www/qlbv && php artisan schedule:run`
- [ ] Cấu hình queue monitoring: `*/5 * * * * cd /var/www/qlbv && php artisan queue:monitor redis --max=100`
- [ ] Kiểm tra crontab: `crontab -u www-data -l`

## Bảo mật

### ✅ Firewall
- [ ] Cấu hình UFW: `sudo ufw allow OpenSSH`
- [ ] Allow Nginx: `sudo ufw allow 'Nginx Full'`
- [ ] Enable firewall: `sudo ufw enable`
- [ ] Kiểm tra firewall status

### ✅ SSL Certificate
- [ ] Cài đặt Certbot
- [ ] Cấu hình SSL: `sudo certbot --nginx -d your-domain.com`
- [ ] Test SSL certificate
- [ ] Cấu hình auto-renewal

### ✅ Security Headers
- [ ] Cấu hình security headers trong Nginx
- [ ] Test security headers
- [ ] Ẩn server version

## Monitoring và Backup

### ✅ Log Rotation
- [ ] Cấu hình logrotate cho Laravel logs
- [ ] Test log rotation
- [ ] Kiểm tra log files

### ✅ Backup Script
- [ ] Tạo script backup: `/usr/local/bin/qlbv-backup.sh`
- [ ] Cấu hình cron cho backup tự động
- [ ] Test backup script
- [ ] Kiểm tra backup files

### ✅ Monitoring Script
- [ ] Tạo script monitoring: `/usr/local/bin/qlbv-monitor.sh`
- [ ] Test monitoring script
- [ ] Cấu hình alerting (nếu cần)

## Testing

### ✅ Kiểm tra ứng dụng
- [ ] Truy cập website qua HTTP
- [ ] Truy cập website qua HTTPS
- [ ] Kiểm tra tất cả chức năng chính
- [ ] Test upload files
- [ ] Test database operations

### ✅ Kiểm tra performance
- [ ] Kiểm tra memory usage
- [ ] Kiểm tra CPU usage
- [ ] Kiểm tra disk usage
- [ ] Test response time

### ✅ Kiểm tra logs
- [ ] Kiểm tra Laravel logs
- [ ] Kiểm tra Nginx logs
- [ ] Kiểm tra PHP-FPM logs
- [ ] Kiểm tra Supervisor logs

## Post-deployment

### ✅ Documentation
- [ ] Cập nhật tài liệu triển khai
- [ ] Ghi lại các cấu hình đặc biệt
- [ ] Tạo runbook cho maintenance

### ✅ Monitoring Setup
- [ ] Cấu hình monitoring tools
- [ ] Setup alerting
- [ ] Test monitoring

### ✅ Backup Verification
- [ ] Test restore từ backup
- [ ] Verify backup integrity
- [ ] Document backup procedures

## Maintenance Plan

### ✅ Regular Tasks
- [ ] Cập nhật hệ thống hàng tuần
- [ ] Kiểm tra logs hàng ngày
- [ ] Monitor performance
- [ ] Backup verification

### ✅ Update Procedures
- [ ] Document update process
- [ ] Test update procedures
- [ ] Create rollback plan

## Final Checklist

### ✅ Pre-production
- [ ] Tất cả tests pass
- [ ] Performance đạt yêu cầu
- [ ] Security audit completed
- [ ] Backup procedures tested
- [ ] Monitoring configured

### ✅ Production Ready
- [ ] SSL certificate active
- [ ] Firewall configured
- [ ] Monitoring active
- [ ] Backup automated
- [ ] Documentation complete

### ✅ Handover
- [ ] Admin credentials documented
- [ ] Emergency contacts provided
- [ ] Maintenance procedures documented
- [ ] Support team trained

## Troubleshooting Notes

### Common Issues
- [ ] Permission denied errors
- [ ] Database connection issues
- [ ] Queue worker problems
- [ ] SSL certificate issues
- [ ] Performance problems

### Solutions
- [ ] Document common solutions
- [ ] Create troubleshooting guide
- [ ] Setup monitoring alerts

---

**Lưu ý**: Checklist này nên được sử dụng cùng với tài liệu triển khai chi tiết. Đánh dấu ✅ khi hoàn thành mỗi bước để đảm bảo không bỏ sót. 