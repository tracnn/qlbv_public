<h6>Dịch vụ chỉ định</h6>
@foreach($cls as $item)
  <div>
    <strong>Nhóm:</strong> {{ $item['nhom'] }} <br>
    <strong>Dịch vụ:</strong> {{ $item['ten'] }} <br>
    <strong>Kết quả:</strong> {{ $item['ket_qua'] }}
    <hr>
  </div>
@endforeach

<h6>Thuốc</h6>
<table class="table table-sm table-bordered">
  <thead>
    <tr>
      <th>Thuốc</th><th>ĐVT</th><th>Cách dùng</th><th>Ghi chú</th>
    </tr>
  </thead>
  <tbody>
    @foreach($thuoc as $t)
      <tr>
        <td>{{ $t['ten'] }}</td>
        <td>{{ $t['dvt'] }}</td>
        <td>{{ $t['cach_dung'] }}</td>
        <td>{{ $t['ghi_chu'] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>