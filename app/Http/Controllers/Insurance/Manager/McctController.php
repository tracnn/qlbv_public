<?php

namespace App\Http\Controllers\Insurance\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Services\Mcct\McctLuuTraCuu;
use App\Services\Mcct\McctPhanHoiJson;
use App\Services\Mcct\McctTraCuuService;
use App\Services\Mcct\McctXacThucException;
use App\Services\Mcct\NguongMienCungChiTra;
use Illuminate\Http\Request;

/**
 * Man tra cuu tien cung chi tra (MCCT) tren cong BHXH.
 *
 * Controller CHI ghep luong: khong chua logic nghiep vu. Nguong nam o
 * NguongMienCungChiTra (task 1), phan tich JSON cong tra ve nam o KetQuaMcct (task 2).
 */
class McctController extends Controller
{
    /**
     * Man tra cuu MCCT.
     *
     * CHI dung khung va dien san o nhap - KHONG goi cong. Cong tra ve cham (5-60 giay), nen
     * goi ngay trong lan nap trang se chan ca trang: trinh duyet trang, nguoi dung khong biet
     * chuyen gi dang xay ra va bam lai - moi lan bam la mot luot goi cong. Javascript goi
     * endpoint JSON roi dung ket qua tai cho.
     *
     * Duong dan /insurance/mcct/search?ma_the=... VAN dung duoc de gui cho nhau: trang nap len
     * voi tham so co san thi javascript tu tra ngay, khong phai bam lai.
     */
    public function index(Request $request)
    {
        $params = [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            'ma_the' => McctRequest::chuanHoaMaThe($request->get('ma_the')),
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];

        // Du bon truong thi tra ngay khi nap trang - do la truong hop di tu duong dan chia se
        // hoac tu man tra cuu the sang.
        $traNgay = $params['ma_cskcb'] !== '' && $params['ma_the'] !== ''
            && $params['ho_ten'] !== '' && $params['ngay_sinh'] !== '';

        return view('insurance.manager.mcct.index', [
            'params' => $params,
            'danhSachCoSo' => CoSoTraCuu::tuCauHinh(),
            'traNgay' => $traNgay,
        ]);
    }

    /**
     * Endpoint JSON cho modal tra cuu tren man tra cuu the BHYT.
     *
     * LUON tra HTTP 200 kem co `ok`, tru khi validate truot (Laravel tu tra 422 cho request
     * AJAX). Javascript nho vay chi doc MOT cho de biet ket qua - dung nhu chinh cong BHXH:
     * ma ket qua nam trong than, ma HTTP chi mang tinh tham khao.
     */
    public function api(McctRequest $request)
    {
        $params = $this->thamSo($request);

        if ($params['ma_cskcb'] === '') {
            return response()->json(
                McctPhanHoiJson::loi('Chọn cơ sở khám chữa bệnh rồi bấm Tra cứu')
            );
        }

        $ra = $this->thucHien($params);

        if ($ra['loi'] !== null) {
            return response()->json(McctPhanHoiJson::loi($ra['loi']));
        }

        $phanHoi = McctPhanHoiJson::tuKetQua($ra['kq'], $ra['nguong'], $ra['du_dieu_kien'],
            $params['ma_cskcb'], $ra['muc']);
        $phanHoi['loi_luu'] = $ra['loi_luu'];
        $phanHoi['lich_su'] = $this->lichSu($params['ma_the']);

        return response()->json($phanHoi);
    }

    /**
     * Loi tra cuu, dung chung cho ca man HTML lan endpoint JSON.
     *
     * VI SAO TACH RA: hai duong vao cung goi cong, cung tinh nguong, cung luu vet. Chep doi
     * nghia la moi lan sua phai nho sua ca hai cho - va cho bi quen se lech am tham.
     *
     * @param array $params bon khoa ma_cskcb, ma_the, ho_ten, ngay_sinh
     * @return array ['loi' => string|null, 'kq' => KetQuaMcct|null, 'nguong' => float,
     *                'du_dieu_kien' => bool|null, 'muc' => array|null (ket qua
     *                NguongMienCungChiTra::tinhTheoQuyDinh), 'loi_luu' => string|null]
     */
    private function thucHien(array $params)
    {
        $hong = function ($loi) {
            return ['loi' => $loi, 'kq' => null, 'nguong' => 0.0, 'du_dieu_kien' => null,
                'muc' => null, 'loi_luu' => null];
        };

        try {
            $kq = (new McctTraCuuService($params['ma_cskcb']))
                ->traCuu($params['ma_the'], $params['ho_ten'], $params['ngay_sinh']);
        } catch (McctXacThucException $e) {
            return $hong($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            // CauHinhCoSo nem khi co so chua khai tai khoan. Noi ro khai o dau - thong bao
            // chung chung khien nguoi dung di do nham sang phia cong.
            return $hong('Cơ sở ' . $params['ma_cskcb'] . ' chưa khai tài khoản cổng BHXH trong '
                . 'config/organization.php, khối BHYT_CO_SO.');
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Rieng loi mang: dat TRUOC nhanh \Exception de bat truoc, tranh bi nhanh do
            // "nuot" mat va gan nham tien to cau hinh cho loi mang.
            //
            // Tach rieng HET GIO khoi "khong ket noi duoc": hai chuyen khac han nhau. Het gio
            // nghia la cong CO song nhung tra loi qua cham - nguoi dung chi can thu lai, chu
            // khong phai di goi bo phan mang. Cau goc cua cURL ("Operation timed out after
            // 30006 milliseconds with 0 bytes received") khong noi duoc dieu do voi ho.
            if (mb_stripos($e->getMessage(), 'timed out') !== false
                || mb_stripos($e->getMessage(), 'timeout') !== false) {
                return $hong('Cổng BHXH không trả lời sau '
                    . (int) config('mcct.timeout_tong', 60) . ' giây. Cổng đang quá tải; '
                    . 'chờ ít phút rồi tra lại.');
            }

            return $hong('Không kết nối được cổng BHXH: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Tien to trung lap: CongBhxh::baseUrl() va BHYTLoginService::login() nem loi
            // CAU HINH (thieu base_url, thieu tai khoan...), khong phai loi mang. Gan cung
            // mot cau "khong ket noi duoc" se day nguoi doc di do nham huong mang.
            return $hong('Lỗi khi gọi cổng BHXH: ' . $e->getMessage());
        }

        $bangLuong = (array) config('mcct.luong_co_so', []);
        $soThang = (int) config('mcct.so_thang_luong_co_so', 6);

        $nguong = NguongMienCungChiTra::nguong(date('Y-m-d'), $bangLuong, $soThang);

        // Muc mien tinh THEO DUNG diem c khoan 2 Dieu 18 ND 188/2025: khi luong co so doi
        // giua nam, khong duoc lay thang 6 x luong hien hanh lam nguong. $nguong o tren van
        // duoc luu lai de doi chieu voi cac ban ghi cu, nhung KET LUAN lay tu day.
        $muc = NguongMienCungChiTra::tinhTheoQuyDinh($kq->dong, date('Y-m-d'), $bangLuong, $soThang);

        $duDieuKien = $kq->thanhCong() ? $muc['du_dieu_kien'] : null;

        // Luu hong thi VAN tra ket qua: luot goi len cong da tieu roi, va cong co danh sach
        // tai khoan bi han che tra cuu nen khong duoc de mot loi ghi CSDL nuot mat ca ket qua.
        // Mat dau vet con hon mat ca ket qua lan dau vet.
        $loiLuu = null;

        try {
            McctLuuTraCuu::luu($kq, array_merge($params, [
                'nguon' => 'thu_cong',
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

        return ['loi' => null, 'kq' => $kq, 'nguong' => $nguong, 'du_dieu_kien' => $duDieuKien,
            'muc' => $muc, 'loi_luu' => $loiLuu];
    }

    /**
     * Vai lan tra gan nhat cua chinh the do.
     *
     * Doc CSDL cung co the hong (bang chua duoc migrate) - khong duoc de mot loi doc lich su
     * xoa mat ket qua vua ton mot luot goi cong de lay ve.
     *
     * @param string $maThe
     * @return array
     */
    private function lichSu($maThe)
    {
        try {
            return McctTraCuu::where('ma_the', $maThe)
                ->orderBy('id', 'desc')->take(5)
                ->get(['tra_luc', 'ma_cskcb', 'ma_ket_qua', 'luy_ke_lon_nhat', 'nguong_ap_dung',
                    'tra_boi'])
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('MCCT khong doc duoc lich su tra cuu: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array bon khoa dau vao da chuan hoa
     */
    private function thamSo(McctRequest $request)
    {
        return [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            // Da chuan hoa trong McctRequest::prepareForValidation() TRUOC khi validate,
            // nen lay thang gia tri da merge, khong goi lai chuanHoaMaThe() o day.
            'ma_the' => $request->get('ma_the'),
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];
    }

}
