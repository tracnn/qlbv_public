# API tra cứu tiền cùng chi trả (MCCT)

`GET /api/mcct/tra-cuu`

## Xác thực

Header `Authorization: Bearer {token}`. Token do quản trị qlbv cấp.

## Tham số

| Tham số | Bắt buộc | Ghi chú |
|---|---|---|
| `ma_the` | luôn | 10, 12, 15 hoặc 17 ký tự sau khi bỏ khoảng trắng |
| `lam_moi` | không | `1` = gọi cổng BHXH. Mặc định `0` = đọc dữ liệu đã lưu |
| `ho_ten` | khi `lam_moi=1` | |
| `ngay_sinh` | khi `lam_moi=1` | `dd/mm/yyyy`, `mm/yyyy` hoặc `yyyy` |
| `ma_cskcb` | khi `lam_moi=1` | Mã cơ sở đã khai tài khoản cổng BHXH trong qlbv |

## Ba điều phải biết trước khi viết mã gọi

1. **`lam_moi=0` trả về dưới 100ms. `lam_moi=1` có thể mất tới 60 giây.** Đặt timeout phía bạn **≥ 70 giây** cho `lam_moi=1`. Đặt 30 giây (mặc định của phần lớn thư viện HTTP) là tự cắt giữa chừng, và lượt gọi lên cổng BHXH **vẫn bị tiêu**.

2. **`lam_moi=1` cho cùng một mã thẻ trong vòng 15 phút sẽ bị bỏ qua** — bạn nhận dữ liệu đã lưu kèm `meta.bo_qua_lam_moi = true` và `meta.lam_moi_duoc_sau` (số giây còn phải chờ). Đây không phải lỗi. Cổng BHXH giới hạn số lượt tra cứu của mỗi tài khoản.

3. **`du_nguong_6_thang_luong = true` CHƯA đủ để kết luận người bệnh được miễn cùng chi trả.** Điều kiện miễn gồm hai vế: tham gia BHYT đủ 5 năm liên tục **và** lũy kế cùng chi trả lớn hơn 6 tháng lương cơ sở. Hàm của cổng BHXH không trả về vế thứ nhất — cờ `can_kiem_5_nam_lien_tuc` có mặt ở mọi phản hồi để nhắc điều đó.

Luồng khuyến nghị lúc tiếp đón: gọi `lam_moi=0` trước để hiện ngay; chỉ gọi `lam_moi=1` khi cán bộ chủ động yêu cầu.

## Phản hồi thành công

```json
{
  "success": true,
  "data": {
    "ma_the": "HT3382797052765",
    "nguon": "da_luu",
    "tra_luc": "2026-09-07 11:45:11",
    "ghi_chu": "Nguồn DL ... tính đến: 14/08/2026 14:41",
    "thong_tin_the": { "ho_ten": "...", "ngay_sinh": "...", "ngay_ket_thuc": "...", "ma_bhxh": "..." },
    "luy_ke_cung_chi_tra": 4762612,
    "nguong_ca_nam": 15066588,
    "con_thieu": 10303976,
    "du_nguong_6_thang_luong": false,
    "can_kiem_5_nam_lien_tuc": true,
    "chi_tiet": [ { "ma_cskcb": "...", "ngay_vao": "...", "ngay_ra": "...", "t_bn_cct_mcct": 0, "t_bn_cct_luy_ke": 0 } ]
  },
  "meta": { "timestamp": "20260907114511", "request_id": "req_..." }
}
```

`nguon` nhận `da_luu` hoặc `cong_bhxh`.

Hai mốc thời gian **khác nhau**, đừng gộp làm một:

- `tra_luc` — thời điểm qlbv hỏi cổng BHXH.
- Cụm "tính đến …" trong `ghi_chu` — mốc dữ liệu của chính cổng. Những đợt khám chữa bệnh mà cơ sở chưa gửi hồ sơ đề nghị thanh toán thì chưa được tính vào lũy kế.

## Chưa từng tra thẻ này

```json
{ "success": true, "data": null, "meta": { "trang_thai": "chua_tra_lan_nao", "...": "..." } }
```

Không có dữ liệu không phải lỗi. Gọi lại với `lam_moi=1` để tra thật.

## Lỗi

Luôn kiểm mã HTTP trước; chỉ phân tích thân phản hồi khi mã nằm trong bảng dưới.

```json
{ "success": false, "error": { "code": "...", "message": "...", "details": "..." }, "meta": {} }
```

| HTTP | `code` | Nghĩa |
|---|---|---|
| 401 | `UNAUTHORIZED` | Thiếu hoặc sai token |
| 422 | `VALIDATION_ERROR` | Thiếu tham số hoặc mã thẻ sai độ dài |
| 429 | | Vượt hạn mức 60 request/phút. Do tầng hạ tầng trả về, KHÔNG theo khuôn `{success, error, meta}` — thân phản hồi có thể là JSON `{message}` hoặc HTML. Phản hồi kèm header `Retry-After` |
| 502 | `GATEWAY_ERROR` | Cổng BHXH báo lỗi hoặc không kết nối được |
| 504 | `GATEWAY_TIMEOUT` | Cổng BHXH không trả lời kịp |
| 500 | `INTERNAL_ERROR` | Lỗi phía qlbv |

Trường `details` là câu soạn sẵn theo loại lỗi — không phải thông điệp ngoại lệ thô. Bên gọi **không nên** phân tích chuỗi `details` để đoán nguyên nhân; thay vào đó hãy dựa vào `error.code` và mã HTTP.
