{{-- THÔNG TIN DVKT --}}
@if(isset($dvktGrouped) && $dvktGrouped->count())
  <h6 class="mt-4">
    <span class="toggle-cls-all text-primary" data-target="cls-wrapper" style="cursor:pointer">[+]</span>
    Thông tin DVKT
  </h6>
  <div id="cls-wrapper" style="display: none; margin-left: 1rem;">
    @foreach($dvktGrouped as $maNhom => $groupedByNhom)
      @php
        $nhomId = 'cls-nhom-' . preg_replace('/[^A-Za-z0-9]/', '_', $maNhom);
        $tenNhom = config('dvkt_nhom')[$maNhom] ?? 'Không rõ nhóm';
      @endphp

      <div class="mb-2">
        <span class="toggle-cls-nhom text-primary" data-nhom="{{ $nhomId }}" style="cursor:pointer">[+]</span>
        Nhóm: {{ $tenNhom }}
      </div>

      <div id="{{ $nhomId }}" class="cls-nhom-table mb-4" style="display: none;">
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="table-light">
              <tr>
                <th>STT</th>
                <th></th>
                <th>Tên dịch vụ</th>
                <th>Đơn vị tính</th>
                <th>Khoa thực hiện</th>
                <th>Ngày chỉ định</th>
                <th>Kết quả (Giá trị/Kết luận)</th>
              </tr>
            </thead>
            <tbody>
              @php $stt = 1; @endphp
              @foreach($groupedByNhom->groupBy('maDichVu') as $maDichVu => $dvktList)
                @php
                  $clsId = 'cls-group-' . preg_replace('/[^A-Za-z0-9]/', '_', $maDichVu);
                  $dvkt = $dvktList[0]; // lấy 1 dòng đại diện cho DVKT
                @endphp
                <tr>
                  <td>{{ $stt++ }}</td>
                  <td>
                    <span class="toggle-cls text-primary" data-dv="{{ $clsId }}" style="cursor:pointer">[+]</span>
                  </td>
                  <td>{{ $dvkt['tenDichVu'] ?? '' }}</td>
                  <td>{{ $dvkt['donViTinh'] ?? '' }}</td>
                  <td>{{ $dvkt['maKhoa'] ?? '' }}</td>
                  <td>{{ isset($dvkt['ngayYl']) ? strtodatetime($dvkt['ngayYl']) : '' }}</td>
                  <td>
                    @if(!empty($dvkt['canLamSang']))
                      <span class="text-info">Có kết quả</span>
                    @else
                      <span class="text-muted">---</span>
                    @endif
                  </td>
                </tr>
                <tr id="{{ $clsId }}" class="cls-group-table" style="display: none;">
                  <td colspan="7" class="bg-light">
                    @if(!empty($dvkt['canLamSang']))
                      <div>
                        <b>Chi tiết cận lâm sàng:</b>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                              <tr>
                                <th>#</th>
                                <th>Mã chỉ số</th>
                                <th>Tên chỉ số</th>
                                <th>Giá trị</th>
                                <th>Đơn vị</th>
                                <th>Kết luận</th>
                                <th>Ngày kết quả</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach($dvkt['canLamSang'] as $index => $cls)
                                @if(!empty($cls['giaTri']) || !empty($cls['ketLuan']))
                                  <tr>
                                    <td>{{ $index+1 }}</td>
                                    <td>{{ $cls['maChiSo'] ?? '' }}</td>
                                    <td>{{ $cls['tenChiSo'] ?? '' }}</td>
                                    <td>{{ $cls['giaTri'] ?? '' }}</td>
                                    <td>{{ $cls['donViDo'] ?? '' }}</td>
                                    <td>{{ $cls['ketLuan'] ?? '-' }}</td>
                                    <td>{{ isset($cls['ngayKq']) ? strtodatetime($cls['ngayKq']) : '' }}</td>
                                  </tr>
                                @endif
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      </div>
                    @else
                      <div class="text-muted">Không có dữ liệu cận lâm sàng.</div>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="text-muted">Không có dữ liệu dịch vụ kỹ thuật.</div>
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
  $(function () {
    // Toggle tất cả nhóm dịch vụ kỹ thuật
    $(document).on('click', '.toggle-cls-all', function () {
      const targetId = $(this).data('target');
      const target = $('#' + targetId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });

    // Toggle từng nhóm dịch vụ kỹ thuật
    $(document).on('click', '.toggle-cls-nhom', function () {
      const nhomId = $(this).data('nhom');
      const target = $('#' + nhomId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });

    // Toggle từng dịch vụ kỹ thuật
    $(document).on('click', '.toggle-cls', function () {
      const maDv = $(this).data('dv');
      const target = $('#' + maDv);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });

    // Toggle thuốc
    $(document).on('click', '.toggle-thuoc', function () {
      const targetId = $(this).data('target');
      const target = $('#' + targetId);
      const btn = $(this);

      target.toggle();
      btn.text(target.is(':visible') ? '[–]' : '[+]');
    });
  });
</script>