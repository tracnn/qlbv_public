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

# Check environment
if [ -z "$1" ]; then
    print_error "Usage: $0 <environment>"
    print_error "Example: $0 production"
    exit 1
fi

ENVIRONMENT=$1

print_status "Deploying QLBV to $ENVIRONMENT environment..."

# Backup current deployment
print_status "Creating backup..."
./scripts/docker-backup.sh

# Pull latest code
print_status "Pulling latest code..."
git pull origin main

# Build production images
print_status "Building production images..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

# Stop current containers
print_status "Stopping current containers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Start new containers
print_status "Starting new containers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Wait for services
print_status "Waiting for services to be ready..."
sleep 30

# Run migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear and cache configuration
print_status "Caching configuration..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache

# Restart queue workers
print_status "Restarting queue workers..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart queue

print_status "Deployment completed successfully!"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps 