<?php

namespace Tests\Unit\BHYT;

use App\Services\BHYT\LichSuKcb;
use App\Services\BHYTLoginService;
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
 * duoc, va no TU DANG NHAP LAI khi cache token trong - mot lan chay bo test da tao mot phien
 * dang nhap THAT len cong BHXH san xuat. Khong test nao trong tep nay duoc cham cong that.
 */
class LoginGiaLapLskcb extends BHYTLoginService
{
    public function __construct()
    {
        parent::__construct('01929');
    }

    public function getAccessToken(): string
    {
        return 'TOKEN-A';
    }

    public function getIdToken(): string
    {
        return 'ID-A';
    }

    public function username(): string
    {
        return '01929_BV';
    }

    public function password(): string
    {
        return 'mat-khau-01929';
    }

    public function hoTenCb(): string
    {
        return 'NGUYEN VAN CAN BO';
    }

    public function cccdCb(): string
    {
        return '000000000000';
    }
}

class LichSuKcbTest extends TestCase
{
    /** @var array cac request that su da di ra ngoai */
    protected $daGui = [];

    protected function setUp()
    {
        parent::setUp();

        $this->daGui = [];

        config([
            'organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn',
            'organization.BHYT.lich_su_kcb_enabled' => true,
        ]);
    }

    /**
     * Guzzle gia lap, ghi lai moi request di ra.
     *
     * Dung MockHandler chu KHONG dung Mockery: Mockery da nhieu lan vo voi cac lop khai bao
     * kieu tra ve trong du an nay.
     */
    protected function client(array $phanHoi)
    {
        $stack = HandlerStack::create(new MockHandler($phanHoi));
        $stack->push(Middleware::history($this->daGui));

        return new Client(['handler' => $stack]);
    }

    protected function dichVu(array $phanHoi)
    {
        return new LichSuKcb('01929', $this->client($phanHoi), new LoginGiaLapLskcb());
    }

    /** Than phan hoi THAT cua cong, cat bot cho gon. */
    protected function than000()
    {
        return json_encode([
            'maKetQua' => '000',
            'ghiChu' => null,
            'dsLichSuKCB2025' => [
                [
                    'maHoSo' => '01929202500001',
                    'maCSKCB' => '01929',
                    'ngayVao' => '202503101030',
                    'ngayRa' => '202503101430',
                    'tenBenh' => 'Viêm dạ dày',
                    'tinhTrang' => '4',
                    'kqDieuTri' => '1',
                    'lyDoVV' => '1',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @test */
    public function goi_dung_url_ghep_tu_base_url_va_duong_dan()
    {
        $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $uri = $this->daGui[0]['request']->getUri();

        $this->assertSame('https://egw.baohiemxahoi.gov.vn', $uri->getScheme() . '://' . $uri->getHost());
        $this->assertSame('/api/egw/Lskcb2025', $uri->getPath());
    }

    /**
     * Ham nay truyen token qua QUERY STRING + form_params, khac han kieu HEADER + than JSON
     * cua ham MCCT. Nham kieu la cong tu choi ma khong noi ly do.
     */
    /** @test */
    public function token_di_qua_query_string()
    {
        $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        parse_str($this->daGui[0]['request']->getUri()->getQuery(), $q);

        $this->assertSame('TOKEN-A', $q['token']);
        $this->assertSame('ID-A', $q['id_token']);
        $this->assertSame('01929_BV', $q['username']);
        $this->assertSame('mat-khau-01929', $q['password']);
    }

    /** @test */
    public function than_la_form_urlencoded_du_nam_truong()
    {
        $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $req = $this->daGui[0]['request'];

        $this->assertContains('application/x-www-form-urlencoded', $req->getHeaderLine('Content-Type'));

        parse_str((string) $req->getBody(), $than);

        $this->assertSame('HC4010127955384', $than['maThe']);
        $this->assertSame('NGUYEN VAN A', $than['hoTen']);
        $this->assertSame('20/02/1979', $than['ngaySinh']);
        $this->assertSame('NGUYEN VAN CAN BO', $than['hoTenCb']);
        $this->assertSame('000000000000', $than['cccdCb']);
    }

    /**
     * Ma QR the BHYT tra ngay sinh dang 8 chu so lien va man tra cuu the KHONG kiem dinh dang
     * o nay, nen chuoi do di thang toi day duoc. Cong tra loi voi dang do.
     */
    /** @test */
    public function ngay_sinh_tam_chu_so_duoc_chuan_hoa()
    {
        $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20021979');

        parse_str((string) $this->daGui[0]['request']->getBody(), $than);

        $this->assertSame('20/02/1979', $than['ngaySinh']);
    }

    /** @test */
    public function ngay_sinh_dang_khac_giu_nguyen()
    {
        $this->assertSame('1979', LichSuKcb::chuanHoaNgaySinh('1979'));
        $this->assertSame('02/1979', LichSuKcb::chuanHoaNgaySinh('02/1979'));
        $this->assertSame('20/02/1979', LichSuKcb::chuanHoaNgaySinh(' 20/02/1979 '));
    }

    /** @test */
    public function phan_tich_dung_danh_sach_dot_kcb()
    {
        $ds = $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertCount(1, $ds);
        $this->assertSame('01929202500001', $ds[0]['maHoSo']);
        $this->assertSame('202503101030', $ds[0]['ngayVao'],
            'Giu NGUYEN VAN chuoi ngay cua cong; view moi la noi dinh dang');
    }

    /**
     * Than phan hoi THAT tren san xuat hom nay: khong co dot nao thi cong tra mang rong.
     * Day khong phai loi - view chi phan biet "co dong" voi "khong co dong".
     */
    /** @test */
    public function khong_co_dot_nao_thi_tra_mang_rong()
    {
        $ds = $this->dichVu([new Response(200, [], '{"maKetQua":"000","dsLichSuKCB2025":[]}')])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
    }

    /** Cong tra null thay vi mang rong - da gap voi khoi dsLichSuKCB2018. */
    /** @test */
    public function danh_sach_null_thi_tra_mang_rong()
    {
        $ds = $this->dichVu([new Response(200, [], '{"maKetQua":"000","dsLichSuKCB2025":null}')])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
    }

    /** @test */
    public function ma_ket_qua_khac_000_thi_tra_mang_rong()
    {
        $ds = $this->dichVu([new Response(200, [], '{"maKetQua":"002","ghiChu":"Thẻ không tồn tại"}')])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
    }

    /** @test */
    public function than_khong_phai_json_thi_tra_mang_rong()
    {
        $ds = $this->dichVu([new Response(500, [], '<html>Bad Gateway</html>')])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
    }

    /**
     * DIEU QUAN TRONG NHAT cua lop nay: day la mot loi goi mang THEM VAO mot luong dang chay
     * tot. Cong hong khong duoc lam hong ket qua tra the da cam chac trong tay.
     */
    /** @test */
    public function loi_mang_khong_nem_ra_ngoai()
    {
        $ds = $this->dichVu([new ConnectException('Khong noi duoc', new Request('POST', '/'))])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
    }

    /** @test */
    public function tat_bang_cau_hinh_thi_khong_goi_cong()
    {
        config(['organization.BHYT.lich_su_kcb_enabled' => false]);

        $ds = $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertSame([], $ds);
        $this->assertCount(0, $this->daGui, 'Tat roi thi khong duoc gui request nao');
    }

    /**
     * config/organization.php nam trong .gitignore nen mot ban cai moi KHONG co khoa nay.
     * Vang mat phai la BAT: doi ban cai khai them mot khoa la tao ra mot cach de tinh nang
     * lang le bien mat ma khong ai biet.
     */
    /** @test */
    public function thieu_khoa_cau_hinh_thi_van_goi()
    {
        config(['organization.BHYT.lich_su_kcb_enabled' => null]);

        $this->dichVu([new Response(200, [], $this->than000())])
            ->tra('HC4010127955384', 'NGUYEN VAN A', '20/02/1979');

        $this->assertCount(1, $this->daGui);
    }
}
