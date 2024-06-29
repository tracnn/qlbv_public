<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điện thoại</title>
    <style>
      .phone-container {
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .phone-keyboard {
        display: grid;
        grid-template-columns: repeat(3, 0fr);
        gap: 5px;
        margin-top: 10px;
      }

      .key {
        width: 140px;
        height: 140px;
        border: 1px solid #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 5em;
        cursor: pointer;
        transition: background-color 0.3s ease;
      }

      .key:hover {
        background-color: #808080;/* #eee; */
      }

      #display {
        width: 400px;
        height: 70px;
        margin-top: 10px;
        text-align: center;
        font-size: 4.3em;
        border: 1px solid #ccc;
        padding: 5px;
      }

      .display {
        color: blue;
      }

      #display-current {
        width: 99%;
        height: 50px;
        margin-top: 10px;
        text-align: center;
        font-size: 2.5em;
        border: 0px solid #ccc;
        padding: 5px;
      }

      .display-current {
        color: blue;
      }

      #control-buttons {
        display: flex;
        justify-content: space-between;
        width: 370px;
        margin-top: 10px;
        margin-left: auto;
        margin-right: auto;
      }

      .control-button {
        width: 170px;
        height: 100px;
        border: 1px solid #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-left: auto;
        margin-right: auto;
      }

      .control-button:hover {
        background-color: #eee;
      }

      .control-button.confirm {
        background-color: green;
        color: white;
      }

      .control-button.delete {
        background-color: red;
        color: white;
      }

      .control-button.confirm.active,
      .control-button.confirm:hover {
        background-color: darkgreen;
      }

      .control-button.delete.active,
      .control-button.delete:hover {
        background-color: darkred;
      }
      body {
        background-image: url('/images/background.jpg'); /* Đường dẫn đến ảnh nền */
        background-size: cover; /* Đảm bảo ảnh nền hiển thị đầy đủ kích thước trên màn hình */
        background-repeat: no-repeat; /* Ngăn lặp lại ảnh nền */
        background-attachment: fixed; /* Giữ ảnh nền cố định khi cuộn trang */
        /* Thêm các thuộc tính CSS khác tùy thuộc vào thiết kế của bạn */
      }
    </style>
    <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
  </head>
  <body>
    <div id="display-current" class="display-current">NHẬP SỐ ĐIỆN THOẠI (SỐ THỨ TỰ HIỆN TẠI LÀ: {{$currentQueueNumber}})</div>
    <div class="phone-container">
      <div id="display" class="display"></div>
      <div id="control-buttons" class="control-buttons">
        <div class="control-button delete" onclick="clearDisplay()">XÓA</div>
        <div class="control-button confirm" onclick="confirmInput()">ĐỒNG Ý</div>
      </div>
      <div class="phone-keyboard">
        <div class="key" onclick="handleButtonClick(1)">1</div>
        <div class="key" onclick="handleButtonClick(2)">2</div>
        <div class="key" onclick="handleButtonClick(3)">3</div>
        <div class="key" onclick="handleButtonClick(4)">4</div>
        <div class="key" onclick="handleButtonClick(5)">5</div>
        <div class="key" onclick="handleButtonClick(6)">6</div>
        <div class="key" onclick="handleButtonClick(7)">7</div>
        <div class="key" onclick="handleButtonClick(8)">8</div>
        <div class="key" onclick="handleButtonClick(9)">9</div>
        <div class="key" onclick="handleButtonClick('*')">*</div>
        <div class="key" onclick="handleButtonClick(0)">0</div>
        <div class="key" onclick="handleButtonClick('#')">#</div>
      </div>
    </div>
    <script>
      function handleButtonClick(value) {
        updateDisplay(value);
      }

      function updateDisplay(value) {
        var display = document.getElementById("display");
        var currentValue = display.innerHTML;
        // Kiểm tra nếu độ dài của giá trị hiện tại đã đạt đến 10 chữ số
        if (currentValue.length < 10) {
          display.innerHTML = currentValue + value;
        }
      }

      function clearDisplay() {
        document.getElementById("display").innerHTML = "";
        document.querySelector(".control-button.delete").classList.add("hover");
        document.querySelector(".control-button.confirm").classList.remove("hover");
      }

      function confirmInput() {
        var inputValue = document.getElementById("display").innerHTML;
        var displayCurrent = document.getElementById("display-current");
        const urlParams = new URLSearchParams(window.location.search);
        const maKhoa = urlParams.get('maKhoa');

        if (!maKhoa) {
          toastr.error('Mã khoa phòng không được để trống', 'Lỗi!!!').css("font-size", "30px");
          return;
        }

        if (!inputValue) {
          toastr.error('Bạn chưa nhập số điện thoại di động', 'Lỗi!!!').css("font-size", "30px");
          return;
        }

        if (isVietnameseMobileNumber(inputValue)) {
          document.querySelector(".control-button.delete").classList.add("hover");
          document.querySelector(".control-button.confirm").classList.remove("hover");
          $.ajax({
            url: "{{route('queue.register')}}",
            type: "POST",
            data: {
              _token: "{{csrf_token()}}",
              phoneNumber: inputValue,
              maKhoa: maKhoa,
            },
          })
          .done(function(data) {
            switch(data.maKetQua) {
              case "1":
                toastr.success(data.noiDung, 'Thành công!!!').css("font-size", "30px");
                displayCurrent.innerHTML = 'NHẬP SỐ ĐIỆN THOẠI (SỐ THỨ TỰ HIỆN TẠI LÀ: ' + data.soHienTai +')';
                document.getElementById("display").innerHTML = "";
                break;
              case "2":
                toastr.warning(data.noiDung, 'Cảnh báo!!!').css("font-size", "30px");
                break;
              default:
                toastr.error(data.noiDung, 'Lỗi!!!').css("font-size", "30px");
            }
          })
        } else {
          //alert('Đây không phải là số di động Việt Nam');
          toastr.error('Đây không phải là số điện thoại di động', 'Lỗi!!!').css("font-size", "30px");
        }
        document.querySelector(".control-button.confirm").classList.add("hover");
        document.querySelector(".control-button.delete").classList.remove("hover");
      }

      function isVietnameseMobileNumber(number) {
        // Biểu thức chính quy cho số di động Việt Nam (điều chỉnh theo định dạng hiện tại)
        const regex = /^(03[2-9]|07[0-9]|08[1-9]|09[0-4,6-9]|05[6-9]|06[2-9])+([0-9]{7})$/;
        return regex.test(number);
      }
    </script>
    <script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
  </body>
</html>