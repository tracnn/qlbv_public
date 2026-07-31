@extends('adminlte::page')
@section('title', 'Báo cáo giao ban')
@section('content_header')<h1>Báo cáo giao ban <small id="report-status"></small></h1>@stop

@section('css')
<style>
  .cell-input.mb-thieu, .cell-text.mb-thieu { border-color: #dd4b39; background: #fff5f4; }
  .cell-input.mb-ke-thua { color: #999; font-style: italic; background: #fafafa; }
  .cell-text { font-family: inherit; }
</style>
@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Ngày giao ban</label>
        <input type="date" id="report_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
      {{-- Ai bam duoc "Lay so lieu" thi thay va sua duoc khung gio nay (xem
           GiaoBanPermission::canFetchData) — khong con rieng cho admin. Rui ro de nham khung
           da luu duoc xu ly o JS: loadReport() dien san gia tri report.from_time/to_time khi
           bao cao da ton tai, thay vi luon reset ve mac dinh (xem dienKhungGioDaLuu()).
           Luu y: $canFetch o day chi quyet dinh CO MAT trong DOM hay khong (quyen co so, tinh
           mot lan luc render trang). Hien hay an theo tung ngay dang xem la viec cua JS
           capNhatNutLaySoLieu(), tinh lai moi lan doi ngay ma khong tai lai trang. --}}
      @if($canFetch)
      <div class="col-md-2" id="o-tu-thoi-diem"><label>Từ thời điểm</label>
        <input type="datetime-local" id="from_time" class="form-control"></div>
      <div class="col-md-2" id="o-den-thoi-diem"><label>Đến thời điểm</label>
        <input type="datetime-local" id="to_time" class="form-control"></div>
      @endif
      {{-- Do rong bam theo canFetch: hai o gio chiem cho khi nguoi dung bam duoc "Lay so lieu",
           giu tong du 12 theo luoi Bootstrap. --}}
      <div id="cot-nut" class="col-md-{{ $canFetch ? 6 : 10 }}" style="padding-top:24px">
        <button id="btn-view" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
        {{-- Trình chiếu và Xuất Excel đều là số liệu toàn viện -> chỉ admin. Để ngoài thì
             người khoa vẫn thấy nút rồi bấm vào ăn 403. --}}
        @if($isAdmin)
        <button id="btn-present" class="btn btn-info"><i class="fa fa-desktop"></i> Trình chiếu</button>
        @endif
        {{-- Lấy số liệu tách riêng: người được gán khoa cũng bấm được, xem
             GiaoBanPermission::canFetchData. Day cung chi la quyen co so (co mat trong DOM);
             hien/an nut theo ngay dang xem la viec cua JS capNhatNutLaySoLieu(). --}}
        @if($canFetch)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-cloud-download"></i> Lấy số liệu</button>
        @endif
        @if($isAdmin)
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
        @endif
      </div>
      <div class="col-md-12" style="padding-top:12px">
        <small id="da-lay-luc" class="text-muted"></small>
      </div>
      <div class="col-md-12" style="padding-top:12px">
        <small id="che-do" class="text-muted"></small>
      </div>
    </div>
  </div>
</div>

<div id="report-body"></div>

<div class="box box-info">
  <div class="box-header with-border"><b>Kíp trực lãnh đạo</b>
    <button id="btn-copy-duty" class="btn btn-xs btn-default"><i class="fa fa-copy"></i> Sao chép kíp ngày trước</button>
  </div>
  <div class="box-body"><div id="duty-body"></div></div>
</div>

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
@stop

@section('js')
<script>
var IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};
var CAN_FETCH = {{ $canFetch ? 'true' : 'false' }};
var ASSIGNED = @json($assignedDeptIds);
var CURRENT = null;

function defaultTimes() {
  // Nguoi khong bam duoc "Lay so lieu" (CAN_FETCH false) khong co #from_time/#to_time trong
  // DOM, nhung ham nay van duoc goi cho ho moi lan doi ngay (xem $('#report_date').on('change'))
  // -> giu guard de khoi phai suy nghi lai moi lan doc, du .val() tren tap rong cua jQuery vo hai.
  if (!$('#from_time').length) return;
  var d = $('#report_date').val();
  var prev = new Date(new Date(d).getTime() - 86400000).toISOString().slice(0, 10);
  $('#from_time').val(prev + 'T07:00');
  $('#to_time').val(d + 'T07:00');
}

function fmt(dtLocal) { return dtLocal.replace('T', ' ') + ':00'; }

/** Chieu nguoc lai fmt(): 'Y-m-d H:i:s' server tra ve -> 'YYYY-MM-DDTHH:MM' cho input datetime-local. */
function toDtLocal(ymdHis) {
  return String(ymdHis || '').slice(0, 16).replace(' ', 'T');
}

/** '2026-07-31 07:12:00' -> '07:12 ngày 31/07/2026'. Chuoi rong hay sai dinh dang -> rong. */
function gioNgay(ymdHis) {
  var s = String(ymdHis || '');
  if (s.length < 16) return '';
  return s.slice(11, 16) + ' ngày ' + s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4);
}

/**
 * Dien san khung gio DA LUU cua bao cao vao hai o, thay vi de defaultTimes() luon thang o
 * mac dinh (07:00 hom truoc -> 07:00 hom nay) de nguyen. Day la phan xu ly rui ro "de nham
 * khung gio" sau khi Y 1 chot lai cho nguoi khoa tu do sua nhu admin — xem muc Rui ro Y 1 trong
 * docs/superpowers/specs/2026-07-31-giaoban-4-dieu-chinh-design.md.
 *
 * Chi goi trong luong tai du lieu (loadReport), khong goi o cho nao khac: goi nham luc nguoi
 * dung dang go do se cuop mat gia tri ho vua sua.
 */
function dienKhungGioDaLuu(res) {
  if (!$('#from_time').length) return; // nguoi khong duoc fetch khong co hai o nay
  if (!res.report) return; // ngay chua co bao cao -> giu nguyen khung mac dinh cua defaultTimes()
  if (res.report.from_time) $('#from_time').val(toDtLocal(res.report.from_time));
  if (res.report.to_time) $('#to_time').val(toDtLocal(res.report.to_time));
}

/**
 * Hien/an nut "Lay so lieu" cung hai o khung gio, va dong "da lay luc nao".
 *
 * Khong quyet dinh duoc o Blade: $canFetch render MOT LAN luc mo trang, con dieu kien nay doi
 * theo NGAY dang xem ma nguoi dung chuyen ngay bang JS, khong tai lai trang. Blade chi quyet
 * dinh co dua cac phan tu nay vao DOM hay khong (quyen co so); day quyet dinh hien hay an.
 *
 * Chan that nam o GiaoBanController::fetchData(); day chi la trai nghiem.
 */
function capNhatNutLaySoLieu(res) {
  var duocLay = !!res.can_fetch;

  // Nguoi khong co quyen co so khong co cac phan tu nay trong DOM; .toggle() tren tap rong
  // cua jQuery vo hai nen khong can guard rieng.
  $('#btn-fetch').toggle(duocLay);
  $('#o-tu-thoi-diem, #o-den-thoi-diem').toggle(duocLay);
  // Hai o gio an di thi cot nut phai gian ra, neu khong luoi Bootstrap de lai mot khoang trong.
  $('#cot-nut').toggleClass('col-md-6', duocLay).toggleClass('col-md-10', !duocLay);

  var luc = gioNgay(res.report && res.report.data_fetched_at);
  if (!luc) { $('#da-lay-luc').empty(); return; }
  // Hien cho CA admin: "lay luc nao" tu no huu ich, khong phai chi de giai thich viec bi chan.
  $('#da-lay-luc').html('<i class="fa fa-clock-o"></i> Số liệu đã lấy lúc ' + esc(luc) +
    (res.is_admin ? '' : ' — cần lấy lại thì liên hệ phòng KHTH.'));
}

function canEditDept(deptId) {
  if (CURRENT && CURRENT.report && CURRENT.report.status === 'final') return false;
  return IS_ADMIN || ASSIGNED.indexOf(deptId) !== -1;
}

/** Bật/tắt trạng thái loading cho 1 nút (disable + spinner, khôi phục khi xong). */
function setLoading($btn, loading, loadingText) {
  if (loading) {
    if ($btn.data('orig-html') === undefined) $btn.data('orig-html', $btn.html());
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + (loadingText || 'Đang xử lý...'));
  } else {
    $btn.prop('disabled', false).html($btn.data('orig-html'));
    $btn.removeData('orig-html');
  }
}

function loadReport(onDone) {
  $('#report-status').html('<i class="fa fa-spinner fa-spin"></i> đang tải...');
  $('#report-body').css('opacity', 0.5);
  $.get('{{ route('khth.giao-ban-show') }}', { date: $('#report_date').val() })
    .done(function (res) {
      CURRENT = res;
      // Phai sau khi co res: defaultTimes() da chay truoc do (luc doi ngay), neu dien khung
      // luu san o day thi moi la gia tri sau cung con lai tren man, khong bi mac dinh de len.
      dienKhungGioDaLuu(res);
      capNhatNutLaySoLieu(res);
      renderCheDo(res);
      render(res);
    })
    .fail(function () {
      $('#report-status').html('<span class="text-red"><i class="fa fa-exclamation-triangle"></i> Lỗi tải dữ liệu</span>');
      // Doi ngay ma request hong thi khong duoc de dong "Che do" cua lan tai truoc dung
      // nguyen tren man — voi cong cu chan doan thi hien SAI te hon khong hien.
      $('#che-do').empty();
      // Goi voi doi tuong rong thay vi chi xoa #da-lay-luc: res.can_fetch undefined -> an nut
      // va hai o khung gio cua ngay cu di, res.report undefined -> gioNgay() cung tra rong nen
      // dong "da lay luc" duoc don theo. capNhatNutLaySoLieu() la noi DUY NHAT quyet dinh hien/an.
      capNhatNutLaySoLieu({});
    })
    .always(function () {
      $('#report-body').css('opacity', 1);
      if (typeof onDone === 'function') onDone();
    });
}

function esc(s) {
  return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

/**
 * Dong trang thai quyen.
 *
 * Cong cu chan doan cho phan anh "da phan quyen khoa nhung van thay tat ca khoa": nhin mot cai
 * la biet tai khoan dang o che do nao, khong phai hoi KHTH. Tra loi luon cau hoi thuong gap
 * "sao toi khong thay khoa X".
 *
 * Doc tu chinh JSON ma man hinh dang ve, nen no phan anh dung cai server thuc su tra ve.
 */
function renderCheDo(res) {
  var $o = $('#che-do');
  // is_admin falsy (false/undefined/null deu roi vao nhanh Khoa): fail-safe co y — sai kieu du
  // lieu hay thieu truong thi CHAN bot quyen chu khong lo them, an toan hon la mac dinh admin.
  if (res.is_admin) {
    $o.html('<i class="fa fa-shield"></i> Chế độ: <b>Quản trị</b> — xem toàn viện');
    return;
  }
  // assigned_dept_ids la nguon DOC LAP voi configs (tra thang tu giaoban_user_departments,
  // khong di qua visibleDeptConfigIds) -> so sanh hai con so nay moi la tin hieu that. Truoc day
  // so ten rut tu chinh configs voi do dai cua no, nen luon khop, cong cu chan doan khong bao gio
  // kich hoat duoc. Lech nhau la dau hieu khoa bi tat is_active hoac loc sai — xem Buoc 1 trong
  // docs/giaoban-chan-doan-phan-quyen-khoa.md.
  var soPhanCong = (res.assigned_dept_ids || []).length;
  var ten = (res.configs || []).map(function (c) { return esc(c.display_name); });
  $o.html('<i class="fa fa-user"></i> Chế độ: <b>Khoa</b> — phân công ' + soPhanCong + ' khoa, đang hiện ' +
    ten.length + (ten.length ? ': ' + ten.join(', ') : ''));
}

/**
 * Giai ma nguoc gia tri chi tieu kieu chuoi truoc khi do vao textarea.
 * Server luu bang NoteSanitizer::cleanPlain (htmlspecialchars) de gia tri khong bao gio
 * chay duoc du hien thi kieu gi. Khong giai ma o day thi nguoi dung thay "Hb &lt; 8"
 * roi luu tiep se thanh "&amp;lt;" — hong dan qua tung lan sua.
 */
function giaiMaHtml(s) {
  if (s === null || s === undefined || s === '') return '';
  var d = document.createElement('textarea');
  d.innerHTML = String(s);
  return d.value;
}

function cellOf(res, deptId, code) {
  for (var i = 0; i < res.cells.length; i++) {
    var c = res.cells[i];
    if (c.dept_config_id === deptId && c.metric_code === code) return c;
  }
  return null;
}

function render(res) {
  var $body = $('#report-body').empty();

  // Kiem TRUOC nhanh !res.report: hai trang thai nay deu ra man trong, nhung ly do khac han nhau.
  // Khong tach thi nguoi dung khong biet minh dang gap cai nao.
  if (res.no_assignment) {
    $('#report-status').text('');
    $body.html('<div class="callout callout-warning">' +
      '<h4><i class="fa fa-user-times"></i> Bạn chưa được phân công khoa nào</h4>' +
      '<p>Màn hình giao ban chỉ hiển thị các khoa bạn được phân công nhập số liệu. ' +
      'Liên hệ phòng KHTH để được gán khoa.</p></div>');
    return;
  }
  if (!res.report) {
    // Nguoi khong duoc lay so lieu thi dung chi ho bam mot nut khong ton tai voi ho.
    if (CAN_FETCH) {
      $('#report-status').text('(chưa có dữ liệu — bấm Lấy số liệu)');
    } else {
      $('#report-status').text('');
      $body.html('<div class="callout callout-info">' +
        '<h4><i class="fa fa-clock-o"></i> Chưa có số liệu cho ngày này</h4>' +
        '<p>Phòng KHTH chưa lấy số liệu từ HIS cho ngày giao ban đã chọn. ' +
        'Bấm <b>Làm mới</b> để kiểm tra lại, hoặc chọn ngày khác.</p></div>');
    }
    return;
  }
  var r = res.report;
  $('#report-status').text(r.status === 'final' ? '(ĐÃ CHỐT)' : '(nháp, số liệu ' + r.from_time + ' → ' + r.to_time + ')');
  $('#btn-unlock').toggle(r.status === 'final');
  $('#btn-finalize').toggle(r.status !== 'final');
  $('#btn-edit-general').toggle(r.status !== 'final');
  $('#general-note-view').html(r.general_note || '');
  renderDuties(res);

  res.configs.forEach(function (cfg) {
    var editable = canEditDept(cfg.id);
    var warn = res.balance_warnings && res.balance_warnings[cfg.id]
      ? ' <i class="fa fa-warning text-yellow" title="Lệch cân đối: ' + res.balance_warnings[cfg.id] + '"></i>' : '';
    var html = '<div class="box box-solid"><div class="box-header with-border"><b>' +
      esc(cfg.display_name) + '</b>' + warn + '</div><div class="box-body"><div class="row">';
    // Chi tieu chuoi gom xuong duoi luoi o so, khong de xen giua lam vo luoi.
    var htmlChuoi = '';
    cfg.metrics.forEach(function (m) {
      var c = cellOf(res, cfg.id, m.code) || {};
      var val = c.manual_value !== null && c.manual_value !== undefined ? c.manual_value : c.auto_value;
      var edited = c.manual_value !== null && c.manual_value !== undefined;
      // Chi tieu tu dong khong bao gio doc khai bao input: quy uoc nay khong duoc
      // MetricValidator ep buoc voi type != manual, nen chan thang o day.
      var laNhapTay = m.type === 'manual';
      var inp = laNhapTay ? (m.input || {}) : {};
      var keThua = !!c.carried_over;

      // step theo kieu gia tri; decimal(12,2) o DB nen toi da 2 chu so le
      var step = inp.value_type === 'decimal' || inp.value_type === 'percent' ? '0.01' : '1';
      // tinh min/max hieu luc MOT LAN roi moi xuat ra 1 thuoc tinh duy nhat
      // (neu noi chuoi rieng "min=..."/"max=..." roi lai noi them max="100" cho percent
      // se ra 2 thuoc tinh max trung nhau tren cung 1 the html, trinh duyet chi lay cai dau)
      var minHL = inp.min !== undefined && inp.min !== null ? inp.min : null;
      var maxHL = inp.max !== undefined && inp.max !== null ? inp.max : null;
      if (inp.value_type === 'percent') {
        maxHL = maxHL !== null ? Math.min(maxHL, 100) : 100;
        minHL = minHL !== null ? Math.max(minHL, 0) : 0;
      }
      var rangBuoc = ' step="' + (laNhapTay ? step : 'any') + '"';
      if (minHL !== null) rangBuoc += ' min="' + minHL + '"';
      if (maxHL !== null) rangBuoc += ' max="' + maxHL + '"';

      var trong = val === null || val === undefined || val === '';
      var thieuBatBuoc = laNhapTay && inp.required && (trong || keThua);

      var tip = keThua ? 'Kế thừa từ phiên trước, chưa xác nhận'
                       : (edited ? 'Số HIS: ' + (c.auto_value === null ? '—' : c.auto_value)
                       : (inp.hint || ''));

      var nhan = esc(m.name) + (inp.required ? ' <span class="text-red">*</span>' : '') +
                 (inp.hint ? ' <i class="fa fa-question-circle text-muted" title="' + esc(inp.hint) + '"></i>' : '');

      // Chi tieu nhap tay kieu chuoi: textarea chiem ca hang, gia tri nam o cot `note`.
      // Khong co nut hoan tac, khong to vang — nhung thu do chi co nghia voi so.
      if (laNhapTay && inp.value_type === 'text') {
        var thieuChuoi = inp.required && !(c.note || '').length;
        htmlChuoi += '<div class="col-md-12" style="margin-bottom:8px">' +
          '<label style="font-weight:normal">' + nhan + '</label>' +
          '<textarea rows="4" class="form-control cell-text' + (thieuChuoi ? ' mb-thieu' : '') + '"' +
          ' data-dept="' + cfg.id + '" data-metric="' + esc(m.code) + '"' +
          (inp.hint ? ' placeholder="' + esc(inp.hint) + '"' : '') +
          (editable ? '' : ' readonly') + '>' +
          // Gia tri luu da qua htmlspecialchars (NoteSanitizer::cleanPlain) nen phai giai ma
          // truoc khi do vao textarea, neu khong se ma hoa kep dan qua tung lan sua.
          esc(giaiMaHtml(c.note)) +
          '</textarea></div>';
        return;
      }

      html += '<div class="col-md-2" style="margin-bottom:8px"><label style="font-weight:normal">' + nhan + '</label>' +
        '<div class="input-group">' +
        '<input type="number"' + rangBuoc + ' class="form-control cell-input' +
          (edited ? ' bg-warning' : '') + (thieuBatBuoc ? ' mb-thieu' : '') + (keThua ? ' mb-ke-thua' : '') + '"' +
        ' data-dept="' + cfg.id + '" data-metric="' + esc(m.code) + '"' +
        (tip ? ' title="' + esc(tip) + '"' : '') +
        ' value="' + (trong ? '' : Number(val)) + '"' + (editable ? '' : ' readonly') + '>' +
        (inp.unit ? '<span class="input-group-addon">' + esc(inp.unit) + '</span>' : '') +
        (edited && editable
          ? '<span class="input-group-btn"><button class="btn btn-default btn-reset-cell" title="Trả về số tự động" data-dept="' +
            cfg.id + '" data-metric="' + esc(m.code) + '"><i class="fa fa-undo"></i></button></span>'
          : '') +
        '</div></div>';
    });
    var noteCell = cellOf(res, cfg.id, 'note') || {};
    // Dong luoi o so, roi mo mot hang rieng cho cac o chuoi.
    if (htmlChuoi) html += '</div><div class="row">' + htmlChuoi;
    html += '</div><div class="dept-note-block"><label style="font-weight:normal">Ghi chú khoa</label>' +
      (editable ? ' <button class="btn btn-xs btn-default btn-edit-note" data-dept="' + cfg.id + '"><i class="fa fa-pencil"></i> Sửa</button>' : '') +
      '<div class="note-view dept-note-view" data-dept="' + cfg.id + '"></div></div>';
    html += '</div></div>';
    $body.append(html);
    $body.find('.dept-note-view[data-dept="' + cfg.id + '"]').html(noteCell.note || '');
  });
}

function renderDuties(res) {
  var canEdit = res.can_edit_duty && !(res.report && res.report.status === 'final');
  var byPos = {};
  (res.duties || []).forEach(function (d) {
    if (!byPos[d.position_id]) byPos[d.position_id] = [];
    byPos[d.position_id].push(d);
  });
  var $b = $('#duty-body').empty();
  if (!res.duty_positions || !res.duty_positions.length) {
    $b.html('<i class="text-muted">Chưa cấu hình chức danh trực (Cấu hình giao ban).</i>');
    return;
  }
  var html = '<table class="table table-bordered"><thead><tr><th style="width:220px">Chức danh</th><th>Người trực</th></tr></thead><tbody>';
  res.duty_positions.forEach(function (p) {
    var people = byPos[p.id] || [];
    var cell = '';
    people.forEach(function (d) {
      cell += '<div class="duty-chip" style="display:inline-flex;align-items:center;gap:4px;background:#f4f4f4;border:1px solid #ddd;border-radius:4px;padding:2px 6px;margin:2px">' +
        '<b>' + esc(d.person_name || '') + '</b>' +
        '<input type="text" class="duty-chip-phone" data-duty="' + d.id + '" value="' + esc(d.phone || '') + '" placeholder="SĐT" style="width:110px;border:none;background:transparent"' + (canEdit ? '' : ' readonly') + '>' +
        (canEdit ? '<a href="#" class="duty-remove" data-duty="' + d.id + '" title="Xóa" style="color:#c00;font-weight:bold">&times;</a>' : '') +
        '</div>';
    });
    if (canEdit) {
      cell += '<div style="margin-top:4px"><input type="text" class="form-control input-sm duty-add" data-pos="' + p.id + '" placeholder="+ thêm người (gõ tên/mã NV)..." style="max-width:340px;display:inline-block"></div>';
    } else if (!people.length) {
      cell = '<i class="text-muted">—</i>';
    }
    html += '<tr data-pos="' + p.id + '"><td>' + esc(p.name) + '</td><td>' + cell + '</td></tr>';
  });
  html += '</tbody></table><div id="duty-results" class="list-group" style="max-height:220px;overflow:auto;display:none;margin-top:4px"></div>';
  $b.html(html);
}

function saveCell(deptId, metric, payload, done) {
  $.post('{{ route('khth.giao-ban-save-cell') }}', $.extend({
    _token: '{{ csrf_token() }}', report_id: CURRENT.report.id,
    dept_config_id: deptId, metric_code: metric
  }, payload)).done(done).fail(function (xhr) {
    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu dữ liệu');
    loadReport();
  });
}

$(function () {
  defaultTimes();
  $('#report_date').on('change', function () { defaultTimes(); loadReport(); });
  $('#btn-view').on('click', function () {
    var $b = $(this);
    setLoading($b, true, 'Đang tải...');
    loadReport(function () { setLoading($b, false); });
  });

  $('#btn-fetch').on('click', function () {
    var $b = $(this);
    setLoading($b, true, 'Đang lấy số liệu...');
    // #btn-fetch va #from_time/#to_time gio cung chiu chung dieu kien CAN_FETCH (xem
    // giaoban-index.blade.php) nen luon co mat cung nhau — khong can guard .length nua.
    var data = {
      _token: '{{ csrf_token() }}', date: $('#report_date').val(),
      from_time: fmt($('#from_time').val()), to_time: fmt($('#to_time').val())
    };
    $.post('{{ route('khth.giao-ban-fetch') }}', data).done(function () {
      loadReport(function () { setLoading($b, false); });
    }).fail(function (xhr) {
      setLoading($b, false);
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lấy số liệu từ HIS');
      // Dong bo lai nut/hai o khung gio: bi chan 403 (nguoi khac vua lay xong) nghia la trang
      // thai tren man da cu, phai tai lai de an nut di theo dung can_fetch moi.
      loadReport();
    });
  });

  $('#report-body').on('change', '.cell-input', function () {
    var $i = $(this);
    saveCell($i.data('dept'), $i.data('metric'), { manual_value: $i.val() }, loadReport);
  });
  $('#report-body').on('click', '.btn-reset-cell', function () {
    saveCell($(this).data('dept'), $(this).data('metric'), { manual_value: '' }, loadReport);
  });
  // Chi tieu kieu chuoi: gia tri di theo tham so `note`, khong phai `manual_value`.
  // Khong goi loadReport() sau khi luu — ve lai ca man se cuop con tro dang go cua nguoi dung.
  $('#report-body').on('change', '.cell-text', function () {
    var $t = $(this);
    saveCell($t.data('dept'), $t.data('metric'), { note: $t.val() }, function () {
      $t.removeClass('mb-thieu');
    });
  });
  $('#btn-finalize').on('click', function () {
    if (!confirm('Chốt báo cáo? Sau khi chốt sẽ không sửa được.')) return;
    $.post('{{ route('khth.giao-ban-finalize') }}', { _token: '{{ csrf_token() }}', report_id: CURRENT.report.id }).done(loadReport);
  });
  $('#btn-unlock').on('click', function () {
    $.post('{{ route('khth.giao-ban-unlock') }}', { _token: '{{ csrf_token() }}', report_id: CURRENT.report.id }).done(loadReport);
  });
  $('#btn-export').on('click', function () {
    window.location = '{{ route('khth.giao-ban-export') }}?date=' + $('#report_date').val();
  });

  $('#btn-present').on('click', function () {
    window.open('{{ route('khth.giao-ban-present') }}?date=' + encodeURIComponent($('#report_date').val()), '_blank', 'noopener');
  });

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

  var dutyTimer = null, dutyActivePos = null;
  $('#duty-body').on('input', '.duty-add', function () {
    var $i = $(this); dutyActivePos = $i.data('pos');
    var q = $i.val();
    clearTimeout(dutyTimer);
    var $res = $('#duty-results');
    if (q.length < 2) { $res.hide(); return; }
    dutyTimer = setTimeout(function () {
      $.get('{{ route('khth.giao-ban-search-employees') }}', { q: q }, function (rows) {
        $res.empty();
        (rows || []).forEach(function (u) {
          $res.append('<a href="#" class="emp-pick" data-id="' + u.id + '" data-name="' + esc(u.name || '') + '" data-phone="' + esc(u.phone || '') +
            '" style="display:block;padding:6px 10px;border-bottom:1px solid #eee">' + esc(u.name || '') +
            (u.loginname ? ' <small class="text-muted">[' + esc(u.loginname) + ']</small>' : '') +
            (u.title ? ' <small class="text-muted">(' + esc(u.title) + ')</small>' : '') +
            (u.phone ? ' <small>' + esc(u.phone) + '</small>' : '') + '</a>');
        });
        $res.show();
      });
    }, 300);
  });
  $('#duty-body').on('mousedown', '.emp-pick', function (e) {
    e.preventDefault();
    var $pick = $(this);
    $('#duty-results').hide();
    $('#duty-body tr[data-pos="' + dutyActivePos + '"] .duty-add').val('');
    $.post('{{ route('khth.giao-ban-add-duty') }}', {
      _token: '{{ csrf_token() }}', date: $('#report_date').val(), position_id: dutyActivePos,
      employee_id: $pick.data('id'), person_name: $pick.data('name'), phone: $pick.data('phone')
    }).done(loadReport).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi thêm người trực');
    });
  });
  $('#duty-body').on('click', '.duty-remove', function (e) {
    e.preventDefault();
    $.post('{{ route('khth.giao-ban-remove-duty') }}', { _token: '{{ csrf_token() }}', duty_id: $(this).data('duty') })
      .done(loadReport).fail(function () { alert('Lỗi xóa người trực'); });
  });
  $('#duty-body').on('blur', '.duty-chip-phone', function () {
    $.post('{{ route('khth.giao-ban-update-duty-phone') }}', { _token: '{{ csrf_token() }}', duty_id: $(this).data('duty'), phone: $(this).val() });
  });
  $(document).on('click', function (e) { if (!$(e.target).closest('#duty-body, #duty-results').length) $('#duty-results').hide(); });
  $('#btn-copy-duty').on('click', function () {
    $.post('{{ route('khth.giao-ban-copy-duties') }}', { _token: '{{ csrf_token() }}', date: $('#report_date').val() })
      .done(loadReport)
      .fail(function (xhr) { alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi sao chép'); });
  });

  loadReport();
});
</script>
@stop
