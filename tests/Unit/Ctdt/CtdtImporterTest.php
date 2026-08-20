<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use Illuminate\Support\Facades\Queue;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;
use App\Models\BHYT\Ctdt\CtdtCt04;
use App\Models\BHYT\Ctdt\CtdtLoi;

class CtdtImporterTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->importer = new CtdtImporter();
        Queue::fake();
    }

    /** @test */
    public function nhap_mot_ho_so_ct2025()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
        ]]);

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(1, $kq->soThanhCong);
        $this->assertSame(['YT001'], $kq->dsMaHoSo);
        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame('39', CtdtHoSo::first()->loai_hs);
    }

    /** @test */
    public function nhap_giay_bao_tu_va_giay_chung_sinh()
    {
        config(['organization.BHYT.ma_cskcb' => '01013']);
        $kqGbt = $this->importer->nhapTuChuoi($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $kqGcs = $this->importer->nhapTuChuoi(
            $this->goiGcs(['MA_GCS' => 'GCS-1']),
            ['macskcb' => '01929']
        );

        $this->assertTrue($kqGbt->thanhCong, (string) $kqGbt->lyDoThatBai);
        $this->assertTrue($kqGcs->thanhCong, (string) $kqGcs->lyDoThatBai);

        $this->assertSame('60', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->loai_hs);
        $this->assertSame('61', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->loai_hs);
    }

    /** @test */
    public function giay_chung_sinh_lay_macskcb_tu_tuy_chon()
    {
        // Goi GCS khong mang ma co so o bat ky the nao.
        $kq = $this->importer->nhapTuChuoi(
            $this->goiGcs(['MA_GCS' => 'GCS-1']),
            ['macskcb' => '01929']
        );

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame('01929', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function giay_chung_sinh_lui_ve_cau_hinh_don_vi_khi_khong_truyen_tuy_chon()
    {
        config(['organization.BHYT.ma_cskcb' => '01013']);

        $kq = $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-2']));

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame('01013', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function can_ca_ba_nguon_ma_co_so_thi_tu_choi_ca_tep()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $kq = $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-3']));

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('ma co so', $kq->lyDoThatBai);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function xml_hong_thi_that_bai_som_khong_nem_ra_ngoai()
    {
        $kq = $this->importer->nhapTuChuoi('<HSCHUNGTU><chua dong');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
        $this->assertSame([], $kq->ketQua);
    }

    /** @test */
    public function the_goc_la_thi_that_bai_som()
    {
        $kq = $this->importer->nhapTuChuoi('<GIAMDINHHS/>');

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('GIAMDINHHS', $kq->lyDoThatBai);
    }

    /** @test */
    public function thieu_macskcb_o_goi_ct2025_thi_that_bai_som()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['macskcb' => null]
        );

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function mot_ho_so_hong_KHONG_keo_cac_ho_so_con_lai_xuong()
    {
        // Day la nguyen tac quan trong nhat cua importer. Ho so #2 co the goc lech.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><NGAYLAP>20251101</NGAYLAP><SOLUONGHOSO>3</SOLUONGHOSO>'
            . '<DANHSACHHOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT001</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT04><MA_YTE>YT002</MA_YTE></CT04>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT003</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '</DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong, 'Ca tep khong duoc bao thanh cong khi co ho so hong');
        $this->assertSame(2, $kq->soThanhCong);
        $this->assertSame(1, $kq->soThatBai);
        $this->assertSame(['YT001', 'YT003'], $kq->dsMaHoSo);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);

        $this->assertSame(2, CtdtHoSo::count(), 'Hai ho so lanh phai duoc ghi');
    }

    /** @test */
    public function ho_so_hong_khong_de_lai_du_lieu_do_dang()
    {
        // Ho so co hai chung tu, cai thu hai the goc lech. Transaction phai quay lui SACH.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><SOLUONGHOSO>1</SOLUONGHOSO><DANHSACHHOSO><HOSO>'
            . '<FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT001</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO>'
            . '<FILEHOSO><LOAIHOSO>CT04</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT06><MA_BHXH>x</MA_BHXH></CT06>') . '</NOIDUNGFILE></FILEHOSO>'
            . '</HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
        $this->assertSame(0, CtdtChungTu::count());
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function mot_NOIDUNGFILE_base64_hong_o_giua_tep_khong_keo_ca_tep_xuong()
    {
        // Tai hien I-1: dac ta muc 5.3 xep "duyet tung HOSO, moi HOSO mot transaction
        // rieng" TRUOC "voi moi FILEHOSO: giai base64, parse". Ban cu nhac buoc parse len
        // truoc, nen mot NOIDUNGFILE hong o ho so giua tep lam CA TEP bi tu choi ngay tu
        // dau, truoc khi vong lap per-ho-so kip chay.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><NGAYLAP>20251101</NGAYLAP><SOLUONGHOSO>3</SOLUONGHOSO>'
            . '<DANHSACHHOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT001</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><chua dong the') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT003</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '</DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertSame(2, $kq->soThanhCong);
        $this->assertSame(1, $kq->soThatBai);
        $this->assertSame(['YT001', 'YT003'], $kq->dsMaHoSo);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);

        $this->assertSame(2, CtdtHoSo::count(), 'Hai ho so lanh phai xuong CSDL');
        $this->assertNotNull(CtdtHoSo::where('ma_ho_so', 'YT001')->first());
        $this->assertNotNull(CtdtHoSo::where('ma_ho_so', 'YT003')->first());
    }

    /** @test */
    public function nap_lai_ghi_de_qua_importer()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'CU']),
        ]]));

        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'MOI']),
        ]]));

        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame('MOI', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function ghi_nhan_nguoi_nap_va_duong_dan_goc()
    {
        $this->importer->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]),
            ['imported_by' => 'nguoinap', 'duong_dan_goc' => 'inbox/goi-1.xml']
        );

        $hoSo = CtdtHoSo::first();
        $this->assertSame('nguoinap', $hoSo->imported_by);
        $this->assertSame('inbox/goi-1.xml', $hoSo->duong_dan_goc);
    }

    /** @test */
    public function so_luong_khai_bao_nhieu_hon_thuc_te_thi_tu_choi_ca_tep()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['so_luong_ho_so' => 3]
        );

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('SOLUONGHOSO', $kq->lyDoThatBai);
    }

    /** @test */
    public function ho_so_rong_de_de_KHONG_xoa_sach_du_lieu_cu_va_bao_that_bai()
    {
        // Tai hien C-1: mot goi voi <HOSO/> rong, cung Id, cung chi so voi mot lan nap
        // truoc do khong co MA_YTE (nen khoa lui ve id_goi#chi_so). Truoc khi sua, chuoi
        // hong nay lam CtdtMaHoSo::cua([], ...) van tra ve DUNG khoa cua ban ghi cu, roi
        // CtdtLuuHoSo::xoaHoSoCu() xoa sach du lieu cu va ghi lai voi so_chung_tu = 0 - va
        // BAO THANH CONG.
        $lanDau = $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]], ['id' => 'Id-goi-mau', 'macskcb' => '01929']));

        $this->assertTrue($lanDau->thanhCong, (string) $lanDau->lyDoThatBai);
        $this->assertSame(['Id-goi-mau#1'], $lanDau->dsMaHoSo);
        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame(1, CtdtChungTu::count());
        $this->assertSame(1, CtdtCt04::count());

        // Goi thu hai: cung Id, HOSO thu nhat rong (khong co FILEHOSO nao) - vd loi truyen
        // tep hoac loi phia BHXH.
        $xmlHong = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-goi-mau"><NGAYLAP>20251101</NGAYLAP><SOLUONGHOSO>1</SOLUONGHOSO>'
            . '<DANHSACHHOSO><HOSO/></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $lanHai = $this->importer->nhapTuChuoi($xmlHong);

        $this->assertFalse($lanHai->thanhCong, 'Ho so rong phai bi tu choi, khong duoc bao thanh cong');

        // Du lieu cu phai con NGUYEN - khong bi xoa boi ho so rong ghi de.
        $this->assertSame(1, CtdtHoSo::count(), 'Ban ghi ho so cu phai con');
        $this->assertSame(1, CtdtChungTu::count(), 'Chung tu cu phai con');
        $this->assertSame(1, CtdtCt04::count(), 'Chi tiet cu phai con');
        $this->assertSame('CT-1', CtdtCt04::first()->ma_ct);
    }

    /** @test */
    public function macskcb_dai_qua_5_ky_tu_thi_tu_choi_ca_tep()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['macskcb' => null]
        );

        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '0192912345']);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function ma_ho_so_suy_ra_dai_qua_100_ky_tu_thi_ho_so_do_that_bai()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => str_repeat('Y', 150)])]],
            ['macskcb' => '01929']
        );

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function loi_lap_trinh_KHONG_bi_nuot_thanh_ho_so_hong()
    {
        // Chi loi mang dau hieu CtdtLoiNap moi duoc ghi thanh "ho so nay hong". Loi khac
        // phai noi len de nguoi van hanh thay - nuot no di la cach chac chan de mot bug
        // song hang thang duoi vo boc "vai ho so khong nap duoc".
        $luuHong = new class extends \App\Services\Ctdt\CtdtLuuHoSo {
            public function luu(array $hoSo)
            {
                throw new \LogicException('bug that su');
            }
        };

        $importer = new CtdtImporter($luuHong);

        $this->expectException(\LogicException::class);

        $importer->nhapTuChuoi($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]));
    }

    /** @test */
    public function nap_xong_thi_day_job_kiem_loi()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        Queue::assertPushed(\App\Jobs\CheckCtdtJob::class);
    }

    /** @test */
    public function job_kiem_di_dung_hang_doi_lay_tu_cau_hinh()
    {
        // KHONG co luoi nao canh ten hang doi truoc dot sua nay: doi ->onQueue(...) thanh
        // mot ten bia thi CA 315 test van xanh. Worker se nghe mot hang doi con job vao
        // hang doi khac, IM LANG, va vi ho so chua kiem cung co so_loi = 0 nen man danh
        // sach se bao moi ho so deu sach mai mai.
        //
        // Dat mot ten KHAC mac dinh de chung minh ma that su DOC cau hinh chu khong go
        // cung - va de test khong phu thuoc config/organization.php cua tung may.
        config(['organization.chung_tu_dien_tu.queue_name' => 'HangDoiThuNghiem']);

        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        Queue::assertPushed(\App\Jobs\CheckCtdtJob::class, function ($job) {
            return $job->queue === 'HangDoiThuNghiem';
        });
    }

    /** @test */
    public function moi_ho_so_mot_job_rieng()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT001'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT002'])],
        ]));

        Queue::assertPushed(\App\Jobs\CheckCtdtJob::class, 2);
    }

    /** @test */
    public function ho_so_hong_KHONG_day_job_kiem()
    {
        // Ho so hong khong co gi de kiem, va job se chi tim thay mot ma ho so khong ton tai.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><SOLUONGHOSO>1</SOLUONGHOSO><DANHSACHHOSO><HOSO>'
            . '<FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT04><MA_YTE>YT001</MA_YTE></CT04>') . '</NOIDUNGFILE></FILEHOSO>'
            . '</HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $this->importer->nhapTuChuoi($xml);

        Queue::assertNotPushed(\App\Jobs\CheckCtdtJob::class);
    }

    /** @test */
    public function nap_KHONG_chay_bo_kiem_ngay_chi_xep_hang()
    {
        // Ho so nay thieu HO_TEN/NGAY_SINH/NGAY_VAO/NGAY_RA - neu bo kiem chay ngay
        // trong luc nap thi chac chan sinh loi. Test nap phai kiem viec NAP, khong phai
        // viec KIEM: voi QUEUE_DRIVER=sync (phpunit.xml), thieu Queue::fake() se lam job
        // chay that ngay tai day, va mot thay doi o bang quy tac (Task 1-3) se lam do
        // nhung test nap khong lien quan gi toi no.
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::where('ma_ho_so', 'YT001')->value('so_loi'));
    }
}
