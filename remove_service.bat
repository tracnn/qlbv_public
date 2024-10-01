@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

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

echo Các dịch vụ đã được xóa thành công.
pause