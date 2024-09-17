<?php

return [
    'emr-check-bangke-signer' => [
        'check' => '<div style="display: inline-block;"><h4>Bảng kê thanh toán: Chữ ký của bệnh nhân:</h4></div>',
        'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Bảng kê chưa có chữ ký của bệnh nhân</label></div>',
        'success' => '<div style="display: inline-block;"><label class="alert alert-success">Bảng kê đã có chữ ký của bệnh nhân</label></div>',
    ],
    'emr-check-bangke' => [
        'check' => '<div style="display: inline-block;"><h4>Bảng kê thanh toán:</h4></div>',
        'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo bảng kê</label></div>',
        'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo bảng kê</label></div>',
    ],
    'emr-check-accountant' => [
        'check' => '<div style="display: inline-block;"><h4>Viện phí:</h4></div>',
        'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Còn nợ viện phí: <strong>{amount} VNĐ</strong></label></div>',
        'success' => '<div style="display: inline-block;"><label class="alert alert-success">Không nợ viện phí</label></div>',
    ],
    'emr-check-general-info' => [
        'check' => '<h4>Thông tin chung:</h4>',
        'vo-benh-an-hanh-chinh' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo vỏ hành chính</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo vỏ hành chính</label></div>',
        ],
        'vo-benh-an-hanh-chinh-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Vỏ hành chính chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Vỏ hành chính đã đủ người ký</label></div>',
        ],
        'vo-benh-an-hoi-benh' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo vỏ hỏi bệnh</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo vỏ hỏi bệnh</label></div>',
        ],
        'vo-benh-an-hoi-benh-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Vỏ hỏi bệnh chưa ký đủ</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Vỏ hỏi bệnh đã ký đủ</label></div>',
        ],
        'vo-benh-an-tong-ket' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo vỏ tổng kết</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo vỏ tổng kết</label></div>',
        ],
        'vo-benh-an-tong-ket-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Vỏ tổng kết chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Vỏ tổng kết đã đủ người ký</label></div>',
        ],
    ],
    'no_permission' => [
        'error' => '<div class="alert alert-danger" style="display: inline-block;"><strong>Bạn chưa được phân quyền để kiểm tra hồ sơ</strong></div>',
    ]
];