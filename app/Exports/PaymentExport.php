<?php

namespace App\Exports;

use App\Payment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithMapping
{
    protected $treatmentCode;
    protected $fromDate;
    protected $toDate;

    protected $rowNumber = 0;

    public function __construct($treatmentCode = null, $fromDate = null, $toDate = null)
    {
        $this->treatmentCode = $treatmentCode;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        $query = Payment::query();

        if ($this->fromDate && $this->toDate) {
            $query = $query->whereBetween('created_at', [$this->fromDate, $this->toDate]);
        }
        
        // Kiểm tra vai trò của người dùng và tùy chỉnh truy vấn
        if (\Auth::user()->hasRole(['superadministrator', 'thungan-tonghop'])) {
        } elseif (\Auth::user()->hasRole('thungan')) {
            $query = $query->where('login_name', \Auth::user()->loginname);
        } 
        elseif (!\Auth::user()->hasRole('superadministrator') && !\Auth::user()->hasRole('thungan-tonghop')) {
            // Nếu người dùng không có vai trò 'superadministrator' hoặc 'thungan-tonghop', không trả về dữ liệu nào
            $query = $query->where('id', null); // Điều kiện không tồn tại để không trả về bản ghi nào
        }
        // Đối với 'superadministrator' và 'thungan-tonghop', không cần điều chỉnh truy vấn vì họ có thể xem tất cả dữ liệu

        if ($this->treatmentCode) {
            $query->where('treatment_code', $this->treatmentCode);
            return $query;
        }

        return $query;
        
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Điều Trị',
            'Tên Bệnh Nhân',
            'Ngày Sinh',
            'Địa Chỉ',
            'Số Điện Thoại',
            'Số Điện Thoại Người Nhà',
            'Loại Thanh Toán',
            'Số Tiền',
            'Mã Người Lập',
            'Tên Người Lập',
            'Ngày Lập',
            'Ngày Sửa',
            'Khoa Điều Trị'
            // Thêm các tiêu đề cột khác của bạn ở đây
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Thiết lập độ rộng cụ thể cho các cột
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(13);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(10);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(12);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(12);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(20);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(18);
                $sheet->getColumnDimension('N')->setWidth(20);
                // Thiết lập độ rộng cho các cột khác tùy theo nhu cầu của bạn
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

    public function map($payment): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $payment->treatment_code,
            $payment->patient_name,
            $payment->patient_dob,
            $payment->patient_address,
            $payment->patient_mobile,
            $payment->patient_relative_mobile,
            $payment->is_payment == 1 ? 'Thanh toán' : 'Tạm thu', // Thay đổi cách hiển thị dựa trên giá trị
            $payment->amount,
            $payment->login_name,
            $payment->user_name,
            $payment->created_at,
            $payment->updated_at,
            $payment->department_name,
            // Có thể thêm các trường khác nếu cần
        ];
    }
}
