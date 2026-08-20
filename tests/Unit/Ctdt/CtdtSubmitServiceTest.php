<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtSubmitService;
use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Lop login gia, ke thua lop that de giu dung chu ky phuong thuc.
 *
 * KHONG dung createMock(): PHPUnit 6 sinh deprecation ReflectionType voi cac lop co kieu
 * tra ve khai bao - da lam do test o cho khac trong du an nay.
 */
class FakeCtdtLoginService extends BHYTLoginService
{
    /** @var string[] */
    public $tokenSequence = ['token-1'];
    public $tokenCallCount = 0;
    public $logoutCallCount = 0;

    /**
     * Nhan de chung minh bon truong (token/id_token/username/password) den tu CUNG mot
     * nguon. Neu mot trong bon phuong thuc bi hard-code thanh hang so, gia tri cua no se
     * khong mang nhan nay va test bao ve se bat duoc.
     *
     * @var string
     */
    public $nhan = '01929';

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle/Config that.
    }

    public function getAccessToken(): string
    {
        if (isset($this->tokenSequence[$this->tokenCallCount])) {
            $token = $this->tokenSequence[$this->tokenCallCount];
        } else {
            $token = 'token@' . $this->nhan;
        }
        $this->tokenCallCount++;

        return $token;
    }

    public function getIdToken(): string
    {
        return 'id@' . $this->nhan;
    }

    public function username(): string
    {
        return 'user@' . $this->nhan;
    }

    public function password(): string
    {
        return 'pw@' . $this->nhan;
    }

    public function logout(): void
    {
        $this->logoutCallCount++;
    }
}

class CtdtSubmitServiceTest extends TestCase
{
    private function dungDichVu()
    {
        config(['ctdt.dich_vu' => [
            'CT2025' => [
                'ten' => 'Chứng từ TT25/2025', 'the_goc' => 'HSCHUNGTU', 'loai_hs' => '39',
                'url' => 'https://vi-du.test/api/chungtugw/GuiHoSoChungTu2025',
            ],
            'GBT' => [
                'ten' => 'Giấy báo tử', 'the_goc' => 'HSDLGBT', 'loai_hs' => '60',
                'url' => 'https://vi-du.test/api/hososuckhoe/guiGiayToDienTu',
            ],
        ]]);
    }

    private function dungService(MockHandler $mock, BHYTLoginService $login)
    {
        $this->dungDichVu();

        $service = new CtdtSubmitService($login);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $ref = new \ReflectionProperty(CtdtSubmitService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    private function phanHoi(array $than, $maHttp = 200)
    {
        return new Response($maHttp, [], json_encode($than));
    }

    /** @test */
    public function gui_thanh_cong_tra_du_ba_truong()
    {
        $mock = new MockHandler([$this->phanHoi([
            'MaGD' => 'GD-001', 'MaKetQua' => '200', 'ThoiGianTiepNhan' => '20260820083000',
        ])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('GD-001', $kq['ma_gd']);
        $this->assertSame('20260820083000', $kq['thoi_gian_tiep_nhan']);
    }

    /** @test */
    public function bay_truong_deu_nam_trong_BODY_khong_o_header()
    {
        // PL02 dat TAT CA trong body. BHYTXmlSubmitService dat xac thuc o header voi ten
        // truong khac - ep chung mot ham la cong tu choi ma khong noi vi sao.
        $than = null;
        $mock = new MockHandler([function ($request) use (&$than) {
            $than = (string) $request->getBody();

            return new Response(200, [], json_encode(['MaGD' => 'G', 'MaKetQua' => '200']));
        }]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        parse_str($than, $truong);
        $khoa = array_keys($truong);
        sort($khoa);

        $this->assertSame(
            ['fileBase64Str', 'id_token', 'loaiHs', 'maCskcb', 'password', 'token', 'username'],
            $khoa
        );
        $this->assertSame('01929', $truong['maCskcb']);
    }

    /** @test */
    public function loai_hs_lay_theo_dich_vu()
    {
        // 39 / 60 / 61 la ba loai ho so khac nhau. Gui giay bao tu voi loaiHs 39 la cong
        // nhan vao dung hang doi sai.
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSDLGBT/>', 'GBT', '01929');

        $this->assertSame('60', $than['loaiHs']);
    }

    /** @test */
    public function fileBase64Str_giai_ra_dung_XML_da_ky()
    {
        $xml = '<HSCHUNGTU><CHUKYDONVI>chu-ky</CHUKYDONVI></HSCHUNGTU>';
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui($xml, 'CT2025', '01929');

        $this->assertSame($xml, base64_decode($than['fileBase64Str']));
    }

    /** @test */
    public function token_va_username_lay_tu_CUNG_MOT_loginService()
    {
        // Neu token va tai khoan trong body thuoc hai co so khac nhau thi cong van nhan, va
        // ho so bi ghi sai don vi gui - hong IM LANG, khong lo ra cho toi luc doi soat.
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $login = new FakeCtdtLoginService();
        $login->nhan = 'nguon-rieng-01929';
        $login->tokenSequence = []; // ep getAccessToken() roi ve nhan, khong dung gia tri co dinh

        $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        // Khang dinh CA BON truong mang cung mot nhan, thay vi khoa vao gia tri hang so cu
        // the - khoa hang so de lot mot phep hard-code khien ca bon van "dung" ma khong con
        // den tu cung mot nguon.
        foreach (['token', 'id_token', 'username', 'password'] as $truong) {
            $this->assertContains(
                '@nguon-rieng-01929',
                $than[$truong],
                $truong . ' phai den tu cung mot loginService'
            );
        }
    }

    /** @test */
    public function ma_401_trong_THAN_phan_hoi_thi_dang_nhap_lai_va_gui_lai_DUNG_MOT_LAN()
    {
        // Cong tra HTTP 200 kem MaKetQua 401 - KHONG phai HTTP 401. Bat nham cho nghia la
        // khong bao gio retry, va moi token het han thanh mot ho so gui hong.
        $token = [];
        $mock = new MockHandler([
            function ($request) use (&$token) {
                parse_str((string) $request->getBody(), $t);
                $token[] = $t['token'];

                return new Response(200, [], json_encode(['MaKetQua' => '401']));
            },
            function ($request) use (&$token) {
                parse_str((string) $request->getBody(), $t);
                $token[] = $t['token'];

                return new Response(200, [], json_encode(['MaKetQua' => '200', 'MaGD' => 'GD-002']));
            },
        ]);

        $login = new FakeCtdtLoginService();
        $login->tokenSequence = ['token-het-han', 'token-moi'];

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('GD-002', $kq['ma_gd']);
        $this->assertSame(['token-het-han', 'token-moi'], $token);
        $this->assertSame(1, $login->logoutCallCount, 'Phai xoa cache token dung 1 lan');
        $this->assertSame(2, $login->tokenCallCount, 'Phai lay token 2 lan');
    }

    /** @test */
    public function ca_hai_lan_401_thi_dung_lai_khong_lap_vo_han()
    {
        $mock = new MockHandler([
            $this->phanHoi(['MaKetQua' => '401']),
            $this->phanHoi(['MaKetQua' => '401']),
        ]);

        $login = new FakeCtdtLoginService();
        $login->tokenSequence = ['cu', 'van-hong'];

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('401', $kq['ma_ket_qua']);
        $this->assertSame(1, $login->logoutCallCount, 'Chi duoc dang nhap lai dung mot lan');
    }

    /** @test */
    public function ma_khac_401_thi_KHONG_dang_nhap_lai()
    {
        foreach (['205', '500', '1001'] as $ma) {
            $login = new FakeCtdtLoginService();
            $mock = new MockHandler([$this->phanHoi(['MaKetQua' => $ma])]);

            $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

            $this->assertSame($ma, $kq['ma_ket_qua']);
            $this->assertSame(0, $login->logoutCallCount, 'Ma ' . $ma . ' khong duoc re-auth');
        }
    }

    /** @test */
    public function ma_ket_qua_kieu_SO_van_nhan_ra_la_401()
    {
        // Cong co the tra so 401 thay vi chuoi '401'. So sanh nghiem ngat se truot va bo
        // qua ca duong retry.
        $mock = new MockHandler([
            $this->phanHoi(['MaKetQua' => 401]),
            $this->phanHoi(['MaKetQua' => 200, 'MaGD' => 'GD-003']),
        ]);

        $login = new FakeCtdtLoginService();

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame(1, $login->logoutCallCount);
        $this->assertSame('GD-003', $kq['ma_gd']);
    }

    /** @test */
    public function thong_diep_tra_ve_lay_tu_danh_muc_ma_ket_qua()
    {
        config(['ctdt.ma_ket_qua' => ['200' => 'Thành công', '205' => 'fileBase64Str không hợp lệ']]);

        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '205'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertContains('fileBase64Str', $kq['thong_diep']);
    }

    /** @test */
    public function ma_ket_qua_ngoai_danh_muc_van_co_thong_diep_doc_duoc()
    {
        // BHXH co the them ma moi. Hien mot o trong la nguoi van hanh khong biet chuyen gi.
        config(['ctdt.ma_ket_qua' => ['200' => 'Thành công']]);

        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '999'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertNotEmpty($kq['thong_diep']);
        $this->assertContains('999', $kq['thong_diep']);
    }

    /** @test */
    public function giu_nguyen_van_phan_hoi_de_doi_soat()
    {
        // Khi cong bao 205, nguyen van phan hoi la thu duy nhat doi chieu duoc.
        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '205', 'ChiTiet' => 'sai the goc'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertContains('sai the goc', $kq['nguyen_van']);
    }

    /** @test */
    public function dich_vu_la_thi_nem_truoc_khi_goi_mang()
    {
        $this->dungDichVu();

        $this->expectException(\InvalidArgumentException::class);

        $mock = new MockHandler([]);
        $this->dungService($mock, new FakeCtdtLoginService())->gui('<X/>', 'KHONG_TON_TAI', '01929');
    }

    /** @test */
    public function loi_mang_thi_nem_de_hang_doi_thu_lai()
    {
        // Mang chap la loi TAM THOI. Nuot no thanh mot ket qua "that bai" se lam ho so mat
        // co hoi duoc hang doi thu lai.
        $mock = new MockHandler([new \GuzzleHttp\Exception\ConnectException(
            'Connection refused',
            new \GuzzleHttp\Psr7\Request('POST', 'https://vi-du.test')
        )]);

        $this->expectException(\Exception::class);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');
    }

    /** @test */
    public function khong_tiem_phu_thuoc_thi_dung_login_service_theo_dung_ma_co_so()
    {
        // Nhanh mac dinh phai dung duoc that. Truoc day no dung BHYTLoginService khong ma
        // co so, nen moi lan goi deu nem "Thieu ma co so KCB" - mot nhanh chet.
        //
        // Khong khai tai khoan cho ma co so nay (99999 khong nam trong
        // organization.BHYT_CO_SO), nen ta ky vong loi NEU DICH DANH chinh no, chu khong
        // phai loi "thieu ma co so" - dieu do chung minh maCskcb da duoc truyen xuong.
        $this->dungDichVu();

        $service = new CtdtSubmitService();

        $client = new Client(['handler' => HandlerStack::create(new MockHandler([]))]);
        $ref = new \ReflectionProperty(CtdtSubmitService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        try {
            $service->gui('<HSCHUNGTU/>', 'CT2025', '99999');
            $this->fail('Phai nem vi co so 99999 chua khai tai khoan');
        } catch (\Throwable $e) {
            $this->assertNotContains(
                'Thieu ma co so',
                $e->getMessage(),
                'Phai neu dich danh co so 99999, khong phai "thieu ma co so"'
            );
        }
    }

    /** @test */
    public function ma_ket_qua_luon_tra_ve_kieu_CHUOI()
    {
        // Cong co the tra so 200 thay vi chuoi '200'. Lop goi o cac task sau so sanh voi
        // chuoi; tra ve so la mot phep so sanh truot im lang.
        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => 200, 'MaGD' => 'GD-1'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
    }

    /** @test */
    public function ghi_log_kich_thuoc_base64_moi_lan_gui()
    {
        // Tai lieu PL02 khong noi nguong cua ma 1001 (file size qua dai). Dong log nay la
        // cach duy nhat do duoc tu thuc te - xoa no di la mat luon duong do.
        $xml = '<HSCHUNGTU/>';

        \Log::shouldReceive('info')->once()->with(
            'CTDT gui ho so',
            \Mockery::on(function ($ngucanh) use ($xml) {
                return is_array($ngucanh)
                    && isset($ngucanh['so_ky_tu_base64'])
                    && $ngucanh['so_ky_tu_base64'] === strlen(base64_encode($xml));
            })
        );
        \Log::shouldReceive('warning')->zeroOrMoreTimes();
        \Log::shouldReceive('error')->zeroOrMoreTimes();

        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '200'])]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui($xml, 'CT2025', '01929');
    }
}
