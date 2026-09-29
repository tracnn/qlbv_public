<?php

namespace Tests\Unit\Xml3176\Chuoi;

use Tests\TestCase;

/**
 * Hang doi ky moi phai co dich vu Windows chay no. Thieu thi MOI chuoi dung o buoc Ky - job
 * nam cho, khong hong, khong ai biet.
 */
class DichVuHangDoiKyTest extends TestCase
{
    const DICH_VU = 'QLBV JobSignXml3176';

    private function tep($ten)
    {
        return file_get_contents(base_path($ten));
    }

    /** @test */
    public function update_bat_giu_nguyen_tung_byte_toi_het_git_pull()
    {
        // May chu tu chay update.bat moi gio. cmd doc tiep tep MOI theo vi tri byte sau
        // 'git pull' - doi mot byte phia truoc la dich chuyen moi lenh phia sau.
        $b = $this->tep('update.bat');
        $cuoi = strpos($b, "\n", strpos($b, 'git pull origin main')) + 1;

        $this->assertSame(710, $cuoi);
        $this->assertSame('e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117',
            hash('sha256', substr($b, 0, $cuoi)));
    }

    /** @test */
    public function update_bat_cai_dung_stop_start_dich_vu_ky()
    {
        $b = $this->tep('update.bat');
        $hangDoi = config('xml3176.sign_queue_name');

        $this->assertContains('nssm status "' . self::DICH_VU . '"', $b, 'Khoi cai phai kiem truoc - chay lai moi gio');
        $this->assertContains('nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=' . $hangDoi . '"', $b);
        $this->assertContains('nssm set "' . self::DICH_VU . '" AppDirectory %LARAVEL_PATH%', $b);
        $this->assertContains('nssm stop "' . self::DICH_VU . '"', $b);
        $this->assertContains('nssm start "' . self::DICH_VU . '"', $b);
        $this->assertLessThan(strpos($b, 'php artisan config:clear'), strpos($b, 'nssm stop "' . self::DICH_VU . '"'));
        $this->assertGreaterThan(strpos($b, 'php artisan config:cache'), strpos($b, 'nssm start "' . self::DICH_VU . '"'));
    }

    /** @test */
    public function install_va_remove_co_dich_vu_ky()
    {
        $hangDoi = config('xml3176.sign_queue_name');

        $cai = $this->tep('install_service.bat');
        $this->assertContains('nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=' . $hangDoi . '"', $cai);
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
