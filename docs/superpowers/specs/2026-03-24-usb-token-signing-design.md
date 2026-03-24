# Thiết kế: Ký số XML bằng USB Token (Server-side)

**Date:** 2026-03-24
**Project:** QLBV - Hệ thống Quản lý Bệnh viện
**Status:** Draft

---

## 1. Bối cảnh & Mục tiêu

### 1.1 Hiện trạng
Hệ thống đã có chức năng ký số XML (QD130/XML3176) qua HSM API:
- `XMLSignService.signXml()` gọi HTTP POST tới `http://192.168.7.239:1415/api/EmrSign/SignXmlBhyt`
- Auth qua `ACSLoginService` (token từ `192.168.7.200:1401`)
- Response trả về XML đã ký dưới dạng base64

### 1.2 Vấn đề
Cần bổ sung hỗ trợ ký số bằng USB Token (thay thế hoặc bổ sung cho HSM) với yêu cầu:
- Ký **tự động trên server** (không cần tương tác user nhập PIN)
- Hỗ trợ **mọi CA provider** (VNPT, Viettel, BKAV, HILO, ...)
- Tương thích **ngược** với code Laravel hiện tại (tối thiểu thay đổi)

### 1.3 Format chữ ký xác nhận
Chữ ký XML sử dụng chuẩn **XMLDSig (W3C)**:
- Signature Algorithm: `RSA-SHA256`
- Digest Algorithm: `SHA-256`
- Canonicalization: `C14N` (http://www.w3.org/TR/2001/REC-xml-c14n-20010315)
- Transform: `enveloped-signature` (ký toàn bộ document, embed vào trong)
- KeyInfo: `X509Certificate` đầy đủ nhúng trong chữ ký
- Vị trí chữ ký: trong tag `<CHUKYDONVI>` của XML

---

## 2. Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────────┐
│                    Windows Server                            │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │            Laravel PHP Application                   │   │
│  │                                                     │   │
│  │  XMLSignService.signXml()                           │   │
│  │       │                                             │   │
│  │       ├──[usb_token_sign.enabled=true]              │   │
│  │       │        └──► POST localhost:18081 ──────────►│───┤
│  │       │                                             │   │
│  │       └──[xml_sign HSM]                             │   │
│  │                └──► POST 192.168.7.239:1415         │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │      UsbTokenSigningService (.NET 8 Windows Svc)    │   │
│  │      PORT: 18081                                    │   │
│  │                                                     │   │
│  │  POST /api/EmrSign/SignXmlBhyt                      │   │
│  │       │                                             │   │
│  │       ├── Decode base64 XML                         │   │
│  │       ├── Load cert từ Windows Certificate Store    │   │
│  │       ├── Set PIN → RSACng (CngKey.SmartCardPin)    │   │
│  │       ├── SignedXml (RSA-SHA256 + SHA256 + C14N)    │   │
│  │       ├── Embed <Signature> vào <CHUKYDONVI>        │   │
│  │       └── Return {Success, Data: base64SignedXml}   │   │
│  └──────────────────────────────────────┬──────────────┘   │
│                                         │                   │
│                              Windows CNG/Cert Store         │
│                                         │                   │
│                                    USB Token                │
│                              (Driver tự đăng ký cert)       │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Tại sao dùng Windows CNG (không dùng PKCS#11 trực tiếp)

| Tiêu chí | PKCS#11 trực tiếp | Windows CNG (chọn) |
|----------|-------------------|---------------------|
| Hỗ trợ nhiều CA | Phải cấu hình từng .dll | ✅ Driver tự đăng ký vào cert store |
| Tích hợp .NET | Cần thư viện 3rd party | ✅ Native `RSACng`, `X509Store` |
| PIN tự động | Phức tạp | ✅ `CngKey.SetProperty("SmartCardPin")` |
| Setup | Cấu hình path dll riêng từng hãng | ✅ Chỉ cần cài driver CA |

**Nguyên lý:** Mọi driver USB Token của VNPT/Viettel/BKAV/HILO khi cài trên Windows đều tự đăng ký certificate vào Windows Certificate Store (store "My"). .NET `cert.GetRSAPrivateKey()` trả về `RSACng` được backed bởi private key trong token.

---

## 4. Component 1: UsbTokenSigningService

### 4.1 Cấu trúc project
```
UsbTokenSigningService/
├── UsbTokenSigningService.csproj   (.NET 8, Worker Service)
├── Program.cs                      (DI setup, Windows Service host)
├── appsettings.json                (config)
├── Services/
│   └── XmlSigningService.cs        (core signing logic)
└── Controllers/
    └── SignController.cs           (HTTP endpoint)
```

### 4.2 appsettings.json
```json
{
  "Kestrel": {
    "Endpoints": {
      "Http": { "Url": "http://127.0.0.1:18081" }
    }
  },
  "SigningService": {
    "CertificateThumbprint": "AA:BB:CC:...",
    "TokenPin": "DPAPI:AQAAANCMnd8...",
    "XmlSignatureTag": "CHUKYDONVI",
    "ServiceToken": "random-32-byte-hex-shared-secret"
  },
  "Logging": {
    "LogLevel": { "Default": "Information" }
  }
}
```

**Kestrel binding:** Chỉ bind `127.0.0.1` (không phải `0.0.0.0`) → service không expose ra mạng LAN.

**PIN bảo mật:** Mã hóa bằng Windows DPAPI `LocalMachine` scope (`ProtectedData.Protect(data, null, DataProtectionScope.LocalMachine)`).
- `LocalMachine` scope: decrypt được bởi bất kỳ process nào trên cùng máy (bao gồm service account bất kỳ)
- Chỉ giải mã được trên đúng máy Windows đó → an toàn nếu server được bảo vệ vật lý

**Authentication:** Service yêu cầu header `X-Service-Token` khớp với `ServiceToken` trong config.
Mọi request thiếu hoặc sai token → trả HTTP 401.

### 4.3 HTTP API Contract

**Request:**
```json
POST http://localhost:18081/api/EmrSign/SignXmlBhyt
Content-Type: application/json

{
  "ApiData": {
    "XmlBase64": "<base64 encoded XML>",
    "TagStoreSignatureValue": "CHUKYDONVI",
    "ConfigData": {}
  }
}
```

**Response (success):**
```json
{
  "Success": true,
  "Data": "<base64 encoded signed XML>",
  "Param": { "Messages": [] }
}
```

**Response (error):**
```json
{
  "Success": false,
  "Data": null,
  "Param": { "Messages": ["Chi tiết lỗi"] }
}
```

### 4.4 Luồng ký số trong XmlSigningService

```
1. Decode base64 → XmlDocument
2. Load cert từ X509Store (StoreName.My, StoreLocation.LocalMachine) theo thumbprint
3. Lấy CngKey từ cert bằng cách dùng CngKey.Open() với CngKeyOpenOptions.Silent
   → Silent mode: không bao giờ hiện PIN dialog (sẽ throw exception nếu PIN chưa set)
4. Set PIN vào CNG key bằng NCryptSetProperty với NCRYPT_PIN_PROPERTY:
   CngProperty pinProp = new CngProperty("SmartCardPin", pinBytes, CngPropertyOptions.None)
   cngKey.SetProperty(pinProp)
   Nếu token vẫn prompt sau đó: thử thêm NCryptSetProperty(hKey, "SmartCardPinId", ...)
5. Tạo RSACng từ cngKey → dùng làm signing key
6. Tạo SignedXml(doc):
   - CanonicalizationMethod = http://www.w3.org/TR/2001/REC-xml-c14n-20010315
   - SignatureMethod = http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
   - Reference URI="" với:
     - Transform: http://www.w3.org/2000/09/xmldsig#enveloped-signature
     - DigestMethod: http://www.w3.org/2001/04/xmlenc#sha256
7. AddReference(ref)
8. KeyInfo: thêm X509Certificate của cert (DER encoded)
9. ComputeSignature()  ← nếu PIN sai sẽ throw exception tại đây
10. GetXml() → XmlElement (Signature node)
11. Tìm hoặc tạo <CHUKYDONVI> trong document
12. AppendChild(signatureNode) vào CHUKYDONVI
13. Encode toàn bộ XML → base64
14. Return response

LƯU Ý QUAN TRỌNG về Silent mode:
- CngKeyOpenOptions.Silent ngăn PIN dialog hoàn toàn
- Nếu PIN chưa set trước khi ComputeSignature(), sẽ throw CryptographicException
  → Service trả về Success=false với message rõ ràng, KHÔNG hang
- Test với từng CA provider (VNPT, Viettel, BKAV, HILO) vì hành vi PIN cache khác nhau
```

### 4.5 Cài đặt Windows Service

```powershell
# Publish
dotnet publish -c Release -r win-x64

# Cài service
sc create UsbTokenSign binPath="C:\Services\UsbTokenSign\UsbTokenSigningService.exe"
sc config UsbTokenSign start=auto
sc start UsbTokenSign
```

---

## 5. Component 2: Thay đổi Laravel

### 5.1 config/organization.php (bổ sung)
```php
// Thêm sau block 'xml_sign'
'usb_token_sign' => [
    'endpoint'                  => env('USB_TOKEN_ENDPOINT', 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt'),
    'enabled'                   => env('USB_TOKEN_SIGN_ENABLED', false),
    'service_token'             => env('USB_TOKEN_SERVICE_TOKEN', ''),
    'tag_store_signature_value' => 'CHUKYDONVI',
    'timeout'                   => 30, // seconds
],
```

### 5.2 XMLSignService.php (bổ sung method)
Thêm logic chọn signing method vào đầu `signXml()` và sửa `isEnabled()`:

```php
public function signXml($xmlContent)
{
    $usbConfig = Config::get('organization.usb_token_sign', []);
    $hsmEnabled = !empty($this->config['enabled']);
    $usbEnabled = !empty($usbConfig['enabled']);

    // Cảnh báo nếu cả hai cùng bật
    if ($usbEnabled && $hsmEnabled) {
        Log::warning('XMLSignService: Cả USB Token và HSM đều enabled. USB Token được ưu tiên.');
    }

    // USB Token mode
    if ($usbEnabled) {
        return $this->signWithUsbToken($xmlContent, $usbConfig);
    }

    // HSM mode (giữ nguyên code cũ bên dưới)
    if (!$hsmEnabled) {
        Log::info('XML signing is disabled');
        return ['isSigned' => false, 'data' => $xmlContent];
    }
    // ... phần code HSM cũ giữ nguyên ...
}

/**
 * Trả về true nếu BẤT KỲ signing mode nào đang enabled
 */
public function isEnabled(): bool
{
    $hsmEnabled = !empty($this->config['enabled']);
    $usbEnabled = !empty(Config::get('organization.usb_token_sign.enabled'));
    return $hsmEnabled || $usbEnabled;
}

private function signWithUsbToken(string $xmlContent, array $usbConfig): array
{
    try {
        $response = $this->httpClient->post($usbConfig['endpoint'], [
            'headers' => [
                'Content-Type'   => 'application/json',
                'X-Service-Token' => $usbConfig['service_token'],
            ],
            'json' => [
                'ApiData' => [
                    'XmlBase64'              => base64_encode($xmlContent),
                    'TagStoreSignatureValue' => $usbConfig['tag_store_signature_value'],
                    'ConfigData'             => new \stdClass(),  // phải là {} không phải []
                ]
            ],
            'timeout' => $usbConfig['timeout'] ?? 30,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!$result['Success']) {
            $error = implode(', ', $result['Param']['Messages'] ?? ['Unknown error']);
            Log::error('USB Token signing failed: ' . $error);
            return ['isSigned' => false, 'data' => $xmlContent, 'error' => $error];
        }

        return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

    } catch (GuzzleException $e) {
        Log::error('USB Token Sign Service Error: ' . $e->getMessage());
        return ['isSigned' => false, 'data' => $xmlContent, 'error' => $e->getMessage()];
    }
}
```

### 5.3 .env additions
```bash
# USB Token Signing Service
USB_TOKEN_SIGN_ENABLED=true
USB_TOKEN_ENDPOINT=http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt
USB_TOKEN_SERVICE_TOKEN=your-random-32-byte-hex-secret
```

---

## 6. Setup & Deployment

### 6.1 Chuẩn bị USB Token trên Windows Server
1. Cắm USB Token vào server
2. Cài driver của CA (VNPT/Viettel/BKAV/HILO) - driver tự đăng ký cert vào Windows cert store
3. Mở `certmgr.msc` → Personal → Certificates → xác nhận cert xuất hiện
4. Copy thumbprint của cert

### 6.2 Cấu hình UsbTokenSigningService
```powershell
# Mã hóa PIN bằng DPAPI LocalMachine scope (có thể chạy từ bất kỳ admin account nào)
Add-Type -AssemblyName System.Security
$pin = "12345678"
$pinBytes = [System.Text.Encoding]::Unicode.GetBytes($pin)
$encrypted = [System.Security.Cryptography.ProtectedData]::Protect(
    $pinBytes, $null,
    [System.Security.Cryptography.DataProtectionScope]::LocalMachine  # ← LocalMachine, không phải CurrentUser
)
$encrypted64 = [Convert]::ToBase64String($encrypted)
Write-Host "DPAPI:$encrypted64"
```

Paste giá trị vào `appsettings.json` → `TokenPin`.

**Lưu ý:** Dùng `LocalMachine` scope (không phải `CurrentUser`) để service account bất kỳ trên cùng máy đều giải mã được. Chỉ máy vật lý đó mới giải mã được → đủ bảo mật.

### 6.3 Cài Windows Service với account phù hợp
```powershell
# Cài service (dùng LocalSystem hoặc dedicated account đều được với LocalMachine DPAPI)
sc create UsbTokenSign `
  binPath="C:\Services\UsbTokenSign\UsbTokenSigningService.exe" `
  DisplayName="USB Token Signing Service" `
  start=auto

# Nếu USB Token đăng ký cert vào LocalMachine store (thường với driver cài system-wide)
# → LocalSystem account có thể đọc được, không cần config thêm

# Nếu USB Token đăng ký cert vào CurrentUser store của admin account
# → Cần chạy service dưới đúng account đó:
sc config UsbTokenSign obj= ".\AdminAccount" password= "password"

sc start UsbTokenSign
```

### 6.4 Kiểm tra kết nối
```powershell
# Test từ PowerShell (phải có X-Service-Token header)
Invoke-RestMethod -Uri "http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt" `
  -Method POST `
  -ContentType "application/json" `
  -Headers @{ "X-Service-Token" = "your-secret-here" } `
  -Body '{"ApiData":{"XmlBase64":"PD94bWw+","TagStoreSignatureValue":"CHUKYDONVI","ConfigData":{}}}'
# PD94bWw+ = base64("<?xml>") - thay bằng base64 của XML thật để test đầy đủ
```

---

## 7. Error Handling & Edge Cases

| Tình huống | Xử lý |
|-----------|--------|
| USB Token không cắm | Service trả `Success=false`, message "Token không khả dụng" |
| PIN sai | CNG throw exception, service trả error |
| Cert hết hạn | Kiểm tra `cert.NotAfter` trước khi ký, trả warning |
| Service chưa chạy | Guzzle timeout, Laravel log error, `isSigned=false` |
| XML không có tag CHUKYDONVI | Service tự tạo tag trước khi nhúng chữ ký |

---

## 8. Security

- PIN mã hóa DPAPI (LocalMachine scope): chỉ giải mã được trên đúng máy đó → an toàn nếu server không bị compromise vật lý
- Service chỉ lắng nghe `localhost` - không expose ra mạng ngoài
- Kết nối từ PHP tới service: local network only
- Private key KHÔNG bao giờ rời khỏi USB Token hardware

---

## 9. Files cần tạo/sửa

### Files mới (C# project)
- `UsbTokenSigningService/` (toàn bộ project .NET 8)

### Files sửa (Laravel)
- `config/organization.php` - thêm `usb_token_sign` block
- `app/Services/XMLSignService.php` - thêm `signWithUsbToken()` và logic chọn method

### Files cấu hình
- `.env` - thêm `USB_TOKEN_SIGN_ENABLED`, `USB_TOKEN_ENDPOINT`

---

## 10. Không nằm trong scope

- UI để user ký thủ công từ browser (client-side)
- Hỗ trợ nhiều USB Token cùng lúc (multi-token load balancing)
- OCSP/CRL revocation check realtime
- Database lưu lịch sử ký (đã có cột `is_signed`, `signed_error` trong model)
