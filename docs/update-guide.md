# Hướng dẫn update phần mềm Tiền giám định + Dashboard

## Tổng quan

Hệ thống QLBV (Quản lý Bệnh viện) được xây dựng trên Laravel 5.5 với PHP 7.4, MySQL, Redis và Nginx.

## Các bước tiến hành

### Pull source code mới nhất từ Github

- Sử dụng Powershell hoặc Cmd mở với quyền Administrator
- Cd vào thư mục chứa source code đã cài trên máy. Ví dụ C:\qlbv_public
- Gõ lệnh update: Ví dụ C:\qlbv_public>update
- Chờ chạy xong, kiểm tra xem có thông báo lỗi không. Nếu không có thông báo lỗi thì tiếp tục bước chi tiết sau

### Hướng dẫn cập nhật phiên bản mới nhất, bổ sung tiền giám định XML3176 (Phiên bản 20260120.1)

#### 1. Copy nội dung sau trên file /docs/organization.php vào cuối file /config/organazition.php

// Phien ban cap nhat 20260120.1

'xml_3176_not_check'=> false,//Không kiểm tra lỗi trước khi xuất xml

'truc_du_lieu_y_te'=>[

'username'=>'',// Tài khoản được Trục dữ liệu Y Tế cấp

'password'=>'',// Mật khẩu tương ứng

'code'=>'',// Mã đơn vị (maCSKCB)

'loai_ho_so'=>'3176',// Loại hồ sơ

'ma_tinh'=>'HN',// Mã tỉnh

'environment'=>'production',// sandbox hoặc production/poc

// Môi trường thử nghiệm (Sandbox)

'login_url_sandbox'=>'https://sbauth-soyt.hanoi.gov.vn/api/auth/token/take',

'submit_xml_url_sandbox'=>'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml',

'check_status_url_sandbox'=>'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai',

// Môi trường chính thức (POC)

'login_url_production'=>'https://auth-soyt.hanoi.gov.vn/api/auth/token/take',

'submit_xml_url_production'=>'https://axis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml',

'check_status_url_production'=>'https://axis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai',

'enabled'=> false,// Bật/tắt chức năng gửi dữ liệu lên Trục

'disk'=>'trucDuLieuYTe',// Tên disk trong filesystems config

'scan_sleep_interval'=>300,// Thời gian sleep giữa các lần quét (giây)

],

'cong_du_lieu_y_te_dien_bien'=>[

'username'=>'',// Tài khoản được Sở Y tế tỉnh Điện Biên cung cấp

'password'=>'',// Mật khẩu (sẽ được hash MD5 khi gửi)

'login_url'=>'http://api.congdulieuytedienbien.vn/api/token',

'submit_xml_url'=>'http://api.congdulieuytedienbien.vn/api/Cong130/CheckIn',

'enabled'=> false,// Bật/tắt chức năng gửi dữ liệu lên Cổng dữ liệu Y tế tỉnh Điện Biên

'disk'=>'congDuLieuYTeDienBien',// Tên disk trong filesystems config

'scan_sleep_interval'=>300,// Thời gian sleep giữa các lần quét (giây)

],

#### 2. Copy nội dung docs/filesystem.php vào config/filesystem.php và sửa lại cấu hình cho phù hợp

'trucDuLieuYTe'=>[

'driver'=>'local',

'root'=>'D:\XML\TrucDuLieuYTe',

],

'xml3176'=>[

'driver'=>'local',

'root'=>'D:\XML\3176',

],

'xml3176tt'=>[

'driver'=>'local',

'root'=>'D:\XML\3176TT',

],

'xml3176GoogleDrive'=>[

'driver'=>'local',

'root'=>'D:\XML\ImportXml3176',

],

'exportXml3176'=>[

'driver'=>'local',

'root'=>'D:\XML\ExportXml3176',

],

#### 3. Bổ sung cài đặt service bằng cách chạy lệnh sau: install_service

- Tại con trỏ đường dẫn thư mục chứa source code thực hiện lệnh sau: install_service. Ví dụ C:\qlbv_public>install_service

#### 4. Thực hiện câu lệnh: update

- Tại con trỏ dường dẫn thư mục chứa source code thực hiện lệnh sau: update. Ví dụ C:\qlbv_public>update

#### 5. Update danh mục nghề nghiệp cấp 2 phục vụ cho Xml3176

- Khởi động vào trang web Tiền giám định với quyền Super Administator và thực hiện chức năng sau
- Quản lý danh mục / Nhập khẩu danh mục và thực hiện import file Danh-muc-nghe-nghiep-Viet-Nam-cap-2.xlsx trong thư mục docs. Ví dụ C:\qlbv_public\docs\Danh-muc-nghe-nghiep-Viet-Nam-cap-2.xlsx

-- Hoàn thành update --
