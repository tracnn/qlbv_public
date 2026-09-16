<?php

namespace App\Services\Tt12;

use Illuminate\Support\Facades\Storage;
use App\Models\BHYT\Tt12\Tt12HoSo;

/**
 * Lay noi dung XML cua MOT ho so de tai ve, kem chu ky so neu co.
 *
 * "KEM CHU KY NEU CO" la HAI NGUON KHAC NHAU, khong phai mot:
 *
 *   da ky   -> ban ky nam o TEP TREN DIA (duong_dan_da_ky, disk exportTt12). Day la ban
 *              THAT da gui len cong, chu ky XMLDSig nam san ben trong. Phuc vu nguyen ven.
 *   chua ky -> CHUA HE CO XML NAO TON TAI. Tt12PhongBi::dung() chi duoc goi luc ky. Phai
 *              dung lai tu cac dong trong CSDL, va ban do mang the CHUKYDONVI RONG.
 *
 * DUNG LAI DUNG BA DONG cua SignTt12Job::handle() - khong viet lai cach dung phong bi o
 * day. Nho vay ban chua ky xuat ra KHOP TUNG BYTE voi ban sap duoc ky, mien du lieu khong
 * doi. Viet lai lan hai la mo duong cho hai ban lech nhau, va nguoi doi chieu se thay hai
 * tep khac nhau cho cung mot ho so ma khong hieu vi sao.
 *
 * id_danh_sach LAY TU BAN GHI, tuyet doi khong sinh moi: chu ky XMLDSig tham chieu chinh
 * #Id do - xem chu thich o migration create_tt12_ho_so_table. Sinh moi thi ban xuat khac
 * ban se ky.
 */
class Tt12XuatXml
{
    /** Ban ky that, doc tu tep tren dia */
    const DA_KY = 'da_ky';

    /** Ban dung lai tu du lieu, the CHUKYDONVI rong */
    const CHUA_KY = 'chua_ky';

    /** Khong lay duoc noi dung - xem 'ly_do' */
    const HONG = 'hong';

    /**
     * @param  Tt12HoSo $hoSo
     * @return array ['trang_thai' => hang so tren, 'noi_dung' => string|null,
     *                'ten_tep' => string, 'ly_do' => string]
     */
    public static function cua(Tt12HoSo $hoSo)
    {
        $banKy = self::docBanDaKy($hoSo);

        if ($banKy !== null) {
            return self::ket(self::DA_KY, $banKy, $hoSo);
        }

        // Toi day: hoac ho so chua ky, hoac ho so ghi is_signed nhung TEP DA MAT. Ca hai
        // deu dung lai tu du lieu, va ca hai deu duoc dat ten "-chua-ky".
        //
        // KHONG dat ten "-da-ky" cho ca mat tep: nguoi dung se cam mot ban KHONG CO CHU KY
        // ma tuong la ban da ky. Ly do that duoc ghi vao tep ke de ho biet.
        try {
            $lop = Tt12MauRegistry::cho($hoSo->mau);

            $xml = Tt12PhongBi::dung(
                $lop,
                $hoSo->id_danh_sach,
                (new Tt12DocDong())->choPhongBi($hoSo)
            );
        } catch (\Exception $e) {
            // Tt12PhongBi::dung() nem khi ho so khong co dong nao, hoac thieu id_danh_sach.
            // Mot ho so hong KHONG duoc lam hong ca lo - ghi ly do roi di tiep.
            return self::ket(self::HONG, null, $hoSo, $e->getMessage());
        }

        $lyDo = self::matTep($hoSo)
            ? 'Hồ sơ ghi đã ký nhưng không tìm thấy tệp: ' . $hoSo->duong_dan_da_ky
                . '. Đây là bản dựng lại, KHÔNG có chữ ký.'
            : '';

        return self::ket(self::CHUA_KY, $xml, $hoSo, $lyDo);
    }

    /** @return string|null Noi dung tep da ky, null khi chua ky hoac tep khong con */
    private static function docBanDaKy(Tt12HoSo $hoSo)
    {
        if (empty($hoSo->duong_dan_da_ky)) {
            return null;
        }

        $dia = Storage::disk('exportTt12');

        if (!$dia->exists($hoSo->duong_dan_da_ky)) {
            return null;
        }

        return $dia->get($hoSo->duong_dan_da_ky);
    }

    /** Ho so ghi la da ky nhung tep khong con tren dia */
    private static function matTep(Tt12HoSo $hoSo)
    {
        return !empty($hoSo->duong_dan_da_ky)
            && !Storage::disk('exportTt12')->exists($hoSo->duong_dan_da_ky);
    }

    /**
     * Ten tep PHAI noi ro ban nao.
     *
     * Hai ban khac nhau ve PHAP LY: mot ban mang chu ky so cua don vi, mot ban khong. Nguoi
     * mo ZIP phai phan biet duoc ma khong can mo tung tep ra xem.
     */
    public static function tenTep(Tt12HoSo $hoSo, $trangThai)
    {
        // Ma ho so di vao TEN TEP va vao muc luc ZIP. Loc ky tu khong an toan cho duong dan:
        // mot ma chua '/' hay '..' se tao thu muc trong ZIP hoac thoat ra ngoai khi giai nen.
        //
        // LOAI LUON DAU CHAM. Ma ho so that khong bao gio co dau cham (dang
        // TT12_MAU_01_01929_20260825_001), va phan mo rong .xml do chinh ham nay noi vao -
        // nen giu dau cham chi de lai '..' trong ten tep ma khong duoc loi ich gi.
        $ma = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $hoSo->ma_ho_so);

        return $ma . ($trangThai === self::DA_KY ? '-da-ky' : '-chua-ky') . '.xml';
    }

    private static function ket($trangThai, $noiDung, Tt12HoSo $hoSo, $lyDo = '')
    {
        return array(
            'trang_thai' => $trangThai,
            'noi_dung'   => $noiDung,
            'ten_tep'    => $trangThai === self::HONG ? '' : self::tenTep($hoSo, $trangThai),
            'ly_do'      => $lyDo,
        );
    }

    /** Nhan doc duoc cho tep ke */
    public static function nhan($trangThai)
    {
        $nhan = array(
            self::DA_KY   => 'Đã ký',
            self::CHUA_KY => 'Chưa ký',
            self::HONG    => 'Không xuất được',
        );

        return isset($nhan[$trangThai]) ? $nhan[$trangThai] : (string) $trangThai;
    }
}
