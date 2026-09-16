<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Tt12\Tt12Importer;
use App\Services\Tt12\Tt12MauRegistry;
use Illuminate\Support\Facades\Storage;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Services\Tt12\Tt12DanhSach;
use App\Services\Tt12\Tt12DetailTabs;
use App\Services\Tt12\Tt12QuyetDinhGui;
use App\Services\Tt12\Tt12XepHangKyGui;
use App\Services\Tt12\Tt12XoaHoSo;
use App\Services\Tt12\Tt12XuatXml;
use App\Services\Tt12\Tt12DongBoDanhMuc;
use App\Jobs\SignTt12Job;
use App\Jobs\SubmitTt12Job;
use App\Exports\Tt12DanhSachExport;
use App\Exports\Tt12LoiExport;
use App\Exports\Tt12BieuMauExport;
use App\Exports\Tt12NhatKyGuiExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Man tai len tep Excel danh muc TT12/2026/BTC.
 *
 * Moi tep .xlsx/.xls la MOT ho so - khac voi man nhap danh muc thu cong (mot lan nhap
 * ghi thang vao bang danh muc). Uploader chi lam nhiem vu tiep nhan tep va goi
 * Tt12Importer; toan bo logic doc/kiem tra/luu nam o Task 1-4.
 */
class BHYTTt12Controller extends Controller
{
    /**
     * Tran so ho so mot luot gui hang loat.
     *
     * Kiem o SERVER chu khong chi o JavaScript: gioi han phia trinh duyet chi la tien nghi
     * cho nguoi dung, ai goi thang endpoint se lot qua het. Va endpoint nay xep hang KY VA
     * GUI THAT len cong BHXH.
     *
     * Bang con so cua CTDT cho nhat quan. Luu y mot ho so TT12 nang hon mot chung tu CTDT
     * nhieu lan - no la ca mot tep danh muc, co the toi hang chuc nghin dong - nen neu sau
     * nay hang doi bi don thi day la con so dau tien nen ha.
     */
    const TRAN_GUI_NHIEU = 50;

    /**
     * Tran so ho so mot luot XUAT XML.
     *
     * Bang tran gui de nguoi dung khong phai nho hai con so cho cung mot o tich. Xuat la
     * thao tac CHI DOC nen tran o day chi de giu bo nho va thoi gian dung ZIP trong tam -
     * may chu gioi han PHP 128MB - chu khong phai de chan mot hanh dong nguy hiem.
     */
    const TRAN_XUAT_XML = 50;

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
        $this->noiRongBoNho('512M');

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

    public function index()
    {
        return view('bhyt.tt12.index', array(
            'danhSachMau'   => $this->danhSachMau(),
            'danhSachCoSo'  => \App\Services\BHYT\DanhSachCoSo::danhSach(),
            'cacTrangThai'  => Tt12DanhSach::cacTrangThai(),
        ));
    }

    /** Nguon du lieu cho DataTable phia may chu */
    public function fetchData(Request $request)
    {
        $q = Tt12DanhSach::truyVan($this->loc($request));

        $tong = (clone $q)->count();
        $batDau = (int) $request->input('start', 0);
        $soDong = (int) $request->input('length', 25);

        $cac = $q->skip($batDau)->take($soDong > 0 ? $soDong : 25)->get();

        $hang = array();

        foreach ($cac as $hoSo) {
            $hang[] = $this->dongDanhSach($hoSo);
        }

        return response()->json(array(
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $tong,
            'recordsFiltered' => $tong,
            'data'            => $hang,
        ));
    }

    public function detail($maHoSo)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->firstOrFail();

        return view('bhyt.tt12.detail', array(
            'hoSo'   => $hoSo,
            'cacTab' => Tt12DetailTabs::cacTab(),
        ));
    }

    /**
     * Chi THAN cua man chi tiet, khong layout - de modal tren man danh sach nap bang AJAX.
     *
     * VI SAO KHONG dung lai detail(): detail() tra view co @extends('adminlte::page'), nap
     * vao modal se long mot ban AdminLTE thu hai vao trong ban dang chay - menu trong menu,
     * va hai bo JS cua cung mot thu vien chay song song.
     */
    public function detailThan($maHoSo)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->firstOrFail();

        return view('bhyt.tt12.partials.than-chi-tiet', array(
            'hoSo'   => $hoSo,
            'cacTab' => Tt12DetailTabs::cacTab(),
        ));
    }

    public function detailTab($maHoSo, $tab)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->firstOrFail();

        if (!Tt12DetailTabs::coTab($tab)) {
            abort(404);
        }

        if ($tab === 'loi') {
            return view('bhyt.tt12.tab-loi', array(
                'hoSo' => $hoSo,
                'cacLoi' => Tt12Loi::where('ho_so_id', $hoSo->id)
                    ->orderBy('stt_dong')->orderBy('id')->paginate(200),
            ));
        }

        if ($tab === 'xml') {
            return view('bhyt.tt12.tab-xml', array(
                'hoSo' => $hoSo,
                'xml'  => $this->docXml($hoSo),
            ));
        }

        if ($tab === 'lich_su') {
            return view('bhyt.tt12.tab-lich-su', array(
                'hoSo'      => $hoSo,
                'cacLichSu' => $hoSo->lichSuGui()->paginate(200),
            ));
        }

        return view('bhyt.tt12.tab-dong', array(
            'hoSo'   => $hoSo,
            'cotBang' => Tt12DetailTabs::cotBang($hoSo->mau),
            'cacDong' => Tt12Dong::where('ho_so_id', $hoSo->id)->orderBy('stt')->paginate(200),
        ));
    }

    /**
     * Ky roi gui MOT ho so.
     *
     * Chi day job KY - job gui duoc day boi chinh SignTt12Job khi ky xong? KHONG: hai job
     * doc lap, va nguoi dung bam mot lan thi mong doi ca hai chay. Nen o day: chua ky thi
     * day job ky, da ky thi day job gui. Bam lan hai sau khi ky xong se day job gui.
     */
    public function kyVaGui(Request $request, $maHoSo)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return $this->traLoi($request, false, 'Không tìm thấy hồ sơ ' . $maHoSo);
        }

        $ketQua = $this->dayJob($hoSo, $request->user() ? $request->user()->loginname : null);

        return $this->traLoi($request, $ketQua['thanh_cong'], $ketQua['thong_diep']);
    }

    /** Tich chon nhieu ho so roi ky va gui bang mot lan bam */
    public function kyVaGuiNhieu(Request $request)
    {
        $ma = $request->input('ma_ho_so', array());
        $ma = is_array($ma) ? $ma : array($ma);

        // Chuan hoa TRUOC khi do tran: dem con so tho thi 60 phan tu trung nhau bi tu choi
        // oan, trong khi whereIn() phia duoi von da gop trung - tuc chi vai ho so that su
        // duoc xep hang.
        $ma = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ma), 'strlen')));

        if (empty($ma)) {
            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Chưa chọn hồ sơ nào.',
            ), 400);
        }

        // Chan CA LO chu khong xep 50 cai dau roi bo phan con lai - nguoi dung se tuong da
        // gui het.
        if (count($ma) > self::TRAN_GUI_NHIEU) {
            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Mỗi lượt chỉ gửi tối đa ' . self::TRAN_GUI_NHIEU
                    . ' hồ sơ. Đang chọn ' . count($ma) . ' hồ sơ.',
            ), 422);
        }

        $nguoi = $request->user() ? $request->user()->loginname : null;

        $daXep = 0;
        $boQua = array();

        // Doc TUNG ho so trong vong lap chu khong whereIn() mot lan: toi da 50 truy van la
        // khong dang ke, con doc mot lan roi xep hang dan thi ho so cuoi cung duoc quyet
        // dinh dua tren trang thai da cu vai giay - trong khi CheckTt12Job hoac SignTt12Job
        // chay nen co the vua doi no.
        //
        // Duyet theo $ma (danh sach NGUOI DUNG GUI) chu khong theo thu tu CSDL tra ve, nen
        // ma go sai cung duoc bao lai. whereIn() chi tra ve nhung ho so TIM THAY: nguoi dung
        // chon 5, thay bao "da day 4", va khong biet cai thu 5 di dau.
        foreach ($ma as $maHoSo) {
            $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

            if ($hoSo === null) {
                $boQua[] = $maHoSo . ': Không tìm thấy hồ sơ này.';
                continue;
            }

            $ketQua = $this->dayJob($hoSo, $nguoi, Tt12XepHangKyGui::NGUON_HANG_LOAT);

            if ($ketQua['thanh_cong']) {
                $daXep++;
            } else {
                $boQua[] = $hoSo->ma_ho_so . ': ' . $ketQua['thong_diep'];
            }
        }

        // MOT con so, khong tach "day vao hang doi ky" va "day vao hang doi gui" nua: tu khi
        // mot lan bam xep ca chuoi thi moi ho so du dieu kien deu di het ca hai buoc, va cau
        // cu ("Da day 30 ho so vao hang doi ky va 0 ho so vao hang doi gui") rat de doc luot
        // thanh "chi co 0 ho so duoc gui".
        return response()->json(array(
            'thanh_cong' => $daXep > 0,
            'thong_diep' => 'Đã xếp ' . $daXep . ' hồ sơ vào hàng đợi ký và gửi.'
                . ($boQua ? ' Bỏ qua ' . count($boQua) . ' hồ sơ, xem chi tiết bên dưới.' : ''),
            'bo_qua'     => $boQua,
        ));
    }

    public function xuatDanhSach(Request $request)
    {
        return Excel::download(
            new Tt12DanhSachExport($this->loc($request)),
            'tt12-danh-sach-ho-so.xlsx'
        );
    }

    public function xuatLoi(Request $request)
    {
        $maHoSo = $request->input('ma_ho_so');

        return Excel::download(
            new Tt12LoiExport($maHoSo),
            'tt12-loi-' . ($maHoSo ?: 'tat-ca') . '.xlsx'
        );
    }

    /**
     * Tai bieu mau Excel RONG cho mot mau danh muc - chi hang tieu de, khong dong du lieu.
     *
     * abort(404) cho mau la, KHONG tra tep rong: mot tep .xlsx rong voi ten mau sai se
     * lam nguoi dung tuong da tai dung, roi dien vao mot bieu mau vo nghia.
     */
    public function bieuMau(Request $request)
    {
        $mau = $request->input('mau');

        if (!Tt12MauRegistry::co($mau)) {
            abort(404);
        }

        return Excel::download(new Tt12BieuMauExport($mau), $mau . '_bieu_mau.xlsx');
    }

    /**
     * Xuat nhat ky gui theo khoang ngay - dung lai khoang ngay tt12LocDaTai da mang tren
     * man danh sach, KHONG mo them man chon ngay rieng.
     *
     * Nut xuat la window.location (dieu huong GET thuong), nen vuot tran khong duoc bung
     * 500: bat InvalidArgumentException roi quay lai voi thong bao, giong nhanh khong-ajax
     * cua traLoi().
     */
    public function xuatNhatKy(Request $request)
    {
        // Bu khoang ngay mac dinh TRUOC khi dung Export: link dan tay, bookmark, hoac bam
        // nut truoc khi DataTable kip gan tt12LocDaTai (bien do khoi tao null) deu toi day
        // voi tu_ngay/den_ngay rong. Thieu buoc nay thi cap rong lot qua duoc phep kiem
        // tran cua Export (Carbon tu suy ra hom nay, hieu so bang 0).
        $loc = Tt12DanhSach::khoangMacDinh(array(
            'tu_ngay'  => $request->input('tu_ngay'),
            'den_ngay' => $request->input('den_ngay'),
        ));

        try {
            return Excel::download(
                new Tt12NhatKyGuiExport($loc['tu_ngay'], $loc['den_ngay']),
                'tt12-nhat-ky-gui.xlsx'
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function delete($maHoSo)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return response()->json(array('thanh_cong' => false,
                'thong_diep' => 'Không tìm thấy hồ sơ'), 404);
        }

        if (Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua)) {
            // Con dau vet doi soat voi co quan BHXH thi khong duoc xoa khoi he thong.
            return response()->json(array('thanh_cong' => false,
                'thong_diep' => 'Hồ sơ đã được cổng tiếp nhận (mã giao dịch '
                    . $hoSo->ma_gd . '), không xoá được.'), 422);
        }

        // Uy thac cho Tt12XoaHoSo, KHONG viet lai doan xoa o day. Truoc day cho nay tu xoa
        // lay hai bang va bo sot tt12_dong_thuoc_px, tt12_lich_su_gui lan tep XML da ky -
        // dung lo hong da duoc sua o Tt12Importer::doSach(), tai xuat vi co hai ban cai dat
        // song song.
        (new Tt12XoaHoSo())->xoa($hoSo);

        return response()->json(array('thanh_cong' => true, 'thong_diep' => 'Đã xoá hồ sơ'));
    }

    /**
     * Chay lai buoc dong bo sang bang danh muc cho mot ho so DA DUOC TIEP NHAN.
     *
     * VI SAO CAN NUT NAY: SubmitTt12Job commit ma_ket_qua = '200' TRUOC roi moi goi
     * dongBo(). Neu dongBo() nem giua chung (mot TEN_THUOC vuot do dai cot tren MySQL, mat
     * ket noi giua lo thu 5...) thi lan thu lai cua hang doi se thay DA_TIEP_NHAN va return
     * som - khong duong nao chay lai buoc dong bo duoc nua. Hau qua khong nhin thay ngay:
     * Xml3176Xml3Checker tu do kiem ho so KCB theo mot danh muc thieu mot nua va bao loi
     * gia cho hang nghin ma.
     */
    public function dongBoLai(Request $request, $maHoSo)
    {
        // Danh muc thuoc that co the vai nghin dong; may chu dat 128 MB / 120 giay.
        set_time_limit(600);

        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return $this->traLoi($request, false, 'Không tìm thấy hồ sơ ' . $maHoSo);
        }

        if (!Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua)) {
            // Danh muc la nguon cho buoc kiem XML3176, va giam dinh doi chieu ho so KCB voi
            // chinh bo danh muc co so DA GUI LEN. Ghi truoc khi cong tiep nhan la kiem theo
            // mot ban BHXH chua co.
            return $this->traLoi($request, false,
                'Hồ sơ chưa được cổng tiếp nhận (mã kết quả: '
                . ($hoSo->ma_ket_qua ?: 'chưa gửi') . '), chưa đồng bộ được.');
        }

        try {
            $soDong = (new Tt12DongBoDanhMuc())->dongBo($hoSo);
        } catch (\Exception $e) {
            // Bao NGUYEN VAN ly do: phan lon truong hop la mot o vuot do dai cot, va nguoi
            // dung chi sua duoc khi biet cot nao.
            return $this->traLoi($request, false, 'Đồng bộ thất bại: ' . $e->getMessage());
        }

        return $this->traLoi($request, true,
            'Đã đồng bộ ' . $soDong . ' dòng sang bảng danh mục.');
    }

    /**
     * Day lai CheckTt12Job cho mot ho so con ket o trang thai chua kiem.
     *
     * Ho so chua kiem thi khong ky duoc, va neu hang doi bi tat luc nap hoac job da het
     * tries thi khong con duong nao kiem lai - nguoi dung se tuong chuc nang ky bi hong.
     */
    public function kiemLai(Request $request, $maHoSo)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return $this->traLoi($request, false, 'Không tìm thấy hồ sơ ' . $maHoSo);
        }

        if (Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua)) {
            // Kiem lai ghi de checked_at va so_loi. Lam viec do sau khi cong da nhan la sua
            // dau vet doi soat cua mot ho so khong con sua duoc nua.
            return $this->traLoi($request, false,
                'Hồ sơ đã được cổng tiếp nhận (mã giao dịch ' . $hoSo->ma_gd
                . '), không kiểm lại được.');
        }

        if ((bool) $hoSo->is_signed) {
            // Tep XML da ky van nam nguyen tren dia va mang con so cua LAN KIEM LUC KY.
            // Kiem lai co the cho so_loi khac, va khi do hai con so noi hai dieu khac nhau
            // ve cung mot ho so - nguoi doi soat khong biet tin cai nao.
            return $this->traLoi($request, false,
                'Hồ sơ đã được ký số, không kiểm lại được vì tệp XML đã ký vẫn mang '
                . 'kết quả kiểm lúc ký. Nếu cần kiểm khác đi thì xoá hồ sơ này và nạp lại tệp.');
        }

        $hangDoi = config('organization.tt12.hang_doi');

        $job = new \App\Jobs\CheckTt12Job($hoSo->ma_ho_so);

        if (!empty($hangDoi)) {
            $job->onQueue($hangDoi);
        }

        dispatch($job);

        return $this->traLoi($request, true, 'Đã đẩy hồ sơ vào hàng đợi kiểm.');
    }

    /** @return array ['thanh_cong' => bool, 'hanh_dong' => 'ky'|'gui'|'bo_qua', 'thong_diep' => string] */
    private function dayJob(Tt12HoSo $hoSo, $nguoi, $nguon = Tt12XepHangKyGui::NGUON_MAN_HINH)
    {
        // Hoi dieu kien TRUOC khi dat khoa: dat khoa roi moi phat hien ho so khong du dieu
        // kien la chan chinh no trong 40 phut ma khong lam gi ca.
        if (!$hoSo->is_signed) {
            $nenKy = Tt12QuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi);

            if ($nenKy !== Tt12QuyetDinhGui::KY) {
                return array('thanh_cong' => false, 'hanh_dong' => 'bo_qua',
                    'thong_diep' => Tt12QuyetDinhGui::moTa($nenKy));
            }
        } else {
            // Da ky roi ma van bam: chuoi se bo qua buoc ky va di thang toi buoc gui.
            $nenGui = Tt12QuyetDinhGui::nenGui(true, $hoSo->ma_ket_qua);

            if ($nenGui !== Tt12QuyetDinhGui::GUI) {
                return array('thanh_cong' => false, 'hanh_dong' => 'bo_qua',
                    'thong_diep' => Tt12QuyetDinhGui::moTa($nenGui));
            }
        }

        // MOT lan bam xep CA CHUOI ky - gui. Truoc day moi lan bam chi lam mot buoc, nen
        // nguoi dung phai bam hai lan dung thu tu moi gui duoc that; ten nut noi mot dang
        // ma hanh vi mot neo.
        if (!Tt12XepHangKyGui::xep($hoSo->ma_ho_so, $nguoi, $nguon)) {
            return array('thanh_cong' => false, 'hanh_dong' => 'bo_qua',
                'thong_diep' => 'Hồ sơ đang được xử lý ở một lượt khác, thử lại sau.');
        }

        return array('thanh_cong' => true, 'hanh_dong' => $hoSo->is_signed ? 'gui' : 'ky',
            'thong_diep' => 'Đã đẩy vào hàng đợi ký và gửi');
    }

    /**
     * NOI RONG gioi han bo nho, khong bao gio thu hep.
     *
     * ini_set('memory_limit', '512M') tran la SAI hai duong:
     * - Tren may chu da dat 1G trong php.ini, no HA xuong 512M - dung cai nguoc lai voi y
     *   dinh, va chi lo ra khi mot tep lon lam dinh bo nho.
     * - Khi tien trinh dang dung nhieu hon muc dat, PHP tu choi va phat canh bao. Trong bo
     *   test (convertWarningsToExceptions="true") canh bao do thanh mot ngoai le va lam
     *   hong mot ham khong lien quan gi den bo nho.
     */
    private function noiRongBoNho($muc)
    {
        $hienTai = trim((string) ini_get('memory_limit'));

        // '-1' la khong gioi han - da rong hon moi con so.
        if ($hienTai === '' || $hienTai === '-1') {
            return;
        }

        if ($this->sangByte($hienTai) >= $this->sangByte($muc)) {
            return;
        }

        ini_set('memory_limit', $muc);
    }

    /** @return int doi chuoi kieu '512M' / '1G' cua php.ini sang so byte */
    private function sangByte($chuoi)
    {
        $chuoi = trim((string) $chuoi);
        $so = (int) $chuoi;
        $donVi = strtolower(substr($chuoi, -1));

        if ($donVi === 'g') {
            return $so * 1024 * 1024 * 1024;
        }

        if ($donVi === 'm') {
            return $so * 1024 * 1024;
        }

        if ($donVi === 'k') {
            return $so * 1024;
        }

        return $so;
    }

    /** @return array bo loc doc tu request, dung chung cho man hinh va xuat Excel */
    private function loc(Request $request)
    {
        return array(
            'mau'        => $request->input('mau'),
            'ma_cskcb'   => $request->input('ma_cskcb'),
            'imported_by' => $request->input('imported_by'),
            'trang_thai' => $request->input('trang_thai'),
            'tu_ngay'    => $request->input('tu_ngay'),
            'den_ngay'   => $request->input('den_ngay'),
            'tim'        => $request->input('tim'),
        );
    }

    /** @return array mot dong cho DataTable */
    private function dongDanhSach(Tt12HoSo $hoSo)
    {
        return array(
            'ma_ho_so'            => $hoSo->ma_ho_so,
            'mau'                 => $hoSo->mau,
            'ten_tep'             => $hoSo->ten_tep,
            'ma_cskcb'            => $hoSo->ma_cskcb,
            'so_dong'             => (int) $hoSo->so_dong,
            'so_loi'              => (int) $hoSo->so_loi,
            // Co CO_LOI_NAP, khong phai noi dung loi: cot danh sach chi can bao "ho so nay
            // hong, mo ra xem". Noi dung day du in o man chi tiet.
            'co_loi_nap'          => $hoSo->import_error ? 1 : 0,
            'da_kiem'             => $hoSo->checked_at ? 1 : 0,
            'da_ky'               => $hoSo->is_signed ? 1 : 0,
            'ma_gd'               => $hoSo->ma_gd,
            'ma_ket_qua'          => $hoSo->ma_ket_qua,
            'thoi_gian_tiep_nhan' => $hoSo->thoi_gian_tiep_nhan,
            'da_dong_bo'          => $hoSo->dong_bo_at ? 1 : 0,
            'imported_at'         => $hoSo->imported_at ? $hoSo->imported_at->format('d/m/Y H:i') : null,
        );
    }

    /** @return string noi dung XML da ky, hoac thong bao neu chua co */
    /**
     * Xuat XML cua cac ho so da tich chon, kem chu ky so neu co.
     *
     * MOT ho so -> tai thang .xml. NHIEU ho so -> dong ZIP kem tep ke.
     *
     * Thao tac CHI DOC: khong sua gi, khong goi cong. Vi vay dung quyen cua man danh sach,
     * khong tach quyen rieng.
     */
    public function xuatXml(Request $request)
    {
        $ma = $request->input('ma_ho_so');
        $ma = is_array($ma) ? $ma : array();

        $ma = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ma), 'strlen')));

        if (empty($ma)) {
            return $this->traLoi($request, false, 'Chưa chọn hồ sơ nào.');
        }

        if (count($ma) > self::TRAN_XUAT_XML) {
            return $this->traLoi($request, false, 'Mỗi lượt chỉ xuất tối đa '
                . self::TRAN_XUAT_XML . ' hồ sơ. Đang chọn ' . count($ma) . ' hồ sơ.');
        }

        $cacTep = array();
        $dongKe = array();

        foreach ($ma as $maHoSo) {
            $hoSo = Tt12HoSo::where('ma_ho_so', $maHoSo)->first();

            if ($hoSo === null) {
                $dongKe[] = array($maHoSo, '', 'Không tìm thấy', 'Hồ sơ không còn trong phần mềm');
                continue;
            }

            $kq = Tt12XuatXml::cua($hoSo);

            $dongKe[] = array(
                $maHoSo,
                (string) $hoSo->mau,
                Tt12XuatXml::nhan($kq['trang_thai']),
                $kq['ly_do'],
            );

            if ($kq['trang_thai'] === Tt12XuatXml::HONG) {
                continue;
            }

            $cacTep[$kq['ten_tep']] = $kq['noi_dung'];
        }

        if (empty($cacTep)) {
            return $this->traLoi($request, false,
                'Không hồ sơ nào xuất được XML. Kiểm tra lại danh sách đã chọn.');
        }

        // MOT tep thi tai thang, khong boc ZIP: bat nguoi dung giai nen mot tep la them mot
        // buoc vo ich cho truong hop thuong gap nhat.
        if (count($cacTep) === 1 && count($dongKe) === 1) {
            $ten = key($cacTep);

            return response($cacTep[$ten], 200, array(
                'Content-Type'        => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $ten . '"',
            ));
        }

        return $this->dongZip($cacTep, $dongKe);
    }

    /**
     * Dong ZIP trong thu muc tam roi tra ve, xoa tep tam sau khi gui.
     *
     * TEP KE luon co mat: khong co no thi nguoi mo ZIP thay thieu vai tep ma khong biet vi
     * sao - va se tuong phan mem lam mat, thay vi biet rang ho so do khong co dong nao.
     */
    private function dongZip(array $cacTep, array $dongKe)
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'tt12xml');

        $zip = new \ZipArchive();

        if ($zip->open($duongDan, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($duongDan);

            return response()->json(array(
                'thanh_cong' => false,
                'thong_diep' => 'Không tạo được tệp ZIP trên máy chủ.',
            ), 500);
        }

        foreach ($cacTep as $ten => $noiDung) {
            $zip->addFromString($ten, $noiDung);
        }

        $zip->addFromString('_ke-khai.csv', $this->tepKe($dongKe));
        $zip->close();

        $tenZip = 'tt12-xml-' . date('Ymd-His') . '.zip';

        return response()->download($duongDan, $tenZip, array(
            'Content-Type' => 'application/zip',
        ))->deleteFileAfterSend(true);
    }

    /**
     * Tep ke dang CSV, co BOM UTF-8.
     *
     * BOM la BAT BUOC: khong co no thi Excel tren Windows doc CSV theo bang ma he thong va
     * moi dau tieng Viet thanh ky tu la - dung loi da gap o cac tep xuat truoc.
     */
    private function tepKe(array $dongKe)
    {
        $noi = "\xEF\xBB\xBF";
        $noi .= "Mã hồ sơ,Mẫu,Tình trạng,Ghi chú\r\n";

        foreach ($dongKe as $dong) {
            $o = array();

            foreach ($dong as $gt) {
                // Boc dau nhay kep va nhan doi dau nhay ben trong - quy tac CSV. Ly do loi
                // co the chua dau phay va xuong dong.
                $o[] = '"' . str_replace('"', '""', (string) $gt) . '"';
            }

            $noi .= implode(',', $o) . "\r\n";
        }

        return $noi;
    }

    private function docXml(Tt12HoSo $hoSo)
    {
        if (empty($hoSo->duong_dan_da_ky)) {
            return 'Hồ sơ chưa được ký.';
        }

        $dia = Storage::disk('exportTt12');

        if (!$dia->exists($hoSo->duong_dan_da_ky)) {
            return 'Không tìm thấy tệp đã ký: ' . $hoSo->duong_dan_da_ky;
        }

        return $dia->get($hoSo->duong_dan_da_ky);
    }

    /** Tra JSON cho lenh goi AJAX, redirect cho lenh goi thuong */
    private function traLoi(Request $request, $thanhCong, $thongDiep)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array(
                'thanh_cong' => $thanhCong,
                'thong_diep' => $thongDiep,
            ), $thanhCong ? 200 : 422);
        }

        return redirect()->route('bhyt.tt12.index')
            ->with($thanhCong ? 'success' : 'error', $thongDiep);
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
