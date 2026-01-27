@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Đưa ứng dụng vào chế độ bảo trì
echo Putting the application into maintenance mode...
php artisan down

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

:: Stop từng dịch vụ
%NSSM_PATH%\nssm stop "QLBV JobQd130Xml"
%NSSM_PATH%\nssm stop "QLBV JobXml3176"
%NSSM_PATH%\nssm stop "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm stop "QLBV ImportCatalog"
%NSSM_PATH%\nssm stop "QLBV XMLImport"
%NSSM_PATH%\nssm stop "QLBV XMLImport3176"
%NSSM_PATH%\nssm stop "QLBV TrucDuLieuYTeXmlScan"
%NSSM_PATH%\nssm stop "QLBV CongDuLieuYTeDienBienXmlScan"
%NSSM_PATH%\nssm stop "QLBV JobSubmitQd130Xml"
%NSSM_PATH%\nssm stop "QLBV JobSubmitXml3176"
%NSSM_PATH%\nssm stop "QLBV JobExportQd130Xml"
%NSSM_PATH%\nssm stop "QLBV JobExportXml3176"

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
%NSSM_PATH%\nssm start "QLBV JobQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm start "QLBV ImportCatalog"
%NSSM_PATH%\nssm start "QLBV XMLImport"
%NSSM_PATH%\nssm start "QLBV JobXml3176"
%NSSM_PATH%\nssm start "QLBV XMLImport3176"
%NSSM_PATH%\nssm start "QLBV TrucDuLieuYTeXmlScan"
%NSSM_PATH%\nssm start "QLBV CongDuLieuYTeDienBienXmlScan"
%NSSM_PATH%\nssm start "QLBV JobSubmitQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobSubmitXml3176"
%NSSM_PATH%\nssm start "QLBV JobExportQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobExportXml3176"

:: Đưa ứng dụng ra khỏi chế độ bảo trì
echo Bringing the application out of maintenance mode...
php artisan up

echo Update completed successfully!