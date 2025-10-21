# Quản lý Email Nhận Báo Cáo

## Tổng quan

Module quản lý email nhận báo cáo cho phép quản trị viên hệ thống cấu hình danh sách email để nhận các loại báo cáo khác nhau theo chu kỳ định sẵn.

## Tính năng

### 1. Quản lý CRUD cơ bản
- **Tạo mới**: Thêm email nhận báo cáo mới
- **Xem danh sách**: Hiển thị tất cả email với bộ lọc và tìm kiếm
- **Xem chi tiết**: Xem thông tin chi tiết của một email
- **Chỉnh sửa**: Cập nhật thông tin email
- **Xóa**: Xóa email khỏi hệ thống (soft delete)

### 2. Quản lý trạng thái
- **Kích hoạt/Vô hiệu hóa**: Thay đổi trạng thái hoạt động của email
- **Bulk actions**: Thực hiện hành động hàng loạt (kích hoạt, vô hiệu hóa, xóa)

### 3. Cấu hình loại báo cáo
- **Báo cáo BHXH** (`bcaobhxh`): Báo cáo liên quan đến bảo hiểm xã hội
- **Báo cáo quản trị** (`bcaoqtri`): Báo cáo quản trị tổng hợp
- **Thống kê chi tiết** (`qtri_tckt`): Báo cáo thống kê chi tiết
- **Hồ sơ đăng ký** (`qtri_hsdt`): Báo cáo hồ sơ đăng ký
- **Dịch vụ kỹ thuật** (`qtri_dvkt`): Báo cáo dịch vụ kỹ thuật
- **Cảnh báo** (`qtri_canhbao`): Báo cáo cảnh báo hệ thống

### 4. Báo cáo đặc thù
- **Có** (`true`): Nhận báo cáo đặc thù (báo cáo quan trọng, khẩn cấp)
- **Không** (`false`): Chỉ nhận báo cáo thông thường theo loại đã chọn

## Cài đặt

### 1. Chạy Migration
```bash
php artisan migrate
```

### 2. Chạy Seeder (tùy chọn)
```bash
php artisan db:seed --class=EmailReceiveReportSeeder
```

## Sử dụng

### 1. Truy cập module
- URL: `/email-receive-reports`
- Yêu cầu quyền: `superadministrator`

### 2. Thêm email mới
1. Click nút "Thêm mới"
2. Điền thông tin:
   - Tên người nhận (bắt buộc)
   - Địa chỉ email (bắt buộc, phải unique)
   - Trạng thái kích hoạt
   - Nhận báo cáo đặc thù (tùy chọn)
   - Chọn loại báo cáo muốn nhận
3. Click "Lưu"

### 3. Tìm kiếm và lọc
- **Tìm kiếm**: Theo tên hoặc email
- **Lọc theo trạng thái**: Hoạt động/Không hoạt động
- **Lọc theo báo cáo đặc thù**: Có/Không
- **Lọc theo loại báo cáo**: Chọn loại báo cáo cụ thể

### 4. Thao tác hàng loạt
1. Chọn các email bằng checkbox
2. Chọn hành động (Kích hoạt/Vô hiệu hóa/Xóa)
3. Click "Thực hiện"

## API

### Lấy danh sách email theo loại báo cáo
```
GET /email-receive-reports/api/get-emails-by-report-type
```

**Parameters:**
- `report_type`: Loại báo cáo (bcaobhxh, bcaoqtri, qtri_tckt, qtri_hsdt, qtri_dvkt, qtri_canhbao, special)
- `include_special`: Bao gồm email nhận báo cáo đặc thù (true/false)

**Response:**
```json
{
    "success": true,
    "data": {
        "Tên người nhận": "email@example.com"
    }
}
```

## Model Methods

### Static Methods
- `getEmailsForBHXHReport($includeSpecial)`: Lấy email cho báo cáo BHXH
- `getEmailsForAdminReport($includeSpecial)`: Lấy email cho báo cáo quản trị
- `getEmailsForDetailStats($includeSpecial)`: Lấy email cho thống kê chi tiết
- `getEmailsForRegistrationFiles($includeSpecial)`: Lấy email cho hồ sơ đăng ký
- `getEmailsForTechnicalServices($includeSpecial)`: Lấy email cho dịch vụ kỹ thuật
- `getEmailsForAlerts($includeSpecial)`: Lấy email cho cảnh báo
- `getEmailsForSpecialReport()`: Lấy email nhận báo cáo đặc thù

### Scopes
- `active()`: Lấy các email đang hoạt động
- `forReportType($reportType)`: Lấy email theo loại báo cáo
- `forSpecialReport()`: Lấy email nhận báo cáo đặc thù

## Database Schema

### Bảng: `email_receive_reports`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | bigint | Primary key |
| name | varchar(255) | Tên người nhận |
| email | varchar(255) | Địa chỉ email (unique) |
| active | boolean | Trạng thái hoạt động |
| bcaobhxh | boolean | Nhận báo cáo BHXH |
| bcaoqtri | boolean | Nhận báo cáo quản trị |
| qtri_tckt | boolean | Nhận thống kê chi tiết |
| qtri_hsdt | boolean | Nhận hồ sơ đăng ký |
| qtri_dvkt | boolean | Nhận dịch vụ kỹ thuật |
| qtri_canhbao | boolean | Nhận cảnh báo |
| period | boolean | Nhận báo cáo đặc thù (true/false) |
| created_at | timestamp | Thời gian tạo |
| updated_at | timestamp | Thời gian cập nhật |
| deleted_at | timestamp | Thời gian xóa (soft delete) |

## Validation Rules

### Tạo mới
- `name`: required, string, max:255
- `email`: required, email, max:255, unique
- `active`: boolean
- `bcaobhxh`: boolean
- `bcaoqtri`: boolean
- `qtri_tckt`: boolean
- `qtri_hsdt`: boolean
- `qtri_dvkt`: boolean
- `qtri_canhbao`: boolean
- `period`: boolean

### Cập nhật
- Tương tự như tạo mới, nhưng email có thể trùng với chính nó

## Security

- Chỉ user có role `superadministrator` mới có thể truy cập
- Sử dụng middleware `checkrole:superadministrator`
- Validation đầy đủ cho tất cả input
- Soft delete để bảo vệ dữ liệu

## Troubleshooting

### Lỗi thường gặp

1. **Email đã tồn tại**
   - Kiểm tra email có bị trùng không
   - Sử dụng email khác hoặc cập nhật email hiện có

2. **Không thể truy cập**
   - Kiểm tra user có role `superadministrator` không
   - Đăng nhập lại nếu cần

3. **Validation errors**
   - Kiểm tra tất cả trường bắt buộc đã điền chưa
   - Kiểm tra format email có đúng không
   - Kiểm tra các trường boolean có đúng giá trị không

## Tương lai

- [ ] Thêm tính năng gửi email test
- [ ] Thêm lịch sử gửi email
- [ ] Thêm template email tùy chỉnh
- [ ] Thêm báo cáo thống kê gửi email
- [ ] Tích hợp với hệ thống notification
