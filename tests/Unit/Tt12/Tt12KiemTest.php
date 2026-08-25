<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\Tt12\Kiem\LuatO;
use App\Services\Tt12\Kiem\LuatDong;
use App\Services\Tt12\Kiem\LuatHoSo;
use App\Services\Tt12\Kiem\LuatRiengMau;

/**
 * Kiem cac luat o dang HAM THUAN - khong cham CSDL.
 *
 * Tach nhu vay de moi luat kiem duoc rieng. Tt12Kiem (lop ghep cac luat va ghi CSDL)
 * duoc kiem o Tt12KiemGhiTest.
 */
class Tt12KiemTest extends TestCase
{
    private function lop($mau)
    {
        return Tt12MauRegistry::cho($mau);
    }

    private function maLoi(array $loi)
    {
        return array_map(function ($l) { return $l->maLoi(); }, $loi);
    }

    private function dongMau01(array $ghiDe = array())
    {
        return array_merge(array(
            'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
            'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
            'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
        ), $ghiDe);
    }

    /** @test */
    public function dong_hop_le_khong_sinh_loi_nao()
    {
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(), 1);

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function thieu_truong_bat_buoc_bi_bat()
    {
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('MA_KHOA' => '')), 1);

        $this->assertContains('THIEU_BAT_BUOC', $this->maLoi($loi));
        $this->assertSame('MA_KHOA', $loi[0]->cot());
        $this->assertSame(1, $loi[0]->sttDong());
    }

    /** @test */
    public function truong_kieu_so_ma_co_chu_bi_bat()
    {
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('BAN_KHAM' => '3 bàn')), 1);

        $this->assertContains('SAI_KIEU_SO', $this->maLoi($loi));
    }

    /** @test */
    public function truong_khong_bat_buoc_de_rong_thi_khong_kiem_kieu()
    {
        // Rong la hop le voi truong khong bat buoc. Kiem kieu tren chuoi rong se bao
        // "khong phai so" o moi dong khong dien - hang nghin loi gia.
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('BAN_KHAM' => '')), 1);

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function vuot_do_dai_toi_da_bi_bat()
    {
        $loi = LuatO::kiem(
            $this->lop('MAU_01'),
            $this->dongMau01(array('MA_KHOA' => str_repeat('K', 51))),
            1
        );

        $this->assertContains('QUA_DAI', $this->maLoi($loi));
    }

    /** @test */
    public function max_null_thi_khong_gioi_han_do_dai()
    {
        // TEN_KHOA khai max = null (tai lieu ghi 'n'). Ap mot gioi han tu nghi o day se
        // chan nhung ten khoa dai that.
        $loi = LuatO::kiem(
            $this->lop('MAU_01'),
            $this->dongMau01(array('TEN_KHOA' => str_repeat('X', 5000))),
            1
        );

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function ngay_khong_dung_8_ky_tu_bi_bat()
    {
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('TU_NGAY' => '01/01/2026')), 1);

        $this->assertContains('SAI_DINH_DANG_NGAY', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_khong_co_that_bi_bat()
    {
        // 20260231 dung 8 ky tu va toan chu so, nhung 31 thang 2 khong ton tai. Kiem
        // bang bieu thuc chinh quy don thuan se cho qua.
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('TU_NGAY' => '20260231')), 1);

        $this->assertContains('NGAY_KHONG_CO_THAT', $this->maLoi($loi));
    }

    /** @test */
    public function den_ngay_truoc_tu_ngay_bi_bat()
    {
        $loi = LuatDong::kiem(
            $this->lop('MAU_01'),
            $this->dongMau01(array('TU_NGAY' => '20260301', 'DEN_NGAY' => '20260101')),
            1,
            '01929'
        );

        $this->assertContains('DEN_TRUOC_TU', $this->maLoi($loi));
    }

    /** @test */
    public function den_ngay_bang_tu_ngay_la_hop_le()
    {
        // Mot dong ap dung dung mot ngay la hop le - vi du bang gia chi co hieu luc mot
        // ngay roi bi thay. Chan no la chan mot truong hop that.
        $loi = LuatDong::kiem(
            $this->lop('MAU_01'),
            $this->dongMau01(array('TU_NGAY' => '20260101', 'DEN_NGAY' => '20260101')),
            1,
            '01929'
        );

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function ma_cskcb_trong_dong_lech_voi_ho_so_bi_bat()
    {
        $loi = LuatDong::kiem(
            $this->lop('MAU_01'),
            $this->dongMau01(array('MA_CSKCB' => '37470')),
            1,
            '01929'
        );

        $this->assertContains('MA_CSKCB_LECH', $this->maLoi($loi));
    }

    /** @test */
    public function stt_trung_nhau_trong_mot_ho_so_bi_bat()
    {
        $loi = LuatHoSo::kiem($this->lop('MAU_01'), array(
            array('stt' => 1, 'du_lieu' => $this->dongMau01(array('MA_KHOA' => 'K01'))),
            array('stt' => 1, 'du_lieu' => $this->dongMau01(array('MA_KHOA' => 'K02'))),
        ));

        $this->assertContains('STT_TRUNG', $this->maLoi($loi));
    }

    /** @test */
    public function cap_dong_cu_moi_hop_le_thi_khong_bao_loi()
    {
        // Dong cu: co DEN_NGAY. Dong moi: DEN_NGAY rong, TU_NGAY sau DEN_NGAY cua dong cu.
        $loi = LuatHoSo::kiem($this->lop('MAU_01'), array(
            array('stt' => 1, 'du_lieu' => $this->dongMau01(
                array('MA_KHOA' => 'K01', 'TU_NGAY' => '20250101', 'DEN_NGAY' => '20251231'))),
            array('stt' => 2, 'du_lieu' => $this->dongMau01(
                array('MA_KHOA' => 'K01', 'TU_NGAY' => '20260101', 'DEN_NGAY' => ''))),
        ));

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function hai_dong_cung_ma_cung_de_ngo_den_ngay_bi_bat()
    {
        $loi = LuatHoSo::kiem($this->lop('MAU_01'), array(
            array('stt' => 1, 'du_lieu' => $this->dongMau01(array('MA_KHOA' => 'K01', 'DEN_NGAY' => ''))),
            array('stt' => 2, 'du_lieu' => $this->dongMau01(array('MA_KHOA' => 'K01', 'DEN_NGAY' => ''))),
        ));

        $this->assertContains('HAI_DONG_CUNG_MO', $this->maLoi($loi));
    }

    /** @test */
    public function dong_moi_bat_dau_truoc_khi_dong_cu_ket_thuc_bi_bat()
    {
        $loi = LuatHoSo::kiem($this->lop('MAU_01'), array(
            array('stt' => 1, 'du_lieu' => $this->dongMau01(
                array('MA_KHOA' => 'K01', 'TU_NGAY' => '20250101', 'DEN_NGAY' => '20261231'))),
            array('stt' => 2, 'du_lieu' => $this->dongMau01(
                array('MA_KHOA' => 'K01', 'TU_NGAY' => '20260101', 'DEN_NGAY' => ''))),
        ));

        $this->assertContains('HIEU_LUC_CHONG_LAN', $this->maLoi($loi));
    }

    /** @test */
    public function mau_03_duoc_lieu_thieu_ten_khoa_hoc_bi_bat()
    {
        $duLieu = array('LOAI_THUOC' => '4', 'TEN_KHOA_HOC' => '', 'NGUON_GOC' => 'Việt Nam',
                        'PP_CHEBIEN' => 'Sao vàng', 'TLHH_CB' => '10', 'TLHH_BQ' => '12');

        $loi = LuatRiengMau::kiem($this->lop('MAU_03'), $duLieu, 1, array());

        $this->assertContains('THIEU_TRUONG_DUOC_LIEU', $this->maLoi($loi));
    }

    /** @test */
    public function mau_03_tan_duoc_khong_doi_hoi_truong_duoc_lieu()
    {
        $duLieu = array('LOAI_THUOC' => '1', 'TEN_KHOA_HOC' => '', 'NGUON_GOC' => '',
                        'PP_CHEBIEN' => '', 'TLHH_CB' => '', 'TLHH_BQ' => '');

        $loi = LuatRiengMau::kiem($this->lop('MAU_03'), $duLieu, 1, array());

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function mau_05_co_thuoc_phong_xa_ma_thieu_ma_thuoc_bi_bat()
    {
        $con = array(array('ma_thuoc' => '', 'don_gia_thuoc' => '120000', 'ten_thuoc' => 'Tc-99m'));

        $loi = LuatRiengMau::kiem($this->lop('MAU_05'), array(), 1, $con);

        $this->assertContains('THIEU_TRUONG_THUOCPX', $this->maLoi($loi));
    }

    /** @test */
    public function mau_05_khong_co_thuoc_phong_xa_thi_khong_doi_hoi_gi()
    {
        $loi = LuatRiengMau::kiem($this->lop('MAU_05'), array(), 1, array());

        $this->assertSame(array(), $loi);
    }

    /** @test */
    public function so_am_bi_bat()
    {
        // Don gia am di thang ra cong. Chan tai day chu khong doi cong tra 205.
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('GIUONG_PD' => '-5')), 1);

        $this->assertContains('SO_AM', $this->maLoi($loi));
        $this->assertSame('GIUONG_PD', $loi[0]->cot());
        $this->assertTrue($loi[0]->laLoi(), 'So am phai CHAN ky, khong phai canh bao');
    }

    /** @test */
    public function so_am_khac_voi_sai_kieu_so()
    {
        // '-5' la so hop le ve dinh dang; chi mot loi duy nhat, khong duoc bao kem
        // SAI_KIEU_SO cho cung mot o.
        $loi = LuatO::kiem($this->lop('MAU_01'), $this->dongMau01(array('GIUONG_PD' => '-5')), 1);

        $this->assertNotContains('SAI_KIEU_SO', $this->maLoi($loi));
    }

    /** @test */
    public function den_ngay_hd_truoc_tu_ngay_hd_la_CANH_BAO_chu_khong_chan_ky()
    {
        // Thoi han hop dong nguoc khong lam XML sai cau truc va cong khong tu choi vi no.
        // Chan ky vi mot du lieu dang nghi ngo la chan ca danh muc thuoc cua benh vien.
        $duLieu = array(
            'STT' => '1', 'MA_THUOC' => 'T001', 'TEN_THUOC' => 'Paracetamol',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            'TU_NGAY_HD' => '20260601', 'DEN_NGAY_HD' => '20260101',
        );

        $loi = LuatDong::kiem($this->lop('MAU_03'), $duLieu, 1, '01929');

        $this->assertSame(array('DEN_HD_TRUOC_TU_HD'), $this->maLoi($loi));
        $this->assertFalse($loi[0]->laLoi(), 'Phai la canh bao, khong chan ky');
        $this->assertSame('DEN_NGAY_HD', $loi[0]->cot());
    }

    /** @test */
    public function thoi_han_hop_dong_thuan_thi_khong_canh_bao()
    {
        $duLieu = array(
            'STT' => '1', 'MA_THUOC' => 'T001', 'TEN_THUOC' => 'Paracetamol',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
            'TU_NGAY_HD' => '20260101', 'DEN_NGAY_HD' => '20261231',
        );

        $this->assertSame(array(), LuatDong::kiem($this->lop('MAU_03'), $duLieu, 1, '01929'));
    }

    /** @test */
    public function hai_dong_cung_ma_cung_TU_NGAY_cho_ket_qua_ON_DINH()
    {
        // usort() cua PHP KHONG on dinh. Hai dong cung ma cung TU_NGAY thi thu tu sau sap
        // xep khong xac dinh, va HIEU_LUC_CHONG_LAN bao luc co luc khong cho CUNG MOT TEP.
        // Day khong phai bien hiem: cap dong cu/moi cung ngay chinh la tep TT12 khuyen
        // khich gui khi co so sua nham ngay. Ma day la luat CHAN KY.
        $mot = array('stt' => 1, 'du_lieu' => array(
            'MA_KHOA' => 'K01', 'TU_NGAY' => '20260101', 'DEN_NGAY' => '20260101',
        ));

        $hai = array('stt' => 2, 'du_lieu' => array(
            'MA_KHOA' => 'K01', 'TU_NGAY' => '20260101', 'DEN_NGAY' => '',
        ));

        $xuoi = LuatHoSo::kiem($this->lop('MAU_01'), array($mot, $hai));
        $nguoc = LuatHoSo::kiem($this->lop('MAU_01'), array($hai, $mot));

        $moTa = function (array $loi) {
            return array_map(function ($l) { return $l->maLoi() . '|' . $l->moTa(); }, $loi);
        };

        $this->assertSame(
            $moTa($xuoi),
            $moTa($nguoc),
            'Cung mot tap dong phai cho cung mot ket qua bat ke thu tu doc'
        );
    }
}
