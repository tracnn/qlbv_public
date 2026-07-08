# Ghi chú rich text (CKEditor) cho báo cáo giao ban — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ghi chú khoa và ghi chú chung của báo cáo giao ban soạn bằng CKEditor (định dạng), lưu HTML đã sanitize, trình chiếu hiển thị đẹp, Excel strip HTML.

**Architecture:** CKEditor 4 (đã bundle offline, nạp toàn cục qua layout) trong một modal dùng chung; sanitize HTML server-side bằng HTMLPurifier khi lưu; hiển thị HTML thô an toàn ở màn nhập + trình chiếu; Excel strip_tags.

**Tech Stack:** Laravel 5.5/PHP7.4, CKEditor 4 (`public/vendor/ckeditor/ckeditor.js`), HTMLPurifier (`ezyang/htmlpurifier`, đã có), Bootstrap modal (AdminLTE), phpunit 6.

**Spec:** `docs/superpowers/specs/2026-07-08-giao-ban-ghi-chu-richtext-design.md`

**Đã xác minh:** `class_exists('HTMLPurifier')`=true; CKEditor nạp toàn cục ở `resources/views/vendor/adminlte/master.blade.php:59`; ghi chú khoa = cell `metric_code='note'` (cột `note`), ghi chú chung = `giaoban_reports.general_note`.

---

### Task 1: NoteSanitizer (TDD)

**Files:**
- Create: `app/Services/GiaoBan/NoteSanitizer.php`
- Test: `tests/Unit/GiaoBan/NoteSanitizerTest.php`

- [ ] **Step 1: Test**

`tests/Unit/GiaoBan/NoteSanitizerTest.php`:
```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\NoteSanitizer;

class NoteSanitizerTest extends TestCase
{
    /** @test */
    public function keeps_basic_formatting()
    {
        $out = NoteSanitizer::clean('<b>đậm</b> <span style="color:#ff0000">đỏ</span><ul><li>a</li></ul>');
        $this->assertContains('<b>đậm</b>', $out);
        $this->assertContains('color', $out);
        $this->assertContains('<li>a</li>', $out);
    }

    /** @test */
    public function strips_script_and_event_handlers_and_iframe()
    {
        $out = NoteSanitizer::clean('<b>ok</b><script>alert(1)</script><img src=x onerror=alert(1)><iframe src="x"></iframe>');
        $this->assertNotContains('<script', $out);
        $this->assertNotContains('onerror', $out);
        $this->assertNotContains('<iframe', $out);
        $this->assertContains('<b>ok</b>', $out);
    }

    /** @test */
    public function null_or_empty_returns_empty_string()
    {
        $this->assertSame('', NoteSanitizer::clean(null));
        $this->assertSame('', NoteSanitizer::clean('   '));
    }
}
```

- [ ] **Step 2: Run FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\NoteSanitizerTest.php`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement**

`app/Services/GiaoBan/NoteSanitizer.php`:
```php
<?php

namespace App\Services\GiaoBan;

/**
 * Làm sạch HTML ghi chú giao ban: chỉ giữ định dạng cơ bản an toàn (chống XSS).
 * Dùng HTMLPurifier (ezyang/htmlpurifier). Cache tắt để không cần thư mục ghi.
 */
class NoteSanitizer
{
    public static function clean($html)
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,ul,ol,li,span[style],div[style],h3,h4');
        $config->set('CSS.AllowedProperties', 'color,font-size,text-align,font-weight,font-style,text-decoration');
        $config->set('AutoFormat.RemoveEmpty', true);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
```

- [ ] **Step 4: Run PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\NoteSanitizerTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**
```bash
git add app/Services/GiaoBan/NoteSanitizer.php tests/Unit/GiaoBan/NoteSanitizerTest.php
git commit -m "feat(giao-ban): NoteSanitizer HTMLPurifier cho ghi chu (TDD)"
```

---

### Task 2: Controller sanitize khi lưu ghi chú

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php`

- [ ] **Step 1: Thêm use + sanitize**

Mở `app/Http/Controllers/KHTH/GiaoBanController.php`.

1a. Sau dòng `use App\Services\GiaoBan\GiaoBanReportService;` thêm:
```php
use App\Services\GiaoBan\NoteSanitizer;
```

1b. Trong method `saveCell`, tìm nhánh ghi note:
```php
        if ($request->input('metric_code') === 'note') {
            $cell->note = $request->input('note');
        } else {
```
Đổi dòng gán note thành:
```php
        if ($request->input('metric_code') === 'note') {
            $cell->note = NoteSanitizer::clean($request->input('note'));
        } else {
```

1c. Trong method `saveGeneralNote`, tìm:
```php
        $report->update(['general_note' => $request->input('general_note')]);
```
Đổi thành:
```php
        $report->update(['general_note' => NoteSanitizer::clean($request->input('general_note'))]);
```

- [ ] **Step 2: Verify + test không vỡ**

Run: `php -l app/Http/Controllers/KHTH/GiaoBanController.php`
Expected: No syntax errors.
Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS toàn bộ (29 tests).

- [ ] **Step 3: Commit**
```bash
git add app/Http/Controllers/KHTH/GiaoBanController.php
git commit -m "feat(giao-ban): sanitize ghi chu khi luu (saveCell/saveGeneralNote)"
```

---

### Task 3: Màn nhập — modal CKEditor + vùng xem ghi chú

**Files:**
- Modify: `resources/views/khth/giaoban-index.blade.php`

Thay 3 chỗ: (a) khối "Ghi chú chung" → vùng xem + nút Sửa; (b) thêm modal; (c) JS: render ghi chú dạng HTML + mở modal CKEditor + lưu; bỏ handler cũ `.dept-note change` và `#btn-save-note`.

- [ ] **Step 1: Đổi khối Ghi chú chung + thêm modal**

Tìm khối:
```blade
<div class="box box-default">
  <div class="box-header with-border"><b>Ghi chú chung</b></div>
  <div class="box-body">
    <textarea id="general_note" class="form-control" rows="3" @if(!$isAdmin) readonly @endif></textarea>
    @if($isAdmin)<button id="btn-save-note" class="btn btn-sm btn-primary" style="margin-top:5px">Lưu ghi chú</button>@endif
  </div>
</div>
```
Thay bằng:
```blade
<div class="box box-default">
  <div class="box-header with-border"><b>Ghi chú chung</b>
    @if($isAdmin) <button id="btn-edit-general" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i> Sửa</button>@endif
  </div>
  <div class="box-body"><div id="general-note-view" class="note-view"></div></div>
</div>

<div class="modal fade" id="note-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Sửa ghi chú</h4>
    </div>
    <div class="modal-body"><textarea id="note-editor" rows="8"></textarea></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
      <button type="button" id="note-save" class="btn btn-primary">Lưu</button>
    </div>
  </div></div>
</div>
```

- [ ] **Step 2: Sửa hàm `render()` — ghi chú khoa thành vùng xem + nút Sửa**

Trong `render()`, tìm đoạn tạo textarea ghi chú khoa:
```js
    var noteCell = cellOf(res, cfg.id, 'note') || {};
    html += '</div><label style="font-weight:normal">Ghi chú khoa</label>' +
      '<textarea class="form-control dept-note" rows="2" data-dept="' + cfg.id + '"' +
      (editable ? '' : ' readonly') + '>' + esc(noteCell.note || '') + '</textarea>';
    html += '</div></div>';
    $body.append(html);
```
Thay bằng (không nhúng HTML ghi chú vào chuỗi; set bằng .html() sau khi append để render định dạng):
```js
    var noteCell = cellOf(res, cfg.id, 'note') || {};
    html += '</div><div class="dept-note-block"><label style="font-weight:normal">Ghi chú khoa</label>' +
      (editable ? ' <button class="btn btn-xs btn-default btn-edit-note" data-dept="' + cfg.id + '"><i class="fa fa-pencil"></i> Sửa</button>' : '') +
      '<div class="note-view dept-note-view" data-dept="' + cfg.id + '"></div></div>';
    html += '</div></div>';
    $body.append(html);
    $body.find('.dept-note-view[data-dept="' + cfg.id + '"]').html(noteCell.note || '');
```

Đồng thời tìm trong `render()` dòng set ghi chú chung cũ:
```js
  $('#general_note').val(r.general_note || '');
```
Thay bằng:
```js
  $('#general-note-view').html(r.general_note || '');
```

- [ ] **Step 3: Sửa JS handlers — bỏ handler cũ, thêm modal/CKEditor**

Trong `$(function () { ... })`:

3a. XÓA handler ghi chú khoa cũ:
```js
  $('#report-body').on('change', '.dept-note', function () {
    saveCell($(this).data('dept'), 'note', { note: $(this).val() }, function () {});
  });
```

3b. XÓA handler lưu ghi chú chung cũ:
```js
  $('#btn-save-note').on('click', function () {
    $.post('{{ route('khth.giao-ban-save-note') }}', {
      _token: '{{ csrf_token() }}', report_id: CURRENT.report.id, general_note: $('#general_note').val()
    }).done(function () { alert('Đã lưu'); });
  });
```

3c. THÊM (cạnh các handler khác) khối modal/CKEditor:
```js
  var NOTE_TARGET = null;
  var noteEditorReady = false;
  function initNoteEditor() {
    if (window.CKEDITOR && !noteEditorReady) {
      CKEDITOR.replace('note-editor', {
        toolbar: [
          ['Bold', 'Italic', 'Underline'], ['TextColor'], ['FontSize'],
          ['NumberedList', 'BulletedList'], ['JustifyLeft', 'JustifyCenter', 'JustifyRight'],
          ['RemoveFormat']
        ],
        removePlugins: 'elementspath', resize_enabled: false, height: 200
      });
      noteEditorReady = true;
    }
  }
  function editorSet(html) {
    if (noteEditorReady && CKEDITOR.instances['note-editor']) CKEDITOR.instances['note-editor'].setData(html || '');
    else $('#note-editor').val(html || '');
  }
  function editorGet() {
    return (noteEditorReady && CKEDITOR.instances['note-editor'])
      ? CKEDITOR.instances['note-editor'].getData() : $('#note-editor').val();
  }
  function openNoteModal(target, html) {
    NOTE_TARGET = target;
    initNoteEditor();
    editorSet(html);
    $('#note-modal').modal('show');
  }

  $('#report-body').on('click', '.btn-edit-note', function () {
    var deptId = $(this).data('dept');
    var c = cellOf(CURRENT, deptId, 'note') || {};
    openNoteModal({ type: 'dept', deptId: deptId }, c.note || '');
  });
  $('#btn-edit-general').on('click', function () {
    openNoteModal({ type: 'general' }, (CURRENT && CURRENT.report ? CURRENT.report.general_note : '') || '');
  });
  $('#note-save').on('click', function () {
    if (!NOTE_TARGET || !CURRENT || !CURRENT.report) return;
    var html = editorGet();
    if (NOTE_TARGET.type === 'dept') {
      saveCell(NOTE_TARGET.deptId, 'note', { note: html }, function () { loadReport(); });
      $('#note-modal').modal('hide');
    } else {
      $.post('{{ route('khth.giao-ban-save-note') }}', {
        _token: '{{ csrf_token() }}', report_id: CURRENT.report.id, general_note: html
      }).done(function () { $('#note-modal').modal('hide'); loadReport(); })
        .fail(function () { alert('Lỗi lưu ghi chú'); });
    }
  });
```

- [ ] **Step 4: Verify compile**

Run: `php artisan view:clear` (không lỗi).
Render kiểm tra biên dịch (bootstrap + render, dừng ở menu composer là OK):
Run: `php scratchpad/render_index.php` (nếu chưa có, tạo script bootstrap render view `khth.giaoban-index` với isAdmin=true, assignedDeptIds=[] — báo `VIEW_COMPILED_OK` khi lỗi rơi vào `hasRole`/`filterMenu`).
Expected: `VIEW_COMPILED_OK` hoặc `RENDER_OK`.

- [ ] **Step 5: Commit**
```bash
git add resources/views/khth/giaoban-index.blade.php
git commit -m "feat(giao-ban): modal CKEditor + vung xem HTML cho ghi chu khoa/chung"
```

---

### Task 4: Trình chiếu — render ghi chú HTML

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php`

- [ ] **Step 1: Đổi render note sang HTML thô**

Tìm:
```js
    var noteHtml = (note && String(note).trim() !== '')
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + esc(note) + '</div></div>' : '';
```
Thay `esc(note)` bằng `note` (đã sanitize khi lưu):
```js
    var noteHtml = (note && String(note).trim() !== '')
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + note + '</div></div>' : '';
```

- [ ] **Step 2: Verify**

Run: `php artisan view:clear` (không lỗi). Xác nhận chỉ đổi 1 chỗ (các `esc()` khác giữ nguyên):
Run (PowerShell): `Select-String -Path resources/views/khth/giaoban-present.blade.php -Pattern "esc\(note\)"`
Expected: KHÔNG còn kết quả.

- [ ] **Step 3: Commit**
```bash
git add resources/views/khth/giaoban-present.blade.php
git commit -m "feat(giao-ban): trinh chieu render ghi chu HTML (da sanitize)"
```

---

### Task 5: Excel strip HTML

**Files:**
- Modify: `app/Exports/GiaoBanExport.php`

- [ ] **Step 1: Strip tags ghi chú khoa + chung**

Trong `app/Exports/GiaoBanExport.php`, tìm dòng ghi chú khoa:
```php
                $rows[] = ['* ' . $cells[$noteKey]->note];
```
Thay bằng:
```php
                $rows[] = ['* ' . trim(html_entity_decode(strip_tags((string) $cells[$noteKey]->note)))];
```
Và ghi chú chung:
```php
            $rows[] = [$this->report->general_note];
```
Thay bằng:
```php
            $rows[] = [trim(html_entity_decode(strip_tags((string) $this->report->general_note)))];
```
Lưu ý: dòng `if (trim((string) $cells[$noteKey]->note) !== '')` và `if (trim((string) $this->report->general_note) !== '')` ở trên giữ nguyên (kiểm tra rỗng trước strip vẫn đúng vì HTML rỗng như `<p></p>` hiếm; nếu muốn chặt hơn có thể strip trong điều kiện nhưng KHÔNG bắt buộc).

- [ ] **Step 2: Verify**

Run: `php -l app/Exports/GiaoBanExport.php`
Expected: No syntax errors.

- [ ] **Step 3: Commit**
```bash
git add app/Exports/GiaoBanExport.php
git commit -m "feat(giao-ban): Excel strip HTML ghi chu"
```

---

### Task 6: Kiểm thử tổng thể

- [ ] **Step 1: Full unit test**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS (29 tests: 26 cũ + 3 NoteSanitizer).

- [ ] **Step 2: Verify present render HTML (Node)**

Dùng cách đã có: seed 1 report + 1 khoa với ghi chú đã-sanitize chứa `<b>` (mô phỏng dữ liệu DB), xuất `show_payload.json`, render present blade, chạy Node test kiểm: slide khoa chứa `<b>` (không bị escape thành `&lt;b&gt;`). Nếu đã có `scratchpad/test_present.cjs` từ trước, cập nhật kỳ vọng cho note HTML; nếu không, kiểm thủ công bằng cách seed report có note `<b>Test</b> <span style="color:red">đỏ</span>` rồi mở `/khth/giao-ban/present?date=...` xác nhận chữ đậm/đỏ hiển thị.

Đối chiếu sanitize thực: chạy nhanh
```
php -r "require 'C:/Users/tracnn/qlbv/vendor/autoload.php'; require 'C:/Users/tracnn/qlbv/bootstrap/app.php'; echo App\Services\GiaoBan\NoteSanitizer::clean('<b>x</b><script>alert(1)</script>');"
```
Expected: in ra `<b>x</b>` (không có `<script>`).

- [ ] **Step 3: Cập nhật readme + commit**

Thêm đầu `readme.md`:
```markdown
# 08/07/2026 (cập nhật 5)

- Ghi chú khoa và ghi chú chung của Báo cáo giao ban chuyển sang soạn thảo rich text (CKEditor) qua popup; lưu HTML đã làm sạch (HTMLPurifier chống XSS); trình chiếu hiển thị định dạng đẹp; xuất Excel tự bỏ thẻ HTML.
```

```bash
git add readme.md
git commit -m "docs(giao-ban): readme ghi chu rich text"
```
