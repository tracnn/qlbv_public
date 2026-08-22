<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtXepHangKyGui;

/**
 * Quet thu muc inbox, nap chung tu dien tu PL02 va xep hang ky/gui.
 */
class CtdtImport extends Command
{
    protected $signature = 'ctdt:import
        {--duong-dan= : Thu muc quet, mac dinh lay tu cau hinh}
        {--gioi-han= : Tran so TEP xu ly moi luot, mac dinh lay tu cau hinh (200 neu khong cau hinh)}
        {--dry-run : Chi liet ke, khong nap khong doi gi. KHONG duoc ket hop voi --lien-tuc}
        {--khong-ky : Nap va kiem, dung truoc buoc ky}
        {--khong-gui : Dung CA chuoi ky-gui: khong ky va khong gui ho so nao. Van nap va kiem}
        {--lien-tuc : Chay nen lien tuc giong xml3176import:day, tu thoat sau --so-vong vong}
        {--nghi=5 : So giay nghi giua hai vong khi --lien-tuc}
        {--so-vong=1000 : Tran so vong khi --lien-tuc, lenh tu thoat sau khi chay du}
        {--go-khoa : CAP CUU: go khoa luot vo dieu kien. CHI dung khi CHAC CHAN khong con tien trinh ctdt:import nao dang chay - kiem truoc bang tasklist hoac dich vu nssm, roi thoat NGAY, khong quet/nap/xep hang gi ca}';

    protected $description = 'Quet thu muc inbox, nap chung tu dien tu PL02';

    const THU_MUC_DA_NAP = 'da-nap';
    const THU_MUC_LOI = 'loi';
    const KHOA_LUOT = 'ctdt:import:dang-chay';
    const KHOA_LUOT_PHUT = 1440;
    const TRAN_NHAT = 200;
    const TEP_CO_DUNG = 'DUNG-GUI';

    /** @var CtdtImporter */
    protected $importer;

    public function __construct(CtdtImporter $importer = null)
    {
        parent::__construct();

        $this->importer = $importer ?: new CtdtImporter();
    }

    public function handle()
    {
        if ($this->option('go-khoa')) {
            Cache::forget(self::KHOA_LUOT);
            $this->warn('CAP CUU: da go khoa ' . self::KHOA_LUOT . ' VO DIEU KIEN.');
            $this->warn('Neu mot tien trinh --lien-tuc THAT SU dang chay va ban vua go NHAM'
                . ' khoa cua no, mot lenh ctdt:import khac chay tiep theo se quet CUNG thu'
                . ' muc dong thoi - nap trung roi GUI TRUNG ho so len cong BHXH.');
            $this->warn('Kiem TRUOC khi chay lenh ke tiep: tasklist /FI "IMAGENAME eq'
                . ' php.exe" /V (tim dong lenh co ctdt:import --lien-tuc), hoac xem dich vu'
                . ' nssm "QLBV CtdtImport" con dang chay khong. Chi tiep tuc khi da xac nhan'
                . ' KHONG con tien trinh ctdt:import nao con song.');
            $this->info('Khong quet/nap/xep hang gi ca trong lan chay nay.');

            return 0;
        }

        $thuMuc = $this->option('duong-dan')
            ?: config('filesystems.disks.importCtdt.root');

        if (empty($thuMuc)) {
            $this->error('Chua cau hinh filesystems.disks.importCtdt.root'
                . ' (dat CTDT_IMPORT_PATH trong .env).');

            return 1;
        }

        if (!is_dir($thuMuc)) {
            if (!@mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
                $this->error('Khong tao duoc thu muc: ' . $thuMuc);
                $this->line('Kiem quyen ghi, va kiem o dia co ton tai khong. Doi duong dan'
                    . ' bang CTDT_IMPORT_PATH trong .env.');

                return 1;
            }

            $this->info('Da tao thu muc quet: ' . $thuMuc);
        }

        try {
            Storage::disk('exportCtdt');
        } catch (\Exception $e) {
            $this->warn('Khong mo duoc disk exportCtdt: ' . $e->getMessage());
        }

        $khoDe = (bool) $this->option('dry-run');

        if (!$khoDe && !Cache::add(self::KHOA_LUOT, true, self::KHOA_LUOT_PHUT)) {
            $this->warn('Mot luot ctdt:import khac dang chay. Bo qua luot nay.');

            return 0;
        }

        try {
            if ($this->option('lien-tuc')) {
                if ($khoDe) {
                    $this->error('Khong ket hop --dry-run voi --lien-tuc duoc.');

                    return 1;
                }

                return $this->vongLap($thuMuc, $this->gioiHanHieuLuc());
            }

            return $this->quet($thuMuc, $this->gioiHanHieuLuc(), $khoDe);
        } finally {
            if (!$khoDe) {
                Cache::forget(self::KHOA_LUOT);
            }
        }
    }

    /**
     * @param  string  $thuMuc
     * @param  int  $gioiHan
     * @param  bool  $khoDe
     * @return int
     */
    protected function quet($thuMuc, $gioiHan, $khoDe)
    {
        $tep = $this->dsTep($thuMuc);

        if (empty($tep)) {
            $this->info('Khong co tep nao trong ' . $thuMuc);

            return 0;
        }

        if (count($tep) > $gioiHan) {
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
                $soKhongDoiDuoc++;
            }
        }

        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        if (!$khoDe && !$this->option('khong-ky')) {
            $this->nhatVaXepHang($thuMuc);
        }

        if ($soKhongDoiDuoc > 0) {
            $this->error($soKhongDoiDuoc . ' tep DA XU LY nhung KHONG DOI duoc khoi thu muc'
                . ' goc - luot quet sau se gap lai chung. Kiem tra quyen ghi / tep bi khoa,'
                . ' roi doi tay hoac chay lai.');

            return 1;
        }

        return 0;
    }

    /**
     * @param  string  $thuMuc
     * @param  int  $gioiHan
     * @return int
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
                    $soVongHong++;
                }
            } catch (\Exception $e) {
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
     * @param  string  $duongDan
     * @param  string  $thuMuc
     * @return array
     */
    protected function napMotTep($duongDan, $thuMuc)
    {
        try {
            $kq = $this->importer->nhapTuTep($duongDan);
        } catch (\Exception $e) {
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
            $this->warn(basename($duongDan) . ': ghi de ' . count($kq->dsGhiDeDaGui)
                . ' ho so DA TUNG GUI - ' . implode(', ', $kq->dsGhiDeDaGui));
            Log::warning('ctdt:import ghi de ho so da gui: ' . implode(', ', $kq->dsGhiDeDaGui));
        }

        $this->line(basename($duongDan) . ': ' . $kq->soThanhCong . ' ho so, '
            . $kq->soThatBai . ' hong');

        $daChuyen = $this->chuyen($duongDan, $thuMuc, self::THU_MUC_DA_NAP);

        if (!$daChuyen) {
            $this->error(basename($duongDan) . ': DA NAP THANH CONG vao CSDL nhung KHONG'
                . ' DOI duoc khoi thu muc goc. Luot quet sau se nap lai. Kiem tra quyen ghi'
                . ' / tep bi khoa boi chuong trinh khac.');
            Log::error('ctdt:import da nap ' . $duongDan . ' nhung khong doi duoc khoi thu'
                . ' muc goc - nguy co nap trung o luot sau');
        }

        return ['ma_ho_so' => $kq->dsMaHoSo, 'da_chuyen' => $daChuyen];
    }

    /**
     * @param  string  $thuMuc
     * @return array
     */
    protected function dsTep($thuMuc)
    {
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
     * @param  string  $duongDan
     * @param  string  $thuMuc
     * @param  string  $thuMucCon
     * @return bool
     */
    protected function chuyen($duongDan, $thuMuc, $thuMucCon)
    {
        $dich = rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . $thuMucCon;

        if (!is_dir($dich) && !@mkdir($dich, 0775, true) && !is_dir($dich)) {
            Log::error('ctdt:import khong tao duoc thu muc ' . $dich);

            return false;
        }

        $tepDich = $dich . DIRECTORY_SEPARATOR . basename($duongDan);

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
     * @param  string  $thuMuc
     * @return int
     */
    protected function nhatVaXepHang($thuMuc)
    {
        if (!$this->duocGui($thuMuc)) {
            return 0;
        }

        $ungVien = CtdtDanhSach::chuaCoKetQua(
                CtdtHoSo::whereNotNull('checked_at')->where('so_loi', '=', 0)
            )
            ->where(function ($q) {
                $q->whereNull('submit_error')->orWhere('submit_error', '');
            })
            ->where(function ($q) {
                $q->whereNull('signed_error')->orWhere('signed_error', '');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ctdt_lich_su_gui')
                    ->whereRaw('ctdt_lich_su_gui.ma_ho_so = ctdt_ho_so.ma_ho_so');
            })
            ->orderBy('imported_at')
            ->limit(self::TRAN_NHAT)
            ->get();

        if ($ungVien->isEmpty()) {
            return 0;
        }

        $soXep = 0;
        $soBoQua = 0;

        foreach ($ungVien as $hoSo) {
            if (CtdtQuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi) !== CtdtQuyetDinhGui::KY) {
                $soBoQua++;
                continue;
            }

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
     * @param  string  $thuMuc
     * @return bool
     */
    protected function duocGui($thuMuc)
    {
        if ($this->coDung($thuMuc)) {
            $this->warn('Thay tep ' . self::TEP_CO_DUNG . ' trong thu muc inbox - DUNG GUI. '
                . 'Van tiep tuc nap va kiem. Xoa tep do di de gui lai.');

            return false;
        }

        if ($this->option('khong-gui')) {
            return false;
        }

        if (!(bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui')) {
            return false;
        }

        if (!(bool) config('organization.chung_tu_dien_tu.submit_enabled')) {
            $this->warn('submit_enabled dang TAT - khong xep hang ky/gui ho so nao. Bat khoa'
                . ' organization.chung_tu_dien_tu.submit_enabled neu that su muon gui len cong.');

            return false;
        }

        return true;
    }

    /**
     * @param  string  $thuMuc
     * @return bool
     */
    public function coDung($thuMuc)
    {
        return file_exists(rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . self::TEP_CO_DUNG);
    }
}
