# Báo cáo giao ban — người khoa chỉ lấy số liệu một lần

Ngày: 2026-07-31

## Bối cảnh

Đợt trước (`docs/superpowers/specs/2026-07-31-giaoban-4-dieu-chinh-design.md`, Ý 1) đã mở
quyền "Lấy số liệu từ HIS" cho người được phân công khoa, với phạm vi thao tác bằng quản trị:
lấy lại bao nhiêu lần cũng được, tự chọn khung giờ.

Thực tế sử dụng cho thấy nới thế là rộng quá. Người khoa cần đúng một việc: khi phòng KHTH
chưa lấy số liệu thì họ tự lấy để có cái mà nhập, không phải chờ. Lấy **lại** là thao tác khác
hẳn — nó tính lại `auto_value` của toàn viện và chỉ nên nằm trong tay KHTH.

Đợt này thu hẹp lại: người khoa lấy được đúng một lần cho mỗi báo cáo; sau đó chỉ xem.
Quản trị không đổi gì.

## Quy tắc

Người **không phải** quản trị lấy được số liệu khi và chỉ khi:

- có ít nhất một bản ghi trong `giaoban_user_departments` (quyền cơ sở, đã có từ đợt trước), **và**
- báo cáo của ngày đang xem **chưa từng lấy số liệu**.

"Chưa từng lấy số liệu" = `giaoban_reports.data_fetched_at` rỗng, hoặc chưa có bản ghi báo cáo
cho ngày đó.

Dùng `data_fetched_at` chứ không dùng "báo cáo đã tồn tại": luồng kíp trực (`reportForDuty()`,
`copyDuties()`) tạo bản ghi báo cáo qua `getOrCreateReport()` mà chưa lấy số liệu lần nào. Lấy
sự tồn tại của bản ghi làm mốc sẽ khoá nhầm người khoa ngay từ đầu.

Quản trị: không đổi, lấy lại bao nhiêu lần cũng được.

## Điểm mấu chốt về kiến trúc

`$canFetch` hiện là biến Blade, tính một lần trong `GiaoBanController::index()` và render cứng
vào HTML. Điều kiện mới phụ thuộc **ngày đang xem**, mà người dùng đổi ngày bằng JS không tải
lại trang. Vì vậy quyết định hiện/ẩn phải chuyển từ Blade sang JS, chạy lại sau mỗi `loadReport()`.

Phân vai rõ:

- **Blade / `$canFetch`** — quyền cơ sở: có render nút "Lấy số liệu" và hai ô khung giờ ra DOM
  hay không. Người chưa được gán khoa nào thì không bao giờ có các phần tử đó.
- **JS / `res.can_fetch`** — trạng thái theo ngày: các phần tử đã có trong DOM được hiện hay ẩn.
- **Server / `fetchData()`** — lớp chặn thật. JS chỉ là trải nghiệm.

## Thay đổi

### 1. `app/Services/GiaoBan/GiaoBanPermission.php`

Thêm hàm thuần:

```php
public static function canFetchReport($isAdmin, array $assignedDeptIds, $daFetchRoi)
```

- `$isAdmin` → `true` (lấy lại bao nhiêu lần cũng được).
- Không qua `canFetchData($isAdmin, $assignedDeptIds)` → `false`.
- `$daFetchRoi` truthy → `false`.
- Còn lại → `true`.

`canFetchData()` giữ nguyên, vẫn là quyền cơ sở cho Blade.

### 2. `app/Http/Controllers/KHTH/GiaoBanController.php`

**`show()`** trả thêm hai khóa:

- `can_fetch` (bool) — `canFetchReport()` tính trên báo cáo của đúng ngày đang xem.
- `data_fetched_at` (string|null) — lấy từ `$report`, để JS dựng dòng thông báo.

`can_fetch` đặt ở **cấp gốc** của JSON — nó có nghĩa cả khi `report` là `null` (ngày chưa có
báo cáo, người khoa vẫn lấy được). `data_fetched_at` đặt **trong `$reportOut`**, cạnh
`from_time`/`to_time`, vì nó là thuộc tính của báo cáo và chỉ tồn tại khi có báo cáo.

**`fetchData()`** đổi guard. Tra bản ghi báo cáo **đã có** của ngày đó (`GiaoBanReport::where('report_date', $date)->first()`,
có thể trả `null`) **trước** khi gọi `getOrCreateReport()`, rồi:

```php
$daFetch = $daCo ? $daCo->data_fetched_at : null;
if (!GiaoBanPermission::canFetchReport($isAdmin, $assigned, $daFetch)) abort(403);
```

Thứ tự này bắt buộc vì hai lý do: `fetchAndStore()` tự đặt `data_fetched_at` ở dòng cuối, và
`getOrCreateReport()` có thể vừa tạo một bản ghi mới — đọc sau bất kỳ hàm nào trong hai hàm đó
đều cho câu trả lời sai.

Guard này thay cho lời gọi `canFetchData()` hiện tại, không đứng thêm bên cạnh: `canFetchReport()`
đã bao hàm quyền cơ sở.

Không đổi `validate()`, không đổi nhánh 422 "Báo cáo đã chốt".

### 3. `resources/views/khth/giaoban-index.blade.php`

Blade giữ nguyên: nút `#btn-fetch` và hai ô `#from_time`/`#to_time` vẫn render theo `$canFetch`.

Thêm hàm JS chạy trong `.done()` của `loadReport()`, cạnh `dienKhungGioDaLuu()`:

- Hiện/ẩn `#btn-fetch` và hai ô khung giờ theo `res.can_fetch`.
- Khi `data_fetched_at` không rỗng, hiện dòng nhỏ: `Số liệu đã lấy lúc 07:12 ngày 31/07/2026`.
  Với người không phải quản trị nối thêm: ` — cần lấy lại thì liên hệ phòng KHTH.`
- Dòng này hiện cho **cả** quản trị: thông tin "lấy lúc nào" tự nó hữu ích, không phải chỉ để
  giải thích việc bị chặn.

Thứ tự gọi: phải sau `dienKhungGioDaLuu(res)`, vì hàm đó ghi giá trị vào hai ô mà ta có thể vừa
ẩn đi. Ẩn một ô đã có giá trị thì vô hại; ghi vào ô đã ẩn cũng vô hại — nhưng giữ thứ tự
"điền rồi mới ẩn/hiện" để đọc code không phải suy nghĩ.

Hai ô khung giờ ẩn theo cùng điều kiện với nút: người khoa không lấy được nữa thì hai ô đó
không dùng vào việc gì.

## Hệ quả đã cân nhắc

- `dienKhungGioDaLuu()` (làm ở đợt trước, để người khoa không vô tình đè khung giờ) từ nay chỉ
  còn tác dụng với quản trị — người khoa chỉ thấy hai ô đúng một lần, khi báo cáo chưa có gì
  để mà đè. **Không xoá**: quản trị vẫn cần, và nó vẫn là lớp bảo vệ đúng cho trường hợp duy
  nhất người khoa còn thấy hai ô.
- Rủi ro hai người cùng bấm khi báo cáo chưa fetch vẫn còn (đã ghi ở spec đợt trước, mục "Chạy
  chồng"). Thực tế giảm hẳn vì sau lượt đầu là khoá luôn. Không thêm khoá trong đợt này.
- Người khoa lấy nhầm khung giờ ở lượt đầu thì không tự sửa được nữa, phải nhờ KHTH lấy lại.
  Chấp nhận: đây chính là ý đồ của yêu cầu — thao tác toàn viện thuộc về KHTH.
- Rủi ro "ô kế thừa một-lần-duy-nhất bị đóng dấu sai ngày" (spec đợt trước) **giảm nhưng chưa
  hết**: người khoa vẫn tạo được báo cáo cho một ngày bất kỳ ở lượt đầu tiên của ngày đó.
- Báo cáo `final` mà chưa từng fetch: trường hợp có thật — kíp trực tạo báo cáo qua
  `getOrCreateReport()`, admin chốt, `data_fetched_at` vẫn null. Người khoa thấy nút, bấm, qua
  được guard 403 rồi bị chặn ở nhánh 422 "Báo cáo đã chốt, cần mở khóa trước." Kết quả cuối
  đúng và thông điệp rõ, chỉ là nút không đáng hiện. Không xử lý trong đợt này.
- Bất biến thứ tự đọc chỉ được bảo vệ bằng comment: trong `fetchData()`, `$daCo` phải đọc trước
  `getOrCreateReport()`/`fetchAndStore()`. Nếu ai đó về sau chuyển xuống dưới, toàn bộ 173 test
  vẫn xanh vì `canFetchReport()` là hàm thuần còn tầng controller không có test nào (module giao
  ban chỉ có Unit test, `tests/Feature` không có test giao ban). Dựng hạ tầng Feature test cho
  module này vượt phạm vi đợt; ghi nhận rủi ro regression.
- Đường 403 của `fetchData()` nay trả thông điệp cụ thể thay vì chuỗi rỗng, và JS tự gọi lại
  `loadReport()` sau khi bị chặn để đồng bộ lại nút/khung giờ theo đúng `can_fetch` mới nhất
  — tránh kịch bản người dùng bấm lại liên tục vì tưởng HIS lỗi.

## Không đổi

`finalize`, `unlock`, `export`, `present`, toàn bộ màn cấu hình vẫn yêu cầu `giaoban-admin`.
Quyền sửa ô số liệu (`saveCell`) không liên quan và không đụng tới — người khoa vẫn nhập tay
bình thường sau khi số liệu đã được lấy.

## Kiểm thử

`tests/Unit/GiaoBan/GiaoBanPermissionTest.php` bổ sung ca cho `canFetchReport`:

- admin, báo cáo đã fetch → `true`
- người khoa được gán, báo cáo chưa fetch (`null`) → `true`
- người khoa được gán, báo cáo đã fetch → `false`
- không được gán khoa nào, báo cáo chưa fetch → `false`

Phần JS và view không có test tự động (dự án không có hạ tầng test JS) — nghiệm thu bằng
`php artisan view:clear` và đọc lại code.

## Nghiệm thu

1. Tài khoản khoa, ngày chưa có số liệu: thấy nút "Lấy số liệu" và hai ô khung giờ, bấm được.
2. Ngay sau khi lấy xong, không cần tải lại trang: nút và hai ô biến mất, hiện dòng
   "Số liệu đã lấy lúc ... — cần lấy lại thì liên hệ phòng KHTH."
3. Đổi sang một ngày khác chưa có số liệu: nút và hai ô hiện lại.
4. Tài khoản khoa gọi thẳng `POST giao-ban/fetch-data` cho ngày đã có số liệu: nhận 403.
5. Tài khoản quản trị: nút luôn hiện, lấy lại được bao nhiêu lần cũng được, và vẫn thấy dòng
   "Số liệu đã lấy lúc ...".
