# Báo cáo giao ban — bốn điều chỉnh — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mở quyền "Lấy số liệu" cho người được phân công khoa, bỏ dòng lệch cân đối khỏi slide Tổng quan, thêm cờ chọn cột cho slide Hoạt động điều trị, và thêm công cụ chẩn đoán cho báo lỗi phân quyền khoa.

**Architecture:** Dự án là Laravel 5.x + AdminLTE, view Blade thuần với jQuery, không build frontend. Logic quyền và logic dựng bảng nằm ở các lớp **thuần** (`GiaoBanPermission`, `BangDieuTri`, `MetricValidator`, `MetricSchema`) — không chạm DB, nhận dữ liệu qua tham số, có unit test đầy đủ. Mọi thay đổi hành vi đi vào các lớp thuần này trước, controller và blade chỉ gọi lại. Không có migration, không đổi cấu trúc bảng.

**Tech Stack:** PHP 7 / Laravel 5, PHPUnit 6.5, Blade + jQuery + AdminLTE 2, Oracle (kết nối `HISPro` và `ACS_RS`).

## Global Constraints

- Chạy test bằng: `php vendor/bin/phpunit --filter GiaoBan`. Baseline hiện tại **151 tests, 384 assertions, OK** — mọi task phải giữ nguyên trạng thái xanh này.
- Comment trong code viết **tiếng Việt không dấu**, giải thích *tại sao* chứ không mô tả *cái gì* — theo đúng khuôn mẫu các tệp `app/Services/GiaoBan/*`.
- Tên test viết tiếng Việt không dấu, dùng annotation `/** @test */` (không dùng tiền tố `test`), theo `tests/Unit/GiaoBan/*`.
- Chuỗi hiển thị cho người dùng viết **tiếng Việt có dấu**.
- Không tạo migration. Cờ `dieu_tri_slide` nằm trong cột JSON `giaoban_dept_configs.metrics`, giống mọi thuộc tính chỉ tiêu khác.
- Không đổi chữ ký các hàm public đã có (`BangDieuTri::dung`, `MetricValidator::validate`, `GiaoBanPermission::visibleDeptConfigIds`, ...) — có nơi khác gọi.
- Commit sau mỗi task. Tiền tố commit: `feat(giaoban):` hoặc `fix(giaoban):`. Nội dung commit viết tiếng Việt không dấu.

---

### Task 1: Mở quyền "Lấy số liệu" cho người được phân công khoa

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanPermission.php` (thêm method sau `canEditReport`, khoảng dòng 22)
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php:40-46` (`index`), `:193-196` (`fetchData`)
- Modify: `resources/views/khth/giaoban-index.blade.php:19-38` (thanh công cụ), `:76` (biến JS), `:159-171` (nhánh thông báo)
- Test: `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`

**Interfaces:**
- Consumes: `GiaoBanController::isAdmin()` (trả bool), `GiaoBanController::assignedDeptIds()` (trả mảng `dept_config_id`) — cả hai đã có sẵn.
- Produces: `GiaoBanPermission::canFetchData($isAdmin, array $assignedDeptIds): bool`. View `khth.giaoban-index` nhận thêm biến `$canFetch` (bool); JS toàn cục thêm `CAN_FETCH` (bool).

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`, ngay trước dấu `}` đóng class:

```php
    // ===== Quyen LAY SO LIEU tu HIS =====

    /** @test */
    public function admin_luon_duoc_lay_so_lieu()
    {
        $this->assertTrue(GiaoBanPermission::canFetchData(true, []));
        $this->assertTrue(GiaoBanPermission::canFetchData(true, [3]));
    }

    /** @test */
    public function nguoi_duoc_gan_khoa_duoc_lay_so_lieu()
    {
        $this->assertTrue(GiaoBanPermission::canFetchData(false, [3]));
        $this->assertTrue(GiaoBanPermission::canFetchData(false, [3, 5]));
    }

    /** @test */
    public function chua_duoc_gan_khoa_nao_thi_khong_duoc_lay_so_lieu()
    {
        $this->assertFalse(GiaoBanPermission::canFetchData(false, []));
    }
```

- [ ] **Step 2: Chạy test để xác nhận nó đỏ**

```bash
php vendor/bin/phpunit --filter GiaoBanPermissionTest
```

Kỳ vọng: FAIL với `Call to undefined method App\Services\GiaoBan\GiaoBanPermission::canFetchData()`.

- [ ] **Step 3: Cài đặt tối thiểu**

Trong `app/Services/GiaoBan/GiaoBanPermission.php`, chèn ngay sau method `canEditReport` (sau dòng 22):

```php

    /**
     * Ai duoc bam "Lay so lieu" tu HIS.
     *
     * Nguoi da duoc gan khoa la nguoi chiu trach nhiem nhap lieu -> cho ho tu lay thay vi
     * cho KHTH bam ho ngoai gio hanh chinh. Pham vi thao tac bang admin (tu chon khung gio,
     * tao duoc bao cao moi) — day la quyet dinh co y thuc cua nguoi dat yeu cau.
     *
     * An toan vi fetchAndStore() chi ghi auto_value, khong de len manual_value cua khoa khac,
     * va chi khoi tao o ke thua o lan lay dau tien cua bao cao.
     *
     * @param bool  $isAdmin          user->can('giaoban-admin')
     * @param array $assignedDeptIds  dept_config_id duoc gan trong giaoban_user_departments
     */
    public static function canFetchData($isAdmin, array $assignedDeptIds)
    {
        if ($isAdmin) return true;
        return count($assignedDeptIds) > 0;
    }
```

- [ ] **Step 4: Chạy test để xác nhận nó xanh**

```bash
php vendor/bin/phpunit --filter GiaoBanPermissionTest
```

Kỳ vọng: PASS.

- [ ] **Step 5: Nối vào controller**

Trong `app/Http/Controllers/KHTH/GiaoBanController.php`, thay toàn bộ method `index()` (dòng 40-46) bằng:

```php
    public function index()
    {
        $isAdmin = $this->isAdmin();
        $assigned = $this->assignedDeptIds();

        return view('khth.giaoban-index', [
            'isAdmin' => $isAdmin,
            'assignedDeptIds' => $assigned,
            'canFetch' => GiaoBanPermission::canFetchData($isAdmin, $assigned),
        ]);
    }
```

Trong method `fetchData()`, thay dòng 195:

```php
        if (!$this->isAdmin()) abort(403);
```

bằng:

```php
        // Khong con la dac quyen cua KHTH: khoa duoc gan cung tu lay duoc so lieu.
        if (!GiaoBanPermission::canFetchData($this->isAdmin(), $this->assignedDeptIds())) abort(403);
```

`GiaoBanPermission` đã được `use` ở đầu tệp (dòng 15), không cần thêm import.

- [ ] **Step 6: Sửa thanh công cụ trên blade**

Trong `resources/views/khth/giaoban-index.blade.php`, thay khối dòng 19-38 bằng:

```blade
      {{-- Hai mốc thời gian chỉ là tham số cho "Lấy số liệu". Ai không được lấy thì
           hiện ra chỉ tổ rối. --}}
      @if($canFetch)
      <div class="col-md-2"><label>Từ thời điểm</label>
        <input type="datetime-local" id="from_time" class="form-control"></div>
      <div class="col-md-2"><label>Đến thời điểm</label>
        <input type="datetime-local" id="to_time" class="form-control"></div>
      @endif
      <div class="col-md-{{ $canFetch ? 6 : 10 }}" style="padding-top:24px">
        <button id="btn-view" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
        {{-- Trình chiếu và Xuất Excel đều là số liệu toàn viện -> chỉ admin. Để ngoài thì
             người khoa vẫn thấy nút rồi bấm vào ăn 403. --}}
        @if($isAdmin)
        <button id="btn-present" class="btn btn-info"><i class="fa fa-desktop"></i> Trình chiếu</button>
        @endif
        {{-- Lấy số liệu tách riêng: người được gán khoa cũng bấm được, xem
             GiaoBanPermission::canFetchData. --}}
        @if($canFetch)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-cloud-download"></i> Lấy số liệu</button>
        @endif
        @if($isAdmin)
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
        @endif
      </div>
```

- [ ] **Step 7: Sửa biến JS và nhánh thông báo**

Vẫn trong tệp đó, sau dòng 76 (`var IS_ADMIN = ...`) thêm:

```js
var CAN_FETCH = {{ $canFetch ? 'true' : 'false' }};
```

Rồi trong hàm `render()`, thay khối dòng 160-169 bằng:

```js
    // Nguoi khong duoc lay so lieu thi dung chi ho bam mot nut khong ton tai voi ho.
    if (CAN_FETCH) {
      $('#report-status').text('(chưa có dữ liệu — bấm Lấy số liệu)');
    } else {
      $('#report-status').text('');
      $body.html('<div class="callout callout-info">' +
        '<h4><i class="fa fa-clock-o"></i> Chưa có số liệu cho ngày này</h4>' +
        '<p>Phòng KHTH chưa lấy số liệu từ HIS cho ngày giao ban đã chọn. ' +
        'Bấm <b>Làm mới</b> để kiểm tra lại, hoặc chọn ngày khác.</p></div>');
    }
```

- [ ] **Step 8: Chạy toàn bộ test giao ban**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, số test = 151 + 3 = **154**.

- [ ] **Step 9: Kiểm tra blade biên dịch được**

```bash
php artisan view:clear
```

Kỳ vọng: `Compiled views cleared!`, không có lỗi cú pháp.

- [ ] **Step 10: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanPermission.php app/Http/Controllers/KHTH/GiaoBanController.php resources/views/khth/giaoban-index.blade.php tests/Unit/GiaoBan/GiaoBanPermissionTest.php
git commit -m "feat(giaoban): nguoi duoc gan khoa cung duoc lay so lieu tu HIS"
```

---

### Task 2: Bỏ dòng "LỆCH CÂN ĐỐI" khỏi slide Tổng quan

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php:307-315` (khai báo), `:337` (chuỗi HTML trả về)

**Interfaces:**
- Consumes: JSON từ `GiaoBanController::show()` — khóa `balance_warnings` vẫn được trả về như cũ, không đụng backend.
- Produces: không có gì cho task sau.

Task này không có unit test: đây là JavaScript nhúng trong Blade, dự án không có hạ tầng test JS. Nghiệm thu bằng kiểm tra tĩnh + mắt.

- [ ] **Step 1: Xóa phần dựng khối cảnh báo lệch**

Trong `resources/views/khth/giaoban-present.blade.php`, hàm `overviewSlide()`, xóa **toàn bộ** khối dòng 307-315:

```js
    // Hai khoi canh bao: tra loi "hom nay co gi bat thuong", thu ma cac slide sau khong noi.
    var lech = data.balance_warnings || {};
    var dsLech = [];
    data.configs.forEach(function (cfg) {
      if (lech[cfg.id]) dsLech.push(esc(cfg.display_name) + ' (' + lech[cfg.id] + ')');
    });
    var lechHtml = '<div class="ov-canh-bao' + (dsLech.length ? ' xau' : ' tot') + '">' +
      '<span class="lbl">LỆCH CÂN ĐỐI</span> ' +
      (dsLech.length ? dsLech.length + ' khoa: ' + dsLech.join(' · ') : 'Không khoa nào lệch') + '</div>';
```

và thay bằng:

```js
    // Chi con MOT khoi canh bao tren man tong hop. Lech can doi da bo khoi day theo yeu cau
    // su dung: no van hien o badge tren slide tung khoa va o man nhap lieu — hai cho co ngu
    // canh de xu ly, con man tong hop thi chi lam nhieu.
```

- [ ] **Step 2: Bỏ `lechHtml` khỏi chuỗi HTML trả về**

Vẫn trong `overviewSlide()`, dòng 337 hiện là:

```js
      lechHtml + thieuHtml + noteHtml + '</div>';
```

Đổi thành:

```js
      thieuHtml + noteHtml + '</div>';
```

- [ ] **Step 3: Xác nhận không còn tham chiếu mồ côi**

```bash
grep -n "lechHtml\|dsLech\|LỆCH CÂN ĐỐI" resources/views/khth/giaoban-present.blade.php
```

Kỳ vọng: **không có kết quả nào**. Nếu còn dòng nào thì Step 1 hoặc 2 làm sót.

- [ ] **Step 4: Xác nhận hai chỗ báo lệch khác vẫn còn nguyên**

```bash
grep -n "Lệch cân đối" resources/views/khth/giaoban-present.blade.php resources/views/khth/giaoban-index.blade.php
```

Kỳ vọng: đúng **2** kết quả — một ở `giaoban-present.blade.php` (badge `▲` trong `deptSlide`, khoảng dòng 448) và một ở `giaoban-index.blade.php` (icon cảnh báo, khoảng dòng 183). Giữ nguyên có chủ đích.

- [ ] **Step 5: Kiểm tra blade biên dịch được**

```bash
php artisan view:clear
```

Kỳ vọng: `Compiled views cleared!`, không có lỗi cú pháp.

- [ ] **Step 6: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php
git commit -m "fix(giaoban): bo dong lech can doi khoi slide Tong quan"
```

---

### Task 3: Khai báo cờ `dieu_tri_slide` và kiểm tra cấu hình

**Files:**
- Modify: `app/Services/GiaoBan/MetricSchema.php:106-110` (`COMMON_FIELDS`)
- Modify: `app/Services/GiaoBan/MetricValidator.php:93-128` (`kiemKhoaDungChung`)
- Test: `tests/Unit/GiaoBan/MetricValidatorTest.php`

**Interfaces:**
- Consumes: `MetricSchema::COMMON_FIELDS` đã được `giaoban-config.blade.php:68` truyền thẳng xuống form builder JS, nên thêm một khóa `widget => bool` là ô checkbox tự mọc trên form — không phải sửa JS.
- Produces: khóa `dieu_tri_slide` (bool) trên mỗi chỉ tiêu trong JSON `giaoban_dept_configs.metrics`. Task 4 đọc khóa này.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối `tests/Unit/GiaoBan/MetricValidatorTest.php`, ngay trước dấu `}` đóng class:

```php
    // ===== Co chon cot cho slide Hoat dong dieu tri =====

    /** @test */
    public function bat_co_hien_o_slide_dieu_tri_la_hop_le()
    {
        $m = [['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from', 'dieu_tri_slide' => true]];

        $this->assertSame([], MetricValidator::validate($m, 'dieu_tri'));
    }

    /** @test */
    public function co_slide_dieu_tri_phai_la_true_false()
    {
        $m = [['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from', 'dieu_tri_slide' => 'co']];
        $loi = MetricValidator::validate($m, 'dieu_tri');

        $this->assertCount(1, $loi);
        $this->assertEquals('dieu_tri_slide', $loi[0]['field']);
    }

    /** @test */
    public function chi_tieu_chuoi_khong_bat_co_slide_dieu_tri_duoc()
    {
        // Bang chi cong duoc so -> chan ngay tu cau hinh, dung de slide am tham bo qua.
        $m = [['code' => 'ds_mo', 'name' => 'Danh sách mổ', 'type' => 'manual',
               'input' => ['value_type' => 'text'], 'dieu_tri_slide' => true]];
        $loi = MetricValidator::validate($m, 'dieu_tri');

        $this->assertCount(1, $loi);
        $this->assertEquals('dieu_tri_slide', $loi[0]['field']);
    }

    /** @test */
    public function chi_tieu_nhap_tay_kieu_so_bat_co_slide_dieu_tri_duoc()
    {
        $m = [['code' => 'de_mo', 'name' => 'Đẻ mổ', 'type' => 'manual',
               'input' => ['value_type' => 'int'], 'dieu_tri_slide' => true]];

        $this->assertSame([], MetricValidator::validate($m, 'dieu_tri'));
    }
```

- [ ] **Step 2: Chạy test để xác nhận nó đỏ**

```bash
php vendor/bin/phpunit --filter MetricValidatorTest
```

Kỳ vọng: 2 test đỏ (`co_slide_dieu_tri_phai_la_true_false` và `chi_tieu_chuoi_khong_bat_co_slide_dieu_tri_duoc`) với thông báo kiểu `Failed asserting that actual size 0 matches expected size 1` — vì hiện chưa có luật nào cho khóa này. Hai test còn lại xanh sẵn (không có luật thì không có lỗi).

- [ ] **Step 3: Khai báo cờ trong MetricSchema**

Trong `app/Services/GiaoBan/MetricSchema.php`, thay hằng `COMMON_FIELDS` (dòng 106-110) bằng:

```php
    const COMMON_FIELDS = [
        'overview'       => ['widget' => 'bool', 'label' => 'Hiện ở màn Tổng quan'],
        'overview_label' => ['widget' => 'text', 'label' => 'Nhãn gộp trên Tổng quan', 'max' => 60,
                             'show_if' => ['overview' => [true]]],
        // Chon cot cho slide Hoat dong dieu tri. Khong bat co nao thi slide hien TAT CA cot
        // nhu truoc — xem BangDieuTri::dungCot().
        'dieu_tri_slide' => ['widget' => 'bool', 'label' => 'Hiện ở slide Hoạt động điều trị'],
    ];
```

Cập nhật luôn khối docblock ngay trên hằng (dòng 98-105) — thêm một đoạn sau đoạn nói về `overview`:

```php
     * `dieu_tri_slide` chon cot cho slide Hoat dong dieu tri. Cung theo NHAN: he mot chi tieu
     * mang nhan do bat co thi cot len slide, va moi khoa khai cung nhan deu do so vao — KHTH
     * chi phai bat mot noi thay vi nho bat o tung khoa.
```

- [ ] **Step 4: Thêm luật kiểm tra**

Trong `app/Services/GiaoBan/MetricValidator.php`, method `kiemKhoaDungChung()`, chèn ngay **trước** dòng `return $loi;` cuối method (dòng 127):

```php
        // Co chon cot slide Hoat dong dieu tri. Cung luat voi overview: bang chi cong duoc so.
        $batSlide = isset($m['dieu_tri_slide']) && $m['dieu_tri_slide'] !== false && $m['dieu_tri_slide'] !== '';

        if (isset($m['dieu_tri_slide']) && !is_bool($m['dieu_tri_slide'])) {
            $loi[] = self::loi($i, 'dieu_tri_slide', "'dieu_tri_slide' phải là true/false.");
            $batSlide = false;
        }

        if ($batSlide && $type === 'manual') {
            $inSlide = isset($m['input']) && is_array($m['input']) ? $m['input'] : [];
            if (isset($inSlide['value_type']) && $inSlide['value_type'] === 'text') {
                $loi[] = self::loi($i, 'dieu_tri_slide',
                    'Chỉ tiêu kiểu chuỗi không hiện được ở slide Hoạt động điều trị (bảng chỉ cộng được số).');
            }
        }

```

Cập nhật docblock của method (dòng 93-96): đổi `Kiem hai khoa dung chung cho moi loai chi tieu: overview / overview_label.` thành `Kiem cac khoa dung chung cho moi loai chi tieu: overview / overview_label / dieu_tri_slide.`

Lưu ý: dùng tên biến `$inSlide` chứ không phải `$in` — `$in` đã được dùng trong nhánh `overview` ở trên cùng method.

- [ ] **Step 5: Chạy test để xác nhận nó xanh**

```bash
php vendor/bin/phpunit --filter MetricValidatorTest
```

Kỳ vọng: PASS toàn bộ.

- [ ] **Step 6: Chạy toàn bộ test giao ban**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, số test = 154 + 4 = **158**.

- [ ] **Step 7: Kiểm tra form builder mọc ô mới**

Mở màn cấu hình giao ban (`/khth/giao-ban/cau-hinh`), bấm sửa một khoa khối Điều trị, mở form builder một chỉ tiêu. Kỳ vọng: thấy checkbox **"Hiện ở slide Hoạt động điều trị"** bên cạnh "Hiện ở màn Tổng quan". Bật lên rồi lưu — lưu thành công, mở lại thấy checkbox vẫn bật.

Nếu ô không mọc ra: form builder không render `COMMON_FIELDS` theo vòng lặp như giả định. Dừng lại, đọc `resources/views/khth/partials/giaoban-metric-builder.blade.php` và báo lại trước khi tự sửa.

- [ ] **Step 8: Commit**

```bash
git add app/Services/GiaoBan/MetricSchema.php app/Services/GiaoBan/MetricValidator.php tests/Unit/GiaoBan/MetricValidatorTest.php
git commit -m "feat(giaoban): khai bao co chon cot cho slide Hoat dong dieu tri"
```

---

### Task 4: `BangDieuTri` dựng cột theo cờ

**Files:**
- Modify: `app/Services/GiaoBan/BangDieuTri.php:89-116` (`dungCot`), thêm hai helper sau `laPercent` (khoảng dòng 147)
- Test: `tests/Unit/GiaoBan/BangDieuTriTest.php`

**Interfaces:**
- Consumes: khóa `dieu_tri_slide` (bool) trên mỗi phần tử `metrics` — do Task 3 khai báo.
- Produces: `BangDieuTri::dung()` giữ nguyên chữ ký và cấu trúc trả về `['cot' => [...], 'dong' => [...], 'tong' => [...]]`; chỉ nội dung `cot` thay đổi. `giaoban-present.blade.php` không phải sửa gì.

> **Cập nhật sau khi thực thi (2026-07-31):** cấu trúc hai vòng lặp mô tả ở Step 3 bên dưới
> **không còn hiệu lực**. Task review nêu finding trùng lặp khối logic; người ra quyết định chọn
> reviewer thắng. Bản đã giao (`daf0274`) gộp về **một lượt duyệt**, theo dõi trạng thái `percent`
> theo NHÃN trong một map độc lập với việc cột đã tạo hay chưa, rồi chiếu ra mảng `cot` ở bước
> cuối. Hai quy tắc hành vi ngay dưới đây **vẫn giữ nguyên hiệu lực** — chúng mới là phần cốt lõi.

**Hai quy tắc phải giữ đúng — đọc kỹ trước khi code:**

1. Cờ chọn **CỘT**, không chọn từng ô. Cột đã tồn tại thì **mọi** khoa khai cùng nhãn đều đổ giá trị vào, kể cả khoa không bật cờ. Vì vậy `giaTri()` **không** đổi.
2. Cột chỉ mang `percent = true` khi **mọi** khai báo gộp vào nó đều `percent` — kể cả khai báo không bật cờ, vì giá trị của nó vẫn được cộng vào. Việc hạ cờ `percent` phải là một **vòng lặp riêng** chạy sau khi cột đã tạo xong: khoa làm mất tính percent có thể được duyệt *trước* khoa tạo ra cột.

- [ ] **Step 1: Viết test thất bại**

Trong `tests/Unit/GiaoBan/BangDieuTriTest.php`, thêm helper ngay sau method `m()` (sau dòng 27):

```php
    /** Chi tieu co bat co hien tren slide Hoat dong dieu tri. */
    private function mCo($code, $name, $type = 'census_from', $valueType = null)
    {
        $m = $this->m($code, $name, $type, $valueType);
        $m['dieu_tri_slide'] = true;

        return $m;
    }
```

Rồi thêm vào cuối class, trước dấu `}` đóng:

```php
    // ===== Co chon cot cho slide =====

    /** @test */
    public function khong_co_nao_bat_thi_hien_toan_bo_cot_nhu_truoc()
    {
        // Tuong thich nguoc: trien khai xong ma chua ai cau hinh thi slide khong duoc trang.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('a', 'A'), $this->m('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B'], $nhan);
    }

    /** @test */
    public function co_it_nhat_mot_co_thi_chi_cot_bat_co_len_slide()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('bn_cu', 'BN cũ'), $this->m('bn_vao', 'BN vào')]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }

    /** @test */
    public function khoa_khong_bat_co_van_do_so_vao_cot_da_ton_tai()
    {
        // Co chon COT chu khong chon o: KHTH bat mot noi, moi khoa cung nhan deu duoc cong.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('de_mo', 'Đẻ mổ')]),
            $this->cfg(2, 'B', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'de_mo', 3), $this->o(2, 'de_mo', 5)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([3.0], $b['dong'][0]['o']);
        $this->assertSame([5.0], $b['dong'][1]['o']);
        $this->assertSame([8.0], $b['tong']);
    }

    /** @test */
    public function khai_bao_khong_bat_co_van_lam_cot_mat_tinh_percent()
    {
        // Khoa A (sort 1, khong bat co, so tuyet doi) duyet TRUOC khoa B (sort 2, bat co,
        // percent). So cua A van duoc cong vao cot -> cot khong con la percent, phai co tong.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x', 'X', 'manual', 'int')]),
            $this->cfg(2, 'B', 2, [$this->mCo('x', 'X', 'manual', 'percent')]),
        ], [$this->o(1, 'x', 2), $this->o(2, 'x', 40)]);

        $this->assertCount(1, $b['cot']);
        $this->assertFalse($b['cot'][0]['percent']);
        $this->assertSame([42.0], $b['tong']);
    }

    /** @test */
    public function cot_toan_percent_va_deu_bat_co_thi_van_khong_cong_tong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->mCo('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
        ], [$this->o(1, 'ty_le', 40), $this->o(2, 'ty_le', 60)]);

        $this->assertTrue($b['cot'][0]['percent']);
        $this->assertSame([null], $b['tong']);
    }

    /** @test */
    public function loc_theo_co_van_giu_thu_tu_sort_order_roi_thu_tu_khai()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('c', 'C'), $this->mCo('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->mCo('a', 'A'), $this->mCo('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B'], $nhan);
    }

    /** @test */
    public function chi_tieu_chuoi_bat_co_van_khong_thanh_cot()
    {
        // MetricValidator da chan tu cau hinh, nhung du lieu cu co the con — khong duoc vo.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [
                $this->mCo('bn_cu', 'BN cũ'),
                $this->mCo('ds_mo', 'Danh sách mổ', 'manual', 'text'),
            ]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }
```

- [ ] **Step 2: Chạy test để xác nhận nó đỏ**

```bash
php vendor/bin/phpunit --filter BangDieuTriTest
```

Kỳ vọng: đỏ ở `co_it_nhat_mot_co_thi_chi_cot_bat_co_len_slide` (nhận 2 cột, mong 1) và `loc_theo_co_van_giu_thu_tu_sort_order_roi_thu_tu_khai` (nhận `['A','B','C']`, mong `['A','B']`). Các test còn lại xanh sẵn — đúng, chúng chốt hành vi phải **giữ nguyên**.

- [ ] **Step 3: Cài đặt**

Trong `app/Services/GiaoBan/BangDieuTri.php`, thay toàn bộ method `dungCot()` (dòng 85-116, kể cả docblock) bằng:

```php
    /**
     * Cot theo thu tu xuat hien dau tien: duyet khoa theo sort_order, trong moi khoa duyet
     * chi tieu theo thu tu khai.
     *
     * Co `dieu_tri_slide` chon COT chu khong chon o: he mot chi tieu mang nhan do bat co thi
     * cot len slide, va moi khoa khai cung nhan deu do so vao (xem giaTri). Khong khoa nao bat
     * co thi hien tat ca — neu khong, trien khai xong la slide trang cho toi khi KHTH cau hinh.
     */
    protected static function dungCot(array $khoa)
    {
        $locTheoCo = self::coChiTieuBatCo($khoa);
        $cot = [];
        $viTri = [];

        // Vong 1: TAO cot.
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if ($kh === '' || isset($viTri[$kh])) {
                    continue;
                }

                if ($locTheoCo && !self::batCo($m)) {
                    continue;
                }

                $viTri[$kh] = count($cot);
                $cot[] = ['khoa' => $kh, 'nhan' => $kh, 'percent' => true];
            }
        }

        // Vong 2: ha co percent. Cot chi la percent khi MOI khai bao gop vao no deu la percent
        // — ke ca khai bao KHONG bat co, vi gia tri cua no van duoc cong vao cot. Phai tach
        // thanh vong rieng: khoa lam mat tinh percent co the duoc duyet TRUOC khoa tao ra cot.
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if ($kh === '' || !isset($viTri[$kh])) {
                    continue;
                }

                if (!self::laPercent($m)) {
                    $cot[$viTri[$kh]]['percent'] = false;
                }
            }
        }

        return $cot;
    }

    /** Co it nhat mot chi tieu SO bat co -> bat che do chon cot. */
    protected static function coChiTieuBatCo(array $khoa)
    {
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                if (self::batCo($m)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Chi tieu duoc danh dau hien tren slide Hoat dong dieu tri.
     *
     * Dung !empty chu khong ===true: MetricValidator ep kieu bool tu nay tro di, nhung ban ghi
     * cu di qua duong khac co the mang 1 hoac '1'.
     */
    protected static function batCo(array $m)
    {
        return !empty($m['dieu_tri_slide']);
    }
```

`giaTri()` **không** đổi.

- [ ] **Step 4: Chạy test để xác nhận nó xanh**

```bash
php vendor/bin/phpunit --filter BangDieuTriTest
```

Kỳ vọng: PASS toàn bộ.

- [ ] **Step 5: Chạy toàn bộ test giao ban**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, số test = 158 + 7 = **165**.

- [ ] **Step 6: Commit**

```bash
git add app/Services/GiaoBan/BangDieuTri.php tests/Unit/GiaoBan/BangDieuTriTest.php
git commit -m "feat(giaoban): slide Hoat dong dieu tri chi hien cot da danh dau"
```

---

### Task 5: Dòng trạng thái quyền và tài liệu chẩn đoán

**Files:**
- Modify: `resources/views/khth/giaoban-index.blade.php` (thêm ô hiển thị trong thanh công cụ, thêm hàm `renderCheDo`, gọi trong `loadReport`)
- Create: `docs/giaoban-chan-doan-phan-quyen-khoa.md`

**Interfaces:**
- Consumes: JSON của `GiaoBanController::show()` — dùng `res.is_admin` (bool) và `res.configs` (mảng, mỗi phần tử có `display_name`). Cả hai đã có sẵn, không thêm API.
- Produces: không có gì cho task sau.

Đây là công cụ chẩn đoán cho báo lỗi ý 4, **không** sửa logic quyền — logic hiện tại đã lọc đúng ở server và chưa có bằng chứng nó sai.

- [ ] **Step 1: Thêm ô hiển thị vào thanh công cụ**

Trong `resources/views/khth/giaoban-index.blade.php`, ngay sau thẻ `</div>` đóng cột nút bấm và **trước** thẻ `</div>` đóng `.row` (tức trước dòng `    </div>` ở khoảng dòng 39), chèn:

```blade
      <div class="col-md-12" style="padding-top:12px">
        <small id="che-do" class="text-muted"></small>
      </div>
```

- [ ] **Step 2: Thêm hàm dựng dòng trạng thái**

Vẫn trong tệp đó, chèn ngay **sau** hàm `esc()` (sau dòng 123):

```js
/**
 * Dong trang thai quyen.
 *
 * Cong cu chan doan cho phan anh "da phan quyen khoa nhung van thay tat ca khoa": nhin mot cai
 * la biet tai khoan dang o che do nao, khong phai hoi KHTH. Tra loi luon cau hoi thuong gap
 * "sao toi khong thay khoa X".
 *
 * Doc tu chinh JSON ma man hinh dang ve, nen no phan anh dung cai server thuc su tra ve.
 */
function renderCheDo(res) {
  var $o = $('#che-do');
  if (res.is_admin) {
    $o.html('<i class="fa fa-shield"></i> Chế độ: <b>Quản trị</b> — xem toàn viện');
    return;
  }
  var ten = (res.configs || []).map(function (c) { return esc(c.display_name); });
  $o.html('<i class="fa fa-user"></i> Chế độ: <b>Khoa</b> — được phân công ' + ten.length + ' khoa' +
    (ten.length ? ': ' + ten.join(', ') : ''));
}
```

- [ ] **Step 3: Gọi hàm khi tải xong dữ liệu**

Trong hàm `loadReport()` (khoảng dòng 109), thay:

```js
    .done(function (res) { CURRENT = res; render(res); })
```

bằng:

```js
    .done(function (res) { CURRENT = res; renderCheDo(res); render(res); })
```

Gọi trước `render()` để dòng trạng thái vẫn hiện cả khi `render()` thoát sớm ở nhánh `no_assignment` hoặc chưa có báo cáo.

- [ ] **Step 4: Kiểm tra blade biên dịch được**

```bash
php artisan view:clear
```

Kỳ vọng: `Compiled views cleared!`, không có lỗi cú pháp.

- [ ] **Step 5: Viết tài liệu chẩn đoán**

Tạo `docs/giaoban-chan-doan-phan-quyen-khoa.md` với nội dung:

````markdown
# Chẩn đoán: "Đã phân quyền khoa nhưng đăng nhập vẫn thấy tất cả khoa"

## Hành vi đúng

`GiaoBanController::show()` lọc **ở server** trước khi trả JSON: `GiaoBanPermission::visibleDeptConfigIds()`
quyết định nội dung `configs`, `cells` và `bang_dieu_tri`. Tài khoản chỉ có role `giaoban_khoa`
lẽ ra chỉ nhận về đúng các khoa được gán trong `giaoban_user_departments`.

Nếu thực tế khác, chạy các bước dưới đây trước khi sửa code.

## Bước 1 — Đọc dòng trạng thái trên màn giao ban

Ngay dưới thanh công cụ có dòng `Chế độ: ...`.

- `Chế độ: Quản trị — xem toàn viện` → tài khoản đang có quyền `giaoban-admin`. Sang Bước 2.
- `Chế độ: Khoa — được phân công N khoa: ...` mà màn hình vẫn hiện nhiều khoa hơn N → lỗi thật,
  sang Bước 4.

## Bước 2 — Kiểm tra role và permission của tài khoản

```sql
SELECT u.id, u.loginname, r.name role_name, p.name perm_name
  FROM acs_user u
  LEFT JOIN role_user ru       ON ru.user_id = u.id
  LEFT JOIN roles r            ON r.id = ru.role_id
  LEFT JOIN permission_role pr ON pr.role_id = r.id
  LEFT JOIN permissions p      ON p.id = pr.permission_id
 WHERE LOWER(u.loginname) = LOWER(:loginname);
```

Có dòng nào `perm_name = 'giaoban-admin'` → đây là nguyên nhân. Xử lý bằng cách gỡ role tương ứng
(thường là `administrator`, được migration `2026_07_08_100004_seed_giaoban_permissions` gán full
quyền giao ban), **không sửa code**.

## Bước 3 — Kiểm tra khoa đã phân công

```sql
SELECT ud.user_id, ud.dept_config_id, dc.display_name, dc.is_active
  FROM giaoban_user_departments ud
  JOIN giaoban_dept_configs dc ON dc.id = ud.dept_config_id
 WHERE ud.user_id = :user_id;
```

`is_active = 0` nghĩa là khoa đã tắt — người dùng được gán nhưng không nhìn thấy, màn hình sẽ ra
callout "Bạn chưa được phân công khoa nào". Đây là hành vi có chủ đích.

## Bước 4 — Đối chiếu bản triển khai

Việc lọc ở server là một lần sửa về sau; bản cũ chỉ ẩn bằng CSS, khớp đúng mô tả
"invisible không có tác dụng". Trên máy chủ:

```bash
git log --oneline -1
```

So với `main`. Nếu là bản cũ thì triển khai lại là hết.

## Bước 5 — Xóa cache quyền

Laratrust cache role/permission. Nếu vừa đổi phân quyền mà chưa thấy tác dụng:

```bash
php artisan cache:clear
```
````

- [ ] **Step 6: Kiểm tra bằng mắt trên trình duyệt**

Mở màn giao ban bằng một tài khoản quản trị. Kỳ vọng: dòng `Chế độ: Quản trị — xem toàn viện` hiện
ngay dưới thanh nút, không đè lên nội dung nào.

- [ ] **Step 7: Chạy toàn bộ test giao ban lần cuối**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, **165 tests**.

- [ ] **Step 8: Commit**

```bash
git add resources/views/khth/giaoban-index.blade.php docs/giaoban-chan-doan-phan-quyen-khoa.md
git commit -m "feat(giaoban): dong trang thai quyen va tai lieu chan doan phan quyen khoa"
```

---

## Nghiệm thu cuối (chạy sau khi xong cả 5 task)

- [ ] `php vendor/bin/phpunit --filter GiaoBan` → OK, 165 tests.
- [ ] Tài khoản chỉ có role `giaoban_khoa`, đã được gán khoa: thấy nút "Lấy số liệu" và hai ô thời gian, bấm được, số liệu về; kiểm tra lại `manual_value` của một khoa khác không đổi.
- [ ] Tài khoản `giaoban_khoa` chưa được gán khoa nào: không thấy nút "Lấy số liệu", không thấy hai ô thời gian.
- [ ] Slide Tổng quan không còn dòng "LỆCH CÂN ĐỐI"; slide từng khoa vẫn có badge `▲`; màn nhập liệu vẫn có icon cảnh báo vàng.
- [ ] Chưa bật cờ nào: slide "Hoạt động điều trị" giữ nguyên bộ cột như trước khi sửa.
- [ ] Bật cờ cho vài chỉ tiêu rồi lưu: slide chỉ còn đúng các cột đó; số tổng mỗi cột khớp tổng các khoa có chỉ tiêu cùng nhãn (kể cả khoa không bật cờ).
- [ ] Màn giao ban hiện dòng "Chế độ: ..." đúng với quyền tài khoản đang đăng nhập.
- [ ] Sau khi triển khai lên máy chủ: hỏi lại khách hàng xem lỗi ý 4 còn không; nếu còn, chạy `docs/giaoban-chan-doan-phan-quyen-khoa.md` và ghi kết quả vào spec.
