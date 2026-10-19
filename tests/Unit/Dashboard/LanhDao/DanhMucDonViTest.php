<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use App\Services\Dashboard\LanhDao\DanhMucDonVi;

class DanhMucDonViTest extends TestCase
{
    private function khoa($id, $ten, $ma, $branch)
    {
        return (object) ['id' => $id, 'department_name' => $ten, 'bhyt_code' => $ma, 'branch_id' => $branch];
    }

    private function giuong($ma, $cskcb, $ten, $giuong, $tu = null, $den = null)
    {
        return (object) [
            'ma_khoa' => $ma, 'ma_cskcb' => $cskcb, 'ten_khoa' => $ten,
            'giuong_pd' => $giuong, 'tu_ngay' => $tu, 'den_ngay' => $den,
        ];
    }

    /** @test */
    public function gop_khoa_con_cung_bhyt_code()
    {
        $khoa = [
            $this->khoa(10, 'Khoa Tim mạch A', 'K04', 1),
            $this->khoa(11, 'Khoa Tim mạch B', 'K04', 1),
            $this->khoa(12, 'Khoa Tim mạch C', 'K04', 1),
        ];
        $giuong = [$this->giuong('K04', '01929', 'Viện Tim mạch', 402, '20240101')];

        $kq = DanhMucDonVi::dung($khoa, $giuong, '20261019');

        $this->assertSame([10 => '1|K04', 11 => '1|K04', 12 => '1|K04'], $kq['khoa']);
        $this->assertCount(1, $kq['don_vi']);
        $this->assertSame('Viện Tim mạch', $kq['don_vi']['1|K04']['ten']);
        $this->assertSame(402, $kq['don_vi']['1|K04']['giuong_pd']);
        $this->assertSame('K04', $kq['don_vi']['1|K04']['ma']);
        $this->assertSame(1, $kq['don_vi']['1|K04']['branch_id']);
    }

    /** @test */
    public function cung_ma_hai_co_so_la_hai_don_vi()
    {
        $khoa = [$this->khoa(1, 'Tim mạch HN', 'K04', 1), $this->khoa(2, 'Tim mạch NB', 'K04', 81)];
        $giuong = [
            $this->giuong('K04', '01929', 'Viện Tim mạch', 402),
            $this->giuong('K04', '37470', 'Khoa Tim mạch', 50),
        ];

        $kq = DanhMucDonVi::dung($khoa, $giuong, '20261019');

        $this->assertSame('1|K04', $kq['khoa'][1]);
        $this->assertSame('81|K04', $kq['khoa'][2]);
        $this->assertSame(402, $kq['don_vi']['1|K04']['giuong_pd']);
        $this->assertSame(50, $kq['don_vi']['81|K04']['giuong_pd']);
        $this->assertSame('Viện Tim mạch (HN)', DanhMucDonVi::nhan($kq['don_vi']['1|K04'], '2cs'));
        $this->assertSame('Khoa Tim mạch (NB)', DanhMucDonVi::nhan($kq['don_vi']['81|K04'], '2cs'));
        $this->assertSame('Viện Tim mạch', DanhMucDonVi::nhan($kq['don_vi']['1|K04'], 'hn'));
        $this->assertSame('Khoa Tim mạch', DanhMucDonVi::nhan($kq['don_vi']['81|K04'], 'nb'));
    }

    /** @test */
    public function bo_ktest_rong_va_branch_61()
    {
        $khoa = [
            $this->khoa(1, 'Khoa thử', 'KTEST', 1),
            $this->khoa(2, 'Khoa rỗng', '', 1),
            $this->khoa(3, 'Khoa null', null, 1),
            $this->khoa(4, 'Phòng Y tế', 'K04', 61),
            $this->khoa(5, 'Khoa thật', 'K05', 1),
        ];

        $kq = DanhMucDonVi::dung($khoa, [], '20261019');

        $this->assertSame([5 => '1|K05'], $kq['khoa']);
        $this->assertSame(['1|K05'], array_keys($kq['don_vi']));
    }

    /** @test */
    public function ma_khong_co_trong_danh_muc_giuong()
    {
        $khoa = [
            $this->khoa(1, 'Khám bệnh theo yêu cầu', 'K01', 1),
            $this->khoa(2, 'Khám', 'K01', 1),
        ];

        $kq = DanhMucDonVi::dung($khoa, [], '20261019');

        $this->assertSame('1|K01', $kq['khoa'][1]);
        $this->assertNull($kq['don_vi']['1|K01']['giuong_pd']);
        $this->assertSame('Khám', $kq['don_vi']['1|K01']['ten']);
    }

    /** @test */
    public function chon_dong_giuong_con_hieu_luc()
    {
        $khoa = [$this->khoa(1, 'K39 HIS', 'K39', 1)];
        $giuong = [
            $this->giuong('K39', '01929', 'Cũ', 10, '20250101'),
            $this->giuong('K39', '01929', 'Mới', 20, '20260601'),
            $this->giuong('K39', '01929', 'Hết hạn', 30, '20240101', '20260101'),
        ];

        $moi = DanhMucDonVi::dung($khoa, $giuong, '20261019');
        $this->assertSame('Mới', $moi['don_vi']['1|K39']['ten']);
        $this->assertSame(20, $moi['don_vi']['1|K39']['giuong_pd']);

        $cu = DanhMucDonVi::dung($khoa, $giuong, '20260301');
        $this->assertSame('Cũ', $cu['don_vi']['1|K39']['ten']);
        $this->assertSame(10, $cu['don_vi']['1|K39']['giuong_pd']);
    }
}
