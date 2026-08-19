# Module Chứng từ điện tử BHXH (PL02)

> Liên thông dữ liệu chứng từ điện tử qua dịch vụ web của cổng BHXH, theo Phụ lục 02
> ban hành kèm công văn BHXH Việt Nam 2025.
>
> Mọi `file:line` trích từ mã nguồn thực tế; khi mã thay đổi cần đối chiếu lại.
> Cập nhật: 2026-08-19 — **Giai đoạn 1 (nền dữ liệu) đã hoàn tất.**

---

## 1. Module này làm gì

Nạp gói XML chứng từ điện tử → kiểm lỗi → ký số → gửi lên cổng BHXH → theo dõi kết quả.
Trải nghiệm vận hành giống module XML3176 đang chạy: nạp tệp, xem danh sách, xem chi tiết,
theo dõi trạng thái, xuất báo cáo.

**Nguồn dữ liệu:** nạp tệp XML có sẵn (gói `HSCHUNGTU` / `HSDLGBT` / `HSDLGCS` hoàn chỉnh,
chưa ký). Module **không** sinh chứng từ từ HIS.

### Ba dịch vụ của cổng

| Dịch vụ | Thẻ gốc | `loaiHs` | URL |
|---|---|---|---|
| Chứng từ TT25/2025 | `HSCHUNGTU` | `39` | `/api/chungtugw/GuiHoSoChungTu2025` |
| Giấy báo tử | `HSDLGBT` | `60` | `/api/hososuckhoe/guiGiayToDienTu` |
| Giấy chứng sinh (TT22/2025) | `HSDLGCS` | `61` | `/api/hososuckhoe/guiGiayToDienTu` |

Máy chủ cổng: `https://egw.baohiemxahoi.gov.vn`. Lấy token dùng lại
`App\Services\BHYTLoginService` sẵn có (`/api/token/take`).

### Chín loại chứng từ

| Giá trị `LOAIHOSO` | Tên | Dịch vụ | Bảng |
|---|---|---|---|
| `CT03` | Giấy ra viện (Mẫu 02 - TT25) | CT2025 | `ctdt_ct03` |
| `CT04` | Tóm tắt hồ sơ bệnh án (Mẫu 03) | CT2025 | `ctdt_ct04` |
| `CT06` | Nghỉ dưỡng thai (Mẫu 11) | CT2025 | `ctdt_ct06` |
| `CT07` | Nghỉ việc hưởng BHXH (Mẫu 07) | CT2025 | `ctdt_ct07` |
| `GIAYDIEUTRINOITRU` | Điều trị nội trú (Mẫu 06) | CT2025 | `ctdt_dieu_tri_noi_tru` |
| `GIAYDIEUTRIVOSINH` | Điều trị vô sinh (Mẫu 09) | CT2025 | `ctdt_dieu_tri_vo_sinh` |
| `GIAYSUCKHOEME` | Sức khỏe người mẹ (Mẫu 10) | CT2025 | `ctdt_suc_khoe_me` |
| `GIAYBAOTU` | Giấy báo tử | GBT | `ctdt_giay_bao_tu` |
| `GIAYCHUNGSINH` | Giấy chứng sinh | GCS | `ctdt_giay_chung_sinh` |

---

## 2. Trạng thái hiện tại

**Đã có (Giai đoạn 1 — nền dữ liệu):**

| Thành phần | Vị trí |
|---|---|
| Hằng số giao thức PL02 | `config/ctdt.php` |
| Tham số theo cơ sở | `config/organization.php` khóa `chung_tu_dien_tu` |
| Disk lưu tệp | `config/filesystems.php` khóa `exportCtdt` |
| 12 bảng | `database/migrations/2026_08_19_1000*.php` |
| 12 model | `app/Models/BHYT/Ctdt/` |
| Interface loại chứng từ | `app/Services/Ctdt/Loai/LoaiChungTu.php` |
| 9 lớp loại tự mô tả | `app/Services/Ctdt/Loai/` |
| Registry tra loại | `app/Services/Ctdt/CtdtLoaiRegistry.php` |
| 59 test đơn vị | `tests/Unit/Ctdt/` |

**Chưa có (đúng phạm vi, không phải thiếu sót):** parser gói XML, importer, controller, view,
job kiểm/ký/gửi, service gửi lên cổng, lệnh Console quét thư mục, dashboard.

Vì vậy **module hiện chưa gọi mạng, chưa đọc tệp, chưa có giao diện** — triển khai lên máy chủ
ở trạng thái này không ảnh hưởng gì tới XML3176 hay bất kỳ nghiệp vụ nào đang chạy.

Lộ trình 5 giai đoạn và ghi chú chuyển tiếp: xem
[docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md](superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md)
mục 9 và mục 11.

---

## 3. Cấu hình

### 3.1. `config/organization.php` — tham số theo từng cơ sở

Khối `chung_tu_dien_tu`. Đây là thứ **người triển khai chỉnh khi cài đặt**:

| Khóa | Mặc định | Ghi chú |
|---|---|---|
| `submit_enabled` | `false` | **Cờ chặn gửi thật.** Mặc định tắt. |
| `import_enabled` | `true` | |
| `sign_enabled` | `true` | |
| `import_path` | `D:\XML\ChungTuDienTu\inbox` | Thư mục lệnh Console sẽ quét (Giai đoạn 5) |
| `queue_name` | `JobCtdt` | Hàng đợi kiểm lỗi |
| `sign_queue_name` | `JobSignCtdt` | Hàng đợi ký số |
| `submit_queue_name` | `JobSubmitCtdt` | Hàng đợi gửi cổng |

Đọc trong mã bằng `config('organization.chung_tu_dien_tu.submit_enabled')` — cùng cách
`SubmitXml3176Job` đọc `config('organization.BHYT.submit_xml_3176_enabled')`.

⚠️ **`submit_enabled` phải để `false` ở mọi môi trường thử nghiệm.** Cổng thật của BHXH nhận là
nhận thật, không có đường rút lại. Chỉ bật sau khi đã chạy thử và đối chiếu tay một hồ sơ.

⚠️ `config/organization.php` nằm trong `.gitignore` — **mỗi máy phải tự thêm khối này**, không tự
có khi `git pull`. Bản tham chiếu: [`docs/organization.php:37`](organization.php).

Tài khoản cổng BHXH thì **không cần khai thêm gì**: module dùng lại `organization.BHYT_CO_SO`
sẵn có (mỗi mã cơ sở KCB một tài khoản) qua `App\Services\BHYT\CauHinhCoSo`.

### 3.2. `config/ctdt.php` — hằng số giao thức PL02

Được track trong git, **giống nhau ở mọi cơ sở**, không ai chỉnh khi triển khai:

| Khóa | Nội dung |
|---|---|
| `token_url` | `https://egw.baohiemxahoi.gov.vn/api/token/take` |
| `dich_vu` | 3 mục — `the_goc`, `loai_hs`, `url` của ba dịch vụ |
| `ma_ket_qua` | 5 mã — `200` · `205` · `401` · `500` · `1001` |
| `ma_ket_qua_token` | 5 mã của dịch vụ lấy token (mục I của PL02) |

**Không có biến `.env` nào cho module này.** Đường cấu hình duy nhất là hai tệp trên; test
`CtdtCauHinhTest::config_ctdt_khong_giu_tham_so_theo_co_so` canh không ai vô tình dựng lại nguồn
sự thật thứ hai trong `config/ctdt.php`.

⚠️ **Khóa của `ma_ket_qua` và `ma_ket_qua_token` bị PHP ép thành `int`.** `'200' => ...` trở thành
khóa `int(200)`, nên `$ma === $phanHoi['MaKetQua']` **luôn trượt**. Tra bằng `array_key_exists()`
hoặc so sánh lỏng. Cảnh báo này đã ghi ngay trên hai mảng đó trong mã.

### 3.3. Disk `exportCtdt`

```php
'exportCtdt' => [
    'driver' => 'local',
    'root' => 'D:\XML\ChungTuDienTu',
],
```

⚠️ `config/filesystems.php` nằm trong `.gitignore` — **mỗi máy phải tự thêm khối này**, không
tự có khi `git pull`. Bản tham chiếu: [`docs/filesystems.php:131`](filesystems.php).

Cần tạo sẵn hai thư mục trên đĩa: `D:\XML\ChungTuDienTu` và `D:\XML\ChungTuDienTu\inbox`.

---

## 4. Cơ sở dữ liệu

12 bảng, tất cả đều **mới**, không sửa bảng nào đang có:

```
ctdt_ho_so          1 HOSO = 1 bản ghi = 1 đơn vị ký / gửi / ghi đè
   ├──< ctdt_chung_tu    mỗi giấy tờ trong hồ sơ (+ 5 cột rút gọn để lọc danh sách)
   │        └──1:1── 9 bảng chi tiết theo loại
   └──< ctdt_loi         lỗi kiểm tra trước khi gửi
```

**Nguyên tắc:** mọi cột dữ liệu chứng từ là `string`/`text` và `nullable`. PL02 khai mọi trường
là *Chuỗi ký tự*, kể cả ngày tháng. Ép sang `date`/`int` sẽ làm `201912121200` mất phần phút và
`01` mất số 0 đầu.

### Chạy migration

```bash
php artisan migrate --path=database/migrations
```

Chạy được trên CSDL đang vận hành. Không có `ALTER TABLE` lên bảng cũ.

---

## 5. Ba cái bẫy của đặc tả — đọc trước khi sửa mã

**(1) Giá trị `LOAIHOSO` không trùng tên thẻ gốc bên trong base64.** Ba loại lệch:

| `LOAIHOSO` | Thẻ gốc thực tế |
|---|---|
| `GIAYDIEUTRINOITRU` | `<CTGiayDieuTriNoiTru>` |
| `GIAYDIEUTRIVOSINH` | `<CTGiayDieuTriVoSinh>` |
| `GIAYSUCKHOEME` | `<CTGiaySucKhoeMe>` |

Lấy tên thẻ gốc làm khóa tra bảng — cách tự nhiên nhất — sẽ khiến ba loại này rơi vào nhánh
"loại lạ" **im lặng**. `CtdtLoaiRegistry::xacNhanTheGoc()` đối chiếu chéo cả hai tên và ném lỗi
khi lệch.

**(2) Tên trường ICD và dân tộc cố ý không đều giữa các loại. Đừng "sửa cho đều".**

| Loại | Trường ICD |
|---|---|
| CT03 | `BENHICD10_ID` / `TENBENHNICD10` (không gạch dưới) |
| CT04, CT06, CT07, giấy báo tử | `BENH_ICD10_ID` / `BENH_ICD10_TEN` |
| nội trú, vô sinh, sức khỏe mẹ | `BENH_ICD10_MA` / `BENH_ICD10_TEN` |

Tương tự: CT03 dùng `MA_DANTOC`, nội trú dùng `MA_DAN_TOC`.

**(3) Giấy chứng sinh có bốn nhóm người phân biệt bằng hậu tố.** `_NND` người đẻ · `_MTH` mẹ
thay thế (mang thai hộ) · `_CHA_MTH` cha của mẹ thay thế · `_CHA_NND` cha của người đẻ. Gán nhầm
một thẻ sang nhóm khác nghĩa là dữ liệu người này ghi vào chỗ người kia.

**Lưới an toàn:** `tests/Unit/Ctdt/CtdtToanVenTest.php` canh ba nơi khai cột (migration ↔
`truong()` ↔ `$fillable`) phải khớp **cả hai chiều**, và canh mọi thẻ mà `rutGon()`/`maChungTu()`
đọc đều nằm trong `truong()`. Vì chín khối `rutGon()` được cố ý sao chép chín lần, lưới này là
thứ duy nhất giữ chín bản không trôi khỏi nhau.

---

## 6. Một hạn chế đã biết

**Hồ sơ chỉ gồm CT04 / CT06 / CT07 sẽ không ghi đè được khi nạp lại.** Ba loại này **không có**
thẻ `MA_YTE`, nên khóa nghiệp vụ phải lùi về `Id` GUID của `THONGTINHOSO` — mà GUID đổi mỗi lần
xuất tệp. Nạp lại sẽ tạo bản ghi thứ hai.

Đây là lựa chọn có chủ đích: bịa khóa từ `MA_THE + NGAY_VAO` mang rủi ro ngược lại và nặng hơn —
hai hồ sơ khác nhau bị coi là một và mất dữ liệu im lặng. Màn danh sách (Giai đoạn 2) sẽ có nhãn
cảnh báo "hồ sơ không có mã y tế".

---

## 7. Triển khai

### Hiện tại (sau Giai đoạn 1)

1. `git pull`
2. Thêm khối `exportCtdt` vào `config/filesystems.php` (xem mục 3.3) — **không tự có**.
3. Thêm khối `chung_tu_dien_tu` vào `config/organization.php` (xem mục 3.1) — **không tự có**.
4. Tạo thư mục `D:\XML\ChungTuDienTu` và `D:\XML\ChungTuDienTu\inbox`.
5. `php artisan migrate --path=database/migrations`
6. `php artisan config:clear`

**Chưa cần thêm dịch vụ queue worker nào.** Ba hàng đợi `JobCtdt` / `JobSignCtdt` /
`JobSubmitCtdt` mới chỉ được khai trong cấu hình; chưa job nào đẩy vào chúng. Dựng worker bây giờ
chỉ tạo ra ba tiến trình chạy không.

### Khi Giai đoạn 3–4 hoàn tất

Bổ sung ba dịch vụ vào `install_service.bat` theo đúng khuôn các dịch vụ sẵn có:

```bat
%NSSM_PATH%\nssm install "QLBV JobCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobCtdt"
%NSSM_PATH%\nssm install "QLBV JobSignCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSignCtdt"
%NSSM_PATH%\nssm install "QLBV JobSubmitCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitCtdt"
```

Ký số và gửi để **hai hàng đợi riêng** là có chủ đích: ký hỏng vì lý do cục bộ (USB token bị rút,
HSM không phản hồi) còn gửi hỏng vì mạng. Gộp chung thì một lần mạng chập sẽ kéo theo ba lần ký
lại — thao tác tốn thời gian nhất trong chuỗi.

---

## 8. Kiểm thử

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng `OK (59 tests)`.

⚠️ Repo có sẵn test đỏ **không liên quan** module này: suite `Unit` cho 4 lỗi + 7 đỏ
(`NhapDanhMucUniqueTest`, `OrderCheck\CatalogLookupTest`, `BHYT\Xml3176ExportLocCoSoTest`,
`Import\GhiTheoLoTest`), suite `Feature` cho 8 lỗi + 4 đỏ (`Dashboard\*ControllerTest`,
`ExampleTest`). Chạy `php vendor/bin/phpunit` trước khi sửa gì để biết đâu là đỏ cũ.

**Lưu ý khi viết test cho module này:**

- **Không dùng `RefreshDatabase`** — `.env` dự án trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật.
  Dùng trait `Tests\Support\DungBangCtdtSqlite` (SQLite bộ nhớ).
- Laravel 5.5 **không bật `PRAGMA foreign_keys`** cho SQLite, nên khóa ngoại không được thực thi
  trong test. Đừng viết test khẳng định cascade — nó cho cảm giác an tâm giả.
- PHPUnit 6: `setUp()` **không** có `: void`. PHP 7.4: không dùng cú pháp PHP 8.

---

## 9. Tài liệu liên quan

| Tài liệu | Nội dung |
|---|---|
| [Thiết kế module (spec)](superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md) | Đặc tả đầy đủ 11 mục: tóm lược PL02, kiến trúc, lược đồ dữ liệu từng cột, các lớp, giao diện, kiểm thử, rủi ro, lộ trình 5 giai đoạn, ghi chú chuyển tiếp |
| [Kế hoạch Giai đoạn 1](superpowers/plans/2026-08-19-ctdt-giai-doan-1-nen-du-lieu.md) | 8 task TDD đã thực thi |
| [XML3176 & Order-Check](tai-lieu-tong-hop-xml3176-order-check.md) | Module tiền giám định — khuôn mẫu mà module này bám theo |
| [Ký số USB Token](Digital-Signature-USB-Token-Implementation-Plan.md) | `XMLSignService` mà Giai đoạn 4 sẽ dùng lại |
