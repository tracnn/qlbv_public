# Kế hoạch: màn Hoạt động điều trị trên trình chiếu giao ban

Spec: `docs/superpowers/specs/2026-07-28-giaoban-man-hoat-dong-dieu-tri-design.md`

**Mục tiêu:** thêm slide bảng tổng hợp các khoa khối Điều trị nội trú, và đưa khối Kíp trực
lãnh đạo lên đầu slide Tổng quan.

**Kiến trúc:** máy chủ dựng sẵn cấu trúc bảng bằng lớp thuần `BangDieuTri`; blade chỉ vẽ.
Không truy vấn HIS, không migration — dữ liệu lấy từ `giaoban_report_cells` đã chụp sẵn.

## Ràng buộc chung

- Cổng: `vendor/bin/phpunit --testsuite Unit`. Chạy trước để ghi số nền.
- Bình luận mã nguồn viết tiếng Việt không dấu.
- Test dùng `/** @test */`.
- `BangDieuTri` **không** chạm cơ sở dữ liệu — nhận dữ liệu qua tham số để kiểm được.
- Commit + push lên `main` sau khi xong.

---

## Task 1: Lớp thuần `BangDieuTri`

**Tệp:**
- Tạo: `app/Services/GiaoBan/BangDieuTri.php`
- Test: `tests/Unit/GiaoBan/BangDieuTriTest.php`

**Interfaces:**
- Consumes: không
- Produces: `BangDieuTri::dung(array $configs, array $cells): array`
  trả `['cot' => [...], 'dong' => [...], 'tong' => [...]]`

Hình dạng dữ liệu vào, cố ý dùng mảng thuần thay vì model để kiểm được:

```php
$configs = [
    ['id' => 3, 'block_type' => 'dieu_tri', 'display_name' => 'Nội TH', 'sort_order' => 1,
     'metrics' => [
         ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
         ['code' => 'ghi_chu', 'name' => 'Ghi chú', 'type' => 'manual',
          'input' => ['value_type' => 'text']],
     ]],
];

$cells = [
    ['dept_config_id' => 3, 'metric_code' => 'bn_cu', 'auto_value' => 79, 'manual_value' => null],
];
```

Hình dạng ra:

```php
[
    'cot'  => [['khoa' => 'BN cũ', 'nhan' => 'BN cũ', 'percent' => false]],
    'dong' => [['ten' => 'Nội TH', 'o' => [79.0]]],
    'tong' => [79.0],          // null o cot percent
]
```

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\BangDieuTri;

class BangDieuTriTest extends TestCase
{
    private function cfg($id, $ten, $sort, array $metrics, $block = 'dieu_tri', $active = 1)
    {
        return [
            'id' => $id, 'block_type' => $block, 'display_name' => $ten,
            'sort_order' => $sort, 'is_active' => $active, 'metrics' => $metrics,
        ];
    }

    private function m($code, $name, $type = 'census_from', $valueType = null)
    {
        $m = ['code' => $code, 'name' => $name, 'type' => $type];

        if ($valueType !== null) {
            $m['input'] = ['value_type' => $valueType];
        }

        return $m;
    }

    private function o($deptId, $code, $auto, $manual = null)
    {
        return ['dept_config_id' => $deptId, 'metric_code' => $code,
                'auto_value' => $auto, 'manual_value' => $manual];
    }

    /** @test */
    public function khong_khoa_dieu_tri_nao_thi_bang_rong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'KKB', 1, [$this->m('a', 'A')], 'kham'),
            $this->cfg(2, 'CDHA', 2, [$this->m('b', 'B')], 'can_lam_sang'),
        ], []);

        $this->assertSame([], $b['cot']);
        $this->assertSame([], $b['dong']);
        $this->assertSame([], $b['tong']);
    }

    /** @test */
    public function chi_lay_khoi_dieu_tri()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'KKB', 1, [$this->m('a', 'A')], 'kham'),
            $this->cfg(2, 'Nội TH', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], []);

        $this->assertCount(1, $b['dong']);
        $this->assertSame('Nội TH', $b['dong'][0]['ten']);
    }

    /** @test */
    public function bo_qua_khoa_da_tat()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Cũ', 1, [$this->m('bn_cu', 'BN cũ')], 'dieu_tri', 0),
            $this->cfg(2, 'Nội TH', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], []);

        $this->assertCount(1, $b['dong']);
    }

    /** @test */
    public function hai_khoa_cung_nhan_gop_mot_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Ngoại', 1, [$this->m('de_mo', 'Đẻ mổ')]),
            $this->cfg(2, 'Phụ sản', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'de_mo', 3), $this->o(2, 'de_mo', 5)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('Đẻ mổ', $b['cot'][0]['nhan']);
        $this->assertSame([3.0], $b['dong'][0]['o']);
        $this->assertSame([5.0], $b['dong'][1]['o']);
        $this->assertSame([8.0], $b['tong']);
    }

    /** @test */
    public function cung_ma_khac_nhan_thi_tach_hai_cot()
    {
        // He qua co chu dich cua viec khoa cot theo NHAN, xem spec muc 4.3.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('bn_cu', 'Bệnh nhân cũ')]),
        ], []);

        $this->assertCount(2, $b['cot']);
    }

    /** @test */
    public function khong_co_nhan_thi_lay_ma_lam_khoa()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [['code' => 'bn_cu', 'type' => 'census_from']]),
        ], [$this->o(1, 'bn_cu', 7)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('bn_cu', $b['cot'][0]['nhan']);
        $this->assertSame([7.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function chi_tieu_chuoi_khong_thanh_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Ngoại', 1, [
                $this->m('bn_cu', 'BN cũ'),
                $this->m('ds_mo', 'Danh sách mổ', 'manual', 'text'),
            ]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }

    /** @test */
    public function chi_tieu_nhap_tay_kieu_so_van_thanh_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('de_mo', 'Đẻ mổ', 'manual', 'int')]),
        ], [$this->o(1, 'de_mo', null, 4)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([4.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function khoa_khong_khai_chi_tieu_thi_o_bang_khong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'bn_cu', 9), $this->o(2, 'de_mo', 2)]);

        $this->assertSame([9.0, 0.0], $b['dong'][0]['o']);
        $this->assertSame([0.0, 2.0], $b['dong'][1]['o']);
        $this->assertSame([9.0, 2.0], $b['tong']);
    }

    /** @test */
    public function manual_value_de_len_auto_value()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', 10, 12)]);

        $this->assertSame([12.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function ca_hai_gia_tri_rong_thi_bang_khong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', null, null)]);

        $this->assertSame([0.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function cot_toan_percent_thi_khong_cong_tong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->m('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
        ], [$this->o(1, 'ty_le', 40), $this->o(2, 'ty_le', 60)]);

        $this->assertTrue($b['cot'][0]['percent']);
        $this->assertSame([null], $b['tong']);
    }

    /** @test */
    public function cot_tron_percent_va_so_dem_thi_van_cong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x', 'X', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->m('x', 'X', 'manual', 'int')]),
        ], [$this->o(1, 'x', 40), $this->o(2, 'x', 2)]);

        $this->assertFalse($b['cot'][0]['percent']);
        $this->assertSame([42.0], $b['tong']);
    }

    /** @test */
    public function thu_tu_cot_theo_sort_order_roi_theo_thu_tu_khai()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('c', 'C'), $this->m('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->m('a', 'A'), $this->m('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B', 'C'], $nhan);
    }

    /** @test */
    public function dong_sap_theo_sort_order()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->m('a', 'A')]),
        ], []);

        $this->assertSame('Truoc', $b['dong'][0]['ten']);
        $this->assertSame('Sau', $b['dong'][1]['ten']);
    }

    /** @test */
    public function o_cua_khoa_khac_khong_bi_lay_nham()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', 5)]);

        $this->assertSame([5.0], $b['dong'][0]['o']);
        $this->assertSame([0.0], $b['dong'][1]['o']);
    }

    /** @test */
    public function khoa_khai_hai_ma_khac_nhau_cung_mot_nhan_thi_cong_don()
    {
        // Cung mot khoa, hai chi tieu khac ma nhung trung nhan -> mot cot, cong lai.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x1', 'Đẻ mổ'), $this->m('x2', 'Đẻ mổ')]),
        ], [$this->o(1, 'x1', 3), $this->o(1, 'x2', 4)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([7.0], $b['dong'][0]['o']);
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/GiaoBan/BangDieuTriTest.php
```

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\GiaoBan;

/**
 * Dung cau truc bang tong hop khoi Dieu tri noi tru cho man trinh chieu.
 *
 * Lop THUAN: khong cham co so du lieu, nhan du lieu qua tham so. Cac quy tac ben duoi
 * (loc chi tieu so, gop cot theo nhan, quy null ve 0, bo tong cot phan tram) neu nam trong
 * JavaScript cua blade thi khong co cach nao kiem ngoai nhin bang mat.
 *
 * KHOA COT THEO NHAN, khong theo ma. Ly do lay tu chinh ghi chu cua man Tong quan trong
 * MetricSchema::COMMON_FIELDS: gom theo nhan thi man khong trong lai khi KHTH doi ma chi
 * tieu. Danh doi: khoa doi NHAN thi cot tach doi.
 */
class BangDieuTri
{
    const KHOI = 'dieu_tri';

    /**
     * @param array $configs mang khai bao khoa; moi phan tu co block_type, display_name,
     *                       sort_order, is_active, metrics
     * @param array $cells   mang o; moi phan tu co dept_config_id, metric_code,
     *                       auto_value, manual_value
     * @return array ['cot' => [...], 'dong' => [...], 'tong' => [...]]
     */
    public static function dung(array $configs, array $cells)
    {
        $khoa = self::locKhoa($configs);

        if (empty($khoa)) {
            return ['cot' => [], 'dong' => [], 'tong' => []];
        }

        $cot = self::dungCot($khoa);

        if (empty($cot)) {
            return ['cot' => [], 'dong' => [], 'tong' => []];
        }

        $tra = self::traO($cells);
        $dong = [];

        foreach ($khoa as $k) {
            $o = [];

            foreach ($cot as $c) {
                $o[] = self::giaTri($k, $c['khoa'], $tra);
            }

            $dong[] = ['ten' => (string) $k['display_name'], 'o' => $o];
        }

        return ['cot' => $cot, 'dong' => $dong, 'tong' => self::tong($cot, $dong)];
    }

    /** Khoa thuoc khoi dieu tri, dang bat, sap theo sort_order */
    protected static function locKhoa(array $configs)
    {
        $ra = [];

        foreach ($configs as $c) {
            $block = isset($c['block_type']) ? $c['block_type'] : '';
            $active = !array_key_exists('is_active', $c) || $c['is_active'];

            if ($block === self::KHOI && $active) {
                $ra[] = $c;
            }
        }

        usort($ra, function ($a, $b) {
            $sa = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
            $sb = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;

            return $sa === $sb ? 0 : ($sa < $sb ? -1 : 1);
        });

        return $ra;
    }

    /**
     * Cot theo thu tu xuat hien dau tien: duyet khoa theo sort_order, trong moi khoa duyet
     * chi tieu theo thu tu khai.
     */
    protected static function dungCot(array $khoa)
    {
        $cot = [];
        $viTri = [];

        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if (!isset($viTri[$kh])) {
                    $viTri[$kh] = count($cot);
                    $cot[] = ['khoa' => $kh, 'nhan' => $kh, 'percent' => self::laPercent($m)];
                    continue;
                }

                // Cot chi la percent khi MOI khai bao gop vao no deu la percent.
                if (!self::laPercent($m)) {
                    $cot[$viTri[$kh]]['percent'] = false;
                }
            }
        }

        return $cot;
    }

    /** Chi tieu dang SO cua mot khoa; loai chi tieu nhap tay kieu van ban */
    protected static function chiTieuSo(array $cfg)
    {
        $ra = [];
        $ds = isset($cfg['metrics']) && is_array($cfg['metrics']) ? $cfg['metrics'] : [];

        foreach ($ds as $m) {
            if (!self::laChuoi($m)) {
                $ra[] = $m;
            }
        }

        return $ra;
    }

    /** value_type chi co int|decimal|percent|text; chi text la khong phai so */
    protected static function laChuoi(array $m)
    {
        $type = isset($m['type']) ? $m['type'] : '';
        $vt = isset($m['input']['value_type']) ? $m['input']['value_type'] : '';

        return $type === 'manual' && $vt === 'text';
    }

    protected static function laPercent(array $m)
    {
        $vt = isset($m['input']['value_type']) ? $m['input']['value_type'] : '';

        return $vt === 'percent';
    }

    protected static function khoaCot(array $m)
    {
        $nhan = isset($m['name']) ? trim((string) $m['name']) : '';

        return $nhan !== '' ? $nhan : (string) (isset($m['code']) ? $m['code'] : '');
    }

    /** @return array "deptConfigId|metricCode" => float */
    protected static function traO(array $cells)
    {
        $ra = [];

        foreach ($cells as $o) {
            $id = isset($o['dept_config_id']) ? (int) $o['dept_config_id'] : 0;
            $code = isset($o['metric_code']) ? (string) $o['metric_code'] : '';

            $v = array_key_exists('manual_value', $o) && $o['manual_value'] !== null
                ? $o['manual_value']
                : (isset($o['auto_value']) ? $o['auto_value'] : null);

            $ra[$id . '|' . $code] = $v === null ? 0.0 : (float) $v;
        }

        return $ra;
    }

    /**
     * Gia tri cua mot khoa tai mot cot.
     *
     * Mot khoa co the khai NHIEU chi tieu cung nhan (khac ma) - khi do cong don, vi chung
     * da duoc gop thanh mot cot.
     */
    protected static function giaTri(array $cfg, $khoaCot, array $tra)
    {
        $id = isset($cfg['id']) ? (int) $cfg['id'] : 0;
        $tong = 0.0;

        foreach (self::chiTieuSo($cfg) as $m) {
            if (self::khoaCot($m) !== $khoaCot) {
                continue;
            }

            $key = $id . '|' . (isset($m['code']) ? $m['code'] : '');
            $tong += isset($tra[$key]) ? $tra[$key] : 0.0;
        }

        return $tong;
    }

    /** @return array tong tung cot; null o cot percent */
    protected static function tong(array $cot, array $dong)
    {
        $ra = [];

        foreach ($cot as $i => $c) {
            if (!empty($c['percent'])) {
                $ra[] = null;
                continue;
            }

            $t = 0.0;

            foreach ($dong as $d) {
                $t += isset($d['o'][$i]) ? $d['o'][$i] : 0.0;
            }

            $ra[] = $t;
        }

        return $ra;
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/GiaoBan/BangDieuTriTest.php
```

---

## Task 2: `show()` trả thêm `bang_dieu_tri`

**Tệp:**
- Sửa: `app/Http/Controllers/KHTH/GiaoBanController.php`

**Interfaces:**
- Consumes: `BangDieuTri::dung()` (Task 1)
- Produces: khoá `bang_dieu_tri` trong JSON của `show()`

- [ ] **Bước 1: Dựng bảng trong `show()`**

`show()` hiện dựng `$configs` (đã lọc quyền) và `$cells`. Cả hai đã đúng dạng mảng mà
`BangDieuTri` cần, chỉ thiếu `block_type`, `sort_order`, `is_active` trong `$configs` vì
đoạn `map()` chỉ lấy bốn trường.

Không sửa `$configs` đang trả ra client — đó là dữ liệu các slide khoa đang dùng. Thay vào
đó dựng danh sách riêng từ `$allConfigs` (chưa bị `map()` cắt trường), lọc theo `$visibleIds`:

```php
        // Bang tong hop khoi dieu tri: dung tu khai bao DAY DU (allConfigs), khong dung
        // $configs vi bien do da bi map() cat bot truong block_type / sort_order.
        $bangDieuTri = \App\Services\GiaoBan\BangDieuTri::dung(
            $allConfigs
                ->filter(function ($c) use ($visibleIds) {
                    return in_array((int) $c->id, $visibleIds, true);
                })
                ->map(function ($c) {
                    return [
                        'id' => (int) $c->id,
                        'block_type' => $c->block_type,
                        'display_name' => $c->display_name,
                        'sort_order' => (int) $c->sort_order,
                        'is_active' => (bool) $c->is_active,
                        'metrics' => $c->metricList(),
                    ];
                })
                ->values()
                ->all(),
            $cells
        );
```

Đặt đoạn này **sau** khi `$cells` đã dựng xong.

- [ ] **Bước 2: Thêm vào mảng trả về**

Đọc câu `return response()->json([...])` cuối `show()` rồi thêm khoá:

```php
            'bang_dieu_tri' => $bangDieuTri,
```

- [ ] **Bước 3: Kiểm bằng tay**

```bash
php artisan tinker --execute="echo 'ok';"
```

Rồi mở màn giao ban và gọi endpoint `show` với ngày có báo cáo, xác nhận JSON có khoá
`bang_dieu_tri` với `cot`/`dong`/`tong`. Nếu không có báo cáo cho ngày đó thì `cells` rỗng
nên mọi ô bằng 0 — vẫn phải trả đủ cột.

---

## Task 3: Vẽ slide và đưa Kíp trực lên đầu Tổng quan

**Tệp:**
- Sửa: `resources/views/khth/giaoban-present.blade.php`

**Interfaces:**
- Consumes: `data.bang_dieu_tri` (Task 2)

- [ ] **Bước 1: Thêm CSS cho bảng**

Đặt cạnh các khối CSS hiện có:

```css
  .bdt-wrap { flex: 1; overflow: auto; }
  .bdt { width: 100%; border-collapse: collapse; color: #dbe6f0; }
  .bdt th, .bdt td { border: 1px solid #24405c; padding: .5vh .6vw; white-space: nowrap; }
  .bdt th { background: #14293e; color: #8aa4bd; font-weight: 600; text-align: center; }
  .bdt td.ten { text-align: left; color: #fff; }
  .bdt td { text-align: center; }
  .bdt tr.tong td { background: #14293e; color: #fff; font-weight: 700; }
```

- [ ] **Bước 2: Thêm hàm vẽ slide**

Đặt cạnh `phongKhamSlide`:

```js
  /**
   * Bang tong hop khoi Dieu tri noi tru. May chu da dung san cau truc (BangDieuTri),
   * o day chi ve.
   *
   * Co chu nho dan theo so cot: 20+ cot ma giu co chu goc thi tran khoi man chieu.
   * Co SAN toi thieu; cham san ma van tran thi .bdt-wrap cho cuon.
   */
  function dieuTriSlide(data) {
    var b = data.bang_dieu_tri;
    if (!b || !b.cot || !b.cot.length || !b.dong || !b.dong.length) return '';

    var soCot = b.cot.length;
    var co = soCot <= 8 ? 2.0 : (soCot <= 14 ? 1.7 : (soCot <= 20 ? 1.45 : 1.25));

    var thead = '<tr><th style="text-align:left">KHOA PHÒNG</th>' +
      b.cot.map(function (c) { return '<th>' + esc(c.nhan) + '</th>'; }).join('') + '</tr>';

    var tbody = b.dong.map(function (d) {
      return '<tr><td class="ten">' + esc(d.ten) + '</td>' +
        d.o.map(function (v) { return '<td>' + num(v) + '</td>'; }).join('') + '</tr>';
    }).join('');

    var tfoot = '<tr class="tong"><td class="ten">TỔNG CỘNG</td>' +
      b.tong.map(function (v) { return '<td>' + (v === null ? '—' : num(v)) + '</td>'; }).join('') +
      '</tr>';

    return '<div class="slide"><div class="s-head"><div class="s-title">Hoạt động điều trị</div>' +
      '<div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="bdt-wrap"><table class="bdt" style="font-size:' + co + 'vh">' +
      '<thead>' + thead + '</thead><tbody>' + tbody + tfoot + '</tbody></table></div></div>';
  }
```

- [ ] **Bước 3: Chèn slide vào `build()`**

Trong `build()`, ngay **sau** `slides.push(overviewSlide(data));`:

```js
    var dtHtml = dieuTriSlide(data);
    if (dtHtml) {
      deptNames.push({ idx: slides.length, name: 'Hoạt động điều trị' });
      slides.push(dtHtml);
    }
```

Chú ý: `deptNames` phải gán chỉ số **trước** khi `slides.push`, đúng như các khối hiện có —
có ghi chú sẵn trong tệp rằng gán sai thứ tự thì bấm tên khoa sẽ nhảy sai slide.

- [ ] **Bước 4: Đưa Kíp trực lãnh đạo lên đầu slide Tổng quan**

Trong `overviewSlide()`, câu `return` cuối đang ghép:

```
'<div class="kpis" ...>' + kpiHtml + '</div>' + lechHtml + thieuHtml + dutyHtml + noteHtml
```

Đổi thành:

```
dutyHtml + '<div class="kpis" ...>' + kpiHtml + '</div>' + lechHtml + thieuHtml + noteHtml
```

`dutyHtml` đang có `style="margin-top:1.6vh"` — khi lên đầu thì khoảng cách trên thừa, đổi
thành `margin-bottom:1.6vh`.

- [ ] **Bước 5: Kiểm bằng mắt trên trình chiếu**

Mở màn trình chiếu với ngày có báo cáo. Xác nhận:

- Slide thứ hai là `Hoạt động điều trị`, thanh điều hướng có tên đó và bấm vào nhảy đúng.
- Bảng có dòng cho từng khoa khối điều trị, dòng TỔNG CỘNG khớp tổng các dòng trên.
- Khoa không khai chỉ tiêu nào đó hiện `0`, không phải `—`.
- `Danh sách mổ phiên` (chỉ tiêu chuỗi) **không** xuất hiện thành cột.
- Trên slide Tổng quan, khối KÍP TRỰC LÃNH ĐẠO nằm trên cùng.

---

## Task 4: Chạy toàn bộ và commit

- [ ] **Bước 1: Bộ Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

- [ ] **Bước 2: Commit và push**

```bash
git add app/Services/GiaoBan app/Http/Controllers/KHTH/GiaoBanController.php resources/views/khth/giaoban-present.blade.php docs/superpowers tests/Unit/GiaoBan
```

Commit message ghi rõ: bảng khoá cột theo **nhãn** chứ không theo mã, và lý do lấy từ ghi chú
của màn Tổng quan trong `MetricSchema`.
