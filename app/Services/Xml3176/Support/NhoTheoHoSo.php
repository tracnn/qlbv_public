<?php

namespace App\Services\Xml3176\Support;

/**
 * Bo nho THEO HO SO cho checker: du lieu cua mot ma_lk nap mot lan, dung cho moi dong cua
 * ho so do trong cung job. Doi ma_lk -> xoa sach va nap lai, nen du lieu ho so truoc khong
 * bao gio ro sang ho so sau (checker co the duoc dung lai qua nhieu ho so).
 *
 * Chi dung cho du lieu KHONG doi trong luc kiem (checker chi doc). Nap loi (ngoai le) thi
 * khong luu gi, ngoai le di tiep nhu truy van cu.
 */
trait NhoTheoHoSo
{
    /** @var string|null ma_lk ma bo nho dang giu */
    private $hoSoMaLk;

    /** @var array khoa => du lieu da nap cho $hoSoMaLk */
    private $hoSoDuLieu = [];

    private function theoHoSo($maLk, $khoa, callable $nap)
    {
        // So sanh chat: ma_lk khac nhau du chi hoa thuong/khoang trang van nap lai - truy van
        // nap dung chinh ma_lk do nen ket qua y nhu truy van cu.
        if ($this->hoSoMaLk !== $maLk) {
            $this->hoSoMaLk = $maLk;
            $this->hoSoDuLieu = [];
        }

        if (!array_key_exists($khoa, $this->hoSoDuLieu)) {
            $this->hoSoDuLieu[$khoa] = $nap();
        }

        return $this->hoSoDuLieu[$khoa];
    }

    /**
     * Khoa so sanh chuoi theo ngu nghia '=' cua MySQL (collation *_general_ci, PAD SPACE):
     * khong phan biet hoa thuong, bo khoang trang CUOI (chi dau cach, khong bo tab/xuong dong).
     * Khac biet con lai: general_ci con coi chu co dau = khong dau; ma DVKT la ASCII nen khong anh huong.
     */
    private static function khoaChuoiSql($s)
    {
        return mb_strtoupper(rtrim((string) $s, ' '), 'UTF-8');
    }
}
