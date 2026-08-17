<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Phiếu lỗi hồ sơ {{ $ma }}</title>
<style>
  @page { size: A4 portrait; margin: 12mm; }
  body { font-family: "Times New Roman", serif; font-size: 13px; color: #000; }
  h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
  h2 { font-size: 14px; margin: 14px 0 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th, td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
  th { background: #eee; }
  .ho-so td { border: none; padding: 2px 4px; }
  .chan { margin-top: 10px; text-align: right; font-style: italic; }
</style>
</head>
<body onload="window.print()">

<h1>PHIẾU LỖI HỒ SƠ</h1>
<p style="text-align:center">Mã điều trị: <strong>{{ $ma }}</strong></p>

<h2>Thông tin hồ sơ</h2>
@if ($hoSo)
<table class="ho-so">
  <tr><td>Họ tên: <strong>{{ $hoSo['patient_name'] }}</strong></td>
      <td>Ngày sinh: {{ $hoSo['patient_dob_text'] }}</td>
      <td>Giới tính: {{ $hoSo['gender_name'] }}</td></tr>
  <tr><td>Mã thẻ BHYT: {{ $hoSo['hein_card_number'] }}</td>
      <td>Nơi ĐKBĐ: {{ $hoSo['hein_medi_org_code'] }}</td>
      <td>Cơ sở KCB: {{ $hoSo['ma_cskcb'] }}</td></tr>
  <tr><td>Khoa: {{ $hoSo['department_name'] }}</td>
      <td>Loại điều trị: {{ $hoSo['treatment_type_name'] }}</td>
      <td>Vào/ra: {{ $hoSo['in_time_text'] }} — {{ $hoSo['out_time_text'] }}</td></tr>
</table>
@else
<p><em>Không tìm thấy hồ sơ với mã này trên HIS.</em></p>
@endif

<h2>Sai sót y lệnh ({{ $summary['order_check'] }})</h2>
@if (count($data['order_check']))
<table>
  <tr><th>Mức độ</th><th>Luật</th><th>Nội dung</th><th>Phát hiện lúc</th><th>Trạng thái</th></tr>
  @foreach ($data['order_check'] as $d)
  <tr><td>{{ $d['severity'] }}</td><td>{{ $d['rule_code'] }}</td><td>{{ $d['message'] }}</td>
      <td>{{ $d['detected_at'] }}</td><td>{{ $d['status'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<h2>Lỗi tra thẻ BHYT ({{ $summary['hein_card'] }})</h2>
@if (count($data['hein_card']))
<table>
  <tr><th>Mã tra cứu</th><th>Mã kiểm tra</th><th>Kết quả</th><th>Ghi chú</th><th>Tra lúc</th></tr>
  @foreach ($data['hein_card'] as $d)
  <tr><td>{{ $d['ma_tracuu'] }}</td><td>{{ $d['ma_kiemtra'] }}</td><td>{{ $d['ma_ketqua'] }}</td>
      <td>{{ $d['ghi_chu'] }}</td><td>{{ $d['checked_at'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<h2>Lỗi XML3176 ({{ $summary['xml3176'] }})</h2>
@if (count($data['xml3176']))
<table>
  <tr><th>XML</th><th>STT</th><th>Mã lỗi</th><th>Tên lỗi</th><th>Mô tả</th></tr>
  @foreach ($data['xml3176'] as $d)
  <tr><td>{{ $d['xml'] }}</td><td>{{ $d['stt'] }}</td><td>{{ $d['error_code'] }}</td>
      <td>{{ $d['error_name'] }}</td><td>{{ $d['description'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<p class="chan">In lúc {{ date('d/m/Y H:i') }}</p>
</body>
</html>
