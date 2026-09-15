<?php

namespace App\Exports;

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
        $sheets = [];

        foreach (Xml3176KhoaNguon::LOAI_XML as $loai) {
            $sheets[] = new Xml3176ErrorSheetExport($loai, $this->loc, $this->danhSachCoSo);
        }

        $sheets[] = new Xml3176ErrorSheetExport('XMLComplete', $this->loc, $this->danhSachCoSo);

        // Bang check_hein_card khong co cot ma_cskcb nen khong ap truc tiep duoc bo loc co
        // so; cat theo tap ma_lk cua man danh sach thi ap duoc CA bo loc.
        $sheets[] = new HeinCardErrorExport(
            array_get($this->loc, 'date_from'),
            array_get($this->loc, 'date_to'),
            Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo),
            'xml3176',
            true
        );

        // Hai sheet danh muc PHAI dung cuoi. Chung cai StringValueBinder, ma Laravel Excel
        // dat bo gan gia tri bang bien TINH toan cuc khi mo sheet va khong tra lai khi dong
        // (vendor/maatwebsite/excel/src/Sheet.php) - sheet nao dung sau se thua huong.
        $sheets[] = new DmKhoaGiuongSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);
        $sheets[] = new DmNvytSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);

        return $sheets;
    }
}
