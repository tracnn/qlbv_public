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

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "Setting up QLBV Docker environment..."

# Create necessary directories
print_status "Creating directories..."
mkdir -p storage/logs/nginx
mkdir -p storage/logs/mysql
mkdir -p storage/logs/redis
mkdir -p storage/app/public
mkdir -p bootstrap/cache
mkdir -p backups/mysql
mkdir -p ssl

# Set permissions
print_status "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Copy environment file
if [ ! -f .env ]; then
    print_status "Creating .env file..."
    cp env.docker .env
fi

# Generate application key
print_status "Generating application key..."
docker-compose run --rm app php artisan key:generate

# Build and start containers
print_status "Building Docker containers..."
docker-compose build

print_status "Starting containers..."
docker-compose up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Run migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force

# Install dependencies
print_status "Installing PHP dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

print_status "Installing Node.js dependencies..."
docker-compose exec app npm install
docker-compose exec app npm run production

# Cache configuration
print_status "Caching configuration..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Create storage link
print_status "Creating storage link..."
docker-compose exec app php artisan storage:link

print_status "Docker setup completed successfully!"
print_status "Application URL: http://localhost:8080"
print_status "MySQL: localhost:3306"
print_status "Redis: localhost:6379"

# Show container status
docker-compose ps 