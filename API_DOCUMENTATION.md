# HIS Dashboard API Documentation

## Table of Contents
1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Authentication](#authentication)
4. [Rate Limiting](#rate-limiting)
5. [Request/Response Format](#requestresponse-format)
6. [Error Handling](#error-handling)
7. [API Endpoints](#api-endpoints)
8. [Data Models](#data-models)
9. [Examples](#examples)
10. [SDKs and Libraries](#sdks-and-libraries)
11. [Support](#support)

## Overview

The HIS Dashboard API provides comprehensive healthcare statistics and reporting data from the Hospital Information System (HIS) for the Ministry of Health. This RESTful API is built on Laravel 5.5 framework and leverages existing business logic from `HomeController` and `DashboardController`.

### Key Features
- **Real-time Statistics**: Get up-to-date healthcare statistics
- **Multiple Data Views**: Treatment, patient, revenue, and transaction data
- **Flexible Filtering**: Date range and pagination support
- **Raw Data Format**: Unprocessed data for custom analysis
- **RESTful Design**: Standard HTTP methods and status codes

## Getting Started

### Base URL
```
https://your-domain.com/api/
```

### API Information
- **Version:** 1.0
- **Protocol:** HTTPS
- **Content-Type:** application/json
- **Character Encoding:** UTF-8
- **Framework:** Laravel 5.5

### Quick Start
```bash
# Test API connectivity with authentication
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

## Authentication

The API uses **Bearer Token Authentication** for secure access. All API requests must include a valid access token in the Authorization header.

### Authentication Method
- **Type:** Bearer Token
- **Header:** `Authorization: Bearer {access_token}`
- **Token Source:** Pre-configured in `config/organization.php`

### How to Use Authentication

#### 1. Include Token in Request Headers
```bash
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

#### 2. JavaScript Example
```javascript
const response = await fetch('/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer your-access-token-here'
  }
});
```

#### 3. Python Example
```python
import requests

headers = {
    'Accept': 'application/json',
    'Authorization': 'Bearer your-access-token-here'
}

response = requests.get(
    'https://your-domain.com/api/dashboard/treatment-stats',
    params={'startDate': '20240101000000', 'endDate': '20240131235959'},
    headers=headers
)
```

### Token Configuration

The access token is configured in `config/organization.php`:

```php
return [
    // ... other configurations
    'api' => [
        'access_token' => 'your-secure-access-token-here',
        'token_name' => 'HIS-API-Token',
        'description' => 'Token for Ministry of Health API access'
    ],
    // ... rest of configuration
];
```

### Security Features

#### 1. Token Validation
- ✅ **Format Check:** Validates Bearer token format
- ✅ **Token Match:** Compares with configured token
- ✅ **Case Sensitive:** Exact token match required

#### 2. Error Responses
```json
// Missing Authorization Header
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Authorization header is required",
    "details": "Please include 'Authorization: Bearer {token}' in your request headers"
  },
  "meta": {
    "timestamp": "20240115103000",
    "request_id": "req_123456"
  }
}

// Invalid Token
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED", 
    "message": "Invalid access token",
    "details": "The provided token is not valid or has expired"
  },
  "meta": {
    "timestamp": "20240115103000",
    "request_id": "req_123456"
  }
}
```

### HTTP Status Codes
| Status Code | Description |
|-------------|-------------|
| 401 | Unauthorized - Missing or invalid token |
| 403 | Forbidden - Valid token but insufficient permissions |

### Best Practices

#### 1. Token Security
- ✅ **Keep Token Secret:** Never expose token in client-side code
- ✅ **Use HTTPS:** Always use secure connections
- ✅ **Rotate Token:** Change token periodically for security
- ✅ **Monitor Usage:** Track API access for security audit

#### 2. Implementation Tips
- ✅ **Environment Variables:** Consider using environment variables for production
- ✅ **Token Logging:** Log authentication attempts for monitoring
- ✅ **Rate Limiting:** Combine with rate limiting for additional security

### Token Management

#### For System Administrators:
1. **Generate Secure Token:**
   ```bash
   # Generate a secure random token
   openssl rand -hex 32
   ```

2. **Update Configuration:**
   ```php
   // In config/organization.php
   'api' => [
       'access_token' => 'a1b2c3d4e5f6...', // Your generated token
       'token_name' => 'HIS-API-Token',
       'description' => 'Token for Ministry of Health API access'
   ]
   ```

3. **Distribute Token:**
   - Share token securely with authorized users
   - Provide clear usage instructions
   - Document token expiration policy

#### For API Consumers:
1. **Store Token Securely:** Never hardcode in client applications
2. **Use Environment Variables:** Store token in secure environment variables
3. **Implement Error Handling:** Handle 401/403 responses appropriately
4. **Monitor Usage:** Track API calls and handle rate limits

## Rate Limiting

- **Limit:** 60 requests per minute per IP address
- **Implementation:** Laravel throttle middleware
- **Headers:** Rate limit information is included in response headers

### Rate Limit Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

## Request/Response Format

### Standard Response Structure

#### Success Response
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_count": 123,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
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
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_123456"
  }
}
```

#### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid date format",
    "details": {
      "startDate": ["The start date field is required."]
    }
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_123456"
  }
}
```

### Request Parameters

#### Date Format
- **Format:** YYYYMMDDHHmmss (bắt buộc)
- **Examples:** 
  - `20240115000000` (2024-01-15 00:00:00)
  - `20240115235959` (2024-01-15 23:59:59)
  - `20240115083000` (2024-01-15 08:30:00)
- **Timezone:** Server timezone (Asia/Ho_Chi_Minh)
- **Validation:** 
  - Phải đúng format 14 chữ số
  - endDate phải >= startDate
  - Không chấp nhận format khác

#### Pagination Parameters
- **page:** Page number (default: 1, minimum: 1)
- **limit:** Records per page (default: 10, maximum: 100)

#### Common Query Parameters
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |
| page | integer | No | Page number for pagination | 1 |
| limit | integer | No | Number of records per page | 10 |

## Error Handling

### HTTP Status Codes
| Status Code | Description |
|-------------|-------------|
| 200 | OK - Request successful |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Authentication required |
| 404 | Not Found - Resource not found |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

### Error Codes
| Code | Description |
|------|-------------|
| VALIDATION_ERROR | Request validation failed |
| INTERNAL_ERROR | Internal server error |
| NOT_FOUND | Resource not found |
| UNAUTHORIZED | Authentication required |

### Error Response Example
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The start date field is required.",
    "details": {
      "startDate": ["The start date field is required."]
    }
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_123456"
  }
}
```

## API Endpoints

### 1. Dashboard Statistics APIs

#### GET /api/dashboard/treatment-stats
Retrieves treatment statistics grouped by patient type.

**Endpoint:** `GET /api/dashboard/treatment-stats`

**Description:** Returns aggregated treatment data categorized by patient type (BHYT, Dịch vụ, Miễn phí, etc.) for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Request Examples:**
```bash
# Lấy dữ liệu cả tháng 1/2024
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"

# Lấy dữ liệu trong giờ làm việc
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240101080000&endDate=20240131173000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"

# Lấy dữ liệu trong ngày cụ thể
curl -X GET "https://your-domain.com/api/dashboard/treatment-stats?startDate=20240115000000&endDate=20240115235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_treatments": 175,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
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
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_123456"
  }
}
```

**Response Fields:**
| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Indicates if the request was successful |
| data.summary.total_treatments | integer | Total number of treatments in the period |
| data.summary.period.start_date | string | Start date of the reporting period |
| data.summary.period.end_date | string | End date of the reporting period |
| data.data[].patient_type_name | string | Name of the patient type |
| data.data[].count | integer | Number of treatments for this patient type |

---

#### GET /api/dashboard/patient-stats
Retrieves new patient statistics grouped by branch.

**Endpoint:** `GET /api/dashboard/patient-stats`

**Description:** Returns aggregated data of new patients categorized by hospital branch for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_new_patients": 150,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "branch_name": "Chi nhánh 1",
        "count": 80
      },
      {
        "branch_name": "Chi nhánh 2",
        "count": 70
      }
    ]
  }
}
```

---

#### GET /api/dashboard/revenue-stats
Retrieves revenue statistics grouped by service type.

**Endpoint:** `GET /api/dashboard/revenue-stats`

**Description:** Returns aggregated revenue data categorized by service type for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_revenue": 50000000,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "service_type_name": "Khám bệnh",
        "service_type_id": 1,
        "amount": 1000,
        "revenue": 25000000
      },
      {
        "service_type_name": "Xét nghiệm",
        "service_type_id": 2,
        "amount": 500,
        "revenue": 15000000
      }
    ]
  }
}
```

---

#### GET /api/dashboard/transaction-stats
Retrieves transaction statistics grouped by multiple criteria.

**Endpoint:** `GET /api/dashboard/transaction-stats`

**Description:** Returns comprehensive transaction data categorized by cashiers, transaction types, payment forms, departments, and treatment types.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": {
      "cashiers": [
        {"name": "Thu ngân 1", "y": 1000000},
        {"name": "Thu ngân 2", "y": 800000}
      ],
      "transaction_types": [
        {"name": "Thanh toán", "y": 1500000},
        {"name": "Hoàn tiền", "y": 300000}
      ],
      "pay_forms": [
        {"name": "Tiền mặt", "y": 1000000},
        {"name": "Chuyển khoản", "y": 800000}
      ],
      "departments": [
        {"name": "Khoa Nội", "y": 900000},
        {"name": "Khoa Ngoại", "y": 700000}
      ],
      "treatment_types": [
        {"name": "Nội trú", "y": 1200000},
        {"name": "Ngoại trú", "y": 600000}
      ]
    }
  }
}
```

---

#### GET /api/dashboard/inpatient-stats
Retrieves inpatient treatment statistics grouped by department.

**Endpoint:** `GET /api/dashboard/inpatient-stats`

**Description:** Returns aggregated inpatient treatment data categorized by department for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_inpatient_treatments": 200,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "department_name": "Khoa Nội",
        "department_id": 1,
        "count": 120
      },
      {
        "department_name": "Khoa Ngoại",
        "department_id": 2,
        "count": 80
      }
    ]
  }
}
```

---

#### GET /api/dashboard/outpatient-stats
Retrieves outpatient treatment statistics grouped by department.

**Endpoint:** `GET /api/dashboard/outpatient-stats`

**Description:** Returns aggregated outpatient treatment data categorized by department for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_outpatient_treatments": 500,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "department_name": "Khoa Nội",
        "department_id": 1,
        "count": 300
      },
      {
        "department_name": "Khoa Ngoại",
        "department_id": 2,
        "count": 200
      }
    ]
  }
}
```

---

#### GET /api/dashboard/average-inpatient-days
Retrieves average inpatient days statistics.

**Endpoint:** `GET /api/dashboard/average-inpatient-days`

**Description:** Returns average number of days patients stay in the hospital for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "average_days": 5.2,
      "total_patients": 150,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "day_count": 3,
        "treatment_day_count": 3,
        "in_time": "20240101080000",
        "clinical_in_time": "20240101080000",
        "out_time": "20240103120000",
        "treatment_code": "T202401001"
      }
    ]
  }
}
```

### 2. Detail Data APIs

#### GET /api/treatments
Retrieves detailed treatment records with pagination.

**Endpoint:** `GET /api/treatments`

**Description:** Returns detailed treatment records based on the specified data type and date range with pagination support.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |
| dataType | string | Yes | Type of data (treatment, newpatient, noitru, ravien-kham, ravien, chuyenvien, ravien-noitru, ravien-ngoaitru) | treatment |
| page | integer | No | Page number for pagination | 1 |
| limit | integer | No | Number of records per page | 10 |

**Data Type Options:**
| Value | Description |
|-------|-------------|
| treatment | All treatments in the period |
| newpatient | New patients in the period |
| noitru | Inpatient treatments |
| ravien-kham | Outpatient examinations |
| ravien | All discharged patients |
| chuyenvien | Transferred patients |
| ravien-noitru | Discharged inpatients |
| ravien-ngoaitru | Discharged outpatients |

**Request Example:**
```bash
curl -X GET "https://your-domain.com/api/treatments?startDate=20240101000000&endDate=20240131235959&dataType=treatment&page=1&limit=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

**Response Schema:**
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
      "per_page": 50,
      "total": 150,
      "last_page": 3
    }
  }
}
```

---

#### GET /api/services
Retrieves detailed service records with pagination.

**Endpoint:** `GET /api/services`

**Description:** Returns detailed service records based on the specified data type and date range with pagination support.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |
| dataType | string | No | Type of service (phauthuat, thuthuat) | phauthuat |
| page | integer | No | Page number for pagination | 1 |
| limit | integer | No | Number of records per page | 10 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "tdl_treatment_code": "T202401001",
        "tdl_patient_code": "P001",
        "tdl_patient_name": "Nguyễn Văn A",
        "in_time": "20240101080000",
        "out_time": "20240103120000",
        "tdl_service_name": "Phẫu thuật tim",
        "intruction_time": "20240101090000",
        "request_username": "doctor01"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 100,
      "last_page": 2
    }
  }
}
```

### 3. Service-specific APIs

#### GET /api/services/by-type/{id}
Retrieves service statistics by service type and status.

**Endpoint:** `GET /api/services/by-type/{id}`

**Description:** Returns service statistics grouped by status for a specific service type.

**Path Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| id | integer | Yes | Service type ID | 1 |

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Request Example:**
```bash
curl -X GET "https://your-domain.com/api/services/by-type/1?startDate=20240101000000&endDate=20240131235959" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-access-token-here"
```

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_services": 200,
      "service_type_id": 1,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "status_id": 1,
        "status_name": "Chưa thực hiện",
        "count": 50
      },
      {
        "status_id": 2,
        "status_name": "Đang thực hiện",
        "count": 30
      },
      {
        "status_id": 3,
        "status_name": "Đã thực hiện",
        "count": 120
      }
    ]
  }
}
```

---

#### GET /api/examinations/paraclinical
Retrieves paraclinical examination data.

**Endpoint:** `GET /api/examinations/paraclinical`

**Description:** Returns paraclinical examination data with timing information for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "branch_name": "Chi nhánh 1",
        "service_req_type_name": "Xét nghiệm",
        "instruction_time": "20240101090000",
        "start_time": "20240101091000",
        "finish_time": "20240101092000"
      }
    ]
  }
}
```

---

#### GET /api/examinations/imaging
Retrieves diagnostic imaging data.

**Endpoint:** `GET /api/examinations/imaging`

**Description:** Returns diagnostic imaging data with timing information for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "branch_name": "Chi nhánh 1",
        "diim_type_name": "X-quang",
        "instruction_time": "20240101090000",
        "start_time": "20240101091000",
        "finish_time": "20240101092000"
      }
    ]
  }
}
```

---

#### GET /api/examinations/prescription
Retrieves prescription data.

**Endpoint:** `GET /api/examinations/prescription`

**Description:** Returns prescription data with timing information for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "branch_name": "Chi nhánh 1",
        "instruction_time": "20240101090000",
        "start_time": "20240101091000",
        "finish_time": "20240101092000"
      }
    ]
  }
}
```

---

#### GET /api/examinations/fee
Retrieves fee data.

**Endpoint:** `GET /api/examinations/fee`

**Description:** Returns fee data with timing information for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "branch_name": "Chi nhánh 1",
        "instruction_time": "20240101090000",
        "start_time": "20240101091000",
        "finish_time": "20240101092000"
      }
    ]
  }
}
```

---

#### GET /api/examinations/by-room
Retrieves examination data grouped by room and status.

**Endpoint:** `GET /api/examinations/by-room`

**Description:** Returns examination data categorized by room and status for the specified date range.

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| startDate | string | Yes | Start date in YYYYMMDDHHmmss format | 20240101000000 |
| endDate | string | Yes | End date in YYYYMMDDHHmmss format (>= startDate) | 20240131235959 |

**Response Schema:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_examinations": 500,
      "period": {
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
      }
    },
    "data": [
      {
        "room_name": "Phòng khám 1",
        "status_id": 1,
        "status_name": "Chưa thực hiện",
        "count": 20
      },
      {
        "room_name": "Phòng khám 1",
        "status_id": 2,
        "status_name": "Đang thực hiện",
        "count": 15
      },
      {
        "room_name": "Phòng khám 1",
        "status_id": 3,
        "status_name": "Đã thực hiện",
        "count": 65
      }
    ]
  }
}
```

## Data Models

### Treatment Model
| Field | Type | Description |
|-------|------|-------------|
| treatment_code | string | Unique treatment identifier |
| tdl_patient_code | string | Patient code |
| tdl_patient_name | string | Patient name |
| in_time | string | Admission time (YYYYMMDDHHMMSS) |
| out_time | string | Discharge time (YYYYMMDDHHMMSS) |
| icd_code | string | ICD diagnosis code |
| icd_name | string | ICD diagnosis name |

### Service Model
| Field | Type | Description |
|-------|------|-------------|
| tdl_treatment_code | string | Treatment code |
| tdl_patient_code | string | Patient code |
| tdl_patient_name | string | Patient name |
| tdl_service_name | string | Service name |
| intruction_time | string | Instruction time (YYYYMMDDHHMMSS) |
| request_username | string | Requesting user |

### Examination Model
| Field | Type | Description |
|-------|------|-------------|
| branch_name | string | Branch name |
| instruction_time | string | Instruction time (YYYYMMDDHHMMSS) |
| start_time | string | Start time (YYYYMMDDHHMMSS) |
| finish_time | string | Finish time (YYYYMMDDHHMMSS) |

## Examples

### JavaScript Example
```javascript
// Fetch treatment statistics
async function getTreatmentStats(startDate, endDate, accessToken) {
  try {
    const response = await fetch(
      `https://your-domain.com/api/dashboard/treatment-stats?startDate=${startDate}&endDate=${endDate}`,
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${accessToken}`
        }
      }
    );
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Treatment stats:', data.data);
      return data.data;
    } else {
      console.error('API Error:', data.error);
      throw new Error(data.error.message);
    }
  } catch (error) {
    console.error('Request failed:', error);
    throw error;
  }
}

// Usage
const accessToken = 'your-access-token-here';
getTreatmentStats('20240101000000', '20240131235959', accessToken)
  .then(data => {
    console.log('Total treatments:', data.summary.total_treatments);
    data.data.forEach(item => {
      console.log(`${item.patient_type_name}: ${item.count}`);
    });
  })
  .catch(error => {
    console.error('Error:', error);
  });
```

### Python Example
```python
import requests
import json

def get_treatment_stats(start_date, end_date, access_token):
    """Fetch treatment statistics from the API"""
    url = "https://your-domain.com/api/dashboard/treatment-stats"
    params = {
        'startDate': start_date,
        'endDate': end_date
    }
    headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': f'Bearer {access_token}'
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
        print(f"Request failed: {e}")
        raise
    except json.JSONDecodeError as e:
        print(f"JSON decode error: {e}")
        raise

# Usage
access_token = 'your-access-token-here'
try:
    stats = get_treatment_stats('20240101000000', '20240131235959', access_token)
    print(f"Total treatments: {stats['summary']['total_treatments']}")
    
    for item in stats['data']:
        print(f"{item['patient_type_name']}: {item['count']}")
        
except Exception as e:
    print(f"Error: {e}")
```

### PHP Example
```php
<?php
function getTreatmentStats($startDate, $endDate, $accessToken) {
    $url = "https://your-domain.com/api/dashboard/treatment-stats";
    $params = http_build_query([
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP error: " . $httpCode);
    }
    
    $data = json_decode($response, true);
    
    if (!$data['success']) {
        throw new Exception("API Error: " . $data['error']['message']);
    }
    
    return $data['data'];
}

// Usage
$accessToken = 'your-access-token-here';
try {
    $stats = getTreatmentStats('20240101000000', '20240131235959', $accessToken);
    echo "Total treatments: " . $stats['summary']['total_treatments'] . "\n";
    
    foreach ($stats['data'] as $item) {
        echo $item['patient_type_name'] . ": " . $item['count'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```

## SDKs and Libraries

### Postman Collection
Import the following collection to test the API endpoints:

```json
{
  "info": {
    "name": "HIS Dashboard API",
    "description": "Healthcare statistics and reporting API",
    "version": "1.0"
  },
  "variable": [
    {
      "key": "baseUrl",
      "value": "https://your-domain.com/api"
    },
    {
      "key": "accessToken",
      "value": "your-access-token-here"
    }
  ],
  "item": [
    {
      "name": "Treatment Stats",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          },
          {
            "key": "Authorization",
            "value": "Bearer {{accessToken}}"
          }
        ],
        "url": {
          "raw": "{{baseUrl}}/dashboard/treatment-stats?startDate=20240101000000&endDate=20240131235959",
          "host": ["{{baseUrl}}"],
          "path": ["dashboard", "treatment-stats"],
          "query": [
            {
              "key": "startDate",
              "value": "20240101000000"
            },
            {
              "key": "endDate",
              "value": "20240131235959"
            }
          ]
        }
      }
    }
  ]
}
```

## Support

### Contact Information
- **Technical Support:** support@your-domain.com
- **Documentation:** https://your-domain.com/docs
- **Status Page:** https://status.your-domain.com

### Common Issues

#### Date Format Issues
**Problem:** Invalid date format error
**Solution:** Ensure dates are in YYYY-MM-DD format (e.g., 2024-01-15)

#### Rate Limiting
**Problem:** 429 Too Many Requests
**Solution:** Implement exponential backoff and respect rate limits

#### Large Date Ranges
**Problem:** Slow response times
**Solution:** Use smaller date ranges or implement pagination

### Changelog

#### Version 1.0 (2024-01-15)
- Initial release
- Dashboard statistics APIs
- Detail data APIs
- Service-specific APIs
- Raw data format
- Pagination support

---

**Last Updated:** January 15, 2024  
**API Version:** 1.0  
**Documentation Version:** 1.0
