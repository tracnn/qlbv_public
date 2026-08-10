# Trình chiếu giao ban: xuất PPTX — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm nút xuất tệp PPTX từ màn trình chiếu giao ban, mỗi slide là đối tượng PowerPoint thật (bảng, hộp văn bản, biểu đồ) và màu theo theme đang chọn.

**Architecture:** Tách một tầng mô tả deck thuần dữ liệu (`moTaDeck`) khỏi việc sinh HTML; bộ dựng HTML và bộ dựng PPTX cùng đọc từ đó. Việc sinh PPTX chạy hoàn toàn ở trình duyệt bằng PptxGenJS nhúng sẵn, nạp lười ở lần bấm đầu tiên. Logic dựng PPTX nằm ở tệp JS riêng, không biết gì về DOM, nên chạy được cả trong Node để kiểm chứng tự động.

**Tech Stack:** JavaScript thuần ES5 (không framework, không build step), PptxGenJS 3.12.0 (MIT), Node 24 chỉ dùng để kiểm chứng.

## Global Constraints

- JavaScript viết theo phong cách ES5 đang có trong dự án: `var`, `function`, không arrow function, không template literal, không `const`/`let`. Áp cho **cả** `public/js/giaoban/pptx.js`.
- Chú thích trong mã viết **tiếng Việt không dấu**.
- Không sửa bất kỳ tệp PHP nào: không controller, không service, không route, không API.
- **Không xuất PDF.** Ngoài phạm vi.
- Không đổi bảng `.bdt` (slide Hoạt động điều trị) về mặt hiển thị.
- Sau Task 2, HTML của cả bốn loại slide phải **giống hệt** trước khi tách tầng mô tả deck.
- `pptx.js` **không được đọc DOM**. Mọi thứ nó cần đến từ tham số.
- Cỡ chữ nội dung nhân `var(--z)`; thanh điều khiển `#bar` thì không.
- Thư viện đặt ở `public/js/vendor/pptxgen.bundle.js`, nhúng nguyên bản, **không sửa**, commit kèm repo (dự án không có npm nên không khôi phục lại được bằng lệnh cài).

## Cách kiểm chứng thủ công (dùng lại ở mọi nhiệm vụ)

Trang kiểm thử sinh tự động từ chính tệp Blade, có sẵn từ đợt trước:

```bash
node "C:/Users/tracnn/AppData/Local/Temp/claude/C--Users-tracnn-qlbv/c12fd085-976b-4f10-bad5-30b2b0c26095/scratchpad/harness.js"
```

Nó ghi ra `storage/app/giaoban-present-test.html` (thư mục này đã nằm trong `.gitignore`). Mở bằng preview pane với đường dẫn `file:///C:/Users/tracnn/qlbv/storage/app/giaoban-present-test.html`. Thêm tham số `no-overview` để sinh bản không khoa nào tích tiêu chí overview.

**Dọn tệp kiểm thử trước mỗi lần commit.**

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `public/js/vendor/pptxgen.bundle.js` (tạo) | Thư viện PptxGenJS nguyên bản |
| `public/js/giaoban/pptx.js` (tạo) | Dựng PPTX từ mô tả deck + bảng màu. Không chạm DOM, chạy được ở Node |
| `resources/views/khth/giaoban-present.blade.php` (sửa) | `moTaDeck()`, bộ dựng HTML, nút xuất và trạng thái nút |

Tệp Blade đã khoảng 640 dòng nên phần dựng PPTX (vài trăm dòng) tách ra ngoài, theo đúng nếp `public/js/giaoban/metric-builder.js` đang có.

---

### Task 1: Nhúng thư viện PptxGenJS

**Files:**
- Create: `public/js/vendor/pptxgen.bundle.js`

**Interfaces:**
- Produces: tệp thư viện. Trong trình duyệt nó đặt `window.PptxGenJS`; trong Node `require()` trả về constructor. Task 3 và Task 4 dựa vào cả hai lối này.

- [ ] **Step 1: Tải thư viện**

```bash
mkdir -p public/js/vendor && curl -fsSL https://cdn.jsdelivr.net/npm/pptxgenjs@3.12.0/dist/pptxgen.bundle.js -o public/js/vendor/pptxgen.bundle.js && ls -la public/js/vendor/
```

Kỳ vọng: tệp khoảng 1MB.

- [ ] **Step 2: Kiểm tra nạp được trong Node**

Ghi `<scratchpad>/kiem-thu-vien.js`:

```js
var PptxGenJS = require('C:/Users/tracnn/qlbv/public/js/vendor/pptxgen.bundle.js');
var pptx = new PptxGenJS();
pptx.layout = 'LAYOUT_16x9';
var s = pptx.addSlide();
s.addText('Kiểm tra tiếng Việt có dấu', { x: 0.5, y: 0.5, w: 6, h: 0.6, fontSize: 20 });
pptx.writeFile({ fileName: process.argv[2] }).then(function (f) { console.log('OK ' + f); });
```

Chạy:

```bash
node "<scratchpad>/kiem-thu-vien.js" "<scratchpad>/smoke.pptx"
```

Kỳ vọng: in `OK` và tệp `smoke.pptx` tồn tại, kích thước > 10KB.

**Kết quả thực tế: `require()` thẳng KHÔNG chạy được.** Bundle gộp JSZip trước; UMD của JSZip thấy `module` nên đẩy mình vào `module.exports` thay vì gán biến toàn cục, rồi phần PptxGenJS lại tìm `JSZip` ở toàn cục → `ReferenceError: JSZip is not defined`.

Dùng shim `vm` dưới đây (vẫn **không sửa** tệp thư viện). Điểm mấu chốt: ngữ cảnh **không** có `module`/`exports` (để JSZip gán vào toàn cục của ngữ cảnh) và **không** có `window` (để PptxGenJS hiểu đang chạy ở Node và ghi tệp bằng `fs`):

```js
var fs = require('fs'), vm = require('vm');
var ma = fs.readFileSync('C:/Users/tracnn/qlbv/public/js/vendor/pptxgen.bundle.js', 'utf8');
var hop = {
  require: require, Buffer: Buffer, console: console, process: process,
  setTimeout: setTimeout, clearTimeout: clearTimeout,
  setInterval: setInterval, clearInterval: clearInterval,
  setImmediate: setImmediate, TextDecoder: TextDecoder, TextEncoder: TextEncoder,
  Promise: Promise, URL: URL
};
hop.global = hop;
vm.createContext(hop);
vm.runInContext(ma, hop);
var PptxGenJS = hop.PptxGenJS;
```

Shim này nằm ở `<scratchpad>/nap-lib.js` và được Task 3 dùng lại. **Chỉ dùng cho kiểm chứng ở Node** — trong trình duyệt thẻ `<script>` nạp bình thường và thư viện tự gắn vào `window.PptxGenJS`.

- [ ] **Step 3: Kiểm tra tệp sinh ra là PPTX hợp lệ**

```bash
powershell -NoProfile -Command "Copy-Item '<scratchpad>/smoke.pptx' '<scratchpad>/smoke.zip' -Force; Remove-Item '<scratchpad>/smoke' -Recurse -Force -ErrorAction SilentlyContinue; Expand-Archive '<scratchpad>/smoke.zip' '<scratchpad>/smoke'; Get-ChildItem '<scratchpad>/smoke' | Select-Object -ExpandProperty Name"
```

Kỳ vọng: thấy `[Content_Types].xml`, `ppt`, `_rels`, `docProps`.

Rồi kiểm chữ có dấu còn nguyên:

```bash
grep -c 'Kiểm tra tiếng Việt có dấu' "<scratchpad>/smoke/ppt/slides/slide1.xml"
```

Kỳ vọng: `1`.

- [ ] **Step 4: Commit**

```bash
git add public/js/vendor/pptxgen.bundle.js && git commit -m "chore(giaoban): nhung thu vien PptxGenJS 3.12.0 de xuat PPTX"
```

---

### Task 2: Tầng mô tả deck (HTML không đổi)

Nhiệm vụ này **không được làm đổi giao diện**. Kiểm chứng là "HTML giống hệt trước".

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — `oSoLieu`, `bangChiTieu`, bốn hàm dựng slide, `build`

**Interfaces:**
- Consumes: `esc`, `num`, `fmtDate`, `cellVal`, `cellNote`, `noteOf`, `theTongQuan`, `khoaThieuBatBuoc`, `laChiTieuChuoi`, `kpiClass`, `capColor`, `donutHtml` — đã có sẵn.
- Produces:
  - `moTaDeck(data)` → mảng mô tả slide (cấu trúc đầy đủ ở Step 2).
  - `veSlide(s)` → chuỗi HTML một slide.
  - `oSoLieu(v, mau)` đổi tham số thứ hai từ `' teal'` (có dấu cách đầu) sang `'teal'` (không dấu cách).
  - Task 3 và Task 4 đọc **đúng** cấu trúc mô tả này.

- [ ] **Step 1: Đổi `oSoLieu` nhận màu không có dấu cách đầu**

Thay:

```js
  function oSoLieu(v, cls) {
    var s = num(v);
    var laSo = /^-?[\d.,]+%?$/.test(s);
    return '<td class="' + (laSo ? 'so' + (cls || '') : 'khuyet') + '">' + s + '</td>';
  }
```

bằng:

```js
  function oSoLieu(v, mau) {
    var s = num(v);
    var laSo = /^-?[\d.,]+%?$/.test(s);
    return '<td class="' + (laSo ? 'so' + (mau ? ' ' + mau : '') : 'khuyet') + '">' + s + '</td>';
  }
```

- [ ] **Step 2: Thêm `moTaDeck()`**

Chèn ngay **trước** `function bangChiTieu` (trước dòng khai báo hàm đó):

```js
  /**
   * Mo ta toan bo deck duoi dang du lieu thuan, KHONG chua HTML.
   *
   * Bo dung HTML va bo dung PPTX cung doc tu day, nen khong co ban sao logic thu hai.
   * PPTX tuyet doi khong duoc doc nguoc tu DOM: doi mot ten class la hong ban xuat ma
   * khong test nao bat duoc.
   *
   * Thu tu giu nguyen nhu truoc: Tong quan -> Hoat dong dieu tri -> tung khoa (sort_order)
   * -> Cong suat giuong. Slide thieu du lieu thi bo qua, khong tao muc rong.
   *
   * Truong `ten` la nhan cho nut nhay khoa; `tieuDe` la chu in tren slide. Hai thu nay khac
   * nhau o slide Tong quan.
   *
   * `noiDung` cua khoi chuoi va `ghiChu` giu NGUYEN chuoi may chu tra ve — no da qua
   * htmlspecialchars nen cam vao HTML la dung, con PPTX phai giai ma nguoc (xem pptx.js).
   */
  function moTaDeck(data) {
    var ngay = fmtDate(DATE);
    var deck = [];

    deck.push(moTaTongQuan(data, ngay));

    var dt = moTaDieuTri(data, ngay);
    if (dt) deck.push(dt);

    data.configs.forEach(function (cfg) { deck.push(moTaKhoa(data, cfg, ngay)); });

    var cs = moTaCongSuat(data, ngay);
    if (cs) deck.push(cs);

    return deck;
  }

  function moTaTongQuan(data, ngay) {
    var r = data.report;

    var duties = (data.duties || []).filter(function (d) { return (d.person_name || '').trim() !== ''; });
    var theoViTri = {};
    duties.forEach(function (d) { (theoViTri[d.position_id] = theoViTri[d.position_id] || []).push(d); });
    var kipTruc = [];
    (data.duty_positions || []).forEach(function (p) {
      var ds = theoViTri[p.id];
      if (!ds || !ds.length) return;
      kipTruc.push({
        viTri: p.name,
        nguoi: ds.map(function (d) { return { ten: d.person_name, dienThoai: d.phone || '' }; })
      });
    });

    var dsThieu = khoaThieuBatBuoc(data);
    var chuThieu = dsThieu.length
      ? dsThieu.length + ' khoa: ' + dsThieu.map(function (x) { return x.ten + ' (' + x.so + ')'; }).join(' · ')
      : 'Các khoa đã nhập đủ';

    return {
      loai: 'tong-quan',
      ten: 'Tổng quan',
      tieuDe: 'Giao ban ' + ngay,
      ngay: ngay,
      badge: (r && r.status === 'final')
        ? { chu: 'ĐÃ CHỐT', loai: 'chot' }
        : { chu: 'BẢN NHÁP', loai: 'nhap' },
      phuDe: r ? ('Số liệu ' + r.from_time + ' → ' + r.to_time) : '',
      kipTruc: kipTruc,
      items: theTongQuan(data).map(function (t) {
        return { nhan: t.nhan, giaTri: t.tong, mau: (t.cls || '').replace(/^\s+/, '') };
      }),
      canhBao: { dat: dsThieu.length === 0, chu: chuThieu },
      ghiChu: (r && r.general_note) ? String(r.general_note).trim() : ''
    };
  }

  function moTaDieuTri(data, ngay) {
    var b = data.bang_dieu_tri;
    if (!b || !b.cot || !b.cot.length || !b.dong || !b.dong.length) return null;
    return {
      loai: 'dieu-tri',
      ten: 'Hoạt động điều trị',
      tieuDe: 'Hoạt động điều trị',
      ngay: ngay,
      cot: b.cot.map(function (c) { return c.nhan; }),
      dong: b.dong.map(function (d) { return { ten: d.ten, o: d.o.slice() }; }),
      tong: b.tong.slice()
    };
  }

  function moTaKhoa(data, cfg, ngay) {
    var khoiChuoi = [];
    cfg.metrics.filter(laChiTieuChuoi).forEach(function (m) {
      var t = cellNote(data, cfg.id, m.code);
      if (!t || String(t).trim() === '') return;
      khoiChuoi.push({ nhan: m.name, noiDung: t });
    });
    var gc = noteOf(data, cfg.id);
    return {
      loai: 'khoa',
      ten: cfg.display_name,
      tieuDe: cfg.display_name,
      ngay: ngay,
      lechCanDoi: (data.balance_warnings && data.balance_warnings[cfg.id]) || null,
      items: cfg.metrics.filter(function (m) { return !laChiTieuChuoi(m); }).map(function (m) {
        return {
          nhan: m.name,
          giaTri: cellVal(data, cfg.id, m.code),
          mau: (kpiClass(m) || '').replace(/^\s+/, '')
        };
      }),
      khoiChuoi: khoiChuoi,
      ghiChu: (gc && String(gc).trim() !== '') ? gc : ''
    };
  }

  function moTaCongSuat(data, ngay) {
    // Uu tien khoa bao cao dieu tri; khong co thi dung tung khoa HIS co giuong (nhu dashboard).
    var nguon = (data.bed_by_config && data.bed_by_config.length)
      ? data.bed_by_config : (data.bed_by_department || []);
    var theoKhoa = nguon.filter(function (b) { return Number(b.total) > 0; })
      .map(function (b) {
        var t = Number(b.total), u = Number(b.used);
        return { ten: b.display_name, dung: u, tong: t, pct: Math.round(u / t * 100) };
      })
      .sort(function (a, b) { return b.pct - a.pct; });

    var tongGiuong = Number(data.bed_total || 0);
    var donut = null;
    if (tongGiuong > 0) {
      var dung = Number(data.bed_used || 0);
      donut = {
        tong: tongGiuong,
        dung: dung,
        trong: Math.max(0, tongGiuong - dung),
        pct: Math.round(dung / tongGiuong * 100)
      };
    }

    if (!theoKhoa.length && !donut) return null;
    return { loai: 'cong-suat', ten: 'Công suất giường', tieuDe: 'Công suất giường',
      ngay: ngay, donut: donut, theoKhoa: theoKhoa };
  }
```

- [ ] **Step 3: Đổi `bangChiTieu` đọc mô tả**

Trong `bangChiTieu`, thay dòng dựng ô:

```js
        tbody += '<td class="ten">' + esc(it.nhan) + '</td>' + oSoLieu(it.gia_tri, it.cls);
```

bằng:

```js
        tbody += '<td class="ten">' + esc(it.nhan) + '</td>' + oSoLieu(it.giaTri, it.mau);
```

- [ ] **Step 4: Thay bốn hàm dựng slide bằng bộ dựng đọc mô tả**

Xoá **toàn bộ** bốn hàm `overviewSlide`, `dieuTriSlide`, `capacityDeptSlide`, `deptSlide` (kể cả khối chú thích dài phía trên `dieuTriSlide` — chép lại nguyên văn vào hàm mới bên dưới), thay bằng:

```js
  function veSlide(s) {
    if (s.loai === 'tong-quan') return veTongQuan(s);
    if (s.loai === 'dieu-tri') return veDieuTri(s);
    if (s.loai === 'khoa') return veKhoa(s);
    return veCongSuat(s);
  }

  function veTongQuan(s) {
    var bangHtml = bangChiTieu(s.items, false);
    if (bangHtml === '') {
      // Cau huong dan goi dung ten nut that ben man Cau hinh giao ban.
      bangHtml = '<div class="note" style="margin-top:2vh"><div class="lbl">CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO</div>' +
        '<div class="txt">Vào Cấu hình giao ban → mở Tiêu chí của khoa → tích "Hiện ở màn Tổng quan".</div></div>';
    }

    var dutyHtml = '';
    if (s.kipTruc.length) {
      // Khoi nay nam DAU slide Tong quan nen dung margin-bottom, khong phai margin-top.
      dutyHtml = '<div class="panel" style="margin-bottom:1.6vh"><div class="lbl">KÍP TRỰC LÃNH ĐẠO</div>' +
        '<div style="display:flex;flex-wrap:wrap;gap:1vh 2vw">';
      s.kipTruc.forEach(function (v) {
        var names = v.nguoi.map(function (n) {
          return '<b style="color:var(--strong)">' + esc(n.ten) + '</b>' +
            (n.dienThoai ? ' <span style="color:var(--brand)">' + esc(n.dienThoai) + '</span>' : '');
        }).join(', ');
        dutyHtml += '<div style="font-size:calc(2.38vh * var(--z))"><span style="color:var(--muted)">' +
          esc(v.viTri) + ':</span> ' + names + '</div>';
      });
      dutyHtml += '</div></div>';
    }

    var noteHtml = s.ghiChu !== ''
      ? '<div class="note" style="margin-top:1.6vh"><div class="lbl">GHI CHÚ CHUNG</div><div class="txt">' +
        s.ghiChu + '</div></div>'
      : '';

    // Chi con MOT khoi canh bao tren man tong hop. Lech can doi da bo khoi day theo yeu cau
    // su dung: no van hien o badge tren slide tung khoa va o man nhap lieu — hai cho co ngu
    // canh de xu ly, con man tong hop thi chi lam nhieu.
    var thieuHtml = '<div class="ov-canh-bao' + (s.canhBao.dat ? ' tot' : ' xau') + '">' +
      '<span class="lbl">Ô BẮT BUỘC CÒN TRỐNG</span> ' + esc(s.canhBao.chu) + '</div>';

    var trangThai = '<span class="ov-badge ' + s.badge.loai + '">' + s.badge.chu + '</span>';

    return '<div class="slide"><div class="s-head"><div>' +
      '<div class="s-brand">BÁO CÁO GIAO BAN</div>' +
      '<div class="s-title">' + esc(s.tieuDe) + ' ' + trangThai + '</div></div>' +
      '<div class="s-sub">' + esc(s.phuDe) + '</div></div>' +
      // Kip truc len DAU: nguoi du giao ban can biet ngay ai truc truoc khi doc so lieu.
      dutyHtml +
      bangHtml +
      thieuHtml + noteHtml + '</div>';
  }

  /**
   * Bang tong hop khoi Dieu tri noi tru.
   *
   * May chu da dung san cau truc (App\Services\GiaoBan\BangDieuTri): loc khoi, loc quyen,
   * gop cot theo nhan, quy null ve 0, bo tong cot phan tram. O day CHI VE.
   *
   * Co chu nho dan theo so cot: bay khoa moi khoa vai chi tieu rieng thi de len 20+ cot,
   * giu co chu goc thi tran khoi man chieu. Co san toi thieu; cham san van tran thi
   * .bdt-wrap cho cuon.
   *
   * Cac nguong duoi chi tang 15%, khong phai 25% nhu phan con lai cua trinh chieu: bang nay
   * la cho chat nhat, tang manh la bang nhieu cot phai cuon — ma chieu len tuong thi phan
   * phai cuon coi nhu mat du lieu.
   */
  function veDieuTri(s) {
    var soCot = s.cot.length;
    var co = soCot <= 8 ? 2.3 : (soCot <= 14 ? 1.95 : (soCot <= 20 ? 1.65 : 1.45));

    var thead = '<tr><th class="ten">KHOA PHÒNG</th>' +
      s.cot.map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('') + '</tr>';

    var tbody = s.dong.map(function (d) {
      return '<tr><td class="ten">' + esc(d.ten) + '</td>' +
        d.o.map(function (v) { return oSoLieu(v); }).join('') + '</tr>';
    }).join('');

    var tfoot = '<tr class="tong"><td class="ten">TỔNG CỘNG</td>' +
      s.tong.map(function (v) { return oSoLieu(v); }).join('') + '</tr>';

    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(s.tieuDe) + '</div>' +
      '<div class="s-sub">Giao ban ' + esc(s.ngay) + '</div></div>' +
      '<div class="bdt-wrap"><table class="bdt" style="font-size:calc(' + co + 'vh * var(--z))">' +
      '<thead>' + thead + '</thead><tbody>' + tbody + tfoot + '</tbody></table></div></div>';
  }

  function veKhoa(s) {
    var warn = s.lechCanDoi
      ? '<span class="warn" title="Lệch cân đối">▲ ' + num(s.lechCanDoi) + '</span>' : '';
    var bangHtml = bangChiTieu(s.items, true);

    // Moi chi tieu chuoi mot khoi, dung lai class .note.
    // Noi dung da qua htmlspecialchars o server nen chen thang vao HTML se hien dung dau < >;
    // xuong dong la ky tu \n that -> can white-space: pre-wrap (class .txt-pre).
    var chuoiHtml = s.khoiChuoi.map(function (k) {
      return '<div class="note"><div class="lbl">' + esc(k.nhan) +
        '</div><div class="txt txt-pre">' + k.noiDung + '</div></div>';
    }).join('');
    if (chuoiHtml) chuoiHtml = '<div class="ds-chuoi">' + chuoiHtml + '</div>';

    var noteHtml = s.ghiChu !== ''
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + s.ghiChu + '</div></div>' : '';

    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(s.tieuDe) + warn +
      '</div><div class="s-sub">Giao ban ' + esc(s.ngay) + '</div></div>' +
      bangHtml + chuoiHtml + noteHtml + '</div>';
  }

  function veCongSuat(s) {
    var capHtml = '';
    if (s.theoKhoa.length) {
      var rowsC = s.theoKhoa.map(function (x) {
        var col = capColor(x.pct);
        return '<div class="caprow"><div class="capname">' + esc(x.ten) + '</div>' +
          '<div class="captrack"><div class="capfill" style="width:' + Math.min(100, x.pct) +
          '%;background:' + col + '"></div></div>' +
          '<div class="cappct" style="color:' + col + '">' + x.pct + '%</div>' +
          '<div class="capnum">' + x.dung + '/' + x.tong + '</div></div>';
      }).join('');
      capHtml = '<div class="panel"><div class="lbl">CÔNG SUẤT GIƯỜNG THEO KHOA</div>' +
        '<div class="caplist">' + rowsC + '</div></div>';
    }

    // Donut tong vien nam cung cho voi cong suat theo khoa, de moi thu ve giuong mot slide.
    var donut = s.donut ? donutHtml(s.donut.dung, s.donut.tong) : '';

    // Donut mot cot, cong suat theo khoa mot cot.
    var cols = (donut && capHtml) ? 'minmax(24vw,1fr) 2fr' : '1fr';

    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(s.tieuDe) + '</div>' +
      '<div class="s-sub">Giao ban ' + esc(s.ngay) + '</div></div>' +
      '<div class="cap-grid" style="grid-template-columns:' + cols + '">' + donut + capHtml + '</div></div>';
  }
```

- [ ] **Step 5: Đổi `build()` đi qua mô tả deck**

Thay khối từ `// Thu tu: Tong quan -> ...` đến hết đoạn dựng `capHtml` (tức từ dòng chú thích thứ tự cho tới trước `var stage = document.getElementById('stage');`) bằng:

```js
    // Thu tu do moTaDeck() quyet dinh. deptNames bam theo dung thu tu ay, khong thi bam ten
    // khoa se nhay sai slide.
    deck = moTaDeck(data);
    deck.forEach(function (s) {
      deptNames.push({ idx: slides.length, name: s.ten });
      slides.push(veSlide(s));
    });
```

Khai báo `deck` cạnh các biến trạng thái sẵn có. Thay:

```js
  var slides = [];
  var current = 0;
  var deptNames = [];
```

bằng:

```js
  var slides = [];
  var current = 0;
  var deptNames = [];
  var deck = []; // mo ta deck, nut xuat PPTX doc lai tu day
```

- [ ] **Step 6: Kiểm chứng HTML không đổi**

Trước khi sinh lại trang kiểm thử, lưu bản HTML cũ để so:

```bash
git stash && node "<scratchpad>/harness.js" && cp "C:/Users/tracnn/qlbv/storage/app/giaoban-present-test.html" "<scratchpad>/truoc.html" && git stash pop && node "<scratchpad>/harness.js"
```

Mở trang kiểm thử trong preview pane rồi chạy đoạn sau trong console để lấy chữ ký HTML của **mọi** slide:

```js
(function(){var out=[];var els=document.querySelectorAll('#stage .slide');
for(var i=0;i<els.length;i++)out.push(els[i].outerHTML);
return {so:els.length, tong:out.join('').length, bam:out.join('').replace(/\s+/g,' ').slice(0,0)||'', ds:out.map(function(h){return h.replace(/\s+/g,' ').length;})};})()
```

Làm y hệt với `truoc.html`. Kỳ vọng: **cùng số slide, cùng độ dài từng slide**.

Sau đó đối chiếu chi tiết bằng cách so hai tệp HTML sinh ra: phần khác duy nhất được phép là không có gì.

```bash
diff <(sed -n '/<script>/,$p' "<scratchpad>/truoc.html" | head -0) /dev/null; node -e "
var fs=require('fs');
function slides(p){var h=fs.readFileSync(p,'utf8');return h.length;}
console.log('truoc', slides('<scratchpad>/truoc.html'));
"
```

Kiểm chứng bằng mắt trên preview pane: duyệt hết mọi slide ở cả hai theme, không thấy khác biệt nào. Console không lỗi.

Chạy thêm bản `no-overview` và xác nhận vẫn hiện khối "CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO".

- [ ] **Step 7: Dọn và commit**

```bash
rm -f storage/app/giaoban-present-test.html storage/app/giaoban-present-test-no-overview.html && git add resources/views/khth/giaoban-present.blade.php && git commit -m "refactor(giaoban): tach tang mo ta deck khoi viec sinh HTML"
```

---

### Task 3: Bộ dựng PPTX

**Files:**
- Create: `public/js/giaoban/pptx.js`

**Interfaces:**
- Consumes: cấu trúc mô tả deck từ Task 2 (`loai`, `ten`, `tieuDe`, `ngay`, `badge`, `phuDe`, `kipTruc`, `items`, `canhBao`, `ghiChu`, `cot`, `dong`, `tong`, `lechCanDoi`, `khoiChuoi`, `donut`, `theoKhoa`); thư viện từ Task 1.
- Produces:
  - `xuatPptx(deck, mau, tenTep, PptxGenJS)` → Promise. Task 4 gọi hàm này.
  - `mau` là object với các khoá: `bg`, `strong`, `muted`, `txt2`, `panel2`, `line2`, `teal`, `amber`, `red`, `blue`, `brand` — mỗi giá trị là chuỗi `RRGGBB` không có dấu thăng.
  - Trong trình duyệt hàm nằm ở `window.GiaoBanPptx.xuatPptx`; trong Node ở `module.exports.xuatPptx`.

- [ ] **Step 1: Viết `public/js/giaoban/pptx.js`**

```js
/**
 * Dung tep PPTX tu mo ta deck cua man trinh chieu giao ban.
 *
 * Tep nay KHONG duoc doc DOM: moi thu no can den tu tham so. Nho vay no chay duoc ca trong
 * Node de kiem chung tu dong — thu khong lam duoc neu ma nam trong Blade.
 *
 * Moi slide dung bang doi tuong PowerPoint THAT (bang, hop van ban, bieu do) chu khong phai
 * anh: nguoi nhan con phai sua lai noi dung va chieu o cuoc hop khac.
 */
(function (root) {
  'use strict';

  var W = 10, H = 5.625;       // khung 16:9 tinh bang inch
  var LE = 0.4;                // le trai/phai
  var RONG = W - LE * 2;
  var Y_NOI_DUNG = 0.95;       // day cua khoi tieu de

  /** htmlspecialchars nguoc. May chu da ma hoa 5 ky tu nay truoc khi tra ve. */
  function giaiMa(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"').replace(/&#0?39;/g, "'")
      .replace(/&amp;/g, '&');
  }

  function so(v) {
    return v === null || v === undefined || v === ''
      ? '—' : String(Math.round(Number(v) * 100) / 100);
  }

  function laSo(s) { return /^-?[\d.,]+%?$/.test(s); }

  /** Mau cua o so: theo nhan teal/amber neu co, khong thi mau chu thuong. */
  function mauO(m, mau) {
    if (m === 'teal') return mau.teal;
    if (m === 'amber') return mau.amber;
    return mau.strong;
  }

  function mauTheoPct(pct, mau) {
    return pct >= 90 ? mau.red : pct >= 80 ? mau.amber : pct >= 60 ? mau.teal : mau.blue;
  }

  /** Khung chung: nen, tieu de trai, ngay phai, duong ke duoi tieu de. */
  function khungSlide(pptx, s, mau) {
    var sl = pptx.addSlide();
    sl.background = { color: mau.bg };
    var tieuDe = s.tieuDe + (s.badge ? '   ' + s.badge.chu : '');
    sl.addText(tieuDe, { x: LE, y: 0.22, w: RONG - 2.6, h: 0.5,
      fontSize: 24, bold: true, color: mau.strong, valign: 'middle' });
    sl.addText('Giao ban ' + s.ngay, { x: W - LE - 2.6, y: 0.28, w: 2.6, h: 0.38,
      fontSize: 11, color: mau.muted, align: 'right', valign: 'middle' });
    sl.addShape(pptx.ShapeType.rect, { x: LE, y: 0.82, w: RONG, h: 0.014,
      fill: { color: mau.line2 } });
    return sl;
  }

  function oTieuDe(chu, mau) {
    return { text: chu, options: { align: 'center', bold: true, color: mau.muted,
      fill: { color: mau.panel2 } } };
  }

  /**
   * Bang tieu chi: moi dong hai cap "Tieu chi | So lieu", giong het tren man chieu.
   * So chi tieu le thi cap cuoi de trong nhung van co vien.
   */
  function bangTieuChi(sl, items, mau, y, cao) {
    var CAP = 2, soDong = Math.ceil(items.length / CAP), rows = [], r, k;

    var head = [];
    for (k = 0; k < CAP; k++) { head.push(oTieuDe('TIÊU CHÍ', mau)); head.push(oTieuDe('SỐ LIỆU', mau)); }
    rows.push(head);

    for (r = 0; r < soDong; r++) {
      var hang = [];
      for (k = 0; k < CAP; k++) {
        var it = items[r * CAP + k];
        if (!it) {
          hang.push({ text: '', options: {} });
          hang.push({ text: '', options: {} });
          continue;
        }
        var v = so(it.giaTri);
        hang.push({ text: it.nhan, options: { align: 'left', color: mau.strong } });
        hang.push({ text: v, options: laSo(v)
          ? { align: 'right', bold: true, color: mauO(it.mau, mau) }
          : { align: 'left', color: mau.txt2 } });
      }
      rows.push(hang);
    }

    var coChu = soDong <= 6 ? 14 : (soDong <= 10 ? 12 : (soDong <= 14 ? 10 : 9));
    sl.addTable(rows, {
      x: LE, y: y, w: RONG, h: cao,
      colW: [RONG * 0.3, RONG * 0.2, RONG * 0.3, RONG * 0.2],
      border: { type: 'solid', pt: 0.5, color: mau.line2 },
      fontSize: coChu, color: mau.txt2, valign: 'middle', autoPage: false
    });
  }

  function veTongQuan(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var y = Y_NOI_DUNG;

    if (s.phuDe) {
      sl.addText(s.phuDe, { x: LE, y: y, w: RONG, h: 0.26, fontSize: 10, color: mau.muted });
      y += 0.3;
    }

    if (s.kipTruc.length) {
      var dong = s.kipTruc.map(function (v) {
        return v.viTri + ': ' + v.nguoi.map(function (n) {
          return n.ten + (n.dienThoai ? ' (' + n.dienThoai + ')' : '');
        }).join(', ');
      }).join('    •    ');
      sl.addText('KÍP TRỰC LÃNH ĐẠO', { x: LE, y: y, w: RONG, h: 0.24, fontSize: 10, color: mau.muted });
      sl.addText(dong, { x: LE, y: y + 0.24, w: RONG, h: 0.34, fontSize: 12, color: mau.strong });
      y += 0.66;
    }

    if (s.items.length) {
      var caoBang = Math.min(2.6, 0.32 * (Math.ceil(s.items.length / 2) + 1));
      bangTieuChi(sl, s.items, mau, y, caoBang);
      y += caoBang + 0.18;
    } else {
      sl.addText('CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO', { x: LE, y: y, w: RONG, h: 0.3,
        fontSize: 12, bold: true, color: mau.amber });
      y += 0.36;
    }

    sl.addText('Ô BẮT BUỘC CÒN TRỐNG:  ' + s.canhBao.chu, { x: LE, y: y, w: RONG, h: 0.32,
      fontSize: 11, color: s.canhBao.dat ? mau.teal : mau.red });
    y += 0.4;

    if (s.ghiChu) {
      sl.addText('GHI CHÚ CHUNG', { x: LE, y: y, w: RONG, h: 0.24, fontSize: 10, color: mau.muted });
      sl.addText(giaiMa(s.ghiChu), { x: LE, y: y + 0.24, w: RONG, h: H - y - 0.4,
        fontSize: 12, color: mau.txt2, valign: 'top' });
    }
    return sl;
  }

  function veDieuTri(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);

    var head = [oTieuDe('KHOA PHÒNG', mau)];
    s.cot.forEach(function (c) { head.push(oTieuDe(c, mau)); });

    var rows = [head];
    s.dong.forEach(function (d) {
      var hang = [{ text: d.ten, options: { align: 'left', color: mau.strong } }];
      d.o.forEach(function (v) {
        var t = so(v);
        hang.push({ text: t, options: laSo(t)
          ? { align: 'right', color: mau.txt2 } : { align: 'left', color: mau.txt2 } });
      });
      rows.push(hang);
    });

    var hangTong = [{ text: 'TỔNG CỘNG', options: { align: 'left', bold: true, color: mau.strong,
      fill: { color: mau.panel2 } } }];
    s.tong.forEach(function (v) {
      var t = so(v);
      hangTong.push({ text: t, options: { align: laSo(t) ? 'right' : 'left', bold: true,
        color: mau.strong, fill: { color: mau.panel2 } } });
    });
    rows.push(hangTong);

    var soCot = s.cot.length + 1;
    var wTen = Math.min(2.6, RONG * 0.28);
    var wSo = (RONG - wTen) / (soCot - 1);
    var colW = [wTen];
    for (var i = 1; i < soCot; i++) colW.push(wSo);

    sl.addTable(rows, {
      x: LE, y: Y_NOI_DUNG, w: RONG,
      colW: colW,
      border: { type: 'solid', pt: 0.5, color: mau.line2 },
      fontSize: soCot <= 8 ? 12 : (soCot <= 14 ? 10 : 8),
      color: mau.txt2, valign: 'middle', autoPage: false
    });
    return sl;
  }

  function veKhoa(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var y = Y_NOI_DUNG;

    if (s.lechCanDoi) {
      sl.addText('▲ Lệch cân đối: ' + so(s.lechCanDoi), { x: LE, y: y, w: RONG, h: 0.26,
        fontSize: 10, color: mau.amber });
      y += 0.3;
    }

    if (s.items.length) {
      var caoBang = Math.min(2.8, 0.3 * (Math.ceil(s.items.length / 2) + 1));
      bangTieuChi(sl, s.items, mau, y, caoBang);
      y += caoBang + 0.18;
    }

    s.khoiChuoi.forEach(function (k) {
      if (y > H - 0.6) return; // het cho tren slide, bo qua phan con lai
      sl.addText(k.nhan, { x: LE, y: y, w: RONG, h: 0.22, fontSize: 10, color: mau.amber });
      sl.addText(giaiMa(k.noiDung), { x: LE, y: y + 0.22, w: RONG, h: 0.5,
        fontSize: 11, color: mau.txt2, valign: 'top' });
      y += 0.78;
    });

    if (s.ghiChu && y <= H - 0.6) {
      sl.addText('Ghi chú khoa', { x: LE, y: y, w: RONG, h: 0.22, fontSize: 10, color: mau.amber });
      sl.addText(giaiMa(s.ghiChu), { x: LE, y: y + 0.22, w: RONG, h: H - y - 0.4,
        fontSize: 11, color: mau.txt2, valign: 'top' });
    }
    return sl;
  }

  function veCongSuat(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var coDonut = !!s.donut, coKhoa = s.theoKhoa.length > 0;

    if (coDonut) {
      sl.addChart(pptx.ChartType.doughnut,
        [{ name: 'Công suất giường', labels: ['Đang dùng', 'Trống'],
           values: [s.donut.dung, s.donut.trong] }],
        { x: LE, y: Y_NOI_DUNG, w: coKhoa ? 3.3 : RONG, h: H - Y_NOI_DUNG - 0.3,
          holeSize: 55, showLegend: true, legendPos: 'b', legendColor: mau.muted,
          showValue: true, dataLabelColor: mau.bg, dataLabelFontSize: 11,
          chartColors: [mauTheoPct(s.donut.pct, mau), mau.muted],
          title: s.donut.pct + '% — ' + s.donut.dung + '/' + s.donut.tong + ' giường',
          showTitle: true, titleColor: mau.strong, titleFontSize: 12 });
    }

    if (coKhoa) {
      var x = coDonut ? LE + 3.5 : LE;
      sl.addChart(pptx.ChartType.bar,
        [{ name: 'Công suất %', labels: s.theoKhoa.map(function (k) { return k.ten; }),
           values: s.theoKhoa.map(function (k) { return k.pct; }) }],
        { x: x, y: Y_NOI_DUNG, w: W - LE - x, h: H - Y_NOI_DUNG - 0.3,
          barDir: 'bar', valAxisMaxVal: 100, showValue: true, dataLabelColor: mau.txt2,
          dataLabelFontSize: 9, catAxisLabelColor: mau.txt2, catAxisLabelFontSize: 9,
          valAxisLabelColor: mau.muted, valAxisLabelFontSize: 9,
          chartColors: [mau.blue], showLegend: false });
    }
    return sl;
  }

  function veSlide(pptx, s, mau) {
    if (s.loai === 'tong-quan') return veTongQuan(pptx, s, mau);
    if (s.loai === 'dieu-tri') return veDieuTri(pptx, s, mau);
    if (s.loai === 'khoa') return veKhoa(pptx, s, mau);
    return veCongSuat(pptx, s, mau);
  }

  /**
   * `PptxGenJS` nhan qua tham so chu khong doc bien toan cuc, de Node truyen ban require() vao.
   */
  function xuatPptx(deck, mau, tenTep, PptxGenJS) {
    var pptx = new PptxGenJS();
    pptx.layout = 'LAYOUT_16x9';
    deck.forEach(function (s) { veSlide(pptx, s, mau); });
    return pptx.writeFile({ fileName: tenTep });
  }

  var api = { xuatPptx: xuatPptx, giaiMa: giaiMa };
  if (typeof module === 'object' && module.exports) module.exports = api;
  else root.GiaoBanPptx = api;
})(typeof window !== 'undefined' ? window : this);
```

- [ ] **Step 2: Viết kịch bản kiểm chứng ở Node**

Ghi `<scratchpad>/kiem-pptx.js`:

```js
// Sinh PPTX that tu mo ta deck mau, roi giai nen doi chieu XML.
var path = require('path');
var PptxGenJS = require('C:/Users/tracnn/qlbv/public/js/vendor/pptxgen.bundle.js');
var pptx = require('C:/Users/tracnn/qlbv/public/js/giaoban/pptx.js');

var MAU_TOI = { bg: '0D1B2A', strong: 'FFFFFF', muted: '8AA4BD', txt2: 'DBE6F0',
  panel2: '14293E', line2: '24405C', teal: '5DCAA5', amber: 'EF9F27', red: 'E57373',
  blue: '378ADD', brand: '6EA8D8' };
var MAU_SANG = { bg: 'F4F6F9', strong: '0F1A26', muted: '4F6577', txt2: '2B3949',
  panel2: 'EAF0F6', line2: 'C3D0DD', teal: '0E6D4E', amber: '8F5200', red: 'C53A3A',
  blue: '1668B3', brand: '1D6FA5' };

var deck = [
  { loai: 'tong-quan', ten: 'Tổng quan', tieuDe: 'Giao ban thứ Năm, 06/08/2026',
    ngay: 'thứ Năm, 06/08/2026', badge: { chu: 'BẢN NHÁP', loai: 'nhap' },
    phuDe: 'Số liệu 06/08/2026 07:00 → 07/08/2026 07:00',
    kipTruc: [{ viTri: 'Trực lãnh đạo', nguoi: [{ ten: 'BS. Nguyễn Văn A', dienThoai: '0912345678' }] }],
    items: [{ nhan: 'BN vào viện', giaTri: 30, mau: 'teal' },
            { nhan: 'BN ra viện', giaTri: 19, mau: 'amber' },
            { nhan: 'Tử vong', giaTri: 0, mau: '' }],
    canhBao: { dat: false, chu: '1 khoa: Hồi sức tích cực (1)' },
    ghiChu: 'Toàn viện an toàn, không có sự cố &lt;nghiêm trọng&gt;.' },
  { loai: 'dieu-tri', ten: 'Hoạt động điều trị', tieuDe: 'Hoạt động điều trị',
    ngay: 'thứ Năm, 06/08/2026',
    cot: ['Đầu kỳ', 'Vào viện', 'Ra viện', 'Công suất %'],
    dong: [{ ten: 'Nội tổng hợp', o: [128, 21, 19, 92.1] },
           { ten: 'Hồi sức tích cực', o: [22, 5, null, 55] }],
    tong: [150, 26, 19, null] },
  { loai: 'khoa', ten: 'Nội tổng hợp', tieuDe: 'Nội tổng hợp', ngay: 'thứ Năm, 06/08/2026',
    lechCanDoi: 3,
    items: [{ nhan: 'Bệnh nhân đầu kỳ', giaTri: 128, mau: '' },
            { nhan: 'Thở máy', giaTri: null, mau: '' },
            { nhan: 'Lọc máu', giaTri: 7, mau: '' }],
    khoiChuoi: [{ nhan: 'Diễn biến nổi bật', noiDung: 'Ca nặng phòng 305 đã ổn định.' }],
    ghiChu: 'Đề nghị bổ sung điều dưỡng.' },
  { loai: 'cong-suat', ten: 'Công suất giường', tieuDe: 'Công suất giường',
    ngay: 'thứ Năm, 06/08/2026',
    donut: { tong: 260, dung: 217, trong: 43, pct: 83 },
    theoKhoa: [{ ten: 'Nội tổng hợp', dung: 129, tong: 140, pct: 92 },
               { ten: 'Hồi sức tích cực', dung: 22, tong: 40, pct: 55 }] }
];

var raDir = process.argv[2];
pptx.xuatPptx(deck, MAU_TOI, path.join(raDir, 'toi.pptx'), PptxGenJS)
  .then(function () { return pptx.xuatPptx(deck, MAU_SANG, path.join(raDir, 'sang.pptx'), PptxGenJS); })
  .then(function () {
    // Slide thieu du lieu bi bo qua: deck khong co muc nao thi tep khong co slide nao.
    return pptx.xuatPptx([], MAU_TOI, path.join(raDir, 'rong.pptx'), PptxGenJS);
  })
  .then(function () { console.log('DA SINH 3 TEP'); })
  .catch(function (e) { console.error('LOI:', e && e.message); process.exit(1); });
```

- [ ] **Step 3: Chạy và giải nén**

```bash
node "<scratchpad>/kiem-pptx.js" "<scratchpad>"
```

Kỳ vọng: in `DA SINH 3 TEP`.

```bash
powershell -NoProfile -Command "foreach ($n in 'toi','sang','rong') { Copy-Item \"<scratchpad>/$n.pptx\" \"<scratchpad>/$n.zip\" -Force; Remove-Item \"<scratchpad>/x-$n\" -Recurse -Force -ErrorAction SilentlyContinue; Expand-Archive \"<scratchpad>/$n.zip\" \"<scratchpad>/x-$n\" }; Get-ChildItem '<scratchpad>/x-toi/ppt/slides' -Filter *.xml | Select-Object -ExpandProperty Name"
```

Kỳ vọng: `slide1.xml` … `slide4.xml`.

- [ ] **Step 4: Đối chiếu nội dung XML**

```bash
cd "<scratchpad>" && echo "== so slide toi:" && ls x-toi/ppt/slides/*.xml | wc -l && echo "== so slide rong:" && (ls x-rong/ppt/slides/*.xml 2>/dev/null | wc -l) && echo "== thu tu tieu de:" && for i in 1 2 3 4; do grep -o 'Giao ban thứ Năm[^<]*\|Hoạt động điều trị\|Nội tổng hợp\|Công suất giường' x-toi/ppt/slides/slide$i.xml | head -1; done && echo "== dong TONG CONG:" && grep -c 'TỔNG CỘNG' x-toi/ppt/slides/slide2.xml && echo "== o khuyet:" && grep -c '—' x-toi/ppt/slides/slide2.xml && echo "== bieu do:" && ls x-toi/ppt/charts/*.xml | wc -l
```

Kỳ vọng:
- số slide `toi` = `4`; số slide `rong` = `0`
- thứ tự tiêu đề: Tổng quan → Hoạt động điều trị → Nội tổng hợp → Công suất giường
- `TỔNG CỘNG` xuất hiện ít nhất 1 lần ở slide 2
- dấu `—` xuất hiện ở slide 2 (hai ô khuyết trong dữ liệu mẫu)
- có 2 tệp biểu đồ (doughnut + bar)

```bash
cd "<scratchpad>" && echo "== giai ma HTML entity:" && grep -c 'nghiêm trọng' x-toi/ppt/slides/slide1.xml && grep -c '&amp;lt;' x-toi/ppt/slides/slide1.xml
```

Kỳ vọng: `1` rồi `0` — nội dung `&lt;nghiêm trọng&gt;` đã được giải mã thành `<nghiêm trọng>`, không còn entity thô.

```bash
cd "<scratchpad>" && echo "== mau nen theo bang mau:" && grep -o 'srgbClr val="0D1B2A"' x-toi/ppt/slides/slide1.xml | head -1 && grep -o 'srgbClr val="F4F6F9"' x-sang/ppt/slides/slide1.xml | head -1 && echo "== nen toi KHONG xuat hien trong ban sang:" && (grep -c '0D1B2A' x-sang/ppt/slides/slide1.xml || true)</bash>
```

Kỳ vọng: thấy `0D1B2A` ở bản tối, `F4F6F9` ở bản sáng, và `0` lần `0D1B2A` trong bản sáng.

- [ ] **Step 5: Mở thử tệp để chắc PowerPoint không báo hỏng**

```bash
powershell -NoProfile -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; $z=[System.IO.Compression.ZipFile]::OpenRead('<scratchpad>/toi.pptx'); $z.Entries.Count; $z.Dispose()"
```

Kỳ vọng: một số nguyên > 15 (tệp zip đọc được, không hỏng cấu trúc).

- [ ] **Step 6: Commit**

```bash
git add public/js/giaoban/pptx.js && git commit -m "feat(giaoban): bo dung PPTX tu mo ta deck"
```

---

### Task 4: Nút xuất trên màn trình chiếu

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — thanh `#bar`, thêm hàm bảng màu và hàm xuất, gắn sự kiện trong `setupNav`

**Interfaces:**
- Consumes: `deck` (Task 2), `xuatPptx(deck, mau, tenTep, PptxGenJS)` (Task 3), thư viện (Task 1).
- Produces: không có gì cho nhiệm vụ sau.

- [ ] **Step 1: Thêm nút vào thanh điều khiển**

Trong `#bar`, chèn ngay **sau** nút `theme-btn`:

```html
      <button class="btn" id="pptx-btn" title="Xuất tệp PowerPoint" style="display:none">⬇ PPTX</button>
```

Mặc định ẩn: ngày chưa có dữ liệu báo cáo thì không có gì để xuất.

- [ ] **Step 2: Thêm hàm đọc bảng màu và hàm xuất**

Chèn ngay **trước** `function setupNav()`:

```js
  /*
   * Xuat PPTX.
   *
   * Thu vien nang khoang 1MB nen chi nap o lan bam dau tien: nguoi chi chieu ma khong xuat
   * thi khong phai tai. Lan bam sau dung lai ban da nap.
   *
   * Loi thi chi bao o nut, KHONG dung den man chieu: dang hop ma trang man vi bam nham nut
   * xuat la hong viec.
   */
  var PPTX_LIB = '/js/vendor/pptxgen.bundle.js';
  var PPTX_SRC = '/js/giaoban/pptx.js';
  var dangXuat = false;

  /** Nap mot tep script mot lan, tra Promise. */
  function napScript(src) {
    return new Promise(function (ok, hong) {
      var da = document.querySelector('script[data-nap="' + src + '"]');
      if (da) { ok(); return; }
      var el = document.createElement('script');
      el.src = src;
      el.setAttribute('data-nap', src);
      el.onload = function () { ok(); };
      el.onerror = function () { hong(new Error('Không nạp được ' + src)); };
      document.head.appendChild(el);
    });
  }

  /**
   * Bang mau cho PPTX lay tu chinh cac bien CSS dang ap, nen "theo theme dang chon" la he qua
   * tu nhien, khong phai chep lai bang mau lan thu ba.
   */
  function bangMauPptx() {
    var st = getComputedStyle(document.documentElement);
    function lay(ten, luiVe) {
      var v = (st.getPropertyValue(ten) || '').trim().replace('#', '');
      if (v.length === 3) v = v[0] + v[0] + v[1] + v[1] + v[2] + v[2];
      return /^[0-9a-fA-F]{6}$/.test(v) ? v.toUpperCase() : luiVe;
    }
    return {
      bg: lay('--bg', '0D1B2A'), strong: lay('--strong', 'FFFFFF'),
      muted: lay('--muted', '8AA4BD'), txt2: lay('--txt-2', 'DBE6F0'),
      panel2: lay('--panel-2', '14293E'), line2: lay('--line-2', '24405C'),
      teal: lay('--teal', '5DCAA5'), amber: lay('--amber', 'EF9F27'),
      red: lay('--red', 'E57373'), blue: lay('--blue', '378ADD'),
      brand: lay('--brand', '6EA8D8')
    };
  }

  function datTrangThaiNut(chu, khoa) {
    var b = document.getElementById('pptx-btn');
    if (!b) return;
    b.textContent = chu;
    b.disabled = !!khoa;
  }

  function xuatFile() {
    if (dangXuat || !deck.length) return;
    dangXuat = true;
    datTrangThaiNut('Đang xuất…', true);
    napScript(PPTX_LIB)
      .then(function () { return napScript(PPTX_SRC); })
      .then(function () {
        return window.GiaoBanPptx.xuatPptx(deck, bangMauPptx(), 'giao-ban-' + DATE + '.pptx', window.PptxGenJS);
      })
      .then(function () {
        dangXuat = false;
        datTrangThaiNut('⬇ PPTX', false);
      })
      .catch(function (e) {
        // Ghi console de con lan ra nguyen nhan; man chieu giu nguyen.
        if (window.console) console.error('Xuất PPTX lỗi:', e);
        dangXuat = false;
        datTrangThaiNut('Xuất lỗi', true);
        setTimeout(function () { datTrangThaiNut('⬇ PPTX', false); }, 3000);
      });
  }
```

- [ ] **Step 3: Gắn sự kiện và hiện nút khi có dữ liệu**

Trong `setupNav()`, ngay **trước** `napTheme();`, chèn:

```js
    document.getElementById('pptx-btn').addEventListener('click', xuatFile);
```

Trong `build()`, ngay **sau** vòng lặp dựng `deck` (sau `deck.forEach(...)`), chèn:

```js
    // Co du lieu roi moi cho xuat.
    document.getElementById('pptx-btn').style.display = '';
```

- [ ] **Step 4: Kiểm chứng trên trình duyệt**

Sinh lại trang kiểm thử và mở trong preview pane.

Lưu ý: trang kiểm thử nằm ở `storage/app/` nên đường dẫn tuyệt đối `/js/...` không trỏ đúng. Chỉ để kiểm thử, sinh bản có đường dẫn tệp cục bộ bằng cách thêm vào `harness.js` phép thay chuỗi:

```js
html = html.replace("'/js/vendor/pptxgen.bundle.js'", JSON.stringify('file:///C:/Users/tracnn/qlbv/public/js/vendor/pptxgen.bundle.js'));
html = html.replace("'/js/giaoban/pptx.js'", JSON.stringify('file:///C:/Users/tracnn/qlbv/public/js/giaoban/pptx.js'));
```

Xác nhận:

- Nút `⬇ PPTX` hiện trên thanh dưới, cạnh nút theme.
- Chạy trong console: `document.querySelector('script[data-nap]')` → `null` trước khi bấm (thư viện chưa nạp).
- Bấm nút: chữ đổi thành `Đang xuất…`, nút bị khoá.
- Sau khi xong: nút trở lại `⬇ PPTX`, và `document.querySelectorAll('script[data-nap]').length` = `2`.
- Bấm lần hai: vẫn `2` (không nạp lại thư viện).
- Console không có lỗi.
- Duyệt hết mọi slide: màn chiếu **không đổi gì** so với trước Task 4.
- Bản `no-overview`: nút vẫn hiện và xuất được (deck vẫn có slide).

Với bản không có `data.report` (không có dữ liệu báo cáo), xác nhận nút **không** hiện — kiểm bằng cách sinh một biến thể harness đặt `report: null`.

- [ ] **Step 5: Dọn và commit**

```bash
rm -f storage/app/giaoban-present-test*.html && git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): nut xuat PPTX tren man trinh chieu"
```

---

## Self-Review

Đối chiếu kế hoạch với spec:

| Yêu cầu trong spec | Nhiệm vụ |
|---|---|
| Nhúng PptxGenJS vào `public/js/vendor/`, không sửa | Task 1 |
| `moTaDeck(data)` trả mô tả thuần dữ liệu, đúng thứ tự slide | Task 2 Step 2 |
| Bộ dựng HTML đọc từ mô tả, HTML không đổi | Task 2 Step 4 + 6 |
| PPTX không đọc DOM | Task 3 Step 1 (`xuatPptx` chỉ nhận tham số) |
| Bảng màu lấy từ biến CSS, chuẩn hoá `RRGGBB` | Task 4 Step 2 (`bangMauPptx`) |
| Khổ 16:9, tiêu đề trái, ngày phải, đường kẻ | Task 3 Step 1 (`khungSlide`) |
| Tổng quan: kíp trực + bảng thật + cảnh báo + ghi chú | Task 3 Step 1 (`veTongQuan`) |
| Điều trị: bảng thật, TỔNG CỘNG đậm | Task 3 Step 1 (`veDieuTri`) |
| Khoa: bảng thật + khối chuỗi + ghi chú | Task 3 Step 1 (`veKhoa`) |
| Công suất: doughnut + bar là biểu đồ gốc | Task 3 Step 1 (`veCongSuat`) |
| Căn lề số phải / khuyết trái / tiêu đề giữa | Task 3 Step 1 (`bangTieuChi`, `veDieuTri`) |
| Slide thiếu dữ liệu bị bỏ qua | Task 2 Step 2 (`moTaDeck` trả `null`), Task 3 Step 4 (kiểm `rong.pptx` = 0 slide) |
| Nút trên `#bar`, không phím tắt | Task 4 Step 1 + 3 |
| Nạp lười ở lần bấm đầu | Task 4 Step 2 (`napScript`) + Step 4 (kiểm số thẻ script) |
| Trạng thái `Đang xuất…`, khoá nút | Task 4 Step 2 |
| Tên tệp `giao-ban-YYYY-MM-DD.pptx` theo ngày báo cáo | Task 4 Step 2 (dùng `DATE`) |
| Lỗi thì báo ở nút, màn chiếu không ảnh hưởng, tự hồi sau 3s | Task 4 Step 2 |
| Nút chỉ hiện khi có dữ liệu | Task 4 Step 1 (ẩn sẵn) + Step 3 (hiện trong `build`) |
| Kiểm chứng Node: số slide, thứ tự, bảng, dấu tiếng Việt, màu theo theme | Task 3 Step 3–5 |
| Kiểm chứng trình duyệt: HTML không đổi, nút hoạt động | Task 2 Step 6, Task 4 Step 4 |
| Không xuất PDF, không sửa tệp PHP | Không nhiệm vụ nào chạm PHP hay PDF |

Nhất quán tên gọi: mô tả deck dùng `items` / `nhan` / `giaTri` / `mau` ở Task 2 Step 2, Task 3 Step 1 và Task 3 Step 2 — cùng bộ trường. `oSoLieu(v, mau)` khai báo Task 2 Step 1, gọi ở Task 2 Step 3 và Step 4. `xuatPptx(deck, mau, tenTep, PptxGenJS)` khai báo Task 3 Step 1, gọi ở Task 3 Step 2 (Node) và Task 4 Step 2 (trình duyệt) — cùng chữ ký bốn tham số. `deck` khai báo Task 2 Step 5, đọc lại ở Task 4 Step 2.
