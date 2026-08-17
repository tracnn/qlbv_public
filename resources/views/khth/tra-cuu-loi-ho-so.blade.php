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
  <p>
    <a id="btn-in" class="btn btn-default" target="_blank"><i class="fa fa-print"></i> In phiếu lỗi</a>
    <button id="btn-tra-lai-the" class="btn btn-default"><i class="fa fa-refresh"></i> Tra lại thẻ BHYT</button>
    <span id="ket-qua-tra-lai-the" style="margin-left:8px"></span>
  </p>
  <div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Thông tin hồ sơ</h3></div>
    <div class="box-body" id="khoi-ho-so"></div>
  </div>

  <div class="callout callout-danger" id="loi-ket-qua" style="display:none"></div>

  <div class="callout callout-success" id="khong-loi" style="display:none">
    <h4>Không phát hiện lỗi trên hồ sơ này</h4>
  </div>

  <div class="box box-danger">
    <div class="box-header with-border">
      <h3 class="box-title">Sai sót y lệnh <span class="badge" id="dem-order-check">0</span></h3>
    </div>
    <div class="box-body table-responsive">
      <table id="bang-order-check" class="table table-bordered table-condensed" style="width:100%">
        <thead><tr>
          <th>Mức độ</th><th>Luật</th><th>Nội dung</th><th>Phát hiện lúc</th>
          <th>Trạng thái</th><th>Xử lý</th>
        </tr></thead>
      </table>
    </div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi tra thẻ BHYT <span class="badge" id="dem-hein-card">0</span></h3>
    </div>
    <div class="box-body table-responsive">
      <table id="bang-hein-card" class="table table-bordered table-condensed" style="width:100%">
        <thead><tr>
          <th>Mã tra cứu</th><th>Mã kiểm tra</th><th>Kết quả</th>
          <th>Ghi chú</th><th>Mã thẻ</th><th>Tra lúc</th>
        </tr></thead>
      </table>
    </div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi XML3176 <span class="badge" id="dem-xml3176">0</span></h3>
    </div>
    <div class="box-body table-responsive">
      <table id="bang-xml3176" class="table table-bordered table-condensed" style="width:100%">
        <thead><tr>
          <th>XML</th><th>STT</th><th>Mã lỗi</th><th>Tên lỗi</th><th>Mô tả</th><th>Ngày YL</th>
        </tr></thead>
      </table>
    </div>
  </div>
</div>
@stop

@section('js')
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
@include('partials.dt-vi')
<script>
$(function () {
  var URL_TRA_CUU = '{{ route('khth.tra-cuu-loi-ho-so-tra-cuu') }}';
  var URL_DOI_TRANG_THAI = '{{ route('khth.order-check-update-status') }}';
  var DUOC_DOI_TRANG_THAI = {{ Auth::user()->hasRole('order-check') ? 'true' : 'false' }};
  // Nhan tieng Viet cho severity/status: MOT nguon duy nhat la ViolationLabels o PHP,
  // xuong day qua json_encode - khong tu dich lai o JS keo lech voi OrderCheckController
  // va phieu in.
  var NHAN_SEVERITY = {!! json_encode(\App\Services\OrderCheck\ViolationLabels::severity()) !!};
  var NHAN_STATUS = {!! json_encode(\App\Services\OrderCheck\ViolationLabels::status()) !!};
  var maHienTai = '';

  function thoat(s) {
    return $('<div>').text(s === null || s === undefined ? '' : s).html();
  }

  // Chuoi ngay MySQL "Y-m-d H:i:s" -> "d/m/Y H:i", dong bo voi cach app dang hien ngay
  // o cac man khac (xem OrderCheckController::fetch).
  function ngayGio(s) {
    if (!s) { return ''; }
    var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(s);
    if (!m) { return s; }
    return m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5];
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

  // Cot dang van ban thuan: van phai qua thoat() vi DataTables KHONG tu thoat gia tri.
  function cotChu(khoa) {
    return { data: khoa, defaultContent: '', render: function (v) { return thoat(v); } };
  }

  /**
   * Cot ngay gio: hien "d/m/Y H:i" nhung sap xep tren chuoi goc "Y-m-d H:i:s".
   * Neu sap xep tren chuoi da dinh dang thi 01/09 se dung truoc 02/08 - sai thu tu thoi
   * gian, ma bang loi thi thu tu thoi gian chinh la thu can nhat.
   */
  function cotNgayGio(khoa) {
    return {
      data: khoa,
      defaultContent: '',
      render: function (v, loai) {
        return loai === 'display' ? thoat(ngayGio(v)) : (v || '');
      }
    };
  }

  var CAU_HINH_BANG = {
    language: DT_VI,
    autoWidth: false,
    pageLength: 10,
    lengthChange: false,
    // Giu nguyen thu tu service da sap xep (vi pham theo detected_at giam dan, XML3176
    // theo xml roi stt). Nguoi dung van bam duoc vao tieu de cot de sap lai.
    order: [],
    deferRender: true
  };

  var bangOrderCheck = null;
  var bangHeinCard = null;
  var bangXml3176 = null;

  function taoBangOrderCheck() {
    return $('#bang-order-check').DataTable($.extend({}, CAU_HINH_BANG, {
      columns: [
        {
          data: 'severity',
          render: function (v) {
            var cls = v === 'critical' ? 'label-danger' : (v === 'warning' ? 'label-warning' : 'label-info');
            return '<span class="label ' + cls + '">' + thoat(NHAN_SEVERITY[v] || v) + '</span>';
          }
        },
        cotChu('rule_code'),
        cotChu('message'),
        cotNgayGio('detected_at'),
        { data: 'status', render: function (v) { return thoat(NHAN_STATUS[v] || v); } },
        {
          data: 'id',
          orderable: false,
          searchable: false,
          // An han cot thay vi dung hai bo cot khac nhau: thead trong blade luon co du
          // cot, chi hien voi nguoi co quyen order-check.
          visible: DUOC_DOI_TRANG_THAI,
          render: function (v) {
            return '<select class="form-control input-sm doi-trang-thai" data-id="' + thoat(v) + '">' +
              '<option value="">— đổi —</option><option value="seen">Đã xem</option>' +
              '<option value="processed">Đã xử lý</option>' +
              '<option value="false_positive">Bỏ qua</option></select>';
          }
        }
      ]
    }));
  }

  function taoBangHeinCard() {
    return $('#bang-hein-card').DataTable($.extend({}, CAU_HINH_BANG, {
      columns: [
        cotChu('ma_tracuu'), cotChu('ma_kiemtra'), cotChu('ma_ketqua'),
        cotChu('ghi_chu'), cotChu('ma_the_masked'), cotNgayGio('checked_at')
      ]
    }));
  }

  function taoBangXml3176() {
    return $('#bang-xml3176').DataTable($.extend({}, CAU_HINH_BANG, {
      columns: [
        cotChu('xml'),
        cotChu('stt'),
        {
          data: 'error_code',
          render: function (v, loai, dong) {
            if (loai !== 'display') { return v || ''; }
            return dong.critical_error
              ? '<span class="label label-danger">' + thoat(v) + '</span>'
              : thoat(v);
          }
        },
        cotChu('error_name'), cotChu('description'), cotChu('ngay_yl')
      ]
    }));
  }

  /**
   * Nap du lieu moi vao mot bang. Bang duoc tao o lan nap dau tien chu khong tao san luc
   * trang tai xong: #ket-qua dang an, DataTables do be rong cot tren phan tu an se ra 0.
   * columns.adjust() sau khi #ket-qua hien lo not phan con lai.
   */
  function napBang(bang, taoBang, dong) {
    var b = bang || taoBang();

    b.clear();
    b.rows.add(dong || []);
    b.draw();

    return b;
  }

  // Dem luot goi tang dan: may quet barcode co the ban hai lan lien tiep, phan hoi cua
  // luot cu ve sau phai bi bo qua, khong duoc ve de len ket qua cua luot moi.
  var demTraCuu = 0;

  function traCuu() {
    var ma = $.trim($('#ma-dieu-tri').val());
    $('#loi-nhap').text('');

    // An ket qua cu VA xoa maHienTai NGAY tu dau, truoc khi goi server: neu luot tra cuu
    // nay that bai (mat phien, loi 500, mang chap chon) thi man hinh khong duoc tiep tuc
    // hien ho so/loi cua ma truoc do, va nut In / Tra lai the khong duoc tro vao ma cu.
    $('#ket-qua').hide();
    maHienTai = '';

    if (!ma) { $('#loi-nhap').text('Chưa nhập mã điều trị'); return; }

    demTraCuu++;
    var luotNay = demTraCuu;

    $.getJSON(URL_TRA_CUU, { treatment_code: ma })
      .done(function (r) {
        if (luotNay !== demTraCuu) { return; } // co luot goi moi hon da chay sau luot nay

        maHienTai = ma;
        $('#khoi-ho-so').html(veHoSo(r.profile, r.profile_error));
        $('#loi-ket-qua').toggle(!!r.data_error).text(r.data_error || '');
        bangOrderCheck = napBang(bangOrderCheck, taoBangOrderCheck, r.data.order_check);
        bangHeinCard = napBang(bangHeinCard, taoBangHeinCard, r.data.hein_card);
        bangXml3176 = napBang(bangXml3176, taoBangXml3176, r.data.xml3176);
        $('#dem-order-check').text(r.summary.order_check);
        $('#dem-hein-card').text(r.summary.hein_card);
        $('#dem-xml3176').text(r.summary.xml3176);
        $('#khong-loi').toggle(!r.summary.has_error);
        $('#btn-in').attr('href',
          '{{ route('khth.tra-cuu-loi-ho-so-in') }}?treatment_code=' + encodeURIComponent(ma));
        $('#ket-qua').show();
        // Do lai be rong cot SAU khi #ket-qua hien: DataTables khoi tao tren phan tu dang
        // an se do ra 0 va tieu de lech khoi than bang.
        bangOrderCheck.columns.adjust();
        bangHeinCard.columns.adjust();
        bangXml3176.columns.adjust();
        // Boi den de luot quet ke tiep ghi de: may quet barcode go chuoi roi gui Enter.
        $('#ma-dieu-tri').focus().select();
      })
      .fail(function (x) {
        if (luotNay !== demTraCuu) { return; }

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

  /**
   * Vung giai ma phai la HINH CHU NHAT NGANG, khong duoc de o vuong.
   *
   * Thu vien cat khung hinh dung bang qrbox roi moi dua di giai ma. Voi o vuong 250px,
   * ma vach Code 128 cua ma dieu tri (14 ky tu, rat dai va thap) chi nhet vua chieu ngang
   * khi nguoi dung lui that xa - luc do be rong moi vach tut xuong duoi nguong doc duoc,
   * nen barcode khong bao gio giai ma noi trong khi QR van chay (QR vuong, nam gon trong
   * o do). Khung ngang giai quyet ca hai loai ma.
   */
  function khungQuet(rongKhungHinh, caoKhungHinh) {
    var rong = Math.floor(rongKhungHinh * 0.9);
    var cao = Math.floor(Math.min(caoKhungHinh * 0.6, Math.max(rong * 0.4, 140)));

    // Thu vien NEM loi neu mot chieu nho hon 50px, va loi do lai noi ra dung cho voi loi
    // "khong mo duoc camera" - phai chan tai day thay vi de no lam hong ca lan mo camera.
    return { width: Math.max(rong, 50), height: Math.max(cao, 60) };
  }

  function dongCamera() {
    if (!mayQuet) { return; }
    mayQuet.stop().then(function () {
      mayQuet.clear();
      mayQuet = null;
      $('#vung-camera').hide();
    }).catch(function () {
      // stop() bi tu choi (vi du camera da bi rut/thu hoi quyen giua chung): van phai don
      // dep trang thai o day, khong thi nut "Quet bang camera" se khong mo lai duoc vi
      // dieu kien "if (mayQuet) return" o tren coi nhu dang mo.
      mayQuet = null;
      $('#vung-camera').hide();
    });
  }

  function batDauQuet(rangBuocCamera) {
    return mayQuet.start(
      rangBuocCamera,
      { fps: 10, qrbox: khungQuet },
      function (ma) {
        $('#ma-dieu-tri').val(ma);
        dongCamera();
        traCuu();
      },
      function () { /* moi khung hinh khong doc duoc deu goi vao day - bo qua */ }
    );
  }

  $('#btn-camera').on('click', function () {
    if (mayQuet) { return; }

    $('#vung-camera').show();
    mayQuet = new Html5Qrcode('khung-camera');

    // Do phan giai cao giup giai ma Code 128 (moi vach can du diem anh), NHUNG mot so may
    // tu choi mo camera khi bi kem rang buoc kich thuoc - da gap that tren dien thoai.
    // Vi vay coi day la mong muon, khong phai dieu kien: hong thi lui ve rang buoc toi
    // thieu von van chay. Mo duoc camera o do phan giai thap con hon khong mo duoc.
    //
    // start() goi u.cancel() tren moi nhanh loi nen trang thai da duoc tra lai, thu lai
    // tren cung mot doi tuong Html5Qrcode la an toan.
    batDauQuet({ facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } })
      .catch(function (loi) {
        console.warn('Khong mo duoc camera kem rang buoc do phan giai, thu lai khong rang buoc:', loi);

        return batDauQuet({ facingMode: 'environment' });
      })
      .catch(function (loi) {
        $('#vung-camera').hide();
        mayQuet = null;
        // Hien nguyen van loi cua thu vien: bao chung chung "Khong mo duoc camera" khien
        // moi nguyen nhan (tu choi quyen, trinh duyet chan vi HTTP, qrbox sai kich thuoc)
        // trong giong het nhau va khong the chan doan tu xa.
        console.error('Khong mo duoc camera:', loi);
        alert('Không mở được camera: ' + (loi && loi.message ? loi.message : loi));
      });
  });

  $('#btn-dong-camera').on('click', dongCamera);

  $('#btn-tra-lai-the').on('click', function () {
    if (!maHienTai) { return; }

    var $b = $(this).prop('disabled', true);
    $('#ket-qua-tra-lai-the').text('');

    $.post('{{ route('khth.tra-cuu-loi-ho-so-tra-lai-the') }}', {
      _token: '{{ csrf_token() }}', treatment_code: maHienTai
    }).done(function (r) {
      // Job chay bat dong bo: KHONG tu nap lai roi hien nhu the da co ket qua moi.
      $('#ket-qua-tra-lai-the').css('color', '#00a65a').text(r.message);
    }).fail(function (x) {
      var t = (x.responseJSON && x.responseJSON.message) || 'Không gửi được yêu cầu';
      $('#ket-qua-tra-lai-the').css('color', '#dd4b39').text(t);
    }).always(function () {
      $b.prop('disabled', false);
    });
  });
});
</script>
@stop
