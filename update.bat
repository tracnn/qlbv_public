@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Cập nhật mã nguồn từ GitHub
echo Pulling latest changes from GitHub...
git pull origin master

:: Cập nhật các gói phụ thuộc của PHP bằng Composer
echo Installing PHP dependencies...
composer install --no-interaction --prefer-dist --optimize-autoloader

:: Chạy các migration (nếu có)
echo Running migrations...
php artisan migrate --force

:: Dọn dẹp cache
echo Clearing cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

:: Tạo cache mới
echo Optimizing configuration...
php artisan config:cache
php artisan route:cache

echo Update completed successfully!
pause