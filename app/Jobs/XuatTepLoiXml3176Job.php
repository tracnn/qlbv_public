<?php

namespace App\Jobs;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Xml3176\Xml3176TepXuatService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Tao tep "danh sach loi" XML3176 chay nen cho mot yeu cau trong xml3176_tep_xuat.
 *
 * Chay tren ket noi 'xuat_tep' (retry_after 3600), hang doi JobXuatTepXml3176, dich vu NSSM
 * rieng - mot lan xuat ngay lon ton 12-30 phut va ~2,5 GB, khong duoc chan chuoi
 * kiem-xuat-ky-gui.
 *
 * KHONG khai $timeout: PHP Windows khong co pcntl nen $timeout khong duoc thi hanh. Treo thi
 * Xml3176TepXuatService::danhDauTreo() chuyen loi sau 90 phut.
 */
class XuatTepLoiXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, KHONG tu chay lai mot viec ton 12-30 phut. */
    public $tries = 1;

    /** @var int id dong xml3176_tep_xuat - nhan id chu khong nhan model: job co the cho lau */
    public $yeuCauId;

    public function __construct($yeuCauId)
    {
        $this->yeuCauId = $yeuCauId;
    }

    public function handle()
    {
        $y = Xml3176TepXuat::find($this->yeuCauId);

        // Da bi don, hoac da duoc xu ly boi mot lan chay khac: khong lam gi.
        if ($y === null || $y->trang_thai !== Xml3176TepXuat::CHO) {
            return;
        }

        $y->update(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()]);

        // Ngay 29/09/2026 (204.617 dong loi): ~700 giay, bo nho dinh ~2,5 GB.
        set_time_limit(0);
        ini_set('memory_limit', '4096M');

        $duongDan = Xml3176TepXuatService::duongDanTep($y);

        try {
            $daGhi = Excel::store(
                new Xml3176ErrorMultiSheetExport((array) $y->bo_loc, DanhSachCoSo::danhSach()),
                $duongDan,
                'local'
            );

            // Excel::store tra false khi khong chep duoc tep vao disk: khong danh dau xong.
            if ($daGhi === false) {
                throw new \RuntimeException('Không ghi được tệp xuất ra đĩa');
            }
        } finally {
            // Hai sheet danh muc dat StringValueBinder vao bien TINH (vendor/maatwebsite/excel/
            // src/Sheet.php) va khong tra lai. Worker nay chay nhieu lan xuat noi tiep: khong
            // tra lai thi cac lan sau ghi moi o thanh chuoi.
            Cell::setValueBinder(new DefaultValueBinder());
        }

        $disk = Storage::disk('local');

        $y->update([
            'trang_thai' => Xml3176TepXuat::XONG,
            'duong_dan' => $duongDan,
            'kich_thuoc' => $disk->exists($duongDan) ? $disk->size($duongDan) : null,
            'xong_luc' => Carbon::now(),
            'loi' => null,
        ]);
    }

    public function failed(\Throwable $e)
    {
        Log::error('XuatTepLoiXml3176Job that bai: ' . $e->getMessage(), ['yeu_cau' => $this->yeuCauId]);

        $y = Xml3176TepXuat::find($this->yeuCauId);

        if ($y === null) {
            return;
        }

        Storage::disk('local')->delete(Xml3176TepXuatService::duongDanTep($y));

        // Noi dung ngoai le (SQL, cau tieng Anh cua queue) da vao nhat ky o tren, khong lo cho nguoi dung.
        $y->update([
            'trang_thai' => Xml3176TepXuat::LOI,
            'loi' => $e instanceof MaxAttemptsExceededException
                ? 'Dịch vụ xuất đã dừng giữa chừng (có thể thiếu bộ nhớ hoặc vừa cập nhật phần mềm). Bấm tạo lại.'
                : 'Tạo tệp lỗi, xem nhật ký máy chủ. Bấm tạo lại.',
        ]);
    }
}
