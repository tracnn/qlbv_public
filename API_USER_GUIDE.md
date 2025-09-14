# Hướng dẫn sử dụng API HIS Dashboard - Sở Y tế

## 📋 Mục lục
1. [Giới thiệu](#giới-thiệu)
2. [Thông tin cơ bản](#thông-tin-cơ-bản)
3. [Xác thực (Authentication)](#xác-thực-authentication)
4. [Hướng dẫn sử dụng](#hướng-dẫn-sử-dụng)
5. [Các API endpoints](#các-api-endpoints)
6. [Ví dụ thực tế](#ví-dụ-thực-tế)
7. [Xử lý lỗi](#xử-lý-lỗi)
8. [FAQ - Câu hỏi thường gặp](#faq---câu-hỏi-thường-gặp)
9. [Liên hệ hỗ trợ](#liên-hệ-hỗ-trợ)

---

## 🏥 Giới thiệu

**API HIS Dashboard** là hệ thống cung cấp dữ liệu thống kê y tế từ Hệ thống Thông tin Bệnh viện (HIS) cho Sở Y tế. API này cho phép truy xuất dữ liệu thống kê về:

- 🏥 **Thống kê điều trị** theo loại bệnh nhân
- 👥 **Thống kê bệnh nhân mới** theo chi nhánh
- 💰 **Thống kê doanh thu** theo loại dịch vụ
- 🏥 **Thống kê nội trú/ngoại trú** theo khoa
- 📈 **Thống kê giao dịch** theo nhiều tiêu chí
- 🔬 **Thống kê khám cận lâm sàng** và chẩn đoán hình ảnh

### Lợi ích
- ✅ **Dữ liệu thời gian thực** từ hệ thống HIS
- ✅ **Định dạng chuẩn** dễ xử lý và phân tích
- ✅ **Bảo mật cao** với token authentication
- ✅ **Tốc độ nhanh** với rate limiting hợp lý
- ✅ **Dễ tích hợp** với các hệ thống báo cáo

---

## 🔧 Thông tin cơ bản

### Base URL
```
https://your-domain.com/api/
```

### Thông tin kỹ thuật
- **Phiên bản:** 1.0
- **Giao thức:** HTTPS
- **Định dạng dữ liệu:** JSON
- **Mã hóa:** UTF-8
- **Framework:** Laravel 5.5

### Giới hạn sử dụng
- **Rate Limit:** 60 requests/phút cho mỗi IP
- **Timeout:** 30 giây cho mỗi request
- **Kích thước response:** Tối đa 10MB

---

## 🔐 Xác thực (Authentication)

### Yêu cầu bắt buộc
Tất cả các API calls đều **BẮT BUỘC** phải có token xác thực.

### Cách sử dụng token
Thêm header `Authorization` vào mọi request:

```bash
Authorization: Bearer your-access-token-here
```

### Ví dụ cơ bản
```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

### Lỗi xác thực thường gặp
| Lỗi | Nguyên nhân | Giải pháp |
|-----|-------------|-----------|
| 401 Unauthorized | Thiếu token | Thêm header Authorization |
| 401 Invalid token | Token sai | Kiểm tra lại token |
| 401 Wrong format | Format header sai | Sử dụng "Bearer {token}" |

---

## 📖 Hướng dẫn sử dụng

### 1. Định dạng ngày tháng
**Bắt buộc sử dụng format:** `YYYYMMDDHHmmss` (14 chữ số)

#### Ví dụ:
- ✅ **Đúng:** `20240115000000` (15/01/2024 00:00:00)
- ✅ **Đúng:** `20240115235959` (15/01/2024 23:59:59)
- ❌ **Sai:** `2024-01-15` (thiếu giờ phút giây)
- ❌ **Sai:** `15/01/2024` (format không đúng)

#### Công cụ chuyển đổi:
```javascript
// JavaScript
const date = new Date('2024-01-15 08:30:00');
const formatted = date.getFullYear() + 
  String(date.getMonth() + 1).padStart(2, '0') + 
  String(date.getDate()).padStart(2, '0') + 
  String(date.getHours()).padStart(2, '0') + 
  String(date.getMinutes()).padStart(2, '0') + 
  String(date.getSeconds()).padStart(2, '0');
// Kết quả: 20240115083000
```

### 2. Cấu trúc response chuẩn
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
    "data": [
      {
        "field_name": "value",
        "count": 123
      }
    ]
  },
  "meta": {
    "timestamp": "20240115103000",
    "request_id": "req_123456"
  }
}
```

### 3. Phân trang (Pagination)
Một số API hỗ trợ phân trang:

```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

**Tham số phân trang:**
- `page`: Số trang (mặc định: 1)
- `limit`: Số bản ghi/trang (mặc định: 10, tối đa: 100)

---

## 🎯 Các API endpoints

### 1. Dashboard Statistics APIs

#### 1.1 Thống kê điều trị
**Endpoint:** `GET /api/dashboard/treatment-stats`

**Mô tả:** Thống kê số lượng điều trị theo loại bệnh nhân (BHYT, Dịch vụ, Miễn phí...)

**Tham số:**
- `startDate` (bắt buộc): Ngày bắt đầu (YYYYMMDDHHmmss)
- `endDate` (bắt buộc): Ngày kết thúc (YYYYMMDDHHmmss)

**Ví dụ:**
```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-token"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_treatments": 175,
      "period": {
        "start_date": "20240101000000",
        "end_date": "20240131235959"
      }
    },
    "data": [
      {
        "patient_type_name": "BHYT",
        "count": 100
      },
      {
        "patient_type_name": "Dịch vụ",
        "count": 50
      },
      {
        "patient_type_name": "Miễn phí",
        "count": 25
      }
    ]
  }
}
```

#### 1.2 Thống kê bệnh nhân mới
**Endpoint:** `GET /api/dashboard/patient-stats`

**Mô tả:** Thống kê số lượng bệnh nhân mới theo chi nhánh

**Ví dụ:**
```bash
curl -X GET "https://your-domain.com/api/dashboard/patient-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-token"
```

#### 1.3 Thống kê doanh thu
**Endpoint:** `GET /api/dashboard/revenue-stats`

**Mô tả:** Thống kê doanh thu theo loại dịch vụ

**Ví dụ:**
```bash
curl -X GET "https://your-domain.com/api/dashboard/revenue-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-token"
```

#### 1.4 Thống kê nội trú
**Endpoint:** `GET /api/dashboard/inpatient-stats`

**Mô tả:** Thống kê điều trị nội trú theo khoa

#### 1.5 Thống kê ngoại trú
**Endpoint:** `GET /api/dashboard/outpatient-stats`

**Mô tả:** Thống kê điều trị ngoại trú theo khoa

#### 1.6 Thống kê giao dịch
**Endpoint:** `GET /api/dashboard/transaction-stats`

**Mô tả:** Thống kê giao dịch theo thu ngân, loại giao dịch, hình thức thanh toán

#### 1.7 Thống kê số ngày nằm viện trung bình
**Endpoint:** `GET /api/dashboard/average-inpatient-days`

**Mô tả:** Thống kê số ngày nằm viện trung bình của bệnh nhân nội trú

### 2. Detail Data APIs

#### 2.1 Chi tiết điều trị
**Endpoint:** `GET /api/treatments`

**Mô tả:** Lấy danh sách chi tiết các ca điều trị với phân trang

**Tham số:**
- `startDate` (bắt buộc): Ngày bắt đầu
- `endDate` (bắt buộc): Ngày kết thúc
- `dataType` (bắt buộc): Loại dữ liệu
- `page` (tùy chọn): Số trang (mặc định: 1)
- `limit` (tùy chọn): Số bản ghi/trang (mặc định: 10)

**Các loại dataType:**
| Giá trị | Mô tả |
|---------|-------|
| `treatment` | Tất cả điều trị trong khoảng thời gian |
| `newpatient` | Bệnh nhân mới |
| `noitru` | Điều trị nội trú |
| `ravien-kham` | Ra viện khám |
| `ravien` | Tất cả ra viện |
| `chuyenvien` | Chuyển viện |
| `ravien-noitru` | Ra viện nội trú |
| `ravien-ngoaitru` | Ra viện ngoại trú |

**Ví dụ:**
```bash
curl -X GET "https://your-domain.com/api/treatments?startDate=20240101000000&endDate=20240131235959&dataType=treatment&page=1&limit=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-token"
```

#### 2.2 Chi tiết dịch vụ
**Endpoint:** `GET /api/services`

**Mô tả:** Lấy danh sách chi tiết các dịch vụ với phân trang

**Tham số:**
- `startDate` (bắt buộc): Ngày bắt đầu
- `endDate` (bắt buộc): Ngày kết thúc
- `dataType` (tùy chọn): Loại dịch vụ (`phauthuat`, `thuthuat`)
- `page` (tùy chọn): Số trang
- `limit` (tùy chọn): Số bản ghi/trang

### 3. Service-specific APIs

#### 3.1 Dịch vụ theo loại
**Endpoint:** `GET /api/services/by-type/{id}`

**Mô tả:** Thống kê dịch vụ theo loại và trạng thái

**Tham số:**
- `id` (path): ID loại dịch vụ
- `startDate` (bắt buộc): Ngày bắt đầu
- `endDate` (bắt buộc): Ngày kết thúc

#### 3.2 Khám cận lâm sàng
**Endpoint:** `GET /api/examinations/paraclinical`

**Mô tả:** Dữ liệu khám cận lâm sàng với thông tin thời gian

#### 3.3 Chẩn đoán hình ảnh
**Endpoint:** `GET /api/examinations/imaging`

**Mô tả:** Dữ liệu chẩn đoán hình ảnh với thông tin thời gian

#### 3.4 Đơn thuốc
**Endpoint:** `GET /api/examinations/prescription`

**Mô tả:** Dữ liệu đơn thuốc với thông tin thời gian

#### 3.5 Phí dịch vụ
**Endpoint:** `GET /api/examinations/fee`

**Mô tả:** Dữ liệu phí dịch vụ với thông tin thời gian

#### 3.6 Khám theo phòng
**Endpoint:** `GET /api/examinations/by-room`

**Mô tả:** Dữ liệu khám theo phòng và trạng thái

---

## 💡 Ví dụ thực tế

### Ví dụ 1: Lấy thống kê điều trị tháng 1/2024

```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token"
```

**Kết quả:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_treatments": 1250,
      "period": {
        "start_date": "20240101000000",
        "end_date": "20240131235959"
      }
    },
    "data": [
      {
        "patient_type_name": "BHYT",
        "count": 800
      },
      {
        "patient_type_name": "Dịch vụ",
        "count": 300
      },
      {
        "patient_type_name": "Miễn phí",
        "count": 150
      }
    ]
  }
}
```

### Ví dụ 2: Lấy chi tiết điều trị nội trú với phân trang

```bash
curl -X GET "https://your-domain.com/api/treatments?startDate=20240101000000&endDate=20240131235959&dataType=noitru&page=1&limit=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token"
```

**Kết quả:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "treatment_code": "T202401001",
        "tdl_patient_code": "P001",
        "tdl_patient_name": "Nguyễn Văn A",
        "in_time": "20240101080000",
        "out_time": "20240103120000",
        "icd_code": "A00",
        "icd_name": "Tả"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 5,
      "total": 150,
      "last_page": 30
    }
  }
}
```

### Ví dụ 3: Sử dụng JavaScript

```javascript
async function getTreatmentStats(startDate, endDate, token) {
  try {
    const response = await fetch(
      `https://your-domain.com/api/dashboard/treatment-stats?startDate=${startDate}&endDate=${endDate}`,
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      }
    );
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Tổng số điều trị:', data.data.summary.total_treatments);
      data.data.data.forEach(item => {
        console.log(`${item.patient_type_name}: ${item.count} ca`);
      });
      return data.data;
    } else {
      throw new Error(data.error.message);
    }
  } catch (error) {
    console.error('Lỗi:', error);
    throw error;
  }
}

// Sử dụng
const token = 'your-access-token';
getTreatmentStats('20240101000000', '20240131235959', token)
  .then(data => {
    // Xử lý dữ liệu
    console.log('Dữ liệu thống kê:', data);
  })
  .catch(error => {
    console.error('Lỗi khi lấy dữ liệu:', error);
  });
```

### Ví dụ 4: Sử dụng Python

```python
import requests
import json

def get_treatment_stats(start_date, end_date, token):
    """Lấy thống kê điều trị từ API"""
    url = "https://your-domain.com/api/dashboard/treatment-stats"
    params = {
        'startDate': start_date,
        'endDate': end_date
    }
    headers = {
        'Accept': 'application/json',
        'Authorization': f'Bearer {token}'
    }
    
    try:
        response = requests.get(url, params=params, headers=headers)
        response.raise_for_status()
        
        data = response.json()
        
        if data['success']:
            return data['data']
        else:
            raise Exception(f"API Error: {data['error']['message']}")
            
    except requests.exceptions.RequestException as e:
        print(f"Lỗi request: {e}")
        raise
    except json.JSONDecodeError as e:
        print(f"Lỗi JSON: {e}")
        raise

# Sử dụng
token = 'your-access-token'
try:
    stats = get_treatment_stats('20240101000000', '20240131235959', token)
    print(f"Tổng số điều trị: {stats['summary']['total_treatments']}")
    
    for item in stats['data']:
        print(f"{item['patient_type_name']}: {item['count']} ca")
        
except Exception as e:
    print(f"Lỗi: {e}")
```

---

## ⚠️ Xử lý lỗi

### Các mã lỗi thường gặp

#### 1. Lỗi xác thực (401 Unauthorized)
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Authorization header is required",
    "details": "Please include 'Authorization: Bearer {token}' in your request headers"
  }
}
```

**Giải pháp:**
- Kiểm tra header Authorization
- Đảm bảo token đúng format: `Bearer {token}`
- Liên hệ để lấy token mới nếu cần

#### 2. Lỗi tham số (400 Bad Request)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid date format",
    "details": "startDate phải có format YYYYMMDDHHmmss"
  }
}
```

**Giải pháp:**
- Kiểm tra format ngày tháng (YYYYMMDDHHmmss)
- Đảm bảo endDate >= startDate
- Kiểm tra các tham số bắt buộc

#### 3. Lỗi rate limit (429 Too Many Requests)
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests",
    "details": "Rate limit: 60 requests per minute"
  }
}
```

**Giải pháp:**
- Giảm tần suất gọi API
- Implement retry với delay
- Sử dụng pagination cho dữ liệu lớn

#### 4. Lỗi server (500 Internal Server Error)
```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Internal server error",
    "details": "An unexpected error occurred"
  }
}
```

**Giải pháp:**
- Thử lại sau vài phút
- Liên hệ hỗ trợ kỹ thuật
- Kiểm tra logs nếu có quyền

### Cách xử lý lỗi trong code

#### JavaScript
```javascript
async function callAPI(url, token) {
  try {
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });
    
    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Token không hợp lệ hoặc đã hết hạn');
      } else if (response.status === 429) {
        throw new Error('Vượt quá giới hạn request, vui lòng thử lại sau');
      } else if (response.status >= 500) {
        throw new Error('Lỗi server, vui lòng thử lại sau');
      } else {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    }
    
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error.message);
    }
    
    return data.data;
  } catch (error) {
    console.error('Lỗi API:', error.message);
    throw error;
  }
}
```

#### Python
```python
import requests
import time

def call_api_with_retry(url, token, max_retries=3):
    """Gọi API với retry logic"""
    for attempt in range(max_retries):
        try:
            headers = {
                'Accept': 'application/json',
                'Authorization': f'Bearer {token}'
            }
            
            response = requests.get(url, headers=headers)
            
            if response.status_code == 401:
                raise Exception('Token không hợp lệ hoặc đã hết hạn')
            elif response.status_code == 429:
                if attempt < max_retries - 1:
                    time.sleep(60)  # Chờ 1 phút
                    continue
                else:
                    raise Exception('Vượt quá giới hạn request')
            elif response.status_code >= 500:
                if attempt < max_retries - 1:
                    time.sleep(5)  # Chờ 5 giây
                    continue
                else:
                    raise Exception('Lỗi server')
            
            response.raise_for_status()
            data = response.json()
            
            if not data['success']:
                raise Exception(data['error']['message'])
            
            return data['data']
            
        except requests.exceptions.RequestException as e:
            if attempt == max_retries - 1:
                raise Exception(f'Lỗi request: {e}')
            time.sleep(2)
    
    raise Exception('Đã thử tối đa số lần')
```

---

## ❓ FAQ - Câu hỏi thường gặp

### Q1: Làm sao để lấy token truy cập?
**A:** Token được cung cấp bởi bộ phận IT của bệnh viện. Liên hệ để được cấp token mới.

### Q2: Token có hết hạn không?
**A:** Hiện tại token không có thời hạn, nhưng có thể được thay đổi định kỳ vì lý do bảo mật.

### Q3: Tại sao phải dùng format ngày YYYYMMDDHHmmss?
**A:** Đây là format chuẩn của hệ thống HIS, đảm bảo tính nhất quán và tránh nhầm lẫn múi giờ.

### Q4: Có thể lấy dữ liệu theo giờ không?
**A:** Có, bạn có thể chỉ định giờ cụ thể trong format ngày. Ví dụ: `20240115080000` (8:00 sáng).

### Q5: Làm sao để lấy tất cả dữ liệu mà không bị giới hạn?
**A:** Sử dụng pagination với tham số `page` và `limit`. Lấy từng trang một cho đến hết.

### Q6: API có hỗ trợ filter theo khoa không?
**A:** Một số API trả về dữ liệu theo khoa, nhưng không có tham số filter trực tiếp. Bạn có thể filter ở phía client.

### Q7: Dữ liệu được cập nhật theo thời gian thực không?
**A:** Dữ liệu được lấy trực tiếp từ database HIS, nên gần như thời gian thực (có độ trễ vài phút).

### Q8: Có thể lấy dữ liệu của nhiều tháng cùng lúc không?
**A:** Có, chỉ cần mở rộng khoảng thời gian trong `startDate` và `endDate`.

### Q9: Làm sao để tối ưu tốc độ truy vấn?
**A:** 
- Sử dụng khoảng thời gian hợp lý (không quá dài)
- Sử dụng pagination cho dữ liệu lớn
- Cache dữ liệu ở phía client
- Tránh gọi API quá thường xuyên

### Q10: Có API nào để lấy danh sách các loại dịch vụ không?
**A:** Hiện tại chưa có API riêng, nhưng bạn có thể xem các giá trị trong response của API services để biết các loại dịch vụ.

---

## 📞 Liên hệ hỗ trợ

### Thông tin liên hệ
- **Email hỗ trợ kỹ thuật:** support@your-domain.com
- **Điện thoại:** 0123-456-789
- **Thời gian hỗ trợ:** 8:00 - 17:00 (Thứ 2 - Thứ 6)

### Khi nào cần liên hệ
- ✅ Gặp lỗi 500 (Internal Server Error)
- ✅ Token không hoạt động
- ✅ Cần thêm API mới
- ✅ Có thắc mắc về dữ liệu
- ✅ Cần hỗ trợ tích hợp

### Thông tin cần cung cấp khi liên hệ
- Mô tả chi tiết vấn đề
- URL và tham số request
- Response lỗi (nếu có)
- Thời gian xảy ra lỗi
- Request ID (nếu có)

### Tài liệu bổ sung
- **API Documentation:** [Link đến tài liệu chi tiết]
- **Postman Collection:** [Link download]
- **Changelog:** [Link đến changelog]

---

## 📝 Changelog

### Version 1.0 (2024-01-15)
- ✅ Initial release
- ✅ 15 API endpoints
- ✅ Bearer token authentication
- ✅ Rate limiting (60 requests/minute)
- ✅ Comprehensive error handling
- ✅ Pagination support
- ✅ Raw data format

---

**Cập nhật lần cuối:** 15/01/2024  
**Phiên bản tài liệu:** 1.0  
**Phiên bản API:** 1.0

---

*Tài liệu này được tạo để hỗ trợ Sở Y tế sử dụng API HIS Dashboard một cách hiệu quả và an toàn.*
