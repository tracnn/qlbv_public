# Bao cao: bo sung menu Danh muc TT12

## Trang thai
Hoan thanh.

## Thay doi
- `config/adminlte.php`: them khoi menu cap 2 `'Danh mục TT12'` (icon `list-alt`,
  `checkrole => 'xml-man'`) vao submenu cua cap 1 `'Hồ sơ XML'`, dat ngay sau khoi
  `'Chứng từ điện tử'` va truoc khoi `'Xml 4750'`. Hai muc con:
  - `'Danh sách hồ sơ'` -> route `bhyt.tt12.index`, active `['bhyt/tt12/index*']`.
  - `'Nạp danh mục'` -> route `bhyt.tt12.import.index`, active `['bhyt/tt12/import*']`.
- `tests/Unit/Tt12/MenuTt12Test.php` (moi): 4 test chot khoi menu ton tai, checkrole
  dung `xml-man`, hai route co that (tra bang `app('router')->getRoutes()->getByName()`),
  active cua "Nạp danh mục" khong lam sang "Danh sách hồ sơ", va checkrole khop
  middleware that cua route `bhyt.tt12.index` (`gatherMiddleware()`).

## Ghi chu ky thuat
- Khoi TT12 la muc CAP 2 (nam trong submenu cua cap 1 "Hồ sơ XML"), khong phai cap 1
  - giong cau truc cua Xml3176, Chung tu dien tu, Xml 4750, Xml 4210 lan can.
- Route `bhyt.tt12.index` va `bhyt.tt12.import.index` nam trong
  `Route::group(['prefix' => 'bhyt/', 'middleware' => ['checkrole:xml-man']])`
  (routes/web.php dong 542), khong co middleware rieng nao khac -> checkrole
  menu `xml-man` khop dung.
- Khong them muc "Dashboard" vi module TT12 khong co dashboard/route tuong ung.

## Ket qua test
- `tests/Unit/Tt12/MenuTt12Test.php`: 4/4 xanh (16 assertions).
- `tests/Unit/Tt12` (ca thu muc): 182 test xanh (178 cu + 4 moi), 1254 assertions.
- Toan suite: 1774 test, 17 do (8 loi + 9 that bai) - dung 17 test do co san, khong
  co ten test do nao moi xuat hien. Danh sach 17 test do (khong lien quan module nay):
  DoctorStatsControllerTest (x4), OperatingRoomControllerTest (x3),
  TrendAnalysisControllerTest (x4... theo chi tiet loi/that bai), ExampleTest,
  Xml3176ExportLocCoSoTest (x2), CtdtCauHinhTest, NhapDanhMucUniqueTest (x2).

## Con ngo
Khong co.
