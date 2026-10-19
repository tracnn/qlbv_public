<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01Dong;
use App\Services\Tt12\Bh01\Bh01DacTa;
use App\Services\Tt12\Bh01\Bh01GhiBan;
use App\Services\Tt12\Bh01\Bh01KhongTaoDuocException;
use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01NhapExcel;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DungBangBh01Sqlite;
use Tests\Support\TaoXlsxBh01;
use Tests\TestCase;

class Bh01NhapExcelTest extends TestCase
{
    use DungBangBh01Sqlite, TaoXlsxBh01;

    public function setUp()
    {
        parent::setUp();
        // dungBangBh01 ep ket noi sang SQLite bo nho (Maatwebsite boc lo doc trong DB::transaction
        // tren ket noi mac dinh, ma .env dev tro CSDL THAT) va dung bang ban/dong.
        $this->dungBangBh01();
        Storage::fake('exportTt12');
    }

    private function ky()
    {
        return Bh01Ky::tu(2026, 10);
    }

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

    private function tep(array $cacDong)
    {
        $tieuDe = array();
        foreach (Bh01DacTa::the() as $dt) {
            $tieuDe[] = $dt['the'];
        }
        $hang = array($tieuDe);
        foreach ($cacDong as $d) {
            $r = array();
            foreach ($tieuDe as $the) {
                $r[] = $d[$the];
            }
            $hang[] = $r;
        }
        return $this->taoXlsx($hang);
    }

    private function nhap($duongDan)
    {
        return (new Bh01NhapExcel())->nhap($duongDan, 'HSTH01BH.xlsx', '37470', $this->ky(), 'tester');
    }

    /** @test */
    public function nhap_thanh_cong_tao_ban_nguon_excel()
    {
        // Thu tu tep cố tình KHONG theo muc/ngay: STT phai theo dung thu tu dong trong tep.
        $kq = $this->nhap($this->tep(array(
            $this->dong(array('HO_TEN' => 'Zeta', 'MA_LOAI_KCB' => '03')),
            $this->dong(array('HO_TEN' => 'Alpha', 'MA_LOAI_KCB' => '01')),
            $this->dong(array('HO_TEN' => 'Mid', 'MA_LOAI_KCB' => '02')),
        )));

        $ban = Bh01Ban::find($kq['ban']->id);
        $this->assertSame('excel', $ban->nguon);
        $this->assertSame('HSTH01BH.xlsx', $ban->ten_tep_nguon);
        $this->assertSame(3, $ban->so_dong);
        $this->assertSame(1, $ban->so_ban);
        $this->assertSame(array(), $kq['cot_thua']);

        $dong = Bh01Dong::where('ban_id', $ban->id)->orderBy('stt')->get();
        $this->assertEquals(array(1, 2, 3), $dong->pluck('stt')->all());
        $this->assertSame(array('Zeta', 'Alpha', 'Mid'), $dong->pluck('ho_ten')->all());
        $this->assertNull($dong[0]->ma_lk);

        $this->assertSame('BH01/nguon/' . $ban->ma_ban . '.xlsx', $ban->duong_dan_tep_nguon);
        Storage::disk('exportTt12')->assertExists('BH01/nguon/' . $ban->ma_ban . '.xlsx');
    }

    /** @test */
    public function nhap_hai_lan_cung_tep_ra_hai_ban()
    {
        $tep = $this->tep(array($this->dong()));

        $a = $this->nhap($tep)['ban'];
        $b = $this->nhap($tep)['ban'];

        $this->assertSame(1, $a->so_ban);
        $this->assertSame(2, $b->so_ban);
        $this->assertNotSame($a->ma_ban, $b->ma_ban);
        Storage::disk('exportTt12')->assertExists('BH01/nguon/' . $a->ma_ban . '.xlsx');
        Storage::disk('exportTt12')->assertExists('BH01/nguon/' . $b->ma_ban . '.xlsx');
    }

    /** @test */
    public function chung_day_so_voi_ban_xml3176()
    {
        (new Bh01GhiBan())->ghi('37470', $this->ky(), 'tester', function ($nhanLo) {
            $nhanLo(array(array(
                'du_lieu' => \App\Services\Tt12\Bh01\Bh01ChuanHoa::dong((object) array(
                    'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000', 'gioi_tinh' => '1',
                    'ma_the_bhyt' => 'DN4010000000001', 'ma_benh_chinh' => 'E11',
                    'ngay_vao' => '202610010800', 'ngay_ra' => '202610010900',
                    'ma_cskcb' => '37470', 'nam_qt' => '2026', 'thang_qt' => '10', 'ma_loai_kcb' => '01',
                    't_tongchi_bv' => 100, 't_tongchi_bh' => 80, 't_bhtt' => 70,
                    't_bncct' => 10, 't_bntt' => 20, 't_nguonkhac' => 0,
                )),
                'ma_lk' => 'LK1', 'vi_tri' => 'x',
            )));
        });

        $ban = $this->nhap($this->tep(array($this->dong())))['ban'];

        $this->assertSame(2, $ban->so_ban);
        $this->assertSame('excel', Bh01Ban::find($ban->id)->nguon);
    }

    /** @test */
    public function tep_loi_thi_nem_va_khong_luu_gi()
    {
        $tep = $this->tep(array($this->dong(), $this->dong(array('THANG_QT' => '09'))));

        try {
            $this->nhap($tep);
            $this->fail('Phai nem Bh01KhongTaoDuocException');
        } catch (Bh01KhongTaoDuocException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame(0, Bh01Ban::count());
        $this->assertSame(array(), Storage::disk('exportTt12')->allFiles('BH01/nguon'));
    }
}
