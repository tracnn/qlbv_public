# Modal chi tiết hồ sơ XML3176 — cắt N+1 khi tô đỏ dòng lỗi

Ngày: 2026-07-28
Phạm vi: `BHYTXml3176Controller@detailXml` + `resources/views/bhyt/xml3176/detail-xml{,-2,-3,-4,-5}.blade.php`

## Vấn đề

Click đúp vào một dòng hồ sơ để mở modal chi tiết thì treo, tải rất chậm.

Controller không phải nguyên nhân — nó chỉ có hai dòng:

```php
$xml1 = Xml3176Xml1::where('ma_lk', $ma_lk)->firstOrFail();
return view('bhyt.xml3176.detail-xml', compact('xml1'));
```

Toàn bộ chi phí nằm trong blade.

## Nguyên nhân

Việc tô đỏ dòng có lỗi được tính bằng **một truy vấn cho mỗi dòng**, và chạy **hai lượt**.

### Lượt 1 — huy hiệu số lỗi trên tab

`detail-xml.blade.php` có 12 khối theo mẫu:

```php
$errorCountXml = $xml1->Xml3176Xml2->filter(function($item) {
    return $item->errorResult()
        ->where('xml', 'XML2')
        ->where('ma_lk', $item->ma_lk)
        ->where('stt', $item->stt)
        ->exists();
})->count();
```

`errorResult()` có dấu ngoặc nên trả về **query builder mới mỗi lần gọi** — Eloquent không
cache. Mỗi phần tử trong collection là một truy vấn.

### Lượt 2 — thân bảng

`detail-xml-2/3/4/5.blade.php` lặp lại y hệt bên trong `@foreach`, thêm một truy vấn nữa
cho mỗi dòng:

```php
$errorDescriptions = $value_xml2
    ->errorResult()
    ->where('stt', $value_xml2->stt)
    ->pluck('description')
    ->implode('; ');
```

### Quy mô

| Nhóm bảng | Ràng buộc | Số dòng mỗi hồ sơ |
|---|---|---|
| XML2, 3, 4, 5 | `unique(ma_lk, stt)` | nhiều — thuốc, VTYT, DVKT, CLS |
| XML7, 8, 9, 10, 11, 13, 14, 15 | `ma_lk` unique | đúng 1 |

```
Tổng truy vấn ≈ 24 + 2 × (số dòng XML2 + XML3 + XML4 + XML5)
```

Một hồ sơ nội trú 300 dòng thuốc + 200 dịch vụ + 100 CLS → khoảng **1.220 truy vấn** cho
một lần mở modal. Ở 5–10 ms mỗi truy vấn là 6–12 giây.

### Điều khiến cách chữa rất rẻ

Dữ liệu đó **đã nằm sẵn trong bộ nhớ**. `$xml1->Xml3176ErrorResult` được nạp một lần ở
`detail-xml.blade.php:239` để dựng tab "Lỗi XML". Hơn nghìn truy vấn kia chỉ đang hỏi lại
những gì đã có.

## Hai giả thuyết đã bị bác bỏ

Ghi lại để lần sau không ai đi lại đường cụt:

1. **Thiếu index trên `ma_lk` ở xml6–xml15.** Sai. Chúng dùng `$table->string('ma_lk')->unique()`,
   vốn đã tạo index. Index đầy đủ ở cả 14 bảng con.
2. **Huy hiệu của các tab không lọc theo `stt` là lỗi.** Sai. Bảy bảng đó (XML7, 8, 9, 10,
   11, 13, 14) **không có cột `stt`**. Sự phân đôi là có nguyên tắc, không phải sơ suất.

## Thiết kế

### Quy tắc khớp lỗi phải giữ nguyên

| Bảng | Có cột `stt` | Hôm nay khớp theo | Sau khi sửa |
|---|---|---|---|
| XML2, 3, 4, 5 | có | `(xml, stt)` | `(xml, stt)` |
| XML7, 8, 9, 10, 11, 13, 14 | không | `xml` | `xml` |
| XML15 | có | `xml` (huy hiệu bỏ qua `stt`) | `xml` — giữ đúng hôm nay |

XML15 có cột `stt` nhưng huy hiệu hiện tại không dùng tới. Thiết kế **giữ nguyên** hành vi
đó thay vì "sửa cho nhất quán" — đổi cách đếm là đổi con số người dùng nhìn thấy, không
thuộc phạm vi một đợt sửa hiệu năng. Vì vậy API có hai phương thức đếm riêng biệt chứ không
tự suy luận từ sự tồn tại của `stt`.

### Lớp `App\Services\Xml3176\Xml3176ErrorIndex`

Dựng **một lần** từ collection đã nạp, sau đó mọi tra cứu đều trong bộ nhớ.

```php
public static function tu($errors): self       // $errors: Collection<Xml3176ErrorResult>

public function coLoi($xml, $stt = null): bool
public function moTa($xml, $stt = null): string        // noi bang '; ', '' neu khong co
public function demLoi($xml): int                      // XML1
public function demTheoStt($items, $xml): int          // XML2, 3, 4, 5
public function demTheoXml($items, $xml): int          // XML7..XML15
```

- `coLoi($xml)` (không truyền `$stt`) — có bất kỳ lỗi nào thuộc `$xml` hay không.
- `coLoi($xml, $stt)` — có lỗi đúng cặp `(xml, stt)` hay không.

Ba phương thức đếm vì màn hình đang dùng **ba ngữ nghĩa đếm khác nhau**. Đây là thực tế
của giao diện hiện tại, không phải lựa chọn thiết kế; gộp chúng lại sẽ đổi con số người
dùng nhìn thấy:

| Phương thức | Tab | Đếm cái gì |
|---|---|---|
| `demLoi($xml)` | XML1 | số **bản ghi lỗi** thuộc `$xml` |
| `demTheoStt($items, $xml)` | XML2, 3, 4, 5 | số **dòng** có lỗi khớp `stt` của chính nó |
| `demTheoXml($items, $xml)` | XML7…XML15 | `coLoi($xml) ? count($items) : 0` — có lỗi thì **mọi** dòng được tính |

Chuẩn hoá `stt` về chuỗi (`(string)`) ở **cả hai phía** trước khi so. Cả `xml3176_error_results.stt`
lẫn `stt` của các bảng con đều khai `integer`, nhưng driver PDO có thể trả về số nguyên
dưới dạng chuỗi tuỳ cấu hình `PDO::ATTR_EMULATE_PREPARES` — nếu một bên là `int 7` và bên
kia là `string '7'` thì khoá mảng vẫn khớp, song việc ép kiểu tường minh khiến điều đó
đúng theo thiết kế chứ không phải nhờ may mắn.

Lớp này **không chạm cơ sở dữ liệu** — nhận vào một collection, trả ra giá trị. Nhờ vậy
unit-test được đầy đủ, khác đợt sửa màn danh sách vốn phải dựa vào nghiệm thu thủ công.

### Controller

```php
public function detailXml($ma_lk)
{
    $xml1 = Xml3176Xml1::with('Xml3176ErrorResult')->where('ma_lk', $ma_lk)->firstOrFail();

    return view('bhyt.xml3176.detail-xml', [
        'xml1'      => $xml1,
        'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
    ]);
}
```

`with()` chỉ làm rõ ý định — blade vẫn dùng `$xml1->Xml3176ErrorResult` cho tab "Lỗi XML"
nên số truy vấn không đổi.

### Blade

Biến `$chiMucLoi` truyền từ controller nên `@include` con nhận được tự động.

13 khối huy hiệu trong `detail-xml.blade.php` (XML1 cộng 12 tab con):

```php
$errorCountXml = $chiMucLoi->demLoi('XML1');                           // XML1
$errorCountXml = $chiMucLoi->demTheoStt($xml1->Xml3176Xml2, 'XML2');   // XML2,3,4,5
$errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml15, 'XML15'); // XML7..XML15
```

Khối XML1 chỉ tốn một truy vấn (không phải mỗi dòng) nên không phải nguồn chậm, nhưng
thay luôn cho nhất quán và để không còn lời gọi truy vấn nào sót lại trong blade.

4 vòng lặp thân bảng trong `detail-xml-2/3/4/5.blade.php`:

```php
$errorDescriptions = $chiMucLoi->moTa('XML2', $value_xml2->stt);
```

Phần còn lại của mỗi blade (`@if($errorDescriptions) class="highlight-red" ...`) không đổi.

### Kết quả

```
Trước: ≈ 24 + 2 × (số dòng XML2..XML5)
Sau:   ≈ 16, không phụ thuộc số dòng
```

## Ngoài phạm vi

1. **Kích thước HTML và 6 DataTable phía trình duyệt.** Với hồ sơ 600 dòng thì phản hồi và
   thời gian dựng bảng có thể vẫn đáng kể. Quyết định của chủ đầu tư: sửa N+1 trước, **đo
   lại bằng tab Network**, rồi mới quyết có làm tiếp không — tránh sửa mò.
2. **`whereColumn('stt', 'stt')` trong quan hệ `errorResult()`** (Xml3176Xml2/3/4/5/15) so
   cột `stt` của bảng lỗi với chính nó — luôn đúng, một no-op. Vô hại vì blade tự thêm điều
   kiện `stt` thật. Sau đợt này `errorResult()` không còn được gọi ở đâu trong luồng
   XML3176 nữa. Cùng lỗi có ở cả 12 model `Qd130*`, nơi vẫn đang được dùng.
3. **Màn QD130** có cấu trúc blade tương tự và nhiều khả năng mắc đúng N+1 này. Chưa kiểm.

## Kiểm chứng

**Tự động (unit test)** — lần này phủ được phần cốt lõi vì lớp là logic thuần:

- `coLoi` phân biệt đúng cặp `(xml, stt)`, không lẫn giữa XML2 và XML3 cùng `stt`.
- `coLoi($xml)` không truyền `stt` trả đúng "có lỗi nào thuộc xml này không".
- `moTa` nối nhiều mô tả bằng `; ` đúng thứ tự, trả `''` khi không có lỗi.
- `demTheoStt` đếm số phần tử có lỗi, không phải tổng số lỗi.
- `demTheoXml` trả về **toàn bộ** số phần tử khi có lỗi, `0` khi không — đúng ngữ nghĩa
  hôm nay, không phải cách đếm "hợp lý hơn".
- `stt` là `int 7` một phía và `string '7'` phía kia vẫn khớp (driver PDO có thể trả về
  số nguyên dưới dạng chuỗi).

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại 260 test xanh.

**Thủ công** — DB dev trống cả bốn bảng `xml3176_*` nên không đo được trước/sau tại chỗ:

1. Mở modal một hồ sơ nội trú nhiều dòng → thời gian tải giảm rõ rệt.
2. Dòng có lỗi vẫn tô đỏ (`highlight-red`) ở cả bốn tab XML2, XML3, XML4, XML5.
3. Rê chuột lên dòng đỏ → tooltip vẫn hiện đúng mô tả lỗi, nhiều lỗi vẫn nối bằng `; `.
4. Huy hiệu số trên tab giữ **đúng con số như trước khi sửa** — so trực tiếp với ảnh chụp
   màn hình cũ, đây là điểm dễ trôi nhất.
5. Hồ sơ không có lỗi nào → không tab nào hiện huy hiệu, không dòng nào đỏ.
