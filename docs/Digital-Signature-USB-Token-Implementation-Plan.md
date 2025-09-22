# PHƯƠNG ÁN TRIỂN KHAI SERVICE KÝ SỐ BẰNG USB TOKEN

## 1. TỔNG QUAN DỰ ÁN

### 1.1 Mục tiêu
Bổ sung tính năng ký số điện tử bằng USB Token vào hệ thống Quản lý Bệnh viện (QLBV) nhằm:
- Đảm bảo tính xác thực và toàn vẹn của tài liệu y tế
- Tuân thủ quy định pháp lý về chữ ký số trong lĩnh vực y tế
- Nâng cao bảo mật và khả năng kiểm soát tài liệu
- Hỗ trợ ký số cho các file XML QD130, tài liệu EMR và báo cáo

### 1.2 Phạm vi triển khai
- **Tài liệu cần ký số:**
  - File XML QD130 (XML1-XML14)
  - Tài liệu EMR (Hồ sơ bệnh án điện tử)
  - Báo cáo y tế và thống kê
  - Chứng chỉ nghỉ việc, giấy ra viện
  
- **Đối tượng sử dụng:**
  - Bác sĩ trưởng khoa
  - Giám đốc bệnh viện
  - Trưởng phòng kế hoạch tổng hợp
  - Nhân viên được ủy quyền

## 2. KIẾN TRÚC TỔNG THỂ

### 2.1 Cấu trúc hệ thống
```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
├─────────────────────────────────────────────────────────┤
│  - Digital Signature UI Components                     │
│  - USB Token Detection & Management                    │
│  - Document Preview & Signature Workflow               │
└─────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────┐
│                 Application Layer                       │
├─────────────────────────────────────────────────────────┤
│  - DigitalSignatureController                          │
│  - UsbTokenController                                  │
│  - DocumentSigningController                          │
└─────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────┐
│                   Service Layer                         │
├─────────────────────────────────────────────────────────┤
│  - DigitalSignatureService                             │
│  - UsbTokenService                                     │
│  - CertificateValidationService                       │
│  - DocumentHashingService                             │
│  - SignatureVerificationService                       │
└─────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────┐
│                Infrastructure Layer                     │
├─────────────────────────────────────────────────────────┤
│  - PKCS#11 Library Integration                         │
│  - Certificate Authority (CA) Integration              │
│  - HSM/USB Token Communication                         │
│  - Database Storage for Signatures                     │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Luồng ký số cơ bản
1. **Xác thực USB Token:** Phát hiện và xác thực USB Token
2. **Chọn chứng chỉ:** Liệt kê và chọn chứng chỉ từ Token
3. **Chuẩn bị tài liệu:** Hash tài liệu cần ký
4. **Ký số:** Thực hiện ký số với private key từ Token
5. **Lưu trữ:** Lưu chữ ký số và metadata
6. **Xác minh:** Kiểm tra tính hợp lệ của chữ ký

## 3. THIẾT KẾ CHI TIẾT

### 3.1 Database Schema

#### 3.1.1 Bảng digital_signatures
```sql
CREATE TABLE digital_signatures (
    id CHAR(36) PRIMARY KEY,
    document_id VARCHAR(255) NOT NULL,
    document_type ENUM('xml_qd130', 'emr_document', 'report', 'certificate') NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    signer_id BIGINT UNSIGNED NOT NULL,
    signer_name VARCHAR(255) NOT NULL,
    certificate_serial VARCHAR(255) NOT NULL,
    certificate_issuer VARCHAR(500) NOT NULL,
    certificate_subject VARCHAR(500) NOT NULL,
    signature_value LONGTEXT NOT NULL,
    signature_algorithm VARCHAR(100) NOT NULL,
    hash_algorithm VARCHAR(50) NOT NULL DEFAULT 'SHA256',
    document_hash VARCHAR(255) NOT NULL,
    signed_at TIMESTAMP NOT NULL,
    signature_status ENUM('valid', 'invalid', 'expired', 'revoked') DEFAULT 'valid',
    verification_details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255) NOT NULL,
    updated_by VARCHAR(255) NULL,
    
    FOREIGN KEY (signer_id) REFERENCES users(id),
    INDEX idx_document_id (document_id),
    INDEX idx_document_type (document_type),
    INDEX idx_signer_id (signer_id),
    INDEX idx_signed_at (signed_at),
    INDEX idx_signature_status (signature_status)
);
```

#### 3.1.2 Bảng certificate_authorities
```sql
CREATE TABLE certificate_authorities (
    id CHAR(36) PRIMARY KEY,
    ca_name VARCHAR(255) NOT NULL,
    ca_certificate LONGTEXT NOT NULL,
    ca_serial VARCHAR(255) NOT NULL,
    ca_issuer VARCHAR(500) NOT NULL,
    ca_subject VARCHAR(500) NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    is_trusted BOOLEAN DEFAULT TRUE,
    crl_url VARCHAR(500) NULL,
    ocsp_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_ca_serial (ca_serial),
    INDEX idx_ca_name (ca_name),
    INDEX idx_valid_to (valid_to),
    INDEX idx_is_trusted (is_trusted)
);
```

#### 3.1.3 Bảng signature_logs
```sql
CREATE TABLE signature_logs (
    id CHAR(36) PRIMARY KEY,
    signature_id CHAR(36) NOT NULL,
    action ENUM('sign', 'verify', 'revoke', 'error') NOT NULL,
    status ENUM('success', 'failed', 'warning') NOT NULL,
    message TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(255) NOT NULL,
    
    FOREIGN KEY (signature_id) REFERENCES digital_signatures(id),
    INDEX idx_signature_id (signature_id),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### 3.2 Service Classes

#### 3.2.1 DigitalSignatureService
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\DigitalSignature;
use App\Models\SignatureLog;
use Carbon\Carbon;

class DigitalSignatureService
{
    protected $usbTokenService;
    protected $certificateValidationService;
    protected $documentHashingService;

    public function __construct(
        UsbTokenService $usbTokenService,
        CertificateValidationService $certificateValidationService,
        DocumentHashingService $documentHashingService
    ) {
        $this->usbTokenService = $usbTokenService;
        $this->certificateValidationService = $certificateValidationService;
        $this->documentHashingService = $documentHashingService;
    }

    /**
     * Ký số tài liệu
     */
    public function signDocument(array $params): array
    {
        try {
            // Validate input parameters
            $this->validateSigningParams($params);

            // Detect and validate USB Token
            $tokenInfo = $this->usbTokenService->detectToken();
            if (!$tokenInfo) {
                throw new \Exception('USB Token không được phát hiện');
            }

            // Get certificate from token
            $certificate = $this->usbTokenService->getCertificate($params['certificate_serial']);
            
            // Validate certificate
            $this->certificateValidationService->validateCertificate($certificate);

            // Hash document
            $documentHash = $this->documentHashingService->hashDocument(
                $params['document_path'], 
                $params['hash_algorithm'] ?? 'SHA256'
            );

            // Sign document hash
            $signatureValue = $this->usbTokenService->signHash(
                $documentHash, 
                $params['certificate_serial'],
                $params['pin']
            );

            // Save signature to database
            $signature = $this->saveSignature([
                'document_id' => $params['document_id'],
                'document_type' => $params['document_type'],
                'document_path' => $params['document_path'],
                'signer_id' => auth()->id(),
                'signer_name' => auth()->user()->name,
                'certificate_serial' => $params['certificate_serial'],
                'certificate_issuer' => $certificate['issuer'],
                'certificate_subject' => $certificate['subject'],
                'signature_value' => $signatureValue,
                'signature_algorithm' => $params['signature_algorithm'] ?? 'RSA-PSS',
                'hash_algorithm' => $params['hash_algorithm'] ?? 'SHA256',
                'document_hash' => $documentHash,
                'signed_at' => Carbon::now(),
            ]);

            // Log signing action
            $this->logSignatureAction($signature->id, 'sign', 'success', 'Tài liệu đã được ký thành công');

            return [
                'success' => true,
                'signature_id' => $signature->id,
                'message' => 'Ký số thành công'
            ];

        } catch (\Exception $e) {
            Log::error('Digital signature error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Xác minh chữ ký số
     */
    public function verifySignature(string $signatureId): array
    {
        try {
            $signature = DigitalSignature::findOrFail($signatureId);

            // Verify certificate is still valid
            $certificateStatus = $this->certificateValidationService->checkCertificateStatus(
                $signature->certificate_serial
            );

            // Re-hash document and compare
            $currentHash = $this->documentHashingService->hashDocument(
                $signature->document_path,
                $signature->hash_algorithm
            );

            $hashMatches = hash_equals($signature->document_hash, $currentHash);

            // Verify signature value
            $signatureValid = $this->verifySignatureValue(
                $signature->signature_value,
                $signature->document_hash,
                $signature->certificate_serial
            );

            $overallStatus = $certificateStatus['valid'] && $hashMatches && $signatureValid;

            // Update signature status
            $signature->update([
                'signature_status' => $overallStatus ? 'valid' : 'invalid',
                'verification_details' => [
                    'certificate_valid' => $certificateStatus['valid'],
                    'certificate_message' => $certificateStatus['message'],
                    'hash_matches' => $hashMatches,
                    'signature_valid' => $signatureValid,
                    'verified_at' => Carbon::now()->toISOString()
                ]
            ]);

            // Log verification
            $this->logSignatureAction(
                $signature->id, 
                'verify', 
                $overallStatus ? 'success' : 'failed',
                $overallStatus ? 'Chữ ký hợp lệ' : 'Chữ ký không hợp lệ'
            );

            return [
                'valid' => $overallStatus,
                'details' => [
                    'certificate_valid' => $certificateStatus['valid'],
                    'hash_matches' => $hashMatches,
                    'signature_valid' => $signatureValid
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Signature verification error', [
                'signature_id' => $signatureId,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách chữ ký của tài liệu
     */
    public function getDocumentSignatures(string $documentId, string $documentType): array
    {
        return DigitalSignature::where('document_id', $documentId)
            ->where('document_type', $documentType)
            ->with(['signer:id,name,email'])
            ->orderBy('signed_at', 'desc')
            ->get()
            ->map(function ($signature) {
                return [
                    'id' => $signature->id,
                    'signer_name' => $signature->signer_name,
                    'signed_at' => $signature->signed_at->format('d/m/Y H:i:s'),
                    'status' => $signature->signature_status,
                    'certificate_subject' => $signature->certificate_subject,
                ];
            })
            ->toArray();
    }

    private function validateSigningParams(array $params): void
    {
        $required = ['document_id', 'document_type', 'document_path', 'certificate_serial', 'pin'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \Exception("Thiếu tham số bắt buộc: {$field}");
            }
        }

        if (!file_exists($params['document_path'])) {
            throw new \Exception('Tài liệu không tồn tại');
        }
    }

    private function saveSignature(array $data): DigitalSignature
    {
        return DigitalSignature::create(array_merge($data, [
            'id' => \Str::uuid(),
            'created_by' => auth()->user()->name ?? 'system',
        ]));
    }

    private function logSignatureAction(string $signatureId, string $action, string $status, string $message): void
    {
        SignatureLog::create([
            'id' => \Str::uuid(),
            'signature_id' => $signatureId,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_by' => auth()->user()->name ?? 'system',
        ]);
    }

    private function verifySignatureValue(string $signatureValue, string $documentHash, string $certificateSerial): bool
    {
        // Implementation depends on the cryptographic library used
        // This is a placeholder for the actual signature verification logic
        return $this->usbTokenService->verifySignature($signatureValue, $documentHash, $certificateSerial);
    }
}
```

#### 3.2.2 UsbTokenService
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class UsbTokenService
{
    protected $pkcs11Library;
    protected $session;

    public function __construct()
    {
        $this->pkcs11Library = config('digital_signature.pkcs11_library_path');
    }

    /**
     * Phát hiện USB Token
     */
    public function detectToken(): ?array
    {
        try {
            // Initialize PKCS#11 library
            $this->initializePKCS11();

            // Get slot list
            $slots = $this->getSlotList();

            if (empty($slots)) {
                return null;
            }

            // Get token info from first available slot
            $tokenInfo = $this->getTokenInfo($slots[0]);

            return [
                'slot_id' => $slots[0],
                'token_label' => $tokenInfo['label'],
                'serial_number' => $tokenInfo['serial_number'],
                'manufacturer' => $tokenInfo['manufacturer'],
                'model' => $tokenInfo['model'],
            ];

        } catch (\Exception $e) {
            Log::error('USB Token detection error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Lấy danh sách chứng chỉ từ Token
     */
    public function getCertificates(): array
    {
        try {
            if (!$this->session) {
                throw new \Exception('Chưa kết nối với USB Token');
            }

            // Find certificate objects
            $certificates = $this->findCertificateObjects();

            $result = [];
            foreach ($certificates as $cert) {
                $certData = $this->parseCertificate($cert);
                $result[] = [
                    'serial' => $certData['serial_number'],
                    'subject' => $certData['subject'],
                    'issuer' => $certData['issuer'],
                    'valid_from' => $certData['valid_from'],
                    'valid_to' => $certData['valid_to'],
                    'key_usage' => $certData['key_usage'],
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Get certificates error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Lấy thông tin chứng chỉ cụ thể
     */
    public function getCertificate(string $serialNumber): array
    {
        $certificates = $this->getCertificates();
        
        foreach ($certificates as $cert) {
            if ($cert['serial'] === $serialNumber) {
                return $cert;
            }
        }

        throw new \Exception('Không tìm thấy chứng chỉ với serial: ' . $serialNumber);
    }

    /**
     * Đăng nhập vào Token với PIN
     */
    public function loginWithPin(string $pin): bool
    {
        try {
            if (!$this->session) {
                throw new \Exception('Chưa kết nối với USB Token');
            }

            // Login to token
            $result = $this->performTokenLogin($pin);

            if (!$result) {
                throw new \Exception('PIN không chính xác');
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Token login error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Ký hash với private key từ Token
     */
    public function signHash(string $hash, string $certificateSerial, string $pin): string
    {
        try {
            // Login with PIN
            $this->loginWithPin($pin);

            // Find private key corresponding to certificate
            $privateKey = $this->findPrivateKey($certificateSerial);

            if (!$privateKey) {
                throw new \Exception('Không tìm thấy private key tương ứng');
            }

            // Sign hash
            $signature = $this->performSigning($privateKey, $hash);

            return base64_encode($signature);

        } catch (\Exception $e) {
            Log::error('Hash signing error', [
                'certificate_serial' => $certificateSerial,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Xác minh chữ ký
     */
    public function verifySignature(string $signatureValue, string $documentHash, string $certificateSerial): bool
    {
        try {
            // Get certificate
            $certificate = $this->getCertificate($certificateSerial);

            // Extract public key from certificate
            $publicKey = $this->extractPublicKey($certificate);

            // Verify signature
            $signature = base64_decode($signatureValue);
            
            return $this->performSignatureVerification($publicKey, $documentHash, $signature);

        } catch (\Exception $e) {
            Log::error('Signature verification error', [
                'certificate_serial' => $certificateSerial,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Private helper methods
    private function initializePKCS11(): void
    {
        // Initialize PKCS#11 library - implementation depends on chosen library
        // This could use phpseclib, OpenSSL extension, or a custom C extension
    }

    private function getSlotList(): array
    {
        // Get list of available slots
        // Implementation specific to PKCS#11 library
        return [];
    }

    private function getTokenInfo(int $slotId): array
    {
        // Get token information
        // Implementation specific to PKCS#11 library
        return [];
    }

    private function findCertificateObjects(): array
    {
        // Find certificate objects in token
        // Implementation specific to PKCS#11 library
        return [];
    }

    private function parseCertificate($certObject): array
    {
        // Parse certificate and extract information
        // Implementation specific to certificate format
        return [];
    }

    private function performTokenLogin(string $pin): bool
    {
        // Perform actual login to token
        // Implementation specific to PKCS#11 library
        return true;
    }

    private function findPrivateKey(string $certificateSerial)
    {
        // Find private key object corresponding to certificate
        // Implementation specific to PKCS#11 library
        return null;
    }

    private function performSigning($privateKey, string $hash): string
    {
        // Perform actual signing operation
        // Implementation specific to PKCS#11 library
        return '';
    }

    private function extractPublicKey(array $certificate)
    {
        // Extract public key from certificate
        // Implementation specific to certificate format
        return null;
    }

    private function performSignatureVerification($publicKey, string $hash, string $signature): bool
    {
        // Perform signature verification
        // Implementation specific to cryptographic library
        return true;
    }
}
```

### 3.3 Controller Classes

#### 3.3.1 DigitalSignatureController
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DigitalSignatureService;
use App\Http\Requests\SignDocumentRequest;
use Illuminate\Http\JsonResponse;

class DigitalSignatureController extends Controller
{
    protected $digitalSignatureService;

    public function __construct(DigitalSignatureService $digitalSignatureService)
    {
        $this->digitalSignatureService = $digitalSignatureService;
        $this->middleware('auth');
        $this->middleware('permission:digital-signature-sign')->only(['sign']);
        $this->middleware('permission:digital-signature-verify')->only(['verify', 'getSignatures']);
    }

    /**
     * Ký số tài liệu
     */
    public function sign(SignDocumentRequest $request): JsonResponse
    {
        $result = $this->digitalSignatureService->signDocument($request->validated());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => ['signature_id' => $result['signature_id']]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * Xác minh chữ ký số
     */
    public function verify(Request $request, string $signatureId): JsonResponse
    {
        $result = $this->digitalSignatureService->verifySignature($signatureId);

        return response()->json([
            'valid' => $result['valid'],
            'details' => $result['details'] ?? null,
            'error' => $result['error'] ?? null
        ]);
    }

    /**
     * Lấy danh sách chữ ký của tài liệu
     */
    public function getSignatures(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|string',
            'document_type' => 'required|in:xml_qd130,emr_document,report,certificate'
        ]);

        $signatures = $this->digitalSignatureService->getDocumentSignatures(
            $request->document_id,
            $request->document_type
        );

        return response()->json([
            'success' => true,
            'data' => $signatures
        ]);
    }

    /**
     * Hiển thị form ký số
     */
    public function showSigningForm(Request $request)
    {
        $request->validate([
            'document_id' => 'required|string',
            'document_type' => 'required|in:xml_qd130,emr_document,report,certificate',
            'document_path' => 'required|string'
        ]);

        return view('digital-signature.sign', [
            'document_id' => $request->document_id,
            'document_type' => $request->document_type,
            'document_path' => $request->document_path,
            'document_name' => basename($request->document_path)
        ]);
    }

    /**
     * Hiển thị thông tin xác minh chữ ký
     */
    public function showVerification(string $signatureId)
    {
        $result = $this->digitalSignatureService->verifySignature($signatureId);

        return view('digital-signature.verify', [
            'signature_id' => $signatureId,
            'verification_result' => $result
        ]);
    }
}
```

#### 3.3.2 UsbTokenController
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UsbTokenService;
use Illuminate\Http\JsonResponse;

class UsbTokenController extends Controller
{
    protected $usbTokenService;

    public function __construct(UsbTokenService $usbTokenService)
    {
        $this->usbTokenService = $usbTokenService;
        $this->middleware('auth');
    }

    /**
     * Phát hiện USB Token
     */
    public function detect(): JsonResponse
    {
        $tokenInfo = $this->usbTokenService->detectToken();

        if ($tokenInfo) {
            return response()->json([
                'detected' => true,
                'token_info' => $tokenInfo
            ]);
        }

        return response()->json([
            'detected' => false,
            'message' => 'Không phát hiện USB Token'
        ]);
    }

    /**
     * Lấy danh sách chứng chỉ
     */
    public function getCertificates(): JsonResponse
    {
        try {
            $certificates = $this->usbTokenService->getCertificates();

            return response()->json([
                'success' => true,
                'certificates' => $certificates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Kiểm tra PIN
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:12'
        ]);

        try {
            $result = $this->usbTokenService->loginWithPin($request->pin);

            return response()->json([
                'valid' => $result,
                'message' => $result ? 'PIN chính xác' : 'PIN không chính xác'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

## 4. FRONTEND IMPLEMENTATION

### 4.1 JavaScript USB Token Detection
```javascript
// public/js/usb-token-manager.js

class UsbTokenManager {
    constructor() {
        this.tokenDetected = false;
        this.certificates = [];
        this.selectedCertificate = null;
    }

    async detectToken() {
        try {
            const response = await fetch('/api/usb-token/detect', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.detected) {
                this.tokenDetected = true;
                this.displayTokenInfo(result.token_info);
                await this.loadCertificates();
            } else {
                this.displayTokenError('USB Token không được phát hiện. Vui lòng cắm Token và thử lại.');
            }

            return result.detected;

        } catch (error) {
            console.error('Token detection error:', error);
            this.displayTokenError('Lỗi khi phát hiện USB Token: ' + error.message);
            return false;
        }
    }

    async loadCertificates() {
        try {
            const response = await fetch('/api/usb-token/certificates', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.certificates = result.certificates;
                this.displayCertificates(this.certificates);
            } else {
                this.displayCertificateError(result.message);
            }

        } catch (error) {
            console.error('Certificate loading error:', error);
            this.displayCertificateError('Lỗi khi tải danh sách chứng chỉ: ' + error.message);
        }
    }

    async verifyPin(pin) {
        try {
            const response = await fetch('/api/usb-token/verify-pin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pin: pin })
            });

            const result = await response.json();
            return result.valid;

        } catch (error) {
            console.error('PIN verification error:', error);
            return false;
        }
    }

    async signDocument(documentData, certificateSerial, pin) {
        try {
            const response = await fetch('/api/digital-signature/sign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    document_id: documentData.document_id,
                    document_type: documentData.document_type,
                    document_path: documentData.document_path,
                    certificate_serial: certificateSerial,
                    pin: pin
                })
            });

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('Document signing error:', error);
            return { success: false, message: error.message };
        }
    }

    displayTokenInfo(tokenInfo) {
        const tokenInfoDiv = document.getElementById('token-info');
        if (tokenInfoDiv) {
            tokenInfoDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> USB Token đã được phát hiện</h5>
                    <p><strong>Nhãn:</strong> ${tokenInfo.token_label}</p>
                    <p><strong>Serial:</strong> ${tokenInfo.serial_number}</p>
                    <p><strong>Nhà sản xuất:</strong> ${tokenInfo.manufacturer}</p>
                    <p><strong>Model:</strong> ${tokenInfo.model}</p>
                </div>
            `;
        }
    }

    displayTokenError(message) {
        const tokenInfoDiv = document.getElementById('token-info');
        if (tokenInfoDiv) {
            tokenInfoDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Lỗi USB Token</h5>
                    <p>${message}</p>
                    <button type="button" class="btn btn-primary" onclick="tokenManager.detectToken()">
                        <i class="fas fa-sync"></i> Thử lại
                    </button>
                </div>
            `;
        }
    }

    displayCertificates(certificates) {
        const certificateSelect = document.getElementById('certificate-select');
        if (certificateSelect) {
            certificateSelect.innerHTML = '<option value="">-- Chọn chứng chỉ --</option>';
            
            certificates.forEach(cert => {
                const option = document.createElement('option');
                option.value = cert.serial;
                option.textContent = `${cert.subject} (Hết hạn: ${cert.valid_to})`;
                certificateSelect.appendChild(option);
            });

            certificateSelect.addEventListener('change', (e) => {
                this.selectedCertificate = certificates.find(cert => cert.serial === e.target.value);
                this.displayCertificateDetails(this.selectedCertificate);
            });
        }
    }

    displayCertificateDetails(certificate) {
        const detailsDiv = document.getElementById('certificate-details');
        if (detailsDiv && certificate) {
            detailsDiv.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        <h6>Thông tin chứng chỉ</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Subject:</strong> ${certificate.subject}</p>
                        <p><strong>Issuer:</strong> ${certificate.issuer}</p>
                        <p><strong>Có hiệu lực từ:</strong> ${certificate.valid_from}</p>
                        <p><strong>Hết hạn:</strong> ${certificate.valid_to}</p>
                        <p><strong>Key Usage:</strong> ${certificate.key_usage}</p>
                    </div>
                </div>
            `;
        }
    }

    displayCertificateError(message) {
        const certificateDiv = document.getElementById('certificate-selection');
        if (certificateDiv) {
            certificateDiv.innerHTML = `
                <div class="alert alert-warning">
                    <p>${message}</p>
                </div>
            `;
        }
    }
}

// Initialize token manager
const tokenManager = new UsbTokenManager();

// Auto-detect token on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('token-info')) {
        tokenManager.detectToken();
    }
});
```

### 4.2 Signing Form View
```php
<!-- resources/views/digital-signature/sign.blade.php -->

@extends('adminlte::page')

@section('title', 'Ký số tài liệu')

@section('content_header')
    <h1>
        <i class="fas fa-signature"></i> Ký số tài liệu
    </h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Thông tin tài liệu</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">ID tài liệu:</th>
                        <td>{{ $document_id }}</td>
                    </tr>
                    <tr>
                        <th>Loại tài liệu:</th>
                        <td>{{ ucfirst(str_replace('_', ' ', $document_type)) }}</td>
                    </tr>
                    <tr>
                        <th>Tên file:</th>
                        <td>{{ $document_name }}</td>
                    </tr>
                    <tr>
                        <th>Đường dẫn:</th>
                        <td><code>{{ $document_path }}</code></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quy trình ký số</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="time-label">
                        <span class="bg-primary">Bước 1</span>
                    </div>
                    <div>
                        <i class="fas fa-usb bg-blue"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Phát hiện USB Token</h3>
                            <div class="timeline-body" id="token-info">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Đang phát hiện USB Token...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="time-label">
                        <span class="bg-warning">Bước 2</span>
                    </div>
                    <div>
                        <i class="fas fa-certificate bg-yellow"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Chọn chứng chỉ</h3>
                            <div class="timeline-body" id="certificate-selection">
                                <div class="form-group">
                                    <label for="certificate-select">Chứng chỉ:</label>
                                    <select class="form-control" id="certificate-select" disabled>
                                        <option value="">Đang tải...</option>
                                    </select>
                                </div>
                                <div id="certificate-details"></div>
                            </div>
                        </div>
                    </div>

                    <div class="time-label">
                        <span class="bg-success">Bước 3</span>
                    </div>
                    <div>
                        <i class="fas fa-key bg-green"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Nhập PIN và ký</h3>
                            <div class="timeline-body">
                                <form id="signing-form">
                                    <div class="form-group">
                                        <label for="pin-input">PIN của USB Token:</label>
                                        <input type="password" class="form-control" id="pin-input" 
                                               placeholder="Nhập PIN..." maxlength="12" disabled>
                                    </div>
                                    <button type="submit" class="btn btn-success" id="sign-button" disabled>
                                        <i class="fas fa-signature"></i> Ký số tài liệu
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hướng dẫn</h3>
            </div>
            <div class="card-body">
                <ol>
                    <li>Cắm USB Token vào máy tính</li>
                    <li>Đợi hệ thống phát hiện Token</li>
                    <li>Chọn chứng chỉ phù hợp</li>
                    <li>Nhập PIN của Token</li>
                    <li>Nhấn nút "Ký số tài liệu"</li>
                </ol>

                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Lưu ý</h5>
                    <ul class="mb-0">
                        <li>Đảm bảo chứng chỉ còn hiệu lực</li>
                        <li>Không chia sẻ PIN với người khác</li>
                        <li>Kiểm tra kỹ thông tin tài liệu trước khi ký</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Trạng thái</h3>
            </div>
            <div class="card-body" id="status-panel">
                <div class="text-muted">Chưa bắt đầu</div>
            </div>
        </div>
    </div>
</div>

<!-- Signing Progress Modal -->
<div class="modal fade" id="signing-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div id="signing-progress">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h4>Đang ký số tài liệu...</h4>
                    <p class="text-muted">Vui lòng không tháo USB Token</p>
                </div>
                <div id="signing-result" style="display: none;">
                    <!-- Result will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="{{ asset('js/usb-token-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentData = {
        document_id: '{{ $document_id }}',
        document_type: '{{ $document_type }}',
        document_path: '{{ $document_path }}'
    };

    // Enable form elements when certificate is selected
    document.getElementById('certificate-select').addEventListener('change', function() {
        const selected = this.value;
        const pinInput = document.getElementById('pin-input');
        const signButton = document.getElementById('sign-button');
        
        if (selected) {
            pinInput.disabled = false;
            signButton.disabled = false;
            updateStatus('Đã chọn chứng chỉ. Có thể nhập PIN và ký.', 'success');
        } else {
            pinInput.disabled = true;
            signButton.disabled = true;
            updateStatus('Vui lòng chọn chứng chỉ.', 'warning');
        }
    });

    // Handle signing form submission
    document.getElementById('signing-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const certificateSerial = document.getElementById('certificate-select').value;
        const pin = document.getElementById('pin-input').value;

        if (!certificateSerial || !pin) {
            alert('Vui lòng chọn chứng chỉ và nhập PIN');
            return;
        }

        // Show progress modal
        $('#signing-modal').modal('show');
        
        try {
            // Verify PIN first
            updateStatus('Đang xác thực PIN...', 'info');
            const pinValid = await tokenManager.verifyPin(pin);
            
            if (!pinValid) {
                throw new Error('PIN không chính xác');
            }

            // Sign document
            updateStatus('Đang ký số tài liệu...', 'info');
            const result = await tokenManager.signDocument(documentData, certificateSerial, pin);

            if (result.success) {
                showSigningResult(true, 'Ký số thành công!', result.data.signature_id);
                updateStatus('Ký số hoàn tất.', 'success');
            } else {
                throw new Error(result.message || 'Lỗi không xác định');
            }

        } catch (error) {
            console.error('Signing error:', error);
            showSigningResult(false, error.message);
            updateStatus('Ký số thất bại: ' + error.message, 'danger');
        }
    });

    function updateStatus(message, type = 'info') {
        const statusPanel = document.getElementById('status-panel');
        const alertClass = type === 'success' ? 'text-success' : 
                          type === 'warning' ? 'text-warning' :
                          type === 'danger' ? 'text-danger' : 'text-info';
        
        statusPanel.innerHTML = `<div class="${alertClass}">${message}</div>`;
    }

    function showSigningResult(success, message, signatureId = null) {
        const progressDiv = document.getElementById('signing-progress');
        const resultDiv = document.getElementById('signing-result');
        
        progressDiv.style.display = 'none';
        resultDiv.style.display = 'block';
        
        if (success) {
            resultDiv.innerHTML = `
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4 class="text-success">Ký số thành công!</h4>
                <p>${message}</p>
                ${signatureId ? `<p><small class="text-muted">Signature ID: ${signatureId}</small></p>` : ''}
                <div class="mt-3">
                    <button type="button" class="btn btn-success" onclick="location.reload()">
                        Ký tài liệu khác
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Đóng
                    </button>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4 class="text-danger">Ký số thất bại!</h4>
                <p>${message}</p>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        Thử lại
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Đóng
                    </button>
                </div>
            `;
        }
    }
});
</script>
@stop

@section('css')
<style>
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}

.timeline > li {
    position: relative;
    margin-right: 10px;
    margin-bottom: 15px;
}

.timeline > li:before,
.timeline > li:after {
    content: '';
    display: table;
}

.timeline > li:after {
    clear: both;
}

.timeline > li > .timeline-item {
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    margin-top: 0;
    background: #fff;
    color: #444;
    margin-left: 60px;
    margin-right: 15px;
    padding: 0;
    position: relative;
}

.timeline > li > .timeline-item > .timeline-header {
    margin: 0;
    color: #555;
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-size: 16px;
    line-height: 1.1;
}

.timeline > li > .timeline-item > .timeline-body {
    padding: 10px;
}

.timeline > li > .fa,
.timeline > li > .fas,
.timeline > li > .far,
.timeline > li > .fab,
.timeline > li > .fal,
.timeline > li > .fad,
.timeline > li > .svg-inline--fa {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}

.time-label > span {
    font-weight: 600;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    display: inline-block;
    background-color: #fff;
    border-radius: 4px;
}
</style>
@stop
```

## 5. CONFIGURATION & DEPLOYMENT

### 5.1 Configuration Files

#### 5.1.1 config/digital_signature.php
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PKCS#11 Library Configuration
    |--------------------------------------------------------------------------
    */
    'pkcs11_library_path' => env('PKCS11_LIBRARY_PATH', '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so'),
    
    /*
    |--------------------------------------------------------------------------
    | Supported Hash Algorithms
    |--------------------------------------------------------------------------
    */
    'hash_algorithms' => [
        'SHA1' => 'sha1',
        'SHA256' => 'sha256',
        'SHA384' => 'sha384',
        'SHA512' => 'sha512',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Hash Algorithm
    |--------------------------------------------------------------------------
    */
    'default_hash_algorithm' => 'SHA256',
    
    /*
    |--------------------------------------------------------------------------
    | Supported Signature Algorithms
    |--------------------------------------------------------------------------
    */
    'signature_algorithms' => [
        'RSA-PSS',
        'RSA-PKCS1',
        'ECDSA',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Signature Algorithm
    |--------------------------------------------------------------------------
    */
    'default_signature_algorithm' => 'RSA-PSS',
    
    /*
    |--------------------------------------------------------------------------
    | Certificate Validation
    |--------------------------------------------------------------------------
    */
    'certificate_validation' => [
        'check_expiry' => true,
        'check_revocation' => true,
        'trusted_ca_certificates' => storage_path('certificates/trusted_ca'),
        'crl_cache_duration' => 3600, // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Document Types Configuration
    |--------------------------------------------------------------------------
    */
    'document_types' => [
        'xml_qd130' => [
            'name' => 'XML QD130',
            'extensions' => ['xml'],
            'max_size' => 10 * 1024 * 1024, // 10MB
        ],
        'emr_document' => [
            'name' => 'EMR Document',
            'extensions' => ['pdf', 'doc', 'docx'],
            'max_size' => 50 * 1024 * 1024, // 50MB
        ],
        'report' => [
            'name' => 'Report',
            'extensions' => ['pdf', 'xlsx', 'docx'],
            'max_size' => 20 * 1024 * 1024, // 20MB
        ],
        'certificate' => [
            'name' => 'Certificate',
            'extensions' => ['pdf', 'doc', 'docx'],
            'max_size' => 5 * 1024 * 1024, // 5MB
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'max_pin_attempts' => 3,
        'pin_lockout_duration' => 300, // seconds
        'session_timeout' => 1800, // seconds
        'require_user_confirmation' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'digital_signature',
        'level' => 'info',
        'log_successful_operations' => true,
        'log_failed_operations' => true,
    ],
];
```

#### 5.1.2 .env additions
```bash
# Digital Signature Configuration
PKCS11_LIBRARY_PATH="/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so"
DIGITAL_SIGNATURE_ENABLED=true
CERTIFICATE_VALIDATION_ENABLED=true
TRUSTED_CA_PATH="/var/www/qlbv/storage/certificates/trusted_ca"
```

### 5.2 Database Migrations

#### 5.2.1 Create digital signatures migration
```bash
php artisan make:migration create_digital_signatures_table
php artisan make:migration create_certificate_authorities_table
php artisan make:migration create_signature_logs_table
```

#### 5.2.2 Migration files (already included in database schema section)

### 5.3 Routes Configuration

#### 5.3.1 routes/api.php additions
```php
// Digital Signature API Routes
Route::middleware(['auth:api'])->group(function () {
    // USB Token Management
    Route::prefix('usb-token')->group(function () {
        Route::get('/detect', [UsbTokenController::class, 'detect']);
        Route::get('/certificates', [UsbTokenController::class, 'getCertificates']);
        Route::post('/verify-pin', [UsbTokenController::class, 'verifyPin']);
    });
    
    // Digital Signature Operations
    Route::prefix('digital-signature')->group(function () {
        Route::post('/sign', [DigitalSignatureController::class, 'sign']);
        Route::get('/verify/{signatureId}', [DigitalSignatureController::class, 'verify']);
        Route::get('/signatures', [DigitalSignatureController::class, 'getSignatures']);
    });
});
```

#### 5.3.2 routes/web.php additions
```php
// Digital Signature Web Routes
Route::middleware(['auth', 'web'])->group(function () {
    Route::prefix('digital-signature')->group(function () {
        Route::get('/sign', [DigitalSignatureController::class, 'showSigningForm'])->name('digital-signature.sign');
        Route::get('/verify/{signatureId}', [DigitalSignatureController::class, 'showVerification'])->name('digital-signature.verify');
    });
});
```

## 6. SECURITY CONSIDERATIONS

### 6.1 Authentication & Authorization
- Tích hợp với hệ thống Laratrust hiện có
- Tạo permissions mới: `digital-signature-sign`, `digital-signature-verify`, `digital-signature-admin`
- Kiểm soát quyền truy cập theo role và department

### 6.2 Data Protection
- Mã hóa dữ liệu nhạy cảm trong database
- Sử dụng HTTPS cho tất cả communication
- Bảo vệ private keys trong USB Token (không export được)
- Audit trail đầy đủ cho tất cả operations

### 6.3 USB Token Security
- PIN protection với lockout mechanism
- Session timeout tự động
- Phát hiện và xử lý token removal
- Validate certificate chain đầy đủ

## 7. TESTING STRATEGY

### 7.1 Unit Tests
- Test các service methods
- Mock USB Token operations
- Test certificate validation logic
- Test signature verification

### 7.2 Integration Tests
- Test API endpoints
- Test database operations
- Test file handling
- Test error scenarios

### 7.3 Security Tests
- Penetration testing
- Certificate validation tests
- PIN brute force protection
- Session management tests

## 8. DEPLOYMENT PLAN

### 8.1 Phase 1: Infrastructure Setup (Week 1-2)
- Cài đặt PKCS#11 libraries
- Cấu hình database schema
- Setup certificate authorities
- Test USB Token connectivity

### 8.2 Phase 2: Backend Development (Week 3-5)
- Implement service classes
- Create API endpoints
- Database integration
- Unit testing

### 8.3 Phase 3: Frontend Development (Week 6-7)
- JavaScript USB Token manager
- Signing interface
- Verification interface
- User experience testing

### 8.4 Phase 4: Integration & Testing (Week 8-9)
- Integration with existing XML/EMR systems
- End-to-end testing
- Security testing
- Performance optimization

### 8.5 Phase 5: Production Deployment (Week 10)
- Production environment setup
- User training
- Go-live support
- Monitoring setup

## 9. MAINTENANCE & SUPPORT

### 9.1 Monitoring
- Certificate expiry alerts
- USB Token connectivity monitoring
- Signature verification failure alerts
- Performance metrics

### 9.2 Backup & Recovery
- Regular backup of signature database
- Certificate authority backup
- Disaster recovery procedures

### 9.3 Updates & Patches
- Regular security updates
- Certificate authority updates
- PKCS#11 library updates
- Bug fixes and improvements

## 10. COST ESTIMATION

### 10.1 Development Costs
- Backend development: 120 hours
- Frontend development: 80 hours  
- Testing & QA: 60 hours
- Documentation: 20 hours
- **Total: 280 hours**

### 10.2 Infrastructure Costs
- USB Token devices: $50-100 per device
- SSL certificates: $200-500 per year
- Development tools: $500-1000

### 10.3 Ongoing Costs
- Certificate renewal: $200-500 per year
- Maintenance: 10-20 hours per month
- Support: As needed

## 11. RISK ASSESSMENT

### 11.1 Technical Risks
- **USB Token compatibility issues:** Mitigate by testing multiple token types
- **PKCS#11 library stability:** Use well-established libraries
- **Certificate authority integration:** Work with trusted CA providers

### 11.2 Security Risks
- **Private key compromise:** USB Token provides hardware protection
- **PIN attacks:** Implement lockout mechanisms
- **Certificate validation bypass:** Implement comprehensive validation

### 11.3 Operational Risks
- **User adoption:** Provide comprehensive training
- **Token loss/damage:** Implement token replacement procedures
- **System downtime:** Implement redundancy and backup systems

## 12. SUCCESS CRITERIA

### 12.1 Functional Requirements
- ✅ Successful USB Token detection and management
- ✅ Certificate selection and validation
- ✅ Document signing with multiple formats
- ✅ Signature verification and audit trail
- ✅ Integration with existing QLBV systems

### 12.2 Performance Requirements
- Sign document within 10 seconds
- Verify signature within 5 seconds
- Support concurrent users (up to 50)
- 99.9% uptime availability

### 12.3 Security Requirements
- Pass security audit
- Comply with Vietnamese digital signature regulations
- No security vulnerabilities in penetration testing
- Complete audit trail for all operations

---

**Tài liệu này được tạo cho dự án QLBV - Digital Signature Implementation**  
**Version:** 1.0  
**Date:** {{ date('d/m/Y') }}  
**Author:** Development Team
