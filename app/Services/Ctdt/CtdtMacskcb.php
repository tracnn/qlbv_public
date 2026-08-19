<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\MacskcbKhongHopLeException;

/**
 * Phan giai ma co so KCB cho mot goi chung tu, theo ba bac.
 *
 * VI SAO LA LOP RIENG CONG KHAI chu khong phai ham private trong CtdtImporter: man nap tep
 * can hien "goi nay se dung ma co so X, dung khong?" TRUOC khi ghi. De logic trong importer
 * thi man hinh phai nhan ban no, va hai ban se lech nhau - dung cai benh ma module nay
 * sinh ra de tranh.
 *
 * VI SAO CAN BAC THU BA: goi HSDLGCS (giay chung sinh) KHONG mang ma co so o bat ky the nao.
 * The MA_TTDV trong giong nhung muc 6 phan IV cua PL02 dinh nghia la "ma so BHXH cua Thu
 * truong co so KBCB" - ma cua mot CON NGUOI.
 */
class CtdtMacskcb
{
    /** Do dai toi da, khop cot ctdt_ho_so.macskcb varchar(5). */
    const DAI_TOI_DA = 5;

    /**
     * @param string|null $maTrongGoi  Gia tri doc duoc tu XML (CtdtGoiParser::macskcb)
     * @param string|null $maNguoiChon Gia tri nguoi nap chon tren man hinh
     * @return string
     * @throws ThieuMacskcbException khi can ca ba nguon
     * @throws MacskcbKhongHopLeException khi dai qua gioi han cot
     */
    public static function phanGiai($maTrongGoi, $maNguoiChon)
    {
        $ma = trim((string) $maTrongGoi);

        if ($ma === '') {
            $ma = trim((string) $maNguoiChon);
        }

        if ($ma === '') {
            $ma = trim((string) config('organization.BHYT.ma_cskcb', ''));
        }

        if ($ma === '') {
            throw new ThieuMacskcbException(
                'Khong xac dinh duoc ma co so KCB: goi khong khai, nguoi nap khong chon,'
                . ' va cau hinh organization.BHYT.ma_cskcb dang trong'
            );
        }

        // SQLite khong cuong che do dai nen test khong bao gio bat duoc. Tren MySQL strict
        // thi QueryException KHONG mang CtdtLoiNap nen thoat khoi catch cua importer va do
        // ca lan nap; MySQL long thi cat cut IM LANG va ho so duoc gui len cong voi ma co
        // so SAI. Chan tai day.
        if (strlen($ma) > self::DAI_TOI_DA) {
            throw new MacskcbKhongHopLeException(
                'Ma co so KCB "' . $ma . '" dai ' . strlen($ma) . ' ky tu, toi da '
                . self::DAI_TOI_DA
            );
        }

        return $ma;
    }
}
