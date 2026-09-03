<?php

namespace Tests\Unit\Mcct;

use App\Services\BHYTLoginService;
use App\Services\Mcct\McctTraCuuService;
use App\Services\Mcct\McctXacThucException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

/**
 * Login service GIA LAP - khong bao gio cham mang.
 *
 * VI SAO CAN: BHYTLoginService that tu tao Client trong constructor nen khong tiem mock vao
 * duoc, va no TU DANG NHAP LAI khi cache token trong. Luong 401 cua McctTraCuuService goi
 * logout() (xoa cache) roi goi lai, nen mot BHYTLoginService that se gui request dang nhap
 * THAT len cong BHXH SAN XUAT ngay giua bo test - da xay ra that mot lan.
 *
 * Doi token sau logout(): nho vay kiem duoc ca viec lan goi thu hai co mang token MOI hay
 * khong, dieu ma cach mo san token vao cache khong kiem duoc.
 */
class LoginServiceGiaLap extends BHYTLoginService
{
    /** @var int so lan logout() bi goi - dung de kiem service co lam moi phien hay khong */
    public $soLanLogout = 0;

    private $token = 'TOKEN-A';
    private $idToken = 'ID-A';

    public function __construct()
    {
        parent::__construct('01929');
    }

    public function getAccessToken(): string
    {
        return $this->token;
    }

    public function getIdToken(): string
    {
        return $this->idToken;
    }

    public function passwordHash(): string
    {
        return 'bam-mat-khau-01929';
    }

    public function username(): string
    {
        return '01929_BV';
    }

    public function logout(): void
    {
        $this->soLanLogout++;
        $this->token = 'TOKEN-B';
        $this->idToken = 'ID-B';
    }
}

class McctXacThucTest extends TestCase
{
    /** @var array cac request that su da di ra ngoai */
    protected $daGui = [];

    protected function setUp()
    {
        parent::setUp();

        $this->daGui = [];

        config([
            'organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn',
            'organization.BHYT_CO_SO' => [
                '01929' => [
                    'username' => '01929_BV',
                    'password' => 'bam-mat-khau-01929',
                ],
            ],
            'mcct.duong_dan' => '/api/TraCuuCCT/TraCuuTienMCCT',
            'mcct.timeout_ket_noi' => 10,
            'mcct.timeout_tong' => 30,
        ]);
    }

    /**
     * Guzzle gia lap, ghi lai moi request di ra.
     *
     * Dung MockHandler chu KHONG dung Mockery: Mockery da nhieu lan vo voi cac lop khai bao
     * kieu tra ve trong du an nay.
     *
     * @param array $phanHoi danh sach Response|Exception tra ve lan luot
     */
    protected function client(array $phanHoi)
    {
        $stack = HandlerStack::create(new MockHandler($phanHoi));
        $stack->push(Middleware::history($this->daGui));

        return new Client(['handler' => $stack]);
    }

    protected function than200()
    {
        return json_encode([
            'MaKetQua' => '200',
            'GhiChu' => 'tính đến: 05/08/2026 17:30',
            'DataCCT' => [],
            'ThongTinSoThe' => null,
        ]);
    }

    /** @test */
    public function gui_dung_ba_header_xac_thuc()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), new LoginServiceGiaLap());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $req = $this->daGui[0]['request'];

        $this->assertSame('TOKEN-A', $req->getHeaderLine('accessToken'));
        $this->assertSame('ID-A', $req->getHeaderLine('tokenId'));
        $this->assertSame('bam-mat-khau-01929', $req->getHeaderLine('passwordHash'));
    }

    /** @test */
    public function gui_dung_url_ghep_tu_base_url_va_duong_dan()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), new LoginServiceGiaLap());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/TraCuuCCT/TraCuuTienMCCT',
            (string) $this->daGui[0]['request']->getUri()
        );
    }

    /** @test */
    public function body_json_dung_bon_truong()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), new LoginServiceGiaLap());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $body = json_decode((string) $this->daGui[0]['request']->getBody(), true);

        $this->assertSame(
            ['username', 'maThe', 'hoTen', 'ngaySinh'],
            array_keys($body)
        );
        $this->assertSame('01929_BV', $body['username']);
        $this->assertSame('DN4010100000001', $body['maThe']);
        $this->assertSame('Nguyen Van A', $body['hoTen']);
        $this->assertSame('01/01/1990', $body['ngaySinh']);
    }

    /**
     * Phien cong chi 10 phut VA khoa theo IP, nen 401 de gap hon han luong cu. Gap 401 thi
     * lam moi token roi goi lai DUNG MOT LAN.
     */
    /** @test */
    public function gap_401_thi_lam_moi_token_va_goi_lai_mot_lan()
    {
        $login = new LoginServiceGiaLap();

        $sv = new McctTraCuuService('01929', $this->client([
            new Response(401, [], ''),
            new Response(200, [], $this->than200()),
        ]), $login);

        $kq = $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame('200', $kq->maKetQua);
        $this->assertCount(2, $this->daGui, 'Phai goi dung hai lan');
        $this->assertSame(1, $login->soLanLogout, 'Phai lam moi phien dung mot lan');
        $this->assertSame('TOKEN-A', $this->daGui[0]['request']->getHeaderLine('accessToken'));
        $this->assertSame('TOKEN-B', $this->daGui[1]['request']->getHeaderLine('accessToken'),
            'Lan goi thu hai phai mang token MOI, khong phai token cu');
    }

    /**
     * Lan hai van 401 thi DUNG - khong lap vo han. Nem ngoai le RIENG de controller phan
     * biet duoc voi loi mang, va noi duoc nguyen nhan (cong tra than RONG, tu no khong noi
     * duoc gi).
     */
    /** @test */
    public function lan_hai_van_401_thi_nem_ngoai_le_rieng()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(401, [], ''),
            new Response(401, [], ''),
        ]), new LoginServiceGiaLap());

        $this->expectException(McctXacThucException::class);

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');
    }

    /**
     * Ma 400/500 KHONG duoc goi lai: cong co danh sach tai khoan bi han che tra cuu, tu
     * nhan doi luot goi la tu chuoc lay no.
     */
    /** @test */
    public function ma_400_va_500_khong_goi_lai()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(500, [], json_encode([
                'MaKetQua' => '500',
                'GhiChu' => 'Có lỗi xảy ra trong quá trình tra cứu!',
            ])),
        ]), new LoginServiceGiaLap());

        $kq = $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame('500', $kq->maKetQua);
        $this->assertCount(1, $this->daGui, 'Ma 500 khong duoc sinh lan goi thu hai');
    }

    /**
     * Ma 400 cung khong duoc goi lai - cung ly do voi ma 500: doi luot goi la tu chuoc lay no.
     */
    /** @test */
    public function ma_400_khong_goi_lai()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(400, [], json_encode([
                'MaKetQua' => '400',
                'GhiChu' => 'Các tham số đầu vào không chính xác!',
            ])),
        ]), new LoginServiceGiaLap());

        $kq = $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame('400', $kq->maKetQua);
        $this->assertCount(1, $this->daGui, 'Ma 400 khong duoc sinh lan goi thu hai');
    }

    /** Loi mang cung khong duoc goi lai - cung ly do voi ma 500 */
    /** @test */
    public function loi_mang_khong_goi_lai()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new ConnectException('Khong noi duoc', new Request('POST', '/')),
        ]), new LoginServiceGiaLap());

        $this->expectException(ConnectException::class);

        try {
            $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');
        } finally {
            $this->assertCount(1, $this->daGui, 'Loi mang khong duoc sinh lan goi thu hai');
        }
    }

    /** @test */
    public function login_service_tra_dung_password_hash()
    {
        $this->assertSame('bam-mat-khau-01929', (new BHYTLoginService('01929'))->passwordHash());
    }
}
