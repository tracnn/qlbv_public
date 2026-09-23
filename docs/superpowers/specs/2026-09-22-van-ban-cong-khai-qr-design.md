# Thiết kế module Văn bản công khai có QR (VBCK)

Ngày: 2026-09-22 (cập nhật sau review 2026-09-22 và 2026-09-23)
Trạng thái: đã thống nhất, chờ lập kế hoạch triển khai
Nguồn yêu cầu: người dùng đăng nhập qlbv, ký số văn bản hành chính; ảnh ký là mã QR trỏ tới bản
văn bản đã ký được đẩy ra cổng công khai để người dân quét và truy cập.
Tài liệu tham chiếu: `Đặc Tả API Bệnh Án Điện Tử.pdf` (EMR 12C, v2.0 07/2023) — mục 2.b (ghi chú trạng
thái văn bản, tr.25), 2.d `CreateAndSignHsm`, 3.a/3.c `EmrSigner/Get|Update`, 2.a `EmrDocument/Get`.

---

## 1. Mục tiêu

Cho phép người có thẩm quyền, sau khi đăng nhập qlbv bằng tài khoản HIS (ACS), chọn một văn bản
hành chính **đã hoàn thành luồng ký** trong EMR và ký thêm một chữ ký công khai bằng HSM của EMR,
trong đó **khối chữ ký chứa mã QR**. QR trỏ thẳng tới file PDF đã ký, được qlbv đẩy sang một thư mục
tĩnh trên máy chủ công khai (DMZ). Người dân quét QR là mở được đúng bản PDF; tính xác thực nằm ở
chính các chữ ký số trong PDF (kiểm bằng Adobe hoặc công cụ "Kiểm tra văn bản ký số" của NEAC).

### Quyết định đã chốt

| Vấn đề | Quyết định |
|---|---|
| Loại văn bản | Chỉ **văn bản hành chính** → công khai hẳn, không xác thực thêm khi xem |
| Nguồn PDF | File `emr_version` mới nhất của **văn bản EMR có sẵn đã hoàn thành luồng ký** |
| Danh sách chờ ký | Văn bản EMR đã hoàn thành ký, thuộc **loại đã cấu hình "công khai"**, chưa có bản công khai; chỉ user có permission `vbck.ky` |
| Cơ chế ký | **`CreateAndSignHsm`** — tạo văn bản EMR mới cùng hồ sơ với văn bản nguồn và ký luôn; không dùng luồng `EMR_SIGN`/`NEXT_SIGNER` |
| Tên bản mới trong EMR | `"[Công khai] " + tên văn bản nguồn`, cùng loại/nhóm văn bản với nguồn, `HisCode = VBCK-{id}` |
| Chứng thư | HSM của EMR (`192.168.7.239:1415`) |
| Gắn QR | Ưu tiên **`per_call`** nếu API EMR hiện hành nhận ảnh ký theo từng lần gọi (xác minh đầu tiên ở Pha 0). Nếu không: **`swap`** — đổi tạm ảnh ký của người ký thành QR rồi khôi phục. Nhánh phụ `stamp` (vẽ QR vào PDF) chỉ cho file nguồn không có chữ ký số |
| Ai được ký công khai văn bản nào | **Không giới hạn** theo khoa/phòng hay người ký văn bản nguồn: mọi user có `vbck.ky` ký được mọi văn bản thuộc loại đã bật (quyết định có chủ đích) |
| Cổng công khai | Máy chủ riêng trong DMZ, **chỉ là thư mục file tĩnh**, không ứng dụng, không CSDL |
| URL trong QR | `{public_base_url}{token}.pdf`, `token` ngẫu nhiên 128-bit; URL **bất biến vĩnh viễn** |
| Đẩy file | Một chiều nội bộ → DMZ qua disk Laravel `vbck_public`, **driver `local` trỏ share SMB/UNC** (không thêm package composer) |
| Thu hồi | Thay file bằng **PDF tĩnh "Văn bản đã bị thu hồi"** (tên, ngày, lý do) ở đúng URL đó |
| Đăng nhập qlbv | User qlbv vốn là `ACS_RS.acs_user`; khi đăng nhập **gọi thêm API ACS** lấy `TokenCode`, lưu trong session để ký không phải nhập lại mật khẩu. ACS API không phản hồi → **fallback** so hash như hiện nay (đăng nhập được, không ký được). Bật/tắt bằng cờ `auth.acs_enabled` |
| Phân quyền | Giữ Laratrust/`CheckRole`; không tự tạo user từ ACS; `vbck.cau-hinh` chỉ cấp cho lãnh đạo |
| Dữ liệu cá nhân | Cảnh báo bắt buộc xác nhận khi bật một loại văn bản công khai |

### Ngoài phạm vi (v1)

- Ký hàng loạt nhiều văn bản trong một thao tác.
- Kéo thả chỉnh vị trí khung ký trên bản xem trước (v1 dùng cấu hình theo loại văn bản).
- Trang tra cứu/metadata trên cổng, mã tra cứu ngắn nhập tay.
- Văn bản y tế có dữ liệu cá nhân của người bệnh.
- "Mở lại" văn bản đã thu hồi để ký công khai lần nữa.
- Sửa lỗ hổng các route public hiện có (xem mục 11).

---

## 2. Bối cảnh kỹ thuật (đã đối chiếu code)

- EMR đọc qua connection Oracle `EMR_RS` (read-only, query thô): `emr_document`
  (`is_delete`, `is_active`, `document_type_id`, `document_group_id`, `treatment_code`, `his_code`,
  `next_signer`, `create_time`...), `emr_document_type`, `emr_version` (`document_id`, `url`,
  `is_delete`, `is_active`; bản mới nhất = `id` lớn nhất — như `EmrController:484`).
- PDF lấy từ FSS: `organization.fss_config.baseUrl` + `emr_version.url` (`PdfFetchService`; **không bật
  cache** khi dùng cho VBCK).
- `ACSLoginService`: một tài khoản dịch vụ cố định, token trong cache, `isTokenExpired()` trừ hao 5 phút,
  `renewToken()` chưa có API thật (fallback login), `logout()` chỉ xóa cache.
- Đăng nhập hiện tại: `LoginController::customLogin()` — input tên `email` chứa loginname, so
  `sha512(password + salt)` với `CustomUser.password`, bắt buộc `is_active = 1`. Vì override `login()`,
  luồng này **bỏ qua `ThrottlesLogins` và `session()->regenerate()`** của `AuthenticatesUsers`.
- **Server EMR đang chạy bản API mới hơn tài liệu 2023**: `XMLSignService` gọi `EmrSign/SignXmlBhyt`
  (không có trong tài liệu) và truyền **thông tin HSM tường minh mỗi lần gọi** (`ConfigData`: `HsmType`,
  `HsmUserCode`, `Password`, `SecretKey`, `IdentityNumber`, `HsmSerialNumber` — chứng thư đơn vị).
  Chưa rõ `CreateAndSignHsm` bản hiện hành có cần `ConfigData` của từng người ký, hay có tham số ảnh ký
  theo lần gọi hay không → xác minh đầu tiên ở Pha 0.
- `TokenCode` lấy từ ACS với `application_code = HIS` dùng được cho EMR API với header
  `ApplicationCode: EMR` (đang chạy thật trong `XMLSignService`).
- `EmrSigner/Update` nhận **toàn bộ bản ghi** `EmrSigner` (ID, TITLE, DEPARTMENT_*, `PCA_SERIAL`,
  `SIGN_IMAGE`…) kèm `ImgBase64Data`; `EmrSigner/Get` trả `SIGN_IMAGE` (chưa rõ là đường dẫn hay dữ liệu)
  và `ImgBase64Data: null` trong ví dụ.
- Session driver `file`, lifetime 1440 phút. Queue driver `database`, **mỗi queue một service NSSM**
  trong `install_service.bat`.
- **Không dùng Laravel scheduler** (`Kernel::schedule()` rỗng); tác vụ định kỳ chạy dạng service NSSM
  với lệnh vòng lặp (ví dụ `ctdt:import --lien-tuc`, `kiemtraylenh:scan`).
- Đã có: `simplesoftwareio/simple-qrcode ^4.2`, `setasign/fpdi ^2.6` + `fpdf`, `dompdf/dompdf ^2.0`,
  `spatie/activitylog`, Laratrust 5.0. Laravel 5.5, PHP 7.4 — **chưa có `Cache::lock`**.
- API EMR: header `TokenCode` + `ApplicationCode`; GET dùng `?param=<base64 JSON {ApiData, CommonParam}>`;
  kết quả `{Data, Success, Param{Messages, BugCodes}}`.
- `CreateAndSignHsm` bắt buộc `TreatmentCode` — lấy từ văn bản nguồn.
- Theo tài liệu 2023, `SignPdfHsm`/`CreateAndSignHsm` **không có tham số ảnh ký theo lần ký** (bản hiện
  hành chưa rõ — Pha 0 bước A); ảnh ký lấy từ
  `EMR_SIGNER.SIGN_IMAGE`, sửa được qua `EmrSigner/Update` (`ImgBase64Data`).
- Bài học repo phải tuân thủ: không nhận service qua type-hint của `Job::handle()` (khuôn `SubmitCtdtJob`);
  không `RefreshDatabase`; test không phụ thuộc Oracle HIS.

---

## 3. Kiến trúc

```
[Mạng nội bộ]                                             [DMZ]
 Người ký ─ đăng nhập ACS ─> qlbv (module VBCK)            Web server tĩnh (IIS/Nginx)
                              │ đọc   → EMR_RS (Oracle)      └─ vb/{token}.pdf
                              │ API   → ACS Token/Login               ▲
                              │ API   → EMR (CreateAndSignHsm,        │ ghi/thay file (1 chiều)
                              │          EmrSigner/Get|Update,        │
                              │          EmrDocument/Get)             │
                              │ tải   → FSS (emr_version.url)         │
                              ├─ PublishVbckJob (queue vbck) ─────────┤
                              └─ vbck:daemon (guard + verify) ────────┘
 Người dân ─ quét QR ─> {public_base_url}{token}.pdf   (chỉ chạm DMZ)
```

### Thành phần trong qlbv

Namespace `App\Services\Vbck` (trừ phần đăng nhập ACS).

| Thành phần | Trách nhiệm |
|---|---|
| `Auth\AcsAuthenticator` | Gọi ACS `Token/Login` bằng tài khoản người dùng; trả `TokenCode`, `ExpireTime` hoặc ném lỗi phân loại (sai thông tin / ACS lỗi) |
| `Auth\AcsSessionToken` | Lưu/đọc token trong session (mã hóa `Crypt`); `current()` trả null nếu thiếu, còn dưới 5 phút là hết hạn, hoặc phiên ký bị khóa do không thao tác; `touch()` gia hạn mốc hoạt động |
| `EmrApiClientInterface` + `EmrApiClient` | Bọc EMR API: mã hóa `param`, header, phân tích `Success/Messages`, timeout 60 giây; các hàm `createAndSignHsm`, `findDocumentByHisCode`, `findSignerByLoginname`, `getSigner`, `readSignerImage`, `updateSignerImage`. `updateSignerImage` **luôn gửi lại nguyên bản ghi vừa `getSigner`**, chỉ thay ảnh — không bao giờ gửi bản ghi thiếu trường |
| `VbckInboxQuery` | Danh sách văn bản nguồn chờ ký công khai (mục 6.1) |
| `SourceDocumentGuard` | Kiểm một văn bản nguồn đủ điều kiện (đã hoàn thành ký, loại đang bật, chưa công khai) |
| `PublicTokenService` | Sinh `token` 26 ký tự base32 từ `random_bytes(16)`, bảo đảm unique |
| `QrImageBuilder` | Dựng PNG: QR (mức sửa lỗi M, lề 4 module) + dòng chữ "Quét để xem văn bản gốc" |
| `PdfSignatureInspector` | Phân loại PDF: `unsigned` / `signed` / `certified` (có `/DocMDP` hoặc `/Perms`) |
| `QrPlacementStrategy` (interface) | Bọc quanh lời gọi ký: chuẩn bị PDF + `TypeDisplay` + `PointSign`, dọn dẹp sau ký |
| `PerCallImageStrategy` | **Ưu tiên nếu API hỗ trợ.** Giữ nguyên byte PDF, truyền ảnh QR trong chính lời gọi ký, `TypeDisplay=4`; không đụng `EMR_SIGNER`, không khóa, không guard |
| `SwapSignerImageStrategy` | Dùng khi không có `per_call`. Giữ nguyên byte PDF; khóa người ký, sao lưu và đổi `SIGN_IMAGE` thành QR, `TypeDisplay=4`, khôi phục trong `finally` (mục 6.3) |
| `StampQrStrategy` | Nhánh phụ cho PDF `unsigned`: FPDI vẽ QR vào `qr_box`; `TypeDisplay=1` đặt ở `text_box` |
| `SignerImageLock` | MySQL `GET_LOCK('vbck_signer_{signerId}', 15)` / `RELEASE_LOCK` |
| `VbckSignService` | Điều phối luồng ký (mục 6.2) |
| `PublishVbckJob` | Lấy bản đã ký, tính SHA-256, ghi atomically lên disk `vbck_public` |
| `RevokedNoticeBuilder` | Sinh PDF thông báo thu hồi bằng dompdf (hỗ trợ UTF-8 tiếng Việt) |
| `VbckRevokeService` | Thu hồi: ghi đè file công khai bằng PDF thông báo, cập nhật trạng thái |
| Command `vbck:daemon --lien-tuc` | Vòng lặp chạy dưới NSSM: mỗi 60 giây chạy guard ảnh ký (chỉ khi dùng `swap`); mỗi ngày một lần (sau 01:00) chạy verify file công khai. Mốc "lần verify gần nhất" lưu bền trong bảng `vbck_runtime` (key/value), để NSSM khởi động lại service không làm chạy lặp |
| Command `vbck:guard-signer-images`, `vbck:verify-public` | Chạy một lần (daemon gọi lại; dùng tay khi cần) |

`VbckSignService` chỉ phụ thuộc interface `QrPlacementStrategy`; chọn `per_call` hay `swap` bằng cấu hình
`vbck.signed_source_strategy`, không đổi luồng nghiệp vụ. Nếu Pha 0 xác nhận có `per_call` thì **không
xây** `SwapSignerImageStrategy`, `SignerImageLock`, guard và các bảng `vbck_signer_images*`.

### Màn hình mới

1. **Văn bản chờ ký công khai** — danh sách, lọc, xem trước PDF kèm ô ký dự kiến, nút Ký (hộp xác nhận).
   Ký xong hiện ngay khung kết quả: mã văn bản EMR mới, `public_url` (nút sao chép, nút mở), ảnh QR, và
   trạng thái đẩy file tự cập nhật (poll 5 giây/lần tới khi `published` hoặc `publish_failed`).
2. **Văn bản đã công khai** — tra cứu, trạng thái, mở link công khai, Đẩy lại, Thu hồi (bắt buộc lý do),
   cảnh báo đỏ khi có ảnh ký chưa khôi phục hoặc file lệch hash.
3. **Cấu hình loại văn bản công khai** — chọn loại EMR, bật/tắt, khung ký. Khi bật một loại phải tích
   xác nhận: "Tôi xác nhận văn bản thuộc loại này không chứa dữ liệu cá nhân cần bảo vệ (số CCCD, ngày
   sinh, địa chỉ, thông tin sức khỏe…) và được phép công khai". Lưu người và thời điểm xác nhận.
   **Chặn bật** (không chỉ cảnh báo) các loại văn bản y tế mà module khác đang dựa vào: danh sách cấm
   `vbck.forbidden_document_type_ids` mặc định gồm `1, 17, 28, 41, 42` (dùng trong `CheckEmrService`) hợp
   với `organization.patient.emr_document_type_result_ids` (màn tra cứu kết quả của người bệnh). Lý do:
   bản `[Công khai]` cùng loại sẽ lọt vào các màn và phép kiểm đó (mục 14).

---

## 4. Dữ liệu

### 4.1 `vbck_document_types`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | bigint PK | |
| `emr_document_type_id` | bigint unique | Id trong `EMR_DOCUMENT_TYPE` |
| `emr_document_type_code`, `name` | string | Snapshot để hiển thị |
| `is_enabled` | bool | |
| `enabled_confirmed_by`, `enabled_confirmed_at` | nullable | Xác nhận dữ liệu cá nhân khi bật |
| `sign_page` | string | `last` hoặc số trang |
| `text_box_x`, `text_box_y`, `text_box_w`, `text_box_h` | decimal | Khung `PointSign` (hệ toạ độ EMR, xác định ở Pha 0) |
| `qr_box_x`, `qr_box_y`, `qr_box_size` | decimal nullable | Chỉ dùng cho `stamp`; `qr_box_size` ≥ 20 mm |
| `timestamps` | | |

### 4.2 `vbck_publications` — mỗi văn bản nguồn tối đa một bản công khai

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | bigint PK | |
| `source_emr_document_id` | bigint **unique** | Văn bản EMR nguồn |
| `source_document_code`, `source_version_id` | | Snapshot tại lúc ký |
| `document_name`, `emr_document_type_id`, `emr_document_group_id`, `treatment_code` | | Lấy từ nguồn |
| `token` | char(26) **unique** | Cấp một lần, không tái sử dụng |
| `public_url` | string | URL đầy đủ đã in vào QR (lưu vĩnh viễn, không suy lại từ cấu hình) |
| `his_code` | string **unique** | `VBCK-{id}` |
| `qr_strategy` | enum `per_call`,`swap`,`stamp` nullable | |
| `status` | enum | Xem 4.4 |
| `signing_started_at` | nullable | Phát hiện `signing` bị kẹt |
| `emr_document_id`, `emr_document_code` | nullable | Văn bản EMR **mới tạo** |
| `signer_loginname`, `emr_signer_id`, `signed_at` | nullable | |
| `public_path` | nullable | `vb/{token}.pdf` |
| `file_sha256`, `file_size`, `published_at`, `publish_attempts` | nullable | Hash của file đang nằm trên cổng |
| `last_error` | text nullable | |
| `revoked_at`, `revoked_by`, `revoke_reason` | nullable | |
| `timestamps` | | |

### 4.3 Ảnh ký gốc — chỉ khi dùng `swap`

**`vbck_signer_images`** — trạng thái đổi ảnh của từng người ký

| Cột | Ghi chú |
|---|---|
| `emr_signer_id` PK, `loginname` | |
| `swapped_at`, `swapped_publication_id` | Khác null nghĩa là EMR **có thể** đang mang ảnh QR ("bẩn") |
| `restored_at`, `timestamps` | |

**`vbck_signer_image_backups`** — lịch sử bản lưu, **chỉ thêm, không sửa, không xóa**

| Cột | Ghi chú |
|---|---|
| `id` PK, `emr_signer_id`, `loginname` | |
| `signer_record_json` (longtext) | Nguyên bản ghi `EmrSigner` đọc được lúc sao lưu |
| `image_base64` (longtext), `image_sha256` | Ảnh gốc đọc được qua `readSignerImage` |
| `created_at` | |

Quy tắc bất biến:
1. **Chỉ sao lưu khi người ký đang "sạch"** (`swapped_at` null). Khi "bẩn", tuyệt đối không đọc ảnh hiện tại
   làm bản lưu — ảnh đó có thể là QR đã bị EMR nén lại, hash không khớp gì cả.
2. Mỗi lần ký `swap` ở trạng thái sạch: đọc ảnh hiện tại; nếu hash khác bản lưu mới nhất (hoặc chưa có bản
   lưu) thì **thêm** một dòng backup mới. Không so với hash ảnh QR (không tin được sau khi EMR nén).
3. Khôi phục luôn dùng **dòng backup mới nhất có trước `swapped_at`**.
4. Người ký **không có ảnh gốc** (`readSignerImage` rỗng) → không được dùng `swap` (xem 6.3).

### 4.4 Máy trạng thái `vbck_publications.status`

```
reserved ─> signing ─┬─> signed ─> publishing ─┬─> published ─> revoked
    ▲          │     │                         └─> publish_failed ─(Đẩy lại)─> publishing
    │          │     └─> sign_failed ─(Ký lại)─> signing
    │          └─(kẹt > 5 phút)─> được coi như sign_failed khi có yêu cầu ký lại
```

Chuyển trạng thái chỉ qua các hàm của model (`markSigning()`, `markSigned()`...), mỗi hàm kiểm trạng
thái nguồn hợp lệ; chuyển sai ném exception.

### 4.5 Cổng công khai

Không CSDL. Thư mục `vb/` chứa `{token}.pdf`. Cấu hình web server bắt buộc:
- Tắt directory listing; chỉ phục vụ `*.pdf` trong `vb/`; không phục vụ `*.tmp`.
- `Content-Type: application/pdf`, `Content-Disposition: inline`, `X-Robots-Tag: noindex`.
- Domain và đường dẫn `public_base_url` **không bao giờ đổi** sau khi đã in QR; nếu buộc phải đổi hạ tầng
  thì giữ domain cũ và chuyển hướng.

---

## 5. Đăng nhập qlbv bằng ACS

**Bối cảnh quyết định** (đối chiếu code 2026-09-23): qlbv **không có bảng user riêng** — `App\CustomUser` là
bảng `acs_user` trên connection `ACS_RS`, và `customLogin()` so `sha512(password + salt)` với cột `password`
của chính bảng ACS. Tức người dùng **đã** đăng nhập qlbv bằng tài khoản/mật khẩu HIS. Thay đổi ở đây chỉ là
gọi thêm API ACS `Token/Login` để lấy `TokenCode`; người dùng không thấy khác biệt.

0. **Cờ `auth.acs_enabled`** (mặc định `false`): `false` → không gọi API ACS, không có `TokenCode`, module
   VBCK không ký được; `true` → luồng dưới đây. Cờ chỉ để bật/tắt tính năng, không còn là đường lui sự cố
   (đường lui là fallback ở bước 3).
1. Màn login giữ nguyên (input `email` chứa loginname, `password`). Áp dụng **bất kể cờ**:
   - **Chống dò mật khẩu**: `ThrottlesLogins` theo khóa `loginname|IP`, tối đa 5 lần sai mỗi phút, kiểm
     **trước** mọi truy vấn/lời gọi ACS (tránh lợi dụng màn login qlbv để dò hoặc làm khóa tài khoản HIS).
   - Thành công → **`$request->session()->regenerate()`** (chống session fixation — luồng cũ đang thiếu) và
     xóa bộ đếm throttle.
2. Tìm `CustomUser` theo `LOWER(loginname)` và `is_active = 1` (như hiện nay). Không có → báo sai tài khoản
   hoặc mật khẩu (thông báo chung, không lộ tài khoản có tồn tại hay không).
3. `acs_enabled = false` → so hash sha512 như hiện nay. `acs_enabled = true` → `AcsAuthenticator::login()`:
   - **thành công** → `Auth::login($user)`, `AcsSessionToken::store(TokenCode, ExpireTime)`, đặt mốc
     `vbck_last_activity = now`;
   - **ACS trả sai thông tin** (`Success = false` hoặc HTTP 4xx) → báo sai tài khoản hoặc mật khẩu,
     **không** so hash (ACS là nguồn quyết định);
   - **ACS không phản hồi** (lỗi kết nối, timeout, HTTP 5xx, JSON hỏng) → **fallback**: so hash sha512 như
     hiện nay. Đúng → `Auth::login($user)` **không có token**, flash thông báo "Hệ thống xác thực ký số tạm
     thời không phản hồi. Các chức năng khác dùng bình thường; chức năng ký công khai tạm chưa dùng được."
     Log mức warning (không kèm mật khẩu). Fallback không mở lối vào mới vì dùng đúng tài khoản/mật khẩu trong
     cùng bảng `acs_user`.
   - Không ghi mật khẩu, header Basic hay `TokenCode` vào log (kể cả trong exception của Guzzle).
4. Đăng xuất: xóa session.
5. **Khóa phiên ký**: mốc `vbck_last_activity` được **khởi tạo lúc lấy được token (đăng nhập hoặc reauth)** và
   chỉ được làm mới bởi request thuộc nhóm route `vbck/*`. `AcsSessionToken::current()` trả null khi thiếu
   token, token còn dưới 5 phút là hết hạn, hoặc `now - vbck_last_activity > vbck.sign_idle_minutes`
   (mặc định 15). Các màn khác không bị ảnh hưởng và không làm mới mốc.
6. **Nhập lại mật khẩu** (modal trên màn VBCK): `POST vbck/reauth` gọi ACS với `loginname` của user đang
   đăng nhập (không cho đổi tài khoản), lưu token mới. Reauth **không fallback** — mục đích duy nhất là lấy
   token; ACS không phản hồi → báo "Hệ thống xác thực ký số chưa phản hồi, thử lại sau". Reauth cũng qua
   throttle như bước 1.

Token dịch vụ (đổi ảnh ký, chỉ khi `swap`) dùng tài khoản riêng trong `.env` (`VBCK_SERVICE_ACS_USER/PASS`),
đăng nhập và cache như `ACSLoginService` nhưng key cache riêng.

Màn Đổi mật khẩu (`user/changepass`) hiện chỉ hiển thị "Chức năng đang xây dựng..." — **không đổi**.

**Trước khi bật `acs_enabled`:** hỏi nhà cung cấp chính sách khóa tài khoản ACS khi sai mật khẩu nhiều lần
(để đặt ngưỡng throttle thấp hơn ngưỡng khóa).

---

## 6. Luồng nghiệp vụ

### 6.1 Danh sách chờ ký công khai

`VbckInboxQuery` trên `EMR_RS`, điều kiện văn bản nguồn (theo ghi chú trạng thái tr.25 tài liệu EMR):
- `is_delete = 0` (hoặc null), `is_active = 1`;
- **đã hoàn thành ký**: `next_signer` null/rỗng, `rejecter` null/rỗng, `count_resign_wait` null/0,
  `count_resign_failed` null/0 (tên cột `rejecter`, `count_resign_wait` lấy từ tài liệu, repo mới dùng
  `next_signer` và `count_resign_failed` — **đối chiếu với `EMR_RS` thật khi lập kế hoạch**, sai tên thì
  query lỗi ngay chứ không âm thầm lọc sai);
- `document_type_id` thuộc `vbck_document_types` đang `is_enabled`;
- không có `his_code` bắt đầu bằng `VBCK-` (không lấy chính bản công khai làm nguồn);
- loại trừ id đã có publication ở `signing`, `signed`, `publishing`, `published`, `publish_failed`,
  `revoked` (lọc phía PHP theo danh sách id từ MySQL); publication `reserved`/`sign_failed` vẫn hiện;
- lọc ngày tạo (mặc định 30 ngày), tên, mã; phân trang phía server.

Xem trước: `emr_version` mới nhất theo `document_id`, tải qua `PdfFetchService` (không cache).

### 6.2 Ký (đồng bộ trong request)

1. **Token**: `AcsSessionToken::current()`; null → trả `need_reauth`, frontend mở modal (mục 5.6), xong
   gửi lại yêu cầu ký.
2. **Xác nhận**: request phải có `confirmed=1`.
3. **Kiểm nguồn**: `SourceDocumentGuard` (đủ điều kiện 6.1 tại thời điểm bấm).
4. **Publication**: transaction MySQL, `lockForUpdate` theo `source_emr_document_id`:
   - chưa có → tạo, cấp `token`, `public_url`, `his_code`;
   - `signing` và `signing_started_at` < 5 phút trước → từ chối "Văn bản đang được ký";
   - `reserved`, `sign_failed`, hoặc `signing` quá 5 phút → cho ký lại;
   - các trạng thái khác → từ chối.
   Ghi `status = signing`, `signing_started_at = now`.
5. **Đối soát EMR trước khi tạo** (mọi lần, kể cả lần đầu): `findDocumentByHisCode(his_code)`; có kết
   quả → nhận văn bản đó, sang bước 10.
6. **Tải file nguồn** và `PdfSignatureInspector`:
   - `certified` → `sign_failed`, báo "Văn bản được khóa chứng thực, không thể ký thêm";
   - `signed` → `vbck.signed_source_strategy` (`per_call` hoặc `swap`); `unsigned` → `stamp` (nếu FPDI
     không đọc được và `stamp_fallback_to_swap` bật → chiến lược của `signed`).
7. **Dựng ảnh QR** từ `public_url`.
8. **Người ký**: `findSignerByLoginname(loginname)` → `SignerId`; không có → `sign_failed`, báo
   "Tài khoản chưa được khai báo người ký trong EMR".
9. **Gọi ký** (`strategy` bọc quanh) — `createAndSignHsm` bằng token người ký:
   - `TreatmentCode`, `DocumentTypeId`, `DocumentGroupId` từ nguồn;
   - `DocumentName = "[Công khai] " + tên nguồn`; `HisCode = his_code`;
   - `OriginalVersion.Base64Data` = PDF đã chuẩn bị;
   - `PointSign {CoorXRectangle, CoorYRectangle, PageNumber, MaxPageNumber, WidthRectangle,
     HeightRectangle, TextPosition: 0, TypeDisplay}` — `PageNumber` suy từ `sign_page`, khung phải nằm
     trong kích thước trang thực tế, nếu không → `sign_failed` "Khung ký nằm ngoài trang";
   - `Signs = [{SignerId, Loginname, Username, NumOrder: 1}]`.
   `Success=false` → `sign_failed` + `last_error` = `Messages`. Lỗi mạng/timeout → `sign_failed`
   (lần ký lại qua bước 5 sẽ nhận văn bản nếu EMR thực tế đã tạo).
10. **Thành công** → `signed`, lưu `emr_document_id/code`, `emr_signer_id`, `signed_at`; dispatch
    `PublishVbckJob`; activitylog (IP, user-agent).

Giới hạn thời gian: timeout EMR 60 giây < `max_execution_time` của request ký (đặt `set_time_limit(110)`
trong action) < ngưỡng guard 2 phút < ngưỡng `signing` kẹt 5 phút.

### 6.3 `SwapSignerImageStrategy` (chỉ khi không có `per_call`)

1. `SignerImageLock::acquire(signerId, 15s)`; thất bại → "Đang ký văn bản khác, thử lại sau".
2. **Người ký đang "bẩn"** (`swapped_at` khác null) → **thử khôi phục ngay** (như guard); vẫn thất bại →
   `sign_failed` "Ảnh ký đang chờ khôi phục, liên hệ quản trị". **Không bao giờ ký tiếp khi đang bẩn.**
3. `getSigner()` + `readSignerImage()`:
   - không có ảnh gốc → `sign_failed` "Tài khoản chưa có ảnh chữ ký trong EMR, không thể gắn QR" (không
     có cách đưa EMR về trạng thái "không ảnh");
   - có ảnh → sao lưu theo quy tắc 4.3 (thêm dòng nếu khác bản mới nhất).
4. Ghi `swapped_at = now`, `swapped_publication_id` **trước** khi gọi EMR.
5. `updateSignerImage(signerRecord, qrPng)` bằng **token dịch vụ** — gửi lại nguyên bản ghi vừa đọc.
6. (Ký — bước 9 của 6.2, token người ký, `TypeDisplay=4`.)
7. `finally`: `updateSignerImage(signerRecordTừBackup, originalImage)` bằng token dịch vụ, dùng dòng backup
   mới nhất có trước `swapped_at`; sau đó `getSigner()` kiểm các trường ngoài ảnh (đặc biệt `PCA_SERIAL`,
   `TITLE`, `DEPARTMENT_*`) khớp `signer_record_json`. Khớp → xóa `swapped_*`, ghi `restored_at`; không khớp
   hoặc lỗi → giữ nguyên, log critical. `RELEASE_LOCK` trong mọi trường hợp.

Guard (daemon, mỗi 60 giây): mỗi bản ghi `swapped_at` cũ hơn 2 phút → lấy khóa người ký (không lấy được
thì bỏ qua lượt này), khôi phục theo bước 7, xóa `swapped_*` khi kiểm khớp.

**Rủi ro còn lại đã chấp nhận**: khóa chỉ có hiệu lực trong qlbv. Nếu đúng trong vài giây ký, chính người
ký đó ký một văn bản khác trên **EMR client**, văn bản kia sẽ mang ảnh QR. Giảm thiểu: cửa sổ đổi ảnh chỉ
kéo dài bằng một lời gọi ký; hướng dẫn người ký không ký song song trên EMR; loại bỏ hẳn khi có
`PerCallImageStrategy`.

### 6.4 `StampQrStrategy` (nhánh phụ)

FPDI nhập toàn bộ trang, vẽ QR tại `qr_box` của trang ký (mm, gốc trên-trái của FPDI; quy đổi sang hệ
toạ độ `PointSign` theo kết quả Pha 0), xuất PDF mới; `TypeDisplay=1` tại `text_box`. FPDI bỏ annotation,
form, link, bookmark — chấp nhận vì chỉ áp dụng cho PDF chưa ký. Kiểm số trang đầu ra bằng đầu vào.

### 6.5 Đẩy file công khai — `PublishVbckJob`

Queue `vbck`, `tries=3`, backoff 1/5/15 phút. `handle()` **không tham số**; tự khởi tạo service, có thuộc
tính public để test tiêm (khuôn `SubmitCtdtJob`).
1. → `publishing`.
2. Lấy file đã ký: dùng `Base64Data` trong phản hồi `CreateAndSignHsm` nếu Pha 0 xác nhận đó là bản đã
   ký (lưu tạm trên disk local khi ký), nếu không thì `emr_version` mới nhất của `emr_document_id` qua FSS
   (có thể chưa có ngay do độ trễ `_RS` — retry xử lý).
3. Kiểm `%PDF-` và `PdfSignatureInspector` phải ra `signed`.
4. SHA-256, kích thước.
5. Ghi `vb/{token}.pdf.tmp` → đổi tên `vb/{token}.pdf`; đọc lại kích thước xác nhận.
6. → `published`, lưu `public_path`, `file_sha256`, `file_size`, `published_at`.
7. Hết lượt retry → `publish_failed`, `last_error`; nút **Đẩy lại** trên màn quản lý.

### 6.6 Thu hồi

Permission `vbck.thu-hoi`, bắt buộc lý do.
1. `RevokedNoticeBuilder` sinh PDF một trang: tên văn bản, đơn vị ban hành, ngày ký, **"Văn bản này đã
   bị thu hồi ngày … Lý do: …"**.
2. Ghi đè `vb/{token}.pdf` theo cách atomic như 6.5 (tệp chưa tồn tại vẫn ghi).
3. → `revoked`, lưu `revoked_*`, cập nhật `file_sha256` = hash PDF thông báo (để verify không báo lệch).
Văn bản EMR không bị động tới. Văn bản nguồn không xuất hiện lại trong danh sách chờ ký.

### 6.7 Verify file công khai (daemon, hằng ngày)

Với mỗi publication `published`/`revoked`: đọc file trên disk, so SHA-256 với `file_sha256`; thiếu hoặc
lệch → ghi `last_error`, hiện cảnh báo đỏ trên màn quản lý (không tự ghi đè).

---

## 7. Cấu hình

`config/vbck.php` (đọc từ `.env`):
- `public_base_url` — ví dụ `https://congkhai.<bv>.vn/vb/` (**bất biến sau khi vận hành**)
- `public_disk` — mặc định `vbck_public`
- `sign_idle_minutes` — 15
- `signed_source_strategy` — `per_call` hoặc `swap` (chốt sau Pha 0 bước A)
- `stamp_fallback_to_swap` — true (dự phòng bằng chiến lược của `signed_source_strategy`)
- `qr_caption` — "Quét để xem văn bản gốc"
- `document_name_prefix` — "[Công khai] "
- `emr_api.base_url`, `emr_api.application_code`, `emr_api.timeout` (60)
- `service_acs.username`, `service_acs.password`
- `signing_stale_minutes` (5), `guard_after_minutes` (2)

`config/filesystems.php`: disk `vbck_public` — **driver `local`, `root` là đường dẫn UNC share trên máy DMZ**
(ví dụ `\\<dmz-host>\vbck$`). Không dùng SFTP để không phải thêm `league/flysystem-sftp` vào `composer.lock`
của Laravel 5.5. Tài khoản Windows chạy PHP/worker (NSSM) cần quyền ghi/xóa trên share, chỉ trong thư mục `vb/`.
`vbck.forbidden_document_type_ids` — mặc định `[1, 17, 28, 41, 42]`, hợp thêm
`organization.patient.emr_document_type_result_ids` lúc chạy.
`config/auth.php`: `acs_enabled` (mặc định `false`), `acs_timeout` (10 giây),
`login_max_attempts` (5), `login_decay_minutes` (1).
Bảng `vbck_runtime` (key/value) cho mốc chạy của daemon.
Permission mới (Laratrust): `vbck.ky`, `vbck.quan-ly`, `vbck.thu-hoi`, `vbck.cau-hinh` (chỉ lãnh đạo).

---

## 8. Xử lý lỗi

| Tình huống | Xử lý |
|---|---|
| ACS không phản hồi khi đăng nhập | Fallback so hash sha512 → đăng nhập không có token, thông báo chức năng ký tạm không dùng được |
| ACS trả sai thông tin | Báo sai tài khoản/mật khẩu, không so hash |
| Token hết hạn / EMR trả lỗi token | `need_reauth` → modal nhập lại mật khẩu, ký tiếp cùng publication |
| Văn bản nguồn chưa hoàn thành ký / bị mở lại luồng / bị xóa | Chặn ở bước 3, báo rõ |
| Hai request ký cùng văn bản | `lockForUpdate` + `signing` còn mới → từ chối request sau |
| Request chết giữa chừng | `signing` quá 5 phút → cho ký lại; bước 5 nhận văn bản nếu EMR đã tạo |
| PDF nguồn `certified` | `sign_failed`, báo không thể ký thêm |
| FPDI không đọc được PDF chưa ký | `stamp_fallback_to_swap` → `swap` + log; tắt → báo lỗi |
| Loginname chưa có trong `EMR_SIGNER` | `sign_failed`, báo cần khai báo người ký trong EMR |
| Khung ký nằm ngoài trang | `sign_failed`, báo cần sửa cấu hình loại văn bản |
| `CreateAndSignHsm` `Success=false` | `sign_failed`, hiện `Messages`, cho ký lại |
| Lỗi mạng/timeout khi ký | `sign_failed`; lần ký lại đối soát `HIS_CODE` trước |
| Không lấy được khóa người ký | Báo thử lại sau vài giây |
| Khôi phục ảnh ký thất bại / bản ghi người ký sau khôi phục lệch trường | Log critical, guard thử lại mỗi phút, cảnh báo đỏ; người ký bị chặn ký công khai tới khi sạch |
| Người ký chưa có ảnh chữ ký trong EMR (`swap`) | `sign_failed`, báo cần cập nhật ảnh chữ ký trên EMR |
| Đăng nhập sai quá 5 lần/phút | Throttle, báo thử lại sau; không gọi ACS |
| Đẩy file lỗi | Retry 3 lần → `publish_failed`, nút Đẩy lại |
| File trên cổng lệch/mất | Verify hằng ngày cảnh báo đỏ |

---

## 9. Bảo mật

- Token công khai 128-bit ngẫu nhiên; web server tắt listing; `noindex`.
- Kênh đẩy file một chiều; tài khoản SMB/SFTP chỉ có quyền ghi/xóa trong `vb/`; DMZ không giữ credential
  vào mạng nội bộ.
- `TokenCode` ACS chỉ trong session phía server, mã hóa; không ghi log, không trả về frontend.
- Đăng nhập: throttle 5 lần sai/phút theo `loginname|IP` trước khi gọi ACS; `session()->regenerate()` sau
  khi đăng nhập thành công; ACS không phản hồi thì fallback so hash trên cùng bảng `acs_user` (không mở lối
  vào mới), reauth thì không fallback.
- `swap`: không bao giờ ký khi người ký đang "bẩn"; bản lưu ảnh chỉ thêm, không ghi đè; `EmrSigner/Update`
  luôn gửi nguyên bản ghi và kiểm lại các trường sau khôi phục.
- Ký: hộp xác nhận bắt buộc, khóa phiên ký sau 15 phút không thao tác, CSRF, permission kiểm ở route và
  service.
- Nhật ký (activitylog): đăng nhập ACS, ký (IP, user-agent), đổi/khôi phục ảnh ký, đẩy file, thu hồi, bật
  loại văn bản công khai (kèm xác nhận dữ liệu cá nhân).
- Credential mới (tài khoản dịch vụ ACS, SMB/SFTP) để trong `.env`, không hardcode.
- Đánh đổi đã chấp nhận: không nhập lại mật khẩu mỗi lần ký (bù bằng xác nhận, khóa phiên ký, nhật ký);
  rủi ro còn lại của `swap` (mục 6.3).

---

## 10. Triển khai

- Thêm vào `install_service.bat` / `remove_service.bat`:
  - `QLBV JobVbck` → `artisan queue:work --queue=vbck`
  - `QLBV VbckDaemon` → `artisan vbck:daemon --lien-tuc`
- Web server DMZ theo mục 4.5; share/SFTP với quyền hạn chế.
- Tạo permission, gán `vbck.cau-hinh` cho lãnh đạo.
- Bật `AUTH_ACS_ENABLED=true` sau khi đã biết ngưỡng khóa tài khoản của ACS (mục 5). Người dùng không
  cần thông báo: tài khoản/mật khẩu không đổi.
- `QLBV VbckDaemon` vẫn cài khi dùng `per_call` (verify hằng ngày); guard tự bỏ qua khi không có `swap`.
- `.env`: `VBCK_*`, credential dịch vụ ACS, disk công khai.

---

## 11. Phát hiện ngoài phạm vi

Các route public `index/view-doc` và `/api/view-pdf` (trong `routes/web.php`) có dấu hiệu trả PDF EMR
theo mã văn bản/mã điều trị **mà không kiểm token**. Đã tách thành task kiểm tra riêng; module này không sửa.

---

## 12. Kiểm thử

Tuân thủ quy ước repo: không `RefreshDatabase`, giữ chốt an toàn CSDL test, EMR_RS và EMR API đi sau
interface/fake.

- **Unit**: `PublicTokenService`; `PdfSignatureInspector` (fixture unsigned/signed/certified);
  `QrImageBuilder` (kích thước, chuỗi URL truyền vào generator); `StampQrStrategy` (số trang giữ nguyên,
  có ảnh trên đúng trang); kiểm khung ký trong trang; máy trạng thái publication; `RevokedNoticeBuilder`
  (PDF hợp lệ, chứa lý do tiếng Việt).
- **Service với fake** (`FakeEmrApiClient`; HTTP thật của `EmrApiClient` test bằng Guzzle `MockHandler`):
  ký `per_call` thành công (ảnh QR có trong payload); ký `swap` thành công; `stamp` thành công; nguồn chưa
  hoàn thành ký bị chặn; `certified` bị chặn; `Success=false`; lỗi mạng rồi ký lại tìm thấy theo
  `HIS_CODE`; `signing` kẹt quá 5 phút cho ký lại; `signing` còn mới bị từ chối; loginname không có trong
  `EMR_SIGNER`.
- **Riêng `swap`**: khôi phục thất bại → guard khôi phục; khóa bận; **người ký đang bẩn → không ký, không
  đọc ảnh hiện tại làm backup** (kịch bản ảnh QR bị EMR nén lại, hash khác mọi giá trị); backup chỉ thêm
  dòng mới; khôi phục dùng backup mới nhất có trước `swapped_at`; `updateSignerImage` gửi đủ mọi trường
  của bản ghi; sau khôi phục lệch `PCA_SERIAL` → giữ trạng thái bẩn, cảnh báo; người ký không có ảnh gốc →
  `sign_failed`.
- **Đăng nhập ACS**: `acs_enabled=false` → so hash như cũ, không gọi ACS; ACS thành công → vào, có token,
  session được regenerate, mốc `vbck_last_activity` được đặt; ACS sai thông tin → từ chối, không so hash
  (kể cả khi hash khớp); ACS không phản hồi + hash đúng → vào, **không có token**, có flash thông báo; ACS
  không phản hồi + hash sai → từ chối; user không tồn tại / `is_active != 1` → từ chối, **không gọi ACS**;
  sai quá 5 lần/phút → bị throttle và **không gọi ACS**; token hết hạn/idle → `need_reauth`; request ngoài
  `vbck/*` không làm mới mốc idle; reauth không cho đổi tài khoản, không fallback. Rà lại test đăng nhập
  hiện có.
- **Daemon**: khởi động lại không chạy lặp verify trong cùng ngày (mốc trong `vbck_runtime`).
- **Cấu hình loại văn bản**: bật loại nằm trong danh sách cấm (kể cả loại lấy từ
  `organization.patient.emr_document_type_result_ids`) → bị từ chối ở cả form và service.
- **Hồi quy**: test hiện có của đăng nhập, `CheckEmrService`, màn tra cứu người bệnh vẫn xanh với
  `acs_enabled=false`.
- **Publish/Revoke/Verify**: `Storage::fake('vbck_public')` — ghi `.tmp` rồi đổi tên; retry →
  `publish_failed`; thu hồi ghi đè PDF thông báo và cập nhật hash; verify phát hiện thiếu/lệch.
- **Job**: `PublishVbckJob::handle()` không tham số (test quét mã nguồn, bỏ comment trước khi quét —
  `Tests\Support\LocComment`).
- **Nghiệm thu tay** trên EMR test: ký công khai một văn bản đã hoàn thành luồng ký nhiều người; mở file
  công khai bằng Adobe và NEAC — **mọi chữ ký cũ và mới đều hợp lệ**, không báo sửa đổi; in ra giấy, quét
  QR bằng điện thoại mở đúng file; thu hồi → quét lại thấy PDF thông báo.

---

## 13. Phân pha

- **Pha 0 — Xác minh với nhà cung cấp và EMR test (code vứt đi)**. Thứ tự bắt buộc:

  **Bước A — hỏi nhà cung cấp (trước mọi thử nghiệm):**
  1. Xin **tài liệu API EMR bản hiện hành** (server đang chạy bản mới hơn tài liệu 07/2023).
  2. `CreateAndSignHsm`/`SignPdfHsm` bản hiện hành **có nhận ảnh ký theo từng lần gọi** không?
     Có → `signed_source_strategy = per_call`, **bỏ toàn bộ `swap`** (bảng `vbck_signer_images*`, khóa,
     guard, các điểm B6–B9 bên dưới).
  3. Thông tin HSM của người ký lấy từ đâu: EMR tự tra theo user của `TokenCode`, hay phải truyền
     `ConfigData` (như `SignXmlBhyt`)? Nếu phải truyền và là thông tin riêng từng người → **dừng, thiết kế
     lại** phần quản lý bí mật HSM cá nhân (ngoài phạm vi spec này).

  **Bước B — thử trên EMR test:**
  1. `CreateAndSignHsm` ký incremental — mọi chữ ký có sẵn trong file nguồn vẫn hợp lệ (Adobe + NEAC).
  2. Chữ ký EMR hiện tại có phải certification (`/DocMDP`) không; nếu có thì P bằng bao nhiêu.
  3. `SignerId` tra theo `LOGINNAME` hợp lệ khi ký bằng token chính người đó.
  4. Hệ toạ độ `PointSign` (đơn vị, gốc), cách EMR co ảnh ở `TypeDisplay=4`/`TextPosition`; QR sau khi
     EMR vẽ vào PDF, in ra giấy, còn quét được ở kích thước khung dự kiến.
  5. Phản hồi `CreateAndSignHsm` có trả file đã ký trong `Base64Data` không.
  6. *(chỉ khi `swap`)* Ảnh cập nhật qua `EmrSigner/Update` được dùng **ngay** ở lần ký kế tiếp (không cache).
  7. *(chỉ khi `swap`)* Tài khoản dịch vụ được sửa `EMR_SIGNER` của người khác.
  8. *(chỉ khi `swap`)* `EmrSigner/Get` trả ảnh ký dưới dạng nào, và **đọc lại được đúng byte ảnh gốc**
     để khôi phục.
  9. *(chỉ khi `swap`)* `EmrSigner/Update` gửi thiếu trường có ghi null đè không; gửi nguyên bản ghi rồi
     đọc lại có giữ nguyên `PCA_SERIAL`, `TITLE`, `DEPARTMENT_*` không; người ký sau khi đổi-khôi phục
     vẫn ký bình thường trên EMR client.
  10. Tỉ lệ văn bản hoàn thành ký nhưng không có chữ ký số thuộc các loại dự kiến công khai (quyết định
      có giữ `stamp` trong v1 không); FPDI miễn phí đọc được các file đó không.
  11. Thời hạn token ACS; có endpoint renew/logout không.
  12. Tên cột `rejecter`, `count_resign_wait` trên `EMR_RS`.
  13. **Văn bản hành chính nằm trong hồ sơ nào**: thống kê `treatment_code` của các văn bản thuộc loại dự
      kiến công khai. Hồ sơ "hành chính" dành riêng → bản `[Công khai]` không ảnh hưởng module khác. Hồ sơ
      người bệnh thật → phải xem lại (mục 14): ít nhất loại trừ `his_code LIKE 'VBCK-%'` ở các màn liệt kê
      văn bản theo hồ sơ, hoặc dùng loại văn bản đích riêng.
  14. Tài khoản chạy worker ghi/đổi tên/xóa được trên share UNC của DMZ.

  **Điều kiện dừng**: A3 cần bí mật HSM cá nhân; B1 hoặc B2 không đạt; hoặc (khi phải dùng `swap`) một
  trong B6, B8, B9 không đạt.
- **Pha 1** — Throttle + regenerate session (áp dụng ngay, bất kể cờ); đăng nhập gọi ACS sau cờ
  `auth.acs_enabled` (mặc định tắt) với fallback so hash; `AcsSessionToken`. (Reauth thuộc Pha 2 vì gắn với
  màn VBCK.)
- **Pha 2** — Cấu hình loại văn bản, danh sách chờ ký, chiến lược cho file đã ký (`PerCallImageStrategy`,
  hoặc `SwapSignerImageStrategy` + khóa + guard + backup), `vbck:daemon`, ký, publish, thu hồi (PDF thông báo),
  khung kết quả sau ký.
- **Pha 3** — `StampQrStrategy` (chỉ nếu Pha 0 B10 cho thấy cần).
- **Pha 4** — Verify file công khai, màn quản lý hoàn chỉnh, cảnh báo.

---

## 14. Ảnh hưởng tới các module đang chạy

Rà soát 2026-09-23 trên code hiện tại.

| Hạng mục | Mức | Ảnh hưởng | Biện pháp trong spec |
|---|---|---|---|
| Đăng nhập toàn qlbv | Thấp | User qlbv vốn là user ACS, tài khoản/mật khẩu không đổi. Thêm một lời gọi API ACS khi đăng nhập (timeout 10 giây); ACS không phản hồi thì fallback so hash như cũ. Thêm throttle và regenerate session — người gõ sai quá 5 lần/phút phải chờ | Cờ mặc định tắt; fallback (mục 5 bước 3); ngưỡng throttle thấp hơn ngưỡng khóa của ACS |
| Đăng nhập API (`api.auth`, JWT) | Không | Không nơi nào khác dùng mật khẩu user để đăng nhập | — |
| Các màn đọc `emr_document` theo hồ sơ (`PatientController` tra cứu của người bệnh, `KHTHController@viewEmr`, `EmrController` xem/gộp PDF, `BhxhController`, `KskController`, `PdfFlipController`, `CheckEmrService`) | Trung bình, **phụ thuộc Pha 0 B13** | Bản `[Công khai]` cùng hồ sơ, cùng loại sẽ xuất hiện thêm / bị đếm thêm nếu văn bản hành chính nằm trong hồ sơ người bệnh thật | Danh sách loại cấm (mục 3, màn 3); Pha 0 B13; nếu cần thì loại trừ `his_code LIKE 'VBCK-%'` ở các màn trên (việc riêng, lập kế hoạch sau Pha 0) |
| Người ký trên EMR client | Trung bình, **chỉ khi `swap`** | Trong vài giây ký công khai, văn bản khác ký song song trên EMR client có thể mang ảnh QR | Khóa, cửa sổ ngắn, hướng dẫn người ký; hết hẳn nếu có `per_call` |
| Ký XML (QD130, XML3176, CTĐT, TT12 — `XMLSignService`) | Không | Dùng chứng thư đơn vị qua `ConfigData`, không đụng `EMR_SIGNER`; token dịch vụ VBCK dùng key cache riêng | — |
| CSDL qlbv | Không | Chỉ thêm bảng `vbck_*` | — |
| Queue / NSSM | Thấp | Thêm queue `vbck` và 2 service; dùng chung bảng `jobs` | Deploy theo quy trình: rút cạn queue, không làm gián đoạn worker đang chạy |
| `config/*` | Không | Chỉ thêm khóa mới | — |
| Composer | Không | Dùng driver `local` + UNC, không thêm package | — |
| Route public hiện có | Không | Không đụng tới | — |
| Tải EMR server | Không đáng kể | Mỗi lần ký thêm một lời gọi `CreateAndSignHsm` | — |
