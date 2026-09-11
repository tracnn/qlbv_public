<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Http\Request;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtSuaXml;
use App\Services\Ctdt\CtdtXepHangKyGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;
use App\Models\BHYT\Ctdt\CtdtLichSuSua;

/**
 * Sua VAN BAN THO cua XML goc.
 *
 * noi_dung_goc duoc base64 THANG vao phong bi roi ky so va POST len cong BHXH. Khac voi
 * duong nap (noi dung do phan mem HIS sinh ra), o day noi dung do NGUOI GO - nen moi test
 * duoi day canh mot duong ma mot chuoi nguoi dung go co the lam hong.
 */
class CtdtSuaXmlTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();

        config([
            'organization.chung_tu_dien_tu.sign_enabled'   => true,
            'organization.chung_tu_dien_tu.submit_enabled' => true,
        ]);
    }

    /** XML CT03 hop le, du 18 truong bat buoc. */
    private function xmlCt03(array $ghiDe = [])
    {
        $truong = array_merge([
            'MA_YTE'             => 'YT001',
            'MA_BHXH'            => '0123456789',
            'MA_KHOA'            => 'K01',
            'HO_TEN'             => 'Nguyen Van Test',
            'NGAY_SINH'          => '19950914',
            'GIOI_TINH'          => '1',
            'DIA_CHI'            => 'Ha Noi',
            'NGAY_VAO'           => '201912121200',
            'NGAY_RA'            => '201912180001',
            'MA_THE'             => 'DN1234567890',
            'PP_DIEUTRI'         => 'Dieu tri noi khoa',
            'CHAN_DOAN'          => 'U ac tinh o dai trang(C18.9)',
            'BENHICD10_ID'       => 'C18.9',
            'TENBENHICD10'       => 'U ac tinh o dai trang',
            'NGAY_CHUNG_TU'      => '20260907',
            'THU_TRUONG_DVI'     => 'Pham Cam Phuong',
            'TEN_TRUONGKHOA'     => 'Pham Van Dung',
            'MA_CCHN_TRUONGKHOA' => '004929/HNO-GPHN',
            'LOAI_GIAYTO'        => '1',
            'NGHE_NGHIEP'        => 'Khong xac dinh',
        ], $ghiDe);

        $ben = '';

        foreach ($truong as $the => $giaTri) {
            $ben .= '<' . $the . '>' . htmlspecialchars((string) $giaTri, ENT_XML1) . '</' . $the . '>';
        }

        return '<CT03>' . $ben . '</CT03>';
    }

    private function dungHoSo(array $ghiDeHoSo = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-09-11 08:00:00', 'so_loi' => 0, 'is_signed' => true,
            'signed_at' => '2026-09-11 08:05:00', 'sign_method' => 'usb_token',
            'duong_dan_da_ky' => 'D:\\XML\\da-ky\\YT001.xml',
        ], $ghiDeHoSo));

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'ho_ten' => 'Nguyen Van Test', 'noi_dung_goc' => $this->xmlCt03(),
        ]);

        CtdtCt03::create(['chung_tu_id' => $chungTu->id, 'ma_yte' => 'YT001',
            'ho_ten' => 'Nguyen Van Test', 'pp_dieutri' => 'Dieu tri noi khoa']);

        return [$hoSo->fresh(), $chungTu->fresh()];
    }

    private function goi($noiDung, $maHoSo = 'YT001', $chungTuId = null, array $them = [])
    {
        if ($chungTuId === null) {
            $hoSo = CtdtHoSo::where('ma_ho_so', $maHoSo)->first();
            $chungTuId = CtdtChungTu::where('ho_so_id', $hoSo->id)->value('id');
        }

        $request = Request::create('/sua-xml', 'POST', array_merge(
            ['noi_dung' => $noiDung], $them
        ));

        return json_decode(
            $this->controller->suaXml($request, $maHoSo, $chungTuId)->getContent(),
            true
        );
    }

    // ------------------------------------------------------------------ sau chot noi dung

    /** @test */
    public function noi_dung_RONG_bi_tu_choi()
    {
        list(, $chungTu) = $this->dungHoSo();

        $this->assertSame(CtdtSuaXml::RONG, CtdtSuaXml::kiem('   ', $chungTu)['ma']);
    }

    /** @test */
    public function noi_dung_VUOT_TRAN_bi_tu_choi()
    {
        list(, $chungTu) = $this->dungHoSo();

        // Do tren du lieu that: ban lon nhat 16.093 ky tu. Tran 256KB rong gap ~16 lan.
        $qua = '<CT03><GHI_CHU>' . str_repeat('x', CtdtSuaXml::TOI_DA_BYTE) . '</GHI_CHU></CT03>';

        $this->assertSame(CtdtSuaXml::QUA_LON, CtdtSuaXml::kiem($qua, $chungTu)['ma']);
    }

    /** @test */
    public function DOCTYPE_bi_chan_DU_XML_hop_le_ve_cu_phap()
    {
        // XXE THAT SU: khong chan thi bo phan giai doc tep cua may chu, nhet noi dung do vao
        // chung tu, roi phan mem KY SO va GUI len cong BHXH. Ro ri du lieu ra ben ngoai.
        list(, $chungTu) = $this->dungHoSo();

        $doc = '<?xml version="1.0"?>'
            . '<!DOCTYPE CT03 [<!ENTITY xxe SYSTEM "file:///c:/windows/win.ini">]>'
            . '<CT03><GHI_CHU>&xxe;</GHI_CHU></CT03>';

        $kq = CtdtSuaXml::kiem($doc, $chungTu);

        $this->assertSame(CtdtSuaXml::CO_DOCTYPE, $kq['ma']);
        $this->assertNull($kq['xml'], 'Khong duoc parse truoc roi moi chan');
    }

    /** @test */
    public function billion_laughs_cung_bi_chan_vi_no_can_DOCTYPE()
    {
        list(, $chungTu) = $this->dungHoSo();

        $bom = '<!DOCTYPE lolz [<!ENTITY lol "lol">'
            . '<!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;">]>'
            . '<CT03><GHI_CHU>&lol2;</GHI_CHU></CT03>';

        $this->assertSame(CtdtSuaXml::CO_DOCTYPE, CtdtSuaXml::kiem($bom, $chungTu)['ma']);
    }

    /** @test */
    public function XML_hong_bi_tu_choi_kem_dong_loi()
    {
        list(, $chungTu) = $this->dungHoSo();

        $kq = CtdtSuaXml::kiem('<CT03><HO_TEN>Thieu the dong</CT03>', $chungTu);

        $this->assertSame(CtdtSuaXml::KHONG_PARSE, $kq['ma']);
        $this->assertNotSame('', $kq['chi_tiet'], 'Phai neu duoc dong loi cho nguoi go');
    }

    /** @test */
    public function KHONG_duoc_doi_loai_bang_cach_doi_the_goc()
    {
        // The goc quyet dinh BANG CHI TIET va TAB. Cho doi thi mot ban ghi CT03 mang noi
        // dung CT04, va du lieu ghi vao bang cua loai CU - sai cho ma khong loi nao.
        list(, $chungTu) = $this->dungHoSo();

        $kq = CtdtSuaXml::kiem('<CT04><HO_TEN>A</HO_TEN></CT04>', $chungTu);

        $this->assertSame(CtdtSuaXml::THE_GOC_LECH, $kq['ma']);
    }

    /** @test */
    public function KHONG_duoc_doi_khoa_nghiep_vu()
    {
        // ma_ho_so duoc suy tu khoa nay LUC NAP va khong doi theo. Doi khoa thi lan nap goi
        // sau se tao ra ho so THU HAI thay vi ghi de - hai ban cung ton tai, khong thong bao
        // nao, chi lo ra luc doi soat.
        list(, $chungTu) = $this->dungHoSo();

        $kq = CtdtSuaXml::kiem($this->xmlCt03(['MA_YTE' => 'YT999']), $chungTu);

        $this->assertSame(CtdtSuaXml::DOI_KHOA, $kq['ma']);
        $this->assertContains('YT001', $kq['chi_tiet']);
        $this->assertContains('YT999', $kq['chi_tiet']);
    }

    /** @test */
    public function noi_dung_hop_le_thi_qua_het_sau_chot()
    {
        list(, $chungTu) = $this->dungHoSo();

        $kq = CtdtSuaXml::kiem($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']), $chungTu);

        $this->assertSame(CtdtSuaXml::OK, $kq['ma']);
        $this->assertInstanceOf(\SimpleXMLElement::class, $kq['xml']);
    }

    /** @test */
    public function moi_ma_tu_choi_deu_co_ly_do_doc_duoc()
    {
        foreach ([CtdtSuaXml::RONG, CtdtSuaXml::QUA_LON, CtdtSuaXml::CO_DOCTYPE,
                  CtdtSuaXml::KHONG_PARSE, CtdtSuaXml::THE_GOC_LECH, CtdtSuaXml::DOI_KHOA,
                  CtdtSuaXml::LOAI_LA] as $ma) {
            $this->assertNotSame('Nội dung XML chưa hợp lệ.', CtdtSuaXml::lyDo($ma),
                $ma . ' phai co ly do rieng, khong duoc roi ve cau chung');
        }
    }

    // ------------------------------------------------------------------ duong ghi

    /** @test */
    public function luu_xong_thi_bang_chi_tiet_duoc_dung_lai_theo_noi_dung_moi()
    {
        // Khong dung lai ban ghi chi tiet thi man hinh hien mot dang, thu gui di la dang
        // khac - va khong ai doi chieu hai cai do bang mat.
        list(, $chungTu) = $this->dungHoSo();

        $kq = $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen', 'HO_TEN' => 'Tran Thi B']));

        $this->assertTrue($kq['thanh_cong'], json_encode($kq, JSON_UNESCAPED_UNICODE));

        $chiTiet = CtdtCt03::where('chung_tu_id', $chungTu->id)->first();

        $this->assertSame('Thai Nguyen', $chiTiet->dia_chi);
        $this->assertSame('Tran Thi B', $chiTiet->ho_ten);
        $this->assertSame(1, CtdtCt03::where('chung_tu_id', $chungTu->id)->count(),
            'Phai XOA ban cu roi dung lai, khong duoc de hai ban ghi chi tiet');
    }

    /** @test */
    public function luu_xong_thi_cot_rut_gon_cung_doi_theo()
    {
        // Cot rut gon la thu man DANH SACH hien. Khong cap nhat thi danh sach hien ten cu
        // trong khi chi tiet da doi.
        list(, $chungTu) = $this->dungHoSo();

        $this->goi($this->xmlCt03(['HO_TEN' => 'Tran Thi B']));

        $this->assertSame('Tran Thi B', $chungTu->fresh()->ho_ten);
    }

    /** @test */
    public function luu_xong_thi_CHU_KY_CU_bi_vo_hieu()
    {
        // Noi dung doi thi chu ky cu khong con noi ve noi dung nay nua. Khong vo hieu thi
        // ho so van hien "Da ky" va co the duoc gui di voi mot chu ky cua ban khac.
        list($hoSo, ) = $this->dungHoSo();

        $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']));

        $moi = $hoSo->fresh();

        $this->assertFalse((bool) $moi->is_signed);
        $this->assertNull($moi->signed_at);
        $this->assertNull($moi->sign_method);
        $this->assertNull($moi->duong_dan_da_ky);
    }

    /** @test */
    public function luu_xong_van_GIU_ma_gd_chu_khong_xoa()
    {
        // Duong NAP LAI xoa ma_gd, va dieu do tao ra cai bay module dang phai canh bao bang
        // mot hop thoai: ho so cong DA nhan lai hien "Chua ky so". O day giu lai thi trang
        // thai van la "Da gui" - dung su that - va nut hien "Ky va gui lai".
        list($hoSo, ) = $this->dungHoSo(['ma_gd' => 'HS_CHUNGTU01929_ABC', 'ma_ket_qua' => '200']);

        $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']), 'YT001', null,
            ['xac_nhan_da_gui' => 1]);

        $moi = $hoSo->fresh();

        $this->assertSame('HS_CHUNGTU01929_ABC', $moi->ma_gd);
        $this->assertSame('200', (string) $moi->ma_ket_qua);
        $this->assertContains('sua XML goc', (string) $moi->lich_su_gui);
    }

    /** @test */
    public function nhat_ky_luu_CA_BAN_TRUOC_de_con_khoi_phuc()
    {
        // Nguoi dung sua VAN BAN THO. Mot lan dan de len toan bo noi dung la mat han ban goc
        // do HIS sinh ra, va noi_dung_goc la ban duy nhat trong CSDL.
        list(, $chungTu) = $this->dungHoSo();
        $truoc = $chungTu->noi_dung_goc;

        $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']));

        $nhatKy = CtdtLichSuSua::first();

        $this->assertNotNull($nhatKy);
        $this->assertSame($truoc, $nhatKy->noi_dung_truoc);
        $this->assertContains('Thai Nguyen', $nhatKy->noi_dung_sau);
        $this->assertSame('YT001', $nhatKy->ma_ho_so);
        $this->assertSame('CT03', $nhatKy->loai_ho_so);
    }

    /** @test */
    public function sua_xong_thi_BO_KIEM_chay_lai_va_so_loi_doi()
    {
        // Sua ma khong kiem lai thi so_loi con la so cua noi dung DA KHONG CON TON TAI.
        list($hoSo, ) = $this->dungHoSo();

        $this->assertSame(0, (int) $hoSo->so_loi);

        $kq = $this->goi($this->xmlCt03(['PP_DIEUTRI' => '']));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertSame(1, $kq['so_loi'], 'Bo PP_DIEUTRI phai sinh mot loi chan');
        $this->assertSame(1, (int) $hoSo->fresh()->so_loi);
    }

    // ------------------------------------------------------------------ chot trang thai

    /** @test */
    public function ho_so_DANG_XU_LY_thi_khong_cho_sua()
    {
        // Job ky co the da doc ban cu va dang gui ban do, trong khi CSDL hien ban moi. Luc
        // doi soat khong ai biet ban nao that su len cong.
        list(, $chungTu) = $this->dungHoSo();
        CtdtXepHangKyGui::giuKhoa('YT001');

        $kq = $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertContains('đang trong lượt ký và gửi', $kq['thong_diep']);
        $this->assertSame($this->xmlCt03(), $chungTu->fresh()->noi_dung_goc, 'Khong duoc ghi gi');
    }

    /** @test */
    public function ho_so_da_co_ma_gd_thi_HOI_XAC_NHAN_truoc()
    {
        list(, $chungTu) = $this->dungHoSo(['ma_gd' => 'HS_ABC']);

        $kq = $this->goi($this->xmlCt03(['DIA_CHI' => 'Thai Nguyen']));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertTrue($kq['can_xac_nhan']);
        $this->assertSame($this->xmlCt03(), $chungTu->fresh()->noi_dung_goc, 'Chua xac nhan thi chua ghi');
    }

    /** @test */
    public function chung_tu_cua_HO_SO_KHAC_khong_sua_duoc_qua_id()
    {
        // Thieu buoc doi chieu thi mot nguoi sua duoc chung tu cua ho so bat ky chi bang
        // cach doi so id tren URL.
        $this->dungHoSo();

        $hoSoB = CtdtHoSo::create([
            'ma_ho_so' => 'YT002', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);
        $chungTuB = CtdtChungTu::create([
            'ho_so_id' => $hoSoB->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT002',
            'noi_dung_goc' => '<CT03/>',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Ma ho so YT001 nhung id chung tu cua YT002
        $this->goi($this->xmlCt03(), 'YT001', $chungTuB->id);
    }
}
