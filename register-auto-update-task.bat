@echo off
:: Chạy với quyền Administrator để đăng ký Scheduler Task
:: Script này thiết lập tự động hóa chạy file auto-updater.ps1 mỗi 1 tiếng

:: Đường dẫn hiện tại của dự án
set PROJECT_DIR=%~dp0
set SCRIPT_NAME=QLBV_AutoUpdater
set SCRIPT_PATH=%PROJECT_DIR%auto-updater.ps1

echo Setting up Windows Task Scheduler for [ %SCRIPT_NAME% ]...

:: Xóa task cũ nếu đã tồn tại
schtasks /delete /tn "%SCRIPT_NAME%" /f >nul 2>&1

:: Tạo task mới: Chạy mỗi 1 tiếng (/sc hourly /mo 1)
:: /ru "SYSTEM": Chạy với quyền hệ thống (không hiển thị cửa sổ CMD gây phiền người dùng)
:: /f: Ép buộc tạo
schtasks /create /tn "%SCRIPT_NAME%" /tr "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SCRIPT_PATH%\"" /sc hourly /mo 1 /ru "SYSTEM" /f

if %ERRORLEVEL% EQU 0 (
    echo [OK] Da dang ky thanh cong. He thong se tu dong kiem tra update moi 1 tieng.
    echo Hoac ban co the chay ngay bang lenh: schtasks /run /tn "%SCRIPT_NAME%"
) else (
    echo [ERROR] Loi khi dang ky Task! Vui long chay script nay voi quyen Administrator.
)

pause
