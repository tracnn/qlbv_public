# Hướng dẫn sử dụng Service Cổng dữ liệu Y tế tỉnh Điện Biên

## 1. Cấu hình

Cấu hình trong file `config/organization.php`:

```php
'cong_du_lieu_y_te_dien_bien' => [
    'username' => 'your_username', // Tài khoản được Sở Y tế tỉnh Điện Biên cung cấp
    'password' => 'your_password', // Mật khẩu (sẽ được hash MD5 tự động)
    'login_url' => 'http://api.congdulieuytedienbien.vn/api/token',
    'submit_xml_url' => 'http://api.congdulieuytedienbien.vn/api/Cong130/CheckIn',
    'enabled' => true, // Bật/tắt chức năng
    'disk' => 'congDuLieuYTeDienBien', // Tên disk trong filesystems config
    'scan_sleep_interval' => 300, // Thời gian sleep giữa các lần quét (giây)
],
```

## 2. Sử dụng Login Service

### 2.1. Đăng nhập và lấy token

```php
use App\Services\CongDuLieuYTeDienBienLoginService;

$loginService = new CongDuLieuYTeDienBienLoginService();

// Đăng nhập và lấy token
$tokens = $loginService->login();
// Kết quả: ['access_token' => '...', 'token_type' => 'Bearer', 'username' => '...']

// Lấy access token (tự động đăng nhập lại nếu hết hạn)
$accessToken = $loginService->getAccessToken();

// Lấy toàn bộ thông tin token
$tokens = $loginService->getTokens();

// Kiểm tra đã đăng nhập chưa
$isLoggedIn = $loginService->isLoggedIn();

// Đăng xuất (xóa token khỏi cache)
$loginService->logout();
```

### 2.2. Xử lý lỗi

```php
try {
    $tokens = $loginService->login();
} catch (\Exception $e) {
    // Xử lý lỗi: thiếu config, sai thông tin đăng nhập, lỗi API...
    Log::error('Login failed: ' . $e->getMessage());
}
```

## 3. Sử dụng XML Submit Service

### 3.1. Gửi hồ sơ XML

```php
use App\Services\CongDuLieuYTeDienBienXmlSubmitService;

$submitService = new CongDuLieuYTeDienBienXmlSubmitService();

// Đọc nội dung XML từ file hoặc tạo XML
$xmlContent = file_get_contents('path/to/hoso.xml');

// Gửi XML lên hệ thống
$result = $submitService->submitXml($xmlContent);

// Kiểm tra kết quả
if ($result['success']) {
    // Thành công (status 200, trangThai = 1)
    $maGiaoDich = $result['maGiaoDich'];
    echo "Gửi thành công. Mã giao dịch: {$maGiaoDich}";
} else {
    // Có lỗi
    if (isset($result['maLoi'])) {
        echo "Lỗi: {$result['maLoi']}";
    } else {
        echo "Lỗi hệ thống: " . ($result['error'] ?? 'Unknown error');
    }
}
```

### 3.2. Cấu trúc kết quả trả về

**Trường hợp thành công (status 200, trangThai = 1):**
```php
[
    'maGiaoDich' => '95b243d0-3786-4dfb-88cc-2f57ed4a197f',
    'trangThai' => 1,
    'maLoi' => null,
    'success' => true,
    'statusCode' => 200,
]
```

**Trường hợp hồ sơ lỗi (status 201, trangThai = 2):**
```php
[
    'maGiaoDich' => '558a01c8-2447-4fa7-a9f0-b689649d1948',
    'trangThai' => 2,
    'maLoi' => 'Lỗi hồ sơ thứ 16: (Lỗi cấu trúc xml2 - SO_CCCD (011041000111)...',
    'success' => false,
    'statusCode' => 201,
]
```

**Trường hợp lỗi hệ thống (status 400, 401, 500):**
```php
[
    'statusCode' => 400,
    'error' => 'Error message',
    'body' => '...',
    'success' => false,
]
```

### 3.3. Xử lý lỗi

```php
try {
    $result = $submitService->submitXml($xmlContent);
    
    if (!$result['success']) {
        // Xử lý lỗi hồ sơ hoặc lỗi hệ thống
        if (isset($result['maLoi'])) {
            // Lỗi hồ sơ (trangThai = 2)
            Log::warning('XML validation error', ['maLoi' => $result['maLoi']]);
        } else {
            // Lỗi hệ thống
            Log::error('API error', ['error' => $result['error'] ?? 'Unknown']);
        }
    }
} catch (\Exception $e) {
    // Xử lý exception (lỗi kết nối, lỗi đăng nhập...)
    Log::error('Submit XML failed: ' . $e->getMessage());
}
```

## 4. Ví dụ sử dụng trong Controller

```php
namespace App\Http\Controllers;

use App\Services\CongDuLieuYTeDienBienXmlSubmitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CongDuLieuYTeDienBienController extends Controller
{
    public function submitXml(Request $request)
    {
        try {
            $xmlContent = $request->input('xml_content');
            
            if (empty($xmlContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'XML content is required',
                ], 400);
            }
            
            $submitService = new CongDuLieuYTeDienBienXmlSubmitService();
            $result = $submitService->submitXml($xmlContent);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gửi hồ sơ thành công',
                    'data' => [
                        'maGiaoDich' => $result['maGiaoDich'],
                        'trangThai' => $result['trangThai'],
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gửi hồ sơ thất bại',
                    'data' => $result,
                ], $result['statusCode'] ?? 400);
            }
        } catch (\Exception $e) {
            Log::error('Submit XML error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

## 5. Lưu ý quan trọng

1. **Mật khẩu MD5**: Service tự động hash mật khẩu bằng MD5 trước khi gửi lên API.

2. **Multipart/form-data**: API sử dụng `multipart/form-data` thay vì `application/json`.

3. **Token caching**: Token được cache trong 1 giờ (vì API không trả về thời gian hết hạn).

4. **File tạm**: Service tự động tạo và xóa file tạm khi gửi XML.

5. **Trạng thái hồ sơ**:
   - `trangThai = 1`: Hồ sơ đúng (status 200)
   - `trangThai = 2`: Hồ sơ lỗi (status 201)
   - Status 400, 401, 500: Lỗi hệ thống

6. **Mã giao dịch**: Mỗi lần gửi thành công sẽ nhận được `maGiaoDich` duy nhất để theo dõi hồ sơ.

## 6. So sánh với Service Hà Nội

| Tính năng | Hà Nội | Điện Biên |
|-----------|--------|-----------|
| Environment | Có (sandbox/production) | Không |
| Content-Type | application/json | multipart/form-data |
| Password | Plain text | MD5 hash |
| Token expires | Có (từ API) | Không (cache 1h) |
| Response format | maKetQua, fileResults | maGiaoDich, trangThai, maLoi |

