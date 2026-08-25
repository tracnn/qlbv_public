<?php

namespace App\Services\Tt12;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

use App\Services\BHYTLoginService;
use App\Services\BHYT\CauHinhCoSo;

/**
 * Gui mot goi danh muc da ky len cong BHXH theo TT12.
 *
 * VI SAO KHONG DUNG LAI BHYTXmlSubmitService: khuon giong (xac thuc o header, body
 * form-urlencoded) nhung TEN TRUONG khac - TT12 dung loaiHs/fileHsBase64 con QD4750 dung
 * loaiHoSo/fileHSBase64 - va ma loi TT12 nam trong THAN phan hoi. Ep chung mot ham thi
 * mot trong hai giao thuc se im lang gui sai truong.
 *
 * BAY LON NHAT CUA GIAO THUC NAY: ma 401 nam trong THAN phan hoi (maKetQua), khong phai
 * ma HTTP. Cong tra HTTP 200 kem {"maKetQua":"401"}. Khuon retry cua
 * CongDuLieuYTeDienBienXmlSubmitService bat HTTP 401 - chep y nguyen sang day la khong
 * bao gio retry, va moi lan token het han thanh mot ho so gui hong.
 *
 * KHONG khai kieu tra ve (`: array`) tren gui(): Mockery trong du an nay vo khi mock
 * phuong thuc co return type.
 */
class Tt12SubmitService
{
    /** @var Client */
    private $httpClient;

    /** @var BHYTLoginService|null chi khac null khi duoc TIEM (vi du trong test) */
    private $loginService;

    public function __construct(BHYTLoginService $loginService = null)
    {
        $this->httpClient = new Client(array(
            'timeout'         => 120,
            'connect_timeout' => 5,
        ));

        $this->loginService = $loginService;
    }

    /** Thay Guzzle client - chi dung trong test */
    public function dungClient(Client $client)
    {
        $this->httpClient = $client;
    }

    /**
     * @param string $xmlDaKy noi dung XML da ky
     * @param string $mau     MAU_01..MAU_06
     * @param string $maCskcb ma co so cua CHINH ho so - phai cung co so voi token
     * @return array ['ma_ket_qua', 'ma_gd', 'thoi_gian_tiep_nhan', 'thong_diep', 'nguyen_van']
     * @throws \InvalidArgumentException|\Exception
     */
    public function gui($xmlDaKy, $mau, $maCskcb)
    {
        $lop = Tt12MauRegistry::cho($mau);   // nem \InvalidArgumentException neu mau la

        $url = $lop::url();
        $loaiHs = $lop::loaiHs();

        if ($url === '' || $loaiHs === '') {
            throw new \InvalidArgumentException('Thiếu cấu hình url/loaiHs cho mẫu ' . $mau);
        }

        // Dung login service theo dung ma co so cua ho so nay. Neu token va maCSKCB trong
        // body thuoc HAI co so khac nhau thi cong van nhan, va ho so bi ghi sai don vi
        // gui - hong im lang, khong lo ra cho toi luc doi soat.
        $login = $this->loginService ?: new BHYTLoginService($maCskcb);

        $base64 = base64_encode($xmlDaKy);

        // Ghi kich thuoc moi lan gui: tai lieu khong noi nguong cua ma 1001 (file size
        // qua dai), phai tu do tu thuc te.
        Log::info('TT12 gui ho so', array(
            'mau'             => $mau,
            'loai_hs'         => $loaiHs,
            'so_ky_tu_base64' => strlen($base64),
        ));

        $ketQua = $this->motLanGui($url, $loaiHs, $base64, $maCskcb, $login);

        // Ma 401 = token bi tu choi. Xoa cache token, dang nhap lai, thu lai DUNG MOT LAN.
        if ((string) $ketQua['ma_ket_qua'] === '401') {
            Log::warning('TT12: token bi tu choi (maKetQua 401), dang nhap lai va gui lai', array(
                'mau' => $mau,
            ));

            $login->logout();

            $ketQua = $this->motLanGui($url, $loaiHs, $base64, $maCskcb, $login);
        }

        return $ketQua;
    }

    /** Mot lan goi mang, khong retry */
    private function motLanGui($url, $loaiHs, $base64, $maCskcb, BHYTLoginService $login)
    {
        $truong = config('tt12.truong_body');

        // Ten truong lay tu cau hinh: tep PDF cua BHXH bi loi tim-thay-the nen ta chua
        // chac chan 'maCSKCB' hay mot bien the khac. Doi duoc ma khong sua ma nguon.
        //
        // MA TINH SUY TU MA CO SO, khong doc tu cau hinh: config/organization.php da ghi
        // ro "KHONG khai ma_tinh: no luon la hai ky tu dau cua ma co so (01929 -> 01,
        // 37470 -> 37)". Chot cung mot ma tinh trong cau hinh se gui ho so Ninh Binh voi
        // maTinh=01 - cong co the van nhan, va ho so bi ghi sai tinh.
        $body = array(
            $truong['username']    => $login->username(),
            $truong['loai_hs']     => (string) $loaiHs,
            $truong['ma_tinh']     => CauHinhCoSo::maTinh($maCskcb),
            $truong['ma_cskcb']    => (string) $maCskcb,
            $truong['file_base64'] => $base64,
        );

        $headers = array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'accessToken'   => $login->getAccessToken(),
            'tokenId'       => $login->getIdToken(),
            'passwordHash'  => $login->password(),
        );

        try {
            $response = $this->httpClient->post($url, array(
                'headers'     => $headers,
                'form_params' => $body,
            ));

            $nguyenVan = (string) $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            // NEM chu khong nuot: mang chap la loi TAM THOI. Bien no thanh mot ket qua
            // "that bai" se lam ho so mat co hoi duoc hang doi thu lai.
            Log::error('TT12 gui that bai (loi mang): ' . $e->getMessage(), array('url' => $url));

            throw new \Exception('Lỗi gọi cổng BHXH: ' . $e->getMessage(), 0, $e);
        }

        return $this->docPhanHoi($nguyenVan);
    }

    /**
     * Doc phan hoi. Cong doi khi tra HTML (502 tu proxy) thay vi JSON.
     *
     * @return array
     */
    private function docPhanHoi($nguyenVan)
    {
        $json = json_decode($nguyenVan, true);

        if (!is_array($json)) {
            Log::error('TT12: phan hoi khong phai JSON', array(
                'nguyen_van' => mb_substr($nguyenVan, 0, 500),
            ));

            return array(
                'ma_ket_qua'          => null,
                'ma_gd'               => null,
                'thoi_gian_tiep_nhan' => null,
                'thong_diep'          => 'Cổng trả về nội dung không đọc được (không phải JSON)',
                'nguyen_van'          => $nguyenVan,
            );
        }

        // Tai lieu viet khoa thuong o vi du body va khoa hoa o bang dac ta dau ra
        // (maKetQua so voi MaKetQua). Doc ca hai.
        $maKetQua = $this->lay($json, array('maKetQua', 'MaKetQua'));

        return array(
            'ma_ket_qua'          => $maKetQua === null ? null : (string) $maKetQua,
            'ma_gd'               => $this->lay($json, array('maGiaoDich', 'MaGiaoDich')),
            'thoi_gian_tiep_nhan' => $this->lay($json, array('thoiGianTiepNhan', 'ThoiGianTiepNhan')),
            'thong_diep'          => $this->thongDiep($json, $maKetQua),
            'nguyen_van'          => $nguyenVan,
        );
    }

    private function lay(array $json, array $cacKhoa)
    {
        foreach ($cacKhoa as $khoa) {
            if (array_key_exists($khoa, $json) && $json[$khoa] !== '') {
                return $json[$khoa];
            }
        }

        return null;
    }

    private function thongDiep(array $json, $maKetQua)
    {
        $tuCong = $this->lay($json, array('thongDiep', 'ThongDiep'));

        if (!empty($tuCong)) {
            return (string) $tuCong;
        }

        if ($maKetQua === null) {
            return 'Cổng không trả về mã kết quả';
        }

        // array_key_exists chu khong ===: PHP ep khoa mang '200' thanh int 200, nen
        // duyet mang va so sanh nghiem ngat voi chuoi se LUON truot.
        $bang = config('tt12.ma_ket_qua', array());

        return array_key_exists((string) $maKetQua, $bang)
            ? 'Mã ' . $maKetQua . ': ' . $bang[(string) $maKetQua]
            : 'Mã ' . $maKetQua;
    }
}
