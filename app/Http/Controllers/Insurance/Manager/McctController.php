<?php

namespace App\Http\Controllers\Insurance\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
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
     * voi tham so co san thi javascript tu tra ngay, khong phai bam lai. Moi lan nap nhu vay
     * la mot lan goi cong THAT - man web khong con hien ket qua da luu (15/9/2026).
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

        $ra = McctTraCuuChung::goiVaLuu($params, 'thu_cong');

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
