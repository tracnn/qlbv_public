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
        // Boc TOAN THAN trong try/catch(\Throwable): Handler.php mac dinh cua Laravel voi
        // APP_DEBUG=true se tra {message, exception, file, line, trace} cho MOI ngoai le
        // khong bat duoc - duong dan tep va stack trace lo ra cho he thong ngoai. Cac try
        // rieng le ben duoi (doc CSDL, goi cong) khong phu het: CoSoTraCuu::tuCauHinh(),
        // McctDungLaiKetQua::tuBanGhi() va response()->json() (json_encode co the nem neu
        // ghi_chu tho tu cong khong phai UTF-8 hop le) deu nam ngoai chung.
        try {
            return $this->traCuuAnToan($request);
        } catch (\Throwable $e) {
            \Log::error('MCCT API loi khong bat duoc: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống', 'Vui lòng thử lại sau', 500);
        }
    }

    private function traCuuAnToan(Request $request)
    {
        $maThe = McctRequest::chuanHoaMaThe($request->get('ma_the'));
        $lamMoi = (string) $request->get('lam_moi') === '1';

        if ($maThe === '') {
            return $this->loiApi('VALIDATION_ERROR', 'Thiếu tham số bắt buộc',
                'Cần truyền ma_the', 422);
        }

        if (!preg_match(McctRequest::REGEX_MA_THE, $maThe)) {
            return $this->loiApi('VALIDATION_ERROR', 'Mã thẻ không hợp lệ',
                'ma_the phải có 10, 12, 15 hoặc 17 ký tự sau khi bỏ khoảng trắng', 422);
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
            // Cache::add() la NGUYEN TU: tra false neu khoa da ton tai. Doc-roi-hanh-dong
            // khong co khoa se de hai request cung mot ma the den cung luc CUNG goi cong -
            // cua so dua rong toi ca phut vi cong tra loi 5-60 giay.
            $khoa = 'mcct:lam_moi:' . $maThe;
            $phut = (int) ceil(((int) config('mcct.khoang_cho_lam_moi', 900)) / 60);

            if (!\Cache::add($khoa, 1, max(1, $phut))) {
                return $this->tuDuLieuDaLuu($maThe, ['goi' => false, 'con_lai' => 0], $lamMoi);
            }

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

        // Doc CUNG mot regex voi man web (McctRequest::rules()): mot HIS gui dinh dang ISO
        // (1980-01-01) se lot qua kiem '!== ""' va tieu mot luot goi cong chi de nhan lai 400.
        if (!preg_match(McctRequest::REGEX_NGAY_SINH, $params['ngay_sinh'])) {
            return 'ngay_sinh phải theo dd/mm/yyyy, mm/yyyy hoặc yyyy';
        }

        if (!in_array($params['ma_cskcb'],
            CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh()), true)) {
            return 'ma_cskcb không thuộc danh sách cơ sở đã khai tài khoản cổng BHXH';
        }

        return null;
    }

    private function goiCong(array $params)
    {
        // Lay moc TRUOC khi goi cong: McctLuuTraCuu (ben trong goiVaLuu) tu ghi moc rieng cua
        // no ngay sau khi cong tra loi. Lay o day thay vi SAU goiVaLuu bot duoc phan lech do
        // cho ket qua cua goiVaLuu roi moi doc dong ho - phan lech con lai (thoi gian ghi
        // CSDL) la khong dang ke va khong dang doi chu ky de trieu tieu not.
        $traLuc = date('Y-m-d H:i:s');

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

        // goiVaLuu() KHONG nem khi cong tra ma ung dung 400/500 - no tra ve mot KetQuaMcct
        // binh thuong voi maKetQua = '400'/'500'. Phai tu kiem o day, neu khong mot cong hong
        // se bi doc thanh "luy ke = 0, da tra cuu thanh cong" (HTTP 200) thay vi 502. Ma 500
        // kem GhiChu nhac "qua trinh tra cuu" chinh la tin hieu tai khoan co so dang bi cong
        // han che tra cuu - rui ro trung tam ca thiet ke nay dang phong.
        if ($kq->maKetQua !== '200' && $kq->maKetQua !== '204') {
            \Log::warning('MCCT API cong tra ma loi ung dung', [
                'ma_the' => $params['ma_the'],
                'ma_ket_qua' => $kq->maKetQua,
                'ghi_chu' => $kq->ghiChu,
            ]);

            // KHONG dua $kq->ghiChu (chuoi tho tu cong) ra details - noi dung khong kiem
            // soat duoc, chi ghi log phia may chu.
            return $this->loiApi('GATEWAY_ERROR', 'Không tra cứu được',
                'Cổng BHXH báo lỗi khi tra cứu. Thử lại sau ít phút.', 502);
        }

        return $this->traData(
            $this->duLieu($params['ma_the'], 'cong_bhxh', $traLuc, $kq->ghiChu,
                $kq->thongTinThe, $muc, $kq->dong),
            ['ma_ket_qua_cong' => $kq->maKetQua]
        );
    }

    private function tuDuLieuDaLuu($maThe, array $quyetDinh, $lamMoi)
    {
        $meta = [];

        // Dung TRUOC nhanh "chua tra lan nao" ben duoi va ap dung cho CA HAI duong tra ve:
        // mot ban ghi 204 (khong phai loi) van chan lam_moi qua QuyetDinhGoiCong, nhung
        // truy van ben duoi chi loc ma_ket_qua='200' nen se khong thay no va roi vao nhanh
        // "chua tra lan nao" - neu bao ro nam SAU nhanh do thi bi mat, va HIS doc tai lieu
        // "goi lai voi lam_moi=1 de tra that" se lap lai vo han suot ca khau do.
        if ($lamMoi) {
            $meta['bo_qua_lam_moi'] = true;
            $meta['lam_moi_duoc_sau'] = $quyetDinh['con_lai'];
        }

        try {
            $phien = McctTraCuu::where('ma_the', $maThe)
                ->where('ma_ket_qua', '200')
                ->orderBy('id', 'desc')->first();

            if ($phien === null) {
                return $this->traData(null, array_merge(['trang_thai' => 'chua_tra_lan_nao'], $meta));
            }

            $dong = McctChiPhi::where('tra_cuu_id', $phien->id)->orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            \Log::error('MCCT API khong doc duoc du lieu da luu: ' . $e->getMessage());

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống', 'Vui lòng thử lại sau', 500);
        }

        $cache = McctDungLaiKetQua::tuBanGhi($phien->toArray(), $dong,
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6));

        $meta['ma_ket_qua_cong'] = $cache['ma_ket_qua'];

        return $this->traData(
            $this->duLieu($maThe, 'da_luu', $cache['tra_luc'], $cache['ghi_chu'],
                $cache['thong_tin_the'], $cache['muc'], $cache['dong']),
            $meta
        );
    }

    /**
     * Dung mang `data` cua phan hoi thanh cong.
     *
     * MOT CHO DUY NHAT dung 11 khoa nay: hai duong (goi cong va du lieu da luu) phai tra ve
     * CUNG mot hinh dang, neu khong ben goi se nhan hai kieu du lieu khac nhau tuy luc.
     */
    private function duLieu($maThe, $nguon, $traLuc, $ghiChu, array $the, array $muc, array $chiTiet)
    {
        return [
            'ma_the' => $maThe,
            'nguon' => $nguon,
            'tra_luc' => $traLuc,
            'ghi_chu' => $ghiChu,
            'thong_tin_the' => $the,
            'luy_ke_cung_chi_tra' => $muc['luy_ke_tong'],
            'nguong_ca_nam' => $muc['tong_nguong_ca_nam'],
            'con_thieu' => $muc['con_thieu'],
            'du_nguong_6_thang_luong' => (bool) $muc['du_dieu_kien'],
            'can_kiem_5_nam_lien_tuc' => true,
            'chi_tiet' => $chiTiet,
        ];
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
