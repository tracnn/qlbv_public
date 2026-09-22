# Thiết kế module Văn bản công khai có QR (VBCK)

Ngày: 2026-09-22
Trạng thái: đã thống nhất, chờ lập kế hoạch triển khai
Nguồn yêu cầu: người dùng đăng nhập qlbv, ký số văn bản hành chính; ảnh ký là mã QR trỏ tới bản
văn bản đã ký được đẩy ra cổng công khai để người dân quét và truy cập.
Tài liệu tham chiếu: `Đặc Tả API Bệnh Án Điện Tử.pdf` (EMR 12C, v2.0 07/2023) — mục 2.d
`CreateAndSignHsm`, 3.a/3.c `EmrSigner/Get|Update`, 2.a `EmrDocument/Get`.

---

## 1. Mục tiêu

Cho phép người có thẩm quyền, sau khi đăng nhập qlbv bằng tài khoản HIS (ACS), chọn một văn bản
hành chính đang có trong EMR, ký số bằng HSM của EMR, trong đó **khối chữ ký chứa mã QR**. QR
trỏ thẳng tới file PDF đã ký, được qlbv đẩy sang một thư mục tĩnh trên máy chủ công khai (DMZ).
Người dân quét QR là mở được đúng bản PDF gốc; tính xác thực nằm ở chính chữ ký số trong PDF
(kiểm bằng Adobe hoặc công cụ "Kiểm tra văn bản ký số" của NEAC).

### Quyết định đã chốt

| Vấn đề | Quyết định |
|---|---|
| Loại văn bản | Chỉ **văn bản hành chính** (không chứa dữ liệu sức khỏe) → công khai hẳn, không cần xác thực thêm khi xem |
| Nguồn PDF | Lấy file từ **văn bản EMR có sẵn** (`emr_version` mới nhất trên FSS) |
| Danh sách chờ ký | Mọi văn bản EMR thuộc các **loại đã cấu hình "công khai"**, chưa có bản công khai; chỉ user có permission `vbck.ky` |
| Cơ chế ký | **`CreateAndSignHsm`** — tạo văn bản EMR mới (cùng hồ sơ với văn bản nguồn) và ký luôn; **không dùng luồng ký `EMR_SIGN`/`NEXT_SIGNER`** |
| Chứng thư | HSM của EMR (`192.168.7.239:1415`) |
| Gắn QR | Tự chọn: file nguồn **chưa có chữ ký** → vẽ QR vào PDF (`stamp`); **đã có chữ ký** → đổi tạm ảnh ký của người ký thành QR (`swap`) |
| Cổng công khai | Máy chủ riêng trong DMZ, **chỉ là thư mục file tĩnh**, không ứng dụng, không CSDL |
| URL trong QR | `https://<cong-khai>/vb/{token}.pdf`, `token` ngẫu nhiên 128-bit |
| Đẩy file | Một chiều nội bộ → DMZ qua disk Laravel `vbck_public` (SMB/UNC hoặc SFTP) |
| Đăng nhập qlbv | **Xác thực qua ACS**, lưu `TokenCode` trong session để ký không phải nhập lại mật khẩu |
| Phân quyền | Giữ Laratrust/`CheckRole` hiện có; không tự tạo user từ ACS |
| Thu hồi | Xóa file trên cổng (người quét nhận 404); văn bản EMR giữ nguyên |

### Ngoài phạm vi (v1)

- Ký hàng loạt nhiều văn bản trong một thao tác.
- Kéo thả chỉnh vị trí khung ký trên bản xem trước (v1 dùng cấu hình theo loại văn bản).
- Trang tra cứu/metadata trên cổng, mã tra cứu ngắn nhập tay.
- Văn bản y tế có dữ liệu cá nhân của người bệnh.
- Sửa lỗ hổng các route public hiện có (xem mục 9).

---

## 2. Bối cảnh kỹ thuật

- EMR được đọc qua connection Oracle `EMR_RS` (read-only, query thô, không có Eloquent model):
  `emr_document`, `emr_document_type`, `emr_version`, `emr_treatment`.
- PDF lấy từ FSS: `organization.fss_config.baseUrl` + `emr_version.url` (`PdfFetchService`).
- `ACSLoginService` hiện đăng nhập bằng **một tài khoản dịch vụ cố định** (`organization.login_acs`),
  endpoint `http://192.168.7.200:1401/api/Token/Login`, Basic `HIS:user:pass`.
- User qlbv (`App\CustomUser`) có cột `loginname` trùng HIS/ACS.
- Đã có: `simplesoftwareio/simple-qrcode ^4.2`, `setasign/fpdi ^2.6` + `fpdf`, `spatie/activitylog`,
  Laratrust 5.0. Laravel 5.5, PHP 7.4 — **chưa có `Cache::lock`**.
- API EMR: header `TokenCode` + `ApplicationCode`; GET dùng `?param=<base64 JSON {ApiData, CommonParam}>`;
  kết quả `{Data, Success, Param{Messages, BugCodes}}`.
- `CreateAndSignHsm` bắt buộc `TreatmentCode`; văn bản nguồn trong EMR luôn có sẵn trường này.
- `SignPdfHsm`/`CreateAndSignHsm` **không có tham số ảnh ký theo lần ký**; ảnh ký lấy từ
  `EMR_SIGNER.SIGN_IMAGE`, sửa được qua `EmrSigner/Update` (`ImgBase64Data`).

---

## 3. Kiến trúc

```
[Mạng nội bộ]                                             [DMZ]
 Người ký ─ đăng nhập ACS ─> qlbv (module VBCK)            Web server tĩnh (IIS/Nginx)
                              │ đọc   → EMR_RS (Oracle)      └─ vb/{token}.pdf
                              │ API   → ACS Token/Login               ▲
                              │ API   → EMR (CreateAndSignHsm,        │ ghi/xóa file (1 chiều)
                              │          EmrSigner/Get|Update,        │
                              │          EmrDocument/Get)             │
                              │ tải   → FSS (emr_version.url)         │
                              └─ PublishVbckJob ─ disk vbck_public ───┘
 Người dân ─ quét QR ─> https://<cong-khai>/vb/{token}.pdf   (chỉ chạm DMZ)
```

### Thành phần trong qlbv

Namespace `App\Services\Vbck` (trừ phần đăng nhập ACS).

| Thành phần | Trách nhiệm |
|---|---|
| `Auth\AcsAuthenticator` | Gọi ACS `Token/Login` bằng tài khoản người dùng; trả `TokenCode`, `ExpireTime` hoặc ném lỗi phân loại (sai mật khẩu / ACS lỗi) |
| `Auth\AcsSessionToken` | Lưu/đọc token trong session (mã hóa `Crypt`); `current()` trả null nếu thiếu, hết hạn, hoặc phiên ký bị khóa do không thao tác; `touch()` gia hạn mốc hoạt động |
| `EmrApiClientInterface` + `EmrApiClient` | Bọc EMR API: mã hóa `param`, header, phân tích `Success/Messages`; các hàm `createAndSignHsm`, `findDocumentByHisCode`, `getSigner`, `updateSignerImage` |
| `VbckInboxQuery` | Danh sách văn bản nguồn chờ ký công khai (mục 5.1) |
| `PublicTokenService` | Sinh `token` 26 ký tự base32 từ `random_bytes(16)`, bảo đảm unique |
| `QrImageBuilder` | Dựng PNG: QR (URL công khai) + dòng chữ nhỏ "Quét để xem văn bản gốc", theo tỉ lệ khung ký |
| `PdfSignatureDetector` | Xác định PDF đã có chữ ký số chưa (`/ByteRange` hoặc `/Type /Sig`) |
| `QrPlacementStrategy` (interface) | `prepare(pdfBytes, qrPng, box, signer): PreparedSigning` → PDF gửi đi + `TypeDisplay` + hook trước/sau ký |
| `StampQrStrategy` | FPDI vẽ QR vào PDF tại khung ký; `TypeDisplay=1`; không đụng `EMR_SIGNER` |
| `SwapSignerImageStrategy` | Giữ nguyên byte PDF; khóa người ký, sao lưu và đổi `SIGN_IMAGE` thành QR, `TypeDisplay=4`, khôi phục trong `finally` |
| `SignerImageLock` | MySQL `GET_LOCK('vbck_signer_{signerId}', 15)` / `RELEASE_LOCK` |
| `VbckSignService` | Điều phối luồng ký (mục 5.2) |
| `PublishVbckJob` | Lấy bản đã ký từ FSS, tính SHA-256, ghi atomically lên disk `vbck_public` |
| `VbckRevokeService` | Thu hồi: xóa file công khai, cập nhật trạng thái |
| Command `vbck:guard-signer-images` | Mỗi phút: khôi phục ảnh ký bị kẹt ở trạng thái QR |
| Command `vbck:verify-public` | Hằng đêm: đối chiếu SHA-256 file trên cổng với bản ghi |

Khi nhà cung cấp EMR bổ sung tham số ảnh ký theo từng lần ký (đề nghị song song), thêm
`PerCallImageStrategy` thay cho `SwapSignerImageStrategy`, không đổi `VbckSignService`.

### Màn hình mới

1. **Văn bản chờ ký công khai** — danh sách, lọc, xem trước PDF kèm ô QR dự kiến, nút Ký.
2. **Văn bản đã công khai** — tra cứu, trạng thái, mở link công khai, Đẩy lại, Thu hồi (có lý do),
   cảnh báo đỏ khi có ảnh ký chưa khôi phục hoặc file lệch hash.
3. **Cấu hình loại văn bản công khai** — chọn loại EMR, bật/tắt, khung ký mặc định.

---

## 4. Dữ liệu

### 4.1 `vbck_document_types` — loại văn bản EMR được công khai

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | bigint PK | |
| `emr_document_type_id` | bigint unique | Id trong `EMR_DOCUMENT_TYPE` |
| `emr_document_type_code`, `name` | string | Snapshot để hiển thị |
| `is_enabled` | bool | |
| `sign_page` | string | `last` hoặc số trang |
| `coor_x`, `coor_y`, `width`, `height` | decimal | Khung ký (đơn vị theo `PointSign` của EMR) |
| `timestamps` | | |

### 4.2 `vbck_publications` — mỗi văn bản nguồn tối đa một bản công khai

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | bigint PK | |
| `source_emr_document_id` | bigint **unique** | Văn bản EMR nguồn |
| `source_document_code`, `source_version_id` | | Snapshot tại lúc ký |
| `document_name`, `emr_document_type_id`, `treatment_code` | | Lấy từ nguồn |
| `token` | char(26) **unique** | Trong URL QR; cấp một lần, không tái sử dụng sau thu hồi |
| `his_code` | string **unique** | `VBCK-{id}`, gửi vào `HisCode` để chống tạo trùng |
| `qr_strategy` | enum `stamp`,`swap` | |
| `status` | enum | Xem 4.4 |
| `emr_document_id`, `emr_document_code` | nullable | Văn bản EMR **mới tạo** |
| `signer_loginname`, `emr_signer_id`, `signed_at` | nullable | |
| `public_path` | nullable | `vb/{token}.pdf` |
| `file_sha256`, `file_size`, `published_at`, `publish_attempts` | nullable | |
| `last_error` | text nullable | |
| `revoked_at`, `revoked_by`, `revoke_reason` | nullable | |
| `timestamps` | | |

### 4.3 `vbck_signer_images` — bản lưu ảnh ký gốc (chỉ dùng cho `swap`)

| Cột | Ghi chú |
|---|---|
| `emr_signer_id` PK, `loginname` | |
| `original_image_base64` (longtext), `original_image_sha256` | Sao lưu trước lần đổi đầu tiên; cập nhật khi phát hiện người dùng tự đổi ảnh gốc trên EMR (hash hiện tại khác bản lưu và khác hash ảnh QR vừa đặt) |
| `swapped_at` nullable, `swapped_publication_id` nullable, `swapped_qr_sha256` nullable | Khác null nghĩa là EMR đang mang ảnh QR |
| `restored_at`, `timestamps` | |

### 4.4 Máy trạng thái `vbck_publications.status`

```
reserved ─> signing ─┬─> signed ─> publishing ─┬─> published ─> revoked
                     │                         └─> publish_failed ─(Đẩy lại)─> publishing
                     └─> sign_failed ─(Ký lại)─> signing
```

Chuyển trạng thái chỉ qua các hàm của model (`markSigning()`, `markSigned()`...), mỗi hàm kiểm trạng
thái nguồn hợp lệ; chuyển sai ném exception.

### 4.5 Cổng công khai

Không CSDL. Thư mục `vb/` chứa `{token}.pdf`. Cấu hình web server bắt buộc:
- Tắt directory listing; chỉ phục vụ `*.pdf` trong `vb/`.
- `Content-Type: application/pdf`, `Content-Disposition: inline`.
- `X-Robots-Tag: noindex` (mặc định bật, ghi trong hướng dẫn triển khai).

---

## 5. Luồng nghiệp vụ

### 5.1 Danh sách chờ ký công khai

`VbckInboxQuery` trên `EMR_RS`:
- `emr_document.is_delete = 0` (hoặc null) và `is_active = 1`;
- `document_type_id` thuộc `vbck_document_types` đang `is_enabled`;
- loại trừ `id` đã có publication trạng thái `signing`, `signed`, `publishing`, `published`, `publish_failed`,
  `revoked` (lọc phía PHP theo danh sách id từ MySQL, vì hai CSDL khác nhau); publication `reserved` hoặc
  `sign_failed` vẫn hiện để ký lại với cùng token;
- lọc theo ngày tạo (mặc định 30 ngày gần nhất), tên, mã; phân trang phía server.

Xem trước: tải `emr_version` mới nhất theo `document_id` (id lớn nhất) qua `PdfFetchService`.

### 5.2 Ký (đồng bộ trong request, dự kiến 2–5 giây)

1. **Token**: `AcsSessionToken::current()`; null → trả mã `need_reauth`, frontend mở modal nhập lại
   mật khẩu (5.4), xong thì gửi lại yêu cầu ký.
2. **Xác nhận**: request phải kèm cờ `confirmed=1` (hộp "Tôi đã đọc và đồng ý ký văn bản …").
3. **Kiểm lại nguồn**: văn bản còn tồn tại, loại đang bật, chưa có bản công khai hợp lệ.
4. **Publication**: upsert theo `source_emr_document_id` trong transaction MySQL (`lockForUpdate`);
   lần đầu cấp `token`, `his_code`; trạng thái → `signing`. Nếu đang `signing` bởi request khác → từ chối.
5. **Chuẩn bị**: tải PDF nguồn; `PdfSignatureDetector` chọn `stamp`/`swap`; dựng ảnh QR;
   tính khung ký (`sign_page=last` → số trang thực tế).
6. **Chống trùng khi ký lại**: nếu publication từng `sign_failed` do timeout, gọi
   `findDocumentByHisCode(his_code)`; có kết quả → nhận văn bản đó, sang bước 9.
7. **Gọi ký**, `strategy->prepare()` bọc quanh `createAndSignHsm` với:
   - `TreatmentCode`, `DocumentTypeId`, `DocumentGroupId`, `DocumentName` từ nguồn; `HisCode = his_code`;
   - `OriginalVersion.Base64Data` = PDF đã chuẩn bị;
   - `PointSign {CoorXRectangle, CoorYRectangle, PageNumber, MaxPageNumber, WidthRectangle,
     HeightRectangle, TextPosition: 0, TypeDisplay}`;
   - `Signs = [{SignerId, Loginname, Username, NumOrder: 1}]` — `SignerId` tra bằng
     `EmrSigner/Get` theo `LOGINNAME__EXACT`.
8. **Kết quả EMR**: `Success=false` → `sign_failed` + `last_error` = `Messages`; timeout → `sign_failed`
   (lần ký lại sẽ qua bước 6).
9. **Thành công**: → `signed`, lưu `emr_document_id/code`, `emr_signer_id`, `signed_at`; dispatch
   `PublishVbckJob`. Ghi activitylog (IP, user-agent).

### 5.3 Chi tiết `SwapSignerImageStrategy`

1. `SignerImageLock::acquire(signerId, 15s)`; thất bại → lỗi "Đang ký văn bản khác, thử lại sau".
2. `getSigner()` lấy ảnh hiện tại; cập nhật bản lưu `vbck_signer_images` nếu chưa có hoặc người dùng
   đã tự đổi ảnh gốc (quy tắc ở 4.3).
3. Ghi `swapped_at`, `swapped_publication_id`, `swapped_qr_sha256` **trước** khi gọi EMR.
4. `updateSignerImage(signer, qrPng)` bằng **token dịch vụ**.
5. (Ký — bước 7 của 5.2, bằng token người ký.)
6. `finally`: `updateSignerImage(signer, original)` bằng token dịch vụ → thành công thì xóa
   `swapped_*`, ghi `restored_at`; thất bại thì giữ nguyên `swapped_*`, log critical.
   `RELEASE_LOCK` trong mọi trường hợp.

`vbck:guard-signer-images` (mỗi phút): với mỗi bản ghi `swapped_at` cũ hơn 2 phút, lấy khóa người ký,
khôi phục ảnh gốc, xóa `swapped_*`.

### 5.4 Đăng nhập qlbv bằng ACS

1. Màn login giữ nguyên, nhận `loginname` + mật khẩu.
2. Nếu `loginname` thuộc `auth.local_accounts` → xác thực mật khẩu local như hiện nay (tài khoản
   này **không ký được**).
3. Ngược lại → `AcsAuthenticator::login()`:
   - sai thông tin → báo sai tài khoản/mật khẩu;
   - ACS lỗi/timeout → báo "Không kết nối được hệ thống xác thực", **không fallback**;
   - thành công nhưng không có `CustomUser` cùng `loginname` → báo "Tài khoản chưa được cấp quyền trên qlbv";
   - thành công → `Auth::login($user)`, `AcsSessionToken::store(TokenCode, ExpireTime)`.
4. Đăng xuất: xóa session (gọi ACS logout nếu nhà cung cấp có endpoint).
5. **Khóa phiên ký**: `AcsSessionToken::current()` trả null nếu lần thao tác ký/xem màn VBCK gần nhất
   quá `vbck.sign_idle_minutes` (mặc định 15). Các màn khác của qlbv không bị ảnh hưởng.
6. **Nhập lại mật khẩu** (modal ở màn VBCK): endpoint `POST vbck/reauth` gọi ACS với `loginname` của
   user đang đăng nhập (không cho đổi tài khoản), lưu token mới.

Token dịch vụ (đổi ảnh ký) dùng tài khoản riêng cấu hình trong `.env` (`VBCK_SERVICE_ACS_USER/PASS`),
đăng nhập và cache giống `ACSLoginService` nhưng key cache riêng.

### 5.5 Đẩy file công khai — `PublishVbckJob`

Queue riêng `vbck`, `tries=3`, backoff 1/5/15 phút.
1. Trạng thái → `publishing`.
2. Lấy `emr_version` mới nhất của `emr_document_id` (văn bản mới), tải từ FSS; kiểm tra header `%PDF-`
   và `PdfSignatureDetector` phải thấy chữ ký.
3. SHA-256, kích thước.
4. Ghi `vb/{token}.pdf.tmp` → đổi tên `vb/{token}.pdf` (disk hỗ trợ `move`); đọc lại kích thước xác nhận.
5. → `published`, lưu `public_path`, `file_sha256`, `file_size`, `published_at`.
6. Hết lượt retry → `publish_failed`, `last_error`; màn "Đã công khai" có nút **Đẩy lại**.

### 5.6 Thu hồi

Yêu cầu permission `vbck.thu-hoi` và lý do. Xóa `vb/{token}.pdf` trên disk (không tồn tại vẫn coi là
thành công) → `revoked`, lưu `revoked_*`. Văn bản EMR không bị động tới. Token không tái sử dụng; văn
bản nguồn sau thu hồi **không** xuất hiện lại trong danh sách chờ ký (tránh hai QR cho cùng văn bản) —
nếu cần ký lại phải thao tác "Mở lại" có chủ đích (ngoài phạm vi v1).

---

## 6. Cấu hình

`config/vbck.php` (đọc từ `.env`):
- `public_base_url` — ví dụ `https://congkhai.<bv>.vn/vb/`
- `public_disk` — mặc định `vbck_public`
- `sign_idle_minutes` — 15
- `stamp_fallback_to_swap` — true: FPDI không đọc được PDF chưa ký thì chuyển sang `swap`
- `qr_caption` — "Quét để xem văn bản gốc"
- `emr_api.base_url`, `emr_api.application_code`, `emr_api.timeout`
- `service_acs.username`, `service_acs.password`

`config/filesystems.php` thêm disk `vbck_public` (driver `local` trỏ UNC share DMZ, hoặc `sftp`
qua `league/flysystem-sftp` bản tương thích Flysystem 1).
`config/auth.php` thêm `local_accounts` (mảng loginname).
Permission mới (Laratrust): `vbck.ky`, `vbck.quan-ly` (xem danh sách đã công khai, đẩy lại),
`vbck.thu-hoi`, `vbck.cau-hinh`.

---

## 7. Xử lý lỗi

| Tình huống | Xử lý |
|---|---|
| ACS không phản hồi khi đăng nhập | Báo lỗi, không fallback (trừ `local_accounts`) |
| Token hết hạn / EMR trả lỗi token khi ký | `need_reauth` → modal nhập lại mật khẩu, ký tiếp với cùng publication |
| Văn bản nguồn bị xóa / đã có bản công khai | Chặn ở bước 3, báo rõ |
| Hai request ký cùng văn bản | `lockForUpdate` + trạng thái `signing` → request sau bị từ chối |
| FPDI không đọc được PDF chưa ký | `stamp_fallback_to_swap` → dùng `swap` + log; tắt → báo lỗi |
| `CreateAndSignHsm` `Success=false` | `sign_failed`, hiện `Messages`, cho ký lại |
| `CreateAndSignHsm` timeout | `sign_failed`; lần sau tra `HIS_CODE` trước khi tạo |
| Không lấy được khóa người ký | Báo thử lại sau vài giây |
| Khôi phục ảnh ký thất bại | Log critical, guard thử lại mỗi phút, cảnh báo đỏ trên màn quản lý |
| Đẩy file lỗi | Retry 3 lần → `publish_failed`, nút Đẩy lại |
| File trên cổng lệch/mất | `vbck:verify-public` cảnh báo trên màn quản lý |

---

## 8. Bảo mật

- Token công khai 128-bit ngẫu nhiên; web server tắt listing; `noindex`.
- Kênh đẩy file một chiều; tài khoản SMB/SFTP chỉ có quyền ghi/xóa trong `vb/`; DMZ không giữ
  credential vào mạng nội bộ.
- `TokenCode` ACS chỉ trong session phía server, mã hóa; không ghi log, không trả về frontend.
- Ký: hộp xác nhận bắt buộc, khóa phiên ký sau 15 phút không thao tác, CSRF, permission kiểm ở
  route và service.
- Nhật ký (activitylog): đăng nhập ACS, ký (IP, user-agent), đổi/khôi phục ảnh ký, đẩy file, thu hồi.
- Credential mới (tài khoản dịch vụ ACS, SMB/SFTP) để trong `.env`, không hardcode.
- Đánh đổi đã chấp nhận: không nhập lại mật khẩu mỗi lần ký; bù bằng hộp xác nhận, khóa phiên ký
  theo thời gian không thao tác, và nhật ký.

---

## 9. Phát hiện ngoài phạm vi

Các route public `index/view-doc` và `/api/view-pdf` (trong `routes/web.php`) có dấu hiệu trả PDF EMR
theo mã văn bản/mã điều trị **mà không kiểm token** — nếu đúng, bất kỳ ai biết mã có thể xem bệnh án.
Cần một task kiểm tra riêng; module này không sửa.

---

## 10. Kiểm thử

Tuân thủ quy ước repo: không `RefreshDatabase`, giữ chốt an toàn CSDL test, không phụ thuộc Oracle HIS
(EMR_RS query đi sau interface/fake).

- **Unit**: `PublicTokenService` (độ dài, bảng ký tự, unique); `PdfSignatureDetector` (fixture có/không
  chữ ký); `QrImageBuilder` (kích thước; giải mã QR ra đúng URL nếu có thư viện đọc QR, nếu không thì
  kiểm chuỗi truyền vào generator); `StampQrStrategy` (số trang giữ nguyên, có ảnh trên đúng trang);
  máy trạng thái publication (chuyển hợp lệ/không hợp lệ).
- **Service với fake** (`FakeEmrApiClient`; HTTP thật của `EmrApiClient` test bằng Guzzle `MockHandler`):
  ký thành công (`stamp`, `swap`); `Success=false`; timeout rồi ký lại tìm thấy theo `HIS_CODE`;
  khôi phục ảnh thất bại → guard khôi phục; khóa bận; hai request đồng thời.
- **Đăng nhập ACS**: có ACS + có user → vào; có ACS + không user → từ chối; `local_accounts` → mật khẩu
  local; ACS lỗi → không fallback; token hết hạn/idle → màn ký trả `need_reauth`; reauth không cho đổi
  tài khoản. Rà lại các test đăng nhập hiện có.
- **Publish/Revoke**: `Storage::fake('vbck_public')` — ghi `.tmp` rồi đổi tên, retry → `publish_failed`,
  thu hồi xóa file, verify-public phát hiện lệch hash.
- **Nghiệm thu tay** trên EMR test: ký một văn bản chưa có chữ ký và một văn bản đã có chữ ký; mở file
  công khai bằng Adobe và NEAC — mọi chữ ký hợp lệ; QR quét bằng điện thoại mở đúng file.

---

## 11. Phân pha

- **Pha 0 — Spike xác minh với EMR test (code vứt đi)**. Kết quả quyết định có đi tiếp nguyên thiết kế:
  1. `CreateAndSignHsm` ký incremental — chữ ký có sẵn trong file nguồn vẫn hợp lệ sau khi ký thêm.
  2. `CreateAndSignHsm` với token của người ký dùng đúng HSM của người đó; có cần PIN/serial không.
  3. `SignerId` tra theo `LOGINNAME` hợp lệ khi ký bằng token chính người đó.
  4. Ảnh cập nhật qua `EmrSigner/Update` được dùng **ngay** ở lần ký kế tiếp (không cache).
  5. Tài khoản dịch vụ được sửa `EMR_SIGNER` của người khác.
  6. FPDI bản miễn phí đọc được PDF EMR thật (xref stream nén); nếu không: `qpdf` giải nén trước,
     FPDI PDF-Parser thương mại, hoặc dùng `swap` cho mọi file.
  7. Thời hạn token ACS; có endpoint renew/logout không.
  Nếu (1) hoặc (4) không đạt → dừng, quay lại thiết kế (phương án C: đề nghị nhà cung cấp thêm
  tham số ảnh ký theo lần ký).
- **Pha 1** — Đăng nhập ACS, `AcsSessionToken`, reauth, `local_accounts`.
- **Pha 2** — Cấu hình loại văn bản, danh sách chờ ký, `StampQrStrategy`, ký, publish, thu hồi.
- **Pha 3** — `SwapSignerImageStrategy`, `SignerImageLock`, guard.
- **Pha 4** — `vbck:verify-public`, màn quản lý hoàn chỉnh, cảnh báo.

Song song: gửi nhà cung cấp EMR đề nghị bổ sung tham số ảnh ký theo từng lần gọi
(`PointSign.ImgBase64Data`) để thay `swap` bằng `PerCallImageStrategy`.
