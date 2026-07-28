<?php

namespace Tests\Support;

/**
 * Bo comment khoi ma nguon PHP truoc khi quet chuoi.
 *
 * VI SAO CAN: cac test canh gac quet ma nguon de tim (hoac cam) mot chuoi nao do. Neu
 * khong bo comment thi chinh CAU CHU THICH GIAI THICH viec sua lai lam test do - da xay
 * ra ba lan trong mot ngay voi 'return false', '4096M' va 'deleteErrors'.
 *
 * Nguy hiem hon la chieu nguoc lai: test kiem su TON TAI cua mot chuoi (vi du 'throw',
 * 'DB::transaction') co the XANH NHAM chi vi chuoi do nam trong mot dong comment, trong
 * khi ma that su khong co. Loi kieu do khong ai phat hien duoc.
 */
trait LocComment
{
    /**
     * @param string $duongDan Duong dan file PHP
     * @return string Ma nguon da bo moi comment
     */
    protected function maKhongComment(string $duongDan): string
    {
        return $this->boComment(file_get_contents($duongDan));
    }

    protected function boComment(string $nguon): string
    {
        $ma = '';

        foreach (token_get_all($nguon) as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                $ma .= $token[1];
            } else {
                $ma .= $token;
            }
        }

        return $ma;
    }
}
