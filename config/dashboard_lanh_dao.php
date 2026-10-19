<?php

// Cau hinh Dashboard lanh dao. File nay duoc theo doi git CO CHU DICH
// vi config/organization.php bi .gitignore (cau hinh rieng tung may).
return [
    'co_so' => [
        'hn' => ['branch_id' => 1, 'ma_cskcb' => '01929', 'nhan' => 'HN'],
        'nb' => ['branch_id' => 81, 'ma_cskcb' => '37470', 'nhan' => 'NB'],
    ],
    'pay_form_qr' => ['08', '07', '19'],
    'pay_form_hoan_online' => ['02'],
    'khoa_ngoai' => ['hn' => ['K19', 'K20', 'K21', 'K22', 'K23', 'K24', 'K52'], 'nb' => ['K19']],
    'phong_can_thiep_tim_mach' => [
        'CLS.CT0001', 'CLS.CT0002', 'CLS.CT0003', 'CLS.CT0004', 'CLS.CT0005', 'CLS.CT0006', 'CLS.CT0007',
        'NBKP03.K19', 'NBKP03.K20', 'NBKP03.K21', 'NBKP03.K22', 'NBKP03.K23',
    ],
    'ma_khoa_bo_qua' => ['KTEST'],
    'so_ngay_toi_da' => 92,
];
