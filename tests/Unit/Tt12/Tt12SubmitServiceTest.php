<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use App\Services\BHYTLoginService;
use App\Services\Tt12\Tt12SubmitService;

class Tt12SubmitServiceTest extends TestCase
{
    /** @var array cac request da gui, de doi chieu body */
    private $daGui = array();

    /**
     * Dung mot Tt12SubmitService co Guzzle gia va login gia.
     *
     * @param array $phanHoi cac Response tra ve lan luot
     */
    private function dichVu(array $phanHoi, &$login = null)
    {
        $mock = new MockHandler($phanHoi);
        $stack = HandlerStack::create($mock);

        $this->daGui = array();
        $luu = &$this->daGui;

        $stack->push(Middleware::history($luu));

        // Chu ky PHAI khop y nguyen lop cha, KE CA kieu tra ve: BHYTLoginService khai
        // getAccessToken(): string va logout(): void. Bo kieu di la PHP bao loi tuong
        // thich chu ky ngay khi nap lop.
        $login = new class extends BHYTLoginService {
            public $soLanLogout = 0;

            public function __construct() {}
            public function getAccessToken(): string { return 'TOKEN'; }
            public function getIdToken(): string     { return 'IDTOKEN'; }
            public function username(): string       { return '01929_BV'; }
            public function password(): string       { return 'MD5HASH'; }
            public function logout(): void           { $this->soLanLogout++; }
        };

        $dv = new Tt12SubmitService($login);
        $dv->dungClient(new Client(array('handler' => $stack)));

        return $dv;
    }

    /** @test */
    public function gui_thanh_cong_tra_ve_ma_giao_dich()
    {
        $dv = $this->dichVu(array(new Response(200, array(), json_encode(array(
            'maKetQua' => '200',
            'maGiaoDich' => 'DANHMUC01_01929',
            'thongDiep' => 'Tiếp nhận thành công',
            'thoiGianTiepNhan' => '20260308145721',
        )))));

        $kq = $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('DANHMUC01_01929', $kq['ma_gd']);
        $this->assertSame('20260308145721', $kq['thoi_gian_tiep_nhan']);
    }

    /** @test */
    public function body_va_header_dung_theo_cau_hinh()
    {
        $dv = $this->dichVu(array(new Response(200, array(), '{"maKetQua":"200"}')));

        $dv->gui('<HSDANHMUC/>', 'MAU_03', '01929');

        $req = $this->daGui[0]['request'];

        $this->assertSame('TOKEN',   $req->getHeaderLine('accessToken'));
        $this->assertSame('IDTOKEN', $req->getHeaderLine('tokenId'));
        $this->assertSame('MD5HASH', $req->getHeaderLine('passwordHash'));

        parse_str((string) $req->getBody(), $body);

        $truong = config('tt12.truong_body');

        $this->assertSame('01929_BV', $body[$truong['username']]);
        $this->assertSame('10',       $body[$truong['loai_hs']], 'MAU_03 phai gui loaiHs=10');
        $this->assertSame('01929',    $body[$truong['ma_cskcb']]);
        $this->assertSame(base64_encode('<HSDANHMUC/>'), $body[$truong['file_base64']]);
    }

    /** @test */
    public function ma_tinh_SUY_TU_ma_co_so_chu_khong_lay_tu_cau_hinh()
    {
        // Bach Mai co 01929 (Ha Noi -> 01) va 37470 (Ninh Binh -> 37). Mot ma tinh chot
        // cung trong cau hinh se gui ho so Ninh Binh voi maTinh=01.
        $truong = config('tt12.truong_body');

        $dv = $this->dichVu(array(new Response(200, array(), '{"maKetQua":"200"}')));
        $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');
        parse_str((string) $this->daGui[0]['request']->getBody(), $body);
        $this->assertSame('01', $body[$truong['ma_tinh']]);

        $dv = $this->dichVu(array(new Response(200, array(), '{"maKetQua":"200"}')));
        $dv->gui('<HSDANHMUC/>', 'MAU_01', '37470');
        parse_str((string) $this->daGui[0]['request']->getBody(), $body);
        $this->assertSame('37', $body[$truong['ma_tinh']]);
        $this->assertSame('37470', $body[$truong['ma_cskcb']]);
    }

    /** @test */
    public function url_dung_theo_mau()
    {
        $dv = $this->dichVu(array(new Response(200, array(), '{"maKetQua":"200"}')));

        $dv->gui('<HSDANHMUC/>', 'MAU_05', '01929');

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc05_DVKT',
            (string) $this->daGui[0]['request']->getUri()
        );
    }

    /** @test */
    public function ma_401_trong_THAN_phan_hoi_kich_hoat_dang_nhap_lai_va_gui_lai_mot_lan()
    {
        // BAY LON NHAT CUA GIAO THUC: cong tra HTTP 200 kem {"maKetQua":"401"}. Khuon
        // retry bat HTTP 401 se khong bao gio kich hoat.
        $login = null;

        $dv = $this->dichVu(array(
            new Response(200, array(), '{"maKetQua":"401","thongDiep":"Token het han"}'),
            new Response(200, array(), '{"maKetQua":"200","maGiaoDich":"GD2"}'),
        ), $login);

        $kq = $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('GD2', $kq['ma_gd']);
        $this->assertSame(1, $login->soLanLogout, 'Phai xoa token va dang nhap lai dung mot lan');
        $this->assertCount(2, $this->daGui, 'Phai goi mang dung hai lan');
    }

    /** @test */
    public function ma_401_hai_lan_lien_thi_dung_lai_khong_lap_vo_han()
    {
        $dv = $this->dichVu(array(
            new Response(200, array(), '{"maKetQua":"401"}'),
            new Response(200, array(), '{"maKetQua":"401"}'),
        ));

        $kq = $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');

        $this->assertSame('401', $kq['ma_ket_qua']);
        $this->assertCount(2, $this->daGui);
    }

    /** @test */
    public function loi_mang_thi_NEM_chu_khong_nuot()
    {
        // Mang chap la loi TAM THOI. Bien no thanh mot ket qua "that bai" se lam ho so
        // mat co hoi duoc hang doi thu lai.
        $dv = $this->dichVu(array(
            new \GuzzleHttp\Exception\ConnectException(
                'Connection timed out',
                new \GuzzleHttp\Psr7\Request('POST', 'x')
            ),
        ));

        $this->expectException(\Exception::class);

        $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');
    }

    /** @test */
    public function phan_hoi_khong_phai_json_van_tra_ve_ket_qua_doc_duoc()
    {
        $dv = $this->dichVu(array(new Response(200, array(), '<html>502 Bad Gateway</html>')));

        $kq = $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');

        $this->assertNull($kq['ma_gd']);
        $this->assertNotEmpty($kq['thong_diep']);
        $this->assertContains('502 Bad Gateway', $kq['nguyen_van']);
    }

    /** @test */
    public function mau_khong_biet_bi_tu_choi_truoc_khi_goi_mang()
    {
        $dv = $this->dichVu(array());

        $this->expectException(\InvalidArgumentException::class);

        $dv->gui('<HSDANHMUC/>', 'MAU_99', '01929');
    }

    /** @test */
    public function tao_login_nhan_DUNG_ma_co_so_cua_ho_so_dang_gui()
    {
        // Bai test nay KHONG tiem $loginService qua constructor - moi test khac o tren
        // deu tiem san login gia nen chua bao gio di vao nhanh
        // "$this->loginService ?: $this->taoLogin($maCskcb)". Muc dich chinh la buoc di
        // dung vao nhanh do va bat lai ma co so ma taoLogin() nhan duoc, de chung minh no
        // TRUNG voi ma co so nam trong body - khong bi chot cung hoac lech nguon.
        $mock = new MockHandler(array(
            new Response(200, array(), '{"maKetQua":"200"}'),
            new Response(200, array(), '{"maKetQua":"200"}'),
        ));
        $stack = HandlerStack::create($mock);
        $daGui = array();
        $stack->push(Middleware::history($daGui));

        $loginGia = new class extends BHYTLoginService {
            public function __construct() {}
            public function getAccessToken(): string { return 'TOKEN'; }
            public function getIdToken(): string     { return 'IDTOKEN'; }
            public function username(): string       { return 'BV'; }
            public function password(): string       { return 'MD5HASH'; }
            public function logout(): void           {}
        };

        $dv = new class($loginGia) extends Tt12SubmitService {
            public $maCskcbNhanDuoc = array();
            private $loginGia;

            public function __construct($loginGia)
            {
                parent::__construct();
                $this->loginGia = $loginGia;
            }

            protected function taoLogin($maCskcb)
            {
                $this->maCskcbNhanDuoc[] = $maCskcb;

                return $this->loginGia;
            }
        };
        $dv->dungClient(new Client(array('handler' => $stack)));

        $truong = config('tt12.truong_body');

        $dv->gui('<HSDANHMUC/>', 'MAU_01', '37470');
        parse_str((string) $daGui[0]['request']->getBody(), $body);
        $this->assertSame('37470', $dv->maCskcbNhanDuoc[0]);
        $this->assertSame('37470', $body[$truong['ma_cskcb']]);

        $dv->gui('<HSDANHMUC/>', 'MAU_01', '01929');
        parse_str((string) $daGui[1]['request']->getBody(), $body);
        $this->assertSame('01929', $dv->maCskcbNhanDuoc[1]);
        $this->assertSame('01929', $body[$truong['ma_cskcb']]);
    }
}
