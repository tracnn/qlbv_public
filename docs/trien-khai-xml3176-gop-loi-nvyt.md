# Triển khai prod: gộp 34 mã lỗi NVYT thành 3 mã mức hồ sơ

**Thiết kế:** `docs/superpowers/specs/2026-10-15-xml3176-gop-loi-nvyt-design.md`
**Người thực hiện:** quản trị máy chủ QLBV, mở `cmd` tại thư mục dự án trên máy chủ.

## 1. Thay đổi

Kiểm tra mã nhân viên y tế (NVYT) của XML1..14 nay chạy một lần ở mức hồ sơ (`Xml3176CompleteChecker::kiemNvyt`), mỗi giá trị lỗi chỉ ra một dòng lỗi, thay cho hàng chục dòng lỗi theo từng dòng XML.

| Mã mới (`xml3176_error_catalogs`) | Ý nghĩa | Mức chặn |
|---|---|---|
| `XMLComplete_NVYT_THIEU` | Trường NVYT bắt buộc để trống | **Chặn** gửi/xuất |
| `XMLComplete_NVYT_KHONG_CO_TRONG_DM` | Đúng dạng CCHN/số định danh nhưng không có trong danh mục NVYT | Cảnh báo |
| `XMLComplete_NVYT_SAI_DINH_DANG` | Sai định danh (tài khoản/tên đăng nhập, số định danh hay mã BHXH ở vai trò chuyên môn, CCHN thiếu đuôi...) | Cảnh báo |

34 mã cũ (danh sách ở `NvytRule::MA_CU` và migration) bị **tắt** (`is_check = false`, ghi chú "Đã thay bằng XMLComplete_NVYT_*"); không xoá để giữ lịch sử.

## 2. Sau khi `update.bat` chạy

`update.bat` tự `git pull`, `migrate --force` (nạp 3 mã mới, tắt mã cũ), `config:cache`, khởi động lại dịch vụ. Hồ sơ đã kiểm trước đó vẫn mang dòng lỗi cũ (và cờ chặn cũ) cho đến khi kiểm lại:

1. Chạy thử, chỉ đếm, không ghi:
   `php artisan xml3176:kiem-lai-nvyt --tu=2026-09-01 --thu`
2. Đối chiếu số liệu rồi chạy thật:
   `php artisan xml3176:kiem-lai-nvyt --tu=2026-09-01`
   (tuỳ chọn `--den=Y-m-d`, `--lo=500`). Lệnh xoá dòng lỗi NVYT cũ/mới của hồ sơ rồi ghi lại theo luật mới, mỗi hồ sơ được xử lý trong một transaction riêng.
3. Lệnh ghi danh sách `ma_lk` hết chặn (trước đây bị chặn bởi mã cũ, nay không còn) vào `storage/logs/kiem-lai-nvyt-<YmdHis>.txt`. Khi cần đẩy các hồ sơ này đi tiếp, dùng:
   `php artisan xml3176:chay-lai-tu-xuat --ma-lk=<ma_lk> ...` (thêm `--thuc-hien` để đẩy thật; thiếu cờ này chỉ đếm).

`--thu` chỉ in số liệu đếm (số dòng sẽ xoá, số lỗi mới theo từng mã, số hồ sơ), KHÔNG liệt kê các hồ sơ sẽ hết chặn; chỉ lần chạy thật mới in số hồ sơ hết chặn và ghi tệp `storage/logs/kiem-lai-nvyt-<YmdHis>.txt`.

Chạy lệnh ngoài giờ cao điểm; không chạy song song với đợt nạp lô lớn. Không chạy lệnh thật khi hàng đợi kiểm còn tồn (tránh chạy đồng thời với chuỗi kiểm từng hồ sơ trên cùng các hồ sơ đó).

## 3. Lưu ý

- Bảng tra NVYT (`NvytLookup`) dùng bộ nhớ đệm; sau khi sửa danh mục NVYT, lệnh tự xoá đệm khi bắt đầu chạy.
- Bảng danh mục NVYT rỗng thì luật im lặng (không báo `KHONG_CO_TRONG_DM`/`SAI_DINH_DANG`), chỉ còn báo `THIEU`.
- Nếu từng có lần triển khai chạy code mới mà migration chưa thành công, các hồ sơ được kiểm trong khoảng thời gian đó có thể mang dòng `KHONG_CO_TRONG_DM`/`SAI_DINH_DANG` bị đánh dấu chặn; chạy lệnh kiểm lại trên khoảng ngày phủ các hồ sơ đó sẽ sửa (lần chạy khuyến nghị `--tu=2026-09-01` đã phủ toàn bộ).
- Quay lui: `php artisan migrate:rollback --path=database/migrations/2026_10_15_100000_gop_ma_loi_nvyt_xml3176.php` bật lại 34 mã cũ, sau đó `config:cache`. Phải gỡ code mới (revert) trên main TRƯỚC, nếu không `update.bat` chạy mỗi giờ sẽ migrate lại.
