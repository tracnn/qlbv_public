<?php

namespace App\Services\Ctdt;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

use App\Services\BHYTLoginService;

/**
 * Gui mot goi chung tu dien tu da ky len cong BHXH theo Phu luc 02.
 *
 * VI SAO KHONG DUNG LAI BHYTXmlSubmitService: hai giao thuc khac nhau that su, khong phai
 * khac tham so. BHYTXmlSubmitService dat xac thuc o HEADER (accessToken, tokenId,
 * passwordHash) va body dung ten truong khac (loaiHoSo, maTinh, maCSKCB, fileHSBase64).
 * PL02 dat CA BAY TRUONG trong body voi ten khac han. Ep chung mot ham la cong tu choi ma
 * khong noi vi sao.
 *
 * BAY LON NHAT CUA GIAO THUC NAY: ma 401 nam trong THAN phan hoi (MaKetQua), khong phai ma
 * HTTP. Cong tra HTTP 200 kem {"MaKetQua":"401"}. Khuon retry cua
 * CongDuLieuYTeDienBienXmlSubmitService bat HTTP 401 - chep y nguyen sang day la khong bao
 * gio retry, va moi lan token het han thanh mot ho so gui hong.
 */
class CtdtSubmitService
{
    /** @var Client */
    private $httpClient;

    /** @var BHYTLoginService */
    private $loginService;

    public function __construct(BHYTLoginService $loginService = null)
    {
        $this->httpClient = new Client([
            'timeout'         => 60,
            'connect_timeout' => 5,
        ]);

        $this->loginService = $loginService ?: new BHYTLoginService();
    }

    /**
     * @param string $xmlDaKy Noi dung XML da ky
     * @param string $dichVu  CT2025 | GBT | GCS
     * @param string $maCskcb Ma co so cua CHINH ho so - phai cung co so voi token
     * @return array ['ma_ket_qua', 'ma_gd', 'thoi_gian_tiep_nhan', 'thong_diep', 'nguyen_van']
     * @throws \InvalidArgumentException|\Exception
     */
    public function gui($xmlDaKy, $dichVu, $maCskcb)
    {
        $cauHinh = config('ctdt.dich_vu.' . $dichVu);

        if (empty($cauHinh['url']) || empty($cauHinh['loai_hs'])) {
            throw new \InvalidArgumentException('Dich vu khong biet: ' . (string) $dichVu);
        }

        $base64 = base64_encode($xmlDaKy);

        // Ghi kich thuoc moi lan gui: tai lieu khong noi nguong cua ma 1001 (file size qua
        // dai), phai tu do tu thuc te.
        Log::info('CTDT gui ho so', [
            'dich_vu'         => $dichVu,
            'loai_hs'         => $cauHinh['loai_hs'],
            'so_ky_tu_base64' => strlen($base64),
        ]);

        $ketQua = $this->motLanGui($cauHinh, $base64, $maCskcb);

        // Ma 401 = token bi tu choi. Xoa cache token, dang nhap lai, thu lai DUNG MOT LAN.
        // BHYTLoginService cache token theo co so va token co the het han giua chung.
        if ($this->la401($ketQua['ma_ket_qua'])) {
            Log::warning('CTDT: token bi tu choi (MaKetQua 401), dang nhap lai va gui lai', [
                'dich_vu' => $dichVu,
            ]);

            $this->loginService->logout();

            $ketQua = $this->motLanGui($cauHinh, $base64, $maCskcb);
        }

        return $ketQua;
    }

    /** Mot lan goi mang, khong retry */
    private function motLanGui(array $cauHinh, $base64, $maCskcb)
    {
        // Token VA tai khoan lay tu CUNG mot loginService. Neu chung thuoc hai co so khac
        // nhau thi cong van nhan, va ho so bi ghi sai don vi gui - hong im lang, khong co
        // dau hieu gi cho toi luc doi soat.
        $body = [
            'maCskcb'       => (string) $maCskcb,
            'token'         => $this->loginService->getAccessToken(),
            'id_token'      => $this->loginService->getIdToken(),
            'username'      => $this->loginService->username(),
            'password'      => $this->loginService->password(),
            'loaiHs'        => (string) $cauHinh['loai_hs'],
            'fileBase64Str' => $base64,
        ];

        try {
            $response = $this->httpClient->post($cauHinh['url'], [
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $body,
            ]);

            $nguyenVan = (string) $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            // NEM chu khong nuot: mang chap la loi TAM THOI. Bien no thanh mot ket qua
            // "that bai" se lam ho so mat co hoi duoc hang doi thu lai.
            Log::error('CTDT gui that bai (loi mang): ' . $e->getMessage(), [
                'url' => $cauHinh['url'],
            ]);

            throw new \Exception('Loi goi cong BHXH: ' . $e->getMessage(), 0, $e);
        }

        return $this->docPhanHoi($nguyenVan);
    }

    private function docPhanHoi($nguyenVan)
    {
        $than = json_decode($nguyenVan, true);

        if (!is_array($than)) {
            $than = [];
        }

        $maKetQua = isset($than['MaKetQua']) ? (string) $than['MaKetQua'] : '';

        return [
            'ma_ket_qua'          => $maKetQua,
            'ma_gd'               => isset($than['MaGD']) ? (string) $than['MaGD'] : null,
            'thoi_gian_tiep_nhan' => isset($than['ThoiGianTiepNhan'])
                ? (string) $than['ThoiGianTiepNhan'] : null,
            'thong_diep'          => $this->moTa($maKetQua),
            'nguyen_van'          => $nguyenVan,
        ];
    }

    /**
     * BAY KHOA MANG: PHP ep khoa mang dang chuoi so thanh int, vd '200' thanh int(200).
     * array_key_exists('200', $mang) van dung (PHP tu ep chuoi truy van thanh int), nhung
     * so sanh === giua khoa va chuoi thi LUON truot. Da canh bao trong config/ctdt.php.
     */
    private function moTa($maKetQua)
    {
        if ($maKetQua === '') {
            return 'Cong khong tra ve MaKetQua';
        }

        $danhMuc = config('ctdt.ma_ket_qua', []);

        if (array_key_exists($maKetQua, $danhMuc)) {
            return 'Mã ' . $maKetQua . ': ' . $danhMuc[$maKetQua];
        }

        // Ma ngoai danh muc van phai doc duoc: BHXH co the them ma moi, va hien mot o trong
        // la nguoi van hanh khong biet chuyen gi da xay ra.
        return 'Mã ' . $maKetQua . ': không có trong danh mục';
    }

    /** So sanh LONG: cong co the tra so 401 thay vi chuoi '401' */
    private function la401($maKetQua)
    {
        return $maKetQua !== '' && $maKetQua == '401';
    }
}
