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
    }

    protected function tearDown()
    {
        Xml3176BoDem::xoaTatCa();
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
    public function dat_va_xoa_tat_ca()
    {
        Xml3176BoDem::dat('a', 'k', 'daDat');
        $this->assertSame('daDat', Xml3176BoDem::nho('a', 'k', function () {
            return 'khac';
        }));

        Xml3176BoDem::xoaTatCa();
        Xml3176BoDem::$bayGio = function () {
            return $this->gio;
        };
        $this->assertSame('khac', Xml3176BoDem::nho('a', 'k', function () {
            return 'khac';
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
