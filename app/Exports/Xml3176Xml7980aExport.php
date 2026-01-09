<?php

namespace App\Exports;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Xml3176Xml7980aExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $request;
    protected $rowNumber = 0;
    protected $xml_submit_status;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->xml_submit_status = $xml_submit_status;
    }

    public function query()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        $date_from = $this->request->input('date_from');
        $date_to = $this->request->input('date_to');

        $xml_filter_status = $this->request->input('xml_filter_status');
        $date_type = $this->request->input('date_type');
        $xml3176_xml_error_catalog_id = $this->request->input('xml3176_xml_error_catalog');
        $payment_date_filter = $this->request->input('payment_date_filter');
        $xml_export_status = $this->request->input('xml_export_status');
        $xml_submit_status = $this->request->input('xml_submit_status');
        $treatmentCode = $this->request->input('treatment_code');

        // Nếu có truyền treatment_code, chỉ lấy hồ sơ có ma_lk bằng treatmentCode và bỏ qua tất cả các điều kiện khác
        if ($treatmentCode) {
            return Xml3176Xml1::selectRaw("
                xml3176_xml1s.ma_lk,
                xml3176_xml1s.ma_bn, 
                xml3176_xml1s.ho_ten, 
                CASE 
                    WHEN SUBSTRING(xml3176_xml1s.ngay_sinh, 5, 2) <> '00' AND SUBSTRING(xml3176_xml1s.ngay_sinh, 7, 2) <> '00' 
                    THEN LEFT(xml3176_xml1s.ngay_sinh, 8)
                    ELSE LEFT(xml3176_xml1s.ngay_sinh, 4)
                END AS ngay_sinh,
                xml3176_xml1s.gioi_tinh, 
                xml3176_xml1s.dia_chi, 
                SUBSTRING_INDEX(xml3176_xml1s.ma_the_bhyt, ';', 1) AS ma_the, 
                SUBSTRING_INDEX(xml3176_xml1s.ma_dkbd, ';', 1) AS ma_dkbd,   
                SUBSTRING_INDEX(xml3176_xml1s.gt_the_tu, ';', 1) AS gt_the_tu, 
                SUBSTRING_INDEX(xml3176_xml1s.gt_the_den, ';', 1) AS gt_the_den, 
                xml3176_xml1s.ma_benh_chinh AS ma_benh, 
                xml3176_xml1s.ma_benh_kt AS ma_benhkhac,
                LEFT(xml3176_xml1s.ma_doituong_kcb, 1) AS ma_lydo_vvien, 
                xml3176_xml1s.ma_noi_di AS ma_noi_chuyen, 
                xml3176_xml1s.ngay_vao, 
                xml3176_xml1s.ngay_ra,
                xml3176_xml1s.so_ngay_dtri, 
                xml3176_xml1s.ket_qua_dtri, 
                xml3176_xml1s.ma_loai_rv AS tinh_trang_rv,
                xml3176_xml1s.t_tongchi_bh AS t_tongchi,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom = 1 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_xn,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom = 2 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_cdha,
                xml3176_xml1s.t_thuoc AS t_thuoc,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom = 7 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_mau,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (8,18) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_pttt,
                xml3176_xml1s.t_vtyt AS t_vtyt,
                NULL AS t_dvkt_tyle,
                NULL AS t_thuoc_tyle,
                NULL AS t_vtyt_tyle,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom = 13 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_kham,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (14,15,16) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_giuong,
                SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (12) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_vchuyen,
                (COALESCE(xml3176_xml1s.t_bntt, 0) + COALESCE(xml3176_xml1s.t_bncct, 0)) AS t_bntt,
                xml3176_xml1s.t_bhtt, 
                NULL AS t_ngoaids,
                xml3176_xml1s.ma_khoa, 
                xml3176_xml1s.nam_qt, 
                xml3176_xml1s.thang_qt, 
                xml3176_xml1s.ma_khuvuc, 
                xml3176_xml1s.ma_loai_kcb AS ma_loaikcb,
                xml3176_xml1s.ma_cskcb,
                xml3176_xml1s.t_nguonkhac
            ")
            ->leftJoin('xml3176_xml3s', 'xml3176_xml3s.ma_lk', '=', 'xml3176_xml1s.ma_lk')
            ->where('xml3176_xml1s.ma_lk', $treatmentCode)
            ->groupBy(
                'xml3176_xml1s.ma_lk',
                'xml3176_xml1s.ma_bn', 
                'xml3176_xml1s.ho_ten', 
                'xml3176_xml1s.ngay_sinh', 
                'xml3176_xml1s.gioi_tinh', 
                'xml3176_xml1s.dia_chi', 
                'ma_the', 
                'ma_dkbd', 
                'gt_the_tu', 
                'gt_the_den', 
                'xml3176_xml1s.ma_benh_chinh', 
                'xml3176_xml1s.ma_benh_kt',
                'xml3176_xml1s.ma_doituong_kcb', 
                'xml3176_xml1s.ma_noi_di', 
                'xml3176_xml1s.ngay_vao', 
                'xml3176_xml1s.ngay_ra',
                'xml3176_xml1s.so_ngay_dtri', 
                'xml3176_xml1s.ket_qua_dtri', 
                'xml3176_xml1s.ma_loai_rv', 
                'xml3176_xml1s.t_tongchi_bh',
                'xml3176_xml1s.t_thuoc',
                'xml3176_xml1s.t_vtyt',
                'xml3176_xml1s.t_bntt', 
                'xml3176_xml1s.t_bncct', 
                'xml3176_xml1s.t_bhtt', 
                'xml3176_xml1s.ma_khoa', 
                'xml3176_xml1s.nam_qt', 
                'xml3176_xml1s.thang_qt', 
                'xml3176_xml1s.ma_khuvuc',
                'xml3176_xml1s.ma_loai_kcb',
                'xml3176_xml1s.ma_cskcb',
                'xml3176_xml1s.t_nguonkhac'
            );
        }

        // Nếu không có treatment_code, thực hiện các điều kiện lọc khác như bình thường

        // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
        $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $date_from)->format('YmdHi');
        $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $date_to)->format('YmdHi');

        // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $date_from)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $date_to)->format('Y-m-d H:i:s');

        // Define the date field based on date_type
        switch ($date_type) {
            case 'date_in':
                $dateField = 'xml3176_xml1s.ngay_vao';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_out':
                $dateField = 'xml3176_xml1s.ngay_ra';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_payment':
                $dateField = 'xml3176_xml1s.ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
            case 'date_create':
                $dateField = 'xml3176_xml1s.created_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            case 'date_update':
                $dateField = 'xml3176_xml1s.updated_at';
                $formattedDateFrom = $formattedDateFromForTimestamp;
                $formattedDateTo = $formattedDateToForTimestamp;
                break;
            default:
                $dateField = 'xml3176_xml1s.ngay_ttoan';
                $formattedDateFrom = $formattedDateFromForFields;
                $formattedDateTo = $formattedDateToForFields;
                break;
        }

        $query = Xml3176Xml1::selectRaw("
            xml3176_xml1s.ma_lk,
            xml3176_xml1s.ma_bn, 
            xml3176_xml1s.ho_ten, 
            CASE 
                WHEN SUBSTRING(xml3176_xml1s.ngay_sinh, 5, 2) <> '00' AND SUBSTRING(xml3176_xml1s.ngay_sinh, 7, 2) <> '00' 
                THEN LEFT(xml3176_xml1s.ngay_sinh, 8)
                ELSE LEFT(xml3176_xml1s.ngay_sinh, 4)
            END AS ngay_sinh,
            xml3176_xml1s.gioi_tinh, 
            xml3176_xml1s.dia_chi, 
            SUBSTRING_INDEX(xml3176_xml1s.ma_the_bhyt, ';', 1) AS ma_the, 
            SUBSTRING_INDEX(xml3176_xml1s.ma_dkbd, ';', 1) AS ma_dkbd,   
            SUBSTRING_INDEX(xml3176_xml1s.gt_the_tu, ';', 1) AS gt_the_tu, 
            SUBSTRING_INDEX(xml3176_xml1s.gt_the_den, ';', 1) AS gt_the_den, 
            xml3176_xml1s.ma_benh_chinh AS ma_benh, 
            xml3176_xml1s.ma_benh_kt AS ma_benhkhac,
            LEFT(xml3176_xml1s.ma_doituong_kcb, 1) AS ma_lydo_vvien, 
            xml3176_xml1s.ma_noi_di AS ma_noi_chuyen, 
            xml3176_xml1s.ngay_vao, 
            xml3176_xml1s.ngay_ra,
            xml3176_xml1s.so_ngay_dtri, 
            xml3176_xml1s.ket_qua_dtri, 
            xml3176_xml1s.ma_loai_rv AS tinh_trang_rv,
            xml3176_xml1s.t_tongchi_bh AS t_tongchi,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom = 1 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_xn,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom = 2 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_cdha,
            xml3176_xml1s.t_thuoc AS t_thuoc,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom = 7 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_mau,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (8,18) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_pttt,
            xml3176_xml1s.t_vtyt AS t_vtyt,
            NULL AS t_dvkt_tyle,
            NULL AS t_thuoc_tyle,
            NULL AS t_vtyt_tyle,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom = 13 THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_kham,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (14,15,16) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_giuong,
            SUM(CASE WHEN xml3176_xml3s.ma_nhom IN (12) THEN xml3176_xml3s.thanh_tien_bh ELSE NULL END) AS t_vchuyen,
            (COALESCE(xml3176_xml1s.t_bntt, 0) + COALESCE(xml3176_xml1s.t_bncct, 0)) AS t_bntt,
            xml3176_xml1s.t_bhtt, 
            NULL AS t_ngoaids,
            xml3176_xml1s.ma_khoa, 
            xml3176_xml1s.nam_qt, 
            xml3176_xml1s.thang_qt, 
            xml3176_xml1s.ma_khuvuc, 
            xml3176_xml1s.ma_loai_kcb AS ma_loaikcb,
            xml3176_xml1s.ma_cskcb,
            xml3176_xml1s.t_nguonkhac
        ")
        ->leftJoin('xml3176_xml3s', 'xml3176_xml3s.ma_lk', '=', 'xml3176_xml1s.ma_lk')
        ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo])
        ->groupBy(
            'xml3176_xml1s.ma_lk',
            'xml3176_xml1s.ma_bn', 
            'xml3176_xml1s.ho_ten', 
            'xml3176_xml1s.ngay_sinh', 
            'xml3176_xml1s.gioi_tinh', 
            'xml3176_xml1s.dia_chi', 
            'ma_the', 
            'ma_dkbd', 
            'gt_the_tu', 
            'gt_the_den', 
            'xml3176_xml1s.ma_benh_chinh', 
            'xml3176_xml1s.ma_benh_kt',
            'xml3176_xml1s.ma_doituong_kcb', 
            'xml3176_xml1s.ma_noi_di', 
            'xml3176_xml1s.ngay_vao', 
            'xml3176_xml1s.ngay_ra',
            'xml3176_xml1s.so_ngay_dtri', 
            'xml3176_xml1s.ket_qua_dtri', 
            'xml3176_xml1s.ma_loai_rv', 
            'xml3176_xml1s.t_tongchi_bh',
            'xml3176_xml1s.t_thuoc',
            'xml3176_xml1s.t_vtyt',
            'xml3176_xml1s.t_bntt', 
            'xml3176_xml1s.t_bncct', 
            'xml3176_xml1s.t_bhtt', 
            'xml3176_xml1s.ma_khoa', 
            'xml3176_xml1s.nam_qt', 
            'xml3176_xml1s.thang_qt', 
            'xml3176_xml1s.ma_khuvuc',
            'xml3176_xml1s.ma_loai_kcb',
            'xml3176_xml1s.ma_cskcb',
            'xml3176_xml1s.t_nguonkhac'
        );

        // Điều kiện lọc theo trạng thái xuất XML
        if ($xml_export_status === 'has_export') {
            $query->whereNotNull('xml3176_xml1s.exported_at');
        } elseif ($xml_export_status === 'no_export') {
            $query->whereNull('xml3176_xml1s.exported_at');
        }

        // Apply filter based on xml_submit_status
        if ($xml_submit_status === 'has_submit') {
            $query->whereNotNull('xml3176_xml1s.submitted_at');
        } elseif ($xml_submit_status === 'not_submit') {
            $query->whereNull('xml3176_xml1s.submitted_at');
        }

        // Điều kiện lọc theo trạng thái thanh toán
        if ($payment_date_filter === 'has_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '!=', '');
        } elseif ($payment_date_filter === 'no_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '=', '');
        }

        // Điều kiện lọc theo xml3176_xml_error_catalog_id
        if ($xml3176_xml_error_catalog_id) {
            $query->where('xml3176_xml1s.xml3176_xml_error_catalog_id', '=', $xml3176_xml_error_catalog_id);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'STT',
            'MA_LK',
            'MA_BN',
            'HO_TEN',
            'NGAY_SINH',
            'GIOI_TINH',
            'DIA_CHI',
            'MA_THE',
            'MA_DKBD',
            'GT_THE_TU',
            'GT_THE_DEN',
            'MA_BENH',
            'MA_BENHKHAC',
            'MA_LYDO_VVIEN',
            'MA_NOI_CHUYEN',
            'NGAY_VAO',
            'NGAY_RA',
            'SO_NGAY_DTRI',
            'KET_QUA_DTRI',
            'TINH_TRANG_RV',
            'T_TONGCHI',
            'T_XN',
            'T_CDHA',
            'T_THUOC',
            'T_MAU',
            'T_PTTT',
            'T_VTYT',
            'T_DVKT_TYLE',
            'T_THUOC_TYLE',
            'T_VTYT_TYLE',
            'T_KHAM',
            'T_GIUONG',
            'T_VCHUYEN',
            'T_BNTT',
            'T_BHTT',
            'T_NGOAIDS',
            'MA_KHOA',
            'NAM_QT',
            'THANG_QT',
            'MA_KHUVUC',
            'MA_LOAIKCB',
            'MA_CSKCB',
            'T_NGUONKHAC',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Vô hiệu hóa auto-sizing
                foreach (range('A', 'AN') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                // Đặt độ rộng các cột theo file Excel đã gửi
                $sheet->getColumnDimension('A')->setWidth(5);   // STT
                $sheet->getColumnDimension('B')->setWidth(20);  // MA_LK
                $sheet->getColumnDimension('C')->setWidth(20);  // MA_BN
                $sheet->getColumnDimension('D')->setWidth(8);   // HO_TEN
                $sheet->getColumnDimension('E')->setWidth(7);   // NGAY_SINH
                $sheet->getColumnDimension('G')->setWidth(90);  // DIA_CHI
                $sheet->getColumnDimension('H')->setWidth(16);   // MA_THE
                $sheet->getColumnDimension('I')->setWidth(9);   // MA_DKBD
                $sheet->getColumnDimension('J')->setWidth(11);  // GT_THE_TU
                $sheet->getColumnDimension('K')->setWidth(12);  // GT_THE_DEN
                $sheet->getColumnDimension('L')->setWidth(12);  // MA_BENH
                $sheet->getColumnDimension('M')->setWidth(9);   // MA_BENHKHAC
                $sheet->getColumnDimension('N')->setWidth(10);  // MA_LYDO_VVIEN
                $sheet->getColumnDimension('O')->setWidth(9);   // MA_NOI_CHUYEN
                $sheet->getColumnDimension('P')->setWidth(15);   // NGAY_VAO
                $sheet->getColumnDimension('Q')->setWidth(15);   // NGAY_RA
                
                // Các cột từ T_U_XN đến T_NGUONKHAC đều có độ rộng 15
                foreach (range('R', 'AN') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setWidth(15);
                }
                $sheet->getStyle('P:Q')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        //$sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);
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
            $data->ma_lk,
            $data->ma_bn,
            $data->ho_ten,
            $data->ngay_sinh,
            $data->gioi_tinh,
            $data->dia_chi,
            $data->ma_the,
            $data->ma_dkbd,
            $data->gt_the_tu,
            $data->gt_the_den,
            $data->ma_benh,
            $data->ma_benhkhac,
            $data->ma_lydo_vvien,
            $data->ma_noi_chuyen,
            $data->ngay_vao,
            $data->ngay_ra,
            $data->so_ngay_dtri,
            $data->ket_qua_dtri,
            $data->tinh_trang_rv,
            $data->t_tongchi,
            $data->t_xn,
            $data->t_cdha,
            $data->t_thuoc,
            $data->t_mau,
            $data->t_pttt,
            $data->t_vtyt,
            $data->t_dvkt_tyle,
            $data->t_thuoc_tyle,
            $data->t_vtyt_tyle,
            $data->t_kham,
            $data->t_giuong,
            $data->t_vchuyen,
            $data->t_bntt,
            $data->t_bhtt,
            $data->t_ngoaids,
            $data->ma_khoa,
            $data->nam_qt,
            $data->thang_qt,
            $data->ma_khuvuc,
            $data->ma_loaikcb,
            $data->ma_cskcb,
            $data->t_nguonkhac,
        ];
    }
}
