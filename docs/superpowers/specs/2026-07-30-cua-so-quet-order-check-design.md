# Order-check: chặn trên cửa sổ quét và bỏ qua nguồn khi danh mục rỗng

Ngày: 2026-07-30

## Mục tiêu

Bộ quét y lệnh mất tới 2 phút mỗi lượt khi có tồn đọng, và càng tồn nhiều thì càng chậm —
đúng lúc cần nhanh nhất. Chặn trên khoảng quét để thời gian mỗi lượt trở thành hằng số, và
bỏ qua nguồn quét khi danh mục của nó rỗng.

## Nguyên nhân gốc đã đo được

Laravel sinh SQL này cho mọi truy vấn theo mốc trên Oracle:

```sql
select t2.* from (
  select rownum AS "rn", t1.* from (
    select … from HIS_SERE_SERV ss
    left join HIS_TREATMENT t … left join HIS_BRANCH br …
    where SS.IS_DELETE = ? and SS.ID > ?
    order by SS.ID asc
  ) t1
) t2 where t2."rn" between 1 and 500
```

Truy vấn trong cùng **không có giới hạn**. Oracle nối và sắp xếp **mọi dòng sau mốc**, chỉ
tầng ngoài mới cắt 500. Hai `LEFT JOIN` chặn tối ưu hoá đẩy điểm dừng xuống dưới.

**Đo trên production**, tăng dần khoảng tồn, `limit` giữ nguyên 500:

| Số dòng sau mốc | Thời gian |
| --- | --- |
| 10.000 | 68 ms |
| 100.000 | 582 ms |
| 1.000.000 | 4.849 ms |
| 5.000.000 | 21.356 ms |

Tuyến tính với khoảng tồn, không liên quan `limit`.

Khớp với 591 lượt chạy thật của `his_sere_serv_restriction` — phân bố **hai cực**: 378 lượt
0 giây (đã bắt kịp), phần còn lại 113–126 giây. Lượt lấy đủ 500 dòng: trung bình **117,9
giây**. Lượt lấy 1–499 dòng: 21,9 giây.

Hệ quả nghiệt ngã: mỗi lượt vẫn chỉ tiêu thụ 500 dòng nhưng phải sắp xếp lại toàn bộ tồn.
Tồn càng lớn, tốc độ đuổi kịp càng chậm.

### Phát hiện thứ hai, độc lập

`order_check_ref_service_restriction` có **0 dòng đang bật**. Nguồn quét
"Dịch vụ (giới tính/tuổi)" vì thế **không thể sinh vi phạm nào** — nó nối bảng, kéo 500
dòng về PHP, rồi bỏ hết vì danh mục rỗng. Số liệu xác nhận: 24.402 đã quét, 0 vi phạm.
Hai quy tắc `A_GENDER_MISMATCH` và `A_AGE_OUT_OF_RANGE` đều `is_active = 1` nhưng không có
danh mục thì vô nghĩa.

## Thiết kế

### 1. Chặn trên cửa sổ quét

Thêm điều kiện `id <= mốc + cửa_sổ` vào các truy vấn theo mốc **dùng `id`**. Tập phải sắp
xếp bị chặn cứng, thời gian mỗi lượt thành hằng số.

Cửa sổ mặc định **50.000**, đặt trong `config/order_check.php` khoá `scan_id_window` để đổi
được qua `.env`. Chọn 50.000 vì đo được 10.000 → 68ms và 100.000 → 582ms, nên 50.000 rơi
vào khoảng ~300ms — đủ nhanh mà vẫn tiến được xa mỗi lượt.

Giá trị `0` nghĩa là **không chặn** — giữ đường lui về hành vi cũ nếu cần.

### 2. Quy tắc đẩy mốc

Đây là phần dễ sai nhất, nên tách thành hàm thuần
`App\Services\OrderCheck\Support\CuaSoQuet`:

```php
/** Cuoi cua so quet; $cuaSo = 0 nghia la khong chan */
public static function ketThuc($moc, $cuaSo)

/** Moc moi sau mot luot quet */
public static function mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo)
```

`ketThuc`: `$cuaSo <= 0` → trả `0` (không chặn); ngược lại trả `$moc + $cuaSo`.

`mocMoi` theo bảng:

| Số dòng lấy được | Nghĩa | Mốc mới |
| --- | --- | --- |
| Bằng `limit` | Cửa sổ **chưa** duyệt hết | `$maxIdTrongLo` |
| Ít hơn `limit` (kể cả 0) | Cửa sổ **đã** duyệt hết | `$cuoiCuaSo` |

Và **không bao giờ lùi**: kết quả luôn `>= $moc`. Không chặn cửa sổ (`$cuoiCuaSo = 0`) thì
luôn trả `$maxIdTrongLo` — giữ nguyên hành vi cũ.

Vế thứ hai chính là thứ chữa cái bẫy: cửa sổ rỗng mà không đẩy mốc thì bộ quét **đứng im
vĩnh viễn** và im lặng, không lỗi nào báo ra. Vế thứ nhất bảo đảm không bỏ sót dòng.

### 3. Phạm vi áp dụng

Áp cho **ba nguồn dùng mốc `id`**:

| Nguồn | Bảng | Cỡ |
| --- | --- | --- |
| `his_sere_serv_restriction` | `his_sere_serv` | 168 triệu id, 2 join — nặng nhất |
| `his_exp_mest_medicine` | `his_exp_mest_medicine` | 42,8 triệu id |
| `his_medicine_interactive` | `his_medicine_interactive` | 4.406 id |

**Không** áp cho `his_service_req`: nguồn này dùng mốc theo `modify_time`, mà một dòng cũ
được sửa lại sẽ nhảy về cuối hàng đợi — cửa sổ theo thời gian có ngữ nghĩa khác hẳn và
chưa được khảo sát. Để lại thành việc riêng.

Nguồn `his_medicine_interactive` chỉ có 4.406 dòng nên không hưởng lợi gì, nhưng vẫn áp để
cả ba nguồn cùng một khuôn — bảng đó lớn dần theo thời gian, và một nguồn lệch khuôn là chỗ
người bảo trì sau dễ hiểu nhầm.

### 4. Bỏ qua nguồn quét khi danh mục rỗng

`ServiceRestrictionScanner`: nếu `order_check_ref_service_restriction` không có dòng nào
`is_active`, **không truy vấn HIS** — cả hai quy tắc đều không thể sinh vi phạm.

Nhưng **vẫn phải đẩy mốc**, nếu không đến lúc nhập danh mục sẽ tồn đọng cả chục triệu dòng
và rơi lại đúng vấn đề hiệu năng này. Đẩy mốc tới `min(mốc + cửa_sổ, max(id) hiện tại)` —
một truy vấn `max(id)` rẻ, không join.

**Quyết định có chủ ý:** những dòng bị bỏ qua trong lúc danh mục rỗng sẽ **không bao giờ
được kiểm lại**, kể cả sau khi nhập danh mục. Điều này **không làm mất gì so với hiện tại**:
hôm nay các dòng ấy vẫn được quét nhưng luôn cho kết quả rỗng, và bộ quét vốn chỉ chạy tới
trước, không bao giờ quay lại. Khác biệt duy nhất là chi phí.

Kiểm danh mục rỗng bằng `exists()`, không `count()` — chỉ cần biết có hay không.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`CuaSoQuetTest`) — không đụng CSDL:

1. `ketThuc(1000, 50000)` → `51000`.
2. `ketThuc(1000, 0)` → `0` (không chặn).
3. Lấy đủ `limit` → mốc mới là `maxIdTrongLo`.
4. Lấy ít hơn `limit` → mốc mới là `cuoiCuaSo`.
5. Lấy **0 dòng** → mốc mới là `cuoiCuaSo`. Đây là ca chống đứng im, quan trọng nhất.
6. `cuoiCuaSo = 0` (không chặn) → luôn trả `maxIdTrongLo`, kể cả khi lấy ít hơn `limit`.
7. Không bao giờ lùi: `maxIdTrongLo` nhỏ hơn `moc` → trả `moc`.

**Truy vấn** (`HisOrderSourceCuaSoTest`) — quét mã nguồn, dùng trait `Tests\Support\LocComment`
method `maKhongComment()` để bỏ chú thích trước khi quét (tránh test xanh giả):

1. Cả ba truy vấn theo mốc `id` đều có điều kiện chặn trên.
2. `fetchServiceRequests` (mốc theo thời gian) **không** bị áp cửa sổ — chống việc ai đó
   thấy "cho đồng bộ" mà áp nhầm.

**Bỏ qua khi danh mục rỗng** (`ServiceRestrictionScannerTest`):

1. Danh mục rỗng → không gọi `fetchSereServWithPatient` (dùng test double cho
   `HisOrderSource`), và mốc **vẫn tiến**.
2. Danh mục có dòng → vẫn gọi như cũ.

## Nghiệm thu bằng số

Trên production, đo lại đúng phép đo ở phần nguyên nhân gốc nhưng qua đường mã mới:

- Với tồn 1.000.000 dòng: hiện **4.849 ms**, sau khi sửa kỳ vọng **dưới 500 ms**.
- Với tồn 5.000.000 dòng: hiện **21.356 ms**, sau khi sửa kỳ vọng **dưới 500 ms**.

Thời gian phải **không còn phụ thuộc** khoảng tồn. Đây là nghiệm thu bắt buộc — nó là bằng
chứng duy nhất cho thấy bản sửa giải quyết đúng vấn đề đã đo.

## Phạm vi không làm

- Không đụng `fetchServiceRequests` (mốc theo `modify_time`).
- Không đổi `limit` mỗi lượt (đang 500).
- Không bật/tắt quy tắc nào.
- Không nhập dữ liệu vào `order_check_ref_service_restriction` — đó là việc nghiệp vụ.
- Không quét lại dữ liệu cũ đã bị bỏ qua.
- Không đụng cơ chế watermark của XML3176.

## Ghi chú đã kiểm, không cần xử lý

`ServiceRestrictionScanner` và `InteractionLogScanner` đẩy mốc theo điều kiện
`create_time > maxCreate || (create_time == maxCreate && id > maxId)`, trong khi truy vấn
lại sắp xếp theo `id`. Nếu `create_time` không đơn điệu theo `id` thì mốc có thể tiến chậm
hơn thực tế. Đã đo trên production: lô 500 dòng liên tiếp có **0 dòng** `create_time` nhỏ
hơn dòng trước — `create_time` đơn điệu theo `id`. Không phải rủi ro thực tế, nhưng logic
đẩy mốc sẽ được thay bằng `CuaSoQuet::mocMoi()` nên điểm này tự khắc biến mất.
