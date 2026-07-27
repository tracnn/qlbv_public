# Nghiệm thu: Cấu hình chỉ tiêu báo cáo giao ban

## 1. Tính năng này làm gì

Trước đây, khi cấu hình chỉ tiêu cho báo cáo giao ban của một khoa, người quản trị phải tự gõ một
đoạn JSON tay. Tính năng mới thay đoạn JSON đó bằng một màn hình (modal) có form: thêm/sửa/xoá/kéo
thả từng chỉ tiêu, chọn danh mục HIS bằng tên thay vì mã số, chọn phạm vi khoa, nạp mẫu có sẵn, và
bấm thử để xem số ra ngay trước khi lưu. Đồng thời màn hình giao ban (nơi các khoa nhập số liệu
hàng ngày) cũng được cập nhật: ô nhập tay hiện đúng theo khai báo (đơn vị, giá trị nhỏ nhất/lớn
nhất, bắt buộc hay không), và có thể tự điền sẵn số của kỳ trước để khoa đỡ phải gõ lại.

Toàn bộ phần giao diện (7 hạng mục công việc, viết ra JavaScript và các trang Blade) chưa từng được
mở trên trình duyệt thật trong quá trình phát triển, vì các phần đó do các tiến trình tự động thực
hiện và không có phiên đăng nhập. Phần logic phía máy chủ đã được kiểm bằng test tự động và đọc lại
code kỹ, nhưng "chạy đúng trên giấy" khác với "bấm thật thấy đúng". Vì vậy cần một lượt nghiệm thu
tay trước khi coi tính năng này là xong.

## 2. Chuẩn bị trước khi nghiệm thu

- Đăng nhập bằng tài khoản có quyền `giaoban-admin`.
- Hai trang cần dùng:
  - `khth/giao-ban/cau-hinh` — trang cấu hình chỉ tiêu (nơi thực hiện phần lớn checklist).
  - `khth/giao-ban` — màn hình giao ban hàng ngày (dùng ở hai mục cuối checklist).
- Dữ liệu nên có sẵn trước khi bắt đầu:
  - Ít nhất **một khoa khối cận lâm sàng** (để kiểm phạm vi khoa, nhóm "Khác", và các ô chọn dịch
    vụ/phòng/loại CĐHA, loại xét nghiệm).
  - Ít nhất **một khoa khối điều trị** (để kiểm chọn "Loại kết thúc điều trị", chọn giường).
  - Ít nhất **hai khoa cùng một khối** (để kiểm chức năng "Nhân bản từ khoa khác").
  - Một chỉ tiêu **nhập tay** đã khai báo đầy đủ: đơn vị, giải thích, kiểu giá trị, nhỏ nhất/lớn
    nhất, bắt buộc — để kiểm ô nhập trên màn giao ban.
  - Một chỉ tiêu nhập tay có bật **"Kế thừa từ phiên trước"**, và **báo cáo của ngày hôm trước**
    đã có số nhập tay cho đúng chỉ tiêu đó — bắt buộc phải có để kiểm mục "kế thừa số kỳ trước"
    (nếu không có báo cáo hôm trước, ô kế thừa sẽ không có gì để lấy).
  - 5 mẫu chỉ tiêu có sẵn (đã được tạo sẵn trong hệ thống) để kiểm mục "Nạp mẫu".

## 3. Checklist nghiệm thu theo luồng thao tác

### 3.1. Mở trang cấu hình, mở modal chỉ tiêu

- [ ] Vào `khth/giao-ban/cau-hinh`, ở bảng khoa, cột hiển thị số chỉ tiêu là một **nút** (ví dụ
      "Chỉ tiêu (8)") — không còn là ô nhập JSON như trước.
- [ ] Bấm nút đó → modal mở, tiêu đề có tên khoa và tên khối (điều trị / khám / cận lâm sàng...).
- [ ] Danh sách chỉ tiêu hiện đúng số lượng, mỗi dòng (card) hiện mã, tên hiển thị và loại chỉ tiêu.
- [ ] Bấm mũi tên mở rộng một card → card mở ra thấy các ô cấu hình chi tiết; sửa "Tên hiển thị"
      thì chữ đậm trên đầu card đổi theo ngay lập tức.

### 3.2. Thêm / sửa / xoá / kéo thả chỉ tiêu

- [ ] Bấm "Thêm chỉ tiêu" → menu chỉ liệt kê các loại **hợp với khối của khoa đang mở** (ví dụ khoa
      khối điều trị sẽ không thấy loại "Đếm dịch vụ").
- [ ] Mở một card loại "Kết thúc điều trị" (khối điều trị) → thấy ô "Loại kết thúc" là ô chọn nhiều
      với tên tiếng Việt (Ra viện, Chuyển viện...), không phải mã số.
- [ ] Mở một card loại "Đếm dịch vụ" (khối cận lâm sàng) → thấy nhóm "Điều kiện lọc" gồm các ô:
      Loại dịch vụ, Loại CĐHA, Loại xét nghiệm, Ưu tiên từ/đến.
- [ ] Mở card "Nhập tay" → thấy đủ các ô: Đơn vị, Giải thích cho khoa, Kiểu giá trị, Nhỏ nhất, Lớn
      nhất, Bắt buộc nhập, Giá trị mặc định, Kế thừa từ phiên trước.
- [ ] Kéo một card bằng biểu tượng tay cầm ở đầu card, thả xuống dưới một card khác → card đó nằm
      **đúng sau** vị trí thả (không lệch hướng); thả lên trên thì nằm **đúng trước**. Không có lỗi
      hiện trong Console khi thả.
- [ ] Bấm biểu tượng xoá trên một card → có hỏi xác nhận; đồng ý thì card biến mất khỏi danh sách.

### 3.3. Chọn danh mục HIS theo tên

- [ ] Với danh mục nhỏ (ví dụ Loại kết thúc điều trị, Loại CĐHA, Loại xét nghiệm): mở ô chọn thấy
      ngay danh sách tên, không cần gõ tìm.
- [ ] Với danh mục lớn (Dịch vụ cụ thể, Phòng thực hiện, Giường): ô chọn hiện chữ mờ nhắc "Gõ ít
      nhất 2 ký tự để tìm". Gõ 1 ký tự chưa gọi tìm kiếm; gõ đủ 2 ký tự mới thấy kết quả tên hiện ra.
- [ ] Chọn 2-3 mục trong ô danh mục lớn (ví dụ 2 giường) → lưu lại được, không lỗi.
- [ ] Mở ô chọn (cả nhỏ lẫn lớn) trong lúc modal đang mở → danh sách gợi ý không bị modal che cắt
      mất, cuộn và gõ tìm bình thường.

### 3.4. Phạm vi khoa và nhóm "Khác" (chỉ khoa khối cận lâm sàng)

- [ ] Thêm chỉ tiêu "Đếm dịch vụ" ở khoa khối cận lâm sàng → thấy 3 lựa chọn phạm vi: khoa này chỉ
      định / khoa này thực hiện / phòng-dịch vụ cụ thể (mặc định là "khoa này chỉ định").
- [ ] Chọn "khoa này thực hiện" → không có gì bị lỗi, lưu vẫn được.
- [ ] Chọn "phòng / dịch vụ cụ thể" → hiện thêm hai ô chọn danh mục lớn (Phòng thực hiện, Dịch vụ
      cụ thể); phải chọn được ít nhất một mục.
- [ ] Chọn lại "khoa này chỉ định" → hai ô chọn phòng/dịch vụ biến mất.
- [ ] Ở ô "Loại CĐHA" (hoặc tương tự), chọn vài loại rồi tick ô "Là nhóm Khác" → danh sách đã chọn
      không mất, chỉ chuyển nghĩa thành "các loại còn lại ngoài danh sách này". Nhãn loại trên đầu
      card hiện thêm chữ "(nhóm Khác)".
- [ ] Bỏ tick "Là nhóm Khác" → trở lại đúng như trước khi tick, danh sách đã chọn vẫn còn nguyên.
- [ ] Lưu, mở lại modal → trạng thái phạm vi khoa và tick "Là nhóm Khác" giữ nguyên như lúc lưu.

### 3.5. Tab JSON

- [ ] Mở modal, chuyển sang tab JSON → nội dung JSON hiện đúng với những gì đang có ở tab Form,
      trình bày dễ đọc (có thụt lề).
- [ ] Sửa nội dung tab Form (ví dụ đổi tên một chỉ tiêu) → chuyển sang tab JSON → thấy thay đổi đó
      đã cập nhật trong JSON.
- [ ] Ở tab JSON, xoá bớt một chỉ tiêu trong JSON rồi quay lại tab Form → danh sách card giảm đúng
      một card.
- [ ] Gõ hỏng cú pháp JSON (ví dụ xoá một dấu ngoặc vuông) → khung JSON viền đỏ, có dòng thông báo
      lỗi, nút "Lưu chỉ tiêu" bị mờ không bấm được.
- [ ] Với JSON đang hỏng, chuyển sang tab Form → danh sách card **giữ nguyên như trước khi hỏng**,
      không bị xoá trắng.
- [ ] Sửa JSON lại cho đúng → viền đỏ và thông báo lỗi biến mất, nút Lưu bấm được trở lại.

### 3.6. Nạp mẫu / lưu mẫu / nhân bản từ khoa khác

- [ ] Mở menu "Nạp mẫu" → chỉ liệt kê mẫu **cùng khối** với khoa đang mở (khoa khối điều trị không
      thấy mẫu của khối cận lâm sàng).
- [ ] Chọn một mẫu, nạp vào khoa đang trống chỉ tiêu → đủ số card của mẫu, đúng thứ tự.
- [ ] Nạp lại mẫu đó lần nữa, khi được hỏi có xác nhận chọn "nối thêm" (không thay thế) → số card
      tăng gấp đôi, các card mới không trùng mã với card cũ.
- [ ] Mở menu "Nhân bản từ khoa khác" → chỉ liệt kê các khoa **cùng khối**, không có chính khoa
      đang mở.
- [ ] Nhân bản từ một khoa khác, sửa một chỉ tiêu vừa nhân bản, lưu lại → chỉ khoa đang mở bị đổi;
      khoa nguồn được nhân bản từ đó không hề bị ảnh hưởng.
- [ ] Bấm "Lưu bộ này thành mẫu…", đặt tên → có thông báo lưu thành công; tải lại trang, mở menu
      "Nạp mẫu" lại → mẫu mới xuất hiện trong danh sách.

### 3.7. Tính thử

- [ ] Bấm "Tính thử" → hai mốc thời gian mặc định được đề xuất (khoảng từ 7h hôm qua đến 7h hôm
      nay); sau khi chạy xong, bảng kết quả hiện giá trị số cho từng chỉ tiêu, kèm dòng ghi thời
      gian tính (mili-giây).
- [ ] Trong lúc đang tính, nút "Tính thử" bị mờ, không bấm lại được cho đến khi có kết quả.
- [ ] Với khoa chưa gán khoa HIS: các dòng kết quả có nền đỏ, ghi chú "Cấu hình chưa gán khoa HIS
      nào", giá trị hiện là 0.
- [ ] Với chỉ tiêu nhập tay: cột giá trị hiện dấu gạch ngang (không phải số 0), ghi chú "Chỉ tiêu
      nhập tay — không có số tự động".
- [ ] Với chỉ tiêu đang sai schema (ví dụ chưa chọn phòng/dịch vụ cho phạm vi "cụ thể"): hiện thông
      báo lỗi tiếng Việt, và card của chỉ tiêu đó được tô đỏ.

### 3.8. Lưu và xử lý lỗi

- [ ] Lưu chỉ tiêu hợp lệ → modal đóng lại, bảng danh sách khoa tự tải lại, số trên nút "Chỉ tiêu"
      cập nhật đúng.
- [ ] Thử lưu mẫu (mục 3.6) khi trong danh sách đang có một chỉ tiêu sai schema → hiện thông báo
      lỗi tiếng Việt, **không** tạo ra mẫu rác trong hệ thống.

### 3.9. Màn giao ban — ô nhập tay

- [ ] Ô của chỉ tiêu nhập tay bắt buộc có dấu `*` đỏ sau tên và biểu tượng dấu hỏi; rê chuột vào
      dấu hỏi hiện đúng câu giải thích đã khai báo.
- [ ] Bên phải ô nhập có chữ đơn vị (ví dụ "lượt") nếu đã khai báo đơn vị.
- [ ] Ô đang trống mà là bắt buộc → viền đỏ, nền hồng nhạt.
- [ ] Gõ số vào ô đó, lưu lại, tải lại trang → viền đỏ biến mất.
- [ ] Nếu chỉ tiêu khai kiểu số nguyên, nút tăng/giảm của ô nhảy từng 1 đơn vị; nếu khai kiểu phần
      trăm, nhảy từng 0.01 và không vượt quá 100.
- [ ] Gõ số âm vào ô có ràng buộc "không được nhỏ hơn 0" → trình duyệt tự chặn, không cho gõ/rời ô.
- [ ] Các chỉ tiêu tự động (không phải nhập tay, ví dụ "BN cũ", "BN vào") hiển thị y như trước:
      không dấu `*`, không đơn vị, không có các ràng buộc trên.

### 3.10. Màn giao ban — kế thừa số kỳ trước

- [ ] Tạo báo cáo ngày mới (khoa có chỉ tiêu bật "Kế thừa từ phiên trước"), bấm "Lấy số liệu" → ô
      đó đã có sẵn số của ngày hôm trước, chữ hiển thị màu xám và nghiêng, rê chuột hiện dòng "Kế
      thừa từ phiên trước, chưa xác nhận".
- [ ] Sửa ô đó thành một số khác, lưu lại → chữ trở về bình thường (hết xám nghiêng).

## 4. Bốn điểm nghiệm thu quan trọng nhất

Bốn điểm dưới đây là các "điểm sống còn" của tính năng — nếu sai ở đây thì tính năng coi như chưa
đạt, dù các mục khác ở trên đều đúng.

### 4.1. Lưu chỉ tiêu sai schema phải hiện lỗi ngay trên giao diện

Cố tình sửa mã một chỉ tiêu thành dạng sai quy ước (ví dụ đổi thành `BN_Cu` có chữ hoa), rồi bấm
Lưu.

**Kỳ vọng:** card đó viền đỏ, tự động mở ra, có thông báo lỗi tiếng Việt ngay trên card; modal
**không đóng lại**.

Đây là điểm chứng minh đường kiểm tra dữ liệu phía máy chủ đã nối đúng tới giao diện — nếu modal
tự đóng hoặc không thấy lỗi, nghĩa là dữ liệu sai vẫn có thể lọt vào hệ thống mà không ai biết.

### 4.2. Mở lại cấu hình có chọn danh mục lớn phải hiện đúng tên

Chọn vài dịch vụ/phòng/giường (danh mục lớn) cho một chỉ tiêu, lưu lại, đóng modal rồi **mở lại**
đúng chỉ tiêu đó.

**Kỳ vọng:** ô chọn hiện **đúng tên** các dịch vụ/phòng/giường đã chọn, không phải hiện số hoặc
hiện trống.

Đây là điểm chứng minh cơ chế "tra ngược từ mã số ra tên" hoạt động đúng — nếu sai, người quản trị
mở lại cấu hình cũ sẽ không biết mình đã chọn gì, dễ chọn nhầm hoặc chọn lại từ đầu.

### 4.3. Kế thừa số kỳ trước không được ghi đè số khoa vừa sửa

Ở màn giao ban, với chỉ tiêu có bật "Kế thừa từ phiên trước": bấm "Lấy số liệu", sửa ô đó thành
một số khác, rồi bấm "Lấy số liệu" **thêm một lần nữa**.

**Kỳ vọng:** số vừa sửa **vẫn còn nguyên**, không bị số của kỳ trước ghi đè lại.

Đây là điểm chống mất dữ liệu quan trọng nhất của toàn bộ tính năng. Nếu sai, mỗi lần khoa bấm
"Lấy số liệu" để cập nhật các chỉ tiêu tự động khác, số liệu khoa đã tự tay nhập và xác nhận có
thể bị âm thầm thay bằng số cũ của hôm trước.

### 4.4. Xoá trắng ô đã sửa rồi lấy số liệu lại — ô phải vẫn trống

Đây là điểm bổ sung sau lần rà soát cuối cùng của tính năng (sau khi các mục 4.1–4.3 đã được xác
nhận), xử lý một tình huống dễ bị bỏ sót: sau khi sửa số, sửa ô về trạng thái trống bằng nút hoàn
tác (biểu tượng ↺) cạnh ô, rồi bấm "Lấy số liệu" lại.

**Kỳ vọng:** ô đó **vẫn trống**, không bị số của kỳ trước tự động điền lại vào.

Nếu sai, một khoa chủ động xoá số (vì biết số đó chưa đúng hoặc chưa có) sẽ bị hệ thống tự ý điền
lại số cũ ngay ở lần lấy số liệu tiếp theo, khiến khoa tưởng nhầm là số đã được xác nhận.

## 5. Nếu thấy sai thì báo gì

Khi phát hiện một mục không đúng như kỳ vọng, xin ghi lại đủ các thông tin sau rồi báo lại:

- **Thao tác đã làm** — các bước bấm/gõ theo đúng thứ tự, càng chi tiết càng tốt (khoa nào, chỉ
  tiêu loại gì, giá trị đã nhập).
- **Đã thấy gì** — chụp màn hình nếu có thể.
- **Kỳ vọng là gì** — theo đúng mục checklist tương ứng ở trên.
- **Console trình duyệt có lỗi đỏ không** — mở công cụ dành cho lập trình viên của trình duyệt
  (phím F12), chọn tab Console, xem có dòng chữ đỏ nào xuất hiện đúng lúc thao tác hay không, và
  chụp lại nội dung dòng đỏ đó nếu có.
