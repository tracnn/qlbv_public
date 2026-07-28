# Modal chi tiết XML3176 — tải lười theo tab và phân trang phía server

Ngày: 2026-07-28
Phạm vi: `BHYTXml3176Controller` (3 action), `routes/web.php`, blade `bhyt/xml3176/detail-xml*`, JS trong `index.blade.php`

Tiếp nối: `2026-07-28-xml3176-modal-chi-tiet-n1-design.md` (đã cắt N+1). Đợt đó bỏ hàng
nghìn truy vấn; đợt này bỏ việc render toàn bộ hồ sơ ra HTML.

## Vấn đề

Modal chi tiết render **mọi thứ** trong một lần: 15 tab, toàn bộ dòng của mọi bảng con.
Hồ sơ điều trị dài ngày, nhiều dịch vụ thì phản hồi rất lớn và trình duyệt dựng chậm.

Không có phân trang nào — **kể cả phía trình duyệt**. Các bảng XML2–XML5 mang
`class="datatable"` nhưng lớp đó không được khởi tạo ở đâu trong toàn bộ view và JS của
dự án. Mọi dòng đổ thẳng ra HTML thô.

`initializeModalDataTables()` khởi tạo 6 id — `#thuocvt`, `#dvkt`, `#cls`, `#dienbien`,
`#checkHeinCard`, `#xmlErrorChecks` — nhưng chỉ `#checkHeinCard` tồn tại trong blade.
Năm lời gọi còn lại vô tác dụng.

### Chi phí chia hai phần

**Cố định** — 9 tab dạng biểu mẫu một dòng (XML1, 7, 8, 9, 10, 11, 13, 14, 15): khoảng
1.900 dòng markup, luôn render kể cả khi người dùng không mở tab nào trong số đó.

**Biến thiên** — XML2–XML5, tỉ lệ với số dòng của hồ sơ.

### Cách nhóm tab con — không đồng nhất

| Tab | Nội dung | Nhóm theo | Kích thước một tab con |
|---|---|---|---|
| XML2 | thuốc | `ngay_yl`, 8 ký tự đầu | nhỏ — một ngày |
| XML3 | DVKT | **`ma_nhom`** (mã 1–14) | **có thể vài trăm dòng** |
| XML4 | CLS | `ngay_kq`, 8 ký tự đầu | nhỏ — một ngày |
| XML5 | diễn biến | `thoi_diem_dbls`, 8 ký tự đầu | nhỏ — một ngày |

XML3 gom theo nhóm dịch vụ chứ không theo ngày, nên một đợt nằm viện dài có thể dồn vài
trăm dòng vào một tab con. Đây là chỗ duy nhất thực sự cần phân trang.

## Quyết định của chủ đầu tư

1. **Giữ nguyên cách nhóm tab con**, kể cả XML3 nhóm theo `ma_nhom`. Không đổi cách đọc
   dữ liệu của người dùng.
2. **Phân trang phía server cho cả bốn bảng, cỡ trang 100.** Một cơ chế duy nhất thay vì
   hai. Với XML2/4/5 một ngày hiếm khi quá 100 dòng nên thanh phân trang gần như không
   bao giờ hiện — người dùng không thấy gì khác.

## Thiết kế

### Hai tầng tải lười

Bản trình bày ban đầu chia ba tầng. Rút còn hai: dùng `pluck('<cột nhóm>', 'stt')` thì
**một truy vấn cho ra cả danh sách `stt`** (để tính huy hiệu) **lẫn các khoá nhóm** (để
dựng thanh tab con), nên thanh tab con nằm luôn ở vỏ modal.

**Vỏ modal** — `GET xml3176/index/detail-xml/{ma_lk}` (route hiện có, không đổi URL)

Trả về: phần đầu hồ sơ, thanh tab với đầy đủ huy hiệu và ẩn/hiện, nội dung tab XML1
(luôn hiển thị), thanh tab con của XML2–XML5, và **khung rỗng** cho mọi phần còn lại.

| Truy vấn | Mục đích |
|---|---|
| 1 | `xml1` + `withCount` 13 quan hệ → số dòng từng bảng, phục vụ ẩn/hiện tab và `demTheoXml` |
| 1 | tập lỗi → chỉ mục `Xml3176ErrorIndex` |
| 4 | `pluck('<cột nhóm>', 'stt')` của XML2–XML5 → `stt` cho huy hiệu, khoá nhóm cho tab con |

**6 truy vấn, không phụ thuộc độ dài đợt điều trị.**

**Tầng lười A — nội dung một tab** — `GET .../detail-xml/{ma_lk}/tab/{xml}`

Cho 9 tab biểu mẫu một dòng, cộng tab Thẻ BHYT và tab Lỗi XML. Gọi khi người dùng bấm
vào tab đó lần đầu.

**Tầng lười B — một trang của một bảng** — `GET .../detail-xml/{ma_lk}/rows/{xml}?nhom=&page=`

Gọi khi người dùng bấm vào một tab con của XML2–XML5, và khi bấm số trang. Trả về đúng
một `<table>` cộng thanh phân trang.

### Đăng ký bảng nhiều dòng

Một hằng duy nhất mô tả bốn bảng, dùng chung cho cả ba action:

```php
const BANG_NHIEU_DONG = [
    'XML2' => ['model' => Xml3176Xml2::class, 'cot_nhom' => 'ngay_yl',        'cat' => 8],
    'XML3' => ['model' => Xml3176Xml3::class, 'cot_nhom' => 'ma_nhom',        'cat' => 0],
    'XML4' => ['model' => Xml3176Xml4::class, 'cot_nhom' => 'ngay_kq',        'cat' => 8],
    'XML5' => ['model' => Xml3176Xml5::class, 'cot_nhom' => 'thoi_diem_dbls', 'cat' => 8],
];
```

`cat` = số ký tự đầu dùng làm khoá nhóm; `0` nghĩa là lấy nguyên giá trị.

**Bảo mật:** `{xml}` đến từ URL nên phải đối chiếu danh sách trắng này trước khi dùng.
Không khớp thì `abort(404)`. Không được ghép chuỗi tên bảng hay tên cột từ tham số.

### Chia blade

Mỗi blade nặng tách làm hai, **giữ nguyên toàn bộ markup cột hiện có**:

| Trước | Sau |
|---|---|
| `detail-xml-2.blade.php` | `detail-xml-2.blade.php` (thanh tab con + khung rỗng) + `detail-xml-2-rows.blade.php` (một `<table>`) |

Bốn cặp như vậy cho XML2–XML5.

Không gộp bốn bảng thành một blade dùng chung điều khiển bằng cấu hình: chúng khác nhau
hoàn toàn về cột, và cả module đang theo lối một blade một bảng. Gộp lại là đổi lối viết
của codebase để đổi lấy một chỗ ít lặp hơn.

### Tách nhóm là logic thuần

Việc biến danh sách giá trị cột thành danh sách khoá nhóm đã sắp xếp là hàm thuần, tách
riêng để unit-test:

```php
Xml3176DetailTabs::khoaNhom(iterable $giaTri, int $cat): array
```

- `cat > 0` → `substr($v, 0, $cat)`; `cat = 0` → giữ nguyên
- loại giá trị rỗng/null, khử trùng lặp, sắp tăng dần
- trả mảng đánh số lại từ 0

### Thay đổi API của `Xml3176ErrorIndex`

`demTheoStt($items, $xml)` hiện nhận danh sách **model** và đọc `$item->stt`. Đổi thành
nhận thẳng danh sách **số `stt`**, để vỏ modal dùng được `pluck` thay vì dựng 600 model
chỉ để đọc một cột. Test hiện có của phương thức này sửa theo.

Không phương thức nào khác đổi. `demLoi` và `demTheoXml` giữ nguyên; `demTheoXml` nhận
thêm được số nguyên (từ `withCount`) bên cạnh collection.

### Phía trình duyệt

- `shown.bs.tab` trên tab cấp 1: nếu khung còn rỗng thì nạp tầng A.
- `shown.bs.tab` trên tab con của XML2–XML5: nếu khung còn rỗng thì nạp tầng B trang 1.
- Bấm số trang: nạp tầng B trang đó vào đúng khung, không dựng lại gì khác.
- Mỗi khung có cờ "đang nạp" để bấm liên tiếp không bắn trùng request.
- `initializeModalDataTables()`: bỏ 5 id không tồn tại, và lời gọi cho `#checkHeinCard`
  chuyển vào callback sau khi tab Thẻ BHYT nạp xong.

## Ràng buộc phải giữ

1. **Huy hiệu và ẩn/hiện tab giống hệt hôm nay.** Rủi ro cao hơn đợt trước vì nguồn tính
   đổi từ collection đầy đủ sang `withCount` + `pluck`.
2. Ba ngữ nghĩa đếm giữ nguyên: `demLoi` (XML1, số bản ghi lỗi), `demTheoStt`
   (XML2–XML5, số dòng có lỗi), `demTheoXml` (XML7–XML15, có lỗi thì mọi dòng được tính).
3. Cách nhóm tab con giữ nguyên, kể cả XML3 theo `ma_nhom`.
4. Thứ tự tab con giữ nguyên: XML2/XML4/XML5 sắp tăng theo khoá ngày; XML3 theo thứ tự
   `ma_nhom` tăng dần.
5. Tô đỏ dòng lỗi và tooltip mô tả giữ nguyên ở mọi bảng.

## Ngoài phạm vi

1. **`config('__tech.pl6_4210')[$ma_nhom]`** trong `detail-xml-3.blade.php` truy cập mảng
   không kiểm tồn tại — `ma_nhom` lạ sẽ ném lỗi. Lỗi tiềm ẩn có sẵn, không thuộc đợt này.
2. Các nợ đã ghi ở đợt trước: `exportXml()` dựng 2000 file trong một request; các endpoint
   xuất nhận thiếu bộ lọc; `config/datatables.php` đặt `'escape' => '*'` cho toàn app;
   `whereColumn('stt','stt')` trong các model `Qd130*`; màn QD130 nhiều khả năng mắc cùng
   lỗi N+1 chưa kiểm.

## Kiểm chứng

**Tự động** — phủ được phần logic thuần và các hàng rào:

- `khoaNhom` cắt đúng số ký tự, khử trùng lặp, sắp tăng, loại rỗng, `cat = 0` giữ nguyên.
- `demTheoStt` nhận danh sách số `stt` cho ra đúng số dòng có lỗi.
- Danh sách trắng `{xml}` từ chối giá trị ngoài đăng ký.
- Mọi blade trong `bhyt/xml3176` biên dịch ra PHP hợp lệ (`Xml3176BladeCompilesTest` đã có).
- Không blade chi tiết nào chứa `errorResult()` / `Xml3176ErrorResult()` có dấu ngoặc
  (`Xml3176DetailBladeTest` đã có).

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại 272 test xanh.

**Thủ công** — DB dev trống cả bốn bảng `xml3176_*`, không đo được trước/sau tại chỗ, và
không có hạ tầng test JS.

**Trước khi deploy: chụp màn hình modal của một hồ sơ dài ngày, thấy rõ mọi huy hiệu và
mọi tab con.** Không có ảnh này thì mục 3 và 4 dưới đây không kiểm được.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Mở modal hồ sơ dài ngày, xem tab Network | Phản hồi đầu nhỏ hơn hẳn; thời gian mở giảm rõ |
| 2 | Bấm lần lượt từng tab | Mỗi tab nạp một request, lần bấm thứ hai **không** gọi lại |
| 3 | So huy hiệu từng tab với ảnh chụp cũ | Giống hệt từng con số |
| 4 | So danh sách tab con XML2–XML5 với ảnh chụp cũ | Đủ và đúng thứ tự |
| 5 | Bấm một tab con | Bảng hiện đủ dòng của nhóm đó, tối đa 100 dòng mỗi trang |
| 6 | Nhóm quá 100 dòng (thường là XML3) | Thanh phân trang hiện; bấm trang 2 nạp đúng phần tiếp theo |
| 7 | Dòng có lỗi | Vẫn tô đỏ, tooltip vẫn đúng mô tả, ở mọi trang |
| 8 | Tab Thẻ BHYT | Bảng vẫn sắp xếp/tìm kiếm được (DataTables khởi tạo sau khi nạp) |
| 9 | Hồ sơ không có lỗi | Không huy hiệu, không dòng đỏ |
| 10 | Đóng modal, mở hồ sơ khác | Không còn sót nội dung hồ sơ trước |
| 11 | Bấm nhanh liên tiếp vào một tab | Chỉ một request được gửi |
