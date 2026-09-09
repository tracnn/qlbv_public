<?php

namespace App\Services\BHYT;

use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Tra cuu LICH SU KHAM CHUA BENH tren cong BHXH (ham Lskcb2025).
 *
 * VI SAO CO LOP NAY: ham tra cuu the (KQNhanLichSuKCB2024) truoc day tra kem khoi
 * `dsLichSuKCB2018`, nay cong luon tra ve null - bang lich su KCB tren man tra cuu the con
 * nguyen nhung mat nguon. Cong van 2746/BHXH-CNTT tach phan lich su sang ham RIENG
 * `Lskcb2025`. Lop nay la nguon moi cho dung cai bang cu do.
 *
 * KHONG them ham vao App\BHYT: lop do la static, co sau diem goi dang chay san xuat va
 * khong kiem duoc neu khong goi mang that. Day la dung tien le da lap voi McctTraCuuService.
 *
 * KHONG BAO GIO NEM RA NGOAI. Day la mot loi goi mang THEM VAO mot luong dang chay tot:
 * cong hong, het gio hay tra ma la, deu chi duoc lam mat cai bang lich su, tuyet doi khong
 * duoc lam hong ket qua tra the. Moi duong that bai deu ve mang rong + mot dong log.
 */
class LichSuKcb
{
    /**
     * Duong dan do BHXH quy dinh - HANG SO GIAO THUC, di theo kho ma.
     *
     * Host lay tu organization.BHYT.base_url qua CongBhxh, nen doi moi truong (chinh thuc
     * <-> daotaoegw) chi sua MOT dong, va ban cai san co khong phai khai them gi.
     */
    const DUONG_DAN = '/api/egw/Lskcb2025';

    /** Khoa cau hinh cong tac bat/tat. Vang mat = BAT. */
    const KHOA_BAT = 'organization.BHYT.lich_su_kcb_enabled';

    const TIMEOUT_KET_NOI = 15;
    const TIMEOUT_TONG = 60;

    /** @var string */
    private $maCskcb;

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
        $this->maCskcb = trim((string) $maCskcb);
        $this->httpClient = $httpClient ?: new Client();
        $this->loginService = $loginService ?: new BHYTLoginService($maCskcb);
    }

    /**
     * @param string $maThe
     * @param string $hoTen
     * @param string $ngaySinh dd/MM/yyyy (chiu duoc ca dang 8 chu so lien)
     * @return array danh sach dot KCB; MANG RONG khi tat, khi cong bao loi, hoac khi hong
     */
    public function tra($maThe, $hoTen, $ngaySinh)
    {
        // So NULL rieng: config() chi tra ve gia tri mac dinh khi khoa VANG MAT, con khoa co
        // mat voi gia tri null thi tra ve null. Ca hai truong hop deu la "chua khai" nen
        // deu phai la BAT - khong duoc de mot dong khai do dang lang le tat tinh nang.
        $bat = config(self::KHOA_BAT, true);

        if ($bat !== null && !$bat) {
            return [];
        }

        try {
            $res = $this->httpClient->post(CongBhxh::url(self::DUONG_DAN), [
                // Bien the DA KIEM CHUNG bang mot lan goi that len cong: form-urlencoded cho
                // than, token qua QUERY STRING (khac han kieu header cua ham MCCT), ngay sinh
                // dang dd/MM/yyyy. Bang mo ta trong cong van ghi application/json nhung than
                // JSON bi cong tu choi.
                'query' => [
                    'token' => $this->loginService->getAccessToken(),
                    'id_token' => $this->loginService->getIdToken(),
                    'username' => $this->loginService->username(),
                    'password' => $this->loginService->password(),
                ],
                'form_params' => [
                    'maThe' => $maThe,
                    'hoTen' => $hoTen,
                    'ngaySinh' => self::chuanHoaNgaySinh($ngaySinh),
                    'hoTenCb' => $this->loginService->hoTenCb(),
                    'cccdCb' => $this->loginService->cccdCb(),
                ],
                'connect_timeout' => self::TIMEOUT_KET_NOI,
                'timeout' => self::TIMEOUT_TONG,
                // Doc than cua 4xx/5xx thay vi de Guzzle nem: than JSON co maKetQua noi ro
                // ly do, nem di la vut mat dung cai can doc.
                'http_errors' => false,
            ]);
        } catch (\Exception $e) {
            // Loi mang. KHONG ghi token hay mat khau - chung nam trong query string cua
            // request nen tuyet doi khong duoc log nguyen ca URL.
            Log::warning('Lskcb2025 loi mang', [
                'ma_cskcb' => $this->maCskcb,
                'ma_the' => $maThe,
                'loi' => $e->getMessage(),
            ]);

            return [];
        }

        $than = json_decode((string) $res->getBody(), true);
        $than = is_array($than) ? $than : [];

        $maKetQua = isset($than['maKetQua']) ? (string) $than['maKetQua'] : '';

        if ($maKetQua !== '000') {
            Log::warning('Lskcb2025 cong tu choi', [
                'ma_cskcb' => $this->maCskcb,
                'ma_the' => $maThe,
                'ma_http' => $res->getStatusCode(),
                'ma_ket_qua' => $maKetQua,
                'ghi_chu' => isset($than['ghiChu']) ? $than['ghiChu'] : null,
            ]);

            return [];
        }

        $ds = isset($than['dsLichSuKCB2025']) ? $than['dsLichSuKCB2025'] : null;

        // Cong tra null khi khong co dot KCB nao - khong phai loi, va view chi phan biet
        // "co dong" voi "khong co dong".
        return is_array($ds) ? $ds : [];
    }

    /**
     * Ngay sinh ve dang dd/MM/yyyy ma cong doi.
     *
     * VI SAO CAN: ma QR the BHYT tra ngay sinh dang 8 chu so lien (01011980) va man tra cuu
     * the KHONG kiem dinh dang o nay, nen chuoi do di thang toi day duoc. Cong tra loi voi
     * dang do. Cac dang khac (mm/yyyy, yyyy) giu nguyen - cong chap nhan.
     *
     * @param string $s
     * @return string
     */
    public static function chuanHoaNgaySinh($s)
    {
        $s = trim((string) $s);

        if (preg_match('/^\d{8}$/', $s)) {
            return substr($s, 0, 2) . '/' . substr($s, 2, 2) . '/' . substr($s, 4);
        }

        return $s;
    }
}
