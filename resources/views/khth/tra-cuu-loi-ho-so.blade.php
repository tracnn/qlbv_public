@extends('adminlte::page')
@section('title', 'Tra cứu lỗi hồ sơ')
@section('content_header')<h1>Tra cứu lỗi hồ sơ</h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-6">
        <label>Mã điều trị</label>
        <div class="input-group">
          <input type="text" id="ma-dieu-tri" class="form-control input-lg"
                 placeholder="Nhập hoặc quét mã điều trị" autofocus autocomplete="off">
          <span class="input-group-btn">
            <button id="btn-tra-cuu" class="btn btn-primary btn-lg"><i class="fa fa-search"></i> Tra cứu</button>
          </span>
        </div>
        <p class="help-block" id="loi-nhap" style="color:#dd4b39"></p>
      </div>
      <div class="col-md-3">
        <label>&nbsp;</label>
        <div>
          <button id="btn-camera" class="btn btn-default btn-lg" style="display:none">
            <i class="fa fa-camera"></i> Quét bằng camera
          </button>
          <p class="help-block" id="camera-khong-san-sang" style="display:none">
            Trình duyệt không cho dùng camera ở trang này (cần HTTPS).
          </p>
        </div>
      </div>
    </div>
    <div class="row" id="vung-camera" style="display:none; margin-top:10px">
      <div class="col-md-6">
        <div id="khung-camera"></div>
        <button id="btn-dong-camera" class="btn btn-default" style="margin-top:5px">Đóng camera</button>
      </div>
    </div>
  </div>
</div>

<div id="ket-qua" style="display:none">
  <p><a id="btn-in" class="btn btn-default" target="_blank"><i class="fa fa-print"></i> In phiếu lỗi</a></p>
  <div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Thông tin hồ sơ</h3></div>
    <div class="box-body" id="khoi-ho-so"></div>
  </div>

  <div class="callout callout-success" id="khong-loi" style="display:none">
    <h4>Không phát hiện lỗi trên hồ sơ này</h4>
  </div>

  <div class="box box-danger">
    <div class="box-header with-border">
      <h3 class="box-title">Sai sót y lệnh <span class="badge" id="dem-order-check">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-order-check"></div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi tra thẻ BHYT <span class="badge" id="dem-hein-card">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-hein-card"></div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi XML3176 <span class="badge" id="dem-xml3176">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-xml3176"></div>
  </div>
</div>
@stop

@section('js')
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
$(function () {
  var URL_TRA_CUU = '{{ route('khth.tra-cuu-loi-ho-so-tra-cuu') }}';
  var URL_DOI_TRANG_THAI = '{{ route('khth.order-check-update-status') }}';
  var DUOC_DOI_TRANG_THAI = {{ Auth::user()->hasRole('order-check') ? 'true' : 'false' }};
  var maHienTai = '';

  function thoat(s) {
    return $('<div>').text(s === null || s === undefined ? '' : s).html();
  }

  function bang(cot, dong, veDong) {
    if (!dong.length) { return '<p class="text-muted">Không có</p>'; }
    var h = '<table class="table table-bordered table-condensed"><thead><tr>';
    cot.forEach(function (c) { h += '<th>' + thoat(c) + '</th>'; });
    h += '</tr></thead><tbody>';
    dong.forEach(function (d) { h += veDong(d); });
    return h + '</tbody></table>';
  }

  function veHoSo(ho, loi) {
    if (loi) { return '<p style="color:#dd4b39">' + thoat(loi) + '</p>'; }
    if (!ho) { return '<p style="color:#dd4b39">Không tìm thấy hồ sơ với mã này trên HIS</p>'; }

    var truong = [
      ['Mã điều trị', ho.treatment_code], ['Họ tên', ho.patient_name],
      ['Ngày sinh', ho.patient_dob_text], ['Giới tính', ho.gender_name],
      ['Mã thẻ BHYT', ho.hein_card_number], ['Nơi ĐKBĐ', ho.hein_medi_org_code],
      ['Hạn thẻ từ', ho.hein_card_from_time_text], ['Hạn thẻ đến', ho.hein_card_to_time_text],
      ['Khoa', ho.department_name], ['Loại điều trị', ho.treatment_type_name],
      ['Vào lúc', ho.in_time_text], ['Ra lúc', ho.out_time_text],
      ['Cơ sở KCB', ho.ma_cskcb]
    ];

    var h = '<div class="row">';
    truong.forEach(function (t) {
      h += '<div class="col-md-4"><strong>' + thoat(t[0]) + ':</strong> ' +
           thoat(t[1] || '—') + '</div>';
    });
    return h + '</div>';
  }

  function veOrderCheck(dong) {
    var cot = ['Mức độ', 'Luật', 'Nội dung', 'Phát hiện lúc', 'Trạng thái'];
    if (DUOC_DOI_TRANG_THAI) { cot.push('Xử lý'); }

    return bang(cot, dong, function (d) {
      var nhan = d.severity === 'critical'
        ? '<span class="label label-danger">Nghiêm trọng</span>'
        : '<span class="label label-warning">' + thoat(d.severity) + '</span>';
      var h = '<tr><td>' + nhan + '</td><td>' + thoat(d.rule_code) + '</td><td>' +
              thoat(d.message) + '</td><td>' + thoat(d.detected_at) + '</td><td>' +
              thoat(d.status) + '</td>';
      if (DUOC_DOI_TRANG_THAI) {
        h += '<td><select class="form-control input-sm doi-trang-thai" data-id="' + d.id + '">' +
             '<option value="">— đổi —</option><option value="seen">Đã xem</option>' +
             '<option value="processed">Đã xử lý</option>' +
             '<option value="false_positive">Bỏ qua</option></select></td>';
      }
      return h + '</tr>';
    });
  }

  function veHeinCard(dong) {
    return bang(['Mã tra cứu', 'Mã kiểm tra', 'Kết quả', 'Ghi chú', 'Mã thẻ', 'Tra lúc'], dong, function (d) {
      return '<tr><td>' + thoat(d.ma_tracuu) + '</td><td>' + thoat(d.ma_kiemtra) +
             '</td><td>' + thoat(d.ma_ketqua) + '</td><td>' + thoat(d.ghi_chu) +
             '</td><td>' + thoat(d.ma_the_masked) + '</td><td>' + thoat(d.checked_at) + '</td></tr>';
    });
  }

  function veXml3176(dong) {
    return bang(['XML', 'STT', 'Mã lỗi', 'Tên lỗi', 'Mô tả', 'Ngày YL'], dong, function (d) {
      var ma = d.critical_error
        ? '<span class="label label-danger">' + thoat(d.error_code) + '</span>'
        : thoat(d.error_code);
      return '<tr><td>' + thoat(d.xml) + '</td><td>' + thoat(d.stt) + '</td><td>' + ma +
             '</td><td>' + thoat(d.error_name) + '</td><td>' + thoat(d.description) +
             '</td><td>' + thoat(d.ngay_yl) + '</td></tr>';
    });
  }

  function traCuu() {
    var ma = $.trim($('#ma-dieu-tri').val());
    $('#loi-nhap').text('');

    if (!ma) { $('#loi-nhap').text('Chưa nhập mã điều trị'); return; }

    $.getJSON(URL_TRA_CUU, { treatment_code: ma })
      .done(function (r) {
        maHienTai = ma;
        $('#khoi-ho-so').html(veHoSo(r.profile, r.profile_error));
        $('#khoi-order-check').html(veOrderCheck(r.data.order_check));
        $('#khoi-hein-card').html(veHeinCard(r.data.hein_card));
        $('#khoi-xml3176').html(veXml3176(r.data.xml3176));
        $('#dem-order-check').text(r.summary.order_check);
        $('#dem-hein-card').text(r.summary.hein_card);
        $('#dem-xml3176').text(r.summary.xml3176);
        $('#khong-loi').toggle(!r.summary.has_error);
        $('#btn-in').attr('href',
          '{{ route('khth.tra-cuu-loi-ho-so-in') }}?treatment_code=' + encodeURIComponent(ma));
        $('#ket-qua').show();
        // Boi den de luot quet ke tiep ghi de: may quet barcode go chuoi roi gui Enter.
        $('#ma-dieu-tri').focus().select();
      })
      .fail(function (x) {
        var t = (x.responseJSON && x.responseJSON.message) || 'Không tra cứu được';
        $('#loi-nhap').text(t);
      });
  }

  $('#btn-tra-cuu').on('click', traCuu);
  $('#ma-dieu-tri').on('keydown', function (e) {
    if (e.which === 13) { e.preventDefault(); traCuu(); }
  });

  $(document).on('change', '.doi-trang-thai', function () {
    var $s = $(this), status = $s.val();
    if (!status) { return; }

    $.post(URL_DOI_TRANG_THAI, {
      _token: '{{ csrf_token() }}', id: $s.data('id'), status: status
    }).done(function () {
      $('#ma-dieu-tri').val(maHienTai);
      traCuu();
    }).fail(function () {
      alert('Không đổi được trạng thái');
      $s.val('');
    });
  });

  // Camera chi kha dung tren HTTPS (hoac localhost). Tren HTTP thuan
  // navigator.mediaDevices khong ton tai -> hien chu thich thay vi mot nut bam khong an.
  var coCamera = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  $(coCamera ? '#btn-camera' : '#camera-khong-san-sang').show();

  var mayQuet = null;

  function dongCamera() {
    if (!mayQuet) { return; }
    mayQuet.stop().then(function () {
      mayQuet.clear();
      mayQuet = null;
      $('#vung-camera').hide();
    });
  }

  $('#btn-camera').on('click', function () {
    if (mayQuet) { return; }

    $('#vung-camera').show();
    mayQuet = new Html5Qrcode('khung-camera');
    mayQuet.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      function (ma) {
        $('#ma-dieu-tri').val(ma);
        dongCamera();
        traCuu();
      },
      function () { /* moi khung hinh khong doc duoc deu goi vao day - bo qua */ }
    ).catch(function () {
      $('#vung-camera').hide();
      mayQuet = null;
      alert('Không mở được camera');
    });
  });

  $('#btn-dong-camera').on('click', dongCamera);
});
</script>
@stop
