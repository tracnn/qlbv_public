<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\MaTtdv;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class MaTtdvTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const CAC_BANG = [1, 7, 8, 9, 10, 11, 13, 14];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        DB::connection('HISPro')->table('his_employee')->insert([
            ['id' => 1, 'loginname' => 'gd', 'diploma' => 'CCHN-GD', 'social_insurance_number' => 'BHXH-GD'],
            ['id' => 2, 'loginname' => 'pgd', 'diploma' => 'CCHN-PGD', 'social_insurance_number' => 'BHXH-PGD'],
            ['id' => 3, 'loginname' => 'khongbhxh', 'diploma' => 'CCHN-X', 'social_insurance_number' => null],
        ]);
    }

    /** Danh muc voi key MA_TTDV_FROM_PROFILE_XML = $key (null: khong co key). Goi lai thi thay key cu. */
    private function dm($key = null)
    {
        $his = DB::connection('HISPro');
        $his->table('his_config')->delete();
        if ($key !== null) {
            $his->table('his_config')->insert(['id' => 1, 'key' => MaTtdv::KEY, 'value' => $key, 'is_active' => 1]);
        }

        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function du(array $ghi = [])
    {
        return new DuLieuHoSo((object) array_merge(array_fill_keys(HisNapHoSo::COT_HO_SO, null), [
            'id' => 5, 'treatment_code' => 'HS5', 'representative_hein_code' => 'RHC',
            'hosp_subs_director_loginname' => 'pgd', 'hospital_director_loginname' => 'gd',
        ], $ghi));
    }

    public function test_key_rong_dung_diploma_giam_doc()
    {
        foreach (self::CAC_BANG as $so) {
            $this->assertSame('CCHN-PGD', MaTtdv::cua($this->du(), $this->dm(), $so), "bang $so");
            $this->assertSame('CCHN-PGD', MaTtdv::cua($this->du(), $this->dm(''), $so), "bang $so, key rong");
        }
        // Pho giam doc rong => giam doc; ca hai rong => ''
        $this->assertSame('CCHN-GD', MaTtdv::cua($this->du(['hosp_subs_director_loginname' => '']), $this->dm(), 9));
        $this->assertSame('', MaTtdv::cua($this->du(['hosp_subs_director_loginname' => null, 'hospital_director_loginname' => null]), $this->dm(), 9));
        // Nhan vien khong co trong danh muc => ''
        $this->assertSame('', MaTtdv::cua($this->du(['hosp_subs_director_loginname' => 'la']), $this->dm(), 9));
    }

    public function test_key_chua_so_bang_dung_ma_bhxh()
    {
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('2,1'), 1));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('1;2'), 1));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('2 1'), 1));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('7,9'), 9));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('8, 10 ,14'), 14));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('11'), 11));
        // Khong co nhan vien hoac nhan vien khong co ma BHXH => '' (khong roi ve REPRESENTATIVE)
        $this->assertSame('', MaTtdv::cua($this->du(['hosp_subs_director_loginname' => 'khongbhxh']), $this->dm('10'), 10));
        $this->assertSame('', MaTtdv::cua($this->du(['hosp_subs_director_loginname' => 'la']), $this->dm('10'), 10));
    }

    public function test_key_khong_chua_so_bang_dung_representative()
    {
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('2'), 1));
        // "1" khong khop "13"/"11"/"14" khi DLL tach theo dau phan cach
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('13'), 1));
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('11'), 1));
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('1'), 11));
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('1,13'), 14));
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('1,13'), 10));
    }

    public function test_quy_tac_khop_rieng_tung_bang()
    {
        // XML7 khong trim: "1, 7" khong khop; cac bang khac co trim
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('1, 7'), 7));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('1,7'), 7));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('1, 8'), 8));
        // XML13 la chuoi con: "113", "130" cung khop; XML1 thi khong
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('113'), 13));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('130'), 13));
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('113'), 1));
        // XML1 tach ',', ';', ' ' ; cac bang con lai chi tach ','
        $this->assertSame('RHC', MaTtdv::cua($this->du(), $this->dm('8;9'), 9));
        $this->assertSame('BHXH-PGD', MaTtdv::cua($this->du(), $this->dm('8;1'), 1));
    }

    public function test_khong_co_login_dung_representative()
    {
        $du = $this->du(['hosp_subs_director_loginname' => null, 'hospital_director_loginname' => '']);
        foreach (self::CAC_BANG as $so) {
            // key chua so bang nhung khong co loginname => REPRESENTATIVE_HEIN_CODE
            $this->assertSame('RHC', MaTtdv::cua($du, $this->dm((string) $so), $so), "bang $so");
        }
    }
}
