<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Exports\CtdtDanhSachExport;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Tep xuat phai khop DUNG man hinh. Moi ben tu dung bo loc thi them mot o loc ma quen ben
 * kia se lam tep xuat khac han, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu
 * tung dong voi ban cua BHXH.
 */
class CtdtXuatExcelTest extends TestCase
{
    private function hoSo(array $ghiDe = [])
    {
        $hoSo = new CtdtHoSo();

        foreach (array_merge([
            'ma_ho_so'   => 'YT001',
            'dich_vu'    => 'CT2025',
            'macskcb'    => '01001',
            'checked_at' => '2026-08-20 08:00:00',
            'so_loi'     => 0,
            'is_signed'  => true,
            'ma_ket_qua' => '200',
            'ma_gd'      => 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
        ], $ghiDe) as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function so_cot_tieu_de_khop_so_o_moi_dong()
    {
        // Lech mot cot la moi gia tri tu do tro di nam duoi sai tieu de - va bang van trong
        // hoan toan binh thuong. Day la kieu loi khong ai phat hien bang mat.
        $xuat = new CtdtDanhSachExport(CtdtHoSo::query());

        $this->assertCount(
            count($xuat->headings()),
            $xuat->map($this->hoSo()),
            'So o moi dong phai bang so tieu de'
        );
    }

    /** @test */
    public function nhan_trang_thai_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        // Go lai chuoi tieng Viet o day nghia la doi nhan tren man hinh se khong doi nhan
        // trong tep xuat - hai nguon su that cho cung mot khai niem.
        $hoSo = $this->hoSo(['ma_ket_qua' => null, 'is_signed' => false, 'signed_error' => 'USB token bi rut']);

        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($hoSo);

        // Bon tham so cuoi ep so sanh NGHIEM NGAT (===): assertContains mac dinh dung so
        // sanh long (==), va mang $dong co nhieu phan tu int(0) (so_chung_tu, so_loi). Trong
        // PHP7, 0 == '<bat ky chuoi khong phai so nao>' la TRUE - nen ban so sanh long se
        // luon xanh du nhan co dung hay khong.
        $this->assertContains(
            CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::KY_HONG),
            $dong,
            'Phai dung dung nhan cua CtdtTrangThaiGui',
            false,
            false,
            true
        );
    }

    /** @test */
    public function ma_gd_dai_khong_bi_cat_trong_tep_xuat()
    {
        // ma_gd la cot doi soat quan trong nhat voi BHXH. Cong tra ve 52 ky tu that.
        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($this->hoSo());

        // So sanh NGHIEM NGAT - xem chu thich o test ben tren ve loi 0 == chuoi cua PHP7.
        $this->assertContains(
            'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
            $dong,
            '',
            false,
            false,
            true
        );
    }

    /** @test */
    public function duyet_theo_lo_chu_khong_nap_ca_bang()
    {
        // Bang nay phinh theo thoi gian, may chu moi gioi han PHP 128MB. FromCollection se
        // nap het vao bo nho.
        $this->assertInstanceOf(
            \Maatwebsite\Excel\Concerns\FromQuery::class,
            new CtdtDanhSachExport(CtdtHoSo::query())
        );
    }
}
