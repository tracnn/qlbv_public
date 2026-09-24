@echo off
:: Đổi sang thư mục dự án (nếu cần)
cd /d "%~dp0"

:: Đường dẫn đến nssm.exe (giả sử nằm trong thư mục gốc của dự án)
set NSSM_PATH=%~dp0

:: Xóa dịch vụ cho JobXml3176
%NSSM_PATH%\nssm stop "QLBV JobXml3176"
%NSSM_PATH%\nssm remove "QLBV JobXml3176" confirm

:: Xóa dịch vụ cho JobKtTheBHYT
%NSSM_PATH%\nssm stop "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm remove "QLBV JobKtTheBHYT" confirm

:: Xóa dịch vụ cho importCatalogBHXH:data
%NSSM_PATH%\nssm stop "QLBV ImportCatalog"
%NSSM_PATH%\nssm remove "QLBV ImportCatalog" confirm

:: Xóa dịch vụ cho XMLImport3176
%NSSM_PATH%\nssm stop "QLBV XMLImport3176"
%NSSM_PATH%\nssm remove "QLBV XMLImport3176" confirm

:: Xóa dịch vụ cho TrucDuLieuYTeXmlScan
%NSSM_PATH%\nssm stop "QLBV TrucDuLieuYTeXmlScan"
%NSSM_PATH%\nssm remove "QLBV TrucDuLieuYTeXmlScan" confirm

:: Xóa dịch vụ cho CongDuLieuYTeDienBienXmlScan
%NSSM_PATH%\nssm stop "QLBV CongDuLieuYTeDienBienXmlScan"
%NSSM_PATH%\nssm remove "QLBV CongDuLieuYTeDienBienXmlScan" confirm

:: Xóa dịch vụ cho JobSubmitXml3176
%NSSM_PATH%\nssm stop "QLBV JobSubmitXml3176"
%NSSM_PATH%\nssm remove "QLBV JobSubmitXml3176" confirm

:: Xóa dịch vụ cho JobExportXml3176
%NSSM_PATH%\nssm stop "QLBV JobExportXml3176"
%NSSM_PATH%\nssm remove "QLBV JobExportXml3176" confirm

:: Xoa dich vu cho JobCtdt (bo kiem loi)
%NSSM_PATH%\nssm stop "QLBV JobCtdt"
%NSSM_PATH%\nssm remove "QLBV JobCtdt" confirm

:: Xoa dich vu cho JobSignCtdt (ky so)
%NSSM_PATH%\nssm stop "QLBV JobSignCtdt"
%NSSM_PATH%\nssm remove "QLBV JobSignCtdt" confirm

:: Xoa dich vu cho JobSubmitCtdt (gui cong BHXH)
%NSSM_PATH%\nssm stop "QLBV JobSubmitCtdt"
%NSSM_PATH%\nssm remove "QLBV JobSubmitCtdt" confirm

:: Xoa dich vu cho hang doi TT12 (kiem, ky, gui danh muc TT12/2026/BTC)
%NSSM_PATH%\nssm stop "QLBV JobTt12"
%NSSM_PATH%\nssm remove "QLBV JobTt12" confirm

%NSSM_PATH%\nssm stop "QLBV JobSignTt12"
%NSSM_PATH%\nssm remove "QLBV JobSignTt12" confirm

%NSSM_PATH%\nssm stop "QLBV JobSubmitTt12"
%NSSM_PATH%\nssm remove "QLBV JobSubmitTt12" confirm

:: Xoa dich vu cho ctdt:import (quet inbox chung tu dien tu)
%NSSM_PATH%\nssm stop "QLBV CtdtImport"
%NSSM_PATH%\nssm remove "QLBV CtdtImport" confirm

:: Xóa dịch vụ cho kiemtraylenh:scan
%NSSM_PATH%\nssm stop "QLBV KiemTraYLenh"
%NSSM_PATH%\nssm remove "QLBV KiemTraYLenh" confirm

:: Xóa dịch vụ cho kiemtraylenh:notify
%NSSM_PATH%\nssm stop "QLBV KiemTraYLenhNotify"
%NSSM_PATH%\nssm remove "QLBV KiemTraYLenhNotify" confirm

:: Dich vu cu cua module Qd130 (XML4750) - module da go, he thong chi con giu XML3176.
:: install_service.bat va update.bat khong con cai cac dich vu nay (update.bat tu go chung);
:: giu lai day de may nao con sot van don duoc. Dich vu khong ton tai thi nssm chi bao loi
:: roi chay tiep, khong anh huong. "QLBV XMLImport" la dich vu chay xml130import:day.
for %%S in ("QLBV JobQd130Xml" "QLBV JobSubmitQd130Xml" "QLBV JobExportQd130Xml" "QLBV XMLImport") do (
    %NSSM_PATH%\nssm stop %%S
    %NSSM_PATH%\nssm remove %%S confirm
)

echo Services uninstall completed successfully.