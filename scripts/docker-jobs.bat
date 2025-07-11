@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

REM QLBV Docker Jobs Management Script for Windows
REM Quản lý các service job trên Docker

echo ========================================
echo QLBV Docker Jobs Management Script
echo ========================================
echo.

REM Check if Docker is running
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker chưa được cài đặt hoặc không chạy
    pause
    exit /b 1
)

REM Function to list jobs
:list_jobs
if "%1"=="list" (
    echo [INFO] Danh sách các service job:
    echo.
    echo 1. queue_qd130xml     - JobQd130Xml Queue Worker
    echo 2. queue_ktbhytthe    - JobKtTheBHYT Queue Worker
    echo 3. import_catalog     - Import Catalog BHXH Service
    echo 4. xml_import         - XML Import Service
    echo 5. scheduler          - Laravel Scheduler
    echo.
    goto :eof
)

REM Function to show status
:show_status
if "%1"=="status" (
    echo [INFO] Trạng thái các service job:
    echo.
    docker-compose ps | findstr /i "queue import xml scheduler" 2>nul || echo Không có service job nào đang chạy
    echo.
    goto :eof
)

REM Function to start job
:start_job
if "%1"=="start" (
    if "%2"=="" (
        echo [ERROR] Cần chỉ định job name
        goto :show_usage
    )
    
    if "%2"=="qd130xml" (
        echo [INFO] Khởi động JobQd130Xml Queue Worker...
        docker-compose up -d queue_qd130xml
        echo [SUCCESS] JobQd130Xml Queue Worker đã được khởi động
    ) else if "%2"=="ktbhytthe" (
        echo [INFO] Khởi động JobKtTheBHYT Queue Worker...
        docker-compose up -d queue_ktbhytthe
        echo [SUCCESS] JobKtTheBHYT Queue Worker đã được khởi động
    ) else if "%2"=="catalog" (
        echo [INFO] Khởi động Import Catalog Service...
        docker-compose up -d import_catalog
        echo [SUCCESS] Import Catalog Service đã được khởi động
    ) else if "%2"=="xml" (
        echo [INFO] Khởi động XML Import Service...
        docker-compose up -d xml_import
        echo [SUCCESS] XML Import Service đã được khởi động
    ) else if "%2"=="scheduler" (
        echo [INFO] Khởi động Laravel Scheduler...
        docker-compose up -d scheduler
        echo [SUCCESS] Laravel Scheduler đã được khởi động
    ) else if "%2"=="all" (
        echo [INFO] Khởi động tất cả service job...
        docker-compose up -d queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
        echo [SUCCESS] Tất cả service job đã được khởi động
    ) else (
        echo [ERROR] Service job không hợp lệ: %2
        echo Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all
        exit /b 1
    )
    goto :eof
)

REM Function to stop job
:stop_job
if "%1"=="stop" (
    if "%2"=="" (
        echo [ERROR] Cần chỉ định job name
        goto :show_usage
    )
    
    if "%2"=="qd130xml" (
        echo [INFO] Dừng JobQd130Xml Queue Worker...
        docker-compose stop queue_qd130xml
        echo [SUCCESS] JobQd130Xml Queue Worker đã được dừng
    ) else if "%2"=="ktbhytthe" (
        echo [INFO] Dừng JobKtTheBHYT Queue Worker...
        docker-compose stop queue_ktbhytthe
        echo [SUCCESS] JobKtTheBHYT Queue Worker đã được dừng
    ) else if "%2"=="catalog" (
        echo [INFO] Dừng Import Catalog Service...
        docker-compose stop import_catalog
        echo [SUCCESS] Import Catalog Service đã được dừng
    ) else if "%2"=="xml" (
        echo [INFO] Dừng XML Import Service...
        docker-compose stop xml_import
        echo [SUCCESS] XML Import Service đã được dừng
    ) else if "%2"=="scheduler" (
        echo [INFO] Dừng Laravel Scheduler...
        docker-compose stop scheduler
        echo [SUCCESS] Laravel Scheduler đã được dừng
    ) else if "%2"=="all" (
        echo [INFO] Dừng tất cả service job...
        docker-compose stop queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
        echo [SUCCESS] Tất cả service job đã được dừng
    ) else (
        echo [ERROR] Service job không hợp lệ: %2
        echo Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all
        exit /b 1
    )
    goto :eof
)

REM Function to restart job
:restart_job
if "%1"=="restart" (
    if "%2"=="" (
        echo [ERROR] Cần chỉ định job name
        goto :show_usage
    )
    
    if "%2"=="qd130xml" (
        echo [INFO] Restart JobQd130Xml Queue Worker...
        docker-compose restart queue_qd130xml
        echo [SUCCESS] JobQd130Xml Queue Worker đã được restart
    ) else if "%2"=="ktbhytthe" (
        echo [INFO] Restart JobKtTheBHYT Queue Worker...
        docker-compose restart queue_ktbhytthe
        echo [SUCCESS] JobKtTheBHYT Queue Worker đã được restart
    ) else if "%2"=="catalog" (
        echo [INFO] Restart Import Catalog Service...
        docker-compose restart import_catalog
        echo [SUCCESS] Import Catalog Service đã được restart
    ) else if "%2"=="xml" (
        echo [INFO] Restart XML Import Service...
        docker-compose restart xml_import
        echo [SUCCESS] XML Import Service đã được restart
    ) else if "%2"=="scheduler" (
        echo [INFO] Restart Laravel Scheduler...
        docker-compose restart scheduler
        echo [SUCCESS] Laravel Scheduler đã được restart
    ) else if "%2"=="all" (
        echo [INFO] Restart tất cả service job...
        docker-compose restart queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
        echo [SUCCESS] Tất cả service job đã được restart
    ) else (
        echo [ERROR] Service job không hợp lệ: %2
        echo Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all
        exit /b 1
    )
    goto :eof
)

REM Function to show logs
:show_logs
if "%1"=="logs" (
    if "%2"=="" (
        echo [ERROR] Cần chỉ định job name
        goto :show_usage
    )
    
    if "%2"=="qd130xml" (
        echo [INFO] Logs của JobQd130Xml Queue Worker:
        docker-compose logs -f queue_qd130xml
    ) else if "%2"=="ktbhytthe" (
        echo [INFO] Logs của JobKtTheBHYT Queue Worker:
        docker-compose logs -f queue_ktbhytthe
    ) else if "%2"=="catalog" (
        echo [INFO] Logs của Import Catalog Service:
        docker-compose logs -f import_catalog
    ) else if "%2"=="xml" (
        echo [INFO] Logs của XML Import Service:
        docker-compose logs -f xml_import
    ) else if "%2"=="scheduler" (
        echo [INFO] Logs của Laravel Scheduler:
        docker-compose logs -f scheduler
    ) else if "%2"=="all" (
        echo [INFO] Logs của tất cả service job:
        docker-compose logs -f queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
    ) else (
        echo [ERROR] Service job không hợp lệ: %2
        echo Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all
        exit /b 1
    )
    goto :eof
)

REM Function to execute command
:exec_job
if "%1"=="exec" (
    if "%2"=="" (
        echo [ERROR] Cần chỉ định job name
        goto :show_usage
    )
    if "%3"=="" (
        echo [ERROR] Cần chỉ định command
        goto :show_usage
    )
    
    if "%2"=="qd130xml" (
        docker-compose exec queue_qd130xml php artisan %3
    ) else if "%2"=="ktbhytthe" (
        docker-compose exec queue_ktbhytthe php artisan %3
    ) else if "%2"=="catalog" (
        docker-compose exec import_catalog php artisan %3
    ) else if "%2"=="xml" (
        docker-compose exec xml_import php artisan %3
    ) else if "%2"=="scheduler" (
        docker-compose exec scheduler php artisan %3
    ) else (
        echo [ERROR] Service job không hợp lệ: %2
        echo Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler
        exit /b 1
    )
    goto :eof
)

REM Function to monitor queues
:monitor_queues
if "%1"=="monitor" (
    echo [INFO] Monitoring queue jobs...
    echo.
    
    echo [INFO] Queue Status:
    docker-compose exec app php artisan queue:monitor
    
    echo [INFO] Failed Jobs:
    docker-compose exec app php artisan queue:failed
    
    echo [INFO] Queue Workers:
    docker-compose ps | findstr /i "queue"
    goto :eof
)

REM Show usage
:show_usage
echo QLBV Docker Jobs Management Script
echo.
echo Usage: %0 [COMMAND] [JOB_NAME] [OPTIONS]
echo.
echo Commands:
echo   list                    - Liệt kê các service job
echo   status                  - Hiển thị trạng thái các job
echo   start [JOB_NAME]        - Khởi động job
echo   stop [JOB_NAME]         - Dừng job
echo   restart [JOB_NAME]      - Restart job
echo   logs [JOB_NAME]         - Xem logs của job
echo   exec [JOB_NAME] [CMD]   - Chạy lệnh trong job container
echo   monitor                 - Monitor queue jobs
echo.
echo Job Names:
echo   qd130xml                - JobQd130Xml Queue Worker
echo   ktbhytthe               - JobKtTheBHYT Queue Worker
echo   catalog                 - Import Catalog Service
echo   xml                     - XML Import Service
echo   scheduler               - Laravel Scheduler
echo   all                     - Tất cả jobs
echo.
echo Examples:
echo   %0 list                 - Liệt kê jobs
echo   %0 start qd130xml       - Khởi động JobQd130Xml
echo   %0 stop all             - Dừng tất cả jobs
echo   %0 logs catalog         - Xem logs Import Catalog
echo   %0 exec qd130xml tinker - Chạy tinker trong container
echo   %0 monitor              - Monitor queue jobs
echo.
goto :eof

REM Main logic
if "%1"=="" (
    goto :show_usage
)

if "%1"=="list" (
    call :list_jobs %*
) else if "%1"=="status" (
    call :show_status %*
) else if "%1"=="start" (
    call :start_job %*
) else if "%1"=="stop" (
    call :stop_job %*
) else if "%1"=="restart" (
    call :restart_job %*
) else if "%1"=="logs" (
    call :show_logs %*
) else if "%1"=="exec" (
    call :exec_job %*
) else if "%1"=="monitor" (
    call :monitor_queues %*
) else (
    goto :show_usage
) 