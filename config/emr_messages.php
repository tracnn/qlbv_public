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
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Vỏ hỏi bệnh chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Vỏ hỏi bệnh đã đủ người ký</label></div>',
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
    'emr-check-bbhc-dvkt' => [
        'check' => '<h4><strong>Biên bản hội chẩn DVKT:</strong></h4>',
        'bbhc-dvkt-his' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo BBHC DVKT (MRI/CT) trên HIS</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo BBHC DVKT (MRI/CT) trên HIS</label></div>',
        ],
        'bbhc-dvkt-emr' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa thiết lập ký BBHC DVKT trên EMR</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã thiết lập ký BBHC DVKT trên EMR</label></div>',
        ],
        'bbhc-dvkt-emr-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">BBHC DVKT chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">BBHC DVKT đã đủ người ký</label></div>',
        ],
    ],
    'emr-check-bbhc-pttt' => [
        'check' => '<h4><strong>Biên bản hội chẩn PTTT:</strong></h4>',
        'bbhc-pttt-his' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo BBHC PTTT trên HIS</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo BBHC PTTT trên HIS</label></div>',
        ],
        'bbhc-pttt-emr' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa thiết lập ký BBHC PTTT trên EMR</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã thiết lập ký BBHC PTTT trên EMR</label></div>',
        ],
        'bbhc-pttt-emr-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">BBHC PTTT chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">BBHC PTTT đã đủ người ký</label></div>',
        ],
    ],
    'emr-check-bbhc-thuoc' => [
        'check' => '<h4><strong>Biên bản hội chẩn thuốc (*):</strong></h4>',
        'bbhc-thuoc-his' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa tạo/Tạo không đúng loại BBHC thuốc (*) trên HIS</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã tạo BBHC thuốc (*) trên HIS</label></div>',
        ],
        'bbhc-thuoc-emr' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Chưa thiết lập ký BBHC thuốc (*) trên EMR</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">Đã thiết lập ký BBHC thuốc (*) trên EMR</label></div>',
        ],
        'bbhc-thuoc-emr-signer' => [
            'error' => '<div style="display: inline-block;"><label class="alert alert-danger">BBHC thuốc (*) chưa đủ người ký</label></div>',
            'success' => '<div style="display: inline-block;"><label class="alert alert-success">BBHC thuốc (*) đã đủ người ký</label></div>',
        ],
    ],
    'no_permission' => [
        'error' => '<div class="alert alert-danger" style="display: inline-block;"><strong>Bạn chưa được phân quyền để kiểm tra hồ sơ</strong></div>',
    ],
    'inpatient' => [
        'check' => '<h4><strong>BN điều trị nội trú</strong></h4>',
        'keywords' => [
            'sốt', '38.5', '38,5', 'co giật', 'đau đầu nhiều', 'chóng mặt', 'nôn nhiều',
            'khó thở', 'huyết áp thấp', 'huyết áp tối thiểu', 'nôn ra máu',
            'ỉa máu', 'chảy máu', 'ban xuất huyết', 'tiêm', 'truyền', 'phẫu thuật', 'bụng cấp', 'đau ngực'
        ],
        'no_exam' => '<div style="display: inline-block;"><label class="alert alert-danger">Không có thông tin phiếu khám</label></div>',
        'error' => '<div style="display: inline-block;"><label class="alert alert-danger">Không cần nhập viện</label></div>',
        'success' => '<div style="display: inline-block;"><label class="alert alert-success">Cần nhập viện</label></div>',
    ],
];