# Triển khai: tăng tốc kiểm XML3176 (Bước A)

Mục tiêu của đợt này: kiểm XML3176 nhanh hơn nhiều lần mà **kết quả không đổi** (cùng dữ liệu thì cùng
danh sách lỗi: xml, ma_lk, stt, error_code, description, critical_error).

## 1. Kết quả nghiệm thu

### Tương đương kết quả

- Mẫu 200 hồ sơ (lấy từ 01/10/2026, một nửa nội trú), chạy trước và sau: **23.738 lỗi, giống hệt nhau**.
  Khác biệt duy nhất là cách biểu diễn `critical_error` (0 thành false), giá trị lưu trữ không đổi.
- Mẫu 40 hồ sơ: 2.842 lỗi, giống hệt.
- `tests/Unit/Xml3176`: 889 test, chỉ còn 4 lỗi mốc đã biết (Xml3176Xml3CheckerRuleTest:
  tg_th_yl_trung_tg_kq, thuc_hien_duoi_3_phut_nhom_1, bac_si_vua_ra_vua_thuc_hien;
  Xml3176ErrorServiceGomTest: che_do_gom_bat_tat_dung).

### Tốc độ (mẫu 200 hồ sơ, đo từ máy dev tới CSDL prod, CHƯA có hai chỉ mục mới trên prod)

| Mục | Trước | Sau |
|---|---|---|
| Tổng thời gian SQL | 1.251 s (khoảng 6,3 s/hồ sơ) | 253 s (khoảng 1,3 s/hồ sơ) |
| XML3 | 698 s (7,7 truy vấn/dòng) | 212 s (0,5 truy vấn/dòng) |
| XML4 | 456 s (3,5 truy vấn/dòng) | 3,7 s (0,0 truy vấn/dòng) |
| XML2 | 45 s | 4,4 s |
| XML5 | 18 s | 2,3 s |

Các truy vấn nặng nhất còn lại (đầu vào cho Bước B; hai mục đầu dự kiến giảm mạnh sau khi có chỉ mục):

1. Tra `service_catalogs` theo hiệu lực ngày trong XML3: 2.566 lần gọi, 124 s. Được phủ bởi chỉ mục
   `service_catalogs(ma_dich_vu, ma_cskcb)`.
2. Kiểm trùng giường trong XML3: 1.063 lần gọi, 61 s. Được phủ bởi chỉ mục
   `xml3176_xml3s(ma_giuong, ma_khoa, ngay_th_yl)`.
3. Truy vấn chéo hồ sơ kiểm trùng bác sĩ PTTT: 726 lần gọi, 8,6 s.

## 2. Những gì thay đổi khi deploy

### 2.1. Hai migration chỉ mục

- `2026_10_15_110000_them_chi_muc_ma_dich_vu_service_catalogs`: chỉ mục
  `service_catalogs_ma_dich_vu_ma_cskcb_index` trên `service_catalogs(ma_dich_vu, ma_cskcb)`.
- `2026_10_15_120000_them_chi_muc_trung_giuong_xml3176_xml3s`: chỉ mục
  `xml3176_xml3s_giuong_khoa_th_yl_index` trên `xml3176_xml3s(ma_giuong, ma_khoa, ngay_th_yl)`.

Lưu ý khi deploy:

- Thời gian bảo trì của lượt deploy này **có thể dài thêm vài phút** (dựng chỉ mục trên bảng lớn,
  đặc biệt `xml3176_xml3s`).
- Hai migration là **best-effort**: nếu tạo chỉ mục thất bại thì chỉ ghi cảnh báo vào log và migration
  vẫn coi như xong (không chặn deploy). Sau deploy kiểm tra `storage/logs/laravel*.log`, tìm
  "Khong tao duoc chi muc". Nếu có, tạo chỉ mục tay ở giờ thấp điểm.
- Kết quả kiểm vẫn đúng khi chưa có chỉ mục, chỉ chậm hơn.

### 2.2. Bộ đệm 600 giây

Danh mục nhỏ (mã lỗi, mức chặn, danh mục tra cứu) được đệm trong worker 600 giây.

- Thay đổi danh mục hoặc mức chặn (`critical_error`/`is_check` của mã lỗi) có hiệu lực **tối đa sau
  10 phút** trong các worker đang chạy.
- Muốn có hiệu lực ngay: khởi động lại các dịch vụ hàng đợi (queue worker).

### 2.3. createOrUpdate không ghi đè mức chặn do admin đặt

Khi ghi nhận mã lỗi mới/đã có, `createOrUpdate` không còn ghi đè `critical_error` mà quản trị viên đã
chỉnh trong danh mục lỗi. Giá trị admin đặt được giữ nguyên.

## 3. Lệnh đo và so sánh: `php artisan xml3176:do-kiem`

Lệnh **chỉ đọc**: câu đầu tiên đặt phiên MySQL ở chế độ READ ONLY, và ErrorService chạy ở chế độ gom
(không bao giờ kết thúc gom) nên không ghi dòng lỗi nào. Chạy y như `CheckXml3176TypeJob::handle`:
mỗi loại XML nạp dòng theo lô 500, rồi XMLComplete.

Tuỳ chọn:

| Tuỳ chọn | Ý nghĩa |
|---|---|
| `--ma-lk-tep=` | Tệp mỗi dòng một `ma_lk` (dùng lại đúng một mẫu cho trước/sau) |
| `--so=40` | Số hồ sơ lấy ngẫu nhiên nếu không có tệp |
| `--tu=2026-10-01` | Lấy hồ sơ có `created_at` từ ngày này |
| `--uu-tien-noi-tru` | Ít nhất một nửa mẫu là hồ sơ nội trú |
| `--ghi-ma-lk=` | Lưu danh sách `ma_lk` đã dùng ra tệp |
| `--xuat-loi=` | Xuất danh sách lỗi dạng jsonl |
| `--xuat-do=` | Xuất thống kê (số truy vấn, ms SQL theo loại XML, mẫu truy vấn nặng) |

Mã thoát: `0` bình thường; `1` tham số sai (ví dụ không thấy tệp); **`2` có ngoại lệ trong lúc kiểm**
(kết quả đo/so sánh khi đó không dùng được, xem dòng "ngoai le" in ra).

### Công thức so sánh trước/sau

```
# 1. Lấy mẫu một lần, lưu lại
php artisan xml3176:do-kiem --so=200 --uu-tien-noi-tru --tu=2026-10-01 --ghi-ma-lk=mau200.txt

# 2. Ở commit mốc (trước tối ưu)
php artisan xml3176:do-kiem --ma-lk-tep=mau200.txt --xuat-loi=loi_truoc.jsonl

# 3. Ở commit cuối (sau tối ưu), cùng tệp mẫu
php artisan xml3176:do-kiem --ma-lk-tep=mau200.txt --xuat-loi=loi_sau.jsonl

# 4. So sánh: phải giống hệt
diff loi_truoc.jsonl loi_sau.jsonl
```

Nếu `diff` có dòng khác: trước hết kiểm dữ liệu hồ sơ có bị sửa giữa hai lần chạy không; nếu không thì
đó là lỗi hồi quy, phải điều tra. Đo tốc độ: thêm `--xuat-do=do_truoc.txt` / `--xuat-do=do_sau.txt` rồi
so truy vấn/dòng và ms SQL theo loại XML.

## 4. Việc cần làm sau deploy

1. Xem log: `storage/logs/laravel*.log`, tìm cảnh báo "Khong tao duoc chi muc" (mục 2.1).
2. Chạy EXPLAIN (chỉ đọc, không ghi gì) để xác nhận hai chỉ mục mới được dùng. Thay giá trị mẫu bằng
   một mã thật trong CSDL.

H1: tra hiệu lực DVKT trong `service_catalogs` (kỳ vọng `key` = `service_catalogs_ma_dich_vu_ma_cskcb_index`):

```sql
EXPLAIN
SELECT * FROM service_catalogs
WHERE (ma_cskcb IS NULL OR ma_cskcb = '' OR ma_cskcb = '<MA_CSKCB>')
  AND ma_dich_vu = '<MA_DICH_VU>'
  AND tu_ngay <= '<YYYYMMDD>'
  AND (den_ngay >= '<YYYYMMDD>' OR den_ngay IS NULL);
```

H3: kiểm trùng giường trong `xml3176_xml3s` (kỳ vọng `key` = `xml3176_xml3s_giuong_khoa_th_yl_index`):

```sql
EXPLAIN
SELECT * FROM xml3176_xml3s
WHERE ma_khoa = '<MA_KHOA>'
  AND ma_giuong = '<MA_GIUONG>'
  AND id != <ID>
  AND ngay_th_yl < '<NGAY_KQ>'
  AND ngay_kq > '<NGAY_TH_YL>';
```

3. Nếu `key` là NULL hoặc chỉ mục khác: báo lại, đừng tự thêm chỉ mục khác. Sau khi chỉ mục chạy, đo lại
   bằng `xml3176:do-kiem` (mục 3) để lấy số liệu mới cho Bước B.
