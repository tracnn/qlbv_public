<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176Xml1;
use App\Services\BHYT\Xml3176LocDanhSach;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class Xml3176ErrorExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping, WithTitle
{
    /** @var array bo loc doc tu man danh sach (Xml3176LocDanhSach::tuRequest()) */
    protected $loc;
    /** @var array ma co so => nhan */
    protected $danhSachCoSo;
    protected $rowNumber = 0;

    public function __construct(array $loc, array $danhSachCoSo = [])
    {
        $this->loc = $loc;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    /**
     * Xuat MOI dong loi cua DUNG tap ho so ma man danh sach dang hien thi.
     *
     * Quy tac da chot: "xuat ra dung cai nhin thay". Tap ho so cat theo Xml3176LocDanhSach
     * (tron ven 15 bo loc), con trong tung ho so thi lay het cac dong loi - KHONG cat
     * them o muc dong. Vi du loc "ho so co loi nghiem trong": file chua moi dong loi cua
     * nhung ho so do, ke ca dong muc canh bao, dung nhu khi mo tung ho so tren man hinh.
     *
     * Ban cu tu dung truy van va chi hieu xml_filter_status o HAI trong bay gia tri
     * (has_error_critical, has_error_warning); nam gia tri con lai - has_error, no_error,
     * has_error_hein_card, has_error_hein_card_without_xml, no_error_critical - bi bo qua
     * im lang. Sau bo loc khac thi phia JavaScript khong gui.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        set_time_limit(1800);
        ini_set('memory_limit', '4096M');

        return Xml3176Xml1::whereIn('xml3176_xml1s.ma_lk',
                Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo))
            ->join('xml3176_error_results', 'xml3176_error_results.ma_lk', '=', 'xml3176_xml1s.ma_lk')
            ->join('xml3176_error_catalogs', 'xml3176_error_results.error_code', '=', 'xml3176_error_catalogs.error_code')
            ->join('xml3176_informations', 'xml3176_informations.ma_lk', '=', 'xml3176_xml1s.ma_lk')
            ->select('xml3176_error_results.*', 'xml3176_error_catalogs.error_name as catalog_error_name',
                'xml3176_xml1s.ngay_vao', 'xml3176_xml1s.ngay_ra', 'xml3176_xml1s.ma_bn', 'xml3176_xml1s.ho_ten',
                'xml3176_xml1s.ngay_sinh', 'xml3176_xml1s.ma_the_bhyt', 'xml3176_xml1s.ngay_ttoan',
                'xml3176_informations.imported_by' , 'xml3176_informations.exported_by')
            ->orderBy('xml3176_error_results.ma_lk')
            ->orderBy('xml3176_error_results.xml')
            ->orderBy('xml3176_error_results.stt');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại XML',
            'STT XML',
            'Mã Liên Kết',
            'Mã Bệnh Nhân',
            'Họ Và Tên',
            'Ngày Sinh',
            'Mã Thẻ BHYT',
            'Ngày Vào',
            'Ngày Ra',
            'Ngày T.Toán',
            'Ngày Y Lệnh',
            'Ngày Kết Quả',
            'Mã Lỗi',
            'Mô Tả',
            'Loại lỗi',
            'Imported by',
            'Exported by'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(10);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(13);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(13);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(13);
                $sheet->getColumnDimension('J')->setWidth(13);
                $sheet->getColumnDimension('K')->setWidth(13);
                $sheet->getColumnDimension('l')->setWidth(13);
                $sheet->getColumnDimension('M')->setWidth(13);
                $sheet->getColumnDimension('N')->setWidth(30);
                $sheet->getColumnDimension('O')->setWidth(50);
                $sheet->getColumnDimension('P')->setWidth(13);
                $sheet->getColumnDimension('Q')->setWidth(12);
                $sheet->getColumnDimension('R')->setWidth(12);

                $sheet->getStyle('G')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('I')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('J')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('K')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('L')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
                $sheet->getStyle('M')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);
        return [
            // Căn giữa tiêu đề
            1    => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ],
                'font' => [
                    'bold' => true, // In đậm tiêu đề
                ],
            ],
        ];
    }

    public function map($data): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $data->xml,
            $data->stt,
            $data->ma_lk,
            $data->ma_bn,
            $data->ho_ten,
            $data->ngay_sinh,
            $data->ma_the_bhyt,
            $data->ngay_vao,
            $data->ngay_ra,
            $data->ngay_ttoan,
            $data->ngay_yl,
            $data->ngay_kq,
            $data->catalog_error_name,
            $data->description,
            $data->critical_error ? 'Nghiêm trọng' : 'Cảnh báo',
            $data->imported_by,
            $data->exported_by,
        ];
    }

    public function title(): string
    {
        return 'Lỗi XML'; // Tên cho sheet này
    }
}
