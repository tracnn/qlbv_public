<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BHYT\BHYTTt12Controller;
use App\Services\Tt12\Tt12XuatXml;
use App\Services\Tt12\Tt12PhongBi;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\Tt12\Tt12DocDong;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;

/**
 * Xuat XML tu man danh sach, kem chu ky so neu co.
 *
 * "Kem chu ky neu co" la HAI NGUON KHAC NHAU: ho so da ky doc tu TEP TREN DIA (ban that da
 * gui len cong), ho so chua ky phai DUNG LAI tu du lieu. Moi test duoi day canh mot cho hai
 * nguon do co the lan sang nhau.
 */
class Tt12XuatXmlTest extends TestCase
{
    use DungBangTt12Sqlite;

    /** @var BHYTTt12Controller */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
        Storage::fake('exportTt12');

        $this->controller = new BHYTTt12Controller(new \App\Services\Tt12\Tt12Importer());
    }

    private function hoSo($maHoSo = 'TT12_MAU_01_01929_20260825_001', array $ghiDe = array(), $coDong = true)
    {
        $hoSo = Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $maHoSo,
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-09-16 10:00:00', 'so_loi' => 0,
        ), $ghiDe));

        if ($coDong) {
            Tt12Dong::create(array(
                'ho_so_id' => $hoSo->id, 'stt' => 1,
                'du_lieu' => array('STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
                                   'TU_NGAY' => '20260101', 'MA_CSKCB' => '01929'),
            ));
        }

        return $hoSo->fresh();
    }

    /** Dat mot tep da ky gia vao dia, tra noi dung da dat */
    private function datTepDaKy(Tt12HoSo $hoSo, $noiDung = null)
    {
        $noiDung = $noiDung ?: '<?xml version="1.0"?><DANHSACH_MAU01 Id="Id-abc">'
            . '<CHUKYDONVI>CHU-KY-THAT</CHUKYDONVI></DANHSACH_MAU01>';

        $duongDan = 'MAU_01/202609/' . $hoSo->ma_ho_so . '.xml';

        Storage::disk('exportTt12')->put($duongDan, $noiDung);

        $hoSo->update(array('is_signed' => true, 'duong_dan_da_ky' => $duongDan));

        return $noiDung;
    }

    private function goi(array $ma)
    {
        return $this->controller->xuatXml(
            Request::create('/tt12/xuat/xml', 'POST', array('ma_ho_so' => $ma))
        );
    }

    // ------------------------------------------------------------------ hai nguon

    /** @test */
    public function ho_so_DA_KY_lay_dung_noi_dung_tep_tren_dia()
    {
        // Day la ban THAT da gui len cong, chu ky XMLDSig nam san ben trong. Dung lai tu du
        // lieu se cho ra ban KHONG co chu ky - khac han ve phap ly.
        $hoSo = $this->hoSo();
        $noiDung = $this->datTepDaKy($hoSo);

        $kq = Tt12XuatXml::cua($hoSo->fresh());

        $this->assertSame(Tt12XuatXml::DA_KY, $kq['trang_thai']);
        $this->assertSame($noiDung, $kq['noi_dung']);
        $this->assertContains('CHU-KY-THAT', $kq['noi_dung']);
    }

    /** @test */
    public function ho_so_CHUA_KY_dung_lai_KHOP_TUNG_BYTE_voi_ban_sap_duoc_ky()
    {
        // Bat bien quan trong nhat cua tinh nang nay. SignTt12Job dung phong bi bang ba dong;
        // neu o day viet lai cach dung do lan hai, hai ban se lech nhau va nguoi doi chieu
        // thay hai tep khac nhau cho cung mot ho so ma khong hieu vi sao.
        $hoSo = $this->hoSo();

        $mongDoi = Tt12PhongBi::dung(
            Tt12MauRegistry::cho($hoSo->mau),
            $hoSo->id_danh_sach,
            (new Tt12DocDong())->choPhongBi($hoSo)
        );

        $kq = Tt12XuatXml::cua($hoSo);

        $this->assertSame(Tt12XuatXml::CHUA_KY, $kq['trang_thai']);
        $this->assertSame($mongDoi, $kq['noi_dung']);
    }

    /** @test */
    public function ban_dung_lai_dung_dung_id_danh_sach_da_luu_chu_khong_sinh_moi()
    {
        // Chu ky XMLDSig tham chieu chinh #Id nay - xem chu thich migration
        // create_tt12_ho_so_table. Sinh moi thi ban xuat khac ban se ky.
        $hoSo = $this->hoSo('TT12_X', array('id_danh_sach' => 'Id-rieng-biet-123'));

        $kq = Tt12XuatXml::cua($hoSo);

        $this->assertContains('Id-rieng-biet-123', $kq['noi_dung']);
    }

    /** @test */
    public function ho_so_ghi_da_ky_nhung_MAT_TEP_thi_danh_dau_la_CHUA_KY()
    {
        // Neu lang le dung lai roi dat ten "-da-ky", nguoi dung cam mot ban KHONG CO CHU KY
        // ma tuong la ban da ky. Do la kieu sai khong ai phat hien bang mat.
        $hoSo = $this->hoSo();
        $hoSo->update(array('is_signed' => true, 'duong_dan_da_ky' => 'MAU_01/202609/mat-tieu.xml'));

        $kq = Tt12XuatXml::cua($hoSo->fresh());

        $this->assertSame(Tt12XuatXml::CHUA_KY, $kq['trang_thai']);
        $this->assertContains('-chua-ky.xml', $kq['ten_tep']);
        $this->assertContains('không tìm thấy tệp', $kq['ly_do']);
        $this->assertContains('KHÔNG có chữ ký', $kq['ly_do']);
    }

    /** @test */
    public function ho_so_KHONG_CO_DONG_NAO_bao_hong_kem_ly_do()
    {
        // Tt12PhongBi::dung() nem cho truong hop nay co y: mot phong bi rong van duoc cong
        // nhan va tra ve MaGD. O day khong duoc de ngoai le thoat ra lam hong ca lo.
        $hoSo = $this->hoSo('TT12_RONG', array(), false);

        $kq = Tt12XuatXml::cua($hoSo);

        $this->assertSame(Tt12XuatXml::HONG, $kq['trang_thai']);
        $this->assertNull($kq['noi_dung']);
        $this->assertNotSame('', $kq['ly_do']);
    }

    /** @test */
    public function ho_so_THIEU_id_danh_sach_cung_bao_hong()
    {
        $hoSo = $this->hoSo('TT12_THIEU_ID', array('id_danh_sach' => null));

        $this->assertSame(Tt12XuatXml::HONG, Tt12XuatXml::cua($hoSo)['trang_thai']);
    }

    // ------------------------------------------------------------------ ten tep

    /** @test */
    public function ten_tep_noi_ro_ban_da_ky_hay_chua()
    {
        // Hai ban khac nhau ve PHAP LY. Nguoi mo ZIP phai phan biet duoc ma khong can mo
        // tung tep ra xem.
        $hoSo = $this->hoSo('TT12_A');

        $this->assertContains('-chua-ky.xml', Tt12XuatXml::tenTep($hoSo, Tt12XuatXml::CHUA_KY));
        $this->assertContains('-da-ky.xml', Tt12XuatXml::tenTep($hoSo, Tt12XuatXml::DA_KY));
    }

    /** @test */
    public function ten_tep_loc_ky_tu_lam_thoat_thu_muc()
    {
        // Ma ho so di vao TEN TEP va vao muc luc ZIP. Mot ma chua '../' se tao duong dan
        // thoat ra ngoai khi nguoi dung giai nen.
        $hoSo = $this->hoSo('../../etc/passwd');

        $ten = Tt12XuatXml::tenTep($hoSo, Tt12XuatXml::DA_KY);

        $this->assertNotContains('/', $ten);
        $this->assertNotContains('..', $ten);
    }

    // ------------------------------------------------------------------ controller

    /** @test */
    public function MOT_ho_so_thi_tai_thang_tep_xml_khong_boc_ZIP()
    {
        // Bat nguoi dung giai nen mot tep la them mot buoc vo ich cho truong hop thuong gap
        // nhat.
        $hoSo = $this->hoSo();
        $noiDung = $this->datTepDaKy($hoSo);

        $phanHoi = $this->goi(array($hoSo->ma_ho_so));

        $this->assertContains('application/xml', $phanHoi->headers->get('Content-Type'));
        $this->assertContains('-da-ky.xml', $phanHoi->headers->get('Content-Disposition'));
        $this->assertSame($noiDung, $phanHoi->getContent());
    }

    /** @test */
    public function NHIEU_ho_so_thi_dong_ZIP_kem_tep_ke()
    {
        $a = $this->hoSo('TT12_A');
        $this->datTepDaKy($a);
        $this->hoSo('TT12_B');

        $phanHoi = $this->goi(array('TT12_A', 'TT12_B'));

        $this->assertContains('.zip', $phanHoi->headers->get('Content-Disposition'));

        $trongZip = $this->docZip($phanHoi->getFile()->getPathname());

        $this->assertArrayHasKey('TT12_A-da-ky.xml', $trongZip);
        $this->assertArrayHasKey('TT12_B-chua-ky.xml', $trongZip);
        $this->assertArrayHasKey('_ke-khai.csv', $trongZip);
    }

    /** @test */
    public function tep_ke_neu_ly_do_cua_ho_so_KHONG_xuat_duoc()
    {
        // Khong co tep ke thi nguoi mo ZIP thay thieu vai tep ma khong biet vi sao - va se
        // tuong phan mem lam mat, thay vi biet rang ho so do khong co dong nao.
        $this->hoSo('TT12_A');
        $this->hoSo('TT12_RONG', array(), false);

        $phanHoi = $this->goi(array('TT12_A', 'TT12_RONG'));

        $trongZip = $this->docZip($phanHoi->getFile()->getPathname());

        $this->assertArrayNotHasKey('TT12_RONG-chua-ky.xml', $trongZip,
            'Ho so hong khong duoc sinh ra mot tep XML rong');

        $ke = $trongZip['_ke-khai.csv'];

        $this->assertContains('TT12_RONG', $ke);
        $this->assertContains('Không xuất được', $ke);
        $this->assertContains('TT12_A', $ke);
    }

    /** @test */
    public function tep_ke_co_BOM_de_Excel_doc_dung_tieng_Viet()
    {
        // Khong co BOM thi Excel tren Windows doc CSV theo bang ma he thong va moi dau tieng
        // Viet thanh ky tu la - loi da gap o cac tep xuat truoc.
        $this->hoSo('TT12_A');
        $this->hoSo('TT12_B');

        $trongZip = $this->docZip($this->goi(array('TT12_A', 'TT12_B'))->getFile()->getPathname());

        $this->assertSame("\xEF\xBB\xBF", substr($trongZip['_ke-khai.csv'], 0, 3));
    }

    /** @test */
    public function mot_ho_so_hong_KHONG_lam_hong_ca_lo()
    {
        $this->hoSo('TT12_A');
        $this->hoSo('TT12_RONG', array(), false);
        $this->hoSo('TT12_C');

        $trongZip = $this->docZip($this->goi(array('TT12_A', 'TT12_RONG', 'TT12_C'))
            ->getFile()->getPathname());

        $this->assertArrayHasKey('TT12_A-chua-ky.xml', $trongZip);
        $this->assertArrayHasKey('TT12_C-chua-ky.xml', $trongZip);
    }

    /** @test */
    public function ca_lo_hong_thi_bao_loi_chu_khong_tra_ZIP_rong()
    {
        $this->hoSo('TT12_RONG', array(), false);

        $phanHoi = $this->goi(array('TT12_RONG'));

        // Man hinh POST bang FORM AN chu khong AJAX, nen traLoi() tra REDIRECT kem thong
        // bao - va do la hanh vi DUNG: nguoi dung quay ve danh sach va doc duoc ly do, thay
        // vi nhin mot trang trang hay mot tep ZIP rong.
        $this->assertSame(302, $phanHoi->getStatusCode());
        $this->assertContains('không hồ sơ nào', mb_strtolower(session('error')));
    }

    /** @test */
    public function danh_sach_rong_bi_tu_choi()
    {
        $this->assertSame(302, $this->goi(array())->getStatusCode());
        $this->assertContains('Chưa chọn hồ sơ nào', session('error'));
    }

    /** @test */
    public function vuot_tran_thi_tu_choi_ca_lo()
    {
        $ds = array();

        for ($i = 1; $i <= BHYTTt12Controller::TRAN_XUAT_XML + 1; $i++) {
            $ds[] = 'TT12_' . $i;
        }

        $this->assertSame(302, $this->goi($ds)->getStatusCode());
        $this->assertContains('tối đa', session('error'));
    }

    /** @test */
    public function tran_ghim_o_50()
    {
        // GHIM CON SO: test vuot tran o tren tu dung danh sach TU CHINH hang so nen no troi
        // theo - noi tran len 500 thi test do van xanh.
        $this->assertSame(50, BHYTTt12Controller::TRAN_XUAT_XML);
    }

    /** @return array [ten trong zip => noi dung] */
    private function docZip($duongDan)
    {
        $zip = new \ZipArchive();

        $this->assertTrue($zip->open($duongDan) === true, 'Khong mo duoc tep ZIP');

        $ket = array();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $ten = $zip->getNameIndex($i);
            $ket[$ten] = $zip->getFromIndex($i);
        }

        $zip->close();

        return $ket;
    }
}
