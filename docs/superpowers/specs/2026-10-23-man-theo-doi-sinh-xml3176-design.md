# Màn theo dõi sinh XML3176

Ngày 23/10/2026. Bổ sung cho module sinh XML3176 từ HIS (`docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`, `docs/superpowers/specs/2026-10-23-sinh-xml3176-quet-modify-time-design.md`).

## Vì sao

Module sinh XML3176 chạy nền (lệnh `sinhxml3176:quet`) và hiện không có giao diện. Muốn biết đã sinh gì thì phải đọc log hoặc mở thẳng bảng `sinh_xml3176_lan_sinh`.

Một hồ sơ có thể được sinh nhiều lần: bản thông tuyến khi ra viện, bản chính thức khi khoá viện phí, rồi sinh lại mỗi lần sửa mà nội dung đổi. Chủ dự án cần:
- lọc danh sách theo ngày;
- theo dõi từng lần sinh của một hồ sơ;
- xem XML gốc của từng lần;
- biết hai lần sinh khác nhau ở trường nào.

Tiền giám định (`XML3176Import`) nạp xong là **xoá tệp** khỏi thư mục quét. Vì vậy muốn xem lại XML thì module phải tự giữ bản sao.

## Chủ dự án đã chốt (23/10)

1. **Lưu XML của mỗi lần sinh.** Mỗi lần module ghi tệp (trạng thái `da_ghi`) thì giữ thêm một bản sao. Thư mục lưu trữ chia **mỗi ngày một thư mục con**. **Không tự xoá.** Xoá bằng tay trên màn danh sách.
2. **Danh sách:** lọc theo ngày. **Mỗi dòng là một hồ sơ** (không phải một lần sinh). Bấm vào hồ sơ thì thấy lịch sử, mỗi lần sinh một dòng.
3. **Xem XML:** hiện từng bảng thành **bảng có cột** như màn chi tiết XML3176 của tiền giám định, kèm tab XML thô và nút tải tệp gốc.
4. **So hai lần sinh** của cùng một hồ sơ: chỉ ra bảng nào, dòng nào, trường nào khác.
5. **Quyền:** `xml-man` được xem và so sánh; chỉ `superadministrator` được xoá.

## Thiết kế

### Lưu trữ

- Khoá cấu hình `thu_muc_luu` trong khối `sinh_xml3176`. Mẫu `D:\XML\SinhXml3176LuuTru`.
  - Phải nằm **ngoài** `thu_muc`, `thu_muc_tt`, `thu_muc_thu`, giống cách kiểm `thu_muc_thu` hiện có.
  - Để trống thì không lưu bản sao. Màn vẫn chạy, chỉ báo "không có XML lưu trữ".
- Đường dẫn: `<thu_muc_luu>\<yyyyMMdd của lúc sinh>\<tên tệp>`. Tên tệp lấy đúng tên tệp đã ghi, vốn đã có mã điều trị và dấu thời gian.
- Ghi bản sao **sau khi** ghi tệp chính thành công. Lỗi ghi bản sao **không** làm hỏng lần sinh: ghi `Log::warning`, cột đường dẫn để rỗng.
- Bảng theo dõi thêm cột bằng một migration mới:
  - `tep_luu`: đường dẫn tương đối so với `thu_muc_luu`, nullable;
  - `xoa_luu_luc`: datetime, nullable;
  - `xoa_luu_boi`: user id, nullable.
- Lần sinh trước khi có tính năng này không có bản sao. Màn hiện "không có XML lưu trữ".

### Danh sách (route trong nhóm `checkrole:xml-man`)

- **Bộ lọc:**
  - khoảng ngày, mặc định hôm nay;
  - kiểu ngày: ngày sinh tệp (`created_at`, mặc định), ngày ra viện (`out_time`) hoặc ngày khoá viện phí (`fee_lock_time`);
  - trạng thái lần sinh mới nhất: tất cả, đã ghi, bị loại, lỗi;
  - loại bản: tất cả, chính thức, thông tuyến;
  - mã điều trị.
- **Mỗi dòng một hồ sơ** (`treatment_id`) có ít nhất một lần sinh trong khoảng lọc. Các cột:
  - mã điều trị;
  - lần sinh mới nhất: giờ, loại bản, trạng thái, lý do nếu bị loại hoặc lỗi, các bảng, thư mục thử hay thật;
  - số lần sinh (đếm trên toàn bộ lịch sử, không chỉ trong khoảng lọc);
  - ngày ra viện, ngày khoá viện phí.
- **Phân trang phía máy chủ** (DataTables server-side như các màn khác trong repo), vì mỗi ngày khoảng 1.800 hồ sơ.
- **Ô tổng hợp** cho khoảng lọc: số hồ sơ theo trạng thái mới nhất, và mốc quét hiện tại của từng loại bản (`sinh_xml3176_moc`).
- **Không đọc HIS** khi hiển thị. Mọi số liệu lấy từ bảng của module.

### Lịch sử một hồ sơ

- Mỗi dòng `sinh_xml3176_lan_sinh` của hồ sơ là một lần sinh, sắp theo `id` tăng. Mỗi lần có: số thứ tự, giờ, loại bản, trạng thái, lý do, các bảng, thư mục, có hay không có XML lưu trữ (và đã xoá lúc nào, bởi ai).
- Lần `khong_doi` không có dòng riêng. Thiết kế quét hiện tại chỉ cập nhật `modify_time` của dòng cuối. Màn ghi chú điều này.
- Mỗi lần có XML lưu trữ thì có nút "Xem XML". Hai lần cùng có XML thì so được: chọn hai lần, hoặc bấm "So với lần trước".

### Xem XML một lần sinh

- Đọc tệp lưu trữ, giải mã từng `FILEHOSO` (base64), hiện tab theo bảng XML1–XML15.
- Mỗi bảng là bảng có cột, cột theo đúng thứ tự trường module xuất (danh sách trường của `XmlCon`). Bảng nhiều dòng (XML2–XML6, XML9, XML15) mỗi dòng dữ liệu một hàng. Bảng phẳng hiện dạng hai cột tên trường / giá trị.
- Có tab "XML thô" cho từng bảng và nút "Tải tệp gốc".
- Hình thức theo màn chi tiết XML3176 của tiền giám định. **Không dùng lại** các view `bhyt/xml3176/detail-xml-*`, vì chúng gắn với mô hình CSDL của tiền giám định và module phải tách biệt. Builder dựng một view chung cho mọi bảng.

### So hai lần sinh

- Tách logic ghép dòng và so trường của lệnh `sinhxml3176:so-sanh` thành một lớp dùng chung: khoá ghép từng bảng, chuẩn hoá số, rỗng bằng 0 ở cột số, đánh số khoá trùng. Lệnh và màn cùng gọi lớp này. Hành vi lệnh không đổi; test hiện có của lệnh phải xanh.
- Kết quả theo bảng gồm: dòng chỉ có ở lần A, dòng chỉ có ở lần B, và với dòng có ở cả hai thì các trường khác giá trị (A → B). Bảng không khác gì thì hiện "giống nhau".

### Xoá (chỉ `superadministrator`)

- Xoá **tệp lưu trữ** của các lần sinh đã chọn, hoặc của mọi lần sinh nằm trong một ngày lưu trữ (thư mục ngày).
- Dòng theo dõi **không xoá**. Chỉ điền `xoa_luu_luc` và `xoa_luu_boi`, xoá `tep_luu` khỏi đĩa. Thư mục ngày rỗng thì xoá luôn thư mục.
- Có hộp xác nhận ghi rõ số tệp sẽ xoá. Thao tác ghi `Log::info`.
- Kiểm đường dẫn: chỉ xoá tệp nằm trong `thu_muc_luu`, chặn `..`.

## Không làm

- Không sửa, không sinh lại, không gửi hồ sơ từ màn này. Màn chỉ xem; chạy bù vẫn dùng lệnh.
- Không lưu XML vào CSDL.
- Không đổi bộ quét, cách dựng bảng hay lệnh `sinhxml3176:so-sanh`, chỉ tách lớp.
- Không đọc bảng `xml3176_*` của tiền giám định.

## Ràng buộc

- Laravel 5.5, PHP 7.4, theo khuôn màn quản trị đang có trong repo: blade, Bootstrap 3, DataTables, `checkrole`.
- Cách ly module: không dùng lớp `App\Services\Xml3176...` hay `App\Models\BHYT\Xml3176...`.
- Tệp lưu trữ khoảng 170–185KB mỗi tệp, tức khoảng 300MB cho mỗi ngày 1.800 hồ sơ, chưa kể lần sinh lại. Ghi số này vào tài liệu vận hành để chuẩn bị dung lượng ổ đĩa.
- Test: cấm `RefreshDatabase`. Test controller theo cách các test HTTP đang có trong repo; lưu ý bẫy `CheckRole` với user factory (xem test hiện có).

## Các điểm Claude tự chốt (sau bước rà thiết kế, chủ dự án duyệt cùng tài liệu)

1. **Tên bản sao duy nhất:** `<thu_muc_luu>\<yyyyMMdd>\<id dòng theo dõi>_<tên tệp đã ghi>`. `tep_luu` ghi đúng tên thật đó.
   - Lý do: tên tệp chính có độ phân giải một giây. Hai lần sinh cùng giây của cùng hồ sơ sẽ ghi đè nhau, làm hai lần sinh trỏ chung một tệp, khiến so sánh sai và xoá một lần làm mất lần kia.
2. **Lịch sử, số lần sinh, so sánh:**
   - Lịch sử gộp cả hai loại bản, sắp theo `id`, có cột loại bản.
   - "Số lần sinh" là số dòng `da_ghi`, tức số lần đã ghi tệp. Dòng `bi_loai` và `loi` vẫn hiện trong lịch sử nhưng không đếm.
   - "So với lần trước" là so với lần `da_ghi` có XML ngay trước, **cùng loại bản**.
   - Chọn tay hai lần thì cho so cả khác loại bản, có ghi chú "khác loại bản".
3. **Bộ lọc danh sách:**
   - Hồ sơ hiện ra nếu có ít nhất một dòng theo dõi mà ngày (theo kiểu ngày đã chọn, lấy trên chính dòng đó) nằm trong khoảng lọc.
   - "Lần sinh mới nhất" lấy trên **toàn bộ lịch sử**, tức trạng thái hiện tại của hồ sơ. Lọc trạng thái, lọc loại bản và ô tổng hợp đều dùng lần mới nhất này.
   - `out_time` và `fee_lock_time` dạng `yyyyMMddHHmmss`, so theo khoảng `[ngày 000000, ngày 235959]`.
   - Migration thêm chỉ mục `created_at` cho bảng theo dõi.
4. **`khong_doi` không có dòng:** đã kiểm mã (`LuotQuet::xuLyLo` chỉ gọi `capNhatModify`, không `ghi`), nên không có gì phải lọc.
5. **Xoá theo ngày:** một ô riêng "Xoá XML lưu trữ của ngày" chọn **ngày sinh tệp** (thư mục ngày), độc lập với bộ lọc. Hộp xác nhận ghi số tệp. Xoá theo dòng đã chọn thì nằm trên danh sách lịch sử.
6. **Tệp lưu trữ mất hoặc hỏng:**
   - `tep_luu` còn nhưng tệp không còn trên đĩa: hiện "tệp không còn trên đĩa", tắt nút xem và so, không tự sửa dữ liệu.
   - Tệp không giải mã được: hiện lỗi ở tab của bảng đó, các bảng khác vẫn xem được.

## Để builder quyết

- Đường route và tên route. Đề xuất `bhyt/sinh-xml3176/...`, cạnh `bhyt/xml3176`.
- Vị trí mục menu (đề xuất trong nhóm XML3176).
- Cách hiện bảng nhiều dòng rất dài (XML2/XML3 hàng trăm dòng): phân nhóm theo ngày như màn tiền giám định hoặc phân trang. Builder chọn, nhưng phải đọc được với hồ sơ 1.000 dòng.
