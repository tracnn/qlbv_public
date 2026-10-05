<?php

namespace App\Exports;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\BHYT\KhoaDieuTriHis;
use App\Services\BHYT\Xml3176LocDanhSach;
use App\Services\Xml3176\Xml3176KhoaNguon;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * MOT sheet loi cho MOT loai XML trong file xuat loi XML3176.
 *
 * Thay Xml3176ErrorExport (mot sheet tron moi loai XML). Quy tac "xuat ra dung cai nhin
 * thay" giu nguyen: tap ho so cat theo Xml3176LocDanhSach, trong tung ho so lay het cac
 * dong loi cua loai nay, khong cat them o muc dong.
 *
 * KHONG dung ShouldAutoSize: do rong da dat co dinh o DO_RONG, con tu co gian tren sheet
 * XML4 (152.700 dong tren du lieu that) phai do tung o.
 *
 * DOC MOT LAN (FromGenerator + cursor), KHONG FromQuery: FromQuery doc theo lo LIMIT/OFFSET,
 * moi lo MySQL chay lai toan bo truy van (subquery + 4 join + ORDER BY). Do 29/09/2026: moi lo
 * ~7 giay, sheet XML3 ~102 lo; doc mot lan ca sheet 7,8 giay. query() giu lai de test va de
 * doc SQL.
 */
class Xml3176ErrorSheetExport implements FromGenerator, WithHeadings, WithStyles, WithEvents, WithMapping, WithTitle
{
    /**
     * Do rong tung cot. Cot E (Ma Khoa) moi chen; moi cot tu F tro di la cot cu dich sang
     * phai mot vi tri so voi Xml3176ErrorExport.
     */
    const DO_RONG = [
        'A' => 5,  'B' => 10, 'C' => 8,  'D' => 13, 'E' => 10,
        'F' => 14, 'G' => 22, 'H' => 13, 'I' => 18, 'J' => 13,
        'K' => 13, 'L' => 13, 'M' => 13, 'N' => 13, 'O' => 30,
        'P' => 50, 'Q' => 13, 'R' => 12, 'S' => 12,
        // Khoa dieu tri cuoi trong HIS.
        'T' => 12, 'U' => 30,
    ];

    /** Cot ngay dang so YYYYMMDD[HHMM]: dinh dang so de Excel khong hien dang 2,03E+11. */
    const COT_NGAY = ['H', 'J', 'K', 'L', 'M', 'N'];

    protected $loai;
    protected $loc;
    protected $danhSachCoSo;
    protected $rowNumber = 0;

    /** So dong gom lai cho mot lan tra khoa HIS. */
    const LO_KHOA_HIS = 1000;

    /** @var KhoaDieuTriHis dung chung cho ca file - xem Xml3176ErrorMultiSheetExport */
    protected $khoaHis;

    /**
     * @param string $loai 'XML1'...'XML15' hoac 'XMLComplete'
     * @param array $loc bo loc doc tu man danh sach (Xml3176LocDanhSach::tuRequest())
     * @param array $danhSachCoSo ma co so => nhan
     * @param KhoaDieuTriHis|null $khoaHis tra khoa HIS; bo xuat nhieu sheet truyen MOT instance
     */
    public function __construct($loai, array $loc, array $danhSachCoSo = [], KhoaDieuTriHis $khoaHis = null)
    {
        $this->loai = $loai;
        $this->loc = $loc;
        $this->danhSachCoSo = $danhSachCoSo;
        $this->khoaHis = $khoaHis ?: new KhoaDieuTriHis();
    }

    /**
     * Doc ca sheet bang MOT truy van (cursor), khong phan trang.
     *
     * @return \Generator
     */
    public function generator(): \Generator
    {
        // Van MOT cursor; chi gom LO_KHOA_HIS dong de tra khoa HIS mot lan cho ca lo. KHONG
        // dung prepareRows(): voi FromGenerator Laravel Excel goi no mot lan cho CA generator,
        // tuc nap ca sheet (XML4 ~152.700 dong) vao bo nho.
        //
        // KHONG 'yield from $mang': no giu khoa 0..999 cua tung lo, ma Laravel Excel doc
        // generator bang iterator_to_array GIU KHOA - lo sau ghi de lo truoc, sheet chi con
        // 1.000 dong cuoi. 'yield $d' tu danh khoa tang dan tren ca generator.
        $lo = [];

        foreach ($this->query()->cursor() as $dong) {
            $lo[] = $dong;

            if (count($lo) >= self::LO_KHOA_HIS) {
                foreach ($this->ganKhoaHis($lo) as $d) {
                    yield $d;
                }
                $lo = [];
            }
        }

        foreach ($this->ganKhoaHis($lo) as $d) {
            yield $d;
        }
    }

    protected function ganKhoaHis(array $lo)
    {
        $khoa = $this->khoaHis->khoaChoXuat(array_map(function ($d) { return $d->ma_lk; }, $lo));

        foreach ($lo as $d) {
            $k = $khoa[trim((string) $d->ma_lk)] ?? ['ma_khoa' => null, 'ten_khoa' => null];
            $d->ma_khoa_his = $k['ma_khoa'];
            $d->ten_khoa_his = $k['ten_khoa'];
        }

        return $lo;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        // Gioi han thoi gian/bo nho do XuatTepLoiXml3176Job dat, khong dat o day.
        $query = Xml3176ErrorResult::query()
            ->whereIn('xml3176_error_results.ma_lk',
                Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo))
            ->where('xml3176_error_results.xml', $this->loai)
            // Moi ma_lk trong truy van con deu lay tu xml3176_xml1s nen join nay khong lam
            // mat dong loi nao.
            ->join('xml3176_xml1s', 'xml3176_xml1s.ma_lk', '=', 'xml3176_error_results.ma_lk')
            // LEFT: ho so thieu dong informations van phai ra du dong loi.
            ->leftJoin('xml3176_informations', 'xml3176_informations.ma_lk', '=', 'xml3176_error_results.ma_lk')
            // Noi theo CA xml lan error_code: ban cu chi noi error_code, mot ma loi trung o
            // hai loai XML se nhan doi dong. LEFT: ma loi chua co trong danh muc van ra dong.
            ->leftJoin('xml3176_error_catalogs', function ($j) {
                $j->on('xml3176_error_catalogs.xml', '=', 'xml3176_error_results.xml')
                  ->on('xml3176_error_catalogs.error_code', '=', 'xml3176_error_results.error_code');
            })
            ->select(
                'xml3176_error_results.xml',
                'xml3176_error_results.stt',
                'xml3176_error_results.ma_lk',
                'xml3176_error_results.ngay_yl',
                'xml3176_error_results.ngay_kq',
                'xml3176_error_results.error_code',
                'xml3176_error_results.description',
                'xml3176_error_results.critical_error',
                'xml3176_error_catalogs.error_name as catalog_error_name',
                'xml3176_xml1s.ma_bn',
                'xml3176_xml1s.ho_ten',
                'xml3176_xml1s.ngay_sinh',
                'xml3176_xml1s.ma_the_bhyt',
                'xml3176_xml1s.ngay_vao',
                'xml3176_xml1s.ngay_ra',
                'xml3176_xml1s.ngay_ttoan',
                'xml3176_informations.imported_by',
                'xml3176_informations.exported_by'
            );

        // Bo loc ve LOI phai cat ca dong, khong chi chon ho so.
        Xml3176LocDanhSach::apDongLoi($query, $this->loc);

        $nguon = Xml3176KhoaNguon::nguon($this->loai);

        if ($nguon === null) {
            $query->selectRaw('xml3176_xml1s.ma_khoa as ma_khoa_xuat');
        } else {
            $query->leftJoin($nguon['bang'] . ' as khoa_nguon', function ($j) use ($nguon) {
                $j->on('khoa_nguon.ma_lk', '=', 'xml3176_error_results.ma_lk');

                if ($nguon['noiStt']) {
                    $j->on('khoa_nguon.stt', '=', 'xml3176_error_results.stt');
                }
            });

            // Ten cot lay tu hang so cua Xml3176KhoaNguon, khong tu dau vao nguoi dung.
            $query->selectRaw(
                "COALESCE(NULLIF(khoa_nguon.{$nguon['cot']}, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat"
            );
        }

        return $query
            ->orderBy('xml3176_error_results.ma_lk')
            ->orderBy('xml3176_error_results.stt')
            ->orderBy('xml3176_error_results.id');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại XML',
            'STT XML',
            'Mã Liên Kết',
            'Mã Khoa',
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
            'Exported by',
            // Khoa dieu tri cuoi trong HIS (his_treatment.last_department_id) - them CUOI de
            // khong xe dich cot nguoi dung da quen.
            'Mã khoa (HIS)',
            'Khoa điều trị (HIS)',
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
            $data->ma_khoa_xuat,
            $data->ma_bn,
            $data->ho_ten,
            $data->ngay_sinh,
            $data->ma_the_bhyt,
            $data->ngay_vao,
            $data->ngay_ra,
            $data->ngay_ttoan,
            $data->ngay_yl,
            $data->ngay_kq,
            // Cot tieu de ghi "Ma Loi" nhung tu ban cu da chua TEN loi tu danh muc; giu nguyen.
            // Ma loi chua co trong danh muc (LEFT JOIN ra null) thi hien ma de khong o trong.
            $data->catalog_error_name ?: $data->error_code,
            $data->description,
            $data->critical_error ? 'Xuất toán' : 'Cảnh báo',
            $data->imported_by,
            $data->exported_by,
            $data->ma_khoa_his ?? null,
            $data->ten_khoa_his ?? null,
        ];
    }

    /**
     * Dinh dang dung CHUNG cho duong Laravel Excel (registerEvents/styles) va duong ghi luong
     * (App\Services\ExcelLuong\GhiExcelLuong): mot nguon, hai duong khong the lech nhau.
     */
    public function dinhDangLuong(): array
    {
        return [
            'do_rong' => self::DO_RONG,
            'cot_so' => self::COT_NGAY,
            'kieu_o' => 'tu_dong',
            'xuong_dong' => true,
            'tieu_de_can_giua' => true,
        ];
    }

    public function registerEvents(): array
    {
        $d = $this->dinhDangLuong();

        return [
            AfterSheet::class => function (AfterSheet $event) use ($d) {
                $sheet = $event->sheet->getDelegate();

                foreach ($d['do_rong'] as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                }

                foreach ($d['cot_so'] as $cot) {
                    $sheet->getStyle($cot)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $doRong = $this->dinhDangLuong()['do_rong'];
        $sheet->getStyle('A:' . array_keys($doRong)[count($doRong) - 1])->getAlignment()->setWrapText(true);

        return [
            1 => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return $this->loai;
    }
}
