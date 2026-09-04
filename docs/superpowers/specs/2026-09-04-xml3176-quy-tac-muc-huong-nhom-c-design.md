# XML3176 — Nhóm C: Quy tắc kiểm tra Mức hưởng (design)

**Ngày:** 2026-09-04
**Module:** XML3176 (kiểm tra hồ sơ BHYT trước khi gửi cổng)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Bổ sung rule phát hiện hồ sơ khai `muc_huong` (mức hưởng BHYT) **vượt trần cho phép** — nguyên nhân xuất toán "Thay đổi mức hưởng" (1.033 dòng trong file giám định cơ sở 01929 kỳ 08/2026). Bắt lỗi **trước khi gửi** để cơ sở tự sửa.

## 2. Bối cảnh & bằng chứng

File giám định `20260903075424_01929_202608.xlsx` cho hai chuyên đề mức hưởng, đã giải mã chắc chắn từ dữ liệu:

| Chuyên đề | Số dòng | Điều kiện | Mức hưởng đúng | Bằng chứng dữ liệu |
|---|---|---|---|---|
| 1. Đúng tuyến | 803 | không trái tuyến + chi phí ≥ 15% lương cơ sở | = quyền lợi thẻ | 803 dòng đều quyền lợi '4', tỷ lệ giảm/đề nghị = 0.2 (khai 100 → đúng 80) |
| 2. Trái tuyến nội trú TW | 230 | trái tuyến + nội trú + cơ sở tuyến TW | 40% | 230 dòng nội trú, tỷ lệ giảm = 0.6 (khai 100 → đúng 40), quyền lợi '4'+'2' |

**Bản chất:** cơ sở khai `muc_huong = 100%` vượt trần. Rule so sánh `muc_huong` khai với trần cho phép; **chỉ báo khi khai > trần** (khai thấp hơn không phải xuất toán).

## 3. Phạm vi

**Trong phạm vi:**
- Rule 1 — đúng tuyến, chi phí ≥ 15% lương cơ sở, `muc_huong` vượt quyền lợi thẻ.
- Rule 2 — trái tuyến nội trú tuyến TW, `muc_huong` vượt 40% (**có guard**: bỏ qua khi không xác định được cơ sở là tuyến TW).
- Phát **1 lỗi / hồ sơ** (mức hưởng đúng là thuộc tính của cả hồ sơ, không phải từng dòng).

**Ngoài phạm vi (bỏ qua có chủ đích, không báo lỗi):**
- Trái tuyến ngoại trú, trái tuyến nội trú tuyến tỉnh (quy tắc % khác, để đợt sau).
- Thuốc/VTYT sai tỷ lệ TT (đã xử lý ở rule `XML3_INVALID_APPROVED_TYLE_TT_BH`).
- Trường hợp thiếu căn cứ (xem §7) — im lặng để tránh dương tính giả.

## 4. Kiến trúc

Đặt tại `app/Services/Xml3176CompleteChecker.php` — checker liên-XML, đọc thẻ/tuyến/chi phí từ XML1 (`Xml3176Xml1 $data`) và `muc_huong` từ các dòng con `$data->Xml3176Xml2()` / `$data->Xml3176Xml3()`. Prefix error = `XMLComplete_`; lưu qua `saveErrors('XMLComplete', $data->ma_lk, $data->stt, $errors)`.

Logic tính trần tách vào helper thuần `app/Services/Xml3176/Support/MucHuongCalculator.php` để unit test không cần DB (theo khuôn `LieuDungParser`, `DrugCatalogAttrChecker`, `TyLeComparator`).

### 4.1 Config mới — `config/xml3176.php`, khối `muc_huong`

```php
'muc_huong' => [
    // Ký tự quyền lợi (vị trí 3 mã thẻ) -> % mức hưởng. Chỉnh được không cần sửa code.
    'quyen_loi_map'              => ['1' => 100, '2' => 100, '3' => 95, '4' => 80, '5' => 100],
    'nguong_luong_co_so_rate'    => 0.15, // Ngưỡng 15% lương cơ sở cho miễn cùng chi trả
    'trai_tuyen_noi_tru_tw_rate' => 40,   // % mức hưởng trái tuyến nội trú tuyến TW
    'tuyen_tw_values'            => ['1'], // Giá trị medical_organizations.tuyen_cmkt coi là tuyến TW
],
```

Tái dùng config sẵn có: `xml3176.xml1.ma_doituong_kcb_trai_tuyen` (đầu mã đối tượng trái tuyến, `['3']`), `xml3176.treatment_type_inpatient` (loại KCB nội trú), `mcct.luong_co_so` (mốc lương cơ sở theo ngày).

### 4.2 Helper thuần `MucHuongCalculator`

```php
namespace App\Services\Xml3176\Support;

class MucHuongCalculator
{
    /**
     * Trích ký tự quyền lợi (vị trí thứ 3) từ mã thẻ.
     * Mã thẻ có thể là danh sách ngăn cách ';' (thẻ cũ/mới). Nếu các đoạn cho
     * quyền lợi KHÁC nhau -> trả null (mơ hồ, bỏ qua). Đoạn < 3 ký tự bị bỏ.
     *
     * @return string|null ký tự '1'..'9' hoặc null nếu không xác định
     */
    public static function quyenLoiChar($maThe): ?string;

    /**
     * Tra % mức hưởng theo ký tự quyền lợi. null nếu không có trong map.
     */
    public static function entitlement(?string $qlChar, array $map): ?int;

    /**
     * Trần mức hưởng cho phép khi ĐÚNG TUYẾN.
     *  - $luongCoSo null hoặc <= 0 -> null (guard: chưa có mốc lương).
     *  - $entitlement null -> null (guard).
     *  - chi phí >= rate * luongCoSo -> $entitlement (áp quyền lợi thẻ).
     *  - chi phí <  rate * luongCoSo -> 100 (miễn: chi phí nhỏ vẫn 100%).
     *
     * @param float $chiPhi tổng chi BHYT một lần KCB
     * @param float $rate   tỷ lệ ngưỡng (0.15)
     */
    public static function tranDungTuyen(?int $entitlement, float $chiPhi, ?int $luongCoSo, float $rate): ?int;
}
```

Quyết định phân tách: helper KHÔNG biết DB. Việc phân loại trái/đúng tuyến, nội/ngoại trú, tra `tuyen_cmkt`, tra lương cơ sở theo ngày, và duyệt dòng con — đều nằm ở checker.

### 4.3 Checker `Xml3176CompleteChecker::checkMucHuong(Xml3176Xml1 $data): Collection`

Nối vào `checkErrors()`: thêm `$errors = $errors->merge($this->checkMucHuong($data));`.

Luồng (mã giả):

```
$errors = collect();

$qlChar = MucHuongCalculator::quyenLoiChar($data->ma_the_bhyt);
if ($qlChar === null) return $errors;                       // guard: quyền lợi mơ hồ
$entitlement = MucHuongCalculator::entitlement($qlChar, config('xml3176.muc_huong.quyen_loi_map'));
if ($entitlement === null) return $errors;                  // guard: không map được

$traiTuyen = đầu ký tự $data->ma_doituong_kcb ∈ config('xml3176.xml1.ma_doituong_kcb_trai_tuyen');
$noiTru    = in_array($data->ma_loai_kcb, config('xml3176.treatment_type_inpatient'));

if ($traiTuyen) {
    if (!$noiTru) return $errors;                           // ngoài phạm vi
    $tuyen = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->value('tuyen_cmkt');
    if (!in_array($tuyen, config('xml3176.muc_huong.tuyen_tw_values'), true)) return $errors; // GUARD tuyến
    $tran = (int) config('xml3176.muc_huong.trai_tuyen_noi_tru_tw_rate'); // 40
    $errorKey = 'MUC_HUONG_TRAI_TUYEN_TW';
    $loaiMo   = 'trái tuyến nội trú tuyến TW';
} else {
    $dt   = Xml3176DateHelper::toDateTime($data->ngay_vao); // ngay_vao dạng 'YmdHi'
    if ($dt === null) return $errors;                       // guard: ngày vào không hợp lệ
    $ngay = $dt->format('Y-m-d');                           // khớp key config mcct.luong_co_so
    $lcs  = NguongMienCungChiTra::luongCoSoTaiNgay($ngay, (array) config('mcct.luong_co_so', []));
    $chiPhi = (float) $data->t_tongchi_bh;
    $tran = MucHuongCalculator::tranDungTuyen($entitlement, $chiPhi, $lcs ?: null,
                (float) config('xml3176.muc_huong.nguong_luong_co_so_rate'));
    if ($tran === null) return $errors;                     // guard
    $errorKey = 'MUC_HUONG_EXCEEDS_ENTITLEMENT';
    $loaiMo   = 'đúng tuyến';
}

// max muc_huong các dòng con (bỏ null)
$maxXml2 = $data->Xml3176Xml2()->whereNotNull('muc_huong')->max('muc_huong');
$maxXml3 = $data->Xml3176Xml3()->whereNotNull('muc_huong')->max('muc_huong');
$maxDeclared = max((float) $maxXml2, (float) $maxXml3);      // null coi như 0
if ($maxXml2 === null && $maxXml3 === null) return $errors;  // guard: không có mức hưởng khai

if ($maxDeclared > $tran + 0.01) {
    $errorCode = $this->generateErrorCode($errorKey);
    $errors->push((object)[
        'error_code'     => $errorCode,
        'error_name'     => 'Mức hưởng khai vượt trần cho phép',
        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
        'description'    => 'Hồ sơ ' . $loaiMo . ': mức hưởng khai tối đa ' . $maxDeclared
                          . '%, trần cho phép ' . $tran . '% (quyền lợi thẻ ' . $entitlement . '%).',
    ]);
}
return $errors;
```

### 4.4 Error code & seeder

Hai code, seeder mới `database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php` (idempotent `updateOrCreate`, `xml = 'XMLComplete'`, `critical_error = true`, `is_check = true`):

| xml | error_code | error_name |
|---|---|---|
| XMLComplete | `XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT` | Mức hưởng vượt quyền lợi thẻ (đúng tuyến, chi phí ≥ 15% lương cơ sở) |
| XMLComplete | `XMLComplete_MUC_HUONG_TRAI_TUYEN_TW` | Mức hưởng vượt 40% (trái tuyến nội trú tuyến TW) |

## 5. Luồng dữ liệu

```
Xml3176Xml1 (hồ sơ)
 ├─ ma_the_bhyt ──► quyenLoiChar ──► entitlement (map config)
 ├─ ma_doituong_kcb ──► trái/đúng tuyến
 ├─ ma_loai_kcb ──► nội/ngoại trú
 ├─ ma_cskcb ──► MedicalOrganization.tuyen_cmkt ──► có phải TW? (Rule 2)
 ├─ ngay_vao ──► luongCoSoTaiNgay ──► ngưỡng 15% (Rule 1)
 ├─ t_tongchi_bh ──► chi phí một lần KCB (Rule 1)
 └─ Xml3176Xml2/Xml3.muc_huong ──► max khai ──► so với trần
```

## 6. Xử lý lỗi & so sánh

- So sánh `maxDeclared > tran + 0.01` (dung sai nhỏ vì `muc_huong` là double dạng %).
- Chỉ bắt **vượt trần**; khai bằng hoặc thấp hơn → không lỗi.
- `t_tongchi_bh` null → `(float)` = 0 → với đúng tuyến, chi phí 0 < ngưỡng → trần = 100 → hầu như không lỗi (an toàn).

## 7. Guard — các trường hợp im lặng (không báo lỗi)

1. Quyền lợi mơ hồ: mã thẻ rỗng, < 3 ký tự, hoặc danh sách `;` cho quyền lợi khác nhau.
2. Ký tự quyền lợi không có trong `quyen_loi_map`.
3. Đúng tuyến nhưng chưa có mốc lương cơ sở tại ngày vào (`luongCoSoTaiNgay` trả 0).
4. Trái tuyến nhưng `tuyen_cmkt` của cơ sở không thuộc `tuyen_tw_values` (gồm cả khi cột NULL/chưa nạp).
5. Trái tuyến ngoại trú (ngoài phạm vi).
6. Không dòng con nào khai `muc_huong`.

Triết lý: "thiếu căn cứ thì im lặng" — chấp nhận bỏ sót hơn là dương tính giả trên tập hồ sơ lớn.

## 8. Kế hoạch kiểm thử

**Unit — `tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php` (không cần DB):**
- `quyenLoiChar`: mã thẻ thường → ký tự vị trí 3; danh sách `;` cùng quyền lợi → ký tự đó; danh sách `;` khác quyền lợi → null; chuỗi < 3 ký tự → null; rỗng/null → null.
- `entitlement`: '4'→80, '3'→95, '1'/'2'/'5'→100; ký tự lạ → null.
- `tranDungTuyen`: chi phí ≥ ngưỡng → entitlement; chi phí < ngưỡng → 100; LCS null/0 → null; entitlement null → null; đúng biên (chi phí = 15%×LCS) → entitlement.

**Integration (tùy chọn, nếu chi phí thấp) — nhánh trái tuyến/đúng tuyến qua sqlite bootstrap** dùng trait `Xml3176RuleTestSupport::bootXml3176Sqlite`, dựng bảng `medical_organizations` + `xml3176_xml1s/xml2s/xml3s`, khẳng định phát/không phát lỗi theo guard. Quyết định trong plan tùy độ phức tạp dựng bảng; nếu bỏ, dựa vào unit helper + rà nối tay (theo tiền lệ B1).

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` phải xanh (hiện 157/157).

## 9. Ghi chú triển khai (production)

- Chạy `php artisan db:seed --class=Xml3176ErrorCatalogMucHuongSeeder` để bật 2 code.
- **Rà `medical_organizations.tuyen_cmkt`**: xác nhận cột đã nạp dữ liệu và giá trị nào ứng với tuyến TW; chỉnh `config('xml3176.muc_huong.tuyen_tw_values')` cho khớp. Nếu chưa nạp, Rule 2 sẽ im lặng (không sai, chỉ bỏ sót).
- Kiểm chứng `muc_huong` thực trong `xml3176_xml2s`/`xml3176_xml3s` lưu dạng % (80/100), không phải tỷ lệ (0.8/1.0), trước khi tin số liệu.
- Rà mốc `mcct.luong_co_so` phủ kỳ đang xét (2026-07-01 = 2.530.000đ).

## 10. Rủi ro

- **`tuyen_cmkt` chưa nạp** → Rule 2 bỏ sót 230 dòng. Giảm thiểu: guard an toàn + ghi chú triển khai; có thể chuyển sang khai `tuyen_tw_values` theo dữ liệu thực.
- **Mã thẻ dạng danh sách** khiến trích quyền lợi mơ hồ → bỏ qua; chấp nhận bỏ sót thiểu số.
- **Giả định 15% áp trên tổng hồ sơ** (`t_tongchi_bh`): nếu nghiệp vụ tính "một lần KCB" khác (ví dụ tách đợt), có thể lệch biên; theo dõi sau khi chạy thật.
