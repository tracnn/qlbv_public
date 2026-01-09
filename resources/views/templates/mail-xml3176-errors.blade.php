<!DOCTYPE HTML>
<html lang="vi">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 100%;
      max-width: 600px;
      margin: 0px auto;
      background-color: #ffffff;
      border-radius: 0px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    .header {
      background-color: #007bff;
      color: #ffffff;
      padding: 0px;
      text-align: center;
    }
    .content {
      padding: 0px;
    }
    .content p {
      margin: 0 0 0px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 0px 0;
    }
    table, th, td {
      border: 1px solid #ddd;
    }
    th, td {
      padding: 1px;
      text-align: left;
    }
    th {
      background-color: #f2f2f2;
      text-align: center;
    }
    .footer {
      background-color: #f8f8f8;
      padding: 0px;
      text-align: center;
      font-size: 12px;
      color: #666;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Hệ Thống Hỗ Trợ Giám Định BHYT</h1>
    </div>
    
    <div class="content">
      <h2>Xin chào, {{$name}}</h2>
      <h3>I. Lỗi thẻ.</h3>
      <!-- Hiển thị lỗi errorsHeinCard nếu có -->
      @if(count($errorsHeinCard) > 0)
      <table>
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã LK</th>
            <th>Mã Thẻ (HISPro)</th>
            <th>Mã Lỗi</th>
            <th>Ghi Chú</th>
          </tr>
        </thead>
        <tbody>
          @foreach($errorsHeinCard as $index => $error)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $error->ma_lk }}</td>
            <td>{{ optional($error->his_treatment)->tdl_hein_card_number }}</td>
            <td>
              @if($error->ma_tracuu != '000')
                {{ config('__tech.insurance_error_code')[$error->ma_tracuu] }}
              @elseif($error->ma_kiemtra != '00')
                {{ config('__tech.check_insurance_code')[$error->ma_kiemtra] }}
              @endif
            </td>
            <td>{{ $error->ghi_chu }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @else
      <p>Không có lỗi nào được tìm thấy.</p>
      @endif

      <h3>II. Lỗi XML.</h3>
      @if($errorsXml->isNotEmpty())
        @foreach($errorsXml->sortBy('xml')->groupBy('xml') as $xml => $errorsByXml)
          <h4>Lỗi: {{ $xml }}</h4>
          @foreach($errorsByXml->sortBy('error_code')->groupBy('error_code') as $errorCode => $errorsByErrorCode)
            <h4>{{ $loop->iteration }} - {{ $errorsByErrorCode->first()->Xml3176XmlErrorCatalog->error_name }}</h4>
            <table>
              <thead>
                <tr>
                  <th>STT</th>
                  <th>Mã LK</th>
                  <th>Ngày YL</th>
                  <th>Mô tả lỗi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($errorsByErrorCode as $index => $error)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $error->ma_lk }}</td>
                  <td>{{ $error->ngay_yl }}</td>
                  <td>{{ $error->description ?? 'Không có mô tả' }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          @endforeach
        @endforeach
      @else
        <p>Không có lỗi XML nào được tìm thấy.</p>
      @endif
    </div>

    <div class="footer">
      <p>&copy; 2024 Hệ Thống Hỗ Trợ Giám Định BHYT</p>
    </div>
  </div>

</body>
</html>