<?php

namespace App\Services\SinhXml3176;

/**
 * Cau hinh bat bien cua module sinh XML3176 truc tiep tu HIS.
 * Gop khoi organization.sinh_xml3176 voi gia tri mac dinh; thieu khoi thi module tat.
 * Gia tri null trong khoi KHONG de len mac dinh (tru ket_noi_his: null/rong la loi cau hinh,
 * khong bao gio roi ve ket noi mac dinh). loiCauHinh() liet ke loi; lenh kiem truoc khi chay.
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
        // null = thu muc anh em cua thu muc dich: <cha cua dich>\.sinh_xml3176_tmp (xem GhiTep)
        'thu_muc_tam'      => null,
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

        $ketNoiRong = array_key_exists('ket_noi_his', $khoi) && trim((string) $khoi['ket_noi_his']) === '';
        $khoi = array_filter($khoi, function ($gt) {
            return $gt !== null;
        });
        $kyThuat = array_filter($kyThuat, function ($gt) {
            return $gt !== null;
        });
        $gop = array_merge(self::$macDinh, $khoi);
        if ($ketNoiRong) {
            $gop['ket_noi_his'] = '';
        }

        return new self($gop, $kyThuat);
    }

    /**
     * Loi cau hinh chan chay lenh (rong = hop le).
     * - ket_noi_his rong/null.
     * - thu_muc_thu trung hoac nam trong thu_muc/thu_muc_tt (tien giam dinh quet de quy).
     * - thu muc tam cua bat ky thu muc dich nao nam trong thu_muc/thu_muc_tt, hoac khac o dia voi dich.
     *
     * @return string[]
     */
    public function loiCauHinh()
    {
        $loi = [];
        if (trim((string) $this->khoi['ket_noi_his']) === '') {
            $loi[] = 'ket_noi_his rong: phai khai ten ket noi HIS (vd HISPro), khong dung ket noi mac dinh';
        }

        $quet = [
            'thu_muc'    => (string) $this->khoi['thu_muc'],
            'thu_muc_tt' => (string) $this->khoi['thu_muc_tt'],
        ];
        $thu = (string) $this->khoi['thu_muc_thu'];
        foreach ($quet as $khoa => $goc) {
            if (self::namTrong($thu, $goc)) {
                $loi[] = 'thu_muc_thu (' . $thu . ') trung hoac nam trong ' . $khoa . ' (' . $goc
                    . '): tien giam dinh quet de quy se nap tep thu';
            }
        }

        foreach (array_unique(array_merge(array_values($quet), [$thu])) as $dich) {
            $tam = $this->thuMucTam($dich);
            foreach ($quet as $khoa => $goc) {
                if (self::namTrong($tam, $goc)) {
                    $loi[] = 'Thu muc tam (' . $tam . ') nam trong ' . $khoa . ' (' . $goc
                        . '): khai thu_muc_tam ra ngoai cay thu muc quet';
                }
            }
            if (self::oDia($tam) !== self::oDia($dich)) {
                $loi[] = 'Thu muc tam (' . $tam . ') khac o dia voi ' . $dich . ': rename khong con nguyen tu';
            }
        }

        return array_values(array_unique($loi));
    }

    /** Thu muc ghi tep tam cho thu muc dich: thu_muc_tam neu khai, nguoc lai thu muc anh em (GhiTep). */
    public function thuMucTam($thuMucDich)
    {
        $khai = trim((string) $this->khoi['thu_muc_tam']);

        return $khai !== '' ? $khai : GhiTep::thuMucTamMacDinh($thuMucDich);
    }

    /** Chuan hoa duong dan de so sanh: '/' thanh '\\', bo '\\' cuoi, chu thuong (Windows khong phan biet hoa thuong). */
    public static function chuanHoaDuongDan($duongDan)
    {
        $p = str_replace('/', '\\', trim((string) $duongDan));
        $p = preg_replace('#\\\\+#', '\\', $p);
        $p = rtrim($p, '\\');

        return strtolower($p);
    }

    /** $con trung hoac nam trong $cha (sau chuan hoa). Duong dan rong khong nam trong gi. */
    public static function namTrong($con, $cha)
    {
        $con = self::chuanHoaDuongDan($con);
        $cha = self::chuanHoaDuongDan($cha);
        if ($con === '' || $cha === '') {
            return false;
        }

        return $con === $cha || strpos($con . '\\', $cha . '\\') === 0;
    }

    private static function oDia($duongDan)
    {
        $p = self::chuanHoaDuongDan($duongDan);

        return preg_match('/^([a-z]):/', $p, $m) ? $m[1] : '';
    }

    public function batBat()
    {
        return (bool) $this->khoi['enabled'];
    }

    public function thongTuyen()
    {
        return (bool) $this->khoi['thong_tuyen'];
    }

    /**
     * Cong tac duy nhat cho ghi vao thu muc tien giam dinh quet that: chi bat khi gia tri dung la true
     * (chuoi 'false', 1, '1' ... deu coi nhu tat).
     */
    public function ghiThuMucThat()
    {
        return $this->khoi['ghi_thu_muc_that'] === true;
    }

    /** Ten ket noi HIS; rong/null => loi cau hinh (khong roi ve ket noi mac dinh cua Laravel). */
    public function ketNoiHis()
    {
        $ten = trim((string) $this->khoi['ket_noi_his']);
        if ($ten === '') {
            throw new \InvalidArgumentException('Cau hinh sinh_xml3176.ket_noi_his rong: phai khai ten ket noi HIS');
        }

        return $ten;
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
        return (int) $this->kyThuat('kich_thuoc_lo', 5);
    }

    /** memory_limit dat cho lenh CLI. */
    public function memoryLimit()
    {
        $gt = trim((string) $this->kyThuat('memory_limit', '1024M'));

        return $gt !== '' ? $gt : '1024M';
    }

    /**
     * Doi gia tri memory_limit ("512M", "1G", "2048K", "1048576", "-1") ra so byte.
     * -1 = khong gioi han; khong hop le => null.
     */
    public static function memoryLimitRaByte($gt)
    {
        $gt = strtolower(trim((string) $gt));
        if ($gt === '-1') {
            return -1;
        }
        if (!preg_match('/^(\d+)([kmg]?)$/', $gt, $m)) {
            return null;
        }
        $so = (int) $m[1];
        switch ($m[2]) {
            case 'g':
                $so *= 1024;
                // no break
            case 'm':
                $so *= 1024;
                // no break
            case 'k':
                $so *= 1024;
        }

        return $so;
    }

    public function nghiGiay()
    {
        return (int) $this->kyThuat('nghi_giay', 60);
    }

    public function chongLanPhut()
    {
        return (int) $this->kyThuat('chong_lan_phut', 10);
    }

    /** So ngay lui tu hom nay 00:00 cho cua so out_time cua luot quet; nho hon 1 thi dung 1. */
    public function cuaSoRaVienNgay()
    {
        return max(1, (int) $this->kyThuat('cua_so_ra_vien_ngay', 60));
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
