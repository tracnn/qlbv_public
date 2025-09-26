# Tài liệu giới thiệu hệ thống Hỗ trợ điều hành

## 📋 Tổng quan hệ thống

**Hệ thống Hỗ trợ điều hành** là một hệ thống quản lý bảo hiểm y tế toàn diện được xây dựng trên nền tảng Laravel 5.5, tích hợp với hệ thống HIS (Hospital Information System) để quản lý và xử lý các hoạt động liên quan đến bảo hiểm y tế trong bệnh viện.

### 🎯 Mục tiêu chính
- Quản lý và kiểm soát các hoạt động khám chữa bệnh có BHYT
- Tích hợp với hệ thống HIS để đồng bộ dữ liệu
- Cung cấp các báo cáo thống kê chi tiết
- Hỗ trợ quản lý hồ sơ XML theo quy định của BHYT
- Kiểm tra và xác thực thông tin thẻ BHYT
- Dashboard tổng quan hoạt động của Cơ sở khám chữa bệnh
- Tra cứu và kiểm soát hồ sơ bệnh án điện tử
- Tra cứu kết quả cho người bệnh

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
- **Dashboard tổng quan**: Giao diện tổng quan với các chỉ số quan trọng
- **Biểu đồ trực quan**: Biểu đồ trực quan hóa dữ liệu theo thời gian thực
- **Thống kê chi tiết**: Các chỉ số về số lượt khám, bệnh nhân mới, ra viện, chuyển viện
- **Phân tích dịch vụ**: Thống kê theo loại dịch vụ (khám, xét nghiệm, chẩn đoán hình ảnh, thủ thuật, phẫu thuật)
- **Báo cáo doanh thu**: Theo dõi doanh thu theo các nguồn và thời gian
- **Cập nhật thời gian thực**: Dữ liệu được cập nhật liên tục

### 2. **Cập nhật dữ liệu**

#### 🏥 Khám sức khỏe
- Quản lý hồ sơ khám sức khỏe định kỳ
- Theo dõi kết quả khám sức khỏe
- Báo cáo tình trạng sức khỏe

#### 📋 Quản lý xếp hàng
- **Đăng ký số thứ tự**: Bệnh nhân đăng ký số thứ tự khám bệnh qua điện thoại
- **Quản lý hàng đợi**: Phân bổ bệnh nhân theo phòng khám và khoa
- **Thông báo SMS**: Gửi thông báo số thứ tự qua SMS cho bệnh nhân
- **Theo dõi thời gian chờ**: Quản lý và theo dõi thời gian chờ đợi của bệnh nhân
- **In vé số**: Hỗ trợ in vé số thứ tự cho bệnh nhân

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
- **Trả kết quả cho bệnh nhân**: Cung cấp kết quả khám chữa bệnh qua web
- **QRCode thanh toán**: Tạo mã QR để thanh toán viện phí
- **Tra cứu lịch sử khám bệnh**: Bệnh nhân tra cứu lịch sử khám chữa bệnh
- **Xem chi tiết hồ sơ**: Hiển thị chi tiết hồ sơ bệnh án điện tử
- **Gộp PDF**: Tự động gộp các tài liệu PDF thành một file
- **Bảo mật thông tin**: Mã hóa token và giới hạn thời gian truy cập

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
- **Quản lý người dùng**: Quản lý người dùng và phân quyền chi tiết
- **Cấu hình kết nối**: Cấu hình kết nối với các hệ thống khác
- **Nhắc việc tự động**: Hệ thống tự động nhắc nhở các công việc cần thực hiện
- **Kiểm tra lỗi hệ thống**: Theo dõi và báo cáo lỗi hệ thống
- **Quản lý hàng đợi**: Kiểm tra và quản lý các job trong hàng đợi
- **Upload XML**: Tải lên và xử lý các file XML
- **Tham số hệ thống**: Cấu hình các tham số vận hành hệ thống

### 12. **Thu ngân**

#### 💰 Quản lý thanh toán
- **Thanh toán viện phí**: Xử lý thanh toán cho bệnh nhân
- **Tạm ứng/Hoàn ứng**: Quản lý tạm ứng và hoàn ứng tiền
- **Báo cáo thu tiền**: Thống kê tình hình thu tiền viện phí
- **QR Code thanh toán**: Tạo mã QR để thanh toán
- **Broadcast thông báo**: Gửi thông báo thanh toán thời gian thực
- **Export báo cáo**: Xuất báo cáo thanh toán ra Excel

### 13. **Điều dưỡng**

#### 👩‍⚕️ Thực hiện y lệnh
- **Ghi nhận y lệnh**: Ghi nhận việc thực hiện y lệnh của điều dưỡng
- **Theo dõi tình trạng**: Theo dõi tình trạng thực hiện y lệnh
- **Báo cáo kết quả**: Báo cáo kết quả thực hiện y lệnh
- **Quản lý thuốc**: Theo dõi việc thực hiện y lệnh thuốc

### 14. **Tra cứu lịch sử khám chữa bệnh**

#### 🔍 QHisPlus
- **Tra cứu lịch sử**: Tra cứu lịch sử khám chữa bệnh của bệnh nhân
- **Chi tiết hồ sơ**: Xem chi tiết từng lần khám chữa bệnh
- **Tìm kiếm đa tiêu chí**: Tìm kiếm theo mã BN, mã ĐT, CCCD, số điện thoại
- **Hiển thị thông tin**: Hiển thị đầy đủ thông tin bệnh nhân và quá trình điều trị

---

## 🔧 Tính năng kỹ thuật

### 🔐 Bảo mật
- Xác thực người dùng với JWT
- Phân quyền chi tiết theo vai trò
- Mã hóa dữ liệu nhạy cảm

### 📊 API Dashboard
- **15 API endpoints** cung cấp dữ liệu thống kê
- **Rate limiting** 60 requests/phút
- **Hỗ trợ phân trang** và sắp xếp
- **Định dạng JSON** chuẩn
- **API thống kê điều trị**: Số lượt khám, bệnh nhân mới, ra viện
- **API thống kê doanh thu**: Doanh thu theo thời gian và nguồn
- **API dịch vụ kỹ thuật**: Thống kê theo loại dịch vụ
- **API nội trú/ngoại trú**: Phân tích hoạt động điều trị

### 🔄 Tích hợp
- **Kết nối HIS**: Kết nối với hệ thống HIS để đồng bộ dữ liệu
- **Đồng bộ thời gian thực**: Dữ liệu được cập nhật liên tục
- **Tích hợp BHYT**: Kết nối với hệ thống BHYT quốc gia
- **Pusher realtime**: Thông báo thời gian thực qua Pusher
- **SMS Integration**: Tích hợp gửi SMS thông báo
- **Email Reports**: Gửi báo cáo tự động qua email
- **PACS Integration**: Kết nối với hệ thống PACS để xem hình ảnh

### 📱 Giao diện
- **Responsive design**: Tương thích với mọi thiết bị
- **Dashboard trực quan**: Giao diện thân thiện với người dùng
- **Đa ngôn ngữ**: Hỗ trợ tiếng Việt
- **QR Code**: Tích hợp tạo và hiển thị mã QR
- **PDF Viewer**: Xem tài liệu PDF trực tiếp trên web
- **DataTables**: Bảng dữ liệu với tìm kiếm và phân trang
- **Real-time updates**: Cập nhật dữ liệu thời gian thực

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

- **Email**: support@hothotro.com
- **Hotline**: 1900-xxxx
- **Tài liệu**: Xem các file API_README.md, API_USER_GUIDE.md, API_QUICK_START_GUIDE.md

---

*Tài liệu này được cập nhật thường xuyên để phản ánh các tính năng mới nhất của hệ thống.*
