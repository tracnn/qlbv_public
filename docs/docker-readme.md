# Hướng dẫn sử dụng Docker cho dự án QLBV

## Tổng quan

Dự án QLBV được containerized bằng Docker để đảm bảo tính nhất quán giữa các môi trường development, staging và production.

## Cấu trúc Docker

```
qlbv/
├── docker-compose.yml              # Cấu hình chính
├── docker-compose.override.yml     # Override cho development
├── docker-compose.prod.yml         # Cấu hình production
├── env.docker                      # Environment variables
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── nginx.conf
│   ├── mysql/
│   │   └── init.sql
│   └── redis/
│       └── redis.conf
└── scripts/
    ├── docker-setup.sh
    ├── docker-deploy.sh
    └── docker-backup.sh
```

## Yêu cầu hệ thống

- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **RAM**: Tối thiểu 4GB (8GB khuyến nghị)
- **Storage**: 20GB trống

## Quick Start

### 1. Cài đặt Docker

#### Ubuntu/Debian
```bash
# Cài đặt Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Thêm user vào docker group
sudo usermod -aG docker $USER

# Khởi động Docker
sudo systemctl start docker
sudo systemctl enable docker
```

#### Windows/macOS
Tải Docker Desktop từ https://www.docker.com/products/docker-desktop

### 2. Setup dự án

```bash
# Clone repository
git clone <repository_url> qlbv
cd qlbv

# Setup Docker environment
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh
```

### 3. Truy cập ứng dụng

- **Application**: http://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Development

### Chạy ứng dụng

```bash
# Start containers
docker-compose up -d

# View logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f redis

# Stop containers
docker-compose down
```

### Thực thi commands

```bash
# Laravel commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan make:controller TestController
docker-compose exec app php artisan tinker

# Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# Node.js commands
docker-compose exec app npm install
docker-compose exec app npm run dev
docker-compose exec app npm run production
```

### Database

```bash
# Access MySQL
docker-compose exec mysql mysql -u qlbv_user -p qlbv

# Backup database
docker-compose exec mysql mysqldump -u qlbv_user -p qlbv > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u qlbv_user -p qlbv < backup.sql
```

### Redis

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Test Redis connection
docker-compose exec redis redis-cli ping
```

## Production

### Deploy to production

```bash
# Deploy to production
chmod +x scripts/docker-deploy.sh
./scripts/docker-deploy.sh production

# Monitor deployment
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f
```

### Production configuration

1. **Environment variables**: Cập nhật `env.docker` với thông tin production
2. **SSL certificates**: Thêm certificates vào thư mục `ssl/`
3. **Database passwords**: Sử dụng mật khẩu mạnh cho production
4. **Monitoring**: Cấu hình monitoring và alerting

### Backup và restore

```bash
# Create backup
chmod +x scripts/docker-backup.sh
./scripts/docker-backup.sh

# Restore from backup
docker-compose exec -T mysql mysql -u qlbv_user -p qlbv < backups/database_20241201_120000.sql
```

## Monitoring

### Container status

```bash
# Check container status
docker-compose ps

# Check resource usage
docker stats

# Check disk usage
docker system df
```

### Logs

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

### Performance monitoring

```bash
# Memory usage
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Disk usage
docker system df

# Network connections
docker network ls
docker network inspect qlbv_qlbv_network
```

## Troubleshooting

### Common issues

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

### Performance optimization

```bash
# Increase memory limits
docker-compose exec app php -i | grep memory_limit

# Optimize images
docker-compose build --no-cache

# Clean unused resources
docker system prune -f
```

### Security

```bash
# Update base images
docker-compose pull

# Scan for vulnerabilities
docker scan qlbv_app

# Use secrets management
echo "your_secret_password" | docker secret create db_password -
```

## Maintenance

### Regular tasks

```bash
# Update containers
docker-compose pull
docker-compose build --no-cache
docker-compose up -d

# Clean up
docker system prune -f
docker volume prune -f

# Backup
./scripts/docker-backup.sh
```

### Scaling

```bash
# Scale queue workers
docker-compose up -d --scale queue=3

# Scale web servers
docker-compose up -d --scale nginx=2
```

## Environment Variables

### Development (.env)
```env
APP_ENV=local
APP_DEBUG=true
DB_HOST=mysql
DB_PASSWORD=qlbv_password
REDIS_HOST=redis
```

### Production (.env)
```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=mysql
DB_PASSWORD=your_secure_password
REDIS_HOST=redis
```

## Services

### Web Server (Nginx)
- **Port**: 80, 443
- **Image**: nginx:alpine
- **Config**: `docker/nginx/nginx.conf`

### Application (PHP-FPM)
- **Port**: 9000 (internal)
- **Image**: Custom PHP 7.4
- **Config**: `docker/php/php.ini`

### Database (MySQL)
- **Port**: 3306
- **Image**: mysql:8.0
- **Config**: `docker/mysql/init.sql`

### Cache (Redis)
- **Port**: 6379
- **Image**: redis:7-alpine
- **Config**: `docker/redis/redis.conf`

### Queue Worker
- **Image**: Custom PHP 7.4
- **Command**: `php artisan queue:work redis`

### Scheduler
- **Image**: Custom PHP 7.4
- **Command**: `php artisan schedule:work`

## Volumes

### Persistent data
- `mysql_data`: MySQL database files
- `redis_data`: Redis data files
- `./storage`: Laravel storage files
- `./backups`: Backup files

### Logs
- `./storage/logs/nginx`: Nginx access/error logs
- `./storage/logs/mysql`: MySQL logs
- `./storage/logs/redis`: Redis logs

## Networks

### Default network
- **Name**: `qlbv_qlbv_network`
- **Driver**: bridge
- **Services**: app, nginx, mysql, redis, queue, scheduler

## Scripts

### docker-setup.sh
- Setup môi trường Docker
- Tạo directories và permissions
- Build và start containers
- Run migrations và install dependencies

### docker-deploy.sh
- Deploy to production
- Backup trước khi deploy
- Pull latest code
- Build production images
- Run migrations và cache config

### docker-backup.sh
- Backup database
- Backup application files
- Backup storage
- Clean old backups

## Best Practices

1. **Security**:
   - Sử dụng secrets management
   - Cập nhật base images thường xuyên
   - Scan vulnerabilities
   - Use strong passwords

2. **Performance**:
   - Optimize PHP settings
   - Use Redis for caching
   - Monitor resource usage
   - Scale services as needed

3. **Backup**:
   - Backup định kỳ
   - Test restore procedures
   - Store backups securely
   - Document backup procedures

4. **Monitoring**:
   - Monitor container health
   - Check logs regularly
   - Set up alerting
   - Track performance metrics

## Support

Nếu gặp vấn đề:

1. Kiểm tra logs: `docker-compose logs -f`
2. Xem container status: `docker-compose ps`
3. Restart services: `docker-compose restart`
4. Rebuild containers: `docker-compose build --no-cache`
5. Liên hệ team phát triển với thông tin chi tiết

## Changelog

### Version 1.0.0
- Initial Docker setup
- Multi-environment support
- Automated deployment scripts
- Backup and monitoring tools 