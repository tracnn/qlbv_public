<?php

namespace App\Exports;

use App\Services\BHYT\KhoaDieuTriHis;
use App\Services\BHYT\Xml3176LocDanhSach;
use App\Services\Xml3176\Xml3176KhoaNguon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bo xuat "danh sach loi" cua man XML3176 - 19 sheet, thu tu co dinh:
 *
 *   1-15  XML1 ... XML15      dong loi tung loai XML, LUON du 15 sheet ke ca sheet trong
 *   16    XMLComplete         loi lien bang
 *   17    Loi the BHYT        ket qua tra cuu the co loi
 *   18    DM khoa-giuong      bang tra cuu, xuat toan bo
 *   19    DM NVYT             bang tra cuu, xuat toan bo
 *
 * Sheet 1-17 cat theo DUNG tap ho so ma man danh sach dang hien thi
 * (Xml3176LocDanhSach). Sheet 18-19 chi loc theo ma co so dang chon.
 */
class Xml3176ErrorMultiSheetExport implements WithMultipleSheets
{
    /** @var array bo loc doc tu man danh sach (Xml3176LocDanhSach::tuRequest()) */
    protected $loc;
    /** @var array ma co so => nhan */
    protected $danhSachCoSo;

    public function __construct(array $loc, array $danhSachCoSo = [])
    {
        $this->loc = $loc;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    public function sheets(): array
    {
        // KHONG dat set_time_limit/memory_limit o day: ham nay chay BEN TRONG Excel::store,
        // sau khi XuatTepLoiXml3176Job da dat set_time_limit(0). Dat 1800 o day se ghi de lai
        // 30 phut - tren Windows do theo gio thuc, lan xuat ngay lon co the bi giet giua chung.

        $sheets = [];

        // MOT bo tra khoa HIS cho ca 17 sheet: no nho ma da tra, ho so loi o nhieu sheet chi
        // tra HIS mot lan.
        $khoaHis = new KhoaDieuTriHis();

        foreach (Xml3176KhoaNguon::LOAI_XML as $loai) {
            $sheets[] = new Xml3176ErrorSheetExport($loai, $this->loc, $this->danhSachCoSo, $khoaHis);
        }

        $sheets[] = new Xml3176ErrorSheetExport('XMLComplete', $this->loc, $this->danhSachCoSo, $khoaHis);

        // Cat theo tap ma_lk cua man danh sach (Xml3176LocDanhSach::truyVanMaLk) de sheet
        // nay ap duoc TAT CA bo loc cua man danh sach, khong chi rieng ma co so.
        $sheets[] = new HeinCardErrorExport(
            array_get($this->loc, 'date_from'),
            array_get($this->loc, 'date_to'),
            Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo),
            'xml3176',
            true,
            $khoaHis
        );

        // Hai sheet danh muc PHAI dung cuoi. Chung cai StringValueBinder, ma Laravel Excel
        // dat bo gan gia tri bang bien TINH toan cuc khi mo sheet va khong tra lai khi dong
        // (vendor/maatwebsite/excel/src/Sheet.php) - sheet nao dung sau se thua huong.
        //
        // Bien tinh nay con SONG suot doi tien trinh PHP, khong rieng file xuat nay. Voi
        // request web (moi request mot tien trinh PHP rieng) thi vo hai. Neu sau nay bo
        // xuat nay chay trong queue worker (mot tien trinh xu ly nhieu job), cac lan xuat
        // KHAC sau do trong CUNG worker se bi ghi MOI o thanh chuoi cho den khi worker do
        // duoc khoi dong lai - phai tu dat lai Cell::setValueBinder() ve mac dinh sau khi
        // hai sheet danh muc nay dong.
        $sheets[] = new DmKhoaGiuongSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);
        $sheets[] = new DmNvytSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);

        return $sheets;
    }
}
