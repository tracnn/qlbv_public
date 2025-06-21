{{-- KẾT QUẢ CẬN LÂM SÀNG --}}
@if(isset($clsGrouped) && $clsGrouped->count())
  <h6 class="mt-4">
    <span class="toggle-cls-all text-primary" data-target="cls-wrapper" style="cursor:pointer">[+]</span>
    Kết quả cận lâm sàng
  </h6>
  <div id="cls-wrapper" style="display: none; margin-left: 1rem;">
    @foreach($clsGrouped as $maNhom => $groupedByNhom)
      @php
        $nhomId = 'cls-nhom-' . preg_replace('/[^A-Za-z0-9]/', '_', $maNhom);
        $tenNhom = config('dvkt_nhom')[$maNhom] ?? 'Không rõ nhóm';
      @endphp

      <div class="mb-2">
        <span class="toggle-cls-nhom text-primary" data-nhom="{{ $nhomId }}" style="cursor:pointer">[+]</span>
        Nhóm: {{ $tenNhom }}
      </div>

      <div id="{{ $nhomId }}" class="cls-nhom-table mb-4" style="display: none;">
        @foreach($groupedByNhom->groupBy('maDichVu') as $maDichVu => $ketQuas)
          @php $clsId = 'cls-group-' . preg_replace('/[^A-Za-z0-9]/', '_', $maDichVu); @endphp

          <div class="mb-2 ms-3">
            <span class="toggle-cls text-primary" data-dv="{{ $clsId }}" style="cursor:pointer">[+]</span>
            {{ $ketQuas[0]['tenDichVu'] }} ({{ $maDichVu }})
          </div>

          <div id="{{ $clsId }}" class="cls-group-table mb-4 ms-4" style="display: none;">
            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead class="table-light">
                  <tr>
                    <th>STT</th>
                    <th>Tên chỉ số</th>
                    <th>Mã chỉ số</th>
                    <th>Giá trị</th>
                    <th>Đơn vị</th>
                    <th>Kết luận</th>
                    <th>Ngày kết quả</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ketQuas as $kq)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $kq['tenChiSo'] }}</td>
                      <td>{{ $kq['maChiSo'] }}</td>
                      <td>{{ $kq['giaTri'] }}</td>
                      <td>{{ $kq['donViDo'] }}</td>
                      <td>{{ $kq['ketLuan'] ?? '-' }}</td>
                      <td>{{ strtodatetime($kq['ngayKq']) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach
  </div>
@else
  <p class="text-muted">Không có dữ liệu kết quả cận lâm sàng.</p>
@endif
{{-- THUỐC --}}
@if(count($thuocs))
  <h6 class="mt-4">
    <span class="toggle-thuoc text-primary" data-target="thuoc-table" style="cursor:pointer">[+]</span>
    Thông tin thuốc
  </h6>
  <div id="thuoc-table" class="table-responsive" style="display: none;">
    <table class="table table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>STT</th>
          <th>Tên thuốc</th>
          <th>Hàm lượng</th>
          <th>Đơn vị</th>
          <th>Liều dùng</th>
          <th>Số lượng</th>
          <th>Ngày YL</th>
        </tr>
      </thead>
      <tbody>
        @foreach($thuocs as $index => $thuoc)
          <tr>
            <td>{{ $thuoc['stt'] }}</td>
            <td>{{ $thuoc['tenThuoc'] }}</td>
            <td>{{ $thuoc['hamLuong'] }}</td>
            <td>{{ $thuoc['donViTinh'] }}</td>
            <td>{{ $thuoc['lieuDung'] }}</td>
            <td>{{ $thuoc['soLuong'] }}</td>
            <td>{{ strtodatetime($thuoc['ngayYl'])}}
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <div class="text-muted">Không có dữ liệu thuốc.</div>
@endif
<script>
  $(document).ready(function () {
    $('.toggle-cls-nhom').on('click', function () {
      const nhomId = $(this).data('nhom');
      const target = $('#' + nhomId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });

    $('.toggle-cls').on('click', function () {
      const maDv = $(this).data('dv');
      const target = $('#' + maDv);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });

    $('.toggle-thuoc').on('click', function () {
      const targetId = $(this).data('target');
      const target = $('#' + targetId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });
    $('.toggle-cls-all').on('click', function () {
      const targetId = $(this).data('target');
      const target = $('#' + targetId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });
  });
</script>