@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Đường dẫn đến PHP executable
set PHP_PATH=php.exe

:: Thư mục gốc chứa ứng dụng Laravel (thư mục hiện tại là thư mục gốc của dự án)
set LARAVEL_PATH=%~dp0

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

:: Tạo dịch vụ cho TrucDuLieuYTeXmlScan
%NSSM_PATH%\nssm install "QLBV TrucDuLieuYTeXmlScan" %PHP_PATH% "%LARAVEL_PATH%artisan truc-du-lieu-y-te:scan"
%NSSM_PATH%\nssm set "QLBV TrucDuLieuYTeXmlScan" AppDirectory %LARAVEL_PATH%

:: Khởi động tất cả các dịch vụ
%NSSM_PATH%\nssm start "QLBV JobQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm start "QLBV ImportCatalog"
%NSSM_PATH%\nssm start "QLBV XMLImport"
%NSSM_PATH%\nssm start "QLBV TrucDuLieuYTeXmlScan"

echo Service install completed successfully.