<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Sinh token API moi va ghi BAN BAM cua no vao config/organization.php.
 *
 * Lam giong key:generate: thay dung MOT DONG bang regex, khong ghi lai ca tep. Tep nay
 * con chua cau hinh co so KCB va tai khoan cong BHXH cua tung ban cai - ghi lai ca tep
 * (nhu lenh config:add-keys dang lam) xoa sach chu thich va de lam mat cau hinh.
 */
class ApiGenerateToken extends Command
{
    protected $signature = 'api:generate {--force : Ghi de token cu khong hoi}';

    protected $description = 'Sinh token API moi, ghi ban bam SHA-256 vao config/organization.php';

    const KHOA = 'access_token_hash';

    public function handle()
    {
        $duongDan = $this->duongDanConfig();

        if (!is_file($duongDan)) {
            $this->error('Khong tim thay ' . $duongDan);

            return 1;
        }

        $noiDung = file_get_contents($duongDan);

        // Chi THAY, khong CHEN: ban cai cu thieu khoa nay la tinh huong that (tep khong
        // nam trong git). Doan cho chen vao mot tep bi mat thu cong la cach nhanh nhat
        // lam hong no - bao de nguoi van hanh tu sua dung cho.
        if (!preg_match($this->mauKhoa(), $noiDung, $khop)) {
            $this->error('Khong tim thay khoa \'' . self::KHOA . '\' trong ' . $duongDan);
            $this->line('Them thu cong dong sau vao muc api roi chay lai:');
            $this->line('    \'' . self::KHOA . '\' => \'\',');

            return 1;
        }

        if ($khop[1] !== '' && !$this->option('force')
            && !$this->confirm('Da co token. Ghi de se cat dut moi ben dang goi. Tiep tuc?')) {
            $this->line('Da huy.');

            return 1;
        }

        $token = bin2hex(random_bytes(32));

        file_put_contents($duongDan, preg_replace(
            $this->mauKhoa(),
            '\'' . self::KHOA . '\' => \'' . hash('sha256', $token) . '\'',
            $noiDung,
            1
        ));

        // Khong xoa cache thi hash moi nam im trong tep con ung dung van dung ban cu.
        if (is_file($this->duongDanCacheConfig())) {
            $this->call('config:clear');
        }

        $this->info('Token API moi (chep ngay, khong hien lai):');
        $this->line('  ' . $token);
        $this->info('Da ghi hash vao ' . $duongDan);

        return 0;
    }

    protected function mauKhoa()
    {
        return '/\'' . self::KHOA . '\'\s*=>\s*\'([^\']*)\'/';
    }

    /** Tach rieng de test ghi vao tep tam, khong cham config that. */
    protected function duongDanConfig()
    {
        return config_path('organization.php');
    }

    protected function duongDanCacheConfig()
    {
        return base_path('bootstrap/cache/config.php');
    }
}
