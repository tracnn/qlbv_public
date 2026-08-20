# Module Chứng từ điện tử BHXH (PL02)

> Liên thông dữ liệu chứng từ điện tử qua dịch vụ web của cổng BHXH, theo Phụ lục 02
> ban hành kèm công văn BHXH Việt Nam 2025.
>
> Mọi `file:line` trích từ mã nguồn thực tế; khi mã thay đổi cần đối chiếu lại.
> Cập nhật: 2026-08-20 — **Giai đoạn 1 (nền dữ liệu), 2A (nền nạp), 2B (ba màn hình) và 3 (bộ kiểm lỗi) đã hoàn tất.**

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

**Đã có (Giai đoạn 1 — nền dữ liệu; 2A — nền nạp; 2B — ba màn hình; 3 — bộ kiểm lỗi):**

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
| Luồng nạp | `app/Services/Ctdt/CtdtGoiParser.php`, `CtdtMaHoSo.php`, `CtdtLuuHoSo.php`, `CtdtImporter.php` |
| Cây ngoại lệ nạp | `app/Services/Ctdt/Loi/` |
| Ba màn hình | `app/Http/Controllers/BHYT/BHYTCtdtController.php`, `resources/views/bhyt/ctdt/` |
| Suy trạng thái, bộ lọc, tab động, nhãn trường | `app/Services/Ctdt/CtdtTrangThaiGui.php`, `CtdtDanhSach.php`, `CtdtDetailTabs.php`, `CtdtNhanTruong.php` |
| Bộ kiểm lỗi | `app/Services/Ctdt/Kiem/CtdtChecker.php`, `config/ctdt.php` khóa `ma_loi` |
| Job kiểm, một hồ sơ một job | `app/Jobs/CheckCtdtJob.php` |
| 323 test đơn vị | `tests/Unit/Ctdt/` |

**Chưa có (đúng phạm vi, không phải thiếu sót):** ký số, service gửi lên cổng, nút "Ký và gửi"
(Giai đoạn 4); xuất Excel, lệnh Console quét thư mục, dashboard (Giai đoạn 5).

Vì vậy **module hiện chưa gọi mạng** — triển khai lên máy chủ ở trạng thái này không ảnh hưởng
gì tới XML3176 hay bất kỳ nghiệp vụ nào đang chạy.

⚠️ **Từ Giai đoạn 3, một cột "Lỗi chặn gửi" bằng `0` KHÔNG còn là chuyện đương nhiên.** Hồ sơ nạp
xong sẽ được đẩy vào hàng đợi `JobCtdt` để kiểm; nếu worker của hàng đợi đó không chạy thì không
hồ sơ nào được kiểm, và số lỗi sẽ đứng yên ở `0` — trông y như mọi hồ sơ đều sạch. Đây chính là
lý do có trạng thái riêng **"Chưa kiểm"**: hồ sơ chưa đi qua bộ kiểm được gắn nhãn "Chưa kiểm"
(cột "Trạng thái gửi" trên màn danh sách, và khối tóm tắt trên màn chi tiết) chứ **không** rơi vào
"Chờ gửi", nên một worker chết là chuyện nhìn thấy được ngay trên màn hình. Xem mục 8, khối
"Worker hàng đợi — BẮT BUỘC từ Giai đoạn 3".

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

## 6. Bộ mã lỗi của bộ kiểm — bảng có hiệu lực

Nguồn sự thật là `config/ctdt.php` khóa `ma_loi`; bảng dưới là bản chép của nó tại thời điểm
Giai đoạn 3 hoàn tất. Bộ kiểm là `app/Services/Ctdt/Kiem/CtdtChecker.php` (hàm thuần), job ghi
kết quả là `app/Jobs/CheckCtdtJob.php`.

| Mã | Kiểm gì | Mức |
|---|---|---|
| `CTDT001` | Trường bắt buộc rỗng | chặn |
| `CTDT002` | Trường ngày sai định dạng (độ dài nào cũng vào mã này) | chặn |
| `CTDT003` | `GIOI_TINH` ngoài giá trị cho phép | chặn |
| `CTDT004` | `LOAI_GIAYTO` ngoài giá trị cho phép | chặn |
| `CTDT005` | Trường cờ (`TEKT`, `DINH_CHI_THAI_NGHEN`, `IS_*`, `CAP_LAN_DAU`, `SINHCON_*`) ngoài `0/1` | cảnh báo |
| `CTDT006` | Ngày kết thúc sớm hơn ngày bắt đầu | chặn |
| `CTDT007` | `MACSKCB` trong chứng từ lệch với mã cơ sở của hồ sơ | chặn |
| `CTDT008` | Thiếu mã thẻ BHYT | **cảnh báo** |

Chỉ lỗi mức **chặn** mới cộng vào `ctdt_ho_so.so_loi` và mới chặn việc gửi. Lỗi mức cảnh báo vẫn
hiện đủ trên tab "Lỗi" của màn chi tiết — vì vậy badge trên tab (đếm **cả** cảnh báo) thường lớn
hơn con số "Lỗi chặn gửi" ở khối tóm tắt. Hai con số khác nhau là đúng.

### Vì sao lệch với bảng trong đặc tả

Mục 5.4 của [tệp đặc tả](superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md) còn giữ
bộ mã **cũ** của lúc thiết kế. Bảng trên mới là bảng có hiệu lực. Ba điều chỉnh, và lý do:

1. **Gộp ba mã ngày (`CTDT002`/`CTDT003`/`CTDT004` cũ) thành một `CTDT002`.** Đặc tả tách theo độ
   dài chuỗi — 8, 12, 14 ký tự — như thể mỗi độ dài là một loại lỗi khác nhau. Nhưng **cùng một
   tên thẻ có độ dài khác nhau tùy loại chứng từ**: `NGAY_VAO` của CT03 là 12 ký tự, còn
   `NGAY_VAO` của CT06 là 8. Giữ ba mã thì cùng một sai sót của người nhập lại được báo bằng ba
   mã khác nhau tùy chứng từ nó nằm trong — người sửa hồ sơ không tra cứu nổi.
2. **`MA_THE` để mức cảnh báo, không phải chặn.** PL02 có thẻ `TEKT` (trẻ em chưa có thẻ) và giá
   trị `1` của nó là **hợp lệ**: đó là hồ sơ trẻ sơ sinh chưa được cấp thẻ BHYT. Đặt "thiếu mã
   thẻ" ở mức chặn sẽ chặn nhầm **mọi hồ sơ trẻ sơ sinh** — đúng nhóm mà thẻ `TEKT` sinh ra để
   xử lý.
3. **Bỏ `CTDT010` (độ dài `username`/`password`/`macskcb`).** Đó là ràng buộc của **tham số gọi
   API**, không phải nội dung chứng từ. Bộ kiểm nhận một mảng dữ liệu chứng từ và không hề thấy
   thông tin đăng nhập; chỗ đúng để canh chúng là service gửi của Giai đoạn 4.

⚠️ **Người làm Giai đoạn 4 đọc kỹ:** trong mã hiện tại `CTDT008` là **"thiếu mã thẻ BHYT", mức
cảnh báo** — *không* phải "ngày ra sớm hơn ngày vào, mức chặn" như bảng cũ trong đặc tả. Dựng
theo bảng cũ sẽ chặn nhầm đúng nhóm trẻ sơ sinh nói trên.

---

## 7. Một hạn chế đã biết

**Hồ sơ chỉ gồm CT04 / CT06 / CT07 sẽ không ghi đè được khi nạp lại.** Ba loại này **không có**
thẻ `MA_YTE`, nên khóa nghiệp vụ phải lùi về `Id` GUID của `THONGTINHOSO` — mà GUID đổi mỗi lần
xuất tệp. Nạp lại sẽ tạo bản ghi thứ hai.

Đây là lựa chọn có chủ đích: bịa khóa từ `MA_THE + NGAY_VAO` mang rủi ro ngược lại và nặng hơn —
hai hồ sơ khác nhau bị coi là một và mất dữ liệu im lặng. Màn danh sách (Giai đoạn 2) sẽ có nhãn
cảnh báo "hồ sơ không có mã y tế".

---

## 8. Triển khai

### Hiện tại (sau Giai đoạn 3)

1. `git pull`
2. Thêm khối `exportCtdt` vào `config/filesystems.php` (xem mục 3.3) — **không tự có**.
3. Thêm khối `chung_tu_dien_tu` vào `config/organization.php` (xem mục 3.1) — **không tự có**.
4. Tạo thư mục `D:\XML\ChungTuDienTu` và `D:\XML\ChungTuDienTu\inbox`.
5. `php artisan migrate --path=database/migrations`
6. `php artisan config:clear`

**`JobCtdt` là BẮT BUỘC** từ Giai đoạn 3 — xem khối ngay dưới đây. `JobSignCtdt` và
`JobSubmitCtdt` thì chưa cần: hai hàng đợi này mới chỉ được khai trong cấu hình, để dành ký số và
gửi của Giai đoạn 4; dựng worker cho chúng bây giờ chỉ tạo ra hai tiến trình chạy không.

### Worker hàng đợi — BẮT BUỘC từ Giai đoạn 3

Bộ kiểm lỗi chạy trong hàng đợi `JobCtdt`. **Không có worker nghĩa là không hồ sơ nào được kiểm**,
và cột "Số lỗi" sẽ vĩnh viễn bằng `0` — trông y như mọi hồ sơ đều sạch.

Cài dịch vụ (đã có sẵn trong `install_service.bat`):

```bat
nssm install "QLBV JobCtdt" php.exe "<đường dẫn dự án>artisan queue:work --queue=JobCtdt"
nssm start "QLBV JobCtdt"
```

Kiểm hàng đợi có đang chạy không:

```sql
SELECT COUNT(*) FROM jobs WHERE queue = 'JobCtdt';
```

Con số này tăng dần mà không giảm nghĩa là worker chưa chạy.

### Khi Giai đoạn 4 hoàn tất

`JobCtdt` đã có sẵn trong `install_service.bat` từ Giai đoạn 3 (xem khối "Worker hàng đợi" ở
trên) — không cần thêm lại. Bổ sung hai dịch vụ ký số/gửi vào `install_service.bat` theo đúng
khuôn các dịch vụ sẵn có:

```bat
%NSSM_PATH%\nssm install "QLBV JobSignCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSignCtdt"
%NSSM_PATH%\nssm install "QLBV JobSubmitCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitCtdt"
```

Ký số và gửi để **hai hàng đợi riêng** là có chủ đích: ký hỏng vì lý do cục bộ (USB token bị rút,
HSM không phản hồi) còn gửi hỏng vì mạng. Gộp chung thì một lần mạng chập sẽ kéo theo ba lần ký
lại — thao tác tốn thời gian nhất trong chuỗi.

---

## 9. Kiểm thử

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng `OK (323 tests)`.

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

## 10. Tài liệu liên quan

| Tài liệu | Nội dung |
|---|---|
| [Thiết kế module (spec)](superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md) | Đặc tả đầy đủ 11 mục: tóm lược PL02, kiến trúc, lược đồ dữ liệu từng cột, các lớp, giao diện, kiểm thử, rủi ro, lộ trình 5 giai đoạn, ghi chú chuyển tiếp |
| [Kế hoạch Giai đoạn 1](superpowers/plans/2026-08-19-ctdt-giai-doan-1-nen-du-lieu.md) | 8 task TDD đã thực thi |
| [XML3176 & Order-Check](tai-lieu-tong-hop-xml3176-order-check.md) | Module tiền giám định — khuôn mẫu mà module này bám theo |
| [Ký số USB Token](Digital-Signature-USB-Token-Implementation-Plan.md) | `XMLSignService` mà Giai đoạn 4 sẽ dùng lại |
