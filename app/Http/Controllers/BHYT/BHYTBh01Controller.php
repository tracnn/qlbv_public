<?php

namespace App\Http\Controllers\BHYT;

use App\Http\Controllers\Controller;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01KhongTaoDuocException;
use App\Services\Tt12\Bh01\Bh01NhapExcel;
use App\Services\Tt12\Bh01\Bh01Nguon;
use App\Services\Tt12\Bh01\Bh01TaoBan;
use App\Services\Tt12\Bh01\Bh01TepExcelHongException;
use App\Services\Tt12\Bh01\Bh01XepHang;
use App\Services\Tt12\Bh01\Bh01XuatExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Man hinh Ho so tong hop 01/BH: xem truoc, tao ban, tai tep da ky, gui cong (spec 5.1-5.5, 8.3).
 *
 * Controller chi dieu phoi: dung ban nam o Bh01TaoBan, xep ky/gui (kem khoa chong gui trung) o
 * Bh01XepHang. KHONG goi conGiuKhoa/nhaKhoa o day - do la viec cua job.
 *
 * Quyen: nhu cac thao tac ky/gui TT12 danh muc (khong middleware rieng) - quyet dinh cua nguoi thiet ke.
 */
class BHYTBh01Controller extends Controller
{
    /** Dong moi trang o man Xem dong. */
    const DONG_MOI_TRANG = 200;

    /** Tran so ban moi trang danh sach: moi ban moi nhat cua ky ton mot lan tong hop XML1. */
    const BAN_TOI_DA_MOI_TRANG = 100;

    public function index()
    {
        return view('bhyt.tt12.bh01.index', array(
            // O CHON khong co mac dinh: he thong phuc vu nhieu co so, chon nham la tao ban cho don vi khac.
            'danhSachCoSo' => DanhSachCoSo::danhSach(),
            'guiBat'       => (bool) config('organization.tt12.submit_enabled', false),
        ));
    }

    /** Xem truoc tong hop + loi chan cua (co so, ky), chua ghi gi. */
    public function xemTruoc(Request $request)
    {
        $loi = $this->docCoSoVaKy($request, $maCskcb, $ky);
        if ($loi !== null) {
            return $loi;
        }

        return response()->json((new Bh01TaoBan())->xemTruoc($maCskcb, $ky));
    }

    /** Nguon DataTables cua danh sach ban. */
    public function fetchData(Request $request)
    {
        $q = Bh01Ban::query();

        $maCskcb = trim((string) $request->input('ma_cskcb'));
        if ($maCskcb !== '') {
            $q->where('ma_cskcb', $maCskcb);
        }
        if ($request->input('nam') !== null && $request->input('nam') !== '') {
            $q->where('nam_qt', (string) (int) $request->input('nam'));
        }
        if ($request->input('thang') !== null && $request->input('thang') !== '') {
            $q->where('thang_qt', sprintf('%02d', (int) $request->input('thang')));
        }

        $tong = (clone $q)->count();
        $batDau = max(0, (int) $request->input('start', 0));
        $soDong = (int) $request->input('length', 25);
        $soDong = $soDong > 0 ? min($soDong, self::BAN_TOI_DA_MOI_TRANG) : 25;

        $cacBan = $q->orderBy('nam_qt', 'desc')->orderBy('thang_qt', 'desc')
            ->orderBy('ma_cskcb')->orderBy('so_ban', 'desc')
            ->skip($batDau)->take($soDong)->get();

        // du_lieu_da_doi chi tinh cho ban MOI NHAT cua moi ky (spec 5.5): tongHop quet xml3176_xml1s,
        // tinh cho moi dong la nang va vo nghia voi ban cu. kyDaDoi() tra false ngay voi ban cu.
        $hang = array();
        foreach ($cacBan as $ban) {
            $hang[] = $this->dongDanhSach($ban, $this->kyDaDoi($ban));
        }

        return response()->json(array(
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $tong,
            'recordsFiltered' => $tong,
            'data'            => $hang,
        ));
    }

    public function tao(Request $request)
    {
        // Ky tat ma van tao thi ban nam cho_ky mai (SignBh01Job bo qua khi co tat), chiem so ban.
        if (!(bool) config('organization.tt12.sign_enabled', false)) {
            return $this->loi('Chức năng ký số đang tắt, chưa tạo bản.', 422);
        }

        $loi = $this->docCoSoVaKy($request, $maCskcb, $ky);
        if ($loi !== null) {
            return $loi;
        }

        try {
            $ban = (new Bh01TaoBan())->tao($maCskcb, $ky, $this->nguoiDung($request));
        } catch (Bh01KhongTaoDuocException $e) {
            return $this->loi($e->getMessage(), 422);
        } catch (\InvalidArgumentException $e) {
            return $this->loi($e->getMessage(), 422);
        }

        // Ky xong moi gui duoc; ky chay o hang doi vi dich vu ky (HSM/USB Token) cham.
        $xepDuoc = Bh01XepHang::xepKyBanMoi($ban->ma_ban);

        return response()->json(array(
            'thanh_cong' => true,
            'thong_diep' => 'Đã tạo bản ' . $ban->so_ban . ' (' . $ban->so_dong . ' hồ sơ).'
                . ($xepDuoc ? ' Đang chờ ký.' : ' Chưa xếp được hàng đợi ký, sau 10 phút bấm "Ký lại".'),
            'ma_ban'     => $ban->ma_ban,
        ));
    }

    /**
     * Nhap tep Excel thanh ban 01/BH nguon excel (spec 4.1). Cung chot voi tao: tat ky thi khong
     * nhan, vi ban se nam cho_ky mai va chiem so ban.
     */
    public function nhapExcel(Request $request)
    {
        if (!(bool) config('organization.tt12.sign_enabled', false)) {
            return $this->loi('Chức năng ký số đang tắt, chưa tạo bản.', 422);
        }

        // Than yeu cau vuot post_max_size: PHP bo sach $_POST/$_FILES nen docCoSoVaKy se bao nham
        // "chua chon co so". Nhan ra bang CONTENT_LENGTH truoc.
        $tran = $this->byteTuIni(ini_get('post_max_size'));
        if ($tran > 0 && (int) $request->server('CONTENT_LENGTH') > $tran
            && count($request->all()) === 0 && count($request->allFiles()) === 0) {
            return $this->loi('Tệp vượt cỡ cho phép (tối đa ' . ini_get('post_max_size') . ').', 422);
        }

        $loi = $this->docCoSoVaKy($request, $maCskcb, $ky);
        if ($loi !== null) {
            return $loi;
        }

        // file() chu khong phai hasFile(): hasFile() tra false cho tep loi upload, se bao nham "chua chon tep".
        $tep = $request->file('tep');
        if ($tep === null) {
            return $this->loi('Chưa chọn tệp Excel.', 422);
        }
        if (!$tep->isValid()) {
            if (in_array($tep->getError(), array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)) {
                return $this->loi('Tệp vượt cỡ cho phép (' . ini_get('upload_max_filesize') . ').', 422);
            }

            return $this->loi('Tải tệp lên không thành công, hãy thử lại.', 422);
        }
        if (strtolower((string) $tep->getClientOriginalExtension()) !== 'xlsx') {
            return $this->loi('Chỉ nhận tệp .xlsx. Hãy lưu lại tệp dạng Excel Workbook (.xlsx) rồi thử lại.', 422);
        }

        try {
            $kq = (new Bh01NhapExcel())->nhap(
                $tep->getRealPath(), $tep->getClientOriginalName(), $maCskcb, $ky, $this->nguoiDung($request)
            );
        } catch (Bh01KhongTaoDuocException $e) {
            return $this->loi($e->getMessage(), 422);
        } catch (\InvalidArgumentException $e) {
            return $this->loi($e->getMessage(), 422);
        } catch (Bh01TepExcelHongException $e) {
            // CHI bat loi doc tep: loi CSDL/may chu (cung la RuntimeException) phai noi len va vao log,
            // khong duoc bao nham la tep hong. Khong lo thong diep thu vien cho nguoi dung.
            return $this->loi('Không đọc được tệp Excel. Hãy lưu lại dạng .xlsx rồi thử lại.', 422);
        }

        $ban = Bh01Ban::find($kq['ban']->id);
        $xepDuoc = Bh01XepHang::xepKyBanMoi($ban->ma_ban);

        $dong = function ($n) {
            return number_format((float) $n, 0, ',', '.');
        };
        $thongDiep = 'Đã nhập bản ' . $ban->ma_ban . ': ' . $ban->so_dong . ' hồ sơ, ' . (int) $ban->so_canh_bao
            . ' hồ sơ cảnh báo, tổng chi BV ' . $dong($ban->t_tongchi_bv) . ', tổng BHTT ' . $dong($ban->t_bhtt) . '.'
            . ($xepDuoc ? ' Đang chờ ký.' : ' Chưa xếp được hàng đợi ký, sau 10 phút bấm "Ký lại".');
        if (!empty($kq['cot_thua'])) {
            $thongDiep .= ' Bỏ qua cột: ' . implode(', ', $kq['cot_thua']) . '.';
        }

        return response()->json(array('thanh_cong' => true, 'thong_diep' => $thongDiep, 'ma_ban' => $ban->ma_ban));
    }

    /** Xuat cac dong cua ban ra Excel dung bo cot nhap (spec 4.2). */
    public function xuatExcel($maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            return $this->loi('Không tìm thấy bản ' . $maBan, 404);
        }

        $duongDan = (new Bh01XuatExcel())->xuatBan($ban);

        return response()->download($duongDan, $maBan . '.xlsx')->deleteFileAfterSend(true);
    }

    public function tepMau()
    {
        return response()->download((new Bh01XuatExcel())->tepMau(), 'HSTH01BH_mau.xlsx')->deleteFileAfterSend(true);
    }

    public function kyLai(Request $request, $maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            return $this->loi('Không tìm thấy bản ' . $maBan, 404);
        }

        // Luat nam o Bh01XepHang::coTheKyLai; xepKy kiem lai trong chinh cau UPDATE (hai lan bam
        // cung luc), nen ca hai deu tra 409 cung mot thong diep.
        if (!Bh01XepHang::coTheKyLai($ban) || !Bh01XepHang::xepKy($maBan)) {
            return $this->loi('Không ký lại được: bản đã ký xong, đã/đang gửi cổng, hoặc đang chờ ký chưa quá '
                . Bh01XepHang::KY_LAI_SAU_PHUT . ' phút.', 409);
        }

        return response()->json(array('thanh_cong' => true, 'thong_diep' => 'Đã xếp hàng đợi ký lại.'));
    }

    public function gui(Request $request, $maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            return $this->loi('Không tìm thấy bản ' . $maBan, 404);
        }

        if ($ban->trang_thai_ky !== Bh01Ban::KY_XONG) {
            return $this->loi('Bản chưa ký xong, chưa gửi được.', 422);
        }

        if (!(bool) config('organization.tt12.submit_enabled', false)) {
            return $this->loi('Chức năng gửi cổng đang tắt.', 422);
        }

        $daNhan = Bh01XepHang::daDuocTiepNhan($ban->ma_cskcb, $ban->nam_qt, $ban->thang_qt);
        if ($daNhan !== null && (string) $request->input('xac_nhan') !== '1') {
            return response()->json(array(
                'thanh_cong'   => false,
                'can_xac_nhan' => true,
                'thong_diep'   => 'Kỳ ' . $ban->thang_qt . '/' . $ban->nam_qt . ' của cơ sở ' . $ban->ma_cskcb
                    . ' đã được cổng tiếp nhận ở bản ' . $daNhan->so_ban . ' (nguồn ' . $this->nhanNguon($daNhan->nguon)
                    . ', mã GD ' . $daNhan->ma_gd . '). Gửi bản ' . $ban->so_ban . ' (nguồn '
                    . $this->nhanNguon($ban->nguon) . ')?',
            ), 409);
        }

        if (!Bh01XepHang::xepGui($maBan, $this->nguoiDung($request))) {
            return $this->loi('Bản đang được gửi, chờ kết quả rồi mới gửi lại.', 409);
        }

        return response()->json(array('thanh_cong' => true, 'thong_diep' => 'Đã xếp hàng đợi gửi cổng.'));
    }

    public function tai($maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        $duongDan = $ban !== null ? $ban->duong_dan_da_ky : null;

        if ($ban === null || $duongDan === null || $duongDan === '') {
            return $this->loi('Bản chưa có tệp đã ký.', 404);
        }

        $dia = Storage::disk('exportTt12');
        if (!$dia->exists($duongDan)) {
            return $this->loi('Không thấy tệp đã ký trên đĩa, hãy ký lại bản này.', 404);
        }

        return $dia->download($duongDan, $maBan . '.xml');
    }

    public function dong($maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            abort(404);
        }

        return view('bhyt.tt12.bh01.dong', array(
            'ban'     => $ban,
            'cacDong' => $ban->dong()->paginate(self::DONG_MOI_TRANG),
        ));
    }

    public function lichSu($maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            return $this->loi('Không tìm thấy bản ' . $maBan, 404);
        }

        // Khong tra nguyen_van: tep base64 hang chuc MB, man nhat ky khong can.
        $hang = array();
        foreach ($ban->lichSuGui()->take(100)->get() as $ls) {
            $hang[] = array(
                'gui_luc'             => $ls->gui_luc ? $ls->gui_luc->format('d/m/Y H:i:s') : null,
                'gui_boi'             => $ls->gui_boi,
                'ma_ket_qua'          => $ls->ma_ket_qua,
                'ma_gd'               => $ls->ma_gd,
                'thoi_gian_tiep_nhan' => $ls->thoi_gian_tiep_nhan,
                'thong_diep'          => $ls->thong_diep,
                'loi'                 => $ls->loi,
            );
        }

        return response()->json(array('data' => $hang));
    }

    /**
     * Doc co so + ky tu request. Tra JSON 422 neu sai, null neu hop le (gan $maCskcb, $ky qua tham chieu).
     */
    private function docCoSoVaKy(Request $request, &$maCskcb, &$ky)
    {
        $maCskcb = trim((string) $request->input('ma_cskcb'));

        if ($maCskcb === '') {
            return $this->loi('Chưa chọn cơ sở khám chữa bệnh.', 422);
        }
        if (!array_key_exists($maCskcb, DanhSachCoSo::danhSach())) {
            return $this->loi('Mã cơ sở KCB không hợp lệ: "' . $maCskcb . '"', 422);
        }

        try {
            $ky = Bh01Ky::tu($request->input('nam'), $request->input('thang'));
        } catch (\InvalidArgumentException $e) {
            return $this->loi($e->getMessage(), 422);
        }

        return null;
    }

    /** So ban nay (neu la ban moi nhat cua ky) voi XML1 hien tai: so ho so, tong chi BV, tong BHTT. */
    private function kyDaDoi(Bh01Ban $ban)
    {
        // Ban Excel khong lay tu XML1 nen khong co gi de so (spec 5): khong bao "du lieu da doi".
        if ($ban->nguon === Bh01Ban::NGUON_EXCEL) {
            return false;
        }

        // "Moi nhat" chi tinh trong cac ban xml3176: ban Excel moi hon khong tuoc co bao cua ban xml3176 cuoi.
        $max = Bh01Ban::where('ma_cskcb', $ban->ma_cskcb)
            ->where('nguon', Bh01Ban::NGUON_XML3176)
            ->where('nam_qt', $ban->nam_qt)
            ->where('thang_qt', $ban->thang_qt)
            ->max('so_ban');

        if ((int) $max !== (int) $ban->so_ban) {
            return false;
        }

        $nay = Bh01Nguon::tongHop($ban->ma_cskcb, array('nam' => $ban->nam_qt, 'thang' => $ban->thang_qt));

        return (int) $nay['so_ho_so'] !== (int) $ban->so_dong
            || round((float) $nay['t_tongchi_bv'], 2) !== round((float) $ban->t_tongchi_bv, 2)
            || round((float) $nay['t_bhtt'], 2) !== round((float) $ban->t_bhtt, 2);
    }

    private function dongDanhSach(Bh01Ban $ban, $daDoi)
    {
        return array(
            'ma_ban'              => $ban->ma_ban,
            'ma_cskcb'            => $ban->ma_cskcb,
            'ky'                  => $ban->thang_qt . '/' . $ban->nam_qt,
            'so_ban'              => $ban->so_ban,
            'so_dong'             => $ban->so_dong,
            't_bhtt'              => $ban->t_bhtt,
            'so_canh_bao'         => $ban->so_canh_bao,
            'trang_thai_ky'       => $ban->trang_thai_ky,
            'signed_error'        => $ban->signed_error,
            'da_gui'              => $ban->submitted_at !== null,
            'dang_gui'            => $ban->dang_xu_ly_tu !== null,
            'co_the_ky_lai'       => Bh01XepHang::coTheKyLai($ban),
            'ma_ket_qua'          => $ban->ma_ket_qua,
            'thong_diep'          => $ban->thong_diep,
            'ma_gd'               => $ban->ma_gd,
            'thoi_gian_tiep_nhan' => $ban->thoi_gian_tiep_nhan,
            'du_lieu_da_doi'      => $daDoi,
            'nguon'               => $ban->nguon,
            'ten_tep_nguon'       => $ban->ten_tep_nguon,
        );
    }

    /** '8M' -> byte; 0 neu khong gioi han ('0'/'-1'/rong). */
    private function byteTuIni($gt)
    {
        $gt = trim((string) $gt);
        if ($gt === '' || (int) $gt <= 0) {
            return 0;
        }
        $so = (float) $gt;
        switch (strtolower(substr($gt, -1))) {
            case 'g':
                $so *= 1024;
            case 'm':
                $so *= 1024;
            case 'k':
                $so *= 1024;
        }

        return (int) $so;
    }

    private function nhanNguon($nguon)
    {
        return $nguon === Bh01Ban::NGUON_EXCEL ? 'Excel' : 'XML3176';
    }

    private function nguoiDung(Request $request)
    {
        return $request->user() ? $request->user()->loginname : null;
    }

    private function loi($thongDiep, $maHttp)
    {
        return response()->json(array('thanh_cong' => false, 'thong_diep' => $thongDiep), $maHttp);
    }
}
