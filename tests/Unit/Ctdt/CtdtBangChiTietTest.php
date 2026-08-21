<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;

/**
 * Canh chin bang chi tiet: du cot theo PL02, va GIU NGUYEN ten the ke ca khi khong deu.
 */
class CtdtBangChiTietTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    public function soCotMongDoi()
    {
        return [
            ['ctdt_ct03', 33],
            ['ctdt_ct04', 45],
            ['ctdt_ct06', 24],
            ['ctdt_ct07', 27],
            ['ctdt_dieu_tri_noi_tru', 36],
            ['ctdt_dieu_tri_vo_sinh', 29],
            ['ctdt_suc_khoe_me', 29],
            ['ctdt_giay_bao_tu', 38],
            ['ctdt_giay_chung_sinh', 69],
        ];
    }

    /** @test */
    public function chin_bang_chi_tiet_duoc_tao_du_so_cot()
    {
        foreach ($this->soCotMongDoi() as list($bang, $soCot)) {
            $this->assertTrue(Schema::hasTable($bang), 'Thieu bang ' . $bang);

            // id + chung_tu_id + created_at + updated_at = 4 cot khung
            $thucTe = count(Schema::getColumnListing($bang)) - 4;

            $this->assertSame($soCot, $thucTe,
                $bang . ': mong doi ' . $soCot . ' cot du lieu, thuc te ' . $thucTe);
        }
    }

    /** @test */
    public function ten_the_icd_giu_nguyen_khong_sua_cho_deu()
    {
        // Ba kieu dat ten ICD khac nhau trong cung mot dac ta. Sua cho deu la lam sai
        // anh xa the -> cot, va loi chi lo ra luc gui that bai.
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'benhicd10_id'), 'CT03 phai la benhicd10_id');
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'tenbenhicd10'), 'CT03 phai la tenbenhicd10');

        $this->assertTrue(Schema::hasColumn('ctdt_ct04', 'benh_icd10_id'), 'CT04 phai la benh_icd10_id');
        $this->assertTrue(Schema::hasColumn('ctdt_giay_bao_tu', 'benh_icd10_id'), 'GBT phai la benh_icd10_id');

        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_noi_tru', 'benh_icd10_id'), 'Noi tru phai la benh_icd10_id - XML that dung BENH_ICD10_ID');
        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_vo_sinh', 'benh_icd10_ma'), 'Vo sinh phai la benh_icd10_ma');
        $this->assertTrue(Schema::hasColumn('ctdt_suc_khoe_me', 'benh_icd10_ma'), 'Suc khoe me phai la benh_icd10_ma');
    }

    /** @test */
    public function ten_the_dan_toc_giu_nguyen_khac_biet()
    {
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'ma_dantoc'), 'CT03 phai la ma_dantoc');
        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_noi_tru', 'ma_dan_toc'), 'Noi tru phai la ma_dan_toc');
    }

    /** @test */
    public function ct03_co_cot_ghi_chu_du_xml_mau_khong_co()
    {
        // Muc 9.2 cua PL02 liet ke GHI_CHU nhung XML mau muc 9.1 khong co the nay.
        // Thua mot cot rong thi vo hai, thieu mot cot thi mat du lieu.
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'ghi_chu'));
    }

    /** @test */
    public function giay_chung_sinh_du_ba_nhom_nguoi()
    {
        // NND = nguoi de, MTH = me thay the (mang thai ho), CHA_NND = cha cua nguoi de.
        // Ba nhom nay dung hau to khac nhau cho cung mot khai niem - de nham lan nhat.
        foreach (['hoten_nnd', 'hoten_mth', 'ho_ten_cha_mth', 'so_cccd_cha_nnd'] as $cot) {
            $this->assertTrue(Schema::hasColumn('ctdt_giay_chung_sinh', $cot),
                'ctdt_giay_chung_sinh thieu cot ' . $cot);
        }
    }

    /** @test */
    public function chi_tiet_gan_duoc_vao_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS100', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtCt03::create([
            'chung_tu_id' => $chungTu->id,
            'ma_yte'      => 'YT001',
            'ho_ten'      => 'Nguyen Van Test',
            'ngay_vao'    => '201912121200',
        ]);

        $this->assertSame('YT001', CtdtCt03::first()->ma_yte);
        $this->assertSame('CT03', CtdtCt03::first()->chungTu->loai_ho_so);
    }

    /** @test */
    public function ngay_gio_giu_nguyen_khong_bi_ep_kieu()
    {
        // PL02 khai moi truong la Chuoi ky tu. Ep sang date se lam mat phan phut cua
        // 201912121200 va mat so 0 dau cua '01'.
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS101', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtCt03::create([
            'chung_tu_id' => $chungTu->id,
            'ngay_vao'    => '201912121200',
            'ma_dantoc'   => '01',
        ]);

        $ban = CtdtCt03::first();

        $this->assertSame('201912121200', $ban->ngay_vao);
        $this->assertSame('01', $ban->ma_dantoc);
    }
}
