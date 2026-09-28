<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;

use App\Services\Xml3176Service;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Information;

class ExportXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** So lan toi da hoan lai de cho kiem loi xong (10 x 15s = 2,5 phut). */
    const SO_LAN_CHO_TOI_DA = 10;

    /** Giay cho moi lan hoan lai. */
    const GIAY_CHO_MOI_LAN = 15;

    protected $ma_lk;

    /** So lan job nay da hoan lai de cho kiem loi xong. */
    protected $soLanCho;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ma_lk, $soLanCho = 0)
    {
        $this->ma_lk = $ma_lk;
        $this->soLanCho = $soLanCho;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Xml3176Service $xmlService)
    {
        // Kiểm tra nếu không có lỗi nghiêm trọng trước khi xuất XML
        $hasCriticalError = Xml3176ErrorResult::where('ma_lk', $this->ma_lk)
            ->where('critical_error', true)
            ->exists();

        // Lấy hồ sơ XML theo ma_lk
        $xmlRecord = Xml3176Xml1::where('ma_lk', $this->ma_lk)->first();

        // Kiểm tra nếu hồ sơ tồn tại và ngay_ra hợp lệ (đúng độ dài format YmdHi)
        if ($xmlRecord && $xmlRecord->ngay_ra && strlen($xmlRecord->ngay_ra) == 12) {
            try {
                if (Carbon::createFromFormat('YmdHi', $xmlRecord->ngay_ra)->gt(Carbon::now())) {
                    // Nếu ngay_ra lớn hơn thời điểm hiện tại, không xuất hồ sơ XML
                    return;
                }
            } catch (\Exception $e) {
                \Log::warning('Invalid date format for ngay_ra: ' . $xmlRecord->ngay_ra);
            }
        }

        // CHO kiem loi xong moi duoc hoi "co loi nghiem trong khong".
        //
        // Buoc kiem loi chay tren hang doi JobXml3176, buoc xuat chay tren
        // JobExportXml3176: hai worker chay song song va worker xuat thuong
        // thang cuoc dua. Do ngay 28/09/2026: ca 50 ho so xuat luc 11:17:38,
        // dong loi nghiem trong dau tien mai 11:17:39 - 11:17:42 moi duoc ghi,
        // nen $hasCriticalError o tren la false cho MOI ho so, ca 50 deu duoc
        // xuat, ky so va gui cong du dang bat kiem loi.
        if ($this->phaiChoKiemLoi()) {
            if ($this->soLanCho < self::SO_LAN_CHO_TOI_DA) {
                static::dispatch($this->ma_lk, $this->soLanCho + 1)
                    ->onQueue(config('xml3176.export_queue_name'))
                    ->delay(Carbon::now()->addSeconds(self::GIAY_CHO_MOI_LAN));
                return;
            }

            // Het luot cho: chon huong AN TOAN la KHONG xuat. Yeu cau van hanh la
            // moi ho so phai duoc kiem truoc khi len cong BHXH, nen tha khong gui
            // con hon gui mot ho so chua ai kiem. Nguoi van hanh nap lai ho so la
            // job kiem chay lai.
            $giay = self::SO_LAN_CHO_TOI_DA * self::GIAY_CHO_MOI_LAN;
            Xml3176Information::where('ma_lk', $this->ma_lk)->update([
                'export_error' => 'Không xuất: chờ ' . $giay . ' giây mà bước kiểm lỗi chưa xong.',
            ]);
            \Log::warning('ExportXml3176Job: het luot cho kiem loi, khong xuat ma_lk ' . $this->ma_lk);
            return;
        }

        if (config('organization.export_xml_not_check')) {
            $xmlService->processExportXml($this->ma_lk);
        } else {
            if (!$hasCriticalError) {
                $xmlService->processExportXml($this->ma_lk);
            }
        }
    }

    /**
     * Da bat kiem loi ma ho so nay chua kiem xong?
     *
     * Chi cho khi buoc kiem tong the THUC SU chay: neu co so tat
     * xml_3176_not_check thi khong co ai dat checked_at ca, cho la treo vinh vien.
     */
    private function phaiChoKiemLoi()
    {
        if (config('organization.export_xml_not_check')) {
            return false;
        }

        if (config('organization.xml_3176_not_check', false)) {
            return false;
        }

        return !Xml3176Information::where('ma_lk', $this->ma_lk)
            ->whereNotNull('checked_at')
            ->exists();
    }
}
