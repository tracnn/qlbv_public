<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Ctdt\CtdtImporter;

/**
 * Quet thu muc inbox, nap moi tep XML tim duoc.
 *
 * PHAM VI TASK NAY DUNG O NAP: ky so va gui len cong la Task 4, che do chay lien tuc
 * (--lien-tuc) la Task 5. Lenh nay chi co --dry-run, --khong-ky, --khong-gui khai bao san
 * lam cho san cho hai task sau, nhung CHUA doc chung o day.
 */
class CtdtImport extends Command
{
    protected $signature = 'ctdt:import
        {--duong-dan= : Thu muc quet, mac dinh lay tu cau hinh}
        {--gioi-han= : Tran so TEP xu ly moi luot, mac dinh lay tu cau hinh (200 neu khong cau hinh)}
        {--dry-run : Chi liet ke, khong nap khong doi gi}
        {--khong-ky : Nap va kiem, dung truoc buoc ky}
        {--khong-gui : Ky nhung khong gui len cong}';

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
     */
    const KHOA_LUOT_PHUT = 60;

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
            return $this->quet($thuMuc, $this->gioiHanHieuLuc(), $khoDe);
        } finally {
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
}
