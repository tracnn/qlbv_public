#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
DATE=$(date +%Y%m%d_%H%M%S)

print_status "Creating backup directory: $BACKUP_DIR"
mkdir -p $BACKUP_DIR

# Backup database
print_status "Backing up database..."
docker-compose exec mysql mysqldump -u qlbv_user -pqlbv_password qlbv > $BACKUP_DIR/database_$DATE.sql

# Backup application files
print_status "Backing up application files..."
tar -czf $BACKUP_DIR/application_$DATE.tar.gz \
    --exclude=node_modules \
    --exclude=vendor \
    --exclude=.git \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    .

# Backup storage
print_status "Backing up storage..."
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz storage/

# Create backup info
cat > $BACKUP_DIR/backup_info.txt << EOF
Backup created: $(date)
Environment: $(docker-compose exec app php artisan env)
Git commit: $(git rev-parse HEAD)
Git branch: $(git branch --show-current)
EOF

print_status "Backup completed successfully!"
print_status "Backup location: $BACKUP_DIR"

# Clean old backups (keep last 7 days)
find backups -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null || true 