<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

/**
 * Canh cac route va blade cua man tai len.
 *
 * Khong dung HTTP that (bo test khong co phien dang nhap dung san): kiem route co ton
 * tai va blade bien dich duoc. Hai thu nay vo im lang - route go nham ten thi nut bam
 * tren blade nem RouteNotFoundException khi nguoi dung mo trang, khong phai luc build.
 */
class Tt12ManImportTest extends TestCase
{
    /** @test */
    public function cac_route_tt12_deu_duoc_dang_ky()
    {
        foreach (['bhyt.tt12.import.index', 'bhyt.tt12.upload'] as $ten) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($ten),
                'Thieu route ' . $ten
            );
        }
    }

    /** @test */
    public function blade_import_bien_dich_duoc()
    {
        $duongDan = resource_path('views/bhyt/tt12/import.blade.php');

        $this->assertFileExists($duongDan);

        // Bien dich Blade thanh PHP roi kiem cu phap PHP. Bat loi go nham the @endforeach
        // ngay tai day thay vi luc nguoi dung mo trang.
        $php = app('blade.compiler')->compileString(file_get_contents($duongDan));

        $tam = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($tam, $php);

        exec('php -l ' . escapeshellarg($tam), $ra, $ma);
        unlink($tam);

        $this->assertSame(0, $ma, 'Blade sinh ra PHP sai cu phap: ' . implode(PHP_EOL, $ra));
    }

    /** @test */
    public function dia_exportTt12_duoc_khai_bao()
    {
        $this->assertNotNull(
            config('filesystems.disks.exportTt12'),
            'Thieu dia exportTt12 trong config/filesystems.php'
        );
    }

    /** @test */
    public function cau_hinh_co_so_tt12_co_du_khoa()
    {
        foreach (['sign_enabled', 'submit_enabled', 'hang_doi'] as $khoa) {
            $this->assertTrue(
                config()->has('organization.tt12.' . $khoa),
                'Thieu organization.tt12.' . $khoa
            );
        }
    }

    /** @test */
    public function khoi_tt12_KHONG_duoc_khai_ma_tinh_hay_ma_cskcb_mac_dinh()
    {
        // ma_tinh luon suy tu hai ky tu dau cua ma co so (CauHinhCoSo::maTinh) - khai lai
        // la them mot cho co the khai sai, va config/organization.php da ghi ro dieu do.
        //
        // ma_cskcb mac dinh la mot cai bay o he thong nhieu co so: nguoi dung nap danh muc
        // Ninh Binh (37470) ma quen doi o chon thi ca tep vao co so Ha Noi (01929).
        $this->assertFalse(config()->has('organization.tt12.ma_tinh'),
            'Khong duoc khai organization.tt12.ma_tinh');
        $this->assertFalse(config()->has('organization.tt12.ma_cskcb'),
            'Khong duoc khai organization.tt12.ma_cskcb');
    }

    /** @test */
    public function moi_co_so_trong_BHYT_CO_SO_deu_suy_dung_ma_tinh_va_du_tai_khoan_thi_tra_duoc()
    {
        // KHONG bat moi co so phai co tai khoan: mot co so da khai trong BHYT_CO_SO nhung
        // chua duoc BHXH cap tai khoan la trang thai hop le va pho bien (vi du 01283 tren
        // may nay). Bat dieu do se buoc nguoi dung bia thong tin dang nhap gia chi de test
        // xanh - nguy hiem hon la huu ich, vi CauHinhCoSo::cua() chi kiem "khac rong", chuoi
        // bia se lot qua buoc kiem do va di thang ra cong BHXH, nhan ve loi 401 mo ho hon
        // nhieu so voi loi "thieu tai khoan" ro rang ma CauHinhCoSo da nem san.
        $ds = config('organization.BHYT_CO_SO', []);

        $this->assertNotEmpty($ds, 'Chua khai co so nao trong organization.BHYT_CO_SO');

        foreach ($ds as $ma => $khoi) {
            // Logic thuan, luon dung bat ke co tai khoan hay chua - Task 9 dua vao dieu
            // nay de gui dung ma tinh.
            $this->assertSame(
                substr((string) $ma, 0, 2),
                \App\Services\BHYT\CauHinhCoSo::maTinh($ma),
                $ma . ': ma tinh phai la hai ky tu dau cua ma co so'
            );

            $coTaiKhoan = isset($khoi['username']) && trim((string) $khoi['username']) !== '';

            if (!$coTaiKhoan) {
                continue;
            }

            // Nem thay vi tra ve gia tri mac dinh: dung tai khoan cua co so khac chinh la
            // thu lam ho so khong hop le.
            $tk = \App\Services\BHYT\CauHinhCoSo::cua($ma, $ds);

            $this->assertNotEmpty($tk['username'], $ma . ': thieu username');
        }
    }
}
