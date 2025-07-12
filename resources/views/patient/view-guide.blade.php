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
        <button class="btn btn-xs btn-default" type="button" data-toggle="collapse" data-target="#searchFormCollapse" aria-expanded="{{ isset($patientHistory) && count($patientHistory) > 0 ? 'false' : 'true' }}" aria-controls="searchFormCollapse">
          <i class="fa fa-chevron-down"></i>
        </button>
      </div>
      <div id="searchFormCollapse" class="panel-collapse collapse {{ (!isset($histories) || $histories->isEmpty()) ? 'in' : '' }}">
        <div class="panel-body">
          <form id="searchForm" method="GET" action="#">
            <div class="form-group row">
              <label class="col-sm-3 col-form-label">Mã BN/Mã ĐT/CCCD</label>
              <div class="col-sm-9">
                <input class="form-control" type="tel" id="code" name="code" placeholder="Mã ĐT/Mã BN/CCCD" maxlength="12" value="{{ $param_code }}">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-3 col-form-label">Số điện thoại</label>
              <div class="col-sm-9">
                <input class="form-control" type="tel" id="phone" name="phone" placeholder="Số điện thoại" maxlength="11" value="{{ $param_phone }}">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12">
                <button class="btn btn-info float-right">
                  <i class="fa fa-search"></i> Xem lịch sử
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @include('patient.partials.patient_history')

  @include('patient.partials.footer')
  
  <script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/adminlte/vendor/bootstrap/dist/js/bootstrap.min.js') }}"></script>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
  <script src="{{ asset('/js/customize.js')}}"></script>

  <script>
    $('#searchFormCollapse').on('shown.bs.collapse', function () {
      $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    $('#searchFormCollapse').on('hidden.bs.collapse', function () {
      $(this).prev('.panel-heading').find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });
  $(document).ready(function() {
    $('#code').on('blur', function() {
        var code = $(this).val().trim(); // Remove whitespace from both ends of the input
        if (code.length > 0 && code.length < 10) { // Check if the code has less than 10 characters
            while (code.length < 10) {
                code = '0' + code; // Add zeros to the start until it has exactly 10 characters
            }
        } else if (code.length > 10) { // Check if the code has more than 10 characters
            while (code.length < 12) {
                code = '0' + code; // Add zeros to the start until it has exactly 12 characters
            }
        }
        $(this).val(code); // Update the input field with the padded code
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
    $('#code, #phone').on('keypress', function(evt) {
      return isNumberKey(evt);
    });

    $('#searchForm').submit(function (event) {
      event.preventDefault(); // Chặn submit mặc định

      var code = $('#code').val().trim();
      var phone = $('#phone').val().trim();
      // Nếu giá trị input vẫn đang ở dạng mask thì không cho submit
      if (code.startsWith('*') || phone.startsWith('*')) {
        toastr.error('Vui lòng sửa thông tin trước khi tìm kiếm.');
        return false;
      }

      if (!code || code.length < 10 || !phone || phone.length < 9) {
        toastr.error('Phải nhập cả mã và số điện thoại hợp lệ.');
        return false;
      }

      fetch(`/encrypt-token-general?code=${encodeURIComponent(code)}&phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(data => {
          if (data.token && data.isExist) {
            window.location.href = `/view-guide?token=${encodeURIComponent(data.token)}`;
          } else if (data.token && !data.isExist) {
            toastr.error('Mã BN/Mã ĐT/CCCD hoặc Số điện thoại không tồn tại trong hệ thống.');
          } else {
            toastr.error('Không thể tạo token.');
          }
        })
        .catch(() => toastr.error('Lỗi hệ thống khi tạo token.'));
    });

  });
  </script>
<script>
$(document).ready(function() {
  // ... các đoạn code bạn có sẵn ở đây ...

  // Nếu histories có dữ liệu rồi thì mask input
  @if(isset($histories) && $histories->isNotEmpty())
    var originalCode = $('#code').val();
    var originalPhone = $('#phone').val();

    if (originalCode.length > 3) {
      $('#code').data('full', originalCode); // lưu giá trị đầy đủ vào data attribute
      $('#code').val('*********' + originalCode.slice(-3));
    }

    if (originalPhone.length > 3) {
      $('#phone').data('full', originalPhone);
      $('#phone').val('*******' + originalPhone.slice(-3));
    }

    // Khi focus vào input thì trả lại giá trị đầy đủ để người dùng chỉnh sửa
    $('#code').on('focus', function() {
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
</body>
</html>