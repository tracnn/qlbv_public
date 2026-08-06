# Trình chiếu giao ban: chỉ tiêu dạng bảng + theme sáng/tối — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đổi lưới thẻ KPI ở slide Tổng quan và slide từng khoa sang bảng có viền căn lề theo kiểu dữ liệu, và thêm nút chuyển theme sáng/tối có ghi nhớ.

**Architecture:** Toàn bộ thay đổi nằm trong một tệp Blade duy nhất. Phần bảng: một hàm JS dựng bảng dùng chung cho hai slide, kèm một bộ lớp CSS `.bct` dùng lại hệ viền của bảng `.bdt` sẵn có. Phần theme: rút mọi màu viết cứng (cả trong CSS lẫn trong chuỗi HTML do JS sinh) về biến CSS ở `:root`, khai báo lại bộ biến đó trong `html[data-theme="light"]`, nên đổi theme chỉ là đổi một thuộc tính trên `<html>` — không dựng lại DOM, không mất slide đang chiếu.

**Tech Stack:** Laravel Blade, JavaScript thuần ES5 (không framework, không build step), CSS custom properties.

## Global Constraints

- Chỉ sửa `resources/views/khth/giaoban-present.blade.php`. Không đổi controller, service, route, hay API.
- JavaScript viết theo phong cách ES5 đang có trong tệp: `var`, `function`, không arrow function, không template literal, không `const`/`let`.
- Chú thích trong mã viết **tiếng Việt không dấu**, đúng như phần còn lại của tệp.
- Mọi cỡ chữ của **nội dung** phải nhân với `var(--z)` để nút `A−/A+` còn tác dụng. Cỡ chữ của **thanh điều khiển** `#bar` thì không được nhân — đây là quy ước đã ghi trong chú thích ở đầu tệp.
- Bảng `.bdt` (slide Hoạt động điều trị) giữ nguyên hoàn toàn: dữ liệu, cột, căn lề căn giữa như hiện tại.
- Không dựng ma trận khoa × chỉ tiêu cho slide Tổng quan. `theTongQuan()` giữ nguyên logic gom theo `overview_label`.
- Không đọc `prefers-color-scheme`. Theme mặc định là `dark`.
- Không thêm phím tắt cho theme.
- Tệp không có test tự động (Blade thuần, không có tầng logic tách rời). Kiểm chứng từng nhiệm vụ bằng cách mở màn trình chiếu thật trên trình duyệt.

## Cách kiểm chứng thủ công (dùng lại ở mọi nhiệm vụ)

Route: `Route::get('giao-ban/present', 'KHTH\GiaoBanController@present')->name('khth.giao-ban-present')` — `routes/web.php:731`.

Mở trong trình duyệt, đã đăng nhập, với một ngày **đã có dữ liệu giao ban**:

```
/khth/giao-ban/present?date=YYYY-MM-DD
```

Điều hướng: `→` / `←` chuyển slide, nút `☰ Khoa` ở thanh dưới nhảy thẳng tới slide cần xem.

## File Structure

Một tệp duy nhất — `resources/views/khth/giaoban-present.blade.php` (568 dòng). Các vùng bị tác động:

| Vùng | Dòng hiện tại | Nhiệm vụ |
|---|---|---|
| Khối `<style>` — biến gốc và màu | 7–103 | 1, 3, 4 |
| Thanh điều khiển `#bar` | 110–124 | 4 |
| `donutHtml()` | 247–266 | 3 |
| `capColor()` | 243–245 | 3 |
| `overviewSlide()` | 268–334 | 2, 3 |
| `deptSlide()` | 411–439 | 1 |
| `setupNav()` | 522–551 | 4 |

Tệp đã lớn nhưng có cấu trúc rõ theo hàm dựng slide; không tách tệp trong phạm vi này (Blade một tệp là mẫu đang dùng cho màn trình chiếu, tách ra sẽ phải kéo theo cơ chế nạp asset mà dự án chưa có).

---

### Task 1: Hàm `bangChiTieu()` và áp dụng cho slide từng khoa

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — thêm CSS sau khối `.bdt` (sau dòng 62), thêm hàm JS trước `overviewSlide()` (trước dòng 268), sửa `deptSlide()` (dòng 411–439)

**Interfaces:**
- Consumes: `esc(s)`, `num(v)`, `cellVal(data, deptId, code)`, `kpiClass(metric)`, `laChiTieuChuoi(m)` — đã có sẵn trong tệp.
- Produces: `bangChiTieu(items, gian)` → chuỗi HTML.
  - `items`: mảng `{ nhan: string, gia_tri: number|null, cls: string }`. `cls` là giá trị trả về của `kpiClass()` — chuỗi rỗng, `' teal'`, hoặc `' amber'` (chú ý dấu cách ở đầu).
  - `gian`: boolean. `true` thì khung bảng chiếm hết chiều cao còn lại và tự cuộn khi tràn; `false`/khuyết thì bảng cao tự nhiên.
  - Trả về `''` khi `items` rỗng. Task 2 dựa vào điều này.

- [ ] **Step 1: Thêm CSS cho bảng chỉ tiêu**

Chèn ngay **sau** dòng `.bdt tr.tong td { ... }` (dòng 62), trước chú thích về `#bar`:

```css
  /* Bang chi tieu cua man Tong quan va man tung khoa. Dung lai he vien cua .bdt de hai man
     nhin cung mot kieu, nhung cang le theo kieu du lieu: ten trai, so phai. */
  .bct-wrap { min-height: 0; overflow: auto; margin-top: 2vh; }
  /* Chi man tung khoa moi cho bang gian het chieu cao: man Tong quan con cac khoi canh bao
     va ghi chu nam ngay duoi bang, gian ra la day chung xuong day man. */
  .bct-wrap.gian { flex: 1; }
  .bct { width: 100%; border-collapse: collapse; color: #dbe6f0; table-layout: fixed; }
  .bct th, .bct td { border: 1px solid #24405c; padding: .6vh .8vw; }
  .bct th { background: #14293e; color: #8aa4bd; font-weight: 600; text-align: center; }
  .bct th.so, .bct td.so { width: 15%; }
  .bct td.ten { text-align: left; color: #fff; }
  .bct td.so { text-align: right; font-weight: 600; color: #fff; white-space: nowrap; }
  .bct td.so.teal { color: #5dcaa5; }
  .bct td.so.amber { color: #ef9f27; }
```

- [ ] **Step 2: Thêm hàm `bangChiTieu()`**

Chèn ngay **trước** hàm `overviewSlide(data)` (trước dòng 268):

```js
  /**
   * Bang chi tieu dung chung cho man Tong quan va man tung khoa.
   *
   * Moi dong hai cap "Chi tieu | So lieu" de tan dung be ngang man chieu; so chi tieu le thi
   * cap cuoi de trong nhung van co vien cho bang khong khuyet goc.
   *
   * Cang le theo KIEU DU LIEU chu khong theo cot: so va phan tram can phai, con lai can trai.
   * O khuyet (num() tra dau gach) tinh la chuoi nen can trai.
   *
   * Co chu nho dan theo so dong, cung tinh than voi cach .bdt nho dan theo so cot. Cham san
   * ma van tran thi .bct-wrap cho cuon (khi gian) hoac .slide cho cuon (khi khong gian).
   */
  function bangChiTieu(items, gian) {
    if (!items.length) return '';

    var CAP = 2; // so cap "Chi tieu | So lieu" tren mot dong
    var soDong = Math.ceil(items.length / CAP);
    var co = soDong <= 6 ? 2.75 : (soDong <= 10 ? 2.3 : (soDong <= 14 ? 2 : 1.75));

    var thead = '<tr>';
    for (var c = 0; c < CAP; c++) thead += '<th class="ten">CHỈ TIÊU</th><th class="so">SỐ LIỆU</th>';
    thead += '</tr>';

    var tbody = '';
    for (var r = 0; r < soDong; r++) {
      tbody += '<tr>';
      for (var k = 0; k < CAP; k++) {
        var it = items[r * CAP + k];
        if (!it) { tbody += '<td class="ten"></td><td class="so"></td>'; continue; }
        var v = num(it.gia_tri);
        var laSo = /^-?[\d.,]+%?$/.test(v);
        tbody += '<td class="ten">' + esc(it.nhan) + '</td>' +
          '<td class="' + (laSo ? 'so' + (it.cls || '') : 'ten') + '">' + v + '</td>';
      }
      tbody += '</tr>';
    }

    return '<div class="bct-wrap' + (gian ? ' gian' : '') + '">' +
      '<table class="bct" style="font-size:calc(' + co + 'vh * var(--z))">' +
      '<thead>' + thead + '</thead><tbody>' + tbody + '</tbody></table></div>';
  }
```

- [ ] **Step 3: Sửa `deptSlide()` dùng bảng thay lưới thẻ**

Trong `deptSlide()` (dòng 411–439), thay khối dựng `cards`:

```js
    // Luoi KPI chi nhan chi tieu SO. Chi tieu chuoi xuong khoi rieng ben duoi.
    var cards = cfg.metrics.filter(function (m) { return !laChiTieuChuoi(m); }).map(function (m) {
      var v = cellVal(data, cfg.id, m.code);
      return '<div class="kpi' + kpiClass(m) + '"><div class="lbl">' + esc(m.name) +
        '</div><div class="val">' + num(v) + '</div></div>';
    }).join('');
```

bằng:

```js
    // Bang chi tieu chi nhan chi tieu SO. Chi tieu chuoi xuong khoi rieng ben duoi.
    var items = cfg.metrics.filter(function (m) { return !laChiTieuChuoi(m); }).map(function (m) {
      return { nhan: m.name, gia_tri: cellVal(data, cfg.id, m.code), cls: kpiClass(m) };
    });
    var bangHtml = bangChiTieu(items, true);
```

và trong chuỗi trả về của hàm, thay:

```js
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + cards + '</div>' +
```

bằng:

```js
      bangHtml +
```

- [ ] **Step 4: Kiểm chứng trên trình duyệt**

Mở `/khth/giao-ban/present?date=YYYY-MM-DD` với ngày có dữ liệu, dùng nút `☰ Khoa` nhảy tới một khoa bất kỳ. Xác nhận:

- Slide khoa hiện bảng 4 cột `CHỈ TIÊU | SỐ LIỆU | CHỈ TIÊU | SỐ LIỆU`, tiêu đề căn giữa.
- Tên chỉ tiêu căn trái, số căn phải, mọi ô có viền.
- Chỉ tiêu chuyển đến hiện màu xanh, ra viện / chuyển viện hiện màu cam.
- Khoa có số chỉ tiêu **lẻ**: cặp cuối cùng là hai ô trống có viền, bảng không khuyết góc.
- Khoa chưa nhập: ô hiện `—` và căn **trái**.
- Bấm `A+` vài lần: chữ trong bảng to lên; phóng tối đa thì bảng cuộn trong khung, không tràn ra ngoài slide.
- Slide Tổng quan vẫn là thẻ KPI như cũ (chưa đụng tới ở nhiệm vụ này).
- Slide Hoạt động điều trị không đổi.

- [ ] **Step 5: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): chi tieu tung khoa hien dang bang co vien"
```

---

### Task 2: Áp bảng cho slide Tổng quan và dọn CSS thẻ KPI

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — `overviewSlide()` (dòng 268–334), xoá CSS `.kpis`/`.kpi` (dòng 27–31)

**Interfaces:**
- Consumes: `bangChiTieu(items, gian)` từ Task 1; `theTongQuan(data)` trả mảng `{ nhan, tong, cls }` — đã có sẵn (dòng 167–185).
- Produces: không có gì cho nhiệm vụ sau.

- [ ] **Step 1: Sửa `overviewSlide()` dùng bảng**

Trong `overviewSlide()`, thay toàn bộ khối từ `var kpiHtml = '';` đến hết nhánh `if (kpiHtml === '') { ... }` (dòng 270–285):

```js
    var kpiHtml = '';
    function kpi(label, val, cls) {
      if (val === null) return '';
      return '<div class="kpi' + (cls || '') + '"><div class="lbl">' + esc(label) +
        '</div><div class="val">' + num(val) + '</div></div>';
    }
    // KPI do KHTH danh dau tren tung chi tieu, gom theo NHAN chu khong theo MA.
    // Ban cu tra theo ma viet cung trong code nen chi can KHTH doi ma la man nay trong tron.
    theTongQuan(data).forEach(function (t) {
      kpiHtml += kpi(t.nhan, t.tong, t.cls);
    });
    if (kpiHtml === '') {
      kpiHtml = '<div class="kpi" style="grid-column:1/-1"><div class="lbl">Chưa đánh dấu chỉ tiêu nào</div>' +
        '<div class="txt" style="font-size:calc(2.25vh * var(--z));color:#8aa4bd;margin-top:.6vh">' +
        'Vào Cấu hình giao ban → mở Chỉ tiêu của khoa → tích "Hiện ở màn Tổng quan".</div></div>';
    }
```

bằng:

```js
    // Chi tieu do KHTH danh dau, gom theo NHAN chu khong theo MA. Ban cu tra theo ma viet cung
    // trong code nen chi can KHTH doi ma la man nay trong tron.
    // Khong gian bang: cac khoi canh bao va ghi chu nam ngay duoi, gian ra la day chung xuong day man.
    var kpiHtml = bangChiTieu(theTongQuan(data).map(function (t) {
      return { nhan: t.nhan, gia_tri: t.tong, cls: t.cls };
    }), false);
    if (kpiHtml === '') {
      kpiHtml = '<div class="note" style="margin-top:2vh"><div class="lbl">CHƯA ĐÁNH DẤU CHỈ TIÊU NÀO</div>' +
        '<div class="txt">Vào Cấu hình giao ban → mở Chỉ tiêu của khoa → tích "Hiện ở màn Tổng quan".</div></div>';
    }
```

- [ ] **Step 2: Bỏ lớp bọc lưới ở chuỗi trả về**

Trong chuỗi trả về của `overviewSlide()` (dòng 332), thay:

```js
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + kpiHtml + '</div>' +
```

bằng:

```js
      kpiHtml +
```

Xoá luôn ba dòng chú thích ngay trên nó (dòng 327–329, bắt đầu bằng `// Bo class ov-kpis:`) — chúng nói về lưới KPI vừa bị gỡ nên đã hết đúng. Giữ lại dòng chú thích `// Kip truc len DAU: ...`.

- [ ] **Step 3: Xoá CSS thẻ KPI không còn ai dùng**

Xác nhận không còn chỗ nào tham chiếu bằng:

```bash
grep -n 'class="kpi\|kpis\|ov-kpis' resources/views/khth/giaoban-present.blade.php
```

Kết quả mong đợi: chỉ còn các dòng **định nghĩa CSS**, không còn dòng nào trong JS. Sau đó xoá các dòng CSS 27–31 và dòng 83:

```css
  .kpis { display: grid; gap: 1.4vh; margin-top: 2vh; }
  .kpi { background: #13293d; border-radius: 10px; padding: 1.6vh 1.6vw; }
  .kpi .lbl { font-size: calc(2.12vh * var(--z)); color: #8aa4bd; }
  .kpi .val { font-size: calc(5.75vh * var(--z)); font-weight: 500; color: #fff; line-height: 1.1; }
  .kpi.teal .val { color: #5dcaa5; } .kpi.amber .val { color: #ef9f27; }
```

```css
  .ov-kpis { flex: 1; align-content: start; }
```

Chạy lại lệnh `grep` trên: kết quả mong đợi là **không còn dòng nào**.

- [ ] **Step 4: Kiểm chứng trên trình duyệt**

Tải lại `/khth/giao-ban/present?date=YYYY-MM-DD`. Slide đầu tiên là Tổng quan. Xác nhận:

- Khối kíp trực vẫn ở trên cùng, ngay dưới tiêu đề.
- Bảng chỉ tiêu 4 cột nằm dưới kíp trực, căn lề đúng như slide khoa.
- Khối "Ô BẮT BUỘC CÒN TRỐNG" và ghi chú chung nằm **ngay dưới bảng**, không bị đẩy xuống đáy màn hình.
- Badge `ĐÃ CHỐT` / `BẢN NHÁP` trên tiêu đề vẫn hiện.
- Với ngày mà không khoa nào tích chỉ tiêu overview: hiện khối hướng dẫn "CHƯA ĐÁNH DẤU CHỈ TIÊU NÀO" thay vì bảng trống.
- Slide từng khoa và slide Hoạt động điều trị vẫn như sau Task 1.

- [ ] **Step 5: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): man tong quan hien chi tieu dang bang, bo the KPI"
```

---

### Task 3: Rút toàn bộ màu về biến CSS (giao diện không đổi)

Nhiệm vụ này **không được làm đổi diện mạo**. Giá trị mọi biến đúng bằng hex đang dùng; kiểm chứng là "trông y hệt trước".

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — toàn bộ khối `<style>`, `capColor()` (dòng 243–245), `donutHtml()` (dòng 247–266), các `style="...#..."` nội tuyến trong `overviewSlide()`

**Interfaces:**
- Consumes: không.
- Produces: bộ biến CSS ở `:root` mà Task 4 sẽ khai báo lại. Tên biến chốt như bảng dưới — Task 4 dựa đúng vào danh sách này.
  `capColor(pct)` đổi kiểu trả về từ chuỗi hex sang chuỗi `var(--...)`; nơi dùng vẫn là `background:` và `color:` nên không phải sửa gì thêm.

- [ ] **Step 1: Khai báo bộ biến ở `:root`**

Thay khối `:root { --z: 1; }` (dòng 11) bằng:

```css
  /* Bang mau nen toi (mac dinh). Theme sang khai bao lai dung bo bien nay o cuoi khoi style.
     Moi mau cua trinh chieu phai di qua day: mau viet cung con sot lai o dau thi cho do se
     khong doi theo theme va thanh mang lac long tren nen sang. */
  :root {
    --z: 1;
    --bg: #0d1b2a;          /* nen trang */
    --text: #e8eef5;        /* chu tren nen trang */
    --panel: #13293d;       /* nen panel, note, nut */
    --panel-2: #14293e;     /* nen o tieu de bang va dong tong */
    --line: #24384d;        /* duong ke chung, vien nut */
    --line-2: #24405c;      /* vien o bang */
    --brand: #6ea8d8;       /* nhan thuong hieu, dot dang xem, so dien thoai */
    --muted: #8aa4bd;       /* chu phu */
    --txt-2: #dbe6f0;       /* chu noi dung */
    --strong: #fff;         /* chu nhan manh */
    --teal: #5dcaa5;        /* tang / tot */
    --amber: #ef9f27;       /* giam / canh bao */
    --amber-2: #efc877;     /* nhan cua khoi ghi chu */
    --red: #e57373;         /* xau */
    --blue: #378add;        /* cong suat thap */
    --btn-text: #cfe0f0;
    --btn-hover: #1b3348;
    --bar-text: #6f8aa6;
    --dot: #3a5570;
    --track: #24384d;       /* ranh thanh cong suat, vong nen donut */
    --ok-bg: #122b23;
    --bad-bg: #33201f;
    --badge-nhap-bg: #3a2f12;
    --badge-nhap-fg: #efc877;
    --badge-chot-bg: #12331f;
    --badge-chot-fg: #5dcaa5;
  }
```

- [ ] **Step 2: Thay mọi hex trong khối `<style>` bằng biến**

Áp bảng tra sau cho toàn bộ khối `<style>` (trừ chính khối `:root` vừa viết):

| Hex hiện tại | Biến | Ghi chú |
|---|---|---|
| `#0d1b2a` | `var(--bg)` | nền `html, body` |
| `#e8eef5` | `var(--text)` | chữ `html, body` |
| `#13293d` | `var(--panel)` | `.kpi` đã xoá; còn `.panel`, `.note`, `.btn`, `#jump-list` |
| `#14293e` | `var(--panel-2)` | `.bdt th`, `.bdt tr.tong td`, `.bct th` |
| `#24384d` | `var(--line)` | `.s-head`, `#bar`, `.btn`, `#jump-list` |
| `#24384d` | `var(--track)` | **chỉ** ở `.captrack` |
| `#24405c` | `var(--line-2)` | `.bdt th/td`, `.bct th/td` |
| `#6ea8d8` | `var(--brand)` | `.s-brand`, `#dots i.on` |
| `#8aa4bd` | `var(--muted)` | các nhãn phụ, `.donut-cap`, `.donut-legend small`, `.capnum`, `#center` |
| `#fff` | `var(--strong)` | `.s-title`, `.bdt td.ten`, `.bdt tr.tong td`, `.bct td.ten`, `.bct td.so`, `.donut-pct` |
| `#dbe6f0` | `var(--txt-2)` | `.note .txt`, `.ov-canh-bao`, `.bdt`, `.bct`, `.capname` |
| `#efc877` | `var(--amber-2)` | `.note .lbl` |
| `#ef9f27` | `var(--amber)` | `.note` border-left, `.warn`, `.bct td.so.amber` |
| `#5dcaa5` | `var(--teal)` | `.ov-canh-bao.tot` border, `.bct td.so.teal` |
| `#e57373` | `var(--red)` | `.ov-canh-bao.xau` border |
| `#122b23` | `var(--ok-bg)` | `.ov-canh-bao.tot` nền |
| `#33201f` | `var(--bad-bg)` | `.ov-canh-bao.xau` nền |
| `#3a2f12` / `#efc877` | `var(--badge-nhap-bg)` / `var(--badge-nhap-fg)` | `.ov-badge.nhap` |
| `#12331f` / `#5dcaa5` | `var(--badge-chot-bg)` / `var(--badge-chot-fg)` | `.ov-badge.chot` |
| `#cfe0f0` | `var(--btn-text)` | `.btn`, `#jump-list button` |
| `#1b3348` | `var(--btn-hover)` | `.btn:hover`, `#jump-list button:hover` |
| `#6f8aa6` | `var(--bar-text)` | `#bar` |
| `#3a5570` | `var(--dot)` | `#dots i` |

Sau khi thay, xác nhận khối `<style>` không còn hex nào ngoài `:root`:

```bash
sed -n '/<style>/,/<\/style>/p' resources/views/khth/giaoban-present.blade.php | grep -n '#[0-9a-fA-F]\{3,6\}'
```

Kết quả mong đợi: chỉ ra các dòng nằm trong khối `:root`.

- [ ] **Step 3: Đổi `capColor()` trả tên biến**

Thay (dòng 243–245):

```js
  function capColor(pct) {
    return pct >= 90 ? '#e57373' : pct >= 80 ? '#ef9f27' : pct >= 60 ? '#5dcaa5' : '#378add';
  }
```

bằng:

```js
  // Tra TEN BIEN chu khong phai hex: mau nam trong style noi tuyen cua thanh cong suat va donut,
  // tra bien thi doi theme la mau tu doi theo, khong phai dung lai DOM.
  function capColor(pct) {
    return pct >= 90 ? 'var(--red)' : pct >= 80 ? 'var(--amber)' : pct >= 60 ? 'var(--teal)' : 'var(--blue)';
  }
```

- [ ] **Step 4: Đổi màu nội tuyến trong `donutHtml()`**

Trong `donutHtml()` (dòng 247–266) thay bốn chỗ:

- `stroke="#24384d"` → `stroke="var(--track)"`
- `style="color:#fff"` → `style="color:var(--strong)"`
- `style="color:#5dcaa5"` → `style="color:var(--teal)"`
- `style="color:#8aa4bd"` → `style="color:var(--muted)"`

- [ ] **Step 5: Đổi màu nội tuyến trong `overviewSlide()`**

Trong khối dựng `dutyHtml` (dòng 293–301) thay ba chỗ:

- `<b style="color:#fff">` → `<b style="color:var(--strong)">`
- `<span style="color:#6ea8d8">` → `<span style="color:var(--brand)">`
- `<span style="color:#8aa4bd">` → `<span style="color:var(--muted)">`

Xác nhận toàn tệp không còn hex ngoài `:root`:

```bash
grep -n '#[0-9a-fA-F]\{3,6\}' resources/views/khth/giaoban-present.blade.php
```

Kết quả mong đợi: chỉ còn các dòng trong khối `:root`. Nếu còn dòng nào khác, thay nốt theo bảng tra ở Step 2.

- [ ] **Step 6: Kiểm chứng trên trình duyệt**

Tải lại `/khth/giao-ban/present?date=YYYY-MM-DD` và duyệt **hết mọi slide** (`→` liên tục tới cuối). Xác nhận **không có gì đổi so với trước nhiệm vụ này**: cùng màu nền, cùng màu chữ, donut và thanh công suất vẫn đủ bốn mức màu, badge và khối cảnh báo vẫn đúng màu.

Mở Console của trình duyệt, xác nhận không có lỗi JS.

- [ ] **Step 7: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "refactor(giaoban): rut mau trinh chieu ve bien CSS, chua doi giao dien"
```

---

### Task 4: Nút chuyển theme và bảng màu sáng

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — thêm khối `html[data-theme="light"]` cuối `<style>`, thêm nút vào `#bar` (sau dòng 115), thêm hàm theme cạnh `napZoom()` (sau dòng 520), gắn sự kiện trong `setupNav()` (dòng 522–551)

**Interfaces:**
- Consumes: bộ biến CSS từ Task 3 — khai báo lại **đúng** các tên biến đó.
- Produces: `datTheme(t)`, `napTheme()`; khoá localStorage `giaoban.present.theme` nhận `'dark'` | `'light'`.

- [ ] **Step 1: Thêm bảng màu sáng**

Chèn vào **cuối** khối `<style>`, ngay trước `</style>`:

```css
  /* Bang mau nen sang. Khong dao mau may moc tu ban toi: cac mau pastel cua nen toi (#5dcaa5,
     #efc877) doc khong ro tren nen trang nen mau nhan o day dam va bao hoa hon. */
  html[data-theme="light"] {
    --bg: #f4f6f9;
    --text: #1c2733;
    --panel: #ffffff;
    --panel-2: #eaf0f6;
    --line: #d3dde7;
    --line-2: #c3d0dd;
    --brand: #1d6fa5;
    --muted: #5a7085;
    --txt-2: #2b3949;
    --strong: #0f1a26;
    --teal: #12855f;
    --amber: #b96a00;
    --amber-2: #8a5a00;
    --red: #c53a3a;
    --blue: #1668b3;
    --btn-text: #1c2733;
    --btn-hover: #e6eef6;
    --bar-text: #5a7085;
    --dot: #b8c6d3;
    --track: #dbe4ec;
    --ok-bg: #e6f5ee;
    --bad-bg: #fdeceb;
    --badge-nhap-bg: #fdf0d6;
    --badge-nhap-fg: #8a5a00;
    --badge-chot-bg: #ddf3e7;
    --badge-chot-fg: #12855f;
  }
  /* Nen sang: panel trang tren nen trang nga can vien moi tach khoi duoc, ban toi thi khong. */
  html[data-theme="light"] .panel,
  html[data-theme="light"] .note { border: 1px solid var(--line); }
  html[data-theme="light"] .note { border-left: 3px solid var(--amber); }
```

- [ ] **Step 2: Thêm nút vào thanh điều khiển**

Trong `#bar`, chèn ngay **sau** nút `z-in` (dòng 115):

```html
      <button class="btn" id="theme-btn" title="Chuyển nền sáng">☀</button>
```

- [ ] **Step 3: Thêm hàm đặt và nạp theme**

Chèn ngay **sau** hàm `napZoom()` (sau dòng 520), trước `setupNav()`:

```js
  /*
   * Theme la thiet lap cua MAY DANG CHIEU giong muc zoom, khong phai cua bao cao: luu
   * localStorage, khong gui len may chu. Mac dinh nen toi ke ca khi may dat che do sang.
   */
  var THEME_KEY = 'giaoban.present.theme';
  var theme = 'dark';

  function datTheme(t) {
    theme = t === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    var b = document.getElementById('theme-btn');
    if (b) {
      // Nhan la thu SE chuyen sang khi bam, khong phai thu dang dung.
      b.textContent = theme === 'dark' ? '☀' : '☾';
      b.title = theme === 'dark' ? 'Chuyển nền sáng' : 'Chuyển nền tối';
    }
    try { localStorage.setItem(THEME_KEY, theme); } catch (err) { /* che do rieng tu */ }
  }

  function napTheme() {
    var t = 'dark';
    try { t = localStorage.getItem(THEME_KEY) || 'dark'; } catch (err) { t = 'dark'; }
    datTheme(t);
  }
```

- [ ] **Step 4: Gắn sự kiện trong `setupNav()`**

Trong `setupNav()`, ngay **trước** dòng `napZoom();` (dòng 550), chèn:

```js
    document.getElementById('theme-btn').addEventListener('click', function () {
      datTheme(theme === 'dark' ? 'light' : 'dark');
    });
    napTheme();
```

- [ ] **Step 5: Kiểm chứng trên trình duyệt**

Tải lại `/khth/giao-ban/present?date=YYYY-MM-DD`. Xác nhận:

- Mở lần đầu (localStorage sạch): nền **tối**, nút hiện `☀`.
- Bấm `☀`: toàn bộ deck sang nền sáng, nút đổi thành `☾`, và **vẫn ở đúng slide đang xem** — không nhảy về slide đầu.
- Ở nền sáng, duyệt hết mọi slide và xác nhận không còn mảng tối lạc lõng: bảng chỉ tiêu, bảng Hoạt động điều trị, khối ghi chú, badge trạng thái, khối cảnh báo, donut, thanh công suất theo khoa, danh sách `☰ Khoa`, thanh điều khiển dưới — tất cả đều đọc rõ trên nền sáng.
- Donut và thanh công suất ở nền sáng vẫn phân biệt được bốn mức màu (đỏ ≥90%, cam ≥80%, xanh lá ≥60%, xanh dương còn lại).
- Bấm `☾`: quay lại nền tối, vẫn đúng slide.
- Tải lại trang khi đang ở nền sáng: vẫn mở ra nền sáng.
- Bấm `A+`/`A−` ở cả hai theme: chữ nội dung phóng, thanh điều khiển dưới **không** phóng theo.
- Console không có lỗi JS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): nut chuyen nen sang/toi cho man trinh chieu"
```

---

## Self-Review

Đối chiếu kế hoạch với spec:

| Yêu cầu trong spec | Nhiệm vụ |
|---|---|
| Hàm `bangChiTieu` dùng chung hai slide | Task 1 Step 2 |
| 4 cột, 2 cặp mỗi dòng, số lẻ để trống | Task 1 Step 2 |
| Tiêu đề giữa, tên trái, số/phần trăm phải | Task 1 Step 1 + 2 |
| Mọi ô có viền, dùng lại hệ viền `.bdt` | Task 1 Step 1 |
| Màu nhấn `kpiClass` tô vào ô số | Task 1 Step 1 + 2 |
| Chỉ tiêu chuỗi vẫn ở khối `.note` riêng | Task 1 Step 3 (bộ lọc `laChiTieuChuoi` giữ nguyên) |
| Cỡ chữ co theo số dòng, nhân `var(--z)`, tràn thì cuộn | Task 1 Step 2 |
| Áp cho `overviewSlide` | Task 2 Step 1 + 2 |
| Giữ nội dung "Chưa đánh dấu chỉ tiêu nào" | Task 2 Step 1 |
| Kíp trực, cảnh báo, ghi chú giữ nguyên vị trí | Task 2 Step 4 (kiểm chứng) |
| Rút màu về biến CSS | Task 3 Step 1 + 2 |
| `capColor` và donut trả tên biến | Task 3 Step 3 + 4 |
| Nút trên `#bar`, không phím tắt | Task 4 Step 2 + 4 |
| localStorage `giaoban.present.theme`, mặc định dark | Task 4 Step 3 |
| Bảng màu sáng riêng, không đảo máy móc | Task 4 Step 1 |
| Đổi theme không dựng lại DOM, giữ slide | Task 4 Step 5 (kiểm chứng) |
| `.bdt` không đổi | Không nhiệm vụ nào sửa `.bdt` ngoài việc thay hex bằng biến cùng giá trị (Task 3) |

Nhất quán tên gọi: `bangChiTieu(items, gian)` khai báo ở Task 1 Step 2, gọi ở Task 1 Step 3 (`true`) và Task 2 Step 1 (`false`) — cùng chữ ký. Trường của `items` là `nhan` / `gia_tri` / `cls` ở cả ba chỗ. `datTheme` / `napTheme` khai báo Task 4 Step 3, gọi Task 4 Step 4. Bộ biến CSS đặt ở Task 3 Step 1 và khai báo lại **đủ 26 biến** ở Task 4 Step 1.
