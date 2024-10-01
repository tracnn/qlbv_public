<?php

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

    'title' => 'QLBV',

    'title_prefix' => '',

    'title_postfix' => '',

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

    'logo' => '<img src="/images/logo.png" alt="QLBV" style="height: 50px;">',

    'logo_mini' => '<img src="/images/logo.png" alt="QT" style="height: 50px;">',

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
            'text'    => 'Hồ sơ XML',
            'icon'    => 'file',
            'checkrole'   => 'administrator',
            'submenu' => [
                [
                    'text'    => 'Xml 4750',
                    'icon'    => 'archive',
                    'checkrole'   => 'administrator',
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
                    'checkrole'   => 'administrator',
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
            'checkrole'   => 'administrator',
            'submenu' => [
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
                    'checkrole'   => 'thungan-tckt',
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
            ],
        ],
        [
            'text'       => 'Thiết lập hệ thống',
            'icon' => 'cog',
            'checkrole'   => 'administrator',
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
                    'checkrole' => 'administrator',
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
