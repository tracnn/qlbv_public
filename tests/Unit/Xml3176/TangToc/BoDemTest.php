<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Services\Xml3176\Xml3176BoDem;
use PHPUnit\Framework\TestCase;

class BoDemTest extends TestCase
{
    private $gio = 1000;

    protected function setUp()
    {
        parent::setUp();
        Xml3176BoDem::xoaTatCa();
        Xml3176BoDem::$bayGio = function () {
            return $this->gio;
        };
        Xml3176BoDem::$docBoNho = function () {
            return 0;
        };
    }

    protected function tearDown()
    {
        Xml3176BoDem::xoaTatCa();
        Xml3176BoDem::$docBoNho = null;
        parent::tearDown();
    }

    /** @test */
    public function nho_goi_tinh_mot_lan()
    {
        $dem = 0;
        $tinh = function () use (&$dem) {
            $dem++;

            return 'v';
        };
        $this->assertSame('v', Xml3176BoDem::nho('a', 'k', $tinh));
        $this->assertSame('v', Xml3176BoDem::nho('a', 'k', $tinh));
        $this->assertSame(1, $dem);
    }

    /** @test */
    public function nho_ca_false_va_null()
    {
        $dem = 0;
        foreach ([false, null] as $i => $giaTri) {
            $tinh = function () use (&$dem, $giaTri) {
                $dem++;

                return $giaTri;
            };
            $this->assertSame($giaTri, Xml3176BoDem::nho('a', 'k' . $i, $tinh));
            $this->assertSame($giaTri, Xml3176BoDem::nho('a', 'k' . $i, $tinh));
        }
        $this->assertSame(2, $dem);
    }

    /** @test */
    public function ngoai_le_khong_nho()
    {
        try {
            Xml3176BoDem::nho('a', 'k', function () {
                throw new \RuntimeException('loi csdl');
            });
            $this->fail('phai nem lai ngoai le');
        } catch (\RuntimeException $e) {
            $this->assertSame('loi csdl', $e->getMessage());
        }
        $this->assertSame('ok', Xml3176BoDem::nho('a', 'k', function () {
            return 'ok';
        }));
    }

    /** @test */
    public function het_ttl_tinh_lai()
    {
        $dem = 0;
        $tinh = function () use (&$dem) {
            return ++$dem;
        };
        $this->assertSame(1, Xml3176BoDem::nho('a', 'k', $tinh));
        $this->gio += Xml3176BoDem::TTL_GIAY;
        $this->assertSame(1, Xml3176BoDem::nho('a', 'k', $tinh));
        $this->gio += 1;
        $this->assertSame(2, Xml3176BoDem::nho('a', 'k', $tinh));

        $bang = 0;
        $nap = function () use (&$bang) {
            return ['n' => ++$bang];
        };
        $this->assertSame(['n' => 1], Xml3176BoDem::bang('b', $nap));
        $this->assertSame(['n' => 1], Xml3176BoDem::bang('b', $nap));
        $this->gio += Xml3176BoDem::TTL_GIAY + 1;
        $this->assertSame(['n' => 2], Xml3176BoDem::bang('b', $nap));
    }

    /** @test */
    public function vuot_tran_xoa_bo_dem()
    {
        for ($i = 0; $i < Xml3176BoDem::TRAN_MUC; $i++) {
            Xml3176BoDem::nho('a', (string) $i, function () use ($i) {
                return $i;
            });
        }
        $dem = 0;
        Xml3176BoDem::nho('a', 'moi', function () use (&$dem) {
            $dem++;

            return 1;
        });
        // muc cu da bi xoa nen phai tinh lai
        Xml3176BoDem::nho('a', '0', function () use (&$dem) {
            $dem++;

            return 0;
        });
        $this->assertSame(2, $dem);
        // muc moi van con
        $this->assertSame(1, Xml3176BoDem::nho('a', 'moi', function () {
            return 'sai';
        }));
    }

    /** @test */
    public function dat_khi_bang_chua_nap_khong_lam_gi()
    {
        Xml3176BoDem::dat('b', 'k', 'daDat');
        // khong ghi vao kho theo khoa: nho() van tinh moi
        $this->assertSame('khac', Xml3176BoDem::nho('b', 'k', function () {
            return 'khac';
        }));
        // bang nap sau do khong co muc da dat
        $this->assertSame(['x' => 1], Xml3176BoDem::bang('b', function () {
            return ['x' => 1];
        }));
    }

    /** @test */
    public function dat_khi_bang_het_han_khong_lam_gi()
    {
        Xml3176BoDem::bang('b', function () {
            return ['x' => 1];
        });
        $this->gio += Xml3176BoDem::TTL_GIAY + 1;
        Xml3176BoDem::dat('b', 'y', 2);
        $this->assertSame(['x' => 2], Xml3176BoDem::bang('b', function () {
            return ['x' => 2];
        }));
    }

    /** @test */
    public function xoa_tat_ca_bo_het()
    {
        Xml3176BoDem::nho('a', 'k', function () {
            return 'cu';
        });
        Xml3176BoDem::xoaTatCa();
        Xml3176BoDem::$bayGio = function () {
            return $this->gio;
        };
        $this->assertSame('khac', Xml3176BoDem::nho('a', 'k', function () {
            return 'khac';
        }));
    }

    /** @test */
    public function tran_muc_theo_ten_bo_dem()
    {
        // bo dem model: tran 5.000
        for ($i = 0; $i < Xml3176BoDem::TRAN_MUC_MODEL; $i++) {
            Xml3176BoDem::nho('danh_muc.vtyt', (string) $i, function () use ($i) {
                return $i;
            });
        }
        $dem = 0;
        Xml3176BoDem::nho('danh_muc.vtyt', 'moi', function () use (&$dem) {
            $dem++;

            return 1;
        });
        Xml3176BoDem::nho('danh_muc.vtyt', '0', function () use (&$dem) {
            $dem++;

            return 0;
        });
        $this->assertSame(2, $dem, 'bo dem model phai bi xoa khi vuot 5.000');

        // bo dem scalar: van giu duoc hon 5.000 muc
        for ($i = 0; $i < Xml3176BoDem::TRAN_MUC_MODEL + 1; $i++) {
            Xml3176BoDem::nho('danh_muc.dvkt_ton_tai', (string) $i, function () {
                return true;
            });
        }
        $dem = 0;
        Xml3176BoDem::nho('danh_muc.dvkt_ton_tai', '0', function () use (&$dem) {
            $dem++;

            return true;
        });
        $this->assertSame(0, $dem, 'bo dem scalar giu tran 20.000');
    }

    /** @test */
    public function vuot_nguong_bo_nho_xoa_tat_ca_roi_nho_muc_moi()
    {
        Xml3176BoDem::nho('a', 'k', function () {
            return 'cu';
        });
        Xml3176BoDem::bang('b', function () {
            return ['x' => 1];
        });

        Xml3176BoDem::$docBoNho = function () {
            return Xml3176BoDem::NGUONG_BO_NHO + 1;
        };
        $this->assertSame('moi', Xml3176BoDem::nho('c', 'k', function () {
            return 'moi';
        }));
        Xml3176BoDem::$docBoNho = function () {
            return 0;
        };

        // bo dem cu da bi xoa
        $this->assertSame('tinhLai', Xml3176BoDem::nho('a', 'k', function () {
            return 'tinhLai';
        }));
        $this->assertSame(['x' => 2], Xml3176BoDem::bang('b', function () {
            return ['x' => 2];
        }));
        // muc moi van con (duoc nho sau khi xoa)
        $this->assertSame('moi', Xml3176BoDem::nho('c', 'k', function () {
            return 'sai';
        }));
    }

    /** @test */
    public function duoi_nguong_bo_nho_khong_xoa()
    {
        Xml3176BoDem::nho('a', 'k', function () {
            return 'cu';
        });
        Xml3176BoDem::$docBoNho = function () {
            return Xml3176BoDem::NGUONG_BO_NHO;
        };
        Xml3176BoDem::nho('c', 'k', function () {
            return 'moi';
        });
        $this->assertSame('cu', Xml3176BoDem::nho('a', 'k', function () {
            return 'sai';
        }));
    }

    /** @test */
    public function dat_vao_bang_da_nap()
    {
        Xml3176BoDem::bang('b', function () {
            return ['x' => 1];
        });
        Xml3176BoDem::dat('b', 'y', 2);
        $this->assertSame(['x' => 1, 'y' => 2], Xml3176BoDem::bang('b', function () {
            return [];
        }));
    }
}
