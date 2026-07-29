<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class NhapDanhMucUniqueTest extends TestCase
{
    /** medicine_catalogs co 9 cot NOT NULL khong mac dinh */
    private function dongThuoc($maCskcb)
    {
        return [
            'ma_thuoc' => 'ZZUNQ', 'ten_hoat_chat' => 'X', 'ten_thuoc' => 'X',
            'don_vi_tinh' => 'Vien', 'ham_luong' => '1', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'don_gia_bh' => 10, 'tt_thau' => 'T', 'tu_ngay' => '20240101',
            'ma_cskcb' => $maCskcb,
        ];
    }

    /** @test */
    public function hai_co_so_cung_bo_khoa_cu_van_chen_duoc_hai_dong()
    {
        // Ca tai hien loi: rang buoc UNIQUE cu khong co ma_cskcb nen co so thu hai bi tu
        // choi, loi bi catch nuot trong CatalogImportService va dong bi bo IM LANG.
        try {
            DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));
            DB::table('medicine_catalogs')->insert($this->dongThuoc('37470'));

            $this->assertSame(2, DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->count());
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->delete();
        }
    }

    /** @test */
    public function cung_co_so_cung_bo_khoa_thi_bi_chan()
    {
        try {
            DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));

            $nem = false;

            try {
                DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));
            } catch (\Exception $e) {
                $nem = true;
            }

            $this->assertTrue($nem, 'Trung hoan toan ma van chen duoc');
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->delete();
        }
    }

    /** @test */
    public function ba_rang_buoc_unique_deu_co_ma_cskcb()
    {
        foreach (['medicine_catalogs', 'medical_supply_catalogs', 'service_catalogs'] as $bang) {
            $co = false;

            foreach (DB::select('SHOW INDEX FROM ' . $bang) as $i) {
                if ($i->Column_name === 'ma_cskcb' && !$i->Non_unique) { $co = true; }
            }

            $this->assertTrue($co, "$bang chua co ma_cskcb trong rang buoc UNIQUE");
        }
    }
}
