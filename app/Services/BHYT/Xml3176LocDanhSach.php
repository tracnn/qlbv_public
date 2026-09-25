<?php

namespace App\Services\BHYT;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176ErrorCatalog;
use Carbon\Carbon;

/**
 * MOT nguon duy nhat cho bo loc cua man danh sach XML3176.
 *
 * Truoc lop nay, bo loc bi CHEP LAI o bon noi: BHYTXml3176Controller::fetchData(),
 * Xml3176XmlExport, Xml3176ErrorExport va Xml3176Xml7980aExport - moi ban chep mot
 * phan khac nhau. Hau qua do duoc:
 *
 *   - Nut "xuat danh sach ho so": man gui 11/15 tham so, va lop Export doc
 *     xml_filter_status + xml3176_error_catalog vao bien cuc bo roi KHONG dung
 *     -> thuc te chi 6/15 bo loc co tac dung. Nguoi dung loc "chi ho so co loi
 *     nghiem trong" bam xuat van nhan ve TOAN BO ho so trong khoang ngay.
 *   - Nut "xuat danh sach loi": thieu 6/15.
 *
 * Kieu hong nay im lang - van ra file, chi la sai pham vi. No da duoc va BA lan theo
 * kieu them tung tham so mot (xem chu thich con lai trong index.blade.php ve
 * xml_export_status va ma_cskcb) va lan nao cung sot tiep. Them bo loc moi tu nay chi
 * sua MOT cho: hang so KHOA va ham apBoLoc() ben duoi.
 */
class Xml3176LocDanhSach
{
    /**
     * Tron ven cac khoa bo loc cua man danh sach, dung y nhu ten input tren giao dien.
     *
     * 'treatment_type_fillter' viet sai chinh ta tu truoc (fillter); GIU NGUYEN vi do
     * la ten input that tren blade - sua o day ma khong sua blade thi bo loc chet cam.
     */
    const KHOA = [
        'date_from',
        'date_to',
        'date_type',
        'treatment_code',
        'patient_code',
        'xml_filter_status',
        'xml3176_error_catalog',
        'hein_card_filter',
        'payment_date_filter',
        'treatment_type_fillter',
        'ma_khoa',
        'xml_export_status',
        'xml_submit_status',
        'xml_sign_status',
        'imported_by',
        'ma_cskcb',
    ];

    /** Doc tron bo loc tu request. Khoa thieu -> null, khong phai khong co khoa. */
    public static function tuRequest($request): array
    {
        $loc = [];

        foreach (self::KHOA as $k) {
            $loc[$k] = $request->input($k);
        }

        return $loc;
    }

    /**
     * Truy van danh sach HO SO dung y nhu man danh sach dang hien thi.
     *
     * @param array $loc            ket qua tuRequest()
     * @param array $danhSachCoSo   mang ma => nhan (DanhSachCoSo::danhSach())
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function truyVanHoSo(array $loc, array $danhSachCoSo)
    {
        $query = Xml3176Xml1::query();

        return self::apBoLoc($query, $loc, $danhSachCoSo);
    }

    /**
     * Cung tap ho so nhu truyVanHoSo() nhung chi lay cot ma_lk - dung lam truy van con
     * cho cac bang khac (danh sach loi, ket qua tra cuu the) de chung cat theo DUNG tap
     * ho so ma nguoi dung dang nhin thay.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function truyVanMaLk(array $loc, array $danhSachCoSo)
    {
        $query = Xml3176Xml1::query()->select('xml3176_xml1s.ma_lk');

        return self::apBoLoc($query, $loc, $danhSachCoSo)->getQuery();
    }

    /**
     * Ap tron bo loc vao mot query tren Xml3176Xml1.
     *
     * Phai la Eloquent Builder (khong phai Query Builder): nhieu bo loc dung whereHas()
     * tren quan he Xml3176ErrorResult / check_hein_card / Xml3176Information.
     *
     * Moi cot deu ghi ro tien to bang: cac lop Export co join them bang khac, khong ghi
     * ro thi MySQL nem loi cot nhap nhang.
     */
    public static function apBoLoc($query, array $loc, array $danhSachCoSo)
    {
        $g = function ($k) use ($loc) {
            return array_key_exists($k, $loc) ? $loc[$k] : null;
        };

        // Hai nhanh NGAN MACH, giong het man danh sach: tra cuu dich danh mot ho so hoac
        // mot benh nhan thi BO khoang ngay va moi bo loc khac - nguoi dung go ma vao la
        // muon tim dung ho so do, khong phai tim trong pham vi dang loc.
        if (!empty($g('treatment_code'))) {
            $query->where('xml3176_xml1s.ma_lk', $g('treatment_code'));
            self::apPhamViNguoiDung($query, $g('imported_by'));
            LocCoSo::ap($query, $g('ma_cskcb'), $danhSachCoSo, 'xml3176_xml1s.ma_cskcb');

            return $query;
        }

        if (!empty($g('patient_code'))) {
            $query->where('xml3176_xml1s.ma_bn', $g('patient_code'));
            self::apPhamViNguoiDung($query, $g('imported_by'));
            LocCoSo::ap($query, $g('ma_cskcb'), $danhSachCoSo, 'xml3176_xml1s.ma_cskcb');

            return $query;
        }

        list($cotNgay, $tu, $den) = self::khoangNgay($g('date_type'), $g('date_from'), $g('date_to'));
        $query->whereBetween($cotNgay, [$tu, $den]);

        self::apMaLoi($query, $g('xml3176_error_catalog'));
        self::apTrangThaiLoi($query, $g('xml_filter_status'));
        self::apTheBhyt($query, $g('hein_card_filter'));

        if ($g('payment_date_filter') === 'has_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '<>', '');
        } elseif ($g('payment_date_filter') === 'no_payment_date') {
            $query->where('xml3176_xml1s.ngay_ttoan', '=', '');
        }

        if (!empty($g('treatment_type_fillter'))) {
            $query->where('xml3176_xml1s.ma_loai_kcb', $g('treatment_type_fillter'));
        }

        if (!empty($g('ma_khoa'))) {
            $query->where('xml3176_xml1s.ma_khoa', $g('ma_khoa'));
        }

        self::apThongTinXml($query, 'exported_at', $g('xml_export_status'), [
            'has_export' => 'co', 'no_export' => 'khong',
        ]);

        self::apThongTinXml($query, 'submitted_at', $g('xml_submit_status'), [
            'has_submit' => 'co', 'not_submit' => 'khong',
        ]);

        if ($g('xml_submit_status') === 'has_submit_error') {
            $query->whereHas('Xml3176Information', function ($q) {
                $q->whereNotNull('submit_error');
            });
        }

        if ($g('xml_sign_status') === 'has_sign') {
            $query->whereHas('Xml3176Information', function ($q) {
                $q->where('is_signed', true);
            });
        } elseif ($g('xml_sign_status') === 'not_sign') {
            $query->whereHas('Xml3176Information', function ($q) {
                $q->where('is_signed', false);
            });
        } elseif ($g('xml_sign_status') === 'has_sign_error') {
            $query->whereHas('Xml3176Information', function ($q) {
                $q->whereNotNull('signed_error');
            });
        }

        self::apPhamViNguoiDung($query, $g('imported_by'));
        LocCoSo::ap($query, $g('ma_cskcb'), $danhSachCoSo, 'xml3176_xml1s.ma_cskcb');

        return $query;
    }

    /**
     * Loc theo nguoi nap - CHI khi nguoi dung chon o "Nguoi nap".
     *
     * KHONG con gioi han "nguoi dung khong phai quan tri chi thay ho so minh tu nap"
     * (nguoi dung chot bo ngay 25/09/2026): 35.789/35.803 ho so do he thong TU NAP nen
     * imported_by rong, 13 tai khoan xml-man khong phai quan tri gan nhu khong thay ho so
     * nao tren man danh sach lan file xuat. Quyen xem man hinh da do checkrole:xml-man lo.
     */
    private static function apPhamViNguoiDung($query, $imported_by)
    {
        if (empty($imported_by)) {
            return $query;
        }

        return $query->whereHas('Xml3176Information', function ($q) use ($imported_by) {
            $q->where('imported_by', $imported_by);
        });
    }

    /** Ho so co it nhat mot dong loi mang dung ma loi duoc chon. */
    private static function apMaLoi($query, $id)
    {
        if (empty($id)) {
            return $query;
        }

        $catalog = Xml3176ErrorCatalog::find($id);

        if (!$catalog) {
            return $query;
        }

        return $query->whereHas('Xml3176ErrorResult', function ($q) use ($catalog) {
            $q->where('xml', $catalog->xml)->where('error_code', $catalog->error_code);
        });
    }

    /** Bay gia tri cua o "Trang thai loi" tren man danh sach. */
    private static function apTrangThaiLoi($query, $trangThai)
    {
        $theLoi = function ($q) {
            $q->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code', []))
              ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code', []));
        };

        if ($trangThai === 'has_error') {
            return $query->where(function ($q) use ($theLoi) {
                $q->whereHas('Xml3176ErrorResult')->orWhereHas('check_hein_card', $theLoi);
            });
        }

        if ($trangThai === 'no_error') {
            return $query->whereDoesntHave('Xml3176ErrorResult')
                         ->whereDoesntHave('check_hein_card', $theLoi);
        }

        if ($trangThai === 'has_error_critical') {
            return $query->whereHas('Xml3176ErrorResult', function ($q) {
                $q->where('critical_error', true);
            });
        }

        if ($trangThai === 'has_error_warning') {
            return $query->whereHas('Xml3176ErrorResult', function ($q) {
                $q->where('critical_error', false);
            })->whereDoesntHave('Xml3176ErrorResult', function ($q) {
                $q->where('critical_error', true);
            });
        }

        if ($trangThai === 'has_error_hein_card') {
            return $query->whereHas('check_hein_card', $theLoi);
        }

        if ($trangThai === 'has_error_hein_card_without_xml') {
            return $query->whereHas('check_hein_card', $theLoi)
                         ->whereDoesntHave('Xml3176ErrorResult');
        }

        if ($trangThai === 'no_error_critical') {
            return $query->whereDoesntHave('Xml3176ErrorResult', function ($q) {
                $q->where('critical_error', true);
            });
        }

        return $query;
    }

    private static function apTheBhyt($query, $bo)
    {
        if ($bo === 'has_hein_card') {
            return $query->where('xml3176_xml1s.ma_the_bhyt', '<>', '');
        }

        if ($bo === 'no_hein_card') {
            return $query->where('xml3176_xml1s.ma_the_bhyt', '=', '');
        }

        if ($bo === 'has_hein_cards') {
            // Nhieu the tren mot ho so duoc luu thanh danh sach ngan boi ';'
            return $query->where('xml3176_xml1s.ma_the_bhyt', 'LIKE', '%;%');
        }

        return $query;
    }

    /** Cot thoi diem tren xml3176_informations: co gia tri / chua co gia tri. */
    private static function apThongTinXml($query, $cot, $giaTri, array $anhXa)
    {
        if (!isset($anhXa[$giaTri])) {
            return $query;
        }

        $co = $anhXa[$giaTri] === 'co';

        return $query->whereHas('Xml3176Information', function ($q) use ($cot, $co) {
            $co ? $q->whereNotNull($cot) : $q->whereNull($cot);
        });
    }

    /**
     * Cot ngay va hai moc da dinh dang, theo dung o "Loai ngay" tren man danh sach.
     *
     * Cot ngay cua chuan 3176 la chuoi YYYYMMDDHHMM, con created_at/updated_at la
     * timestamp - hai ho dinh dang khac nhau, so nham thi ra rong ma khong bao loi.
     */
    private static function khoangNgay($date_type, $tu, $den): array
    {
        $tu  = self::noNgay($tu, true);
        $den = self::noNgay($den, false);

        $truong    = Carbon::createFromFormat('Y-m-d H:i:s', $tu)->format('YmdHi');
        $truongDen = Carbon::createFromFormat('Y-m-d H:i:s', $den)->format('YmdHi');

        switch ($date_type) {
            case 'date_in':
                return ['xml3176_xml1s.ngay_vao', $truong, $truongDen];
            case 'date_out':
                return ['xml3176_xml1s.ngay_ra', $truong, $truongDen];
            case 'date_payment':
                return ['xml3176_xml1s.ngay_ttoan', $truong, $truongDen];
            case 'date_create':
                return ['xml3176_xml1s.created_at', $tu, $den];
            case 'date_update':
                return ['xml3176_xml1s.updated_at', $tu, $den];
            default:
                return ['xml3176_xml1s.ngay_ttoan', $truong, $truongDen];
        }
    }

    /** 'YYYY-MM-DD' -> dau/cuoi ngay; chuoi day du thi giu nguyen. */
    private static function noNgay($ngay, bool $dauNgay): string
    {
        $ngay = trim((string) $ngay);

        if (strlen($ngay) === 10) {
            $c = Carbon::createFromFormat('Y-m-d', $ngay);

            return ($dauNgay ? $c->startOfDay() : $c->endOfDay())->format('Y-m-d H:i:s');
        }

        return $ngay;
    }
}
