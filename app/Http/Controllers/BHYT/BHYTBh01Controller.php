<?php

namespace App\Http\Controllers\BHYT;

use App\Http\Controllers\Controller;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01KhongTaoDuocException;
use App\Services\Tt12\Bh01\Bh01Nguon;
use App\Services\Tt12\Bh01\Bh01TaoBan;
use App\Services\Tt12\Bh01\Bh01XepHang;
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

        $cacBan = $q->orderBy('nam_qt', 'desc')->orderBy('thang_qt', 'desc')
            ->orderBy('ma_cskcb')->orderBy('so_ban', 'desc')
            ->skip($batDau)->take($soDong > 0 ? $soDong : 25)->get();

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
        $xepDuoc = Bh01XepHang::xepKy($ban->ma_ban);

        return response()->json(array(
            'thanh_cong' => true,
            'thong_diep' => 'Đã tạo bản ' . $ban->so_ban . ' (' . $ban->so_dong . ' hồ sơ).'
                . ($xepDuoc ? ' Đang chờ ký.' : ' Chưa xếp được hàng đợi ký, bấm "Ký lại".'),
            'ma_ban'     => $ban->ma_ban,
        ));
    }

    public function kyLai(Request $request, $maBan)
    {
        $ban = Bh01Ban::where('ma_ban', $maBan)->first();
        if ($ban === null) {
            return $this->loi('Không tìm thấy bản ' . $maBan, 404);
        }

        // xepKy tu choi ban da gui/dang gui: tep da ky cua ban do la dau vet doi soat voi cong.
        if (!Bh01XepHang::xepKy($maBan)) {
            return $this->loi('Không ký lại được: bản đã gửi cổng hoặc đang được xử lý.', 409);
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
                    . ' đã được cổng tiếp nhận ở bản ' . $daNhan->so_ban . ' (mã GD ' . $daNhan->ma_gd . '). Gửi bản '
                    . $ban->so_ban . '?',
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
        $max = Bh01Ban::where('ma_cskcb', $ban->ma_cskcb)
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
            'ma_ket_qua'          => $ban->ma_ket_qua,
            'thong_diep'          => $ban->thong_diep,
            'ma_gd'               => $ban->ma_gd,
            'thoi_gian_tiep_nhan' => $ban->thoi_gian_tiep_nhan,
            'du_lieu_da_doi'      => $daDoi,
        );
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
