<!DOCTYPE HTML>
<html lang="vi">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Hướng dẫn - Trả KQ</title>
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/bootstrap/dist/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/Ionicons/css/ionicons.min.css') }}">
  <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/customize.css') }}">
  <link rel="stylesheet" href="//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
  table td, table th {
    white-space: normal !important;
  }
</style>
</head>

<body>
  <div class="center-block loading_center" id="loading_center" style="display: none; padding: 10px;"></div>
  @include('patient.partials.header')
  <div class="container">
    <div class="panel panel-primary">
      <div class="panel-heading">
        Xem thông tin khám chữa bệnh
        <button class="btn btn-xs btn-default" type="button" data-toggle="collapse" data-target="#searchFormCollapse" aria-expanded="{{ isset($treatment) && $treatment ? 'false' : 'true' }}" aria-controls="searchFormCollapse">
          <i class="fa fa-chevron-down"></i>
        </button>
      </div>
      <div id="searchFormCollapse" class="panel-collapse collapse {{ (!isset($treatment) || !$treatment) ? 'in' : '' }}">
        <div class="panel-body">
          <form id="searchForm" method="GET" action="">
            <div class="form-group row">
              <label class="col-sm-3 col-form-label">Mã điều trị</label>
              <div class="col-sm-9">
                <input class="form-control" type="tel" id="treatment_code" name="treatment_code" placeholder="Mã điều trị" 
                maxlength="12" value="{{ $treatment_code }}">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-3 col-form-label">Số điện thoại</label>
              <div class="col-sm-9">
                <input class="form-control" type="tel" id="phone" name="phone" placeholder="Số điện thoại" maxlength="11" 
                value="{{ $phone }}">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12">
                <button class="btn btn-info float-right">
                  <i class="fa fa-search"></i> Xem kết quả
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="container">
    @include('patient.partials.patient_barcode')
  </div>
  <div class="container">
    <ul class="nav nav-tabs">
      <li class="active"><a data-toggle="tab" href="#tthc"><span><strong>Thông tin chung</strong></span></a></li>
      @if(isset($treatment))
      @if($treatment->tdl_patient_type_id != 43 && $treatment->tdl_patient_type_id != 62)
      <li><a data-toggle="tab" href="#hdct"><span><strong>Chờ thực hiện</strong></span></a></li>
      <li><a data-toggle="tab" href="#hdct_done"><span><strong>Đã thực hiện</strong></span></a></li>
      @endif
      @if($treatment->tdl_treatment_type_id == 3)
      <li><a data-toggle="tab" href="#congkhai"><span><strong>Công khai DV KCB</strong></span></a></li>
      @endif
      @if($treatment->tdl_patient_type_id == 43 || $treatment->tdl_patient_type_id == 62)
      <li><a data-toggle="tab" href="#xemnhanh"><span><strong>Khám</strong></span></a></li>
      <li><a data-toggle="tab" href="#tuvan"><span style="color:orange;"><strong>BS Tư vấn</strong></span></a></li>
      @endif
      @if($treatment->tdl_patient_type_id == 102)
      <li><a data-toggle="tab" href="#sotiemchung"><span><strong>Sổ tiêm chủng</strong></span></a></li>
      @endif
      <li><a data-toggle="tab" href="#xemkq"><span><strong>Kết quả</strong></span></a></li>
      <li><a data-toggle="tab" href="#xemcdha"><span><strong>Phim/Ảnh</strong></span></a></li>
      <li><a data-toggle="tab" href="#xemchiphi"><span><strong>Chi phí KCB</strong></span></a></li>
      @endif
    </ul>

    <div class="tab-content">
      @include('patient.partials.patient_tthc')
      @include('patient.partials.patient_chothuchien')
      @include('patient.partials.patient_dathuchien')
      @include('patient.partials.patient_congkhai')
      @include('patient.partials.patient_xemkq')
      @include('patient.partials.patient_xemcdha')
      @include('patient.partials.patient_sotiemchung')
      @include('patient.partials.patient_xemnhanh')
      @include('patient.partials.patient_tuvan')
      @include('patient.partials.patient_xemchiphi')
    </div>
  </div>

  @include('patient.partials.footer')

<script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/vendor/bootstrap/dist/js/bootstrap.min.js') }}"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script src="//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    // ... các đoạn code bạn có sẵn ở đây ...

    // Nếu histories có dữ liệu rồi thì mask input
    @if(isset($treatment) && $treatment)
      var originalCode = $('#treatment_code').val();
      var originalPhone = $('#phone').val();

      if (originalCode.length > 3) {
        $('#treatment_code').data('full', originalCode); // lưu giá trị đầy đủ vào data attribute
        $('#treatment_code').val('*********' + originalCode.slice(-3));
      }

      if (originalPhone.length > 3) {
        $('#phone').data('full', originalPhone);
        $('#phone').val('*******' + originalPhone.slice(-3));
      }

      // Khi focus vào input thì trả lại giá trị đầy đủ để người dùng chỉnh sửa
      $('#treatment_code').on('focus', function() {
        if ($(this).data('full')) {
          $(this).val($(this).data('full'));
        }
      });

      $('#phone').on('focus', function() {
        if ($(this).data('full')) {
          $(this).val($(this).data('full'));
        }
      });
    @endif
  });
</script>

<script>
$(document).ready(function() {
  // Search form
  $('#searchForm').submit(function(e) {
    e.preventDefault();
    let code = $('#treatment_code').val().trim();
    let phone = $('#phone').val().trim();
    
    if (code.startsWith('*') || phone.startsWith('*')) {
      toastr.error('Vui lòng kiểm tra và sửa thông tin trước khi tra cứu!');
      return;
    }
    if (!code || code.length < 10 || !phone || phone.length < 9) {
      toastr.error('Vui lòng nhập đầy đủ Mã điều trị và Số điện thoại hợp lệ!');
      return;
    }
    fetch(`/encrypt-token?treatment_code=${encodeURIComponent(code)}&phone=${encodeURIComponent(phone)}`)
      .then(r => r.json())
      .then(d => {
        if (d.token && d.isExist) {
          window.location.href = `/view-guide-content?token=${encodeURIComponent(d.token)}`;
        } else if (d.token && !d.isExist) {
          toastr.error('Mã điều trị hoặc Số điện thoại không tồn tại trong hệ thống!');
        } else {
          toastr.error('Không thể tạo token, vui lòng thử lại!');
        }
      })
      .catch(() => toastr.error('Đã xảy ra lỗi khi tạo token!'));
  });

  // Format input treatment_code
  $('#treatment_code').blur(function() {
    let val = $(this).val().trim();
    while (val.length < 12) val = '0' + val;
    $(this).val(val);
  });

  $('#treatment_code, #phone').keypress(function(evt) {
    const code = evt.which || evt.keyCode;
    if (code < 48 || code > 57) evt.preventDefault();
  });

  // Toggle collapse icon
  $('#searchFormCollapse').on('shown.bs.collapse', function () {
    $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
  }).on('hidden.bs.collapse', function () {
    $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  });

  // Load danh sách ngày
  $.ajax({
    url: "{{ route('get-list-congkhai-days') }}",
    type: "GET",
    data: { treatment_id: "{{ $treatment->id ?? '' }}" },
    success: function(resp) {
      let days = resp.days || [];
      if (!days.length) {
        $('#congkhai-content').html('<p>Không có dữ liệu công khai.</p>');
        return;
      }
      let html = '<ul class="list-group">';
      days.forEach(day => {
        html += `<li class="list-group-item">
                  <a href="#" class="toggle-day" data-day="${day}">
                    <i class="fa fa-plus-square"></i> ${formatDate(day)}
                  </a>
                  <ul class="list-group nested" id="nested-day-${day}" style="display:none; margin-left:20px;">
                    <li>
                      <a href="#" class="toggle-type" data-day="${day}" data-type="dvkt">
                        <i class="fa fa-plus-square"></i> DVKT & VTYT
                      </a>
                      <div id="content-${day}-dvkt" style="display:none;"></div>
                    </li>
                    <li>
                      <a href="#" class="toggle-type" data-day="${day}" data-type="thuoc">
                        <i class="fa fa-plus-square"></i> Thuốc
                      </a>
                      <div id="content-${day}-thuoc" style="display:none;"></div>
                    </li>
                  </ul>
                </li>`;
      });
      html += '</ul>';
      $('#congkhai-content').html(html);
    },
    error: () => $('#congkhai-content').html('<p>Lỗi tải danh sách ngày.</p>')
  });

  // Toggle ngày
  $(document).on('click', '.toggle-day', function(e) {
    e.preventDefault();
    let day = $(this).data('day');
    let $nested = $(`#nested-day-${day}`);
    let $icon = $(this).find('i');
    $nested.toggle();
    $icon.toggleClass('fa-plus-square fa-minus-square');
  });

  // Toggle loại
  $(document).on('click', '.toggle-type', function(e) {
    e.preventDefault();
    let day = $(this).data('day');
    let type = $(this).data('type');
    let $content = $(`#content-${day}-${type}`);
    let $icon = $(this).find('i');

    if ($content.is(':visible')) {
      $content.slideUp();
      $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
      return;
    }

    if ($content.data('loaded')) {
      $content.slideDown();
      $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
      return;
    }

    $content.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>').show();

    let url = type === 'dvkt' ? "{{ route('get-list-congkhai-dvkt') }}" : "{{ route('get-list-congkhai-thuoc') }}";

    $.get(url, { treatment_id: "{{ $treatment->id ?? '' }}", day }, function(res) {
      let details = res.details || [];
      let html = '';

      if (type === 'dvkt' && details.length) {
        let grouped = {};
        details.forEach(i => { grouped[i.type] = grouped[i.type] || []; grouped[i.type].push(i); });
        Object.keys(grouped).forEach((grp, idx) => {
          let groupId = `grp-${day}-${idx}`;
          html += `<p><a href="#" class="toggle-type-group" data-target="${groupId}"><i class="fa fa-plus-square"></i> <strong>${grp}</strong></a></p>`;
          html += `<div id="${groupId}" style="display:none;"><div class="table-responsive"><table class="table table-bordered  table-sm table-hover table-striped">
                    <thead><tr><th>STT</th><th>Tên DVKT/VTYT</th><th>Đơn vị tính</th><th>Số lượng</th><th>Đơn giá</th></tr></thead><tbody>`;
          grouped[grp].forEach((i, idx2) => {
            html += `<tr>
                      <td>${idx2 + 1}</td>
                      <td>${i.name}</td>
                      <td>${i.unit}</td>
                      <td class="text-right">${Number(i.amount).toLocaleString('vi-VN')}</td>
                      <td class="text-right">${Number(i.price).toLocaleString('vi-VN')}</td>
                    </tr>`;
          });
          html += '</tbody></table></div></div>';
        });
      }
      else if (type === 'thuoc' && details.length) {
        html += `<div class="table-responsive"><table class="table table-bordered table-sm table-hover table-striped">
                  <thead><tr><th>STT</th><th>Tên thuốc</th><th>Dạng bào chế</th><th>Hàm lượng</th><th>Hướng dẫn</th><th>ĐVT</th><th>Số lượng</th><th>Đơn giá</th></tr></thead><tbody>`;
        details.forEach((i, idx) => {
          html += `<tr>
                    <td>${idx + 1}</td>
                    <td>${i.name || ''}</td>
                    <td>${i.form || ''}</td>
                    <td>${i.concentration || ''}</td>
                    <td>${i.tutorial || ''}</td>
                    <td>${i.unit}</td>
                    <td class="text-right">${Number(i.amount).toLocaleString('vi-VN')}</td>
                    <td class="text-right">${Number(i.price).toLocaleString('vi-VN')}</td>
                  </tr>`;
        });
        html += '</tbody></table></div>';
      } else {
        html = '<p>Không có dữ liệu.</p>';
      }

      $content.html(html);
      $content.data('loaded', true);
      $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
    }).fail(() => {
      $content.html('<p>Lỗi tải dữ liệu.</p>').data('loaded', true);
    });
  });

  // Toggle type group trong DVKT/VTYT
  $(document).on('click', '.toggle-type-group', function(e) {
    e.preventDefault();
    let target = $(this).data('target');
    let $target = $(`#${target}`);
    let $icon = $(this).find('i');
    $target.slideToggle();
    $icon.toggleClass('fa-plus-square fa-minus-square');
  });

});

// Utils
function formatDate(val) {
  if (!val || val.length < 14) return val;
  return `${val.substring(6,8)}/${val.substring(4,6)}/${val.substring(0,4)}`;
}
</script>
</body>
</html>