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

:: Ba hang doi cua module chung tu dien tu (PL02). CA BA deu BAT BUOC: ky so hong vi ly do
:: CUC BO (USB token bi rut, HSM khong phan hoi) con gui hong vi MANG, nen gop chung thi mot
:: lan mang chap keo theo ba lan ky lai - thao tac ton thoi gian nhat trong chuoi.
::
:: Thieu worker nao thi moi ho so dung khung o buoc do, va cot "So loi" tren man danh sach
:: dung yen o 0 - trong y het nhu moi ho so deu sach.
%NSSM_PATH%\nssm status "QLBV JobCtdt" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobCtdt...
    %NSSM_PATH%\nssm install "QLBV JobCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobCtdt"
    %NSSM_PATH%\nssm set "QLBV JobCtdt" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSignCtdt" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSignCtdt...
    %NSSM_PATH%\nssm install "QLBV JobSignCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSignCtdt"
    %NSSM_PATH%\nssm set "QLBV JobSignCtdt" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSubmitCtdt" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSubmitCtdt...
    %NSSM_PATH%\nssm install "QLBV JobSubmitCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitCtdt"
    %NSSM_PATH%\nssm set "QLBV JobSubmitCtdt" AppDirectory %LARAVEL_PATH%
)

:: Tao dich vu cho ctdt:import (quet inbox chung tu dien tu, nap, roi xep hang ky va gui)
::
:: AppExit Default Restart la BAT BUOC, khong phai tuy chon: lenh nay CO Y thoat sau
:: --so-vong vong de nssm dung lai mot tien trinh sach. PHP chay dai han o gioi han 128MB se
:: phinh, va thoat chu dong thi khac han bi OOM giet GIUA LUC dang goi cong BHXH - luc do
:: khong ai biet cong da nhan hay chua. Thieu dong nay thi dich vu chet han sau vong doi dau.
::
:: Cai dich vu nay KHONG bat duong gui that. Con chan cua viec gui la
:: organization.chung_tu_dien_tu.import_tu_dong_gui (mac dinh TAT) - duocGui() hoi no truoc
:: khi xep hang, nen khi chua bat thi lenh chi quet, nap va kiem loi.
::
:: Phanh tay: dat mot tep rong ten DUNG-GUI trong thu muc inbox la chan buoc gui NGAY vong
:: sau, khong can khoi dong lai dich vu.
%NSSM_PATH%\nssm status "QLBV CtdtImport" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV CtdtImport...
    %NSSM_PATH%\nssm install "QLBV CtdtImport" %PHP_PATH% "%LARAVEL_PATH%artisan ctdt:import --lien-tuc"
    %NSSM_PATH%\nssm set "QLBV CtdtImport" AppDirectory %LARAVEL_PATH%
)
:: Luon sua cau hinh: dich vu cai o lan truoc co the thieu AppExit Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppExit Default Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppRestartDelay 10000

:: Hang doi cua module danh muc TT12/2026/BTC.
::
:: CHU Y TEN: hang doi la "tt12" (chu thuong, khong co tien to Job), KHONG phai "JobTt12".
:: Ten dich vu va ten hang doi o day KHAC nhau - cac dich vu khac trong tep nay thi trung.
:: Gia tri that lay tu organization.tt12.hang_doi, ma tep do nam trong .gitignore nen moi
:: may mot ban: doi khoa do thi phai doi ca dong --queue duoi day, khong thi worker nghe
:: nham hang doi va ho so nam im.
::
:: BA hang doi RIENG chu khong mot (doi tu 26/08/2026): ky so hong vi ly do CUC BO (rut USB
:: token, HSM khong phan hoi) con gui hong vi MANG. Gop chung thi mot lan mang chap keo theo
:: ky lai - thao tac ton thoi gian nhat trong chuoi, va mot ho so TT12 co the la hang nghin
:: dong. Ba khoa cau hinh: hang_doi / hang_doi_ky / hang_doi_gui.
::
:: Thieu worker nao thi ho so dung khung o buoc do: thieu tt12 thi nam im o "Chua kiem" voi
:: cot "So loi" bang 0 - trong y het nhu moi ho so deu sach.
%NSSM_PATH%\nssm status "QLBV JobTt12" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobTt12...
    %NSSM_PATH%\nssm install "QLBV JobTt12" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=tt12"
    %NSSM_PATH%\nssm set "QLBV JobTt12" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSignTt12" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSignTt12...
    %NSSM_PATH%\nssm install "QLBV JobSignTt12" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=tt12-ky"
    %NSSM_PATH%\nssm set "QLBV JobSignTt12" AppDirectory %LARAVEL_PATH%
)

%NSSM_PATH%\nssm status "QLBV JobSubmitTt12" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV JobSubmitTt12...
    %NSSM_PATH%\nssm install "QLBV JobSubmitTt12" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=tt12-gui"
    %NSSM_PATH%\nssm set "QLBV JobSubmitTt12" AppDirectory %LARAVEL_PATH%
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
%NSSM_PATH%\nssm stop "QLBV JobCtdt"
%NSSM_PATH%\nssm stop "QLBV JobSignCtdt"
%NSSM_PATH%\nssm stop "QLBV JobSubmitCtdt"
%NSSM_PATH%\nssm stop "QLBV CtdtImport"
%NSSM_PATH%\nssm stop "QLBV JobTt12"
%NSSM_PATH%\nssm stop "QLBV JobSignTt12"
%NSSM_PATH%\nssm stop "QLBV JobSubmitTt12"
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
%NSSM_PATH%\nssm start "QLBV JobCtdt"
%NSSM_PATH%\nssm start "QLBV JobSignCtdt"
%NSSM_PATH%\nssm start "QLBV JobSubmitCtdt"
%NSSM_PATH%\nssm start "QLBV CtdtImport"
%NSSM_PATH%\nssm start "QLBV JobTt12"
%NSSM_PATH%\nssm start "QLBV JobSignTt12"
%NSSM_PATH%\nssm start "QLBV JobSubmitTt12"
%NSSM_PATH%\nssm start "QLBV JobExportQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobExportXml3176"
%NSSM_PATH%\nssm start "QLBV KiemTraYLenh"
%NSSM_PATH%\nssm start "QLBV KiemTraYLenhNotify"

:: Đưa ứng dụng ra khỏi chế độ bảo trì
echo Bringing the application out of maintenance mode...
php artisan up

echo Update completed successfully!