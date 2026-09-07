<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Services\Mcct\McctDungLaiKetQua;
use App\Services\Mcct\McctTraCuuChung;
use App\Services\Mcct\QuyetDinhGoiCong;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * API tra cuu tien cung chi tra (MCCT) cho he thong ngoai.
 *
 * VI SAO TACH KHOI McctController: man web va API cho he thong ngoai co ranh gioi xac thuc
 * khac nhau (mot ben la phien dang nhap, mot ben la token Bearer). De chung mot lop la moi
 * goi nham lan ve sau.
 *
 * Lop nay KHONG chua logic nghiep vu - chi doi ket qua cua McctTraCuuChung va
 * McctDungLaiKetQua sang khuon JSON {success, data, meta} da co tien le trong du an.
 */
class McctApiController extends Controller
{
    /** Doi ma loi cua McctTraCuuChung sang ma HTTP + ma loi cua API */
    private static $banDoLoi = [
        'XAC_THUC' => ['GATEWAY_ERROR', 502],
        'MANG' => ['GATEWAY_ERROR', 502],
        'HET_GIO' => ['GATEWAY_TIMEOUT', 504],
        // Co so chua khai tai khoan la CAU HINH THIEU cua qlbv, khong phai cong hong. Tra
        // 502 se day ben goi di hoi nham phia BHXH.
        'CAU_HINH' => ['INTERNAL_ERROR', 500],
        'KHAC' => ['INTERNAL_ERROR', 500],
    ];

    /**
     * Cau bao cho BEN GOI, soan san theo ma loi - khong bao gio noi chuoi ngoai le vao day.
     * Thong diep that cua McctTraCuuChung (hostname, duong dan tep cau hinh, chan doan mang)
     * chi phu hop cho man web noi bo, khong duoc lo ra API cho he thong ngoai.
     */
    private static $cauBaoLoi = [
        'XAC_THUC' => 'Không xác thực được với cổng BHXH. Liên hệ quản trị hệ thống qlbv.',
        'MANG' => 'Không kết nối được cổng BHXH. Thử lại sau ít phút.',
        'HET_GIO' => 'Cổng BHXH không trả lời kịp. Thử lại sau ít phút.',
        'CAU_HINH' => 'Cơ sở khám chữa bệnh chưa được cấu hình trên qlbv. Liên hệ quản trị hệ thống qlbv.',
        'KHAC' => 'Lỗi khi gọi cổng BHXH. Thử lại sau ít phút.',
    ];

    public function traCuu(Request $request)
    {
        $maThe = McctRequest::chuanHoaMaThe($request->get('ma_the'));
        $lamMoi = (string) $request->get('lam_moi') === '1';

        if ($maThe === '') {
            return $this->loiApi('VALIDATION_ERROR', 'Thiếu tham số bắt buộc',
                'Cần truyền ma_the', 422);
        }

        if (!preg_match('/^[A-Za-z0-9]{10}$|^[A-Za-z0-9]{12}$|^[A-Za-z0-9]{15}$/', $maThe)) {
            return $this->loiApi('VALIDATION_ERROR', 'Mã thẻ không hợp lệ',
                'ma_the phải có 10, 12 hoặc 15 ký tự sau khi bỏ khoảng trắng', 422);
        }

        $params = [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            'ma_the' => $maThe,
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];

        if ($lamMoi) {
            $thieu = $this->thieuThamSoLamMoi($params);

            if ($thieu !== null) {
                return $this->loiApi('VALIDATION_ERROR', 'Thiếu tham số bắt buộc', $thieu, 422);
            }
        }

        // Ban ghi gan nhat BAT KE ma ket qua - mot lan tra ve 204 cung da tieu mot luot goi
        // cong, nen no van phai tinh vao khau do chan. Khac voi truy van trong
        // tuDuLieuDaLuu() ben duoi: cho do chi lay lan THANH CONG de co so lieu that ma hien.
        try {
            $phienCu = McctTraCuu::where('ma_the', $maThe)->orderBy('id', 'desc')->first();
        } catch (\Exception $e) {
            \Log::error('MCCT API khong doc duoc ban ghi cu: ' . $e->getMessage());

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống',
                'Vui lòng thử lại sau', 500);
        }

        $quyetDinh = QuyetDinhGoiCong::nen(
            $lamMoi,
            $phienCu === null ? null : (string) $phienCu->tra_luc,
            date('Y-m-d H:i:s'),
            (int) config('mcct.khoang_cho_lam_moi', 900)
        );

        if ($quyetDinh['goi']) {
            return $this->goiCong($params);
        }

        return $this->tuDuLieuDaLuu($maThe, $quyetDinh, $lamMoi);
    }

    /** @return string|null cau bao loi; null neu du tham so */
    private function thieuThamSoLamMoi(array $params)
    {
        foreach (['ho_ten' => 'ho_ten', 'ngay_sinh' => 'ngay_sinh', 'ma_cskcb' => 'ma_cskcb']
            as $khoa => $ten) {
            if ($params[$khoa] === '') {
                return 'lam_moi=1 cần truyền thêm ' . $ten;
            }
        }

        if (!in_array($params['ma_cskcb'],
            CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh()), true)) {
            return 'ma_cskcb không thuộc danh sách cơ sở đã khai tài khoản cổng BHXH';
        }

        return null;
    }

    private function goiCong(array $params)
    {
        $ra = McctTraCuuChung::goiVaLuu($params, 'api_his');

        if ($ra['loi'] !== null) {
            // Chi tiet that chi ghi vao LOG phia may chu. Thong diep goc noi chuoi
            // $e->getMessage() cua cURL va cua CauHinhCoSo - no chua hostname, duong dan tep
            // cau hinh va chan doan mang. Mot he thong ben ngoai khong duoc nhin thay nhung
            // thu do, ke ca khi da qua duoc xac thuc.
            \Log::warning('MCCT API loi goi cong', [
                'ma_the' => $params['ma_the'],
                'ma_loi' => $ra['ma_loi'],
                'chi_tiet' => $ra['loi'],
            ]);

            $ban = isset(self::$banDoLoi[$ra['ma_loi']])
                ? self::$banDoLoi[$ra['ma_loi']] : ['INTERNAL_ERROR', 500];

            $cau = isset(self::$cauBaoLoi[$ra['ma_loi']])
                ? self::$cauBaoLoi[$ra['ma_loi']] : 'Thử lại sau ít phút.';

            return $this->loiApi($ban[0], 'Không tra cứu được', $cau, $ban[1]);
        }

        $kq = $ra['kq'];
        $muc = $ra['muc'];

        return $this->traData([
            'ma_the' => $params['ma_the'],
            'nguon' => 'cong_bhxh',
            'tra_luc' => date('Y-m-d H:i:s'),
            'ghi_chu' => $kq->ghiChu,
            'thong_tin_the' => $kq->thongTinThe,
            'luy_ke_cung_chi_tra' => $muc['luy_ke_tong'],
            'nguong_ca_nam' => $muc['tong_nguong_ca_nam'],
            'con_thieu' => $muc['con_thieu'],
            'du_nguong_6_thang_luong' => (bool) $muc['du_dieu_kien'],
            'can_kiem_5_nam_lien_tuc' => true,
            'chi_tiet' => $kq->dong,
        ], ['ma_ket_qua_cong' => $kq->maKetQua]);
    }

    private function tuDuLieuDaLuu($maThe, array $quyetDinh, $lamMoi)
    {
        try {
            $phien = McctTraCuu::where('ma_the', $maThe)
                ->where('ma_ket_qua', '200')
                ->orderBy('id', 'desc')->first();

            if ($phien === null) {
                return $this->traData(null, ['trang_thai' => 'chua_tra_lan_nao']);
            }

            $dong = McctChiPhi::where('tra_cuu_id', $phien->id)->orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            \Log::error('MCCT API khong doc duoc du lieu da luu: ' . $e->getMessage());

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống', 'Vui lòng thử lại sau', 500);
        }

        $cache = McctDungLaiKetQua::tuBanGhi($phien->toArray(), $dong,
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6));

        $muc = $cache['muc'];

        $meta = ['ma_ket_qua_cong' => $cache['ma_ket_qua']];

        // Bo qua lam_moi vi con trong khau do: bao ro chu khong im lang. Tra DU LIEU chu
        // khong tra 429 - mot vong lap hong ben goi se khong sinh them vong thu lai.
        if ($lamMoi) {
            $meta['bo_qua_lam_moi'] = true;
            $meta['lam_moi_duoc_sau'] = $quyetDinh['con_lai'];
        }

        return $this->traData([
            'ma_the' => $maThe,
            'nguon' => 'da_luu',
            'tra_luc' => $cache['tra_luc'],
            'ghi_chu' => $cache['ghi_chu'],
            'thong_tin_the' => $cache['thong_tin_the'],
            'luy_ke_cung_chi_tra' => $muc['luy_ke_tong'],
            'nguong_ca_nam' => $muc['tong_nguong_ca_nam'],
            'con_thieu' => $muc['con_thieu'],
            'du_nguong_6_thang_luong' => (bool) $muc['du_dieu_kien'],
            'can_kiem_5_nam_lien_tuc' => true,
            'chi_tiet' => $cache['dong'],
        ], $meta);
    }

    private function traData($data, array $themMeta = [])
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge($this->metaApi(), $themMeta),
        ]);
    }

    /** Khuon loi thong nhat voi ApiAuthMiddleware. Khong lo thong diep ngoai le ra ngoai. */
    private function loiApi($code, $message, $details, $status)
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message, 'details' => $details],
            'meta' => $this->metaApi(),
        ], $status);
    }

    private function metaApi()
    {
        return [
            'timestamp' => Carbon::now()->format('YmdHis'),
            'request_id' => uniqid('req_'),
        ];
    }
}
