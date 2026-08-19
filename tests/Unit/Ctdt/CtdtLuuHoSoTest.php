<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\CtdtLuuHoSo;
use App\Services\Ctdt\Loi\TheGocLechException;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Models\BHYT\Ctdt\CtdtCt03;
use App\Models\BHYT\Ctdt\CtdtCt04;

class CtdtLuuHoSoTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtLuuHoSo */
    private $luu;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->luu = new CtdtLuuHoSo();
    }

    private function moTaHoSo(array $chungTu, array $ghiDe = [])
    {
        return array_merge([
            'ma_ho_so'       => 'YT001',
            'id_goi_xml'     => 'Id-abc',
            'dich_vu'        => 'CT2025',
            'loai_hs'        => '39',
            'macskcb'        => '01929',
            'ngay_lap'       => '20251101',
            'so_luong_ho_so' => 1,
            'imported_by'    => 'nguoinap',
            'duong_dan_goc'  => null,
            'chung_tu'       => $chungTu,
        ], $ghiDe);
    }

    private function chungTuTu($loaiHoSo, array $truong)
    {
        $xml = $this->goiCt2025([[$this->chungTu($loaiHoSo, $truong)]]);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        // 'noi_dung' cua danhSachHoSo() gio la chuoi XML chua parse; CtdtLuuHoSo::luu() van
        // can \SimpleXMLElement nen phai phanTichChungTu() truoc khi tra ve.
        return CtdtGoiParser::phanTichChungTu($ds[0], 1);
    }

    /** @test */
    public function ghi_ho_so_chung_tu_va_chi_tiet()
    {
        $ct = $this->chungTuTu('CT03', [
            'MA_YTE'    => 'YT001',
            'MA_THE'    => 'DN123',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
            'CHAN_DOAN' => 'Dau bung',
        ]);

        $hoSo = $this->luu->luu($this->moTaHoSo($ct));

        $this->assertSame('YT001', $hoSo->ma_ho_so);
        $this->assertSame('CT2025', $hoSo->dich_vu);
        $this->assertSame('01929', $hoSo->macskcb);
        $this->assertSame(1, (int) $hoSo->so_chung_tu);
        $this->assertNotNull($hoSo->imported_at);
        $this->assertSame('nguoinap', $hoSo->imported_by);

        $this->assertSame(1, CtdtChungTu::count());
        $ctdt = CtdtChungTu::first();
        $this->assertSame('CT03', $ctdt->loai_ho_so);
        $this->assertSame('YT001', $ctdt->ma_chung_tu);

        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame('Dau bung', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function cot_rut_gon_duoc_sao_len_ctdt_chung_tu()
    {
        // Khong co cot rut gon thi man danh sach phai UNION 9 bang chi tiet, vi chin loai
        // dat ten truong khac nhau.
        $ct = $this->chungTuTu('CT03', [
            'MA_YTE'    => 'YT001',
            'MA_THE'    => 'DN123',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
        ]);

        $this->luu->luu($this->moTaHoSo($ct));

        $ctdt = CtdtChungTu::first();
        $this->assertSame('DN123', $ctdt->ma_the);
        $this->assertSame('Nguyen Van Test', $ctdt->ho_ten);
        $this->assertSame('19950914', $ctdt->ngay_sinh);
        $this->assertSame('201912121200', $ctdt->ngay_vao);
        $this->assertSame('201912180001', $ctdt->ngay_ra);
    }

    /** @test */
    public function giu_nguyen_xml_goc_de_doi_chieu()
    {
        // Khi cong bao 205 (fileBase64Str khong hop le), thu duy nhat giup doi chieu la
        // noi dung nguyen van da gui.
        $ct = $this->chungTuTu('CT03', ['MA_YTE' => 'YT001']);

        $this->luu->luu($this->moTaHoSo($ct));

        $this->assertContains('<MA_YTE>YT001</MA_YTE>', CtdtChungTu::first()->noi_dung_goc);
    }

    /** @test */
    public function nhieu_loai_chung_tu_trong_mot_ho_so()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $hoSo = $this->luu->luu($this->moTaHoSo(CtdtGoiParser::phanTichChungTu($ds[0], 1)));

        $this->assertSame(2, (int) $hoSo->so_chung_tu);
        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame(1, CtdtCt04::count());
    }

    /** @test */
    public function nap_lai_ghi_de_sach_chung_tu_cu()
    {
        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', [
            'MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Chan doan CU',
        ])));

        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', [
            'MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Chan doan MOI',
        ])));

        $this->assertSame(1, CtdtHoSo::count(), 'Van chi mot ho so');
        $this->assertSame(1, CtdtChungTu::count(), 'Chung tu cu phai bi xoa');
        $this->assertSame(1, CtdtCt03::count(), 'Chi tiet cu phai bi xoa');
        $this->assertSame('Chan doan MOI', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function nap_lai_xoa_ca_loi_cu()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        CtdtLoi::create([
            'ho_so_id'    => $hoSo->id,
            'chung_tu_id' => CtdtChungTu::first()->id,
            'ma_loi'      => 'CTDT002',
            'mo_ta'       => 'Sai dinh dang ngay',
            'muc_do'      => 'chan',
        ]);

        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Loi cu tro toi chung tu da bi xoa. Giu lai thi lan kiem truoc se hien len tab chi
        // tiet cua mot chung tu KHAC - MySQL cap lai dung nhung AUTO_INCREMENT vua giai phong.
        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function nap_lai_reset_trang_thai_ky_va_gui()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $hoSo->update([
            'is_signed'           => true,
            'sign_method'         => 'usb_token',
            'signed_at'           => '2026-08-19 10:00:00',
            'submitted_at'        => '2026-08-19 10:05:00',
            'ma_gd'               => 'HS_123456',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260819100500',
            'so_loi'              => 3,
            'checked_at'          => '2026-08-19 09:00:00',
        ]);

        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui cu
        // noi ve mot ban khac. Giu lai la noi doi voi nguoi doc man danh sach.
        $this->assertFalse((bool) $moi->is_signed);
        $this->assertNull($moi->sign_method);
        $this->assertNull($moi->signed_at);
        $this->assertNull($moi->submitted_at);
        $this->assertNull($moi->ma_gd);
        $this->assertNull($moi->ma_ket_qua);
        $this->assertNull($moi->thoi_gian_tiep_nhan);
        $this->assertNull($moi->checked_at);
        $this->assertSame(0, (int) $moi->so_loi);
    }

    /** @test */
    public function nap_lai_giu_dau_vet_lan_gui_truoc_trong_lich_su()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $hoSo->update([
            'ma_gd'               => 'HS_123456',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260819100500',
        ]);

        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Xoa sach dau vet gui la xoa kha nang giai trinh khi BHXH doi soat.
        $this->assertContains('HS_123456', $moi->lich_su_gui);
        $this->assertContains('200', $moi->lich_su_gui);
    }

    /** @test */
    public function chua_tung_gui_thi_khong_ghi_dong_lich_su_rong()
    {
        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));
        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $this->assertNull($moi->lich_su_gui);
    }

    /** @test */
    public function the_goc_lech_thi_nem_va_khong_ghi_gi()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['the_goc_ghi_de' => 'CT04']
        );
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        try {
            $this->luu->luu($this->moTaHoSo(CtdtGoiParser::phanTichChungTu($ds[0], 1)));
            $this->fail('Phai nem TheGocLechException');
        } catch (TheGocLechException $e) {
            // Mong doi
        }

        // luu() KHONG tu mo transaction - CtdtImporter bao ngoai. Nhung ban ghi chi tiet
        // khong duoc ghi truoc khi phat hien lech, vi kiem the goc dung TRUOC khi ghi.
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function loai_la_thi_nem()
    {
        $chungTu = [[
            'loai_ho_so' => 'CT99',
            'noi_dung'   => simplexml_load_string('<CT99/>'),
        ]];

        $this->expectException(\App\Services\Ctdt\Loi\LoaiKhongBietException::class);

        $this->luu->luu($this->moTaHoSo($chungTu));
    }

    /** @test */
    public function the_khong_khai_trong_truong_thi_bo_qua_khong_nem()
    {
        // BHXH them the moi vao dac ta truoc khi ta kip cap nhat la chuyen se xay ra. Nem
        // o day se lam ca ho so hong chi vi mot the thua ma ta chua biet den.
        $ct = $this->chungTuTu('CT03', ['MA_YTE' => 'YT001', 'THE_MOI_TINH' => 'gi do']);

        $hoSo = $this->luu->luu($this->moTaHoSo($ct));

        $this->assertSame('YT001', $hoSo->ma_ho_so);
        $this->assertSame(1, CtdtCt03::count());
    }

    /** @test */
    public function giay_bao_tu_luu_dung_bang_va_dich_vu()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25', 'HO_TEN' => 'Nguyen Van Test']);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GBT');

        $hoSo = $this->luu->luu($this->moTaHoSo(CtdtGoiParser::phanTichChungTu($ds[0], 1), [
            'ma_ho_so' => '00002.GBT.XXXX.25',
            'dich_vu'  => 'GBT',
            'loai_hs'  => '60',
            'ngay_lap' => null,
        ]));

        $this->assertSame('GBT', $hoSo->dich_vu);
        $this->assertSame('60', $hoSo->loai_hs);
        $this->assertSame(1, \App\Models\BHYT\Ctdt\CtdtGiayBaoTu::count());
        $this->assertSame('Nguyen Van Test', CtdtChungTu::first()->ho_ten);
    }
}
