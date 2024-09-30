# 30/09/2024
- Bổ sung export data BN nợ viện phí dạng xlsx

# 28/09/2024
- Bổ sung báo cáo BN nợ viện phí (thêm thông tin và cải thiện tốc độ xử lý so với HIS)

# 23/09/2024
- Sửa export Xml130 ra thư mục với Ma CSKCB tương ứng trong XmlContent
- Bổ sung lọc Xml4750 theo mã bệnh nhân

# 18/09/2024
- Bổ sung Chức năng kiểm tra hồ sơ Emr
	+ Bổ sung kiểm tra BBHC thuốc có dấu sao
	
# 17/09/2024
- Bổ sung Chức năng kiểm tra hồ sơ Emr
	+ Bổ sung kiểm tra BBHC PTTT
	+ Bổ sung kiểm tra BBHC DVKT
	
# 16/09/2024
- Bổ sung Chức năng kiểm tra hồ sơ Emr
	+ Kiểm tra nợ viện phí
	+ Hiển thị đơn thuốc phòng khám

# 15/09/2024
- Bổ sung Chức năng kiểm tra hồ sơ Emr
	+ Kiểm tra chữ ký của BN trên bảng kê thanh toán

# 12/09/2024
- Bổ sung kiểm tra tính hợp lệ của giấy nghỉ việc hưởng BHXH
	+ Bổ sung Qd130Xml11Checker kiểm tra tu_ngay không được lớn hơn qd130_xml1.ngay_ra
	+ Bổ sung Qd130Xml11Checker kiểm tra den_ngay không được nhỏ hơn qd130_xml1.ngay_ra
	
# 10/09/2024
- Sửa Qd130Xml3Checker 
	+ Bổ sung $this->serviceDisplay ưu tiên lấy ten_vat_tu nếu không có mới lấy ten_dich_vu
	+ Phù hợp với export Xml3 của HisPro Vietsens

# 09/09/2024
- Sửa mail gửi lỗi thẻ BHYT, bổ sung Mã thẻ HISPro của Vietsens trong trường hợp không tra cứu được thông tin
	+ Bổ sung mối quan hệ Models\CheckBHYT\check_hein_card với bảng his_treatment của Hispro
	+ Sửa template gửi email resources\templates\mail-qd130-errors.blade

# 06/09/2024
- Bổ sung chức năng tự động import danh mục cơ sở khám chữa bệnh
	+ Bổ sung fillable trong Models\MedicalOrganization
	+ Tải danh mục đơn vị hành chính từ trang: https://gdbhyt.baohiemxahoi.gov.vn/DM_COSOKCB
	+ Sửa Artisan Command ImportCatalogBHXHFromFiles
		+ Bổ sung kiểm tra cấu trúc file danh mục
		+ Bổ sung thêm case $firstRow === $expectedMedicalOrganizationColumns:
- Sửa Service Check Xml lọc MedicalOrganization với is_active = true
	+ Sửa Service Qd130Xml1Checker

# 04/09/2024
- Bổ sung chức năng tự động import danh mục đơn vị hành chính
	+ Bổ sung fillable trong Models\AdministrativeUnit
	+ Tải danh mục đơn vị hành chính từ trang: https://danhmuchanhchinh.gso.gov.vn/Default.aspx
	+ Sửa Artisan Command ImportCatalogBHXHFromFiles
		+ Bổ sung kiểm tra cấu trúc file danh mục
		+ Bổ sung thêm case $firstRow === $expectedAdministrativeUnitsColumns:
- Sửa Service Check Xml lọc AdministrativeUnit với is_active = true
	+ Sửa Service Qd130Xml1Checker
	
# 27/08/2024
- Tối ưu chức năng tự động quét thẻ BHYT
	+ Đối với những thẻ bị sai thông tin được quy định trong config qd130xml.hein_card_invalid.check_code và qd130xml.hein_card_invalid.result_code thì thực hiện quét lại thẻ BHYT, kể cả không có sự thay đổi thông tin thì vẫn cập nhật updated_at tại thời điểm kiểm tra nhằm mục đích gửi thông báo tới các khoa phòng liên quan để sửa lỗi thông tin thẻ
	+ Sửa job jobKtTheBHYT: phương thức handle() và phương thức addCheckHeinCard()
- Bổ sung kiểm tra tyle_tt_dv và tyle_tt_bh trong Xml2 và Xml3 chỉ được nằm trong khoảng từ 0 đến 100
	+ Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml2Checker
	+ Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml3Checker

# 24/08/2024
- Bổ sung tự động quét kiểm tra thẻ BHYT đối với BN đang điều trị (His Pro Vietsens)
	+ Bổ sung artisan command HISProKiemTraTheBHYT
	+ Chỉ quét một lần trong suốt quá trình điều trị đối với thẻ đúng
	+ Thực hiện quét lại đối với thẻ sai
	+ Cho phép cấu hình thời gian chạy quét bằng task schedule (Windows) hoặc supersivor (Linux/Unix)

# 23/08/2024
- Bổ sung kiểm tra quy tắc kiểm tra Khoa chỉ định không hợp lệ (Warning)
	+ Khoa khám bệnh (K01) chỉ định dịch vụ/vtyt (xml3) và thuốc (xml2) cho BN nội trú - trái tuyến
	+ Thêm key trong config.qd130xml
	+ Bổ sung quy tắc trong Qd130Xml2Checker và Qd130Xml3Checker
- Bổ sung kiểm tra quy tắc TT_THAU đúng định dạng Gx;Nx trong Xml2 và Xml3 nếu có (Warning)
	+ thêm key trong config.qd130xml
	+ Bổ sung quy tắc infoChecker trong Qd130Xml2Checker và Qd130Xml3Checker

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
- Bổ sung thêm config __tech.BHYT.hoTenCb và __tech.BHYT.cccdCb
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