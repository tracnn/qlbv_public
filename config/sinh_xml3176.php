<?php

/**
 * Tham so ky thuat cua module SinhXml3176 (di theo git).
 * Cau hinh theo don vi nam o khoi organization.sinh_xml3176.
 */
return [
    // So ho so nap mot lan. Do Dot 3: 5 ho so noi tru lon nhat = 432MB, 20 ho so = 948MB (PHP Fatal o 512M);
    // khoang 35-40MB moi ho so lon. Xem docs/trien-khai-sinh-xml3176.md, muc "Bo nho va thoi gian".
    'kich_thuoc_lo'  => 5,
    'nghi_giay'      => 60,
    'chong_lan_phut' => 10,
    'khoa_luot_phut' => 120,
    // Luot quet theo MODIFY_TIME chi xet ho so co OUT_TIME trong N ngay gan nhat (HIS chi co chi muc OUT_TIME/FEE_LOCK_TIME)
    'cua_so_ra_vien_ngay' => 60,
    // Lenh CLI tu dat ini memory_limit (may chu mac dinh 128M). 1024M du ~2,4 lan dinh 432MB cua lo 5 ho so lon (Dot 3).
    'memory_limit'   => '1024M',
];
