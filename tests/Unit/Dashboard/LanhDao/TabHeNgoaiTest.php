<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\TabHeNgoai;

class TabHeNgoaiTest extends TestCase
{
    private function ky($coSo = '2cs')
    {
        return new KyLoc('20261001000000', '20261019235959', $coSo);
    }

    private function danhMuc()
    {
        return [
            'khoa' => [
                10 => '1|K19', 11 => '1|K19', 12 => '1|K21', 13 => '1|K26', 14 => '1|K20',
                20 => '81|K19', 21 => '81|K20',
            ],
            'don_vi' => [
                '1|K19' => ['ma' => 'K19', 'ten' => 'Ngoại tiêu hoá', 'branch_id' => 1, 'giuong_pd' => 10],
                '1|K21' => ['ma' => 'K21', 'ten' => 'Ngoại thần kinh', 'branch_id' => 1, 'giuong_pd' => 10],
                '1|K26' => ['ma' => 'K26', 'ten' => 'Gây mê', 'branch_id' => 1, 'giuong_pd' => null],
                '1|K20' => ['ma' => 'K20', 'ten' => 'Ngoại chấn thương', 'branch_id' => 1, 'giuong_pd' => 10],
                '81|K19' => ['ma' => 'K19', 'ten' => 'Ngoại NB', 'branch_id' => 81, 'giuong_pd' => 10],
                '81|K20' => ['ma' => 'K20', 'ten' => 'Khoa K20 NB', 'branch_id' => 81, 'giuong_pd' => 10],
            ],
        ];
    }

    private function pt($khoa, $stt, $so)
    {
        return (object) ['request_department_id' => $khoa, 'service_req_stt_id' => $stt, 'so_luong' => $so];
    }

    private function ct($ma, $ten, $branch, $so)
    {
        return (object) ['execute_room_code' => $ma, 'execute_room_name' => $ten, 'branch_id' => $branch, 'so_ca' => $so];
    }

    /** @test */
    public function chi_khoa_ngoai_dung_co_so()
    {
        $pt = [
            $this->pt(10, 3, 5),
            $this->pt(20, 3, 4),
            $this->pt(21, 3, 9),  // K20 branch 81 không thuộc khoa_ngoai.nb
            $this->pt(13, 3, 50), // K26 gây mê
        ];

        $kq = TabHeNgoai::dinhDang($pt, [], $this->danhMuc(), $this->ky('2cs'));

        $this->assertSame(['Ngoại tiêu hoá (HN)', 'Ngoại NB (NB)'], $kq['theo_khoa']['categories']);
        $this->assertSame([5, 4], $kq['theo_khoa']['y_lenh']);

        $kq = TabHeNgoai::dinhDang($pt, [], $this->danhMuc(), $this->ky('nb'));
        $this->assertSame(['Ngoại NB'], $kq['theo_khoa']['categories']);
    }

    /** @test */
    public function y_lenh_gom_moi_trang_thai_da_thuc_hien_chi_stt_3()
    {
        $pt = [
            $this->pt(10, 1, 2),
            $this->pt(10, 2, 3),
            $this->pt(10, 3, 6),
            $this->pt(11, 3, 1), // khoa con cùng đơn vị K19
            $this->pt(12, 3, 4),
        ];

        $kq = TabHeNgoai::dinhDang($pt, [], $this->danhMuc(), $this->ky('hn'));

        $this->assertSame(['Ngoại tiêu hoá', 'Ngoại thần kinh'], $kq['theo_khoa']['categories']);
        $this->assertSame([12, 4], $kq['theo_khoa']['y_lenh']);
        $this->assertSame([7, 4], $kq['theo_khoa']['da_thuc_hien']);
    }

    /** @test */
    public function sap_giam_theo_y_lenh()
    {
        $pt = [$this->pt(12, 3, 9), $this->pt(10, 3, 2)];

        $kq = TabHeNgoai::dinhDang($pt, [], $this->danhMuc(), $this->ky('hn'));

        $this->assertSame(['Ngoại thần kinh', 'Ngoại tiêu hoá'], $kq['theo_khoa']['categories']);
    }

    /** @test */
    public function can_thiep_loc_co_so()
    {
        $ct = [
            $this->ct('CLS.CT0001', 'Lab 1', 1, 30),
            $this->ct('NBKP03.K19', 'Phòng K19 NB', 81, 7),
            $this->ct('XXX.NGOAI', 'Phòng ngoài danh sách', 1, 99),
        ];

        $kq = TabHeNgoai::dinhDang([], $ct, $this->danhMuc(), $this->ky('hn'));
        $this->assertSame(['Lab 1'], $kq['can_thiep']['categories']);
        $this->assertSame([30], $kq['can_thiep']['so_ca']);

        $kq = TabHeNgoai::dinhDang([], $ct, $this->danhMuc(), $this->ky('2cs'));
        $this->assertSame(['Lab 1 (HN)', 'Phòng K19 NB (NB)'], $kq['can_thiep']['categories']);
        $this->assertSame([30, 7], $kq['can_thiep']['so_ca']);
    }

    /** @test */
    public function the_so_hn_nb_2cs_cong_don()
    {
        $pt = [$this->pt(10, 1, 2), $this->pt(10, 3, 6), $this->pt(20, 3, 4), $this->pt(21, 3, 9), $this->pt(13, 3, 50)];
        $ct = [$this->ct('CLS.CT0001', 'Lab 1', 1, 30), $this->ct('NBKP03.K19', 'K19', 81, 7)];

        $kq = TabHeNgoai::tongHop($pt, $ct, $this->danhMuc());

        $this->assertSame(['hn' => 8, 'nb' => 4, '2cs' => 12], $kq['y_lenh_pt']);
        $this->assertSame(['hn' => 6, 'nb' => 4, '2cs' => 10], $kq['ca_pt']);
        $this->assertSame(['hn' => 30, 'nb' => 7, '2cs' => 37], $kq['can_thiep_tm']);
    }
}
