<?php
// Response Codes & other global configurations
//$techConfig = require app_path('Yantrana/__Laraware/Config/tech-config.php');

$techAppConfig = [
     "gettext_fallback" => true,

    /* Paths
    ------------------------------------------------------------------------- */

    "custom_pages"        => "external-pages/",
    "product_assets"      => "media-storage/products/product-",
    "product_user_assets" => "media-storage/users/user-",
    'day_date_time_format'=> 'l jS F Y  g:ia',
    'account_activation'  => (60*60*48),
    'cart_expiration_time' => 60*60*24*365, // shoppingCart expiration in 1 year

    /* pagination
    ------------------------------------------------------------------------- */

    'pagination_count'  => 15,

    /* default item load type
    ------------------------------------------------------------------------- */
    'loadItemType' => 24, // use scroll

    /* character limit
    ------------------------------------------------------------------------- */

    'character_limit'  => 66,

    /* quantity limit
    ------------------------------------------------------------------------- */

    'qty_limit'  => 99999,

    /* international shipping - All Other Countries
    ------------------------------------------------------------------------- */

    'aoc'  => 'AOC', // treated as Country Code
    'aoc_id'  => 999, // id in countries table 

    /* shop logo name
    ------------------------------------------------------------------------- */

    'logoName'  => 'logo.png',

    /* Email Config
    ------------------------------------------------------------------------- */

    'mail_from'         =>  [ 
        env('MAIL_FROM_ADD', 'support@cme.vn'),
        env('MAIL_FROM_NAME', 'E-Mail Service')
    ],

    /* Account related 
    ------------------------------------------------------------------------- */

    'account' => [
        'activation_expiry'         => 24 * 2, // hours
        'change_email_expiry'       => 24 * 2, // hours
        'password_reminder_expiry'  => 24 * 2, // hours
        'passwordless_login_expiry' => 5, // minutes
    ],

    'login_attempts'    =>  5,

    /* Status Code Multiple Uses
    ------------------------------------------------------------------------- */

    'status_codes' => [
        1 => ('Active'),
        2 => ('Inactive'),
        3 => ('Banned'),
        4 => ('Never Activated'),
        5 => ('Deleted'),
        6 => ('Suspended'),
        7 => ('On Hold'),
        8 => ('Completed'),
        9 => ('Invite')
    ],

    /* Assigned user status codes
    ------------------------------------------------------------------------- */

    'user' => [
        'status_codes' => [ 
            1, // active
            2, // deactive
            3, // banned
            4, // never activated
            5  // deleted
        ]
    ],

    /* Order Status
    ------------------------------------------------------------------------- */

    'orders' => [
        'type' => [ 
            1 => ('Đặt hàng qua Email'),
            2 => ('PayPal/Stripe')
        ],
        'payment_methods' => [ 
            1 => ('PayPal'), // PayPal IPN Payments
            2 => ('Check'),
            3 => ('Bank Transfer'),
            4 => ('COD'),    
            5 => ('Other'),
            6 => ('Stripe'),
            7 => ('PayPal Sandbox'), 
            8 => ('Stripe Test Mode') 
        ],
        'payment_status' => [ 
            1 => ('Đang chờ thanh toán'), // PayPal IPN Payments
            2 => ('Đã hoàn thành'),
            3 => ('Thanh toán không thành'),
            4 => ('Đang chờ giải quyết'),
            5 => ('Hoàn lại tiền')
        ],
        'payment_type' => [ 
            1 => ('Deposit'),
            2 => ('Refund')
        ],
        'products' => [ 
            1 => ('Ordered'),
            2 => ('Confirmed & Available'),
            3 => ('Cancelled'),
            4 => ('Not Available'),
            5 => ('Not Shippable')
        ],
        'status_codes' => [ 
            1 => ('Mới'),
            2 => ('Đang xử lý'),
            3 => ('Đã hủy'),
            4 => ('Giữ lại'),
            5 => ('Đang chuyển'),
            6 => ('Đã hoàn thành'),
            7 => ('Đã xác nhận'),
            // 8 => ('Cancellation Request Received'),
            // 9 => ('User Cancelled'),
            //10 => ('Invalid'),
            11 => ('Đã giao hàng')
        ],
        'date_filter_code' => [ 
            1 => ('Placed'),
            2 => ('Updated')
        ],
    ],


    /* Manage Pages related
    --------------------------------------------------------------------------*/

    'pages_status_codes' => [
        1 => ('Yes'),
        2 => ('No')
    ],

    'pages_types' => [
        1 => ('Page'),
        2 => ('Link')
    ],

    'pages_types_with_system_link' => [
        1 => ('Page'),
        2 => ('Link'),
        3 => ('System Link')
    ],


    /* Reserve page id
    ------------------------------------------------------------------------- */
    'reserve_pages_ids'    =>  [/*1,*/ 2, 3, 4, 5, 6],
    'reserve_pages'        =>  [/*1,*/ 2, 3, 4, 5, 6],

    'system_links'  => [
        //'home'         => 1,
        'categories' => 2,
        'brand'      => 3,
        'login'      => 4,
        'register'   => 5,
        'contact'    => 6
    ],

    'pages_type_codes' => [1,2,3],

    'link_target' => [
        '_blank'  => ('_blank'),
        '_self'   => ('_self') ,
        '_parent' => ('_parent')
    ],

    'link_target_array' => ['_blank','_self','_parent'],

    /* Manage categories related
    --------------------------------------------------------------------------*/

    'categories_status_codes' => [
        1 => ('Active'),
        2 => ('Deactive')
    ],


    /* Store Related Config Values
    --------------------------------------------------------------------------*/

    'currencies'         => [

        /* Zero-decimal currencies
        ----------------------------------------------------------------------*/
        'zero_decimal'  => [
            'BIF' =>  'Burundian Franc',
            'CLP' =>  'Chilean Peso',
            'DJF' =>  'Djiboutian Franc',
            'GNF' =>  'Guinean Franc',
            'JPY' =>  'Japanese Yen',
            'KMF' =>  'Comorian Franc',
            'KRW' =>  'South Korean Won',
            'MGA' =>  'Malagasy Ariary',
            'PYG' =>  'Paraguayan Guaraní',
            'RWF' =>  'Rwandan Franc',
            'VND' =>  'Vietnamese Đồng',
            'VUV' =>  'Vanuatu Vatu',
            'XAF:' => 'Central African Cfa Franc',
            'XOF' =>  'West African Cfa Franc',
            'XPF' =>  'Cfp Franc',
            // Paypal zero-decimal currencies
            'HUF' =>  'Hungarian Forint',
            'TWD' =>  'New Taiwan Dollar',
        ], 
        
        'options'   => [
            'AUD' => ('Australian Dollar'),
            'CAD' => ('Canadian Dollar'),
            'EUR' => ('Euro'),
            'GBP' => ('British Pound'),
            'USD' => ('U.S. Dollar'),
            'NZD' => ('New Zealand Dollar'),
            'CHF' => ('Swiss Franc'),
            'HKD' => ('Hong Kong Dollar'),
            'SGD' => ('Singapore Dollar'),
            'SEK' => ('Swedish Krona'),
            'DKK' => ('Danish Krone'),
            'PLN' => ('Polish Zloty'),
            'NOK' => ('Norwegian Krone'),
            'HUF' => ('Hungarian Forint'),
            'CZK' => ('Czech Koruna'),
            'ILS' => ('Israeli New Shekel'),
            'MXN' => ('Mexican Peso'),
            'BRL' => ('Brazilian Real (only for Brazilian members)'),
            'MYR' => ('Malaysian Ringgit (only for Malaysian members)'),
            'PHP' => ('Philippine Peso'),
            'TWD' => ('New Taiwan Dollar'),
            'THB' => ('Thai Baht'),
            'TRY' => ('Turkish Lira (only for Turkish members)'),
            ''    => ('Other')
        ],
        'details'    => [

            'AUD' => [
                'name'   => ("Australian Dollar"), 
                'symbol' => "A$", 
                'ASCII'  => "A&#36;"
            ],
                 
            'CAD' => [
                'name'   => ("Canadian Dollar"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ],

            'CZK' => [
                'name'   => ("Czech Koruna"), 
                'symbol' => "Kč", 
                'ASCII'  => "K&#x10d;"
            ],

            'DKK' => [
                'name'   => ("Danish Krone"), 
                'symbol' => "Kr", 
                'ASCII'  => "K&#x72;"
            ],

            'EUR' => [
                'name'   => ("Euro"), 
                'symbol' => "€", 
                'ASCII'  => "&euro;"
             ],

            'HKD' => [
                'name'   => ("Hong Kong Dollar"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ],

            'HUF' => [
                'name'   => ("Hungarian Forint"), 
                'symbol' => "Ft", 
                'ASCII'  => "F&#x74;"
            ],

            'ILS' => [
                'name'   => ("Israeli New Sheqel"), 
                'symbol' => "₪", 
                'ASCII'  => "&#8361;"
            ],

            'JPY' => [
                'name'   => ("Japanese Yen"), 
                'symbol' => "¥", 
                'ASCII'  => "&#165;"
            ],

            'MXN' => [
                'name'   => ("Mexican Peso"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ],

            'NOK' => [
                'name'   => ("Norwegian Krone"), 
                'symbol' => "Kr", 
                'ASCII'  => "K&#x72;"
            ],

            'NZD' => [
                'name'   => ("New Zealand Dollar"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ],

            'PHP' => [
                'name'   => ("Philippine Peso"), 
                'symbol' => "₱", 
                'ASCII'  => "&#8369;"
            ],

            'PLN' => [
                'name'   => ("Polish Zloty"), 
                'symbol' => "zł", 
                'ASCII'  => "z&#x142;"
            ],

            'GBP' => [
                'name'   => ("Pound Sterling"), 
                'symbol' => "£", 
                'ASCII'  => "&#163;"
            ],

            'SGD' => [
                'name'   => ("Singapore Dollar"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ],

            'SEK' => [
                'name'   => ("Swedish Krona"), 
                'symbol' => "kr", 
                'ASCII'  => "K&#x72;"
            ],

            'CHF' => [
                'name'   => ("Swiss Franc"), 
                'symbol' => "CHF", 
                'ASCII'  => "&#x43;&#x48;&#x46;"
            ],

            'TWD' => [
                'name'   => ("Taiwan New Dollar"), 
                'symbol' => "NT$", 
                'ASCII'  => "NT&#36;"
            ],

            'THB' => [
                'name'   => ("Thai Baht"), 
                'symbol' => "฿", 
                'ASCII'  => "&#3647;"
            ],

            'USD' => [
                'name'   => ("U.S. Dollar"), 
                'symbol' => "$", 
                'ASCII'  => "&#36;"
            ]
        ],
    ],

    'menu_placement' =>  [
        [
            'value'    => 1,
            'name'  => ('Sidebar')
        ],
        [
            'value'    => 2,
            'name'  => ('Top Menu')
        ],
        [
            'value'    => 3,
            'name'  => ('Both')
        ],
        [
            'value'    => 4,
            'name'  => ('Dont Show')
        ]
    ],

    'settings' => [

        /* Configuration setting data-types id
        ------------------------------------------------------------------------- */
        'datatypes'  => [
            'string' => 1,
            'bool'   => 2,
            'int'    => 3,
            'json'   => 4
        ],
        'fields' => [
            // General Tab
            'store_name' => [
                'key'           => 'store_name',
                'data_type'     => 1,    // string
                'default'       => 'You Website Name'
            ],
            'logo_image' => [
                'key'           => 'logo_image',
                'data_type'     => 1,    // string
                'default'       => 'logo.png'
            ],
            'logo_background_color' => [
                'key'           => 'logo_background_color',
                'data_type'     => 1,    // string
                'default'       => '383838' // dark grey
            ],
            'business_email' => [
                'key'           => 'business_email',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'your-email-address@example.com'
            ],
            'home_page' => [
                'key'           => 'home_page',
                'data_type'     => 3,    // integer
                'default'       => 1    // home page settings
            ],
            'timezone' => [
                'key'           => 'timezone',
                'data_type'     => 1,    // string
                'default'       => 'UTC'
            ],
            // Currency settings
            'currency'              => [
                'key'           => 'currency',
                'data_type'     => 1,    // string
                'default'       => 'USD'
            ],
            'currency_symbol'       => [
                'key'           => 'currency_symbol',
                'data_type'     => 1,    // string
                'default'       => '&#36;'
            ],
            'currency_value'        => [
                'key'           => 'currency_value',
                'data_type'     => 1,    // string
                'default'       => 'USD'
            ],
            'currency_decimal_round' => [
                'key'           => 'currency_decimal_round',
                'data_type'     => 3, // int
                'default'       => 2
            ],
            'round_zero_decimal_currency' => [
                'key'           => 'round_zero_decimal_currency',
                'data_type'     => 2, // boolean
                'default'       => true // round
            ],
            'currency_format'   => [
                'key'           => 'currency_format',
                'data_type'     => 1,    // string
                'default'       => '{__currencySymbol__}{__amount__} {__currencyCode__}'
            ],
            'payment_other'        => [
                'key'           => 'payment_other',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'payment_other_text'        => [
                'key'           => 'payment_other_text',
                'data_type'     => 1,    // string
                'default'       => 'Add here other payment related information'
            ],
            'hide_sidebar_on_order_page' => [
                'key'           => 'hide_sidebar_on_order_page',
                'data_type'     => 2,    // boolean
                'default'       => true
            ],
            // Payment method
            'use_paypal'        => [
                'key'           => 'use_paypal',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'paypal_email'        => [
                'key'           => 'paypal_email',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'your-paypal-email-address@example.com'
            ],
            'paypal_sandbox_email'          => [
                'key'           => 'paypal_sandbox_email',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'your-sandbox-email-address@example.com'
            ],
            'use_stripe'            => [
                'key'           => 'use_stripe',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'stripe_live_secret_key'          => [
                'key'           => 'stripe_live_secret_key',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Live secret key'
            ],
            'stripe_live_publishable_key'          => [
                'key'           => 'stripe_live_publishable_key',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Live Publishable Key'
            ],
            'stripe_testing_secret_key'          => [
                'key'           => 'stripe_testing_secret_key',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Test Secret Key'
            ],
            'stripe_testing_publishable_key'          => [
                'key'           => 'stripe_testing_publishable_key',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Test Publishable key'
            ],
            'payment_check'        => [
                'key'           => 'payment_check',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'payment_check_text'        => [
                'key'           => 'payment_check_text',
                'data_type'     => 1,    // string
                'default'       => 'Add here check related information.'
            ],
            'payment_bank'        => [
                'key'           => 'payment_bank',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'payment_bank_text'        => [
                'key'           => 'payment_bank_text',
                'data_type'     => 1,    // string
                'default'       => 'Add here bank related information'
            ],
            'payment_cod'        => [
                'key'           => 'payment_cod',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'payment_cod_text'        => [
                'key'           => 'payment_cod_text',
                'data_type'     => 1,    // string
                'default'       => 'Nhận được hàng mới phải trả tiền.'
            ],
            'show_out_of_stock'        => [
                'key'           => 'show_out_of_stock',
                'data_type'     => 2,    // boolean
                'default'       => true
            ],
            'pagination_count'        => [
                'key'           => 'pagination_count',
                'data_type'     => 3,    // int
                'default'       => 8
            ],
            'item_load_type'        => [
                'key'           => 'item_load_type',
                'data_type'     => 3,    // int
                'default'       => 2
            ],
            'categories_menu_placement'        => [
                'key'           => 'categories_menu_placement',
                'data_type'     => 3,    // int
                'default'       => 3
            ],
            'brand_menu_placement'        => [
                'key'           => 'brand_menu_placement',
                'data_type'     => 3,    // int
                'default'       => 3
            ],
            'credit_info'       => [
                'key'           => 'credit_info',
                'data_type'     => 2,    // bool
                'default'       => true
            ],
            'addtional_page_end_content'        => [
                'key'           => 'addtional_page_end_content',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'footer_text'        => [
                'key'           => 'footer_text',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'show_language_menu' => [
                'key'           => 'show_language_menu',
                'data_type'     => 2,    // boolean
                'default'       => false
            ],
            'contact_email'        => [
                'key'           => 'contact_email',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'your-email-address@example.com'
            ],
            'contact_address'        => [
                'key'           => 'contact_address',
                'data_type'     => 1,    // string
                'default'       => 'add your contact address'
            ],
            'activation_required_for_new_user'        => [
                'key'           => 'activation_required_for_new_user',
                'data_type'     => 2,    // bool
                'default'       => true
            ],
            'show_captcha' => [
                'key'           => 'show_captcha',
                'data_type'     => 3,       // integer
                'default'       => 5
            ],
            'activation_required_for_change_email'        => [
                'key'           => 'activation_required_for_change_email',
                'data_type'     => 2,    // boolean
                'default'       => true
            ],
            'term_condition'        => [
                'key'           => 'term_condition',
                'data_type'     => 1,    // string
                'default'       => 'Add terms & conditions'
            ],
            'facebook_client_id'    => [
                'key'           => 'facebook_client_id',              
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'facebook_client_secret' => [
                'key'           => 'facebook_client_secret',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'google_client_id'      => [
                'key'           => 'google_client_id',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'google_client_secret'  => [
                'key'           => 'google_client_secret',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'allow_facebook_login'  => [
                'key'           => 'allow_facebook_login',
                'data_type'     => 2,     // boolean
                'default'       => false
            ],
            'allow_google_login' => [
                'key'           => 'allow_google_login',
                'data_type'     => 2,     // boolean
                'default'       => false
            ],// Privacy Policy
            'privacy_policy'        => [
                'key'           => 'privacy_policy',
                'data_type'     => 1,    // string
                'default'       => 'Add Privacy Policy'
            ],
            // Social account configuration
            'social_facebook'   => [
                'key'           => 'social_facebook',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Your Social Facebook Id'
            ],
            'social_twitter' => [
                'key'           => 'social_twitter',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => 'Your Social Twitter Id'
            ],
            'custom_css' => [
                'key'           => 'custom_css',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            // Notification
            'global_notification'         => [
                'key'           => 'global_notification',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'append_email_message' => [
                'key'           => 'append_email_message',
                'data_type'     => 1,    // string
                'default'       => ''
            ],
            'apply_tax_after_before_discount' => [
                'key'           => 'apply_tax_after_before_discount',
                'data_type'     => 3,    // int
                'default'       => 1
            ],
            'calculate_tax_as_per_shipping_billing' => [
                'key'           => 'calculate_tax_as_per_shipping_billing',
                'data_type'     => 3,    // int
                'default'       => 1
            ]
        ],
    ],

    'address_type' => [
        1 => ('Nhà riêng'),
        2 => ('Cơ quan'),
        3 => ('Địa chỉ khác')
    ],

    /*
    ------------------------------------------------------------------------- */
    'home_page_setting' => [
        1 => ('Home page'),
        2 => ('Tất cả sản phẩm'),
        3 => ('Sản phẩm nổi bật'),
        4 => ('Thương hiệu'),
        5 => ('Quầy thuốc')
    ],

    'address_type_list' =>  [
        [
            'id'    => 1,
            'name'  => ('Nhà riêng')
        ],
        [
            'id'    => 2,
            'name'  => ('Cơ quan')
        ],
        [
            'id'    => 3,
            'name'  => ('Địa chỉ khác')
        ]
    ],

    'payment_methods_list' =>  [
        [
            'id'    => 1,
            'name'  => ('PayPal')
        ],
        [
            'id'    => 2,
            'name'  => ('Check')
        ],
        [
            'id'    => 3,
            'name'  => ('Bank Transfer')
        ],
        [
            'id'    => 4,
            'name'  => ('COD')
        ],
        [
            'id'    => 5,
            'name'  =>  ('Other')
        ],
        [
            'id'    => 6,
            'name'  =>  ('Stripe')
        ],
        [
            'id'    => 7,
            'name'  =>  ('PayPal Sandbox')
        ],
        [
            'id'    => 8,
            'name'  =>  ('Stripe Test Mode')
        ]
    ],

    // Brand Status
    'brand_status' => [
        1 => ('Active'),
        2 => ('Deactive')
    ],

    // Coupon Discount Type
    'coupon_type' => [
        1 => ('Số tiền'),
        2 => ('Phần trăm')
    ],

    'coupon_discount_type' =>  [
        [
            'id'    => 1,
            'name'  => ('Số tiền')
        ],
        [
            'id'    => 2,
            'name'  => ('Phần trăm')
        ]
    ],

    /* Shipping 
    ------------------------------------------------------------------------- */
    'shipping' => [
        'type' => [
        
            [
                'id'    => 1,
                'name'  => ('Flat')
            ],
            [
                'id'    => 2,
                'name'  => ('Percentage')
            ],
            [
                'id'    => 3,
                'name'  => ('Free')
            ],
            [
                'id'    => 4,
                'name'  => ('Not Shippable')
            ]
        ],
        'typeShow' => [
            1 => ('Flat'),
            2 => ('Percentage'),
            3 => ('Free'),
            4 => ('Not Shippable')
        ],
        'status' => [
            1 => ('Active'),
            2 => ('Deactive')
        ]
    ],

    /* Tax 
    ------------------------------------------------------------------------- */
    'tax' => [
        'type' => [
            1 => ('Flat'),
            2 => ('Percentage'),
            3 => ('No Tax')
        ],
        'status' => [
            1 => ('Active'),
            2 => ('Deactive')
        ]
    ],

    /* Report duration 
    ------------------------------------------------------------------------- */
    'report_duration' => [
            1 => ('Tháng này'),
            2 => ('Tháng trước'),
            3 => ('Tuần này'),
            4 => ('Tuần trước'),
            5 => ('Hôm nay'),
            6 => ('Hôm qua'),
            7 => ('Năm trước'), 
            8 => ('Năm nay'), 
            9 => ('30 ngày trước'), 
            10 => ('Tùy chọn')
    ],

    'gender' => [
        0 => 'Nữ',
        1 => 'Nam',
    ],

    'gioi_tinh' => [
        1 => 'Nam',
        2 => 'Nữ',
    ],

    'healthcaretime' => [
        1 => 'Buổi sáng',
        2 => 'Buổi chiều',
        3 => 'Ngoài giờ',
    ],

    'card-number' => [
        'min' => 10,
        'max' => 15,
    ],

    'ket_qua_dtri' => [
        1 => 'Khỏi',
        2 => 'Đỡ',
        3 => 'Không thay đổi',
        4 => 'Nặng hơn',
        5 => 'Tử vong',
        6 => 'N/A'
    ],

    'tinh_trang_rv' => [
        1 => 'Ra viện',
        2 => 'Chuyển viện',
        3 => 'Trốn viện',
        4 => 'Xin ra viện',
        5 => 'Chuyển viện theo YC',
    ],

    'tinh_trang_rv_nt' => [
        1 => 'Ra viện',
        2 => 'Xin về',
        3 => 'Bỏ về',
        4 => 'Đưa về',
        5 => 'Chuyển khoa',
        6 => 'Chuyển viện',
    ],

    'insurance_error_code' => [
        '000' => 'Thông tin thẻ BHYT chính xác',
        '001' => 'Thẻ BHYT do BHXH BQP quản lý, đề nghị kiểm tra thẻ BHYT và thông tin giấy tờ tuỳ thân',
        '002' => 'Thẻ BHYT do BHXH BCA quản lý, đề nghị kiểm tra thẻ BHYT và thông tin giấy tờ tuỳ thân',
        '003' => 'Thẻ BHYT cũ hết giá trị sử dụng, được cấp thẻ mới',
        '004' => 'Thẻ BHYT cũ còn giá trị sử dụng, được cấp thẻ mới',
        '010' => 'Thẻ BHYT hết giá trị sử dụng',
        '051' => 'Mã thẻ BHYT không đúng',
        '052' => 'Mã tỉnh cấp thẻ BHYT (ký tự thứ 4,5) không đúng',
        '053' => 'Mã quyền lợi BHYT (ký tự thứ 3) không đúng',
        '050' => 'Không có thông tin thẻ BHYT',
        '060' => 'Thẻ BHYT sai họ tên',
        '061' => 'Thẻ BHYT sai họ tên (đúng ký tự đầu)',
        '070' => 'Thẻ BHYT sai ngày sinh',
        '100' => 'Lỗi khi lấy dữ liệu sổ thẻ',
        '101' => 'Lỗi server',
        '110' => 'Thẻ BHYT đã thu hồi',
        '120' => 'Thẻ BHYT đã báo giảm',
        '121' => 'Thẻ BHYT đã báo giảm chuyển ngoại tỉnh',
        '122' => 'Thẻ BHYT đã báo giảm chuyển nội tỉnh',
        '123' => 'Thẻ BHYT đã báo giảm do tăng lại cùng đơn vị',
        '124' => 'Thẻ BHYT đã báo giảm ngừng tham gia',
        '130' => 'Trẻ em không xuất trình thẻ BHYT',
        '205' => 'Lỗi sai định dạng tham số',
        '401' => 'Lỗi xác thực tài khoản',
        '500' => 'Lỗi máy chủ',
        '054' => 'Số CCCD của cán bộ thực hiện tra cứu không tồn tại trong danh sách người sử dụng do CSKCB đăng ký',
        '055' => 'Họ và tên của cán bộ thực hiện tra cứu không khớp với số CCCD',
    ],

    'check_insurance_code' => [
        '00' => 'Thông tin thẻ chính xác',
        '01' => 'Thẻ hết giá trị sử dụng',
        '02' => 'KCB khi chưa đến hạn',
        '03' => 'Hết hạn thẻ khi chưa ra viện',
        '04' => 'Thẻ có giá trị khi đang nằm viện',
        '05' => 'Thẻ không có trong CSDL',
        '06' => 'Thẻ sai họ tên',
        '07' => 'Thẻ sai ngày sinh',
        '08' => 'Thẻ sai giới tính',
        '09' => 'Thông tin thẻ không chính xác',
        '10' => 'Lỗi khi lấy dữ liệu sổ thẻ',
        '401' => 'Lỗi xác thực tài khoản',
        '11' => 'Thiếu dữ liệu đầu vào',
    ],

    'login_error_BHYT' => [
        '401' => 'Lỗi xác thực tài khoản BHYT',
        '500' => 'Lỗi máy chủ BHYT',
    ],

    'order_type' => [
        'DESC' => 'Giảm dần',
        'ASC' => 'Tăng dần',
    ],

    'isurance_card' => [
        'order_by' => [
            'created_at' => 'Ngày tạo',
            'maThe' => 'Số thẻ',
            'hoTen' => 'Họ tên', 
        ],
    ],
    
    'huybl' => [
        '0' => 'Lưu hành',
        '1' => 'Đã huỷ',
    ],

    'trangthai_noitru' => [
        '1' => 'Đang điều trị',
        '2' => 'Đã xuất khoa',
    ],

    'duoc_act' => [
        '1' => 'act 1',
        '2' => 'act 2',
        '3' => 'Thuốc',
        '4' => 'Đông Y',
        '5' => 'Vật tư tiêu hao',
    ],
    
    'duoc_doituong' => [
        '1' => 'BHYT',
        '2' => 'Thu phí',
        '3' => 'Miễn',
        '6' => 'Trẻ dưới 6 tuổi',
        '7' => 'BHYT Trái tuyến',
    ],

    'loai_hoso' => [
        1 => 'Ngoại trú',
        2 => 'Nội trú',
    ],
    'ket_qua_pk' => [
        1 => 'Cấp đơn cho về',
        2 => 'Điều trị ngoại trú',
        3 => 'Hẹn',
        4 => 'Vào viện',
        5 => 'Chuyển viện',
        6 => 'Tử vong',
        7 => 'Khác',
        8 => 'Chuyển khám',
        11 => 'Đã tiêm văcxin',
        12 => 'Chưa tiêm văcxin',
        10 => 'Hẹn tái khám',
    ],

    'ly_do_vvien' => [
        1 => 'Đúng tuyến',
        2 => 'Cấp cứu',
        3 => 'Trái tuyến',
        4 => 'Thông tuyến',
    ],

    'loai_kcb' => [
        1 => 'Khám bệnh',
        2 => 'Điều trị ngoại trú',
        3 => 'Điều trị nội trú',
        9 => 'Điều trị nội trú < 4h',
    ],

    'pl6_4210' => [
        1 => 'XN',
        2 => 'CDHA',
        3 => 'TDCN',
        4 => 'T_TDM',
        5 => 'T_NDM',
        6 => 'T_TL',
        7 => 'MAU',
        8 => 'PT',
        9 => 'DVKT_TL',
        10 => 'VTYT_TDM',
        11 => 'VTYT_NDM',
        12 => 'VC',
        13 => 'KHAM',
        14 => 'G_DTBN',
        15 => 'G_DTNT',
        16 => 'G_LUU',
        17 => 'CPM',
        18 => 'TT',

    ],

    'loi_vi_pham_xml' => [
        1 => 'Tra cứu và kiểm tra thẻ BHYT',
        2 => 'Không có mã thuốc/tên thuốc',
        3 => 'Không có mã DVKT/VTYT',
        4 => 'Không có TT_THAU thuốc',
        5 => 'Sai định dạng TT_THAU VTYT',
        6 => 'T.gian YL > t.gian ra viện',
        7 => 'T.gian trả KQ > Ngày ra hoặc < Ngày vào',
        8 => 'Thuốc không có số đăng ký',
        9 => 'Không có chứng chỉ hành nghề',
        10 => 'PTTT nhiều hơn 1 lần trong ngày',
        11 => 'Không T.toán ngày giường với hình thức khám bệnh/điều trị ngoại trú',
        12 => 'Ngày giường sai quy định (Ngoài các TH đặc biệt)',
        13 => 'Sai định dạng cân nặng',
        14 => 'Diễn biến bệnh không được để trống',
        15 => 'Sai định dạng TT_THAU thuốc',
        16 => 'Sai trần thanh toán VTYT',
        17 => 'Ngày KQ < Ngày y lệnh hoặc > Ngày ra',
        18 => 'Sai lý do vào viện',
        19 => 'Ngày giường > 1 trên 1 y lệnh',
        20 => 'Khám bệnh dài ngày',
    ],

    'treatment_end_type' => [
        null => 'Chưa kết thúc điều trị',
        1 => 'Tử vong',
        2 => 'Chuyển viện',
        3 => 'Hẹn khám lại',
        4 => 'Cấp toa cho về',
        5 => 'Khác',
        6 => 'Ra viện',
        7 => 'Trốn viện',
        8 => 'Xin ra viện',
        9 => 'NA',
    ],

    'patient_type' => [
        1 => 'BHYT',
        42 => 'Viện phí',
        43 => 'KSK',
        62 => 'Hợp đồng',
        82 => 'Yêu cầu',
        102 => 'Vacxin',
        122 => 'Covid',
        142 => 'Hợp đồng (CLS)',
        null => 'Chưa xác định'
    ],

    'vaccin_status' => [
        0 => 'Chưa chốt số liệu',
        1 => 'Đã chốt số liệu'
    ],

    'treatment_type_kham' => 1,
    'treatment_type_ngoaitru' => 2,
    'treatment_type_noitru' => 3,
    'treatment_end_type_cv' => 2,
    'treatment_end_type_ctcv' => 4,
    'tdl_service_req_type_id_dvkt' => '13,2,3,4,5,8,9,10,1,12,11',
    'service_req_type_kham' => 1,
    'service_req_type_xn' => 2,
    'service_req_type_cdha' => 3,
    'service_req_type_sa' => 10,
    'service_req_type_phauthuat' => 10,
    'service_req_type_thuthuat' => 4,
    'number_in_chart' => 30,
    'tdl_service_req_type_id_dvkt_ko_kham' => '13,2,3,4,5,9,10',
    'number_top_request_dvkt' => 10,
    'patient_type_ko_tinh_tien_dvkt' => '43,62',
    'phong_tai_kham' => '1102,285,1249,842,1863,1862',
    'treatment_end_type_cv_ad' => '2,9',
    'service_req_type_cls' => '2,3,5,8,9',
    
    'creator' => array('quynhnt -kkb'),
    'doctor' => array('anhvt -kkb'),
	
	//Các option liên quan đến QD130 Xml
    'export_qd130_enabled' => true,

];

//return array_merge( $techConfig, $techAppConfig );
return $techAppConfig;