<?php

namespace App\Services\SinhXml3176;

/**
 * Cau hinh bat bien cua module sinh XML3176 truc tiep tu HIS.
 * Gop khoi organization.sinh_xml3176 voi gia tri mac dinh; thieu khoi thi module tat.
 */
class CauHinhSinhXml3176
{
    const CHINH_THUC = 'chinh_thuc';
    const THONG_TUYEN = 'thong_tuyen';

    private static $macDinh = [
        'enabled'          => false,
        'ghi_thu_muc_that' => false,
        'thong_tuyen'      => false,
        'ket_noi_his'      => 'HISPro',
        'thu_muc'          => 'D:\XML\3176',
        'thu_muc_tt'       => 'D:\XML\3176TT',
        'thu_muc_thu'      => 'D:\XML\SinhXml3176Thu',
    ];

    private $khoi;
    private $kyThuat;

    private function __construct(array $khoi, array $kyThuat)
    {
        $this->khoi = $khoi;
        $this->kyThuat = $kyThuat;
    }

    public static function tuConfig()
    {
        $khoi = config('organization.sinh_xml3176', []);
        if (!is_array($khoi)) {
            $khoi = [];
        }
        $kyThuat = config('sinh_xml3176', []);
        if (!is_array($kyThuat)) {
            $kyThuat = [];
        }

        return new self(array_merge(self::$macDinh, $khoi), $kyThuat);
    }

    public function batBat()
    {
        return (bool) $this->khoi['enabled'];
    }

    public function thongTuyen()
    {
        return (bool) $this->khoi['thong_tuyen'];
    }

    public function ghiThuMucThat()
    {
        return (bool) $this->khoi['ghi_thu_muc_that'];
    }

    public function ketNoiHis()
    {
        return (string) $this->khoi['ket_noi_his'];
    }

    public function coSo()
    {
        return $this->mangMa('co_so');
    }

    public function doiTuongBn()
    {
        return $this->mangMa('doi_tuong_bn');
    }

    public function doiTuongTt()
    {
        return $this->mangMa('doi_tuong_tt');
    }

    public function dienDieuTri()
    {
        return $this->mangMa('dien_dieu_tri');
    }

    /** Thu muc that neu ghi_thu_muc_that, nguoc lai thu muc thu. */
    public function thuMuc($loaiBan)
    {
        if (!$this->ghiThuMucThat()) {
            return (string) $this->khoi['thu_muc_thu'];
        }

        return (string) ($loaiBan === self::THONG_TUYEN ? $this->khoi['thu_muc_tt'] : $this->khoi['thu_muc']);
    }

    public function kichThuocLo()
    {
        return (int) $this->kyThuat('kich_thuoc_lo', 50);
    }

    public function nghiGiay()
    {
        return (int) $this->kyThuat('nghi_giay', 60);
    }

    public function chongLanPhut()
    {
        return (int) $this->kyThuat('chong_lan_phut', 10);
    }

    public function khoaLuotPhut()
    {
        return (int) $this->kyThuat('khoa_luot_phut', 120);
    }

    private function kyThuat($khoa, $macDinh)
    {
        return isset($this->kyThuat[$khoa]) ? $this->kyThuat[$khoa] : $macDinh;
    }

    private function mangMa($khoa)
    {
        $gt = isset($this->khoi[$khoa]) ? $this->khoi[$khoa] : [];
        if (!is_array($gt)) {
            $gt = [$gt];
        }
        $ra = [];
        foreach ($gt as $ma) {
            $ma = trim((string) $ma);
            if ($ma !== '') {
                $ra[] = $ma;
            }
        }

        return array_values($ra);
    }
}
