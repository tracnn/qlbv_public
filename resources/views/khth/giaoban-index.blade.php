@extends('adminlte::page')
@section('title', 'Báo cáo giao ban')
@section('content_header')<h1>Báo cáo giao ban <small id="report-status"></small></h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Ngày giao ban</label>
        <input type="date" id="report_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Từ thời điểm</label>
        <input type="datetime-local" id="from_time" class="form-control"></div>
      <div class="col-md-2"><label>Đến thời điểm</label>
        <input type="datetime-local" id="to_time" class="form-control"></div>
      <div class="col-md-6" style="padding-top:24px">
        <button id="btn-view" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
        <button id="btn-present" class="btn btn-info"><i class="fa fa-desktop"></i> Trình chiếu</button>
        @if($isAdmin)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-cloud-download"></i> Lấy số liệu</button>
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        @endif
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
      </div>
    </div>
  </div>
</div>

<div id="report-body"></div>

<div class="box box-default">
  <div class="box-header with-border"><b>Ghi chú chung</b></div>
  <div class="box-body">
    <textarea id="general_note" class="form-control" rows="3" @if(!$isAdmin) readonly @endif></textarea>
    @if($isAdmin)<button id="btn-save-note" class="btn btn-sm btn-primary" style="margin-top:5px">Lưu ghi chú</button>@endif
  </div>
</div>
@stop

@section('js')
<script>
var IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};
var ASSIGNED = @json($assignedDeptIds);
var CURRENT = null;

function defaultTimes() {
  var d = $('#report_date').val();
  var prev = new Date(new Date(d).getTime() - 86400000).toISOString().slice(0, 10);
  $('#from_time').val(prev + 'T07:00');
  $('#to_time').val(d + 'T07:00');
}

function fmt(dtLocal) { return dtLocal.replace('T', ' ') + ':00'; }

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
    .done(function (res) { CURRENT = res; render(res); })
    .fail(function () {
      $('#report-status').html('<span class="text-red"><i class="fa fa-exclamation-triangle"></i> Lỗi tải dữ liệu</span>');
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

function cellOf(res, deptId, code) {
  for (var i = 0; i < res.cells.length; i++) {
    var c = res.cells[i];
    if (c.dept_config_id === deptId && c.metric_code === code) return c;
  }
  return null;
}

function render(res) {
  var $body = $('#report-body').empty();
  if (!res.report) {
    $('#report-status').text('(chưa có dữ liệu — bấm Lấy số liệu)');
    return;
  }
  var r = res.report;
  $('#report-status').text(r.status === 'final' ? '(ĐÃ CHỐT)' : '(nháp, số liệu ' + r.from_time + ' → ' + r.to_time + ')');
  $('#btn-unlock').toggle(r.status === 'final');
  $('#btn-finalize').toggle(r.status !== 'final');
  $('#general_note').val(r.general_note || '');

  res.configs.forEach(function (cfg) {
    var editable = canEditDept(cfg.id);
    var warn = res.balance_warnings && res.balance_warnings[cfg.id]
      ? ' <i class="fa fa-warning text-yellow" title="Lệch cân đối: ' + res.balance_warnings[cfg.id] + '"></i>' : '';
    var html = '<div class="box box-solid"><div class="box-header with-border"><b>' +
      esc(cfg.display_name) + '</b>' + warn + '</div><div class="box-body"><div class="row">';
    cfg.metrics.forEach(function (m) {
      var c = cellOf(res, cfg.id, m.code) || {};
      var val = c.manual_value !== null && c.manual_value !== undefined ? c.manual_value : c.auto_value;
      var edited = c.manual_value !== null && c.manual_value !== undefined;
      html += '<div class="col-md-2" style="margin-bottom:8px"><label style="font-weight:normal">' + esc(m.name) + '</label>' +
        '<div class="input-group">' +
        '<input type="number" step="any" class="form-control cell-input' + (edited ? ' bg-warning' : '') + '"' +
        ' data-dept="' + cfg.id + '" data-metric="' + m.code + '"' +
        (edited ? ' title="Số HIS: ' + (c.auto_value === null ? '—' : c.auto_value) + '"' : '') +
        ' value="' + (val === null || val === undefined ? '' : Number(val)) + '"' + (editable ? '' : ' readonly') + '>' +
        (edited && editable
          ? '<span class="input-group-btn"><button class="btn btn-default btn-reset-cell" title="Trả về số tự động" data-dept="' +
            cfg.id + '" data-metric="' + m.code + '"><i class="fa fa-undo"></i></button></span>'
          : '') +
        '</div></div>';
    });
    var noteCell = cellOf(res, cfg.id, 'note') || {};
    html += '</div><label style="font-weight:normal">Ghi chú khoa</label>' +
      '<textarea class="form-control dept-note" rows="2" data-dept="' + cfg.id + '"' +
      (editable ? '' : ' readonly') + '>' + esc(noteCell.note || '') + '</textarea>';
    html += '</div></div>';
    $body.append(html);
  });
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
    $.post('{{ route('khth.giao-ban-fetch') }}', {
      _token: '{{ csrf_token() }}', date: $('#report_date').val(),
      from_time: fmt($('#from_time').val()), to_time: fmt($('#to_time').val())
    }).done(function () {
      loadReport(function () { setLoading($b, false); });
    }).fail(function (xhr) {
      setLoading($b, false);
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lấy số liệu từ HIS');
    });
  });

  $('#report-body').on('change', '.cell-input', function () {
    var $i = $(this);
    saveCell($i.data('dept'), $i.data('metric'), { manual_value: $i.val() }, loadReport);
  });
  $('#report-body').on('click', '.btn-reset-cell', function () {
    saveCell($(this).data('dept'), $(this).data('metric'), { manual_value: '' }, loadReport);
  });
  $('#report-body').on('change', '.dept-note', function () {
    saveCell($(this).data('dept'), 'note', { note: $(this).val() }, function () {});
  });

  $('#btn-save-note').on('click', function () {
    $.post('{{ route('khth.giao-ban-save-note') }}', {
      _token: '{{ csrf_token() }}', report_id: CURRENT.report.id, general_note: $('#general_note').val()
    }).done(function () { alert('Đã lưu'); });
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

  loadReport();
});
</script>
@stop
