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
      </div>
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
  </script>
</body>
</html>