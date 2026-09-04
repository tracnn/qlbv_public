# XML3176 — B2: Quy tắc ngày giường tối thiểu theo TT39 (design)

**Ngày:** 2026-09-04
**Module:** XML3176 (kiểm tra hồ sơ BHYT trước khi gửi cổng)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Bổ sung rule cảnh báo hồ sơ nội trú khai **tổng ngày giường nhỏ hơn** số ngày điều trị tính theo hướng dẫn TT39 — tương ứng lỗi giám định **mã 440 "Tổng ngày giường nhỏ hơn hướng dẫn của BYT (TT39)"** (166 lỗi / 27 file trong bảng tổng hợp giám định cơ sở 01929). Cảnh báo trước khi gửi để cơ sở rà lại.

## 2. Bối cảnh & bằng chứng

- Nguyên văn lỗi (file `Hoso-Tong-hop-24.8.26 (Giam dinh benh vien).xlsx`, sheet "Tổng Hợp Lỗi", dòng 32): **Mã 440 — "Tổng ngày giường nhỏ hơn hướng dẫn của BYT (TT39)" — loại "BÁO LỖI" — tại XML3 thẻ SO_LUONG — 166 lỗi / 27 file.**
- Đây là **cảnh báo**, KHÔNG phải khoản trừ tiền (không xuất hiện trong các file chi tiết xuất toán). Bản chất: hồ sơ khai **thiếu** ngày giường so với số ngày điều trị → tín hiệu bất nhất dữ liệu.
- **TT39/2024/TT-BYT** (hiệu lực 01/01/2025) — cách xác định số ngày điều trị nội trú / số ngày giường:
  - Số ngày = ngày ra − ngày vào (dương lịch).
  - **+1 ngày** khi tử vong / diễn biến nặng xin về / chuyển viện.
  - Vào–ra cùng ngày: **≥ 4h → 1 ngày; < 4h → 0** (không tính giường).
  - Chuyển 2 khoa cùng ngày: mỗi khoa ½ ngày (tổng vẫn khớp số ngày).
  - Ngày giường ngoại khoa/bỏng tối đa 10 ngày sau PT — quy tắc GIÁ, không ảnh hưởng số ngày (ngoài phạm vi).

## 3. Phạm vi

**Trong phạm vi:** 1 rule cảnh báo tại `Xml3176CompleteChecker`, so tổng ngày giường khai với số ngày điều trị tính lại theo TT39 (dương lịch + quy tắc 4h + cờ đặc biệt). **Logic thuần — KHÔNG đổi schema.**

**Ngoài phạm vi:** quy tắc giá ngày giường ngoại khoa (10 ngày sau PT); nằm ghép (½, ⅓ giá); phân loại phẫu thuật. Các rule ngày giường "thừa" đã có (`INVALID_BED_DAYS`, `EXCESS_BED_DAYS`, `SHORT_INPATIENT_STAY`) — không đụng.

## 4. Kiến trúc

Thêm hàm `checkBedDaysBelowTT39(Xml3176Xml1 $data): Collection` vào `app/Services/Xml3176CompleteChecker.php` (cạnh `checkInvalidBedDays`), nối vào `checkErrors`. Prefix error `XMLComplete_`, lưu qua `saveErrors`. Logic tính `expected` tách vào helper thuần `app/Services/Xml3176/Support/BedDaysTT39Calculator.php` (test không cần DB).

### 4.1 Helper thuần `BedDaysTT39Calculator`

```php
namespace App\Services\Xml3176\Support;

class BedDaysTT39Calculator
{
    /**
     * Số ngày giường đúng theo TT39.
     *  - Cùng ngày (calendarDays == 0): elapsedHours >= 4 -> 1, ngược lại 0.
     *  - Nhiều ngày: calendarDays + (special ? 1 : 0).
     *
     * @param int   $calendarDays số ngày dương lịch giữa ngày vào và ngày ra (>= 0)
     * @param float $elapsedHours tổng giờ trôi qua giữa vào và ra
     * @param bool  $special      tử vong / chuyển viện / nặng xin về -> +1
     */
    public static function expected(int $calendarDays, float $elapsedHours, bool $special): int;

    /**
     * Có thiếu ngày giường không: chỉ xét khi expected >= 1; thiếu khi
     * totalBedDays < expected - tolerance.
     */
    public static function isBelow(float $totalBedDays, int $expected, float $tolerance): bool;
}
```

### 4.2 Config mới — `config/xml3176.php`, khối `bed_days_tt39`

```php
'bed_days_tt39' => [
    'tolerance' => 0.5, // Dung sai (ngày) bỏ qua nhiễu làm tròn ½ ngày; chỉ cảnh báo khi thiếu rõ rệt
],
```

Tái dùng config sẵn có: `xml3176.bed_group_code` [14,15,16]; `xml3176.treatment_type_inpatient` ['03','04','09']; `xml3176.invalid_treatment_result` [3,4,5,6]; `xml3176.invalid_end_type_treatment` [2,3,4].

### 4.3 Checker `checkBedDaysBelowTT39` — luồng (mã giả)

```
$errors = collect();

if (!in_array($data->ma_loai_kcb, config('xml3176.treatment_type_inpatient'))) return $errors; // chỉ nội trú

$dtVao = Xml3176DateHelper::toDateTime($data->ngay_vao);
$dtRa  = Xml3176DateHelper::toDateTime($data->ngay_ra);
if ($dtVao === null || $dtRa === null) return $errors;            // guard: ngày không hợp lệ

$elapsedHours = ($dtRa->getTimestamp() - $dtVao->getTimestamp()) / 3600;
if ($elapsedHours < 0) return $errors;                            // guard: ra trước vào

$calendarDays = (int) (new DateTime($dtVao->format('Y-m-d')))->diff(new DateTime($dtRa->format('Y-m-d')))->days;

$special = in_array($data->ket_qua_dtri, config('xml3176.invalid_treatment_result'))
        || in_array($data->ma_loai_rv,   config('xml3176.invalid_end_type_treatment'));

$expected = BedDaysTT39Calculator::expected($calendarDays, $elapsedHours, $special);
if ($expected < 1) return $errors;                               // guard: lưu trú <4h hợp lệ

$totalBedDays = (float) $data->Xml3176Xml3()
    ->whereIn('ma_nhom', config('xml3176.bed_group_code'))
    ->sum('so_luong');

$tol = (float) config('xml3176.bed_days_tt39.tolerance', 0.5);
if (BedDaysTT39Calculator::isBelow($totalBedDays, $expected, $tol)) {
    $errorCode = $this->generateErrorCode('BED_DAYS_BELOW_TT39');
    $errors->push((object)[
        'error_code'     => $errorCode,
        'error_name'     => 'Tổng ngày giường nhỏ hơn hướng dẫn TT39',
        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode), // = false sau khi seed
        'description'    => 'Tổng ngày giường khai ' . $totalBedDays . ' nhỏ hơn số ngày điều trị theo TT39 '
                          . $expected . ' (chênh ' . round($expected - $totalBedDays, 2) . ').',
    ]);
}
return $errors;
```

Nối vào `checkErrors`: thêm `$errors = $errors->merge($this->checkBedDaysBelowTT39($data));`.

### 4.4 Error code & seeder

Seeder mới `database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php` (idempotent), **critical_error = false** (cảnh báo, không chặn xuất):

| xml | error_code | error_name | critical_error | is_check |
|---|---|---|---|---|
| XMLComplete | `XMLComplete_BED_DAYS_BELOW_TT39` | Tổng ngày giường nhỏ hơn hướng dẫn TT39 | **false** | true |

## 5. Xử lý biên / guard (im lặng)

1. Không phải hồ sơ nội trú.
2. Ngày vào/ra không hợp lệ (toDateTime null) hoặc ra trước vào.
3. `expected < 1` (lưu trú <4h hợp lệ — không cần giường).

**Cảnh báo cả khi Σ ngày giường = 0** (nội trú mà không khai giường nào là bất thường — đúng tinh thần 440, đã chốt).

## 6. So sánh & dung sai

- `isBelow`: chỉ khi `expected >= 1` và `totalBedDays < expected - tolerance` (0.5). Bỏ qua nhiễu làm tròn ½ ngày (chuyển khoa cùng ngày); chỉ cảnh báo khi thiếu rõ rệt (≥ ~0.5 ngày).
- Dùng **dương lịch** cho `calendarDays` (khớp TT39 "ngày ra − ngày vào"), tránh lệch +1 so với cách đếm 24h trôi qua.

## 7. Kế hoạch kiểm thử

**Unit — `tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php` (không cần DB):**
- `expected`: cùng ngày ≥4h → 1; cùng ngày <4h → 0; nhiều ngày thường → calendarDays; nhiều ngày special → calendarDays+1.
- `isBelow`: expected<1 → false; totalBedDays đủ → false; thiếu trong dung sai → false; thiếu ngoài dung sai → true; totalBedDays=0 & expected≥1 → true.

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` xanh (hiện 166).

Integration test cho checker: tùy chọn (theo tiền lệ B1/Nhóm C — helper thuần + rà nối tay). Quyết định trong plan.

## 8. Ghi chú triển khai (production)

- **BẮT BUỘC** chạy `php artisan db:seed --class=Xml3176ErrorCatalogBedDaysTT39Seeder` — vì `getCriticalErrorStatus` **mặc định true khi catalog thiếu**; chưa seed thì rule sẽ bị coi là lỗi nghiêm trọng (chặn xuất) thay vì cảnh báo.
- Rà một lô hồ sơ thật sau khi bật: kiểm tra tỷ lệ cảnh báo hợp lý (không nhiễu); nếu nhiều dương tính giả do làm tròn, nới `bed_days_tt39.tolerance`.
- Kiểm chứng cờ đặc biệt: xác nhận `invalid_treatment_result` / `invalid_end_type_treatment` đúng là các mã tử vong/chuyển viện/nặng-xin-về của cơ sở (đang tái dùng từ rule ngày giường "thừa").

## 9. Rủi ro

- **Đếm dương lịch vs 24h**: TT39 dùng dương lịch; hồ sơ biên (vào/ra sát nửa đêm) có thể lệch 1 ngày — dung sai 0.5 giảm thiểu phần nào, nhưng cảnh báo (không chặn) nên chi phí sai thấp.
- **Cờ đặc biệt +1** dựa trên config tái dùng; nếu mã của cơ sở khác, `expected` lệch → điều chỉnh config.
- **Σ=0 cảnh báo**: có thể gồm hồ sơ nội trú ngắn hợp lệ chưa lọc hết bởi guard `expected>=1`; theo dõi sau khi chạy thật.
