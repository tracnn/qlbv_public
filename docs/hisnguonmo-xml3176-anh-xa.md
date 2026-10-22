# Ánh xạ trường XML QĐ130 / QĐ3176 trong hisnguonmo

Nguồn: dịch ngược (ILSpy 11.1) bộ DLL `His.Bhyt.ExportXml.XML130*.dll` lấy từ `D:\his-x64\ReferencedAssemblies`. Đây là bản build trên máy trạm, ngày file từ 11/05/2026 đến 16/06/2026. Mã nguồn dịch ngược nằm ở thư mục scratchpad của phiên làm việc, không đưa vào repo.

Số dòng trong tài liệu được viết tắt như sau:
- `HSP:` là `HoSoProcessor.cs` của lõi
- `X1P:` là `Xml1Processor.cs`
- `L:` là dòng trong processor của chính bảng đang mô tả

## Tóm tắt

### Kiến trúc
Phần sinh XML gồm một DLL lõi `His.Bhyt.ExportXml.XML130` và 15 DLL con, mỗi bảng XML1–XML15 một DLL. Plugin `HIS.Desktop.Plugins.ExportXmlQD130` nạp dữ liệu qua MOS API, gom thành `InputADO` rồi gọi `CreateXmlProcessor`.

Lõi làm các việc sau:
- Ghép các bảng vào file bao `<GIAMDINHHS>`. `NOIDUNGFILE` của mỗi bảng là Base64 UTF-8, có kèm BOM.
- Ký riêng XML13, XML14, XML15 bằng `delegateSignXml(xml, "DU_PHONG")` (HSP:720/744/765).
- Đăng nhập `api/token/take` với MD5 của mật khẩu.
- Gửi hồ sơ lên `api/qd130/guiHoSoXmlQD4750`. Riêng XML12 gửi lên `guiDuLieuGdykQd130`.

### QĐ3176 khác QĐ130 ở đâu
Không có bộ sinh riêng cho 3176. Cờ `IS_3176` được chuyền từ lõi xuống các DLL con, trừ XML4 và XML12. Mỗi DLL con có đúng một khối `if (data.IS_3176)` ở cuối hàm, khối này sửa hoặc bổ sung vài trường:

| Bảng | Thay đổi khi IS_3176 |
|---|---|
| XML1 | SO_CCCD rơi xuống CMND rồi hộ chiếu. MA_NGHE_NGHIEP cắt còn 2 ký tự. CHAN_DOAN_VAO lấy chẩn đoán sơ bộ. MA_BENH_YHCT tối đa 13 mã. MA_TTDV lấy CCHN thủ trưởng. DU_PHONG ghi chiều cao |
| XML2 | Tách T_NGUONKHAC thành NSNN/VTNN/VTTN/CL theo `HEIN_PAY_SOURCE_TYPE_ID`. Thêm tuỳ chọn để trống NGAY_TH_YL |
| XML3 | MA_MAY = `MACHINE_GROUP_CODE.SOURCE_CODE[SOURCE_NAME].SERIAL_NUMBER`. NGUOI_THUC_HIEN lấy CCCD khi người đó không có CCHN |
| XML5 | NGUOI_THUC_HIEN của dòng khám lấy `DIPLOMA ?? IDENTIFICATION_NUMBER` |
| XML6, XML15 | SO_CCCD rơi xuống CMND rồi hộ chiếu |
| XML7 | PP_DIEU_TRI, MA_TTDV, MA_BS, TEN_BS lấy theo trưởng khoa ra viện |
| XML8 | MA_TTDV. MA_LOAI_KCB được tính lại nhưng ra kết quả y như 130 |
| XML9, XML11, XML14 | MA_TTDV (thêm MA_BS ở XML11; NGAY_HEN_KL và MA_BENH_YHCT ở XML14) |
| XML10 | MA_TTDV, MA_BS. **CHAN_DOAN_RV bị gán cứng "dưỡng thai"** |
| XML13 | MA_NGHE_NGHIEP, MA_TTDV. **MA_NOI_DI, MA_NOI_DEN, TEN_DICH_VU, TEN_THUOC, PP_DIEU_TRI bị gán null** |

Ở tất cả các bảng, khi bật IS_3176 thì **MA_TTDV luôn bằng DIPLOMA (số CCHN) của (phó) giám đốc**. Key `MA_TTDV_FROM_PROFILE_XML` và `REPRESENTATIVE_HEIN_CODE` bị bỏ qua. Hồ sơ không ghi giám đốc thì MA_TTDV rỗng.

### Các lỗi đáng kể nhất
Các mục 1, 2, 3, 5, 6 và lỗi `long.Parse` ở mục 4 đã được kiểm lại trực tiếp trên mã dịch ngược. Các mục còn lại lấy theo báo cáo của agent đọc mã, có số dòng để tra lại.

1. **XML15** luôn có 2 phần tử `CHITIET_DIEUTRI_BENHLAO` giống hệt nhau, do `new List { x, x }`.
2. **XML10 và XML13 khi bật IS_3176** mất dữ liệu như bảng trên: chẩn đoán dưỡng thai bị gán cứng, 5 trường chuyển tuyến bị null.
3. **Bộ lọc XML3** nhận mọi DVKT/VTYT đã thực hiện mà không xét giá (L:201). Key `LAY_CA_DVU_0_DONG` không có tác dụng. Plugin bù lại bằng cách lọc `PRICE > 0` từ trước.
4. **Cả một bảng có thể mất chỉ vì một dòng dữ liệu lỗi.** Mỗi bảng nằm trong một `try/catch` riêng, nên khi có lỗi bảng đó biến mất khỏi hồ sơ mà hồ sơ vẫn được gửi. Ví dụ:
   - giường cuối không có thời điểm ra: `long.Parse("" + "00")` làm mất cả XML3
   - mã thẻ dưới 10 ký tự
   - ICD_TEXT có ít phần tử hơn ICD_SUB_CODE (XML11)
5. **Điều kiện sinh XML5 (HSP:571)** sai thứ tự ưu tiên toán tử. Khi `serverInfo == null` mã vẫn đọc `serverInfo.TypeXml`, ném `NullReferenceException`, và cả hồ sơ thất bại.
6. **Token chỉ được làm mới sau khi đã hết hạn 30 giây** (`Now - 30s >= expires`, HSP:887). Kiểm tra chứng chỉ SSL bị tắt cho toàn bộ tiến trình (HSP:1077).
7. **Tiền VTYT (XML3):**
   - `THANH_TIEN_BH` không nhân `TYLE_TT_BH`.
   - Tổng các nguồn khác con không khớp `T_NGUONKHAC` khi có chặn số âm.
   - Tỷ lệ bị làm tròn về số nguyên (kiểu banker) trước khi nhân tiền.
8. **XML8:** HO_TEN_CHA và HO_TEN_ME bị gán ngược do thứ tự toán tử. MA_LOAI_KCB "09" được gắn cho **nội trú** dưới 4 giờ, còn điều trị ban ngày luôn ra "04".
9. **Ngày chứng từ:**
   - XML9 và XML11 lấy NGAY_CT từ `IN_DATE`.
   - XML12 lấy `DateTime.Now` khi không đọc được ngày họp.
   - NGAY_CHUNG_TU của XML12 ở định dạng `ddMMyyyy`, khác mọi trường ngày khác.

Chi tiết từng trường ở 4 phần dưới. Mỗi phần do một agent đọc mã viết, sau đó các kết luận nặng ở mục trên đã được kiểm lại trực tiếp trên mã dịch ngược.

---

## Bản đồ DLL His.Bhyt.ExportXml.XML130 — Lõi + XML1

Nguồn: mã ILSpy tại `scratchpad\xml130\His.Bhyt.ExportXml.XML130\` và `...\His.Bhyt.ExportXml.XML130.XML1\`.
Viết tắt: `CXP` = `CreateXmlProcessor.cs`, `HSP` = `HoSoProcessor.cs` (lõi), `HSPP` = `HoSoProcessorPlus.cs`, `X1P` = `Xml1Processor.cs`. Số sau dấu `:` là số dòng.

---

### 1. Lõi

#### 1.1 Các hàm public của `CreateXmlProcessor`

| Hàm | Làm gì |
|---|---|
| `GetFileName()` CXP:24 | `"{Now:dd.MM.yyyy_HH.mm.ss}_{TREATMENT_CODE}_{TDL_PATIENT_CODE}.xml"`; nếu không có Treatment thì `Guid.xml`. |
| `GetFileNameCollinear()` CXP:39 | Giống trên, thêm hậu tố `_TT.xml`. |
| `Run(ref error)` CXP:54 | `Check()` → `HoSoProcessor.Processor(entity)` → `MemoryStream` của file GIAMDINHHS (1 hồ sơ). |
| `RunCollinearXml(ref error)` CXP:72 | `CheckCollinearXml()` (lỏng hơn: KHÔNG bắt FEE_LOCK_TIME/HEIN_LOCK_TIME, bộ lọc dịch vụ khác: cần `TDL_HEIN_SERVICE_BHYT_NAME`, `VIR_PRICE>0`, dòng BHYT phải có `VIR_TOTAL_HEIN_PRICE>0` hoặc (=0 và `OTHER_SOURCE_PRICE>0`); không lọc `IS_EXPEND`) → cùng `HoSoProcessor.Processor`. |
| `RunXml12(ref error)` CXP:90 | `CheckXml12()` (chỉ cần địa chỉ, quốc tịch, `ListMedicalAssessment`) → `Processor(entity, IsXml12:true)`: hồ sơ CHỈ chứa XML12. |
| `RunPlus(pathSave, ref error)` / `RunXml12Plus` CXP:108/131 | Nhiều hồ sơ một file (`ListTreatment`): `CheckPlus` chạy `Check` từng hồ sơ, loại hồ sơ lỗi (gom lỗi `"{MA}: {lỗi}"` nối `\r\n`), rồi `HoSoProcessorPlus.ProcessorPlus`. `pathSave` chỉ bị kiểm rỗng — xem mục 4. |
| `GetDataFromString(xml)` CXP:154 | Đọc ngược: lấy node `//THONGTINHOSO`, deserialize, giải base64 `NOIDUNGFILE`. |
| `SyncData()` CXP:189 | `SendXMLQD130(IsRunCollinearXml:false, IsRunOnlyXml12:false, isSendfileSign:false)` — tạo bằng `Run` rồi gửi cổng. |
| `SyncDataCollinear()` CXP:202 | Như trên, tạo bằng `RunCollinearXml`. |
| `SyncDataXml12()` CXP:215 | Tạo bằng `RunXml12`, gửi endpoint GĐYK. |
| `SendFileSign(filepath)` CXP:228 | Đọc nguyên byte file đã ký từ đĩa (`File.ReadAllBytes`) và gửi, KHÔNG chạy `Check` (HSP:902–908). |
| `RunXML1_2_3()` CXP:818 | Lọc dịch vụ rồi `HoSoProcessor.CreateNoiDungFileXML1_2_3` → trả về `XML1.Base.InputADO` đã có tổng tiền (dùng để xem trước XML1). |

**`Check()` (CXP:241–371)** — điều kiện chặn (thông báo lỗi tiếng Việt):
địa chỉ (cần tỉnh+xã của `TDL_PATIENT_*` hoặc `HT_*`), `TDL_PATIENT_MPS_NATIONAL_CODE`, `FEE_LOCK_TIME`, `OUT_TIME`, `FEE_LOCK_TIME >= OUT_TIME`, `END_DEPARTMENT_ID`, `TREATMENT_END_TYPE_ID`, `TREATMENT_RESULT_ID`, `ICD_CODE`, có `ListPatientTypeAlter`; diện điều trị cuối là `DT` thì phải có `CLINICAL_IN_TIME`. Nếu PTA cuối là BHYT: phải có `HEIN_LOCK_TIME`, `FEE_LOCK_TIME <= HEIN_LOCK_TIME`, ICD chính không `DO_NOT_USE_HEIN`, lao tiềm ẩn phải có `TUBERCULOSIS_ISSUED_ORG_NAME/DATE`.
Lọc `ListSereServ` (CXP:344): giữ dòng có `TDL_HEIN_SERVICE_TYPE_ID` (hoặc khám chính `SERVICE_TYPE_ID=1 && IS_MAIN_EXAM=1`), `AMOUNT>0`, `IS_EXPEND!=1`. Trẻ sơ sinh phải có `HEIN_CARD_NUMBER_TMP`.
`CheckDetailData` (CXP:700): dòng thiếu `ICD_CODE` (trừ dòng không thực hiện loại 5) → lỗi; y lệnh trước `IN_TIME` hoặc sau `FEE_LOCK_TIME`; dòng loại 9,10,11,12 thiếu `TUTORIAL` (trừ mã trong key `HeinServiceTypeCodeNoTutorial`) → "thiếu liều dùng"; dòng khác không có `DIPLOMA` của cả người thực hiện lẫn người chỉ định → "thiếu chứng chỉ hành nghề".

#### 1.2 Cấu trúc file bao (namespace `His.Bhyt.ExportXml.XML130.XML`)

```
<GIAMDINHHS xmlns:xsd=... xmlns:xsi=...>        ← XmlRoot("GIAMDINHHS"), KHÔNG phải GIAMDINHHOSO
  <THONGTINDONVI><MACSKCB>  = Treatment.HEIN_MEDI_ORG_CODE        (HSP:183)
  <THONGTINHOSO>
     <NGAYLAP>   = Now "yyyyMMdd"                                   (HSP:204)
     <SOLUONGHOSO> = 1 (Plus: số HOSO)                              (HSP:205, HSPP:155)
     <DANHSACHHOSO><HOSO>
        <FILEHOSO><LOAIHOSO>XMLn</LOAIHOSO><NOIDUNGFILE>base64</NOIDUNGFILE></FILEHOSO> ...
  <CHUKYDONVI></CHUKYDONVI>  = "" (luôn rỗng)                       (HSP:165)
```

- `NOIDUNGFILE` = Base64(UTF-8) của XML con hoàn chỉnh (có `<?xml ... encoding="utf-8"?>`, thụt dòng, không namespace). XML2/3/7 serialize ở lõi (`CreatedXmlFile`, HSP:807); các XML khác do module con trả chuỗi, lõi `Base64Encode`.
- Các FILEHOSO được sắp theo số trong `LOAIHOSO` (HSP:777).
- File bao ở `Processor` ghi bằng `Encoding.UTF8` (có BOM) (HSP:119); `ProcessorPlus` dùng UTF-8 không BOM (HSPP:81).

#### 1.3 Bảng nào vào hồ sơ, điều kiện gì (`CreateNoiDungFile` HSP:318–787)

Cổng lọc chung: `serverInfo.TypeXml` (chuỗi `"1,2,3,..."`, `HasTypeXml` HSP:67). Nếu `serverInfo == null` hoặc `TypeXml` rỗng → lấy mọi bảng.

| Bảng | Điều kiện thêm | Truyền IS_3176? |
|---|---|---|
| XML12 | Chỉ khi `IsRunOnlyXml12` (RunXml12/SyncDataXml12) — khi đó hồ sơ chỉ có XML12 và return sớm (HSP:360–381) | Không |
| XML2 | TypeXml có "2" và có ≥1 `CHI_TIET_THUOC` | Có (gán 2 lần, HSP:394–395) |
| XML3 | TypeXml có "3" và có ≥1 `CHI_TIET_DVKT` | Có |
| XML1 | TypeXml có "1" và chuỗi trả về khác rỗng | Có |
| XML4 | TypeXml có "4" và chuỗi khác rỗng | **Không** |
| XML5 | biểu thức HSP:571 (xem mục 4 — sai thứ tự ưu tiên) | Có |
| XML6, 8, 9, 10, 11 | TypeXml có số tương ứng và chuỗi khác rỗng | Có |
| XML7 | TypeXml có "7" và `GenerateXml7Data()` khác null — lõi KHÔNG kiểm tra ra viện, để module XML7 tự quyết. PTA truyền vào: ưu tiên mã BHYT (key `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT`), rồi LOG_TIME/ID mới nhất (HSP:612–619) | Có |
| XML13 | `TREATMENT_END_TYPE_ID == 2` (chuyển viện) + TypeXml "13" | Có |
| XML14 | `TREATMENT_END_TYPE_ID == 3` + TypeXml "14" | Có |
| XML15 | TypeXml "15" (lõi không kiểm có lao hay không) | Có |

Danh sách dịch vụ truyền cho XML1/4/5/13 là `list2` (HSP:325): lọc thêm `IS_NO_EXECUTE!=1 && PRICE>0` và (không phải BHYT, hoặc BHYT có `VIR_TOTAL_HEIN_PRICE>0`, hoặc =0 mà `OTHER_SOURCE_PRICE>0`), cộng thêm mọi dòng `IS_NO_EXECUTE==1`. XML2/XML3 nhận nguyên `data.ListSereServ` (đã lọc ở Check).

**Tổng tiền XML1** (HSP:397–537): cộng dồn từ dòng XML2 và XML3 đã sinh: `THANH_TIEN_BH→tongchiBH`, `T_BHTT→tongBHTT` (và `tongBHTTGDV` nếu `MA_PTTT=="1"`/`==1`), `T_BNCCT`, `T_BNTT`, `T_NGUONKHAC`, `THANH_TIEN_BV→tongchiBV`; `tienThuoc` = ΣTHANH_TIEN_BV của XML2; `tienVTYT` = ΣTHANH_TIEN_BV của XML3 có `MA_VAT_TU`. Sau đó `Math.Round(...,2, AwayFromZero)`.

#### 1.4 Ký số `delegateSignXml`

`Func<string,string,string> delegateSignXml` (InputADO). Lõi chỉ gọi cho **XML13, XML14, XML15**: `text = delegateSignXml(text, "DU_PHONG")` trước khi base64 (HSP:718–721, 742–745, 763–766; HSPP:784–786, 808–810, 829–831). Tham số thứ hai là tên thẻ `"DU_PHONG"`. XML1–12 và file bao không được ký; `CHUKYDONVI` luôn rỗng, `chukydonvi` gửi cổng cũng rỗng.

#### 1.5 API cổng (`SendXMLQD130` HSP:861, `RegisToken` HSP:1069)

- `ServerInfo`: `Username, Password, Address, Xml130Api, XmlGdykApi, TypeXml`. Thiếu Username/Password/Address → `ErrorCode "01"`.
- **Đăng nhập**: `POST {Address}api/token/take`, form-urlencoded `username`, `password = MD5(password)` (hex HOA). Phản hồi `LoginResultLDO {maKetQua, APIKey{access_token,id_token,token_type,username,expires_in}}` lưu vào biến static `plv`. Lỗi HTTP → `"03"`; ngoại lệ → `"02"`; `maKetQua=="401"` → `"07"`. Tắt kiểm tra chứng chỉ SSL toàn cục (`ServerCertificateValidationCallback => true`, HSP:1077).
- Gia hạn token: gọi lại khi `plv==null` hoặc `Now - 30s >= Convert.ToDateTime(expires_in)` (HSP:879, 887).
- **Gửi**: endpoint = `XmlGdykApi ?? "api/qd130/guiDuLieuGdykQd130"` (XML12) hoặc `Xml130Api ?? "api/qd130/guiHoSoXmlQD4750"`; nếu `Address` có path thì nối path vào trước (HSP:939–950). Header: `accessToken`, `tokenId` (= id_token), `passwordHash` (= MD5 password), Accept json. Body JSON `QD130InputADO`: `{username, loaiHoSo:"130", maTinh: Treatment.HEIN_PROVINCE_CODE, maCSKCB: Treatment.HEIN_MEDI_ORG_CODE, fileHSBase64: Base64(toàn bộ file GIAMDINHHS), chukydonvi:""}`.
- **Diễn giải → `SyncResultADO`**:
  - Lỗi `Check` → `ErrorCode ""`, `Message = lỗi`.
  - Stream null → `"04"`.
  - Không phản hồi → `"06"`; HTTP 401 → `plv=null`, `"07"` (không thử lại); HTTP lỗi khác → `"06"`.
  - Deserialize `QD130ResultADO {maKetQua, maGiaoDich, thoiGianTiepNhan, thongDiep}` null → `"99"`.
  - `maKetQua != "200"` → `"06"` (thongDiep chỉ ghi log).
  - `thongDiep` khác rỗng và ≠ `"Tiếp nhận thành công"` → `ErrorCode = thongDiep` (văn bản), `Message = "Lỗi do hệ thống BHYT trả lại"`.
  - Còn lại → `Success=true`, `CheckCode = maGiaoDich`.
  - Ngoại lệ → `"99"`.

#### 1.6 IS_3176 ở lõi

Lõi **không** làm gì khác cho 3176 ngoài chuyền cờ xuống XML1,2,3,5,6,7,8,9,10,11,13,14,15 (không xuống XML4, XML12). Cùng `Check`, cùng cấu trúc file bao, cùng endpoint `guiHoSoXmlQD4750`, `loaiHoSo` luôn `"130"`.

#### 1.7 `HoSoProcessorPlus` khác lõi đơn

- `CreateListHoSo` (HSPP:207) tạo `InputADO` mới cho từng hồ sơ nhưng **không gán `serverInfo`** (HSPP:341–350) → bộ lọc TypeXml không áp dụng, mọi bảng đều sinh. Không gán `ListDepartment` cho XML1 (HSPP:586–605).
- Bộ lọc dịch vụ riêng (HSPP:241–271) giống Collinear: cần `TDL_HEIN_SERVICE_BHYT_NAME`, `VIR_PRICE>0`; **không** lọc `IS_EXPEND`, `IS_NO_EXECUTE`. XML1/4/5 nhận nguyên danh sách này (không có `list2`).
- XML5 không có điều kiện khám chính/tờ điều trị; XML7 sắp PTA chỉ theo LOG_TIME/ID (không ưu tiên BHYT) (HSPP:683).

---

### 2. Bảng XML1 (`XML1Data`, root `<TONG_HOP>`) — `Xml1Processor.GenerateXml1Data` X1P:15–730

Quy ước: thời gian HIS là số `yyyyMMddHHmmss`; "[0..12]" = `ToString().Substring(0,12)` → `yyyyMMddHHmm`; "[0..8]" → `yyyyMMdd`. Toán tử `??` chỉ bắt `null`, không bắt chuỗi rỗng. **Không trường nào bị cắt độ dài** (`SubString()` và `MAX_LENGTH=1024` có nhưng không được gọi). CDATA tạo bằng `ConvertStringToXmlDocument` = lọc ký tự XML không hợp lệ + `CreateCDataSection`.

PTA chính (`PatientTypeAlter`, X1P:30): ưu tiên `PATIENT_TYPE_CODE == key HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT`, rồi LOG_TIME desc, ID desc. Danh sách thẻ (`list2`, X1P:111–118): mọi PTA sắp theo key `SORT_MA_THE_BHYT` (=1: LOG_TIME desc; khác: HEIN_CARD_FROM_TIME, TO_TIME), nhóm theo (số thẻ, nơi ĐKBĐ, từ, đến) lấy bản đầu.

| # | Trường | Nguồn HIS (rút gọn) | Điều kiện / key HIS_CONFIG | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE ?? ""` | | |
| 2 | STT | hằng `1` | | |
| 3 | MA_BN | `TDL_PATIENT_CODE ?? ""` | | |
| 4 | HO_TEN | CDATA `TDL_PATIENT_NAME` | | |
| 5 | SO_CCCD | `TDL_PATIENT_CCCD_NUMBER` hoặc `""` | | `CCCD ?? CMND ?? PASSPORT ?? ""` (X1P:688) — CCCD là `""` thì không rơi xuống CMND |
| 6 | NGAY_SINH | `TDL_PATIENT_DOB`[0..12] (yyyyMMddHHmm); nếu `IS_HAS_NOT_DAY_DOB==1` → `yyyy`+`"00000000"` | | |
| 7 | GIOI_TINH | `GENDER_ID` 1→"2", 2→"1", khác→"3" | | |
| 8 | NHOM_MAU | `TDL_PATIENT_BLOOD_ABO_CODE ?? ""` | | |
| 9 | MA_QUOCTICH | `TDL_PATIENT_MPS_NATIONAL_CODE ?? ""` | | |
| 10 | MA_DANTOC | `ETHNIC_CODE ?? ""` | | |
| 11 | MA_NGHE_NGHIEP | `TDL_PATIENT_CAREER_CODE ?? ""` | | Cắt 2 ký tự đầu nếu dài >2 (X1P:689) |
| 12 | DIA_CHI | CDATA `TDL_PATIENT_ADDRESS` | | |
| 13 | MATINH_CU_TRU | `TDL_PATIENT_PROVINCE_CODE` nếu có cả tỉnh+xã `TDL_PATIENT_*`, ngược lại `HT_PROVINCE_CODE` | | |
| 14 | MAHUYEN_CU_TRU | `TDL_PATIENT_DISTRICT_CODE` / `HT_DISTRICT_CODE` (cùng điều kiện) | | |
| 15 | MAXA_CU_TRU | `TDL_PATIENT_COMMUNE_CODE` / `HT_COMMUNE_CODE` | | |
| 16 | DIEN_THOAI | `TDL_PATIENT_MOBILE ?? TDL_PATIENT_PHONE ?? ""` | | |
| 17 | MA_THE_BHYT | `HEIN_CARD_NUMBER` của các thẻ trong `list2`, nối `;`; rỗng → PTA chính | `SORT_MA_THE_BHYT` (thứ tự) | |
| 18 | MA_DKBD | `HEIN_MEDI_ORG_CODE` từng thẻ, nối `;` (không distinct); rỗng → PTA chính | `SORT_MA_THE_BHYT` | |
| 19 | GT_THE_TU | `HEIN_CARD_FROM_TIME`[0..8] từng thẻ, nối `;`; fallback PTA chính | `SORT_MA_THE_BHYT` | |
| 20 | GT_THE_DEN | `HEIN_CARD_TO_TIME`[0..8], nối `;`; fallback | `SORT_MA_THE_BHYT` | |
| 21 | NGAY_MIEN_CCT | `FREE_CO_PAID_TIME`[0..8] — chỉ khi đúng 1 giá trị; không thì lấy của PTA chính | | |
| 22 | LY_DO_VV | CDATA `HOSPITALIZATION_REASON` | | |
| 23 | LY_DO_VNT | CDATA `HOSPITALIZE_REASON_NAME` | | |
| 24 | MA_LY_DO_VNT | `HOSPITALIZE_REASON_CODE ?? ""` | | |
| 25 | CHAN_DOAN_VAO | CDATA `PROVISIONAL_DIAGNOSIS ?? ICD_NAME` | | Dùng `IsNullOrEmpty`: chuỗi rỗng cũng rơi về `ICD_NAME` (X1P:690–695) |
| 26 | CHAN_DOAN_RV | CDATA `ICD_NAME + ";" + ICD_TEXT` (luôn có `;`) | | |
| 27 | MA_BENH_CHINH | `ICD_CODE ?? ""` | | |
| 28 | MA_BENH_KT | `ICD_SUB_CODE` tách `;`, lấy tối đa 12 mã đầu, nối `;` (không distinct, không bỏ mã chính) | | |
| 29 | MA_BENH_YHCT | Tách `;` của `TRADITIONAL_ICD_CODE` + `TRADITIONAL_ICD_SUB_CODE`, bỏ mã = `ICD_CODE`, distinct, không giới hạn | | `TRADITIONAL_ICD_CODE` thêm nguyên chuỗi (không tách) + SUB tách; distinct; `Take(13)`; KHÔNG bỏ mã = `ICD_CODE` (X1P:696–705) |
| 30 | MA_PTTT_QT | `ICD_CM_CODE` + `ICD_CM_SUB_CODE` của mọi `V_HIS_SERE_SERV_PTTT`, tách `;`, distinct, nối `;`, không giới hạn | | |
| 31 | MA_DOITUONG_KCB | `HEIN_PATIENT_TYPE_CODE ?? <mã tự suy>`; mã tự suy (X1P:256–353), mặc định "9": DT+CC→2; DT: sơ sinh 1.7, GT 1.3, HK 1.5, lao/HIV/COVID 1.1, đúng nơi ĐKBĐ hoặc `ACCEPT_HEIN_MEDI_ORG_CODE`/`SYS_MEDI_ORG_CODE` 1.1, giấy khai sinh/TE/cùng tỉnh tuyến 3-4/TT46/TH 1.2, giấy nghỉ/đi làm 1.4; TT: HN/K1-3 3.6, tuyến 1 NT/ban ngày 3.1, tuyến 2 NT 1.15, tuyến 3 khác tỉnh 3.3, tuyến1 khám 3.1, tuyến2 khám 3.3, còn lại 3.3 (tuyến 2→1.15); không DT/TT và `TREATMENT_TYPE_ID==6` → 7.1 | Dùng `ListHeinMediOrg` (hoặc danh mục cứng `ConfigHeinMediOrg`) lấy `LEVEL_CODE` nơi ĐKBĐ | |
| 32 | MA_NOI_DI | `TRANSFER_IN_MEDI_ORG_CODE ?? ""` | | |
| 33 | MA_NOI_DEN | `MEDI_ORG_CODE ?? ""` | | |
| 34 | MA_TAI_NAN | `ACCIDENT_HURT_TYPE_BHYT_CODE ?? "0"` | | |
| 35 | NGAY_VAO | `IN_TIME`[0..12] | | |
| 36 | NGAY_VAO_NOI_TRU | `CLINICAL_IN_TIME`[0..12] hoặc "" | `NGAY_VAO_NOI_TRU_OPTION=1` và MA_LOAI_KCB ∈ {02,05,08} → "" | |
| 37 | NGAY_RA | `OUT_TIME`[0..12] hoặc "" | | |
| 38 | GIAY_CHUYEN_TUYEN | `TRANSFER_IN_CODE ?? ""` | `GIAY_CHUYEN_TUYEN_XML1_FORMAT=1` → `OUT_CODE ?? TRANSFER_IN_CODE` | |
| 39 | SO_NGAY_DTRI | MA_LOAI_KCB 01/07/09/100 → 0; 02/03/04/05/08 → (ngày `NGAY_RA` − ngày `NGAY_VAO`).TotalDays + 1 (chỉ phần ngày); còn lại 0 | `SO_NGAY_DTRI_OPTION=1` → `(long)TREATMENT_DAY_COUNT.Value` | |
| 40 | PP_DIEU_TRI | `TREATMENT_METHOD ?? ""` (không CDATA) | | |
| 41 | KET_QUA_DTRI | ký tự **cuối** của `TREATMENT_RESULT_CODE` | | |
| 42 | MA_LOAI_RV | mặc định "1"; `END_TYPE` 7→"3", 8→"4", 2 + `TRAN_PATI_REASON_ID` 1→"2", 2→"5" | | |
| 43 | GHI_CHU | CDATA `ADVISE` | | |
| 44 | NGAY_TTOAN | `FEE_LOCK_TIME`[0..12] hoặc "" | | |
| 45 | T_THUOC | `Round(tienThuoc,2)` "G27" (ΣTHANH_TIEN_BV XML2) | phụ thuộc TypeXml có "2" | |
| 46 | T_VTYT | `Round(tienVTYT,2)` (ΣTHANH_TIEN_BV XML3 có MA_VAT_TU) | phụ thuộc TypeXml có "3" | |
| 47 | T_TONGCHI_BV | `tongchiBV` "G27" (đã round 2 ở lõi) | TypeXml 2/3 | |
| 48 | T_TONGCHI_BH | `tongchiBH` | TypeXml 2/3 | |
| 49 | T_BNTT | `tongBNTT` | | |
| 50 | T_BNCCT | `tongBNCCT` | | |
| 51 | T_BHTT | `tongBHTT` | | |
| 52 | T_NGUONKHAC | `tongNguonKhac` | | |
| 53 | T_BHTT_GDV | MA_THE_BHYT bắt đầu CA/QN/CY → "0"; khác → `tongBHTTGDV` (ΣT_BHTT dòng MA_PTTT=1) | | |
| 54 | NAM_QT | mặc định `HEIN_LOCK_TIME`[0..4] (không có → OUT_TIME) | `THOI_GIAN_QT_OPTION`: 1→OUT_TIME, 2→năm hiện tại | |
| 55 | THANG_QT | như trên, `[4..6]` | `THOI_GIAN_QT_OPTION` | |
| 56 | MA_LOAI_KCB | `TDL_TREATMENT_TYPE_ID`: 1→01; 2→02, mãn tính (`IS_CHRONIC=1`): có dòng y lệnh loại ∉{1,6,14,15}→08, không →05; 3→03 (hoặc 09 nếu OUT−CLINICAL_IN <4 giờ); 4→04; 5→06; 6→07; khác→10 | `KEY_KSK` (`MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.KSK`): 01 + có PTA KSK trùng `TDL_PATIENT_TYPE_ID` → "100" | |
| 57 | MA_KHOA | `END_ROOM_BHYT_CODE ?? EXIT_BHYT_CODE ?? END_DEPARTMENT_BHYT_CODE ?? ""` | `MA_KHOA_OPTION=1` và có `ListDepartment` → `BHYT_CODE` của mọi khoa trong `DEPARTMENT_IDS`+`CO_DEPARTMENT_IDS`, nối `;` | |
| 58 | MA_CSKCB | `HEIN_MEDI_ORG_CODE ?? ""` | | |
| 59 | MA_KHUVUC | `LIVE_AREA_CODE` — chỉ khi đúng 1 giá trị; fallback PTA chính | | |
| 60 | CAN_NANG | DHST mới nhất (EXECUTE_TIME desc) có `WEIGHT>0`, `Round(,2)` "G27" | | |
| 61 | CAN_NANG_CON | `WEIGHT` từng `V_HIS_BABY` (bỏ bé không có cân), Round 2, nối `;` | | |
| 62 | NAM_NAM_LIEN_TUC | `JOIN_5_YEAR_TIME`[0..8] — chỉ khi đúng 1 giá trị; fallback PTA chính | | |
| 63 | NGAY_TAI_KHAM | `APPOINTMENT_TIME`[0..8] (yyyyMMdd) | | |
| 64 | MA_HSBA | `TREATMENT_CODE` (= MA_LK) | | |
| 65 | MA_TTDV | `REPRESENTATIVE_HEIN_CODE ?? ""` | `MA_TTDV_FROM_PROFILE_XML` chứa "1" → `SOCIAL_INSURANCE_NUMBER` của nhân viên `HOSP_SUBS_DIRECTOR_LOGINNAME ?? HOSPITAL_DIRECTOR_LOGINNAME` (không tìm thấy → ""; không có loginname → `REPRESENTATIVE_HEIN_CODE`) | Ghi đè vô điều kiện bằng `DIPLOMA` (CCHN) của `HOSP_SUBS_DIRECTOR ?? HOSPITAL_DIRECTOR` (X1P:707) — bỏ qua key |
| 66 | DU_PHONG | `""` | | `HEIGHT` của DHST mới nhất có chiều cao (EXECUTE_TIME, ID desc), `ToString(Invariant)`, không làm tròn (X1P:708–718) |

Khi không có PTA nào: các trường thẻ = "" và MA_DOITUONG_KCB = `HEIN_PATIENT_TYPE_CODE ?? "9"`.

`HoSoProcessor(string)` + `ConvertValue` (XML1 `HoSoProcessor.cs`) chỉ dùng khi ĐỌC lại XML1 để hiển thị (đổi ngày sang dạng dd/MM/yyyy...), không ảnh hưởng xuất.

`ConfigHeinMediOrg.cs`: danh mục cứng **15.634** cơ sở KCB (`MEDI_ORG_CODE`, `MEDI_ORG_NAME`, `LEVEL_CODE`), ví dụ `01001 Bệnh viện Hữu Nghị LEVEL 1`. Chỉ dùng làm dự phòng khi `ListHeinMediOrg` rỗng (`GlobalConfigStore.HisHeinMediOrg`), để lấy tuyến nơi ĐKBĐ khi suy MA_DOITUONG_KCB.

---

### 3. Key HIS_CONFIG mà XML1 dùng

`GetConfigData`: lấy `VALUE`, rỗng thì `DEFAULT_VALUE`, `Trim()`.

| Key | Tác dụng |
|---|---|
| `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | Mã đối tượng BHYT để chọn PTA chính (ưu tiên BHYT). Lưu ý: khác key `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` lõi dùng để lọc dịch vụ. |
| `HIS.QD_130_BYT.SORT_MA_THE_BHYT` | `1`: thứ tự thẻ theo LOG_TIME giảm dần; khác: theo hạn thẻ từ/đến tăng dần. Ảnh hưởng MA_THE_BHYT, MA_DKBD, GT_THE_TU/DEN. |
| `HIS.QD_130_BYT.THOI_GIAN_QT_OPTION` | NAM_QT/THANG_QT: `1` = OUT_TIME; `2` = thời điểm xuất; khác = HEIN_LOCK_TIME (rơi về OUT_TIME). |
| `HIS.QD_130_BYT.XML1.MA_KHOA_OPTION` | `1` = liệt kê mã BHYT mọi khoa điều trị/phối hợp; khác = khoa/phòng kết thúc. |
| `HIS.QD_130_BYT.XML1.SO_NGAY_DTRI_OPTION` | `1` = dùng `TREATMENT_DAY_COUNT`; khác = ngày ra − ngày vào + 1. |
| `HIS.QD_130_BYT.XML1.NGAY_VAO_NOI_TRU_OPTION` | `1` = để trống NGAY_VAO_NOI_TRU khi MA_LOAI_KCB 02/05/08. |
| `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.KSK` | Nhận diện khám sức khoẻ → MA_LOAI_KCB "100". |
| `HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_XML1_FORMAT` (hằng viết thẳng, X1P:476) | `1` = GIAY_CHUYEN_TUYEN lấy `OUT_CODE` trước. |
| `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` (hằng viết thẳng, X1P:661) | Danh sách tách `, ; space`; chứa `1` → MA_TTDV lấy mã BHXH của giám đốc/phó giám đốc (bị 3176 ghi đè). |

Key lõi liên quan: `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` (lọc dịch vụ, Check), `HIS.Desktop.Plugins.ExportXml.HeinServiceTypeCodeNoTutorial` (mã thuốc được miễn kiểm liều dùng, phân tách `|`).

---

### 4. Điểm đáng ngờ / lỗi tiềm ẩn (chỉ những gì thấy rõ trong mã)

#### Lõi
1. **Sai thứ tự ưu tiên ở điều kiện XML5** (HSP:571): `((cóKhámChính || ListTracking!=null) && serverInfo == null) || string.IsNullOrEmpty(data.serverInfo.TypeXml) || ...`. Khi `serverInfo == null` (xuất file, không gửi cổng) mà hồ sơ không có khám chính và `ListTracking == null` → truy cập `data.serverInfo.TypeXml` ném NullReferenceException → `catch` ở HSP:782 trả `resultADO = null` → cả hồ sơ thất bại ("File ho so khong co du lieu"). Khi `serverInfo != null`, điều kiện khám chính/tờ điều trị bị bỏ qua hoàn toàn.
2. **Gia hạn token lệch dấu** (HSP:887): `DateTime.Now.AddSeconds(-30) >= expires` chỉ làm mới token SAU khi đã hết hạn 30 giây; trong 30 giây đó vẫn dùng token hết hạn. Nếu `expires_in` không parse được thành DateTime thì `MinValue` → đăng nhập lại mỗi lần gửi.
3. HTTP 401 khi gửi chỉ đặt `plv=null` và trả `"07"`, **không thử lại** (HSP:1001–1007), dù log ghi "Reset token và thử lại".
4. `maKetQua != "200"` → Message cố định "Lỗi gọi api gửi hồ sơ", `thongDiep` của cổng bị bỏ (chỉ log, HSP:1027–1031). Khi bị từ chối có `thongDiep`, nội dung lỗi lại nằm trong `ErrorCode` chứ không phải `Message` (HSP:1035).
5. `Xml130Api ?? mặc định` (HSP:939): chuỗi rỗng `""` không được thay mặc định.
6. Tắt kiểm chứng chỉ SSL toàn cục cho cả tiến trình (HSP:1077).
7. Trạng thái tĩnh dùng chung: `plv`, `IsRunOnlyXml12` là `static` (HSP:55–57). `Processor()` ghi `IsRunOnlyXml12` rồi `CreateNoiDungFile` đọc lại — gọi song song XML12 và hồ sơ thường có thể lẫn nhau.
8. **XML1 lỗi thì hồ sơ vẫn đi**: nếu `RunXml1String()` trả null/rỗng (vd ngoại lệ trong Xml1Processor) thì lõi chỉ bỏ qua FILEHOSO XML1 (HSP:542–549), không báo lỗi. Tương tự cho mọi bảng khác.
9. Tổng tiền XML1 lấy từ XML2/XML3 đã sinh: nếu `TypeXml` không chứa "2" hoặc "3" thì T_THUOC/T_VTYT/T_TONGCHI_*/T_BHTT... của XML1 thiếu phần tương ứng (có thể bằng 0).
10. BOM: `CreatedXmlFile` (HSP:813–837) và `XmlProcessorBase.CreatedXmlString` dùng `Encoding.UTF8` cho XmlWriter (ghi BOM) rồi `GetString` buffer → chuỗi XML con bắt đầu bằng U+FEFF, sau đó base64 lại → NOIDUNGFILE chứa BOM. File bao ở `Processor` cũng có BOM; `ProcessorPlus` thì không.
11. Root file bao là `GIAMDINHHS` (XmlRoot trong `GiamDinhHoSo.cs`), không phải `GIAMDINHHOSO`.
12. `delegateSignXml` chỉ áp cho XML13/14/15, tham số thẻ `"DU_PHONG"`; `CHUKYDONVI`/`chukydonvi` luôn rỗng.
13. `HoSoProcessorPlus`:
    - `PathSaveXml` truyền vào `CreatedXmlFileEncodingPlus(..., saveFile:true, path)` nhưng hàm **không ghi file** (HSPP:76–112) — tham số `saveFile/path` bị bỏ qua.
    - Không gán `serverInfo` cho từng hồ sơ → bỏ qua TypeXml (HSPP:341–350).
    - Không gán `ListDepartment` cho XML1 → `MA_KHOA_OPTION=1` không có tác dụng ở chế độ nhiều hồ sơ.
    - Bộ lọc dịch vụ không loại `IS_EXPEND==1` (hao phí) và `IS_NO_EXECUTE` (HSPP:241–271), khác `Run`.
    - Biến `text2` luôn rỗng (HSPP:352); hồ sơ lỗi chỉ ghi log, không báo người dùng.
14. `CreateNoiDungFileXML1_2_3` (HSP:1182–1198): nhánh XML12 dựng file rồi `return null`; biến `list` trong hàm không được dùng.
15. Gán `inputADO2.IS_3176` hai lần liên tiếp (HSP:394–395, 1210–1211) — vô hại.

#### XML1
16. **MA_TTDV gán rồi bị ghi đè khi IS_3176** (X1P:661–684 rồi X1P:707): key `MA_TTDV_FROM_PROFILE_XML` và `REPRESENTATIVE_HEIN_CODE` bị bỏ, thay bằng `DIPLOMA` (số CCHN) — trong khi nhánh 130 lấy `SOCIAL_INSURANCE_NUMBER`. Hai chế độ dùng hai loại mã khác nhau cho cùng trường.
17. **Không cắt độ dài trường nào**: `SubString()` (theo byte UTF-8) và `GlobalConfigStore.MAX_LENGTH=1024` tồn tại nhưng không được gọi trong Xml1Processor. Các trường văn bản dài (CHAN_DOAN_RV, CHAN_DOAN_VAO, DIA_CHI, PP_DIEU_TRI, GHI_CHU, MA_PTTT_QT, MA_KHOA...) xuất nguyên văn.
18. CHAN_DOAN_RV luôn ghép `ICD_NAME + ";" + ICD_TEXT` → khi không có ICD_TEXT ra `"Tên bệnh;"` (dấu `;` thừa); khi ICD_NAME null ra `";..."` (X1P:407).
19. `??` không bắt chuỗi rỗng: SO_CCCD (3176), DIEN_THOAI, MA_KHOA, MA_DOITUONG_KCB (`HEIN_PATIENT_TYPE_CODE=""` → xuất rỗng thay vì mã tự suy), GIAY_CHUYEN_TUYEN.
20. MA_DOITUONG_KCB: nếu `HEIN_PATIENT_TYPE_CODE` khác null thì toàn bộ logic suy mã (X1P:256–353) bị bỏ. Nhánh `RIGHT_ROUTE_CODE=="DT"` không khớp điều kiện con nào → giữ "9".
21. Ngoại lệ làm mất cả XML1 (rồi lõi bỏ qua im lặng, xem mục 8):
    - `data.TotalIcdData.FirstOrDefault(...)` không kiểm null (X1P:266, 271).
    - `PatientTypeAlter.HEIN_MEDI_ORG_CODE.Substring(0,2)` khi mã dài 1 ký tự (X1P:241).
    - `PatientTypeAlter.HEIN_CARD_NUMBER.StartsWith("HN")` khi MA_THE_BHYT lấy từ PTA khác còn PTA chính không có số thẻ (X1P:312).
    - `TREATMENT_DAY_COUNT.Value` khi `SO_NGAY_DTRI_OPTION=1` mà giá trị null (X1P:642); ép `(long)` cắt phần thập phân.
22. Các danh sách thẻ bị **lệch hàng**: MA_THE_BHYT, MA_DKBD, GT_THE_TU, GT_THE_DEN được dựng riêng, mỗi danh sách bỏ phần tử rỗng độc lập (X1P:128–143) → nếu một thẻ thiếu nơi ĐKBĐ hoặc hạn thẻ thì vị trí thứ i của các trường không còn tương ứng. MA_DKBD không distinct nên có thể lặp mã.
23. NGAY_MIEN_CCT, MA_KHUVUC, NAM_NAM_LIEN_TUC chỉ lấy khi đúng 1 giá trị; nhiều thẻ có giá trị khác nhau → rơi về giá trị của PTA chính, không nối `;` như các trường thẻ khác.
24. MA_LOAI_KCB:
    - Ngoại trú mãn tính (`TREATMENT_TYPE_ID=2`, `IS_CHRONIC=1`) mà `vSereServ` rỗng → giữ mặc định "10" (X1P:591–598).
    - Giá trị `"100"` cho KSK (X1P:605) dài 3 ký tự — cần đối chiếu bộ mã MA_LOAI_KCB QĐ130/3176 (mã khác đều 2 ký tự).
25. MA_LOAI_RV: chuyển viện (`END_TYPE=2`) với `TRAN_PATI_REASON_ID` khác 1/2 hoặc null → giữ "1" (ra viện) (X1P:496–506).
26. MA_BENH_YHCT bản 3176: `TRADITIONAL_ICD_CODE` được thêm nguyên chuỗi (không tách `;`) và không loại mã trùng `ICD_CODE` — khác bản 130 (X1P:697–705 so với X1P:432–447). `Take(13)` so với MA_BENH_KT `12`.
27. MA_BENH_KT không loại mã trùng mã bệnh chính, không distinct; MA_PTTT_QT không giới hạn số mã.
28. SO_NGAY_DTRI (cách mặc định) tính từ `NGAY_VAO` (IN_TIME), không từ `NGAY_VAO_NOI_TRU`, và luôn +1 kể cả với MA_LOAI_KCB 03/04 (X1P:647–652).
29. DU_PHONG bản 3176 = chiều cao `HEIGHT.ToString()` không làm tròn (có thể ra nhiều chữ số thập phân), khác CAN_NANG có Round 2.
30. `KET_QUA_DTRI` = ký tự cuối của `TREATMENT_RESULT_CODE` (X1P:486) — chỉ đúng nếu mã HIS kết thúc bằng mã kết quả 1 chữ số.

---

## Bản đồ XML2 (thuốc) & XML3 (DVKT + VTYT) — HIS hisnguonmo, DLL QĐ130/QĐ3176

Nguồn: mã C# dịch ngược ILSpy trong `scratchpad/xml130/`
- XML2: `His.Bhyt.ExportXml.XML130.XML2\...\Xml2Processor.cs` (964 dòng), `XmlProcessorBase.cs`, `HisConfigKey.cs`, `InputADO.cs`, `Xml2ADO.cs`, `XML2DetailData.cs`, `HoSoProcessor.cs`
- XML3: `His.Bhyt.ExportXml.XML130.XML3\...\Xml3Processor.cs` (1331 dòng), `HisConfigKey.cs`, `InputXml3ADO.cs`, `XML3ADO.cs`, `XML3DetailData.cs`, `XML3Data.cs`
- Bên gọi (chỉ tham chiếu): `His.Bhyt.ExportXml.XML130\HoSoProcessor.cs` dòng 384–470 và `HoSoProcessorPlus.cs` dòng 214–270.

Quy ước trong tài liệu: `ss` = một dòng `V_HIS_SERE_SERV_2`; `L:123` = số dòng trong file processor tương ứng; `round(x,n)` = `Math.Round(x, n, MidpointRounding.AwayFromZero)` trừ khi ghi "round banker" (là `Math.Round(x,0)` mặc định ToEven). Mọi số tiền xuất ra bằng `ToString("G27", InvariantCulture)`: dấu chấm thập phân, không phân tách nghìn, **không ép số chữ số thập phân** (giữ nguyên scale của decimal).

Định dạng ngày: mọi trường ngày đều là 12 ký tự `yyyyMMddHHmm`, lấy bằng cách cắt chuỗi time-number HIS (`yyyyMMddHHmmss`) 12 ký tự đầu, hoặc `DateTime.ToString("yyyyMMddHHmm")`.

Ghi chú về `TDL_HEIN_SERVICE_TYPE_ID` (loại dịch vụ BHYT): mã chỉ dùng số. Vai trò dưới đây là suy từ cách mã xử lý, không có bảng hằng số trong DLL:
- 9, 10, 11, 12: thuốc (XML2, `MA_THUOC` = mã hoạt chất)
- 6, 21: vào XML2 nhưng `MA_THUOC` lấy mã dịch vụ BHYT (nhóm máu/chế phẩm máu)
- 14, 15, 16, 17: VTYT (XML3, nhánh vật tư)
- 3, 4, 19, 20: giường (có `MA_GIUONG`, logic NGAY_KQ giường)
- 5: khám (logic MA_BAC_SI khám, NGAY_YL = START_TIME)
- 7, 22: PTTT (có `PP_VO_CAM`)
- 1, 2, 8, 13, 18: DVKT khác (XN/CĐHA/TDCN/…), thuộc nhóm "được xuất cả khi không thực hiện"

---

### 0. Đầu vào và bên gọi

- XML2 nhận `InputADO`: `vTreatment12`, `vSereServ2`, `HisExpMedimateUsed`, `vHisService` (không dùng), `vHisSereServPttt`, `HisEmployee`, `HisConfig`, `HisPatientTypes`, `IS_3176`.
- XML3 nhận `InputXml3ADO`: `ConfigData`, `Treatment`, `ListSereServ`, `SereServPttts`, `Employees`, `BedLogs`, `MaterialTypes`, `Services`, `Icds` (không dùng), `EkipUsers`, `PatientTypes`, `vHisSereServTeins`, `IS_3176`.
- Cả hai đều nhận **cùng một** `data.ListSereServ`. Ở `HoSoProcessorPlus.cs:241` danh sách này đã được lọc trước: cần (`TDL_HEIN_SERVICE_TYPE_ID` có giá trị, hoặc khám chính `TDL_SERVICE_TYPE_ID==1 && TDL_IS_MAIN_EXAM==1`) và `TDL_HEIN_SERVICE_BHYT_NAME` khác rỗng và `VIR_PRICE>0` và `AMOUNT>0`; dòng BHYT còn cần `VIR_TOTAL_HEIN_PRICE>0` hoặc (`=0` và `OTHER_SOURCE_PRICE>0`). Bên gọi này đổi mã đối tượng BHYT sang ID đúng cách bằng `GetPatientTypeId(...)`. Đường `HoSoProcessor.cs:390/456` truyền `data.ListSereServ` thẳng vào (nếu có lọc thì nằm ở bên gọi phía trên).
- Bên gọi cộng dồn tổng tiền từ XML2 cho XML1: `THANH_TIEN_BH→tongchiBH`, `T_BHTT→tongBHTT` (và `tongBHTTGDV` nếu `MA_PTTT=="1"`), `T_BNCCT`, `T_BNTT`, `T_NGUONKHAC`, `THANH_TIEN_BV→tienThuoc+tongchiBV`.

---

### 1. XML2 — Chi tiết thuốc

#### 1.1 Quy tắc chọn dòng (L:350–400)

Một `ss` được chọn khi **đồng thời**:
1. `TDL_HEIN_SERVICE_TYPE_ID ∈ {9,10,11,12}` (thuốc) hoặc `∈ {6,21}`;
2. `IS_NO_EXECUTE != 1`;
3. `PRICE > 0`;
4. nếu `PATIENT_TYPE_ID == patientTypeId` (coi là BHYT): cần `VIR_TOTAL_HEIN_PRICE > 0`, hoặc (`VIR_TOTAL_HEIN_PRICE == 0` và `OTHER_SOURCE_PRICE > 0`). `VIR_TOTAL_HEIN_PRICE` null thì **loại**. Nếu `PATIENT_TYPE_ID != patientTypeId` (đối tượng khác) thì **luôn lấy**.

`patientTypeId = long.TryParse(<giá trị cấu hình MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT>)`, tức là lấy **mã** đối tượng đem dùng như **ID** (xem mục Điểm đáng ngờ).

- **Bỏ trùng**: `group by ss.ID → First()`, rồi `orderby INTRUCTION_TIME`.
- **Không gộp** các dòng cùng thuốc: mỗi `ss` ra ít nhất một dòng.
- **Tách dòng** (L:426–438): khi `HisConfigs["HIS.QD_130_BYT.XML2.NGAY_TH_YL_CUA_THUOC_OPTION"]=="1"` và `ss.MEDICINE_ID` có giá trị, mỗi bản ghi `HIS_EXP_MEDIMATE_USED` khớp (ưu tiên khớp theo EXP_MEST_MEDICINE_ID → EXP_MEST_MATERIAL_ID → SERE_SERV_ID → MEDICINE_ID → MATERIAL_ID, lấy qua reflection) và có `USE_TIME>0`, `AMOUNT>0` sinh **một dòng XML2 riêng**, sắp theo `MEDICATION_SESSION, UseTime`. Số lượng của dòng = `used.AMOUNT`. Không có bản ghi khớp thì 1 dòng với `ss.AMOUNT`.
- **Bỏ dòng thêm** (L:738–744): nếu `ss.PARENT_ID` trỏ tới một ss cha có `IS_NO_EXECUTE==1` và `HisConfigs["HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN"]=="1"` thì bỏ (`continue`, STT không tăng).
- `STT` tăng liên tục theo dòng xuất ra (kể cả dòng tách).

#### 1.2 Bảng trường XML2DetailData (đúng thứ tự Order 1–39)

| # | Trường | Nguồn HIS / biểu thức | Điều kiện / key cấu hình | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `vTreatment12.TREATMENT_CODE` | | |
| 2 | STT | bộ đếm 1..n | | |
| 3 | MA_THUOC | loại 6/21 hoặc `MEDICINE_ID` null → `TDL_HEIN_SERVICE_BHYT_CODE`; còn lại → `ACTIVE_INGR_BHYT_CODE` | | |
| 4 | MA_PP_CHEBIEN | `PREPROCESSING_CODE;PROCESSING_CODE` (bỏ phần rỗng) | | |
| 5 | MA_CSKCB_THUOC | `TRANSFER_MEDI_ORG_CODE` | | |
| 6 | MA_NHOM | `HST_BHYT_CODE` (không xét `OLD_HST_BHYT_CODE` như XML3) | | |
| 7 | TEN_THUOC (CDATA) | `TDL_HEIN_SERVICE_BHYT_NAME` | lọc ký tự XML không hợp lệ | |
| 8 | DON_VI_TINH | `SERVICE_UNIT_NAME`; nếu `CONVERT_RATIO` khác 0 và `USE_ORIGINAL_UNIT_FOR_PRES != 1` → `CONVERT_UNIT_NAME` | | |
| 9 | HAM_LUONG (CDATA) | `CONCENTRA` | | |
| 10 | DUONG_DUNG (CDATA) | `MEDICINE_USE_FORM_CODE` | | |
| 11 | DANG_BAO_CHE | `DOSAGE_FORM` | | |
| 12 | LIEU_DUNG (CDATA) | xem 1.3 | `HIS.Desktop.Plugins.AssignPrescription.TutorialFormat` | |
| 13 | CACH_DUNG (CDATA) | `MEDICINE_HTU_TEXT`, rỗng thì `ADVISE` | | |
| 14 | SO_DANG_KY | `MEDICINE_REGISTER_NUMBER` | | |
| 15 | TT_THAU | `MEDICINE_TT_THAU` nếu **khác null** (kể cả chuỗi rỗng); ngược lại ghép `MEDICINE_BID_EXTRA_CODE;BID_PACKAGE_CODE;BID_GROUP_CODE;BID_YEAR` (bỏ phần rỗng) | | |
| 16 | PHAM_VI | `TYLE>0` → "1", trừ khi thẻ bắt đầu `CA`/`CY`/`QN` và có `MEDICINE_ID` và loại 9 → "3"; `TYLE==0` → "2" | | |
| 17 | TYLE_TT_BH | chỉ khi `patientType.PATIENT_TYPE_CODE == mã BHYT` và `ORIGINAL_PRICE>0`: có `HEIN_LIMIT_PRICE` → `HEIN_LIMIT_PRICE/(ORIGINAL_PRICE*(1+VAT_RATIO))*100`; không có → `PRICE/ORIGINAL_PRICE*100`; **round banker 0 chữ số**. Ngược lại 0 | | |
| 18 | SO_LUONG | `round(amount,3)` (amount = `ss.AMOUNT` hoặc `used.AMOUNT`); có quy đổi: `ss.AMOUNT*CONVERT_RATIO` (không tách) hoặc `used.AMOUNT` (tách) — **không làm tròn** | | |
| 19 | DON_GIA | `round(ORIGINAL_PRICE*(1+VAT_RATIO),3)`; có quy đổi: `ORIGINAL_PRICE*(1+VAT_RATIO)/CONVERT_RATIO` — **không làm tròn** | | |
| 20 | THANH_TIEN_BV | `round(SO_LUONG*DON_GIA,2)` | | |
| 21 | THANH_TIEN_BH | `round(SO_LUONG*DON_GIA*TYLE/100,2)` (0 nếu TYLE=0) | | |
| 22 | T_NGUONKHAC_NSNN | `NK` nếu `HEIN_PAY_SOURCE_TYPE_ID==1`, ngược lại "0" | | gán lại, kết quả giống |
| 23 | T_NGUONKHAC_VTNN | `NK` nếu nguồn ==2 | | như trên |
| 24 | T_NGUONKHAC_VTTN | `NK` nếu nguồn ==3 | | như trên |
| 25 | T_NGUONKHAC_CL | `NK` nếu nguồn ∉ {1,2,3} (kể cả null) | | như trên |
| 26 | T_NGUONKHAC | `NK = round(round(OTHER_SOURCE_PRICE,3)*round(amount,3),2)`; có thể bị cân đối lại (1.4 bước 5) | | null → "0" |
| 27 | MUC_HUONG | `HEIN_RATIO*100` (không làm tròn), null → "0" | | |
| 28 | T_BHTT | `round(THANH_TIEN_BH*HEIN_RATIO,2)` nếu BH>0 và HEIN_RATIO có giá trị, ngược lại 0 | | |
| 29 | T_BNCCT | xem 1.4 | `MOS.POOR_FUND.IS_APPLIED`, `HIS.QD_130_BYT.XML2.TINH_LAI_TIEN_BNTT_BNCCT_NGUON_KHAC_OPTION` | |
| 30 | T_BNTT | xem 1.4 | như trên | |
| 31 | MA_KHOA | `REQUEST_BHYT_CODE` | | |
| 32 | MA_BAC_SI | `HIS_EMPLOYEE.DIPLOMA` của `REQUEST_LOGINNAME` (chỉ một người) | | |
| 33 | MA_DICH_VU | chỉ khi có `PARENT_ID` và cha có trong `vSereServ2`: `cha.TDL_HEIN_SERVICE_BHYT_CODE`, rỗng thì mã của chính dòng thuốc; thêm `_GT` khi (`PTTT(cha).IS_ANAESTHESIA==1` hoặc `MEDICINE_IS_ANAESTHESIA==1`) **và** `GAY_TE_OPTION=="1"` **và** có danh sách PTTT. Không có cha → "" | `HisConfigs["XML.EXPORT.4210.GAY_TE_OPTION"]` | |
| 34 | NGAY_YL | `INTRUCTION_TIME.ToString().Substring(0,12)` | | |
| 35 | NGAY_TH_YL | xem 1.5 | `HIS.QD_130_BYT.XML2.NGAY_TH_YL_OPTION`, `..._CUA_THUOC_OPTION` | để rỗng khi `HisConfigs["HIS.QD_130_BYT.XML2.DISPLAY_NGAY_TH_YL_OPTION"]=="1"` và `TDL_TREATMENT_TYPE_ID != 3` |
| 36 | MA_PTTT | hằng "1" | | |
| 37 | NGUON_CTRA | `T_BHTT>0` → "1"; nguồn 2/3 → "2"; nguồn 1 → "3"; còn lại "4" | | |
| 38 | VET_THUONG_TP | luôn "" | | |
| 39 | DU_PHONG | luôn "" | | |

#### 1.3 LIEU_DUNG (L:483–530)
- `TUTORIAL` rỗng → "".
- `REMEDY_COUNT` > 0 (thuốc thang) → `TUTORIAL` nguyên văn.
- `REMEDY_COUNT` null/0:
  - `TutorialFormat=="6"` và có `MEDICINE_TUTORIAL` → `MEDICINE_TUTORIAL`.
  - Ngược lại `ProcessDataTutorial`: với format 1, 3, hoặc 6 mà không có MEDICINE_TUTORIAL, bóc mọi cụm `(...)`; còn rỗng thì lấy nội dung cụm ngoặc dài nhất. Sau đó nối thêm ` * N ngày [X <SERVICE_UNIT_NAME>/ngày]`:
    - có `USE_TIME` và `USE_TIME_TO`: N = ngày(USE_TIME_TO) − ngày(USE_TIME) + 1, X = `round(AMOUNT/N, 3)`;
    - không có USE_TIME: N tính từ `TDL_INTRUCTION_DATE`, X = `round(AMOUNT/N, **1**)`.

#### 1.4 Tiền (L:577–728) — ký hiệu BV, BH, BHTT, NK
1. `BV = round(SL*DG,2)`; `BH = round(SL*DG*TYLE/100,2)`; `BHTT = round(BH*HEIN_RATIO,2)`; `NK` như mục 26.
2. `T_BNCCT = max(0, BH − BHTT − NK)`.
3. `T_BNTT = (BV − BH − NK <= 0) ? 0 : (BV − BH)` (điều kiện có trừ NK, giá trị thì không trừ: NK được trừ vào phần cùng chi trả trước).
4. Nếu `NK>0` và `round(VIR_TOTAL_PATIENT_PRICE_BHYT,2)==0` và `T_BNTT==0`: T_BNCCT < 1 đồng thì ép về 0.
5. **Cân đối**: nếu `NK>0` và `BNTT+BNCCT+BHTT+NK ≠ BV` thì `NK := BV − (tổng) + NK` và gán lại các trường NK con theo nguồn.
6. **Tính lại BNCCT/BNTT**: khi `TINH_LAI_TIEN_BNTT_BNCCT_NGUON_KHAC_OPTION=="1"`, **hoặc** (`MOS.POOR_FUND.IS_APPLIED=="1"` và thẻ `CN…`, hoặc thẻ `GD…` có `HNCODE` trong `JSON_PATIENT_TYPE_ALTER`), và `T_NGUONKHAC_CL>0` và `T_BNTT>0`: `T_BNCCT += CL`, `T_BNTT = max(0, T_BNTT − CL)`.

#### 1.5 NGAY_TH_YL
- Dòng tách theo lần dùng: `UseTime`; nếu UseTime chỉ có ngày (`%1000000==0`) và `MedicationSession` từ 1 đến 4 → 07:00/11:00/15:00/19:00.
- Không tách (`CalcNgayThYlForSereServ`): `USED_TIME` → nếu `NGAY_TH_YL_OPTION` có giá trị: (`USE_TIME` khác ngày và sau `TDL_INTRUCTION_DATE` → ngày USE_TIME + giờ phút INTRUCTION_TIME) hoặc `INTRUCTION_TIME + <option> phút`; nếu không có option: `START_TIME` → `FINISH_TIME` (nhỏ hơn INTRUCTION_TIME thì lấy INTRUCTION_TIME) → `INTRUCTION_TIME`.

#### 1.6 Key cấu hình XML2
| Key | Nguồn đọc | Tác dụng |
|---|---|---|
| `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | `data.HisConfig` | mã đối tượng BHYT; dùng làm ID trong bộ lọc (sai kiểu), so CODE khi tính TYLE |
| `HIS.Desktop.Plugins.AssignPrescription.TutorialFormat` | `data.HisConfig` | 1/3/6: cách tạo LIEU_DUNG |
| `MOS.POOR_FUND.IS_APPLIED` | `data.HisConfig` | quỹ người nghèo: CN / GD+HNCODE thì tính lại BNCCT/BNTT |
| `HIS.QD_130_BYT.XML2.TINH_LAI_TIEN_BNTT_BNCCT_NGUON_KHAC_OPTION` | `data.HisConfig` | chuyển NK_CL từ BNTT sang BNCCT |
| `HIS.QD_130_BYT.XML2.NGAY_TH_YL_CUA_THUOC_OPTION` | `HisConfigs` (cache máy trạm) | =1: tách dòng theo HIS_EXP_MEDIMATE_USED |
| `HIS.QD_130_BYT.XML2.NGAY_TH_YL_OPTION` | `HisConfigs` | số phút cộng vào INTRUCTION_TIME |
| `HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN` | `HisConfigs` | =1: bỏ thuốc có DV cha không thực hiện |
| `XML.EXPORT.4210.GAY_TE_OPTION` | `HisConfigs` | =1: thêm hậu tố `_GT` cho MA_DICH_VU |
| `HIS.QD_130_BYT.XML2.DISPLAY_NGAY_TH_YL_OPTION` | `HisConfigs` | chỉ khi IS_3176: =1 thì để rỗng NGAY_TH_YL cho hồ sơ không phải loại điều trị 3 |

---

### 2. XML3 — Chi tiết DVKT và VTYT

#### 2.1 Quy tắc chọn dòng (L:181–288)

Các danh sách:
- `listHeinServiceType` = {1,2,3,4,5,7,8,13,18,19,20,22}
- `Material` = {14,15,16,17}
- `NoExecute` = {1,2,7,8,13,18,22} (là tập con của listHeinServiceType)

Biểu thức lọc có 4 nhánh, nhưng thực tế chỉ 2 nhánh đầu có tác dụng:
- **A**: `IS_NO_EXECUTE==1` và loại ∈ NoExecute và `KHONG_LAY_DVU_KHONG_THUC_HIEN != "1"` → lấy.
- **B**: `IS_NO_EXECUTE != 1` và loại ∈ listHeinServiceType ∪ Material → **lấy, không xét giá, đối tượng hay tiền BHYT**.
- **C** (`LAY_CA_DVU_0_DONG=="1"`…) và **D** (`!= "1"`…) đều đòi `IS_NO_EXECUTE != 1` và loại ∈ NoExecute, mà những dòng đó đã lọt nhánh B → **không bao giờ chạy tới**.

Kết quả thực tế:
- Mọi DVKT/VTYT đã thực hiện thuộc các loại trên đều vào XML3, **kể cả giá 0 và kể cả đối tượng không BHYT** (trừ khi bên gọi đã lọc, như HoSoProcessorPlus).
- DV không thực hiện: chỉ loại {1,2,7,8,13,18,22} được giữ (khi cấu hình ≠ 1), với tiền = 0 và hậu tố `_TB`. Giường, khám, VTYT không thực hiện thì bị loại.
- Loại 6, 9–12, 21 không vào XML3 (đã ở XML2), nên hai bảng không chồng nhau.
- **Không bỏ trùng theo ID** (XML2 có làm), **không gộp**, **không tách**. Sắp theo `INTRUCTION_TIME`; `STT` là int, tăng 1..n.

#### 2.2 Bảng trường XML3DetailData (đúng thứ tự Order 1–47)

| # | Trường | Nguồn HIS / biểu thức | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `Treatment.TREATMENT_CODE` | | |
| 2 | STT | int 1..n | | |
| 3 | MA_DICH_VU | **DVKT**: `TDL_HEIN_SERVICE_BHYT_CODE`. **VTYT**: mã của ss cha (`PARENT_ID`), chỉ khi cha cũng có trong danh sách đã lọc và có con; không có thì "". Hậu tố `_GT`: `MEDICINE_IS_ANAESTHESIA==1` (không cần cấu hình) hoặc (`PTTT của chính dòng`.IS_ANAESTHESIA==1 và `GAY_TE_OPTION=="1"`); kiểm thêm lần nữa cho loại 7/22. Hậu tố `_TB`: `IS_NO_EXECUTE==1` | `XML.EXPORT.4210.GAY_TE_OPTION` | |
| 4 | MA_PTTT_QT | `V_HIS_SERE_SERV_PTTT(SERE_SERV_ID=ss.ID)`: `ICD_CM_CODE;ICD_CM_SUB_CODE` | | |
| 5 | MA_VAT_TU | VTYT: `TDL_HEIN_SERVICE_BHYT_CODE`; DVKT: "" | | |
| 6 | MA_NHOM | `HST_BHYT_CODE`; nếu `Treatment.IN_TIME < HST_BHYT_CODE_IN_TIME` → `OLD_HST_BHYT_CODE ?? HST_BHYT_CODE` | | |
| 7 | GOI_VTYT | VTYT có cha hợp lệ: "G1", "G2"… đánh số theo thứ tự gặp cha (đếm trên cả hồ sơ) | | |
| 8 | TEN_VAT_TU (CDATA) | VTYT: `TDL_HEIN_SERVICE_BHYT_NAME` | | |
| 9 | TEN_DICH_VU (CDATA) | DVKT: `TDL_HEIN_SERVICE_BHYT_NAME`; VTYT: "" | | |
| 10 | MA_XANG_DAU | `V_HIS_SERVICE(ID=SERVICE_ID).PETROLEUM_CODE` | | |
| 11 | DON_VI_TINH | `SERVICE_UNIT_NAME` | | |
| 12 | PHAM_VI | `TYLE_TT_BH>0` → "1"; `==0` → "2" | | |
| 13 | SO_LUONG | `round(AMOUNT,3)` | | |
| 14 | DON_GIA_BV | `p = PRIMARY_PRICE`; nếu `VIR_TOTAL_PATIENT_PRICE>0` và có `PRIMARY_PATIENT_TYPE_ID`: không có `SERVICE_CONDITION_ID` → `p=LIMIT_PRICE`, có → `p=ORIGINAL_PRICE`. `p>0` → `round(p*(1+VAT),3)`; ngược lại `VIR_PRICE` (không làm tròn) | | |
| 15 | DON_GIA_BH | DVKT: `round(ORIGINAL_PRICE*(1+VAT),3)`. VTYT: xem 2.4 | `GAN_LAI_TIEN_BNTT_BNCCT_OPTION` | |
| 16 | TT_THAU (CDATA) | VTYT: `MATERIAL_TT_THAU` nếu khác null; `MATERIAL_INFORMATION_BID==3` → `EXTRA;YEAR`; `==4` → `EXTRA;PACKAGE;YEAR`; khác → `EXTRA;PACKAGE;GROUP;YEAR` (mỗi phần có `;` phía sau, YEAR rỗng thì dư `;` cuối). DVKT: "" | | |
| 17 | TYLE_TT_DV | `ORIGINAL_PRICE>0`: `HEIN_LIMIT_PRICE/(ORIGINAL_PRICE*(1+VAT))*100` hoặc `PRICE/ORIGINAL_PRICE*100`, **round banker 0 chữ số**; không thực hiện → 0; VTYT đã thực hiện → ép 100 | | |
| 18 | TYLE_TT_BH | `patientType.PATIENT_TYPE_CODE == mã BHYT` → `HEIN_LIMIT_RATIO` round banker 0, null → 100; khác đối tượng → 0; không thực hiện → 0; VTYT stent có `CONFIG_HEIN_LIMIT_PRICE` → 100 | | |
| 19 | THANH_TIEN_BV | DVKT: `round(SL*DON_GIA_BV*TYLE_TT_DV/100,2)`; VTYT đã thực hiện: `round(SL*DON_GIA_BV,2)` | | |
| 20 | THANH_TIEN_BH | DVKT: `round(SL*DON_GIA_BH*TYLE_DV/100*TYLE_BH/100,2)`; VTYT: `TYLE_BH==0 ? 0 : round(SL*DON_GIA_BH,2)` | | |
| 21 | T_TRANTT | VTYT: `HEIN_LIMIT_PRICE`; nếu có `STENT_ORDER` và `CONFIG_HEIN_LIMIT_PRICE`: `round(CONFIG_HEIN_LIMIT_PRICE*(HEIN_LIMIT_PRICE/(ORIGINAL_PRICE*(1+VAT))),0)` (không có HEIN_LIMIT_PRICE thì CONFIG_HEIN_LIMIT_PRICE); GAN_LAI=1 và `STENT_ORDER>2` → `HEIN_LIMIT_PRICE`. DVKT: "" | | |
| 22 | MUC_HUONG | `HEIN_RATIO*100`, null → "0" | | |
| 23 | T_NGUONKHAC_NSNN | `NK` nếu `HEIN_PAY_SOURCE_TYPE_ID==1`, ngược lại "0" | | |
| 24 | T_NGUONKHAC_VTNN | `NK` nếu ==2 | | |
| 25 | T_NGUONKHAC_VTTN | `NK` nếu ==3 | | |
| 26 | T_NGUONKHAC_CL | `NK` nếu nguồn null hoặc ∉{1,2,3}; ngược lại "0" | | |
| 27 | T_NGUONKHAC | `NK = round(round(SL,3)*round(OTHER_SOURCE_PRICE,3),2)`; VTYT có thể bị tính lại (2.4) | | |
| 28 | T_BHTT | DVKT: `round(BH*HEIN_RATIO,2)`; VTYT: xem 2.4 | | |
| 29 | T_BNTT | DVKT: `(BV−BH−NK<=0) ? 0 : (BV−BH)`, round 2; VTYT: 2.4 | | |
| 30 | T_BNCCT | DVKT: `round(max(0,BH−BHTT−NK),2)`; VTYT: 2.4 | | |
| 31 | MA_KHOA | `REQUEST_BHYT_CODE`; VTYT có cha → `cha.REQUEST_BHYT_CODE` | | |
| 32 | MA_GIUONG | loại 3/4/19/20: `BED_CODE` (distinct, nối `;`) tìm trong BedLog theo (`SERVICE_REQ_ID`+`BED_SERVICE_TYPE_ID=SERVICE_ID`+`SHARE_COUNT`) → `BED_LOG_ID` → (dịch vụ+SHARE_COUNT+khoảng thời gian); không thấy → "." | | |
| 33 | MA_BAC_SI | Khám (5): `MA_BAC_SI_EXAM_OPTION` 1 → DIPLOMA của các `REQUEST_LOGINNAME` thuộc ss có `PARENT_SERVICE_REQ_ID == SERVICE_REQ_ID`; 2 → thêm `EXECUTE_LOGINNAME`; khác → `EXECUTE_LOGINNAME`. Các loại khác: DIPLOMA của `REQUEST_LOGINNAME`. Distinct, nối `;` | `HIS.QD_130_BYT.XML3.MA_BAC_SI_EXAM_OPTION` | |
| 34 | NGUOI_THUC_HIEN | xem 2.5 | `HIS.QD_130_BYT.NGUOI_THUC_HIEN_OPTION` | dùng `list8` (DIPLOMA, không có thì `IDENTIFICATION_NUMBER`/CCCD) thay vì `list7` (chỉ DIPLOMA) |
| 35 | MA_BENH | `ss.ICD_CODE` + các `ss.ICD_SUB_CODE` (+ `Treatment.ICD_CODE` nếu option 1/3); option khác 1/3 thì **gỡ** `Treatment.ICD_CODE` (một lần xuất hiện). Distinct, nối `;` | `HIS.QD_130_BYT.XML3.MA_BENH_OPTION` | |
| 36 | MA_BENH_YHCT | `TRADITIONAL_ICD_CODE` + sub (+ `Treatment.TRADITIONAL_ICD_CODE` nếu option 2/3); option khác thì gỡ | như trên | |
| 37 | NGAY_YL | `INTRUCTION_TIME` 12 ký tự; khám (5): `START_TIME` nếu có | | |
| 38 | NGAY_TH_YL | `USED_TIME` → `BEGIN_TIME` → option "1": `SAMPLE_TIME ?? START_TIME ?? INTRUCTION_TIME`; "2": `RECEIVE_SAMPLE_TIME ?? START_TIME ?? INTRUCTION_TIME`; khác: `START_TIME ?? USE_TIME ?? INTRUCTION_TIME` | `HIS.QD_130_BYT.XML3.NGAY_TH_YL_OPTION` | |
| 39 | NGAY_KQ | xem 2.6 | `HIS.QD_130_BYT.BED_TIME_OPTION`, `HIS.QD_130_BYT.XML3.NGAY_KQ_OPTION` | |
| 40 | MA_PTTT | hằng int 1 | | |
| 41 | VET_THUONG_TP | luôn "" | | |
| 42 | PP_VO_CAM | loại 7/22 có PTTT: `EMME_HEIN_CODE` → `EMME_SECOND_HEIN_CODE` → "4" | | |
| 43 | VI_TRI_TH_DVKT | luôn "" | | |
| 44 | MA_MAY | xem 2.7 | `HIS.QD_130_BYT.XML3.MA_MAY.HIEN_THI_NGUON_CU_THE` | đổi định dạng (2.7) |
| 45 | MA_HIEU_SP | VTYT: `MATERIAL_SERIAL_NUMBER`, rỗng thì `MODEL_CODE` | | |
| 46 | TAI_SU_DUNG | VTYT: "1" nếu `HIS_MATERIAL_TYPE(SERVICE_ID).IS_REUSABLE==1`, ngược lại "" | | |
| 47 | DU_PHONG | luôn "" | | |

#### 2.3 Tiền DVKT (không phải VTYT)
`SL=round(AMOUNT,3)`, `DG_BV` (#14), `DG_BH=round(ORIGINAL_PRICE*(1+VAT),3)`, `TYLE_DV`, `TYLE_BH`:
- `BV = round(SL*DG_BV*TYLE_DV/100,2)`
- `BH = round(SL*DG_BH*TYLE_DV/100*TYLE_BH/100,2)`
- `NK = round(SL*round(OTHER_SOURCE_PRICE,3),2)`
- `BHTT = round(BH*HEIN_RATIO,2)`; `BNCCT = round(max(0,BH−BHTT−NK),2)`; `BNTT = (BV−BH−NK<=0)?0:round(BV−BH,2)`; BNTT âm thì về 0.
- **Không có** bước cân đối tổng = BV như XML2, **không có** tùy chọn tính lại quỹ người nghèo/NK_CL.

#### 2.4 Tiền VTYT (loại 14–17)
Bước trung gian (L:516–571): BH tạm = `SL*DG_BH*TYLE_DV*TYLE_BH` (đối chiếu với `VIR_TOTAL_HEIN_PRICE + VIR_TOTAL_PATIENT_PRICE_BHYT + NK`); BHTT tạm = `round(BH*HEIN_RATIO,2)`, lệch quá 1 đồng so với `VIR_TOTAL_HEIN_PRICE` thì lấy VIR.
Khối cuối (L:1091–1156) quyết định giá trị xuất:
1. Đã thực hiện → `TYLE_DV = 100`.
2. `DG_BH`: GAN_LAI=1 và có `EQUIPMENT_SET_ID` và không phải stent → `round(DG_BV,3)`; stent có `CONFIG_HEIN_LIMIT_PRICE` → `min(round(ORIGINAL_PRICE*(1+VAT),3), CONFIG_HEIN_LIMIT_PRICE)` và `TYLE_BH=100`; còn lại → `round(ORIGINAL_PRICE*(1+VAT),3)`.
3. `BV = round(SL*DG_BV,2)`; `BH = TYLE_BH==0 ? 0 : round(SL*DG_BH,2)`.
4. `BHTT`: giữ giá trị tạm; lệch quá 1 đồng so với `round(VIR_TOTAL_HEIN_PRICE,2)` thì lấy VIR.
5. `BNCCT`: `x = round(max(0,BH−BHTT−NK),2)`, `y = round(VIR_TOTAL_PATIENT_PRICE_BHYT,2)`; |x−y|<1 → x; |x−y|>1 → y; bằng đúng 1 → giữ giá trị tạm.
6. `BNTT = round(BV−BHTT−BNCCT−NK,2)`.
7. `GAN_LAI_TIEN_BNTT_BNCCT_OPTION=="1"`: `BNTT = round(BV−BH−NK,2)`, `BNCCT = round(BH−BHTT,2)`; stent `STENT_ORDER>2` có CONFIG → `T_TRANTT = HEIN_LIMIT_PRICE`.
8. Chặn âm: BNTT<0 → BNTT=0, BNCCT=BV−BHTT−NK; nếu vẫn âm → BNTT=BNCCT=0 và `NK = BV−BHTT`. Ngược lại BNCCT<0 → BNCCT=0 và `NK = BV−BHTT−BNTT`.

#### 2.5 NGUOI_THUC_HIEN (L:594–801, 1089)
Hai danh sách: `list7` (chỉ DIPLOMA) và `list8` (DIPLOMA, không có thì CCCD).
- Giường (3/4/19/20): một người, theo thứ tự `EXECUTE_LOGINNAME` → `ASSIGNED_EXECUTE_LOGINNAME` → `REQUEST_LOGINNAME`.
- DVKT không phải khám, không phải VTYT:
  - nếu `HST_BHYT_CODE` có trong danh sách `NGUOI_THUC_HIEN_OPTION` (tách bằng dấu phẩy) và có ekip → các thành viên `HIS_EKIP_USER`;
  - nếu `list7` còn rỗng → `SAMPLER_LOGINNAME`, các `SUBCLINICAL_RESULT_LOGINNAME` (tách `;`), `EXECUTE_LOGINNAME`;
  - luôn thêm thành viên ekip lần nữa (nếu có);
  - còn rỗng, chưa có `START_TIME` → `REQUEST_LOGINNAME`.
- Khám (5) và VTYT: rỗng.
- Xuất: QĐ130 = `list7` distinct nối `;`; IS_3176 = `list8` distinct nối `;`.

#### 2.6 NGAY_KQ (L:868–996)
Mặc định: `END_TIME` → `FINISH_TIME` → "".
- `BED_TIME_OPTION != "1"`:
  - Giường {20,3,4,19}: `Treatment.OUT_TIME`, bị thay bởi `USE_TIME ?? TDL_INTRUCTION_TIME` nhỏ nhất của dòng giường **khác** có thời điểm sau dòng này; rồi chặn trần: AMOUNT≥1 → không quá NGAY_TH_YL + AMOUNT×24h; AMOUNT<1 → không quá NGAY_TH_YL + 4h.
  - `NGAY_KQ_OPTION=="1"` và VTYT → "".
  - Còn lại: NGAY_KQ ≤ NGAY_TH_YL và là VTYT → NGAY_TH_YL + 5 phút (DVKT thì giữ nguyên, chỉ ghi log).
- `BED_TIME_OPTION == "1"` và ss có `BED_LOG_ID` khớp BedLog: giường → `BED_LOG.FINISH_TIME`, hoặc thời điểm của ss kế tiếp cùng BED_LOG_ID; chặn trần như trên. Không có bed log thì xử lý như hai nhánh sau.

#### 2.7 MA_MAY (L:1012–1090)
Nguồn: bản ghi `V_HIS_SERE_SERV_TEIN` đầu tiên của ss có đủ `MACHINE_GROUP_CODE`, `SOURCE_CODE`, `SERIAL_NUMBER`; nếu ss **không có** TEIN nào thì lấy chính các trường đó trên ss. Có TEIN nhưng không bản ghi nào đủ ba trường → "" (không quay về trường trên ss).
- QĐ130: `MACHINE_GROUP_CODE.SOURCE_CODE[ [SOURCE_NAME] nếu HIEN_THI_NGUON_CU_THE=1 ].HEIN_MEDI_ORG_CODE.SERIAL_NUMBER` (4 phần, có mã CSKCB của hồ sơ).
- IS_3176 (ghi đè): `MACHINE_GROUP_CODE.SOURCE_CODE[ [SOURCE_NAME] chỉ khi SOURCE_CODE=="3" ].SERIAL_NUMBER` (3 phần, **bỏ mã CSKCB**, không còn phụ thuộc HIEN_THI_NGUON_CU_THE).

#### 2.8 Key cấu hình XML3 (đều đọc từ `data.ConfigData`)
| Key | Tác dụng |
|---|---|
| `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | so CODE để tính TYLE_TT_BH; `Convert.ToInt64` làm ID cho nhánh lọc chết |
| `HIS.QD_130_BYT.BED_TIME_OPTION` | =1: NGAY_KQ giường theo BED_LOG |
| `HIS.QD_130_BYT.NGUOI_THUC_HIEN_OPTION` | danh sách MA_NHOM (phẩy) ưu tiên lấy ekip |
| `HIS.QD_130_BYT.XML3.MA_BENH_OPTION` | 1/2/3: có kèm ICD chính / YHCT của hồ sơ không |
| `HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN` | =1: bỏ DV không thực hiện |
| `HIS.QD_130_BYT.LAY_CA_DVU_0_DONG` | chỉ nằm trong nhánh lọc chết → **không có tác dụng** |
| `HIS.QD_130_BYT.XML3.NGAY_TH_YL_OPTION` | 1: giờ lấy mẫu; 2: giờ nhận mẫu |
| `HIS.QD_130_BYT.XML3.NGAY_KQ_OPTION` | =1: NGAY_KQ VTYT để rỗng |
| `HIS.QD_130_BYT.XML3.GAN_LAI_TIEN_BNTT_BNCCT_OPTION` | VTYT: bộ trang thiết bị lấy DG_BH=DG_BV; công thức BNTT/BNCCT khác |
| `HIS.QD_130_BYT.XML3.MA_BAC_SI_EXAM_OPTION` | khám: MA_BAC_SI lấy BS chỉ định CLS con (1) và thêm người thực hiện (2) |
| `HIS.QD_130_BYT.XML3.MA_MAY.HIEN_THI_NGUON_CU_THE` | QĐ130: chèn `[SOURCE_NAME]` |
| `XML.EXPORT.4210.GAY_TE_OPTION` | `_GT` theo PTTT gây tê |
| `XML.EXPORT.130.XML3.MA_MAY_OPTION` | khai báo hằng nhưng **không dùng** |

---

### 3. Điểm đáng ngờ / lỗi tiềm ẩn (chỉ những gì thấy rõ trong mã)

#### XML2
1. **Lấy mã đối tượng làm ID** (L:352): `patientTypeId = long.TryParse(<PATIENT_TYPE_CODE.BHYT>)`. Chỉ đúng khi ID trùng với mã dạng số. Nếu mã không phải số → `patientTypeId=0` → mọi dòng bị coi là "không BHYT" → bỏ qua điều kiện `VIR_TOTAL_HEIN_PRICE/OTHER_SOURCE_PRICE`. Bên gọi (`HoSoProcessorPlus.cs:214`) lại đổi đúng bằng `GetPatientTypeId`.
2. Lọc: dòng **không phải BHYT luôn được lấy**; trong nhánh BHYT có `if (o.PATIENT_TYPE_ID == patientTypeId)` lồng lặp lại, nhánh else là nhánh chết.
3. **NullReference làm mất cả XML2** (L:578): `value.PATIENT_TYPE_CODE` khi `HisPatientTypes` null hoặc không tìm thấy ID → ngoại lệ bị bắt ở ngoài → `Success=false`, không sinh XML2.
4. **Có quy đổi đơn vị thì không làm tròn** (L:584–596): `SO_LUONG = AMOUNT*CONVERT_RATIO` và `DON_GIA = ORIGINAL_PRICE*(1+VAT)/CONVERT_RATIO` không round; phép chia có thể cho tới 28 chữ số thập phân (G27 in ra tới 27 chữ số có nghĩa).
5. **Tách dòng + quy đổi lệch đơn vị** (L:593 so với L:589): khi tách theo `HIS_EXP_MEDIMATE_USED`, `SO_LUONG = used.AMOUNT` (không nhân `CONVERT_RATIO`) trong khi `DON_GIA` đã chia `CONVERT_RATIO`; còn NK luôn dùng `round(item2,3)` (lượng gốc). Hai đường cho kết quả theo hai đơn vị khác nhau.
6. Lấy `round(amount,3)` ở L:582 rồi bị ghi đè ngay khi có quy đổi (gán rồi ghi đè).
7. LIEU_DUNG: hai nhánh làm tròn khác nhau (3 chữ số và 1 chữ số); dùng `AMOUNT` và `SERVICE_UNIT_NAME` gốc, trong khi DON_VI_TINH có thể là `CONVERT_UNIT_NAME`, nên phần "[X đơn vị/ngày]" có thể lệch đơn vị so với SO_LUONG/DON_VI_TINH.
8. Trong `ProcessDataTutorial`, nhánh `configValue=="6" && có mediTutorial` là nhánh chết, vì L:488 đã xử lý trường hợp này trước khi gọi.
9. TYLE_TT_BH round **0 chữ số kiểu banker** (`Math.Round(x,0)`, không AwayFromZero) rồi mới nhân vào THANH_TIEN_BH, nên tỷ lệ lẻ (ví dụ 33,33%) bị làm tròn trước khi tính tiền.
10. Bước "T_BNCCT < 1 đồng → 0" (L:680–691): nhánh `> 1` gán lại giá trị cũ; trường hợp chênh đúng 1 không vào nhánh nào.
11. Bước cân đối (L:696–704) có thể làm `T_NGUONKHAC` thành số âm (không chặn dưới 0).
12. Khối IS_3176 (L:800–824) đặt NSNN/VTNN/VTTN về "0" rồi gán lại đúng như QĐ130, **không đặt lại** `T_NGUONKHAC_CL`. Kết quả giống QĐ130, tức khối này gần như không đổi gì.
13. Đọc cấu hình từ **hai nguồn**: 4 key lấy từ `data.HisConfig`, 5 key lấy từ `HisConfigs.Get` (cache LocalStorage của desktop). Khi chạy ở nơi không nạp `HisConfigs` thì 5 key này coi như rỗng. `KHONG_LAY_DVU_KHONG_THUC_HIEN` ở XML3 lại đọc từ `data.ConfigData`, nên hai bảng có thể hiểu khác nhau cùng một key.
14. `_GT` ở XML2 cần `GAY_TE_OPTION=="1"` và cần danh sách PTTT khác null **kể cả khi** `MEDICINE_IS_ANAESTHESIA==1`; XML3 thêm `_GT` theo `MEDICINE_IS_ANAESTHESIA` mà không cần cấu hình. Hai bảng không nhất quán.
15. `MA_NHOM` không xét `OLD_HST_BHYT_CODE`/`HST_BHYT_CODE_IN_TIME` như XML3.
16. `MA_BAC_SI` chỉ lấy một người chỉ định (`REQUEST_LOGINNAME`); `MA_PTTT` cố định "1"; `VET_THUONG_TP`, `DU_PHONG` luôn rỗng. Hàm `SubMaxLength` có nhưng không dùng (không cắt độ dài trường nào).
17. TT_THAU: `MEDICINE_TT_THAU == ""` (rỗng nhưng khác null) vẫn được dùng → TT_THAU rỗng, không ghép từ các trường thầu.

#### XML3
18. **Lọc: nhánh C và D là mã chết** (L:205–270): nhánh B đã lấy mọi dòng đã thực hiện thuộc các loại trong danh sách, nên điều kiện giá > 0, `VIR_TOTAL_HEIN_PRICE`, `OTHER_SOURCE_PRICE`, đối tượng và key `HIS.QD_130_BYT.LAY_CA_DVU_0_DONG` **không bao giờ có tác dụng**. DVKT/VTYT giá 0 và DV đối tượng khác đều vào XML3, nếu bên gọi chưa lọc.
19. **`Convert.ToInt64(mã BHYT)`** (L:195): mã không phải số → ngoại lệ → `list=null` → XML3 rỗng hoàn toàn. Đây cũng là chỗ lấy mã làm ID.
20. **NullReference** `val.PATIENT_TYPE_CODE` (L:494–495, kể cả trong câu `LogSystem.Debug`) khi `PatientTypes` null hoặc thiếu → mất cả XML3.
21. **Nguy cơ vỡ cả XML3 ở NGAY_KQ giường** (L:894, 955): `long.Parse(text14 + "00")` khi `text14 == ""` (giường cuối, hồ sơ chưa có `OUT_TIME`, ss không có END/FINISH_TIME; hoặc BED_LOG không có FINISH_TIME và không có ss kế tiếp) → `FormatException` → toàn bộ danh sách null.
22. **THANH_TIEN_BH của VTYT bỏ qua TYLE_TT_BH** (L:1116): `BH = round(SL*DG_BH,2)` khi `TYLE_BH != 0`, không nhân `TYLE_BH/100`. VTYT có `HEIN_LIMIT_RATIO` khác 100 sẽ ra BH bằng 100%.
23. **Gán rồi ghi đè ở VTYT**: khối stent L:577–585 (`TYLE_BH=100`, cap DG_BH, tính lại BH) bị khối cuối L:1091–1116 tính lại hoàn toàn; `tTranTT` L:378 bị L:502 ghi đè; `num10` L:559 tính rồi tính lại ngay ở L:568.
24. **T_BHTT của VTYT không tính lại theo THANH_TIEN_BH mới**: BHTT vẫn là giá trị từ BH tạm (có TYLE_DV, TYLE_BH) hoặc VIR, trong khi BH đã đổi ở khối cuối.
25. **Các trường NK con lệch tổng** (L:1149, 1155): khi chặn âm, `num9` (T_NGUONKHAC) được tính lại nhưng NSNN/VTNN/VTTN/CL đã gán từ `num9` cũ, nên tổng con khác T_NGUONKHAC.
26. **DV không thực hiện vẫn có NK**: `TYLE_DV=TYLE_BH=0` làm BV=BH=0, nhưng `NK = SL*OTHER_SOURCE_PRICE` vẫn tính, nên dòng `_TB` có thể có `T_NGUONKHAC > 0` trong khi `THANH_TIEN_BV = 0`.
27. Với VTYT, các kiểm `IS_NO_EXECUTE != 1` ở khối cuối luôn đúng (VTYT không thực hiện đã bị lọc), và hậu tố `_TB` cho VTYT không bao giờ xảy ra.
28. DVKT: không có bước cân đối `BNTT+BNCCT+BHTT+NK = BV` như XML2. Khi `0 < BV−BH ≤ NK` thì BNTT bị ép 0 dù `BV−BH > 0`, nên tổng có thể nhỏ hơn BV.
29. `TYLE_TT_DV` và `TYLE_TT_BH` round banker 0 chữ số trước khi nhân, giống XML2 mục 9. `THANH_TIEN_BV` của DVKT nhân `DON_GIA_BV` (có thể là PRIMARY/LIMIT price) với `TYLE_TT_DV` được tính trên `ORIGINAL_PRICE`.
30. `_GT` cho VTYT xét PTTT của **chính dòng VTYT** (`SERE_SERV_ID == ss.ID`), không phải PTTT của DV cha (XML2 xét cha), và `MEDICINE_IS_ANAESTHESIA` của dòng vật tư.
31. VTYT có cha nhưng cha không nằm trong danh sách đã lọc (ví dụ cha giá 0 bị lọc ở bên gọi, hoặc cha là loại không xuất) → `MA_DICH_VU`, `GOI_VTYT` rỗng, `MA_KHOA` lấy của chính dòng vật tư.
32. NGUOI_THUC_HIEN: `list11 == null` (L:683) không bao giờ đúng (`ToList()` không trả null) → nhánh chết. Ekip được thêm hai lần (L:662 và L:760); distinct lúc nối nên không lặp, nhưng nhóm trong `NGUOI_THUC_HIEN_OPTION` không còn thật sự "ưu tiên". `flag2` so với `HST_BHYT_CODE` gốc chứ không so với MA_NHOM đã đổi theo `OLD_HST_BHYT_CODE`.
33. MA_BAC_SI khám với option 1/2: nếu phòng khám không chỉ định CLS con nào thì MA_BAC_SI rỗng (option 1) hoặc chỉ còn người thực hiện (option 2); option 1 không có dự phòng.
34. MA_BENH: L:816 là nhánh chết (L:812 cùng điều kiện đã thêm trước). Khi option ≠ 1/3, `List.Remove(Treatment.ICD_CODE)` gỡ cả ICD **của chính ss** nếu trùng ICD chính của hồ sơ, và chỉ gỡ một lần xuất hiện.
35. MA_MAY: có TEIN nhưng không bản ghi nào đủ ba trường → rỗng, không quay về trường trên ss. Hàm `BuildMachinePrefix` và key `XML.EXPORT.130.XML3.MA_MAY_OPTION` khai báo nhưng không dùng.
36. TT_THAU VTYT: dùng `string.Format` với `X + ";"` nên dư `;` cuối khi `BID_YEAR` rỗng; `MATERIAL_TT_THAU == ""` (khác null) vẫn được dùng.
37. Không bỏ trùng theo `ss.ID` (XML2 có `group by ID`).
38. Trường luôn rỗng hoặc cố định ở cả QĐ130 lẫn IS_3176: `VET_THUONG_TP`, `VI_TRI_TH_DVKT`, `DU_PHONG` = ""; `MA_PTTT` = 1. IS_3176 chỉ đổi **hai** trường: `MA_MAY` và `NGUOI_THUC_HIEN`.

---

## Ánh xạ XML4 – XML8 (HIS hisnguonmo, DLL His.Bhyt.ExportXml.XML130.*)

Nguồn: mã C# dịch ngược (ILSpy) trong `scratchpad\xml130\His.Bhyt.ExportXml.XML130.XML4..XML8`.
Chỉ ghi những gì đọc thấy trong mã; chỗ nào phải đối chiếu thêm (nghĩa cột HIS, đặc tả cổng) thì ghi rõ "cần đối chiếu".

### Quy ước chung

- **Định dạng thời gian**: HIS lưu thời gian dạng số `yyyyMMddHHmmss`. Mã cắt chuỗi: `.ToString().Substring(0,12)` → `yyyyMMddHHmm`; `.Substring(0,8)` → `yyyyMMdd`. Không đổi múi giờ, không kiểm độ dài (với `long?` null đã có kiểm `HasValue` ở hầu hết chỗ).
- **CDATA**: trường kiểu `XmlCDataSection` đi qua `ConvertStringToXmlDocument` → `RemoveXmlCharError` (bỏ ký tự không hợp lệ XML), null → chuỗi rỗng. Trường `string` thường **không** được lọc ký tự lỗi.
- **`SubMaxLength`**: cắt theo **số ký tự** (`string.Length`) tới `GlobalConfigStore.MAX_LENGTH = 1024`, không theo byte.
- **`HisConfigKey.GetConfigData(list, key)`**: lấy `VALUE`, rỗng thì `DEFAULT_VALUE`, `Trim()`. Riêng XML4 dùng `HisConfigs.Get<string>(key)` (cấu hình toàn cục desktop, không qua InputADO).
- **Mã bác sĩ**: mọi chỗ "mã bác sĩ" đều tra `HIS_EMPLOYEE` theo `LOGINNAME` rồi lấy **`DIPLOMA`** (số CCHN). Riêng mã thủ trưởng đơn vị ở nhánh QĐ130 (XML7/XML8) lấy **`SOCIAL_INSURANCE_NUMBER`**; nhánh IS_3176 đổi sang `DIPLOMA`.
- **MA_LK** mọi bảng = `TREATMENT_CODE`.
- **Mã loại hình HIS** (theo cách code dùng): `TDL_TREATMENT_TYPE_ID` 1 = khám, 2 = điều trị ngoại trú, 3 = điều trị nội trú, 4 = điều trị ban ngày (5, 6 có trong switch XML8; nghĩa cần đối chiếu danh mục HIS). `TDL_HEIN_SERVICE_TYPE_ID` theo log XML4: 1 = CĐHA, 8 = TDCN, 18 = XN, 7 = PTTT, 22 = TT.
- **IS_3176**: cờ trong InputADO của XML5, XML6, XML7, XML8. **XML4 không có cờ này** → QĐ130 và QĐ3176 xuất giống hệt.

---

### XML4 – Chỉ tiêu chi tiết dịch vụ cận lâm sàng

Gốc XML: `CHITIEU_CHITIET_DICHVUCANLAMSANG/DSACH_CHI_TIET_CLS/CHI_TIET_CLS`.
Đầu vào: `vTreatment` (V_HIS_TREATMENT_12), `vSereServ` (V_HIS_SERE_SERV_2), `vSereServTein` (V_HIS_SERE_SERV_TEIN – chỉ số XN), `vSereServSuin` (V_HIS_SERE_SERV_SUIN – chỉ số CĐHA/TDCN), `ListServices` (V_HIS_SERVICE), `Employee`.

#### Điều kiện chọn dòng

1. Lọc `vSereServ`: `IS_NO_EXECUTE != 1` **và** (
   `TDL_HEIN_SERVICE_TYPE_ID ∈ {1, 8, 18}`
   **hoặc** (`LAY_DVU_PTTT == 1` và loại ∈ {7, 22} và `TRACKING_ID == null`)
   **hoặc** (`LAY_DVU_PTTT == 2` và loại ∈ {7, 22}) ).
   Lọc xong mà rỗng thì không có XML4 (trả `null`, `GetXml4String` không sinh chuỗi).
2. **Khối A – dòng chỉ số XN (Tein)**: duyệt `vSereServTein` sắp theo `ID` tăng; Tein nào có `SERE_SERV_ID` thuộc danh sách đã lọc thì sinh 1 dòng. Không lọc Tein có `VALUE` rỗng.
3. **Khối B – dịch vụ không có chỉ số**: các sere_serv đã lọc có loại ∈ danh sách CLS ({1,8,18}, thêm {7,22} nếu option 1/2; option 1 vẫn loại PTTT có `TRACKING_ID`), **không có Tein** và **không có Suin** → 1 dòng/dịch vụ, sắp theo `INTRUCTION_TIME`.
4. **Khối C – dòng Suin**: nếu có Suin: duyệt `vSereServ` (thứ tự đầu vào), mỗi Suin của dịch vụ → 1 dòng.
5. STT tăng liên tục qua A → B → C.

#### Trường

| Trường | Khối A (Tein) | Khối B (không chỉ số) | Khối C (Suin) | Ghi chú / định dạng |
|---|---|---|---|---|
| MA_LK | TREATMENT_CODE | = | = | |
| STT | đếm tăng | = | = | `int` |
| MA_DICH_VU | ss.TDL_HEIN_SERVICE_BHYT_CODE | = | = | |
| MA_CHI_SO | Tein.BHYT_CODE, rỗng → Tein.ANTIBIOTIC_RESISTANCE_CODE, rỗng → "" | V_HIS_SERVICE.SUIM_INDEX_CODE | Suin.SUIM_INDEX_CODE; nếu mã **hoặc** tên rỗng → cả mã và tên lấy từ V_HIS_SERVICE.SUIM_INDEX_* | không cắt độ dài |
| TEN_CHI_SO | Tein.BHYT_NAME, rỗng → ANTIBIOTIC_RESISTANCE_NAME | V_HIS_SERVICE.SUIM_INDEX_NAME | Suin.SUIM_INDEX_NAME (như trên) | CDATA |
| GIA_TRI | Tein.VALUE | "" | Suin.VALUE | CDATA |
| DON_VI_DO | Tein.TEST_INDEX_UNIT_NAME | "" | Suin.SUIM_INDEX_UNIT_NAME | CDATA |
| MO_TA | Tein.RESULT_DESCRIPTION | ss.DESCRIPTION | Suin.DESCRIPTION | CDATA, cắt 1024 ký tự |
| KET_LUAN | ss.CONCLUDE | ss.CONCLUDE | ss.CONCLUDE | CDATA, cắt 1024 ký tự; lặp lại ở mọi dòng chỉ số của cùng dịch vụ |
| NGAY_KQ | `GetNgayKetQua(ss, FINISH_TIME)` | `GetNgayKetQua(ss, END_TIME)` | `GetNgayKetQua(ss, FINISH_TIME)` | 12 ký tự `yyyyMMddHHmm`. Nếu loại 1 hoặc 8 (CĐHA/TDCN): `RESULT_READ_TIME ?? END_TIME ?? FINISH_TIME ?? START_TIME ?? INTRUCTION_TIME`; loại khác dùng thời điểm truyền vào (null → "") |
| MA_BS_DOC_KQ | xem dưới | = | = | |
| DU_PHONG | "" | "" | "" | |

**MA_BS_DOC_KQ**: mặc định = `ss.EXECUTE_LOGINNAME` (tên đăng nhập thô). Nếu có danh sách Employee: tách `(SUBCLINICAL_RESULT_LOGINNAME ?? EXECUTE_LOGINNAME)` theo `;`, lấy `DIPLOMA` của **người đầu tiên** có DIPLOMA khác rỗng rồi dừng.

#### Key HIS_CONFIG

| Key | Tác dụng |
|---|---|
| `HIS.QD_130_BYT.XML4.LAY_DVU_PTTT` | 0/rỗng: chỉ CĐHA/TDCN/XN. 1: thêm PTTT/TT (7, 22) **không gắn tờ điều trị** (`TRACKING_ID` null). 2: thêm mọi PTTT/TT. Đọc qua `HisConfigs.Get` toàn cục. |
| `HIS.QD_130_BYT.XML4.MA_BS_DOC_KQ_OPTION` | Khai báo hằng trong `HisConfigKey` nhưng **không được dùng** ở đâu. |

#### Khác khi IS_3176
Không có (XML4 không nhận cờ).

#### Điểm đáng ngờ
1. **MA_BS_DOC_KQ rơi về login name**: không tìm được nhân viên, nhân viên không có DIPLOMA, hoặc `Employee` rỗng → xuất nguyên `EXECUTE_LOGINNAME` (không phải CCHN) vào XML.
2. `SUBCLINICAL_RESULT_LOGINNAME ?? EXECUTE_LOGINNAME`: nếu SUBCLINICAL_RESULT_LOGINNAME là chuỗi rỗng (không null) thì không rơi về EXECUTE_LOGINNAME để tra DIPLOMA. Chỉ lấy 1 bác sĩ dù có nhiều login ngăn `;`.
3. Khối A: mã chỉ số và tên chỉ số chọn độc lập → có thể ra MA_CHI_SO từ `ANTIBIOTIC_RESISTANCE_CODE` nhưng TEN_CHI_SO từ `BHYT_NAME` (lệch cặp).
4. Khối A/C lấy NGAY_KQ của XN theo `FINISH_TIME`, khối B theo `END_TIME` → cùng loại dịch vụ XN có thể khác nguồn thời gian tuỳ có chỉ số hay không.
5. Khối C lặp trên `data.vSereServ` (đã lọc) nhưng **không** kiểm loại dịch vụ ∈ CLS list như khối B; vô hại vì đã lọc ở bước 1.
6. `MapADOToXml`: điều kiện `listAdo == null && listAdo.Count <= 0` (dùng `&&`) → NRE nếu null (bị try/catch nuốt).
7. Cắt MO_TA/KET_LUAN theo ký tự, không theo byte.
8. Key `MA_BS_DOC_KQ_OPTION` khai báo mà không dùng → nếu đơn vị đặt cấu hình này sẽ không có tác dụng.

---

### XML5 – Chỉ tiêu chi tiết diễn biến lâm sàng

Gốc XML: `CHITIEU_CHITIET_DIENBIENLAMSANG/DSACH_CHI_TIET_DIEN_BIEN_BENH/CHI_TIET_DIEN_BIEN_BENH`.
Đầu vào: `vTreatment`, `ListSereServ`, `Trackings` (HIS_TRACKING – tờ điều trị), `ListDHSTs` (HIS_DHST), `ListDebates` (HIS_DEBATE – hội chẩn), `SereServPttts` (V_HIS_SERE_SERV_PTTT), `Configs`, `Employee`, `IS_3176`.

#### Điều kiện sinh dòng

- **Dịch vụ khám chính** (`sere_serv_main_exam`): sere_serv đầu tiên có `TDL_IS_MAIN_EXAM == 1`; không có → dịch vụ có `TDL_SERVICE_TYPE_ID == 1` và `IS_NO_EXECUTE != 1`, sớm nhất theo `TDL_INTRUCTION_TIME`. DHST khám = HIS_DHST có `ID == main_exam.DHST_ID`.
- **Nhánh 1 – có tờ điều trị** (`Trackings` khác null và Count > 0): mỗi tracking có `CONTENT` khác rỗng, sắp theo `TRACKING_TIME` → 1 dòng.
- **Nhánh 2 – không có tracking nào** và có khám chính → đúng 1 dòng từ dữ liệu khám.
- Không có khám chính và không có tracking → 0 dòng (không sinh XML5).

#### Trường – Nhánh 1 (tracking)

| Trường | Nguồn | Ghi chú |
|---|---|---|
| MA_LK | TREATMENT_CODE | |
| STT | đếm tăng | |
| DIEN_BIEN_LS | Ghép `"; "`: `"Diễn biến: " + CONTENT.Trim()`; `"CSST<...>"` từ DHST có `TRACKING_ID == tracking.ID` (lấy bản ghi đầu tiên của nhóm); `"Theo dõi - chăm sóc: " + CARE_INSTRUCTION.Trim()` | CDATA, cắt 1024 ký tự. CSST: `M`PULSE, `T`TEMPERATURE, `NT`BREATH_RATE, `SP`SPO2, `W`WEIGHT, `H`HEIGHT, `B`BELLY, `C`CHEST, `U`URINE, `HA`MAX/MIN, `CBG`CAPILLARY_BLOOD_GLUCOSE, nối bằng `_` |
| GIAI_DOAN_BENH | tracking.DISEASE_STAGE | CDATA, không cắt |
| HOI_CHAN | `string.Join("; ", Distinct(HIS_DEBATE.CONCLUSION))` với `TRACKING_ID == tracking.ID` | CDATA, cắt 1024 |
| PHAU_THUAT | Nối `;` các `sere_serv.DESCRIPTION` có `TRACKING_ID == tracking.ID`, `TDL_HEIN_SERVICE_TYPE_ID ∈ {7, 22, 2}` **và** có bản ghi V_HIS_SERE_SERV_PTTT | CDATA, cắt 1024. Lấy DESCRIPTION của sere_serv, không lấy từ bảng PTTT |
| THOI_DIEM_DBLS | TRACKING_TIME 12 ký tự | `yyyyMMddHHmm` |
| NGUOI_THUC_HIEN | DIPLOMA của `tracking.MODIFIER`; không có → DIPLOMA của `tracking.CREATOR`; không → "" | **Ưu tiên người sửa cuối** |
| DU_PHONG | "" | |

#### Trường – Nhánh 2 (khám)

| Trường | Nguồn | Ghi chú |
|---|---|---|
| MA_LK | TREATMENT_CODE | |
| STT | 1 | |
| DIEN_BIEN_LS | `ProcessDataExam`: ghép `"; "` các mục có dữ liệu: "Lý do khám: " HOSPITALIZATION_REASON; "Quá trình bệnh lý: " PATHOLOGICAL_PROCESS; "Tiểu sử bệnh của bệnh nhân: " PATHOLOGICAL_HISTORY; "Tiểu sử bệnh của gia đình: " PATHOLOGICAL_HISTORY_FAMILY; "Khám toàn thân: " FULL_EXAM; "Khám bộ phận: " PART_EXAM; CSST<...> (DHST khám); "Tóm tắt kết quả cls: " SUBCLINICAL; "Phương pháp điều trị: " TREATMENT_INSTRUCTION | CDATA, cắt 1024 |
| GIAI_DOAN_BENH | "" | |
| HOI_CHAN | "" | |
| PHAU_THUAT | "" | |
| THOI_DIEM_DBLS | theo `XML.EXPORT.4210.XML3.NGAY_YL_OPTION`: `"2"` → START_TIME (null → **null**); `"3"` → START_TIME, null → TDL_INTRUCTION_TIME; khác → TDL_INTRUCTION_TIME | 12 ký tự |
| NGUOI_THUC_HIEN | QĐ130: `DIPLOMA ?? ""` của nhân viên `LOGINNAME == main_exam.EXECUTE_LOGINNAME` | |
| DU_PHONG | "" | |

#### Key HIS_CONFIG

| Key | Tác dụng |
|---|---|
| `XML.EXPORT.4210.XML3.NGAY_YL_OPTION` | Chỉ dùng cho THOI_DIEM_DBLS của nhánh khám (key mượn từ XML3 của QĐ4210). |

#### Khác khi IS_3176
- Chỉ ở **nhánh 2 (khám)**: NGUOI_THUC_HIEN = `DIPLOMA ?? IDENTIFICATION_NUMBER` của người khám chính. Nhánh tracking không đổi gì.

#### Điểm đáng ngờ
1. **Có tracking nhưng mọi tracking đều rỗng CONTENT → 0 dòng**, không rơi về dữ liệu khám (nhánh 2 chỉ chạy khi `Trackings` null/rỗng). Hồ sơ nội trú chỉ ghi DHST/chăm sóc trên tờ điều trị sẽ mất XML5.
2. Hệ quả của bộ lọc `CONTENT != rỗng`: điều kiện bên trong `if (CONTENT || DHST || CARE_INSTRUCTION)` luôn đúng, nhánh `else` (rơi về ProcessDataExam) là **mã chết**; tracking chỉ có CSST/chăm sóc bị bỏ.
3. NGUOI_THUC_HIEN nhánh tracking ưu tiên `MODIFIER` (người sửa cuối, có thể là điều dưỡng/người nhập) trước `CREATOR`.
4. IS_3176 `DIPLOMA ?? IDENTIFICATION_NUMBER`: dùng `??` nên DIPLOMA là chuỗi rỗng sẽ **không** rơi về IDENTIFICATION_NUMBER.
5. Nhánh 2: `o.LOGINNAME.Equals(...)` → nhân viên có LOGINNAME null gây NRE, cả XML5 thất bại (Success=false).
6. `NGAY_YL_OPTION = "2"` và START_TIME null → THOI_DIEM_DBLS = null (phần tử rỗng/thiếu).
7. PHAU_THUAT: dịch vụ đầu tiên có DESCRIPTION null làm `text = null`, các mục sau ghép thành `";mô tả"` (thừa dấu `;` đầu). Loại `2` trong {7,22,2} không có trong danh sách loại XML4 dùng → cần đối chiếu nghĩa ID 2.
8. `TDL_IS_MAIN_EXAM == 1` không kiểm `IS_NO_EXECUTE`.
9. GIAI_DOAN_BENH không cắt độ dài.
10. `MapADOToXml` cùng lỗi `&&` như XML4. `ListSereServ` null → NRE ngay đầu.

---

### XML6 – Hồ sơ bệnh án chăm sóc và điều trị HIV/AIDS

Gốc XML: `CHI_TIEU_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS/DSACH_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS/HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS`.
Đầu vào: `vTreatment12`, `HisHivTreatment` (HIS_HIV_TREATMENT), `HisConfig` (không dùng), `IS_3176`.

#### Điều kiện sinh
1 dòng khi `vTreatment12 != null && HisHivTreatment != null && HisHivTreatment.TREATMENT_ID != HisHivTreatment.ID`.

#### Trường (theo thứ tự `Order` trong XML6DetailData)

| # | Trường | Nguồn HIS | Định dạng / ghi chú | IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | TREATMENT_CODE | | |
| 2 | MA_THE_BHYT | TDL_HEIN_CARD_NUMBER | | |
| 3 | SO_CCCD | TDL_PATIENT_CCCD_NUMBER ?? "" | | `CCCD ?? CMND ?? PASSPORT ?? ""` |
| 4 | NGAY_SINH | TDL_PATIENT_DOB | 12 ký tự; nếu `TDL_PATIENT_IS_HAS_NOT_DAY_DOB == 1` → `yyyy` + `"00000000"` | |
| 5 | GIOI_TINH | TDL_PATIENT_GENDER_ID: 1 → "2", 2 → "1", khác → "3" | đảo mã HIS → mã BHYT | |
| 6 | DIA_CHI | TDL_PATIENT_ADDRESS | CDATA | |
| 7 | MATINH_CU_TRU | TDL_PATIENT_PROVINCE_CODE | | |
| 8 | MAHUYEN_CU_TRU | TDL_PATIENT_DISTRICT_CODE | | |
| 9 | MAXA_CU_TRU | TDL_PATIENT_COMMUNE_CODE | | |
| 10 | NGAYKD_HIV | HIV_INFECTION_DATE | 8 ký tự `yyyyMMdd` | |
| 11 | NOI_LAY_MAU_XN | TEST_SAMPLE_PLACE | CDATA | |
| 12 | NOI_XN_KD | TEST_PLACE_KD | string (không CDATA) | |
| 13 | NOI_BDDT_ARV | ARV_PATIENT_BEGIN_PLACE | CDATA | |
| 14 | BDDT_ARV | ARV_PATIENT_BEGIN | 8 ký tự | |
| 15 | MA_PHAC_DO_DIEU_TRI_BD | BEGIN_REGIMEN_HIV_CODE | | |
| 16 | MA_BAC_PHAC_DO_BD | BEGIN_REGIMEN_LEVEL (số → chuỗi) | | |
| 17 | MA_LYDO_DTRI | HIV_TREATMENT_REASON | | |
| 18 | LOAI_DTRI_LAO | TUBERCULOSIS_TREATMENT_TYPE | CDATA | |
| 19 | SANG_LOC_LAO | TUBERCULOSIS_SCREENING | `long?` | |
| 20 | PHACDO_DTRI_LAO | TUBERCULOSIS_REGIMEN | CDATA | |
| 21 | NGAYBD_DTRI_LAO | TUBERCULOSIS_TREATMENT_BEGIN | 8 ký tự | |
| 22 | NGAYKT_DTRI_LAO | TUBERCULOSIS_TREATMENT_END | 8 ký tự | |
| 23 | KQ_DTRI_LAO | TUBERCULOSIS_TREATMENT_RESULT | `long?` | |
| 24 | MA_LYDO_XNTL_VR | TEST_PCR_RNA_REASON | | |
| 25 | NGAY_XN_TLVR | TEST_PCR_RNA_DATE | 8 ký tự | |
| 26 | KQ_XNTL_VR | TEST_PCR_RNA_RESULT | CDATA | |
| 27 | NGAY_KQ_XN_TLVR | TEST_PCR_RNA_RESULT_DATE | 8 ký tự | |
| 28 | MA_LOAI_BN | HIV_PATIENT_TYPE | | |
| 29 | GIAI_DOAN_LAM_SANG | CLINICAL_STAGE | `long?` | |
| 30 | NHOM_DOI_TUONG | GROUP_PATIENT | | |
| 31 | MA_TINH_TRANG_DK | HIV_PATIENT_STATUS | | |
| 32 | LAN_XN_PCR | TEST_PCR_TIMES | | |
| 34 | NGAY_XN_PCR | TEST_PCR_DATE | 8 ký tự (Order 33 bị bỏ trống – không ảnh hưởng thứ tự) | |
| 35 | NGAY_KQ_XN_PCR | TEST_PCR_RESULT_DATE | 8 ký tự | |
| 36 | MA_KQ_XN_PCR | TEST_PCR_RESULT | | |
| 37 | NGAY_NHAN_TT_MANG_THAI | DATE_OF_PREGNANCY | 8 ký tự | |
| 38 | NGAY_BAT_DAU_DT_CTX | CTX_TREATMENT_BEGIN | 8 ký tự | |
| 39 | MA_XU_TRI | HIV_TREATMENT_CODE | | |
| 40 | NGAY_BAT_DAU_XU_TRI | ARV_TREATMEN_BEGIN | 8 ký tự | |
| 41 | NGAY_KET_THUC_XU_TRI | ARV_TREATMEN_END | 8 ký tự | |
| 42 | MA_PHAC_DO_DIEU_TRI | REGIMEN_HIV_CODE | | |
| 43 | MA_BAC_PHAC_DO | REGIMEN_LEVEL | | |
| 44 | SO_NGAY_CAP_THUOC_ARV | PRESCRIPTION_ARV_DAY | | |
| 45 | NGAY_CHUYEN_PHAC_DO | REGIMEN_HIV_DATE | 8 ký tự | |
| 46 | LY_DO_CHUYEN_PHAC_DO | REGIMEN_HIV_REASON | `long?` | |
| 47 | MA_CSKCB | vTreatment12.HEIN_MEDI_ORG_CODE | | |
| 48 | DU_PHONG | "" | | |

#### Key HIS_CONFIG
Không dùng key nào (`HisConfig` có trong InputADO nhưng bỏ không).

#### Điểm đáng ngờ
1. **Điều kiện sinh `HisHivTreatment.TREATMENT_ID != HisHivTreatment.ID`**: so khoá ngoại với khoá chính của chính bản ghi; nếu hai số tình cờ trùng thì hồ sơ HIV bị bỏ im lặng. Không rõ ý đồ.
2. **MA_CSKCB = `HEIN_MEDI_ORG_CODE`**: cần đối chiếu cột này trong V_HIS_TREATMENT_12 là mã cơ sở đang điều trị hay mã nơi ĐKBĐ trên thẻ.
3. Bốn trường `long?` (SANG_LOC_LAO, KQ_DTRI_LAO, GIAI_DOAN_LAM_SANG, LY_DO_CHUYEN_PHAC_DO) khi null: cách XmlSerializer xuất (bỏ phần tử hay `xsi:nil`) cần kiểm trên file thật – không thống nhất với các trường chuỗi khác (luôn có thẻ rỗng).
4. NOI_XN_KD là string thường, không lọc ký tự lỗi XML (khác NOI_LAY_MAU_XN/NOI_BDDT_ARV là CDATA).
5. MATINH/MAHUYEN/MAXA lấy thẳng mã HIS; nhánh IS_3176 không đổi (cần đối chiếu nếu cổng yêu cầu mã hành chính mới).
6. `SubMaxLength` có nhưng không dùng – DIA_CHI không cắt độ dài.

---

### XML7 – Chỉ tiêu dữ liệu giấy ra viện

Gốc XML: `CHI_TIEU_DU_LIEU_GIAY_RA_VIEN` (một bản ghi, không có danh sách).
Đầu vào: `InputXml7ADO` = `ConfigData`, `Treatment` (V_HIS_TREATMENT_12), `PatientTypeAlter` (V_HIS_PATIENT_TYPE_ALTER), `Employees`, `IS_3176`. XML7 không kế thừa XmlProcessorBase, không có HoSoProcessor; `XML7Data.ToXML()` dùng `StringWriter` mặc định.

#### Điều kiện sinh
`TDL_TREATMENT_TYPE_ID ∈ {2, 3, 4}` (ngoại trú, nội trú, ban ngày – **gồm cả ngoại trú mạn tính**). Khác → `null`.

#### Trường

| # | Trường | Nguồn HIS | Định dạng / ghi chú | IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | TREATMENT_CODE | | |
| 2 | SO_LUU_TRU | END_CODE, rỗng → `"0"` | | |
| 3 | MA_YTE | TDL_PATIENT_CODE | | |
| 4 | MA_KHOA_RV | END_DEPARTMENT_BHYT_CODE | | |
| 5 | NGAY_VAO | IN_TIME | 12 ký tự | |
| 6 | NGAY_RA | OUT_TIME | 12 ký tự, null → "" | |
| 7 | MA_DINH_CHI_THAI | IS_PREGNANCY_TERMINATION == 1 ? "1" : "0" | | |
| 8 | NGUYENNHAN_DINHCHI | PREGNANCY_TERMINATION_REASON | string, không lọc | |
| 9 | THOIGIAN_DINHCHI | PREGNANCY_TERMINATION_TIME | 12 ký tự | |
| 10 | TUOI_THAI | GESTATIONAL_AGE | `G27`, null → "" | |
| 11 | CHAN_DOAN_RV | ICD_NAME + (";" nếu ICD_NAME có) + ICD_TEXT | string, không cắt | |
| 12 | PP_DIEUTRI | TREATMENT_METHOD ?? "" | CDATA | TREATMENT_METHOD (không `?? ""`, kết quả như nhau vì CDATA đổi null → "") |
| 13 | GHI_CHU | ADVISE, > 1500 ký tự (CountVi) → `CountVi.SubStringVi(…,1500)` | string, không lọc ký tự | |
| 14 | MA_TTDV | Nếu config `MA_TTDV_FROM_PROFILE_XML` chứa `"7"`: `SOCIAL_INSURANCE_NUMBER` của `HOSP_SUBS_DIRECTOR_LOGINNAME ?? HOSPITAL_DIRECTOR_LOGINNAME` (login rỗng → REPRESENTATIVE_HEIN_CODE). Ngược lại: REPRESENTATIVE_HEIN_CODE | | **DIPLOMA** của `HOSP_SUBS_DIRECTOR ?? HOSPITAL_DIRECTOR`, bỏ qua config, không fallback |
| 15 | MA_BS | `SOCIAL_INSURANCE_NUMBER` của END_HEAD_LOGINNAME | | **DIPLOMA** của `END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? END_HEAD` |
| 16 | TEN_BS | TDL_USERNAME của END_HEAD_LOGINNAME (không thấy → "") | | TDL_USERNAME theo cùng chuỗi ưu tiên như MA_BS |
| 17 | NGAY_CT | OUT_TIME 8 ký tự | `yyyyMMdd` | |
| 18 | MA_CHA | FATHER_SOCIAL_INSURANCE_NUMBER | | |
| 19 | MA_ME | MOTHER_SOCIAL_INSURANCE_NUMBER | | |
| 20 | MA_THE_TAM | TDL_HEIN_CARD_NUMBER nếu `PatientTypeAlter.HAS_BIRTH_CERTIFICATE == "C"` | | |
| 21 | HO_TEN_CHA | FATHER_NAME; rỗng → TDL_PATIENT_RELATIVE_NAME nếu RELATIVE_TYPE (lower) ∈ {cha, bố, bo} | | |
| 22 | HO_TEN_ME | MOTHER_NAME; rỗng → RELATIVE_NAME nếu RELATIVE_TYPE ∈ {mẹ, me} | | |
| 23 | SO_NGAY_NGHI | `(OUTPATIENT_DATE_TO.Date − OUTPATIENT_DATE_FROM.Date).Days` khi có cả hai, không → 0 | `int` | |
| 24 | NGOAITRU_TUNGAY | OUTPATIENT_DATE_FROM 8 ký tự | | |
| 25 | NGOAITRU_DENNGAY | OUTPATIENT_DATE_TO 8 ký tự | | |
| 26 | DU_PHONG | "" | | |

#### Key HIS_CONFIG

| Key | Tác dụng |
|---|---|
| `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` | Danh sách số bảng ngăn `,`; chứa `"7"` → MA_TTDV lấy mã BHXH của (phó) giám đốc trên hồ sơ thay vì REPRESENTATIVE_HEIN_CODE. Bị bỏ qua khi IS_3176. |

#### Điểm đáng ngờ
1. **So khớp config không Trim**: `configData.Split(',').Contains("7")` → giá trị `"1, 7"` (có khoảng trắng) không khớp. XML8 có Trim, XML7 thì không.
2. **IS_3176 MA_TTDV không fallback**: hồ sơ không ghi giám đốc/phó giám đốc → MA_TTDV rỗng (QĐ130 thì rơi về REPRESENTATIVE_HEIN_CODE). Cũng bỏ qua config.
3. **IS_3176 dùng `??` chuỗi login**: login rỗng `""` (không null) sẽ chặn việc rơi xuống người kế tiếp.
4. MA_BS nhánh QĐ130 lấy `SOCIAL_INSURANCE_NUMBER` của `END_HEAD_LOGINNAME`, nhánh 3176 lấy `DIPLOMA` với chuỗi ưu tiên trưởng/phó khoa – hai nhánh khác cả nguồn người lẫn loại mã.
5. **SO_NGAY_NGHI không cộng 1**: nghỉ từ 01 đến 03 ra 2. Cần đối chiếu cách cổng tính.
6. CHAN_DOAN_RV: ICD_NAME có mà ICD_TEXT null → dư `";"` cuối. Không cắt độ dài, không lọc ký tự.
7. Trường string thường (CHAN_DOAN_RV, GHI_CHU, NGUYENNHAN_DINHCHI) không qua `RemoveXmlCharError` → ký tự điều khiển trong ADVISE/ICD_TEXT có thể làm serialize lỗi.
8. Sinh cho cả ngoại trú mạn tính (type 2, IS_CHRONIC = 1), trong khi XML8 loại nhóm này – hai bảng không đồng bộ phạm vi.
9. `XML7Data.ToXML()` dùng StringWriter → khai báo `encoding="utf-16"` và có namespace xsi/xsd, nếu nơi gọi dùng hàm này (không thấy nơi gọi trong DLL).
10. NGAY_CT dùng OUT_TIME, XML8 dùng OUT_DATE.

---

### XML8 – Chỉ tiêu dữ liệu tóm tắt hồ sơ bệnh án

Gốc XML: `CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN` (một bản ghi).
Đầu vào: `vTreatment12`, `HisBabys` (V_HIS_BABY), `HisPatientTypeAlters`, `HisConfig`, `IS_3176`, `Employees`, `ListSereServ`.

#### Điều kiện sinh
`TDL_TREATMENT_TYPE_ID == 3` **hoặc** `== 4` **hoặc** (`== 2` và `IS_CHRONIC` null/0). Khác → kết quả null, không có XML8.

#### MA_LOAI_KCB – bảng ánh xạ (QĐ130 và IS_3176 **giống hệt nhau**)

Mặc định `"10"`, rồi switch theo `TDL_TREATMENT_TYPE_ID`:

| TDL_TREATMENT_TYPE_ID | Điều kiện | MA_LOAI_KCB | Có chạy được? |
|---|---|---|---|
| 1 (khám) | — | 01 | **Không** – type 1 bị loại ở điều kiện sinh |
| 2 (ngoại trú) | IS_CHRONIC != 1 | 02 | Có |
| 2 | IS_CHRONIC == 1, có sere_serv với `TDL_SERVICE_REQ_TYPE_ID ∉ {1, 6, 14, 15}` | 08 | **Không** – mạn tính bị loại ở điều kiện sinh |
| 2 | IS_CHRONIC == 1, mọi sere_serv thuộc {1, 6, 14, 15} | 05 | **Không** |
| 2 | IS_CHRONIC == 1, ListSereServ rỗng | 10 (giữ mặc định) | **Không** |
| 3 (nội trú) | OUT_TIME và CLINICAL_IN_TIME có, OUT − CLINICAL_IN > 0, `DifferenceTime(CLINICAL_IN_TIME, OUT_TIME, HOUR) < 4` | 09 | Có |
| 3 | còn lại (kể cả CLINICAL_IN_TIME null) | 03 | Có |
| 4 (ban ngày) | — | 04 | Có |
| 5 | — | 06 | **Không** – type 5 bị loại |
| 6 | — | 07 | **Không** – type 6 bị loại |
| khác / null | — | 10 | **Không** |

Nhận xét kiểm tra:
- Mã **"09" (dưới 4 giờ) gắn với type 3 = nội trú**, không phải điều trị ban ngày; ban ngày (type 4) luôn ra "04" bất kể thời lượng. Nếu yêu cầu đơn vị là "ban ngày < 4h → 09" thì mã **không** làm vậy.
- Mốc tính 4 giờ là `CLINICAL_IN_TIME` (vào điều trị lâm sàng) → `OUT_TIME`, không phải `IN_TIME`. CLINICAL_IN_TIME null → "03". Cách làm tròn giờ phụ thuộc `Calculation.DifferenceTime` (thư viện ngoài, không có trong mã đã dịch).
- Trên thực tế XML8 chỉ có thể ra **02, 03, 04, 09**. Các nhánh 01/05/06/07/08/10 là mã chết do điều kiện sinh. Đặc biệt logic phân biệt 05/08 cho ngoại trú mạn tính không bao giờ chạy.
- Khối IS_3176 tính lại MA_LOAI_KCB bằng đúng logic cũ (chỉ viết lại dạng ternary) → thừa, không thay đổi kết quả.
- (Đối chiếu ngoài mã) Danh mục MA_LOAI_KCB thường gặp: 01 khám, 02 ĐT ngoại trú, 03 ĐT nội trú, 04 ĐT nội trú ban ngày, 05 ngoại trú mạn tính có khám + lĩnh thuốc, 06 lưu tại TYT/PKĐK, 07 nhận thuốc theo hẹn, 08 ngoại trú mạn tính có DVKT/thuốc, 09 nội trú dưới 4 giờ, 10 khác. Với danh mục này, ánh xạ 02/03/04/09 là hợp lý; vấn đề là phần mạn tính bị chặn trước.

#### Trường

| # | Trường | Nguồn HIS | Định dạng / ghi chú | IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | TREATMENT_CODE | | |
| 2 | MA_LOAI_KCB | bảng trên | | tính lại, như cũ |
| 3 | HO_TEN_CHA | FATHER_NAME; xem điểm ngờ 1 | string | |
| 4 | HO_TEN_ME | MOTHER_NAME; xem điểm ngờ 1 | string | |
| 5 | NGUOI_GIAM_HO | TDL_PATIENT_RELATIVE_NAME | luôn điền (kể cả người lớn) | |
| 6 | DON_VI | TDL_PATIENT_WORK_PLACE, rỗng → TDL_PATIENT_WORK_PLACE_NAME | | |
| 7 | NGAY_VAO | IN_TIME | 12 ký tự | |
| 8 | NGAY_RA | OUT_TIME | 12 ký tự, null → "" | |
| 9 | CHAN_DOAN_VAO | PROVISIONAL_DIAGNOSIS, rỗng → ICD_NAME | string, không cắt | |
| 10 | CHAN_DOAN_RV | ICD_NAME + ";" + ICD_TEXT | string, không cắt | |
| 11 | QT_BENHLY | CLINICAL_NOTE | string, không cắt, không lọc ký tự | |
| 12 | TOMTAT_KQ | SUBCLINICAL_RESULT; nếu `Normalize(FormKD).Length > 2000` → cắt **2000 byte UTF‑8** | CDATA | |
| 13 | PP_DIEUTRI | TREATMENT_METHOD | CDATA, không cắt | |
| 14 | NGAY_SINHCON | V_HIS_BABY đầu tiên có `BORN_RESULT_ID == 2`: BORN_TIME 8 ký tự | | |
| 15 | NGAY_CONCHET | cùng bé: DEATH_DATE 8 ký tự | | |
| 16 | SO_CONCHET | số bé có `BORN_RESULT_ID == 2` | chuỗi; không có → "" | |
| 17 | KET_QUA_DTRI | **ký tự cuối** của TREATMENT_RESULT_CODE | CDATA | |
| 18 | GHI_CHU | Tuổi (`Calculation.Age(DOB)`) < 16: FATHER_NAME → MOTHER_NAME → RELATIVE_NAME; ≥ 16: "" | | |
| 19 | MA_TTDV | Config `MA_TTDV_FROM_PROFILE_XML` chứa "8" (có Trim): SOCIAL_INSURANCE_NUMBER của HOSP_SUBS_DIRECTOR (ưu tiên, kiểm IsNullOrEmpty) hoặc HOSPITAL_DIRECTOR; không có login → REPRESENTATIVE_HEIN_CODE. Không có "8" → REPRESENTATIVE_HEIN_CODE | | **DIPLOMA** của `HOSP_SUBS_DIRECTOR ?? HOSPITAL_DIRECTOR`, bỏ qua config, không fallback |
| 20 | NGAY_CT | OUT_DATE 8 ký tự | | |
| 21 | MA_THE_TAM | Lấy PTA ưu tiên `PATIENT_TYPE_CODE == <mã BHYT>`, rồi LOG_TIME, ID giảm; `HAS_BIRTH_CERTIFICATE == "C"` → TDL_HEIN_CARD_NUMBER | | |
| 22 | DU_PHONG | "" | | |

#### Key HIS_CONFIG

| Key | Tác dụng |
|---|---|
| `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` | Chứa `"8"` → MA_TTDV lấy mã BHXH giám đốc/phó trên hồ sơ. Bỏ qua khi IS_3176. |
| `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | Mã đối tượng BHYT để ưu tiên PTA khi xét MA_THE_TAM. |
| `HIS.QD_130_BYT.THOI_GIAN_QT_OPTION` | Khai báo hằng nhưng **không dùng**. |

Lớp `TreatmentResultBhytCFG` (1..5, "06", "07") khai báo nhưng không dùng.

#### Điểm đáng ngờ
1. **HO_TEN_CHA / HO_TEN_ME – sai ưu tiên toán tử, ghi đè ngược**:
   `(!IsNullOrWhiteSpace(HoTenCha) && type == "cha") || type == "bố" || type == "bo"` → khi đã có FATHER_NAME và quan hệ là "cha" thì **ghi đè** bằng RELATIVE_NAME; khi FATHER_NAME rỗng và quan hệ "cha" thì **không** điền. Quan hệ "bố"/"bo" thì luôn ghi đè. Tương tự với "mẹ"/"me". XML7 làm đúng chiều (FATHER_NAME trước, rỗng mới lấy người thân) → hai bảng có thể ra tên cha/mẹ khác nhau.
2. **TOMTAT_KQ cắt theo byte**: `Encoding.UTF8.GetString(bytes, 0, 2000)` có thể cắt giữa ký tự tiếng Việt nhiều byte → sinh ký tự thay thế U+FFFD. Điều kiện kích hoạt lại đếm ký tự sau Normalize FormKD (khác thước đo với lúc cắt).
3. **MA_LOAI_KCB**: chỉ 02/03/04/09 có thể xảy ra; logic mạn tính 05/08 là mã chết (xem trên). "09" áp cho nội trú, không cho ban ngày; mốc giờ dùng CLINICAL_IN_TIME.
4. **IS_3176 MA_TTDV** = DIPLOMA, không fallback REPRESENTATIVE_HEIN_CODE, `??` không bắt chuỗi rỗng.
5. KET_QUA_DTRI lấy ký tự cuối mã kết quả HIS (ví dụ "06" → "6"); phụ thuộc mã HIS kết thúc bằng chữ số đúng nghĩa BHYT. Đặt trong CDATA cho một mã số.
6. NGAY_SINHCON chỉ điền khi có bé `BORN_RESULT_ID == 2` (theo ngữ cảnh SO_CONCHET là con chết – cần đối chiếu danh mục HIS); ca sinh bình thường để trống ngày sinh con. Nhiều bé chỉ lấy ngày của bé đầu tiên trong danh sách (không sắp xếp).
7. NGUOI_GIAM_HO luôn = RELATIVE_NAME, không phụ thuộc tuổi; GHI_CHU lại điền tên cha/mẹ cho trẻ < 16.
8. CHAN_DOAN_VAO, CHAN_DOAN_RV, QT_BENHLY, GHI_CHU là string thường: không cắt độ dài, không lọc ký tự lỗi. CHAN_DOAN_RV dư ";" khi ICD_TEXT null.
9. `HisPatientTypeAlters ... .First()` an toàn vì đã kiểm Count > 0.

---

### Tổng hợp key HIS_CONFIG (XML4–XML8)

| Key | Bảng | Dùng? |
|---|---|---|
| `HIS.QD_130_BYT.XML4.LAY_DVU_PTTT` | XML4 | Có (0/1/2) – đọc qua `HisConfigs.Get` toàn cục |
| `HIS.QD_130_BYT.XML4.MA_BS_DOC_KQ_OPTION` | XML4 | Không |
| `XML.EXPORT.4210.XML3.NGAY_YL_OPTION` | XML5 | Có – THOI_DIEM_DBLS nhánh khám |
| `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` | XML7 ("7", không Trim), XML8 ("8", có Trim) | Có – bỏ qua khi IS_3176 |
| `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | XML8 | Có – chọn PTA cho MA_THE_TAM |
| `HIS.QD_130_BYT.THOI_GIAN_QT_OPTION` | XML8 | Không |

### Tổng hợp khác biệt IS_3176

| Bảng | Trường | QĐ130 | IS_3176 |
|---|---|---|---|
| XML4 | — | — | không có cờ, không khác |
| XML5 | NGUOI_THUC_HIEN (chỉ nhánh khám) | DIPLOMA ?? "" | DIPLOMA ?? IDENTIFICATION_NUMBER |
| XML6 | SO_CCCD | CCCD | CCCD ?? CMND ?? PASSPORT |
| XML7 | MA_TTDV | config "7" → BHXH giám đốc, fallback REPRESENTATIVE_HEIN_CODE | DIPLOMA giám đốc, không fallback |
| XML7 | MA_BS / TEN_BS | BHXH / tên của END_HEAD | DIPLOMA / tên của END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? END_HEAD |
| XML7 | PP_DIEUTRI | TREATMENT_METHOD ?? "" | TREATMENT_METHOD (cùng kết quả) |
| XML8 | MA_TTDV | config "8" → BHXH giám đốc, fallback REPRESENTATIVE_HEIN_CODE | DIPLOMA giám đốc, không fallback |
| XML8 | MA_LOAI_KCB | bảng ánh xạ | tính lại y hệt (không khác) |

---

## Ánh xạ XML9–XML15 (QĐ130 / QĐ3176) — DLL His.Bhyt.ExportXml.XML130 (hisnguonmo, mã dịch ngược ILSpy)

Nguồn: `scratchpad/xml130/His.Bhyt.ExportXml.XML130.XML9` … `XML15`. Chỉ ghi những gì thấy trong mã.

### 0. Quy ước chung (áp dụng cho mọi bảng)

- **Định dạng thời gian HIS**: số `yyyyMMddHHmmss` (long). Mã dùng `X.ToString().Substring(0, 8)` → `yyyyMMdd`; `Substring(0, 12)` → `yyyyMMddHHmm`. Không có chuyển đổi múi giờ/định dạng nào khác.
- **CDATA**: trường kiểu `XmlCDataSection` được tạo qua `ConvertStringToXmlDocument(s)` = `CreateCDataSection(RemoveXmlCharError(s))` — bỏ ký tự không hợp lệ XML, null → `""` (CDATA rỗng, phần tử vẫn xuất hiện).
- **Phần tử null**: thuộc tính `string`/`XmlCDataSection` bằng `null` → `XmlSerializer` **bỏ hẳn phần tử** (không có thẻ rỗng). Đây là cơ chế các nhánh IS_3176 dùng để "xoá" trường (gán null).
- **Serialize**: UTF-8, có XML declaration, `Indent = true`, namespace rỗng.
- **Giới tính** (XML9, 13, 14): `GENDER_ID == 1 → "2"`, `== 2 → "1"`, khác → `"3"`.
- **`MAX_LENGTH`** = `GlobalConfigStore.MAX_LENGTH = 1024` (static). `SubMaxLength` mỗi bảng cài khác nhau (xem từng bảng); chỉ XML13 thực sự gọi.
- **Hai kiểu tra nhân viên** (theo `LOGINNAME` trong `List<HIS_EMPLOYEE>`):
  - `GetMaTTDV` → `HIS_EMPLOYEE.SOCIAL_INSURANCE_NUMBER` (mã BHXH).
  - `GetMaBacSi` → `HIS_EMPLOYEE.DIPLOMA` (số CCHN).
- **Mẫu MA_TTDV chung** (QĐ130, XML9/10/11/13/14): nếu key `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` chứa số hiệu bảng → lấy `SOCIAL_INSURANCE_NUMBER` của `HOSP_SUBS_DIRECTOR_LOGINNAME` (ưu tiên) hoặc `HOSPITAL_DIRECTOR_LOGINNAME`; ngược lại (hoặc không có login) → `V_HIS_TREATMENT_12.REPRESENTATIVE_HEIN_CODE`.
  **Khi IS_3176** (XML9/10/11/13/14): ghi đè vô điều kiện `MA_TTDV = GetMaBacSi(HOSP_SUBS_DIRECTOR_LOGINNAME ?? HOSPITAL_DIRECTOR_LOGINNAME)` → **DIPLOMA (CCHN)** của thủ trưởng/người được uỷ quyền; bỏ qua key cấu hình và `REPRESENTATIVE_HEIN_CODE`. Dùng `??` nên login là chuỗi rỗng `""` sẽ không rơi sang `HOSPITAL_DIRECTOR_LOGINNAME`.
- **Không có ghép nhiều mã bác sĩ** (`;`) trong XML9–15; MA_BS/MA_BAC_SI luôn là một mã. Ghép `;` duy nhất kiểu này là `CHUCDANH_NGUOI_HT` (XML13).
- **Hàm đọc ngược** (`Get*DetailData(xmlString)` / `ConvertValue`): đổi ngày về `dd/MM/yyyy` để hiển thị; không ảnh hưởng file xuất.
- **`ConfigHeinMediOrg.cs`** (danh mục cơ sở KCB nhúng cứng, 4,6MB): chỉ được nạp qua `GlobalConfigStore.HisHeinMediOrg` (lazy getter gọi `ConfigHeinMediOrg.GetMediOrgConst()` khi danh sách static rỗng). Trong phạm vi XML9–15: **chỉ XML14** đọc (lấy `LEVEL_CODE` của nơi ĐKBĐ để tính `MA_DOITUONG_KCB`); XML15 có khai báo `HisHeinMediOrg` nhưng **không dùng**. Ngoài phạm vi: XML1 (`Xml1Processor.cs:244/249`) dùng y hệt.

#### Key HIS_CONFIG tổng hợp

| Key | Bảng dùng | Tác dụng |
|---|---|---|
| `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` | 9, 10, 11, 13, 14 | Danh sách số bảng (phân cách `,`). Có số bảng → MA_TTDV lấy mã BHXH thủ trưởng thay vì `REPRESENTATIVE_HEIN_CODE`. XML13 kiểm bằng `Contains("13")` (chuỗi con), các bảng khác tách `,` rồi so khớp. Bị ghi đè khi IS_3176. |
| `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT` | 11, 13, 14 | Mã đối tượng BHYT; dùng để ưu tiên dòng `V_HIS_PATIENT_TYPE_ALTER` có đối tượng BHYT khi chọn PTA "đại diện". |
| `HIS.QD_130_BYT.SORT_MA_THE_BHYT` | 13, 14 | `"1"` → sắp danh sách thẻ theo `LOG_TIME desc, ID desc`; khác → theo `HEIN_CARD_FROM_TIME, HEIN_CARD_TO_TIME` tăng dần. Ảnh hưởng thứ tự `MA_THE_BHYT`/`GT_THE_DEN`. |
| `HIS.QD_130_BYT.SO_CHUYENTUYEN_FORMAT` | 13 | Mẫu số chuyển tuyến; placeholder `<#OUT_CODE;>`, `<#OUT_MONTH;>`, `<#OUT_YEAR;>`, `<#OUT_YEAR_SHORT;>`. Rỗng → `SO_CHUYENTUYEN = "."`. |
| `HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_FORMAT` | 13 | Mẫu số giấy chuyển tuyến; như trên + `<#OUT_CODE_NUM_xxx;>` (đệm số 0). Rỗng → `OUT_CODE`. |
| `HIS.QD_130_BYT.XML1.NGAY_VAO_NOI_TRU_OPTION` | 14 | `"1"` và `TDL_TREATMENT_TYPE_ID == 2` → `NGAY_VAO_NOI_TRU = ""`. |
| `HIS.QD_130_BYT.THOI_GIAN_QT_OPTION` | (khai báo ở 10, 11, 14) | **Không dùng** ở đâu. |
| `KEY_BHYT` ở XML10 | — | Khai báo nhưng **không dùng**. |

XML12 và XML15: không đọc key nào (XML12 có `Configs` trong InputADO nhưng không dùng; XML15 không có `Configs`).

---

### 1. XML9 — Giấy chứng sinh (`CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH / DSACH_GIAYCHUNGSINH / DU_LIEU_GIAY_CHUNG_SINH*`)

**Input**: `vTreatment` (V_HIS_TREATMENT_12 — hồ sơ mẹ), `ListBaby` (List<V_HIS_BABY>), `Configs`, `Employee`, `IS_3176`.

**Điều kiện sinh**: `ListBaby` khác rỗng; mỗi `V_HIS_BABY` có `TREATMENT_ID == vTreatment.ID` → một dòng `DU_LIEU_GIAY_CHUNG_SINH`. Danh sách rỗng → `GetXml9String` trả null (không xuất). Không lọc theo trạng thái/đã cấp giấy.

| # | Trường | Nguồn HIS | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `vTreatment.TREATMENT_CODE` | | |
| 2 | MA_BHXH_NND | `vTreatment.TDL_SOCIAL_INSURANCE_NUMBER`; rỗng → 10 ký tự cuối `vTreatment.TDL_HEIN_CARD_NUMBER`; rỗng → "" | | |
| 3 | MA_THE_NND | `baby.TDL_HEIN_CARD_NUMBER` | | |
| 4 | HO_TEN_NND | `baby.TDL_PATIENT_NAME` — CDATA | | |
| 5 | NGAYSINH_NND | `baby.TDL_PATIENT_DOB` → `yyyyMMdd` | | |
| 6 | MA_DANTOC_NND | `baby.TDL_PATIENT_ETHNIC_CODE` | | |
| 7 | SO_CCCD_NND | `TDL_PATIENT_CCCD_NUMBER` → `CMND_NUMBER` → `PASSPORT_NUMBER` (IsNullOrEmpty) | | |
| 8 | NGAYCAP_CCCD_NND | `CCCD_DATE` → `CMND_DATE` → `PASSPORT_DATE`, `yyyyMMdd` | Chọn theo ngày có giá trị, **độc lập** với loại giấy ở #7 | |
| 9 | NOICAP_CCCD_NND | `CCCD_PLACE` → `CMND_PLACE` → `PASSPORT_PLACE` — CDATA | Như #8 | |
| 10 | NOI_CU_TRU_NND | `baby.TDL_PATIENT_ADDRESS` — CDATA | | |
| 11 | MA_QUOCTICH | `vTreatment.TDL_PATIENT_MPS_NATIONAL_CODE` | | |
| 12 | MATINH_CU_TRU | `baby.TDL_PATIENT_PROVINCE_CODE` | | |
| 13 | MAHUYEN_CU_TRU | `baby.TDL_PATIENT_DISTRICT_CODE` | | |
| 14 | MAXA_CU_TRU | `baby.TDL_PATIENT_COMMUNE_CODE` | | |
| 15 | HO_TEN_CHA | `baby.FATHER_NAME` — CDATA | | |
| 16 | MA_THE_TAM | `baby.HEIN_CARD_NUMBER_TMP` | | |
| 17 | HO_TEN_CON | `baby.BABY_NAME` — CDATA | | |
| 18 | GIOI_TINH_CON | `baby.GENDER_ID` 1→"2", 2→"1", khác→"3" | | |
| 19 | SO_CON | `NUMBER_CHILDREN_BIRTH` | | |
| 20 | LAN_SINH | `NUMBER_OF_BIRTH` | | |
| 21 | SO_CON_SONG | `CURRENT_ALIVE` | | |
| 22 | CAN_NANG_CON | `(long)WEIGHT` (cắt phần thập phân) | | |
| 23 | NGAY_SINH_CON | `BORN_TIME` → `yyyyMMddHHmm` | | |
| 24 | NOI_SINH_CON | Theo `BIRTHPLACE_TYPE`: 1 → `BIRTH_HOSPITAL_NAME`; 2 → BV + `BIRTHPLACE, xã, huyện, tỉnh`; 3 → "Sinh tại nhà, …"; 4 → "Đẻ trên đường đi, …"; 5 → "Trẻ bị bỏ rơi, …"; dọn `", , "` và dấu phẩy cuối — CDATA | | |
| 25 | TINH_TRANG_CON | `BORN_RESULT_NAME` — CDATA | | |
| 26 | SINHCON_PHAUTHUAT | `IS_SURGERY.ToString()`, null → "0" | | |
| 27 | SINHCON_DUOI32TUAN | `WEEK_COUNT < 32` → "1", khác "0" | | |
| 28 | GHI_CHU | `NOTE` — CDATA | | |
| 29 | NGUOI_DO_DE | `MIDWIFE` — CDATA | | |
| 30 | NGUOI_GHI_PHIEU | `ISSUER_USERNAME` — CDATA | | |
| 31 | NGAY_CT | Gán `ISSUED_DATE` rồi **bị ghi đè** bằng `vTreatment.IN_DATE` → `yyyyMMdd` | | |
| 32 | SO | `BIRTH_CERT_NUM` | | |
| 33 | QUYEN_SO | `BIRTH_CERT_BOOK_NAME` — CDATA | | |
| 34 | MA_TTDV | Mẫu chung, số bảng `"9"` (SOCIAL_INSURANCE_NUMBER / REPRESENTATIVE_HEIN_CODE) | `MA_TTDV_FROM_PROFILE_XML` | DIPLOMA của thủ trưởng |
| 35 | DU_PHONG | "" | | |

**Điểm đáng ngờ XML9**
1. **NGAY_CT bị ghi đè**: dòng `xml9ADO.NgayCT = item.ISSUED_DATE…` rồi sau đó `xml9ADO.NgayCT = data.vTreatment.IN_DATE…` → ngày cấp giấy chứng sinh thực tế bị bỏ, luôn là **ngày vào viện của mẹ**.
2. `TDL_HEIN_CARD_NUMBER.Substring(Length - 10, 10)` ném lỗi nếu mã thẻ < 10 ký tự → `catch` → `ResultADO(false)` → **mất cả XML9** của hồ sơ.
3. `MapADOToXml`: `if (listAdo == null && listAdo.Count <= 0)` — điều kiện sai (`&&` thay `||`), listAdo null sẽ NRE (bị catch). Thực tế listAdo không null nên ít ảnh hưởng.
4. Ngày/nơi cấp (#8, #9) chọn theo "trường nào có giá trị" chứ không theo loại giấy đã chọn ở #7 → có thể ra số CMND kèm ngày cấp CCCD.
5. IS_3176 chỉ khác ở MA_TTDV.

---

### 2. XML10 — Giấy nghỉ dưỡng thai (`CHI_TIEU_DU_LIEU_GIAY_NGHI_DUONG_THAI`, một bản ghi phẳng)

**Input**: `vTreatment`, `Employee`, `Configs`, `IS_3176`.

**Điều kiện sinh**: `vTreatment.TREATMENT_END_TYPE_EXT_ID == 2`. Ngược lại `GenerateXml10ADO` trả null → không xuất.

| # | Trường | Nguồn HIS | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE` | | |
| 2 | SO_SERI | `EXTRA_END_CODE` | | |
| 3 | SO_CT | `EXTRA_END_CODE` (trùng SO_SERI) | | |
| 4 | SO_NGAY | `(int)Math.Truncate(SICK_LEAVE_DAY)`; null → **null (bỏ phần tử)** | | |
| 5 | DON_VI | `TDL_PATIENT_WORK_PLACE` (IsNullOrWhiteSpace) → `TDL_PATIENT_WORK_PLACE_NAME` — CDATA | | |
| 6 | CHAN_DOAN_RV | `ICD_NAME` + (`";"` nếu ICD_NAME có) + `ICD_TEXT` — CDATA | | **Gán cứng `"dưỡng thai"`** |
| 7 | TU_NGAY | `SICK_LEAVE_FROM` → `yyyyMMdd` | | |
| 8 | DEN_NGAY | `SICK_LEAVE_TO` → `yyyyMMdd` | | |
| 9 | MA_TTDV | Mẫu chung, số bảng `"10"` | `MA_TTDV_FROM_PROFILE_XML` | DIPLOMA thủ trưởng |
| 10 | TEN_BS | `SICK_USERNAME` — CDATA | | (không đổi) |
| 11 | MA_BS | `SOCIAL_INSURANCE_NUMBER` của nhân viên `LOGINNAME == SICK_LOGINNAME` | | `DIPLOMA` của `END_DEPT_SUBS_HEAD_LOGINNAME ?? END_DEPARTMENT_HEAD_LOGINNAME ?? SICK_LOGINNAME` |
| 12 | NGAY_CT | `OUT_DATE` → `yyyyMMdd` | | |
| 13 | DU_PHONG | "" | | |

**Điểm đáng ngờ XML10**
1. **IS_3176 gán cứng `ChanDoanRV = "dưỡng thai"`** (xác minh: `Xml10Processor.cs`, khối `if (data.IS_3176)` cuối hàm). Chẩn đoán ICD_NAME/ICD_TEXT tính ở trên bị **vứt bỏ hoàn toàn**; mọi giấy nghỉ dưỡng thai theo 3176 đều có CHAN_DOAN_RV đúng chữ "dưỡng thai", không có mã/tên bệnh.
2. **IS_3176: MA_BS và TEN_BS có thể là hai người khác nhau**: MA_BS ưu tiên phó/trưởng khoa ra viện, chỉ rơi về `SICK_LOGINNAME` khi cả hai null; TEN_BS vẫn là `SICK_USERNAME`.
3. QĐ130: MA_BS = mã BHXH (`SOCIAL_INSURANCE_NUMBER`) của bác sĩ, không phải CCHN.
4. `o.LOGINNAME.Equals(...)` → NRE nếu có nhân viên `LOGINNAME` null → catch → **mất XML10**.
5. `SubMaxLength` có nhưng không gọi; `THOI_GIAN_QT_OPTION`, `KEY_BHYT` khai báo không dùng.
6. SO_SERI và SO_CT cùng nguồn `EXTRA_END_CODE`.

---

### 3. XML11 — Giấy chứng nhận nghỉ việc hưởng BHXH, mẫu CT07 (`CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI`)

**Input**: `vTreatment`, `Employee`, `Configs`, `patientTypeAlters` (V_HIS_PATIENT_TYPE_ALTER), `IS_3176`.

**Điều kiện sinh**: `vTreatment.TREATMENT_END_TYPE_EXT_ID == 1`.

| # | Trường | Nguồn HIS | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE` | | |
| 2 | SO_SERI | `EXTRA_END_CODE` (không `??` → null thì bỏ phần tử) | | |
| 3 | SO_CT | `EXTRA_END_CODE` | | |
| 4 | SO_KCB | `TREATMENT_CODE` | | |
| 5 | DON_VI | `TDL_PATIENT_WORK_PLACE` (chỉ kiểm `!= null`) → `TDL_PATIENT_WORK_PLACE_NAME` — CDATA | | |
| 6 | MA_BHXH | `TDL_SOCIAL_INSURANCE_NUMBER` → 10 ký tự cuối `SICK_HEIN_CARD_NUMBER` → 10 ký tự cuối `TDL_HEIN_CARD_NUMBER` | | |
| 7 | MA_THE_BHYT | `SICK_HEIN_CARD_NUMBER` → `TDL_HEIN_CARD_NUMBER` | | |
| 8 | CHAN_DOAN_RV | `ICD_CODE` + `":"` + `ICD_NAME`; nếu có ICD_SUB_CODE hoặc ICD_TEXT thêm `";"`; nếu có **cả hai** thì ghép cặp `sub[i]:text[i]` nối `;` — CDATA | | |
| 9 | PP_DIEUTRI | `TREATMENT_METHOD` — CDATA | | |
| 10 | MA_DINH_CHI_THAI | `IS_PREGNANCY_TERMINATION`, null → "0" | | |
| 11 | NGUYENNHAN_DINHCHI | `PREGNANCY_TERMINATION_REASON` — CDATA | | |
| 12 | TUOI_THAI | `GESTATIONAL_AGE.ToString()` (null → "") | | |
| 13 | SO_NGAY_NGHI | `(SICK_LEAVE_TO.Date - SICK_LEAVE_FROM.Date).TotalDays + 1` | Phụ thuộc `TimeNumberToSystemDateTime` trả null khi thiếu | |
| 14 | TU_NGAY | `SICK_LEAVE_FROM` → `yyyyMMdd` | | |
| 15 | DEN_NGAY | `SICK_LEAVE_TO` → `yyyyMMdd` | | |
| 16 | HO_TEN_CHA | `FATHER_NAME` → nếu `TDL_PATIENT_RELATIVE_TYPE.ToLower()` ∈ {cha, bố, bo} thì `TDL_PATIENT_RELATIVE_NAME` — CDATA | | |
| 17 | HO_TEN_ME | `MOTHER_NAME` → nếu relative type ∈ {mẹ, me} thì `TDL_PATIENT_RELATIVE_NAME` — CDATA | | |
| 18 | MA_TTDV | Mẫu chung, số bảng `"11"` (kiểm login `!= null`) | `MA_TTDV_FROM_PROFILE_XML` | DIPLOMA thủ trưởng |
| 19 | MA_BS | `SOCIAL_INSURANCE_NUMBER` của `SICK_LOGINNAME` | | `DIPLOMA` của `END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? SICK_LOGINNAME` |
| 20 | NGAY_CT | `vTreatment.IN_DATE` → `yyyyMMdd` | | |
| 21 | MA_THE_TAM | PTA chọn: `TREATMENT_ID == treatment`, sắp (`PATIENT_TYPE_CODE == KEY_BHYT` desc, `LOG_TIME` desc, `ID` desc) `.First()`; nếu `HAS_BIRTH_CERTIFICATE == "C"` → `TDL_HEIN_CARD_NUMBER`, khác "" | `PATIENT_TYPE_CODE.BHYT` | |
| 22 | MAU_SO | Gán cứng `"CT07"` | | |
| 23 | DU_PHONG | "" | | |

**Điểm đáng ngờ XML11**
1. **NGAY_CT = IN_DATE (ngày vào viện)**, không phải ngày cấp/ra viện.
2. Ghép ICD phụ: `array2[i]` với `i` chạy theo số mã phụ → nếu ICD_TEXT có ít phần tử hơn ICD_SUB_CODE thì `IndexOutOfRangeException` → **mất XML11**. Nếu ICD_TEXT nhiều hơn thì phần dư bị mất. Cắt `;` đầu/cuối dùng `else if` nên chuỗi có `;` cả hai đầu chỉ cắt một đầu.
3. Chỉ có một trong hai (ICD_SUB_CODE hoặc ICD_TEXT) → vẫn thêm `";"` nhưng không thêm nội dung → CHAN_DOAN_RV kết thúc bằng `;`.
4. MA_THE_TAM: `.First()` ném lỗi nếu danh sách PTA không có dòng nào thuộc treatment → mất XML11 (`val2 != null` phía sau là mã chết).
5. `Substring(Length - 10, 10)` trên mã thẻ < 10 ký tự → mất XML11. `LOGINNAME.Equals` → NRE như XML10.
6. IS_3176: MA_BS ưu tiên trưởng khoa ra viện, không phải bác sĩ ký giấy (`SICK_LOGINNAME`).
7. `GetMaTTDV` ở XML11 trả `SOCIAL_INSURANCE_NUMBER` không `?? ""` → có thể null → bỏ phần tử MA_TTDV.

---

### 4. XML12 — Giám định y khoa (`CHI_TIEU_GIAM_DINH_YKHOA / DSACH_GIAM_DINH_Y_KHOA / GIAM_DINH_Y_KHOA*` + `CHUKYDONVI`)

**Input**: `vTreatment`, `vMedicalAssessment` (List<V_HIS_MEDICAL_ASSESSMENT>), `Configs` (không dùng). **Không có `IS_3176`** → xuất 130 và 3176 giống hệt.

**Điều kiện sinh**: có `V_HIS_MEDICAL_ASSESSMENT` với `TREATMENT_ID == vTreatment.ID`; chỉ lấy **bản đầu tiên** (`FirstOrDefault`) → tối đa 1 dòng.

| # | Trường | Nguồn HIS | Ghi chú |
|---|---|---|---|
| 1 | NGUOI_CHU_TRI | `ma.PRESIDENT_USERNAME` — CDATA | |
| 2 | CHUC_VU | `ON_BEHALF_USERNAME != null` → "2"; else `PRESIDENT_USERNAME != null` → "1"; else "0" | |
| 3 | NGAY_HOP | `ASSESSMENT_TIME_FROM` → `yyyyMMdd`; không đổi được → **`DateTime.Now`** | |
| 4 | HO_TEN | `vTreatment.TDL_PATIENT_NAME` — CDATA | |
| 5 | NGAY_SINH | `TDL_PATIENT_IS_HAS_NOT_DAY_DOB == 1` → `yyyy` + `"00000000"` (12 ký tự); khác → `yyyyMMdd` (8 ký tự) | |
| 6 | SO_CCCD | CCCD → CMND → PASSPORT | |
| 7 | NGAY_CAP_CCCD | Theo loại giấy đã chọn ở #6, `yyyyMMdd` | |
| 8 | NOI_CAP_CCCD | Theo loại giấy — CDATA | |
| 9 | DIA_CHI | `TDL_PATIENT_ADDRESS` — CDATA | |
| 10–12 | MATINH/MAHUYEN/MAXA_CU_TRU | `TDL_PATIENT_PROVINCE/DISTRICT/COMMUNE_CODE` | |
| 13 | MA_BHXH | `TDL_SOCIAL_INSURANCE_NUMBER ?? 10 ký tự cuối TDL_HEIN_CARD_NUMBER` | `??` — chuỗi rỗng không rơi xuống |
| 14 | MA_THE_BHYT | `TDL_HEIN_CARD_NUMBER` | |
| 15 | NGHE_NGHIEP | `TDL_PATIENT_CAREER_NAME` — CDATA | |
| 16 | DIEN_THOAI | `TDL_PATIENT_MOBILE ?? TDL_PATIENT_PHONE` | |
| 17 | MA_DOI_TUONG | `ma.ASSESSMENT_OBJECT_CODE` | |
| 18 | KHAM_GIAM_DINH | `ma.ASSESSMENT_TYPE_ID` (ID thô) | |
| 19 | SO_BIEN_BAN | `ma.REPORT_NUMBER` | |
| 20 | TYLE_TTCT_CU | `PREVIOUS_INJURY_RATE * 100` → `decimal.ToString()` | |
| 21 | DANG_HUONG_CHE_DO | `ma.WELFARE_TYPE_IDS` (chuỗi ID) | |
| 22 | NGAY_CHUNG_TU | `ASSESSMENT_TIME_FROM` → **`ddMMyyyy`**; lỗi → `DateTime.Now` | |
| 23 | SO_GIAY_GIOI_THIEU | `ma.REFERRAL_CODE` (không `??` → null bỏ phần tử) | |
| 24 | NGAY_DE_NGHI | `ma.REQUEST_TIME` → `yyyyMMdd` | |
| 25 | MA_DONVI | `ma.REQUEST_ORG_CODE` | |
| 26 | GIOI_THIEU_CUA | `ma.REQUEST_ORG_NAME` — CDATA | |
| 27 | KET_QUA_KHAM | `ma.EXAMINATION_RESULT` — CDATA | |
| 28 | SO_VAN_BAN_CAN_CU | `ma.LEGAL_GROUND_NUMBERS` | |
| 29 | TYLE_TTCT_MOI | `INJURY_RATE * 100` | |
| 30 | TONG_TYLE_TTCT | `INJURY_RATE_TOTAL * 100` | |
| 31 | DANG_KHUYETTAT | `DISABILITY_TYPE_ID` (ID thô) | |
| 32 | MUC_DO_KHUYETTAT | `DISABILITY_STATUS_ID` (ID thô) | |
| 33 | DE_NGHI | `REQUEST_AFTER_ASSESSMENT` — CDATA | |
| 34 | DUOC_XACDINH | `CONCLUSION` — CDATA | |
| 35 | DU_PHONG | "" | |
| — | CHUKYDONVI (cấp gốc) | `GlobalConfigStore.Signature ?? ""` | |

**Điểm đáng ngờ XML12**
1. **NGAY_HOP / NGAY_CHUNG_TU tự lấy ngày hiện tại** khi `ASSESSMENT_TIME_FROM` không đổi được → dữ liệu sai lặng lẽ.
2. **NGAY_CHUNG_TU dùng `ddMMyyyy`**, trong khi mọi ngày khác trong file (và trong các bảng khác) là `yyyyMMdd`.
3. **NGAY_SINH độ dài không nhất quán**: thiếu ngày → 12 ký tự `yyyy00000000`; đủ ngày → 8 ký tự `yyyyMMdd` (XML13/14 dùng 12 ký tự `yyyyMMddHHmm`).
4. Tỷ lệ `*100` rồi `decimal.ToString()` theo **culture hiện tại** (có thể ra dấu thập phân `,`, có đuôi `.00`); base có helper `ToString(decimal)` invariant nhưng không dùng.
5. Nhiều trường xuất **ID nội bộ HIS** (`ASSESSMENT_TYPE_ID`, `DISABILITY_TYPE_ID`, `DISABILITY_STATUS_ID`, `WELFARE_TYPE_IDS`) — đúng mã danh mục BHXH hay không phụ thuộc dữ liệu ID.
6. Chỉ lấy biên bản giám định đầu tiên của hồ sơ.
7. Không có nhánh IS_3176.
8. `MapADOToXml` có cùng lỗi `listAdo == null && listAdo.Count <= 0` như XML9.

---

### 5. XML13 — Giấy chuyển tuyến (`CHI_TIEU_GIAYCHUYENTUYEN`, một bản ghi phẳng)

**Input**: `vTreatment`, `vSereServ2` (V_HIS_SERE_SERV_2), `PatientTypeAlter` (list), `Employee`, `Configs`, `IS_3176`.

**Điều kiện sinh**: **không có điều kiện trong DLL** (không kiểm loại ra viện là chuyển tuyến); `GetXML13String` luôn serialize nếu hàm sinh không ném lỗi. Bên gọi tự quyết định có gọi hay không.

| # | Trường | Nguồn HIS | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE` | | |
| 2 | SO_HOSO | `TREATMENT_CODE` | | |
| 3 | SO_CHUYENTUYEN | Mẫu cấu hình thay `OUT_CODE`, tháng/năm/năm 2 số từ `OUT_TIME`; rỗng → `"."` | `SO_CHUYENTUYEN_FORMAT` | |
| 4 | GIAY_CHUYEN_TUYEN | Mẫu cấu hình (như trên) + `<#OUT_CODE_NUM_xxx;>` → `int.Parse(OUT_CODE.Substring(0,6))` đệm 0 theo số ký tự `xxx`; rỗng → `OUT_CODE` | `GIAY_CHUYEN_TUYEN_FORMAT` | |
| 5 | MA_CSKCB | `HEIN_MEDI_ORG_CODE` | | |
| 6 | MA_NOI_DI | `TRANSFER_IN_MEDI_ORG_CODE` | | **null (bỏ phần tử)** |
| 7 | MA_NOI_DEN | `MEDI_ORG_CODE` | | **null (bỏ phần tử)** |
| 8 | HO_TEN | `TDL_PATIENT_NAME` — CDATA | | |
| 9 | NGAY_SINH | Thiếu ngày → `yyyy00000000`; khác → `yyyyMMddHHmm` | | |
| 10 | GIOI_TINH | 1→"2", 2→"1", khác "3" | | |
| 11 | MA_QUOCTICH | `TDL_PATIENT_MPS_NATIONAL_CODE` | | |
| 12 | MA_DANTOC | `ETHNIC_CODE` | | |
| 13 | MA_NGHE_NGHIEP | `TDL_PATIENT_CAREER_CODE` | | Cắt **2 ký tự đầu** |
| 14 | DIA_CHI | `TDL_PATIENT_ADDRESS` — CDATA | | |
| 15 | MA_THE_BHYT | PTA (toàn bộ list, **không lọc TREATMENT_ID**), sắp theo `SORT_MA_THE_BHYT`, group (card, nơi ĐKBĐ, from, to) lấy bản đầu; nối `;` các `HEIN_CARD_NUMBER` không rỗng | `SORT_MA_THE_BHYT` | |
| 16 | GT_THE_DEN | Cùng danh sách, `HEIN_CARD_TO_TIME` → `yyyyMMdd` nối `;` | | |
| 17 | NGAY_VAO | `IN_TIME` → `yyyyMMddHHmm` | | |
| 18 | NGAY_VAO_NOI_TRU | `CLINICAL_IN_TIME` → `yyyyMMddHHmm` | | |
| 19 | NGAY_RA | `OUT_TIME` → `yyyyMMddHHmm` | | |
| 20 | DAU_HIEU_LS | `CLINICAL_SIGNS ?? CLINICAL_NOTE` — CDATA | | |
| 21 | CHAN_DOAN_RV | `ICD_NAME + ";" + ICD_TEXT` (luôn có `;`) — CDATA | | |
| 22 | QT_BENHLY | `CLINICAL_NOTE` — CDATA | | |
| 23 | TOMTAT_KQ | `SUBCLINICAL_RESULT`; nếu độ dài (FormKD) > 2000 ký tự → cắt **4000 byte UTF-8** — CDATA | | |
| 24 | PP_DIEUTRI | `TREATMENT_METHOD` — CDATA | | |
| 25 | MA_BENH_CHINH | `ICD_CODE` | | |
| 26 | MA_BENH_KT | `ICD_SUB_CODE` tách `;`, tối đa **12** mã | | |
| 27 | MA_BENH_YHCT | `TRADITIONAL_ICD_CODE` + `TRADITIONAL_ICD_SUB_CODE`, distinct, loại mã trùng với `ICD_CODE` | | |
| 28 | TEN_DICH_VU | `TDL_HEIN_SERVICE_BHYT_NAME` của SS có `TDL_HEIN_SERVICE_TYPE_ID` ∈ {3,4,19,20,1,5,8,2,7,18,13,22}, sắp `INTRUCTION_TIME`, nối `;`, `SubMaxLength` (= `CountVi.SubStringVi(…,1024)`) — CDATA | | **null (bỏ phần tử)** |
| 29 | TEN_THUOC | Như trên với loại {9,11,10,12} — CDATA | | **null (bỏ phần tử)** |
| 30 | PP_DIEU_TRI | `TREATMENT_METHOD` (chỉ gán khi khác rỗng, trùng #24) — CDATA | | **null (bỏ phần tử)** |
| 31 | MA_LOAI_RV | Mặc định "1"; `TREATMENT_END_TYPE_ID` 7→"3", 8→"4", 2 & `TRAN_PATI_REASON_ID`=1→"2", 2 & reason=2→"5" | | |
| 32 | MA_LYDO_CT | `TRAN_PATI_REASON_ID` (ID thô) | | |
| 33 | HUONG_DIEU_TRI | `TREATMENT_DIRECTION` — CDATA | | |
| 34 | PHUONGTIEN_VC | `TRANSPORT_VEHICLE` — CDATA | | |
| 35 | HOTEN_NGUOI_HT | `TRANSPORTER` — CDATA | | |
| 36 | CHUCDANH_NGUOI_HT | `TITLE` của các nhân viên có `LOGINNAME` ∈ `TRANSPORTER_LOGINNAMES` (tách `;`), nối `;` | | |
| 37 | MA_BAC_SI | `DIPLOMA` của `DOCTOR_LOGINNAME`; nếu DOCTOR_LOGINNAME rỗng → `END_LOGINNAME` | | |
| 38 | MA_TTDV | `REPRESENTATIVE_HEIN_CODE`; nếu key chứa chuỗi `"13"` và có login thủ trưởng → `SOCIAL_INSURANCE_NUMBER` (không có → "") | `MA_TTDV_FROM_PROFILE_XML` | DIPLOMA thủ trưởng |
| 39 | DU_PHONG | "" | | |
| 40 | MA_CSKCB_DI | (null ở 130) | | `TRANSFER_IN_MEDI_ORG_CODE` |
| 41 | MA_CSKCB_DEN | (null ở 130) | | `MEDI_ORG_CODE` |
| 42 | TINH_TRANG_CT | (null ở 130) | | `TREATMENT_METHOD` (rỗng → null) |

**Điểm đáng ngờ XML13**
1. **IS_3176 xoá MA_NOI_DI/MA_NOI_DEN/TEN_DICH_VU/TEN_THUOC/PP_DIEU_TRI** (gán `null` → phần tử biến mất khỏi XML). MA_NOI_DI/DEN được thay bằng MA_CSKCB_DI/DEN (cùng nguồn) nhưng **nằm sau DU_PHONG** (Order 40–42) — thứ tự phần tử có khớp lược đồ 3176 hay không thì mã không cho biết.
2. **Gọi `SubMaxLength(...)` rồi bỏ kết quả** (xác minh `XML13Processor.cs` dòng ~299 và ~304): hai lệnh `SubMaxLength(string.Join(";", list4/list5 …))` không gán vào đâu, ngay sau đó `TEN_DICH_VU = null`, `TEN_THUOC = null`. Đây là mã chết; nhìn như ý định là gán chuỗi đã cắt cho trường 3176 nào đó nhưng bị thiếu phép gán.
3. **`list8` (TREATMENT_METHOD + USED_MEDICINE) được dựng rồi không dùng** trong nhánh 3176 → `USED_MEDICINE` (thuốc đã dùng) không lên đâu cả; TINH_TRANG_CT lại lấy `TREATMENT_METHOD` (phương pháp điều trị).
4. **`vSereServ2` rỗng → `source = null` → `from b in source …` ném `ArgumentNullException`** → catch → `ResultADO(false)` → **mất XML13** với hồ sơ không có dịch vụ. Tương tự `TDL_HEIN_SERVICE_TYPE_ID.Value` ném lỗi nếu có dòng SS null loại.
5. `int.Parse(OUT_CODE.Substring(0, 6))` khi dùng `<#OUT_CODE_NUM_…;>`: OUT_CODE < 6 ký tự hoặc 6 ký tự đầu không phải số → mất XML13. Regex `<#OUT_CODE_NUM_.*;>` tham lam (`.*`) có thể nuốt sang placeholder khác trên cùng chuỗi.
6. Biểu thức sắp xếp thẻ ở XML13 là mã dịch ngược **không biên dịch được** (tham chiếu biến `o` ngoài phạm vi, hai `orderby` liên tiếp) → không khẳng định được thứ tự thực; XML14 có bản viết rõ (`if "1"` sắp `LOG_TIME desc, ID desc`, else `FROM, TO`). Dòng `(…).First();` đầu hàm bị bỏ kết quả.
7. MA_THE_BHYT và GT_THE_DEN lọc khác nhau (thẻ rỗng bị loại khỏi #15, thẻ thiếu TO_TIME bị loại khỏi #16) → **số phần tử hai danh sách có thể lệch**, không còn ghép cặp theo vị trí. Thẻ trùng số nhưng khác hạn được lặp lại. PTA không lọc theo `TREATMENT_ID`.
8. TOMTAT_KQ: điều kiện là > 2000 **ký tự** nhưng cắt theo 4000 **byte** → chuỗi ≤ 2000 ký tự nhưng > 4000 byte không bị cắt; cắt byte giữa ký tự UTF-8 nhiều byte sinh ký tự thay thế (U+FFFD).
9. MA_TTDV kiểm `configData3.Contains("13")` (chuỗi con) — giá trị như `"113"` hay `"130"` cũng khớp. Có login nhưng nhân viên không có mã BHXH → MA_TTDV = "" (không rơi về REPRESENTATIVE_HEIN_CODE).
10. MA_BAC_SI: có `DOCTOR_LOGINNAME` nhưng không tìm thấy nhân viên → MA_BAC_SI null (không thử `END_LOGINNAME`). `LOGINNAME.Equals` → NRE nếu nhân viên có LOGINNAME null.
11. CHAN_DOAN_RV luôn có `";"` kể cả khi ICD_TEXT rỗng. PP_DIEUTRI và PP_DIEU_TRI là hai trường trùng nguồn.
12. MA_NOI_DI lấy `TRANSFER_IN_MEDI_ORG_CODE` (theo tên cột: nơi chuyển bệnh nhân **đến** viện này) — với giấy chuyển **đi** thì cần đối chiếu dữ liệu xem cột này có đúng nghĩa "nơi đi" không.

---

### 6. XML14 — Giấy hẹn khám lại (`CHI_TIEU_GIAYHEN_KHAMLAI`, một bản ghi phẳng)

**Input**: `vTreatment`, `vPatientTypeAlter` (list), `PatientTypeAlter` (bị ghi đè trong hàm), `Employee`, `Configs`, `ListHeinMediOrg`, `TotalIcdData`, `IS_3176`.

**Điều kiện sinh**: không có điều kiện trong DLL (không kiểm có `APPOINTMENT_TIME`).

| # | Trường | Nguồn HIS | Điều kiện / key | Khác khi IS_3176 |
|---|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE` | | |
| 2 | SO_GIAYHEN_KL | `END_CODE` | | |
| 3 | MA_CSKCB | `HEIN_MEDI_ORG_CODE` | | |
| 4 | HO_TEN | `TDL_PATIENT_NAME` — CDATA | | |
| 5 | NGAY_SINH | Thiếu ngày → `yyyy00000000`; khác `yyyyMMddHHmm` | | |
| 6 | GIOI_TINH | 1→"2", 2→"1", khác "3" | | |
| 7 | DIA_CHI | `TDL_PATIENT_ADDRESS` — CDATA | | |
| 8 | MA_THE_BHYT | Như XML13 #15 (bản viết rõ), group + `Distinct()` | `SORT_MA_THE_BHYT` | |
| 9 | GT_THE_DEN | Như XML13 #16 | | |
| 10 | NGAY_VAO | `IN_TIME` → `yyyyMMddHHmm` | | |
| 11 | NGAY_VAO_NOI_TRU | `CLINICAL_IN_TIME` → `yyyyMMddHHmm`; key = "1" và `TDL_TREATMENT_TYPE_ID == 2` → "" | `XML1.NGAY_VAO_NOI_TRU_OPTION` | |
| 12 | NGAY_RA | `OUT_TIME` → `yyyyMMddHHmm` | | |
| 13 | NGAY_HEN_KL | `APPOINTMENT_TIME` → `yyyyMMddHHmm` | | **`yyyyMMdd`** (8 ký tự) |
| 14 | CHAN_DOAN_RV | `ICD_NAME + ";" + ICD_TEXT` — CDATA | | |
| 15 | MA_BENH_CHINH | `ICD_CODE` | | |
| 16 | MA_BENH_KT | `ICD_SUB_CODE`, tối đa 12 mã | | |
| 17 | MA_BENH_YHCT | Như XML13 #27 | | Tối đa **13** mã (`Take(13)`) |
| 18 | MA_DOITUONG_KCB | `vTreatment.HEIN_PATIENT_TYPE_CODE ?? text2` (text2 = bộ luật tính đối tượng, xem dưới) | `PATIENT_TYPE_CODE.BHYT` | |
| 19 | MA_BAC_SI | `DIPLOMA` của `DOCTOR_LOGINNAME`, else `END_LOGINNAME` | | |
| 20 | MA_TTDV | Mẫu chung, số bảng `"14"` | `MA_TTDV_FROM_PROFILE_XML` | DIPLOMA thủ trưởng |
| 21 | NGAY_CT | `OUT_DATE` → `yyyyMMdd` | | |
| 22 | DU_PHONG | "" | | |

**Bộ luật `text2` (chỉ dùng khi `HEIN_PATIENT_TYPE_CODE` null)** — PTA đại diện = PTA của treatment sắp (BHYT trước, `LOG_TIME` desc, `ID` desc) `.First()`:
- MA_THE_BHYT rỗng → `"9"`.
- `RIGHT_ROUTE_CODE = "DT"` & `RIGHT_ROUTE_TYPE_CODE = "CC"` → "2".
- `DT`: `GT` → "1.3"; `HK` → "1.5"; `IS_NEWBORN` → "1.7"; ICD chính/phụ `IS_LATENT_TUBERCULOSIS`/`IS_TUBERCULOSIS` → "1.1"; `IS_HIV` → "1.1"; ICD `IS_COVID` → "1.1"; nơi ĐKBĐ = `HEIN_MEDI_ORG_CODE` của treatment, hoặc thuộc `ACCEPT_HEIN_MEDI_ORG_CODE`/`SYS_MEDI_ORG_CODE` → "1.1"; giấy khai sinh "C" / thẻ `TE` / (tuyến 3–4, cùng tỉnh, `LEVEL_CODE` nơi ĐKBĐ 3–4 — tra `GlobalConfigStore.HisHeinMediOrg` ← `ConfigHeinMediOrg`) / TT46 không giấy / `TH` → "1.2"; giấy nghỉ/công tác → "1.4".
- `RIGHT_ROUTE_CODE = "TT"`: thẻ `HN` hoặc `LIVE_AREA_CODE` K1–K3 → "3.6"; theo `HEIN_LEVEL_CODE` × `TDL_TREATMENT_TYPE_ID` → "3.1"/"1.15"/"3.3"; mặc định "3.3" (tuyến 2 → "1.15").
- `TDL_TREATMENT_TYPE_ID == 6` → "7.1".

**Điểm đáng ngờ XML14**
1. `data.vPatientTypeAlter.Count` không kiểm null → NRE → **mất XML14** khi bên gọi truyền null. `.First()` ném lỗi khi danh sách không có PTA nào thuộc treatment.
2. Bộ luật MA_DOITUONG_KCB rất dài nhưng **chỉ chạy khi `HEIN_PATIENT_TYPE_CODE` là null** (`??`; chuỗi rỗng sẽ xuất rỗng).
3. `data.TotalIcdData.FirstOrDefault(...)` không kiểm null; `o.ICD_CODE.Equals` NRE nếu ICD_CODE null; `HEIN_MEDI_ORG_CODE.Substring(0, 2)` ném lỗi nếu mã < 2 ký tự; `PatientTypeAlter.HEIN_CARD_NUMBER.StartsWith` NRE nếu null — mọi lỗi đều làm mất XML14.
4. `GlobalConfigStore.HisHeinMediOrg` là **static**: lời gọi có `ListHeinMediOrg` sẽ ghi đè danh mục cho mọi lời gọi sau (kể cả luồng khác); không truyền thì dùng danh mục nhúng `ConfigHeinMediOrg`.
5. IS_3176 đổi định dạng NGAY_HEN_KL sang 8 ký tự; MA_BENH_YHCT `Take(13)` (QĐ130 không giới hạn).
6. MA_BAC_SI không `?? ""` → DIPLOMA null thì phần tử bị bỏ.
7. Danh sách thẻ MA_THE_BHYT không lọc `TREATMENT_ID` (PTA đại diện thì có lọc).

---

### 7. XML15 — Điều trị lao (`CHI_TIEU_DIEUTRI_BENHLAO / DSACH_CHITIET_DIEUTRI_BENHLAO / CHITIET_DIEUTRI_BENHLAO*`)

**Input**: `vTreatment`, `TuberculosisTreat` (**một** HIS_TUBERCULOSIS_TREAT), `Employees` (không dùng), `IS_3176`.

**Điều kiện sinh**: `TuberculosisTreat != null && TuberculosisTreat.ID != 0`.

| # | Trường | Nguồn HIS | Khác khi IS_3176 |
|---|---|---|---|
| 1 | MA_LK | `TREATMENT_CODE` | |
| 2 | STT | **Gán cứng `"15"`** | |
| 3 | MA_BN | `TDL_PATIENT_CODE` (không `??` → null bỏ phần tử) | |
| 4 | HO_TEN | `TDL_PATIENT_NAME` — CDATA | |
| 5 | SO_CCCD | `CCCD ?? CMND ?? PASSPORT ?? TDL_SOCIAL_INSURANCE_NUMBER` (`??`, chuỗi rỗng không rơi xuống) | Bỏ bậc cuối `TDL_SOCIAL_INSURANCE_NUMBER` |
| 6 | PHANLOAI_LAO_VITRI | `TUBERCULOSIS_CLASSIFY_LOCATION` | |
| 7 | PHANLOAI_LAO_TS | `TUBERCULOSIS_CLASSIFY_TS` | |
| 8 | PHANLOAI_LAO_HIV | `TUBERCULOSIS_CLASSIFY_HIV` | |
| 9 | PHANLOAI_LAO_VK | `TUBERCULOSIS_CLASSIFY_VK` | |
| 10 | PHANLOAI_LAO_KT | `TUBERCULOSIS_CLASSIFY_KT` | |
| 11 | LOAI_DTRI_LAO | `TUBERCULOSIS_TREATMENT_TYPE` | |
| 12 | NGAYBD_DTRI_LAO | `BEGIN_TIME` → `yyyyMMdd` | |
| 13 | PHACDO_DTRI_LAO | `REGIMEN_TUBERCULOSIS_CODE` | |
| 14 | NGAYKT_DTRI_LAO | `END_TIME` → `yyyyMMdd` | |
| 15 | KET_QUA_DTRI_LAO | `TUBERCULOSIS_TREATMEN_RESULT` | |
| 16 | MA_CSKCB | `vTreatment.HEIN_MEDI_ORG_CODE` | |
| 17 | NGAYKD_HIV | `HIV_KD_DATE` → `yyyyMMdd` | |
| 18 | BDDT_ARV | `ARV_BEGIN_TIME` → `yyyyMMdd` | |
| 19 | NGAY_BAT_DAU_DT_CTX | `CTX_BEGIN_TIME` → `yyyyMMdd` | |
| 20 | DU_PHONG | "" | |

Không đọc HIS_CONFIG; không có CDATA ngoài HO_TEN.

**Điểm đáng ngờ XML15**
1. **Một bản ghi bị thêm hai lần** (xác minh: `CHITIET_DIEUTRI_BENHLAO = new List<XML15DetailData> { xML15DetailData, xML15DetailData }`) → file XML15 luôn có **2 phần tử `CHITIET_DIEUTRI_BENHLAO` giống hệt nhau** (cùng tham chiếu một object, cùng MA_LK, cùng STT "15"). Cổng sẽ nhận dòng trùng.
2. **STT gán cứng "15"** (trông như số hiệu bảng), không phải số thứ tự dòng.
3. Chỉ hỗ trợ một đợt điều trị lao/hồ sơ (input là một object).
4. SO_CCCD QĐ130 có thể rơi về **mã BHXH** khi không có giấy tờ — đặt mã BHXH vào trường CCCD; 3176 đã bỏ bậc này.
5. Mã chết: `GetMaBacSi`, `ValidAccept`, `SubMaxLength`, `GlobalConfigStore.HisHeinMediOrg`. `SubMaxLength` của XML15 còn sai logic (`Substring(Length - MAX_LENGTH + 1)` — lấy **đuôi** 1023 ký tự), may là không được gọi.

---

### 8. Tóm tắt khác biệt IS_3176 (XML9–15)

| Bảng | Thay đổi khi IS_3176 |
|---|---|
| XML9 | MA_TTDV → CCHN thủ trưởng |
| XML10 | MA_TTDV → CCHN thủ trưởng; MA_BS → CCHN (trưởng khoa ra viện ưu tiên); **CHAN_DOAN_RV = "dưỡng thai"** |
| XML11 | MA_TTDV → CCHN thủ trưởng; MA_BS → CCHN (trưởng khoa ra viện ưu tiên) |
| XML12 | Không có nhánh 3176 |
| XML13 | Bỏ MA_NOI_DI, MA_NOI_DEN, TEN_DICH_VU, TEN_THUOC, PP_DIEU_TRI; MA_NGHE_NGHIEP 2 ký tự; MA_TTDV → CCHN; thêm MA_CSKCB_DI, MA_CSKCB_DEN, TINH_TRANG_CT (sau DU_PHONG) |
| XML14 | NGAY_HEN_KL `yyyyMMdd`; MA_TTDV → CCHN; MA_BENH_YHCT ≤ 13 mã |
| XML15 | SO_CCCD bỏ bậc rơi về mã BHXH |

Lưu ý chung về độ bền: hầu hết bảng bọc toàn bộ quá trình sinh trong một `try/catch`; **một lỗi dữ liệu nhỏ** (mã thẻ ngắn, nhân viên LOGINNAME null, ICD lệch số phần tử, danh sách null) làm **mất cả bảng** cho hồ sơ đó, chỉ để lại `LogSystem.Error` — không có cảnh báo trong file.

---

