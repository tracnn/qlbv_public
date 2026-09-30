<?php

namespace Tests\Unit\Xml3176\TepXuat;

use Tests\TestCase;

/**
 * Hang doi xuat tep phai co dich vu Windows chay no. Thieu thi moi yeu cau nam o "Dang cho"
 * mai - khong hong, khong ai biet.
 */
class DichVuXuatTepTest extends TestCase
{
    const DICH_VU = 'QLBV JobXuatTepXml3176';

    private function tep($ten)
    {
        return file_get_contents(base_path($ten));
    }

    private function lenh()
    {
        return 'nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work '
            . config('xml3176.xuat_tep_connection') . ' --queue=' . config('xml3176.xuat_tep_queue_name') . '"';
    }

    /** @test */
    public function update_bat_giu_nguyen_tung_byte_toi_het_git_pull()
    {
        $b = $this->tep('update.bat');
        $cuoi = strpos($b, "\n", strpos($b, 'git pull origin main')) + 1;

        $this->assertSame(710, $cuoi);
        $this->assertSame('e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117',
            hash('sha256', substr($b, 0, $cuoi)));
    }

    /** @test */
    public function update_bat_cai_stop_start_dich_vu_xuat_tep()
    {
        $b = $this->tep('update.bat');

        $this->assertContains('nssm status "' . self::DICH_VU . '"', $b, 'Khoi cai phai kiem truoc - chay lai moi lan cap nhat');
        $this->assertContains($this->lenh(), $b, 'Worker phai chay dung ket noi xuat_tep (retry_after 3600)');
        $this->assertContains('nssm set "' . self::DICH_VU . '" AppDirectory %LARAVEL_PATH%', $b);
        $this->assertLessThan(strpos($b, 'php artisan config:clear'), strpos($b, 'nssm stop "' . self::DICH_VU . '"'));
        $this->assertGreaterThan(strpos($b, 'php artisan config:cache'), strpos($b, 'nssm start "' . self::DICH_VU . '"'));
    }

    /** @test */
    public function install_va_remove_co_dich_vu_xuat_tep()
    {
        $cai = $this->tep('install_service.bat');
        $this->assertContains($this->lenh(), $cai);
        $this->assertContains('nssm start "' . self::DICH_VU . '"', $cai);

        $go = $this->tep('remove_service.bat');
        $this->assertContains('nssm stop "' . self::DICH_VU . '"', $go);
        $this->assertContains('nssm remove "' . self::DICH_VU . '" confirm', $go);
    }

    /** @test */
    public function ba_tep_van_la_crlf()
    {
        foreach (['update.bat', 'install_service.bat', 'remove_service.bat'] as $t) {
            $b = $this->tep($t);
            $this->assertSame(substr_count($b, "\n"), substr_count($b, "\r\n"), $t . ' co dong LF tron');
        }
    }
}
