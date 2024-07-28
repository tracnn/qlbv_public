# 24/07/2024
Cập nhật kiểm tra cấu trúc và tính đúng đắn mã máy trong Xml3
- Mở rộng trường ma_may trong Xml3 thành dạng text
- Sửa code chức năng kiểm tra quy tắc trong Xml3: Services/Qd130Xml3Checker
# 25/05/2024
Cập nhật bổ sung kiểm tra quy tắc Xml4 (Cận lâm sàng)
- Sửa code kiểm tra các quy tắc: Services/Qd130Xml4Checker
- Kiểm tra cấu trúc trường dữ liệu
# 28/07/2024
Cập nhật API tra cứu thẻ BHYT 2024: KQNhanLichSuKCB2024
- Bổ sung thêm config __tech.BHYT.hoTenCb và __tech.BHYT.cccdCb
- Sửa hàm tra cứu -> chức năng tra cứu thẻ: App\BHYT.php
- Sửa job thực hiện tra cứu khi import hồ sơ: App\Job\jobKtTheBHYT
- Bổ sung thêm config __tech.insurance_code: Thêm các mã lỗi còn thiếu