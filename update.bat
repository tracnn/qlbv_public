@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Cập nhật mã nguồn từ GitHub
echo Pulling latest changes from GitHub...
git pull origin main

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

:: Restart các dịch vụ đã cài đặt
echo Restarting services...

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Restart từng dịch vụ
%NSSM_PATH%\nssm restart "QLBV JobQd130Xml"
%NSSM_PATH%\nssm restart "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm restart "QLBV ImportCatalog"
%NSSM_PATH%\nssm restart "QLBV XMLImport"

echo Update completed successfully!