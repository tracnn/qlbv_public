<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Console\Commands\CtdtImport;

/**
 * Lenh chay NEN, khong co nguoi ngoi nhin. Moi cai phanh o day deu la thu duy nhat dung
 * giua mot thu muc do nham va hang nghin lan POST that len cong BHXH.
 */
class CtdtImportCommandTest extends TestCase
{
    /** @test */
    public function co_du_nam_tuy_chon_phanh()
    {
        $lenh = new CtdtImport();
        $dinhNghia = $lenh->getDefinition();

        foreach (['duong-dan', 'gioi-han', 'dry-run', 'khong-ky', 'khong-gui'] as $ten) {
            $this->assertTrue($dinhNghia->hasOption($ten), 'Thieu tuy chon --' . $ten);
        }
    }

    /** @test */
    public function gioi_han_mac_dinh_khong_duoc_de_trong()
    {
        // Khong co tran thi mot thu muc do nham 3000 tep thanh 3000 lan POST that len cong,
        // trong MOT luot chay, khong ai kip dung lai.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('gioi-han')->getDefault();

        $this->assertNotNull($macDinh, 'Tuy chon --gioi-han phai co gia tri mac dinh');
        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function ten_lenh_dung_tien_to_ctdt()
    {
        $this->assertSame('ctdt:import', (new CtdtImport())->getName());
    }

    /** @test */
    public function quet_bo_qua_thu_muc_da_nap_va_loi()
    {
        // Storage quet DE QUY. Khong loai hai thu muc con nay ra thi tep da xu ly bi nhat
        // len lai o luot sau - va voi ho so da gui, do la mot lan POST that nua.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains(CtdtImport::THU_MUC_DA_NAP, $nguon);
        $this->assertContains(CtdtImport::THU_MUC_LOI, $nguon);
    }
}
