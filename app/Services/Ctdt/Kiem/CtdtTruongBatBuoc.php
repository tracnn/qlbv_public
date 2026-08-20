<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Truong bat buoc va truong khuyen nghi, theo tung loai chung tu.
 *
 * VI SAO KHONG LAY TU DAC TA: PL02 chi danh dau cot "Bat buoc" cho THAM SO API (muc 2),
 * con cac bang mo ta the cua CT03/CT04/... (muc 9.2, 10.2...) khong co cot do. Danh sach
 * duoi day la quyet dinh nghiep vu, da duoc chu du an duyet.
 *
 * NGUYEN TAC: chi dua vao muc BAT BUOC nhung truong ma thieu la ho so vo nghia hoac cong
 * chac chan tu choi. Bat buoc rong tay se sinh mot bien lo, va nguoi van hanh se hoc cach
 * bo qua ca cot so loi.
 *
 * MA_YTE KHONG bat buoc o loai nao. Cong van 2076/BHXH-CNTT, Phu luc 02, muc 3.4 (bang
 * truong cua CT03) de TRONG cot "Bat buoc" cho no, va dien giai ghi ro: "Ma y te dinh danh
 * chung tu cua cskcb, de trong de he thong BHXH tu sinh (chi nen su dung 1 cach)".
 *
 * Truoc day ta bat buoc no. Doi chieu du lieu that: 1049/1050 CT03 va 923/923
 * GIAYDIEUTRINOITRU deu co the MA_YTE nhung gia tri RONG - tuc phan mem sinh XML lam dung,
 * quy tac cua ta sai, va no chan 97% ho so. Lan gui that dau tien xac nhan: MaGD cong tra ve
 * la HS_CHUNGTU01929_<GUID>, dung la ma BHXH tu sinh.
 *
 * Cung khong ha xuong muc khuyen nghi: de trong la mot trong hai cach dung hop le, nen canh
 * bao tren gan nhu moi ho so chi lam nguoi van hanh hoc cach bo qua ca cot so loi.
 *
 * MA_BHXH la truong cong van 2076 danh dau bat buoc o MOI loai no phu (CT03, CT04, CT06,
 * CT07, va MA_BHXH_NND o CT05). Voi GIAYDIEUTRINOITRU thi cong van khong phu, nhung 923/923
 * chung tu that deu da co gia tri - du can cu de chan.
 *
 * Ba loai con lai (GIAYDIEUTRIVOSINH, GIAYSUCKHOEME, GIAYBAOTU) chi CANH BAO: cong van khong
 * phu, va CSDL chua co mot chung tu nao de doi chieu. Rieng giay bao tu con mot kha nang
 * that - nguoi qua doi co the khong co ma so BHXH. Nang len muc chan khi du lieu that xac
 * nhan, dung doan mo.
 *
 * MA_THE KHONG bat buoc va cung KHONG khuyen nghi: rat nhieu benh nhan khong co the BHYT
 * (tu tra, the het han, tre chua duoc cap the - TEKT = 1), va cong van 2076 khong danh dau
 * no bat buoc o loai nao. Xem chu thich cua KHUYEN_NGHI.
 */
class CtdtTruongBatBuoc
{
    /** @var array LOAIHOSO => danh sach the bat buoc (muc chan) */
    const BAT_BUOC = [
        'CT03'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT04'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT06'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT07'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'TU_NGAY', 'DEN_NGAY'],
        'GIAYDIEUTRINOITRU' => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYDIEUTRIVOSINH' => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYSUCKHOEME'     => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYBAOTU'         => ['MA_GBT', 'HO_TEN', 'NGAY_SINH', 'NGAY_TV'],
        'GIAYCHUNGSINH'     => ['MA_GCS', 'MA_BHXH_NND', 'HOTEN_NND', 'NGAYSINH_NND', 'NGAY_SINH_CON'],
    ];

    /**
     * LOAIHOSO => danh sach the khuyen nghi (muc canh bao).
     *
     * HIEN TRONG O MOI LOAI - co chu dich, khong phai quen dien.
     *
     * MA_THE tung nam o day. Da go: rat nhieu benh nhan khong co the BHYT (tu tra, the het
     * han, tre chua duoc cap the - TEKT = 1), va cong van 2076 khong danh dau MA_THE bat buoc
     * o loai nao. Canh bao tren mot tinh huong BINH THUONG khong phai canh bao - no la tieng
     * on, va no day nguoi van hanh toi cho bo qua ca cot so loi. Cung mot ly le da dung khi
     * go MA_YTE.
     *
     * GIU LAI TANG NAY du dang trong: buoc bo sung cac truong con thieu so voi cong van 2076
     * (CT04 thieu 11 truong, CT06 thieu 7, CT07 thieu 8) nen canh bao truoc roi moi chan -
     * siet thang len muc chan se dong loat khoa lai nhung ho so dang gui duoc.
     *
     * @var array
     */
    const KHUYEN_NGHI = [
        'CT03'              => [],
        'CT04'              => [],
        'CT06'              => [],
        'CT07'              => [],
        'GIAYDIEUTRINOITRU' => [],
        'GIAYDIEUTRIVOSINH' => ['MA_BHXH'],
        'GIAYSUCKHOEME'     => ['MA_BHXH'],
        'GIAYBAOTU'         => ['MA_BHXH'],
        'GIAYCHUNGSINH'     => [],
    ];

    /** @return array Mang rong voi loai la - loai do do CtdtChecker bo qua, khong nem */
    public static function cua($loaiHoSo)
    {
        return isset(self::BAT_BUOC[$loaiHoSo]) ? self::BAT_BUOC[$loaiHoSo] : [];
    }

    /** @return array */
    public static function khuyenNghi($loaiHoSo)
    {
        return isset(self::KHUYEN_NGHI[$loaiHoSo]) ? self::KHUYEN_NGHI[$loaiHoSo] : [];
    }
}
