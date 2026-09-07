<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Jobs\CheckCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CheckCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /**
     * Dung mot ho so CT03 HOP LE, roi de $chiTiet ghi de len.
     *
     * NEN DAY DU 18 TRUONG BAT BUOC: cac test o day khang dinh SO LOI CHINH XAC (1 loi, 3
     * loi...), tuc y cua chung la "ho so hop le TRU dung mot truong". Neu nen thieu san vai
     * truong thi moi lan siet danh sach bat buoc, con so do nhay len va ca loat test do -
     * dot 2026-09-07 them muoi truong da lam do bon test o day cung mot luc.
     *
     * Muon mot truong THIEU thi truyen '' cho no: hoSoCt03(['ho_ten' => '']).
     *
     * Ten khoa la TEN COT (chu thuong) chu khong phai ten the XML - ham nay ghi thang vao
     * bang ctdt_ct03, khong di qua duong nap.
     */
    private function hoSoCt03(array $chiTiet, array $ghiDeHoSo = [])
    {
        $chiTiet = array_merge([
            'ma_yte'    => 'YT001',
            'ma_bhxh'   => '0123456789',
            'ma_khoa'   => 'K01',
            'ho_ten'    => 'Nguyen Van Test',
            'ngay_sinh' => '19950914',
            'gioi_tinh' => '1',
            'dia_chi'   => 'Ha Noi',
            'ngay_vao'  => '201912121200',
            'ngay_ra'   => '201912180001',
            'ma_the'    => 'DN1',

            // Muoi truong them o dot 2026-09-07 - xem docblock CtdtTruongBatBuoc.
            'pp_dieutri'         => 'Dieu tri noi khoa',
            'chan_doan'          => 'U ac tinh o dai trang(C18.9)',
            'benhicd10_id'       => 'C18.9',
            'tenbenhicd10'       => 'U ac tinh o dai trang',
            'ngay_chung_tu'      => '20260907',
            'thu_truong_dvi'     => 'Pham Cam Phuong',
            'ten_truongkhoa'     => 'Pham Van Dung',
            'ma_cchn_truongkhoa' => '004929/HNO-GPHN',
            'loai_giayto'        => '1',
            'nghe_nghiep'        => 'Khong xac dinh',
        ], $chiTiet);

        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ], $ghiDeHoSo));

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        CtdtCt03::create(array_merge(['chung_tu_id' => $chungTu->id], $chiTiet));

        return $hoSo->fresh();
    }

    private function chay($maHoSo = 'YT001')
    {
        (new CheckCtdtJob($maHoSo))->handle();
    }

    /** @test */
    public function ho_so_hop_le_khong_sinh_loi_va_so_loi_bang_khong()
    {
        $this->hoSoCt03([
            'ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ma_khoa' => 'K01',
            'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '19950914', 'gioi_tinh' => '1',
            'dia_chi' => 'Ha Noi',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function ghi_loi_kem_ho_so_id_va_chung_tu_id()
    {
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ma_khoa' => 'K01',
            'ho_ten' => '', 'ngay_sinh' => '19950914', 'gioi_tinh' => '1', 'dia_chi' => 'Ha Noi',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();

        $loi = CtdtLoi::first();

        $this->assertNotNull($loi);
        $this->assertSame('CTDT001', $loi->ma_loi);
        $this->assertSame('HO_TEN', $loi->ten_truong);
        // ho_so_id PHAI co: CtdtLuuHoSo::xoaHoSoCu() xoa ctdt_loi theo ho_so_id. Ban ghi
        // loi khong co ho_so_id se song sot qua lan nap lai.
        $this->assertNotNull($loi->ho_so_id);
        $this->assertNotNull($loi->chung_tu_id);
    }

    /** @test */
    public function so_loi_chi_dem_muc_chan()
    {
        // Thieu ho ten (chan) + co TEKT sai 0/1 (canh bao) + gioi tinh sai (chan) = 3 ban
        // ghi loi nhung so_loi phai la 2. so_loi la con so quyet dinh ho so co duoc gui hay
        // khong; dem ca canh bao vao se chan nham nhung ho so hop le.
        //
        // Truoc day ca nay dung "thieu ma the" lam nguon canh bao. MA_THE nay khong con sinh
        // loi nao (rat nhieu benh nhan khong co the BHYT), nen doi sang TEKT - nguon canh bao
        // con lai duy nhat o muc truong.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ma_khoa' => 'K01',
            'ho_ten' => '', 'ngay_sinh' => '19950914', 'dia_chi' => 'Ha Noi',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001',
            'gioi_tinh' => '9', 'tekt' => '7']);

        $this->chay();

        $this->assertSame(3, CtdtLoi::count());
        $this->assertSame(2, (int) CtdtHoSo::first()->so_loi);
        $this->assertSame(1, CtdtLoi::where('muc_do', 'canh_bao')->count());
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_loi()
    {
        // Job phai tu idempotent: hang doi co the giao lai sau khi that bai giua chung.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ma_khoa' => 'K01',
            'ho_ten' => '', 'ngay_sinh' => '19950914', 'gioi_tinh' => '1', 'dia_chi' => 'Ha Noi',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->chay();
        $this->chay();

        $this->assertSame(1, CtdtLoi::count());
        $this->assertSame(1, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function sua_du_lieu_roi_chay_lai_thi_loi_cu_bien_mat()
    {
        $hoSo = $this->hoSoCt03(['ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ma_khoa' => 'K01',
            'ho_ten' => '', 'ngay_sinh' => '19950914', 'gioi_tinh' => '1', 'dia_chi' => 'Ha Noi',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->assertSame(1, CtdtLoi::count());

        CtdtCt03::first()->update(['ho_ten' => 'Nguyen Van Test']);
        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function truyen_ma_co_so_cua_ho_so_xuong_bo_kiem()
    {
        // Quy tac CTDT007 doi chieu MACSKCB trong chung tu voi ma co so cua HO SO. Truyen
        // nham gia tri o day thi quy tac do bat nham hoac khong bat gi.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'GBT-1', 'dich_vu' => 'GBT', 'loai_hs' => '60',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'GIAYBAOTU',
            'ma_chung_tu' => 'GBT-1', 'noi_dung_goc' => '<GIAYBAOTU/>',
        ]);

        \App\Models\BHYT\Ctdt\CtdtGiayBaoTu::create([
            'chung_tu_id' => $chungTu->id, 'ma_gbt' => 'GBT-1',
            'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '20220101',
            'ngay_tv' => '202510070200', 'ma_the' => 'DN1',
            'macskcb' => '37470',
        ]);

        (new CheckCtdtJob('GBT-1'))->handle();

        $this->assertSame(1, CtdtLoi::where('ma_loi', 'CTDT007')->count());
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        // Job co the nam cho trong hang doi rat lau; giua luc do ho so co the da bi xoa.
        // Nem o day chi lam job that bai va thu lai ba lan cho cung mot ket qua.
        $this->chay('KHONG_TON_TAI');

        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function chung_tu_khong_co_ban_ghi_chi_tiet_thi_bo_qua()
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function het_luot_thu_thi_ghi_lai_dau_vet_tren_ho_so()
    {
        // Job het ba luot thu roi roi vao failed_jobs, va ho so o lai checked_at = null
        // VINH VIEN. Cau SQL dem hang doi khong phat hien duoc ca nay vi hang doi van rong.
        // Tu Giai doan 4, mot ho so "chua kiem" la mot ho so khong bao gio gui duoc.
        $this->hoSoCt03([
            'ma_yte' => 'YT001', 'ma_bhxh' => '0123456789', 'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1',
        ]);

        (new CheckCtdtJob('YT001'))->failed(new \Exception('CSDL mat ket noi'));

        $hoSo = CtdtHoSo::first();

        $this->assertNotEmpty($hoSo->import_error, 'Phai de lai dau vet doc duoc tren man hinh');
        $this->assertContains('CSDL mat ket noi', (string) $hoSo->import_error);
        $this->assertNull($hoSo->checked_at, 'Khong duoc gia vo la da kiem');
    }

    /** @test */
    public function failed_voi_ho_so_khong_ton_tai_thi_khong_nem()
    {
        (new CheckCtdtJob('KHONG_TON_TAI'))->failed(new \Exception('loi gi do'));

        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function loai_la_trong_CSDL_thi_bo_qua_khong_nem()
    {
        // Registry co the bi thu hep sau khi du lieu da duoc ghi. Nem o day thi mot loai
        // da go se lam moi lan kiem cua moi ho so cu deu that bai.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'LOAI_DA_GO',
            'noi_dung_goc' => '<LOAI_DA_GO/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }
}
