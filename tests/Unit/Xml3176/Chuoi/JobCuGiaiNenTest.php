<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\ExportXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176Service;
use Tests\TestCase;

/**
 * Job serialize boi ma CU (truoc chuoi) con nam trong hang doi luc nang cap phai giai nen duoc
 * vao lop MOI (spec muc 5). Chuoi serialize viet tay theo hinh dang cu.
 */
class JobCuGiaiNenTest extends TestCase
{
    /** Thuoc tinh protected serialize thanh "\0*\0ten". */
    private function protectedProp($ten, $giaTri)
    {
        $ten = chr(0) . '*' . chr(0) . $ten;
        $giaTri = is_int($giaTri) ? 'i:' . $giaTri . ';' : 's:' . strlen($giaTri) . ':"' . $giaTri . '";';

        return 's:' . strlen($ten) . ':"' . $ten . '";' . $giaTri;
    }

    private function doc($lop, array $thuocTinh)
    {
        $noiDung = '';
        foreach ($thuocTinh as $ten => $giaTri) {
            $noiDung .= $this->protectedProp($ten, $giaTri);
        }

        return 'O:' . strlen($lop) . ':"' . $lop . '":' . count($thuocTinh) . ':{' . $noiDung . '}';
    }

    private function doc_prop($obj, $ten)
    {
        $p = new \ReflectionProperty($obj, $ten);
        $p->setAccessible(true);

        return $p->getValue($obj);
    }

    /** @test */
    public function job_xuat_cu_giai_nen_duoc_va_khong_xuat()
    {
        $job = unserialize($this->doc(ExportXml3176Job::class, ['ma_lk' => 'LK1', 'soLanCho' => 3]));

        $this->assertInstanceOf(ExportXml3176Job::class, $job);
        $this->assertNull($this->doc_prop($job, 'chainToken'));

        $dichVu = new class extends Xml3176Service {
            public $soLanXuat = 0;
            public function __construct() {}
            public function xuatTepChoKy($ma_lk)
            {
                $this->soLanXuat++;
                return true;
            }
        };

        $job->handle($dichVu);

        $this->assertSame(0, $dichVu->soLanXuat, 'Job xuat dang cu khong duoc xuat');
    }

    /** @test */
    public function job_gui_cu_giai_nen_duoc_va_giu_duong_dan_va_ma_co_so()
    {
        $job = unserialize($this->doc(SubmitXml3176Job::class, [
            'ma_lk' => 'LK1', 'xmlFilePath' => '01929/2026.09.29_10.00.00_LK1.xml', 'macskcb' => '01929',
        ]));

        $this->assertInstanceOf(SubmitXml3176Job::class, $job);
        $this->assertNull($this->doc_prop($job, 'chainToken'));
        $this->assertSame('01929/2026.09.29_10.00.00_LK1.xml', $this->doc_prop($job, 'xmlFilePath'));
        $this->assertSame('01929', $this->doc_prop($job, 'macskcb'));
    }
}
