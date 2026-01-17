@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Xóa dịch vụ cho JobQd130Xml
%NSSM_PATH%\nssm stop "QLBV JobQd130Xml"
%NSSM_PATH%\nssm remove "QLBV JobQd130Xml" confirm

:: Xóa dịch vụ cho JobXml3176
%NSSM_PATH%\nssm stop "QLBV JobXml3176"
%NSSM_PATH%\nssm remove "QLBV JobXml3176" confirm

:: Xóa dịch vụ cho JobKtTheBHYT
%NSSM_PATH%\nssm stop "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm remove "QLBV JobKtTheBHYT" confirm

:: Xóa dịch vụ cho importCatalogBHXH:data
%NSSM_PATH%\nssm stop "QLBV ImportCatalog"
%NSSM_PATH%\nssm remove "QLBV ImportCatalog" confirm

:: Xóa dịch vụ cho xml130import:day
%NSSM_PATH%\nssm stop "QLBV XMLImport"
%NSSM_PATH%\nssm remove "QLBV XMLImport" confirm

:: Xóa dịch vụ cho XMLImport3176
%NSSM_PATH%\nssm stop "QLBV XMLImport3176"
%NSSM_PATH%\nssm remove "QLBV XMLImport3176" confirm

:: Xóa dịch vụ cho TrucDuLieuYTeXmlScan
%NSSM_PATH%\nssm stop "QLBV TrucDuLieuYTeXmlScan"
%NSSM_PATH%\nssm remove "QLBV TrucDuLieuYTeXmlScan" confirm

:: Xóa dịch vụ cho CongDuLieuYTeDienBienXmlScan
%NSSM_PATH%\nssm stop "QLBV CongDuLieuYTeDienBienXmlScan"
%NSSM_PATH%\nssm remove "QLBV CongDuLieuYTeDienBienXmlScan" confirm

echo Services uninstall completed successfully.