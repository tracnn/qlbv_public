<?php

namespace Tests\Unit;

use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;

class ManKetQuaTraCuuTheTest extends TestCase
{
    const BLADE = 'resources/views/bhyt/check-hein-card/index.blade.php';

    protected function maBlade()
    {
        return file_get_contents(base_path(self::BLADE));
    }

    /**
     * chiLoi() va chiHopLe() phai BU NHAU: tong hai ben bang tong so dong. Neu lech thi co
     * dong khong thuoc ben nao - nguoi dung se khong bao gio nhin thay no du chon trang thai
     * gi, ma khong co dau hieu gi.
     */
    /** @test */
    public function chi_loi_va_chi_hop_le_bu_nhau()
    {
        $tong = check_hein_card::count();

        $this->assertSame(
            $tong,
            check_hein_card::chiLoi()->count() + check_hein_card::chiHopLe()->count()
        );
    }

    /** @test */
    public function chi_hop_le_chi_lay_dong_sach()
    {
        $lech = check_hein_card::chiHopLe()
            ->where(function ($w) {
                $w->where('ma_tracuu', '!=', check_hein_card::TRA_CUU_SACH)
                  ->orWhere('ma_kiemtra', '!=', check_hein_card::KIEM_TRA_SACH);
            })
            ->count();

        $this->assertSame(0, $lech, 'chiHopLe() lot dong co van de');
    }

    /** @test */
    public function loc_co_so_rong_thi_khong_loc()
    {
        $tong = check_hein_card::count();

        $this->assertSame($tong, check_hein_card::cuaCoSo('')->count());
        $this->assertSame($tong, check_hein_card::cuaCoSo(null)->count());
    }

    /**
     * Khac danh muc: day la du lieu su kien tai MOT co so cu the, khong co khai niem "dong
     * dung chung". So khop thang.
     */
    /** @test */
    public function loc_co_so_la_so_khop_thang()
    {
        $this->assertSame(
            check_hein_card::where('ma_cskcb', '01929')->count(),
            check_hein_card::cuaCoSo('01929')->count()
        );
    }

    /** @test */
    public function route_va_menu_ton_tai()
    {
        $this->assertNotEmpty(route('bhyt.check-hein-card.index'));
        $this->assertNotEmpty(route('bhyt.check-hein-card.fetch-data'));

        // Gan ra bien truoc: array_walk_recursive nhan tham chieu, truyen thang ket qua
        // config() vao se bao "Only variables should be passed by reference".
        $menu = config('adminlte.menu', []);
        $co = false;

        array_walk_recursive($menu, function ($v) use (&$co) {
            if ($v === 'bhyt.check-hein-card.index') {
                $co = true;
            }
        });

        $this->assertTrue($co, 'Thieu muc menu tro toi man ket qua tra cuu the');
    }

    /**
     * Da xay ra that: viet `use Datatables;` (lop goc, khong ton tai) thay vi
     * `use Yajra\Datatables\Datatables;`. Endpoint nem "Class 'Datatables' not found", va o
     * trinh duyet chi hien "DataTables warning: Ajax error" - khong noi gi ve nguyen nhan.
     *
     * Goi THAT endpoint de bat loai loi nay, thay vi chi quet ma nguon.
     */
    /** @test */
    public function endpoint_chay_duoc_va_tra_ve_du_cot()
    {
        $j = $this->goiFetch([]);

        $this->assertArrayHasKey('data', $j);
        $this->assertSame(check_hein_card::count(), $j['recordsTotal']);

        if (empty($j['data'])) {
            return;
        }

        foreach (['ma_lk', 'nhan_tracuu', 'nhan_kiemtra', 'co_loi'] as $cot) {
            $this->assertArrayHasKey($cot, $j['data'][0], 'Endpoint thieu cot ' . $cot);
        }
    }

    /** @test */
    public function endpoint_ap_dung_dung_cac_bo_loc()
    {
        $this->assertSame(
            check_hein_card::chiLoi()->count(),
            $this->goiFetch(['trang_thai' => 'loi'])['recordsFiltered']
        );

        $this->assertSame(
            check_hein_card::chiHopLe()->count(),
            $this->goiFetch(['trang_thai' => 'hop_le'])['recordsFiltered']
        );

        $this->assertSame(
            check_hein_card::cuaCoSo('01929')->count(),
            $this->goiFetch(['ma_cskcb' => '01929'])['recordsFiltered']
        );

        // Khoang thoi gian o tuong lai xa thi khong con dong nao.
        $this->assertSame(0, $this->goiFetch(['tu_ngay' => '2099-01-01'])['recordsFiltered']);
    }

    /** Goi that endpoint fetch voi tham so DataTables toi thieu. */
    protected function goiFetch(array $loc)
    {
        $req = \Illuminate\Http\Request::create('/bhyt/check-hein-card/fetch-data', 'GET', array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'columns' => [[
                'data' => 'ma_lk', 'name' => '', 'searchable' => 'true',
                'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false'],
            ]],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $loc));

        app()->instance('request', $req);

        $ra = (new \App\Http\Controllers\BHYT\CheckHeinCardController())->fetch($req);

        return json_decode($ra->getContent(), true);
    }

    /** @test */
    public function blade_co_du_bon_bo_loc_va_khoi_tao_select2()
    {
        $ma = $this->maBlade();

        foreach (['trang_thai', 'tim'] as $id) {
            $this->assertContains('id="' . $id . '"', $ma, 'Thieu bo loc ' . $id);
        }

        // Khoang thoi gian KHONG con la hai o <input type="date"> rieng - no den tu
        // partials.date_range, giong man XML3176 va chung tu dien tu. Bo loc van con du,
        // chi doi hinh dang.
        $this->assertContains("@include('partials.date_range'", $ma, 'Thieu o chon khoang thoi gian');
        $this->assertContains("@include('partials.ma_cskcb'", $ma, 'Thieu o loc co so');
        $this->assertContains("select2({width: '100%'})", $ma,
            'Thieu khoi tao select2 - o chon se hien ra dang tho');
    }

    /** @test */
    public function blade_day_du_hai_stack_cua_hai_partial()
    {
        // Hai partial tu dat script cua no vao mot stack RIENG. Khong day ra thi chung im
        // lang khong hoat dong: o chon khoang thoi gian thanh mot o text tron va nut "Tai
        // du lieu" bam khong ra gi - khong loi console, khong dau hieu gi.
        $ma = $this->maBlade();

        $this->assertContains("@include('partials.load_data_button')", $ma, 'Thieu nut Tai du lieu');
        $this->assertContains("@stack('after-scripts-date-range')", $ma);
        $this->assertContains("@stack('after-scripts-load-data-button')", $ma);
    }

    /** @test */
    public function fetchData_nhan_khoang_ngay_theo_dung_hop_dong_cua_partial()
    {
        // partials.load_data_button goi ham TOAN CUC fetchData(startDate, endDate). Khai
        // fetchData() khong tham so thi khoang ngay roi mat va bang nap TOAN BO thoi gian.
        $ma = $this->maBlade();

        $this->assertContains('function fetchData(startDate, endDate)', $ma,
            'fetchData phai nhan khoang ngay - do la hop dong cua partials.load_data_button');
        $this->assertNotContains("$('#tu_ngay').val()", $ma,
            'Hai o input ngay da bi go, khong duoc doc lai chung');
    }

    /** @test */
    public function so_tieu_de_khop_so_cot()
    {
        $ma = $this->maBlade();

        preg_match('~<thead>(.*?)</thead>~s', $ma, $m);
        $soTh = preg_match_all('~<th>~', isset($m[1]) ? $m[1] : '');

        preg_match('~"columns"\s*:\s*\[(.*?)\n\s*\],~s', $ma, $c);
        $soCot = preg_match_all('~\{\s*"data"~', isset($c[1]) ? $c[1] : '');

        $this->assertSame($soTh, $soCot, 'So <th> khac so cot - DataTables se vo khi tai trang');
    }

    /**
     * Man nay KHONG duoc lap lai loi cua cac blade cu: config(...)[$ma] khong phong ve.
     */
    /** @test */
    public function blade_khong_truy_cap_mang_nhan_khong_phong_ve()
    {
        $this->assertNotRegExp(
            "~config\('__tech\.(insurance_error_code|check_insurance_code)'\)\[~",
            $this->maBlade(),
            'Truy cap mang khong phong ve - ma la se lam trang trang'
        );
    }
}
