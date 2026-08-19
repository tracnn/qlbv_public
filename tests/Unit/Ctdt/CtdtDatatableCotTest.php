<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Http\Request;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Khoa danh sach cot di ra ngoai trong JSON cua DataTables.
 *
 * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay se khong tu
 * dong lot ra ngoai lam payload phinh lai - va khong vo tinh day du lieu benh nhan ra mot
 * endpoint ma man hinh khong he hien.
 *
 * Test nay con khoa hai danh sach khop nhau: cot khai trong controller va cot blade doc.
 * Lech nhau thi mot cot hien trong o trong ma khong bao gi ca.
 */
class CtdtDatatableCotTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @test */
    public function danh_sach_cot_khong_rong_va_khong_trung()
    {
        $cot = BHYTCtdtController::DATATABLE_COLUMNS;

        $this->assertNotEmpty($cot);
        $this->assertSame(count($cot), count(array_unique($cot)), 'Co cot bi khai hai lan');
    }

    /** @test */
    public function co_du_cac_cot_dac_ta_doi_hoi()
    {
        // Dac ta muc 6.1: ma ho so, dich vu, ma CSKCB, ho ten, ma the, so chung tu, so loi,
        // trang thai ky, trang thai gui, MaGD, thoi diem tiep nhan.
        foreach ([
            'ma_ho_so', 'dich_vu', 'macskcb', 'ho_ten', 'ma_the', 'so_chung_tu', 'so_loi',
            'is_signed', 'trang_thai_gui', 'ma_gd', 'thoi_gian_tiep_nhan',
        ] as $ten) {
            $this->assertContains($ten, BHYTCtdtController::DATATABLE_COLUMNS,
                'Thieu cot ' . $ten);
        }
    }

    /** @test */
    public function blade_doc_dung_nhung_cot_da_khai()
    {
        // Doc thang tep blade: moi "data": "<ten>" trong khoi columns phai nam trong danh
        // sach trang. Mot cot blade doc ma controller khong tra se hien o trong vinh vien.
        $blade = file_get_contents(base_path('resources/views/bhyt/ctdt/index.blade.php'));

        $this->assertNotFalse($blade, 'Khong doc duoc index.blade.php');

        preg_match_all('/"data"\s*:\s*"([a-z0-9_]+)"/i', $blade, $khop);

        $this->assertNotEmpty($khop[1], 'Khong tim thay cot nao trong blade');

        foreach (array_unique($khop[1]) as $ten) {
            $this->assertContains($ten, BHYTCtdtController::DATATABLE_COLUMNS,
                'Blade doc cot "' . $ten . '" khong co trong DATATABLE_COLUMNS');
        }
    }

    /** @test */
    public function khong_lo_cot_nhay_cam_ra_ngoai()
    {
        // noi_dung_goc la XML nguyen van cua chung tu - hang chuc KB moi dong. Lot vao danh
        // sach thi moi lan tai 200 dong la vai MB qua mang, va du lieu benh nhan di ra mot
        // endpoint khong ai doc no.
        foreach (['noi_dung_goc', 'lich_su_gui', 'submitted_message'] as $cam) {
            $this->assertNotContains($cam, BHYTCtdtController::DATATABLE_COLUMNS,
                'Cot ' . $cam . ' khong duoc ra JSON danh sach');
        }
    }

    /**
     * @test
     *
     * Test HANH VI - khong chi soi hang so DATATABLE_COLUMNS nhu bon test tren, ma goi
     * THAT fetchData() va kiem JSON tra ra. Truoc dot sua nay, ba test tren van XANH trong
     * khi fetchData() khong he goi ->only(...): DATATABLE_COLUMNS chi la mot hang so vo
     * chu, khong ai ap dung no. Do la niem tin sai, con te hon la khong co test.
     */
    public function fetchData_khong_tra_cac_cot_nhay_cam_ra_JSON()
    {
        $this->chuanBiBangCtdt();

        $hoSo = CtdtHoSo::create([
            'ma_ho_so'           => 'YT001',
            'dich_vu'            => 'CT2025',
            'loai_hs'            => '39',
            'macskcb'            => '01929',
            'so_chung_tu'        => 1,
            'imported_at'        => '2026-08-19 08:00:00',
            'lich_su_gui'        => '[2026-08-01] nap lai, ban truoc: MaGD=HS_1',
            'submitted_message'  => 'Cong tra ve: khong hop le',
            'import_error'       => 'The XML nao do bi thieu',
            'duong_dan_goc'      => '/tmp/goi-that.xml',
        ]);

        CtdtChungTu::create([
            'ho_so_id'     => $hoSo->id,
            'loai_ho_so'   => 'CT03',
            'ma_chung_tu'  => 'YT001',
            'ma_the'       => 'DN123',
            'ho_ten'       => 'Nguyen Van Test',
            'noi_dung_goc' => '<CT03/>',
        ]);

        $controller = new BHYTCtdtController();

        $phanHoi = $controller->fetchData(Request::create('/bhyt/ctdt/fetch-data', 'GET'));
        $json = json_decode($phanHoi->getContent(), true);

        $this->assertArrayHasKey('data', $json, 'Phan hoi khong co khoa data: ' . $phanHoi->getContent());
        $this->assertNotEmpty($json['data'], 'Khong co dong nao duoc tra ve');

        $dong = $json['data'][0];

        foreach (['lich_su_gui', 'submitted_message', 'import_error', 'duong_dan_goc', 'id'] as $cam) {
            $this->assertArrayNotHasKey(
                $cam,
                $dong,
                'Cot nhay cam "' . $cam . '" lot ra JSON danh sach - only(DATATABLE_COLUMNS) khong duoc ap dung'
            );
        }
    }
}
