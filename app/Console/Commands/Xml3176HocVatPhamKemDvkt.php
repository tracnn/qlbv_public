<?php

namespace App\Console\Commands;

use App\Exports\VatPhamKemDvktExport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Hoc bang tra vat pham (VTYT/thuoc) -> DVKT me tu dong XML3176 da duoc HIS gan ma_dich_vu.
 * Chay lai bat cu luc nao: dong TAY va trang thai tat cua nguoi dung duoc giu nguyen.
 */
class Xml3176HocVatPhamKemDvkt extends Command
{
    protected $signature = 'xml3176:hoc-vat-pham-kem-dvkt
        {--nguong= : Ty le gan toi thieu (0-1), mac dinh config xml3176.vat_pham_kem_dvkt.nguong}
        {--toi-thieu= : So dong BHYT tra toi thieu cua vat pham}
        {--tu-ngay= : Chi xet ngay_yl tu (yyyymmdd)}
        {--thu : Chi bao cao, khong ghi bang}
        {--xuat= : Ten tep .xlsx trong storage/app de xuat bang sau khi hoc}';

    protected $description = 'Học bảng tra VTYT/thuốc thanh toán ngoài giá -> DVKT mẹ từ dữ liệu XML3176 đã liên kết';

    public function handle(VatPhamKemDvktLearner $learner)
    {
        $nguong = (float) ($this->option('nguong') ?? config('xml3176.vat_pham_kem_dvkt.nguong', 0.8));
        $toiThieu = (int) ($this->option('toi-thieu') ?? config('xml3176.vat_pham_kem_dvkt.toi_thieu', 5));
        if ($nguong <= 0 || $nguong > 1 || $toiThieu < 1) {
            $this->error('Ngưỡng phải trong (0, 1] và tối thiểu >= 1.');

            return 1;
        }

        $kq = $learner->hoc($nguong, $toiThieu, $this->option('tu-ngay') ?: null);
        foreach ($kq['so_vat_pham'] as $loai => $soVp) {
            $soCap = count(array_filter($kq['dong'], function ($d) use ($loai) { return $d['loai'] === $loai; }));
            $this->info("{$loai}: {$soVp} vật phẩm, {$soCap} cặp");
        }

        if ($this->option('thu')) {
            $this->warn('Chế độ thử: không ghi bảng.');
            $dongXuat = $kq['dong'];
        } else {
            $g = $learner->ghi($kq['dong']);
            $this->info("Ghi: thêm {$g['them']}, cập nhật {$g['cap_nhat']}, tắt {$g['tat']}, bật lại {$g['bat_lai']}");
            $dongXuat = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();
        }

        if ($tep = $this->option('xuat')) {
            Excel::store(new VatPhamKemDvktExport($dongXuat), $tep, 'local');
            $this->info('Đã xuất: ' . storage_path('app/' . $tep));
        }

        return 0;
    }
}
