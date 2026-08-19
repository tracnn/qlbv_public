<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loi\CtdtLoiNap;
use App\Services\Ctdt\Loi\LoaiKhongBietException;
use App\Services\Ctdt\Loi\TheGocLechException;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;
use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Moi loi NAP luong truoc duoc mang chung mot dau hieu, de importer bat dung mot loai
 * ma khong nuot mat loi la.
 */
class CtdtLoiNapTest extends TestCase
{
    public function cacLop()
    {
        return [
            LoaiKhongBietException::class,
            TheGocLechException::class,
            GoiKhongDocDuocException::class,
            ThieuMacskcbException::class,
            KhongXacDinhDuocMaHoSoException::class,
        ];
    }

    /** @test */
    public function moi_loi_nap_deu_mang_dau_hieu_chung()
    {
        foreach ($this->cacLop() as $lop) {
            $this->assertContains(CtdtLoiNap::class, class_implements($lop),
                $lop . ' phai cai CtdtLoiNap');
            $this->assertInstanceOf(\Exception::class, new $lop('thu'));
        }
    }

    /** @test */
    public function loai_khong_biet_van_la_InvalidArgumentException()
    {
        // Giai doan 1 da co test khang dinh cho() nem InvalidArgumentException. Doi lop cha
        // se lam do test cu ma khong duoc gi - dau hieu chung la thu duy nhat can them.
        $this->assertInstanceOf(\InvalidArgumentException::class, new LoaiKhongBietException('thu'));
    }

    /** @test */
    public function the_goc_lech_van_la_RuntimeException()
    {
        $this->assertInstanceOf(\RuntimeException::class, new TheGocLechException('thu'));
    }

    /** @test */
    public function registry_nem_lop_moi_khi_loai_la()
    {
        $this->expectException(LoaiKhongBietException::class);

        CtdtLoaiRegistry::cho('CT99');
    }

    /** @test */
    public function registry_nem_lop_moi_khi_the_goc_lech()
    {
        $this->expectException(TheGocLechException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('GIAYDIEUTRINOITRU', 'GIAYDIEUTRINOITRU');
    }

    /** @test */
    public function bat_duoc_ca_hai_bang_mot_menh_de_catch()
    {
        // Day chinh la ly do task nay ton tai: mot catch duy nhat trong importer.
        $daBat = 0;

        foreach ([['CT99', null], ['GIAYSUCKHOEME', 'GIAYSUCKHOEME']] as list($loai, $theGoc)) {
            try {
                if ($theGoc === null) {
                    CtdtLoaiRegistry::cho($loai);
                } else {
                    CtdtLoaiRegistry::xacNhanTheGoc($loai, $theGoc);
                }
            } catch (CtdtLoiNap $e) {
                $daBat++;
            }
        }

        $this->assertSame(2, $daBat, 'Mot catch CtdtLoiNap phai bat duoc ca hai loai loi');
    }
}
