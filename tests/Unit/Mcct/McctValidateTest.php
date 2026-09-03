<?php

namespace Tests\Unit\Mcct;

use App\Http\Requests\McctRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class McctValidateTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u', 'password' => 'p'],
            '37470' => ['username' => 'u', 'password' => 'p'],
        ]]);
    }

    protected function hopLe($ghiDe = [])
    {
        return array_merge([
            'ma_cskcb' => '01929',
            'ma_the' => 'DN4010100000001',
            'ho_ten' => 'NGUYEN VAN A',
            'ngay_sinh' => '01/01/1990',
        ], $ghiDe);
    }

    protected function kiem(array $du_lieu)
    {
        return Validator::make($du_lieu, (new McctRequest())->rules());
    }

    /** @test */
    public function du_lieu_hop_le_thi_qua()
    {
        $this->assertTrue($this->kiem($this->hopLe())->passes());
    }

    /**
     * Phu luc ghi ro: sau khi bo khoang trang, do dai hop le la 10, 12 hoac 15.
     * Chan tai day chu khong de cong tra 400 - vua tiet kiem luot goi (cong co danh sach
     * tai khoan bi han che), vua bao loi dung cho sai.
     */
    /** @test */
    public function ma_the_dung_do_dai_10_12_15_thi_qua()
    {
        foreach ([10, 12, 15] as $doDai) {
            $ma = str_repeat('A', $doDai);

            $this->assertTrue($this->kiem($this->hopLe(['ma_the' => $ma]))->passes(),
                "Ma the $doDai ky tu phai qua");
        }
    }

    /** @test */
    public function ma_the_sai_do_dai_thi_bi_chan()
    {
        foreach ([9, 11, 13, 14, 16] as $doDai) {
            $ma = str_repeat('A', $doDai);

            $this->assertTrue($this->kiem($this->hopLe(['ma_the' => $ma]))->fails(),
                "Ma the $doDai ky tu phai bi chan");
        }
    }

    /** @test */
    public function ba_dinh_dang_ngay_sinh_deu_qua()
    {
        foreach (['01/01/1990', '01/1990', '1990'] as $ns) {
            $this->assertTrue($this->kiem($this->hopLe(['ngay_sinh' => $ns]))->passes(),
                "Ngay sinh $ns phai qua");
        }
    }

    /** @test */
    public function ngay_sinh_sai_dinh_dang_thi_bi_chan()
    {
        foreach (['1/1/1990', '1990-01-01', '01-01-1990', '32/01/1990'] as $ns) {
            $this->assertTrue($this->kiem($this->hopLe(['ngay_sinh' => $ns]))->fails(),
                "Ngay sinh $ns phai bi chan");
        }
    }

    /**
     * O chon va localStorage deu sua duoc tu phia nguoi dung - ma ngoai danh sach phai bi
     * chan TRUOC khi cham toi cong BHXH.
     */
    /** @test */
    public function ma_co_so_ngoai_danh_sach_bi_chan()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ma_cskcb' => '99999']))->fails());
    }

    /**
     * ma_cskcb de trong thi KHONG bao loi: controller dung lai o man da dien san de nguoi
     * dung chon. Giong hanh vi da co o InsuranceRequest.
     */
    /** @test */
    public function ma_co_so_de_trong_thi_khong_bao_loi()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ma_cskcb' => '']))->passes());
    }

    /** @test */
    public function thieu_ho_ten_thi_bi_chan()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ho_ten' => '']))->fails());
    }

    /** @test */
    public function chuan_hoa_ma_the_bo_khoang_trang_va_viet_hoa()
    {
        $this->assertSame('DN4010100000001',
            McctRequest::chuanHoaMaThe('  dn4 0101 000 00001 '));
    }
}
