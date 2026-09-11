<?php

namespace App\Services\Ctdt;

use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLichSuSua;

/**
 * Sua VAN BAN THO cua XML goc mot chung tu.
 *
 * DAY LA DUONG NGUY HIEM NHAT CUA MODULE. noi_dung_goc duoc base64 THANG vao phong bi o
 * CtdtPhongBi roi ky so va POST len cong BHXH - nguoi sua dang sua chinh van ban phap ly se
 * nam tren cong. Khac voi duong nap (noi dung do phan mem HIS sinh ra), o day noi dung do
 * NGUOI GO, nen trinh duyet khong do duoc gi va moi chot phai nam o server.
 *
 * SAU CHOT, theo dung thu tu:
 *   1. Khong rong
 *   2. Khong vuot tran kich thuoc
 *   3. KHONG chua DOCTYPE  <- chot an toan, xem chu thich o kiemDoctype()
 *   4. Parse duoc
 *   5. The goc khop dung loai cua chung tu do
 *   6. Khoa nghiep vu khong doi
 *
 * Rieng chot "ho so dang co khoa xu ly" nam o noi goi (controller), vi no hoi trang thai
 * cua HO SO chu khong phai noi dung XML.
 */
class CtdtSuaXml
{
    /** Du dieu kien ghi */
    const OK = 'ok';

    const RONG = 'rong';
    const QUA_LON = 'qua_lon';
    const CO_DOCTYPE = 'co_doctype';
    const KHONG_PARSE = 'khong_parse';
    const THE_GOC_LECH = 'the_goc_lech';
    const DOI_KHOA = 'doi_khoa';
    const LOAI_LA = 'loai_la';

    /**
     * Tran kich thuoc, tinh bang BYTE.
     *
     * Do tren du lieu that (3048 chung tu, 2026-09-11): ban LON NHAT la 16.093 ky tu, trung
     * binh 2.423. Tran 256KB rong gap ~16 lan ban lon nhat - du cho moi truong hop that, va
     * van chan duoc mot lan dan nham ca tep vai chuc MB vao o nhap.
     */
    const TOI_DA_BYTE = 262144;

    /**
     * @param  string      $noiDung  XML nguoi dung go
     * @param  CtdtChungTu $chungTu  Ban ghi dang sua
     * @return array ['ma' => hang so tren, 'xml' => SimpleXMLElement|null, 'chi_tiet' => string]
     */
    public static function kiem($noiDung, CtdtChungTu $chungTu)
    {
        $noiDung = (string) $noiDung;

        if (trim($noiDung) === '') {
            return self::ket(self::RONG);
        }

        if (strlen($noiDung) > self::TOI_DA_BYTE) {
            return self::ket(self::QUA_LON, null, number_format(strlen($noiDung)) . ' byte');
        }

        if (self::coDoctype($noiDung)) {
            return self::ket(self::CO_DOCTYPE);
        }

        if (!CtdtLoaiRegistry::co($chungTu->loai_ho_so)) {
            return self::ket(self::LOAI_LA, null, (string) $chungTu->loai_ho_so);
        }

        $ketQua = self::doc($noiDung);

        if ($ketQua['xml'] === false) {
            return self::ket(self::KHONG_PARSE, null, $ketQua['loi']);
        }

        $xml = $ketQua['xml'];
        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);

        // The goc quyet dinh BANG CHI TIET va TAB hien thi. Cho doi no nghia la mot ban ghi
        // CT03 bong mang noi dung CT04, va ghiChiTiet() se ghi vao bang cua loai CU - du
        // lieu sai cho ma khong loi nao.
        if ($xml->getName() !== $lop::theGoc()) {
            return self::ket(self::THE_GOC_LECH, null, $xml->getName() . ' (can ' . $lop::theGoc() . ')');
        }

        // KHOA NGHIEP VU KHONG DUOC DOI. ma_ho_so cua ho so duoc suy tu khoa nay LUC NAP va
        // khong doi theo. Cho sua no thi ho so van mang ma cu trong khi chung tu ben trong
        // mang ma moi - lan nap goi sau se KHONG ghi de duoc ho so nay ma tao ra ho so THU
        // HAI, va hai ban cung ton tai. Khong co thong bao nao, chi lo ra luc doi soat.
        $khoaCu  = (string) $chungTu->ma_chung_tu;
        $khoaMoi = (string) $lop::maChungTu($xml);

        if ($khoaCu !== '' && $khoaMoi !== $khoaCu) {
            return self::ket(self::DOI_KHOA, null, $khoaCu . ' -> ' . ($khoaMoi === '' ? '(rong)' : $khoaMoi));
        }

        return self::ket(self::OK, $xml);
    }

    /**
     * Ghi ban sua. Goi SAU khi kiem() tra OK.
     *
     * Mot transaction cho ca bon viec: cap nhat chung tu, dung lai ban ghi chi tiet, vo
     * hieu chu ky, ghi nhat ky. Hong giua chung ma khong boc transaction se de lai mot
     * chung tu mang noi dung MOI nhung ban ghi chi tiet CU - man hinh hien mot dang, thu
     * gui di la dang khac.
     *
     * KHONG chay lai bo kiem o day: CheckCtdtJob tu mo transaction cua no. Noi goi chay sau
     * khi ham nay tra ve.
     *
     * @param  CtdtChungTu       $chungTu
     * @param  \SimpleXMLElement $xml       Ban da qua kiem()
     * @param  string            $noiDung   Van ban nguoi dung go, luu nguyen van
     * @param  string|null       $nguoiSua  loginname
     * @return CtdtLichSuSua
     */
    public static function ap(CtdtChungTu $chungTu, \SimpleXMLElement $xml, $noiDung, $nguoiSua = null)
    {
        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);

        return DB::transaction(function () use ($chungTu, $xml, $noiDung, $nguoiSua, $lop) {
            $hoSo = $chungTu->hoSo;
            $truoc = (string) $chungTu->noi_dung_goc;

            $nhatKy = CtdtLichSuSua::create([
                'ma_ho_so'      => $hoSo ? $hoSo->ma_ho_so : null,
                'ma_chung_tu'   => $chungTu->ma_chung_tu,
                'loai_ho_so'    => $chungTu->loai_ho_so,
                'chung_tu_id'   => $chungTu->id,
                'noi_dung_truoc' => $truoc,
                'noi_dung_sau'  => $noiDung,
                'nguoi_sua'     => $nguoiSua,
                'ma_gd_luc_sua' => $hoSo ? $hoSo->ma_gd : null,
            ]);

            // Luu asXML() chu khong luu $noiDung tho: CtdtLuuHoSo::ghiChungTu() cung luu
            // bang asXML(), va CtdtPhongBi cat khai bao <?xml theo dung dang do. Luu van ban
            // tho se lam hai duong nap sinh ra hai dang khac nhau cho cung mot noi dung.
            $chungTu->update(array_merge($lop::rutGon($xml), [
                'ma_chung_tu'  => $lop::maChungTu($xml),
                'noi_dung_goc' => $xml->asXML(),
            ]));

            self::dungLaiChiTiet($chungTu, $xml, $lop);

            if ($hoSo !== null) {
                self::vuHieuChuKy($hoSo, $nguoiSua);
            }

            return $nhatKy;
        });
    }

    /**
     * Xoa ban ghi chi tiet cu roi dung lai tu XML moi.
     *
     * Dung lai NGUYEN BAN cach cua CtdtLuuHoSo::ghiChungTu(): the co trong XML ma khong khai
     * trong truong() bi bo qua co chu dich - BHXH them the moi truoc khi ta kip cap nhat la
     * chuyen se xay ra.
     */
    private static function dungLaiChiTiet(CtdtChungTu $chungTu, \SimpleXMLElement $xml, $lop)
    {
        $tenModel = $lop::model();

        $tenModel::where('chung_tu_id', $chungTu->id)->delete();

        $chiTiet = ['chung_tu_id' => $chungTu->id];

        foreach ($lop::truong() as $the => $cot) {
            if (!isset($xml->{$the})) {
                continue;
            }

            $chiTiet[$cot] = (string) $xml->{$the};
        }

        $tenModel::create($chiTiet);
    }

    /**
     * Chu ky cu khong con noi ve noi dung nay nua.
     *
     * GIU NGUYEN ma_gd, ma_ket_qua, thoi_gian_tiep_nhan. Duong NAP LAI xoa chung, va dieu do
     * tao ra dung cai bay module dang phai canh bao bang mot hop thoai: ho so cong DA nhan
     * lai hien "Chua ky so", khong con dau vet nao ngoai lich_su_gui. O day giu lai thi trang
     * thai van la "Da gui" - dung su that - va nut hien "Ky va gui lai".
     */
    private static function vuHieuChuKy($hoSo, $nguoiSua)
    {
        $dong = '[' . now()->format('Y-m-d H:i:s') . '] sua XML goc'
            . ($nguoiSua ? ' boi ' . $nguoiSua : '')
            . '; chu ky cu da bi vo hieu';

        $hoSo->update([
            'is_signed'       => false,
            'sign_method'     => null,
            'signed_at'       => null,
            'signed_error'    => null,
            'duong_dan_da_ky' => null,
            'lich_su_gui'     => empty($hoSo->lich_su_gui) ? $dong : $hoSo->lich_su_gui . "\n" . $dong,
        ]);
    }

    /**
     * Chan DOCTYPE - chot AN TOAN, khong phai kiem tra hinh thuc.
     *
     * Noi dung nay do NGUOI DUNG go. Mot khai bao dang
     *   <!DOCTYPE x [<!ENTITY e SYSTEM "file:///c:/windows/win.ini">]>
     * se lam bo phan giai doc tep cua may chu, nhet vao chung tu, roi phan mem KY SO va GUI
     * NOI DUNG DO LEN CONG BHXH. Do la ro ri du lieu ra ben ngoai, khong chi la loi cuc bo.
     *
     * KHONG dua vao libxml_disable_entity_loader(): ham do bi danh dau lac hau tu PHP 8.0, va
     * hanh vi mac dinh khac nhau giua cac ban libxml. Chan o MUC VAN BAN thi dung cho moi ban.
     * Cung chan luon kieu "billion laughs" (entity long nhau lam no bo nho).
     */
    private static function coDoctype($noiDung)
    {
        return (bool) preg_match('/<!DOCTYPE/i', $noiDung);
    }

    /**
     * Parse, thu gom thong diep loi cua libxml de tra lai cho nguoi go.
     *
     * LIBXML_NONET chan truy cap mang khi phan giai. KHONG dung LIBXML_NOENT - co do BAT
     * viec thay the entity, tuc lam dung dieu coDoctype() vua chan.
     *
     * @return array ['xml' => SimpleXMLElement|false, 'loi' => string]
     */
    private static function doc($noiDung)
    {
        $truoc = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($noiDung, 'SimpleXMLElement', LIBXML_NONET);

        $thongDiep = [];

        foreach (libxml_get_errors() as $loi) {
            $thongDiep[] = 'dòng ' . $loi->line . ': ' . trim($loi->message);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($truoc);

        return [
            'xml' => $xml,
            // Chi lay ba thong diep dau: mot the thieu dau dong se sinh hang chuc dong loi
            // noi tiep nhau, va dan het ra man hinh thi nguoi go khong tim duoc cai dau tien.
            'loi' => implode('; ', array_slice($thongDiep, 0, 3)),
        ];
    }

    /** Ly do doc duoc cho nguoi bam nut, khong phai ma trang thai. */
    public static function lyDo($ma, $chiTiet = '')
    {
        $ly = [
            self::RONG         => 'Nội dung XML đang để trống.',
            self::QUA_LON      => 'Nội dung vượt quá ' . number_format(self::TOI_DA_BYTE) . ' byte.',
            self::CO_DOCTYPE   => 'Nội dung chứa khai báo DOCTYPE — không được phép vì lý do an toàn. '
                . 'Hãy xoá khai báo đó đi, chứng từ không cần tới nó.',
            self::KHONG_PARSE  => 'XML không hợp lệ, không đọc được.',
            self::THE_GOC_LECH => 'Thẻ gốc không đúng loại chứng từ đang sửa. Không thể đổi loại chứng từ '
                . 'bằng cách sửa XML — muốn đổi loại thì nạp lại hồ sơ.',
            self::DOI_KHOA     => 'Không được đổi mã định danh của chứng từ. Đổi mã sẽ làm lần nạp sau '
                . 'tạo ra một hồ sơ thứ hai thay vì ghi đè hồ sơ này.',
            self::LOAI_LA      => 'Loại chứng từ này phần mềm chưa biết, không sửa được.',
        ];

        $cau = isset($ly[$ma]) ? $ly[$ma] : 'Nội dung XML chưa hợp lệ.';

        return $chiTiet === '' ? $cau : $cau . ' (' . $chiTiet . ')';
    }

    private static function ket($ma, $xml = null, $chiTiet = '')
    {
        return ['ma' => $ma, 'xml' => $xml, 'chi_tiet' => $chiTiet];
    }
}
