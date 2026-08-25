<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Schema;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;

class Tt12BangTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
    }

    /** @test */
    public function dung_du_nam_bang()
    {
        foreach (['tt12_ho_so', 'tt12_dong', 'tt12_dong_thuoc_px', 'tt12_loi', 'tt12_lich_su_gui'] as $bang) {
            $this->assertTrue(Schema::hasTable($bang), 'Thieu bang ' . $bang);
        }
    }

    /** @test */
    public function ho_so_co_du_cot_theo_doi_vong_doi()
    {
        $cot = ['ma_ho_so', 'mau', 'loai_hs', 'ma_cskcb', 'ten_tep', 'so_dong', 'id_danh_sach',
                'imported_at', 'imported_by', 'import_error', 'checked_at', 'so_loi',
                'is_signed', 'sign_method', 'signed_at', 'signed_error', 'duong_dan_da_ky',
                'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
                'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan', 'dong_bo_at', 'dong_bo_so_dong'];

        foreach ($cot as $c) {
            $this->assertTrue(Schema::hasColumn('tt12_ho_so', $c), 'tt12_ho_so thieu cot ' . $c);
        }
    }

    /** @test */
    public function du_lieu_dong_di_ve_nguyen_ven_qua_cast_mang()
    {
        $hoSo = Tt12HoSo::create([
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'Mau_MAU_01.xlsx', 'so_dong' => 1,
            'id_danh_sach' => 'Id-abc',
        ]);

        Tt12Dong::create([
            'ho_so_id' => $hoSo->id,
            'stt' => 1,
            'du_lieu' => ['MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh & Nội', 'TU_NGAY' => '20260101'],
            'ma' => 'K01', 'ten' => 'Khám bệnh & Nội',
            'tu_ngay' => '20260101', 'den_ngay' => null,
        ]);

        $doc = Tt12Dong::where('ho_so_id', $hoSo->id)->first();

        $this->assertInternalType('array', $doc->du_lieu);
        $this->assertSame('Khám bệnh & Nội', $doc->du_lieu['TEN_KHOA']);
        $this->assertSame('20260101', $doc->du_lieu['TU_NGAY']);
    }

    /** @test */
    public function hai_dong_cung_stt_trong_mot_ho_so_bi_tu_choi()
    {
        $hoSo = Tt12HoSo::create([
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_002',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 2, 'id_danh_sach' => 'Id-def',
        ]);

        Tt12Dong::create(['ho_so_id' => $hoSo->id, 'stt' => 1, 'du_lieu' => []]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Tt12Dong::create(['ho_so_id' => $hoSo->id, 'stt' => 1, 'du_lieu' => []]);
    }

    /** @test */
    public function ma_ho_so_la_duy_nhat()
    {
        $chung = [
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-ghi',
        ];

        Tt12HoSo::create(array_merge($chung, ['ma_ho_so' => 'TRUNG']));

        $this->expectException(\Illuminate\Database\QueryException::class);

        Tt12HoSo::create(array_merge($chung, ['ma_ho_so' => 'TRUNG']));
    }

    /** @test */
    public function ho_so_lay_duoc_cac_dong_cua_no()
    {
        $hoSo = Tt12HoSo::create([
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_003',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 2, 'id_danh_sach' => 'Id-jkl',
        ]);

        Tt12Dong::create(['ho_so_id' => $hoSo->id, 'stt' => 1, 'du_lieu' => ['MA_KHOA' => 'K01']]);
        Tt12Dong::create(['ho_so_id' => $hoSo->id, 'stt' => 2, 'du_lieu' => ['MA_KHOA' => 'K02']]);

        $this->assertCount(2, $hoSo->dong()->get());
    }
}
