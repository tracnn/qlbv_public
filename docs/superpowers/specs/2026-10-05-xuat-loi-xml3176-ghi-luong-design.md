# Xuất lỗi XML3176 — ghi Excel theo luồng (giảm bộ nhớ)

Ngày: 2026-10-05

## Mục tiêu

Tệp "Xuất danh sách lỗi" của màn XML3176 (19 sheet, chạy nền qua `XuatTepLoiXml3176Job`) phải
xuất được ngày lớn với **bộ nhớ đỉnh < 512 MB**, giữ **y hệt** nội dung và giao diện tệp hiện tại.

## Hiện trạng đo được

### Sự cố

Prod, job `xml3176_tep_xuat` #11: ngày 05/10, `date_create`, `has_error` (mọi lỗi) — 2.509 hồ sơ,
~358.800 dòng lỗi (XML3 223.138, XML4 118.631). Chạy 13:05:18, tới 14:05:20 hàng đợi đánh
`MaxAttemptsExceededException` → "Dịch vụ xuất đã dừng giữa chừng". Cùng ngày lọc
`has_error_critical` (#10) xong trong 6 phút.

Tái hiện trên máy dev, cùng bộ lọc, `memory_limit` 4096M như job:

| Bản | Kết quả |
|---|---|
| Trước cột khoa HIS (`20a696c1`) | hết 4096M sau 1.428 s |
| Có cột khoa HIS (`5a5f1153`) | hết 4096M sau 1.271 s |

Đọc MySQL + `map()` + tra HIS cho cả 358.800 dòng chỉ 55,5 s (tra HIS 1,1 s). Thời gian và
bộ nhớ nằm ở **khâu ghi Excel**. Ngày 29/09 (204.617 dòng) còn qua được với ~2,5 GB — trần nằm
giữa hai mức này.

### Bộ nhớ dồn vào đâu

1. **PhpSpreadsheet giữ mọi ô trong RAM.** Đo 50.000 dòng × 21 cột: 666 MB, 63 s → 358.800 dòng
   ≈ 4,8 GB chỉ riêng ô.
2. Laravel Excel 3.1.25 với `FromGenerator` gom **cả generator** vào `Collection`
   (`Sheet::appendRows` → `new Collection($rows)`) trước khi ghi — hàng trăm nghìn model Eloquent.
3. Mảng đã `map()`.

### Các hướng đã loại

| Hướng | Số đo | Lý do loại |
|---|---|---|
| Cache ô ra đĩa (`illuminate`/`batch`) | 10.000 dòng: 281 s (memory: 5,5 s) | chậm ~50 lần → vài giờ |
| Nâng `memory_limit` 8–10 GB | ước ~6 GB, ≥ 40 phút | phụ thuộc RAM prod, ngày lớn hơn lại vỡ |

### Hướng chọn — ghi luồng bằng Spout

`box/spout` v3.3.0 **đã có** trong `composer.lock` (phụ thuộc của `rap2hpoutre/fast-excel`,
khai báo ở `composer.json`). Đo 50.000 dòng × 21 cột: **8,1 s, bộ nhớ tăng ~0 MB**.

Spout 3.3 có: chữ đậm, căn lề, xuống dòng, định dạng số. **Không** có độ rộng cột → chèn `<cols>`
sau khi ghi (mục 4).

## Phạm vi

- **Chỉ** tệp lỗi XML3176 chạy nền (`XuatTepLoiXml3176Job`).
- **Không** chuyển: danh sách hồ sơ, 7980a, kết quả tra cứu thẻ, QD130 — vẫn Laravel Excel.
- **Không** thêm/nâng thư viện, không đổi `composer.json`.
- **Không** đổi cột, thứ tự sheet, bộ lọc, tên tệp, bảng `xml3176_tep_xuat`, màn "Tệp xuất của tôi".

## Thiết kế

### 1. Thành phần

```
XuatTepLoiXml3176Job
  └─ GhiExcelLuong::ghi(Xml3176ErrorMultiSheetExport->sheets(), $dich)   ← thay Excel::store
        ├─ mỗi sheet: NguonSheet (bọc lớp export sẵn có) → các dòng đã map
        │     ├─ có generator()  → dùng thẳng (16 sheet lỗi; đã gom lô + tra HIS)
        │     └─ có query()      → cursor, gom 1000 → prepareRows() → map()
        ├─ Spout XLSXWriter: addRow từng dòng → đĩa
        └─ ChenDoRongCot: chèn <cols> vào từng sheet trong zip (theo luồng)
```

Lớp mới trong `app/Services/ExcelLuong/`:

- **`GhiExcelLuong`** — nhận danh sách sheet + đường dẫn đích; ghi lần lượt; đóng; gọi
  `ChenDoRongCot`; đổi tên tệp tạm sang đích.
- **`NguonSheet`** — bọc một lớp export, cung cấp tiêu đề, dòng (generator), tên sheet, định
  dạng. Nơi DUY NHẤT biết khác biệt `FromGenerator` / `FromQuery`.
- **`ChenDoRongCot`** — chèn `<cols>` vào `xl/worksheets/sheetN.xml`.

### 2. Nguồn định dạng duy nhất: `dinhDangLuong()`

Thêm vào 4 lớp: `Xml3176ErrorSheetExport`, `HeinCardErrorExport`, `DmKhoaGiuongSheetExport`,
`DmNvytSheetExport`:

```php
public function dinhDangLuong(): array
{
    return [
        'do_rong' => ['A' => 5, ...],     // cot => do rong; [] = mac dinh
        'cot_so' => ['H', 'J', ...],      // cot ap dinh dang so '0' cho o SO
        'kieu_o' => 'tu_dong',            // 'tu_dong' (DefaultValueBinder) | 'chu' (StringValueBinder)
        'xuong_dong' => true,             // wrap text moi o
        'tieu_de_can_giua' => true,       // dong 1: dam (luon) + can giua (neu true)
    ];
}
```

`registerEvents()` / `styles()` hiện có **đọc lại từ `dinhDangLuong()`** — đường Laravel Excel cũ
(QD130 vẫn dùng `HeinCardErrorExport`; công tắc quay lui) và đường luồng dùng chung một định nghĩa.

Giá trị phải khớp hiện trạng:

| Sheet | Tiêu đề | Xuống dòng | Độ rộng | `cot_so` | `kieu_o` |
|---|---|---|---|---|---|
| 16 sheet lỗi | đậm, căn giữa | có | `DO_RONG` A–U | `COT_NGAY` | tu_dong |
| Lỗi thẻ BHYT (XML3176) | đậm, căn giữa | có | A–I | — | tu_dong |
| Lỗi thẻ BHYT (QD130) | đậm, căn giữa | có | A–F | — | tu_dong |
| DM khoa-giường, DM NVYT | đậm | không | mặc định | — | chu |

`HeinCardErrorExport` có `ShouldAutoSize` nhưng `AfterSheet` đặt cứng độ rộng cho MỌI cột của nó
nên tự co giãn không có tác dụng — đường luồng chỉ dùng độ rộng cứng.

### 3. Luồng dữ liệu (`NguonSheet`)

| Lớp | Cách đọc | Chuỗi |
|---|---|---|
| `Xml3176ErrorSheetExport` | `generator()` sẵn có | đã gom 1000 + tra HIS → `map()` |
| `HeinCardErrorExport`, 2 sheet DM | `query()->cursor()`, gom 1000 | `prepareRows($lo)` nếu có → `map()` |

- RAM chỉ giữ một lô 1000 dòng.
- **Thứ tự giống cũ:** đường cũ `FromQuery` dùng `chunk()`, tự `orderBy` khoá chính khi truy vấn
  chưa có thứ tự (`enforceOrderBy`); `cursor()` thì không. `NguonSheet` thêm
  `orderBy(<khoá chính có tên bảng>)` khi `$query->getQuery()->orders` rỗng.
  `HeinCardErrorExport::query()` hiện không có `ORDER BY` — rơi đúng trường hợp này.
- STT do `map()` tự tăng như cũ.
- Dòng phải `yield` từng phần tử (KHÔNG `yield from $mang`) — xem bài học 05/10 ở
  `Xml3176ErrorSheetExport::generator()`.

### 4. Kiểu ô và style

Kiểu ô (`kieu_o = 'tu_dong'`): gọi thẳng
`PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder::dataTypeForValue($v)` — chính quy tắc Laravel
Excel đang dùng:

| Kết quả | Ghi |
|---|---|
| `TYPE_NUMERIC` | số: `int` nếu chuỗi không có `.`/`e`/`E`, ngược lại `float` |
| `TYPE_STRING`, `TYPE_ERROR` | chữ |
| `TYPE_NULL` | ô trống |
| `TYPE_BOOL` | bool |
| `TYPE_FORMULA` (chuỗi bắt đầu `=`) | **chữ** — KHÁC CỐ Ý: bản cũ biến thành công thức (chèn công thức từ dữ liệu) |

`kieu_o = 'chu'`: mọi ô khác null ghi chữ (`(string) $v`).

Style — tạo một lần mỗi sheet, dùng lại cho mọi dòng:

| Style | Áp cho | Nội dung |
|---|---|---|
| `tieuDe` | dòng 1 | đậm; căn giữa nếu `tieu_de_can_giua`; xuống dòng nếu `xuong_dong` |
| `duLieu` | ô dữ liệu | xuống dòng nếu `xuong_dong` |
| `duLieuSo` | ô SỐ ở cột `cot_so` | như `duLieu` + định dạng `0` |

### 5. Chèn độ rộng (`ChenDoRongCot`)

Spout ghi sheet thứ n vào `xl/worksheets/sheet{n}.xml` theo thứ tự tạo; phần đầu là
`<worksheet …><sheetData>` (Spout `WorksheetManager`). Thứ tự OOXML: `<cols>` đứng trước
`<sheetData>`.

Với mỗi sheet có `do_rong` khác rỗng:

1. `ZipArchive::getStream()`; đọc tối đa 64 KB đầu, tìm `<sheetData>` đầu tiên.
2. Ghi sang tệp tạm: phần trước + `<cols><col min="i" max="i" width="w" customWidth="1"/>…</cols>`
   + phần còn lại, chép theo khối 1 MB (sheet XML3 có thể > 100 MB — không nạp vào RAM).
3. `addFile()` thay mục cũ; sau cùng `close()`; xoá tệp tạm.

Không thấy `<sheetData>` trong 64 KB đầu → **ném lỗi** (không xuất tệp thiếu độ rộng im lặng).

### 6. Tích hợp job

```php
$export = new Xml3176ErrorMultiSheetExport((array) $y->bo_loc, DanhSachCoSo::danhSach());

if (config('xml3176.xuat_tep_luong', true)) {
    (new GhiExcelLuong())->ghi($export->sheets(), Storage::disk('local')->path($duongDan));
} else {
    $daGhi = Excel::store($export, $duongDan, 'local');   // duong cu, giu de quay lui
    ...
}
```

- Công tắc `xml3176.xuat_tep_luong` ← `env('XML3176_XUAT_TEP_LUONG', true)`. Quay lui trên prod:
  đặt `false` trong `.env`, khởi động lại dịch vụ `QLBV JobXuatTepXml3176`.
- Không đổi: chuyển trạng thái `dang_tao` → `xong`, kích thước, `failed()`, `$tries = 1`,
  `set_time_limit(0)`, `Cell::setValueBinder(...)` trong `finally` (vô hại; cần khi tắt công tắc).
- `memory_limit` 4096M **giữ nguyên** ở lần này (xem Nghiệm thu bước 4).

### 7. Xử lý lỗi — không bao giờ để tệp dở dang

`GhiExcelLuong::ghi()`:

1. Thư mục tạm riêng mỗi lần chạy: `storage/app/xuat-tam/<uniqid>/` — Spout `setTempFolder()` và
   tệp xlsx tạm đều ở đây (biết chắc ổ đĩa, dọn được).
2. Ghi + chèn độ rộng xong mới `rename()` sang đích (tạo thư mục cha nếu thiếu).
3. `finally`: xoá đệ quy thư mục tạm, thành công hay thất bại.
4. Mọi lỗi (MySQL, Spout, zip, rename) **ném tiếp** — job vào `failed()` như hiện nay
   (log, xoá tệp đích, `loi`, "Tạo tệp lỗi, xem nhật ký máy chủ. Bấm tạo lại.").
5. Mất kết nối HIS: không đổi — `KhoaDieuTriHis::khoaChoXuat()` ghi `Lỗi tra HIS`, tệp vẫn ra.

## Kiểm thử

Trong `tests/Unit`; SQLite hoặc dữ liệu dựng tay; HIS dùng `Tests\Support\FakeKhoaDieuTriHis`;
không Oracle, không MySQL thật; đọc xlsx bằng `ZipArchive` + XML. Cấm `RefreshDatabase`.

`GhiExcelLuongTest`:

1. 3 sheet → tệp đúng 3 sheet, đúng tên, đúng thứ tự.
2. Dòng 1 tiêu đề; số dòng dữ liệu = số dòng nguồn; có sheet **2.500 dòng qua nhiều lô** — đủ
   2.500 (chặn lỗi mất dòng im lặng kiểu `yield from`).
3. Kiểu ô: `'202610050800'` → số; `'000007230917'` → chữ; `null` → trống; `'=SUM(A1)'` → chữ,
   không có `<f>`; sheet `kieu_o = 'chu'`: `'123'` → chữ.
4. Style: tiêu đề đậm + căn giữa; ô số ở `cot_so` mang numFmt `0`; xuống dòng bật/tắt theo sheet.
5. Nguồn ném ngoại lệ ở dòng 1.500 → không có tệp đích, thư mục tạm đã xoá, ngoại lệ ném ra.

`ChenDoRongCotTest`:

6. `<cols>` ngay trước `<sheetData>`, đúng `min`/`max`/`width`/`customWidth`; sheet không có
   `do_rong` không bị đụng.
7. Không thấy `<sheetData>` → ném lỗi.
8. Tệp sau chèn: PhpSpreadsheet đọc được, độ rộng đúng.

`NguonSheetTest`:

9. `FromQuery` không `ORDER BY` → sắp theo khoá chính; có sẵn thứ tự → giữ.
10. `prepareRows()` gọi một lần mỗi lô 1000.

Lớp export:

11. `dinhDangLuong()` khớp cái `registerEvents()`/`styles()` áp (một nguồn). Test số cột hiện có
    (21 / 9) giữ xanh.

`XuatTepLoiXml3176JobTest`:

12. Công tắc bật → `GhiExcelLuong`; tắt → `Excel::store`; trạng thái `xong`/`loi` như cũ.

Cổng: `vendor/bin/phpunit --testsuite Unit` (ép `DB_HOST=127.0.0.1`), danh sách lỗi **giống hệt
`main`** (hiện 94 error + 3 failure có sẵn).

### Đối chiếu dữ liệu thật (chỉ đọc, trước khi merge)

Script giữ trong repo: `scripts/so-sanh-xuat-loi-xml3176.php`.

- **04/10** (~74.000 dòng; đường cũ xuất được): xuất cả hai đường, so từng sheet, từng ô (giá trị +
  kiểu số/chữ), độ rộng, numFmt `0`, tiêu đề. Tiêu chí: **0 ô lệch**, ngoại trừ ô bắt đầu `=` —
  script liệt kê riêng.
- **05/10** (~358.800 dòng; đường cũ hết 4096M): chỉ đường mới. Tiêu chí: **chạy xong, đỉnh
  < 512 MB**, số dòng từng sheet = `query()->count()`; ghi thời gian thật.
- Mở tệp 05/10 bằng Excel trên máy.

## Nghiệm thu prod

1. Xuất lại đúng bộ lọc #11 (05/10, `date_create`, `has_error`): phải xong; ghi thời gian
   (`bat_dau_luc` / `xong_luc`) và kích thước.
2. Mở tệp, đối chiếu số dòng XML3/XML4 với kết quả đối chiếu ở trên.
3. Có vấn đề → `XML3176_XUAT_TEP_LUONG=false` + khởi động lại dịch vụ.
4. Ổn định ~1 tuần → hạ `memory_limit` của job về `1024M`, rồi gỡ nhánh `Excel::store` — mỗi việc
   một commit riêng.

## Phạm vi không làm

- Không chuyển các bộ xuất khác sang luồng.
- Không thêm/nâng thư viện.
- Không đổi nội dung cột, thứ tự sheet, bộ lọc.
- Không tự co giãn độ rộng cột.
