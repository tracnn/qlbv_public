# 28/07/2026

- Tối ưu màn hình Danh sách hồ sơ XML (Hồ sơ XML → Xml 3176 → Danh sách hồ sơ): khắc phục lỗi hết bộ nhớ khi chọn cỡ trang lớn trên khoảng thời gian dài. Dữ liệu trả về chỉ còn đúng các cột hiển thị trên bảng (trước đây kèm theo toàn bộ danh sách lỗi, thông tin thẻ và thông tin gửi/ký của từng hồ sơ dù không cột nào dùng tới); số lỗi của mỗi hồ sơ chuyển sang đếm thay vì tải về toàn bộ bản ghi lỗi. Mỗi lần bấm "Tải dữ liệu" nay chỉ gửi một yêu cầu thay vì hai.
- Sửa lỗi mất lựa chọn khi chuyển trang ở Danh sách hồ sơ XML: trước đây chọn hồ sơ ở trang 1 rồi sang trang 2 tích thêm là mất sạch lựa chọn trang 1, và nút "Xuất XML3176" chỉ nhận được các hồ sơ đang hiển thị. Đây cũng là lý do người dùng phải đặt cỡ trang 2000 để chọn hàng loạt. Nay lựa chọn được giữ xuyên suốt các trang.
- Sửa lỗi nút "Tải xuống 7980a" bỏ qua bộ lọc "Trạng thái xuất XML": lọc "Đã xuất XML" trên màn hình rồi tải 79/80a vẫn nhận về cả hồ sơ chưa xuất.
- Tối ưu màn hình Chi tiết hồ sơ XML (bấm đúp vào một dòng hồ sơ): trước đây mở hồ sơ điều trị dài ngày rất chậm hoặc treo. Ba thay đổi — bỏ việc truy vấn cơ sở dữ liệu riêng cho từng dòng khi tô đỏ dòng có lỗi; chỉ tải nội dung của tab người dùng đang xem thay vì dựng sẵn cả 15 tab; các bảng nhiều dòng (thuốc, VTYT-DVKT, cận lâm sàng, diễn biến) chia trang 100 dòng và chỉ tải khi bấm vào từng ngày/nhóm. Cách chia tab theo ngày/nhóm giữ nguyên như cũ. Riêng tab dịch vụ kỹ thuật (XML3) nay xếp các nhóm theo thứ tự mã nhóm tăng dần (trước đây không có thứ tự cố định).
- Tối ưu tab "Lỗi XML" trong màn Chi tiết hồ sơ: bỏ truy vấn danh mục lỗi lặp cho từng dòng, và bật lại phân trang cho bảng lỗi (25 dòng/trang) — trước đây toàn bộ dòng lỗi được dựng cùng lúc nên hồ sơ nhiều lỗi làm đơ trình duyệt. Sửa kèm lỗi trắng trang khi gặp mã lỗi chưa có trong danh mục.
- Báo cáo giao ban - trình chiếu: bỏ biểu đồ "BN vào / ra theo khoa" ở màn Công suất giường. Biểu đồ này lọc theo mã chỉ tiêu cố định của bộ mẫu cũ nên từ khi chuyển sang cấu hình chỉ tiêu tự đặt tên thì không khớp mã nào và tự ẩn mà không báo lỗi. Thông tin vào/ra vẫn có trên slide của từng khoa.
- Ghi bổ sung địa chỉ trang, phương thức và tham số vào nhật ký lỗi hệ thống: lỗi nghiêm trọng của PHP (hết bộ nhớ, quá thời gian chạy) trước đây không cho biết thao tác nào gây ra, khiến việc truy nguyên phải phỏng đoán.
- **Sửa lỗi mất hồ sơ khi nhập XML 3176**: một tệp XML chứa nhiều hồ sơ nhưng hệ thống chỉ nhập hồ sơ **đầu tiên**, các hồ sơ còn lại bị bỏ im lặng mà không báo gì. Đã kiểm chứng trên tệp thật của đơn vị: tệp khai 2 hồ sơ, trước đây chỉ vào 1. Nay nhập đủ mọi hồ sơ trong tệp; mỗi hồ sơ được bọc trong một giao dịch riêng nên một hồ sơ lỗi không kéo đổ các hồ sơ khác. Bổ sung đối chiếu số hồ sơ thực tế với số khai báo trong tệp: thiếu thì từ chối cả tệp, thừa thì vẫn nhập và ghi cảnh báo. **Lưu ý**: lỗi tương tự vẫn còn ở luồng nhập QĐ130 và XML4210, chưa xử lý trong đợt này.
- Gộp hai đường nhập XML 3176 (nhập qua giao diện và quét thư mục tự động) về dùng chung một bộ xử lý. Trước đây hai đường có mã riêng nên sửa một bên không tự áp cho bên kia. Tệp hỏng khi quét thư mục nay được chuyển sang thư mục lỗi thay vì thử đi thử lại vô hạn và làm tắc cả lượt quét.
- Nới giới hạn bộ nhớ và thời gian chạy riêng cho chức năng tải tệp XML lên, khắc phục lỗi hết bộ nhớ khi nhập tệp lớn trên máy chủ mới.
- Tăng tốc khâu kiểm lỗi XML 3176: chia thành từng việc theo cặp (hồ sơ, loại XML) thay vì một việc cho toàn bộ hồ sơ, và gom việc ghi lỗi theo lô 500 dòng thay vì ghi từng dòng. Mỗi việc tự dọn phần lỗi cũ của mình nên chạy lại nhiều lần vẫn cho cùng kết quả.
- Logo trên giao diện nay lấy theo thiết lập của đơn vị (`config/organization.php`, mục `organization_logo`); không thiết lập thì dùng `public/images/logo.png`. Áp dụng cho cả màn hình đăng nhập.
- **Module Kiểm tra sai sót y lệnh — nhóm luật đối chiếu danh mục BHYT (7 luật, mặc định TẮT)**: kiểm dòng dịch vụ thuộc đối tượng BHYT xem đã khai mã BHYT chưa, mã và **tên** DVKT/thuốc/vật tư có khớp danh mục BHYT hay không. Bảo hiểm từ chối cả khi tên lệch chứ không riêng mã sai; bộ kiểm XML 3176 đã bắt lỗi này nhưng chỉ sau khi hồ sơ đã khoá và xuất XML, còn nhóm luật này bắt ngay trên y lệnh đang phát sinh. Việc đối chiếu chỉ tính trên các dòng danh mục **còn hiệu lực tại ngày chỉ định** của y lệnh, vì danh mục BHYT thay đổi theo từng đợt trúng thầu. Bảng danh mục chưa nhập thì luật tương ứng tự im lặng, không báo oan.
- Trước khi bật nhóm luật trên, chạy `php artisan kiemtraylenh:thu --ngay=7` để **đếm thử mà không ghi gì**: lệnh in số vi phạm dự kiến của từng luật, phân bố theo khoa, đồng thời cảnh báo nếu cột ngày hiệu lực trong danh mục không đọc được (khi đó việc lọc theo hiệu lực sẽ không có tác dụng). Có con số rồi mới bật từng luật trên màn Quản lý quy tắc.
- **Kiểm lỗi XML 3176 nay bắt cả lỗi tên dịch vụ kỹ thuật** (`XML3_INVALID_TEN_DICH_VU` — "Tên dịch vụ kỹ thuật khác tên được phê duyệt"). Trước đây thuốc và vật tư đã có kiểm tên, riêng DVKT thì chưa. Một mã DVKT có thể có nhiều dòng trong danh mục (nhiều đợt phê duyệt, nhiều quy trình khác nhau) nên tên khai chỉ cần trùng **một** dòng còn hiệu lực là hợp lệ; mô tả lỗi liệt kê tối đa 3 tên đã được phê duyệt để đối chiếu. Cách so khớp giống hệt luật tên bên module Kiểm tra sai sót y lệnh, nên hai nơi không đưa ra hai kết luận khác nhau cho cùng một hồ sơ.
- **Lưu ý khi dùng kiểm tra tên DVKT**: mã lỗi này đang được xếp mức **lỗi nghiêm trọng**, trong khi lỗi tên vật tư chỉ ở mức cảnh báo. Hồ sơ lệch tên DVKT vì thế sẽ không nằm trong bộ lọc "Không có lỗi nghiêm trọng" — bộ lọc thường dùng để chọn hồ sơ xuất XML. Nếu muốn xếp về mức cảnh báo cho đồng bộ, hoặc muốn tắt hẳn kiểm tra này, sửa trực tiếp trên màn Danh mục lỗi XML mà không cần cài đặt lại phần mềm. Chưa ước lượng được số hồ sơ sẽ bị bắt, nên chạy kiểm lại trên một ít hồ sơ trước khi áp cho toàn bộ.

# 20/07/2026

- Bổ sung Dashboard chuyên biệt cho module kiểm tra lỗi XML 3176 (Hồ sơ XML → Xml 3176 → Dashboard lỗi XML): 5 thẻ KPI (tổng hồ sơ, lỗi nghiêm trọng, lỗi thẻ BHYT, chi phí BHYT bị treo, đã gửi BHXH) và 4 biểu đồ — phễu pipeline 5 bậc (import → không lỗi nghiêm trọng → xuất XML → ký số → gửi BHXH, kèm % rơi rụng từng bậc), Pareto top 15 mã lỗi hay gặp (kèm % tích luỹ, phân biệt lỗi nghiêm trọng/cảnh báo), tồn đọng theo tuổi hồ sơ chưa gửi (0–7/8–15/16–30/>30 ngày) và lỗi nghiêm trọng theo khoa. Lọc theo loại ngày (vào/ra/thanh toán/tạo) như màn hình danh sách. Bấm vào mỗi con số hoặc cột biểu đồ sẽ mở màn hình danh sách hồ sơ với bộ lọc áp sẵn, số trên dashboard khớp đúng số dòng trong danh sách. Riêng biểu đồ tồn đọng luôn tính theo ngày ra viện so với hôm nay, không phụ thuộc khoảng ngày đang chọn (đã ghi chú rõ trên biểu đồ).
- Sửa lỗi cache token khi gửi hồ sơ lên Cổng dữ liệu Y tế Điện Biên và Trục dữ liệu Y tế: thời gian lưu token bị tính sai đơn vị nên giữ lâu gấp 60 lần thực tế, dẫn tới dùng token đã hết hạn và hồ sơ hợp lệ bị đẩy vào thư mục lỗi vĩnh viễn. Bổ sung tự đăng nhập lại và gửi lại một lần khi cổng từ chối token (401), kiểm tra hạn theo nội dung token, và giới hạn tuổi thọ token Điện Biên tối đa 15 phút cho chắc. Sửa thêm lỗi thiếu biến khi ghi log đăng nhập Trục dữ liệu Y tế.

# 09/07/2026

- Báo cáo giao ban - trình chiếu mục tổng hợp: bổ sung công suất giường (donut toàn viện Tổng/Đang dùng/Trống + thanh công suất % theo khoa, màu cảnh báo ≥90% đỏ/≥80% cam/≥60% teal) — dữ liệu chụp snapshot cùng thời điểm "Lấy số liệu", lưu bảng giaoban_report_beds; mở rộng lưới KPI tổng quan lên 8 ô (thêm Vào viện/Ra viện/Chuyển viện/Tử vong/Cấp cứu/PT-Đẻ, ẩn ô không có số liệu); tách thành 2 slide (Tổng quan toàn viện + Công suất & biến động theo khoa). Slide công suất theo khoa ưu tiên khoa báo cáo khối điều trị, nếu chưa cấu hình thì hiển thị theo từng khoa HIS có giường (kèm tên khoa, như dashboard home). Bổ sung hiển thị Ghi chú chung (general_note, rich text) trên slide Tổng quan. Đối chiếu HIS: 831 giường, 506 đang dùng, công suất 60,9% (18 khoa).
- Báo cáo giao ban: bổ sung Kíp trực lãnh đạo — cấu hình danh mục chức danh trực; nhập người trực chọn từ danh mục nhân viên HIS (his_employee, tự điền SĐT), cho phép nhiều người/chức danh, nút sao chép kíp ngày trước; phân quyền cập nhật kíp trực theo user (danh sách người được cập nhật, ngoài admin); hiển thị trên trình chiếu.

# 08/07/2026 (cập nhật 6)

- Báo cáo giao ban - khối Khám ngoại trú: thống kê thêm theo loại ra viện (his_treatment.treatment_end_type_id) — Cấp toa cho về, Chuyển viện, Hẹn khám lại; gom lại migration giao ban còn 5 file cho gọn.

# 08/07/2026 (cập nhật 5)

- Ghi chú khoa và ghi chú chung của Báo cáo giao ban chuyển sang soạn thảo rich text (CKEditor) qua popup; lưu HTML đã làm sạch (HTMLPurifier chống XSS); trình chiếu hiển thị định dạng đẹp; xuất Excel tự bỏ thẻ HTML.

# 08/07/2026 (cập nhật 4)

- Nâng cấp cấu hình Báo cáo giao ban: 1 khoa báo cáo gộp nhiều khoa HIS (loại trừ chuyển nội bộ); phân loại khối Điều trị/Khám/Cận lâm sàng với cách thống kê riêng (census, lượt khám theo tdl_treatment_type_id/tdl_patient_type_id, đếm dịch vụ CLS theo khoa thực hiện); gán tài khoản bằng tài khoản HIS (acs_user) qua ô tìm kiếm; thêm chỉ tiêu cho khoa CĐHA/Xét nghiệm.

# 08/07/2026 (cập nhật 3)

- Bổ sung chế độ Trình chiếu (Present) cho Báo cáo giao ban: mở trang slide toàn màn hình (tổng quan toàn viện + mỗi khoa 1 slide), điều hướng bằng phím/click, nút nhảy nhanh tới khoa, nền tối chuyên nghiệp; đổi nút "Xem" thành "Làm mới".

# 08/07/2026 (cập nhật 2)

- Bổ sung Báo cáo giao ban bệnh viện (KHTH): tự động tính số liệu theo khoa từ HIS (BN cũ/vào/chuyển/ra/hiện có, PTTT, giường YC, XN/CĐHA...) theo khoảng giờ tùy chọn; cho sửa tay từng ô theo phân quyền khoa (giaoban_khoa/giaoban_admin); chốt báo cáo + xuất Excel theo biểu mẫu; màn cấu hình động khoa/chỉ tiêu và gán tài khoản↔khoa.

# 08/07/2026 (cập nhật 1)
- Bổ tài biểu mẫu import bộ danh mục dịch vụ

# 01/07/2026

- Module Kiểm tra sai sót y lệnh (giai đoạn 7): bổ sung trang Quản lý quy tắc (KHTH) — bật/tắt từng luật, sửa mức độ và tên hiển thị ngay trên giao diện, không cần vào database; gom nhóm menu "Kiểm tra sai sót y lệnh" đặt ngang hàng ngay dưới mục "Thống kê".
- Module Kiểm tra sai sót y lệnh: bộ lọc "Loại dịch vụ" trên dashboard nạp từ danh mục HIS (dropdown, giống bộ lọc Khoa) thay vì gõ tay; bỏ cột "Mã DV" khỏi bảng vi phạm vì chỉ có dữ liệu với luật giới tính/tuổi nên hầu như luôn trống.
- Module Kiểm tra sai sót y lệnh: tách luật cấp phiếu chỉ định theo từng loại dịch vụ (luật dùng chung + bộ luật riêng cho Đơn thuốc, Đơn phòng khám, Chẩn đoán hình ảnh, Đơn máu...) để mỗi loại có tiêu chí kiểm tra phù hợp và dễ bổ sung luật mới. Sửa lỗi API tra cứu vi phạm khiến `route:cache` không chạy được.

# 30/06/2026 (cập nhật 5)

- Module Kiểm tra sai sót y lệnh (giai đoạn 6): thêm danh mục tự quản "Giới hạn dịch vụ" (giới tính/tuổi) + màn nhập (KHTH) + 2 luật A_GENDER_MISMATCH, A_AGE_OUT_OF_RANGE đối chiếu chỉ định với giới tính/tuổi bệnh nhân. Luật chỉ phát hiện khi danh mục đã được nhập (HIS không có sẵn dữ liệu giới hạn).

# 30/06/2026 (cập nhật 4)

- Module Kiểm tra sai sót y lệnh (giai đoạn 5): bổ sung luật cấp đợt điều trị — A3 trùng dịch vụ, A2 trùng hoạt chất (HIS_EXP_MEST_MEDICINE + HIS_MEDICINE), A5 liều×ngày không khớp số lượng cấp. Quét incremental theo hoạt động mới rồi re-evaluate cả đợt; bật/tắt trong order_check_rules.

# 30/06/2026 (cập nhật 3)

- Module Kiểm tra sai sót y lệnh (giai đoạn 4): gửi email digest định kỳ các vi phạm mới tới danh sách người nhận (email_receive_report), theo ngưỡng mức độ cấu hình; chạy bằng service `kiemtraylenh:notify`. Mặc định TẮT (bật qua ORDER_CHECK_NOTIFY_ENABLED).

# 30/06/2026 (cập nhật 2)

- Module Kiểm tra sai sót y lệnh (giai đoạn 3): dashboard KHTH "Kiểm tra sai sót y lệnh" (lọc theo ngày/khoa/mức độ/loại luật/trạng thái + KPI + DataTables), quy trình xử lý (đã xử lý/bỏ qua + ghi chú + người xử lý), xuất Excel, và API JSON tra cứu vi phạm theo đợt điều trị.

# 30/06/2026 (cập nhật)

- Module Kiểm tra sai sót y lệnh (giai đoạn 2): tổng quát hóa engine đa-nguồn (multi-scanner); bổ sung luật A1 nạp cảnh báo tương tác thuốc do HIS phát hiện (HIS_MEDICINE_INTERACTIVE) và A4 phát hiện phiếu chỉ định thiếu chẩn đoán ICD. Các luật bật/tắt trong order_check_rules.

# 30/06/2026

- Bổ sung module Kiểm tra sai sót y lệnh (giai đoạn 1): quét incremental phiếu chỉ định từ HIS (HIS_SERVICE_REQ) theo watermark, chạy 4 quy tắc hợp lệ cấu trúc/thời gian & hành nghề (ngày ra<vào, giờ y lệnh ngoài đợt, giờ thực hiện trước y lệnh, BS thiếu chứng chỉ), lưu vi phạm vào order_check_violations. Chạy bằng `php artisan kiemtraylenh:scan` (lập lịch mỗi 1–5 phút qua Windows Task/nssm).

# 23/06/2026

- Bổ sung báo cáo KHTH "Doanh thu theo khoa/phòng thực hiện": lọc theo giai đoạn/khoa/phòng, biểu đồ + bảng doanh thu theo khoa, chi tiết theo phòng (DataTables) + xuất Excel; doanh thu tính theo vir_price (amount × vir_price)
- Bổ sung biểu đồ "Tình trạng giường theo khoa" trên Home dashboard: cột nhóm giường đã sử dụng / còn trống + công suất % theo từng khoa (his_bed, his_treatment_bed_room), trạng thái hiện tại (real-time, không lọc ngày)

# 22/06/2026

- Bổ sung biểu đồ "Doanh thu theo khoa thực hiện" trên Home dashboard: biểu đồ cột doanh thu theo khoa (his_department), mỗi khoa một màu, đơn vị triệu (Tr), loại bỏ khoa không có doanh thu, lọc theo khoảng ngày của dashboard

# 10/06/2026

- Bổ sung biểu đồ "Số lượng dịch vụ theo máy thực hiện" trên Home dashboard: thống kê số lượng dịch vụ theo máy (his_machine), có nút chuyển xem theo nhóm máy / từng máy, lọc theo khoảng ngày của dashboard

# 09/06/2026

- Bổ sung dashboard Tỷ lệ trả kết quả đúng hẹn (KHTH): tính % trả KQ đúng hẹn/trễ hẹn theo thời gian hẹn (ESTIMATE_DURATION) của dịch vụ cận lâm sàng; có tổng hợp theo loại DV/khoa-phòng/dịch vụ/ngày, xem chi tiết, drill-down và export Excel

# 26/05/2026

- Bổ sung kiểm tra mã bệnh thuộc nhóm cảnh báo không được thanh toán BHYT

# 20/05/2026

- Bổ sung thiết lập Logo theo từng đơn vị trên giao diện trả kết quả KCB

# 11/05/2026

- Bổ sung hiển thị Doanh thu / Số lương theo đối tượng

# 06/05/2026

- Update giao diện màn hình hiển thị thông tin chờ khám của bệnh nhân - Dashboard sử dụng cho màn hình lớn

# 24/04/2026

- Update giao diện hiển thị kết quả CĐHA từ PACS VNPT
- Bổ sung thông tin thời gian ra viện cho bệnh nhân nội trú

# 08/04/2026

- Bổ sung báo cáo khảo sát TG khám bệnh

# 31/03/2026

- Update bổ sung báo cáo doanh thu dịch vụ y tế
- Update bổ sung biểu đồ phân tích dữ liệu cho lãnh đạo CSKCB

# 26/03/2026

- Update tích hợp ký số XML3176 bằng USB Token, bổ sung thêm 1 service ký số trên Windows

# 25/03/2026

- Update bổ sung báo cáo doanh thu dịch vụ y tế

# 24/03/2026

- Update bổ sung chức năng xem kết quả CĐHA PACS VNPT

# 20/03/2026

- Update bổ sung tính năng auto update

# 30/01/2026

- Update tối ưu tốc độ xử lý export XML, submit XML lên BHXH, ký số HSM
- Update tự động đẩy hồ sơ XML3176 từ hệ thống export tiền giám định sang module gửi lên cổng dữ liệu Sở y tế Hà Nội

# 20/01/2026

- Cập nhật bổ sung XML3176 - Tương đương với các chức năng của XML4750 hiện có

# 17/01/2026

- Cập nhật, bổ sung chức năng gửi XML4750 lên cổng dữ liệu tỉnh Điện Biên
- Cập nhật bổ sung chức năng gửi XML3176 lên cổng dữ liệu Sở y tế HN

# 16/12/2025

- Bổ sung chức năng gửi hồ sơ XML từ phần mềm tiền giám định
- Quản lý trạng thái hồ sơ đã gửi dựa vào kết quả trả về từ Cổng BHXH

# 29/11/2025

- Sửa chức năng import danh mục do danh mục excel thay đổi cấu trúc cột
- Bổ sung quy tắc bắt lỗi trùng khít ngày y lệnh, ngày thực hiện, ngày kq của XML3 loại PTTT
- Bổ sung báo cáo thống kê danh sách NVYT có chỉ định y lệnh teo thời gian

# 27/11/2025

- Bổ sung báo cáo thống kê Thuốc/VTYT tiêu hao

# 20/10/2025

- Nâng cấp ký số XML sử dụng HSM của VietSens

# 12/11/2024

- Bổ sung import/export XML6 (HIV), XML15 (Lao)

# 05/11/2024

- Bổ sung kiểm tra chỉ cho phép tối đa 1 công khám đối với điều trị nội trú

# 31/10/2024

- Bổ sung kiểm tra Xml3 đối với một dịch vụ có nhiều giá hợp lệ

# 28/10/2024

- Bổ sung chức năng kiểm tra trùng lặp giường: Key kiểm tra ma_giuong + ma_khoa + ngay_th_yl

# 25/10/2024

- Bổ sung chức năng kiểm tra thẻ: Bỏ qua không kiểm tra những thẻ BHYT của CBCS (CA, QN, CY)
- Bổ sung kiểm tra thời gian y lệnh của VTYT trong gói có khớp với thời gian y lệnh của DVKT không

# 24/10/2024

- Bổ sung lọc Hồ sơ XML4750 theo người import

# 23/10/2024

- Export Excel lỗi: Bổ sung sheet lỗi thẻ
- Update logic Kiểm tra mã thẻ tạm

# 22/10/2024

- Bổ sung Import/Export XML10 (Giấy nghỉ việc dưỡng thai)
  - Bổ sung Import
  - Bổ sung Export
  - Bổ sung form hiển thị thông tin
  - Bổ sung các quy tắc giám định

# 21/10/2024

- Bổ sung kiểm tra bắt buộc phải có mã máy đối với những dịch vụ thuộc nhóm cần kiểm tra (XN/CĐHA...)

# 18/11/2024

- Bổ sung lấy thông tin người dùng nào đã import/export hồ sơ (Đối với trường hợp import/export hồ sơ tự động thì để trống)
- Bổ sung tải về 7980a (Tải về file excel)

# 11/10/2024

- Bổ sung kiểm tra VTYT kèm theo DVKT phải có y lệnh trùng ngày y lệnh DVKT.
- Bổ sung cho phép người dùng lựa chọn Lỗi Xml:
  - Cho phép kiểm tra/không kiểm tra lỗi.
  - Cho phép lựa chọn lỗi đó là lỗi critical hoặc warning.
- Bổ sung hướng xử lý trên form hiển thị lỗi Xml: Hướng dẫn cách xử lý một số lỗi cơ bản
- Bổ sung chức năng import danh mục trên giao diện người dùng

# 30/09/2024

- Bổ sung export data BN nợ viện phí dạng xlsx
- Bổ sung chức năng gán quyền superadmin cho user đầu tiên đăng nhập hệ thống:
  Phục vụ cho việc triển khai tool ở một đơn vị mới: Nếu lần đầu tiên đăng nhập, hệ thống sẽ kiểm tra xem đã có User nào được gán quyền superadmin chưa. Nếu chưa có thì sẽ gán cho user đăng nhập hiện tại, nếu có rồi thì bỏ qua.

# 28/09/2024

- Bổ sung báo cáo BN nợ viện phí (thêm thông tin và cải thiện tốc độ xử lý so với HIS)

# 23/09/2024

- Sửa export Xml130 ra thư mục với Ma CSKCB tương ứng trong XmlContent
- Bổ sung lọc Xml4750 theo mã bệnh nhân

# 18/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Bổ sung kiểm tra BBHC thuốc có dấu sao

# 17/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Bổ sung kiểm tra BBHC PTTT
  - Bổ sung kiểm tra BBHC DVKT

# 16/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Kiểm tra nợ viện phí
  - Hiển thị đơn thuốc phòng khám

# 15/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Kiểm tra chữ ký của BN trên bảng kê thanh toán

# 12/09/2024

- Bổ sung kiểm tra tính hợp lệ của giấy nghỉ việc hưởng BHXH
  - Bổ sung Qd130Xml11Checker kiểm tra tu_ngay không được lớn hơn qd130_xml1.ngay_ra
  - Bổ sung Qd130Xml11Checker kiểm tra den_ngay không được nhỏ hơn qd130_xml1.ngay_ra

# 10/09/2024

- Sửa Qd130Xml3Checker
  - Bổ sung $this->serviceDisplay ưu tiên lấy ten_vat_tu nếu không có mới lấy ten_dich_vu
  - Phù hợp với export Xml3 của HisPro Vietsens

# 09/09/2024

- Sửa mail gửi lỗi thẻ BHYT, bổ sung Mã thẻ HISPro của Vietsens trong trường hợp không tra cứu được thông tin
  - Bổ sung mối quan hệ Models\CheckBHYT\check_hein_card với bảng his_treatment của Hispro
  - Sửa template gửi email resources\templates\mail-qd130-errors.blade

# 06/09/2024

- Bổ sung chức năng tự động import danh mục cơ sở khám chữa bệnh
  - Bổ sung fillable trong Models\MedicalOrganization
  - Tải danh mục đơn vị hành chính từ trang: https://gdbhyt.baohiemxahoi.gov.vn/DM_COSOKCB
  - Sửa Artisan Command ImportCatalogBHXHFromFiles
    - Bổ sung kiểm tra cấu trúc file danh mục
    - Bổ sung thêm case $firstRow === $expectedMedicalOrganizationColumns:
- Sửa Service Check Xml lọc MedicalOrganization với is_active = true
  - Sửa Service Qd130Xml1Checker

# 04/09/2024

- Bổ sung chức năng tự động import danh mục đơn vị hành chính
  - Bổ sung fillable trong Models\AdministrativeUnit
  - Tải danh mục đơn vị hành chính từ trang: https://danhmuchanhchinh.gso.gov.vn/Default.aspx
  - Sửa Artisan Command ImportCatalogBHXHFromFiles
    - Bổ sung kiểm tra cấu trúc file danh mục
    - Bổ sung thêm case $firstRow === $expectedAdministrativeUnitsColumns:
- Sửa Service Check Xml lọc AdministrativeUnit với is_active = true
  - Sửa Service Qd130Xml1Checker

# 27/08/2024

- Tối ưu chức năng tự động quét thẻ BHYT
  - Đối với những thẻ bị sai thông tin được quy định trong config qd130xml.hein_card_invalid.check_code và qd130xml.hein_card_invalid.result_code thì thực hiện quét lại thẻ BHYT, kể cả không có sự thay đổi thông tin thì vẫn cập nhật updated_at tại thời điểm kiểm tra nhằm mục đích gửi thông báo tới các khoa phòng liên quan để sửa lỗi thông tin thẻ
  - Sửa job jobKtTheBHYT: phương thức handle() và phương thức addCheckHeinCard()
- Bổ sung kiểm tra tyle_tt_dv và tyle_tt_bh trong Xml2 và Xml3 chỉ được nằm trong khoảng từ 0 đến 100
  - Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml2Checker
  - Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml3Checker

# 24/08/2024

- Bổ sung tự động quét kiểm tra thẻ BHYT đối với BN đang điều trị (His Pro Vietsens)
  - Bổ sung artisan command HISProKiemTraTheBHYT
  - Chỉ quét một lần trong suốt quá trình điều trị đối với thẻ đúng
  - Thực hiện quét lại đối với thẻ sai
  - Cho phép cấu hình thời gian chạy quét bằng task schedule (Windows) hoặc supersivor (Linux/Unix)

# 23/08/2024

- Bổ sung kiểm tra quy tắc kiểm tra Khoa chỉ định không hợp lệ (Warning)
  - Khoa khám bệnh (K01) chỉ định dịch vụ/vtyt (xml3) và thuốc (xml2) cho BN nội trú - trái tuyến
  - Thêm key trong config.qd130xml
  - Bổ sung quy tắc trong Qd130Xml2Checker và Qd130Xml3Checker
- Bổ sung kiểm tra quy tắc TT_THAU đúng định dạng Gx;Nx trong Xml2 và Xml3 nếu có (Warning)
  - thêm key trong config.qd130xml
  - Bổ sung quy tắc infoChecker trong Qd130Xml2Checker và Qd130Xml3Checker

# 15/08/2024

- Cập nhật kiểm tra Xml9 Thông tin trẻ sơ sinh (Critical)

# 07/08/2024

- Cập nhật kiểm tra Xml5, thời điểm dbls phải nằm trong khoảng thời gian vào và ra (Critical)

# 31/07/2024

- Cập nhật phần kiểm tra Qd130XmlCompleteChecker: t_bhtt_gdv, bổ sung qd130xml config, không check đối với những mã thẻ là QN, CY, CA

1. Chi phí của các đối tượng có mã thẻ quân nhân (QN), cơ yếu (CY), công an (CA);
2. Chi phí vận chuyển người bệnh có thẻ BHYT;
3. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng dịch vụ kỹ thuật thận nhân tạo chu kỳ hoặc dịch vụ kỹ thuật lọc màng bụng hoặc dịch lọc màng bụng:
4. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc chống ung thư hoặc dịch vụ can thiệp điều trị bệnh ung thư đối với người bệnh được chẩn đoán bệnh ung thư gồm các mã từ C00 đến 297 và các mã từ 00 đến D09 thuộc bộ mã Phân loại bệnh quốc tế lần thứ X ( sau đây viết tắt là ICD - 10);
5. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc điều trị Hemophilia hoặc máu hoặc chế phẩm của máu đối với người bệnh được chẩn đoán bệnh Hemophilia gồm các mã D60, D67, D68 thuộc bộ mã ICD - 10;
6. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc chống thải ghép đối với người bệnh ghép tạng;
7. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc điều trị viêm gan C của người bệnh bị bệnh viên gan C;
8. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc kháng HIV hoặc dịch vụ xét nghiệm tải lượng HIV của người bệnh có thẻ BHYT được chẩn đoán bệnh HIV.

- Cập nhật kiểm tra trường KET_LUAN trong Xml4
  Bổ sung mã nhóm trong Xml3 bắt buộc phải có KET_LUAN trong Xml4: config.qd130xml.xml4.xml3_ma_nhom_require_ket_luan
  Bổ sung kiểm tra bắt buộc phải có trường KET_LUAN trong Qd130Xml4Checker
- Bổ sung kiểm tra Ngày trả kết quả trong Xml3 đối với DVKT < Ngày y lệnh (Critical)

# 28/07/2024

Cập nhật API tra cứu thẻ BHYT 2024: KQNhanLichSuKCB2024

- Bổ sung thêm config organization.BHYT.hoTenCb và organization.BHYT.cccdCb
- Sửa hàm tra cứu -> chức năng tra cứu thẻ: App\BHYT.php
- Sửa job thực hiện tra cứu khi import hồ sơ: App\Job\jobKtTheBHYT

# 25/05/2024

Cập nhật bổ sung kiểm tra quy tắc Xml4 (Cận lâm sàng)

- Sửa code kiểm tra các quy tắc: Services/Qd130Xml4Checker
- Kiểm tra cấu trúc trường dữ liệu

# 24/07/2024

Cập nhật kiểm tra cấu trúc và tính đúng đắn mã máy trong Xml3

- Mở rộng trường ma_may trong Xml3 thành dạng text
- Sửa code chức năng kiểm tra quy tắc trong Xml3: Services/Qd130Xml3Checker
