@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Đường dẫn PHP và thư mục Laravel (dùng khi tự cài service mới)
set PHP_PATH=php.exe
set LARAVEL_PATH=%~dp0

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

:: Tự cài các service (idempotent - chỉ cài nếu chưa tồn tại)
echo Ensuring services are installed...

%NSSM_PATH%\nssm status "QLBV JobQd130Xml" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobQd130Xml...
    %NSSM_PATH%\nssm install "QLBV JobQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobQd130Xml"
    %NSSM_PATH%\nssm set "QLBV JobQd130Xml" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobXml3176" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobXml3176...
    %NSSM_PATH%\nssm install "QLBV JobXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobXml3176"
    %NSSM_PATH%\nssm set "QLBV JobXml3176" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobKtTheBHYT" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobKtTheBHYT...
    %NSSM_PATH%\nssm install "QLBV JobKtTheBHYT" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobKtTheBHYT"
    %NSSM_PATH%\nssm set "QLBV JobKtTheBHYT" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV ImportCatalog" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV ImportCatalog...
    %NSSM_PATH%\nssm install "QLBV ImportCatalog" %PHP_PATH% "%LARAVEL_PATH%artisan importCatalogBHXH:data"
    %NSSM_PATH%\nssm set "QLBV ImportCatalog" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV XMLImport" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV XMLImport...
    %NSSM_PATH%\nssm install "QLBV XMLImport" %PHP_PATH% "%LARAVEL_PATH%artisan xml130import:day"
    %NSSM_PATH%\nssm set "QLBV XMLImport" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV XMLImport3176" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV XMLImport3176...
    %NSSM_PATH%\nssm install "QLBV XMLImport3176" %PHP_PATH% "%LARAVEL_PATH%artisan xml3176import:day"
    %NSSM_PATH%\nssm set "QLBV XMLImport3176" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV TrucDuLieuYTeXmlScan" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV TrucDuLieuYTeXmlScan...
    %NSSM_PATH%\nssm install "QLBV TrucDuLieuYTeXmlScan" %PHP_PATH% "%LARAVEL_PATH%artisan truc-du-lieu-y-te:scan"
    %NSSM_PATH%\nssm set "QLBV TrucDuLieuYTeXmlScan" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV CongDuLieuYTeDienBienXmlScan" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV CongDuLieuYTeDienBienXmlScan...
    %NSSM_PATH%\nssm install "QLBV CongDuLieuYTeDienBienXmlScan" %PHP_PATH% "%LARAVEL_PATH%artisan cong-du-lieu-y-te-dien-bien:scan"
    %NSSM_PATH%\nssm set "QLBV CongDuLieuYTeDienBienXmlScan" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSubmitQd130Xml" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSubmitQd130Xml...
    %NSSM_PATH%\nssm install "QLBV JobSubmitQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitQd130Xml"
    %NSSM_PATH%\nssm set "QLBV JobSubmitQd130Xml" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSubmitXml3176" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSubmitXml3176...
    %NSSM_PATH%\nssm install "QLBV JobSubmitXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitXml3176"
    %NSSM_PATH%\nssm set "QLBV JobSubmitXml3176" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobExportQd130Xml" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobExportQd130Xml...
    %NSSM_PATH%\nssm install "QLBV JobExportQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobExportQd130Xml"
    %NSSM_PATH%\nssm set "QLBV JobExportQd130Xml" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobExportXml3176" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobExportXml3176...
    %NSSM_PATH%\nssm install "QLBV JobExportXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobExportXml3176"
    %NSSM_PATH%\nssm set "QLBV JobExportXml3176" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV KiemTraYLenh" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV KiemTraYLenh...
    %NSSM_PATH%\nssm install "QLBV KiemTraYLenh" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:scan"
)
:: Luon sua cau hinh (sua service da cai sai o lan truoc)
%NSSM_PATH%\nssm set "QLBV KiemTraYLenh" Application %PHP_PATH%
%NSSM_PATH%\nssm set "QLBV KiemTraYLenh" AppParameters "%LARAVEL_PATH%artisan kiemtraylenh:scan"
%NSSM_PATH%\nssm set "QLBV KiemTraYLenh" AppDirectory %LARAVEL_PATH%

%NSSM_PATH%\nssm status "QLBV KiemTraYLenhNotify" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV KiemTraYLenhNotify...
    %NSSM_PATH%\nssm install "QLBV KiemTraYLenhNotify" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:notify"
)
:: Luon sua cau hinh (sua service da cai sai o lan truoc)
%NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" Application %PHP_PATH%
%NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" AppParameters "%LARAVEL_PATH%artisan kiemtraylenh:notify"
%NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" AppDirectory %LARAVEL_PATH%

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
%NSSM_PATH%\nssm stop "QLBV KiemTraYLenh"
%NSSM_PATH%\nssm stop "QLBV KiemTraYLenhNotify"

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
%NSSM_PATH%\nssm start "QLBV KiemTraYLenh"
%NSSM_PATH%\nssm start "QLBV KiemTraYLenhNotify"

:: Đưa ứng dụng ra khỏi chế độ bảo trì
echo Bringing the application out of maintenance mode...
php artisan up

echo Update completed successfully!