<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Carbon\Carbon;
use Tests\TestCase;
use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\TabDieuTriGiuong;

class TabDieuTriGiuongTest extends TestCase
{
    private function danhMuc()
    {
        return [
            'khoa' => [
                10 => '1|K04', 11 => '1|K04', 15 => '1|K05', 40 => '1|K05',
                20 => '81|K05', 50 => '1|K01', 60 => '1|K06',
            ],
            'don_vi' => [
                '1|K04' => ['ma' => 'K04', 'ten' => 'Tim mạch', 'branch_id' => 1, 'giuong_pd' => 50],
                '1|K05' => ['ma' => 'K05', 'ten' => 'Nội', 'branch_id' => 1, 'giuong_pd' => 40],
                '81|K05' => ['ma' => 'K05', 'ten' => 'Nội NB', 'branch_id' => 81, 'giuong_pd' => 20],
                '1|K01' => ['ma' => 'K01', 'ten' => 'Khám bệnh', 'branch_id' => 1, 'giuong_pd' => null],
                '1|K06' => ['ma' => 'K06', 'ten' => 'Ngoại', 'branch_id' => 1, 'giuong_pd' => 10],
            ],
        ];
    }

    private function ky($coSo = 'hn', $from = '20261001000000', $to = '20261019235959', $now = '2026-10-20 08:00:00')
    {
        return new KyLoc($from, $to, $coSo, Carbon::parse($now, config('app.timezone')));
    }

    private function luot($tid, $khoa, $add, $remove)
    {
        return (object) ['treatment_id' => $tid, 'department_id' => $khoa, 'add_time' => $add, 'remove_time' => $remove];
    }

    private function giay($ymdHis)
    {
        return Carbon::createFromFormat('YmdHis', $ymdHis, config('app.timezone'))->getTimestamp();
    }

    private function ngayGiuong(array $luot, KyLoc $ky)
    {
        return TabDieuTriGiuong::ngayGiuongTheoDonVi(
            $luot, $this->danhMuc()['khoa'], $this->giay($ky->from), $this->giay($ky->denThucTe())
        );
    }

    /** @test */
    public function epoch_theo_mui_gio_ung_dung()
    {
        $this->assertSame($this->giay('20261001000000'), TabDieuTriGiuong::epoch('20261001000000'));
        $this->assertSame(86400 + 3600, TabDieuTriGiuong::epoch('20261002010000') - TabDieuTriGiuong::epoch('20261001000000'));
    }

    /** @test */
    public function ngay_giuong_cat_theo_ky()
    {
        $kq = $this->ngayGiuong([$this->luot(1, 10, '20260930000000', '20261001120000')], $this->ky());

        $this->assertEquals(['1|K04' => 0.5], $kq, '', 1e-9);
    }

    /** @test */
    public function nam_ghep_hai_giuong_tinh_mot()
    {
        // Khoa con 10 và 11 cùng đơn vị 1|K04 (sắp như truy vấn: treatment_id, department_id, add_time);
        // hồ sơ 2 cùng đơn vị nhưng là hồ sơ khác nên cộng riêng
        $luot = [
            $this->luot(1, 10, '20261002000000', '20261003000000'),
            $this->luot(1, 11, '20261002000000', '20261003000000'),
            $this->luot(1, 15, '20261005000000', '20261005120000'),
            $this->luot(2, 10, '20261002000000', '20261002060000'),
        ];

        $kq = $this->ngayGiuong($luot, $this->ky());

        $this->assertEquals(1.25, $kq['1|K04'], '', 1e-9);
        $this->assertEquals(0.5, $kq['1|K05'], '', 1e-9);
    }

    /** @test */
    public function chuyen_khoa_trong_ngay_chia_dung_phan()
    {
        $luot = [
            $this->luot(1, 10, '20261003000000', '20261003060000'),
            $this->luot(1, 40, '20261003060000', '20261004000000'),
        ];

        $kq = $this->ngayGiuong($luot, $this->ky());

        $this->assertEquals(0.25, $kq['1|K04'], '', 1e-9);
        $this->assertEquals(0.75, $kq['1|K05'], '', 1e-9);
    }

    /** @test */
    public function luot_dang_nam_cat_tai_den_thuc_te()
    {
        $ky = $this->ky('hn', '20261001000000', '20261019235959', '2026-10-19 10:00:00');

        $kq = $this->ngayGiuong([$this->luot(1, 10, '20261019000000', null)], $ky);

        $this->assertEquals(10 / 24, $kq['1|K04'], '', 1e-9);
    }

    /** @test */
    public function luot_treo_cat_tai_gio_ra_vien()
    {
        $luot = [
            // remove_time NULL nhưng hồ sơ đã ra viện trong kỳ → kết thúc tại out_time
            (object) ['treatment_id' => 1, 'department_id' => 10, 'add_time' => '20261005000000', 'remove_time' => null, 'out_time' => '20261005120000'],
            // remove_time sớm hơn out_time → giữ remove_time
            (object) ['treatment_id' => 2, 'department_id' => 40, 'add_time' => '20261006000000', 'remove_time' => '20261006060000', 'out_time' => '20261007000000'],
        ];

        $kq = $this->ngayGiuong($luot, $this->ky());

        $this->assertEquals(0.5, $kq['1|K04'], '', 1e-9);
        $this->assertEquals(0.25, $kq['1|K05'], '', 1e-9);
    }

    /** @test */
    public function cong_suat_dung_moc_cat_luu_cung_ngay_giuong()
    {
        // Ngày-giường tính lúc 10:00 (T1) và được cache; request sau lúc 16:00 (T2) dùng lại
        $kyT1 = $this->ky('hn', '20261019000000', '20261019235959', '2026-10-19 10:00:00');
        $kyT2 = $this->ky('hn', '20261019000000', '20261019235959', '2026-10-19 16:00:00');
        $luot = [];
        for ($i = 1; $i <= 10; $i++) {
            $luot[] = $this->luot($i, 60, '20261019000000', null);
        }
        $ngayGiuong = $this->ngayGiuong($luot, $kyT1);

        $kq = TabDieuTriGiuong::dinhDang([], $ngayGiuong, [], $this->danhMuc(), $kyT2, $kyT1->denThucTe());
        $i = array_search('Ngoại', $kq['cong_suat']['categories'], true);
        $this->assertSame(100.0, $kq['cong_suat']['gia_tri'][$i]);

        // Thẻ HN: 100 giường PD (50 + 40 + 10), 10 BN × 10h → 10%
        $tong = TabDieuTriGiuong::tongHop([], $ngayGiuong, $this->danhMuc(), $kyT2, $kyT1->denThucTe());
        $this->assertSame(10.0, $tong['cong_suat_giuong']['hn']);
    }

    /** @test */
    public function luot_ngoai_danh_muc_bi_bo()
    {
        $kq = $this->ngayGiuong([$this->luot(1, 999, '20261002000000', '20261003000000')], $this->ky());

        $this->assertSame([], $kq);
    }

    /** @test */
    public function cong_suat_hom_nay_chia_theo_phan_ngay()
    {
        $ky = $this->ky('hn', '20261019000000', '20261019235959', '2026-10-19 10:00:00');
        $luot = [];
        for ($i = 1; $i <= 10; $i++) {
            $luot[] = $this->luot($i, 60, '20261019000000', null);
        }

        $kq = TabDieuTriGiuong::dinhDang([], $this->ngayGiuong($luot, $ky), [], $this->danhMuc(), $ky);

        $i = array_search('Ngoại', $kq['cong_suat']['categories'], true);
        $this->assertNotFalse($i);
        $this->assertSame(100.0, $kq['cong_suat']['gia_tri'][$i]);
        $this->assertSame(10, $kq['cong_suat']['giuong_pd'][$i]);
    }

    /** @test */
    public function ngay_dieu_tri_tb_gop_khoa_con_theo_trong_so()
    {
        $dong = [
            (object) ['last_department_id' => 10, 'branch_id' => 1, 'tong_ngay' => 10, 'so_ho_so' => 5],
            (object) ['last_department_id' => 11, 'branch_id' => 1, 'tong_ngay' => 30, 'so_ho_so' => 5],
        ];

        $kq = TabDieuTriGiuong::dinhDang($dong, [], [], $this->danhMuc(), $this->ky());

        $this->assertSame(['Tim mạch'], $kq['ngay_dieu_tri']['categories']);
        $this->assertSame([4.0], $kq['ngay_dieu_tri']['gia_tri']);
        $this->assertSame([10], $kq['ngay_dieu_tri']['so_ho_so']);
    }

    /** @test */
    public function ngay_dieu_tri_loc_co_so_theo_khoa_va_sap_giam()
    {
        $dong = [
            (object) ['last_department_id' => 10, 'branch_id' => 1, 'tong_ngay' => 10, 'so_ho_so' => 5],
            (object) ['last_department_id' => 40, 'branch_id' => 1, 'tong_ngay' => 21, 'so_ho_so' => 3],
            (object) ['last_department_id' => 20, 'branch_id' => 81, 'tong_ngay' => 9, 'so_ho_so' => 3],
            (object) ['last_department_id' => 999, 'branch_id' => 1, 'tong_ngay' => 9, 'so_ho_so' => 3],
        ];

        $hn = TabDieuTriGiuong::dinhDang($dong, [], [], $this->danhMuc(), $this->ky('hn'));
        $this->assertSame(['Nội', 'Tim mạch'], $hn['ngay_dieu_tri']['categories']);
        $this->assertSame([7.0, 2.0], $hn['ngay_dieu_tri']['gia_tri']);

        $hai = TabDieuTriGiuong::dinhDang($dong, [], [], $this->danhMuc(), $this->ky('2cs'));
        $this->assertSame(['Nội (HN)', 'Nội NB (NB)', 'Tim mạch (HN)'], $hai['ngay_dieu_tri']['categories']);
    }

    /** @test */
    public function don_vi_khong_giuong_pd_khong_vao_cong_suat()
    {
        $ngayGiuong = ['1|K01' => 5.0, '1|K04' => 25.0];
        $dangNam = [(object) ['department_id' => 50, 'dang_nam' => 3], (object) ['department_id' => 10, 'dang_nam' => 2]];

        $kq = TabDieuTriGiuong::dinhDang([], $ngayGiuong, $dangNam, $this->danhMuc(), $this->ky());

        $this->assertNotContains('Khám bệnh', $kq['cong_suat']['categories']);
        $this->assertNotContains('Khám bệnh', array_column($kq['giuong_hien_tai'], 'don_vi'));
        // Đơn vị có giường PD vẫn hiện dù không có ngày-giường (0%)
        $this->assertSame(['Tim mạch', 'Nội', 'Ngoại'], $kq['cong_suat']['categories']);
        $this->assertSame([50, 40, 10], $kq['cong_suat']['giuong_pd']);
        $soNgay = $this->ky()->soNgayThucTe();
        $this->assertSame(round(25.0 / (50 * $soNgay) * 100, 1), $kq['cong_suat']['gia_tri'][0]);
        $this->assertSame([0.0, 0.0], array_slice($kq['cong_suat']['gia_tri'], 1));
        $this->assertSame(25.0, $kq['cong_suat']['ngay_giuong'][0]);
    }

    /** @test */
    public function giuong_hien_tai_con_trong_co_the_am()
    {
        $dangNam = [
            (object) ['department_id' => 10, 'dang_nam' => 30],
            (object) ['department_id' => 11, 'dang_nam' => 25],
            (object) ['department_id' => 60, 'dang_nam' => 5],
            (object) ['department_id' => 20, 'dang_nam' => 7],
        ];

        $kq = TabDieuTriGiuong::dinhDang([], [], $dangNam, $this->danhMuc(), $this->ky('hn'));

        $this->assertSame([
            ['don_vi' => 'Tim mạch', 'giuong_pd' => 50, 'dang_nam' => 55, 'con_trong' => -5, 'cong_suat' => 110.0],
            ['don_vi' => 'Ngoại', 'giuong_pd' => 10, 'dang_nam' => 5, 'con_trong' => 5, 'cong_suat' => 50.0],
            ['don_vi' => 'Nội', 'giuong_pd' => 40, 'dang_nam' => 0, 'con_trong' => 40, 'cong_suat' => 0.0],
        ], $kq['giuong_hien_tai']);
    }

    /** @test */
    public function tong_co_so_ngay_dieu_tri_theo_branch_ho_so_cong_suat_theo_don_vi()
    {
        // Hồ sơ branch 81 nhưng khoa ra viện thuộc HN: thẻ số tính vào NB
        $dong = [
            (object) ['last_department_id' => 10, 'branch_id' => 1, 'tong_ngay' => 10, 'so_ho_so' => 5],
            (object) ['last_department_id' => 10, 'branch_id' => 81, 'tong_ngay' => 12, 'so_ho_so' => 3],
            (object) ['last_department_id' => 20, 'branch_id' => 81, 'tong_ngay' => 8, 'so_ho_so' => 2],
        ];
        $ngayGiuong = ['1|K04' => 30.0, '1|K06' => 10.0, '1|K01' => 99.0, '81|K05' => 15.0];
        $ky = $this->ky('hn', '20261001000000', '20261010235959');
        $soNgay = $ky->soNgayThucTe();

        $kq = TabDieuTriGiuong::tongHop($dong, $ngayGiuong, $this->danhMuc(), $ky);

        $this->assertSame(['hn' => 2.0, 'nb' => 4.0, '2cs' => 3.0], $kq['ngay_dieu_tri']);
        $this->assertSame([
            'hn' => round(40.0 / (100 * $soNgay) * 100, 1),
            'nb' => round(15.0 / (20 * $soNgay) * 100, 1),
            '2cs' => round(55.0 / (120 * $soNgay) * 100, 1),
        ], $kq['cong_suat_giuong']);
    }
}
