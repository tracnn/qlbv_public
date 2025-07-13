#!/bin/bash

# Script triển khai tự động QLBV trên Ubuntu/Rocky Linux
# Sử dụng: ./scripts/deploy.sh [ubuntu|rocky] [manual|docker]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
OS_TYPE=${1:-ubuntu}
DEPLOY_TYPE=${2:-manual}
PROJECT_NAME="qlbv"
PROJECT_PATH="/var/www/$PROJECT_NAME"
DB_NAME="qlbv"
DB_USER="qlbv_user"
DB_PASS="qlbv_password_$(date +%s)"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "Script này không nên chạy với quyền root"
        exit 1
    fi
}

detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        log_error "Không thể xác định hệ điều hành"
        exit 1
    fi
}

install_dependencies_ubuntu() {
    log_info "Cài đặt dependencies trên Ubuntu..."
    
    sudo apt update
    sudo apt install -y curl wget git unzip software-properties-common \
        apt-transport-https ca-certificates gnupg lsb-release
    
    # PHP 7.4
    sudo apt install -y software-properties-common
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    sudo apt install -y php7.4 php7.4-fpm php7.4-cli php7.4-mysql \
        php7.4-xml php7.4-mbstring php7.4-curl php7.4-gd php7.4-zip \
        php7.4-bcmath php7.4-intl php7.4-soap php7.4-ldap php7.4-redis \
        php7.4-opcache
    
    # MySQL
    wget https://dev.mysql.com/get/mysql-apt-config_0.8.24-1_all.deb
    sudo dpkg -i mysql-apt-config_0.8.24-1_all.deb
    sudo apt update
    sudo apt install -y mysql-server
    
    # Redis
    sudo apt install -y redis-server
    
    # Nginx
    sudo apt install -y nginx
    
    # Node.js
    curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
    sudo apt-get install -y nodejs
    
    # Composer
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
}

install_dependencies_rocky() {
    log_info "Cài đặt dependencies trên Rocky Linux..."
    
    sudo dnf update -y
    sudo dnf install -y curl wget git unzip epel-release
    
    # PHP 7.4
    sudo dnf install -y epel-release
    sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
    sudo dnf module enable php:remi-7.4 -y
    sudo dnf install -y php php-fpm php-cli php-mysqlnd php-xml php-mbstring \
        php-curl php-gd php-zip php-bcmath php-intl php-soap php-ldap \
        php-redis php-opcache
    
    # MySQL
    sudo dnf install -y mysql-server
    sudo systemctl enable mysqld
    sudo systemctl start mysqld
    
    # Redis
    sudo dnf install -y redis
    sudo systemctl enable redis
    sudo systemctl start redis
    
    # Nginx
    sudo dnf install -y nginx
    sudo systemctl enable nginx
    sudo systemctl start nginx
    
    # Node.js
    curl -fsSL https://rpm.nodesource.com/setup_16.x | sudo bash -
    sudo dnf install -y nodejs
    
    # Composer
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
}

configure_php() {
    log_info "Cấu hình PHP..."
    
    if [[ "$OS_TYPE" == "ubuntu" ]]; then
        PHP_INI="/etc/php/7.4/fpm/php.ini"
    else
        PHP_INI="/etc/php.ini"
    fi
    
    sudo tee -a $PHP_INI > /dev/null <<EOF

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
EOF
}

configure_nginx() {
    log_info "Cấu hình Nginx..."
    
    sudo tee /etc/nginx/sites-available/$PROJECT_NAME > /dev/null <<EOF
server {
    listen 80;
    server_name localhost;
    root $PROJECT_PATH/public;
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
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
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
EOF

    sudo ln -sf /etc/nginx/sites-available/$PROJECT_NAME /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    sudo nginx -t
}

configure_mysql() {
    log_info "Cấu hình MySQL..."
    
    # Secure MySQL installation
    sudo mysql_secure_installation <<EOF

y
0
$DB_PASS
$DB_PASS
y
y
y
y
EOF

    # Create database and user
    sudo mysql -u root -p$DB_PASS <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
}

deploy_application() {
    log_info "Triển khai ứng dụng..."
    
    # Create project directory
    sudo mkdir -p $PROJECT_PATH
    sudo chown $USER:$USER $PROJECT_PATH
    
    # Copy project files (assuming current directory is project root)
    cp -r . $PROJECT_PATH/
    cd $PROJECT_PATH
    
    # Install dependencies
    composer install --no-dev --optimize-autoloader
    
    # Copy environment file
    cp docs/.env_example .env
    
    # Generate application key
    php artisan key:generate
    
    # Configure environment
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    
    # Run migrations and seeders
    php artisan migrate --force
    php artisan db:seed --force
    
    # Create storage link
    php artisan storage:link
    
    # Set permissions
    sudo chown -R www-data:www-data $PROJECT_PATH
    sudo chmod -R 755 $PROJECT_PATH
    sudo chmod -R 775 $PROJECT_PATH/storage
    sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache
}

start_services() {
    log_info "Khởi động services..."
    
    sudo systemctl enable php7.4-fpm
    sudo systemctl start php7.4-fpm
    sudo systemctl enable nginx
    sudo systemctl start nginx
    sudo systemctl enable mysql
    sudo systemctl start mysql
    sudo systemctl enable redis
    sudo systemctl start redis
}

deploy_docker() {
    log_info "Triển khai bằng Docker..."
    
    # Install Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    
    # Install Docker Compose
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    # Start containers
    docker-compose up -d
    
    # Wait for database to be ready
    sleep 30
    
    # Run migrations
    docker-compose exec app php artisan migrate --force
    docker-compose exec app php artisan db:seed --force
    docker-compose exec app php artisan storage:link
}

setup_firewall() {
    log_info "Cấu hình firewall..."
    
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    sudo ufw allow ssh
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    sudo ufw --force enable
}

setup_backup() {
    log_info "Cấu hình backup..."
    
    sudo mkdir -p /backup/$PROJECT_NAME
    
    sudo tee /usr/local/bin/${PROJECT_NAME}-backup.sh > /dev/null <<EOF
#!/bin/bash
BACKUP_DIR="/backup/$PROJECT_NAME"
DATE=\$(date +%Y%m%d_%H%M%S)

mkdir -p \$BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > \$BACKUP_DIR/db_backup_\$DATE.sql

# Backup files
tar -czf \$BACKUP_DIR/files_backup_\$DATE.tar.gz $PROJECT_PATH

# Clean old backups (keep 30 days)
find \$BACKUP_DIR -name "*.sql" -mtime +30 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
EOF

    sudo chmod +x /usr/local/bin/${PROJECT_NAME}-backup.sh
    
    # Add to crontab
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/${PROJECT_NAME}-backup.sh") | crontab -
}

main() {
    log_info "Bắt đầu triển khai QLBV..."
    
    check_root
    detect_os
    
    if [[ "$OS_TYPE" == "ubuntu" ]]; then
        install_dependencies_ubuntu
    elif [[ "$OS_TYPE" == "rocky" ]]; then
        install_dependencies_rocky
    else
        log_error "Hệ điều hành không được hỗ trợ. Sử dụng 'ubuntu' hoặc 'rocky'"
        exit 1
    fi
    
    if [[ "$DEPLOY_TYPE" == "docker" ]]; then
        deploy_docker
    else
        configure_php
        configure_nginx
        configure_mysql
        deploy_application
        start_services
    fi
    
    setup_firewall
    setup_backup
    
    log_info "Triển khai hoàn tất!"
    log_info "Truy cập ứng dụng tại: http://localhost"
    log_info "Database credentials:"
    log_info "  Database: $DB_NAME"
    log_info "  Username: $DB_USER"
    log_info "  Password: $DB_PASS"
}

# Run main function
main "$@" 