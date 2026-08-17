# Màn hình tra cứu lỗi hồ sơ theo mã điều trị

Ngày: 2026-08-17

## Bối cảnh

`TreatmentIssueService` đã gộp ba nguồn lỗi của cùng một đợt điều trị — sai sót y lệnh
(`order_check_violations`), lỗi tra thẻ BHYT (`check_hein_cards`), lỗi XML3176
(`xml3176_error_results`) — khoá theo cùng một giá trị `ma_lk` = `treatment_code`. Service
này đang phục vụ `GET /api/order-check/violations` cho HIS.

Trong app chưa có màn hình nào tra một hồ sơ đơn lẻ. Màn "Danh sách vi phạm"
(`khth/order-check-index`) lọc theo khoảng ngày và chỉ thấy nhóm y lệnh; người khoa muốn
biết "hồ sơ này có lỗi gì" phải mở ba màn khác nhau.

## Mục tiêu

Một màn hình: nhập hoặc quét mã điều trị → hiện thông tin hồ sơ và toàn bộ lỗi của nó.

## Ngoài phạm vi

- Không sửa `TreatmentIssueService`, không đổi hợp đồng API đang phục vụ HIS.
- Không sửa màn "Danh sách vi phạm" hay các màn cấu hình order-check.
- Không thêm nguồn lỗi thứ tư (ví dụ bộ kiểm QĐ130 nội bộ).
- **Không kiểm lại XML3176 cho một hồ sơ.** Lỗi XML3176 chỉ đến từ tệp kết quả do cổng
  BHXH trả về, nhập qua `Xml3176Importer`; không có cơ chế kiểm lẻ từng hồ sơ.

---

## 1. Hướng làm

Màn hình blade riêng + endpoint AJAX nội bộ, dùng lại `TreatmentIssueService`.

Hai hướng đã cân nhắc và loại:

- **Gọi thẳng `/api/order-check/violations` từ trình duyệt**: phải nhúng token API vào
  trang, API cố tình lược PII nên không có thông tin hồ sơ, và không đổi trạng thái được.
- **Nhúng modal vào màn "Danh sách vi phạm"**: dính quyền `order-check`, trái mục tiêu
  cho người khoa quét nhanh.

## 2. Quyền và định tuyến

### 2.1. Role mới

Role `tra-cuu-loi-ho-so`, display name "Tra cứu lỗi hồ sơ".

Là **role** chứ không phải permission, theo đúng khuôn
`database/migrations/2026_07_29_090000_them_role_order_check.php`:
`AppServiceProvider::filterMenu` chỉ kiểm `hasRole()`, không có nhánh `can()` — cấp bằng
permission thì route cho vào nhưng menu vẫn ẩn.

Migration mới `2026_08_17_090000_them_role_tra_cuu_loi_ho_so.php`:

- Tạo role nếu chưa có.
- Gán cho mọi user đang có role `order-check` (nhóm đang làm việc với dữ liệu này).
- Sau mỗi lần insert vào `role_user` bằng query builder phải
  `Cache::forget('laratrust_roles_for_user_' . $userId)` — Laratrust cache `hasRole()` 60
  **phút** và insert qua query builder không bắn event nên cache không tự xoá.
- `down()`: xoá `role_user` rồi xoá role bằng **query builder**, không dùng
  `$role->delete()` (event `deleting` của Laratrust gọi quan hệ `users()` tới
  `App\CustomUser` vốn nằm trên kết nối Oracle `ACS_RS` → ORA-00942). Xoá cache cho từng
  user bị ảnh hưởng.

Superadministrator **không** được miễn trừ ở `CheckRole`, nên phải nằm trong danh sách
được gán role, giống ghi chú ở migration order-check.

### 2.2. Routes

Nhóm mới trong `routes/web.php`, đặt cạnh nhóm order-check:

```php
Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:tra-cuu-loi-ho-so']], function () {
    Route::get('tra-cuu-loi-ho-so',                 'KHTH\TraCuuLoiHoSoController@index')
        ->name('khth.tra-cuu-loi-ho-so');
    Route::get('tra-cuu-loi-ho-so/tra-cuu',         'KHTH\TraCuuLoiHoSoController@traCuu')
        ->name('khth.tra-cuu-loi-ho-so-tra-cuu');
    Route::get('tra-cuu-loi-ho-so/in',              'KHTH\TraCuuLoiHoSoController@in')
        ->name('khth.tra-cuu-loi-ho-so-in');
    Route::post('tra-cuu-loi-ho-so/tra-lai-the',    'KHTH\TraCuuLoiHoSoController@traLaiThe')
        ->name('khth.tra-cuu-loi-ho-so-tra-lai-the')
        ->middleware('throttle:5,1');
});
```

### 2.3. Menu

Mục **cấp 1 độc lập** trong `config/adminlte.php`, `checkrole => 'tra-cuu-loi-ho-so'`,
icon `barcode`, `active => ['khth/tra-cuu-loi-ho-so*']`.

Không đặt dưới nhánh "Kiểm tra sai sót y lệnh": nhánh đó có `checkrole: order-check` ở cấp
cha nên người khoa sẽ không nhìn thấy mục con.

## 3. Kiến trúc

```
routes/web.php
  └─ KHTH\TraCuuLoiHoSoController      (đọc request, gọi service, trả JSON/blade)
       ├─ App\Services\OrderCheck\TreatmentIssueService   (dùng lại, không sửa)
       └─ App\Services\OrderCheck\TreatmentProfileService (mới)
```

`TreatmentProfileService::cua(string $treatmentCode): array|null` — trả mảng thuần thông
tin hồ sơ, `null` khi không tìm thấy. Không phụ thuộc `Request`, không bọc HTTP, để test
được không cần gọi HTTP và màn khác dùng lại được. Cùng lý do đã áp cho
`TreatmentIssueService`.

Controller không chứa truy vấn.

## 4. Nguồn dữ liệu hồ sơ

Truy vấn `his_treatment` trên kết nối `HISPro`, lọc `treatment_code = ?`, với
`left join his_gender ON his_gender.id = his_treatment.tdl_patient_gender_id`,
`left join his_branch ON his_branch.id = his_treatment.branch_id`,
`left join his_department ON his_department.id = his_treatment.last_department_id`,
`left join his_treatment_type ON his_treatment_type.id = his_treatment.tdl_treatment_type_id`.

**Cả bốn join đều `left`** — kể cả giới tính. Lệnh quét
`HISProKiemTraTheBHYT` dùng inner join `his_gender` vì nó chỉ cần hồ sơ đủ điều kiện gửi
cổng BHXH; màn này thì ngược lại, hồ sơ khuyết dữ liệu chính là hồ sơ cần soi, inner join
sẽ làm nó biến mất và người dùng tưởng mã sai. Hệ quả: `gender_code` có thể `null`, mục
6.3 phải chặn trước khi dispatch.

Ngoài các cột thô, service trả kèm bản đã định dạng để blade khỏi tự xử lý chuỗi ngày của
HIS: `patient_dob_text` (qua `dob()`), `in_time_text`, `out_time_text`,
`hein_card_from_time_text`, `hein_card_to_time_text` (qua `strtodatetime()`). Hai helper
này đã có sẵn ở `app/Http/Controllers/app-helpers.php`.

Cột trả về:

| Khoá | Nguồn |
|---|---|
| `treatment_code` | `his_treatment.treatment_code` |
| `patient_name` | `his_treatment.tdl_patient_name` |
| `patient_dob` | `his_treatment.tdl_patient_dob` |
| `gender_name` | `his_gender.gender_name` |
| `gender_code` | `his_gender.gender_code` |
| `hein_card_number` | `his_treatment.tdl_hein_card_number` |
| `hein_medi_org_code` | `his_treatment.tdl_hein_medi_org_code` (nơi ĐKBD của bệnh nhân) |
| `hein_card_from_time` / `hein_card_to_time` | `his_treatment.tdl_hein_card_from_time` / `..._to_time` |
| `department_name` | `his_department.department_name` (theo `last_department_id`) |
| `in_time` / `out_time` | `his_treatment.in_time` / `out_time` |
| `treatment_type_name` | `his_treatment_type.treatment_type_name` |
| `ma_cskcb` | `his_branch.hein_medi_org_code` — **cơ sở điều trị** |

`ma_cskcb` **phải** lấy từ `his_branch`, không lấy `tdl_hein_medi_org_code`. Hai cột là hai
khái niệm khác nhau; ghi chú tại `app/Console/Commands/HISProKiemTraTheBHYT.php:79` ghi kết
quả đo trên 45.995 hồ sơ: 4.194 giá trị ĐKBD khác nhau so với 2 cơ sở điều trị, trùng nhau
0,5%. Nhầm cột này thì bước "tra lại thẻ" (mục 6.3) chọn sai tài khoản cổng BHXH.

Mã điều trị được trim trước khi truy vấn. Mã rỗng → trả `null`, không truy vấn.

## 5. Giao diện

Blade `resources/views/khth/tra-cuu-loi-ho-so.blade.php`, layout `adminlte::page` như các
màn KHTH khác.

Bố cục một cột dọc:

1. **Ô nhập mã** — `autofocus`, phím `Enter` gọi tra cứu, sau khi tra xong tự bôi đen nội
   dung để lượt quét kế tiếp ghi đè. Đây là cách máy quét barcode làm việc: nó gõ chuỗi
   rồi gửi `Enter`.
2. **Nút mở camera quét QR** — dùng `html5-qrcode` đặt trong `public/` (không CDN, theo
   thông lệ của app). Nút chỉ hiện khi `navigator.mediaDevices` khả dụng; trang phục vụ
   qua HTTP thuần thì trình duyệt chặn camera, khi đó hiện chú thích thay vì nút hỏng.
   Quét thành công → điền vào ô nhập, đóng camera, tra cứu ngay.
3. **Thẻ Thông tin hồ sơ** — các trường ở mục 4, dạng hai cột nhãn/giá trị.
4. **Ba khối lỗi** — "Sai sót y lệnh", "Lỗi tra thẻ BHYT", "Lỗi XML3176"; mỗi khối một
   bảng và một badge đếm số dòng. Khối rỗng vẫn hiện, ghi "Không có".
5. Tổng `summary.total = 0` → dải xanh "Không phát hiện lỗi trên hồ sơ này".

Dòng `severity = critical` (y lệnh) và `critical_error = true` (XML3176) gắn nhãn đỏ.

Cột hiển thị mỗi bảng lấy đúng tập cột `TreatmentIssueService` đã trả (xem
`docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md` mục A2). Nhóm tra thẻ
trong màn nội bộ hiển thị **mã thẻ đầy đủ** — quy tắc che 4 số cuối chỉ áp cho API gửi ra
ngoài; người dùng màn này đã có toàn bộ thông tin hồ sơ trước mắt.

## 6. Thao tác

### 6.1. Đổi trạng thái vi phạm y lệnh

Chỉ áp cho nhóm `order_check`. Hai nhóm kia không có khái niệm trạng thái.

**Không thêm endpoint mới.** Màn hình gọi thẳng route sẵn có
`khth.order-check-update-status` (`POST khth/order-check-index/update-status`), nhận
`{id, status, note?}` cho **một** vi phạm mỗi lượt — đúng chữ ký hiện tại của
`OrderCheckController@updateStatus`. Route đó nằm trong nhóm `checkrole:order-check` nên
việc phân quyền đã do middleware lo: người chỉ có `tra-cuu-loi-ho-so` gọi vào sẽ bị 403 mà
không cần viết thêm dòng kiểm nào.

Nút đổi trạng thái chỉ render khi `Auth::user()->hasRole('order-check')` — đó là để giao
diện không mời gọi thao tác sẽ thất bại, không phải là lớp bảo vệ.

Hệ quả cần chấp nhận: không có thao tác hàng loạt trên màn này. Một hồ sơ đơn lẻ hiếm khi
có nhiều vi phạm cùng cần đổi, nên đổi từng dòng là đủ; thêm biến thể nhận mảng chỉ để
tiết kiệm vài cú bấm sẽ tạo đường thứ hai vào cùng một logic.

### 6.2. In phiếu lỗi

`in` trả blade in riêng (`tra-cuu-loi-ho-so-in.blade.php`), không extend layout AdminLTE:
khổ A4 dọc, gồm tên bệnh viện, thông tin hồ sơ, ba bảng lỗi, ngày giờ in. Trang tự gọi
`window.print()` khi tải xong. Mọi người xem được màn đều in được.

### 6.3. Tra lại thẻ BHYT

Dựng `$params` từ chính bản ghi `his_treatment` đã lấy ở mục 4, theo đúng công thức tại
`app/Console/Commands/HISProKiemTraTheBHYT.php:100`:

```php
$params = [
    'maThe'    => $hoSo['hein_card_number'],
    'hoTen'    => $hoSo['patient_name'],
    'ngaySinh' => dob($hoSo['patient_dob']),
    'ma_lk'    => $hoSo['treatment_code'],
    'maCskcb'  => $hoSo['ma_cskcb'],
    'maDkbd'   => $hoSo['hein_medi_org_code'],
    'gioiTinh' => $gioiTinh,
];
```

**Phải đảo mã giới tính**: `gender_code` 1 → 2, 2 → 1 trước khi truyền, giống command trên.
Bỏ bước này thì cổng BHXH nhận sai giới tính.

`jobKtTheBHYT::dispatch($params, false)->onQueue('JobKtTheBHYT')`. Tham số thứ hai
`checkOldValue = false` ép tra lại; để mặc định `true` thì job thấy đã có kết quả cũ hợp lệ
và thoát ngay — đúng nghĩa "không làm gì".

Chặn trước khi dispatch:

- `maCskcb` rỗng hoặc không nằm trong `config('organization.BHYT_CO_SO')` → trả lỗi "Không
  xác định được cơ sở của hồ sơ", không dispatch. Đây chính là điều kiện command đang dùng.
- Mã thẻ rỗng → "Hồ sơ không có mã thẻ BHYT", không dispatch.
- `gender_code` rỗng (hệ quả của left join ở mục 4) → "Hồ sơ thiếu giới tính", không
  dispatch. Gửi giới tính rỗng lên cổng chỉ đổi một lỗi rõ ràng lấy một kết quả sai.

Job chạy bất đồng bộ. Giao diện báo "Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít
giây để xem kết quả" và **không** tự nạp lại rồi hiển thị như thể đã có kết quả mới.
Throttle 5 lượt/phút ở route.

## 7. Xử lý lỗi

| Tình huống | Hành vi |
|---|---|
| Mã rỗng | Trình duyệt không gọi server, ô nhập báo tại chỗ. Endpoint `tra-cuu` vẫn tự kiểm và trả 422 khi bị gọi trực tiếp |
| Không có hồ sơ trên HIS | HTTP 200, khối hồ sơ hiện "Không tìm thấy hồ sơ với mã này trên HIS", **vẫn hiển thị ba khối lỗi** — hồ sơ cũ hoặc khác cơ sở vẫn có thể còn dữ liệu lỗi ở MySQL |
| Oracle lỗi | Khối hồ sơ hiện "Không lấy được thông tin từ HIS", ba khối lỗi vẫn hiển thị bình thường (nguồn MySQL độc lập). Chi tiết ngoại lệ chỉ ghi log |
| Không có role | 403 từ `CheckRole` |
| Có `tra-cuu-loi-ho-so` nhưng thiếu `order-check` và gọi đổi trạng thái | 403 từ `CheckRole` của nhóm route order-check (nút không render sẵn) |

Truy vấn hồ sơ và truy vấn lỗi tách rời, một bên hỏng không kéo bên kia — đó là lý do
`TreatmentProfileService` không gộp vào `TreatmentIssueService`.

## 8. Kiểm thử

`tests/Feature/TraCuuLoiHoSoTest.php` và `tests/Unit/TreatmentProfileServiceTest.php`:

1. Hồ sơ có đủ ba nhóm lỗi → JSON có `profile` đúng trường, ba mảng đúng số dòng.
2. Hồ sơ sạch → ba mảng rỗng, `summary.has_error = false`, HTTP 200.
3. Mã không tồn tại trên HIS nhưng có lỗi ở MySQL → `profile = null`, lỗi vẫn trả.
4. Mã rỗng → 422 theo khuôn lỗi hiện hành.
5. Không có role `tra-cuu-loi-ho-so` → 403.
6. Có `tra-cuu-loi-ho-so`, thiếu `order-check`, gọi `khth/order-check-index/update-status`
   → 403; dữ liệu không đổi.
7. `tra-lai-the` với hồ sơ đủ điều kiện → `Queue::fake()` khẳng định `jobKtTheBHYT` được
   dispatch **một lần**, `checkOldValue = false`, `gioiTinh` đã đảo.
8. `tra-lai-the` với hồ sơ thiếu `ma_cskcb` hoặc thiếu mã thẻ → không dispatch, trả lỗi rõ.

Lưu ý hạ tầng test đã biết (xem `docs/`): Mockery vỡ khi mô phỏng hàm có khai báo kiểu trả
về; repo đang có sẵn test đỏ, phải chạy toàn bộ trước khi lấy kết quả làm chuẩn.

## 9. Tệp thay đổi

Mới:

- `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php`
- `app/Services/OrderCheck/TreatmentProfileService.php`
- `resources/views/khth/tra-cuu-loi-ho-so.blade.php`
- `resources/views/khth/tra-cuu-loi-ho-so-in.blade.php`
- `database/migrations/2026_08_17_090000_them_role_tra_cuu_loi_ho_so.php`
- `public/` — thư viện `html5-qrcode`
- `tests/Feature/TraCuuLoiHoSoTest.php`, `tests/Unit/TreatmentProfileServiceTest.php`

Sửa:

- `routes/web.php` — thêm nhóm route
- `config/adminlte.php` — thêm mục menu
