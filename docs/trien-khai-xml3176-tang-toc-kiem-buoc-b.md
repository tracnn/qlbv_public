# Triển khai: tăng tốc kiểm XML3176 (Bước B)

Bước B giảm chi phí kiểm theo từng hồ sơ (truy vấn cấp hồ sơ, số job trong hàng đợi) mà **kết quả không đổi**
(cùng dữ liệu thì cùng danh sách lỗi: xml, ma_lk, stt, error_code, description, critical_error).
Bước A xem `docs/trien-khai-xml3176-tang-toc-kiem-buoc-a.md`.

## 1. Kết quả nghiệm thu

### Tương đương kết quả

- Cùng mẫu 200 hồ sơ, so mã nguồn ở cuối Task 1 (mốc trước) với mã nguồn sau Bước B:
  **23.740 lỗi, giống hệt nhau**.
- Mẫu 40 hồ sơ: 2.842 lỗi, giống hệt.
- `tests/Unit/Xml3176`: 978 test, chỉ còn 4 lỗi mốc đã biết (Xml3176Xml3CheckerRuleTest:
  tg_th_yl_trung_tg_kq, thuc_hien_duoi_3_phut_nhom_1, bac_si_vua_ra_vua_thuc_hien;
  Xml3176ErrorServiceGomTest: che_do_gom_bat_tat_dung). Các lỗi này là mốc, không phải hồi quy
  (che_do_gom_bat_tat_dung cần CSDL MySQL thật nên báo "Access denied" khi chạy với DB_HOST=127.0.0.1).

### Tốc độ (đo chỉ đọc từ máy dev tới CSDL prod, mẫu 200 hồ sơ)

Lệnh đo xả theo từng hồ sơ như worker thật. Chưa có chỉ mục PTTT mới trên prod.

| Mục | Trước | Sau |
|---|---|---|
| Thời gian thực (wall) trung bình mỗi hồ sơ | 309 ms | 256 ms |
| Tổng thời gian SQL cho 200 hồ sơ | 49,0 s | 38,4 s |
| Kiểm cấp hồ sơ (XMLComplete): số truy vấn | 8.182 | 2.408 |
| Kiểm cấp hồ sơ (XMLComplete): thời gian SQL | 13,9 s | 4,3 s |

Còn lớn nhất sau Bước B (chưa có chỉ mục PTTT trên prod):

1. Kiểm trùng bác sĩ PTTT chéo hồ sơ: 726 lần gọi, 5,0 s. Mục này dự kiến giảm sau khi deploy
   (migration 140000, mục 2.3).
2. Kiểm trùng giường: 1.063 lần gọi, 7,9 s.

## 2. Những gì thay đổi khi deploy

### 2.1. Chuỗi job mới

Trước: mỗi hồ sơ có tối đa 12 job theo loại XML (`CheckXml3176TypeJob`) cộng 1 job kiểm cấp hồ sơ
(`CheckCompleteXml3176RecordJob`), rồi xuất, ký, gửi.

Sau: mỗi hồ sơ có **một** job kiểm, `KiemHoSoXml3176Job`, chạy lần lượt mọi loại XML có checker và kiểm
tổng thể, rồi đóng dấu `checked_at`. Sau đó vẫn xuất, ký, gửi như trước.

- Job cũ đã nằm trong hàng đợi lúc deploy **vẫn chạy được** (các lớp cũ được giữ lại), không cần xả hàng đợi.
- Job luôn có mặt trong chuỗi, kể cả khi danh sách loại rỗng hoặc tắt kiểm tổng thể: đó là mốc
  "kiểm xong" giữa bước kiểm và bước xuất.

### 2.2. Kiểm token xuyên suốt

Mỗi hồ sơ mang một mã phiên chuỗi (`chain_token`); nạp lại hồ sơ sinh mã mới. Vì một job nay bao trùm toàn bộ
việc kiểm, khoảng giữa lần xem token đầu job và lần ghi cuối dài hơn trước, nên:

- job **kiểm lại token ngay trước mỗi lần xoá/ghi lỗi**;
- dấu `checked_at` chỉ được ghi khi token còn khớp (một câu lệnh có điều kiện token);
- nếu hồ sơ bị nạp lại giữa chừng, job cũ **dừng và không ghi gì**, chuỗi bị cắt.

### 2.3. Migration mới: `2026_10_15_140000_them_chi_muc_pttt_trung_bac_si_xml3176_xml3s`

Chỉ mục `xml3176_xml3s_bs_ngay_yl_th_kq_index` trên `xml3176_xml3s(ma_bac_si(100), ngay_yl(14),
ngay_th_yl(14), ngay_kq(14))` cho kiểm "PTTT trùng bác sĩ". Tiền tố được chọn theo độ dài thật lớn nhất
trên prod (ma_bac_si 17, các cột ngày 12).

- **Best-effort**: thử `ALGORITHM=INPLACE, LOCK=NONE` (không khoá bảng), không hỗ trợ thì lùi về
  `ADD INDEX` thường; thất bại cả hai thì chỉ ghi `Log::warning`, migration vẫn xong, không chặn deploy.
  Chạy lại không lỗi (đã có chỉ mục thì bỏ qua).
- Thời gian bảo trì có thể dài thêm vài phút (dựng chỉ mục trên bảng lớn).
- Sau deploy xem `storage/logs/laravel*.log`, tìm "Khong tao duoc chi muc". Nếu có, tạo chỉ mục tay ở giờ
  thấp điểm. Kết quả kiểm vẫn đúng khi chưa có chỉ mục, chỉ chậm hơn.

### 2.4. Sửa lỗi nhỏ đi kèm

Lỗi XMLComplete nay được **xoá trước khi ghi lại**; trước đây job thử lại có thể sinh dòng lỗi trùng.

### 2.5. Lệnh đo `xml3176:do-kiem`

Nay xả theo từng hồ sơ (giống worker thật) và báo thêm: thời gian thực mỗi hồ sơ (trung bình / p90 / lớn
nhất) và bộ nhớ đỉnh trong lúc kiểm so với lúc sắp xếp kết quả cuối. Cách dùng và công thức so sánh
trước/sau xem mục 3 của tài liệu Bước A.

## 3. Việc cần làm sau deploy

1. Xem log tìm "Khong tao duoc chi muc" (mục 2.3).
2. Chạy EXPLAIN (chỉ đọc) xác nhận chỉ mục PTTT được dùng. Câu này dựng từ truy vấn thật trong
   `Xml3176Xml3Checker::checkServiceGroupPtttDuplicate`; thay giá trị mẫu bằng một dòng PTTT thật
   (`ma_nhom` thuộc `config('xml3176.xml3.service_groups_pttt')` = 8, 18). Kỳ vọng `key` =
   `xml3176_xml3s_bs_ngay_yl_th_kq_index`; nếu NULL hoặc khác thì báo lại, đừng tự thêm chỉ mục.

```sql
EXPLAIN
SELECT * FROM xml3176_xml3s
WHERE ma_bac_si = '<MA_BAC_SI>'
  AND (ma_lk != '<MA_LK>' OR (ma_lk = '<MA_LK>' AND stt != <STT>))
  AND ngay_yl = '<NGAY_YL>'
  AND ngay_th_yl = '<NGAY_TH_YL>'
  AND ngay_kq = '<NGAY_KQ>'
  AND ma_nhom IN (8, 18);
```

3. Đo thông lượng hàng đợi trên lô nhập kế tiếp. Mức tiết kiệm do giảm số job **không đo được bằng
   `do-kiem`** (lệnh đo không đi qua hàng đợi), nên so trực tiếp: đếm số hồ sơ được đóng dấu `checked_at`
   theo từng phút (chỉ đọc), chạy cho lô sau deploy và cho một lô trước deploy rồi so số hồ sơ/phút.

```sql
SELECT DATE_FORMAT(checked_at, '%Y-%m-%d %H:%i') AS phut, COUNT(*) AS so_ho_so
FROM xml3176_informations
WHERE checked_at >= '<TU_LUC>' AND checked_at < '<DEN_LUC>'
GROUP BY phut
ORDER BY phut;
```

   Chọn khoảng `<TU_LUC>`..`<DEN_LUC>` bao trọn một đợt nhập (ví dụ đợt nhập sau deploy, và một đợt nhập
   tương đương trước deploy). So số hồ sơ/phút ở giai đoạn ổn định, bỏ phút đầu và phút cuối của đợt.
   Lưu ý `checked_at` bị xoá về null khi nạp lại hồ sơ, nên với lô cũ chỉ đếm được hồ sơ chưa bị nạp lại.
