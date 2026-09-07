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
    public function index(Request $request)
    {
        return view('insurance.manager.mcct.index', [
            'params' => $this->thamSoRong(),
            'danhSachCoSo' => CoSoTraCuu::tuCauHinh(),
        ]);
    }

    public function search(McctRequest $request)
    {
        $params = $this->thamSo($request);

        $duLieu = [
            'params' => $params,
            'danhSachCoSo' => CoSoTraCuu::tuCauHinh(),
        ];

        // Thieu ma co so thi DUNG LAI o man da dien san chu khong bao loi: nguoi dung khong
        // lam gi sai, va cung chua biet dung tai khoan cua co so nao de goi.
        if ($params['ma_cskcb'] === '') {
            flash('Chọn cơ sở khám chữa bệnh rồi bấm Tra cứu')->warning();

            return view('insurance.manager.mcct.index', $duLieu);
        }

        $ra = $this->thucHien($params);

        if ($ra['loi'] !== null) {
            flash($ra['loi'])->error();

            return view('insurance.manager.mcct.index', $duLieu);
        }

        // Thong bao lay tu CUNG mot cho voi endpoint JSON, de mot cau chu chi ton tai o mot noi.
        $phanHoi = McctPhanHoiJson::tuKetQua($ra['kq'], $ra['nguong'], $ra['du_dieu_kien'],
            $params['ma_cskcb'], $ra['muc']);

        if ($phanHoi['thong_bao'] !== null) {
            $thongBao = flash($phanHoi['thong_bao']);
            $phanHoi['muc_do'] === 'warning' ? $thongBao->warning() : $thongBao->error();
        }

        if ($ra['loi_luu'] !== null) {
            flash($ra['loi_luu'])->warning();
        }

        // Doc lich su cung co the hong vi cung ly do (bang chua duoc migrate) - khong duoc
        // de trang trang xoa mat ket qua vua tra cuu duoc.
        try {
            $lichSu = McctTraCuu::where('ma_the', $params['ma_the'])
                ->orderBy('id', 'desc')->take(5)->get();
        } catch (\Exception $e) {
            \Log::error('MCCT khong doc duoc lich su tra cuu: ' . $e->getMessage());
            $lichSu = [];
        }

        return view('insurance.manager.mcct.index', array_merge($duLieu, [
            'ketQua' => $ra['kq'],
            'nguong' => $ra['nguong'],
            'duDieuKien' => $ra['du_dieu_kien'],
            'muc' => $ra['muc'],
            'lichSu' => $lichSu,
        ]));
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

    private function thamSoRong()
    {
        return [
            'ma_cskcb' => '',
            'ma_the' => '',
            'ho_ten' => '',
            'ngay_sinh' => '',
        ];
    }
}
