# Màn danh sách XML3176 — cắt bộ nhớ và sửa lỗi lựa chọn — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Màn `bhyt/xml3176/index` không còn hết bộ nhớ ở cỡ trang lớn, và lựa chọn hồ sơ không bị xoá khi chuyển trang.

**Architecture:** Ba thay đổi phía server trong `fetchData()` (đổi eager-load tập lỗi thành `withCount`, chốt danh sách trắng cột trả về, giữ nguyên phần còn lại) và ba thay đổi phía view (khởi tạo DataTable một lần thay vì hai request mỗi lần tải, giữ lựa chọn trong map bền qua các lần tải, giải phóng DataTable của modal). Hai điểm dễ trôi nhất — danh sách trắng cột và tham số nút xuất — được khoá bằng test đối chiếu mã nguồn.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, yajra/laravel-datatables-oracle ^8.3, jQuery + DataTables 1.10, AdminLTE.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-danh-sach-hieu-nang-design.md`
- Cổng test là **`vendor/bin/phpunit --testsuite Unit`** và **chỉ** suite này. Toàn bộ `tests/Feature` đỏ sẵn vì lý do môi trường — không dùng làm cổng.
- Mốc trước khi bắt đầu: **254 test xanh, 677 assertion**. Mỗi task phải giữ số đó không giảm.
- Không có hạ tầng test JS trong repo (không có `package.json`). Mọi thay đổi JavaScript chỉ nghiệm thu thủ công.
- DB dev **trống cả bốn bảng** `xml3176_xml1s`, `xml3176_error_results`, `xml3176_informations`, `check_hein_cards`. Không chạy được test tích hợp có dữ liệu, không đo được trước/sau tại chỗ.
- Không đụng `exportXml()`, không đụng các lớp `Exports/`, không đụng `config/datatables.php`. Đó là nợ kỹ thuật đã ghi trong spec.
- Không thêm/bớt mức nào trong `lengthMenu`. Cỡ trang 2000 phải còn dùng được.
- Comment trong mã nguồn viết tiếng Việt **không dấu**, theo đúng lệ của các file xung quanh.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit, rồi commit.

---

### Task 1: Chốt danh sách trắng cột trả về

Đây là thay đổi cắt payload. Nó có một chế độ hỏng âm thầm: thiếu một khoá trong danh sách trắng thì cột đó **trống trơn trên giao diện mà không báo lỗi**. Nên test đối chiếu phải viết trước.

**Files:**
- Create: `tests/Unit/Xml3176/Xml3176DatatableColumnsTest.php`
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`

**Interfaces:**
- Consumes: không có.
- Produces: hằng `BHYTXml3176Controller::DATATABLE_COLUMNS` (mảng chuỗi) — Task 2 và Task 4 không dùng, nhưng test của Task 1 khoá nó với blade.

- [ ] **Step 1: Viết test đối chiếu (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176DatatableColumnsTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Http\Controllers\BHYT\BHYTXml3176Controller;

class Xml3176DatatableColumnsTest extends TestCase
{
    /** @test */
    public function danh_sach_trang_cot_khop_dung_cac_cot_blade_doc()
    {
        $blade = file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));

        // Trong blade chi co cac khai bao cot moi dung "data" trong ngoac kep;
        // callback ajax dung `data:` khong ngoac nen khong bi bat nham.
        preg_match_all('/"data"\s*:\s*"([a-z0-9_]+)"/i', $blade, $m);
        $bladeDoc = array_values(array_unique($m[1]));

        $this->assertNotEmpty($bladeDoc, 'Khong doc duoc cot nao tu blade - regex hong?');

        $whitelist = BHYTXml3176Controller::DATATABLE_COLUMNS;

        $thieu = array_diff($bladeDoc, $whitelist);
        $this->assertEmpty(
            $thieu,
            'Blade doc cot khong co trong danh sach trang, cot se trong tren giao dien: '
                . implode(', ', $thieu)
        );

        $thua = array_diff($whitelist, $bladeDoc);
        $this->assertEmpty(
            $thua,
            'Danh sach trang giu cot khong ai doc, payload phinh vo ich: '
                . implode(', ', $thua)
        );
    }

    /** @test */
    public function cot_checkbox_render_tu_ma_lk_nen_ma_lk_phai_co_trong_danh_sach_trang()
    {
        // Cot checkbox khai "data": null va render tu row.ma_lk, nen regex tren
        // khong bat duoc - phai khoa rieng, neu khong checkbox se mat gia tri.
        $this->assertContains('ma_lk', BHYTXml3176Controller::DATATABLE_COLUMNS);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DatatableColumnsTest`
Expected: FAIL — `Undefined class constant 'DATATABLE_COLUMNS'`

- [ ] **Step 3: Khai hằng danh sách trắng**

Trong `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`, ngay sau `class BHYTXml3176Controller extends Controller {`, trước `protected $xml3176Service;`:

```php
    /**
     * Cac cot duoc phep di ra ngoai trong JSON cua DataTables.
     *
     * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay
     * se khong tu dong lot ra ngoai lam payload phinh lai. Xml3176DatatableColumnsTest
     * khoa danh sach nay khop dung cac cot blade doc.
     */
    const DATATABLE_COLUMNS = [
        'ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh',
        'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at',
        'exported_at', 'submitted_at', 'is_signed', 'imported_by', 'action',
    ];
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DatatableColumnsTest`
Expected: PASS (2 test)

- [ ] **Step 5: Áp danh sách trắng vào phản hồi**

Trong `fetchData()`, ở cuối chuỗi Datatables, chèn `->only(...)` **ngay trước** `->rawColumns(...)`:

```php
        ->only(self::DATATABLE_COLUMNS)
        ->rawColumns(['exported_at', 'is_signed', 'action', 'submitted_at'])
        ->toJson();
```

Lý do đặt được ở đây: trong `DataProcessor::process()` thứ tự là
`addColumns → editColumns → setupRowVariables → selectOnlyNeededColumns → removeExcessColumns`.
`setRowClass()` nằm trong `setupRowVariables`, chạy **trước** bước lọc cột nên tô màu dòng
không bị ảnh hưởng. `DT_RowClass` nằm trong `DataProcessor::$exceptions` nên không bị cắt.

- [ ] **Step 6: Kiểm cú pháp và chạy toàn bộ suite Unit**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Expected: `No syntax errors detected`

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **256 test** (254 cũ + 2 mới), không test nào đỏ thêm

- [ ] **Step 7: Commit**

```bash
git add tests/Unit/Xml3176/Xml3176DatatableColumnsTest.php app/Http/Controllers/BHYT/BHYTXml3176Controller.php
git commit -m "perf(xml3176): chot danh sach trang cot tra ve cua man danh sach"
```

---

### Task 2: Thay eager-load tập lỗi bằng đếm

Đây là thay đổi cắt bộ nhớ **phía server** — phần nặng hơn cả payload. Nó cũng vá luôn một lỗi N+1 sẵn có.

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` (ba nhánh dựng truy vấn trong `fetchData()`, và `setRowClass()`)

**Interfaces:**
- Consumes: `BHYTXml3176Controller::DATATABLE_COLUMNS` (Task 1) — không sửa, chỉ cần nó đã tồn tại để `only()` cắt luôn cột đếm khỏi payload.
- Produces: thuộc tính `xml3176_error_result_count` trên mỗi model trong `setRowClass()`.

Tên thuộc tính đã xác minh: `Str::snake('Xml3176ErrorResult') . '_count'` = `xml3176_error_result_count`.

- [ ] **Step 1: Nhánh `treatment_code` — bỏ eager-load lỗi, thêm đếm**

Trong `fetchData()`, nhánh `if ($treatment_code) {`. Thay khối `->with([...])` hiện tại:

```php
                ->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }, 'Xml3176ErrorResult' => function($query) {
                    $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                }, 'Xml3176Information' => function($query) {
```

thành:

```php
                // Chi can biet CO loi hay khong (setRowClass), khong can noi dung loi.
                // Eager-load ca tap loi keo ve cot description kieu TEXT cho tung dong.
                ->withCount('Xml3176ErrorResult')
                ->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }, 'Xml3176Information' => function($query) {
```

- [ ] **Step 2: Nhánh `patient_code` — thay đổi y hệt**

Trong nhánh `if ($patient_code) {`, cùng một phép thay như Step 1. Khối ở đây thụt lề sâu hơn một cấp; giữ nguyên thụt lề sẵn có của file.

- [ ] **Step 3: Nhánh lọc theo ngày — thêm đếm vào truy vấn gốc**

Thay:

```php
                $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                    'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);
```

thành:

```php
                $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                    'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                ->withCount('Xml3176ErrorResult')
                ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);
```

- [ ] **Step 4: Bỏ eager-load lỗi ở nhánh không lọc theo mã lỗi**

Trong cùng nhánh ngày, thay:

```php
                } else {
                    $result = $result->with(['Xml3176ErrorResult' => function($query) {
                        $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                    }]);
                }
```

thành:

```php
                }
```

tức bỏ hẳn nhánh `else`. Giữ nguyên nhánh `if ($xml3176_error_catalog_id)` phía trên.

Sau thay đổi này cả hai nhánh đều dựa vào `withCount` ở Step 3 — kể cả nhánh lọc theo mã
lỗi vốn trước đây **không** eager-load, khiến `setRowClass()` lazy-load một truy vấn cho
mỗi dòng.

- [ ] **Step 5: Đổi `setRowClass()` sang dùng số đếm**

Thay:

```php
            if (!$highlight && $result->Xml3176ErrorResult && $result->Xml3176ErrorResult->isNotEmpty()) {
                $highlight = true;
            }
```

thành:

```php
            // Dung so dem thay vi tap loi: khong dung quan he o day thi Eloquent
            // se lazy-load mot truy van cho MOI dong.
            if (!$highlight && $result->xml3176_error_result_count > 0) {
                $highlight = true;
            }
```

- [ ] **Step 6: Xác nhận không còn chỗ nào đọc quan hệ tập lỗi trong controller**

Run: `grep -n "Xml3176ErrorResult" app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Expected: chỉ còn ba dòng `withCount('Xml3176ErrorResult')` và các lời gọi
`whereHas('Xml3176ErrorResult'...)` / `whereDoesntHave('Xml3176ErrorResult'...)` của bộ lọc
trạng thái. **Không còn** dòng `with(['Xml3176ErrorResult'` nào.

- [ ] **Step 7: Kiểm cú pháp và chạy suite Unit**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Expected: `No syntax errors detected`

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 256 test

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTXml3176Controller.php
git commit -m "perf(xml3176): dem so loi thay vi eager-load ca tap loi tung dong"
```

---

### Task 3: Khởi tạo DataTable một lần thay vì hai request mỗi lần tải

**Files:**
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (biến phạm vi module + hàm `fetchData`)

**Interfaces:**
- Consumes: không có.
- Produces: biến `xml3176Range` (object `{from, to}`) mà closure `data:` của DataTables đọc. Task 5 không dùng.

- [ ] **Step 1: Thêm biến giữ khoảng ngày ở phạm vi module**

Thay dòng khai báo biến đầu khối `<script>`:

```js
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;
    var selectedRecords = [];
```

thành:

```js
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;
    var selectedRecords = [];

    // Khoang ngay dung cho lan tai hien tai. Phai o pham vi module chu khong phai
    // tham so cua fetchData(): DataTable chi con duoc dung MOT lan, nen closure
    // "data" ben trong no phai doc duoc gia tri moi nhat o cac lan tai sau.
    var xml3176Range = { from: null, to: null };
```

- [ ] **Step 2: Ghi khoảng ngày vào biến module, ngay trước phần huỷ request cũ**

Trong `fetchData(startDate, endDate)`, chèn ngay **sau** khối `if (xml3176UrlFilters) { ... }` và **trước** `if (currentAjaxRequest != null)`:

```js
        // Ghi sau khoi xml3176UrlFilters vi khoi do co the ghi de startDate/endDate.
        xml3176Range.from = startDate;
        xml3176Range.to = endDate;
```

- [ ] **Step 3: Đọc khoảng ngày từ biến module trong callback `data`**

Trong khối `ajax.data`, thay hai dòng đầu:

```js
                    d.date_from = startDate;
                    d.date_to = endDate;
```

thành:

```js
                    d.date_from = xml3176Range.from;
                    d.date_to = xml3176Range.to;
```

- [ ] **Step 4: Bỏ `destroy` và chỉ dựng bảng ở lần gọi đầu**

Thay dòng mở:

```js
        table = $('#xml-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
```

thành:

```js
        // DataTable voi serverSide TU GOI ajax mot lan khi khoi tao. Truoc day code
        // con goi table.ajax.reload() ngay sau -> hai request nang chay chong nhau
        // moi lan tai. Gio chi dung bang o lan dau, cac lan sau chi reload.
        if (table) {
            table.ajax.reload();
            checkJobStatus();
            return;
        }

        table = $('#xml-list').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true, // Giữ responsive
```

- [ ] **Step 5: Bỏ lời gọi reload thừa ở cuối hàm**

Thay:

```js
        });

        table.ajax.reload();

        // Kiểm tra trạng thái job
        checkJobStatus();
    }
```

thành:

```js
        });

        // Khong goi table.ajax.reload() o day: DataTable() ben tren da tu gui
        // request khoi tao roi.

        // Kiểm tra trạng thái job
        checkJobStatus();
    }
```

- [ ] **Step 6: Kiểm cân bằng — chỉ còn đúng một lời gọi dựng bảng**

Run: `grep -n "DataTable({\|ajax.reload()\|destroy" resources/views/bhyt/xml3176/index.blade.php`
Expected: đúng **một** `$('#xml-list').DataTable({`; **không còn** `"destroy": true`;
các `ajax.reload()` còn lại nằm ở `fetchData` (nhánh lần sau), `xml3176ReloadTable`,
`deleteXML`, và `exportSelectedRecordsToXml` — đều là reload chủ đích.

- [ ] **Step 7: Chạy suite Unit**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 256 test (không đổi — đây là thay đổi JS thuần, không có test tự động)

- [ ] **Step 8: Commit**

```bash
git add resources/views/bhyt/xml3176/index.blade.php
git commit -m "perf(xml3176): dung DataTable mot lan, bo request nhan doi moi lan tai"
```

---

### Task 4: Nút 79/80a gửi đủ bộ lọc mà lớp Export đọc

**Files:**
- Create: `tests/Unit/Xml3176/Xml3176ExportParamsTest.php`
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (handler `#bulk-7980a-btn`)

**Interfaces:**
- Consumes: không có.
- Produces: không có.

- [ ] **Step 1: Viết test đối chiếu (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ExportParamsTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ExportParamsTest extends TestCase
{
    /** @test */
    public function nut_7980a_gui_du_moi_tham_so_ma_lop_export_doc()
    {
        $blade = file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));

        // Cat doan $.param({...}) cua rieng nut 79/80a: tu ten route den dau
        // ngoac dong cua loi goi $.param.
        $start = strpos($blade, 'export-7980a-data');
        $this->assertNotFalse($start, 'Khong tim thay nut 79/80a trong blade');

        $end = strpos($blade, '});', $start);
        $this->assertNotFalse($end, 'Khong tim thay diem ket thuc cua $.param');

        preg_match_all("/'([a-z0-9_]+)'\s*:/", substr($blade, $start, $end - $start), $m);
        $guiDi = array_values(array_unique($m[1]));
        $this->assertNotEmpty($guiDi, 'Khong doc duoc tham so nao - regex hong?');

        // Ben nhan: export7980aData() chuyen thang ca $request sang lop Export,
        // nen tham so that su duoc doc nam trong lop do.
        $export = file_get_contents(app_path('Exports/Xml3176Xml7980aExport.php'));
        preg_match_all("/request->input\('([a-z0-9_]+)'\)/", $export, $m2);
        $docDen = array_values(array_unique($m2[1]));
        $this->assertNotEmpty($docDen, 'Khong doc duoc tham so nao ben Export - regex hong?');

        $thieu = array_diff($docDen, $guiDi);
        $this->assertEmpty(
            $thieu,
            'Lop Export doc tham so ma nut khong gui -> file xuat khong khop man hinh: '
                . implode(', ', $thieu)
        );
    }
}
```

Test **chỉ** kiểm một chiều (Export đọc gì thì nút phải gửi đủ). Chiều ngược lại — nút
gửi `imported_by` và `xml_sign_status` mà Export bỏ qua — là thiếu sót phía server, đã
ghi thành nợ trong spec, không phải việc của task này.

- [ ] **Step 2: Chạy test, xác nhận đỏ và đỏ đúng lý do**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ExportParamsTest`
Expected: FAIL với thông điệp chứa `xml_export_status`

- [ ] **Step 3: Thêm tham số còn thiếu vào nút**

Trong handler `$('#bulk-7980a-btn').on('click', ...)`, thêm dòng lấy giá trị. Sau dòng:

```js
            var xml_sign_status = $('#xml_sign_status').val();
```

thêm:

```js
            var xml_export_status = $('#xml_export_status').val();
```

rồi trong object truyền cho `$.param({...})`, sau dòng `'payment_date_filter': payment_date_filter,` thêm:

```js
                'xml_export_status': xml_export_status,
```

Giữ nguyên `treatment_code`: lớp Export **có** đọc nó, và khi có giá trị thì nó bỏ qua
mọi điều kiện khác.

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ExportParamsTest`
Expected: PASS (1 test)

- [ ] **Step 5: Chạy toàn bộ suite Unit**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **257 test**

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/Xml3176/Xml3176ExportParamsTest.php resources/views/bhyt/xml3176/index.blade.php
git commit -m "fix(xml3176): nut 79/80a gui thieu xml_export_status nen xuat ca ho so chua xuat"
```

---

### Task 5: Giữ lựa chọn bền qua các trang

Đây là task đổi hành vi nhìn thấy được. Sau task này, người dùng không còn bị **ép** đặt cỡ trang 2000 để lựa chọn khỏi mất.

**Files:**
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (khai báo `selectedRecords`, `#select-all`, handler `.row-select`, `updateSelectedRecords`, `applySelectedCheckboxes`, `toggleBulkActionBtn`, `#bulk-action-btn`)

**Interfaces:**
- Consumes: không có.
- Produces: `selectedRecords` đổi từ mảng sang object map `{ma_lk: true}`; hai hàm mới `xml3176SelectedList()` và `xml3176SetSelected(maLk, on)`.

Dùng object map chứ không dùng `Set`: tránh phụ thuộc trình duyệt, và `Object.keys()` cho ra mảng mã cần gửi đi.

- [ ] **Step 1: Đổi kiểu `selectedRecords` và thêm hai hàm trợ giúp**

Thay:

```js
    var selectedRecords = [];
```

thành:

```js
    // Map { ma_lk: true } chu khong phai mang: phai BEN qua cac lan tai bang.
    // Truoc day day la mang va bi dung LAI tu cac checkbox dang co tren DOM, ma
    // voi serverSide thi DOM chi chua trang hien tai -> chon o trang 1, sang trang 2
    // tich them mot cai la mat sach lua chon trang 1.
    var selectedRecords = {};

    function xml3176SelectedList() {
        return Object.keys(selectedRecords);
    }

    function xml3176SetSelected(maLk, on) {
        if (on) {
            selectedRecords[maLk] = true;
        } else {
            delete selectedRecords[maLk];
        }
    }
```

- [ ] **Step 2: Bỏ hàm dựng lại lựa chọn**

Xoá hoàn toàn:

```js
    function updateSelectedRecords() {
        selectedRecords = [];
        $('.row-select:checked').each(function() {
            selectedRecords.push($(this).val());
        });
    }
```

- [ ] **Step 3: `#select-all` thêm/bớt theo trang hiện tại**

Thay:

```js
        $('#select-all').on('click', function(){
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
            updateSelectedRecords();
            toggleBulkActionBtn();
        });
```

thành:

```js
        $('#select-all').on('click', function(){
            // Giu nguyen ngu nghia cu: chi tac dong len cac dong DANG hien thi.
            var chon = this.checked;
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.row-select', rows).each(function () {
                $(this).prop('checked', chon);
                xml3176SetSelected($(this).val(), chon);
            });
            toggleBulkActionBtn();
        });
```

- [ ] **Step 4: Handler `.row-select` thêm/bớt một mã**

Thay:

```js
        $('#xml-list tbody').on('change', '.row-select', function() {
            updateSelectedRecords();
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
            toggleBulkActionBtn();
        });
```

thành:

```js
        $('#xml-list tbody').on('change', '.row-select', function() {
            xml3176SetSelected($(this).val(), this.checked);
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
            toggleBulkActionBtn();
        });
```

- [ ] **Step 5: Khôi phục checkbox theo cả hai chiều**

Thay:

```js
    function applySelectedCheckboxes() {
        var rows = table.rows().nodes();
        $('input[type="checkbox"]', rows).each(function() {
            if (selectedRecords.includes($(this).val())) {
                $(this).prop('checked', true);
            }
        });
    }
```

thành:

```js
    function applySelectedCheckboxes() {
        // Phai dat ca hai chieu: chi tick ma khong bo tick thi dong khong duoc chon
        // van con dau tick sot lai tu trang truoc (DataTables dung lai the <tr>).
        // Tra map thay vi Array.includes() -> het O(n^2) o co trang 2000.
        var rows = table.rows().nodes();
        $('input.row-select', rows).each(function() {
            $(this).prop('checked', !!selectedRecords[$(this).val()]);
        });
    }
```

- [ ] **Step 6: Nút xuất bật/tắt theo tổng lựa chọn, không theo trang**

Thay:

```js
    function toggleBulkActionBtn() {
        if ($('.row-select:checked').length > 0) {
            $('#bulk-action-btn').prop('disabled', false);
        } else {
            $('#bulk-action-btn').prop('disabled', true);
        }
    }
```

thành:

```js
    function toggleBulkActionBtn() {
        // Dem tren toan bo lua chon, khong chi trang hien tai.
        $('#bulk-action-btn').prop('disabled', xml3176SelectedList().length === 0);
    }
```

- [ ] **Step 7: Nút "Xuất XML3176" gửi toàn bộ lựa chọn**

Thay:

```js
        $('#bulk-action-btn').on('click', function(){
            var selectedRecords = [];
            $('.row-select:checked').each(function() {
                selectedRecords.push($(this).val());
            });
            
            if (selectedRecords.length > 0) {
                exportSelectedRecordsToXml(selectedRecords);
            } else {
                alert('Vui lòng chọn ít nhất một hồ sơ.');
            }
        });
```

thành:

```js
        $('#bulk-action-btn').on('click', function(){
            // Doc tu map, khong quet DOM: DOM chi co trang hien tai.
            var danhSach = xml3176SelectedList();

            if (danhSach.length > 0) {
                exportSelectedRecordsToXml(danhSach);
            } else {
                alert('Vui lòng chọn ít nhất một hồ sơ.');
            }
        });
```

Lưu ý biến `selectedRecords` cục bộ trong handler cũ **che** biến toàn cục cùng tên — đổi tên thành `danhSach` để không còn nhập nhằng.

- [ ] **Step 8: Xác nhận không còn chỗ nào dùng lối cũ**

Run: `grep -n "updateSelectedRecords\|selectedRecords.includes\|selectedRecords.push\|row-select:checked" resources/views/bhyt/xml3176/index.blade.php`
Expected: **không có kết quả**

- [ ] **Step 9: Chạy suite Unit**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 257 test

- [ ] **Step 10: Commit**

```bash
git add resources/views/bhyt/xml3176/index.blade.php
git commit -m "fix(xml3176): giu lua chon ho so ben qua cac trang"
```

---

### Task 6: Giải phóng DataTable của modal chi tiết

Hạng mục giá trị thấp nhất trong đợt. Nếu Task 1–5 đã tiêu tốn nhiều thời gian hoặc phát sinh rủi ro, cắt task này là hợp lý.

**Files:**
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (`initializeModalDataTables` + handler mới cho `#infoModal`)

**Interfaces:**
- Consumes: không có.
- Produces: không có.

- [ ] **Step 1: Gộp danh sách bảng con thành một hằng**

Thay:

```js
    function initializeModalDataTables() {
        $('#thuocvt').DataTable();
        $('#dvkt').DataTable();
        $('#cls').DataTable();
        $('#dienbien').DataTable();
        $('#checkHeinCard').DataTable();
        $('#xmlErrorChecks').DataTable();
    }
```

thành:

```js
    var XML3176_MODAL_TABLES = ['#thuocvt', '#dvkt', '#cls', '#dienbien',
                                '#checkHeinCard', '#xmlErrorChecks'];

    function initializeModalDataTables() {
        XML3176_MODAL_TABLES.forEach(function (sel) {
            $(sel).DataTable();
        });
    }
```

- [ ] **Step 2: Huỷ các bảng con khi đóng modal**

Thêm vào trong `$(document).ready(function() { ... })`, ngay sau handler `#openDownloadModalBtn`:

```js
        // Noi dung modal duoc thay moi bang .html() moi lan mo, nen node cu bi go
        // nhung 6 thuc the DataTable van nam lai trong registry noi bo cua thu vien.
        // Mo modal 50 lan la 300 thuc the ton dong. Huy tuong minh khi dong modal.
        $('#infoModal').on('hidden.bs.modal', function () {
            XML3176_MODAL_TABLES.forEach(function (sel) {
                var el = $('#modalContent').find(sel);
                if (el.length && $.fn.DataTable.isDataTable(el)) {
                    el.DataTable().destroy();
                }
            });
            $('#modalContent').empty();
        });
```

- [ ] **Step 3: Chạy suite Unit**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 257 test

- [ ] **Step 4: Commit**

```bash
git add resources/views/bhyt/xml3176/index.blade.php
git commit -m "fix(xml3176): huy DataTable cua modal chi tiet khi dong, tranh ton dong"
```

---

## Nghiệm thu thủ công (bắt buộc — không task nào thay thế được)

DB dev trống cả bốn bảng `xml3176_*`, không có hạ tầng test JS. Toàn bộ phần dưới phải làm trên máy chủ có dữ liệu thật, và **chưa việc nào trong danh sách này được coi là đã kiểm cho tới khi có người chạy**.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Mở màn danh sách, xem tab Network | Đúng **một** request `fetch-data`, không phải hai |
| 2 | Bấm "Tải dữ liệu" | Thêm đúng **một** request |
| 3 | So kích thước JSON trước/sau, cùng bộ lọc và cùng cỡ trang | Giảm rõ rệt |
| 4 | Cỡ trang 2000 trên khoảng một tháng | Không còn lỗi hết bộ nhớ |
| 5 | Hồ sơ có lỗi XML | Vẫn tô đỏ (`highlight-red`) |
| 6 | Lọc theo mã lỗi | Vẫn tô đỏ đúng; số truy vấn không tăng theo số dòng |
| 7 | Đổi bộ lọc rồi bấm "Tải dữ liệu" nhiều lần | Bộ lọc mới áp đúng mỗi lần (kiểm phần Task 3) |
| 8 | Drill-down từ dashboard XML3176 sang màn này | Bộ lọc từ URL vẫn áp đúng ở lần tải đầu |
| 9 | Chọn hồ sơ trang 1 → sang trang 2 tích thêm → quay lại trang 1 | Lựa chọn cũ còn nguyên |
| 10 | "Xuất XML3176" sau khi chọn ở hai trang khác nhau | File nhận đủ hồ sơ của cả hai trang |
| 11 | Lọc "Đã xuất XML" rồi tải 79/80a | File chỉ chứa hồ sơ đã xuất |
| 12 | Mở modal chi tiết hai lần liên tiếp | Bảng con vẫn sắp xếp/phân trang được, không lỗi console |

**Việc số 8 là điểm rủi ro nhất của Task 3.** Logic áp bộ lọc từ URL phụ thuộc vào việc
`fetchData()` được gọi lần đầu tiên; sau khi tách nhánh "lần đầu / lần sau", phải chắc
nhánh lần đầu vẫn chạy trọn khối `xml3176UrlFilters`.

## Nợ kỹ thuật ghi nhận, không làm trong đợt này

1. `exportXml()` dựng tuần tự tới 2000 file XML trong một request rồi mới nén zip. Trên máy chủ mới đây là bức tường tiếp theo sẽ đổ, và đúng vào thao tác người dùng hay làm nhất.
2. Các endpoint xuất nhận thiếu bộ lọc so với màn danh sách: `patient_code`, `hein_card_filter`, `treatment_type_fillter`; riêng lớp `Xml3176Xml7980aExport` còn bỏ qua `imported_by` và `xml_sign_status`.
3. `uploadData()` với luật `'xmls.*' => 'mimes:xml|max:102400'` là nhánh còn lại tới `Arr::dot()`. **Nếu log mới (commit `bec2e05`) cho thấy URL lỗi là `xml3176/index/upload-data` thì nguyên nhân không nằm ở màn danh sách và cả kế hoạch này cần xem lại.**
4. `setInterval(checkJobStatus, 5000)` chạy mãi, cộng thêm bốn lời gọi rời rạc khác.
5. `config/datatables.php` đặt `'escape' => '*'`, khiến yajra chạy `array_dot()` + `e()` cho mọi cột của mọi dòng trên **toàn bộ** các màn dùng DataTables trong app — không riêng màn này.
