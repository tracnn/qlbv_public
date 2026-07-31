<?php

namespace Tests\Unit;

use App\Services\BHYT\NhanMaThe;
use Tests\TestCase;

class NhanMaTheTest extends TestCase
{
    protected function bang()
    {
        return ['000' => 'Thông tin thẻ BHYT chính xác', '003' => 'Thẻ cũ hết giá trị'];
    }

    /** @test */
    public function ma_co_trong_bang_thi_tra_nhan()
    {
        $this->assertSame('Thông tin thẻ BHYT chính xác', NhanMaThe::nhan('000', $this->bang()));
    }

    /**
     * Diem cot loi: cac blade cu viet config(...)[$ma] - truy cap mang KHONG phong ve, gap ma
     * la la "Undefined index" va trang trang. O day phai tra ma tran, khong duoc nem.
     */
    /** @test */
    public function ma_la_thi_tra_ma_tran_chu_khong_nem()
    {
        $this->assertSame('999', NhanMaThe::nhan('999', $this->bang()));
    }

    /** @test */
    public function bang_rong_thi_van_tra_ma_tran()
    {
        $this->assertSame('000', NhanMaThe::nhan('000', []));
    }

    /** @test */
    public function ma_rong_hoac_null_thi_tra_chuoi_rong()
    {
        $this->assertSame('', NhanMaThe::nhan('', $this->bang()));
        $this->assertSame('', NhanMaThe::nhan(null, $this->bang()));
        $this->assertSame('', NhanMaThe::nhan('   ', $this->bang()));
    }

    /** @test */
    public function hai_ham_tien_ich_doc_dung_bang_cau_hinh()
    {
        config([
            '__tech.insurance_error_code' => ['000' => 'Chinh xac'],
            '__tech.check_insurance_code' => ['00' => 'The chinh xac'],
        ]);

        $this->assertSame('Chinh xac', NhanMaThe::traCuu('000'));
        $this->assertSame('The chinh xac', NhanMaThe::kiemTra('00'));

        // Ma la van khong nem.
        $this->assertSame('777', NhanMaThe::traCuu('777'));
        $this->assertSame('88', NhanMaThe::kiemTra('88'));
    }
}
