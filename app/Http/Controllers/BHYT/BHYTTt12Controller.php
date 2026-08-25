<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Tt12\Tt12Importer;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Man tai len tep Excel danh muc TT12/2026/BTC.
 *
 * Moi tep .xlsx/.xls la MOT ho so - khac voi man nhap danh muc thu cong (mot lan nhap
 * ghi thang vao bang danh muc). Uploader chi lam nhiem vu tiep nhan tep va goi
 * Tt12Importer; toan bo logic doc/kiem tra/luu nam o Task 1-4.
 */
class BHYTTt12Controller extends Controller
{
    public function importIndex()
    {
        return view('bhyt.tt12.import', array(
            'danhSachMau'  => $this->danhSachMau(),
            // O CHON, khong phai o nhap tay va khong co gia tri mac dinh: he thong phuc vu
            // nhieu co so (Bach Mai co 01929 o Ha Noi va 37470 o Ninh Binh). Go tay mot
            // ma co so sai thi cong VAN NHAN - vi token duoc lay theo dung ma sai do - va
            // ca danh muc vao nham don vi.
            'danhSachCoSo' => \App\Services\BHYT\DanhSachCoSo::danhSach(),
        ));
    }

    /**
     * Nhan mot hoac nhieu tep .xlsx, moi tep thanh mot ho so.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadData(Request $request)
    {
        // Doc Excel theo lo van can bo nho hon mac dinh: mot tep 10.000 dong x 23 cot
        // (1,3 MB) tung lam dinh 208 MB khi doc mot lan. Doc theo lo giu duoi nguong nay
        // nhung 128 MB mac dinh van sat.
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $tep = $request->file('tepExcel');

        if (empty($tep)) {
            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Không nhận được tệp nào.',
                'chi_tiet'   => array(),
            ), 400);
        }

        $tep = is_array($tep) ? $tep : array($tep);

        $importer = new Tt12Importer();
        $maCskcb = trim((string) $request->input('ma_cskcb'));
        $mauChon = $request->input('mau');

        // Doi chieu voi danh sach co so THAT thay vi nhan bat ky chuoi nao: endpoint nay
        // co the bi goi thang khong qua form, va mot ma co so la se tao ho so khong bao
        // gio gui duoc (BHYTLoginService nem vi co so chua khai tai khoan) - nhung chi
        // nem o buoc gui, sau khi nguoi dung da nap va cho kiem xong.
        if ($maCskcb === '') {
            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Chưa chọn cơ sở khám chữa bệnh.',
                'chi_tiet'   => array(),
            ), 422);
        }

        if (!array_key_exists($maCskcb, \App\Services\BHYT\DanhSachCoSo::danhSach())) {
            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Mã cơ sở KCB không hợp lệ: "' . $maCskcb . '"',
                'chi_tiet'   => array(),
            ), 422);
        }

        $chiTiet = array();
        $tatCaThanhCong = true;

        // Phai KHOP voi maxFilesize trong blade. acceptedFiles/maxFilesize cua Dropzone
        // CHI la kiem phia trinh duyet; ai goi thang endpoint se lot qua het.
        $kichThuocToiDa = 20 * 1024 * 1024;

        foreach ($tep as $mot) {
            $ten = $mot->getClientOriginalName();

            if (!$mot->isValid()) {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->dongLoi($ten, 'Tải lên lỗi, mã lỗi: ' . $mot->getError());
                continue;
            }

            $duoi = strtolower($mot->getClientOriginalExtension());

            if ($duoi !== 'xlsx' && $duoi !== 'xls') {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->dongLoi($ten, 'Chỉ nhận tệp .xlsx hoặc .xls, tệp này là "' . $duoi . '"');
                continue;
            }

            if ($mot->getSize() > $kichThuocToiDa) {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->dongLoi($ten, 'Tệp vượt quá 20MB (thực tế: ' . $mot->getSize() . ' byte)');
                continue;
            }

            $ketQua = $importer->nhapTuTep($mot->getRealPath(), array(
                'ma_cskcb'    => $maCskcb,
                'ten_tep'     => $ten,
                'mau'         => $mauChon,
                'imported_by' => $request->user() ? $request->user()->loginname : null,
            ));

            if (!$ketQua->thanhCong()) {
                $tatCaThanhCong = false;
            }

            $chiTiet[] = array(
                'ten_tep'        => $ten,
                'thanh_cong'     => $ketQua->thanhCong(),
                'ma_ho_so'       => $ketQua->maHoSo(),
                'mau'            => $ketQua->mau(),
                'so_dong'        => $ketQua->soDong(),
                'so_o_dien_them' => $ketQua->soODienThem(),
                'loi'            => $ketQua->loi(),
            );
        }

        return response()->json(array(
            'thanh_cong' => $tatCaThanhCong,
            'thong_diep' => $tatCaThanhCong
                ? 'Đã nạp xong ' . count($chiTiet) . ' tệp.'
                : 'Có tệp nạp không thành công, xem chi tiết bên dưới.',
            'chi_tiet'   => $chiTiet,
        ));
    }

    /** @return array [ma mau => ten hien thi] */
    private function danhSachMau()
    {
        $ds = array();

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $ds[$ma] = $lop::ten();
        }

        return $ds;
    }

    /** @return array mot dong ket qua dang loi tai len, chua cham toi importer */
    private function dongLoi($ten, $moTa)
    {
        return array(
            'ten_tep'        => $ten,
            'thanh_cong'     => false,
            'ma_ho_so'       => null,
            'mau'            => null,
            'so_dong'        => 0,
            'so_o_dien_them' => 0,
            'loi'            => $moTa,
        );
    }
}
