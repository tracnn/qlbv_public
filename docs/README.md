# Tài liệu triển khai dự án QLBV

## Tổng quan

Thư mục này chứa tài liệu triển khai dự án QLBV (Quản lý Bảo hiểm Y tế) sử dụng Docker để đảm bảo tính nhất quán giữa các môi trường development, staging và production.

## Cấu trúc tài liệu

### 1. `Docker-Deployment-Complete.md`
Tài liệu chi tiết hướng dẫn triển khai dự án sử dụng Docker, bao gồm:

- **Chuẩn bị môi trường**: Cài đặt Docker và Docker Compose
- **Cấu hình Docker**: Dockerfiles, docker-compose.yml
- **Triển khai ứng dụng**: Build và chạy containers
- **Cấu hình services**: Nginx, PHP-FPM, MySQL, Redis
- **Queue và Scheduler**: Workers và cron jobs
- **Bảo mật**: SSL, security headers, secrets management
- **Monitoring**: Logs, health checks, performance monitoring
- **Backup và Recovery**: Automated backup scripts
- **Troubleshooting**: Xử lý các lỗi thường gặp

## Hướng dẫn sử dụng

### Triển khai với Docker

1. **Chuẩn bị môi trường**:
   ```bash
   # Cài đặt Docker và Docker Compose
   # Xem hướng dẫn trong Docker-Deployment-Complete.md
   ```

2. **Clone và setup dự án**:
   ```bash
   git clone <repository_url> qlbv
   cd qlbv
   ```

3. **Chạy Docker deployment**:
   ```bash
   # Development
   docker-compose up -d
   
   # Production
   docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
   ```

4. **Kiểm tra ứng dụng**:
   ```bash
   # Truy cập ứng dụng
   http://localhost:8080 (development)
   http://your-domain.com (production)
   ```

## Yêu cầu hệ thống

### Tối thiểu
- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **RAM**: 4GB
- **Storage**: 20GB
- **OS**: Linux, macOS, Windows 10/11

### Khuyến nghị
- **Docker**: 24.0+
- **Docker Compose**: 2.20+
- **RAM**: 8GB
- **Storage**: 50GB SSD
- **OS**: Ubuntu 22.04 LTS, macOS 12+, Windows 11

## Cấu hình môi trường

### Development
```env
APP_ENV=local
APP_DEBUG=true
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
DB_HOST=mysql
REDIS_HOST=redis
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
DB_HOST=mysql
REDIS_HOST=redis
```

## Services

### Web Server
- **Nginx**: Port 80/443
- **PHP-FPM**: Port 9000 (internal)

### Database
- **MySQL**: Port 3306
- **Redis**: Port 6379

### Queue và Scheduler
- **Queue Workers**: Laravel queue workers
- **Scheduler**: Laravel task scheduler

## Monitoring

### Container Status
```bash
# Kiểm tra trạng thái containers
docker-compose ps

# Xem logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f redis
```

### Performance Monitoring
```bash
# Resource usage
docker stats

# Disk usage
docker system df

# Health checks
docker-compose exec app php artisan queue:work --once
```

## Bảo mật

### SSL/TLS
```bash
# Tạo SSL certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout ssl/nginx.key -out ssl/nginx.crt
```

### Secrets Management
```bash
# Tạo secrets
echo "your_secret_password" | docker secret create db_password -
```

### Security Headers
Đã được cấu hình trong Nginx configuration

## Backup và Recovery

### Automated Backup
```bash
# Tạo backup
./scripts/docker-backup.sh

# Restore từ backup
docker-compose exec -T mysql mysql -u qlbv_user -p qlbv < backup.sql
```

### Backup Schedule
- **Database**: Hàng ngày
- **Application files**: Hàng tuần
- **Retention**: 30 ngày

## Troubleshooting

### Lỗi thường gặp

1. **Container không start**
   ```bash
   docker-compose logs container_name
   docker-compose down
   docker-compose up -d
   ```

2. **Queue không hoạt động**
   ```bash
   docker-compose restart queue
   docker-compose logs queue
   ```

3. **Database connection issues**
   ```bash
   docker-compose exec app php artisan config:clear
   docker-compose exec app php artisan cache:clear
   ```

### Performance Issues
```bash
# Memory usage
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Clean up
docker system prune -f
docker volume prune -f
```

## Maintenance

### Cập nhật ứng dụng
```bash
# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear cache
docker-compose exec app php artisan config:cache
```

### Scaling
```bash
# Scale queue workers
docker-compose up -d --scale queue=3

# Scale web servers
docker-compose up -d --scale nginx=2
```

## Scripts tự động hóa

### Docker Setup
- **`scripts/docker-setup.sh`**: Setup môi trường Docker
- **`scripts/docker-deploy.sh`**: Deploy to production
- **`scripts/docker-backup.sh`**: Backup automation

### Job Management
- **`scripts/jobs-linux.sh`**: Quản lý service jobs trên Linux/Mac
- **`scripts/jobs-windows.bat`**: Quản lý service jobs trên Windows

## Liên hệ

Nếu gặp vấn đề trong quá trình triển khai, vui lòng:

1. Kiểm tra logs: `docker-compose logs -f`
2. Xem container status: `docker-compose ps`
3. Tham khảo phần Troubleshooting trong `Docker-Deployment-Complete.md`
4. Liên hệ team phát triển với thông tin chi tiết về lỗi

## Changelog

### Version 2.0.0
- Chuyển sang Docker deployment
- Loại bỏ Ubuntu deployment thủ công
- Tối ưu hóa cấu trúc tài liệu
- Thêm scripts tự động hóa

### Version 1.0.0
- Tạo tài liệu triển khai cơ bản
- Script tự động triển khai Ubuntu
- Cấu hình monitoring và backup
- Hướng dẫn troubleshooting 