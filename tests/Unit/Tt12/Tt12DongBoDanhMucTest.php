<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Services\Tt12\Tt12DongBoDanhMuc;

class Tt12DongBoDanhMucTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
        $this->dungBangDanhMucTt12();
    }

    private function hoSo(array $cacDuLieu, array $ghiDe = array())
    {
        $hoSo = Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => count($cacDuLieu), 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
            'is_signed' => true, 'ma_ket_qua' => '200', 'ma_gd' => 'GD1',
        ), $ghiDe));

        foreach ($cacDuLieu as $i => $duLieu) {
            Tt12Dong::create(array(
                'ho_so_id' => $hoSo->id, 'stt' => $i + 1, 'du_lieu' => $duLieu,
            ));
        }

        return $hoSo;
    }

    private function dong(array $ghiDe = array())
    {
        return array_merge(array(
            'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
            'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
            'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
        ), $ghiDe);
    }

    /** @test */
    public function ghi_duoc_dong_sang_bang_danh_muc()
    {
        $hoSo = $this->hoSo(array($this->dong(), $this->dong(array('MA_KHOA' => 'K02'))));

        $soDong = (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $this->assertSame(2, $soDong);
        $this->assertSame(2, DB::table('department_bed_catalogs')->count());

        $ban = DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')->first();

        $this->assertSame('Khám bệnh', $ban->ten_khoa);
        $this->assertSame('3', (string) $ban->ban_kham);
        $this->assertSame('20260101', $ban->tu_ngay);
        $this->assertSame('01929', $ban->ma_cskcb);
    }

    /** @test */
    public function STT_khong_duoc_ghi_sang_danh_muc()
    {
        $hoSo = $this->hoSo(array($this->dong()));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $cot = array_keys((array) DB::table('department_bed_catalogs')->first());

        $this->assertNotContains('stt', $cot);
    }

    /** @test */
    public function hai_dong_cu_va_moi_cung_ma_ton_tai_SONG_SONG()
    {
        // Day la test chung minh viec noi khoa duy nhat o Task 3 that su co tac dung.
        // Voi khoa cu (ma_khoa, ma_cskcb) hai dong nay se de len nhau va chi con mot.
        $hoSo = $this->hoSo(array(
            $this->dong(array('MA_KHOA' => 'K01', 'BAN_KHAM' => '3',
                              'TU_NGAY' => '20250101', 'DEN_NGAY' => '20251231')),
            $this->dong(array('MA_KHOA' => 'K01', 'BAN_KHAM' => '5',
                              'TU_NGAY' => '20260101', 'DEN_NGAY' => '')),
        ));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $cac = DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')
            ->orderBy('tu_ngay')->get();

        $this->assertCount(2, $cac, 'Ca dong cu lan dong moi deu phai ton tai');
        $this->assertSame('20251231', $cac[0]->den_ngay);
        $this->assertSame('5', (string) $cac[1]->ban_kham);
    }

    /** @test */
    public function dong_bo_lai_cung_ho_so_khong_sinh_ban_ghi_trung()
    {
        $hoSo = $this->hoSo(array($this->dong()));
        $dongBo = new Tt12DongBoDanhMuc();

        $dongBo->dongBo($hoSo);
        $dongBo->dongBo($hoSo->fresh());

        $this->assertSame(1, DB::table('department_bed_catalogs')->count());
    }

    /** @test */
    public function dong_bo_lai_CAP_NHAT_gia_tri_thay_doi()
    {
        $hoSo = $this->hoSo(array($this->dong(array('TEN_KHOA' => 'Khám bệnh'))));
        $dongBo = new Tt12DongBoDanhMuc();
        $dongBo->dongBo($hoSo);

        Tt12Dong::where('ho_so_id', $hoSo->id)->update(array(
            'du_lieu' => json_encode($this->dong(array('TEN_KHOA' => 'Khám bệnh mới'))),
        ));

        $dongBo->dongBo($hoSo->fresh());

        $this->assertSame(
            'Khám bệnh mới',
            DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')->value('ten_khoa')
        );
    }

    /** @test */
    public function ghi_lai_dau_vet_dong_bo_tren_ho_so()
    {
        $hoSo = $this->hoSo(array($this->dong(), $this->dong(array('MA_KHOA' => 'K02'))));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $hoSo = $hoSo->fresh();

        $this->assertNotNull($hoSo->dong_bo_at);
        $this->assertSame(2, (int) $hoSo->dong_bo_so_dong);
    }

    /** @test */
    public function ho_so_chua_duoc_tiep_nhan_thi_KHONG_dong_bo()
    {
        // Danh muc dung de kiem XML3176 phai dung bang thu BHXH da nhan, vi giam dinh se
        // so voi chinh ban do.
        $hoSo = $this->hoSo(array($this->dong()), array('ma_ket_qua' => '500'));

        $soDong = (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $this->assertSame(0, $soDong);
        $this->assertSame(0, DB::table('department_bed_catalogs')->count());
        $this->assertNull($hoSo->fresh()->dong_bo_at);
    }

    /** @test */
    public function o_excel_trong_ghi_thanh_NULL_chu_khong_phai_chuoi_rong()
    {
        // Do THAT tren cong ngay 2026-08-26: ho so duoc cong tiep nhan (maKetQua 200) nhung
        // buoc dong bo ngay sau do nem
        //   SQLSTATE[22007]: Incorrect integer value: '' for column 'ban_kham'
        // vi ban_kham/giuong_* deu la int(11) NULL, ma o Excel trong duoc chep thang sang
        // dang chuoi rong. MySQL che do nghiem ngat tu choi '' cho cot so.
        //
        // KHANG DINH GIA TRI, khong khang dinh "khong nem": bo test chay tren SQLite von de
        // dai voi kieu du lieu nen no se nhan '' ma khong keu gi - test kieu do se xanh gia
        // tren chinh cai loi da xay ra that.
        $hoSo = $this->hoSo(array($this->dong(array(
            'BAN_KHAM' => '', 'GIUONG_PD' => '', 'GIUONG_TK' => '',
            'GIUONG_HSTC' => '', 'GIUONG_HSCC' => '', 'DEN_NGAY' => '',
        ))));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $dong = DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')->first();

        $this->assertNotNull($dong, 'khong ghi duoc dong nao');

        foreach (array('ban_kham', 'giuong_pd', 'giuong_tk', 'giuong_hstc', 'giuong_hscc', 'den_ngay') as $cot) {
            $this->assertNull($dong->$cot, $cot . ': o trong phai thanh NULL, khong phai chuoi rong');
        }
    }

    /** @test */
    public function o_co_gia_tri_van_giu_nguyen_khong_bi_bien_thanh_NULL()
    {
        // Mat khac cua phep sua tren: chi o RONG moi thanh NULL. Bien ca so 0 thanh NULL la
        // mat du lieu - "0 giuong" va "khong khai" la hai chuyen khac nhau voi co quan giam
        // dinh.
        $hoSo = $this->hoSo(array($this->dong(array('BAN_KHAM' => '3', 'GIUONG_PD' => '0'))));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $dong = DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')->first();

        $this->assertSame('3', (string) $dong->ban_kham);
        $this->assertSame('0', (string) $dong->giuong_pd, 'so 0 KHONG duoc bien thanh NULL');
    }

    /** @test */
    public function o_chi_co_khoang_trang_cung_thanh_NULL()
    {
        $hoSo = $this->hoSo(array($this->dong(array('BAN_KHAM' => '   '))));

        (new Tt12DongBoDanhMuc())->dongBo($hoSo);

        $this->assertNull(
            DB::table('department_bed_catalogs')->where('ma_khoa', 'K01')->first()->ban_kham
        );
    }
}
