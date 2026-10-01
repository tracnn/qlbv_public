<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176\Xml3176ChuoiXuLy;
use Illuminate\Console\Command;

/**
 * Day lai chuoi xuat -> ky -> gui cho ho so DA KIEM XONG ma chua xuat. Khong kiem lai.
 *
 * Ra doi ngay 29/09/2026: co che cho theo thoi gian cu lam 5.043 ho so het luot cho, 1.934
 * ho so sach khong len cong. Dung lai duoc sau moi su co lam chuoi dut (HSM hong, CSDL mat).
 *
 * TIEU CHI MAC DINH: checked_at co gia tri va exported_at rong. Tu loai ho so nap truoc
 * 28/09 (cot checked_at sinh ngay do) va ho so dang kiem do (nap lai dat checked_at = null).
 *
 * KHONG chon dai tra "da xuat ma chua ky": o co so khong bat ky so, moi ho so deu thuoc
 * nhom do - chay lai hang loat se COPY TRUNG sang Truc du lieu / Dien Bien. Can ky lai thi
 * chi dinh --ma-lk.
 *
 * GIOI HAN: ho so nap qua duong khong cho phep xuat (cho_phep_xuat = false, thu muc xml3176tt khi
 * exportable_tt = false) van co checked_at va exported_at rong nen bi tieu chi mac dinh chon -
 * lenh khong phan biet duoc. O co so co duong nap do thi KHONG chay chon mac dinh, dung --ma-lk.
 * Khi xml3176.export_xml3176_enabled tat, lenh tu choi chay.
 *
 * Moi ho so duoc day nhan MA PHIEN MOI: chuoi cu con song cua no tu thoi, khong gui trung.
 */
class Xml3176ChayLaiTuXuat extends Command
{
    protected $signature = 'xml3176:chay-lai-tu-xuat
        {--ma-lk=* : Chi dinh tung ho so, bo qua tieu chi mac dinh}
        {--thuc-hien : Day chuoi that; thieu co nay thi chi dem}';

    protected $description = 'Day lai chuoi xuat -> ky -> gui cho ho so XML3176 da kiem xong ma chua xuat';

    public function handle()
    {
        if (!config('xml3176.export_xml3176_enabled')) {
            $this->error('Tự động xuất XML3176 đang tắt (xml3176.export_xml3176_enabled) — không đẩy chuỗi.');
            return 1;
        }

        $chiDinh = (array) $this->option('ma-lk');

        $q = Xml3176Information::query();

        if (!empty($chiDinh)) {
            $q->whereIn('ma_lk', $chiDinh);
        } else {
            $q->whereNotNull('checked_at')->whereNull('exported_at');
        }

        $danhSach = $q->orderBy('id')->pluck('ma_lk')->all();

        if (!empty($chiDinh)) {
            $khongThay = array_values(array_diff($chiDinh, $danhSach));
            if (!empty($khongThay)) {
                $this->warn('Không tìm thấy trong xml3176_informations: ' . implode(', ', $khongThay));
            }
        }

        $coLoi = 0;
        foreach (array_chunk($danhSach, 1000) as $lo) {
            $coLoi += Xml3176ErrorResult::whereIn('ma_lk', $lo)
                ->where('critical_error', true)
                ->distinct()
                ->count('ma_lk');
        }

        $tong = count($danhSach);
        $this->info($tong . ' hồ sơ: ' . ($tong - $coLoi) . ' sạch, ' . $coLoi . ' có lỗi xuất toán'
            . ' (bước xuất sẽ chặn lại nếu export_xml_not_check tắt).');

        if (!$this->option('thuc-hien')) {
            $this->info('Chỉ đếm. Chạy lại với --thuc-hien để đẩy chuỗi thật.');
            return 0;
        }

        foreach ($danhSach as $maLk) {
            Xml3176ChuoiXuLy::xepTuXuat($maLk);
        }

        $this->info('Đã đẩy ' . $tong . ' chuỗi xuất → ký → gửi.');

        return 0;
    }
}
