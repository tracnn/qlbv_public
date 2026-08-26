# Module Chứng từ điện tử BHXH (PL02)

> Liên thông dữ liệu chứng từ điện tử qua dịch vụ web của cổng BHXH, theo Phụ lục 02
> ban hành kèm công văn BHXH Việt Nam 2025.
>
> Mọi `file:line` trích từ mã nguồn thực tế; khi mã thay đổi cần đối chiếu lại.
> Cập nhật: 2026-08-21 — **Giai đoạn 1 (nền dữ liệu), 2A (nền nạp), 2B (ba màn hình), 3 (bộ kiểm
> lỗi), 4 (ký số và gửi) đã hoàn tất; Giai đoạn 5A (lệnh Console `ctdt:import`, kể cả chế độ
> chạy liên tục) đã hoàn tất.**

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

Máy chủ cổng khai ở `organization.BHYT.base_url` — xem mục 3.2. Ba đường dẫn trên ghép với
host đó lúc chạy qua `App\Services\BHYT\CongBhxh`. Lấy token dùng lại
`App\Services\BHYTLoginService` sẵn có (`organization.BHYT.login_url`).

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

**Đã có (Giai đoạn 1 — nền dữ liệu; 2A — nền nạp; 2B — ba màn hình; 3 — bộ kiểm lỗi; 4 — ký số
và gửi):**

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
| Quyết định có gửi hay không (ba cửa chặn) | `app/Services/Ctdt/CtdtQuyetDinhGui.php` |
| Dựng phong bì gửi/ký từ dữ liệu đã lưu | `app/Services/Ctdt/CtdtPhongBi.php` |
| Gọi cổng BHXH, ghi kết quả gửi | `app/Services/Ctdt/CtdtSubmitService.php` |
| Job ký số, ghi tệp đã ký lên disk `exportCtdt` | `app/Jobs/SignCtdtJob.php` |
| Job gửi hồ sơ đã ký lên cổng BHXH | `app/Jobs/SubmitCtdtJob.php` |
| Tên ba hàng đợi, một nguồn duy nhất | `app/Services/Ctdt/CtdtHangDoi.php` |
| Lệnh Console quét thư mục + nạp + xếp hàng ký-gửi, chạy một lượt hoặc liên tục | `app/Console/Commands/CtdtImport.php` |
| 519 test đơn vị | `tests/Unit/Ctdt/` |

**Chưa có (đúng phạm vi, không phải thiếu sót):** xuất Excel, dashboard (Giai đoạn 5B trở đi).

**Từ Giai đoạn 4, module đã gọi mạng thật** — `SubmitCtdtJob` gửi hồ sơ đã ký lên cổng BHXH.
Sự an toàn khi triển khai không nằm ở chỗ module chưa biết gọi mạng, mà ở chỗ
`organization.chung_tu_dien_tu.submit_enabled` **mặc định TẮT**: cho tới khi cấu hình này được
bật tay ở từng cơ sở, không một lần gửi nào diễn ra, dù ký số vẫn dùng được để kiểm tra bằng mắt.

⚠️ **Từ Giai đoạn 3, một cột "Số lỗi" bằng `0` KHÔNG còn là chuyện đương nhiên.** Hồ sơ nạp
xong sẽ được đẩy vào hàng đợi `JobCtdt` để kiểm; nếu worker của hàng đợi đó không chạy thì không
hồ sơ nào được kiểm, và số lỗi sẽ đứng yên ở `0` — trông y như mọi hồ sơ đều sạch. Đây chính là
lý do có trạng thái riêng **"Chưa kiểm"**: hồ sơ chưa đi qua bộ kiểm được gắn nhãn "Chưa kiểm"
(cột "Trạng thái gửi" trên màn danh sách, và khối tóm tắt trên màn chi tiết) chứ **không** rơi vào
"Chờ gửi", nên một worker chết là chuyện nhìn thấy được ngay trên màn hình. Xem mục 9, khối
"Worker hàng đợi — BẮT BUỘC cả ba".

Luật "đã kiểm và sạch chưa" nằm ở **một nơi duy nhất**: `CtdtQuyetDinhGui::nenKy()`. Cả
`CtdtQuyetDinhGui::nen()`, `CtdtTrangThaiGui::cua()` lẫn `SignCtdtJob::handle()` đều hỏi hàm đó
thay vì tự viết lại. Trước đây ba nơi chép tay cùng một luật; nới lỏng một bản mà quên hai bản
kia thì triệu chứng là một hồ sơ hiện "Chưa ký số" vĩnh viễn trên màn danh sách trong khi job ký
im lặng bỏ qua nó. `CtdtHangDoiTest` khoá ba nơi lại bằng một ma trận `(checked_at, so_loi)`.

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
| `queue_name` | `JobCtdt` | Hàng đợi kiểm lỗi |
| `sign_queue_name` | `JobSignCtdt` | Hàng đợi ký số |
| `submit_queue_name` | `JobSubmitCtdt` | Hàng đợi gửi cổng |

### Hai đường dẫn hệ tệp nằm ở `config/filesystems.php`, không ở `organization.php`

| Disk | Biến `.env` | Mặc định | Dùng làm gì |
|---|---|---|---|
| `importCtdt` | `CTDT_IMPORT_PATH` | `D:\XML\ChungTuDienTu\inbox` | Thư mục lệnh `ctdt:import` quét |
| `exportCtdt` | `CTDT_EXPORT_PATH` | `D:\XML\ChungTuDienTu` | Nơi ghi tệp đã ký |

Đường dẫn hệ tệp là đường dẫn hệ tệp — để hai cái cạnh nhau thì người triển khai cho đơn vị
mới chỉ phải nhìn **một chỗ**. Cả hai đọc được từ `.env`, nên đơn vị không có ổ `D:` chỉ cần
đặt hai biến, không phải sửa một tệp đã track.

⚠️ **Đơn vị mới không có gì liên quan chứng từ điện tử vẫn chạy bình thường.** Thiếu hẳn khối
`organization.chung_tu_dien_tu` thì mọi cờ lùi về **TẮT** (không phải bật), tên ba hàng đợi lùi
về hằng số trong `CtdtHangDoi`, và hai đường dẫn trên vẫn có giá trị mặc định. Không chỗ nào ném
lúc khởi động. `CtdtCauHinhTest::don_vi_moi_khong_co_khoi_chung_tu_dien_tu_thi_KHONG_gay_loi`
canh điều đó.

Đọc trong mã bằng `config('organization.chung_tu_dien_tu.submit_enabled')` — cùng cách
`SubmitXml3176Job` đọc `config('organization.BHYT.submit_xml_3176_enabled')`.

⚠️ **Ba khoá tên hàng đợi đừng đọc thẳng bằng `config()`** — dùng `CtdtHangDoi::kiem()`,
`::ky()`, `::gui()`. Hai lý do. Thứ nhất, tên mặc định phải khớp `install_service.bat`, và
`CtdtCauHinhTest` khoá hai bên lại với nhau qua chính ba hằng số trong `CtdtHangDoi`. Thứ hai,
`config($khoá, $mặc_định)` **chỉ** lùi về mặc định khi khoá **không tồn tại**; khoá tồn tại
nhưng giá trị `null` hay chuỗi rỗng — đúng cảnh một người sao chép khối cấu hình rồi xoá giá
trị — vẫn trả về chính giá trị rỗng đó, và `->onQueue(null)` đẩy job vào hàng đợi `default`
mà không worker nào nghe. `CtdtHangDoi` xử cả ba kiểu để trống, kể cả chuỗi toàn khoảng trắng.

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
| `dich_vu` | 3 mục — `the_goc`, `loai_hs`, `duong_dan` của ba dịch vụ |
| `ma_ket_qua` | 5 mã — `200` · `205` · `401` · `500` · `1001` |
| `ma_ket_qua_token` | 5 mã của dịch vụ lấy token (mục I của PL02) |

`duong_dan` là **đường dẫn tương đối** (`/api/chungtugw/GuiHoSoChungTu2025`), không phải URL
đầy đủ: đường dẫn là hằng số giao thức do BHXH quy định nên đi theo kho mã, còn host đổi theo
môi trường nên nằm ở `organization.BHYT.base_url`. `CtdtSubmitService` ghép hai phần lúc gửi.
Khoá `token_url` cũ đã bỏ — nó là mã chết, trùng y hệt `organization.BHYT.login_url` mà
`BHYTLoginService` mới thật sự dùng.

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
| CT03 | `BENHICD10_ID` / `TENBENHICD10` (không gạch dưới) |
| CT04, CT06, CT07, giấy báo tử | `BENH_ICD10_ID` / `BENH_ICD10_TEN` |
| nội trú | `BENH_ICD10_ID` / `BENH_ICD10_TEN` |
| vô sinh, sức khỏe mẹ | `BENH_ICD10_MA` / `BENH_ICD10_TEN` — **chưa kiểm chứng**, xem cảnh báo dưới bảng |

Tương tự: CT03 dùng `MA_DANTOC`, nội trú dùng `MA_DAN_TOC`.

**(3) Giấy chứng sinh có bốn nhóm người phân biệt bằng hậu tố.** `_NND` người đẻ · `_MTH`
mang thai hộ · `_CHA_MTH` cha của mang thai hộ · `_CHA_NND` cha của người đẻ. Gán nhầm
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

## 8. Ký số và gửi

Người vận hành mở màn chi tiết một hồ sơ và bấm **Ký và gửi**. Hệ thống xếp hai công việc nối
tiếp: `SignCtdtJob` (hàng đợi `JobSignCtdt`) rồi `SubmitCtdtJob` (hàng đợi `JobSubmitCtdt`).

**Bốn cửa chặn, theo đúng thứ tự này:**

| Điều kiện | Nút báo gì | Vì sao chặn |
|---|---|---|
| `submit_enabled = false` | "Chức năng gửi đang tắt" | Không lần gửi nào diễn ra, nên không ghi lỗi — ghi là bịa |
| `checked_at` rỗng | "Hồ sơ chưa kiểm" | `so_loi = 0` của hồ sơ chưa kiểm không có nghĩa là sạch |
| `so_loi > 0` | "Hồ sơ còn lỗi chặn gửi" | Cổng cũng sẽ từ chối; chặn tại chỗ cho thông báo rõ hơn |
| `sign_enabled = false` **và** hồ sơ chưa ký (`is_signed = false`) | "Chức năng ký số đang tắt trong cấu hình, và hồ sơ này chưa ký" | Chặn ở controller (không phải `CtdtQuyetDinhGui`) — hồ sơ **đã** ký từ trước vẫn gửi lại được dù chức năng ký đang tắt |

### Trạng thái gửi trên màn danh sách — chín nhãn, một thứ tự cố định

`CtdtTrangThaiGui::cua()` là hàm thuần suy một nhãn duy nhất cho cột "Trạng thái gửi", để người
vận hành không phải mở từng hồ sơ mới biết nó kẹt ở đâu. Hai nhãn mới bổ sung là **"Ký số thất
bại"** (`KY_HONG`) và **"Gửi thất bại"** (`GUI_HONG`), mỗi nhãn tách ra khỏi một nhãn cũ đang gộp
chung hai chuyện khác nhau:

- **"Ký số thất bại" tách khỏi "Chưa ký số".** Cả hai đều có `is_signed = false`, nhưng "chưa ký"
  là hồ sơ chưa ai bấm, còn "ký thất bại" là **đã bấm và hỏng** (USB token bị rút, HSM không phản
  hồi) — lý do nằm ở cột `signed_error`. Gộp chung thì người vận hành nhìn thấy "Chưa ký số" lại
  đi tìm nút ký đã bấm rồi, không biết phải đi sửa nguyên nhân cục bộ ở máy ký.
- **"Gửi thất bại" tách khỏi "Chờ gửi".** Hồ sơ đã ký, cổng chưa từng trả lời (`ma_ket_qua` rỗng),
  nhưng có `submit_error` là hồ sơ **đã thử gửi và hỏng trước khi tới cổng** (mạng chập, timeout) —
  khác với "Chờ gửi" là hồ sơ còn chưa từng thử.

**Thứ tự kiểm tra trong `cua()` cố ý, không phải ngẫu nhiên** (trích chú thích trong mã):

- `KY_HONG` được kiểm **trước** `CHUA_KY`: cả hai đều `is_signed = false`, nhưng nhánh nào **cụ
  thể hơn phải thắng** — không tách thì lý do thật chỉ nằm trong `laravel.log`, còn màn hình báo
  chung chung "Chưa ký số".
- `GUI_HONG` được kiểm **sau** nhánh `ma_ket_qua` — cổng **đã** trả lời thì kết quả đó là **sự
  thật cuối cùng**, kể cả khi `submit_error` cũ còn sót lại từ một lần gửi hỏng trước đó — nhưng
  **trước** `GUI_TAT`: cấu hình gửi có thể vừa bị tắt **sau** một lần gửi hỏng, và lúc đó hiện
  "đang tắt" là **giấu mất lỗi thật**, đẩy người vận hành đi bật lại cấu hình thay vì đọc lý do
  thật trong `submit_error`.

Bộ lọc SQL trong `CtdtDanhSach::locTrangThai()` soi gương đúng thứ tự này, để lọc theo trạng thái
trên màn danh sách ra đúng tập hồ sơ mà `cua()` sẽ gán nhãn.

### Chống bấm trùng — khóa cache theo từng mã hồ sơ

PL02 **không có mã giao dịch phía client** để cổng BHXH tự khử trùng; một hồ sơ bị bấm "Ký và
gửi" nhiều lần liên tiếp (tay nhanh, double-click, mạng chậm khiến người dùng bấm lại) sinh ra
từng đó chuỗi `SignCtdtJob → SubmitCtdtJob` độc lập, và **mỗi chuỗi là một lần POST thật lên
cổng**. Đây không phải giả định: trên CSDL đã ghi nhận một hồ sơ bị bấm **ba lần**, sinh ba chuỗi
job.

`BHYTCtdtController::KHOA_XU_LY` là một khóa cache theo tiền tố `ctdt:dang-xu-ly:<ma_ho_so>`, hết
hạn sau **10 phút** (`KHOA_XU_LY_PHUT`). Khóa được đặt bằng `Cache::add()` — trả `false` nếu khóa
đã tồn tại, dùng chính kết quả đó làm phép thử "đã có người bấm chưa". Hai điểm cố ý:

- **Đặt khóa NGAY TRƯỚC dispatch, sau mọi nhánh từ chối** (`submit_enabled`, `checked_at`,
  `so_loi`, `sign_enabled`): một lần bấm bị từ chối không làm gì cả, giữ khóa ở đó sẽ khóa người
  dùng ra ngoài 10 phút mà không được gì.
- **Khóa được nhả ở mọi đường ra**: cuối `SubmitCtdtJob::handle()` (dòng thành công) và `failed()`
  của cả `SignCtdtJob` lẫn `SubmitCtdtJob` — không nhả ở một nhánh mà quên nhánh lỗi thì một lần
  ký/gửi hỏng sẽ khóa hồ sơ đó 10 phút dù chẳng có gì đang chạy.

⚠️ **`Cache::add()` với driver `file`/`array` không phải mutex thật.** Nó lùi về `get()` rồi
`put()` bên trong, có cửa sổ TOCTOU (time-of-check to time-of-use) giữa hai bước đó. Đủ tốt cho
ca dùng thật — hai lần bấm của cùng một người thật hiếm khi rơi đúng vào khe hở micro-giây đó —
nhưng **không phải khóa cứng** kiểu mutex hệ điều hành hay khóa CSDL. Đừng dựa vào nó cho một ca
cần đúng-một-lần tuyệt đối.

**Tệp gửi lên cổng là tệp ĐÃ KÝ trên disk `exportCtdt`**, đường dẫn
`da-ky/<dịch vụ>/<tên đã làm sạch>-<id>.xml`, không phải phong bì dựng lại lúc gửi. Dựng lại lúc
gửi là gửi một gói không có chữ ký.

Tên tệp **không** dùng thẳng `ma_ho_so`: mọi ký tự ngoài `A-Za-z0-9_-` (kể cả `#`, `/`, `.`) bị thay
bằng `_`, và hậu tố `-<id>` (khóa chính) được nối thêm để hai hồ sơ có `ma_ho_so` làm sạch trùng
nhau (vd. `YT#1` và `YT/1` đều ra `YT_1`) không ghi đè lên tệp của nhau. Xem
`SignCtdtJob::duongDan()`.

**Phong bì được dựng lại từ dữ liệu đã lưu, không phải tệp gốc.** Tệp XML người dùng tải lên không
được giữ trên đĩa (cột `duong_dan_goc` chỉ ghi tên tệp), nên chữ ký `CHUKYDONVI` của bên gửi đã mất
từ lúc nạp. Mỗi hồ sơ được đóng thành một gói riêng với `SOLUONGHOSO = 1`.

**Gửi lại được.** Nút đổi thành "Ký và gửi lại" khi hồ sơ đã có `MaGD`. `MaGD` cũ được nối vào cột
`lich_su_gui` trước khi ghi đè — đó là dấu vết đối soát với BHXH, mất nó là tranh chấp không có gì để tra.

**Ba worker phải chạy đủ:**

```sql
SELECT queue, COUNT(*) FROM jobs GROUP BY queue;
```

`JobCtdt`, `JobSignCtdt`, `JobSubmitCtdt` — hàng nào tăng dần mà không giảm nghĩa là worker đó chưa chạy.

**`submit_enabled` mặc định TẮT.** Cổng thật của BHXH nhận là nhận thật. Bật sau khi đã chạy thử và
đối chiếu vài hồ sơ bằng tay.

---

## 9. Triển khai

### Hiện tại (sau Giai đoạn 4)

1. `git pull`
2. Thêm khối `exportCtdt` vào `config/filesystems.php` (xem mục 3.3) — **không tự có**.
3. Thêm khối `chung_tu_dien_tu` vào `config/organization.php` (xem mục 3.1) — **không tự có**.
4. Tạo thư mục `D:\XML\ChungTuDienTu` và `D:\XML\ChungTuDienTu\inbox`.
5. `php artisan migrate --path=database/migrations`
6. `php artisan config:clear`

**Ba worker `JobCtdt`, `JobSignCtdt`, `JobSubmitCtdt` đều BẮT BUỘC** — xem khối ngay dưới đây.
Cả ba đều đã được `install_service.bat` cài và khởi động sẵn, không cần thêm tay.

### Worker hàng đợi — BẮT BUỘC cả ba

`JobCtdt` chạy bộ kiểm lỗi; `JobSignCtdt` chạy ký số; `JobSubmitCtdt` chạy gửi lên cổng BHXH.
**Thiếu worker nào thì mọi hồ sơ đứng khựng ở bước đó** — ví dụ `JobCtdt` chết thì không hồ sơ
nào được kiểm và cột "Số lỗi" vĩnh viễn bằng `0`, trông y như mọi hồ sơ đều sạch.

Cả ba dịch vụ đã có sẵn trong `install_service.bat`, được cài và khởi động cùng lúc với các
dịch vụ khác của hệ thống — không cần cài tay từng dịch vụ.

Kiểm cả ba hàng đợi có đang chạy không:

```sql
SELECT queue, COUNT(*) FROM jobs GROUP BY queue;
```

Hàng nào tăng dần mà không giảm nghĩa là worker của hàng đợi đó chưa chạy.

Ký số và gửi để **hai hàng đợi riêng** là có chủ đích: ký hỏng vì lý do cục bộ (USB token bị rút,
HSM không phản hồi) còn gửi hỏng vì mạng. Gộp chung thì một lần mạng chập sẽ kéo theo ba lần ký
lại — thao tác tốn thời gian nhất trong chuỗi.

### `ctdt:import --lien-tuc` — chạy tự động không cần người trực

```bash
php artisan ctdt:import --lien-tuc
```

Mặc định `ctdt:import` chạy **một lượt rồi thoát** (dùng cho cron / chạy tay). Với `--lien-tuc`,
lệnh lặp lại việc quét trong một tiến trình nssm sống lâu, cùng khuôn với `xml3176import:day` —
nhưng lệnh này **POST thật lên cổng BHXH** chứ không chỉ ghi tệp, nên có thêm mấy cái phanh mà
`xml3176import:day` không cần.

Mỗi vòng làm hai việc: **quét** thư mục `filesystems.disks.importCtdt.root`, nạp mọi tệp
`.xml` **ngay trong thư mục gốc** (không quét đệ quy), chuyển tệp đã xử lý sang `da-nap/` và tệp
hỏng sang `loi/`; rồi **nhặt** mọi hồ sơ đã kiểm, sạch, chưa có `ma_ket_qua` **và cổng chưa từng
được gọi cho nó** mà xếp hàng ký số và gửi.

### ⚠️ Lệnh nền CHỈ gửi lần đầu — gửi lại là việc của người

Đây là một **ranh giới ngữ nghĩa**, không phải chi tiết cài đặt:

> Lệnh chạy nền chỉ tự động gửi hồ sơ mà **cổng BHXH chưa từng được gọi** cho nó. Đã gọi một lần
> rồi mà chưa có kết quả rõ ràng thì phải để **người** quyết định gửi lại — bằng nút "Ký và gửi"
> trên màn chi tiết, có mắt người đọc log và đối soát với cổng.

Vì sao phải cứng như vậy: cổng có thể **đã nhận gói** rồi mạng mới chập lúc đọc phản hồi.
`CtdtSubmitService::gui()` ném, `SubmitCtdtJob` cố ý không bắt (để hàng đợi thử lại), hết `tries`
thì `failed()` ghi `submit_error` và nhả khoá — nhưng `ma_ket_qua` **vẫn NULL**. Nếu lệnh nền cứ
thấy `ma_ket_qua` trống là gửi, nó sẽ POST lại đúng hồ sơ đó **mỗi vòng, mãi mãi**. Không phanh
nào chặn được: `DUNG-GUI` / `import_tu_dong_gui` / `submit_enabled` đều là công tắc **toàn cục**;
`--gioi-han` giới hạn số **tệp** chứ không giới hạn số lần POST; khoá lượt chặn hai *tiến trình*
chứ không chặn hai *lần gửi*. Body PL02 không mang mã giao dịch phía client nên cổng cũng không
khử trùng lặp giúp được.

Truy vấn nhặt vì thế loại hai nhóm — **hai lớp, mỗi lớp bắt một thời điểm khác nhau của cùng câu
chuyện**:

| Điều kiện loại | Bắt lúc nào |
|---|---|
| `submit_error` khác rỗng | Job đã kết thúc và **kịp ghi** — `failed()`, hoặc các nhánh `ghiLoi()` như *"Hồ sơ chưa ký số"* |
| Đã có ít nhất một dòng trong `ctdt_lich_su_gui` | Cổng **đã trả lời** một lần (kể cả từ chối) — dòng nhật ký được ghi ngay trong `ghiKetQua()`, trước cả khi ai kịp đọc hồ sơ |
| `signed_error` khác rỗng | **Ký hỏng.** Trước đây hồ sơ ký hỏng cũng kẹt y hệt: `SubmitCtdtJob` ghi `submit_error` *"chưa ký số"* mà `ma_ket_qua` vẫn NULL. Một đêm rút nhầm USB token = hàng chục nghìn job rác |

"Rỗng" ở đây theo đúng quy ước chung của module (xem `CtdtDanhSach.php`): `NULL` **hoặc** chuỗi
rỗng.

Hệ quả vận hành: **hồ sơ gửi hỏng sẽ nằm lại, lệnh nền không tự động thử lại nữa.** Người trực
phải mở màn danh sách, lọc trạng thái *"Gửi thất bại"*, đọc `submit_error`, đối soát với cổng
rồi mới bấm gửi lại. Đó là chủ ý — không phải thiếu sót.

### Vì sao bước nhặt đi bằng truy vấn, không theo "vừa nạp"

Hồ sơ vừa nạp gần như **luôn** ở trạng thái *chưa kiểm* — bộ kiểm còn nằm trong hàng đợi
`JobCtdt`. Nếu lệnh chỉ xếp hàng cho những mã nó vừa nạp thì gần như lượt nào cũng trượt, và hồ
sơ phải chờ tới lượt sau mới được nhặt. Truy vấn thẳng thì mỗi vòng vét đúng những hồ sơ *vừa
mới* đủ điều kiện — kể cả hồ sơ người ta sửa tay trên màn hình rồi cho kiểm lại.

### Sáu cái phanh

| Phanh | Tác dụng |
|---|---|
| **Tệp `DUNG-GUI`** | Đặt một tệp rỗng tên `DUNG-GUI` trong thư mục inbox là **dừng ký và gửi ngay vòng sau**, vẫn tiếp tục nạp và kiểm. Xoá tệp đi là chạy lại. Đây là phanh tay duy nhất có tác dụng **không cần khởi động lại dịch vụ** — xem khối cảnh báo ngay dưới. |
| `import_tu_dong_gui` | **Cổng riêng, mặc định TẮT.** `submit_enabled` cho phép *người* bấm nút gửi; khoá này cho phép *máy* gửi khi không ai nhìn. Bật cái thứ nhất không kéo theo cái thứ hai. |
| `submit_enabled` | **Vẫn thắng ở chiều TẮT.** Đây là công tắc "gửi thật", không có đường nào vòng qua nó: `import_tu_dong_gui = true` mà `submit_enabled = false` thì lệnh nền **không xếp hàng gì cả**, kèm cảnh báo đọc được. Nếu không hỏi khoá này, mỗi vòng sẽ xếp tới 200 chuỗi job mà `SubmitCtdtJob` từ chối *không ghi gì*, nên vòng sau nhặt lại y nguyên — ngập hàng đợi, và 200 hồ sơ kẹt ở đầu `orderBy('imported_at')` chặn vĩnh viễn mọi hồ sơ mới. |
| `--gioi-han` | Trần số **tệp** nạp mỗi vòng. Không truyền thì lấy cấu hình `organization.chung_tu_dien_tu.import_gioi_han`; cấu hình đó cũng trống mới lùi về `200`. Một thư mục đổ nhầm 3000 tệp không thành 3000 lần POST trong một vòng. Vượt trần thì lệnh **báo to** rồi cắt, không cắt im lặng. ⚠️ Nó giới hạn số **tệp**, KHÔNG giới hạn số lần POST — chống POST lặp là việc của hai lớp ở mục *"Lệnh nền CHỈ gửi lần đầu"* phía trên. |
| `--so-vong=1000` | Tiến trình **tự thoát** sau bấy nhiêu vòng để nssm dựng lại bản sạch. |
| `--dry-run` / `--khong-ky` / `--khong-gui` | `--dry-run` chỉ liệt kê, không đụng gì. `--khong-ky` dừng trước bước ký. `--khong-gui` dừng **cả chuỗi ký-gửi** — xem ngay dưới. `--dry-run` **không** kết hợp được với `--lien-tuc` — lệnh thoát mã khác 0 kèm thông điệp từ chối, không chạy vòng lặp nào. |

#### ⚠️ Chưa được phép gửi thì lệnh KHÔNG ký hồ sơ nào

`--khong-gui`, `import_tu_dong_gui = false`, `submit_enabled = false` và tệp `DUNG-GUI` đều dừng
**cả bước ký**, không phải chỉ bước gửi. Cụ thể, với **cấu hình mặc định**
(`import_tu_dong_gui = false`), lệnh **nạp và kiểm** hồ sơ nhưng **không ký và không gửi** hồ sơ
nào cả.

Đừng đọc bảng phanh ở trên mà tưởng hồ sơ được ký sẵn để chỉ còn bấm nút gửi — **không phải
vậy.** Khi bấm "Ký và gửi" trên màn chi tiết, hồ sơ mới được ký.

Vì sao: `CtdtXepHangKyGui::xep()` xếp nguyên chuỗi `SignCtdtJob → SubmitCtdtJob` như **một
khối** — không tách được ở tầng lệnh. Xếp chuỗi rồi trông chờ `SubmitCtdtJob` tự từ chối là dựa
vào một phép từ chối *không ghi gì cả*, nghĩa là hồ sơ bị nhặt lại và xếp lại mỗi vòng. Đây cũng
là hành vi **an toàn hơn**: không ký sẵn một đống hồ sơ mà không ai định gửi.

⚠️ **Sửa `import_tu_dong_gui` KHÔNG dừng được tiến trình đang chạy.** Tiến trình sống lâu giữ
cấu hình trong bộ nhớ; khoá đó chỉ được đọc lại khi dịch vụ khởi động lại. Muốn dừng ngay
thì **tạo tệp `DUNG-GUI`** trong thư mục inbox:

```bat
type nul > D:\XML\ChungTuDienTu\inbox\DUNG-GUI
```

Hồ sơ vẫn được nạp và kiểm bình thường — chỉ bước ký và gửi bị chặn.

### Khoá lượt

Một khoá cache (`ctdt:import:dang-chay`) chặn hai tiến trình chạy chồng lên nhau. Khoá được đặt
**MỘT LẦN** trước vòng lặp và mở **MỘT LẦN** sau khi vòng lặp kết thúc — không đặt/mở trong từng
vòng, nếu không vòng thứ hai sẽ tự thấy khoá của chính mình và bỏ qua vĩnh viễn. Thời hạn khoá
hiện là **1440 phút (24 giờ)** — đủ dư địa cho `--so-vong=1000 × --nghi=5 giây` (~83 phút tối
thiểu, chưa tính thời gian quét mỗi vòng), nhưng đổi lại cửa sổ "khoá mồ côi" sau một lần bị kill
cứng cũng dài tới 24 giờ.

Nếu tiến trình bị kill cứng (`finally` không kịp mở khoá), lượt sau sẽ bỏ qua với thông báo *"Mot
luot ctdt:import khac dang chay"* cho tới khi hết hạn khoá. Gỡ ngay bằng lệnh cấp cứu:

```bash
php artisan ctdt:import --go-khoa
```

`--go-khoa` gỡ khoá **VÔ ĐIỀU KIỆN** rồi thoát ngay — không quét, không nạp, không xếp hàng gì
cả. **CHỈ dùng khi đã tự xác nhận** (bằng `tasklist /FI "IMAGENAME eq php.exe" /V` tìm dòng lệnh
có `ctdt:import --lien-tuc`, hoặc xem dịch vụ nssm "QLBV CtdtImport" còn sống không) là **KHÔNG
còn** tiến trình `ctdt:import` nào đang chạy. Lệnh không tự kiểm điều đó — nếu gỡ nhầm lúc một
tiến trình `--lien-tuc` thật sự còn sống, tiến trình đó không hay biết gì (nó không đọc lại
khoá), và một lệnh `ctdt:import` khác gọi ngay sau sẽ nhặt khoá thành công rồi quét **cùng thư
mục** — hai tiến trình cùng nạp một tệp, và vì đây là lệnh POST thật lên cổng BHXH, hậu quả là
**nạp trùng rồi gửi trùng hồ sơ lên cổng**.

### Mã thoát khác 0 — dấu hiệu sớm của nạp trùng

`ctdt:import` trả về mã thoát khác 0 khi có tệp **đã xử lý xong** (nạp thành công vào CSDL, hoặc
đã chuyển sang `loi/`) nhưng **không dời được** khỏi thư mục gốc (quyền ghi, tệp bị khoá bởi
chương trình khác, ổ mạng chập). Tệp đó vẫn nằm ở thư mục gốc, nên vòng quét sau sẽ **nhặt lại
đúng nó** — với hồ sơ đã nạp thành công, đó là nạp trùng, rồi bị ký/gửi lần nữa. Ở chế độ
`--lien-tuc`, mã thoát cuối cùng phản ánh **có từng vòng nào** gặp lỗi này trong suốt cả tiến
trình, không chỉ vòng cuối. Người trực thấy mã thoát khác 0 (hoặc dòng log tổng kết nhắc "tep
khong doi duoc") phải đọc log để biết tệp nào, rồi kiểm quyền ghi / tệp có bị khoá không trước
khi khởi động lại dịch vụ.

Chạy thử ngắn, an toàn (không ký, thư mục tạm, ba vòng):

```bash
php artisan ctdt:import --lien-tuc --so-vong=3 --nghi=2 --khong-ky --duong-dan=storage/app/ctdt-thu
```

### Cài dịch vụ trên máy chủ Windows

```bat
%NSSM_PATH%\nssm install "QLBV CtdtImport" %PHP_PATH% "%LARAVEL_PATH%artisan ctdt:import --lien-tuc"
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppDirectory %LARAVEL_PATH%
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppExit Default Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppRestartDelay 10000
%NSSM_PATH%\nssm start "QLBV CtdtImport"
```

`AppExit Default Restart` là phần bắt buộc: lệnh **cố ý thoát** sau `--so-vong` vòng, và nssm
phải dựng lại nó. `AppRestartDelay 10000` chỉ là 10 giây nghỉ giữa hai tiến trình — nhịp thật do
`--nghi` quyết định.

⚠️ **Ba worker hàng đợi vẫn BẮT BUỘC** (xem mục ngay phía trên). Lệnh này chỉ *xếp hàng*; không
có worker thì không gì chạy cả. Chính ba worker đó — chứ không phải vòng lặp của lệnh này — mới
là thứ gửi hồ sơ lên cổng, y như `JobSubmitXml3176` bên XML3176.

---

## 10. Kiểm thử

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng `OK (518 tests)` — **trừ một test đỏ CÓ CHỦ ĐÍCH trên máy đã chạy thật**, xem ngay dưới.

### `MA_YTE` KHÔNG bắt buộc — đừng thêm lại

Công văn **2076/BHXH-CNTT, Phụ lục 02, mục 3.4** (bảng trường của CT03) để **trống** cột
"Bắt buộc" cho `MA_YTE`, và diễn giải ghi rõ:

> *"Mã y tế định danh chứng từ của cskcb, **để trống để hệ thống BHXH tự sinh** (chỉ nên sử
> dụng 1 cách)"*

Giai đoạn 3 từng bắt buộc nó. Đối chiếu dữ liệu thật cho thấy điều đó **chặn 97% hồ sơ**:
1049/1050 CT03 và 923/923 GIAYDIEUTRINOITRU đều có thẻ `MA_YTE` nhưng giá trị rỗng — phần mềm
sinh XML làm đúng đặc tả, quy tắc của ta sai. Lần gửi thật đầu tiên xác nhận: `MaGD` cổng trả
về là `HS_CHUNGTU01929_<GUID>`, đúng là mã BHXH tự sinh.

Sau khi gỡ và chạy lại bộ kiểm trên 1074 hồ sơ: số hồ sơ bị chặn từ **1049 xuống 14**.

Cũng **không** hạ xuống mức cảnh báo: để trống là một trong hai cách dùng hợp lệ, nên cảnh báo
trên gần như mọi hồ sơ chỉ làm người vận hành học cách bỏ qua cả cột số lỗi.

### `MA_BHXH` — bắt buộc ở năm loại, cảnh báo ở ba loại

Công văn 2076 đánh dấu `x` cho `MA_BHXH` ở **mọi loại nó phủ**: CT03, CT04, CT06, CT07, và
`MA_BHXH_NND` ở CT05 (giấy chứng sinh). `GIAYDIEUTRINOITRU` không nằm trong công văn đó, nhưng
**923/923 chứng từ thật đều đã có giá trị** — đủ căn cứ để chặn.

Ba loại còn lại — `GIAYDIEUTRIVOSINH`, `GIAYSUCKHOEME`, `GIAYBAOTU` — chỉ **cảnh báo**: công văn
không phủ, CSDL chưa có chứng từ nào để đối chiếu, và riêng giấy báo tử còn một khả năng thật:
**người qua đời có thể không có mã số BHXH**. Nâng lên mức chặn khi dữ liệu thật xác nhận, đừng
đoán mò. Đây đúng là việc mà tầng khuyến nghị được giữ lại để làm.

**Đo trước khi siết:** 0/3047 chứng từ thật thiếu `ma_bhxh`, nên thay đổi này không chặn lại hồ sơ
nào. Luôn đo trước khi thêm một trường bắt buộc — bài học từ `MA_YTE`.

### `MA_THE` không sinh lỗi, kể cả cảnh báo

Rất nhiều bệnh nhân không có thẻ BHYT: tự trả, thẻ hết hạn, trẻ chưa được cấp thẻ
(`TEKT = 1`). Công văn 2076 cũng không đánh dấu `MA_THE` bắt buộc ở loại nào.

Trước đây đây là cảnh báo. Nhưng **cảnh báo trên một tình huống bình thường thì không phải
cảnh báo** — nó là tiếng ồn, và nó đẩy người vận hành tới chỗ bỏ qua cả cột số lỗi. Cùng một
lý lẽ đã dùng khi gỡ `MA_YTE`.

**Tầng khuyến nghị vẫn được giữ dù đang trống.** Bước bổ sung các trường còn thiếu so với công
văn 2076 (CT04 thiếu 11 trường, CT06 thiếu 7, CT07 thiếu 8) nên **cảnh báo trước rồi mới chặn** —
siết thẳng lên mức chặn sẽ đồng loạt khoá lại những hồ sơ đang gửi được. Có một test canh việc
`CtdtChecker` còn hỏi tầng đó, để nó không chết im lặng.

### Bổ sung trường bắt buộc theo công văn 2076 — đo trước khi siết

Công văn 2076/BHXH-CNTT, PL02 mục 3.2–3.6 đánh dấu bắt buộc nhiều trường hơn bảng ban đầu của
Giai đoạn 3. `App\Services\Ctdt\Kiem\CtdtTruongBatBuoc` thêm chúng theo đúng một nguyên tắc:
**đo trên dữ liệu thật trước khi thêm vào mức chặn** — bài học từ `MA_YTE` (mục trên): thêm mà
không đo từng chặn 97% hồ sơ trong nhiều ngày.

**Mức chặn (`BAT_BUOC`) — chỉ thêm trường đo được 0% hồ sơ thật thiếu:**

| Loại | Trường thêm | Số lượng |
|---|---|---|
| CT03 | `MA_KHOA`, `GIOI_TINH`, `DIA_CHI` | 3 |
| CT04 | `GIOI_TINH`, `DIA_CHI`, `CHAN_DOAN_VAO`, `CHAN_DOAN_RA`, `QT_BENHLY`, `TOMTAT_KQ`, `TT_RAVIEN`, `NGAY_CT` | 8 |
| CT07 | `SO_KCB`, `GIOI_TINH`, `DON_VI`, `CHANDOAN_DIEUTRI`, `MA_CCHN`, `TEN_NGUOI_HANH_NGHE`, `TEKT` | 7 |

**Mức cảnh báo (`KHUYEN_NGHI`) — trường công văn yêu cầu nhưng dữ liệu thật chưa sẵn sàng chặn:**

| Loại | Trường thêm | Vì sao chưa chặn |
|---|---|---|
| CT04 | `MA_DANTOC`, `PP_DIEUTRI` | Đo được còn thiếu: `MA_DANTOC` rỗng 6/1050, `PP_DIEUTRI` rỗng 79/1050 |
| CT06 | `SO_KCB`, `TEN_DVI`, `CHAN_DOAN`, `TEN_BS`, `MA_BS`, `NGAY_CT` | CSDL chưa có chứng từ CT06 nào để đối chiếu — không có gì để đo |

**Kết quả đo được sau khi chạy lại bộ kiểm trên 1074 hồ sơ thật (2026-08-20):** 0 hồ sơ bị chặn,
85 hồ sơ có cảnh báo (79 `PP_DIEUTRI` + 6 `MA_DANTOC`, đúng khớp số đo lúc siết mức chặn ở trên —
không phát sinh cảnh báo mới), 1074 hồ sơ sẵn sàng gửi. Việc siết mức chặn ở bảng đầu không khóa
lại hồ sơ nào, đúng như đo trước đã dự kiến.

### Trường ngày sinh chấp nhận dạng chỉ có năm (`yyyy`)

Cùng công văn 2076, bảng trường CT03/CT04/CT07 ghi: *"`NGAY_SINH` … định dạng **`yyyyMMdd`
hoặc `yyyy`**, với yyyy là năm sinh"*. Năm sinh không rõ ngày tháng là quy ước quen thuộc với
người cao tuổi — trên dữ liệu thật có 41 trường hợp dạng `1950` / `1948` / `1945` / `1939`
từng bị bắt nhầm.

Nới theo **danh sách hẹp** `CtdtQuyTac::CHI_NAM`, **không** nới quy tắc chung: `NGAY_VAO` và
`NGAY_RA` là `yyyyMMddHHmm`, `TU_NGAY`/`DEN_NGAY` là `yyyyMMdd`. Cho `yyyy` qua ở những trường
đó là để lọt `NGAY_RA = 2026` — một hồ sơ ra viện vào "năm nào đó".

Bốn trường được tài liệu nêu đích danh (`NGAY_SINH`, `NGAYSINH_NND`, `NGAY_SINHCON`,
`NGAY_CHETCON`); các trường ngày sinh của mẹ/cha và ngày cấp giấy tờ là **suy ra** từ cùng ngữ
nghĩa, đã ghi rõ trong chú thích. Nếu đối chiếu với cổng cho thấy suy luận đó sai thì **thu hẹp
danh sách**, đừng sửa `ngayHopLe()`.

### ⚠️ `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` — đỏ có chủ đích, ĐỪNG "SỬA"

Test này khẳng định `organization.chung_tu_dien_tu.submit_enabled` phải là `false`, và nó đọc
thẳng `config/organization.php` **của máy đang chạy** — tệp nằm trong `.gitignore`, mỗi máy một bản.

Trên máy đã đưa vào vận hành thật (đã bật gửi lên cổng BHXH), test này **sẽ đỏ vĩnh viễn**, và
đó là **lựa chọn có ý thức của chủ dự án**: giữ nó như một lời nhắc thường trực rằng máy này
gửi thật, không phải chạy thử.

Vì vậy:

- **Đừng sửa test, đừng xoá test, đừng đổi nó sang đọc `docs/organization.php`.** Đã cân nhắc và
  bác bỏ cả ba.
- Khi đối chiếu baseline, `tests/Unit/Ctdt` trên máy đã bật gửi có **đúng một** test đỏ là test
  này. **Hai** test đỏ trở lên mới là dấu hiệu có gì đó thật sự vỡ.
- Bản mẫu phát hành `docs/organization.php` vẫn giữ `submit_enabled => false` — đó mới là thứ
  quyết định trạng thái mặc định của một máy cài mới.

Cái giá phải trả: bộ test của module không bao giờ xanh hoàn toàn trên máy này nữa, nên một test
đỏ thật sau này dễ bị nhìn lướt qua. Đếm số lượng, đừng chỉ nhìn màu.

⚠️ Repo có sẵn test đỏ **không liên quan** module này: suite `Unit` cho 4 lỗi + 8 đỏ
(`NhapDanhMucUniqueTest`, `OrderCheck\CatalogLookupTest`, `BHYT\Xml3176ExportLocCoSoTest`,
`Import\GhiTheoLoTest` — riêng tệp này lệ thuộc dữ liệu CSDL phát triển thật `qlbv` nên số đỏ
của nó trôi theo thời gian; đo ngày 2026-08-20 ra 5 đỏ trong tệp đó, tổng suite `Unit` ra 8),
suite `Feature` cho 8 lỗi + 4 đỏ (`Dashboard\*ControllerTest`, `ExampleTest`). Chạy
`php vendor/bin/phpunit` trước khi sửa gì để biết đâu là đỏ cũ — đừng chỉ tin con số 7/8 cố định,
đếm lại trên máy đang chạy.

**Lưu ý khi viết test cho module này:**

- **Không dùng `RefreshDatabase`** — `.env` dự án trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật.
  Dùng trait `Tests\Support\DungBangCtdtSqlite` (SQLite bộ nhớ).
- Laravel 5.5 **không bật `PRAGMA foreign_keys`** cho SQLite, nên khóa ngoại không được thực thi
  trong test. Đừng viết test khẳng định cascade — nó cho cảm giác an tâm giả.
- PHPUnit 6: `setUp()` **không** có `: void`. PHP 7.4: không dùng cú pháp PHP 8.

### Dashboard `/dashboard/ctdt`

Ba khối, trả lời ba câu hỏi khác nhau:

| Khối | Câu hỏi | Đọc thế nào |
|---|---|---|
| Ba hàng đợi | Hệ thống có đang chạy không | Hàng đợi **đầy mà không vơi** = worker của nó chưa chạy. "không đếm được" nghĩa là hàng đợi không dùng driver `database` — **không** phải bằng 0. |
| Hồ sơ theo trạng thái | Hồ sơ đang kẹt ở đâu | Đủ **9 thanh**, kể cả thanh bằng 0. Tổng phải khớp màn danh sách với cùng bộ lọc. |
| Tồn đọng | Có gì kẹt lâu không | "Cái cũ nhất đã nằm N ngày" bắt được hàng đợi **chết chậm** — thứ mà biểu đồ sản lượng không chỉ ra, vì số nạp mỗi ngày vẫn bình thường. |
| Sản lượng theo ngày | Nạp/gửi được bao nhiêu | Ngày không có hồ sơ vẫn có mặt trên trục với giá trị 0. Một ngày hệ thống chết sẽ là một hố trên đường, không phải một đoạn bị nuốt mất. |
| Mã lỗi hay gặp | Đi sửa dữ liệu ở đâu | Nhãn có ghi **(chặn)** hay **(cảnh báo)**. Chỉ mã mức *chặn* mới thực sự giữ hồ sơ lại. |

Số đếm theo trạng thái dùng lại `CtdtDanhSach::truyVan(['trang_thai_gui' => ...])` — cùng bản
SQL với bộ lọc trên màn danh sách, nên hai màn hình không bao giờ nói khác nhau.

Khối "Mã lỗi hay gặp" (`CtdtDashboardService::chatLuong()`) lọc hồ sơ theo bộ lọc màn hình
trước (ngày nạp, dịch vụ, cơ sở), lấy id rồi mới join sang bảng `ctdt_loi` — bộ lọc là bộ lọc
theo HỒ SƠ, không áp thẳng lên bảng lỗi được. Danh sách id hồ sơ có trần `20000` phần tử
(`CtdtDashboardService::TRAN_ID_HO_SO`); vượt trần là **cắt bớt**, không phải lỗi — số liệu khi
đó chỉ còn là một phần, không còn đại diện cho toàn bộ bộ lọc. Xếp hạng mã lỗi và xếp hạng cơ
sở đều có trần số hàng (mặc định 15) — không trần thì một hệ thống có hàng trăm mã lỗi sẽ đổ
hết ra biểu đồ và không ai đọc được gì. Mỗi hàng mã lỗi tách riêng theo `muc_do` (`chan` |
`canh_bao`): một mã lỗi mức cảnh báo xếp trên một mã lỗi mức chặn sẽ đưa người đi tập huấn sửa
sai chỗ — cảnh báo không giữ hồ sơ nào lại, còn chặn thì có.

---

## 11. Tài liệu liên quan

| Tài liệu | Nội dung |
|---|---|
| [Thiết kế module (spec)](superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md) | Đặc tả đầy đủ 11 mục: tóm lược PL02, kiến trúc, lược đồ dữ liệu từng cột, các lớp, giao diện, kiểm thử, rủi ro, lộ trình 5 giai đoạn, ghi chú chuyển tiếp |
| [Kế hoạch Giai đoạn 1](superpowers/plans/2026-08-19-ctdt-giai-doan-1-nen-du-lieu.md) | 8 task TDD đã thực thi |
| [XML3176 & Order-Check](tai-lieu-tong-hop-xml3176-order-check.md) | Module tiền giám định — khuôn mẫu mà module này bám theo |
| [Ký số USB Token](Digital-Signature-USB-Token-Implementation-Plan.md) | `XMLSignService` mà Giai đoạn 4 sẽ dùng lại |
