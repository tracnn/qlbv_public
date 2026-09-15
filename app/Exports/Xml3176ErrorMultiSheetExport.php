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
        // Dat MOT LAN cho ca 19 sheet o day; neu de trong Xml3176ErrorSheetExport::query()
        // thi moi sheet goi lai se dat lai gio 16 lan (mot lan moi loai XML).
        set_time_limit(1800);
        ini_set('memory_limit', '4096M');

        $sheets = [];

        foreach (Xml3176KhoaNguon::LOAI_XML as $loai) {
            $sheets[] = new Xml3176ErrorSheetExport($loai, $this->loc, $this->danhSachCoSo);
        }

        $sheets[] = new Xml3176ErrorSheetExport('XMLComplete', $this->loc, $this->danhSachCoSo);

        // Cat theo tap ma_lk cua man danh sach (Xml3176LocDanhSach::truyVanMaLk) de sheet
        // nay ap duoc TAT CA bo loc cua man danh sach, khong chi rieng ma co so.
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
