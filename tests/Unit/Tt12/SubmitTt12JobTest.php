<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Tests\Support\FakeTt12SubmitService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12LichSuGui;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Jobs\SubmitTt12Job;

class SubmitTt12JobTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
        $this->dungBangDanhMucTt12();

        Storage::fake('exportTt12');

        config(['organization.tt12.submit_enabled' => true]);
    }

    private function hoSo(array $ghiDe = array())
    {
        $duongDan = 'MAU_01/202608/TT12_MAU_01_01929_20260825_001.xml';

        Storage::disk('exportTt12')->put($duongDan, '<HSDANHMUC><CHUKYDONVI><Signature/></CHUKYDONVI></HSDANHMUC>');

        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
            'is_signed' => true, 'signed_at' => '2026-08-25 10:05:00',
            'duong_dan_da_ky' => $duongDan,
        ), $ghiDe));
    }

    /** Mot dong MAU_01 that, de buoc dong bo co gi de ghi sang bang danh muc */
    private function motDong(Tt12HoSo $hoSo)
    {
        return Tt12Dong::create(array(
            'ho_so_id' => $hoSo->id,
            'stt'      => 1,
            'du_lieu'  => array(
                'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Kham benh',
                'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
                'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
                'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            ),
        ));
    }

    /** @test */
    public function gui_thanh_cong_thi_dong_bo_luon_sang_bang_danh_muc()
    {
        // Khop noi SubmitTt12Job -> Tt12DongBoDanhMuc truoc day chua tung chay: ho so mau
        // cua bo test nay KHONG co dong nao, nen dongBo() di qua mot vong chunk RONG. Xoa
        // han ba dong goi dongBo() trong job thi ca chin test van xanh - tuc chung khong
        // kiem gi ca ve buoc dong bo.
        $hoSo = $this->hoSo();
        $this->motDong($hoSo);

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle(new FakeTt12SubmitService());

        $this->assertSame(1, DB::table('department_bed_catalogs')->count(),
            'Cong tiep nhan xong phai day dong sang bang danh muc');
        $this->assertNotNull($hoSo->fresh()->dong_bo_at);
        $this->assertSame(1, (int) $hoSo->fresh()->dong_bo_so_dong);

        $ban = DB::table('department_bed_catalogs')->first();

        $this->assertSame('K01', $ban->ma_khoa);
        $this->assertSame('01929', $ban->ma_cskcb);
    }

    /** @test */
    public function cong_tu_choi_thi_KHONG_dong_bo()
    {
        // Danh muc la nguon cho buoc kiem XML3176 va giam dinh doi chieu voi chinh ban BHXH
        // da nhan. Ghi khi cong chua nhan la kiem theo mot ban khong ton tai.
        $hoSo = $this->hoSo();
        $this->motDong($hoSo);

        $gui = new FakeTt12SubmitService();
        $gui->ketQua = array(
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: Lỗi nội dung file XML', 'nguyen_van' => '{"maKetQua":"205"}',
        );

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertSame(0, DB::table('department_bed_catalogs')->count());
        $this->assertNull($hoSo->fresh()->dong_bo_at);
    }

    /** @test */
    public function gui_thanh_cong_ghi_ma_gd_va_lich_su()
    {
        $hoSo = $this->hoSo();
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $hoSo = $hoSo->fresh();

        $this->assertSame('200', $hoSo->ma_ket_qua);
        $this->assertSame('DANHMUC01_01929', $hoSo->ma_gd);
        $this->assertSame('20260825083000', $hoSo->thoi_gian_tiep_nhan);
        $this->assertNotNull($hoSo->submitted_at);
        $this->assertNull($hoSo->submit_error);

        $this->assertSame(1, Tt12LichSuGui::where('ho_so_id', $hoSo->id)->count());
    }

    /** @test */
    public function gui_dung_noi_dung_tep_da_ky_va_dung_mau()
    {
        $hoSo = $this->hoSo();
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertContains('<Signature/>', $gui->xmlNhanDuoc, 'Phai gui tep DA KY');
        $this->assertSame('MAU_01', $gui->mauNhanDuoc);
        $this->assertSame('01929', $gui->maCskcbNhanDuoc);
    }

    /** @test */
    public function ho_so_chua_ky_thi_khong_gui()
    {
        $hoSo = $this->hoSo(array('is_signed' => false));
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertSame(0, $gui->soLanGoi);
    }

    /** @test */
    public function ho_so_da_tiep_nhan_thi_khong_gui_lai()
    {
        // Mot cu bam nham se sinh hai maGiaoDich cho cung mot danh muc.
        $hoSo = $this->hoSo(array('ma_ket_qua' => '200', 'ma_gd' => 'GD_CU'));
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertSame('GD_CU', $hoSo->fresh()->ma_gd);
    }

    /** @test */
    public function gui_hong_lan_truoc_thi_duoc_gui_lai()
    {
        $hoSo = $this->hoSo(array('ma_ket_qua' => '500'));
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertSame(1, $gui->soLanGoi);
        $this->assertSame('200', $hoSo->fresh()->ma_ket_qua);
    }

    /** @test */
    public function ma_khong_thanh_cong_van_duoc_ghi_lai_kem_lich_su()
    {
        $hoSo = $this->hoSo();
        $gui = new FakeTt12SubmitService();
        $gui->ketQua = array(
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: Lỗi nội dung file XML', 'nguyen_van' => '{"maKetQua":"205"}',
        );

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $hoSo = $hoSo->fresh();

        $this->assertSame('205', $hoSo->ma_ket_qua);
        $this->assertContains('205', $hoSo->submitted_message);
        $this->assertSame(1, Tt12LichSuGui::where('ho_so_id', $hoSo->id)->count());
    }

    /** @test */
    public function tep_da_ky_bi_mat_thi_bao_loi_chu_khong_gui_rong()
    {
        $hoSo = $this->hoSo();
        Storage::disk('exportTt12')->delete($hoSo->duong_dan_da_ky);

        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertContains('Không đọc được tệp đã ký', $hoSo->fresh()->submit_error);
    }

    /** @test */
    public function loi_mang_thi_NEM_de_hang_doi_thu_lai()
    {
        $hoSo = $this->hoSo();
        $gui = new FakeTt12SubmitService();
        $gui->nem = new \Exception('Lỗi gọi cổng BHXH: timeout');

        $this->expectException(\Exception::class);

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);
    }

    /** @test */
    public function chuc_nang_gui_dang_tat_thi_khong_ghi_submit_error()
    {
        config(['organization.tt12.submit_enabled' => false]);

        $hoSo = $this->hoSo();
        $gui = new FakeTt12SubmitService();

        (new SubmitTt12Job($hoSo->ma_ho_so))->handle($gui);

        $hoSo = $hoSo->fresh();

        // Khong duoc "xanh vi tinh co": nhanh gui THANH CONG cung cho submit_error =
        // null, nen phai khang dinh them nhung thu CHI dung khi job that su DUNG som -
        // dich vu gui khong duoc goi, va ho so khong mang bat ky dau vet gui nao.
        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNull($hoSo->submit_error);
        $this->assertNull($hoSo->ma_gd);
        $this->assertNull($hoSo->ma_ket_qua);
        $this->assertNull($hoSo->submitted_at);
        $this->assertSame(0, Tt12LichSuGui::where('ho_so_id', $hoSo->id)->count());
    }
}
