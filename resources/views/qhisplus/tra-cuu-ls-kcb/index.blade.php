<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Tra cứu hồ sơ KCB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .detail-row { display: none; }
    .toggle-btn { cursor: pointer; }
  </style>
</head>
<body class="bg-white">

<div class="container py-4">
  <h4 class="mb-4 text-center">Tra cứu hồ sơ khám chữa bệnh</h4>

  <!-- Form nhập mã -->
  <form method="POST" action="{{ route('tra-cuu-ls-kcb') }}" class="mb-4">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="row g-2">
      <div class="col-md-9 col-12">
        <input type="text" name="keyword" class="form-control"
               value="{{ old('keyword', isset($keyword) ? $keyword : '') }}"
               placeholder="Nhập CCCD / Mã BHYT" required>
      </div>
      <div class="col-md-3 col-12 d-grid">
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
      </div>
    </div>
    @if ($errors->has('keyword'))
      <div class="text-danger mt-1">{{ $errors->first('keyword') }}</div>
    @endif
  </form>

  @if(isset($benhNhan))
    <div class="card mb-4">
      <div class="card-header bg-info text-white">Thông tin hành chính</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-2"><strong>Họ tên:</strong> {{ $benhNhan['hoTen'] }}</div>
          <div class="col-md-6 mb-2"><strong>Ngày sinh:</strong> {{ $benhNhan['ngaySinh'] }}</div>
          <div class="col-md-6 mb-2"><strong>Giới tính:</strong> {{ $benhNhan['gioiTinh'] }}</div>
          <div class="col-md-6 mb-2"><strong>Số CCCD:</strong> {{ $benhNhan['soCccd'] }}</div>
          <div class="col-md-6 mb-2"><strong>SĐT:</strong> {{ $benhNhan['sdt'] }}</div>
          <div class="col-md-6 mb-2"><strong>Mã thẻ BHYT:</strong> {{ $benhNhan['maTheBhyt'] }}</div>
          <div class="col-12"><strong>Địa chỉ:</strong> {{ $benhNhan['diaChi'] }}</div>
        </div>
      </div>
    </div>
  @endif

  <!-- Danh sách hồ sơ KCB -->
  @if (isset($hosos) && count($hosos))
    <div class="table-responsive">
      <table class="table table-bordered bg-white">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">Acc</th>
            <th>Ngày vào</th>
            <th>Ngày ra</th>
            <th>Chẩn đoán</th>
          </tr>
        </thead>
        <tbody>
          @foreach($hosos as $hoso)
            <tr>
              <td class="text-center">
                <span class="toggle-btn text-primary" data-id="{{ $hoso['id'] }}">[+]</span>
              </td>
              <td>{{ $hoso['ngayVao'] }}</td>
              <td>{{ $hoso['ngayRa'] }}</td>
              <td>{{ $hoso['chanDoanRv'] }}</td>
            </tr>
            <tr class="detail-row" id="detail-{{ $hoso['id'] }}">
              <td colspan="4">
                <div class="loading text-center text-muted">
					  <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
					    <span class="visually-hidden">Loading...</span>
					  </div>
					  <div class="mt-2">Đang tải dữ liệu chi tiết...</div>
					</div>
                <div class="detail-content mt-2"></div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="mb-4">Không tìm thấy dữ liệu...</div>
  @endif

</div>

<script>
$(document).ready(function () {
  $('.toggle-btn').on('click', function () {
    const btn = $(this);
    const id = btn.data('id');
    const detailRow = $('#detail-' + id);
    const contentDiv = detailRow.find('.detail-content');

    if (detailRow.is(':visible')) {
      detailRow.hide();
      btn.text('[+]');
    } else {
      btn.text('[–]');
      detailRow.show();

      if (contentDiv.is(':empty')) {
        $.ajax({
          url: '/chi-tiet-ho-so/' + id,
          method: 'GET',
          success: function (html) {
            contentDiv.html(html);
            detailRow.find('.loading').hide();
          },
          error: function () {
            contentDiv.html('<div class="text-danger">Lỗi khi tải chi tiết</div>');
          }
        });
      }
    }
  });
});
</script>
</body>
</html>