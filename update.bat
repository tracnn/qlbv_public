@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Đường dẫn đến PHP executable
set PHP_PATH=php.exe

:: Thư mục gốc chứa ứng dụng Laravel (thư mục hiện tại là thư mục gốc của dự án)
set LARAVEL_PATH=%~dp0

:: Đưa ứng dụng vào chế độ bảo trì
echo Putting the application into maintenance mode...
php artisan down

:: Xóa dịch vụ cho JobQd130Xml
%NSSM_PATH%\nssm stop "QLBV JobQd130Xml"
%NSSM_PATH%\nssm remove "QLBV JobQd130Xml" confirm

:: Xóa dịch vụ cho JobKtTheBHYT
%NSSM_PATH%\nssm stop "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm remove "QLBV JobKtTheBHYT" confirm

:: Xóa dịch vụ cho importCatalogBHXH:data
%NSSM_PATH%\nssm stop "QLBV ImportCatalog"
%NSSM_PATH%\nssm remove "QLBV ImportCatalog" confirm

:: Xóa dịch vụ cho xml130import:day
%NSSM_PATH%\nssm stop "QLBV XMLImport"
%NSSM_PATH%\nssm remove "QLBV XMLImport" confirm

echo Services uninstall completed successfully.

:: Hủy các chỉnh sửa từ local
echo Clearing changes from Local Git...
git clean -df
git reset --hard HEAD

:: Cập nhật mã nguồn từ GitHub
echo Pulling latest changes from GitHub...
git checkout -- composer.lock
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

:: Thêm key config mới
echo Adding new config key...
php artisan config:add-keys

:: Dọn dẹp job failed và restart job mắc kẹt
echo Restart stuck jobs
php artisan job:restart-stuck

:: Tạo cache mới
echo Optimizing configuration...
php artisan config:cache
php artisan route:cache

:: Restart các dịch vụ đã cài đặt
echo Restarting services...

:: Tạo dịch vụ cho JobQd130Xml
%NSSM_PATH%\nssm install "QLBV JobQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobQd130Xml"
%NSSM_PATH%\nssm set "QLBV JobQd130Xml" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobKtTheBHYT
%NSSM_PATH%\nssm install "QLBV JobKtTheBHYT" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobKtTheBHYT"
%NSSM_PATH%\nssm set "QLBV JobKtTheBHYT" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho importCatalogBHXH:data
%NSSM_PATH%\nssm install "QLBV ImportCatalog" %PHP_PATH% "%LARAVEL_PATH%artisan importCatalogBHXH:data"
%NSSM_PATH%\nssm set "QLBV ImportCatalog" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho xml130import:day
%NSSM_PATH%\nssm install "QLBV XMLImport" %PHP_PATH% "%LARAVEL_PATH%artisan xml130import:day"
%NSSM_PATH%\nssm set "QLBV XMLImport" AppDirectory %LARAVEL_PATH%

:: Khởi động tất cả các dịch vụ
%NSSM_PATH%\nssm start "QLBV JobQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm start "QLBV ImportCatalog"
%NSSM_PATH%\nssm start "QLBV XMLImport"

echo Service install completed successfully.

:: Đưa ứng dụng ra khỏi chế độ bảo trì
echo Bringing the application out of maintenance mode...
php artisan up

echo Update completed successfully!