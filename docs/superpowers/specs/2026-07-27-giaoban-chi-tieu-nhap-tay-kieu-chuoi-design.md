# Chỉ tiêu nhập tay kiểu chuỗi (textarea)

Ngày: 2026-07-27
Phạm vi: `MetricSchema`, `MetricValidator`, `metric-builder.js`, `giaoban-index.blade.php`, `GiaoBanController::saveCell`.
Xuất phát: nhóm B của phiếu yêu cầu KHTH — danh sách BN mổ cấp cứu / mổ phiên / chờ mổ / theo dõi.

## Bối cảnh

Phiếu yêu cầu KHTH có bốn mục (3–6) là **danh sách bệnh nhân**, không phải con số:

> *khoaps — SP Thắm: Thai 39 tuần 5 ngày CD lần 2 / Viêm gan B / PTLT cũ*
> *SP Huyên: Thai 38 tuần 5 ngày CD lần 2 / PTLT cũ*

Ảnh mẫu cho thấy **một ô chứa nhiều bệnh nhân** cho mỗi khoa, chứ không phải mỗi bệnh nhân một dòng bảng. Đó là hình dạng dữ liệu tự nhiên của thứ này: một đoạn văn bản tự do theo khoa theo kỳ.

Hệ thống hiện chỉ lưu được số: `giaoban_report_cells.manual_value` là `decimal(12,2)`.

## Quyết định đã chốt

| Câu hỏi | Chốt |
|---|---|
| Số lượng BN mổ phiên / mổ cấp cứu | Nhập tay (chỉ tiêu số, đã hỗ trợ sẵn) |
| Danh sách BN | Nhập tay kiểu **chuỗi** |
| Kiểu ô nhập | **Textarea thuần**, xuống dòng — không phải trình soạn định dạng |
| Kế thừa kỳ trước cho chỉ tiêu chuỗi | **Chưa làm**, để sau nếu thấy phiền |
| Tự sinh từ HIS | **Không** — dù dữ liệu mổ trong HIS có sẵn và đáng tin |

Quyết định cuối cùng đó khiến bảng `giaoban_report_patients` trong spec `2026-07-27-giaoban-bo-sung-theo-yeu-cau-khth-design.md` **không cần nữa**. Bỏ được một thực thể dữ liệu, một luồng đồng bộ HIS, và bất biến chống ghi đè đi kèm.

Đánh đổi đã biết: không đối chiếu được với HIS, không sắp xếp hay lọc theo bệnh nhân. Với nhu cầu "đọc lên trong cuộc giao ban" thì không cần hai thứ đó.

## Thiết kế

Không thêm bảng, không thêm cột, không migration.

Chỉ tiêu chuỗi chỉ là **chỉ tiêu nhập tay với `value_type = 'text'`** — thêm một lựa chọn vào ô "Kiểu giá trị" đã có.

Giá trị lưu vào cột **`note`** của `giaoban_report_cells` (đã là `text`), phân biệt bằng mã chỉ tiêu — đúng cơ chế ghi chú khoa đang dùng với mã `note`. Cột `manual_value` để trống.

### 1. `MetricSchema`

Thêm `text` vào `options` của `value_type` trong khai báo type `manual`.

Kèm cơ chế `show_if` để form builder tự ẩn ô vô nghĩa:

```php
'unit'    => ['widget' => 'text',   'label' => 'Đơn vị', 'max' => 20,
              'show_if' => ['value_type' => ['int', 'decimal', 'percent']]],
'min'     => ['widget' => 'number', 'label' => 'Nhỏ nhất',
              'show_if' => ['value_type' => ['int', 'decimal', 'percent']]],
'max'     => ['widget' => 'number', 'label' => 'Lớn nhất',
              'show_if' => ['value_type' => ['int', 'decimal', 'percent']]],
'default' => ['widget' => 'number', 'label' => 'Giá trị mặc định',
              'show_if' => ['value_type' => ['int', 'decimal', 'percent']]],
```

`show_if` là cơ chế tổng quát (khoá → các giá trị cho phép), không phải vá riêng cho `text`. Giữ nguyên tinh thần schema-driven: JS không hard-code loại nào.

`hint`, `required`, `carry_over` vẫn hiện với mọi kiểu. `carry_over` hiện với `text` nhưng bị validator chặn — xem mục 2.

### 2. `MetricValidator`

Khi `value_type === 'text'`, **chặn** khai `min`, `max`, `default`, `unit`, `carry_over` kèm thông báo tiếng Việt rõ ràng, thay vì lặng lẽ bỏ qua.

Chặn chứ không bỏ qua, vì hai lý do: người cấu hình biết ngay mình khai thừa, và nó bảo đảm bất biến ở mục 5.

Thêm giới hạn độ dài giá trị: **5.000 ký tự**. Đủ cho danh sách vài chục bệnh nhân, chặn được việc dán nhầm cả file vào. Kiểm ở `MetricSchema::kiemGiaTriNhapTay` (phía server), không chỉ ở trình duyệt.

### 3. Form builder (`metric-builder.js`)

`renderField` đọc `show_if`: nếu khoá tham chiếu có giá trị hiện tại không nằm trong danh sách cho phép thì không render ô đó.

Đổi `value_type` phải `render()` lại card để các ô ẩn/hiện theo — giống cách radio phạm vi khoa đang làm.

### 4. Màn giao ban (`giaoban-index.blade.php`)

`inp.value_type === 'text'` thì render `<textarea rows="4">` chiếm cả hàng (`col-md-12`) thay vì ô số trong `input-group`.

Không có nút hoàn tác, không tô vàng `bg-warning`, không `step`/`min`/`max` — những thứ đó chỉ có nghĩa với số.

Vẫn giữ: nhãn, dấu `*` khi bắt buộc, icon gợi ý, và `readonly` khi không có quyền sửa.

Giá trị lấy từ `c.note`, không phải `c.manual_value`.

### 5. `GiaoBanController::saveCell`

Định tuyến sang cột `note` khi chỉ tiêu là kiểu chuỗi. Chỗ tra khai báo (`GiaoBanDeptConfig::metricByCode`) đã có sẵn, dùng lại.

Cấu trúc hiện tại:

```
if (metric_code === 'note')  -> $cell->note = NoteSanitizer::clean(...)
else                         -> $cell->manual_value = ...
```

thành ba nhánh: ghi chú khoa (`note`, sanitize HTML) / chỉ tiêu chuỗi (`note`, sanitize **văn bản thuần**) / chỉ tiêu số (`manual_value`).

### Sanitize ở tầng ghi, không dựa vào kỷ luật escape khi hiển thị

Dựa vào việc mọi nơi hiển thị đều nhớ escape là mong manh — quên một lần là hở XSS lưu trữ, trên màn chiếu giữa cuộc giao ban. Chặn ngay lúc ghi thì sai sót về sau chỉ còn là lỗi thẩm mỹ, không thành lỗ hổng.

**Nhưng không dùng được `NoteSanitizer` hiện có.** Đã kiểm chứng bằng dữ liệu thật:

| Đầu vào | HTMLPurifier (`NoteSanitizer`) | `htmlspecialchars` |
|---|---|---|
| `Hb < 8 g/dL` | `Hb &lt; 8 g/dL` | `Hb &lt; 8 g/dL` |
| `<b>Nặng</b>` | `<b>Nặng</b>` — **giữ thành thẻ thật** | `&lt;b&gt;Nặng&lt;/b&gt;` |

HTMLPurifier nửa nọ nửa kia: escape dấu `<` của chỉ số xét nghiệm nhưng lại giữ `<b>` thành markup. Đổ vào textarea sẽ hiện lẫn lộn, và người dùng không hiểu vì sao chỗ này giữ chỗ kia không.

**Thêm `NoteSanitizer::cleanPlain()`** cho văn bản thuần:

```php
public static function cleanPlain($s)
{
    $s = (string) $s;
    $s = str_replace(["\r\n", "\r"], "\n", $s);                        // chuan hoa xuong dong
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);  // bo ky tu dieu khien
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return mb_substr($s, 0, 5000);
}
```

Kết quả: giá trị lưu trong DB **không bao giờ chạy được** dù hiển thị bằng `.html()`, `innerHTML` hay bất cứ cách nào. Hỏng thì hỏng theo hướng an toàn (hiện ra `&lt;`), không hỏng theo hướng mở toang.

### Ràng buộc đi kèm: phải giải mã khi đổ vào textarea

Vì lưu ở dạng đã mã hoá, khi nạp vào textarea phải giải mã lại, nếu không người dùng thấy `Hb &lt; 8` rồi lưu tiếp sẽ thành `&amp;lt;` — hỏng dần qua từng lần sửa.

Đã kiểm chứng vòng lặp: `Hb < 8` → mã hoá → giải mã → mã hoá lại **cho ra đúng chuỗi cũ**, không mã hoá kép. Điều kiện là luôn giải mã ở đúng một chỗ nạp.

Phía JS, giải mã bằng chính DOM (không cần thư viện):

```js
function giaiMaHtml(s) {
  var d = document.createElement('textarea');
  d.innerHTML = s === null || s === undefined ? '' : String(s);
  return d.value;
}
```

Phải có test khoá lại tính ổn định này — xem mục Kiểm thử.

## Bất biến và rủi ro

### Không đụng đường ghi số liệu

`initialManualValues` chỉ sinh giá trị cho chỉ tiêu có `carry_over` hoặc `default`. Kiểu `text` bị validator cấm cả hai (mục 2), nên hàm đó **không bao giờ chạm** vào ô chuỗi. Không cần thêm lớp bảo vệ nào trong `fetchAndStore`.

Đây là lý do thực chất của việc *chặn* thay vì *bỏ qua* ở mục 2: nó biến một quy ước thành một ràng buộc.

Kiểm tra kèm theo: ô chuỗi có `manual_value = null`, nên `$daCo` (lọc `whereNotNull('manual_value')`) coi như chưa tồn tại — nhưng vì `initialManualValues` không trả về mã đó nên vòng ghi không chạy tới. An toàn qua cả hai lớp.

### XSS lưu trữ — đã đóng ở tầng ghi

Giá trị lưu đã qua `htmlspecialchars`, nên không chạy được dù hiển thị bằng cách nào. Màn trình chiếu ở nhóm C **không** phải nhớ escape — đó chính là lý do chọn sanitize tại tầng ghi thay vì dựa vào kỷ luật ở tầng hiển thị.

Ngược lại, nhóm C phải nhớ điều khác: hiển thị bằng `.html()` (hoặc `{!! !!}`) để entity hiện ra đúng dấu `<`. Nếu dùng `.text()` thì người xem thấy `&lt;`. Hỏng thẩm mỹ, không hở bảo mật — đúng chiều an toàn.

Chỗ duy nhất phải giải mã là khi nạp vào textarea trên màn nhập liệu.

### `cellMap` và cân đối

Ô chuỗi vào `cellMap` với `auto = null`, `manual = null` → `display()` trả 0. `checkBalance` chỉ đọc các mã cân đối cố định (`bn_cu`, `bn_vao`, …) nên không ảnh hưởng. Không cần loại trừ gì thêm.

## Kiểm thử

Bổ sung vào `tests/Unit/GiaoBan/`:

- `MetricValidatorTest`: `value_type = 'text'` mà khai `min` / `max` / `default` / `unit` / `carry_over` → mỗi trường hợp một lỗi, đúng `field`.
- `MetricValidatorTest`: `value_type = 'text'` chỉ với `hint` + `required` → hợp lệ.
- `MetricSchemaTest`: `'text'` có trong `options` của `value_type`; các field `min`/`max`/`default`/`unit` đều khai `show_if`.
- `ManualInputRuleTest`: `kiemGiaTriNhapTay` với kiểu `text` — chuỗi thường hợp lệ, chuỗi quá 5.000 ký tự bị chặn, chuỗi rỗng hợp lệ (xoá ô).
- `GiaoBanReportServiceTest`: `initialManualValues` **không** trả về gì cho chỉ tiêu `text` kể cả khi kỳ trước có giá trị — khoá lại bất biến ở mục trên.
- `NoteSanitizerTest` (file đã có): `cleanPlain` —
  - `<script>alert(1)</script>` không còn thẻ chạy được;
  - `Hb < 8 g/dL, HA > 140` giữ nguyên nghĩa sau khi giải mã;
  - xuống dòng `\r\n` và `\r` đều thành `\n`, số dòng giữ nguyên;
  - ký tự điều khiển bị bỏ, `\n` và `\t` được giữ;
  - cắt đúng 5.000 ký tự;
  - **chống mã hoá kép:** `cleanPlain(giaiMa(cleanPlain($x))) === cleanPlain($x)`. Đây là test quan trọng nhất của nhóm này — nó khoá lại vòng lưu-sửa-lưu không làm hỏng dần văn bản.

Gate: `vendor/bin/phpunit --testsuite Unit` xanh sạch (hiện 229).

## Nghiệm thu tay

Phần giao diện không có test tự động (dự án không có hạ tầng test JS).

- [ ] Cấu hình: thêm chỉ tiêu **Nhập tay**, chọn Kiểu giá trị **text** → các ô Đơn vị / Nhỏ nhất / Lớn nhất / Giá trị mặc định **biến mất**; Giải thích và Bắt buộc nhập vẫn còn.
- [ ] Đổi ngược về `int` → các ô đó hiện lại, giá trị cũ không mất.
- [ ] Cố tình khai `min` cho chỉ tiêu text bằng tab JSON rồi Lưu → 422, card tô đỏ, báo lỗi tiếng Việt.
- [ ] Màn giao ban: chỉ tiêu text hiện **textarea chiếm cả hàng**, không có nút hoàn tác.
- [ ] Gõ danh sách nhiều dòng, Lưu, tải lại trang → nội dung và xuống dòng còn nguyên.
- [ ] Gõ chuỗi có `<b>test</b>` và `HA < 90` → hiển thị lại **đúng nguyên văn**, không bị nuốt dấu, không thành chữ đậm.
- [ ] Sửa và lưu ô đó **ba lần liên tiếp** → nội dung không đổi, không xuất hiện `&amp;lt;`. Đây là điểm kiểm chống mã hoá kép.
- [ ] Gõ `<script>alert(1)</script>`, lưu, tải lại → hiện ra đúng dòng chữ đó, **không** có hộp thoại nào bật lên.
- [ ] Tài khoản khoa không được phân công → không thấy khoa đó (quy tắc phân quyền đã làm vẫn áp dụng).
- [ ] Bấm "Lấy số liệu" lại → nội dung textarea **còn nguyên**, không bị xoá.

Điểm cuối là quan trọng nhất: nó chứng minh đường `fetchAndStore` không chạm vào ô chuỗi.

## Việc còn lại sau khi làm xong

Khai chỉ tiêu thực tế cho các khoa — việc của KHTH, không phải lập trình:

- Ngoại TH-CK và Phụ Sản: *Mổ cấp cứu*, *Mổ phiên*, *Chờ mổ* (số) + *DS mổ cấp cứu*, *DS mổ phiên*, *DS chờ mổ* (chuỗi)
- Phụ Sản thêm: *Đẻ thường* (số)
- Các khoa có BN theo dõi: *DS theo dõi* (chuỗi)

Gợi ý đặt tên chỉ tiêu chuỗi có tiền tố "DS " để phân biệt với chỉ tiêu số cùng chủ đề trên màn nhập liệu.
