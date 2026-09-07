<?php

namespace App\Http\Controllers\Insurance\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Models\Mcct\McctChiPhi;
use App\Services\Mcct\McctDungLaiKetQua;
use App\Services\Mcct\McctPhanHoiJson;
use App\Services\Mcct\McctTraCuuChung;
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
     * Endpoint JSON tra ve ket qua tra cuu DA LUU gan nhat cua mot ma the.
     *
     * CHI doc CSDL - khong cham cong, nen tra ve trong vai chuc mili-giay. Man hinh goi ham
     * nay TRUOC de hien ngay so lieu lan truoc, roi de nguoi dung tu quyet dinh co bam Tra
     * cuu lai hay khong. Cong tra ve 5-60 giay, va co danh sach tai khoan bi han che tra cuu.
     *
     * Chi lay lan tra THANH CONG (ma 200): mot thong bao loi cu hien lai khong giup duoc gi,
     * trong khi so lieu that gan nhat thi co. Cac lan hong van nam trong bang lich su.
     */
    public function ganNhat(Request $request)
    {
        $maThe = McctRequest::chuanHoaMaThe($request->get('ma_the'));

        if ($maThe === '') {
            return response()->json(McctDungLaiKetQua::khongCo());
        }

        try {
            $phien = McctTraCuu::where('ma_the', $maThe)
                ->where('ma_ket_qua', '200')
                ->orderBy('id', 'desc')->first();

            if ($phien === null) {
                return response()->json(McctDungLaiKetQua::khongCo());
            }

            $dong = McctChiPhi::where('tra_cuu_id', $phien->id)->orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            // Bang chua duoc migrate hoac CSDL hong: coi nhu chua co du lieu cu, de javascript
            // goi thang cong. Man hinh van dung duoc, chi la mat phan hien nhanh.
            \Log::error('MCCT khong doc duoc ket qua da luu: ' . $e->getMessage());

            return response()->json(McctDungLaiKetQua::khongCo());
        }

        $phanHoi = McctDungLaiKetQua::tuBanGhi(
            $phien->toArray(), $dong,
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6)
        );

        $phanHoi['loi_luu'] = null;
        $phanHoi['lich_su'] = $this->lichSu($maThe);

        return response()->json($phanHoi);
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

        $ra = McctTraCuuChung::goiVaLuu($params, 'thu_cong');

        if ($ra['loi'] !== null) {
            return response()->json(McctPhanHoiJson::loi($ra['loi']));
        }

        $phanHoi = McctPhanHoiJson::tuKetQua($ra['kq'], $ra['nguong'], $ra['du_dieu_kien'],
            $params['ma_cskcb'], $ra['muc']);
        $phanHoi['loi_luu'] = $ra['loi_luu'];
        $phanHoi['lich_su'] = $this->lichSu($params['ma_the']);
        // Hai khoa nay co mat o CA HAI duong de javascript doc mot kieu du lieu duy nhat.
        $phanHoi['tu_cache'] = false;
        $phanHoi['co_du_lieu'] = true;

        return response()->json($phanHoi);
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
