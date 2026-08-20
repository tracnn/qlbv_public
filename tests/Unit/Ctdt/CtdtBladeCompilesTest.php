<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;

class CtdtBladeCompilesTest extends TestCase
{
    /**
     * Bien dich chuoi Blade roi kiem ket qua co phai PHP hop le khong.
     *
     * token_get_all(..., TOKEN_PARSE) nem ParseError khi ma khong hop le - chinh la
     * phep kiem ta can, khong phai goi `php -l` qua exec nen khong phu thuoc moi truong.
     *
     * @return string|null Thong diep loi, null neu hop le
     */
    private function loiBienDich($nguon)
    {
        $compiled = app('blade.compiler')->compileString($nguon);

        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            return $e->getMessage();
        }

        return null;
    }

    /** @test */
    public function moi_blade_ctdt_bien_dich_ra_php_hop_le()
    {
        $viPham = [];

        $thuMuc = [
            resource_path('views/bhyt/ctdt'),
            resource_path('views/bhyt/ctdt/partials'),
        ];

        foreach ($thuMuc as $duong) {
            foreach (glob($duong . '/*.blade.php') as $file) {
                $loi = $this->loiBienDich(file_get_contents($file));

                if ($loi !== null) {
                    $viPham[] = basename($file) . ': ' . $loi;
                }
            }
        }

        $this->assertEmpty($viPham, "Blade khong bien dich duoc:\n" . implode("\n", $viPham));
    }

    /** @test */
    public function phep_kiem_bat_duoc_chi_thi_if_khong_ngoac_nam_trong_comment_javascript()
    {
        // Chung minh phep kiem tren khong rong: day dung la loi da lam vo man chi tiet
        // cua module CTDT (detail.blade.php) trong nhieu ngay ma khong ai biet, vi khong
        // test nao dung toi tep do. DUNG xoa test nay di vi tuong no la ca gia:
        //
        // Mot chi thi "@" + tu khoa KHONG NGOAC (vi du @if) nam trong mot dong chu thich
        // JavaScript (// ...) o BEN TRONG khoi <script> van bi trinh bien dich Blade doc
        // ra va thay the, vi Blade khong biet gi ve cu phap JavaScript - no chi tim chuoi
        // "@if" trong toan bo nguon. Ket qua la mot @if khong co @endif tuong ung sinh ra
        // PHP hong, va CA TRANG nem Parse error, khong render duoc dong nao.
        $hong = "<script>\n"
              . "    // TODO: @if con loi thi doi mau nut\n"
              . "    var x = 1;\n"
              . "</script>\n";

        $this->assertNotNull(
            $this->loiBienDich($hong),
            'Phep kiem khong bat duoc @if trong chu thich JavaScript - no dang vo dung'
        );

        // Doi chung: bo chi thi "@if" khoi chu thich thi cung doan do bien dich binh thuong.
        $lanh = "<script>\n"
              . "    // TODO: con loi thi doi mau nut\n"
              . "    var x = 1;\n"
              . "</script>\n";

        $this->assertNull($this->loiBienDich($lanh));
    }
}
