<?php

namespace Tests\Unit\Xml3176;

use App\Console\Commands\Xml3176DoKiem;
use PHPUnit\Framework\TestCase;

class Xml3176DoKiemMaHoaDongTest extends TestCase
{
    private function dong($mota)
    {
        return ['xml' => 'XML3', 'ma_lk' => 'LK1', 'stt' => 1, 'error_code' => 'E1',
            'description' => $mota, 'critical_error' => false];
    }

    /** @test */
    public function utf8_hong_khong_tao_dong_trong()
    {
        $kq = Xml3176DoKiem::maHoaDong($this->dong("abc\xB1\xFFxyz"));
        $this->assertNotSame('', trim($kq));
        $this->assertSame("\n", substr($kq, -1));
        $this->assertSame('E1', json_decode($kq, true)['error_code']);
    }

    /** @test */
    public function utf8_hop_le_giu_nguyen_byte()
    {
        $kq = Xml3176DoKiem::maHoaDong($this->dong('Bác sĩ a/b'));
        $this->assertSame('{"xml":"XML3","ma_lk":"LK1","stt":1,"error_code":"E1","description":"Bác sĩ a/b","critical_error":false}' . "\n", $kq);
    }
}
