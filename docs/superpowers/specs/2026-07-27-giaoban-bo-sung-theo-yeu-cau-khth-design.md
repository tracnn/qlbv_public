# Bổ sung báo cáo giao ban theo yêu cầu KHTH

Ngày: 2026-07-27
Nguồn: `YC SỬA VÀ BỔ SUNG BC GIAO BAN bổ sung thêm hiển thị bn vào viện các pk.docx` (7 mục, 11 ảnh).
Yêu cầu này có **trước** đợt làm form builder chỉ tiêu, nên một phần đã được giải quyết gián tiếp.

## 1. Phân loại: cái gì cấu hình được, cái gì phải code

Đây là kết luận quan trọng nhất của tài liệu. Sau đợt form builder, danh sách 7 mục tách làm ba nhóm rất khác nhau về chi phí.

| Mục | Nội dung | Nhóm |
|---|---|---|
| I | Khoa XN&CĐHA hiển thị sai bộ chỉ tiêu | **A — cấu hình** |
| III | HSCC thêm "BN cấp cứu" | **A — cấu hình** (cần tra cách nhận biết) |
| IV | 5 khoa thêm "BN nằm yêu cầu" | **A — cấu hình** (`bed_count`) |
| — | Tách "Xin về" khỏi "BN ra viện" | **A — cấu hình** |
| V, VI | Mổ cấp cứu / mổ phiên | **B — cần loại chỉ tiêu mới** |
| V, VI | Chờ mổ | **B — nhập tay** (HIS không có nguồn) |
| VI | Đẻ thường | **A — cấu hình** nếu là một dịch vụ cụ thể; cần tra |
| VII.1.3 | Bảng "BN vào viện tại các phòng khám" | **C — tính năng mới** |
| VII.2 | Bảng "Hoạt động điều trị" gộp + tổng cộng, HIỆN CÓ tách NT/Ng.T | **C — tính năng mới** |
| VII.1 | Dòng "Hoạt động KCB" cộng KKB + KKB Sơn Lương | **C — tính năng mới** |
| VII.3–6 | Danh sách BN mổ CC / mổ phiên / chờ mổ / theo dõi | **D — thực thể dữ liệu mới** |

## 2. Nhóm A — cấu hình, không cần lập trình

### I. Khoa XN&CĐHA đang để nhầm khối

Ảnh 2 cho thấy khoa này đang hiển thị bộ chỉ tiêu **điều trị nội trú** (BN cũ, BN vào, BN chuyển đến…) trong khi phải là **cận lâm sàng**. Đây là lỗi cấu hình, không phải lỗi phần mềm — vào modal chỉ tiêu, đổi khối sang *Cận lâm sàng*, nạp mẫu *Xét nghiệm* và *CĐHA*.

Mẫu đích (ảnh 1) có 22 chỉ tiêu, nhiều hơn hai mẫu seed sẵn có:

- **Xét nghiệm:** Huyết học, Sinh hóa, Nước tiểu, Test, Vi sinh, Liên kết, Miễn dịch, Đông máu
- **Thăm dò chức năng:** Điện tim, Điện não, Đo CN Hô Hấp, Đo MĐX
- **CĐHA:** Xquang, CT, CT tiêm thuốc, Siêu âm, Siêu âm màu, Siêu âm mạch, Siêu âm tim, SÂ đầu dò, SÂ đàn hồi mô
- **Nội soi / thủ thuật:** NSTH, NSTH gây mê, NS TMH, Thủ thuật TMH

Mẫu seed hiện có phủ được nhóm xét nghiệm và bốn chỉ tiêu CĐHA cơ bản. Phần còn lại (CT tiêm thuốc, SÂ đầu dò, SÂ đàn hồi mô, nội soi, thăm dò chức năng) phải chọn **dịch vụ cụ thể** — dùng ô "Dịch vụ cụ thể" trong form builder, gõ tên để tìm.

**Việc cần làm trước:** tra xem mỗi chỉ tiêu ứng với `service_type` / `diim_type` / `test_type` nào, hay phải liệt kê `service_ids`. Tính năng **Tính thử** sinh ra đúng để dò việc này — cấu hình xong bấm Tính thử, so với số trên bản Excel ngày đó.

### Tách "Xin về"

Ảnh mục 2 có cột **Xin Về** riêng. Hiện `XV` đang bị gộp vào "BN ra viện" (`end_codes: RV, HK, CC, XV, KH, TR`). Tách thành một chỉ tiêu `end_type` riêng với `end_codes: ["XV"]`, và bỏ `XV` khỏi chỉ tiêu ra viện.

**Cảnh báo:** đổi cái này làm **số "BN ra viện" giảm** so với các ngày trước. Cần thống nhất với KHTH mốc áp dụng, nếu không người xem sẽ tưởng dữ liệu sai.

### IV. "BN nằm yêu cầu" cho 5 khoa

HSCC, Nội Nhi, Ngoại TH-CK, Phụ Sản, YHCT&PHCN. Dùng chỉ tiêu `bed_count`, chọn các giường yêu cầu trong ô "Giường" (đã có tra cứu theo tên từ đợt trước).

Nếu bệnh viện không đánh dấu giường yêu cầu riêng trong HIS thì chuyển sang chỉ tiêu **nhập tay** với đơn vị "BN" và bật *kế thừa kỳ trước* — số giường yêu cầu ít đổi giữa các kỳ.

### III. "BN cấp cứu" cho HSCC

Cần chốt định nghĩa nghiệp vụ trước: đếm lượt khám cấp cứu, hay số BN vào viện qua đường cấp cứu, hay số BN đang nằm thuộc diện cấp cứu. Mỗi cách một chỉ tiêu khác nhau. Chưa đủ thông tin để chốt.

## 3. Nhóm B — mổ cấp cứu / mổ phiên / chờ mổ

### Vì sao không cấu hình được

Tôi đã truy vấn HIS thật. `his_pttt_priority` có đúng ba mức khớp yêu cầu:

| Mã | Tên |
|---|---|
| 01 | Mổ phiên |
| 02 | Mổ cấp cứu |
| 03 | Mổ theo yêu cầu |

Tháng 7/2026 ghi nhận **161 ca mổ phiên, 93 ca mổ cấp cứu** — dữ liệu có thật và đang được dùng.

Nhưng mức ưu tiên đó nằm ở `his_sere_serv_pttt.pttt_priority_id`, **không phải** `his_service_req.priority`. Kiểm chứng: cả 93 ca mổ cấp cứu đều có `sr.priority = 0`. Mà chỉ tiêu `service_count` hiện có chỉ lọc được `sr.priority`. **Nên không có cách nào cấu hình ra được con số này.**

### Thiết kế: loại chỉ tiêu mới `surgery_count`

Thêm vào `MetricSchema::TYPES`:

```php
'surgery_count' => [
    'label'  => 'Đếm ca phẫu thuật',
    'blocks' => ['dieu_tri'],
    'scope'  => 'service_dept',
    'filter' => [
        'pttt_priority_codes' => ['widget' => 'catalog_multi', 'catalog' => 'pttt_priority',
                                  'value' => 'string', 'label' => 'Mức ưu tiên mổ'],
    ],
],
```

Kèm một danh mục nhỏ mới `pttt_priority` trong `GiaoBanCatalogService::CATALOGS` (bảng `his_pttt_priority`, `id_col` = `pttt_priority_code`), và một `buildSurgeryCountSql()` trong `GiaoBanMetricService`.

**Chú ý khi dựng SQL:** phải quy về **khoa chỉ định** (`his_service_req.request_department_id`), không phải khoa thực hiện. Truy vấn thử cho ra `department_name` = "Khoa PT GMHS" (phòng mổ) cho mọi ca — nếu quy theo khoa thực hiện thì toàn bộ ca mổ dồn vào một khoa, còn Ngoại CK và Phụ Sản đều bằng 0.

Đây là lần đầu phải sửa `GiaoBanMetricService` kể từ đợt form builder. Nhớ cập nhật `MetricSchema::warningFor` và test đối chiếu `$hasScope` đi kèm.

### Chờ mổ

`his_pttt_calendar` chỉ có **27 dòng, mới nhất từ 2023** — bệnh viện không dùng lịch mổ trong HIS. Không có nguồn để tính.

Làm chỉ tiêu **nhập tay**, đơn vị "BN", bật *kế thừa kỳ trước* (danh sách chờ mổ thay đổi chậm).

## 4. Nhóm C — ba bảng mới trên màn tổng hợp / trình chiếu

### C1. Bảng "BN vào viện tại các phòng khám" (mục 1.3)

Đây là tiêu đề file yêu cầu, và là thứ hoàn toàn chưa có.

Mô hình hiện tại là ma trận **khoa × chỉ tiêu**. Bảng này là **phòng khám × 3 cột** (Tổng số khám, Vào viện, % vào viện) cộng dòng tổng — không nhét vào `giaoban_dept_configs` được.

**Danh sách phòng do KHTH cấu hình cố định** (đã chốt): giữ nguyên thứ tự, hiện cả phòng 0 ca, để lãnh đạo đối chiếu được giữa các ngày. Bản Excel hiện liệt kê 18 phòng, trong đó 6 phòng đang 0.

Bảng mới:

```php
Schema::create('giaoban_room_configs', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('his_room_id');   // v_his_room.id
    $table->string('display_name', 255);      // cho phép đặt tên rút gọn khác HIS
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

Số liệu tính từ HIS theo kỳ báo cáo, lưu vào một bảng snapshot song song với `giaoban_report_cells` (để báo cáo đã chốt không đổi số về sau):

- **Tổng số khám** — lượt khám tại phòng: `his_service_req` với `service_req_type_id = khám`, `is_main_exam = 1`, `execute_room_id = <phòng>`.
- **Vào viện** — trong số lượt khám đó, số ca dẫn tới điều trị nội trú (`his_treatment.tdl_treatment_type_id = 3`).
- **% vào viện** — tính khi hiển thị, không lưu. Lưu số dẫn xuất là tự chuốc rủi ro lệch.

`GiaoBanMetricService::buildExamVisitSql` đã lọc theo khoa; cần bản theo **phòng** — thêm `buildExamByRoomSql()`.

### C2. Bảng "Hoạt động điều trị" (mục 2)

Khác bảng đang có ở ba điểm:

1. **Dòng TỔNG CỘNG** — hiện chưa có khái niệm dòng tổng.
2. **Cột HIỆN CÓ tách NT / Ng.T** = Nội trú / Ngoại trú (đã chốt). Chỉ tiêu `census_to` hiện chỉ đếm nội trú (`tdl_treatment_type_id = 3`). Cần thêm chỉ tiêu ngoại trú tương ứng, và cột "Tổng" là tổng hai cái.
3. **Cột Yêu cầu** — chính là "BN nằm yêu cầu" ở nhóm A.

Cách rẻ nhất, không đẻ thêm khái niệm: cột NT/Ng.T/Tổng là **ba chỉ tiêu bình thường** trong cấu hình khoa; màn trình chiếu chỉ lo gom nhóm tiêu đề và cộng dòng tổng. Dòng tổng cộng đơn giản là cộng theo cột trên tập khoa của bảng — không cần lưu.

Cần một loại chỉ tiêu `census_to` biến thể cho ngoại trú, hoặc thêm khoá `treatment_type_ids` vào chỉ tiêu census hiện có. **Đề xuất cái sau** — ít loại hơn, và nhất quán với cách `exam_visit` đã làm.

### C3. Dòng "Hoạt động KCB" gộp KKB + KKB Sơn Lương

Hiện một cấu hình gộp được nhiều **khoa HIS**, nhưng KKB và KKB Sơn Lương là **hai cấu hình riêng** (khoa tự nhập số riêng, ảnh 3).

Không nên gộp chúng thành một cấu hình — mỗi khoa vẫn cần nhập và xem số của mình.

Thêm khái niệm **nhóm hiển thị** cho màn trình chiếu: một bảng nhỏ khai "dòng X trên màn tổng hợp = cộng các cấu hình A, B". Đủ tổng quát cho các yêu cầu gộp về sau mà không đụng vào cách khoa nhập liệu.

## 5. Nhóm D — danh sách bệnh nhân (mục 3–6)

Bốn mục: **BN mổ cấp cứu, BN mổ phiên, BN chờ mổ, BN theo dõi**.

Khác toàn bộ phần còn lại của hệ thống: đây **không phải con số** mà là danh sách dòng chữ. Ví dụ trong tài liệu:

> *khoaps — SP Thắm: Thai 39 tuần 5 ngày CD lần 2 / Viêm gan B / PTLT cũ*

Hệ thống hiện chỉ lưu được số (`decimal(12,2)`) và một ghi chú cho cả khoa.

### Nguồn dữ liệu

**Đã chốt: tự sinh từ HIS, cho khoa sửa và bổ sung.**

Truy vấn thử đã lấy được đủ tên bệnh nhân và chẩn đoán:

| Bệnh nhân | Chẩn đoán | Mức |
|---|---|---|
| DƯƠNG VĂN QUỲNH | Viêm đa xoang mạn tính - Nấm xoang hàm trái | Mổ phiên |
| VŨ ĐÌNH CẨN | Sỏi niệu quản trái 1/3 trên | Mổ phiên |

Đường join: `his_sere_serv_pttt` → `his_sere_serv` → `his_service_req` → `his_treatment` (`tdl_patient_name`, `icd_name`).

- **Mục 3, 4** (mổ cấp cứu, mổ phiên): tự sinh từ HIS.
- **Mục 5, 6** (chờ mổ, theo dõi): không có nguồn — khoa tự nhập.

### Bảng mới

```php
Schema::create('giaoban_report_patients', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('report_id');
    $table->unsignedInteger('dept_config_id');
    $table->string('category', 20);        // mo_cap_cuu | mo_phien | cho_mo | theo_doi
    $table->string('patient_name', 255);
    $table->text('diagnosis')->nullable();
    $table->text('note')->nullable();      // cột "Ghi chú"
    $table->string('source', 10);          // his | manual
    $table->unsignedInteger('his_ref_id')->nullable();  // sere_serv_pttt.id, để đối chiếu khi pull lại
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### Bất biến chống mất dữ liệu

Giống hệt bài học `carry_over` ở đợt trước, và đây là chỗ dễ sai nhất:

- Dòng `source = 'manual'` **không bao giờ** bị đụng khi lấy lại số liệu.
- Dòng `source = 'his'` mà khoa **đã sửa** (ghi chú, hoặc sửa chẩn đoán) cũng không được ghi đè — cần cờ đánh dấu đã sửa, hoặc so `his_ref_id` rồi chỉ thêm dòng mới chứ không sửa dòng cũ.
- Ca đã bị xoá khỏi HIS: **không tự xoá** dòng đã có, chỉ đánh dấu để KHTH xem lại. Tự xoá là mất ghi chú lâm sàng khoa đã viết.

Phải có test cho đúng kịch bản: pull lần 1 → khoa sửa ghi chú → pull lần 2 → ghi chú còn nguyên.

### Nhập liệu

Khoa nhập trên chính màn giao ban, trong khối khoa mình — thêm một khu "Danh sách bệnh nhân" dưới phần chỉ tiêu, có bốn nhóm theo `category`. Chịu cùng quy tắc phân quyền vừa làm: chỉ khoa được phân công mới thấy và sửa được danh sách của khoa mình.

## 6. Thứ tự đề xuất

> **Cập nhật 2026-07-27:** chủ đầu tư chốt cho các khoa **nhập tay** các chỉ tiêu XN & CĐHA thay vì
> tính tự động. Nhóm A vì thế không còn là việc lập trình hay tra cứu, chỉ còn là việc khai chỉ tiêu
> nhập tay trên giao diện — xem mục 6 của
> `2026-07-27-giaoban-nhom-A-anh-xa-chi-tieu-xn-cdha.md`.
>
> **Vẫn phải đổi khối cho khoa XN&CĐHA**, nếu không 8 chỉ tiêu điều trị nội trú vẫn hiển thị đè lên
> và mục I chưa được gỡ.
>
> Quyết định này cũng đặt lại câu hỏi cho nhóm B: nếu XN&CĐHA nhập tay được thì **mổ cấp cứu / mổ
> phiên có cần tự động không**, hay cũng nhập tay luôn? Nếu nhập tay thì `surgery_count` không cần
> viết, và nhóm B thu về chỉ còn khai chỉ tiêu. Cần chốt trước khi bắt đầu nhóm B.

1. ~~**Nhóm A**~~ — chuyển sang nhập tay, không còn việc lập trình.
2. **Nhóm B** — `surgery_count` + chỉ tiêu chờ mổ nhập tay. Nhỏ gọn, khép lại mục V và VI. *(Xem lại phạm vi theo ghi chú trên.)*
3. **Nhóm D** — danh sách bệnh nhân. Nhiều việc nhất nhưng độc lập, và là thứ lãnh đạo đọc trực tiếp trong cuộc giao ban.
4. **Nhóm C** — ba bảng trên màn trình chiếu. Để sau vì phụ thuộc chỉ tiêu của nhóm A và B đã đúng.

## 7. Còn phải làm rõ

- **"BN cấp cứu"** của HSCC (mục III) định nghĩa nghiệp vụ thế nào.
- **"Đẻ thường"** là một dịch vụ cụ thể trong HIS hay phải đếm theo cách khác.
- Mốc áp dụng việc **tách "Xin về"**, vì nó làm số "BN ra viện" giảm so với lịch sử.
- Mục **II** trong tài liệu ("khoa khám bệnh thêm trường vào viện") — khoa Khám bệnh **đã có** chỉ tiêu "Vào viện" (ảnh 3 hiện 73). Nhiều khả năng ý người viết là bảng 1.3 ở mục VII. Cần xác nhận lại kẻo làm thừa.
