# Tài liệu giới thiệu hệ thống QLBV (Quản lý Bảo hiểm Y tế)

## 📋 Tổng quan hệ thống

**Hệ thống QLBV** là một hệ thống quản lý bảo hiểm y tế toàn diện được xây dựng trên nền tảng Laravel 5.5, tích hợp với hệ thống HIS (Hospital Information System) để quản lý và xử lý các hoạt động liên quan đến bảo hiểm y tế trong bệnh viện.

### 🎯 Mục tiêu chính
- Quản lý và kiểm soát các hoạt động khám chữa bệnh có BHYT
- Tích hợp với hệ thống HIS để đồng bộ dữ liệu
- Cung cấp các báo cáo thống kê chi tiết
- Hỗ trợ quản lý hồ sơ XML theo quy định của BHYT
- Kiểm tra và xác thực thông tin thẻ BHYT

---

## 🏥 Các chức năng chính

### 1. **Kế hoạch tổng hợp**

#### 📊 Thống kê
- **Số lượt khám**: Thống kê số lượng bệnh nhân khám theo thời gian
- **Chi phí khám bệnh**: Báo cáo chi phí điều trị theo các tiêu chí
- **Nhập viện theo phòng khám**: Thống kê bệnh nhân nhập viện theo từng phòng khám
- **Nhập viện theo khoa**: Thống kê bệnh nhân nội trú theo khoa phòng
- **Bệnh nhân COVID-19**: Theo dõi và thống kê bệnh nhân dương tính SARS-CoV-2
- **Ngoại trú**: Thống kê hoạt động khám ngoại trú
- **Nội trú**: Thống kê hoạt động điều trị nội trú
- **Doanh thu**: Báo cáo doanh thu theo các nguồn
- **Gia tăng chi phí theo NĐ75**: Thống kê chi phí tăng theo nghị định 75

#### 🔍 Kiểm soát nghiệp vụ
- **Nhắc việc**: Hệ thống nhắc nhở các công việc cần thực hiện
- **Xét nghiệm - Chẩn đoán**: Quản lý và theo dõi kết quả xét nghiệm, chẩn đoán hình ảnh

#### 📈 Dashboard
- Giao diện tổng quan với các chỉ số quan trọng
- Biểu đồ trực quan hóa dữ liệu
- Cập nhật thời gian thực

### 2. **Cập nhật dữ liệu**

#### 🏥 Khám sức khỏe
- Quản lý hồ sơ khám sức khỏe định kỳ
- Theo dõi kết quả khám sức khỏe
- Báo cáo tình trạng sức khỏe

#### 📋 Quản lý xếp hàng
- Quản lý số thứ tự khám bệnh
- Phân bổ bệnh nhân theo phòng khám
- Theo dõi thời gian chờ đợi

### 3. **Tiêm chủng**

#### 📚 Danh mục
- **Danh mục Vaccines**: Quản lý danh sách các loại vắc-xin
- **Danh sách bệnh nhân**: Quản lý thông tin bệnh nhân tiêm chủng

#### 💉 Danh sách tiêm chủng
- Ghi nhận lịch sử tiêm chủng
- Theo dõi lịch tiêm nhắc lại
- Báo cáo tiêm chủng

### 4. **Bệnh án điện tử (EMR)**

#### 🔍 Kiểm tra hồ sơ
- **Kiểm tra hồ sơ chi tiết**: Xem xét và kiểm tra hồ sơ bệnh án điện tử
- **Danh sách hồ sơ bệnh án**: Quản lý danh sách các hồ sơ
- **Quản lý hồ sơ chuyển BHXH**: Xử lý hồ sơ chuyển cơ quan BHXH

#### 🔎 Tra soát hồ sơ
- Kiểm tra tính hợp lệ của hồ sơ
- Phát hiện các lỗi trong hồ sơ
- Đề xuất sửa chữa

#### 📄 Trả kết quả
- **Trả kết quả cho bệnh nhân**: Cung cấp kết quả khám chữa bệnh
- **QRCode thanh toán**: Tạo mã QR để thanh toán viện phí

#### 📊 Báo cáo thống kê
- **Báo cáo nộp tiền**: Thống kê tình hình thu tiền viện phí

### 5. **Thẻ BHYT**

#### 🔍 Tra cứu thẻ BHYT
- Kiểm tra tính hợp lệ của thẻ BHYT
- Xác thực thông tin bệnh nhân
- Kiểm tra quyền lợi BHYT

#### 💊 Tra cứu Thuốc - Thầu
- Tìm kiếm thông tin thuốc trong danh mục BHYT
- Kiểm tra giá thuốc và điều kiện thanh toán

### 6. **Hồ sơ XML**

#### 📁 XML 4750 (Quyết định 130)
- **Danh sách hồ sơ**: Quản lý các hồ sơ XML 4750
- **Nhập khẩu hồ sơ**: Import dữ liệu từ file XML

#### 📁 XML 4210
- **Danh sách hồ sơ**: Quản lý các hồ sơ XML 4210
- **Nhập khẩu hồ sơ**: Import dữ liệu từ file XML

### 7. **Điều dưỡng**

#### 👩‍⚕️ Thực hiện y lệnh
- Ghi nhận việc thực hiện y lệnh của điều dưỡng
- Theo dõi tình trạng thực hiện
- Báo cáo kết quả thực hiện

### 8. **Quản lý danh mục**

#### 📚 Danh mục BHYT
- **DM thuốc BHYT**: Danh mục các loại thuốc được BHYT chi trả
- **DM Vật tư y tế**: Danh mục vật tư y tế
- **DM Dịch vụ kỹ thuật**: Danh mục các dịch vụ kỹ thuật
- **DM Nhân viên y tế**: Danh mục cán bộ y tế
- **DM Khoa Phòng Giường**: Danh mục khoa phòng và giường bệnh
- **DM Trang thiết bị**: Danh mục thiết bị y tế
- **DM lỗi Xml 4750**: Danh mục các lỗi thường gặp trong XML

#### 🔧 Danh mục khác
- **DVKT có điều kiện**: Dịch vụ kỹ thuật có điều kiện đặc biệt
- **Thuốc có điều kiện**: Thuốc có điều kiện sử dụng
- **Danh mục Khoa phòng**: Quản lý thông tin khoa phòng
- **Nhập khẩu danh mục**: Import các danh mục từ file

### 9. **Báo cáo thống kê**

#### 📈 Báo cáo chuyên môn
- **Thống kê dịch vụ kỹ thuật**: Báo cáo sử dụng các dịch vụ kỹ thuật
- **Báo cáo sử dụng thuốc**: Thống kê việc sử dụng thuốc
- **SL Khám và Chi phí theo PK**: Số lượng khám và chi phí theo phòng khám
- **SL Loại thuốc theo đơn**: Thống kê loại thuốc được kê đơn

#### 💰 Báo cáo tài chính
- **Báo cáo thu tiền (HIS)**: Thống kê thu tiền từ hệ thống HIS
- **Danh sách nợ viện phí**: Báo cáo bệnh nhân nợ viện phí
- **Báo cáo doanh thu**: Tổng hợp doanh thu theo các nguồn

#### 👥 Báo cáo bệnh nhân
- **Danh sách BN PT**: Danh sách bệnh nhân phẫu thuật
- **Số lượng BN theo khoa**: Thống kê số lượng bệnh nhân theo khoa
- **Tra cứu LS KCB**: Tra cứu lịch sử khám chữa bệnh

### 10. **Hồ sơ bệnh án**

#### 📋 Quản lý hồ sơ
- **Danh sách**: Quản lý danh sách hồ sơ bệnh án
- Theo dõi tình trạng xử lý hồ sơ
- Báo cáo tình hình hồ sơ

### 11. **Thiết lập hệ thống**

#### ⚙️ Cấu hình
- **Kiểm tra chi tiết**: Kiểm tra và cấu hình các thông số hệ thống
- Quản lý người dùng và phân quyền
- Cấu hình kết nối với các hệ thống khác

---

## 🔧 Tính năng kỹ thuật

### 🔐 Bảo mật
- Xác thực người dùng với JWT
- Phân quyền chi tiết theo vai trò
- Mã hóa dữ liệu nhạy cảm

### 📊 API Dashboard
- 15 API endpoints cung cấp dữ liệu thống kê
- Rate limiting 60 requests/phút
- Hỗ trợ phân trang và sắp xếp
- Định dạng JSON chuẩn

### 🔄 Tích hợp
- Kết nối với hệ thống HIS
- Đồng bộ dữ liệu thời gian thực
- Tích hợp với hệ thống BHYT quốc gia

### 📱 Giao diện
- Responsive design
- Dashboard trực quan
- Hỗ trợ đa ngôn ngữ (Tiếng Việt)

---

## 👥 Đối tượng sử dụng

### 🏥 Cán bộ y tế
- Bác sĩ, điều dưỡng
- Cán bộ quản lý khoa phòng
- Nhân viên kế toán

### 👨‍💼 Quản lý
- Ban giám đốc bệnh viện
- Trưởng khoa phòng
- Cán bộ quản lý chất lượng

### 💼 Cán bộ BHYT
- Cán bộ giám định BHYT
- Nhân viên xử lý hồ sơ
- Cán bộ thống kê báo cáo

---

## 🚀 Lợi ích

### ✅ Hiệu quả quản lý
- Tự động hóa các quy trình
- Giảm thiểu sai sót
- Tăng tốc độ xử lý

### 📈 Báo cáo chính xác
- Dữ liệu thời gian thực
- Báo cáo đa chiều
- Phân tích xu hướng

### 💰 Tiết kiệm chi phí
- Giảm chi phí vận hành
- Tối ưu hóa nguồn lực
- Tăng doanh thu

### 🛡️ Tuân thủ quy định
- Đảm bảo tuân thủ quy định BHYT
- Kiểm soát chất lượng dịch vụ
- Minh bạch trong quản lý

---

## 📞 Hỗ trợ

Để được hỗ trợ sử dụng hệ thống, vui lòng liên hệ:

- **Email**: support@qlbv.com
- **Hotline**: 1900-xxxx
- **Tài liệu**: Xem các file API_README.md, API_USER_GUIDE.md, API_QUICK_START_GUIDE.md

---

*Tài liệu này được cập nhật thường xuyên để phản ánh các tính năng mới nhất của hệ thống.*
