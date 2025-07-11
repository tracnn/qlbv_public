#!/bin/bash

# Script tự động triển khai dự án QLBV trên Ubuntu
# Sử dụng: ./deploy.sh [domain] [db_password] [app_key]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root"
   exit 1
fi

# Check parameters
if [ $# -lt 3 ]; then
    print_error "Usage: $0 <domain> <db_password> <app_key>"
    print_error "Example: $0 example.com mypassword base64:your_app_key"
    exit 1
fi

DOMAIN=$1
DB_PASSWORD=$2
APP_KEY=$3
APP_DIR="/var/www/qlbv"
BACKUP_DIR="/var/backups/qlbv"

print_status "Starting deployment for domain: $DOMAIN"

# Update system
print_status "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
print_status "Installing required packages..."
sudo apt install -y curl wget git unzip software-properties-common \
    apt-transport-https ca-certificates gnupg lsb-release nginx \
    mysql-server mysql-client redis-server supervisor htop iotop nethogs

# Install PHP 7.4
print_status "Installing PHP 7.4 and extensions..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php7.4 php7.4-fpm php7.4-cli php7.4-mysql \
    php7.4-pgsql php7.4-sqlite3 php7.4-bcmath php7.4-mbstring \
    php7.4-xml php7.4-curl php7.4-json php7.4-tokenizer \
    php7.4-zip php7.4-gd php7.4-intl php7.4-soap php7.4-xmlrpc \
    php7.4-ldap php7.4-imap php7.4-redis php7.4-memcached

# Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js
print_status "Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# Configure MySQL
print_status "Configuring MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS qlbv CHARACTER SET utf8 COLLATE utf8_general_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'qlbv_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON qlbv.* TO 'qlbv_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Configure Redis
print_status "Configuring Redis..."
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Configure Nginx
print_status "Configuring Nginx..."
sudo systemctl enable nginx
sudo systemctl start nginx

# Create application user
print_status "Creating application user..."
sudo adduser --disabled-password --gecos "" qlbv || true
sudo usermod -aG sudo qlbv

# Clone application (assuming repository is already available)
print_status "Setting up application directory..."
sudo mkdir -p $APP_DIR
sudo chown -R qlbv:qlbv $APP_DIR

# Copy application files (assuming they are in current directory)
print_status "Copying application files..."
sudo cp -r . $APP_DIR/
sudo chown -R qlbv:qlbv $APP_DIR

# Install dependencies
print_status "Installing PHP dependencies..."
cd $APP_DIR
composer install --no-dev --optimize-autoloader

print_status "Installing Node.js dependencies..."
npm install
npm run production

# Configure environment
print_status "Configuring environment..."
cp .env.example .env

# Update .env file
sed -i "s/APP_NAME=.*/APP_NAME=QLBV/" .env
sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" .env
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
sed -i "s/APP_URL=.*/APP_URL=https:\/\/$DOMAIN/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=qlbv/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=qlbv_user/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=redis/" .env
sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env
sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env

# Set permissions
print_status "Setting permissions..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

# Create storage link
php artisan storage:link

# Run migrations
print_status "Running database migrations..."
php artisan migrate --force

# Cache configuration
print_status "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configure Nginx virtual host
print_status "Configuring Nginx virtual host..."
sudo tee /etc/nginx/sites-available/qlbv > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $APP_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/qlbv /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

# Configure PHP-FPM
print_status "Configuring PHP-FPM..."
sudo tee /etc/php/7.4/fpm/pool.d/www.conf > /dev/null <<EOF
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
EOF

# Configure PHP
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/7.4/fpm/php.ini
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/7.4/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/7.4/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/7.4/fpm/php.ini
sudo sed -i 's/max_input_vars = .*/max_input_vars = 3000/' /etc/php/7.4/fpm/php.ini

sudo systemctl restart php7.4-fpm

# Configure Supervisor
print_status "Configuring Supervisor..."
sudo tee /etc/supervisor/conf.d/qlbv-worker.conf > /dev/null <<EOF
[program:qlbv-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qlbv-worker:*

# Configure cron jobs
print_status "Configuring cron jobs..."
(crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
(crontab -u www-data -l 2>/dev/null; echo "*/5 * * * * cd $APP_DIR && php artisan queue:monitor redis --max=100") | crontab -u www-data -

# Configure firewall
print_status "Configuring firewall..."
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

# Create backup script
print_status "Creating backup script..."
sudo mkdir -p $BACKUP_DIR
sudo tee /usr/local/bin/qlbv-backup.sh > /dev/null <<EOF
#!/bin/bash

BACKUP_DIR="$BACKUP_DIR"
DATE=\$(date +%Y%m%d_%H%M%S)
DB_NAME="qlbv"

# Tạo thư mục backup
mkdir -p \$BACKUP_DIR

# Backup database
mysqldump -u qlbv_user -p'$DB_PASSWORD' \$DB_NAME > \$BACKUP_DIR/db_backup_\$DATE.sql

# Backup application files
tar -czf \$BACKUP_DIR/app_backup_\$DATE.tar.gz -C /var/www qlbv

# Xóa backup cũ hơn 30 ngày
find \$BACKUP_DIR -name "*.sql" -mtime +30 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: \$DATE"
EOF

sudo chmod +x /usr/local/bin/qlbv-backup.sh

# Configure log rotation
print_status "Configuring log rotation..."
sudo tee /etc/logrotate.d/qlbv > /dev/null <<EOF
$APP_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF

# Create monitoring script
print_status "Creating monitoring script..."
sudo tee /usr/local/bin/qlbv-monitor.sh > /dev/null <<EOF
#!/bin/bash

echo "=== QLBV System Status ==="
echo "Date: \$(date)"
echo ""

echo "=== Memory Usage ==="
free -h
echo ""

echo "=== Disk Usage ==="
df -h
echo ""

echo "=== CPU Usage ==="
top -bn1 | grep "Cpu(s)" | awk '{print \$2}' | awk -F% '{print \$1}'
echo ""

echo "=== Service Status ==="
systemctl is-active nginx
systemctl is-active php7.4-fpm
systemctl is-active mysql
systemctl is-active redis-server
supervisorctl status
echo ""

echo "=== Queue Status ==="
cd $APP_DIR && php artisan queue:work --once
echo ""

echo "=== Recent Logs ==="
tail -n 20 $APP_DIR/storage/logs/laravel.log
EOF

sudo chmod +x /usr/local/bin/qlbv-monitor.sh

# Configure SSL (optional - requires domain to be pointing to server)
print_warning "SSL configuration requires domain to be pointing to this server"
print_warning "Run the following command after DNS is configured:"
echo "sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN"

print_status "Deployment completed successfully!"
print_status "Application URL: http://$DOMAIN"
print_status "Backup script: /usr/local/bin/qlbv-backup.sh"
print_status "Monitoring script: /usr/local/bin/qlbv-monitor.sh"
print_status "Logs directory: $APP_DIR/storage/logs"

# Final restart
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo supervisorctl restart qlbv-worker:*

print_status "All services restarted successfully!" 