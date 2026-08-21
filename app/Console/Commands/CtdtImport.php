<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtXepHangKyGui;

/**
 * Quet thu muc inbox, nap moi tep XML tim duoc, roi nhat moi ho so du dieu kien de xep
 * hang ky - gui (xem nhatVaXepHang()).
 *
 * HAI CHE DO. Mac dinh chay MOT LUOT roi thoat (giong truoc day) - dung cho cron/task
 * scheduler hoac chay tay. Voi --lien-tuc, lenh chay giong xml3176import:day: lap lai
 * quet trong mot tien trinh nssm song lau, nghi --nghi giay giua hai vong - nhung KHAC o
 * ba diem, vi lenh nay POST that len cong BHXH chu khong chi ghi tep:
 *
 *   1. TU THOAT sau --so-vong vong (xml3176import:day dung `while (true)` chay mai). PHP
 *      chay dai han o gioi han 128MB se phinh; thoat chu dong de nssm dung lai mot tien
 *      trinh SACH thi khac han bi OOM giet GIUA LUC dang goi cong - luc do khong ai biet
 *      cong da nhan hay chua.
 *   2. Tep co TEP_CO_DUNG trong thu muc inbox la phanh tay nguoi truc dem tao duoc bang
 *      tay (xem coDung()) - khong phu thuoc config trong bo nho cua tien trinh dang chay.
 *   3. --dry-run bi TU CHOI ket hop voi --lien-tuc: --dry-run la de nguoi ta NHIN mot lan
 *      roi quyet dinh, ghep voi --lien-tuc thi no do log mai ma khong lam gi ca.
 */
class CtdtImport extends Command
{
    protected $signature = 'ctdt:import
        {--duong-dan= : Thu muc quet, mac dinh lay tu cau hinh}
        {--gioi-han= : Tran so TEP xu ly moi luot, mac dinh lay tu cau hinh (200 neu khong cau hinh)}
        {--dry-run : Chi liet ke, khong nap khong doi gi. KHONG duoc ket hop voi --lien-tuc}
        {--khong-ky : Nap va kiem, dung truoc buoc ky}
        {--khong-gui : Ky nhung khong gui len cong}
        {--lien-tuc : Chay nen lien tuc giong xml3176import:day, tu thoat sau --so-vong vong}
        {--nghi=5 : So giay nghi giua hai vong khi --lien-tuc}
        {--so-vong=1000 : Tran so vong khi --lien-tuc, lenh tu thoat sau khi chay du}';

    protected $description = 'Quet thu muc inbox, nap chung tu dien tu PL02';

    /** Tep nap xong chuyen vao day */
    const THU_MUC_DA_NAP = 'da-nap';

    /** Tep nap that bai chuyen vao day - KHONG xoa, con du lieu ma dieu tra */
    const THU_MUC_LOI = 'loi';

    /** Khoa chong hai luot chay chong len nhau */
    const KHOA_LUOT = 'ctdt:import:dang-chay';

    /**
     * Thoi han khoa luot, tinh bang PHUT (Cache::add nhan phut o Laravel 5.5).
     *
     * Khoa mo coi khi tien trinh ket thuc binh thuong cung duoc xu bang Cache::forget()
     * trong finally; truong hop bi kill -9 thi nguoi van hanh xoa tay.
     *
     * TASK 5 NANG TU 60 LEN 1440: --lien-tuc giu khoa nay SUOT ca vong doi tien trinh (dat
     * MOT LAN truoc vongLap(), mo MOT LAN sau khi vongLap() ket thuc), khong phai suot mot
     * luot quet nhu che do mot lan. Mac dinh --so-vong=1000 x --nghi=5 giay da la 5000 giay
     * (~83 phut) CHI RIENG thoi gian nghi, chua tinh thoi gian quet moi vong - da vuot khoi
     * han cu 60 phut. Neu khoa het han GIUA LUC tien trinh con dang chay, mot tien trinh
     * thu hai se Cache::add() thanh cong va chen vao - hai tien trinh cung nhat mot tep len.
     * 1440 phut (24 gio) con nhieu du dia tren muc toi thieu ~83 phut cua bo mac dinh.
     */
    const KHOA_LUOT_PHUT = 1440;

    /** Tran so ho so nhat len xep hang moi vong */
    const TRAN_NHAT = 200;

    /**
     * Tep dat trong thu muc inbox de DUNG BUOC GUI ngay vong sau.
     *
     * VI SAO KHONG dung config: tien trinh chay lien tuc giu config trong bo nho, nen sua
     * import_tu_dong_gui thanh false khong an thua cho toi khi ai do nssm restart. Mot cai
     * phanh chi an sau khi khoi dong lai thi khong phai la phanh.
     *
     * VI SAO la TEP chu khong phai khoa cache: nguoi truc dem tao duoc mot tep rong. Ho
     * khong sua duoc PHP, va co the khong vao duoc Redis.
     */
    const TEP_CO_DUNG = 'DUNG-GUI';

    /** @var CtdtImporter */
    protected $importer;

    public function __construct(CtdtImporter $importer = null)
    {
        parent::__construct();

        // CANH BAO Laravel 5.5: tham so vua co typehint lop vua co gia tri mac dinh thi
        // container LUON tiem khi resolve qua app() (Console command la truong hop nay).
        // Phep lui `?: new CtdtImporter()` chi co tac dung khi ai do goi thang
        // `new CtdtImport()` ma khong qua container - dung dieu test lam de doc getDefinition()
        // ma khong can dung toi importer thuc. Giu nguyen ca hai duong.
        $this->importer = $importer ?: new CtdtImporter();
    }

    public function handle()
    {
        $thuMuc = $this->option('duong-dan')
            ?: config('organization.chung_tu_dien_tu.import_path');

        if (empty($thuMuc)) {
            $this->error('Chua cau hinh organization.chung_tu_dien_tu.import_path');

            return 1;
        }

        if (!is_dir($thuMuc)) {
            $this->error('Khong phai thu muc: ' . $thuMuc);

            return 1;
        }

        $khoDe = (bool) $this->option('dry-run');

        // Khoa luot: hai luot chay chong len nhau se cung nhat mot tep len va nap hai lan.
        // Bo qua khoa khi --dry-run vi luot do khong doi gi ca.
        if (!$khoDe && !Cache::add(self::KHOA_LUOT, true, self::KHOA_LUOT_PHUT)) {
            $this->warn('Mot luot ctdt:import khac dang chay. Bo qua luot nay.');

            return 0;
        }

        try {
            if ($this->option('lien-tuc')) {
                if ($khoDe) {
                    // --dry-run la de nguoi ta NHIN mot lan roi quyet dinh. Gap voi
                    // --lien-tuc thi no do log mai ma khong lam gi ca, va che mat dong log
                    // that cua nhung lenh khac.
                    $this->error('Khong ket hop --dry-run voi --lien-tuc duoc.');

                    return 1;
                }

                return $this->vongLap($thuMuc, $this->gioiHanHieuLuc());
            }

            return $this->quet($thuMuc, $this->gioiHanHieuLuc(), $khoDe);
        } finally {
            // Khoa luot dat NGOAI vong lap (Cache::add o tren, chi mot lan) va mo NGOAI
            // vong lap (o day, sau khi vongLap() da chay het --so-vong vong). Neu mo trong
            // than vong lap, vong thu hai se tu thay khoa cua chinh minh va bo qua vinh
            // vien - Cache::add() chi tra true lan dau, tra false moi lan sau vi khoa da
            // duoc mo lai truoc do.
            if (!$khoDe) {
                Cache::forget(self::KHOA_LUOT);
            }
        }
    }

    /**
     * @param  string $thuMuc
     * @param  int    $gioiHan
     * @param  bool   $khoDe   --dry-run
     * @return int ma thoat
     */
    protected function quet($thuMuc, $gioiHan, $khoDe)
    {
        $tep = $this->dsTep($thuMuc);

        if (empty($tep)) {
            $this->info('Khong co tep nao trong ' . $thuMuc);

            return 0;
        }

        if (count($tep) > $gioiHan) {
            // Bao TO, khong chi ghi log: cat bot im lang doc y het chay het.
            $this->warn('Tim thay ' . count($tep) . ' tep, chi xu ly ' . $gioiHan
                . ' tep trong luot nay (--gioi-han). Chay lai de xu tiep.');

            $tep = array_slice($tep, 0, $gioiHan);
        }

        $dsMaHoSo = [];
        $soHong = 0;
        $soKhongDoiDuoc = 0;

        foreach ($tep as $duongDan) {
            if ($khoDe) {
                $this->line('[dry-run] se nap: ' . $duongDan);
                continue;
            }

            $kq = $this->napMotTep($duongDan, $thuMuc);

            if ($kq['ma_ho_so'] === null) {
                $soHong++;
            } else {
                $dsMaHoSo = array_merge($dsMaHoSo, $kq['ma_ho_so']);
            }

            if (!$kq['da_chuyen']) {
                // Tep DA duoc xu ly (nap thanh cong hoac chuyen sang loi/) nhung khong doi
                // duoc khoi thu muc goc. No van nam o goc, nen luot quet sau se NHAT LAI dung
                // no va xu ly lan nua - voi ho so da nap thanh cong, do la nap trung.
                $soKhongDoiDuoc++;
            }
        }

        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        // --dry-run khong duoc dong gi ca (ke ca doc/xep hang); --khong-ky la "dung truoc
        // buoc ky" theo dung mo ta cua chinh tuy chon do khai o $signature.
        if (!$khoDe && !$this->option('khong-ky')) {
            // KHONG truyen $dsMaHoSo vao - xem chu thich nhatVaXepHang(). Chi truyen thu muc,
            // vi Task 5 se dat tep co dung o day.
            $this->nhatVaXepHang($thuMuc);
        }

        if ($soKhongDoiDuoc > 0) {
            // Ma thoat KHAC 0: day la loai loi ma khong ai duoc phep im lang di qua. nssm va
            // nguoi van hanh phai thay dieu nay, khong chi log.
            $this->error($soKhongDoiDuoc . ' tep DA XU LY nhung KHONG DOI duoc khoi thu muc'
                . ' goc - luot quet sau se gap lai chung. Kiem tra quyen ghi / tep bi khoa,'
                . ' roi doi tay hoac chay lai.');

            return 1;
        }

        return 0;
    }

    /**
     * Chay lien tuc, giong xml3176import:day - nhung TU THOAT sau --so-vong.
     *
     * VI SAO tu thoat chu khong `while (true)`: PHP chay dai han o gioi han 128MB se phinh,
     * va viec du an da phai co lenh jobs:restart-stuck cho thay hang doi tung ket that.
     * Thoat chu dong de nssm dung lai mot tien trinh sach thi khac han bi OOM giet GIUA LUC
     * dang goi cong BHXH - luc do khong ai biet cong da nhan hay chua.
     *
     * VI SAO khong vut ma thoat cua quet(): quet() tra ve 1 khi mot tep DA XU LY xong (nap
     * thanh cong hoac chuyen sang loi/) nhung khong doi duoc khoi thu muc goc - day la ket
     * qua cua mot vong sua Critical rieng (xem chuyen()), va no bao hieu nguy co nap trung /
     * gui trung o vong sau. Nhieu vong hong dang duoc gop lai bang each mode "vong nao con
     * hong thi nho lai" thay vi "vong cuoi quyet dinh": ma thoat cuoi cung phan anh CO tung
     * vong nao gap loai loi nay hay khong trong suot ca tien trinh, chu khong chi vong cuoi
     * cung. nssm/nguoi van hanh doc log dong tong ket, khong doc lai tung dong log cua tung
     * vong.
     *
     * @param  string $thuMuc
     * @param  int    $gioiHan
     * @return int ma thoat: khac 0 neu CO IT NHAT MOT vong gap loai loi "khong doi duoc tep"
     */
    protected function vongLap($thuMuc, $gioiHan)
    {
        $soVong = max(1, (int) $this->option('so-vong'));
        $nghi = max(1, (int) $this->option('nghi'));

        $this->info('Chay lien tuc: nghi ' . $nghi . ' giay giua hai vong, tu thoat sau '
            . $soVong . ' vong.');

        $soVongHong = 0;

        for ($i = 1; $i <= $soVong; $i++) {
            try {
                if ($this->quet($thuMuc, $gioiHan, false) !== 0) {
                    // Khong return ngay: mot vong hong loai nay KHONG duoc lam dung ca
                    // chuoi - no chi nghia la vong nay co tep khong doi duoc, van con nhieu
                    // ho so khac can nap va gui o cac vong sau. Chi nho lai de bao o dong
                    // tong ket cuoi cung.
                    $soVongHong++;
                }
            } catch (\Exception $e) {
                // Mot vong hong KHONG duoc lam chet ca tien trinh: dung o day nghia la khong
                // ho so nao duoc gui nua cho toi khi co nguoi phat hien ra dich vu da chet.
                Log::error('ctdt:import vong ' . $i . ' hong: ' . $e->getMessage());
                $this->error('Vong ' . $i . ' hong: ' . $e->getMessage());
                $soVongHong++;
            }

            if ($i < $soVong) {
                sleep($nghi);
            }
        }

        if ($soVongHong > 0) {
            $this->error('Da chay du ' . $soVong . ' vong, trong do ' . $soVongHong
                . ' vong gap tep khong doi duoc / loi - xem log cua tung vong o tren.'
                . ' Thoat de nssm dung lai tien trinh sach.');

            return 1;
        }

        $this->info('Da chay du ' . $soVong . ' vong, thoat de nssm dung lai tien trinh sach.');

        return 0;
    }

    /**
     * Nap mot tep, chuyen no sang da-nap/ hoac loi/.
     *
     * @return array{ma_ho_so: array|null, da_chuyen: bool}
     *   ma_ho_so: danh sach ma ho so khi thanh cong, null khi tep hong
     *   da_chuyen: co doi duoc tep khoi thu muc goc hay khong (du thanh cong hay hong)
     */
    protected function napMotTep($duongDan, $thuMuc)
    {
        try {
            $kq = $this->importer->nhapTuTep($duongDan);
        } catch (\Exception $e) {
            // Mot tep hong KHONG duoc lam dung ca luot quet, va no phai duoc chuyen di
            // de luot sau khong vap lai dung no.
            Log::error('ctdt:import loi khi xu ly ' . $duongDan . ': ' . $e->getMessage());
            $this->error(basename($duongDan) . ': ' . $e->getMessage());
            $daChuyen = $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return ['ma_ho_so' => null, 'da_chuyen' => $daChuyen];
        }

        if (!$kq->thanhCong) {
            Log::error('ctdt:import nap that bai ' . $duongDan . ': ' . $kq->lyDoThatBai);
            $this->error(basename($duongDan) . ': ' . $kq->lyDoThatBai);
            $daChuyen = $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return ['ma_ho_so' => null, 'da_chuyen' => $daChuyen];
        }

        if (!empty($kq->dsGhiDeDaGui)) {
            // Ho so DA duoc cong nhan ma bi nap de: dau vet ma_gd bi xoa. Phai keu to, vi
            // sau nay (Task 4/5) chuoi tu dong se GUI LAI chinh no neu khong ai de y.
            $this->warn(basename($duongDan) . ': ghi de ' . count($kq->dsGhiDeDaGui)
                . ' ho so DA TUNG GUI - ' . implode(', ', $kq->dsGhiDeDaGui));
            Log::warning('ctdt:import ghi de ho so da gui: ' . implode(', ', $kq->dsGhiDeDaGui));
        }

        $this->line(basename($duongDan) . ': ' . $kq->soThanhCong . ' ho so, '
            . $kq->soThatBai . ' hong');

        $daChuyen = $this->chuyen($duongDan, $thuMuc, self::THU_MUC_DA_NAP);

        if (!$daChuyen) {
            // CRITICAL: ho so NAY DA VAO CSDL roi. Neu im lang o day, luot sau se nap lai
            // dung tep nay - va voi ho so roi se duoc ky/gui (Task 4/5), do la mot lan POST
            // that THU HAI len cong BHXH cho cung mot ho so.
            $this->error(basename($duongDan) . ': DA NAP THANH CONG vao CSDL nhung KHONG'
                . ' DOI duoc khoi thu muc goc. Luot quet sau se nap lai. Kiem tra quyen ghi'
                . ' / tep bi khoa boi chuong trinh khac.');
            Log::error('ctdt:import da nap ' . $duongDan . ' nhung khong doi duoc khoi thu'
                . ' muc goc - nguy co nap trung o luot sau');
        }

        return ['ma_ho_so' => $kq->dsMaHoSo, 'da_chuyen' => $daChuyen];
    }

    /**
     * Liet ke tep XML trong thu muc, BO QUA hai thu muc con da-nap/ va loi/.
     *
     * @return array duong dan tuyet doi, sap xep de thu tu on dinh giua cac luot
     */
    protected function dsTep($thuMuc)
    {
        // KHONG quet de quy: chi lay tep ngay trong thu muc goc. Quet de quy se nhat lai
        // chinh nhung tep vua chuyen vao da-nap/ - va voi ho so da gui, do la mot lan POST
        // that nua len cong BHXH.
        //
        // Dung glob('*') roi loc duoi bang pathinfo() thay vi glob('*.[xX][mM][lL]'): mau
        // ky tu-lop tren Windows/NTFS he thong tep khong phan biet hoa thuong nen mau
        // [xX][mM][lL] la thua, nhung van giu cach loc tuong minh nay de code khong am tham
        // phu thuoc vao hanh vi khong phan biet hoa thuong cua he dieu hanh.
        $tim = glob(rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . '*');

        if ($tim === false) {
            return [];
        }

        $tim = array_filter($tim, function ($duongDan) {
            return is_file($duongDan)
                && strtolower(pathinfo($duongDan, PATHINFO_EXTENSION)) === 'xml';
        });

        sort($tim);

        return array_values($tim);
    }

    /**
     * Chuyen tep sang thu muc con, tao thu muc neu chua co.
     *
     * @return bool THANH CONG hay khong. Goi noi CHUA doc gia tri tra ve la mot loi -
     *   rename() hong (AV khoa tep, thieu quyen ghi, o mang chap) truoc day chi vao
     *   Log::error roi bi lang quen, va tep van nam nguyen o thu muc goc de luot sau nap
     *   lai chinh no.
     */
    protected function chuyen($duongDan, $thuMuc, $thuMucCon)
    {
        $dich = rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . $thuMucCon;

        if (!is_dir($dich) && !@mkdir($dich, 0775, true) && !is_dir($dich)) {
            Log::error('ctdt:import khong tao duoc thu muc ' . $dich);

            return false;
        }

        $tepDich = $dich . DIRECTORY_SEPARATOR . basename($duongDan);

        // Trung ten thi ghep thoi diem vao, KHONG ghi de: tep nguon la bang chung goc, ghi
        // de mot cai la mat vinh vien.
        if (file_exists($tepDich)) {
            $tepDich = $dich . DIRECTORY_SEPARATOR
                . pathinfo($duongDan, PATHINFO_FILENAME)
                . '-' . now()->format('YmdHis') . '.xml';
        }

        if (!@rename($duongDan, $tepDich)) {
            Log::error('ctdt:import khong chuyen duoc ' . $duongDan . ' sang ' . $tepDich);

            return false;
        }

        return true;
    }

    /**
     * Gia tri hieu luc cua --gioi-han: uu tien tuy chon dong lenh, roi den cau hinh
     * organization.chung_tu_dien_tu.import_gioi_han, roi moi den 200.
     *
     * Dung ?: chu KHONG dung config($khoa, $macDinh): dang hai tham so cua config() chi
     * lui ve macDinh khi KHOA KHONG TON TAI, chu khong lui khi khoa ton tai voi gia tri
     * null - va organization.php co the co dong 'import_gioi_han' => null trong mot ban
     * cau hinh loi.
     *
     * @return int
     */
    protected function gioiHanHieuLuc()
    {
        $tuyChon = $this->option('gioi-han');

        if ($tuyChon !== null && $tuyChon !== '') {
            return (int) $tuyChon;
        }

        return (int) (config('organization.chung_tu_dien_tu.import_gioi_han') ?: 200);
    }

    /**
     * Nhat moi ho so DA DU DIEU KIEN ma chua len duoc cong, roi xep hang ky - gui.
     *
     * KHONG NHAN danh sach ma ho so vua nap. Ho so vua nap gan nhu LUON o trang thai chua
     * kiem - bo kiem con nam trong hang doi JobCtdt - nen nhat theo danh sach vua nap se
     * truot gan het, va phai trong cho vong sau. Truy van thang thi moi vong deu vet dung
     * nhung ho so VUA MOI du dieu kien, bat ke vong nao nap chung, ke ca ho so nguoi ta sua
     * tay tren man hinh roi cho bo kiem chay lai.
     *
     * VI SAO KHONG cho tat ca vao hang doi roi de job tu loc: job ky da co cua chan cua no,
     * nhung xep 200 job de 195 cai tu thoat lam nhieu log den muc khong ai doc nua - va ba
     * hang doi thi dai ra ma khong ai biet vi sao.
     *
     * @param  string $thuMuc thu muc inbox - Task 5 dat tep co dung o day
     * @return int so ho so da xep hang
     */
    protected function nhatVaXepHang($thuMuc)
    {
        if (!$this->duocGui($thuMuc)) {
            return 0;
        }

        // Dieu kien o CSDL chi la BO LOC THO de thu hep tap phai doc len; luat that van do
        // CtdtQuyetDinhGui::nenKy() quyet dinh o duoi. Khong nhan doi luat o day: mot ban SQL
        // doc lap se lech voi nenKy() vao ngay ai do sua mot trong hai.
        //
        // "Chua co ket qua" o day PHAI khop CHINH XAC voi !empty($hoSo->ma_ket_qua) cua
        // CtdtTrangThaiGui::cua() - va voi dung quy uoc CtdtDanhSach.php da lap: NULL, chuoi
        // rong VA chuoi '0' deu la "chua co ket qua" (PHP coi empty('0') === true). Bo sot
        // '0' se khien mot ho so cong BHXH tra ma_ket_qua = '0' bi coi la "da co ket qua" va
        // VINH VIEN khong duoc lenh nay nhat lai, trong khi man danh sach van hien no la
        // "Cho gui"/"Gui that bai" - cong BHXH la he ngoai, ta khong kiem soat duoc no tra ve
        // gi.
        $ungVien = CtdtHoSo::whereNotNull('checked_at')
            ->where('so_loi', '=', 0)
            ->where(function ($q) {
                $q->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '')->orWhere('ma_ket_qua', '0');
            })
            // Cu truoc moi truoc: ho so nam lau nhat la ho so nguoi ta doi lau nhat.
            ->orderBy('imported_at')
            ->limit(self::TRAN_NHAT)
            ->get();

        if ($ungVien->isEmpty()) {
            return 0;
        }

        $soXep = 0;
        $soBoQua = 0;

        foreach ($ungVien as $hoSo) {
            // Hoi lai bang nenKy() du da loc o SQL: day moi la luat that, va no la MOT NOI
            // duy nhat dung chung voi man hinh va SignCtdtJob.
            if (CtdtQuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi) !== CtdtQuyetDinhGui::KY) {
                $soBoQua++;
                continue;
            }

            // Noi ro nguon la CONSOLE: khong duoc de bang nhat ky suy tu $nguoiGui = null,
            // vi mot cu bam tay khi chua dang nhap cung cho ra null.
            //
            // xep() tra false khi ho so dang co luot xu ly khac - o che do lien tuc day la
            // chuyen THUONG XUYEN, vi vong truoc vua xep chinh no va chuoi con dang chay.
            if (CtdtXepHangKyGui::xep($hoSo->ma_ho_so, null, CtdtLichSuGui::NGUON_CONSOLE)) {
                $soXep++;
            } else {
                $soBoQua++;
            }
        }

        if ($soXep > 0) {
            $this->info('Da xep hang ky va gui: ' . $soXep . ' ho so; bo qua ' . $soBoQua
                . ' (dang xu ly o vong truoc).');
        }

        if ($ungVien->count() >= self::TRAN_NHAT) {
            $this->warn('Cham tran ' . self::TRAN_NHAT . ' ho so trong mot vong. Con ho so '
                . 'du dieu kien chua duoc nhat - vong sau nhat tiep.');
        }

        return $soXep;
    }

    /**
     * Co duoc gui len cong khong. Hoi MOI VONG, khong hoi mot lan roi nho.
     *
     * @param  string $thuMuc thu muc inbox
     * @return bool
     */
    protected function duocGui($thuMuc)
    {
        // Hoi tep co TRUOC MOI THU: day la phanh tay, no phai thang moi cau hinh.
        if ($this->coDung($thuMuc)) {
            $this->warn('Thay tep ' . self::TEP_CO_DUNG . ' trong thu muc inbox - DUNG GUI. '
                . 'Van tiep tuc nap va kiem. Xoa tep do di de gui lai.');

            return false;
        }

        if ($this->option('khong-gui')) {
            return false;
        }

        // Hai cong tac RIENG. submit_enabled la "cho phep NGUOI bam nut gui"; khoa nay la
        // "cho phep MAY gui khi khong co ai nhin". Hai muc do tin cay khac nhau thi phai hai
        // cong tac khac nhau.
        if (!(bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui')) {
            return false;
        }

        return true;
    }

    /**
     * Co tep co dung trong thu muc inbox khong.
     *
     * Cong khai de test goi truc tiep duoc - day la cai phanh tay, no dang duoc kiem ky
     * hon phan con lai.
     *
     * @param  string $thuMuc
     * @return bool
     */
    public function coDung($thuMuc)
    {
        return file_exists(rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . self::TEP_CO_DUNG);
    }
}
