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

:: Tạo dịch vụ cho JobXml3176
%NSSM_PATH%\nssm install "QLBV JobXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobXml3176"
%NSSM_PATH%\nssm set "QLBV JobXml3176" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobKtTheBHYT
%NSSM_PATH%\nssm install "QLBV JobKtTheBHYT" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobKtTheBHYT"
%NSSM_PATH%\nssm set "QLBV JobKtTheBHYT" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho importCatalogBHXH:data
%NSSM_PATH%\nssm install "QLBV ImportCatalog" %PHP_PATH% "%LARAVEL_PATH%artisan importCatalogBHXH:data"
%NSSM_PATH%\nssm set "QLBV ImportCatalog" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho xml130import:day
%NSSM_PATH%\nssm install "QLBV XMLImport" %PHP_PATH% "%LARAVEL_PATH%artisan xml130import:day"
%NSSM_PATH%\nssm set "QLBV XMLImport" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho xml3176import:day
%NSSM_PATH%\nssm install "QLBV XMLImport3176" %PHP_PATH% "%LARAVEL_PATH%artisan xml3176import:day"
%NSSM_PATH%\nssm set "QLBV XMLImport3176" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho TrucDuLieuYTeXmlScan
%NSSM_PATH%\nssm install "QLBV TrucDuLieuYTeXmlScan" %PHP_PATH% "%LARAVEL_PATH%artisan truc-du-lieu-y-te:scan"
%NSSM_PATH%\nssm set "QLBV TrucDuLieuYTeXmlScan" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho CongDuLieuYTeDienBienXmlScan
%NSSM_PATH%\nssm install "QLBV CongDuLieuYTeDienBienXmlScan" %PHP_PATH% "%LARAVEL_PATH%artisan cong-du-lieu-y-te-dien-bien:scan"
%NSSM_PATH%\nssm set "QLBV CongDuLieuYTeDienBienXmlScan" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobSubmitQd130Xml
%NSSM_PATH%\nssm install "QLBV JobSubmitQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitQd130Xml"
%NSSM_PATH%\nssm set "QLBV JobSubmitQd130Xml" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobSubmitXml3176
%NSSM_PATH%\nssm install "QLBV JobSubmitXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitXml3176"
%NSSM_PATH%\nssm set "QLBV JobSubmitXml3176" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobCtdt (kiểm lỗi chứng từ điện tử)
%NSSM_PATH%\nssm install "QLBV JobCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobCtdt"
%NSSM_PATH%\nssm set "QLBV JobCtdt" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobSignCtdt (ký số chứng từ điện tử)
%NSSM_PATH%\nssm install "QLBV JobSignCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSignCtdt"
%NSSM_PATH%\nssm set "QLBV JobSignCtdt" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobSubmitCtdt (gửi chứng từ điện tử lên cổng BHXH)
%NSSM_PATH%\nssm install "QLBV JobSubmitCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitCtdt"
%NSSM_PATH%\nssm set "QLBV JobSubmitCtdt" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobExportQd130Xml
%NSSM_PATH%\nssm install "QLBV JobExportQd130Xml" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobExportQd130Xml"
%NSSM_PATH%\nssm set "QLBV JobExportQd130Xml" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobExportXml3176
%NSSM_PATH%\nssm install "QLBV JobExportXml3176" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobExportXml3176"
%NSSM_PATH%\nssm set "QLBV JobExportXml3176" AppDirectory %LARAVEL_PATH%

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
%NSSM_PATH%\nssm install "QLBV CtdtImport" %PHP_PATH% "%LARAVEL_PATH%artisan ctdt:import --lien-tuc"
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppDirectory %LARAVEL_PATH%
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppExit Default Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppRestartDelay 10000

:: Tạo dịch vụ cho kiemtraylenh:scan (Kiểm tra sai sót y lệnh - quét HIS định kỳ)
%NSSM_PATH%\nssm install "QLBV KiemTraYLenh" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:scan"
%NSSM_PATH%\nssm set "QLBV KiemTraYLenh" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho kiemtraylenh:notify (Gửi email digest sai sót y lệnh)
%NSSM_PATH%\nssm install "QLBV KiemTraYLenhNotify" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:notify"
%NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" AppDirectory %LARAVEL_PATH%

:: Khởi động tất cả các dịch vụ
%NSSM_PATH%\nssm start "QLBV JobQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobXml3176"
%NSSM_PATH%\nssm start "QLBV JobKtTheBHYT"
%NSSM_PATH%\nssm start "QLBV ImportCatalog"
%NSSM_PATH%\nssm start "QLBV XMLImport"
%NSSM_PATH%\nssm start "QLBV XMLImport3176"
%NSSM_PATH%\nssm start "QLBV TrucDuLieuYTeXmlScan"
%NSSM_PATH%\nssm start "QLBV CongDuLieuYTeDienBienXmlScan"
%NSSM_PATH%\nssm start "QLBV JobSubmitQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobSubmitXml3176"
%NSSM_PATH%\nssm start "QLBV JobCtdt"
%NSSM_PATH%\nssm start "QLBV JobSignCtdt"
%NSSM_PATH%\nssm start "QLBV JobSubmitCtdt"
%NSSM_PATH%\nssm start "QLBV CtdtImport"
%NSSM_PATH%\nssm start "QLBV JobExportQd130Xml"
%NSSM_PATH%\nssm start "QLBV JobExportXml3176"
%NSSM_PATH%\nssm start "QLBV KiemTraYLenh"
%NSSM_PATH%\nssm start "QLBV KiemTraYLenhNotify"

echo Service install completed successfully.