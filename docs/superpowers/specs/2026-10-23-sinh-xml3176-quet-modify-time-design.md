# Sinh XML3176: quét theo MODIFY_TIME, chỉ ghi khi nội dung đổi

Ngày 23/10/2026. Bổ sung cho `docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`. Chỉ đổi phần "khi nào sinh"; cách dựng 14 bảng giữ nguyên.

## Vì sao

Hiện module tìm ứng viên theo mốc `FEE_LOCK_TIME` (bản chính thức) hoặc `OUT_TIME` (bản thông tuyến). Hồ sơ đã sinh chỉ được sinh lại khi giá trị đó đổi.

Khoa phòng hay mở hồ sơ đã khoá để sửa rồi khoá lại, và `FEE_LOCK_TIME` vẫn giữ ngày cũ. Đo trên HIS thật ngày 23/10:
- 12.114 hồ sơ BHYT khoá viện phí 15–22/10, trong đó **1.009 (8%) có dịch vụ sửa sau `FEE_LOCK_TIME`**. Module hiện bỏ sót toàn bộ số này.
- Bản thông tuyến cũng không bao giờ được sinh lại khi sửa, vì `OUT_TIME` không đổi.

Chủ dự án chốt (23/10): quét theo `MODIFY_TIME`.

## Ràng buộc đo được

- `HIS_TREATMENT` không có chỉ mục trên `MODIFY_TIME`. Có chỉ mục trên `OUT_TIME` (`HIS_TREATMENT_INDEX15`, `INDEX17`) và `FEE_LOCK_TIME` (`INDEX19`).
- Lọc `OUT_TIME >= hôm nay − 60 ngày` rồi lọc `MODIFY_TIME`: khoảng 91.000 dòng, **0,5 giây** mỗi truy vấn (30 ngày 0,49 giây, 90 ngày 0,6 giây).
- `MODIFY_TIME` đổi rất thường xuyên: 99,7% hồ sơ đã khoá có `MODIFY_TIME` sau `FEE_LOCK_TIME`. Trong 24 giờ có khoảng 6.000 hồ sơ BHYT (cửa sổ 60 ngày) bị chạm vào; trong 10 phút có khoảng 130.
- Dựng lại một hồ sơ mất khoảng 0,12 giây (1814 hồ sơ mất 3,5 phút).

## Thiết kế

1. **Mốc quét = `HIS_TREATMENT.MODIFY_TIME`** cho cả hai loại bản. Mỗi loại bản vẫn giữ mốc riêng trong `sinh_xml3176_moc`. Keyset `(modify_time, id)` và cửa sổ chồng lấn `chong_lan_phut` giữ nguyên, chỉ đổi cột.
   - Điều kiện loại bản giữ nguyên: chính thức có `FEE_LOCK_TIME`; thông tuyến chưa có `FEE_LOCK_TIME` nhưng đã có `OUT_TIME`.
   - Bộ lọc cấu hình (`co_so`, `doi_tuong_bn`, `dien_dieu_tri`) giữ nguyên.
2. **Cửa sổ ra viện:** chỉ xét hồ sơ có `OUT_TIME >= hôm nay − cua_so_ra_vien_ngay` ngày, để Oracle dùng được chỉ mục `OUT_TIME`. Khoá `cua_so_ra_vien_ngay` nằm trong `config/sinh_xml3176.php`, mặc định 60. Hồ sơ ra viện cũ hơn mà bị sửa thì lấy bằng chạy bù.
3. **Bỏ qua sớm khi `MODIFY_TIME` không đổi:** bảng theo dõi lưu `modify_time` của lần xét cuối. Nếu ứng viên có `MODIFY_TIME` bằng giá trị đó, bỏ qua mà không nạp từ HIS. Bước này để cửa sổ chồng lấn 10 phút không làm mỗi hồ sơ bị dựng lại tới 10 lần.
4. **Chỉ ghi tệp khi nội dung đổi:**
   - Dấu vân tay là `sha1` của mã CSKCB cộng 14 chuỗi XML con, theo thứ tự bảng.
   - Không tính phong bì (`NGAYLAP`) và tên tệp, vì hai thứ này đổi theo giờ sinh.
   - Dấu vân tay **bằng** lần ghi cuối của cùng loại bản: không ghi tệp, không thêm dòng theo dõi mới. Chỉ cập nhật `modify_time` của dòng theo dõi cuối và đếm vào kết quả `khong_doi`.
   - Dấu vân tay **khác**, hoặc chưa có (dòng cũ trước bản này): ghi tệp như hiện nay, thêm dòng theo dõi có `noi_dung_hash` và `modify_time`.
5. **Bị loại** (`bi_loai`): chỉ thêm dòng mới khi dòng cuối không phải `bi_loai` có cùng `ly_do`. Nếu trùng thì chỉ cập nhật `modify_time`.
6. **Lỗi** (`loi`): giữ như hiện nay, luôn ghi dòng.
7. **Bảng theo dõi:** migration mới thêm vào `sinh_xml3176_lan_sinh` hai cột nullable, `modify_time` (bigint) và `noi_dung_hash` (char 40). Không sửa migration cũ.
8. **Chạy bù** (`--ma-dieu-tri`, `--tu-ngay/--den-ngay`): giữ nguyên.
   - `--tu-ngay/--den-ngay` vẫn chọn theo `FEE_LOCK_TIME` / `OUT_TIME`, vì người vận hành nghĩ theo ngày khoá và ngày ra.
   - Chạy bù luôn ghi tệp, nhưng có ghi `noi_dung_hash` và `modify_time` để lượt quét sau so được.
9. **Chuyển tiếp khi triển khai:** mốc đã lưu (giá trị `FEE_LOCK_TIME` / `OUT_TIME` cũ) được dùng thẳng làm mốc `MODIFY_TIME`, vì cùng dạng `yyyyMMddHHmmss`.
   - Lượt đầu sau triển khai sẽ dựng lại các hồ sơ có `MODIFY_TIME` sau mốc đó. Dòng theo dõi cũ chưa có hash, nên những hồ sơ này được ghi lại một lần.
   - Đây là chủ ý: bắt luôn những hồ sơ đã bị bỏ sót.

## Không làm

- Không theo dõi riêng thay đổi danh mục (nhân viên, ICD…). Danh mục đổi không làm `MODIFY_TIME` của hồ sơ đổi.
- Không đổi cách dựng bảng và không đổi "bản vào sau thắng" ở tiền giám định.
- Không thêm chỉ mục vào Oracle HIS (HIS chỉ đọc).

## Kiểm chứng

- Unit test cho từng điểm 1–6 và 8.
- Chạy thật trên dev với một hồ sơ đã mở khoá, sửa, khoá lại mà giữ `FEE_LOCK_TIME`: module sinh lại; HIS chỉ chạm vào hồ sơ mà nội dung không đổi thì module không ghi tệp.
- Đo một lượt quét thường: số ứng viên, số `khong_doi`, thời gian và bộ nhớ.
- Cập nhật `docs/trien-khai-sinh-xml3176.md`: mục cơ chế quét, cấu hình mẫu và danh sách kiểm.
