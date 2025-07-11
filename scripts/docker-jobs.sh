#!/bin/bash

# QLBV Docker Jobs Management Script
# Quản lý các service job trên Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# List all job services
list_jobs() {
    print_status "Danh sách các service job:"
    echo ""
    echo "1. queue_qd130xml     - JobQd130Xml Queue Worker"
    echo "2. queue_ktbhytthe    - JobKtTheBHYT Queue Worker"
    echo "3. import_catalog     - Import Catalog BHXH Service"
    echo "4. xml_import         - XML Import Service"
    echo "5. scheduler          - Laravel Scheduler"
    echo ""
}

# Show job status
show_status() {
    print_status "Trạng thái các service job:"
    echo ""
    docker-compose ps | grep -E "(queue|import|xml|scheduler)" || echo "Không có service job nào đang chạy"
    echo ""
}

# Start specific job
start_job() {
    local job_name="$1"
    
    case "$job_name" in
        "qd130xml"|"queue_qd130xml")
            print_status "Khởi động JobQd130Xml Queue Worker..."
            docker-compose up -d queue_qd130xml
            print_success "JobQd130Xml Queue Worker đã được khởi động"
            ;;
        "ktbhytthe"|"queue_ktbhytthe")
            print_status "Khởi động JobKtTheBHYT Queue Worker..."
            docker-compose up -d queue_ktbhytthe
            print_success "JobKtTheBHYT Queue Worker đã được khởi động"
            ;;
        "catalog"|"import_catalog")
            print_status "Khởi động Import Catalog Service..."
            docker-compose up -d import_catalog
            print_success "Import Catalog Service đã được khởi động"
            ;;
        "xml"|"xml_import")
            print_status "Khởi động XML Import Service..."
            docker-compose up -d xml_import
            print_success "XML Import Service đã được khởi động"
            ;;
        "scheduler")
            print_status "Khởi động Laravel Scheduler..."
            docker-compose up -d scheduler
            print_success "Laravel Scheduler đã được khởi động"
            ;;
        "all")
            print_status "Khởi động tất cả service job..."
            docker-compose up -d queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
            print_success "Tất cả service job đã được khởi động"
            ;;
        *)
            print_error "Service job không hợp lệ: $job_name"
            echo "Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all"
            exit 1
            ;;
    esac
}

# Stop specific job
stop_job() {
    local job_name="$1"
    
    case "$job_name" in
        "qd130xml"|"queue_qd130xml")
            print_status "Dừng JobQd130Xml Queue Worker..."
            docker-compose stop queue_qd130xml
            print_success "JobQd130Xml Queue Worker đã được dừng"
            ;;
        "ktbhytthe"|"queue_ktbhytthe")
            print_status "Dừng JobKtTheBHYT Queue Worker..."
            docker-compose stop queue_ktbhytthe
            print_success "JobKtTheBHYT Queue Worker đã được dừng"
            ;;
        "catalog"|"import_catalog")
            print_status "Dừng Import Catalog Service..."
            docker-compose stop import_catalog
            print_success "Import Catalog Service đã được dừng"
            ;;
        "xml"|"xml_import")
            print_status "Dừng XML Import Service..."
            docker-compose stop xml_import
            print_success "XML Import Service đã được dừng"
            ;;
        "scheduler")
            print_status "Dừng Laravel Scheduler..."
            docker-compose stop scheduler
            print_success "Laravel Scheduler đã được dừng"
            ;;
        "all")
            print_status "Dừng tất cả service job..."
            docker-compose stop queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
            print_success "Tất cả service job đã được dừng"
            ;;
        *)
            print_error "Service job không hợp lệ: $job_name"
            echo "Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all"
            exit 1
            ;;
    esac
}

# Restart specific job
restart_job() {
    local job_name="$1"
    
    case "$job_name" in
        "qd130xml"|"queue_qd130xml")
            print_status "Restart JobQd130Xml Queue Worker..."
            docker-compose restart queue_qd130xml
            print_success "JobQd130Xml Queue Worker đã được restart"
            ;;
        "ktbhytthe"|"queue_ktbhytthe")
            print_status "Restart JobKtTheBHYT Queue Worker..."
            docker-compose restart queue_ktbhytthe
            print_success "JobKtTheBHYT Queue Worker đã được restart"
            ;;
        "catalog"|"import_catalog")
            print_status "Restart Import Catalog Service..."
            docker-compose restart import_catalog
            print_success "Import Catalog Service đã được restart"
            ;;
        "xml"|"xml_import")
            print_status "Restart XML Import Service..."
            docker-compose restart xml_import
            print_success "XML Import Service đã được restart"
            ;;
        "scheduler")
            print_status "Restart Laravel Scheduler..."
            docker-compose restart scheduler
            print_success "Laravel Scheduler đã được restart"
            ;;
        "all")
            print_status "Restart tất cả service job..."
            docker-compose restart queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
            print_success "Tất cả service job đã được restart"
            ;;
        *)
            print_error "Service job không hợp lệ: $job_name"
            echo "Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all"
            exit 1
            ;;
    esac
}

# Show logs for specific job
show_logs() {
    local job_name="$1"
    
    case "$job_name" in
        "qd130xml"|"queue_qd130xml")
            print_status "Logs của JobQd130Xml Queue Worker:"
            docker-compose logs -f queue_qd130xml
            ;;
        "ktbhytthe"|"queue_ktbhytthe")
            print_status "Logs của JobKtTheBHYT Queue Worker:"
            docker-compose logs -f queue_ktbhytthe
            ;;
        "catalog"|"import_catalog")
            print_status "Logs của Import Catalog Service:"
            docker-compose logs -f import_catalog
            ;;
        "xml"|"xml_import")
            print_status "Logs của XML Import Service:"
            docker-compose logs -f xml_import
            ;;
        "scheduler")
            print_status "Logs của Laravel Scheduler:"
            docker-compose logs -f scheduler
            ;;
        "all")
            print_status "Logs của tất cả service job:"
            docker-compose logs -f queue_qd130xml queue_ktbhytthe import_catalog xml_import scheduler
            ;;
        *)
            print_error "Service job không hợp lệ: $job_name"
            echo "Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler, all"
            exit 1
            ;;
    esac
}

# Execute artisan command in job container
exec_job() {
    local job_name="$1"
    local command="$2"
    
    case "$job_name" in
        "qd130xml"|"queue_qd130xml")
            docker-compose exec queue_qd130xml php artisan $command
            ;;
        "ktbhytthe"|"queue_ktbhytthe")
            docker-compose exec queue_ktbhytthe php artisan $command
            ;;
        "catalog"|"import_catalog")
            docker-compose exec import_catalog php artisan $command
            ;;
        "xml"|"xml_import")
            docker-compose exec xml_import php artisan $command
            ;;
        "scheduler")
            docker-compose exec scheduler php artisan $command
            ;;
        *)
            print_error "Service job không hợp lệ: $job_name"
            echo "Các service job hợp lệ: qd130xml, ktbhytthe, catalog, xml, scheduler"
            exit 1
            ;;
    esac
}

# Monitor queue jobs
monitor_queues() {
    print_status "Monitoring queue jobs..."
    echo ""
    
    # Check queue status
    print_status "Queue Status:"
    docker-compose exec app php artisan queue:monitor
    
    # Check failed jobs
    print_status "Failed Jobs:"
    docker-compose exec app php artisan queue:failed
    
    # Check queue workers
    print_status "Queue Workers:"
    docker-compose ps | grep queue
}

# Show usage
show_usage() {
    echo "QLBV Docker Jobs Management Script"
    echo ""
    echo "Usage: $0 [COMMAND] [JOB_NAME] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  list                    - Liệt kê các service job"
    echo "  status                  - Hiển thị trạng thái các job"
    echo "  start [JOB_NAME]        - Khởi động job"
    echo "  stop [JOB_NAME]         - Dừng job"
    echo "  restart [JOB_NAME]      - Restart job"
    echo "  logs [JOB_NAME]         - Xem logs của job"
    echo "  exec [JOB_NAME] [CMD]   - Chạy lệnh trong job container"
    echo "  monitor                 - Monitor queue jobs"
    echo ""
    echo "Job Names:"
    echo "  qd130xml                - JobQd130Xml Queue Worker"
    echo "  ktbhytthe               - JobKtTheBHYT Queue Worker"
    echo "  catalog                 - Import Catalog Service"
    echo "  xml                     - XML Import Service"
    echo "  scheduler               - Laravel Scheduler"
    echo "  all                     - Tất cả jobs"
    echo ""
    echo "Examples:"
    echo "  $0 list                 - Liệt kê jobs"
    echo "  $0 start qd130xml       - Khởi động JobQd130Xml"
    echo "  $0 stop all             - Dừng tất cả jobs"
    echo "  $0 logs catalog         - Xem logs Import Catalog"
    echo "  $0 exec qd130xml tinker - Chạy tinker trong container"
    echo "  $0 monitor              - Monitor queue jobs"
    echo ""
}

# Main function
main() {
    case "$1" in
        "list")
            list_jobs
            ;;
        "status")
            show_status
            ;;
        "start")
            if [ -z "$2" ]; then
                print_error "Cần chỉ định job name"
                show_usage
                exit 1
            fi
            start_job "$2"
            ;;
        "stop")
            if [ -z "$2" ]; then
                print_error "Cần chỉ định job name"
                show_usage
                exit 1
            fi
            stop_job "$2"
            ;;
        "restart")
            if [ -z "$2" ]; then
                print_error "Cần chỉ định job name"
                show_usage
                exit 1
            fi
            restart_job "$2"
            ;;
        "logs")
            if [ -z "$2" ]; then
                print_error "Cần chỉ định job name"
                show_usage
                exit 1
            fi
            show_logs "$2"
            ;;
        "exec")
            if [ -z "$2" ] || [ -z "$3" ]; then
                print_error "Cần chỉ định job name và command"
                show_usage
                exit 1
            fi
            exec_job "$2" "$3"
            ;;
        "monitor")
            monitor_queues
            ;;
        *)
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@" 