<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Exports\CtdtDanhSachExport;
use App\Exports\CtdtLoiExport;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Tep xuat phai khop DUNG man hinh. Moi ben tu dung bo loc thi them mot o loc ma quen ben
 * kia se lam tep xuat khac han, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu
 * tung dong voi ban cua BHXH.
 */
class CtdtXuatExcelTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /**
     * Dung mot ho so THAT trong SQLite bo nho, kem N ban ghi ctdt_loi that su - khong phai
     * gia lap quan he trong bo nho. Bat bien can kiem (N loi -> N dong) chi lo ra khi map()
     * chay tren mot Eloquent model duoc nap kem quan he 'loi' dung nhu query() cua
     * CtdtLoiExport lam, khong phai tren mot object dung tay.
     *
     * @return CtdtHoSo da nap kem 'loi' va 'chungTu', dung het cho map()
     */
    private function hoSoThatCoLoi(int $soLoi)
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => $soLoi,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'ho_ten' => 'NGUYEN VAN A', 'ma_the' => 'DN4010112345678',
            'noi_dung_goc' => '<CT03/>',
        ]);

        for ($i = 1; $i <= $soLoi; $i++) {
            CtdtLoi::create([
                'ho_so_id' => $hoSo->id, 'chung_tu_id' => $chungTu->id,
                'ma_loi' => 'CTDT00' . $i, 'ten_truong' => 'TRUONG_' . $i,
                'mo_ta' => 'Loi so ' . $i,
                // Xen ke chan/canh_bao: bat bien phai dung du khong phu thuoc muc do.
                'muc_do' => $i % 2 === 0 ? 'canh_bao' : 'chan',
            ]);
        }

        return CtdtHoSo::with(['loi', 'chungTu' => function ($q) {
            $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten', 'loai_ho_so')->orderBy('id');
        }])->find($hoSo->id);
    }

    private function hoSo(array $ghiDe = [])
    {
        $hoSo = new CtdtHoSo();

        foreach (array_merge([
            'ma_ho_so'   => 'YT001',
            'dich_vu'    => 'CT2025',
            'macskcb'    => '01001',
            'checked_at' => '2026-08-20 08:00:00',
            'so_loi'     => 0,
            'is_signed'  => true,
            'ma_ket_qua' => '200',
            'ma_gd'      => 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
        ], $ghiDe) as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function so_cot_tieu_de_khop_so_o_moi_dong()
    {
        // Lech mot cot la moi gia tri tu do tro di nam duoi sai tieu de - va bang van trong
        // hoan toan binh thuong. Day la kieu loi khong ai phat hien bang mat.
        $xuat = new CtdtDanhSachExport(CtdtHoSo::query());

        $this->assertCount(
            count($xuat->headings()),
            $xuat->map($this->hoSo()),
            'So o moi dong phai bang so tieu de'
        );
    }

    /** @test */
    public function cccd_va_ma_bhxh_ra_duoc_den_o_excel()
    {
        // DI QUA query() THAT chu khong tu goi CtdtHoSo::with(...): map() doc hai gia tri
        // nay tu quan he chungTu, va quan he do duoc nap bang mot select() liet ke TUNG
        // COT. Cat mot cot khoi select do se lam o Excel trong hoan toan - tieu de van
        // dung, so cot van khop, khong test nao do. Chi mot test chay dung query() cua lop
        // Export moi bat duoc.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT777', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT777',
            'ho_ten' => 'NGUYEN VAN A', 'ma_the' => 'DN4010112345678',
            'so_cccd' => '001095012345', 'ma_bhxh' => '0123456789',
            'noi_dung_goc' => '<CT03/>',
        ]);

        $xuat = new CtdtDanhSachExport(CtdtHoSo::where('ma_ho_so', 'YT777'));
        $dong = $xuat->map($xuat->query()->first());

        // So sanh NGHIEM NGAT - xem chu thich ve loi 0 == chuoi cua PHP7 o test ben duoi.
        $this->assertContains('001095012345', $dong, 'Thieu so CCCD trong dong xuat', false, false, true);
        $this->assertContains('0123456789', $dong, 'Thieu ma BHXH trong dong xuat', false, false, true);

        // Dung o duoi dung tieu de: lech mot cot thi hai gia tri tren van "co mat" nhung
        // nam duoi tieu de khac.
        $viTri = array_search('Số CCCD', $xuat->headings(), true);
        $this->assertSame('001095012345', $dong[$viTri], 'So CCCD phai nam dung cot cua no');
        $this->assertSame('0123456789', $dong[$viTri + 1], 'Ma BHXH phai nam ngay sau So CCCD');
    }

    /** @test */
    public function nhan_trang_thai_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        // Go lai chuoi tieng Viet o day nghia la doi nhan tren man hinh se khong doi nhan
        // trong tep xuat - hai nguon su that cho cung mot khai niem.
        $hoSo = $this->hoSo(['ma_ket_qua' => null, 'is_signed' => false, 'signed_error' => 'USB token bi rut']);

        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($hoSo);

        // Bon tham so cuoi ep so sanh NGHIEM NGAT (===): assertContains mac dinh dung so
        // sanh long (==), va mang $dong co nhieu phan tu int(0) (so_chung_tu, so_loi). Trong
        // PHP7, 0 == '<bat ky chuoi khong phai so nao>' la TRUE - nen ban so sanh long se
        // luon xanh du nhan co dung hay khong.
        $this->assertContains(
            CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::KY_HONG),
            $dong,
            'Phai dung dung nhan cua CtdtTrangThaiGui',
            false,
            false,
            true
        );
    }

    /** @test */
    public function ma_gd_dai_khong_bi_cat_trong_tep_xuat()
    {
        // ma_gd la cot doi soat quan trong nhat voi BHXH. Cong tra ve 52 ky tu that.
        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($this->hoSo());

        // So sanh NGHIEM NGAT - xem chu thich o test ben tren ve loi 0 == chuoi cua PHP7.
        $this->assertContains(
            'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
            $dong,
            '',
            false,
            false,
            true
        );
    }

    /** @test */
    public function duyet_theo_lo_chu_khong_nap_ca_bang()
    {
        // Bang nay phinh theo thoi gian, may chu moi gioi han PHP 128MB. FromCollection se
        // nap het vao bo nho.
        $this->assertInstanceOf(
            \Maatwebsite\Excel\Concerns\FromQuery::class,
            new CtdtDanhSachExport(CtdtHoSo::query())
        );
    }

    /** @test */
    public function bang_loi_xuat_ca_muc_chan_lan_canh_bao()
    {
        // Chi xuat muc chan la giau mat nua cong viec cua nguoi nhap lieu: canh bao hom nay
        // la loi chan cua dot siet sau. Nguoi doc phai thay ca hai va tu quyet uu tien.
        $nguon = file_get_contents(base_path('app/Exports/CtdtLoiExport.php'));

        $this->assertNotContains("where('muc_do', 'chan')", $nguon,
            'Khong duoc loc bo canh bao');
        $this->assertContains('Mức độ', $nguon,
            'Phai co cot Muc do de nguoi doc tu quyet uu tien');
    }

    /** @test */
    public function bang_loi_giu_ma_ho_so_o_moi_dong()
    {
        // Mot dong loi khong co ma ho so la mot dong khong ai di sua duoc. Bang nay duoc in
        // ra dua cho nguoi nhap lieu, tach hoan toan khoi man hinh.
        $xuat = new \App\Exports\CtdtLoiExport(\App\Models\BHYT\Ctdt\CtdtHoSo::query());

        $this->assertContains('Mã hồ sơ', $xuat->headings());
    }

    /** @test */
    public function mot_ho_so_ba_loi_sinh_dung_ba_dong()
    {
        // Bat bien cot loi cua ca tinh nang: N loi phai ra N dong, khong phai mot dong gop
        // chung. Neu ai do doi map() thanh gop het loi vao mot dong, day la phep kiem DUY
        // NHAT bat duoc - hai test tren chi soi ma nguon va headings(), khong goi map().
        $hoSo = $this->hoSoThatCoLoi(3);
        $xuat = new CtdtLoiExport(CtdtHoSo::query());

        $dong = $xuat->map($hoSo);

        $this->assertCount(3, $dong, 'Ho so co 3 loi phai ra dung 3 dong, khong duoc gop lai');

        foreach ($dong as $mot) {
            $this->assertCount(
                count($xuat->headings()),
                $mot,
                'So o moi dong loi phai bang so tieu de'
            );

            // So sanh NGHIEM NGAT bang tay (khong dung assertContains): $mot co phan tu
            // int(0)-like o vi tri STT, va PHP7 coi 0 == '<chuoi bat ky>' la true - ban
            // long se xanh du sai. assertSame tren mot vi tri CU THE khong dinh bay nay.
            $this->assertSame(
                'YT001',
                $mot[1],
                'Moi dong loi phai giu ma ho so - dong khong co ma ho so la dong khong ai sua duoc'
            );
        }
    }

    /** @test */
    public function nhat_ky_phan_biet_nguoi_bam_voi_lenh_nen()
    {
        // Cau hoi van hanh dau tien khi co su co: "dem qua LENH NEN gui bao nhieu ho so".
        // Khong phan biet duoc nguon thi cau hoi do khong tra loi duoc.
        $xuat = new \App\Exports\CtdtNhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertContains('Nguồn', $xuat->headings());
    }

    /** @test */
    public function nhat_ky_bat_buoc_co_khoang_ngay()
    {
        // Bang nay chi tang, khong bao gio giam. Xuat khong gioi han khoang la mot truy van
        // toan bang tren may chu gioi han PHP 128MB.
        $nguon = file_get_contents(base_path('app/Exports/CtdtNhatKyGuiExport.php'));

        $this->assertContains('whereBetween', $nguon,
            'Phai gioi han theo khoang ngay, khong duoc xuat toan bang');
    }

    /** @test */
    public function nhat_ky_phan_biet_nguon_qua_map()
    {
        // Dung ban ghi CtdtLichSuGui THAT trong SQLite bo nho, kem ca hai gia tri nguon, de
        // chung minh cot "Nguon" doc dung truong nguon chu khong suy tu nguoi_gui rong/khong.
        $hoSo1 = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);
        $hoSo2 = CtdtHoSo::create([
            'ma_ho_so' => 'YT002', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => 0,
        ]);

        $manHinh = \App\Models\BHYT\Ctdt\CtdtLichSuGui::create([
            'ho_so_id' => $hoSo1->id, 'ma_ho_so' => 'YT001', 'nguoi_gui' => 'tracnn',
            'nguon' => \App\Models\BHYT\Ctdt\CtdtLichSuGui::NGUON_MAN_HINH,
            'ma_gd' => 'GD001', 'ma_ket_qua' => '00', 'thoi_gian_tiep_nhan' => '2026-08-10 08:00:00',
            'thanh_cong' => true, 'thong_diep' => 'OK',
        ]);
        $lenhNen = \App\Models\BHYT\Ctdt\CtdtLichSuGui::create([
            'ho_so_id' => $hoSo2->id, 'ma_ho_so' => 'YT002', 'nguoi_gui' => null,
            'nguon' => \App\Models\BHYT\Ctdt\CtdtLichSuGui::NGUON_CONSOLE,
            'ma_gd' => 'GD002', 'ma_ket_qua' => '01', 'thoi_gian_tiep_nhan' => '2026-08-10 09:00:00',
            'thanh_cong' => false, 'thong_diep' => 'Loi',
        ]);

        $xuat = new \App\Exports\CtdtNhatKyGuiExport('2026-08-01', '2026-08-31');

        $dongManHinh = $xuat->map($manHinh);
        $dongLenhNen = $xuat->map($lenhNen);

        $this->assertCount(count($xuat->headings()), $dongManHinh, 'So o moi dong phai bang so tieu de');
        $this->assertCount(count($xuat->headings()), $dongLenhNen, 'So o moi dong phai bang so tieu de');

        $this->assertSame('Người bấm', $dongManHinh[3], 'Nguon man_hinh phai ra chu Nguoi bam');
        $this->assertSame('Lệnh nền', $dongLenhNen[3], 'Nguon console phai ra chu Lenh nen');
    }
}
