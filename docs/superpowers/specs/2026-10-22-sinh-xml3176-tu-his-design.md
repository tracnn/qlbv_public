# Thiết kế: Module sinh hồ sơ XML3176 trực tiếp từ HIS (nguồn dự phòng thay HIS desktop)

Ngày: 2026-10-22
Trạng thái: chờ chủ dự án duyệt

## 1. Mục tiêu và lý do

Module tiền giám định XML3176 hiện chỉ có một nguồn hồ sơ. HIS desktop (hisnguonmo, plugin
`HIS.Desktop.Plugins.ExportXmlQD130`) chạy đồng bộ tự động trên một máy Windows và dùng DLL
`His.Bhyt.ExportXml.XML130` để sinh file `GIAMDINHHS` vào `D:\XML\3176` (thông tuyến vào
`D:\XML\3176TT`). Tiền giám định quét hai thư mục này.

Máy trạm đó là mắt xích yếu. Khi HIS treo, token cổng hết hạn (DLL chỉ làm mới token sau khi
đã hết hạn 30 giây, xem `docs/hisnguonmo-xml3176-anh-xa.md`), hoặc Windows khởi động lại, thì
không có file mới. Hồ sơ lên hệ thống bị trễ mà không ai hay.

Module mới chạy trên server qlbv. Nó đọc thẳng Oracle HIS, tự sinh file hồ sơ XML3176 và thả vào
đúng các thư mục trên. Với tiền giám định, module chỉ là thêm một "máy HIS" nữa.

**Thước đo thành công:** khi máy trạm HIS ngừng, hồ sơ có ngày thanh toán vẫn vào thư mục trong
vài phút, và hồ sơ do module sinh đúng theo QĐ130/QĐ3176 (nghiệm thu ở mục 9).

## 2. Mục tiêu, không làm, phản mục tiêu

**Làm:**
- Sinh hồ sơ đủ XML1–XML15, trừ XML12, theo QĐ130 sửa đổi bởi QĐ3176/QĐ-BYT.
- Sinh **bản chính thức** cho mọi hồ sơ có ngày thanh toán.
- Sinh **bản thông tuyến** khi cấu hình cho phép.
- Tự sinh lại khi hồ sơ bị mở khoá, sửa rồi khoá lại.
- Lọc theo cơ sở, đối tượng bệnh nhân, đối tượng thanh toán, diện điều trị.
- Có lệnh sinh lại theo khoảng ngày hoặc theo mã điều trị.

**Không làm:**
- Kiểm tra, ký, gửi cổng. Các việc này vẫn do module tiền giám định thực hiện theo cấu hình
  của nó (công tắc `export_xml3176_enabled`, `exportable_tt`…).
- XML12 (giám định y khoa). Tiền giám định cố ý bỏ XML12 khi nạp.
- Phát hiện HIS đã sinh hay chưa. Module **sinh song song**: mọi hồ sơ đủ điều kiện đều được
  sinh, không quan tâm HIS có thả file hay không.
- Ghi nhận nguồn hồ sơ (HIS hay qlbv) ở phía tiền giám định. Nếu cần, đó là thay đổi riêng ở
  module tiền giám định.
- Giữ lịch sử, so sánh bản HIS với bản qlbv bên trong hệ thống.

**Phản mục tiêu** (xảy ra thì coi là hỏng, dù chạy được):
- **Bắt chước lỗi của DLL HIS.** Hồ sơ phải đúng theo quy định, không phải giống HIS.
- **Ghi bất cứ thứ gì vào Oracle HIS.** Module chỉ được chạy `SELECT`.
- **Phụ thuộc vào mã của module tiền giám định.** Không dùng `Xml3176Service`,
  `Xml3176Importer`, model `App\Models\BHYT\Xml3176*`, không thêm cột hay job vào chuỗi của
  nó. Hai bên chỉ gặp nhau ở định dạng file. Module phải nhấc ra project khác được (chủ dự án
  sẽ tách sau).
- **Thả vào thư mục thật một hồ sơ thiếu bảng.** Khi nạp lại, tiền giám định xoá sạch XML2–15
  cũ của hồ sơ (`Xml3176Service::deleteExistingXml3176`), nên một bản thiếu bảng sẽ xoá dữ liệu
  thật của bản HIS đầy đủ.
- **Âm thầm bỏ dữ liệu.** Đây là lỗi lớn của DLL: mỗi bảng bọc một `try/catch`, mất cả bảng
  mà vẫn gửi. Module xử lý dữ liệu xấu theo mục 5.4. Lỗi của một hồ sơ không bao giờ được
  làm dừng cả lượt quét.

## 3. Quyết định của chủ dự án

1. **Đúng quy định, không giống HIS.** Chỗ nào DLL làm khác QĐ130/3176 thì làm theo quy định.
2. **Bản chính thức chỉ cần có `FEE_LOCK_TIME`** (ngày thanh toán). Khác DLL: **không** đòi
   `HEIN_LOCK_TIME` (duyệt giám định BHYT), và không đòi `FEE_LOCK_TIME ≤ HEIN_LOCK_TIME`.
3. **Bản thông tuyến bật/tắt bằng cấu hình.** Điều kiện là đã kết thúc điều trị (`OUT_TIME`)
   mà chưa có `FEE_LOCK_TIME`.
4. **Bản nào thả vào sau thì giữ bản đó.** Đây đúng là hành vi hiện tại của tiền giám định:
   cùng `MA_LK` thì ghi đè. Module không cần làm gì thêm.
5. **Sinh song song**, như mục 2.
6. **Sửa rồi khoá lại thì tự sinh lại.**
7. **Lọc như hisnguonmo**: cơ sở, đối tượng bệnh nhân, đối tượng thanh toán, diện điều trị.
8. **Cấu hình nằm trong `config/organization.php`**, để triển khai được ở các đơn vị khác.
   File này nằm trong `.gitignore`, mỗi đơn vị giữ một bản.
9. **Tạm thời nằm trong repo qlbv**, sẽ tách sau.
10. **Làm đủ các bảng trừ XML12.**
11. **Nghiệm thu bằng cách so với file HIS**, như mục 9.

## 4. Kiến trúc

```
Oracle HIS (connection HISPro, chỉ SELECT)
        │
        ▼
 [Quét]  chọn hồ sơ theo mốc FEE_LOCK_TIME / OUT_TIME + bộ lọc
        │
        ▼
 [Nạp]   đọc theo lô: hồ sơ, diện điều trị, dịch vụ, ekip, giường, CLS, PTTT,
         DHST, tờ điều trị, hội chẩn, sơ sinh, HIV, lao + danh mục + HIS_CONFIG
        │
        ▼
 [Dựng]  mỗi hồ sơ → XML1..XML15 (trừ XML12), mỗi bảng một lớp riêng, hàm thuần
        │
        ▼
 [Ghi]   phong bì GIAMDINHHS → file tạm → đổi tên sang tên thật trong thư mục đích
        │
        ▼
 Bảng theo dõi riêng của module (MySQL qlbv)
```

- **Vị trí mã:** thư mục riêng `app/Services/SinhXml3176/`, có thể thêm `app/Models/SinhXml3176/`
  cho bảng theo dõi. Lệnh artisan riêng, chạy liên tục (`--lien-tuc`, giống các lệnh hiện có),
  cài thành dịch vụ NSSM riêng trong `install_service.bat`.
  *Lựa chọn của người viết:* không dùng scheduler, vì `Kernel::schedule()` đang rỗng và mọi
  tác vụ hiện chạy bằng NSSM.
- **Đọc HIS:** dùng `DB::connection(<tên lấy từ cấu hình>)`, mặc định `HISPro`. Làm theo mẫu
  quét mốc của `app/Services/OrderCheck/HisOrderSource.php`. Các bảng chưa từng được đọc trong
  qlbv mà DLL cần: `HIS_PATIENT_TYPE_ALTER`, `V_HIS_SERE_SERV_2` (hoặc truy vấn tương đương),
  `HIS_CONFIG`.
- **Bảng XML:** mỗi bảng một lớp dựng, nhận dữ liệu đã nạp, trả về mảng trường theo đúng thứ
  tự chỉ tiêu. Không truy vấn CSDL bên trong lớp dựng, để test được bằng dữ liệu giả.
- **Phong bì** theo đúng cấu trúc tiền giám định đang đọc (`Xml3176Importer::nhapTuChuoi`):
  - gốc `GIAMDINHHS`, có `THONGTINDONVI/MACSKCB`
  - `THONGTINHOSO/DANHSACHHOSO/HOSO/FILEHOSO`, mỗi `FILEHOSO` có `LOAIHOSO` và `NOIDUNGFILE`
  - `NOIDUNGFILE` là base64 của XML con (UTF-8, không BOM)
  - `CHUKYDONVI` để trống, vì module không ký

  Một file một hồ sơ.
- **Máy chạy:** cùng máy với dịch vụ nạp XML3176. `D:\XML\3176` và `D:\XML\3176TT` là ổ cục
  bộ. *(Chủ dự án xác nhận.)*
- **Ghi file an toàn:** ghi ra file tạm (đuôi khác `.xml`, hoặc nằm ngoài thư mục quét), rồi đổi
  tên. Mục đích là lệnh quét của tiền giám định không bao giờ đọc phải file đang ghi dở.
- **Tên file:** có tiền tố nhận diện nguồn, ví dụ `QLBV_<yyyyMMddHHmmss>_<MaDieuTri>.xml`. Bản
  thông tuyến thêm đuôi `_TT`. Tiền giám định không phụ thuộc vào tên file. Tiền tố chỉ để người
  vận hành nhận ra file do module sinh.

## 5. Chọn hồ sơ

### 5.1 Hai loại bản

| | Bản chính thức | Bản thông tuyến |
|---|---|---|
| Điều kiện | Có `OUT_TIME` và `FEE_LOCK_TIME` | Có `OUT_TIME`, chưa có `FEE_LOCK_TIME` |
| Bật | Luôn bật khi module bật | `thong_tuyen = true` |
| Thư mục | `thu_muc` (mặc định `D:\XML\3176`) | `thu_muc_tt` (mặc định `D:\XML\3176TT`) |
| Mốc quét | `FEE_LOCK_TIME` | `OUT_TIME` |

Khi hồ sơ thông tuyến được thanh toán, nó có `FEE_LOCK_TIME` và được sinh bản chính thức. Bản
chính thức vào sau nên ghi đè bản thông tuyến ở phía tiền giám định.

### 5.2 Bộ lọc

Một bộ lọc chung cho mọi cơ sở được chọn. *(Lựa chọn của người viết; chủ dự án đã đồng ý với
khối cấu hình ở mục 7.)*

| Khoá cấu hình | Áp vào | Tác dụng | Tương ứng hisnguonmo |
|---|---|---|---|
| `co_so` | Hồ sơ | Chỉ hồ sơ thuộc các cơ sở có mã trong danh sách | `BRANCH_IDs` |
| `doi_tuong_bn` | Hồ sơ | Chỉ hồ sơ có đối tượng bệnh nhân thuộc danh sách | `TDL_PATIENT_TYPE_IDs` |
| `doi_tuong_tt` | **Từng dòng dịch vụ** | Hồ sơ vẫn sinh, XML2/XML3 chỉ giữ dòng có đối tượng thanh toán thuộc danh sách | `patientTypeTTIds` → `PATIENT_TYPE_IDs` của sere_serv |
| `dien_dieu_tri` | Hồ sơ | Rỗng nghĩa là tất cả | `TDL_TREATMENT_TYPE_IDs` |

Cấu hình ghi bằng **mã**, không dùng ID HIS. Module tự đổi mã sang ID khi truy vấn, nhờ vậy
cùng một khối cấu hình dùng được ở mọi đơn vị. Lấy mã cơ sở ở đâu
(`HIS_BRANCH.HEIN_MEDI_ORG_CODE` hay `BRANCH_CODE`) do người làm xác minh, rồi ghi rõ trong
tài liệu triển khai.

### 5.3 Hồ sơ bị loại

Kiểm tra trước khi dựng, tương tự `CreateXmlProcessor.Check/CheckCollinearXml`, trừ quy tắc
`HEIN_LOCK_TIME` (quyết định 2). Các trường hợp bị loại:
- thiếu địa chỉ, quốc tịch, khoa kết thúc, loại ra viện, kết quả điều trị hoặc ICD
- không có diện điều trị
- điều trị nội trú mà không có `CLINICAL_IN_TIME`
- `FEE_LOCK_TIME < OUT_TIME`
- không còn dòng dịch vụ nào sau khi lọc
- trẻ sơ sinh thiếu thẻ tạm

Hồ sơ bị loại thì **không sinh file**, chỉ ghi lý do vào bảng theo dõi.

Danh sách trên lấy theo DLL. Điều kiện nào trái quy định thì người làm ghi lại và bàn với chủ
dự án.

**Bản thông tuyến** dùng bộ kiểm tra và bộ lọc dịch vụ của nhánh thông tuyến trong DLL
(`CheckCollinearXml`, bộ lọc riêng của nhánh này), không dùng của bản chính thức.
*(Lựa chọn của người viết.)*

### 5.4 Dữ liệu xấu

*(Lựa chọn của người viết; có căn cứ: phản mục tiêu không cho thả hồ sơ thiếu bảng.)*

- Một dòng hoặc một bảng không dựng được, mà theo điều kiện thì hồ sơ phải có bảng đó, thì
  **loại cả hồ sơ**. Không ghi file, trạng thái "lỗi", ghi rõ bảng, dòng và lý do. Không bao
  giờ bỏ riêng một dòng rồi vẫn ghi file, vì tổng tiền XML1 sẽ lệch và bản thiếu sẽ đè mất bản
  HIS đầy đủ.
- Hồ sơ lỗi được thử lại ở lượt sau khi dữ liệu HIS của nó đổi (mốc đổi). Lệnh chạy bù cũng
  thử lại được.

### 5.5 Đối tượng không phải BHYT

Module sinh cho mọi hồ sơ mà bộ lọc chọn, kể cả viện phí và dịch vụ. Cách dựng các trường tiền
và đối tượng cho nhóm này đi theo cách DLL đang làm, trừ chỗ trái quy định.
*(Lựa chọn của người viết.)*

Giá trị trong khối cấu hình ở mục 7 chỉ là mẫu. Khi triển khai, phải khai **giống bộ lọc đồng
bộ tự động đang đặt trên máy trạm HIS** (màn "Thiết lập đồng bộ" của plugin ExportXmlQD130).
Nếu khai hẹp hơn, module không thay được HIS khi máy trạm ngừng. Tài liệu triển khai ghi rõ
bước đối chiếu này.

### 5.6 Key `HIS_CONFIG`

*(Lựa chọn của người viết, suy từ quyết định 1.)*

- Key là **lựa chọn của bệnh viện mà quy định cho phép** (ví dụ cách hiển thị `NGAY_TH_YL`, có
  lấy dịch vụ không thực hiện hay không): module đọc và tôn trọng như DLL.
- Key mà giá trị của nó cho ra kết quả **trái quy định**: quy định thắng. Mỗi key như vậy là
  một dòng trong bảng "Khác biệt với HIS".
- Key mà DLL khai báo nhưng không có tác dụng (ví dụ `LAY_CA_DVU_0_DONG` ở XML3): module làm
  theo quy định, ghi chú lại.

## 6. Quét, sinh lại, chạy bù

- **Mốc quét:** lưu riêng cho từng loại bản. Mỗi lượt lấy các hồ sơ có mốc (`FEE_LOCK_TIME`
  hoặc `OUT_TIME`) lớn hơn mốc đã lưu, có lùi lại một khoảng chồng lấn để không sót. Chống
  trùng nhờ bảng theo dõi.
- **Sinh lại khi sửa rồi khoá lại:** việc khoá lại làm đổi `FEE_LOCK_TIME`, hồ sơ lại lọt vào
  lượt quét. Bảng theo dõi lưu giá trị `FEE_LOCK_TIME` của lần sinh gần nhất. Hồ sơ được sinh
  lại khi giá trị đó khác giá trị hiện tại.
  Có những sửa đổi không đi qua khoá lại (ví dụ sửa hành chính sau khi đã khoá). Có bắt các
  trường hợp đó hay không, bằng `MODIFY_TIME` hay cách khác, là phần để người làm cân nhắc.
- **Bản thông tuyến chỉ sinh một lần**, lúc hồ sơ có `OUT_TIME`. Những lần sửa dịch vụ trước
  thanh toán không làm sinh lại bản thông tuyến. Bản đúng cuối cùng là bản chính thức, sinh ra
  khi hồ sơ có `FEE_LOCK_TIME`. *(Lựa chọn của người viết.)*
- **Chạy bù:** lệnh sinh lại theo khoảng ngày, hoặc theo danh sách mã điều trị. Lệnh không đụng
  tới mốc quét. Có tuỳ chọn ghi vào thư mục thử thay cho thư mục thật.
- **Bảng theo dõi** (MySQL qlbv, migration riêng của module), mỗi lần sinh một dòng:
  - mã và ID điều trị, loại bản
  - `FEE_LOCK_TIME` / `OUT_TIME` lúc sinh
  - thời điểm sinh, tên file, trạng thái (đã ghi / bị loại / lỗi), lý do hoặc lỗi
  - danh sách bảng XML đã có

  Cần một màn xem bảng này hay không thì để bàn sau. Lần này chỉ cần bảng và lệnh.

## 7. Cấu hình

Khối mới trong `config/organization.php`:

```php
'sinh_xml3176' => [
    'enabled'        => false,           // tắt sẵn
    'ghi_thu_muc_that' => false,         // false: chỉ ghi vào thu_muc_thu (xem mục 8)
    'thong_tuyen'    => false,
    'ket_noi_his'    => 'HISPro',
    'co_so'          => ['01929'],
    'doi_tuong_bn'   => ['BHYT'],
    'doi_tuong_tt'   => ['BHYT'],
    'dien_dieu_tri'  => [],
    'thu_muc'        => 'D:\XML\3176',
    'thu_muc_tt'     => 'D:\XML\3176TT',
    'thu_muc_thu'    => 'D:\XML\SinhXml3176Thu',
],
```

- Thiếu cả khối, hoặc `enabled = false`, thì module coi như tắt và lệnh thoát êm.
- Khối mẫu được ghi trong tài liệu triển khai (`docs/trien-khai-sinh-xml3176.md`), để đơn vị
  khác chép vào bản `organization.php` của họ.
- Các tham số kỹ thuật **không phụ thuộc đơn vị** (kích thước lô, khoảng nghỉ giữa các lượt,
  khoảng chồng lấn) thì người làm có thể để ở một file config đi theo git. Đây là phần để người
  làm quyết định.

## 8. Thứ tự làm và chặn rủi ro thiếu bảng

| Đợt | Nội dung |
|---|---|
| 1 | Khung chạy (quét, cấu hình, bảng theo dõi, phong bì, ghi file, lệnh chạy bù) + XML1, XML2, XML3 |
| 2 | XML4, XML5 |
| 3 | XML6, XML7, XML8, XML9, XML10, XML11, XML13, XML14, XML15 |

Chừng nào chưa xong đợt 3, module **chỉ được ghi vào `thu_muc_thu`**. Ràng buộc này phải nằm
trong mã chứ không chỉ trong tài liệu: khi `ghi_thu_muc_that = true` mà còn bảng chưa có lớp
dựng, lệnh phải từ chối chạy.

Mỗi bảng có mặt trong hồ sơ theo **điều kiện của chính nó**: XML4 khi có CLS, XML7 khi ra viện,
XML13 khi chuyển tuyến, XML9 khi có sinh con, XML15 khi điều trị lao… Điều kiện cụ thể từng
bảng lấy theo quy định. Bảng ánh xạ ghi lại điều kiện mà DLL đang dùng, để tham khảo.

## 9. Nghiệm thu

1. Chọn một ngày có đủ loại hồ sơ. Trong ngày đó, chủ dự án đổi thư mục lưu của HIS sang một
   thư mục phụ để giữ file gốc, rồi tự chép file sang thư mục quét. *(Chủ dự án xác nhận.)*
   Công cụ so đọc file HIS từ thư mục phụ này.
2. Cho module sinh lại toàn bộ hồ sơ ngày đó vào `thu_muc_thu`.
3. So **từng trường** với file HIS của cùng hồ sơ. Người làm viết công cụ so: giải nén
   `NOIDUNGFILE`, ghép dòng theo khoá hợp lý (ví dụ `MA_LK` + `STT`, hoặc mã dịch vụ + ngày y
   lệnh), rồi liệt kê từng chỗ lệch.
4. **Đạt** khi mọi chỗ lệch đều thuộc một trong hai loại:
   - lỗi DLL đã liệt kê trong `docs/hisnguonmo-xml3176-anh-xa.md`
   - quy tắc cố ý khác HIS, được ghi trong bảng "Khác biệt với HIS" (xem bên dưới)

   Chỗ lệch nào ngoài hai loại đó là lỗi của module.
5. Nạp thử một lô file vào tiền giám định ở môi trường test. Lô phải nạp được hết, và chuỗi
   kiểm tra chạy bình thường.

Song song với mã, người làm duy trì bảng **"Khác biệt với HIS"** trong tài liệu triển khai. Mỗi
dòng gồm: bảng/trường, HIS làm gì, module làm gì, căn cứ quy định.

## 9a. Hệ quả vận hành đã chấp nhận

Đây là hệ quả của quyết định 4 và 5, chủ dự án đã chấp nhận. Chúng cần được ghi trong tài liệu
triển khai:

- **Mỗi hồ sơ thường được nạp và kiểm 2 lần**, 3 lần nếu bật thông tuyến. Tải hàng đợi kiểm của
  tiền giám định tăng tương ứng.
- **Bản nào vào sau cũng có thể bị gửi cổng.** Nếu tiền giám định đang bật xuất, ký, gửi
  (`export_xml3176_enabled`, `exportable_tt`), thì bản nào nạp sau cũng đi tiếp, kể cả bản do
  module sinh. Bật hay tắt các công tắc đó là việc của module tiền giám định.
- **Bản của module có thể bị bản HIS ghi đè.** Module sinh ngay khi có ngày thanh toán, còn HIS
  thường sinh muộn hơn, sau khi duyệt giám định. Vì vậy trong vận hành bình thường, bản HIS
  (còn lỗi DLL) thường là bản cuối cùng. Module phát huy tác dụng khi HIS không sinh được.

## 10. Ràng buộc kỹ thuật

- Laravel hiện có của qlbv, `yajra/laravel-oci8`. Không thêm dịch vụ mới ngoài một dịch vụ
  NSSM.
- **Test:** PHPUnit theo quy ước của repo:
  - SQLite in-memory dựng bằng trait trong `tests/Support/`
  - cấm `RefreshDatabase` (bộ test từng xoá sạch CSDL qlbv)
  - chạy với `DB_HOST=127.0.0.1`
  - không test nào được nối Oracle thật

  Lớp dựng từng bảng là hàm thuần, test bằng dữ liệu giả có chủ đích, gồm cả các trường hợp
  từng làm DLL hỏng (danh sách rỗng, mã thẻ ngắn, ngày null…).
- **Tên lớp và chú thích** viết tiếng Việt không dấu, theo quy ước repo.
- **Hiệu năng:** đọc theo lô ID (giới hạn `IN` của Oracle là 1000), không truy vấn từng dịch vụ
  một. Một lượt quét không giữ quá nhiều hồ sơ trong bộ nhớ: máy chủ mới chỉ có PHP 128MB.

## 11. Tài liệu căn cứ

- `docs/hisnguonmo-xml3176-anh-xa.md`: nguồn cột HIS của từng trường, các key `HIS_CONFIG`,
  điều kiện sinh từng bảng, và danh sách lỗi của DLL. Đây là **bản đồ dữ liệu**, không phải đặc
  tả đúng sai.
- Mã DLL dịch ngược, để tra khi bảng ánh xạ chưa đủ chi tiết:
  `D:\his-x64\ReferencedAssemblies\His.Bhyt.ExportXml.XML130*.dll`, dịch bằng `ilspycmd -p -o
  <out> -r D:\his-x64\ReferencedAssemblies <dll>`.
- QĐ130/QĐ-BYT, QĐ4750/QĐ-BYT, QĐ3176/QĐ-BYT ngày 29/10/2024: **đặc tả đúng sai** của từng chỉ
  tiêu.
- `app/Services/Xml3176/Xml3176Importer.php` và các migration `xml3176_*`: những thẻ mà tiền
  giám định thực sự đọc. Hồ sơ sinh ra phải nạp được qua đây.

## 12. Để người làm quyết định

- Cách chia lớp, tên lớp, tên bảng theo dõi.
- Lấy dịch vụ từ view `V_HIS_SERE_SERV_2` có sẵn trong Oracle hay tự join từ bảng gốc. Cần xác
  minh view tồn tại và tài khoản `HIS_RS` có quyền đọc.
- Đọc `HIS_CONFIG` mỗi lượt hay cache trong khoảng ngắn.
- Khoảng nghỉ giữa các lượt, kích thước lô, khoảng chồng lấn mốc.
- Có bắt các sửa đổi hồ sơ không làm đổi `FEE_LOCK_TIME` hay không (mục 6).
