# API HIS Dashboard - Hướng dẫn nhanh

## 🚀 Bắt đầu trong 5 phút

### 1. Thông tin cơ bản
- **Base URL:** `https://your-domain.com/api/`
- **Authentication:** Bearer Token
- **Format:** JSON
- **Rate Limit:** 60 requests/phút

### 2. Lấy token
Liên hệ bộ phận IT để được cấp token truy cập.

### 3. Test API đầu tiên
```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Các API chính

#### Thống kê điều trị
```bash
GET /api/dashboard/treatment-stats?startDate=YYYYMMDDHHmmss&endDate=YYYYMMDDHHmmss
```

#### Thống kê bệnh nhân mới
```bash
GET /api/dashboard/patient-stats?startDate=YYYYMMDDHHmmss&endDate=YYYYMMDDHHmmss
```

#### Thống kê doanh thu
```bash
GET /api/dashboard/revenue-stats?startDate=YYYYMMDDHHmmss&endDate=YYYYMMDDHHmmss
```

#### Chi tiết điều trị (có phân trang)
```bash
GET /api/treatments?startDate=YYYYMMDDHHmmss&endDate=YYYYMMDDHHmmss&dataType=treatment&page=1&limit=10
```

### 5. Format ngày tháng
**Bắt buộc:** `YYYYMMDDHHmmss` (14 chữ số)

**Ví dụ:**
- `20240115000000` = 15/01/2024 00:00:00
- `20240115235959` = 15/01/2024 23:59:59

### 6. Xử lý lỗi thường gặp

| Lỗi | Nguyên nhân | Giải pháp |
|-----|-------------|-----------|
| 401 | Thiếu/sai token | Kiểm tra header Authorization |
| 400 | Format ngày sai | Sử dụng YYYYMMDDHHmmss |
| 429 | Quá nhiều request | Chờ 1 phút rồi thử lại |

### 7. Ví dụ JavaScript
```javascript
const token = 'YOUR_TOKEN';
const response = await fetch('/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959', {
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
});
const data = await response.json();
console.log(data);
```

### 8. Liên hệ hỗ trợ
- **Email:** support@your-domain.com
- **Điện thoại:** 0123-456-789

---
*Xem tài liệu đầy đủ: [API_USER_GUIDE.md](API_USER_GUIDE.md)*
