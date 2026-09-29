<?php

namespace App\Services\Xml3176;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Jobs\ExportXml3176Job;
use App\Jobs\SignXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Models\BHYT\Xml3176Information;

/**
 * Dung chuoi kiem -> xuat -> ky -> gui cho MOT ho so XML3176 (khuon CtdtXepHangKyGui).
 *
 * [JobXml3176]        Kiem XML1 -> Kiem XML2 -> ... -> Kiem tong the
 * [JobExportXml3176]  -> Xuat
 * [JobSignXml3176]    -> Ky
 * [JobSubmitXml3176]  -> Gui cong
 *
 * VI SAO MOT CHUOI (withChain) CHU KHONG DISPATCH ROI RAC: truoc 29/09/2026 buoc xuat chay
 * song song voi buoc kiem va phai cho theo thoi gian; hang doi kiem ton 90 phut lam 1.934 ho
 * so sach khong len cong. Chuoi con cho hai bao dam ma dispatch roi rac khong co:
 *  - THU TU do framework giu, khong dua vao viec moi hang doi chi co mot worker;
 *  - HONG THI DONG: mot job kiem nem het luot thi phan sau khong chay - khong co chuyen
 *    "kiem do dang ma van xuat".
 *
 * Moi lan dung chuoi deu mang MA PHIEN (xem ThuocChuoiXml3176). Luc nap, ma phai duoc ghi
 * TRONG transaction nap (Xml3176Importer) - ghi sau commit thi co mot khoanh khac du lieu
 * moi da hien ra ma ma cu van con hieu luc.
 */
class Xml3176ChuoiXuLy
{
    /** Laravel 5.5 khong co Str::uuid(). */
    public static function sinhMa()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Goi tu bo nap, SAU commit. Ma phien da duoc bo nap ghi trong transaction.
     *
     * @param string $maLk
     * @param string $maPhien
     * @param array  $loaiDaNap cac LOAIHOSO da nap (co the trung, co the co loai khong co checker)
     * @param bool   $choPhepXuat
     */
    public static function xepSauNap($maLk, $maPhien, array $loaiDaNap, $choPhepXuat)
    {
        $hangDoiKiem = config('xml3176.queue_name');
        $jobs = [];

        foreach (array_values(array_unique($loaiDaNap)) as $loai) {
            if (Xml3176CheckTypes::coChecker($loai)) {
                $jobs[] = (new CheckXml3176TypeJob($maLk, $loai, $maPhien))->onQueue($hangDoiKiem);
            }
        }

        // LUON co mat, ke ca khi xml_3176_not_check bat - job la moc "kiem xong" cua chuoi.
        $jobs[] = (new CheckCompleteXml3176RecordJob($maLk, $maPhien))->onQueue($hangDoiKiem);

        if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) {
            $jobs = array_merge($jobs, self::buocTuXuat($maLk, $maPhien));
        }

        self::day($jobs);
    }

    /**
     * Goi tu lenh xml3176:chay-lai-tu-xuat: day lai xuat -> ky -> gui, khong kiem lai.
     *
     * Sinh ma MOI va ghi len ho so TRUOC khi day: chuoi cu con song cua ho so do se tu thoi.
     * Thieu buoc nay, hai chuoi cung ma co the gui trung len cong.
     *
     * @return string ma phien moi
     */
    public static function xepTuXuat($maLk)
    {
        $maPhien = self::sinhMa();

        Xml3176Information::where('ma_lk', $maLk)->update(['chain_token' => $maPhien]);

        self::day(self::buocTuXuat($maLk, $maPhien));

        return $maPhien;
    }

    private static function buocTuXuat($maLk, $maPhien)
    {
        return [
            (new ExportXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.export_queue_name')),
            (new SignXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.sign_queue_name')),
            (new SubmitXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.submit_queue_name')),
        ];
    }

    /** Moi job da tu khai hang doi cua minh truoc khi serialize vao chuoi. */
    private static function day(array $jobs)
    {
        $dau = array_shift($jobs);

        dispatch($dau->chain($jobs));
    }
}
