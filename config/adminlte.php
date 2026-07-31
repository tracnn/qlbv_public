<?php

// Logo MAC DINH, dung khi don vi khong cau hinh logo rieng.
//
// Muon dung logo rieng thi dat 'organization_logo' trong config/organization.php, hoac
// bien moi truong ORGANIZATION_LOGO. AppServiceProvider::boot() se ghi de gia tri duoi day.
//
// KHONG doc config('organization.organization_logo') tai day: Laravel nap cac file config
// theo thu tu ksort tu nhien, ma 'adminlte' dung TRUOC 'organization', nen luc file nay
// chay thi khoa do chua ton tai va se tra ve null - logo se LUON roi ve mac dinh ma khong
// ai phat hien, vi mac dinh trong van dung.
$logoImg = '<img src="/images/logo.png" alt="GĐBHYT" style="height: 50px;">';

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | The default title of your admin panel, this goes into the title tag
    | of your page. You can override it per page with the title section.
    | You can optionally also specify a title prefix and/or postfix.
    |
    */

    'title' => 'GĐBHYT',

    'title_prefix' => '',

    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Footer: phien ban va dong ban quyen
    |--------------------------------------------------------------------------
    |
    | Hai gia tri nay hien o chan trang, doc boi resources/views/vendor/adminlte/
    | page.blade.php. Doi phien ban hay dong ban quyen thi sua O DAY roi chay
    | `php artisan config:clear` - KHONG sua blade.
    |
    | 'footer' truoc day khong ton tai nen blade luon roi ve chuoi mac dinh viet
    | thang trong no: nhin thi tuong cau hinh duoc, thuc te thi khong. Khai o day
    | de gia tri mac dinh do het tac dung.
    |
    | 'footer' duoc in bang {!! !!} nen HTML trong do co hieu luc. Chi dat noi dung
    | do CHINH MINH viet, khong lay tu dau khac vao.
    |
    */

    'version' => '2026.07.31.1',

    'footer' => 'Copyright &#9400; by <b><a href="https://www.facebook.com/trac.nguyenngoc" target="_blank">Trác Nguyễn Ngọc</a></b>',

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | This logo is displayed at the upper left corner of your admin panel.
    | You can use basic HTML here if you want. The logo has also a mini
    | variant, used for the mini side bar. Make it 3 letters or so
    |
    */

    'logo' => $logoImg,
    'logo_mini' => $logoImg,
    /*
    |--------------------------------------------------------------------------
    | Skin Color
    |--------------------------------------------------------------------------
    |
    | Choose a skin color for your admin panel. The available skin colors:
    | blue, black, purple, yellow, red, and green. Each skin also has a
    | ligth variant: blue-light, purple-light, purple-light, etc.
    |
    */

    'skin' => 'blue',

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Choose a layout for your admin panel. The available layout options:
    | null, 'boxed', 'fixed', 'top-nav'. null is the default, top-nav
    | removes the sidebar and places your menu in the top navbar
    |
    */

    'layout' => null,

    /*
    |--------------------------------------------------------------------------
    | Collapse Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we choose and option to be able to start with a collapsed side
    | bar. To adjust your sidebar layout simply set this  either true
    | this is compatible with layouts except top-nav layout option
    |
    */

    'collapse_sidebar' => false,

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Register here your dashboard, logout, login and register URLs. The
    | logout URL automatically sends a POST request in Laravel 5.3 or higher.
    | You can set the request to a GET or POST with logout_method.
    | Set register_url to null if you don't want a register link.
    |
    */

    'dashboard_url' => 'home',

    'logout_url' => 'logout',

    'logout_method' => null,

    'login_url' => 'login',

    'register_url' => 'register',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Specify your menu items to display in the left sidebar. Each menu item
    | should have a text and and a URL. You can also specify an icon from
    | Font Awesome. A string instead of an array represents a header in sidebar
    | layout. The 'can' is a filter on Laravel's built in Gate functionality.
    |
    */

    'menu' => [
        'CÁC CHỨC NĂNG CHÍNH',
        [
            'text'    => 'Kế hoạch tổng hợp',
            'icon'    => 'id-card',
            'checkrole'   => 'administrator',
            'submenu' => [
                [
                    'text'  => 'Thống kê',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'submenu' => [
                        [
                            'text'  => '1. Số lượt khám',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.so-luot-kham-index',
                            'active'=> ['khth/so-luot-kham-index*'],
                        ],
                        [
                            'text'  => '2. Chi phí khám bệnh',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.chi-phi-kham-benh-index',
                            'active'=> ['khth/chi-phi-kham-benh-index*'],
                        ],
                        [
                            'text'  => '3. Nhập viện theo PK',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.dieu-tri-noi-tru-index',
                            'active'=> ['khth/dieu-tri-noi-tru-index*'],
                        ],
                        [
                            'text'  => '4. Nhập viện theo khoa',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.noi-tru-theo-khoa-index',
                            'active'=> ['khth/noi-tru-theo-khoa-index*'],
                        ],
                        [
                            'text'  => '5. BN (+) SAR-COV-2',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.bn-sar-cov-2-index',
                            'active'=> ['khth/bn-sar-cov-2-index*'],
                        ],
                        [
                            'text'  => '6. Ngoại trú',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.thong-ke-in-index',
                            'active'=> ['khth/thong-ke-in-index*'],
                        ],
                        [
                            'text'  => '7. Nội trú',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.thong-ke-noitru-index',
                            'active'=> ['khth/thong-ke-noitru-index*'],
                        ],
                        [
                            'text'  => '8. Doanh thu',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.thong-ke-doanh-thu-index',
                            'active'=> ['khth/thong-ke-doanh-thu-index*'],
                        ],
                        [
                            'text'  => '9. Gia tăng CP theo NĐ75',
                            'icon'  => 'bar-chart',
                            'checkrole'   => 'administrator',
                            'route'   => 'khth.gia-tang-chi-phi-index',
                            'active'=> ['khth/gia-tang-chi-phi-index*'],
                        ],
                    ],
                ],
                [
                    'text'  => 'Kiểm soát nghiệp vụ',
                    'icon'  => 'check',
                    'checkrole'   => 'manager',
                    'submenu' => [
                        [
                            'text'  => '1. Nhắc việc',
                            'icon'  => 'sticky-note',
                            'checkrole'   => 'manager',
                            'route'   => 'khth.sticky-note',
                            'active'=> ['khth/sticky-note*'],
                        ],
                        [
                            'text'  => '2. Xét nghiệm - Chẩn đoán',
                            'icon'  => 'check',
                            'checkrole'   => 'manager',
                            'route'   => 'khth.xet-nghiem-chan-doan-index',
                            'active'=> ['khth/xet-nghiem-chan-doan-index*'],
                        ],
                    ],
                ],
                [
                    'text'  => 'Số liệu CV19031-BHXH',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'route'   => 'khth.cong-van-19031-index',
                    'active'=> ['khth/cong-van-19031-index*'],
                ],
                [
                    'text'  => 'Dashboard',
                    'icon'  => 'tachometer',
                    'checkrole'   => 'administrator',
                    'route'   => 'khth.dashboard',
                    'active'=> ['khth/dashboard*'],
                ],
                [
                    'text'  => 'Dashboard TV - Phòng Khám',
                    'icon'  => 'tv',
                    'checkrole'   => 'administrator',
                    'route'   => 'khth.phong-kham-tv',
                    'active'=> ['phong-kham-tv*'],
                ],
                [
                    'text'  => 'Thống kê theo bác sĩ',
                    'icon'  => 'user-md',
                    'checkrole'   => 'dashboard',
                    'route'   => 'dashboard.doctor-stats',
                    'active'=> ['dashboard/doctor-stats*'],
                ],
                [
                    'text'  => 'Xu hướng & Vận hành',
                    'icon'  => 'line-chart',
                    'checkrole'   => 'dashboard',
                    'route'   => 'dashboard.trends',
                    'active'=> ['dashboard/trends*'],
                ],
                [
                    'text'  => 'Công suất phòng mổ',
                    'icon'  => 'hospital-o',
                    'checkrole'   => 'dashboard',
                    'route'   => 'dashboard.operating-room',
                    'active'=> ['dashboard/operating-room*'],
                ],
                [
                    'text'      => 'Tỷ lệ trả KQ đúng hẹn',
                    'icon'      => 'clock-o',
                    'checkrole' => 'administrator',
                    'route'     => 'khth.on-time-result-index',
                    'active'    => ['khth/on-time-result-index*'],
                ],
                [
                    'text'      => 'Doanh thu theo khoa/phòng',
                    'icon'      => 'money',
                    'checkrole' => 'administrator',
                    'route'     => 'khth.revenue-dept-room-index',
                    'active'    => ['khth/revenue-dept-room-index*'],
                ],
            ],
        ],
        [
            'text'      => 'Báo cáo giao ban',
            'icon'      => 'calendar-check-o',
            'can'       => 'giaoban',
            'submenu'   => [
                [
                    'text'      => 'Báo cáo giao ban',
                    'icon'      => 'clipboard',
                    'can'       => 'giaoban',
                    'route'     => 'khth.giao-ban',
                    'active'    => ['khth/giao-ban'],
                ],
                [
                    'text'      => 'Cấu hình giao ban',
                    'icon'      => 'cogs',
                    'can'       => 'giaoban-admin',
                    'route'     => 'khth.giao-ban-config',
                    'active'    => ['khth/giao-ban/cau-hinh*'],
                ],
            ],
        ],
        [
            'text'    => 'Cập nhật dữ liệu',
            'icon'    => 'database',
            'submenu' => [
                [
                    'text'  => 'Khám sức khỏe',
                    'icon'  => 'medkit',
                    'checkrole'   => 'ksk',
                    'route'   => 'ksk.index',
                    'active'=> ['ksk/index*'],
                ],
                [
                    'text'  => 'Quản lý xếp hàng',
                    'icon'  => 'stack-overflow',
                    'checkrole'   => 'administrator',
                    'route'   => 'queue.manage',
                    'active'=> ['queue/manage*'],
                ],
            ],
        ],
        [
            'text'    => 'Tiêm chủng',
            'icon'    => 'database',
            'checkrole'   => 'vaccination',
            'submenu' => [
                [
                    'text'  => 'Danh mục',
                    'icon'  => 'book',
                    'checkrole'   => 'vaccination',
                    'submenu' => [
                        [
                            'text'  => 'Danh mục Vaccines',
                            'icon'  => 'medkit',
                            'checkrole'   => 'vaccination',
                            'route'   => 'vaccines.index',
                            'active'=> ['vaccination/vaccines*'],
                        ],
                        [
                            'text'  => 'Danh sách bệnh nhân',
                            'icon'  => 'medkit',
                            'checkrole'   => 'vaccination',
                            'route'   => 'patients.index',
                            'active'=> ['vaccination/patients*'],
                        ],
                    ],
                ],
                [
                    'text'  => 'Danh sách tiêm chủng',
                    'icon'  => 'plus',
                    'checkrole'   => 'vaccination',
                    'route'   => 'vaccination.index',
                    'active'=> ['vaccination/index*'],
                ],
            ],
        ],
        [
            'text'    => 'Bệnh án điện tử',
            'icon'    => 'film',
            'checkrole'   => 'emr-check',
            'submenu' => [
                [
                    'text'  => 'Kiểm tra hồ sơ chi tiết',
                    'icon'  => 'info',
                    'checkrole'   => 'emr-check',
                    'route'   => 'emr-checker.emr-checker-detail',
                    'active'=> ['emr-checker/emr-checker-detail*'],
                ],
                [
                    'text'  => 'Danh sách hồ sơ bệnh án',
                    'icon'  => 'file',
                    'checkrole'   => 'emr-check',
                    'route'   => 'emr-checker.emr-checker-index',
                    'active'=> ['emr-checker/emr-checker-index*'],
                ],
                [
                    'text'  => 'QL hồ sơ chuyển BHXH',
                    'icon'  => 'file',
                    'checkrole'   => 'emr-check',
                    'route'   => 'emr-checker.emr-checker-bhxh-index',
                    'active'=> ['emr-checker/emr-checker-bhxh-index*'],
                ],
                [
                    'text'  => 'Tra soát hồ sơ bệnh án',
                    'icon'  => 'check',
                    //'checkrole'   => 'check-hein-card',
                    'route'   => 'emr.index',
                    'active'=> ['emr/index*'],
                ],
                [
                    'text'  => 'Trả kết quả cho BN',
                    'icon'  => 'address-card',
                    //'checkrole'   => 'check-hein-card',
                    'route'   => 'treatment-result.index',
                    'active'=> ['treatment-result/index*'],
                ],
                [
                    'text'  => 'QRCode Thanh toán',
                    'icon'  => 'address-card',
                    //'checkrole'   => 'check-hein-card',
                    'route'   => 'accountant.broadcast',
                    'active'=> ['accountant/broadcast*'],
                ],
                [
                    'text'    => 'Báo cáo thống kê',
                    'icon'    => 'bar-chart',
                    'checkrole'   => 'thungan',
                    'submenu' => [
                        [
                            'text'  => 'Báo cáo nộp tiền',
                            'icon'  => 'dollar',
                            'checkrole'   => 'thungan',
                            'route'   => 'accountant.payment-report',
                            'active'=> ['accountant/payment-report*'],
                        ],
                    ],
                ],
            ],
        ],
        [
            'text'    => 'Thẻ BHYT',
            'icon'    => 'shield',
            'submenu' => [
                [
                    'text'  => 'Tra cứu thẻ BHYT',
                    'icon'  => 'check',
                    'route'   => 'insurance.check-card',
                    'active'=> ['insurance/check-card*'],
                ],
                [
                    'text'  => 'Tra cứu Thuốc - Thầu',
                    'icon'  => 'list',
                    'route'   => 'insurance.medicine-search',
                    'active'=> ['insurance/medicine-search*'],
                ],
            ],
        ],
        [
            'text'      => 'Kiểm tra sai sót y lệnh',
            'icon'      => 'stethoscope',
            'checkrole' => 'order-check',
            'submenu'   => [
                [
                    'text'      => 'Danh sách vi phạm',
                    'icon'      => 'list',
                    'checkrole' => 'order-check',
                    'route'     => 'khth.order-check-index',
                    'active'    => ['khth/order-check-index*'],
                ],
                [
                    'text'      => 'Danh mục giới hạn DV',
                    'icon'      => 'venus-mars',
                    'checkrole' => 'superadministrator',
                    'route'     => 'khth.order-check-ref-index',
                    'active'    => ['khth/order-check-ref-index*'],
                ],
                [
                    'text'      => 'Quản lý quy tắc kiểm tra',
                    'icon'      => 'sliders',
                    'checkrole' => 'superadministrator',
                    'route'     => 'khth.order-check-rule-index',
                    'active'    => ['khth/order-check-rule-index*'],
                ],
            ],
        ],
        [
            'text'    => 'Hồ sơ XML',
            'icon'    => 'file',
            'checkrole'   => 'xml-man',
            'submenu' => [
                [
                    'text'    => 'Xml 3176',
                    'icon'    => 'archive',
                    'checkrole'   => 'xml-man',
                    'submenu' => [
                        [
                            'text'  => 'Danh sách hồ sơ',
                            'icon'  => 'file',
                            'route'   => 'bhyt.xml3176.index',
                            'active'=> ['bhyt/xml3176/index*'],
                        ],
                        [
                            'text'       => 'Nhập khẩu hồ sơ',
                            'icon'       => 'plus',
                            'route'        => 'bhyt.xml3176.import.index',
                            'active'    => ['bhyt/xml3176/import/index*']
                        ],
                        [
                            // Route dashboard/xml3176 dùng middleware checkrole:xml-man,
                            // trùng quyền với menu cha nên không cần khai checkrole riêng.
                            'text'      => 'Dashboard lỗi XML',
                            'icon'      => 'dashboard',
                            'route'     => 'dashboard.xml3176.index',
                            'active'    => ['dashboard/xml3176*'],
                        ],
                    ],
                ],
                [
                    'text'    => 'Xml 4750',
                    'icon'    => 'archive',
                    'checkrole'   => 'xml-man',
                    'submenu' => [
                        [
                            'text'  => 'Danh sách hồ sơ',
                            'icon'  => 'file',
                            'route'   => 'bhyt.qd130.index',
                            'active'=> ['bhyt/qd130/index*'],
                        ],
                        [
                            'text'       => 'Nhập khẩu hồ sơ',
                            'icon'       => 'plus',
                            'route'        => 'bhyt.qd130.import.index',
                            'active'    => ['bhyt/qd130/import/index*']
                        ],
                    ],
                ],
                [
                    'text'    => 'Xml 4210',
                    'icon'    => 'archive',
                    'checkrole'   => 'xml-man',
                    'submenu' => [
                        [
                            'text'  => 'Danh sách hồ sơ',
                            'icon'  => 'file',
                            'route'   => 'bhyt.index',
                            'active'=> ['bhyt/index*'],
                        ],
                        [
                            'text'       => 'Nhập khẩu hồ sơ',
                            'icon'       => 'plus',
                            'route'        => 'system.upload-xml',
                            'active'    => ['system/upload-xml*']
                        ],
                    ],
                ],
                [
                    'text'    => 'Báo cáo',
                    'icon'    => 'archive',
                    'checkrole'   => 'xml-man',
                    'submenu' => [
                        [
                            'text'  => 'NVYT - Y lệnh',
                            'icon'  => 'file',
                            'route'   => 'bhyt.reports.bac-si-y-lenh',
                            'active'=> ['bhyt/reports/bac-si-y-lenh*'],
                        ],
                    ],
                ],
            ],
        ],
        [
            'text'    => 'Điều dưỡng',
            'icon'    => 'user-md',
            'checkrole'   => 'dieu-duong',
            'submenu' => [
                [
                    'text'  => 'Thực hiện y lệnh',
                    'icon'  => 'user-md',
                    'checkrole'   => 'dieu-duong',
                    'route'   => 'nurse.execute.medication.order.index',
                    'active'=> ['nurse/execute/medication/order/index*'],
                ],
            ],
        ],
        [
            'text'    => 'Quản lý danh mục',
            'icon'    => 'book',
            'checkrole'   => 'category-manager',
            'submenu' => [
                [
                    'text'    => 'HIS',
                    'icon'    => 'book',
                    'submenu' => [
                        [
                            'text'  => 'Tra cứu giá dịch vụ',
                            'icon'  => 'usd',
                            'route' => 'category-his.service-price',
                            'active'=> ['category/his/service-price*'],
                        ],
                    ],
                ],
                [
                    'text'    => 'BHYT',
                    'icon'    => 'book',
                    'submenu' => [
                        [
                            'text'  => 'DM thuốc BHYT',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.medicine-catalog',
                            'active'=> ['category/bhyt/medicine-catalog*'],
                        ],
                        [
                            'text'  => 'DM Vật tư y tế',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.medical-supply-catalog',
                            'active'=> ['category/bhyt/medical-supply-catalog*'],
                        ],
                        [
                            'text'  => 'DM Dịch vụ kỹ thuật',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.service-catalog',
                            'active'=> ['category/bhyt/service-catalog*'],
                        ],
                        [
                            'text'  => 'DM ICD-10',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.icd10-catalog',
                            'active'=> ['category/bhyt/icd10-catalog*'],
                        ],
                        [
                            'text'  => 'DM ICD-YHCT',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.icd-yhct-catalog',
                            'active'=> ['category/bhyt/icd-yhct-catalog*'],
                        ],
                        [
                            'text'  => 'DM Nhân viên y tế',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.medical-staff',
                            'active'=> ['category/bhyt/medical-staff*'],
                        ],
                        [
                            'text'  => 'DM Khoa Phòng Giường',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.department-bed-catalog',
                            'active'=> ['category/bhyt/department-bed-catalog*'],
                        ],
                        [
                            'text'  => 'DM Trang thiết bị',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.equipment-catalog',
                            'active'=> ['category/bhyt/equipment-catalog*'],
                        ],
                        [
                            'text'  => 'DM Đơn vị hành chính',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.administrative-unit',
                            'active'=> ['category/bhyt/administrative-unit*'],
                        ],
                        [
                            'text'  => 'DM Cơ sở KCB',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.medical-organization',
                            'active'=> ['category/bhyt/medical-organization*'],
                        ],
                        [
                            'text'  => 'DM Nghề nghiệp',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.job-category',
                            'active'=> ['category/bhyt/job-category*'],
                        ],
                        [
                            'text'  => 'DM lỗi Xml 4750',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.qd130-xml-error-catalog',
                            'active'=> ['category/bhyt/qd130-xml-error-catalog*'],
                        ],
                        [
                            'text'  => 'DM lỗi Xml 3176',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.xml3176-error-catalog',
                            'active'=> ['category/bhyt/xml3176-error-catalog*'],
                        ],
                    ],
                ],
                
                [
                    'text'  => 'DVKT có điều kiện',
                    'icon'  => 'book',
                    'route'   => 'danh-muc.dvkt-co-dieu-kien',
                    'active'=> ['danh-muc/dvkt-co-dieu-kien*'],
                ],
                [
                    'text'  => 'Thuốc có điều kiện',
                    'icon'  => 'book',
                    'route'   => 'danh-muc.dm-thuoc-co-dieu-kien',
                    'active'=> ['danh-muc/dm-thuoc-co-dieu-kien*'],
                ],
                [
                    'text'  => 'Danh mục Khoa phòng',
                    'icon'  => 'book',
                    'route'   => 'danh-muc.dm-khoa-phong',
                    'active'=> ['danh-muc/dm-khoa-phong*'],
                ],
                [
                    'text'  => 'Nhập khẩu danh mục',
                    'icon'  => 'book',
                    'route'   => 'category-bhyt.import-index',
                    'active'=> ['category/bhyt/category-bhyt-import-index*'],
                ],
            ],
        ],
        [
            'text'    => 'Báo cáo thống kê',
            'icon'    => 'list-alt',
            'submenu' => [
                [
                    'text'  => 'Thống kê dịch vụ kỹ thuật',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'thungan',
                    'route'   => 'khth.dich-vu-ky-thuat-index',
                    'active'=> ['khth/dich-vu-ky-thuat-index*'],
                ],
                [
                    'text'  => 'Báo cáo sử dụng thuốc',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'duoc',
                    'route'   => 'reports-duoc.su-dung-thuoc-index',
                    'active'=> ['reports-duoc/su-dung-thuoc-index*'],
                ],
                [
                    'text'  => 'SL Khám và Chi phí theo PK',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'route'   => 'reports-administrator.clinic-visit-cost-index',
                    'active'=> ['reports-administrator/clinic-visit-cost-index*'],
                ],
                [
                    'text'  => 'SL Loại thuốc theo đơn',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'route'   => 'reports-administrator.number-drug-prescription-index',
                    'active'=> ['reports-administrator/number-drug-prescription-index*'],
                ],
                [
                    'text'  => 'Báo cáo thu tiền (HIS)',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'thungan-tonghop',
                    'route'   => 'reports-administrator.accoutant-payment-index',
                    'active'=> ['reports-administrator/accoutant-payment-index*'],
                ],
                [
                    'text'  => 'Danh sách BN PT',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'qlcl',
                    'route'   => 'reports-administrator.list-patient-pt',
                    'active'=> ['reports-administrator/list-patient-pt*'],
                ],
                [
                    'text'  => 'Danh sách nợ viện phí',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'thungan',
                    'route'   => 'reports-administrator.accoutant-debt-index',
                    'active'=> ['reports-administrator/accoutant-debt-index*'],
                ],
                [
                    'text'  => 'Báo cáo doanh thu',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'thungan',
                    'route'   => 'reports-administrator.accoutant-revenue-index',
                    'active'=> ['reports-administrator/accoutant-revenue-index*'],
                ],
                [
                    'text'  => 'Số lượng BN theo khoa',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'qlcl',
                    'route'   => 'reports-administrator.index-patient-count-by-department',
                    'active'=> ['reports-administrator/index-patient-count-by-department*'],
                ],
                [
                    'text'  => 'Khảo sát TG khám bệnh',
                    'icon'  => 'clock-o',
                    'checkrole'   => 'qlcl',
                    'route'   => 'reports-administrator.khaosat-index',
                    'active'=> ['reports-administrator/khaosat-index*'],
                ],
                [
                    'text'       => 'Tra cứu LS KCB',
                    'icon'       => 'line-chart',
                    'route'   => 'tra-cuu-ls-kcb-index',
                    'active'    => ['tra-cuu-ls-kcb*'],
                ],
                [
                    'text'  => 'Báo cáo thuốc, vtyt tiêu hao',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'route'   => 'reports-administrator.index-thuoc-vtyt-tieu-hao',
                    'active'=> ['reports-administrator/index-thuoc-vtyt-tieu-hao*'],
                ],
                [
                    'text'  => 'Doanh thu dịch vụ chi tiết',
                    'icon'  => 'bar-chart',
                    'checkrole'   => 'administrator',
                    'route'   => 'reports-administrator.sere-serv-revenue-index',
                    'active'=> ['reports-administrator/sere-serv-revenue-index*'],
                ],
            ],
        ],
        [
            'text'       => 'Hồ sơ bệnh án',
            'icon' => 'file',
            'checkrole'   => 'bhxh',
            'submenu' => [
                [
                    'text'       => 'Danh sách',
                    'icon'       => 'file',
                    'checkrole'   => 'bhxh',
                    'route'   => 'bhxh.index',
                    'active'    => ['bhxh/index*'],
                ],
            ],
        ],
        [
            'text'       => 'Thiết lập hệ thống',
            'icon' => 'cog',
            'checkrole'   => 'superadministrator',
            'submenu' => [
                [
                    'text'       => 'Kiểm tra chi tiết',
                    'icon'       => 'rebel',
                    'checkrole'   => 'superadministrator',
                    'route'   => 'system.user-function.index',
                    'active'    => ['system/user-function*'],
                ],
                [
                    'text'       => 'Tham số hệ thống',
                    'icon'       => 'cog',
                    'route'        => 'system.sys-param',
                    'active'    => ['system/sys-param*']
                ],
                [
                    'text' => 'Quản trị hệ thống',
                    'icon' => 'lock',
                    'checkrole' => 'superadministrator',
                    'route'        => 'system.sys-man',
                    'active'    => ['system/sys-man*']
                ],
                [
                    'text' => 'Quyền và Vai trò',
                    'icon' => 'lock',
                    'checkrole' => 'superadministrator',
                    'route'        => 'users.index',
                    'active'    => ['users/index*']
                ],
                [
                    'text' => 'Email nhận báo cáo',
                    'icon' => 'envelope',
                    'checkrole' => 'superadministrator',
                    'route'        => 'email-receive-reports.index',
                    'active'    => ['email-receive-reports*']
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Choose what filters you want to include for rendering the menu.
    | You can add your own filters to this array after you've created them.
    | You can comment out the GateFilter if you don't want to use Laravel's
    | built in Gate functionality
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SubmenuFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        App\Menu\Filters\CheckRoleFilter::class,
        //JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        //MyApp\MyMenuFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Choose which JavaScript plugins should be included. At this moment,
    | only DataTables is supported as a plugin. Set the value to true
    | to include the JavaScript file from a CDN via a script tag.
    |
    */

    'plugins' => [
        'datatables' => true,
        'select2'    => true,
    ],
];
