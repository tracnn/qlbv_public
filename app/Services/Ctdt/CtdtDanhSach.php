<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Dung truy van cho man danh sach ho so.
 *
 * TACH KHOI CONTROLLER co chu dich: bo loc la cho de sai nhat cua mot man danh sach, va
 * kiem duoc no ma khong can dung HTTP thi moi kiem het duoc cac nhanh.
 *
 * Loc theo loai chung tu va tim theo ma the / ho ten phai di qua bang ctdt_chung_tu. Dung
 * whereHas chu khong join: mot ho so co nhieu chung tu cung loai se sinh nhieu dong khi
 * join, va man danh sach hien mot ho so hai lan la loi de nguoi dung thay nhat.
 */
class CtdtDanhSach
{
    /**
     * @param array $loc tu_ngay, den_ngay, dich_vu, loai_ho_so, macskcb, tim,
     *                   chi_con_loi, trang_thai_gui - tat ca deu tuy chon
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function truyVan(array $loc)
    {
        $q = CtdtHoSo::query();

        if (self::coGiaTri($loc, 'tu_ngay')) {
            $q->where('imported_at', '>=', self::mocDau($loc['tu_ngay']));
        }

        if (self::coGiaTri($loc, 'den_ngay')) {
            $q->where('imported_at', '<=', self::mocCuoi($loc['den_ngay']));
        }

        if (self::coGiaTri($loc, 'dich_vu')) {
            $q->where('dich_vu', trim($loc['dich_vu']));
        }

        if (self::coGiaTri($loc, 'macskcb')) {
            $q->where('macskcb', trim($loc['macskcb']));
        }

        if (self::coGiaTri($loc, 'imported_by')) {
            $q->where('imported_by', trim($loc['imported_by']));
        }

        if (self::coGiaTri($loc, 'loai_ho_so')) {
            $loai = trim($loc['loai_ho_so']);

            $q->whereHas('chungTu', function ($con) use ($loai) {
                $con->where('loai_ho_so', $loai);
            });
        }

        if (self::coGiaTri($loc, 'tim')) {
            $tim = trim($loc['tim']);

            $q->where(function ($ngoai) use ($tim) {
                $ngoai->where('ma_ho_so', 'like', '%' . $tim . '%')
                    ->orWhereHas('chungTu', function ($con) use ($tim) {
                        $con->where('ma_the', 'like', '%' . $tim . '%')
                            ->orWhere('ho_ten', 'like', '%' . $tim . '%');
                    });
            });
        }

        if (!empty($loc['chi_con_loi'])) {
            $q->where('so_loi', '>', 0);
        }

        if (self::coGiaTri($loc, 'trang_thai_gui')) {
            self::locTrangThai($q, trim($loc['trang_thai_gui']));
        }

        return $q;
    }

    /**
     * Dich mot trang thai thanh dieu kien SQL.
     *
     * Phai GIU DUNG thu tu uu tien cua CtdtTrangThaiGui::cua(): neu o day "chua ky" khong
     * loai tru "con loi" thi mot ho so vua con loi vua chua ky se hien o ca hai bo loc, va
     * tong so cac bo loc khong bang tong so ho so - nguoi dung se khong tin man hinh nua.
     */
    private static function locTrangThai($q, $trangThai)
    {
        // Dung TRUOC CON_LOI, dung nhu thu tu cua cua(): ho so chua kiem cung co
        // so_loi = 0, nen neu khong loai tru o day thi no se lot vao bo loc CHUA_KY va
        // hien la "sach" trong khi chua ai kiem no.
        if ($trangThai === CtdtTrangThaiGui::CHUA_KIEM) {
            return $q->whereNull('checked_at');
        }

        // Moi trang thai con lai deu ngu y "da kiem".
        $q->whereNotNull('checked_at');

        if ($trangThai === CtdtTrangThaiGui::CON_LOI) {
            return $q->where('so_loi', '>', 0);
        }

        // Moi trang thai con lai deu ngu y "khong con loi".
        $q->where('so_loi', '<=', 0);

        // Soi guong dung thu tu cua CtdtTrangThaiGui::cua(): KY_HONG truoc CHUA_KY vi ca hai
        // deu is_signed = false.
        if ($trangThai === CtdtTrangThaiGui::KY_HONG) {
            return $q->where('is_signed', false)
                ->whereNotNull('signed_error')
                ->where('signed_error', '<>', '');
        }

        if ($trangThai === CtdtTrangThaiGui::CHUA_KY) {
            // CHUA_KY phai LOAI TRU ky hong, khong thi mot ho so hien o ca hai bo loc va
            // tong cac bo loc khong con bang tong so ho so.
            return $q->where('is_signed', false)
                ->where(function ($q2) {
                    $q2->whereNull('signed_error')->orWhere('signed_error', '');
                });
        }

        $q->where('is_signed', true);

        if ($trangThai === CtdtTrangThaiGui::DA_GUI) {
            return $q->where('ma_ket_qua', '200');
        }

        if ($trangThai === CtdtTrangThaiGui::CONG_TU_CHOI) {
            // Phai khop CHINH XAC voi !empty($hoSo->ma_ket_qua) cua CtdtTrangThaiGui::cua():
            // chuoi rong VA chuoi '0' deu la "chua co ket qua", khong phai "bi tu choi".
            return $q->whereNotNull('ma_ket_qua')
                ->where('ma_ket_qua', '<>', '200')
                ->where('ma_ket_qua', '<>', '')
                ->where('ma_ket_qua', '<>', '0');
        }

        if ($trangThai === CtdtTrangThaiGui::GUI_HONG) {
            // Da ky, cong CHUA tra loi, nhung co submit_error: da thu gui va hong truoc khi
            // toi cong.
            return self::chuaCoKetQua($q)
                ->whereNotNull('submit_error')
                ->where('submit_error', '<>', '');
        }

        // GUI_TAT va CHUA_GUI cung la "da ky, chua co ket qua tu cong VA chua tung gui hong".
        return self::chuaCoKetQua($q)
            ->where(function ($q2) {
                $q2->whereNull('submit_error')->orWhere('submit_error', '');
            });
    }

    /**
     * Ap dieu kien "chua co ket qua tu cong BHXH" len mot builder da co san.
     *
     * NOI DUY NHAT dinh nghia dieu nay - goi lai o ca locTrangThai() (GUI_HONG, GUI_TAT,
     * CHUA_GUI) lan tonDong() cua CtdtDashboardService. Viet lai dieu kien nay o noi thu ba
     * la dung nguyen ly da vi pham mot lan o dashboard: mot ho so ma_ket_qua = '0' bi mot
     * ban sao quen mat, con ban goc thi nho.
     *
     * VI SAO CA '0': cong BHXH la he ngoai, ta khong kiem soat duoc no tra gia tri gi truoc
     * khi co ket qua that. Chuoi rong VA chuoi '0' deu la "chua co ket qua" - phai khop
     * CHINH XAC voi !empty($hoSo->ma_ket_qua) cua CtdtTrangThaiGui::cua(), vi PHP coi
     * empty('0') === true.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @return \Illuminate\Database\Eloquent\Builder cung $q, de goi noi tiep duoc
     */
    public static function chuaCoKetQua($q)
    {
        return $q->where(function ($q2) {
            $q2->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '')->orWhere('ma_ket_qua', '0');
        });
    }

    /**
     * Moc dau khoang loc.
     *
     * Bo chon khoang thoi gian (partials.date_range) gui len dang 'YYYY-MM-DD HH:mm:ss',
     * con o ngay tran gui 'YYYY-MM-DD'. Cu noi ' 00:00:00' vao ca hai thi dang co gio
     * thanh '2026-08-19 10:00:00 00:00:00' - MySQL doc khong ra va tra ve rong, man hinh
     * trong tron ma khong bao gi. Nhan dien bang dau hai cham.
     *
     * PUBLIC vi cac lop Export cung phai chuan hoa dung mot kieu. Viet ban chuan hoa thu
     * hai o ben do la tao co hoi cho hai ban lech nhau - dung can benh ma ca nhanh nay
     * dang chua.
     */
    public static function mocDau($giaTri)
    {
        $giaTri = trim((string) $giaTri);

        return strpos($giaTri, ':') === false ? $giaTri . ' 00:00:00' : $giaTri;
    }

    /**
     * Moc cuoi khoang loc.
     *
     * Voi ngay tran phai la 23:59:59, khong phai '<= ngay'. So sanh chuoi ngay tran tren
     * cot datetime se bo het ho so nap trong chinh ngay do tru dung luc 00:00:00.
     *
     * PUBLIC cung mot ly do voi mocDau(): mot ban chuan hoa duy nhat cho ca man hinh lan
     * cac lop Export.
     */
    public static function mocCuoi($giaTri)
    {
        $giaTri = trim((string) $giaTri);

        return strpos($giaTri, ':') === false ? $giaTri . ' 23:59:59' : $giaTri;
    }

    /**
     * Bu khoang ngay mac dinh khi loi goi khong co.
     *
     * Goi thang URL xuat ma khong kem tham so - hoac man hinh chua kip dat ctdtRange - se
     * quet TOAN BO ctdt_ho_so. Tren may chu gioi han PHP 128MB/120s do la mot yeu cau chet
     * giua chung, khong phai mot tep xuat lon.
     *
     * KHONG de len gia tri nguoi dung da chon: mot bo loc bi bo qua am tham te hon han loi
     * no dang chua.
     *
     * @param array $loc
     * @param int   $soNgayLui
     * @return array
     */
    public static function khoangMacDinh(array $loc, $soNgayLui = 30)
    {
        if (!self::coGiaTri($loc, 'tu_ngay')) {
            $loc['tu_ngay'] = now()->subDays($soNgayLui)->format('Y-m-d');
        }

        if (!self::coGiaTri($loc, 'den_ngay')) {
            $loc['den_ngay'] = now()->format('Y-m-d');
        }

        return $loc;
    }

    private static function coGiaTri(array $loc, $khoa)
    {
        return isset($loc[$khoa]) && trim((string) $loc[$khoa]) !== '';
    }
}
