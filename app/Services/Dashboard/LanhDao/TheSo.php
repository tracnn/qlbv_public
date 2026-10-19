<?php

namespace App\Services\Dashboard\LanhDao;

use Illuminate\Support\Facades\Cache;

/**
 * Hàng thẻ số của Dashboard lãnh đạo: ghép số tổng từ ba tab (hình dạng R1).
 */
class TheSo
{
    private $dieuTri;
    private $khamThuoc;
    private $heNgoai;

    public function __construct(TabDieuTriGiuong $dieuTri, TabKhamThuoc $khamThuoc, TabHeNgoai $heNgoai)
    {
        $this->dieuTri = $dieuTri;
        $this->khamThuoc = $khamThuoc;
        $this->heNgoai = $heNgoai;
    }

    public function lay(KyLoc $ky): array
    {
        return Cache::remember($ky->khoaCache('the_so'), $ky->daKetThuc() ? 60 : 5, function () use ($ky) {
            $phan = $this->dieuTri->tongCoSo($ky)
                + $this->khamThuoc->tongCoSo($ky)
                + $this->heNgoai->tongCoSo($ky);

            return self::dinhDang($phan, $ky);
        });
    }

    /**
     * @param array $phan gộp các mảng tongCoSo(): mỗi chỉ số là ['hn'=>, 'nb'=>, '2cs'=>]
     */
    public static function dinhDang(array $phan, KyLoc $ky): array
    {
        $co = $ky->coSo;

        $so = function ($khoa) use ($phan, $co) {
            $o = isset($phan[$khoa]) ? $phan[$khoa] : [];
            $lay = function ($k) use ($o) {
                return isset($o[$k]) ? (float) $o[$k] : null;
            };

            return ['gia_tri' => $lay($co), 'hn' => $lay('hn'), 'nb' => $lay('nb')];
        };
        $dem = function ($khoa) use ($phan, $co) {
            return isset($phan[$khoa][$co]) ? (int) $phan[$khoa][$co] : 0;
        };

        $moi = $dem('moi');
        $cu = $dem('cu');

        return [
            'ngay_dieu_tri' => $so('ngay_dieu_tri'),
            'cong_suat_giuong' => $so('cong_suat_giuong'),
            'tg_kham_phut' => $so('tg_kham_phut') + ['so_ho_so' => $dem('tg_kham_so_ho_so')],
            'ke_don_mua_thuoc_phut' => $so('ke_don_phut') + ['so_phieu' => $dem('ke_don_so_phieu')],
            'kham_moi_cu' => ['moi' => $moi, 'cu' => $cu, 'ty_le_moi' => TinhToan::tyLe($moi, $moi + $cu)],
            'he_ngoai' => [
                'y_lenh_pt' => $dem('y_lenh_pt'),
                'ca_pt' => $dem('ca_pt'),
                'can_thiep_tm' => $dem('can_thiep_tm'),
            ],
        ];
    }
}
