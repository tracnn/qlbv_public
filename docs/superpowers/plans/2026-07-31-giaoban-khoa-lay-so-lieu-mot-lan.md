# Người khoa chỉ lấy số liệu một lần — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Người được phân công khoa chỉ lấy được số liệu từ HIS khi báo cáo của ngày đó chưa từng lấy; đã có số liệu rồi thì chỉ xem. Quản trị không đổi.

**Architecture:** Điều kiện mới phụ thuộc **ngày đang xem**, mà `$canFetch` là biến Blade render một lần lúc mở trang còn người dùng đổi ngày bằng JS không tải lại trang. Nên tách ba vai: Blade/`$canFetch` quyết định có render nút và hai ô khung giờ ra DOM hay không (quyền cơ sở); JS/`res.can_fetch` quyết định hiện hay ẩn chúng sau mỗi lần tải dữ liệu; `fetchData()` ở server là lớp chặn thật. Logic quyết định nằm ở hàm thuần `GiaoBanPermission::canFetchReport()` để unit test được.

**Tech Stack:** PHP 7 / Laravel 5.5, PHPUnit 6.5, Blade + jQuery + AdminLTE 2, Oracle (`HISPro`, `ACS_RS`) và MySQL (bảng `giaoban_*`).

## Global Constraints

- Chạy test bằng: `php vendor/bin/phpunit --filter GiaoBan`. Baseline hiện tại **169 tests, 420 assertions, OK**. Sau Task 1 phải là **173 tests, OK**; Task 2 không thêm test.
- Comment trong code viết **tiếng Việt không dấu**, giải thích *tại sao* chứ không mô tả *cái gì* — theo khuôn mẫu `app/Services/GiaoBan/*` và phần JS đã có trong `giaoban-index.blade.php`.
- Tên test viết tiếng Việt không dấu, dùng annotation `/** @test */` (không dùng tiền tố `test`).
- Chuỗi hiển thị cho người dùng viết **tiếng Việt có dấu**.
- Không tạo migration. Cột `giaoban_reports.data_fetched_at` đã tồn tại (migration `2026_07_08_100001_create_giaoban_reports_table.php:23`) và đã nằm trong `$fillable` của model.
- Không đổi chữ ký các hàm public đã có. `canFetchData()` giữ nguyên, vẫn là quyền cơ sở cho Blade.
- Không đụng `saveCell` — người khoa vẫn nhập tay bình thường sau khi số liệu đã được lấy.
- Commit tiền tố `feat(giaoban):`. Nội dung commit viết tiếng Việt không dấu.
- Shell là Git Bash (POSIX sh) hoặc PowerShell — dùng đúng cú pháp cho shell bạn gọi. Thông điệp commit nhiều dòng dùng heredoc của bash (`git commit -F - <<'EOF'`), **không** dùng PowerShell here-string.
- Cảnh báo có thật từ đợt trước: đã từng có người vô tình làm hỏng Blade khi viết chữ `@if` trong một comment JavaScript — trình biên dịch Blade bắt luôn. Chạy `php artisan view:clear` sau khi sửa view.

---

### Task 1: `canFetchReport()` và hai đầu server

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanPermission.php` (thêm method sau `canFetchData`)
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php` — `show()` (khối `$reportOut` và mảng `response()->json([...])`), `fetchData()`
- Test: `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`

**Interfaces:**
- Consumes: `GiaoBanPermission::canFetchData($isAdmin, array $assignedDeptIds): bool` (đã có). `GiaoBanController::isAdmin()` và `assignedDeptIds()` (đã có, `protected`).
- Produces: `GiaoBanPermission::canFetchReport($isAdmin, array $assignedDeptIds, $daFetchRoi): bool`. JSON của `show()` có thêm `can_fetch` (bool) ở cấp gốc và `report.data_fetched_at` (string|null). Task 2 đọc đúng hai khóa này.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`, ngay trước dấu `}` đóng class:

```php
    // ===== Quyen LAY SO LIEU cho MOT bao cao cu the =====

    /** @test */
    public function admin_lay_lai_duoc_du_bao_cao_da_co_so_lieu()
    {
        $this->assertTrue(GiaoBanPermission::canFetchReport(true, [], '2026-07-31 07:12:00'));
    }

    /** @test */
    public function nguoi_khoa_lay_duoc_khi_bao_cao_chua_tung_lay()
    {
        $this->assertTrue(GiaoBanPermission::canFetchReport(false, [3], null));
    }

    /** @test */
    public function nguoi_khoa_khong_lay_lai_duoc_khi_bao_cao_da_co_so_lieu()
    {
        $this->assertFalse(GiaoBanPermission::canFetchReport(false, [3], '2026-07-31 07:12:00'));
    }

    /** @test */
    public function chua_duoc_gan_khoa_thi_khong_lay_duoc_du_bao_cao_con_trong()
    {
        $this->assertFalse(GiaoBanPermission::canFetchReport(false, [], null));
    }
```

- [ ] **Step 2: Chạy test để xác nhận nó đỏ**

```bash
php vendor/bin/phpunit --filter GiaoBanPermissionTest
```

Kỳ vọng: FAIL với `Call to undefined method App\Services\GiaoBan\GiaoBanPermission::canFetchReport()`.

- [ ] **Step 3: Cài đặt hàm thuần**

Trong `app/Services/GiaoBan/GiaoBanPermission.php`, chèn ngay **sau** method `canFetchData()`:

```php

    /**
     * Ai duoc bam "Lay so lieu" cho MOT bao cao cu the.
     *
     * Khac canFetchData (quyen co so, khong phu thuoc ngay nao): ham nay them dieu kien trang
     * thai. Nguoi khoa chi can lay so lieu khi KHTH chua lay, de co cai ma nhap. Lay LAI la
     * chuyen khac han — no tinh lai auto_value cua toan vien nen giu rieng cho KHTH.
     *
     * @param bool  $isAdmin          user->can('giaoban-admin')
     * @param array $assignedDeptIds  dept_config_id duoc gan trong giaoban_user_departments
     * @param mixed $daFetchRoi       giaoban_reports.data_fetched_at cua bao cao ngay dang xet;
     *                                null hoac rong = chua tung lay so lieu
     */
    public static function canFetchReport($isAdmin, array $assignedDeptIds, $daFetchRoi)
    {
        if ($isAdmin) return true;
        if (!self::canFetchData($isAdmin, $assignedDeptIds)) return false;
        return empty($daFetchRoi);
    }
```

- [ ] **Step 4: Chạy test để xác nhận nó xanh**

```bash
php vendor/bin/phpunit --filter GiaoBanPermissionTest
```

Kỳ vọng: PASS.

- [ ] **Step 5: Trả thêm hai khóa trong `show()`**

Trong `app/Http/Controllers/KHTH/GiaoBanController.php`, method `show()`:

Khối `$reportOut` hiện là:

```php
            $reportOut = [
                'id' => $report->id, 'status' => $report->status,
                'from_time' => $report->from_time, 'to_time' => $report->to_time,
                'general_note' => $report->general_note,
            ];
```

thêm một khóa vào cuối mảng:

```php
                'data_fetched_at' => $report->data_fetched_at,
```

Rồi trong mảng `response()->json([...])`, thêm `can_fetch` ngay sau dòng có `'is_admin'`:

```php
            // Quyen bam "Lay so lieu" cho DUNG ngay dang xem — khac $canFetch ben Blade (quyen
            // co so, render mot lan luc mo trang). Nguoi dung doi ngay bang JS nen trang thai nay
            // phai di theo tung lan tai du lieu.
            'can_fetch' => GiaoBanPermission::canFetchReport(
                $isAdmin, $assigned, $report ? $report->data_fetched_at : null
            ),
```

`$isAdmin`, `$assigned` và `$report` đều đã là biến cục bộ sẵn có ở đầu `show()`. `GiaoBanPermission` đã được `use` ở đầu tệp.

- [ ] **Step 6: Đổi guard trong `fetchData()`**

Vẫn trong tệp đó, method `fetchData()`. Phần đầu hiện là:

```php
        // Khong con la dac quyen cua KHTH: khoa duoc gan cung tu lay duoc so lieu.
        $isAdmin = $this->isAdmin();
        if (!GiaoBanPermission::canFetchData($isAdmin, $this->assignedDeptIds())) abort(403);
```

theo sau là khối comment về khung giờ và `$this->validate(...)`. Đổi thành: **`validate()` chạy trước**, rồi mới tra báo cáo và chặn quyền. Kết quả:

```php
        $isAdmin = $this->isAdmin();

        // validate() phai chay TRUOC guard vi guard can $request->input('date') de tra bao cao.
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
            'from_time' => 'required|date_format:Y-m-d H:i:s',
            'to_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // Tra ban ghi DA CO truoc khi cham vao getOrCreateReport()/fetchAndStore(): mot ham tao
        // ban ghi moi, ham kia dat data_fetched_at o dong cuoi — doc sau bat ky ham nao trong hai
        // ham do deu cho cau tra loi sai.
        $daCo = GiaoBanReport::where('report_date', $request->input('date'))->first();
        // Nguoi khoa chi lay duoc khi bao cao chua tung co so lieu; admin lay lai tuy y.
        // canFetchReport da bao ham quyen co so nen khong goi canFetchData them o day.
        if (!GiaoBanPermission::canFetchReport($isAdmin, $this->assignedDeptIds(), $daCo ? $daCo->data_fetched_at : null)) {
            abort(403);
        }

        // Khung gio lay theo dung gia tri client gui len, khong chot o server: nguoi khoa thay va
        // sua hai o nay nhu admin o lan lay duy nhat cua ho (xem giaoban-index.blade.php), va JS
        // da dien san khung da luu khi bao cao ton tai nen ho khong vo tinh de len (xem
        // docs/superpowers/specs/2026-07-31-giaoban-4-dieu-chinh-design.md, muc Rui ro Y 1).
        $from = $request->input('from_time');
        $to = $request->input('to_time');
```

Phần còn lại của method (`getOrCreateReport`, kiểm `isFinal`, `fetchAndStore`, `return`) giữ nguyên. Xoá khối comment cũ về khung giờ nếu nó bị trùng với khối vừa thêm — chỉ giữ một bản.

`GiaoBanReport` đã được `use` ở đầu tệp (dòng 7), không cần thêm import.

- [ ] **Step 7: Cập nhật docblock của `fetchData()`**

Dòng docblock hiện là `/** Lấy/Lấy lại số liệu từ HIS (admin hoặc khoa được gán). */`. Nó không còn đúng — người khoa không "lấy lại" được nữa. Đổi thành:

```php
    /** Lấy số liệu từ HIS. Admin lấy lại tùy ý; khoa được gán chỉ lấy khi báo cáo còn trống. */
```

- [ ] **Step 8: Chạy toàn bộ test giao ban**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, số test = 169 + 4 = **173**.

- [ ] **Step 9: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanPermission.php app/Http/Controllers/KHTH/GiaoBanController.php tests/Unit/GiaoBan/GiaoBanPermissionTest.php
git commit -m "feat(giaoban): nguoi khoa chi lay so lieu khi bao cao con trong"
```

---

### Task 2: Ẩn nút và hai ô khung giờ theo ngày đang xem

**Files:**
- Modify: `resources/views/khth/giaoban-index.blade.php` — markup thanh công cụ (thêm `id` cho ba khối để JS bắt được, thêm chỗ hiện dòng thông báo), khối JS (thêm hai hàm, gọi trong `loadReport()`, dọn ở nhánh `.fail`)

**Interfaces:**
- Consumes: JSON của `GiaoBanController::show()` — `res.can_fetch` (bool, cấp gốc), `res.report.data_fetched_at` (string `'Y-m-d H:i:s'` hoặc `null`), `res.is_admin` (bool). Cả ba do Task 1 bảo đảm.
- Produces: không có gì cho task sau.

Task này không có unit test: JavaScript nhúng trong Blade, dự án không có hạ tầng test JS. Nghiệm thu bằng `php artisan view:clear` và đọc lại code.

- [ ] **Step 1: Thêm `id` cho các khối mà JS phải bật/tắt**

Trong `resources/views/khth/giaoban-index.blade.php`, thanh công cụ hiện có hai cột chứa ô thời gian và một cột chứa các nút. Thêm `id` cho cả ba (giữ nguyên mọi thứ khác trên các dòng đó):

- `<div class="col-md-2">` chứa `<label>Từ thời điểm</label>` → `<div class="col-md-2" id="o-tu-thoi-diem">`
- `<div class="col-md-2">` chứa `<label>Đến thời điểm</label>` → `<div class="col-md-2" id="o-den-thoi-diem">`
- `<div class="col-md-{{ $canFetch ? 6 : 10 }}" style="padding-top:24px">` → thêm `id="cot-nut"`:
  `<div id="cot-nut" class="col-md-{{ $canFetch ? 6 : 10 }}" style="padding-top:24px">`

Bắt bằng `id` chứ không bằng `closest('.col-md-2')`: lớp lưới là chuyện trình bày, JS bám vào nó thì đổi bố cục là hỏng thầm lặng.

- [ ] **Step 2: Thêm chỗ hiện dòng "đã lấy lúc"**

Vẫn trong thanh công cụ, thêm một khối mới ngay **trước** khối `<div class="col-md-12">` đang chứa `<small id="che-do">`:

```blade
      <div class="col-md-12" style="padding-top:12px">
        <small id="da-lay-luc" class="text-muted"></small>
      </div>
```

- [ ] **Step 3: Thêm hàm định dạng thời điểm**

Trong khối `@section('js')`, chèn ngay **sau** hàm `toDtLocal()` đã có:

```js
/** '2026-07-31 07:12:00' -> '07:12 ngày 31/07/2026'. Chuoi rong hay sai dinh dang -> rong. */
function gioNgay(ymdHis) {
  var s = String(ymdHis || '');
  if (s.length < 16) return '';
  return s.slice(11, 16) + ' ngày ' + s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4);
}
```

- [ ] **Step 4: Thêm hàm bật/tắt nút và dòng thông báo**

Chèn ngay **sau** hàm `dienKhungGioDaLuu()` đã có:

```js
/**
 * Hien/an nut "Lay so lieu" cung hai o khung gio, va dong "da lay luc nao".
 *
 * Khong quyet dinh duoc o Blade: $canFetch render MOT LAN luc mo trang, con dieu kien nay doi
 * theo NGAY dang xem ma nguoi dung chuyen ngay bang JS, khong tai lai trang. Blade chi quyet
 * dinh co dua cac phan tu nay vao DOM hay khong (quyen co so); day quyet dinh hien hay an.
 *
 * Chan that nam o GiaoBanController::fetchData(); day chi la trai nghiem.
 */
function capNhatNutLaySoLieu(res) {
  var duocLay = !!res.can_fetch;

  // Nguoi khong co quyen co so khong co cac phan tu nay trong DOM; .toggle() tren tap rong
  // cua jQuery vo hai nen khong can guard rieng.
  $('#btn-fetch').toggle(duocLay);
  $('#o-tu-thoi-diem, #o-den-thoi-diem').toggle(duocLay);
  // Hai o gio an di thi cot nut phai gian ra, neu khong luoi Bootstrap de lai mot khoang trong.
  $('#cot-nut').toggleClass('col-md-6', duocLay).toggleClass('col-md-10', !duocLay);

  var luc = gioNgay(res.report && res.report.data_fetched_at);
  if (!luc) { $('#da-lay-luc').empty(); return; }
  // Hien cho CA admin: "lay luc nao" tu no huu ich, khong phai chi de giai thich viec bi chan.
  $('#da-lay-luc').html('<i class="fa fa-clock-o"></i> Số liệu đã lấy lúc ' + esc(luc) +
    (res.is_admin ? '' : ' — cần lấy lại thì liên hệ phòng KHTH.'));
}
```

- [ ] **Step 5: Gọi hàm trong `loadReport()` và dọn ở nhánh lỗi**

Trong `loadReport()`, nhánh `.done` hiện gọi lần lượt `dienKhungGioDaLuu(res)`, `renderCheDo(res)`, `render(res)`. Thêm lời gọi mới **ngay sau** `dienKhungGioDaLuu(res)`:

```js
      capNhatNutLaySoLieu(res);
```

Đặt sau `dienKhungGioDaLuu(res)` để đọc code theo đúng thứ tự "điền giá trị rồi mới ẩn/hiện" — ghi vào một ô đã ẩn hay ẩn một ô đã có giá trị đều vô hại, nhưng giữ trật tự này thì không phải suy nghĩ lại.

Trong nhánh `.fail`, cạnh dòng `$('#che-do').empty();` đã có, thêm:

```js
      $('#da-lay-luc').empty();
```

Cùng lý do đã ghi cho `#che-do`: tải hỏng mà để lại thông tin của lần tải trước thì hiện SAI còn tệ hơn không hiện.

- [ ] **Step 6: Kiểm tra Blade biên dịch được**

```bash
php artisan view:clear
```

Kỳ vọng: `Compiled views cleared!`, không có lỗi cú pháp.

- [ ] **Step 7: Kiểm tra tĩnh phần vừa thêm**

```bash
grep -n "o-tu-thoi-diem\|o-den-thoi-diem\|cot-nut\|da-lay-luc\|capNhatNutLaySoLieu\|gioNgay" resources/views/khth/giaoban-index.blade.php
```

Đối chiếu từng `id` xuất hiện **đúng hai lần** (một lần ở markup, một lần ở selector jQuery) — trừ `#o-tu-thoi-diem`/`#o-den-thoi-diem` nằm chung một selector nên mỗi cái hai lần, và `cot-nut` hai lần. `capNhatNutLaySoLieu` xuất hiện hai lần (định nghĩa + lời gọi), `gioNgay` hai lần, `da-lay-luc` **bốn** lần: markup 1, hai nhánh trong `capNhatNutLaySoLieu` (`.empty()` khi không có thời điểm và `.html()` khi có), và `.fail` 1.

Nếu số lần lệch so với mô tả trên, có bước nào đó làm sót hoặc thừa — dừng lại và báo.

- [ ] **Step 8: Xác nhận không đưa ký tự Blade vào JavaScript**

```bash
grep -n "@" resources/views/khth/giaoban-index.blade.php | grep -v "@extends\|@section\|@stop\|@if\|@endif\|@json"
```

Kỳ vọng: **không có kết quả nào**. Có kết quả nghĩa là bạn vừa đưa một ký tự `@` vào chỗ Blade sẽ hiểu nhầm là directive — sửa trước khi đi tiếp.

- [ ] **Step 9: Chạy toàn bộ test giao ban**

```bash
php vendor/bin/phpunit --filter GiaoBan
```

Kỳ vọng: OK, **173 tests** (task này không thêm test, chỉ xác nhận không làm vỡ gì).

- [ ] **Step 10: Commit**

```bash
git add resources/views/khth/giaoban-index.blade.php
git commit -m "feat(giaoban): an nut lay so lieu khi bao cao da co du lieu"
```

---

## Nghiệm thu cuối (chạy sau khi xong cả 2 task)

- [ ] `php vendor/bin/phpunit --filter GiaoBan` → OK, 173 tests.
- [ ] Tài khoản khoa, ngày chưa có số liệu: thấy nút "Lấy số liệu" và hai ô khung giờ, bấm được.
- [ ] Ngay sau khi lấy xong, **không tải lại trang**: nút và hai ô biến mất, cột nút giãn ra không để lại khoảng trống, hiện dòng "Số liệu đã lấy lúc HH:mm ngày dd/mm/yyyy — cần lấy lại thì liên hệ phòng KHTH."
- [ ] Đổi sang một ngày khác chưa có số liệu: nút và hai ô hiện lại.
- [ ] Tài khoản khoa gọi thẳng `POST khth/giao-ban/fetch-data` cho ngày đã có số liệu: nhận **403**.
- [ ] Tài khoản quản trị: nút luôn hiện, lấy lại được nhiều lần, và vẫn thấy dòng "Số liệu đã lấy lúc ..." nhưng **không** có đoạn "liên hệ phòng KHTH".
- [ ] Tài khoản chưa được gán khoa nào: không thấy nút, không thấy hai ô khung giờ, không có lỗi JavaScript trên console khi đổi ngày.
