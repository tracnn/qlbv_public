# Màn chi tiết hồ sơ chứng từ điện tử dạng modal — thiết kế

**Ngày:** 2026-08-21
**Trạng thái:** đã chốt với chủ dự án

## Mục tiêu

Xem chi tiết một hồ sơ chứng từ điện tử **ngay trên màn danh sách**, không phải chuyển trang rồi bấm quay lại. Người vận hành thường duyệt hàng chục hồ sơ liên tiếp; mỗi lần chuyển trang là một lần mất bộ lọc và mất vị trí đang xem.

## Phạm vi

**Trong phạm vi:** modal chi tiết trên `bhyt.ctdt.index`, tách phần thân dùng chung, chuyển JS sang uỷ nhiệm sự kiện.

**Ngoài phạm vi:** đổi nội dung hiển thị, đổi cách nạp tab, liên kết sâu bằng hash URL (đã cân nhắc và bỏ), gộp màn nạp tệp vào modal.

## Quyết định đã chốt

| Câu hỏi | Chốt |
|---|---|
| Trang chi tiết riêng còn không? | **Giữ.** Modal là đường chính; trang riêng vẫn sống để gửi link, mở tab mới, và làm đường lùi khi modal hỏng. |
| Sau khi ký-và-gửi trong modal? | **Đóng modal + `table.ajax.reload(null, false)`.** Giữ nguyên bộ lọc và trang đang xem. |
| Xử lý bẫy `@push` thế nào? | **Tách thân thành partial thuần đánh dấu; JS về màn chủ nhà, uỷ nhiệm sự kiện.** |

### Vì sao bác hai hướng còn lại

**Nhét `<script>` thẳng vào fragment.** jQuery `.html()` có chạy script nội tuyến nên nó hoạt động — nhưng mỗi lần mở modal lại gắn thêm một bộ handler nữa lên cùng `#btn-ky-va-gui`. Mở năm hồ sơ rồi bấm gửi là **năm lần POST**. Trên một module mà cổng BHXH **không khử trùng lặp được** (PL02 không có mã giao dịch phía client), đây không phải đánh đổi được phép cân nhắc.

**Nhúng trang cũ trong `<iframe>`.** Rẻ nhất về công, nhưng nạp lại toàn bộ layout AdminLTE bên trong modal, và mọi liên lạc modal ↔ danh sách phải đi qua `postMessage` — đúng thứ mà yêu cầu "gửi xong nạp lại bảng" cần.

## Vấn đề cốt lõi: `@push` rơi im lặng

`resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php` hiện tự đẩy JS của nó bằng `@push('after-scripts')`. Chỉ thị đó **chỉ có tác dụng khi view được render bên trong một layout có `@stack('after-scripts')`**. Nạp partial ấy bằng AJAX vào modal thì khối `@push` bị **bỏ đi không một lời báo**: nút "Ký và gửi" vẫn hiện, vẫn bấm được, và **không có gì xảy ra**.

Đây là hạng lỗi tệ nhất trong module này — im lặng, và nằm đúng trên nút nguy hiểm nhất.

Thiết kế loại bỏ nó **bằng cấu trúc**: fragment không chứa script thì không có gì để rơi. Một test canh điều đó, không dựa vào kỷ luật của người sửa sau.

## Kiến trúc

### Tệp

| Tệp | Việc |
|---|---|
| `resources/views/bhyt/ctdt/partials/than-chi-tiet.blade.php` | **Mới.** Toàn bộ thân chi tiết: khối thông tin, nút ký-gửi, nút xoá, dải tab, `#noi-dung-tab`. Thuần đánh dấu. |
| `resources/views/bhyt/ctdt/partials/js-chi-tiet.blade.php` | **Mới.** Một bản JS duy nhất, uỷ nhiệm sự kiện, dùng chung cho trang riêng và modal. |
| `resources/views/bhyt/ctdt/detail.blade.php` | Thành vỏ mỏng: `@include` hai partial trên, cộng phần nghe sự kiện của riêng trang. |
| `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php` | Bỏ khối `@push`; chỉ còn đánh dấu. |
| `resources/views/bhyt/ctdt/index.blade.php` | Thêm khung modal, include JS dùng chung, nghe hai sự kiện, chặn click thường trên hai chỗ mở chi tiết. |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Thêm `detailThan($ma_ho_so)`. |
| `routes/web.php` | Thêm `GET ctdt/detail/{ma_ho_so}/than` → `bhyt.ctdt.detail.than`. |

### Ranh giới: JS dùng chung không biết nó đang ở đâu

`js-chi-tiet` xử lý ba việc — chuyển tab, ký-và-gửi, xoá hồ sơ — và **không biết** mình đang nằm trên trang riêng hay trong modal. Xong việc, nó chỉ **phát một sự kiện** trên `document` rồi thôi:

- `ctdt:da-xep-hang` — máy chủ đã nhận lệnh ký-và-gửi
- `ctdt:da-xoa` — hồ sơ đã bị xoá

Trang chủ nhà tự quyết phản ứng:

| Chủ nhà | `ctdt:da-xep-hang` | `ctdt:da-xoa` |
|---|---|---|
| `detail.blade.php` | `location.reload()` | về `bhyt.ctdt.index` |
| `index.blade.php` | đóng modal + `table.ajax.reload(null, false)` | đóng modal + `table.ajax.reload(null, false)` |

**Cố ý không truyền cờ `$trongModal` xuống JS.** Một tham số như thế buộc mọi hành vi thêm sau này phải rẽ nhánh theo nó, và số nhánh chỉ có tăng. Sự kiện thì không: thêm một chủ nhà thứ ba chỉ là thêm một chỗ nghe.

### JS dùng chung nạp một lần, khi trang tải

`index.blade.php` include `js-chi-tiet` **ngay khi trang tải**, không phải lúc mở modal. Uỷ nhiệm sự kiện gắn trên `document` nên nó không cần phần tử đích tồn tại sẵn — đó chính là lý do chọn uỷ nhiệm.

### Chỉ có MỘT thân chi tiết trên mỗi trang

`than-chi-tiet` dùng định danh (`#noi-dung-tab`, `#ctdt-tabs`, `#btn-ky-va-gui`). Điều đó chỉ đúng khi mỗi trang có tối đa **một** thân chi tiết — trang riêng có đúng một, màn danh sách có đúng một (bên trong modal, thay nội dung mỗi lần mở). Không mở hai modal chi tiết cùng lúc, và không nhúng thân chi tiết vào chỗ nào khác.

### Mã hồ sơ đọc từ DOM, không nhúng vào JS

JS hiện tại nhúng `@json($hoSo->ma_ho_so)` và `@json($hoSo->ma_gd)` thẳng vào mã. Một bản JS dùng chung, gắn một lần, **không làm thế được** — lúc gắn thì chưa biết hồ sơ nào sẽ được mở.

Thay bằng: `than-chi-tiet.blade.php` mang dữ liệu trên phần tử bọc ngoài

```html
<div class="ctdt-chi-tiet" data-ma-ho-so="{{ $hoSo->ma_ho_so }}" data-ma-gd="{{ $hoSo->ma_gd }}">
```

JS đọc bằng `$(this).closest('.ctdt-chi-tiet').data('ma-ho-so')`. Blade `{{ }}` thoát giá trị khi ghi vào thuộc tính, và jQuery `.data()` trả về chuỗi — không có đường nào để `ma_ho_so` (đọc từ XML bên ngoài) biến thành thẻ HTML.

### URL và ký tự `#`

`ma_ho_so` ở nhánh lùi GUID **chứa dấu `#`** (ví dụ `Id-abc#1`). Mọi chỗ ghép nó vào URL đều phải `encodeURIComponent`, nếu không trình duyệt cắt từ dấu `#` và yêu cầu trỏ sai hồ sơ. Quy tắc này đã có trong mã hiện tại ở ba chỗ; bản mới giữ nguyên.

## Luồng

1. Người dùng bấm mã hồ sơ hoặc nút "Chi tiết" trên danh sách.
2. JS chặn click **thường**, mở modal, xoá sạch thân cũ, gọi `GET ctdt/detail/{ma}/than`.
3. Máy chủ trả partial `than-chi-tiet` (không layout).
4. JS đổ vào thân modal, rồi nạp **tab đầu tiên** — y như trang riêng đang làm.
5. Bấm một tab khác → `GET ctdt/detail/{ma}/tab/{loai}` (route sẵn có, không đổi).
6. Bấm "Ký và gửi" → POST như cũ → thành công → phát `ctdt:da-xep-hang` → danh sách đóng modal và nạp lại bảng.

**Ctrl+click và chuột giữa vẫn mở tab mới.** Hai chỗ mở chi tiết vẫn là `<a href>` **thật** trỏ trang riêng; chỉ click thường bị chặn. Không dùng `<a href="#">` hay `<button>`.

## Trường hợp biên

| Tình huống | Xử |
|---|---|
| Gọi `.../than` hỏng | Hiện lỗi đọc được trong thân modal. Không để chữ "Đang tải…" treo vĩnh viễn. |
| Mở modal khi thân còn nội dung hồ sơ trước | **Xoá sạch thân trước khi gọi mạng.** Để nguyên là mời người dùng đọc nhầm hồ sơ. |
| Hồ sơ vừa bị người khác xoá → 404 | Báo rõ trong modal, không im lặng. |
| Nút xoá | Vẫn chỉ hiện với `superadministrator`. Controller **vẫn tự kiểm quyền** — giao diện không phải chốt bảo vệ. |
| Hộp xác nhận xoá | Giữ nguyên cảnh báo mất dấu vết `MaGD` khi hồ sơ đã gửi lên cổng. |

## Bẫy Blade phải giữ cảnh báo

Chú thích hiện có trong `detail.blade.php` phải theo sang tệp mới: viết `@@if` (hai dấu a-còng) trong chú thích JavaScript, **không phải một dấu**. Blade dịch **mọi** chỉ thị nó thấy, kể cả bên trong chú thích JS, và một chỉ thị không ngoặc sinh ra PHP hỏng — làm cả trang ném `Parse error`, không render nổi một dòng.

Lỗi này **đã xảy ra thật**: `detail.blade.php` không biên dịch được suốt từ Giai đoạn 2B mà không ai biết, vì trang chỉ vỡ khi có người mở nó.

## Kiểm thử

Chốt canh chính là **ngăn bẫy `@push` quay lại**:

| Test | Khẳng định |
|---|---|
| `than-chi-tiet.blade.php` không chứa `<script` | Fragment không có script thì không có gì để rơi. |
| `than-chi-tiet.blade.php` không chứa `@extends` | Nó là fragment, không phải trang. |
| `nut-ky-va-gui.blade.php` không còn `@push` | Đúng nguồn của lỗi cũ. |
| `index.blade.php` có khung modal và include JS dùng chung | Thiếu một trong hai là modal không mở được. |
| Route `.../than` trả fragment | Phản hồi không chứa `<html`. |
| `CtdtBladeCompilesTest` | Đã quét cả `partials/`, nên hai partial mới tự động được canh biên dịch. |

## Gộp luôn một lỗi nhỏ

Tiêu đề hộp xác nhận gửi lại vẫn ghi *"Hồ sơ đã từng được tiếp nhận"*, trong khi thông điệp máy chủ đã sửa thành *"đã từng được gửi lên cổng BHXH"*. Cùng một lỗi: `CtdtLuuHoSo::noiLichSu()` ghi dòng lịch sử khi `ma_gd` **hoặc** `ma_ket_qua` khác rỗng, nên một hồ sơ từng bị cổng **từ chối** cũng rơi vào nhánh này. Nói "đã được tiếp nhận" ở đó là nói sai với người vận hành.

## Ràng buộc kỹ thuật

- Laravel 5.5 / PHP 7.4 / PHPUnit 6 là **sàn cứng**. Không cú pháp PHP 8. Không `: void` trên `setUp()`.
- Bootstrap 3 (AdminLTE 2). Lớp `modal-xxl` đã có sẵn trong `public/css/customize.css` — dùng lại, đừng khai lớp mới.
- Chú thích trong mã viết **không dấu**; Markdown viết **có dấu**.
- Không chạy `SignCtdtJob` / `SubmitCtdtJob` / `queue:work` khi phát triển — máy này gửi thật lên cổng BHXH.
- Cấm `RefreshDatabase` / `DatabaseMigrations` trong test.
