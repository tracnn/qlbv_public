<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Doc mot goi XML chung tu dien tu va chuan hoa BA DICH VU ve cung mot dang.
 *
 * Ba dich vu co cau truc khac han nhau: HSCHUNGTU long ba tang (DANHSACHHOSO > HOSO >
 * FILEHOSO, noi dung base64), con HSDLGBT/HSDLGCS phang mot tang va noi dung nam thang
 * trong the. Neu de su khac biet do lot xuong CtdtImporter thi importer se co ba nhanh
 * if lon - va moi lan BHXH them mot dich vu la them mot nhanh. Chuan hoa o day, importer
 * chi thay MOT dang duy nhat.
 *
 * Ham THUAN tren chuoi/SimpleXML: khong doc CSDL, khong ghi file, test khong can gi ngoai
 * Laravel de doc config('ctdt.dich_vu').
 */
class CtdtGoiParser
{
    /**
     * @throws GoiKhongDocDuocException khi chuoi khong parse duoc
     */
    public static function doc($noiDungXml)
    {
        // simplexml_load_string phat warning voi chuoi hong. De nguyen thi warning do chui
        // vao storage/logs dung dinh dang mot su co that, va nguoi doc log mat cong dieu
        // tra mot loi khong ton tai. Tat di va tu bao loi.
        // trim() TRUOC khi parse: simplexml_load_string chiu duoc BOM UTF-8 nhung FAIL voi
        // khoang trang hoac newline dung truoc <?xml - va tep nguoi dung tai len rat de
        // dinh dieu do.
        $truocDo = libxml_use_internal_errors(true);
        $goi = @simplexml_load_string(trim((string) $noiDungXml));
        libxml_clear_errors();
        libxml_use_internal_errors($truocDo);

        if ($goi === false) {
            throw new GoiKhongDocDuocException('Khong doc duoc noi dung XML cua goi');
        }

        return $goi;
    }

    /**
     * @return string 'CT2025' | 'GBT' | 'GCS'
     * @throws GoiKhongDocDuocException khi the goc khong thuoc dich vu nao
     */
    public static function nhanDienDichVu(\SimpleXMLElement $goi)
    {
        $theGoc = $goi->getName();

        foreach ((array) config('ctdt.dich_vu') as $ma => $cauHinh) {
            if (isset($cauHinh['the_goc']) && $cauHinh['the_goc'] === $theGoc) {
                return $ma;
            }
        }

        // Doan nghia la mot dinh dang khac cua BHXH bi nap vao SAI DICH VU va gui toi SAI
        // endpoint, ma khong co dau hieu gi.
        throw new GoiKhongDocDuocException(
            'The goc <' . $theGoc . '> khong thuoc dich vu nao khai trong config ctdt.dich_vu'
        );
    }

    /**
     * Ma co so KCB khai TRONG GOI.
     *
     * GCS khong mang ma co so o bat ky the nao (MA_TTDV la ma so BHXH cua Thu truong co so,
     * theo muc 6 phan IV cua PL02 - ma cua mot con nguoi). Tra null de CtdtImporter lui ve
     * nguon khac, thay vi bia mot gia tri.
     *
     * @return string|null
     */
    public static function macskcb(\SimpleXMLElement $goi, $dichVu)
    {
        if ($dichVu === 'CT2025') {
            return self::chuoi($goi->THONGTINDONVI, 'MACSKCB');
        }

        if ($dichVu === 'GBT') {
            return self::chuoi($goi->GIAYBAOTU, 'MACSKCB');
        }

        return null;
    }

    /**
     * So luong ho so KHAI BAO trong goi.
     *
     * Phai doc bang (int)(string). count() tren mot node SimpleXML dem so phan tu CON nen
     * LUON tra 1 bat ke gia tri that - loi da tung co trong XML3176.
     */
    public static function soLuongHoSo(\SimpleXMLElement $goi)
    {
        if (!isset($goi->THONGTINHOSO->SOLUONGHOSO)) {
            return 0;
        }

        return (int) (string) $goi->THONGTINHOSO->SOLUONGHOSO;
    }

    /** @return string|null Chi HSCHUNGTU co NGAYLAP */
    public static function ngayLap(\SimpleXMLElement $goi)
    {
        return self::chuoi($goi->THONGTINHOSO, 'NGAYLAP');
    }

    /**
     * Thuoc tinh Id cua the mang chu ky - duong lui cuoi cung cho khoa ho so.
     *
     * @return string|null
     */
    public static function idGoi(\SimpleXMLElement $goi, $dichVu)
    {
        $the = null;

        if ($dichVu === 'CT2025' && isset($goi->THONGTINHOSO)) {
            $the = $goi->THONGTINHOSO;
        } elseif ($dichVu === 'GBT' && isset($goi->GIAYBAOTU)) {
            $the = $goi->GIAYBAOTU;
        } elseif ($dichVu === 'GCS' && isset($goi->GIAYCHUNGSINH)) {
            $the = $goi->GIAYCHUNGSINH;
        }

        if ($the === null || !isset($the['Id'])) {
            return null;
        }

        $id = trim((string) $the['Id']);

        return $id === '' ? null : $id;
    }

    /**
     * Doc mot the con ve chuoi hoac null. Chuoi rong va "khong khai" phai cho cung ket qua
     * o day: ca hai deu nghia la khong co gia tri de dung.
     *
     * @return string|null
     */
    private static function chuoi($cha, $ten)
    {
        if ($cha === null || !isset($cha->{$ten})) {
            return null;
        }

        $giaTri = trim((string) $cha->{$ten});

        return $giaTri === '' ? null : $giaTri;
    }

    /**
     * Chuan hoa ca ba dich vu ve CUNG MOT dang. KHONG parse noi dung tai day - chi tach
     * khung. 'noi_dung' la CHUOI XML chua parse; goi phanTichChungTu() cho tung ho so o
     * BEN TRONG transaction/try rieng cua ho so do (xem CtdtImporter::nhapMotHoSo()).
     *
     * VI SAO HOAN PARSE: neu ham nay tu giai base64 va parse ngay, mot NOIDUNGFILE hong o
     * ho so #2 se nem ngay tai day - truoc khi vong lap per-ho-so cua importer (moi ho so
     * mot transaction rieng) kip chay - va keo CA TEP bi tu choi thay vi chi ho so do.
     *
     * @return array Mang cac HOSO; moi HOSO la mang cac
     *               ['loai_ho_so' => string, 'noi_dung' => string (XML CHUA PARSE)],
     *               GIU DUNG thu tu xuat hien trong tep.
     */
    public static function danhSachHoSo(\SimpleXMLElement $goi, $dichVu)
    {
        if ($dichVu === 'CT2025') {
            return self::hoSoCuaCt2025($goi);
        }

        return self::hoSoCuaGoiPhang($goi, $dichVu === 'GBT' ? 'GIAYBAOTU' : 'GIAYCHUNGSINH');
    }

    /**
     * Parse noi dung CHUOI XML cua tung chung tu trong MOT ho so thanh \SimpleXMLElement.
     *
     * Goi rieng cho tung ho so, BEN TRONG try/transaction cua ho so do, de mot NOIDUNGFILE
     * hong chi lam HO SO DO that bai chu khong keo ca tep.
     *
     * @param array $chungTu   Mang ['loai_ho_so' =>, 'noi_dung' => string] cua MOT ho so
     * @param int   $chiSoHoSo Vi tri ho so trong tep, dem tu 1 - de thong diep loi neu ro
     * @return array Cung cau truc, 'noi_dung' da la \SimpleXMLElement
     * @throws GoiKhongDocDuocException khi mot phan tu khong parse duoc
     */
    public static function phanTichChungTu(array $chungTu, $chiSoHoSo)
    {
        $ketQua = [];

        foreach ($chungTu as $ct) {
            try {
                $noiDung = self::doc($ct['noi_dung']);
            } catch (GoiKhongDocDuocException $e) {
                throw new GoiKhongDocDuocException(
                    'Ho so #' . $chiSoHoSo . ', FILEHOSO ' . $ct['loai_ho_so']
                    . ': khong doc duoc noi dung'
                );
            }

            $ketQua[] = ['loai_ho_so' => $ct['loai_ho_so'], 'noi_dung' => $noiDung];
        }

        return $ketQua;
    }

    /**
     * HSCHUNGTU: DANHSACHHOSO > nhieu HOSO > nhieu FILEHOSO, noi dung base64.
     */
    private static function hoSoCuaCt2025(\SimpleXMLElement $goi)
    {
        if (!isset($goi->THONGTINHOSO->DANHSACHHOSO->HOSO)) {
            return [];
        }

        $ketQua = [];

        // PHAI foreach tren tap. Truy cap ->HOSO->FILEHOSO tren mot tap nhieu phan tu se
        // TU LAY PHAN TU DAU va bo im lang cac ho so con lai - loi da tung co that.
        foreach ($goi->THONGTINHOSO->DANHSACHHOSO->HOSO as $hoSo) {
            $chungTu = [];

            if (isset($hoSo->FILEHOSO)) {
                foreach ($hoSo->FILEHOSO as $file) {
                    $loai = trim((string) $file->LOAIHOSO);
                    $noiDung = base64_decode((string) $file->NOIDUNGFILE);

                    $chungTu[] = ['loai_ho_so' => $loai, 'noi_dung' => $noiDung];
                }
            }

            $ketQua[] = $chungTu;
        }

        return $ketQua;
    }

    /**
     * HSDLGBT / HSDLGCS: mot the con duy nhat, noi dung nam thang trong the, KHONG base64.
     *
     * Van tra ve dang long hai tang de importer chi biet MOT dang.
     */
    private static function hoSoCuaGoiPhang(\SimpleXMLElement $goi, $tenThe)
    {
        if (!isset($goi->{$tenThe})) {
            return [[]];
        }

        return [[['loai_ho_so' => $tenThe, 'noi_dung' => $goi->{$tenThe}->asXML()]]];
    }
}
