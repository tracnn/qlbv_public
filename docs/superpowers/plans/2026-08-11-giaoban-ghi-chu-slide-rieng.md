# Ghi chú thành slide riêng, tự phân trang — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đưa ghi chú khoa và ghi chú chung ra slide riêng, tự cắt thành nhiều slide khi dài, để số liệu của khoa không bị ghi chú nuốt mất.

**Architecture:** Việc cắt trang nằm trong tầng mô tả deck (`moTaDeck`) nên bản dựng HTML và bản xuất PPTX dùng chung một chỗ cắt. Cắt theo ranh giới thẻ `<p>` với ngân sách dòng ước lượng, không đo DOM. Slide khoa sau đó chỉ còn bảng tiêu chí và khối diễn biến, và cách chia chiều cao giữa hai khối này được sửa lại để bảng không bị cắt.

**Tech Stack:** JavaScript thuần ES5, không framework, không build step.

## Global Constraints

- JavaScript viết theo phong cách ES5 đang có: `var`, `function`, không arrow function, không template literal, không `const`/`let`. Áp cho cả `public/js/giaoban/pptx.js`.
- Chú thích trong mã viết **tiếng Việt không dấu**.
- Không sửa bất kỳ tệp PHP nào: không controller, không service, không route, không API.
- `pptx.js` **không được đọc DOM**. Ràng buộc này KHÔNG áp cho `moTaDeck` trong Blade — chỗ đó chạy trong trình duyệt và được phép dùng DOM để tách đoạn, miễn là kết quả trả ra là dữ liệu thuần.
- Ngân sách cắt trang: **20 dòng**; ước lượng số dòng mỗi đoạn `max(1, ceil(số ký tự chữ / 85))`.
- Không cắt giữa một đoạn. Đoạn dài hơn cả ngân sách thì đứng riêng một trang.
- Cỡ chữ nội dung nhân `var(--z)`; thanh điều khiển `#bar` thì không.
- Ghi chú **luôn** ra slide riêng, kể cả khi ngắn.

## Cách kiểm chứng (dùng lại ở mọi nhiệm vụ)

**Trên máy chủ thật** — đây là nguồn dữ liệu bày ra lỗi, và người dùng đã đăng nhập sẵn trong khung xem:

```
http://117.4.241.247:889/khth/giao-ban/present?date=2026-08-11
```

Đặt khung xem về **1600×900** trước khi đo, vì mọi số đo nền trong spec lấy ở kích thước đó.

Máy chủ chạy mã đã triển khai, **không phải mã đang sửa**. Nên trong lúc làm, kiểm chứng bằng trang kiểm thử sinh từ tệp Blade cục bộ với **dữ liệu thật lấy từ máy chủ** (Task 1 lo phần này).

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `resources/views/khth/giaoban-present.blade.php` (sửa) | `tachDoan`, `catTrang`, mô tả slide `ghi-chu`, `veGhiChu`, CSS chia chiều cao |
| `public/js/giaoban/pptx.js` (sửa) | `chuThuan`, `veGhiChu` cho PPTX |

Không thêm tệp mới: phần cắt trang gắn chặt với tầng mô tả deck đang nằm trong Blade, tách ra sẽ phải bắc thêm cầu mà không được gì.

---

### Task 1: Trang kiểm thử dùng dữ liệu thật

Ba khoa đang hỏng có ghi chú 800–1575 ký tự với cấu trúc `<p>` thật. Dữ liệu mẫu viết tay không bày ra được lỗi này, nên lấy payload thật từ máy chủ về làm dữ liệu kiểm thử.

**Files:**
- Modify: `<scratchpad>/harness.js`

**Interfaces:**
- Produces: `storage/app/giaoban-present-test.html` dựng từ payload thật; các nhiệm vụ sau đều kiểm chứng trên tệp này.

- [ ] **Step 1: Lấy payload thật từ máy chủ**

Trong khung xem đang mở màn trình chiếu của máy chủ, chạy:

```js
fetch(location.origin + '/khth/giao-ban/show?date=2026-08-11', {credentials:'same-origin', headers:{'Accept':'application/json'}})
  .then(function(r){return r.text();})
  .then(function(t){ window.__payload = t; return {dai:t.length}; })
```

Đường dẫn `show` lấy từ biến `SHOW_URL` của trang: chạy `document.querySelector('script:not([src])').textContent.match(/SHOW_URL = (".*?")/)[1]` để đọc đúng giá trị thay vì đoán.

Lấy nội dung ra bằng `window.__payload`, ghi vào `<scratchpad>/payload-that.json`.

- [ ] **Step 2: Cho harness dùng payload thật khi có**

Trong `<scratchpad>/harness.js`, thay khối `const DATA = {...}` bằng: giữ nguyên khối dữ liệu mẫu, nhưng thêm ngay trước dòng dựng `stub`:

```js
// Uu tien payload that lay tu may chu neu co: du lieu mau viet tay khong bay ra duoc
// loi ghi chu dai (800-1600 ky tu, cau truc <p> that).
const P_THAT = path.join(__dirname, 'payload-that.json');
let DU_LIEU = DATA;
if (fs.existsSync(P_THAT) && process.argv.indexOf('mau') === -1) {
  DU_LIEU = JSON.parse(fs.readFileSync(P_THAT, 'utf8'));
  console.log('Dung payload THAT');
}
if (KHONG_REPORT) DU_LIEU.report = null;
```

và đổi dòng dựng `stub` để dùng `DU_LIEU` thay cho `DATA`:

```js
const stub = '<script>window.fetch = function () { return Promise.resolve({ json: function () {' +
  ' return Promise.resolve(' + JSON.stringify(DU_LIEU) + '); } }); };<\/script>';
```

Xoá dòng `if (KHONG_REPORT) DATA.report = null;` cũ để không đặt hai lần.

Truyền tham số `mau` khi muốn ép dùng dữ liệu mẫu viết tay.

- [ ] **Step 3: Sinh trang và xác nhận bày ra đúng lỗi**

```bash
node "<scratchpad>/harness.js"
```

Mở `file:///C:/Users/tracnn/qlbv/storage/app/giaoban-present-test.html`, đặt khung xem 1600×900, chạy:

```js
(function(){
var btns=document.getElementById('jump-list').querySelectorAll('button');
var kq=[];
for(var i=0;i<btns.length;i++){ btns[i].click();
  var s=document.querySelector('#stage .slide.active');
  var bang=s.querySelector('.bct-wrap');
  var ns=s.querySelectorAll('.note'), note=null;
  for(var k=0;k<ns.length;k++){ if(!ns[k].closest('.ds-chuoi')) note=ns[k]; }
  kq.push({ten:btns[i].textContent,
    bangCao:bang?Math.round(bang.getBoundingClientRect().height):null,
    bangBiCat:bang?Math.max(0,bang.scrollHeight-bang.clientHeight):0,
    ghiChuKyTu:note?note.querySelector('.txt').textContent.length:0,
    tran:Math.max(0,s.scrollHeight-s.clientHeight)});}
return kq;})()
```

Kỳ vọng: tái hiện đúng bảng số liệu trong spec — bốn khoa có `bangCao` từ 0 đến 84 và `bangBiCat` từ 266 đến 312. Nếu không tái hiện được thì dừng lại, payload chưa đúng.

- [ ] **Step 4: Không commit**

Trang kiểm thử và payload nằm ngoài repo (`storage/app` đã trong `.gitignore`, scratchpad không thuộc repo). Không có gì để commit ở nhiệm vụ này.

---

### Task 2: Tách đoạn và cắt trang

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — thêm hai hàm ngay trước `moTaDeck`

**Interfaces:**
- Consumes: không.
- Produces:
  - `tachDoan(html)` → mảng chuỗi HTML, mỗi phần tử là một đoạn. Chuỗi rỗng/khoảng trắng trả về `[]`.
  - `catTrang(doan)` → mảng trang; mỗi trang là mảng chuỗi HTML đoạn. Mảng rỗng trả về `[]`.
  - Task 3 gọi cả hai.

- [ ] **Step 1: Thêm hai hàm**

Chèn ngay **trước** khối chú thích của `function moTaDeck(data)`:

```js
  var NGAN_SACH_DONG = 20;   // so dong uoc luong toi da mot slide ghi chu
  var KY_TU_MOI_DONG = 85;   // do duoc 104 tren 16:9; lay 85 de con dung tren may chieu 4:3

  /**
   * Tach noi dung ghi chu thanh tung doan.
   *
   * May chu tra ve HTML tu trinh soan thao: mot chuoi the <p>, moi dong mot the. Dung DOM de
   * tach cho chac — regex tren HTML la cach hong nguoi khac.
   *
   * Ghi chu cu nhap bang van ban thuan thi khong co the khoi nao: khi do tach theo ky tu
   * xuong dong, moi dong mot doan.
   */
  function tachDoan(html) {
    var s = String(html === null || html === undefined ? '' : html);
    if (s.trim() === '') return [];

    var hop = document.createElement('div');
    hop.innerHTML = s;

    var doan = [];
    for (var i = 0; i < hop.children.length; i++) doan.push(hop.children[i].outerHTML);
    if (doan.length) return doan;

    // Khong co the khoi nao -> van ban thuan
    return hop.textContent.split('\n')
      .filter(function (d) { return d.trim() !== ''; })
      .map(function (d) { return '<p>' + esc(d) + '</p>'; });
  }

  /** So dong uoc luong cua mot doan. Doan rong van chiem mot dong. */
  function soDongCuaDoan(html) {
    var hop = document.createElement('div');
    hop.innerHTML = html;
    var dai = hop.textContent.replace(/\u00a0/g, ' ').trim().length;
    return Math.max(1, Math.ceil(dai / KY_TU_MOI_DONG));
  }

  /**
   * Cat danh sach doan thanh cac trang theo ngan sach dong.
   *
   * KHONG cat giua mot doan: mot benh nhan khong bi dut doi giua hai slide. Doan dai hon ca
   * ngan sach thi dung rieng mot trang.
   */
  function catTrang(doan) {
    if (!doan.length) return [];
    var trang = [], hienTai = [], dem = 0;
    doan.forEach(function (d) {
      var n = soDongCuaDoan(d);
      if (hienTai.length && dem + n > NGAN_SACH_DONG) {
        trang.push(hienTai); hienTai = []; dem = 0;
      }
      hienTai.push(d); dem += n;
    });
    if (hienTai.length) trang.push(hienTai);
    return trang;
  }
```

- [ ] **Step 2: Kiểm hai hàm trên trang kiểm thử**

Sinh lại trang (`node "<scratchpad>/harness.js"`), mở, rồi chạy trong console:

```js
(function(){
// lay ghi chu that cua mot khoa dang hong tu chinh DOM hien tai (truoc khi doi cach dung)
var btns=document.getElementById('jump-list').querySelectorAll('button');
for(var i=0;i<btns.length;i++) if(btns[i].textContent.indexOf('Ngoại TH-CK')>=0) btns[i].click();
var s=document.querySelector('.slide.active');
var ns=s.querySelectorAll('.note'), note=null;
for(var k=0;k<ns.length;k++){ if(!ns[k].closest('.ds-chuoi')) note=ns[k]; }
var html=note.querySelector('.txt').innerHTML;
var d=tachDoan(html), t=catTrang(d);
var tongGoc=note.querySelector('.txt').textContent.replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim().length;
var hop=document.createElement('div'); hop.innerHTML=t.map(function(p){return p.join('');}).join('');
var tongSau=hop.textContent.replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim().length;
return {soDoan:d.length, soTrang:t.length, doanMoiTrang:t.map(function(p){return p.length;}),
  tongGoc:tongGoc, tongSau:tongSau, khongMatChu:tongGoc===tongSau};})()
```

Kỳ vọng: `soDoan` khoảng 27, `soTrang` ≥ 2, và **`khongMatChu` bằng `true`** — tổng ký tự trước và sau khi cắt bằng nhau.

Kiểm thêm nhánh văn bản thuần:

```js
(function(){var d=tachDoan('dong mot\ndong hai\n\ndong ba');
return {soDoan:d.length, mau:d};})()
```

Kỳ vọng: 3 đoạn, mỗi đoạn bọc trong `<p>`.

Và nhánh rỗng:

```js
[tachDoan(''), tachDoan(null), tachDoan('   '), catTrang([])].map(function(x){return x.length;})
```

Kỳ vọng: `[0,0,0,0]`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): ham tach doan va cat trang ghi chu"
```

---

### Task 3: Slide ghi chú trong tầng mô tả deck

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — `moTaDeck`, `moTaTongQuan`, `moTaKhoa`

**Interfaces:**
- Consumes: `tachDoan(html)`, `catTrang(doan)` từ Task 2.
- Produces: loại slide mới trong mảng mô tả deck:

```js
{ loai: 'ghi-chu',
  ten: 'Khoa Ngoại TH-CK — Ghi chú (2/3)',  // nhan cho nut nhay khoa
  tieuDe: 'Khoa Ngoại TH-CK — Ghi chú',
  phuTrang: '2/3',                          // chuoi rong khi chi co mot trang
  ngay: '...',
  doan: ['<p>…</p>', '<p>…</p>'] }
```

  Task 4 (dựng HTML) và Task 5 (PPTX) đều đọc đúng cấu trúc này. Trường `ghiChu` của slide `tong-quan` và `khoa` **vẫn còn** trong mô tả nhưng không còn được dựng ra HTML — giữ lại để không phải sửa chỗ khác, và để còn biết khoa nào có ghi chú.

- [ ] **Step 1: Thêm hàm sinh các slide ghi chú**

Chèn ngay **sau** hàm `catTrang`:

```js
  /**
   * Sinh cac slide ghi chu cho mot nguon (ghi chu chung hoac ghi chu khoa).
   * Ghi chu LUON ra slide rieng, ke ca khi ngan: bo cuc moi khoa giong nhau, de doan.
   */
  function moTaGhiChu(html, tenGoc, ngay) {
    var trang = catTrang(tachDoan(html));
    if (!trang.length) return [];
    var tieuDe = tenGoc + ' — Ghi chú';
    return trang.map(function (doan, i) {
      var phuTrang = trang.length > 1 ? ((i + 1) + '/' + trang.length) : '';
      return {
        loai: 'ghi-chu',
        ten: tieuDe + (phuTrang ? ' (' + phuTrang + ')' : ''),
        tieuDe: tieuDe,
        phuTrang: phuTrang,
        ngay: ngay,
        doan: doan
      };
    });
  }
```

- [ ] **Step 2: Chèn các slide ghi chú vào đúng chỗ trong deck**

Thay thân hàm `moTaDeck`:

```js
  function moTaDeck(data) {
    var ngay = fmtDate(DATE);
    var ds = [];

    ds.push(moTaTongQuan(data, ngay));

    var dt = moTaDieuTri(data, ngay);
    if (dt) ds.push(dt);

    data.configs.forEach(function (cfg) { ds.push(moTaKhoa(data, cfg, ngay)); });

    var cs = moTaCongSuat(data, ngay);
    if (cs) ds.push(cs);

    return ds;
  }
```

bằng:

```js
  function moTaDeck(data) {
    var ngay = fmtDate(DATE);
    var ds = [];

    // Ghi chu nam ngay sau slide sinh ra no, khong don het xuong cuoi: nguoi du giao ban doc
    // so lieu cua khoa xong la doc luon ban giao cua khoa do.
    var tq = moTaTongQuan(data, ngay);
    ds.push(tq);
    ds = ds.concat(moTaGhiChu(tq.ghiChu, 'Ghi chú chung', ngay));

    var dt = moTaDieuTri(data, ngay);
    if (dt) ds.push(dt);

    data.configs.forEach(function (cfg) {
      var k = moTaKhoa(data, cfg, ngay);
      ds.push(k);
      ds = ds.concat(moTaGhiChu(k.ghiChu, k.ten, ngay));
    });

    var cs = moTaCongSuat(data, ngay);
    if (cs) ds.push(cs);

    return ds;
  }
```

Lưu ý: `moTaGhiChu` cho ghi chú chung dùng tên gốc `'Ghi chú chung'` nên tiêu đề thành `Ghi chú chung — Ghi chú`, thừa chữ. Sửa bằng cách cho `moTaGhiChu` nhận sẵn tiêu đề đầy đủ thay vì tự ghép — thay dòng

```js
    var tieuDe = tenGoc + ' — Ghi chú';
```

trong `moTaGhiChu` bằng:

```js
    // Ghi chu chung da tu no la mot ten day du; ghi chu khoa thi phai ghep them.
    var tieuDe = tenGoc === 'Ghi chú chung' ? tenGoc : (tenGoc + ' — Ghi chú');
```

- [ ] **Step 3: Kiểm thứ tự và nhãn deck**

Sinh lại trang kiểm thử, mở, rồi chạy:

```js
(function(){
var jl=[].map.call(document.querySelectorAll('#jump-list button'),function(b){return b.textContent;});
var loai=[].map.call(document.querySelectorAll('#stage .slide'),function(s){
  var t=s.querySelector('.s-title'); return t?t.textContent.trim().slice(0,40):'?';});
return {soSlide:loai.length, jump:jl, tieuDe:loai};})()
```

Kỳ vọng: mỗi khoa có ghi chú thì ngay sau slide khoa đó xuất hiện một hoặc nhiều mục `<Tên khoa> — Ghi chú`, và số mục trong `jump` bằng đúng số slide.

Ở bước này slide ghi chú **chưa dựng được** (Task 4 mới thêm `veGhiChu`), nên `veSlide` sẽ rơi vào nhánh mặc định `veCongSuat` và ném lỗi. Vì vậy Task 3 và Task 4 phải chạy liền nhau; kiểm ở Step 3 này chỉ cần đọc mảng mô tả:

```js
JSON.stringify(deck.map(function(s){return {loai:s.loai, ten:s.ten, soDoan:s.doan?s.doan.length:null};}), null, 1)
```

Kỳ vọng: đúng thứ tự Tổng quan → (ghi chú chung) → Hoạt động điều trị → khoa → ghi chú khoa → … → Công suất giường.

- [ ] **Step 4: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): ghi chu thanh slide rieng trong mo ta deck"
```

---

### Task 4: Dựng HTML slide ghi chú và sửa chia chiều cao

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php` — CSS `.ds-chuoi`, thêm `.gc-wrap`; `veSlide`, `veTongQuan`, `veKhoa`, thêm `veGhiChu`

**Interfaces:**
- Consumes: slide `ghi-chu` từ Task 3.
- Produces: không có gì cho nhiệm vụ sau.

- [ ] **Step 1: Sửa CSS chia chiều cao và thêm khung ghi chú**

Thay dòng:

```css
  .ds-chuoi { flex: 1; min-height: 0; overflow: auto; }
```

bằng:

```css
  /* Cac khoi dien bien thuc te chi 1-2 dong ("BS truc", "DD truc"). De flex:1 thi no an dung
     mot nua chieu cao va bang tieu chi bi cat, du con thua cho. Cho no cao tu nhien co tran. */
  .ds-chuoi { flex: 0 1 auto; max-height: 40%; min-height: 0; overflow: auto; }
  /* Slide ghi chu: khoi chiem het chieu cao con lai, van cuon duoc neu zoom cao lam tran. */
  .gc-wrap { flex: 1; min-height: 0; overflow: auto; margin-top: 1.4vh; }
  .gc-wrap .note { margin-top: 0; }
```

- [ ] **Step 2: Thêm `veGhiChu` và gỡ ghi chú khỏi hai slide cũ**

Thêm hàm mới ngay **sau** `veKhoa`:

```js
  /**
   * Slide ghi chu. Cac doan chen nguyen van: noi dung da qua htmlspecialchars phia may chu nen
   * an toan, va giu duoc dinh dang doan cua trinh soan thao.
   */
  function veGhiChu(s) {
    var phu = s.phuTrang ? '<span class="ov-badge nhap">' + esc(s.phuTrang) + '</span>' : '';
    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(s.tieuDe) + phu +
      '</div><div class="s-sub">Giao ban ' + esc(s.ngay) + '</div></div>' +
      '<div class="gc-wrap"><div class="note"><div class="txt txt-pre">' +
      s.doan.join('') + '</div></div></div></div>';
  }
```

Thêm nhánh vào `veSlide`:

```js
  function veSlide(s) {
    if (s.loai === 'tong-quan') return veTongQuan(s);
    if (s.loai === 'dieu-tri') return veDieuTri(s);
    if (s.loai === 'khoa') return veKhoa(s);
    if (s.loai === 'ghi-chu') return veGhiChu(s);
    return veCongSuat(s);
  }
```

Trong `veTongQuan`, xoá khối dựng ghi chú:

```js
    var noteHtml = s.ghiChu !== ''
      ? '<div class="note" style="margin-top:1.6vh"><div class="lbl">GHI CHÚ CHUNG</div><div class="txt">' +
        s.ghiChu + '</div></div>'
      : '';
```

và bỏ `noteHtml` khỏi chuỗi trả về — dòng cuối đổi từ:

```js
      thieuHtml + noteHtml + '</div>';
```

thành:

```js
      thieuHtml + '</div>';
```

Trong `veKhoa`, xoá khối:

```js
    var noteHtml = s.ghiChu !== ''
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + s.ghiChu + '</div></div>' : '';
```

và đổi dòng cuối từ:

```js
      bangHtml + chuoiHtml + noteHtml + '</div>';
```

thành:

```js
      bangHtml + chuoiHtml + '</div>';
```

- [ ] **Step 3: Kiểm chứng đầy đủ trên trang kiểm thử**

Sinh lại trang, đặt khung xem **1600×900**, chạy:

```js
(function(){
var btns=document.getElementById('jump-list').querySelectorAll('button');
var xau=[], tongGhiChu=0;
for(var i=0;i<btns.length;i++){ btns[i].click();
  var s=document.querySelector('#stage .slide.active');
  var tran=Math.max(0,s.scrollHeight-s.clientHeight);
  var bang=s.querySelector('.bct-wrap');
  var cat=bang?Math.max(0,bang.scrollHeight-bang.clientHeight):0;
  var gc=s.querySelector('.gc-wrap');
  if(gc) tongGhiChu += gc.textContent.replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim().length;
  if(tran>0||cat>0) xau.push({ten:btns[i].textContent, tranSlide:tran, bangBiCat:cat});
}
return {soSlide:btns.length, cacSlideCoVanDe:xau, tongKyTuGhiChu:tongGhiChu};})()
```

Kỳ vọng:
- `cacSlideCoVanDe` là **mảng rỗng** — không slide nào tràn, không bảng nào bị cắt.
- `tongKyTuGhiChu` bằng tổng ký tự ghi chú gốc (ghi lại con số này trước khi sửa, ở Task 1 Step 3).

Kiểm nút nhảy khoa trỏ đúng slide:

```js
(function(){
var btns=document.getElementById('jump-list').querySelectorAll('button'), sai=[];
for(var i=0;i<btns.length;i++){ btns[i].click();
  var s=document.querySelector('#stage .slide.active');
  var tieuDe=s.querySelector('.s-title').textContent.trim();
  var nhan=btns[i].textContent.replace(/\s*\(\d+\/\d+\)$/,'').trim();
  if(tieuDe.indexOf(nhan)<0 && nhan.indexOf('Tổng quan')<0) sai.push({nhan:nhan, tieuDe:tieuDe});}
return {soSai:sai.length, sai:sai};})()
```

Kỳ vọng: `soSai` bằng 0.

Kiểm bằng mắt ở cả hai theme: bấm nút theme, duyệt hết slide, ghi chú đọc rõ, bảng tiêu chí hiện đủ.

- [ ] **Step 4: Commit**

```bash
rm -f storage/app/giaoban-present-test*.html && git add resources/views/khth/giaoban-present.blade.php && git commit -m "feat(giaoban): dung slide ghi chu, bang tieu chi khong con bi cat"
```

---

### Task 5: PPTX cho slide ghi chú

**Files:**
- Modify: `public/js/giaoban/pptx.js`

**Interfaces:**
- Consumes: slide `ghi-chu` từ Task 3 (`tieuDe`, `phuTrang`, `ngay`, `doan`).
- Produces: không có gì cho nhiệm vụ sau.

- [ ] **Step 1: Thêm `chuThuan` và `veGhiChu`**

Trong `public/js/giaoban/pptx.js`, thêm ngay **sau** hàm `giaiMa`:

```js
  /**
   * Doi mot doan HTML thanh chu thuan cho hop van ban PowerPoint.
   * Ghi chu den tu trinh soan thao nen day the <p> va &nbsp; — de nguyen thi PPTX hien ca the.
   */
  function chuThuan(html) {
    return giaiMa(String(html === null || html === undefined ? '' : html)
      .replace(/<[^>]*>/g, ''))
      .replace(/&nbsp;/g, ' ')
      .replace(/\u00a0/g, ' ')
      .replace(/[ \t]+/g, ' ')
      .trim();
  }
```

Lưu ý thứ tự: lược thẻ **trước**, giải mã entity **sau**, để chuỗi như `&lt;p&gt;` do người dùng gõ tay không bị hiểu nhầm thành thẻ rồi bị xoá.

Thêm hàm dựng slide ngay **sau** `veKhoa`:

```js
  function veGhiChu(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var dong = s.doan.map(function (d) { return chuThuan(d); })
      .filter(function (d) { return d !== ''; });
    sl.addText(dong.join('\n'), { x: LE, y: Y_NOI_DUNG, w: RONG, h: H - Y_NOI_DUNG - 0.3,
      fontSize: 12, color: mau.txt2, valign: 'top', lineSpacingMultiple: 1.1 });
    return sl;
  }
```

Thêm nhánh vào `veSlide`:

```js
  function veSlide(pptx, s, mau) {
    if (s.loai === 'tong-quan') return veTongQuan(pptx, s, mau);
    if (s.loai === 'dieu-tri') return veDieuTri(pptx, s, mau);
    if (s.loai === 'khoa') return veKhoa(pptx, s, mau);
    if (s.loai === 'ghi-chu') return veGhiChu(pptx, s, mau);
    return veCongSuat(pptx, s, mau);
  }
```

`khungSlide` ghép `s.badge.chu` vào tiêu đề nếu có; slide ghi chú không có `badge` nên số trang phải tự ghép. Đổi dòng tiêu đề trong `veGhiChu` bằng cách truyền một bản sao có `badge`:

```js
  function veGhiChu(pptx, s, mau) {
    var sCopy = { tieuDe: s.tieuDe, ngay: s.ngay,
      badge: s.phuTrang ? { chu: '(' + s.phuTrang + ')' } : null };
    var sl = khungSlide(pptx, sCopy, mau);
    var dong = s.doan.map(function (d) { return chuThuan(d); })
      .filter(function (d) { return d !== ''; });
    sl.addText(dong.join('\n'), { x: LE, y: Y_NOI_DUNG, w: RONG, h: H - Y_NOI_DUNG - 0.3,
      fontSize: 12, color: mau.txt2, valign: 'top', lineSpacingMultiple: 1.1 });
    return sl;
  }
```

Dùng bản này, bỏ bản ở trên.

Cuối tệp, thêm `chuThuan` vào phần phơi ra để kiểm chứng được:

```js
  var api = { xuatPptx: xuatPptx, giaiMa: giaiMa, chuThuan: chuThuan };
```

- [ ] **Step 2: Kiểm `chuThuan` ở Node**

```bash
node -e "
var bo = require('C:/Users/tracnn/qlbv/public/js/giaoban/pptx.js');
var ca = [
  ['<p>1. L\u00ca TH\u1eca PH\u01af\u01a0NG NGOAN&nbsp; &nbsp; 37 Tu\u1ed5i</p>', '1. L\u00ca TH\u1eca PH\u01af\u01a0NG NGOAN 37 Tu\u1ed5i'],
  ['<p>&nbsp;</p>', ''],
  ['<p>a &lt;b&gt; c</p>', 'a <b> c']
];
var hong = 0;
ca.forEach(function (c) {
  var r = bo.chuThuan(c[0]);
  if (r !== c[1]) { hong++; console.log('SAI:', JSON.stringify(c[0]), '->', JSON.stringify(r), 'mong doi', JSON.stringify(c[1])); }
});
console.log(hong === 0 ? 'CHU THUAN OK' : 'CO ' + hong + ' CA SAI');
"
```

Kỳ vọng: `CHU THUAN OK`.

- [ ] **Step 3: Sinh PPTX từ deck thật và đối chiếu**

Lấy deck thật từ trang kiểm thử (sinh lại trang, mở, chạy đoạn dưới rồi ghi kết quả vào `<scratchpad>/deck-ghichu.json`):

```js
(function(){ window.GiaoBanPptx = window.GiaoBanPptx || {};
  return JSON.stringify({deck:deck, mau:{bg:'0D1B2A',strong:'FFFFFF',muted:'8AA4BD',txt2:'DBE6F0',
    panel2:'14293E',line2:'24405C',teal:'5DCAA5',amber:'EF9F27',red:'E57373',blue:'378ADD',brand:'6EA8D8'}});})()
```

Rồi:

```bash
node "<scratchpad>/lay-deck.js" "<scratchpad>/deck-ghichu.json" "<scratchpad>/ghichu.pptx"
```

Giải nén và kiểm:

```bash
powershell -NoProfile -Command "$sp='<scratchpad>'; Copy-Item \"$sp\ghichu.pptx\" \"$sp\ghichu.zip\" -Force; Remove-Item \"$sp\x-gc\" -Recurse -Force -ErrorAction SilentlyContinue; Expand-Archive \"$sp\ghichu.zip\" \"$sp\x-gc\"; 'so slide: ' + (Get-ChildItem \"$sp\x-gc\ppt\slides\" -Filter *.xml).Count; $t = Get-ChildItem \"$sp\x-gc\ppt\slides\" -Filter *.xml | ForEach-Object { Get-Content $_.FullName -Raw -Encoding UTF8 }; 'con the p: ' + ([regex]::Matches($t, '&lt;p&gt;').Count); 'con nbsp: ' + ([regex]::Matches($t, 'nbsp').Count)"
```

Kỳ vọng:
- `so slide` bằng đúng số slide trên màn chiếu (đọc từ `deck.length`).
- `con the p` bằng `0`.
- `con nbsp` bằng `0`.

- [ ] **Step 4: Commit**

```bash
node --check public/js/giaoban/pptx.js && git add public/js/giaoban/pptx.js && git commit -m "feat(giaoban): PPTX dung slide ghi chu"
```

---

### Task 6: Nghiệm thu trên máy chủ thật

Máy chủ chạy mã đã triển khai. Nhiệm vụ này **không sửa mã**, chỉ ghi lại rằng phần còn thiếu là triển khai, và đo lại chỉ số nền để so sau khi triển khai.

**Files:** không sửa tệp nào.

- [ ] **Step 1: Ghi lại chỉ số trước khi triển khai**

Trên `http://117.4.241.247:889/khth/giao-ban/present?date=2026-08-11`, khung 1600×900, chạy lại đoạn đo ở Task 1 Step 3 và lưu kết quả. Đây là bản "trước".

- [ ] **Step 2: Báo người dùng**

Nói rõ: thay đổi đã xong và đã kiểm trên trang kiểm thử dựng từ **payload thật của ngày 11/08/2026**, nhưng máy chủ `117.4.241.247:889` vẫn chạy bản cũ cho tới khi triển khai. Sau khi triển khai, chạy lại đoạn đo ở Task 1 Step 3: kỳ vọng `bangBiCat` bằng 0 ở mọi khoa và không slide nào tràn.

---

## Self-Review

Đối chiếu kế hoạch với spec:

| Yêu cầu trong spec | Nhiệm vụ |
|---|---|
| Tái hiện lỗi bằng dữ liệu thật | Task 1 |
| `tachDoan` xử lý HTML `<p>` và văn bản thuần | Task 2 Step 1 |
| `catTrang` ngân sách 20 dòng, 85 ký tự/dòng, không cắt giữa đoạn | Task 2 Step 1 |
| Đoạn dài hơn ngân sách đứng riêng một trang | Task 2 Step 1 (điều kiện `hienTai.length &&`) |
| Không mất chữ khi cắt | Task 2 Step 2 (`khongMatChu`) |
| Slide `ghi-chu` với `tieuDe`, `phuTrang`, `doan` | Task 3 Step 1 |
| Ghi chú luôn ra slide riêng, kể cả khi ngắn | Task 3 Step 1 (`moTaGhiChu` không có ngưỡng tối thiểu) |
| Ghi chú nằm ngay sau slide sinh ra nó | Task 3 Step 2 |
| Ghi chú chung theo cùng luật | Task 3 Step 2 |
| `.ds-chuoi` cao tự nhiên có trần, bảng lấy phần còn lại | Task 4 Step 1 |
| `veGhiChu` dựng HTML, cuộn được khi zoom cao | Task 4 Step 1 + 2 (`.gc-wrap`) |
| Gỡ ghi chú khỏi `veTongQuan` và `veKhoa` | Task 4 Step 2 |
| Chỉ tiêu chuỗi giữ nguyên trên slide khoa | Không nhiệm vụ nào đụng `khoiChuoi` |
| Không slide nào tràn, bảng không bị cắt | Task 4 Step 3 |
| Nút nhảy khoa trỏ đúng slide | Task 4 Step 3 (kiểm `soSai`) |
| `chuThuan` lược thẻ và giải mã `&nbsp;` | Task 5 Step 1 + 2 |
| PPTX có slide ghi chú | Task 5 Step 1 + 3 |
| Nghiệm thu trên máy chủ thật | Task 6 |

Nhất quán tên gọi: `tachDoan(html)` và `catTrang(doan)` khai báo Task 2 Step 1, gọi ở Task 2 Step 2 và Task 3 Step 1. `moTaGhiChu(html, tenGoc, ngay)` khai báo Task 3 Step 1, gọi ở Task 3 Step 2. Trường `doan` / `phuTrang` / `tieuDe` dùng thống nhất ở Task 3, Task 4 Step 2, Task 5 Step 1. `chuThuan(html)` khai báo Task 5 Step 1, gọi trong `veGhiChu` cùng bước và kiểm ở Step 2. `veGhiChu` có hai bản trong Task 5 Step 1 — bản thứ hai là bản dùng, đã nói rõ trong bước.
