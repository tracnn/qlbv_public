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
        {--gioi-han=200 : Tran so TEP xu ly moi luot}
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
            return $this->quet($thuMuc, (int) $this->option('gioi-han'), $khoDe);
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

        foreach ($tep as $duongDan) {
            if ($khoDe) {
                $this->line('[dry-run] se nap: ' . $duongDan);
                continue;
            }

            $kq = $this->napMotTep($duongDan, $thuMuc);

            if ($kq === null) {
                $soHong++;
                continue;
            }

            $dsMaHoSo = array_merge($dsMaHoSo, $kq);
        }

        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        return 0;
    }

    /**
     * Nap mot tep, chuyen no sang da-nap/ hoac loi/.
     *
     * @return array|null danh sach ma ho so, hoac null khi tep hong
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
            $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return null;
        }

        if (!$kq->thanhCong) {
            Log::error('ctdt:import nap that bai ' . $duongDan . ': ' . $kq->lyDoThatBai);
            $this->error(basename($duongDan) . ': ' . $kq->lyDoThatBai);
            $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return null;
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

        $this->chuyen($duongDan, $thuMuc, self::THU_MUC_DA_NAP);

        return $kq->dsMaHoSo;
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

    /** Chuyen tep sang thu muc con, tao thu muc neu chua co */
    protected function chuyen($duongDan, $thuMuc, $thuMucCon)
    {
        $dich = rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . $thuMucCon;

        if (!is_dir($dich) && !@mkdir($dich, 0775, true) && !is_dir($dich)) {
            Log::error('ctdt:import khong tao duoc thu muc ' . $dich);

            return;
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
        }
    }
}
