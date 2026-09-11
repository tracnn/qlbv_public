<?php

namespace App\Exports;

use App\Services\BHYT\Xml3176LocDanhSach;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bo xuat "danh sach loi" cua man XML3176: mot sheet loi XML, mot sheet loi tra cuu the.
 *
 * CA HAI sheet deu cat theo dung tap ho so ma man danh sach dang hien thi. Truoc day
 * sheet loi the chi nhan khoang ngay, bo qua toan bo bo loc con lai - ke ca ma co so,
 * nen file xuat tron ca cac co so khac du bang tren man da loc dung mot co so.
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
        return [
            new Xml3176ErrorExport($this->loc, $this->danhSachCoSo),
            // Bang check_hein_card khong co cot ma_cskcb nen khong ap truc tiep duoc bo
            // loc co so; cat theo tap ma_lk cua man danh sach thi ap duoc CA 15 bo loc.
            new HeinCardErrorExport(
                array_get($this->loc, 'date_from'),
                array_get($this->loc, 'date_to'),
                Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo),
                'xml3176'
            ),
        ];
    }
}
