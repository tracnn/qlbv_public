<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\SoSanh\SoSanhBang;
use Tests\TestCase;

class SoSanhBangTest extends TestCase
{
    private function thuoc($ma, array $them = [])
    {
        return array_merge(['MA_LK' => 'LK1', 'MA_THUOC' => $ma, 'NGAY_YL' => '202610221030', 'SO_LUONG' => '1'], $them);
    }

    public function test_dong_chi_co_mot_ben()
    {
        $kq = SoSanhBang::so('XML2', [$this->thuoc('T1'), $this->thuoc('T2')], [$this->thuoc('T1'), $this->thuoc('T3')]);

        $this->assertCount(2, $kq);
        $this->assertSame(['khoa' => 'LK1|T2|202610221030', 'kieu' => 'chi_co_a', 'truong' => null, 'a' => null, 'b' => null], $kq[0]);
        $this->assertSame('chi_co_b', $kq[1]['kieu']);
        $this->assertSame('LK1|T3|202610221030', $kq[1]['khoa']);
    }

    public function test_truong_khac_gia_tri()
    {
        $kq = SoSanhBang::so('XML2', [$this->thuoc('T1', ['GHI_CHU' => ' x '])], [$this->thuoc('T1', ['GHI_CHU' => 'y'])]);

        $this->assertSame([['khoa' => 'LK1|T1|202610221030', 'kieu' => 'khac', 'truong' => 'GHI_CHU', 'a' => 'x', 'b' => 'y']], $kq);
    }

    public function test_so_17000_bang_17000_00()
    {
        $this->assertSame([], SoSanhBang::so('XML2', [$this->thuoc('T1', ['DON_GIA' => '17000'])], [$this->thuoc('T1', ['DON_GIA' => '17000.00'])]));
        $kq = SoSanhBang::so('XML2', [$this->thuoc('T1', ['DON_GIA' => '17000'])], [$this->thuoc('T1', ['DON_GIA' => '17'])]);
        $this->assertCount(1, $kq);
        $this->assertSame('DON_GIA', $kq[0]['truong']);
    }

    public function test_rong_bang_0_cot_so()
    {
        $this->assertSame([], SoSanhBang::so('XML2', [$this->thuoc('T1', ['SO_LUONG' => ''])], [$this->thuoc('T1', ['SO_LUONG' => '0'])]));
        // cot khong phai so: rong khac 0
        $kq = SoSanhBang::so('XML2', [$this->thuoc('T1', ['GHI_CHU' => ''])], [$this->thuoc('T1', ['GHI_CHU' => '0'])]);
        $this->assertCount(1, $kq);
    }

    public function test_khoa_trung_danh_so()
    {
        $kq = SoSanhBang::so('XML2', [$this->thuoc('T1', ['SO_LUONG' => '5']), $this->thuoc('T1', ['SO_LUONG' => '2'])], [$this->thuoc('T1')]);

        $this->assertCount(2, $kq);
        $this->assertSame('LK1|T1|202610221030#1', $kq[0]['khoa']);
        $this->assertSame('SO_LUONG', $kq[0]['truong']);
        $this->assertSame('LK1|T1|202610221030#2', $kq[1]['khoa']);
        $this->assertSame('chi_co_a', $kq[1]['kieu']);
    }
}
