<?php

namespace App\Http\Controllers\Insurance\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Services\Mcct\McctLuuTraCuu;
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
        $params = [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            'ma_the' => McctRequest::chuanHoaMaThe($request->get('ma_the')),
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];

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

        try {
            $kq = (new McctTraCuuService($params['ma_cskcb']))
                ->traCuu($params['ma_the'], $params['ho_ten'], $params['ngay_sinh']);
        } catch (McctXacThucException $e) {
            flash($e->getMessage())->error();

            return view('insurance.manager.mcct.index', $duLieu);
        } catch (\InvalidArgumentException $e) {
            // CauHinhCoSo nem khi co so chua khai tai khoan. Noi ro khai o dau - thong bao
            // chung chung khien nguoi dung di do nham sang phia cong.
            flash('Cơ sở ' . $params['ma_cskcb'] . ' chưa khai tài khoản cổng BHXH trong '
                . 'config/organization.php, khối BHYT_CO_SO.')->error();

            return view('insurance.manager.mcct.index', $duLieu);
        } catch (\Exception $e) {
            flash('Không kết nối được cổng BHXH: ' . $e->getMessage())->error();

            return view('insurance.manager.mcct.index', $duLieu);
        }

        $nguong = NguongMienCungChiTra::nguong(
            date('Y-m-d'),
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6)
        );

        $duDieuKien = $kq->thanhCong()
            ? NguongMienCungChiTra::duDieuKien($kq->luyKeLonNhat(), $nguong)
            : null;

        McctLuuTraCuu::luu($kq, array_merge($params, [
            'nguon' => 'thu_cong',
            'tra_boi' => \Auth::check() ? \Auth::user()->username : null,
            'nguong' => $nguong,
            'du_dieu_kien' => $duDieuKien,
        ]));

        $this->baoTrangThai($kq);

        return view('insurance.manager.mcct.index', array_merge($duLieu, [
            'ketQua' => $kq,
            'nguong' => $nguong,
            'duDieuKien' => $duDieuKien,
            'lichSu' => McctTraCuu::where('ma_the', $params['ma_the'])
                ->orderBy('id', 'desc')->take(5)->get(),
        ]));
    }

    /**
     * Doi ma ket qua cua cong thanh thong bao.
     *
     * Ma 500 duoc TACH LAM HAI theo noi dung GhiChu: "loi trong qua trinh tra cuu" nghia la
     * tai khoan bi han che tra cuu - van de tai khoan, khong phai loi he thong. Gop chung se
     * day nguoi doc di do nham huong hang gio.
     */
    private function baoTrangThai($kq)
    {
        if ($kq->maKetQua === '200') {
            return;
        }

        if ($kq->maKetQua === '204') {
            flash('Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, hoặc thẻ chưa phát '
                . 'sinh chi phí cùng chi trả.')->warning();

            return;
        }

        if ($kq->maKetQua === '500' && mb_strpos($kq->ghiChu, 'quá trình tra cứu') !== false) {
            flash('Tài khoản cổng BHXH của cơ sở đang bị hạn chế tra cứu. Liên hệ BHXH tỉnh '
                . 'để được mở.')->error();

            return;
        }

        flash('Cổng BHXH báo lỗi (' . $kq->maKetQua . '): ' . $kq->ghiChu)->error();
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
