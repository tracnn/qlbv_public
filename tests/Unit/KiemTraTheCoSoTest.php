<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh hai lan quay lai loi cu:
 *  - lenh quet lay ma co so tu tdl_hein_medi_org_code (noi DKBD cua benh nhan) thay vi tu
 *    his_branch (co so dieu tri) - do duoc 99,5% loi goi khai sai co so;
 *  - phep so o job dung maCSKCB thay vi maDkbd.
 */
class KiemTraTheCoSoTest extends TestCase
{
    use LocComment;

    protected function maLenh()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Console/Commands/HISProKiemTraTheBHYT.php')
        );
    }

    protected function maJob()
    {
        return $this->maKhongComment(base_path('app/Jobs/jobKtTheBHYT.php'));
    }

    protected function maNhapXml()
    {
        return $this->maKhongComment(base_path('app/Console/Commands/XML4210Import.php'));
    }

    /** @test */
    public function lenh_quet_join_his_branch()
    {
        $ma = $this->maLenh();

        $this->assertContains('his_branch', $ma, 'Lenh quet khong con join his_branch');
        $this->assertContains('hein_medi_org_code', $ma, 'Khong lay ma co so tu his_branch');
    }

    /** @test */
    public function lenh_quet_truyen_hai_gia_tri_tach_bach()
    {
        $ma = $this->maLenh();

        $this->assertContains("'maCskcb'", $ma, 'Thieu tham so maCskcb (co so dieu tri)');
        $this->assertContains("'maDkbd'", $ma, 'Thieu tham so maDkbd (noi DKBD benh nhan)');
    }

    /** @test */
    public function lenh_quet_co_co_thu()
    {
        // Che do chi dem, khong dispatch - de nghiem thu ma khong goi len cong BHXH.
        $this->assertContains('--thu', $this->maLenh(), 'Lenh quet thieu co --thu');
    }

    /** @test */
    public function job_so_sanh_bang_maDkbd_khong_phai_maCskcb()
    {
        $ma = $this->maJob();

        $this->assertContains("params['maDkbd']", $ma,
            'Phep so trong job phai dung maDkbd - no doi chieu noi DKBD, khong phai co so dieu tri');
    }

    /**
     * JobKtTheBHYT duoc dispatch tu HAI noi. Neu chi sua mot noi, noi kia gui thieu maCskcb
     * va job vo - loi chi lo ra luc chay that.
     */
    /** @test */
    public function nhap_xml_cung_truyen_hai_gia_tri_tach_bach()
    {
        $ma = $this->maNhapXml();

        $this->assertContains("'maCskcb'", $ma, 'XML4210Import thieu tham so maCskcb');
        $this->assertContains("'maDkbd'", $ma, 'XML4210Import thieu tham so maDkbd');
        $this->assertNotContains("'maCSKCB'", $ma,
            'XML4210Import con dung ten cu maCSKCB - job khong con doc khoa nay');
    }

    /**
     * Da sot 3 noi dispatch vi grep chi bat 'JobKtTheBHYT::dispatch' viet hoa, trong khi ma
     * nguon dung ca 'jobKtTheBHYT::dispatch' viet thuong - PHP khong phan biet hoa thuong ten
     * lop. Test nay quet CA NAM noi, khong phu thuoc cach viet.
     */
    /** @test */
    public function moi_noi_dispatch_deu_truyen_maCskcb_va_maDkbd()
    {
        $noi = [
            'app/Console/Commands/HISProKiemTraTheBHYT.php',
            'app/Console/Commands/XML4210Import.php',
            'app/Http/Controllers/System/UploadXML.php',
            'app/Services/Qd130XmlService.php',
            'app/Services/Xml3176Service.php',
        ];

        foreach ($noi as $tep) {
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertContains("'maCskcb'", $ma, $tep . ' thieu tham so maCskcb');
            $this->assertContains("'maDkbd'", $ma, $tep . ' thieu tham so maDkbd');
            $this->assertNotContains("'maCSKCB'", $ma,
                $tep . ' con dung ten cu maCSKCB - job khong con doc khoa nay');
        }
    }

    /**
     * Chot chan trong chinh job: job cu con ton trong hang doi hoac mot noi dispatch chua sua
     * phai ra log noi ro ho so nao, thay vi "Undefined index: maCskcb".
     */
    /** @test */
    public function job_chan_som_khi_thieu_maCskcb()
    {
        $ma = $this->maJob();

        $this->assertContains("isset(\$this->params['maCskcb'])", $ma,
            'Job phai kiem tra su ton tai cua maCskcb truoc khi dung');
    }

    /** @test */
    public function bang_ket_qua_co_cot_ma_cskcb()
    {
        $co = false;

        foreach (DB::select('SHOW COLUMNS FROM check_hein_cards') as $c) {
            if ($c->Field === 'ma_cskcb') {
                $co = true;
                break;
            }
        }

        $this->assertTrue($co, 'Bang check_hein_cards thieu cot ma_cskcb');
    }
}
