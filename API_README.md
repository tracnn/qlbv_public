# HIS Dashboard API

## 📋 Tổng quan

**HIS Dashboard API** là hệ thống cung cấp dữ liệu thống kê y tế từ Hệ thống Thông tin Bệnh viện (HIS) cho Sở Y tế. API được xây dựng trên Laravel 5.5 framework với kiến trúc RESTful.

## 🚀 Tính năng chính

- ✅ **15 API endpoints** cung cấp dữ liệu thống kê y tế
- ✅ **Bearer Token Authentication** bảo mật cao
- ✅ **Rate limiting** 60 requests/phút
- ✅ **Pagination** cho dữ liệu lớn
- ✅ **Raw data format** dễ xử lý
- ✅ **Comprehensive error handling**

## 📚 Tài liệu

### 📖 [API_USER_GUIDE.md](API_USER_GUIDE.md) - Hướng dẫn sử dụng chính
Tài liệu đầy đủ cho Sở Y tế và người dùng cuối:
- Hướng dẫn xác thực
- Chi tiết 15 API endpoints
- Ví dụ thực tế (cURL, JavaScript, Python)
- Xử lý lỗi và FAQ

### 🚀 [API_QUICK_START_GUIDE.md](API_QUICK_START_GUIDE.md) - Hướng dẫn nhanh
Bắt đầu sử dụng API trong 5 phút:
- Thông tin cơ bản
- Test API đầu tiên
- Các API chính
- Xử lý lỗi thường gặp

### 🔧 [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Tài liệu kỹ thuật
Tài liệu kỹ thuật chi tiết cho developers:
- API Reference đầy đủ
- Request/Response schemas
- Data models
- SDKs và Libraries

## 🏥 API Endpoints

### Dashboard Statistics
- `GET /api/dashboard/treatment-stats` - Thống kê điều trị
- `GET /api/dashboard/patient-stats` - Thống kê bệnh nhân mới
- `GET /api/dashboard/revenue-stats` - Thống kê doanh thu
- `GET /api/dashboard/transaction-stats` - Thống kê giao dịch
- `GET /api/dashboard/inpatient-stats` - Thống kê nội trú
- `GET /api/dashboard/outpatient-stats` - Thống kê ngoại trú
- `GET /api/dashboard/average-inpatient-days` - Số ngày nằm viện TB

### Detail Data
- `GET /api/treatments` - Chi tiết điều trị (có phân trang)
- `GET /api/services` - Chi tiết dịch vụ (có phân trang)

### Service-specific
- `GET /api/services/by-type/{id}` - Dịch vụ theo loại
- `GET /api/examinations/paraclinical` - Khám cận lâm sàng
- `GET /api/examinations/imaging` - Chẩn đoán hình ảnh
- `GET /api/examinations/prescription` - Đơn thuốc
- `GET /api/examinations/fee` - Phí dịch vụ
- `GET /api/examinations/by-room` - Khám theo phòng

## 🔐 Authentication

Sử dụng Bearer Token Authentication:

```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token"
```

## 📅 Format ngày tháng

**Bắt buộc:** `YYYYMMDDHHmmss` (14 chữ số)

**Ví dụ:**
- `20240115000000` = 15/01/2024 00:00:00
- `20240115235959` = 15/01/2024 23:59:59

## 🛠️ Cài đặt và triển khai

### Yêu cầu hệ thống
- PHP >= 7.1
- Laravel 5.5
- PostgreSQL (HISPro database)
- MySQL (Application database)

### Cài đặt
```bash
# Clone repository
git clone <repository-url>
cd qlbv

# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Configure API token in config/organization.php
```

### Cấu hình API Token
Trong `config/organization.php`:
```php
'api' => [
    'access_token' => 'your-secure-access-token-here',
    'token_name' => 'HIS-API-Token',
    'description' => 'Token for Ministry of Health API access'
],
```

## 📊 Response Format

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_count": 123,
      "period": {
        "start_date": "20240101000000",
        "end_date": "20240131235959"
      }
    },
    "data": [...]
  },
  "meta": {
    "timestamp": "20240115103000",
    "request_id": "req_123456"
  }
}
```

## ⚠️ Error Handling

| Status Code | Description |
|-------------|-------------|
| 200 | OK - Request successful |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Missing or invalid token |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

## 📞 Hỗ trợ

- **Email:** support@your-domain.com
- **Điện thoại:** 0123-456-789
- **Thời gian:** 8:00 - 17:00 (Thứ 2 - Thứ 6)

## 📝 Changelog

### Version 1.0 (2024-01-15)
- ✅ Initial release
- ✅ 15 API endpoints
- ✅ Bearer token authentication
- ✅ Rate limiting (60 requests/minute)
- ✅ Comprehensive error handling
- ✅ Pagination support
- ✅ Raw data format

## 📄 License

This project is proprietary software developed for the Ministry of Health.

---

**Cập nhật lần cuối:** 15/01/2024  
**Phiên bản:** 1.0  
**Framework:** Laravel 5.5
