<?php

namespace App\Services\Xml3176;

use App\Services\Xml3176Service;

/**
 * Diem vao DUY NHAT de nhap mot ho so XML3176.
 *
 * Truoc day nghiep vu nay duoc cai dat hai lan - trong BHYTXml3176Controller (tai len
 * tay) va trong Console\Commands\XML3176Import (quet thu muc) - va hai ban DA lech
 * nhau: controller xu ly XML1-15, command xu ly XML1-18 va co them chinh sach
 * exportable_tt. Cung mot ho so cho hai ket qua khac nhau tuy duong vao.
 */
class Xml3176Importer
{
    /**
     * Anh xa LOAIHOSO -> phuong thuc luu tren Xml3176Service.
     *
     * Ba trang thai KHAC NHAU, dung lan lon:
     *   - co khoa, gia tri chuoi : goi phuong thuc do
     *   - co khoa, gia tri null  : BO QUA CO CHU DICH, khong ghi log
     *   - khong co khoa          : loai la, ghi Log::warning
     *
     * Su nhap nhang giua hai truong hop dau va cuoi chinh la thu da mat khi hai ban
     * cai dat lech nhau.
     */
    const LOAI_XML = [
        'XML1'  => 'storeXml3176Xml1',
        'XML2'  => 'storeXml3176Xml2',
        'XML3'  => 'storeXml3176Xml3',
        'XML4'  => 'storeXml3176Xml4',
        'XML5'  => 'storeXml3176Xml5',
        'XML6'  => 'storeXml3176Xml6',
        'XML7'  => 'storeXml3176Xml7',
        'XML8'  => 'storeXml3176Xml8',
        'XML9'  => 'storeXml3176Xml9',
        'XML10' => 'storeXml3176Xml10',
        'XML11' => 'storeXml3176Xml11',
        'XML12' => null,
        'XML13' => 'storeXml3176Xml13',
        'XML14' => 'storeXml3176Xml14',
        'XML15' => 'storeXml3176Xml15',
        'XML16' => null,
        'XML17' => null,
        'XML18' => null,
    ];

    protected $xml3176Service;

    public function __construct(Xml3176Service $xml3176Service)
    {
        $this->xml3176Service = $xml3176Service;
    }

    public static function coTrongDangKy($loai)
    {
        return is_string($loai) && array_key_exists($loai, self::LOAI_XML);
    }

    /**
     * @return string|null Ten phuong thuc, hoac null neu bo qua co chu dich
     */
    public static function handlerCho($loai)
    {
        return self::coTrongDangKy($loai) ? self::LOAI_XML[$loai] : null;
    }
}
