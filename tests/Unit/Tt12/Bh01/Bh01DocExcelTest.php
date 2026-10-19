<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01DacTa;
use App\Services\Tt12\Bh01\Bh01DocExcel;
use Tests\Support\TaoXlsxBh01;
use Tests\TestCase;

class Bh01DocExcelTest extends TestCase
{
    use TaoXlsxBh01;

    private $ky = array('nam' => '2026', 'thang' => '10', 'ky_qt' => '202610');

    public function setUp()
    {
        parent::setUp();
        // Maatwebsite boc moi lo doc trong DB::transaction tren ket noi mac dinh, ma .env dev tro CSDL
        // THAT: ep ket noi sang SQLite bo nho de test chi doc tep, khong cham CSDL that.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array('driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''),
        ]);
        \DB::purge('mysql');
    }

    private function tieuDe()
    {
        $ten = array();
        foreach (Bh01DacTa::the() as $dt) {
            $ten[] = $dt['the'];
        }
        return $ten;
    }

    /** Dong du lieu chuan, khoa theo ten the, gia tri chu nhu tep HIS. */
    private function dong(array $ghiDe = array())
    {
        $mau = array(
            'STT' => '1', 'HO_TEN' => 'Nguyen Van A', 'NGAY_SINH' => '198001010000', 'GIOI_TINH' => '1',
            'MA_THE_BHYT' => 'GD4010000000001', 'MA_BENH_CHINH' => 'E11.4',
            'NGAY_VAO' => '202610010800', 'NGAY_VAO_NOI_TRU' => '', 'NGAY_RA' => '202610051000',
            'SO_NGAY_DTRI' => '4', 'MA_LOAI_KCB' => '03',
            'T_TONGCHI_BV' => '1000', 'T_TONGCHI_BH' => '800.5', 'T_BHTT' => '700', 'T_BNCCT' => '100',
            'T_BNTT' => '200', 'T_NGUONKHAC' => '0',
            'MA_CSKCB' => '37470', 'NAM_QT' => '2026', 'THANG_QT' => '10',
        );
        return array_merge($mau, $ghiDe);
    }

    private function hang(array $dong)
    {
        $ra = array();
        foreach ($this->tieuDe() as $the) {
            $ra[] = $dong[$the];
        }
        return $ra;
    }

    private function tep(array $cacDong)
    {
        $hang = array($this->tieuDe());
        foreach ($cacDong as $d) {
            $hang[] = $this->hang($d);
        }
        return $this->taoXlsx($hang);
    }

    private function kiem($duongDan)
    {
        return (new Bh01DocExcel())->kiem($duongDan, '37470', $this->ky);
    }

    /** @test */
    public function nhan_tep_dung_dinh_dang_his()
    {
        $kq = $this->kiem($this->tep(array($this->dong(), $this->dong(array('STT' => '2')))));

        $this->assertSame(array(), $kq['loi']);
        $this->assertSame(2, $kq['so_dong']);
        $this->assertSame(array(), $kq['cot_thua']);
        $this->assertEquals(2000.0, $kq['t_tongchi_bv']);
        $this->assertEquals(1400.0, $kq['t_bhtt']);
    }

    /** @test */
    public function tieu_de_khong_phan_biet_hoa_thuong_va_dao_thu_tu()
    {
        $the = array_reverse($this->tieuDe());
        $tieuDe = array();
        foreach ($the as $t) {
            $tieuDe[] = ' ' . strtolower($t) . ' ';
        }
        $tieuDe[] = 'GHI_CHU';

        $d = $this->dong();
        $dong = array();
        foreach ($the as $t) {
            $dong[] = $d[$t];
        }
        $dong[] = 'abc';

        $kq = $this->kiem($this->taoXlsx(array($tieuDe, $dong)));

        $this->assertSame(array(), $kq['loi']);
        $this->assertSame(array('GHI_CHU'), $kq['cot_thua']);
        $this->assertSame(1, $kq['so_dong']);
    }

    /** @test */
    public function thieu_cot_va_trung_cot_thi_bao_loi()
    {
        $thieu = array_values(array_diff($this->tieuDe(), array('T_BHTT')));
        $kq = $this->kiem($this->taoXlsx(array($thieu, array_fill(0, count($thieu), 'x'))));
        $this->assertContains('Thiếu cột: T_BHTT', $kq['loi'][0]);

        $trung = $this->tieuDe();
        $trung[2] = 'HO_TEN';   // NGAY_SINH bi thay bang HO_TEN: HO_TEN hai lan, thieu NGAY_SINH
        $kq = $this->kiem($this->taoXlsx(array($trung, array_fill(0, count($trung), 'x'))));
        $this->assertContains('Trùng cột: HO_TEN', implode(' | ', $kq['loi']));
    }

    /** @test */
    public function lan_ky_hoac_co_so_thi_tu_choi_ca_tep()
    {
        $kq = $this->kiem($this->tep(array(
            $this->dong(),
            $this->dong(array('MA_CSKCB' => '01929')),
            $this->dong(array('THANG_QT' => '09')),
        )));

        $this->assertCount(1, $kq['loi']);
        $this->assertContains('01929 kỳ 10/2026 (1 dòng)', $kq['loi'][0]);
        $this->assertContains('37470 kỳ 09/2026 (1 dòng)', $kq['loi'][0]);
        $this->assertContains('Dòng đầu tiên: 3, 4', $kq['loi'][0]);
    }

    /** @test */
    public function o_so_mat_so_0_dau_duoc_dem_lai()
    {
        $d = Bh01DocExcel::chuanHoaDong($this->dong(array(
            'MA_CSKCB' => 1929, 'MA_LOAI_KCB' => 1, 'THANG_QT' => 9,
        )));
        $this->assertSame('01929', $d['ma_cskcb']);
        $this->assertSame('01', $d['ma_loai_kcb']);
        $this->assertSame('09', $d['thang_qt']);

        // Qua tep that: o SO 1929 / 1 / 10 phai duoc dem lai va khong bi coi la khac co so.
        $hang = array($this->tieuDe(), $this->hang($this->dong(array(
            'MA_CSKCB' => 1929, 'MA_LOAI_KCB' => 1, 'THANG_QT' => 10,
        ))));
        $kq = (new Bh01DocExcel())->kiem($this->taoXlsx($hang), '01929', $this->ky);
        $this->assertSame(array(), $kq['loi']);
    }

    /** @test */
    public function nam_qt_so_thuc_khong_bi_coi_la_khac_ky()
    {
        $hang = array($this->tieuDe(), $this->hang($this->dong(array('NAM_QT' => 2026.0))));
        $kq = $this->kiem($this->taoXlsx($hang));
        $this->assertSame(array(), $kq['loi']);
    }

    /** @test */
    public function tien_so_thuc_lam_tron_hai_chu_so()
    {
        $d = Bh01DocExcel::chuanHoaDong($this->dong(array('T_BHTT' => 1234.567)));
        $this->assertSame('1234.57', $d['t_bhtt']);
        $this->assertCount(19, $d);
        $this->assertArrayNotHasKey('stt', $d);
    }

    /** @test */
    public function tien_khong_phai_so_duoc_giu_nguyen_de_bi_chan()
    {
        $hang = array($this->tieuDe(), $this->hang($this->dong(array('T_BHTT' => 'abc'))));
        $kq = $this->kiem($this->taoXlsx($hang));
        $this->assertContains('Dòng 2', $kq['loi'][0]);
        $this->assertContains('T_BHTT', $kq['loi'][0]);
    }

    /** @test */
    public function ma_the_lay_the_dau_tien()
    {
        $d = Bh01DocExcel::chuanHoaDong($this->dong(array('MA_THE_BHYT' => ' GD4010000000001 ; GD4010000000002')));
        $this->assertSame('GD4010000000001', $d['ma_the_bhyt']);
    }

    /** @test */
    public function o_ngay_kieu_date_bi_chan()
    {
        $hang = array($this->tieuDe(), $this->hang($this->dong(array('NGAY_RA' => new \DateTime('2026-10-05')))));
        $kq = $this->kiem($this->taoXlsx($hang));
        $this->assertNotEmpty($kq['loi']);
        $this->assertContains('Dòng 2', $kq['loi'][0]);
    }

    /** @test */
    public function stt_trong_tep_bi_bo_qua()
    {
        $duongDan = $this->tep(array($this->dong(array('STT' => '99')), $this->dong(array('STT' => '5'))));
        $lo = array();
        (new Bh01DocExcel())->duyetLo($duongDan, function (array $cacDong) use (&$lo) {
            $lo = array_merge($lo, $cacDong);
        });

        $this->assertCount(2, $lo);
        foreach ($lo as $i => $phanTu) {
            $this->assertArrayNotHasKey('stt', $phanTu['du_lieu']);
            $this->assertCount(19, $phanTu['du_lieu']);
            $this->assertNull($phanTu['ma_lk']);
            $this->assertSame('Dòng ' . ($i + 2), $phanTu['vi_tri']);
        }
        $this->assertSame('Nguyen Van A', $lo[0]['du_lieu']['ho_ten']);
    }

    /** @test */
    public function dong_trong_cuoi_sheet_bi_bo_qua()
    {
        $hang = array($this->tieuDe(), $this->hang($this->dong()), $this->hang($this->dong()));
        for ($i = 0; $i < 5; $i++) {
            $hang[] = array_fill(0, 20, '');
        }
        $kq = $this->kiem($this->taoXlsx($hang));
        $this->assertSame(2, $kq['so_dong']);
        $this->assertSame(array(), $kq['loi']);
    }

    /** @test */
    public function tep_chi_co_tieu_de_thi_tu_choi()
    {
        $kq = $this->kiem($this->taoXlsx(array($this->tieuDe())));
        $this->assertSame(0, $kq['so_dong']);
        $this->assertSame(array('Tệp không có hồ sơ nào.'), $kq['loi']);
    }

    /** @test */
    public function chi_bao_toi_da_20_loi_dong()
    {
        $cacDong = array();
        for ($i = 0; $i < 30; $i++) {
            $cacDong[] = $this->dong(array('HO_TEN' => ''));
        }
        $kq = $this->kiem($this->tep($cacDong));
        $this->assertCount(20, $kq['loi']);
        $this->assertSame(30, $kq['so_dong']);
    }

    /** @test */
    public function tep_hong_nem_tep_excel_hong_exception()
    {
        $duongDan = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'khong_phai_xlsx_' . uniqid() . '.xlsx';
        file_put_contents($duongDan, 'khong phai tep excel');
        try {
            $this->expectException(\App\Services\Tt12\Bh01\Bh01TepExcelHongException::class);
            $this->kiem($duongDan);
        } finally {
            @unlink($duongDan);
        }
    }
}
