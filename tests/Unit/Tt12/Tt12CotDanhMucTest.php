<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Chot rang moi the XML deu co cho de ghi trong bang danh muc dich.
 *
 * VI SAO CAN: Mau0x::cotDanhMuc() suy ten cot bang strtolower(). Suy dung khong co
 * nghia la cot TON TAI. Thieu cot thi Tt12DongBoDanhMuc se nem QueryException giua
 * chung mot ho so da duoc cong tiep nhan - hong o dung cho kho go nhat.
 *
 * Test nay chay tren CSDL THAT (khong dung SQLite bo nho) vi no kiem chinh lieu
 * migration da chay hay chua. Neu bang chua ton tai thi bo qua, de bo test van chay
 * duoc tren may chua migrate.
 */
class Tt12CotDanhMucTest extends TestCase
{
    /** @test */
    public function moi_the_xml_deu_co_cot_tuong_ung_trong_bang_danh_muc()
    {
        $daKiem = 0;

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $bang = $lop::bangDanhMuc();

            if (!Schema::hasTable($bang)) {
                $this->markTestSkipped('Chua co bang ' . $bang . ' - chay php artisan migrate truoc');
            }

            foreach ($lop::cotDanhMuc() as $the => $cot) {
                $this->assertTrue(
                    Schema::hasColumn($bang, $cot),
                    $ma . ': the ' . $the . ' can cot ' . $bang . '.' . $cot . ' nhung cot khong ton tai'
                );

                $daKiem++;
            }
        }

        $this->assertGreaterThan(100, $daKiem, 'Kiem qua it cot - co ve registry rong');
    }

    /** @test */
    public function cau_hinh_import_thu_cong_va_dac_ta_tt12_khong_lech_bang_dich()
    {
        $bangCua = [
            'medicine'        => 'medicine_catalogs',
            'medical_supply'  => 'medical_supply_catalogs',
            'service'         => 'service_catalogs',
            'medical_staff'   => 'medical_staffs',
            'department_bed'  => 'department_bed_catalogs',
            'equipment'       => 'equipment_catalogs',
        ];

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $khoa = $lop::danhMuc();

            $this->assertArrayHasKey($khoa, $bangCua, $ma . ': khoa danh muc la');
            $this->assertSame($bangCua[$khoa], $lop::bangDanhMuc(), $ma . ': bang dich lech');
            $this->assertNotNull(
                config('catalog_import_mapping.' . $khoa),
                $ma . ': khoa ' . $khoa . ' khong co trong catalog_import_mapping'
            );
        }
    }
}
