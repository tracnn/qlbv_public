<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medical Register Language Lines
    |--------------------------------------------------------------------------
    */

    'labels' => [
        'total-records' => 'Tổng số bản ghi:',
        'please-choose' => 'Xin chọn...',
    	'title'	=> 'Hệ thống quản trị',
    	'main-info'	=> 'Thông tin chính',
    	'require' => '(*bắt buộc)',
    	'name'	=> 'Họ và tên',
    	'sexual' => 'Giới tính',
    	'gender' => [
    		'man' => 'Nam',
    		'woman' => 'Nữ',
    	],
    	'city' => 'Tỉnh thành',
    	'district' => 'Quận huyện',
    	'ward' => 'Phường xã',
    	'email' => 'Địa chỉ email',
    	'tel' => 'Số điện thoại',
    	'birthday' => 'Ngày sinh (<= ngày hiện tại)',
    	'healthcaredate' => 'Ngày khám (> ngày hiện tại)',
    	'healthcaretime' => 'Giờ khám',
    	'clinic' => 'Chuyên khoa',
    	'reset' => 'Làm lại',
    	'submit' => 'Đăng ký',
        'symptom' => 'Triệu chứng',
        'symptom-title' => 'Mô tả triệu chứng',
        'our-info' => [
            'address' => 'Địa chỉ của chúng tôi',
            'contact' => 'Liên lạc',
            'email' => 'Email',
            'email-address' => 'admin@benhviendakhoanongnghiep.vn',
            'website' => 'Website',
            'website-address' => 'www.benhviendakhoanongnghiep.vn',
            'basis1' => 'Cơ sở 1',
            'basis-address1' => 'Km13+500 Quốc lộ 1A - Ngọc Hồi – Thanh Trì – HN',
            'basis2' => 'Cơ sở 2',
            'basis-address2' => 'Số 16 Ngõ 183 - Đặng Tiến Đông - Đống Đa – HN',
            'hotline' => 'Đường dây nóng',
            'hotline-number' => '024 3861 5320',
        ],
        'session-time' => [
            'morning' => 'Buổi sáng',
            'afternoon' => 'Buổi chiều',
            'overtime' => 'Ngoài giờ',
        ],
        'our-map' => [
            'static-map' => 'https://www.google.com/maps/place/B%E1%BB%87nh+vi%E1%BB%87n+%C4%90a+khoa+N%C3%B4ng+Nghi%E1%BB%87p/@20.921112,105.8510131,17z/data=!3m1!4b1!4m5!3m4!1s0x3135adf343acb4ab:0xedcc73458154d625!8m2!3d20.921112!4d105.8532018',
            'embed-map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3111.2973344406805!2d105.85139341703002!3d20.9208287946751!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135adf343acb4ab%3A0xedcc73458154d625!2zQuG7h25oIHZp4buHbiDEkGEga2hvYSBOw7RuZyBOZ2hp4buHcA!5e0!3m2!1svi!2s!4v1535015989387',
        ],
    ],

    'success' => 'Đăng ký khám thành công!',
    'failed' => 'Có lỗi trong quá trình đăng ký!',

    'exceptions' => [
        'healthcaredate' => [
            'required' => 'Ngày khám không được để trống',
            'after' => 'Ngày khám phải lớn hơn ngày hiện tại',
        ],
        'birthday' => [
            'required' => 'Ngày sinh không được để trống',
            'before' => 'Ngày sinh phải nhỏ hơn ngày hiện tại',
        ],
    ],

    /*
    * Language for backend
    **/
    'backend' => [
        'all' => 'Tất cả',
        'name' => 'Họ và tên',
        'email' => 'Email',
        'phone' => 'Điện thoại',
        'gender' => 'Giới tính',
        'birthday' => 'Ngày sinh',
        'city' => 'Tỉnh thành',
        'district' => 'Quận huyện',
        'ward' => 'Phường xã',
        'healthcaredate' => 'Ngày đăng ký',
        'healthcaretime' => 'Giờ khám',
        'clinic' => 'Chuyên khoa',
        'symptom' => 'Triệu chứng',
        'created' => 'Ngày tạo',
        'search' => [
            'block_title' => 'Tìm kiếm',
        ],
        'confirm' => 'Bạn có chắc chắn không?',
        'category' => [
            'code' => 'Mã',
            'name' => 'Tên',
            'created' => 'Ngày tạo',
        ],
        'info_check' => 'Kiểm tra thông tin',
    ],

];
