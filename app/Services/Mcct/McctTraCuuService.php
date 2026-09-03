<?php

namespace App\Services\Mcct;

use App\Services\BHYT\CongBhxh;
use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Goi ham tra cuu tien cung chi tra (MCCT) tren cong BHXH.
 *
 * Day la noi DUY NHAT trong luong MCCT cham mang. Nho vay bon lop con lai (KetQuaMcct,
 * NguongMienCungChiTra, McctLuuTraCuu, McctRequest) kiem duoc ma khong can mock gi.
 *
 * KHONG dung App\BHYT: lop do la static, co sau diem goi dang chay san xuat, va dang mang
 * san vet "roi ve tai khoan chot cung". Ham nay lai truyen token qua HEADER va body JSON,
 * khac han kieu query-string + form_params cua cac ham cu.
 */
class McctTraCuuService
{
    /** @var Client */
    private $httpClient;

    /** @var BHYTLoginService */
    private $loginService;

    /**
     * @param string $maCskcb ma co so KCB, quyet dinh dung tai khoan cong BHXH nao
     * @param Client|null $httpClient chi de kiem tiem vao; san xuat de null
     * @param BHYTLoginService|null $loginService chi de kiem tiem vao; san xuat de null
     */
    public function __construct($maCskcb, Client $httpClient = null, BHYTLoginService $loginService = null)
    {
        $this->loginService = $loginService ?: new BHYTLoginService($maCskcb);
        $this->httpClient = $httpClient ?: new Client();
    }

    /**
     * @param string $maThe da chuan hoa (bo khoang trang, viet hoa)
     * @param string $hoTen
     * @param string $ngaySinh dd/MM/yyyy hoac MM/yyyy hoac yyyy
     * @return KetQuaMcct
     * @throws McctXacThucException khi 401 ca hai lan
     * @throws \GuzzleHttp\Exception\GuzzleException khi loi mang
     */
    public function traCuu($maThe, $hoTen, $ngaySinh)
    {
        $phanHoi = $this->goi($maThe, $hoTen, $ngaySinh);

        // 401 lan dau: phien cong chi 10 phut, rat co the token trong cache da het han.
        // Xoa token roi dang nhap lai va goi lai DUNG MOT LAN.
        if ($phanHoi['ma_http'] === 401) {
            $this->loginService->logout();
            $phanHoi = $this->goi($maThe, $hoTen, $ngaySinh);

            if ($phanHoi['ma_http'] === 401) {
                throw new McctXacThucException(
                    'Không xác thực được với cổng BHXH sau khi đã lấy lại phiên. '
                    . 'Kiểm tra: phiên hết hạn, hoặc IP máy chủ gọi khác IP lúc lấy token '
                    . '(cổng khoá theo IP).'
                );
            }
        }

        $kq = KetQuaMcct::tuMang($phanHoi['than']);

        // KHONG ghi accessToken hay passwordHash vao log.
        Log::info('MCCT tra cuu', [
            'ma_the' => $maThe,
            'ma_ket_qua' => $kq->maKetQua,
            'ma_http' => $phanHoi['ma_http'],
        ]);

        // Ma 400 dang le KHONG the xay ra: McctRequest da kiem do dai ma the va dinh dang
        // ngay sinh truoc khi goi. Xay ra tuc la luat kiem cua minh lech voi cong - ghi lai
        // dung tham so da gui de doi chieu. Body chi co bon truong, khong chua bi mat nao
        // (accessToken/passwordHash di o HEADER, khong o day).
        if ($phanHoi['ma_http'] === 400) {
            Log::warning('MCCT bi cong tu choi 400 du da kiem dau vao', [
                'ma_the' => $maThe,
                'ho_ten' => $hoTen,
                'ngay_sinh' => $ngaySinh,
                'ghi_chu' => $kq->ghiChu,
            ]);
        }

        return $kq;
    }

    /**
     * Mot lan goi. Tra ve ca ma HTTP vi 401 khong co than de doc.
     *
     * @return array ['ma_http' => int, 'than' => array]
     */
    private function goi($maThe, $hoTen, $ngaySinh)
    {
        $res = $this->httpClient->post(CongBhxh::url(config('mcct.duong_dan')), [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'accessToken' => $this->loginService->getAccessToken(),
                'tokenId' => $this->loginService->getIdToken(),
                'passwordHash' => $this->loginService->passwordHash(),
            ],
            'json' => [
                'username' => $this->loginService->username(),
                'maThe' => $maThe,
                'hoTen' => $hoTen,
                'ngaySinh' => $ngaySinh,
            ],
            'connect_timeout' => (int) config('mcct.timeout_ket_noi', 10),
            'timeout' => (int) config('mcct.timeout_tong', 30),
            // Doc than cua 4xx/5xx thay vi de Guzzle nem: ma 400/500 CO than JSON mang
            // thong tin phan biet duoc (vd tai khoan bi han che tra cuu). Nem di la vut
            // mat dung cai can doc.
            'http_errors' => false,
        ]);

        $than = json_decode((string) $res->getBody(), true);

        return [
            'ma_http' => $res->getStatusCode(),
            'than' => is_array($than) ? $than : [],
        ];
    }
}
