<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ImportSourceTest extends TestCase
{
    /** @test */
    public function nghiep_vu_phan_loai_xml_chi_con_o_mot_noi()
    {
        // Bang anh xa loai XML chi duoc ton tai trong Xml3176Importer. Hai ban cu tung
        // lech nhau (controller XML1-15, command XML1-18) chinh vi co hai noi biet.
        $noiKhongDuocBiet = [
            app_path('Http/Controllers/BHYT/BHYTXml3176Controller.php'),
            app_path('Console/Commands/XML3176Import.php'),
        ];

        foreach ($noiKhongDuocBiet as $file) {
            $this->assertNotContains(
                "case 'XML",
                file_get_contents($file),
                basename($file) . ' van con khoi switch phan loai XML'
            );
        }
    }

    /**
     * Bo comment khoi ma nguon PHP.
     *
     * Phai lam vay thi phep kiem duoi moi kiem MA chu khong kiem van xuoi: chinh cau
     * chu thich giai thich viec bo 'return false' cung chua chuoi do.
     */
    private function boComment($src)
    {
        $ma = '';

        foreach (token_get_all($src) as $token) {
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

    /** @test */
    public function vong_lap_quet_thu_muc_khong_con_return_false_giua_chung()
    {
        // 'return false' giua vong lap lam DUNG ca luot quet: moi file xep sau bi bo
        // qua, file hong khong bi xoa, va lenh chay lai moi 3 giay -> tac vinh vien.
        $ma = $this->boComment(file_get_contents(app_path('Console/Commands/XML3176Import.php')));

        $vitri = strpos($ma, 'function importFilesFromDisk');
        $this->assertNotFalse($vitri, 'Khong tim thay importFilesFromDisk');

        $this->assertNotContains(
            'return false',
            substr($ma, $vitri),
            'importFilesFromDisk van con return false - mot file hong se lam tac ca luot quet'
        );
    }

    /** @test */
    public function phep_kiem_bo_comment_that_su_bat_duoc_ma_that()
    {
        // Chung minh phep kiem tren khong rong: chuoi trong comment phai duoc bo qua,
        // con chuoi trong ma thi phai bat duoc.
        $chiComment = "<?php function importFilesFromDisk() { /* return false */ }";
        $this->assertNotContains('return false', $this->boComment($chiComment));

        $trongMa = "<?php function importFilesFromDisk() { return false; }";
        $this->assertContains('return false', $this->boComment($trongMa));
    }
}
