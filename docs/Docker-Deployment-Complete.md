# Hướng dẫn triển khai QLBV trên Docker

## 📋 Yêu cầu hệ thống

### Phần mềm cần thiết
- **Docker Desktop** (Windows/Mac) hoặc **Docker Engine** (Linux)
- **Docker Compose** (thường đi kèm với Docker Desktop)
- **Git** để clone source code
- **Text Editor**: VS Code, Notepad++, hoặc bất kỳ editor nào

### Yêu cầu hệ thống
- **OS**: Windows 10/11, macOS, hoặc Linux
- **RAM**: Tối thiểu 4GB, khuyến nghị 8GB
- **CPU**: 2 cores trở lên
- **Disk**: Tối thiểu 10GB trống

## 🚀 Cài đặt và triển khai

### 1. Cài đặt Docker

#### Windows/Mac
1. Tải Docker Desktop từ: https://www.docker.com/products/docker-desktop
2. Cài đặt và khởi động Docker Desktop
3. Kiểm tra cài đặt:
```bash
docker --version
docker-compose --version
```

#### Linux (Ubuntu/Debian)
```bash
# Cập nhật package index
sudo apt-get update

# Cài đặt các package cần thiết
sudo apt-get install -y apt-transport-https ca-certificates curl gnupg lsb-release

# Thêm Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Thêm Docker repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Cài đặt Docker Engine
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Thêm user vào docker group
sudo usermod -aG docker $USER

# Khởi động Docker service
sudo systemctl start docker
sudo systemctl enable docker
```

### 2. Clone và chuẩn bị project
```bash
git clone https://github.com/tracnn/qlbv_public.git
cd qlbv_public
```

### 3. Tạo file cấu hình

#### Windows
```cmd
REM Copy file .env từ template
copy docs\.env_example .env

REM Copy các file cấu hình cần thiết
copy docs\auth.php config\
copy docs\organization.php config\
copy docs\database.php config\
copy docs\filesystems.php config\

REM Copy thư mục storage
xcopy docs\storage storage\ /E /I /Y
```

#### Linux/Mac
```bash
# Copy file .env từ template
cp docs/.env_example .env

# Copy các file cấu hình cần thiết
cp docs/auth.php config/
cp docs/organization.php config/
cp docs/database.php config/
cp docs/filesystems.php config/

# Copy thư mục storage
cp -r docs/storage ./
```

### 4. Cấu hình file .env
Chỉnh sửa file `.env` với nội dung sau:
```env
APP_NAME=QLBV
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=qlbv
DB_USERNAME=qlbv_user
DB_PASSWORD=qlbv_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Triển khai với Docker
```bash
# Build Docker images
docker-compose build

# Khởi động tất cả services
docker-compose up -d

# Kiểm tra trạng thái
docker-compose ps
```

### 6. Cài đặt Laravel
```bash
# Cài đặt Composer dependencies
docker-compose exec app composer install --no-dev --optimize-autoloader

# Tạo application key
docker-compose exec app php artisan key:generate

# Chạy migrations
docker-compose exec app php artisan migrate --force

# Tạo symbolic link cho storage
docker-compose exec app php artisan storage:link

# Cấu hình permissions
docker-compose exec app chmod -R 775 storage
docker-compose exec app chmod -R 775 bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chown -R www-data:www-data bootstrap/cache
```

### 7. Import database (nếu có)
```bash
docker-compose exec mysql mysql -u qlbv_user -pqlbv_password qlbv < docs/qlbv.sql
```

## 🔧 Quản lý Service Jobs

### Danh sách Service Jobs
| Service | Mô tả | Container Name |
|---------|-------|----------------|
| **JobQd130Xml** | Xử lý queue cho QD130 XML | `qlbv_queue_qd130xml` |
| **JobKtTheBHYT** | Xử lý queue cho kiểm tra thẻ BHYT | `qlbv_queue_ktbhytthe` |
| **Import Catalog** | Import dữ liệu catalog BHXH | `qlbv_import_catalog` |
| **XML Import** | Import dữ liệu XML | `qlbv_xml_import` |
| **Scheduler** | Laravel Task Scheduling | `qlbv_scheduler` |

### Quản lý với Script

#### Windows
```cmd
REM Liệt kê các service job
scripts\docker-jobs.bat list

REM Xem trạng thái các job
scripts\docker-jobs.bat status

REM Khởi động job cụ thể
scripts\docker-jobs.bat start qd130xml
scripts\docker-jobs.bat start ktbhytthe
scripts\docker-jobs.bat start catalog
scripts\docker-jobs.bat start xml
scripts\docker-jobs.bat start scheduler

REM Khởi động tất cả jobs
scripts\docker-jobs.bat start all

REM Dừng job cụ thể
scripts\docker-jobs.bat stop qd130xml

REM Dừng tất cả jobs
scripts\docker-jobs.bat stop all

REM Restart job
scripts\docker-jobs.bat restart qd130xml

REM Xem logs của job
scripts\docker-jobs.bat logs qd130xml

REM Monitor queue jobs
scripts\docker-jobs.bat monitor
```

#### Linux/Mac
```bash
# Cấp quyền cho script
chmod +x scripts/docker-jobs.sh

# Liệt kê các service job
./scripts/docker-jobs.sh list

# Xem trạng thái các job
./scripts/docker-jobs.sh status

# Khởi động job cụ thể
./scripts/docker-jobs.sh start qd130xml
./scripts/docker-jobs.sh start ktbhytthe
./scripts/docker-jobs.sh start catalog
./scripts/docker-jobs.sh start xml
./scripts/docker-jobs.sh start scheduler

# Khởi động tất cả jobs
./scripts/docker-jobs.sh start all

# Dừng job cụ thể
./scripts/docker-jobs.sh stop qd130xml

# Dừng tất cả jobs
./scripts/docker-jobs.sh stop all

# Restart job
./scripts/docker-jobs.sh restart qd130xml

# Xem logs của job
./scripts/docker-jobs.sh logs qd130xml

# Monitor queue jobs
./scripts/docker-jobs.sh monitor
```

### Quản lý trực tiếp với Docker
```bash
# Khởi động job cụ thể
docker-compose up -d queue_qd130xml
docker-compose up -d queue_ktbhytthe
docker-compose up -d import_catalog
docker-compose up -d xml_import
docker-compose up -d scheduler

# Dừng job cụ thể
docker-compose stop queue_qd130xml
docker-compose stop queue_ktbhytthe
docker-compose stop import_catalog
docker-compose stop xml_import
docker-compose stop scheduler

# Restart job
docker-compose restart queue_qd130xml

# Xem logs của job
docker-compose logs -f queue_qd130xml
docker-compose logs -f queue_ktbhytthe
docker-compose logs -f import_catalog
docker-compose logs -f xml_import
docker-compose logs -f scheduler

# Vào container của job
docker-compose exec queue_qd130xml bash
docker-compose exec import_catalog bash
```

## 📊 Monitoring và Logs

### Xem logs
```bash
# Application logs
docker-compose exec app tail -f storage/logs/laravel.log

# Nginx logs
docker-compose exec nginx tail -f /var/log/nginx/access.log
docker-compose exec nginx tail -f /var/log/nginx/error.log

# MySQL logs
docker-compose exec mysql tail -f /var/log/mysql/error.log

# Redis logs
docker-compose logs -f redis

# Job logs
docker-compose logs -f queue_qd130xml
docker-compose logs -f queue_ktbhytthe
docker-compose logs -f import_catalog
docker-compose logs -f xml_import
docker-compose logs -f scheduler
```

### Monitoring Queue Jobs
```bash
# Kiểm tra trạng thái queue
docker-compose exec app php artisan queue:monitor

# Xem failed jobs
docker-compose exec app php artisan queue:failed

# Xem queue workers
docker-compose ps | grep queue

# Retry failed jobs
docker-compose exec app php artisan queue:retry all

# Clear failed jobs
docker-compose exec app php artisan queue:flush

# Clear queue cache
docker-compose exec app php artisan queue:clear
```

### Monitoring containers
```bash
# Xem resource usage
docker stats

# Xem disk usage
docker system df

# Cleanup unused resources
docker system prune -a
```

## 🔍 Troubleshooting

### Lỗi permission
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Lỗi database connection
```bash
# Kiểm tra MySQL container
docker-compose logs mysql

# Test connection
docker-compose exec app php artisan tinker
# Trong tinker: DB::connection()->getPdo();
```

### Lỗi Redis connection
```bash
# Kiểm tra Redis container
docker-compose logs redis

# Test Redis
docker-compose exec app php artisan tinker
# Trong tinker: Redis::ping();
```

### Lỗi Queue Jobs
```bash
# Kiểm tra queue workers
docker-compose logs queue_qd130xml
docker-compose logs queue_ktbhytthe

# Restart queue workers
docker-compose restart queue_qd130xml queue_ktbhytthe

# Clear queue cache
docker-compose exec app php artisan queue:clear
docker-compose exec app php artisan queue:flush
```

### Lỗi Import Services
```bash
# Kiểm tra logs
docker-compose logs import_catalog
docker-compose logs xml_import

# Restart services
docker-compose restart import_catalog
docker-compose restart xml_import

# Test commands
docker-compose exec import_catalog php artisan importCatalogBHXH:data
docker-compose exec xml_import php artisan xml130import:day
```

### Lỗi Scheduler
```bash
# Kiểm tra logs
docker-compose logs scheduler

# Restart scheduler
docker-compose restart scheduler

# Test scheduler
docker-compose exec scheduler php artisan schedule:run
```

## 🔄 Update và Maintenance

### Update application
```bash
# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build --no-cache

# Restart services
docker-compose down
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

### Update dependencies
```bash
# Update Composer dependencies
docker-compose exec app composer update

# Update NPM dependencies (nếu có)
docker-compose exec app npm update
docker-compose exec app npm run production
```

## 💾 Backup và Restore

### Backup database
```bash
# Backup MySQL database
docker-compose exec mysql mysqldump -u qlbv_user -pqlbv_password qlbv > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup volumes
docker run --rm -v qlbv_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .
```

### Restore database
```bash
# Restore từ file SQL
docker-compose exec -T mysql mysql -u qlbv_user -pqlbv_password qlbv < backup_file.sql

# Restore từ volume backup
docker run --rm -v qlbv_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/backup_file.tar.gz -C /data
```

## 📋 Thông tin truy cập

### URLs
- **Web Application**: http://localhost
- **Database**: localhost:3306
- **Redis**: localhost:6379

### Credentials
- **Database User**: qlbv_user
- **Database Password**: qlbv_password
- **Database Name**: qlbv

## 🛠️ Các lệnh hữu ích

### Quản lý services
```bash
# Xem trạng thái
docker-compose ps

# Xem logs
docker-compose logs -f

# Restart services
docker-compose restart

# Stop tất cả
docker-compose down

# Vào container
docker-compose exec app bash
docker-compose exec mysql bash
docker-compose exec redis sh
```

### Quản lý jobs
```bash
# Xem trạng thái jobs (Windows)
scripts\docker-jobs.bat status

# Xem trạng thái jobs (Linux/Mac)
./scripts/docker-jobs.sh status

# Khởi động tất cả jobs (Windows)
scripts\docker-jobs.bat start all

# Khởi động tất cả jobs (Linux/Mac)
./scripts/docker-jobs.sh start all

# Dừng tất cả jobs (Windows)
scripts\docker-jobs.bat stop all

# Dừng tất cả jobs (Linux/Mac)
./scripts/docker-jobs.sh stop all

# Monitor queues (Windows)
scripts\docker-jobs.bat monitor

# Monitor queues (Linux/Mac)
./scripts/docker-jobs.sh monitor
```

## ⚠️ Lưu ý quan trọng

1. **Docker**: Đảm bảo Docker đang chạy trước khi thực hiện các lệnh
2. **Resources**: Cấp đủ RAM và CPU cho Docker (tối thiểu 4GB RAM)
3. **Ports**: Đảm bảo ports 80, 3306, 6379 không bị sử dụng bởi ứng dụng khác
4. **Backup**: Backup database định kỳ
5. **Logs**: Kiểm tra logs thường xuyên để phát hiện lỗi sớm
6. **Updates**: Update dependencies và security patches thường xuyên

## 🆘 Hỗ trợ

- **GitHub**: https://github.com/tracnn/qlbv_public
- **Docker logs**: `docker-compose logs -f`
- **Application logs**: `storage/logs/laravel.log`
- **Job management**: 
  - Windows: `scripts\docker-jobs.bat`
  - Linux/Mac: `./scripts/docker-jobs.sh`

## 📞 Liên hệ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra logs: `docker-compose logs -f`
2. Kiểm tra trạng thái: `docker-compose ps`
3. Restart services: `docker-compose restart`
4. Liên hệ team phát triển 