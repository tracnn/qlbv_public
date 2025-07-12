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
      <li><a data-toggle="tab" href="#congkhai"><span><strong>Công khai</strong></span></a></li>
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
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
  <script src="{{ asset('/js/customize.js')}}"></script>

  <script>
    document.getElementById('searchForm').addEventListener('submit', function (e) {
      e.preventDefault(); // chặn submit mặc định

      var treatmentCode = $('#treatment_code').val().trim();
      var phone = $('#phone').val().trim();
      // Nếu input đang ở dạng mask thì không cho submit
      if (treatmentCode.startsWith('*') || phone.startsWith('*')) {
        toastr.error('Vui lòng bấm vào các trường để sửa thông tin trước khi tra cứu!');
        return false;
      }

      if (!treatmentCode || treatmentCode.length < 10 || !phone || phone.length < 9) {
        toastr.error('Phải nhập cả Mã điều trị và Số điện thoại hợp lệ.');
        return false;
      }
      // Gọi API để mã hóa dữ liệu rồi redirect
      fetch(`/encrypt-token?treatment_code=${encodeURIComponent(treatmentCode)}&phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(data => {
          if (data.token && data.isExist) {
            window.location.href = `/view-guide-content?token=${encodeURIComponent(data.token)}`;
          } else if (data.token && !data.isExist) {
            toastr.error('Mã điều trị hoặc Số điện thoại không tồn tại trong hệ thống.');
          } else {
            toastr.error('Không thể tạo token');
          }
        })
        .catch(() => toastr.error('Có lỗi xảy ra khi tạo token'));
    });

    $('#searchFormCollapse').on('shown.bs.collapse', function () {
      $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    $('#searchFormCollapse').on('hidden.bs.collapse', function () {
      $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });
  </script>

  <script>
    $(document).ready(function() {
      $('#treatment_code').on('blur', function() {
        var code = $(this).val().trim(); // Loại bỏ khoảng trắng đầu và cuối của chuỗi nhập
        if (code.length > 0) { // Kiểm tra xem trường có dữ liệu không
          while (code.length < 12) {
            code = '0' + code; // Thêm số 0 vào đầu cho đến khi đủ 12 ký tự
          }
          $(this).val(code); // Cập nhật giá trị của trường với số 0 được thêm vào
        }
      });
      // Hàm kiểm tra ký tự nhập vào có phải là số không
      function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        // Chỉ cho phép nhập số
        if (charCode < 48 || charCode > 57) {
            evt.preventDefault();
            return false;
        }
        return true;
      }

      // Áp dụng hàm kiểm tra cho các trường nhập số
      $('#treatment_code, #phone').on('keypress', function(evt) {
        return isNumberKey(evt);
      });
    });

    //Dữ liệu công khai
    $(document).ready(function() {
      // Hiển thị spinner trước khi gửi request
      $('#congkhai-content').html('<div class="text-center" style="padding: 10px;"><i class="fa fa-spinner fa-spin"></i> Đang tải danh sách...</div>');

      $.ajax({
        url: "{{ route('get-list-congkhai') }}",
        type: "GET",
        data: {
          treatment_id: "{{ $treatment->id }}"
        },
        success: function(response) {
          var list_congkhai = response.list_congkhai;
          if (list_congkhai && list_congkhai.length > 0) {
            var html = '<table class="table display table-hover dtr-inline" width="100%">';
            html += '<thead><tr>';
            html += '<th></th>'; // Cột nút (+/-)
            html += '<th>Mã y lệnh</th>';
            html += '<th>Thời gian chỉ định</th>';
            html += '<th>Loại đơn</th>';
            html += '<th>Người chỉ định</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            list_congkhai.forEach(function(item) {
              html += '<tr data-id="' + item.id + '" data-loaded="false">';
              html += '<td><a href="#" class="toggle-detail" data-id="' + item.id + '"><i class="fa fa-plus-square"></i></a></td>';
              html += '<td>' + (item.service_req_code || '') + '</td>';
              html += '<td>' + (formatDateTime(item.intruction_time) || '') + '</td>';
              html += '<td>' + (item.service_req_type_name || '') + '</td>';
              html += '<td>' + (item.request_user_title || '') + ' ' + (item.request_username || '') + '</td>';
              html += '</tr>';
              html += '<tr class="detail-row" id="detail-row-' + item.id + '" style="display:none;"><td colspan="5"></td></tr>';
            });
            html += '</tbody></table>';
            $('#congkhai-content').html(html);
          } else {
            $('#congkhai-content').html('<p>Không có dữ liệu công khai.</p>');
          }
        },
        error: function() {
          $('#congkhai-content').html('<p>Không thể tải dữ liệu công khai.</p>');
        }
      });
    });

    // Xử lý toggle hiển thị chi tiết
    $(document).on('click', '.toggle-detail', function(e) {
      e.preventDefault();
      var $icon = $(this).find('i');
      var id = $(this).data('id');
      var $parentRow = $(this).closest('tr');
      var $detailRow = $('#detail-row-' + id);

      if ($detailRow.is(':visible')) {
        $detailRow.hide();
        $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
      } else {
        if ($parentRow.attr('data-loaded') === 'true') {
          $detailRow.show();
          $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
        } else {
          // 👉 Hiển thị spinner trong khi chờ load
          $detailRow.find('td').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>');
          $detailRow.show();

          $.ajax({
            url: "{{ route('get-list-congkhai-content') }}",
            type: "GET",
            data: { id: id },
            success: function(res) {
              var details = res.details;
              if (details && details.length > 0) {
                var detailHtml = '<table class="table display table-hover dtr-inline" width="100%">';
                detailHtml += '<thead><tr><th>Tên</th><th>Loại</th><th>ĐVT</th><th>Hàm lượng</th><th>SL</th></tr></thead><tbody>';
                details.forEach(function(d) {
                  detailHtml += '<tr>';
                  detailHtml += '<td>' + (d.tdl_service_name || '') + '</td>';
                  detailHtml += '<td>' + (d.service_type_name || '') + '</td>';
                  detailHtml += '<td>' + (d.service_unit_name || '') + '</td>';
                  detailHtml += '<td>' + (d.tdl_medicine_concentra || '') + '</td>';
                  detailHtml += '<td class="text-right">' + (d.amount || 0) + '</td>';
                  detailHtml += '</tr>';
                });
                detailHtml += '</tbody></table>';
                $detailRow.find('td').html(detailHtml);
              } else {
                $detailRow.find('td').html('<p>Không có chi tiết.</p>');
              }
              $detailRow.show();
              $parentRow.attr('data-loaded', 'true');
              $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            },
            error: function() {
              $detailRow.find('td').html('<p>Không thể tải chi tiết.</p>');
              $detailRow.show();
              $parentRow.attr('data-loaded', 'true');
              $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            }
          });
        }
      }
    });

    function formatDateTime(value) {
      if (!value || value.length < 14) return value;
      var year = value.substring(0, 4);
      var month = value.substring(4, 6);
      var day = value.substring(6, 8);
      var hour = value.substring(8, 10);
      var minute = value.substring(10, 12);
      return `${day}/${month}/${year} ${hour}:${minute}`;
    }
  </script>

<script>
  $(document).ready(function() {
    @if(isset($treatment) && $treatment)
      var originalTreatmentCode = $('#treatment_code').val();
      var originalPhone = $('#phone').val();

      if (originalTreatmentCode.length > 3) {
        $('#treatment_code').data('full', originalTreatmentCode);
        $('#treatment_code').val('*********' + originalTreatmentCode.slice(-3));
      }

      if (originalPhone.length > 3) {
        $('#phone').data('full', originalPhone);
        $('#phone').val('*******' + originalPhone.slice(-3));
      }

      $('#treatment_code').on('focus', function() {
        var full = $(this).data('full');
        if (full) {
          $(this).val(full);
        }
      });

      $('#phone').on('focus', function() {
        var full = $(this).data('full');
        if (full) {
          $(this).val(full);
        }
      });
    @endif
  });
</script>
</body>
</html>