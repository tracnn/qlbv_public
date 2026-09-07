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
 *
 * Danh sach mo rong ngay 2026-08-20 theo cong van 2076/BHXH-CNTT PL02, muc 3.2-3.6. Nguyen
 * tac chia hai muc: DO TRUOC tren du lieu that, truong nao rong 0% thi chan duoc ngay ma
 * khong khoa lai ho so nao; truong nao con thieu mot phan (CT04: MA_DANTOC rong 6/1050,
 * PP_DIEUTRI rong 79/1050) hoac chua co du lieu de doi chieu (CT06 chua co chung tu nao) thi
 * canh bao truoc - nguoi van hanh thay de di sua nguon, nhung ho so van gui duoc.
 *
 * LUON DO TRUOC khi them mot truong bat buoc. MA_YTE tung duoc them ma khong do, va no chan
 * 97% ho so trong nhieu ngay.
 *
 * ---------------------------------------------------------------------------------------
 * GIAY RA VIEN (CT03) - dot siet 2026-09-07
 *
 * CAN CU KHAC HAN cac dot truoc: khong phai doc cong van, ma la CONG BHXH DA TU CHOI mot ho
 * so vi thieu PP_DIEUTRI. Cong van 2076 KHONG danh dau truong nay bat buoc o CT03 - neu chi
 * doc cong van thi khong bao gio them no. Cong tu choi la can cu manh hon cong van.
 *
 * PP_DIEUTRI CHAN NGAY du do duoc RONG 78/1050 (7.4%). Day la lan dau mot truong duoc dua
 * thang len muc chan trong khi du lieu that con thieu, va la CO Y: 78 ho so do se chuyen
 * sang "Con loi chan" va khong gui duoc nua cho toi khi phan mem sinh XML dien du roi nap
 * lai. Do chinh la ket qua mong muon - chung la nhung ho so cong se tu choi.
 *
 * VI SAO CT04 CUNG TRUONG DO VAN CHI CANH BAO: cong tu choi GIAY RA VIEN (CT03), khong phai
 * tom tat ho so benh an (CT04) - hai bieu mau khac nhau, va CT04 chua co bang chung nao. Su
 * khong nhat quan nay la CO Y. Dung "sua cho nhat quan" o lan ra soat sau: nang CT04 len
 * chan se khoa them 78 ho so ma khong co can cu gi.
 *
 * MUOI TRUONG THEM, do 2026-09-07 tren 1050 CT03 that:
 *   PP_DIEUTRI          rong  78 (7.4%)  <- chan ngay, cong da tu choi
 *   CHAN_DOAN           rong   0 (0%)
 *   BENHICD10_ID        rong   0 (0%)
 *   TENBENHICD10        rong   0 (0%)
 *   NGAY_CHUNG_TU       rong   0 (0%)
 *   THU_TRUONG_DVI      rong   0 (0%)
 *   TEN_TRUONGKHOA      rong   0 (0%)
 *   MA_CCHN_TRUONGKHOA  rong   0 (0%)
 *   LOAI_GIAYTO         rong   0 (0%)
 *   NGHE_NGHIEP         rong   0 (0%)
 *
 * Chin truong sau do duoc 0% nen chan khong khoa them ho so nao. LOAI_GIAYTO truoc do da co
 * CTDT004 kiem GIA TRI co hop le khong, nhung khong ai kiem no RONG - day la bit not ke ho.
 *
 * LUU Y VAN HANH: module nay KHONG co duong kiem lai. CheckCtdtJob chi duoc day tu
 * CtdtImporter luc nap, nen luat moi CHI bam vao ho so nap tu day tro di. 78 ho so cu van
 * giu so_loi cu va van gui duoc, cho toi khi duoc nap lai.
 */
class CtdtTruongBatBuoc
{
    /** @var array LOAIHOSO => danh sach the bat buoc (muc chan) */
    const BAT_BUOC = [
        'CT03'              => ['MA_BHXH', 'MA_KHOA', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH',
                                'DIA_CHI', 'NGAY_VAO', 'NGAY_RA',
                                // Dot 2026-09-07 - xem docblock muc "Giay ra vien".
                                'PP_DIEUTRI', 'CHAN_DOAN', 'BENHICD10_ID', 'TENBENHICD10',
                                'NGAY_CHUNG_TU', 'THU_TRUONG_DVI', 'TEN_TRUONGKHOA',
                                'MA_CCHN_TRUONGKHOA', 'LOAI_GIAYTO', 'NGHE_NGHIEP'],
        'CT04'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI',
                                'NGAY_VAO', 'NGAY_RA', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RA',
                                'QT_BENHLY', 'TOMTAT_KQ', 'TT_RAVIEN', 'NGAY_CT'],
        'CT06'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT07'              => ['MA_BHXH', 'SO_KCB', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH',
                                'DON_VI', 'CHANDOAN_DIEUTRI', 'TU_NGAY', 'DEN_NGAY',
                                'MA_CCHN', 'TEN_NGUOI_HANH_NGHE', 'TEKT'],
        'GIAYDIEUTRINOITRU' => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYDIEUTRIVOSINH' => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYSUCKHOEME'     => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYBAOTU'         => ['MA_GBT', 'HO_TEN', 'NGAY_SINH', 'NGAY_TV'],
        'GIAYCHUNGSINH'     => ['MA_GCS', 'MA_BHXH_NND', 'HOTEN_NND', 'NGAYSINH_NND', 'NGAY_SINH_CON'],
    ];

    /**
     * LOAIHOSO => danh sach the khuyen nghi (muc canh bao).
     *
     * MA_THE tung nam o day. Da go: rat nhieu benh nhan khong co the BHYT (tu tra, the het
     * han, tre chua duoc cap the - TEKT = 1), va cong van 2076 khong danh dau MA_THE bat buoc
     * o loai nao. Canh bao tren mot tinh huong BINH THUONG khong phai canh bao - no la tieng
     * on, va no day nguoi van hanh toi cho bo qua ca cot so loi. Cung mot ly le da dung khi
     * go MA_YTE.
     *
     * TANG NAY GIO DA CO NOI DUNG: CT04 (MA_DANTOC rong 6/1050, PP_DIEUTRI rong 79/1050) va
     * CT06 (chua co mot chung tu nao de doi chieu) duoc canh bao truoc roi moi chan - siet
     * thang len muc chan se dong loat khoa lai nhung ho so dang gui duoc.
     *
     * So truong con thieu so voi cong van 2076 sau dot 2026-08-20: CT04 con 1, CT07 con 1.
     *
     * @var array
     */
    const KHUYEN_NGHI = [
        'CT03'              => [],
        'CT04'              => ['MA_DANTOC', 'PP_DIEUTRI'],
        'CT06'              => ['SO_KCB', 'TEN_DVI', 'CHAN_DOAN', 'TEN_BS', 'MA_BS', 'NGAY_CT'],
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
