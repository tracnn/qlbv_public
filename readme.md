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

# 07/08/2024
- Cập nhật kiểm tra Xml5, thời điểm dbls phải nằm trong khoảng thời gian vào và ra (Critical)

# 15/08/2024
- Cập nhật kiểm tra Xml9 Thông tin trẻ sơ sinh (Critical)

# 23/08/2024
- Bổ sung kiểm tra quy tắc kiểm tra Khoa chỉ định không hợp lệ (Warning)
	+ Khoa khám bệnh (K01) chỉ định dịch vụ/vtyt (xml3) và thuốc (xml2) cho BN nội trú - trái tuyến
	+ Thêm key trong config.qd130xml
	+ Bổ sung quy tắc trong Qd130Xml2Checker và Qd130Xml3Checker
- Bổ sung kiểm tra quy tắc TT_THAU đúng định dạng Gx;Nx trong Xml2 và Xml3 nếu có (Warning)
	+ thêm key trong config.qd130xml
	+ Bổ sung quy tắc infoChecker trong Qd130Xml2Checker và Qd130Xml3Checker
# 24/08/2024
- Bổ sung tự động quét kiểm tra thẻ BHYT đối với BN đang điều trị (His Pro Vietsens)
	+ Bổ sung artisan command HISProKiemTraTheBHYT
	+ Chỉ quét một lần trong suốt quá trình điều trị đối với thẻ đúng
	+ Chỉ thực hiện quét lại đối với thẻ sai