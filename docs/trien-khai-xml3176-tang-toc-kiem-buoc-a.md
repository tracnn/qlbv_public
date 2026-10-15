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

### 2.1. Migration (hai chỉ mục + bảng phiên bản danh mục)

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

Thêm một migration tạo bảng (nhỏ, chạy tức thì):

- `2026_10_15_130000_create_xml3176_phien_ban_danh_muc_table`: bảng `xml3176_phien_ban_danh_muc`
  (một dòng `id = 1`, cột `phien_ban`, `ly_do`, `updated_at`) - xem mục 2.2. Chạy lại không lỗi.
  Khi chưa migrate, ứng dụng và lệnh `xml3176:do-kiem` vẫn chạy bình thường (chỉ ghi cảnh báo
  "phien ban danh muc" vào log), nhưng sửa danh mục khi đó chỉ có hiệu lực sau tối đa 10 phút.

### 2.2. Bộ đệm 600 giây

Các danh mục sau được đệm trong từng worker 600 giây (10 phút):

- danh mục mã lỗi (`critical_error`, `is_check`);
- danh mục DVKT / dịch vụ (`service_catalogs`), thuốc, vật tư y tế;
- CSKCB và `tuyen_cmkt`;
- ICD10 và ICD YHCT;
- đơn vị hành chính (tỉnh/huyện/xã);
- thiết bị (mã máy), nghề nghiệp;
- danh sách DVKT cần mã máy;
- bệnh thuộc phụ lục 1 (PL1).

**Sửa danh mục qua ứng dụng có hiệu lực ngay ở job kiểm kế tiếp, không cần khởi động lại hàng đợi.**
Mỗi lần ghi danh mục qua các đường sau, số phiên bản trong bảng `xml3176_phien_ban_danh_muc` tăng 1; mỗi
job kiểm đọc số này lúc bắt đầu (một truy vấn) và xoá bộ đệm nếu số đã đổi:

- nhập Excel danh mục trên màn hình và các lệnh nhập tự động (`importCatalogBHXH:data`, chuyển đơn vị
  hành chính 2 cấp) - kể cả khi nhập lỗi giữa chừng;
- xoá danh mục;
- đồng bộ TT12 (sau khi hồ sơ được tiếp nhận, kể cả "đồng bộ lại") khi có dòng được ghi;
- bật/tắt mức chặn (`critical_error`) và kiểm tra (`is_check`) trong danh mục mã lỗi XML3176;
- bảng tra vật phẩm kèm DVKT: học lại, nạp Excel, bật/tắt hiệu lực, ghi chú, xoá, thêm dòng tay, thêm
  DVKT bắt buộc.

**Chỉ khi sửa thẳng trong CSDL (chạy SQL tay)** mới phải làm một trong hai việc trước khi kiểm lại hồ sơ:

1. khởi động lại dịch vụ hàng đợi NSSM **`QLBV JobXml3176`** (tên đúng như trong `update.bat` /
   `install_service.bat`), hoặc
2. chờ **10 phút** để các worker tự đọc bản mới.

(Hoặc sau khi sửa tay, tăng số phiên bản bằng tay:
`UPDATE xml3176_phien_ban_danh_muc SET phien_ban = phien_ban + 1, ly_do = 'sua tay' WHERE id = 1;`.)

Tra soát: `SELECT * FROM xml3176_phien_ban_danh_muc;` - cột `ly_do` cho biết lần tăng gần nhất do việc gì,
`updated_at` lúc nào.

Về `is_check`: các kiểm tra XML1–XML14 (chế độ gom) đọc `is_check` **trực tiếp, mới** từ CSDL ở cuối mỗi
job; chỉ XMLComplete đọc `is_check` từ bộ đệm 600 giây. `critical_error` thì luôn theo bộ đệm.

Ghi chú: các checker Qd130 dùng chung `CommonValidationService` nên cũng được đệm (module đã gỡ);
`isMedicalStaffValid` không được đệm.

Bộ nhớ: bộ đệm có giới hạn để worker dài hạn không phình (PHP `memory_limit` trên prod có thể chỉ 128M):
tối đa 20.000 mục cho mỗi bộ đệm scalar, 5.000 mục cho bộ đệm giữ model/Collection; nếu bộ nhớ thực dùng
vượt 64 MB thì **toàn bộ** bộ đệm bị xoá rồi nạp lại dần (chậm đi một chút nhưng không đổi kết quả).

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
| `--tu=` | Lấy hồ sơ có `created_at` từ ngày này (mặc định: 14 ngày trước hôm nay) |
| `--uu-tien-noi-tru` | Ít nhất một nửa mẫu là hồ sơ nội trú |
| `--ghi-ma-lk=` | Lưu danh sách `ma_lk` đã dùng ra tệp |
| `--xuat-loi=` | Xuất danh sách lỗi dạng jsonl |
| `--xuat-do=` | Xuất thống kê (số hồ sơ thực tế, bộ nhớ đỉnh, số truy vấn, ms SQL theo loại XML, mẫu truy vấn nặng) |

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
