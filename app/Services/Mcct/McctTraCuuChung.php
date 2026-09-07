<?php

namespace App\Services\Mcct;

/**
 * Loi mot lan tra cuu MCCT: goi cong -> tinh nguong -> luu vet.
 *
 * VI SAO TACH RA KHOI CONTROLLER: ca man web lan API cho he thong ngoai deu can DUNG chuoi
 * nay. Chep doi nghia la co hai cho quyet dinh "mot lan tra cuu nghia la gi" - va du an nay
 * da bi can ba lan vi chep doi (hai con so timeout, hai bo dung ket qua javascript).
 *
 * Lop nay KHONG biet gi ve HTTP: khong doc Request, khong tra Response. Ai goi thi tu doi
 * ket qua sang khuon cua minh.
 */
class McctTraCuuChung
{
    /**
     * @param array $params bon khoa ma_cskcb, ma_the, ho_ten, ngay_sinh (da chuan hoa)
     * @param string $nguon ghi vao cot nguon: 'thu_cong' | 'hang_loat' | 'api_his'
     * @return array ['loi' => string|null, 'ma_loi' => string|null, 'kq' => KetQuaMcct|null,
     *                'nguong' => float, 'du_dieu_kien' => bool|null, 'muc' => array|null,
     *                'loi_luu' => string|null]
     */
    public static function goiVaLuu(array $params, $nguon = 'thu_cong')
    {
        $hong = function ($maLoi, $loi) {
            return ['loi' => $loi, 'ma_loi' => $maLoi, 'kq' => null, 'nguong' => 0.0,
                'du_dieu_kien' => null, 'muc' => null, 'loi_luu' => null];
        };

        try {
            $kq = (new McctTraCuuService($params['ma_cskcb']))
                ->traCuu($params['ma_the'], $params['ho_ten'], $params['ngay_sinh']);
        } catch (McctXacThucException $e) {
            return $hong('XAC_THUC', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            // CauHinhCoSo nem khi co so chua khai tai khoan. Noi ro khai o dau - thong bao
            // chung chung khien nguoi dung di do nham sang phia cong.
            return $hong('CAU_HINH', 'Cơ sở ' . $params['ma_cskcb'] . ' chưa khai tài khoản '
                . 'cổng BHXH trong config/organization.php, khối BHYT_CO_SO.');
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Tach rieng HET GIO khoi "khong ket noi duoc": hai chuyen khac han nhau. Het gio
            // nghia la cong CO song nhung tra loi qua cham - chi can thu lai, chu khong phai
            // di goi bo phan mang.
            if (mb_stripos($e->getMessage(), 'timed out') !== false
                || mb_stripos($e->getMessage(), 'timeout') !== false) {
                return $hong('HET_GIO', 'Cổng BHXH không trả lời sau '
                    . (int) config('mcct.timeout_tong', 60) . ' giây. Cổng đang quá tải; '
                    . 'chờ ít phút rồi tra lại.');
            }

            return $hong('MANG', 'Không kết nối được cổng BHXH: ' . $e->getMessage());
        } catch (\Exception $e) {
            // CongBhxh::baseUrl() va BHYTLoginService::login() nem loi CAU HINH (thieu
            // base_url, thieu tai khoan...), khong phai loi mang. Gan cung mot cau "khong ket
            // noi duoc" se day nguoi doc di do nham huong mang.
            return $hong('KHAC', 'Lỗi khi gọi cổng BHXH: ' . $e->getMessage());
        }

        $bangLuong = (array) config('mcct.luong_co_so', []);
        $soThang = (int) config('mcct.so_thang_luong_co_so', 6);

        $nguong = NguongMienCungChiTra::nguong(date('Y-m-d'), $bangLuong, $soThang);

        // Muc mien tinh THEO DUNG diem c khoan 2 Dieu 18 ND 188/2025: khi luong co so doi
        // giua nam, khong duoc lay thang 6 x luong hien hanh lam nguong.
        $muc = NguongMienCungChiTra::tinhTheoQuyDinh($kq->dong, date('Y-m-d'), $bangLuong, $soThang);

        $duDieuKien = $kq->thanhCong() ? $muc['du_dieu_kien'] : null;

        // Luu hong thi VAN tra ket qua: luot goi len cong da tieu roi, va cong co danh sach
        // tai khoan bi han che tra cuu nen khong duoc de mot loi ghi CSDL nuot mat ca ket qua.
        $loiLuu = null;

        try {
            McctLuuTraCuu::luu($kq, array_merge($params, [
                'nguon' => $nguon,
                'tra_boi' => \Auth::check() ? \Auth::user()->username : null,
                'nguong' => $nguong,
                'du_dieu_kien' => $duDieuKien,
                'so_tien_con_phai_dong' => $muc['so_tien_con_phai_dong'],
                'da_dong_truoc_moc' => $muc['da_dong_truoc_moc'],
            ]));
        } catch (\Exception $e) {
            \Log::error('MCCT khong luu duoc lich su tra cuu: ' . $e->getMessage());
            $loiLuu = 'Đã tra cứu được nhưng không lưu được lịch sử tra cứu.';
        }

        return ['loi' => null, 'ma_loi' => null, 'kq' => $kq, 'nguong' => $nguong,
            'du_dieu_kien' => $duDieuKien, 'muc' => $muc, 'loi_luu' => $loiLuu];
    }
}
