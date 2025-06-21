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
<body class="bg-light">

<div class="container py-4">
  <h3 class="mb-4 text-center">Tra cứu hồ sơ khám chữa bệnh</h3>

  <!-- Form nhập mã -->
  <form method="POST" action="{{ route('tra-cuu-ls-kcb') }}" class="mb-4">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="row g-2">
      <div class="col-md-9 col-12">
        <input type="text" name="keyword" class="form-control"
               value="{{ old('keyword', isset($keyword) ? $keyword : '') }}"
               placeholder="Nhập CCCD / Mã BHYT / Mã BHXH" required>
      </div>
      <div class="col-md-3 col-12 d-grid">
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
      </div>
    </div>
  </form>

  <!-- Danh sách hồ sơ KCB -->
  @if (isset($hosos))
    <div class="table-responsive">
      <table class="table table-bordered bg-white">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;"></th>
            <th>Ngày khám</th>
            <th>Khoa phòng</th>
            <th>Bác sĩ</th>
            <th>Chẩn đoán sơ bộ</th>
          </tr>
        </thead>
        <tbody>
          @foreach($hosos as $hoso)
            <tr>
              <td class="text-center">
                <span class="toggle-btn text-primary" data-id="{{ $hoso['id'] }}">[+]</span>
              </td>
              <td>{{ $hoso['ngay_kham'] }}</td>
              <td>{{ $hoso['khoa_phong'] }}</td>
              <td>{{ $hoso['bac_si'] }}</td>
              <td>{{ $hoso['chan_doan'] }}</td>
            </tr>
            <tr class="detail-row" id="detail-{{ $hoso['id'] }}">
              <td colspan="5">
                <div class="loading text-muted">Đang tải dữ liệu chi tiết...</div>
                <div class="detail-content mt-2"></div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
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