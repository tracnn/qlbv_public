<?php

namespace App\Console\Commands;

use App\Exports\VatPhamKemDvktExport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
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

        if ($this->option('thu')) {
            $kq = $learner->hoc($nguong, $toiThieu, $this->option('tu-ngay') ?: null,
                (float) config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien', 0.5));
            foreach ($kq['so_vat_pham'] as $loai => $soVp) {
                $soCap = count(array_filter($kq['dong'], function ($d) use ($loai) { return $d['loai'] === $loai; }));
                $this->info("{$loai}: {$soVp} vật phẩm, {$soCap} cặp");
            }
            $this->info('Ứng viên: ' . count($kq['ung_vien']));
            $this->warn('Chế độ thử: không ghi bảng.');
            if ($this->option('xuat')) {
                $this->warn('Cảnh báo: tệp xuất ở chế độ thử có cột nguon rỗng; nạp lại tệp này sẽ biến toàn bộ dòng thành TAY.');
            }
            $dongXuat = $kq['dong'];
        } else {
            // Di qua service de lich su luot hoc va bang ung vien nhat quan voi man quan tri.
            $lan = Xml3176VpkdLanHoc::create(['user_id' => null, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);
            $k = app(VatPhamKemDvktHocLaiService::class)->thucHien($lan, $nguong, $toiThieu, $this->option('tu-ngay') ?: null);
            foreach ($k['so_vat_pham'] as $loai => $soVp) {
                $this->info("{$loai}: {$soVp} vật phẩm, {$k['so_cap'][$loai]} cặp");
            }
            $this->info("Ghi: thêm {$k['them']}, cập nhật {$k['cap_nhat']}, tắt {$k['tat']}, bật lại {$k['bat_lai']}");
            $this->info("Ứng viên: {$k['so_ung_vien']}");
            $dongXuat = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();
        }

        if ($tep = $this->option('xuat')) {
            Excel::store(new VatPhamKemDvktExport($dongXuat), $tep, 'local');
            $this->info('Đã xuất: ' . storage_path('app/' . $tep));
        }

        return 0;
    }
}
