# Dashboard lãnh đạo — thiết kế

Ngày: 19/10/2026 · Trạng thái: chờ duyệt (đã qua builder check)

## 1. Mục đích và lý do

Lãnh đạo yêu cầu thêm một loạt chỉ số (QR, hoàn online, ngày điều trị TB, công suất giường,
thời gian khám, thời gian mua thuốc, hệ ngoại, khám mới/cũ). Dashboard chính (`/home`) đã
nhiều khối và chạy chậm, nên các chỉ số này đi vào **một dashboard riêng**, không nhét thêm vào
trang chủ.

Người xem: Ban giám đốc, tự mở trên máy trong ngày hoặc khi họp. Họ cần trả lời nhanh:
"cơ sở nào / khoa nào đang tốt, đang tệ" — nên mọi biểu đồ theo khoa đều **sắp xếp giảm dần**
và so sánh được với nhau.

## 2. Mục tiêu / Không làm / Không được phép

**Mục tiêu**
- 1 trang mới, bố cục: hàng thẻ số cố định + 4 tab (Thanh toán · Điều trị & giường ·
  Khám & thuốc · Hệ ngoại).
- Bộ lọc: khoảng ngày + cơ sở (HN / NB / 2CS = cả hai).
- Trang nhẹ: chỉ tab đang mở mới truy vấn HIS; có cache ngắn.

**Không làm**
- Không sửa dashboard chính `/home`.
- Không làm chỉ số đăng ký online / tổng đài (11b — người dùng đã bỏ).
- Không đi xuống cấp khoa con HIS trong bản đầu (xem §4).
- Không xuất Excel/PPTX trong bản đầu (Highcharts exporting sẵn có là đủ).

**Không được phép (coi như hỏng dù chạy được)**
- Ngày điều trị TB, khám mới/cũ tính bằng công thức khác trang chủ — phải dùng chung hàm.
  (Trang chủ không lọc cơ sở và có cả branch 61, nên tổng 2CS có thể chênh trang chủ vài hồ sơ;
  đó là chênh do phạm vi, không phải do công thức. Nghiệm thu: so cùng phạm vi cơ sở.)
- Truy vấn làm nặng HIS: không quét cả bảng `his_transaction`/`his_service_req` không lọc thời
  gian; mỗi truy vấn phải có điều kiện khoảng thời gian trên cột có chỉ mục.
- Ghi bất cứ gì vào HIS (chỉ đọc).

## 3. Ràng buộc

- Laravel hiện có, kết nối Oracle `HISPro` (chỉ đọc), MySQL `qlbv` cho danh mục local.
- Giao diện AdminLTE 2 + Highcharts như các dashboard khác.
- Loại trừ đối tượng BN theo trait `ExcludesDashboardPatientTypes`
  (`app/Http/Controllers/Concerns/ExcludesDashboardPatientTypes.php`) — áp cho mọi chỉ số
  tính trên hồ sơ, giống trang chủ.
- Thời gian HIS dạng số `YYYYMMDDHH24MISS`.

## 4. Đơn vị "khoa" và "cơ sở" (đã chốt với người dùng)

**Khoa = đơn vị giường phê duyệt BHXH (cấp Viện/Trung tâm)**, không phải khoa con HIS.

- Nối: `HIS_DEPARTMENT.bhyt_code` = `department_bed_catalogs.ma_khoa`.
  Ví dụ `K04` gồm 13 khoa con của Viện Tim mạch; `K02` gồm 7 khu Cấp cứu A9.
- Hồ sơ/giao dịch gán vào khoa qua `HIS_TREATMENT.last_department_id` → `bhyt_code`
  (trừ khi chỉ số ghi khác).
- Tên hiển thị: `department_bed_catalogs.ten_khoa` theo (ma_khoa, ma_cskcb). Mã không có trong
  danh mục giường (vd `K01`, `K01.1`, `K01.2` khám bệnh; HN `K19`, `K38`, `K39`; NB `K45`):
  *(quyết định của tôi)* vẫn hiện ở biểu đồ không liên quan giường, tên lấy từ
  `HIS_DEPARTMENT.department_name` của khoa có tên ngắn nhất trong nhóm đó; bị loại khỏi biểu đồ
  giường. Bỏ hẳn `KTEST` và `bhyt_code` rỗng.
- Cùng một mã K ở hai cơ sở là **hai đơn vị khác nhau**. Khi lọc 2CS, nhãn cột thêm hậu tố
  " (HN)" / " (NB)".

**Cơ sở**: `branch_id` 1 = HN (CSKCB `01929`), 81 = NB (`37470`). 2CS = cả hai. Bỏ branch 61
(Phòng Y tế cơ quan). *(quyết định của tôi)* Cơ sở xác định thống nhất như sau:
- Biểu đồ theo khoa: cơ sở = `HIS_DEPARTMENT.branch_id` của khoa (khoa đã có cột `branch_id`:
  HN 153 khoa, NB 37 khoa). Nhờ vậy nhãn " (HN)/(NB)", tên khoa và giường phê duyệt luôn khớp nhau
  giữa các tab.
- Thẻ số không chia khoa: cơ sở = `HIS_TREATMENT.branch_id` (giao dịch: theo hồ sơ của giao dịch).

**Danh mục giường nhiều phiên bản**: khoá bảng là (ma_khoa, ma_cskcb, tu_ngay) nên một khoa có thể
có nhiều dòng. *(quyết định của tôi)* Lấy dòng còn hiệu lực tại ngày `to`
(`tu_ngay` rỗng hoặc ≤ `to`, `den_ngay` rỗng hoặc ≥ `to`); nếu nhiều dòng thì lấy `tu_ngay` lớn
nhất. Hiện chỉ `K39` HN có 3 dòng và không có giường phê duyệt. Mapping branch → ma_cskcb
dùng `App\Services\BHYT\DanhSachCoSo` nếu phù hợp, không hard-code ở nhiều nơi
(*quyết định của tôi*: đặt mapping trong config `organization.dashboard_lanh_dao.co_so`).

## 5. Các chỉ số — định nghĩa

Ký hiệu: [from, to] là khoảng ngày lọc (00:00:00 → 23:59:59).

### Hàng thẻ số (luôn hiện; mỗi thẻ có số theo lựa chọn cơ sở + dòng nhỏ HN · NB)

| Thẻ | Định nghĩa |
|---|---|
| Ngày điều trị TB | Đúng công thức `HomeController::getDetailDayCountInpatient` (`HomeController.php:155`): hồ sơ nội trú (`tdl_treatment_type_id=3`) có `out_time` trong kỳ; <4h = 0, 4–24h = 1, >24h = FLOOR + 1 nếu phần dư ≥4h; trung bình số học. **Tách công thức ra service dùng chung**, trang chủ gọi lại cùng hàm để không lệch. |
| Công suất giường | §5.2 — trên toàn cơ sở. |
| Tổng thời gian khám | Hồ sơ khám (`tdl_treatment_type_id=1`) có `out_time` trong kỳ: `out_time − in_time`. Hiển thị **trung vị** (người dùng đồng ý) + số hồ sơ. Loại bản ghi âm hoặc > 24h. |
| Kê đơn → mua xong thuốc | Phiếu xuất loại "Bán" (`HIS_EXP_MEST`, loại tra theo `HIS_EXP_MEST_TYPE`, không hard-code tên) có `finish_time` trong kỳ và `prescription_id` → `HIS_SERVICE_REQ.id`: `exp_mest.finish_time − service_req.intruction_time`. Trung vị. Chỉ phản ánh BN mua tại nhà thuốc BV (ghi chú dưới thẻ). Đo thử 01/10: 4.026/4.145 phiếu nối được, trung vị ≈ 16 phút. |
| Khám mới / cũ | Dùng đúng logic trang chủ (`HomeController.php:1499-1514, 1561-1576`): mới = `HIS_PATIENT.create_time` trong kỳ; cũ = `create_time < from`. Tách ra service dùng chung như trên. |
| Hệ ngoại | Số y lệnh PT / số ca PT đã thực hiện / số ca can thiệp tim mạch — §5.4. |

### 5.1 Tab Thanh toán

- **Tỷ lệ thanh toán QR theo khoa** (cột, giảm dần):
  số giao dịch thu (`transaction_type_id` IN (1 tạm ứng, 3 thanh toán)) có `pay_form` thuộc nhóm
  QR ÷ tổng số giao dịch thu. Nhóm QR mặc định theo `pay_form_code`: `08` QR Động, `07` QR Tĩnh,
  `19` QR Động (Thu ngân tạo) — **để trong config**, không hard-code.
  Điều kiện: `is_delete=0`, `is_cancel IS NULL`, `transaction_time` trong kỳ.
  Tooltip: số GD QR / tổng GD, số tiền QR / tổng tiền.
- **Tỷ lệ hoàn online theo khoa** (cột, giảm dần): giao dịch hoàn ứng
  (`transaction_type_id=2`) có `pay_form_code = 02` (Chuyển khoản) ÷ tổng giao dịch hoàn ứng.
  Mã hình thức "online" để trong config. Tooltip như trên.
- Khoa của giao dịch: khoa cuối của hồ sơ (§4). Giao dịch không có hồ sơ → bỏ qua (ghi số bị bỏ
  vào log debug, không hiện).

### 5.2 Tab Điều trị & giường

- **Ngày điều trị TB theo khoa** (cột, giảm dần): cùng công thức thẻ số, nhóm theo `bhyt_code`
  của `last_department_id`. *(quyết định của tôi: dùng khoa ra viện, không chia đoạn theo
  `HIS_DEPARTMENT_TRAN`, để tổng các khoa khớp số toàn viện.)*
- **Công suất giường theo khoa** (cột, có vạch 100%, cột >100% màu cảnh báo):
  *(quyết định của tôi — định nghĩa Bộ Y tế)*
  `công suất = tổng ngày-giường thực tế trong kỳ ÷ (giường phê duyệt × số ngày của kỳ)`.
  - Ngày-giường thực tế **tính theo thời lượng** (người dùng chốt): tổng số giờ nằm ÷ 24, ra số
    lẻ (nằm nửa ngày = 0,5). Nguồn `HIS_TREATMENT_BED_ROOM` (loại `HIS_CO_TREATMENT`,
    `is_delete=0`, hồ sơ `tdl_treatment_type_id IN (3,4)`), mỗi lượt nằm cắt theo [from, to]
    (`add_time`; `remove_time` NULL = đang nằm → dùng `min(now, to)`). Chuyển khoa trong ngày:
    mỗi khoa nhận đúng phần giờ nằm ở khoa đó, không tính trùng.
    **Nằm ghép hai giường** (một hồ sơ có hai lượt chồng thời gian trong cùng khoa): chỉ tính một —
    gộp các khoảng chồng nhau theo (treatment_id, khoa) trước khi cộng giờ.
    Khoa của lượt nằm: `his_bed_room → his_room.department_id → bhyt_code`.
    Tham khảo `GiaoBanMetricService::buildBedCapacitySql` (`app/Services/GiaoBan/GiaoBanMetricService.php:370-398`)
    và `HomeController::bedStatusByDepartment` (`HomeController.php:396`).
  - Giường phê duyệt: `department_bed_catalogs.giuong_pd` theo (ma_khoa, ma_cskcb). Khoa không có
    `giuong_pd` > 0 → không hiện ở biểu đồ này.
  - Thẻ số công suất toàn cơ sở = tổng ngày-giường ÷ (tổng giuong_pd × số ngày).
    Hiện tại: HN 3.600 giường, NB 1.000.
- **Bảng giường hiện tại theo khoa** (ảnh chụp lúc tải, không theo bộ lọc ngày):
  đơn vị · giường phê duyệt · đang nằm · còn trống (= PD − đang nằm, có thể âm → hiện đỏ) ·
  công suất tức thời. "Đang nằm" theo đúng điều kiện của `bedStatusByDepartment`.

### 5.3 Tab Khám & thuốc

- **Tổng thời gian khám theo khung giờ tiếp đón** (cột trung vị theo giờ `in_time` 6h→17h, trục
  phụ: số hồ sơ). Cùng tập hồ sơ với thẻ số.
- **Phân bố kê đơn → mua thuốc** (histogram nhóm phút: <10, 10–20, 20–30, 30–60, 60–120, >120).
- **Khám mới / cũ theo cơ sở** (cột chồng 100% HN, NB).

### 5.4 Tab Hệ ngoại

- **Khoa ngoại** = danh sách mã trong config, mặc định HN: `K19 K20 K21 K22 K23 K24 K52`,
  NB: `K19`.
- **Theo từng khoa ngoại** (cột nhóm): số y lệnh phẫu thuật (`HIS_SERVICE_REQ.service_req_type_id=10`,
  `intruction_time` trong kỳ, `is_delete=0`, khoa chỉ định = `request_department_id → bhyt_code`)
  và số ca đã thực hiện (cùng tập, `service_req_stt_id=3`).
  *(quyết định của tôi: gán theo khoa chỉ định, vì phòng mổ thuộc Trung tâm Gây mê K26.)*
- **Can thiệp tim mạch theo phòng** (cột): y lệnh đã thực hiện (`service_req_stt_id=3`) có
  `execute_room` thuộc danh sách phòng can thiệp trong config. Mặc định:
  HN `CLS.CT0001`…`CLS.CT0007` (Phòng can thiệp tim mạch, Lab 1–6);
  NB `NBKP03.K19`…`NBKP03.K23`. Đếm theo y lệnh (một lần vào phòng = một ca), không đếm dịch vụ.
- Thẻ số "Hệ ngoại" = tổng 3 con số trên theo cơ sở đang lọc.

## 6. Kỹ thuật

Theo mẫu dashboard con hiện có (`OperatingRoomController` + `OperatingRoomService`):

- **Route** (nhóm `checkrole:dashboard`, `routes/web.php` cạnh `dashboard/operating-room`):
  - `GET dashboard/lanh-dao` → trang.
  - `GET dashboard/lanh-dao/the-so`, `.../thanh-toan`, `.../dieu-tri-giuong`, `.../kham-thuoc`,
    `.../he-ngoai` → JSON. Tham số `from`, `to` (date, to ≥ from, tối đa 92 ngày),
    `ma_cskcb` = mã CSKCB từ ô chọn dùng chung `partials/ma_cskcb` (`DanhSachCoSo`, chỉ các mã có trong config); rỗng hoặc mã lạ = 2CS (cùng quy ước `LocCoSo`). Nội bộ vẫn quy về hn|nb|2cs.
- **Controller mỏng** `App\Http\Controllers\Dashboard\LanhDaoController`: validate → gọi service →
  JSON.
- **Service** `App\Services\Dashboard\LanhDaoService` (truy vấn) + các hàm thuần tách riêng để unit
  test: tính ngày ĐT theo quy tắc 4h, cắt lượt nằm theo kỳ, gán nhãn khoa/cơ sở, tỷ lệ, trung vị,
  nhóm histogram. Công thức ngày ĐT và khám mới/cũ chuyển vào service dùng chung; `HomeController`
  gọi lại (không đổi kết quả trang chủ).
- **Danh mục khoa**: map `bhyt_code` ↔ tên/giường PD đọc một lần mỗi request, cache 1 giờ.
- **Cache kết quả**: mỗi endpoint cache 5 phút theo khoá (endpoint, from, to, co_so). Khoảng ngày
  kết thúc trước hôm nay → cache 1 giờ.
- **View** `resources/views/dashboard/lanh-dao.blade.php` (AdminLTE box, `nav-tabs`), JS
  `public/js/dashboard/lanh-dao.js`. Tab nạp dữ liệu lần đầu khi được mở; đổi bộ lọc → nạp lại
  thẻ số + tab đang mở, các tab khác đánh dấu "cần nạp lại".
- **Menu**: thêm mục "Dashboard lãnh đạo" trong `config/adminlte.php` cạnh các dashboard con,
  quyền `dashboard`.
- Mặc định bộ lọc: từ đầu tháng đến hôm nay, cơ sở 2CS.
- Mọi mã cấu hình (QR, hoàn online, khoa ngoại, phòng can thiệp, branch ↔ CSKCB) nằm ở
  `config/organization.php` mục `dashboard_lanh_dao`.

## 7. Kiểm thử

- Unit test các hàm thuần (đặt trong `tests/Unit/Dashboard/`), không cần Oracle.
- Không dùng `RefreshDatabase`; tuân thủ chốt an toàn CSDL test hiện có.
- Đối chiếu tay sau khi dựng: ngày ĐT TB và khám mới/cũ trên trang mới = trang chủ cho cùng
  khoảng ngày; tỷ lệ QR toàn viện khớp truy vấn kiểm tra trực tiếp.

## 8. Để lại cho người làm

- Cách viết SQL cụ thể, tên hàm, chia nhỏ service.
- Chọn màu/kiểu Highcharts, miễn nhất quán với các dashboard con hiện có.
- Có tách migration công thức khỏi `ApiController` (bản sao công thức ngày ĐT) hay không.
