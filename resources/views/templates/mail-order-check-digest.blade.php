<h2>Cảnh báo sai sót y lệnh</h2>
<p>Tổng hợp lúc {{ $generatedAt }} — <b>{{ $total }}</b> vi phạm mới
  (Nghiêm trọng: <b style="color:#dd4b39">{{ $critical }}</b>,
   Cảnh báo: <b style="color:#f39c12">{{ $warning }}</b>,
   Thông tin: {{ $info }}).</p>

<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px">
  <thead>
    <tr style="background:#f4f4f4">
      <th>Thời điểm</th><th>Mức độ</th><th>Mã ĐT</th><th>Bệnh nhân</th>
      <th>Bác sĩ</th><th>Khoa (ID)</th><th>Nội dung</th>
    </tr>
  </thead>
  <tbody>
    @foreach($violations as $v)
    <tr>
      <td>{{ $v->detected_at }}</td>
      <td>{{ $v->severity }}</td>
      <td>{{ $v->treatment_code }}</td>
      <td>{{ $v->patient_name }} ({{ $v->patient_code }})</td>
      <td>{{ $v->doctor_username ?: $v->doctor_loginname }}</td>
      <td>{{ $v->department_id }}</td>
      <td>{{ $v->message }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<p style="color:#888;font-size:12px">Email tự động từ hệ thống Kiểm tra sai sót y lệnh. Vui lòng đăng nhập phần mềm (KHTH → Kiểm tra sai sót y lệnh) để xử lý.</p>
