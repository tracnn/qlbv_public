<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176BladeCompilesTest extends TestCase
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
    public function moi_blade_xml3176_bien_dich_ra_php_hop_le()
    {
        $viPham = [];

        foreach (glob(resource_path('views/bhyt/xml3176') . '/*.blade.php') as $file) {
            $loi = $this->loiBienDich(file_get_contents($file));

            if ($loi !== null) {
                $viPham[] = basename($file) . ': ' . $loi;
            }
        }

        $this->assertEmpty($viPham, "Blade khong bien dich duoc:\n" . implode("\n", $viPham));
    }

    /** @test */
    public function phep_kiem_bat_duoc_chi_thi_php_nam_trong_comment_blade()
    {
        // Chung minh phep kiem tren khong rong: day dung la loi da lam vo man chi tiet.
        //
        // compileString() chay storePhpBlocks() TRUOC khi xu ly comment. Ham do khop
        // @php ... @endphp khong tham lam, nen neu chuoi "@php" nam trong mot comment
        // Blade thi no duoc lay lam diem mo, va @endphp that su o phia duoi lam diem
        // dong. Ca doan giua bi thay bang mot khoi raw -> dau dong --}} cua comment
        // bien mat, phan {{-- con lai bi bien dich thanh lenh echo.
        $hong = "{{-- ghi chu co @php ben trong --}}\n"
              . "@php\n"
              . "    \$x = 1;\n"
              . "@endphp\n";

        $this->assertNotNull(
            $this->loiBienDich($hong),
            'Phep kiem khong bat duoc @php trong comment Blade - no dang vo dung'
        );

        // Doi chung: bo chu "@php" khoi comment thi cung file do bien dich binh thuong.
        $lanh = "{{-- ghi chu khong co chi thi nao --}}\n"
              . "@php\n"
              . "    \$x = 1;\n"
              . "@endphp\n";

        $this->assertNull($this->loiBienDich($lanh));
    }
}
