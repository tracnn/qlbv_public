<style>
.navbar-header {
    height: 50px; /* Đặt chiều cao cụ thể cho navbar-header nếu cần */
}

.navbar-brand {
    padding: 2px 2px; /* Điều chỉnh padding để logo không bị ép sát vào các cạnh */
    height: 100%; /* Đặt chiều cao cụ thể cho .navbar-brand để logo có không gian hiển thị đầy đủ */
    display: flex; /* Sử dụng flexbox để căn chỉnh logo */
    align-items: center; /* Căn giữa logo theo chiều dọc */
}

.navbar-brand img {
    height: auto; /* Đặt chiều cao tự động để giữ tỷ lệ */
    width: auto; /* Đặt chiều rộng tự động để giữ tỷ lệ */
    max-height: 100%; /* Đảm bảo logo không vượt quá chiều cao của .navbar-brand */
    max-width: 100%; /* Ngăn logo không vượt quá chiều rộng của .navbar-brand */
}
.navbar-brand .hospital-name {
    margin-left: 10px;
    font-size: 14px; /* Điều chỉnh kích thước font chữ cho phù hợp */
    color: #337ab7; /* Màu sắc có thể thay đổi tùy theo sở thích */
    white-space: nowrap; /* Đảm bảo tên không bị wrap nếu không cần thiết */
    overflow: hidden; /* Ngăn chặn text bị tràn nếu quá dài */
    text-overflow: ellipsis; /* Thêm dấu ... nếu text quá dài */
}

.modal-body {
    margin-top: 10px;
}

#qrcodeCanvas {
    margin-top: 20px;
    display: none;
}

#qrcodeImage {
    margin-top: 20px;
    max-width: 100%;
    display: none;
    margin-left: auto;
    margin-right: auto;
    display: block;
}
</style>
<div class="container">
  <!-- Brand and toggle get grouped for better mobile display -->
  <nav class="navbar navbar-default">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#"><img src="{{asset('images/logo.png')}}" alt="Logo" title="Hospital Logi">
        <span class="hospital-name">Bệnh Viện Đa Khoa Nông Nghiệp</span>
      </a>
      
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="#">Trang chủ <span class="sr-only">(current)</span></a></li>
        <li><a href="#">Các dịch vụ</a></li>
        <li><a href="#">Bảng giá</a></li>
        <li><a href="#">Liên hệ</a></li>
        <li><a href="#" data-toggle="modal" data-target="#qrcodeModal">Gen QRCode</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </nav>
</div>

<!-- Modal -->
<div class="modal fade" id="qrcodeModal" tabindex="-1" role="dialog" aria-labelledby="qrcodeModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="qrcodeModalLabel">Gen QRCode</h4>
      </div>
      <div class="modal-body">
        <form id="qrcodeForm">
          <div class="form-group">
            <label for="qrcodeText">Nhập chuỗi:</label>
            <input type="text" class="form-control" id="qrcodeText" required>
          </div>
          <div class="form-group">
            <label for="qrcodeLogo">Upload logo:</label>
            <input type="file" class="form-control" id="qrcodeLogo" accept="image/*" required>
          </div>
          <button type="submit" class="btn btn-primary">Generate QRCode</button>
        </form>
      </div>
      <div class="modal-footer">
        <canvas id="qrcodeCanvas" style="display:none;"></canvas>
        <img id="qrcodeImage" style="max-width: 100%; display:none;">
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
// Hàm để lấy URI đầy đủ bao gồm cả query string
function getFullURI() {
    return window.location.href;
}

function generateQRCode(text, logoURI) {
    var canvas = document.getElementById('qrcodeCanvas');
    var ctx = canvas.getContext('2d');

    // Xóa các giá trị cũ
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('qrcodeImage').style.display = 'none';
    document.getElementById('qrcodeImage').src = '';

    // Tạo QRCode
    var qr = qrcode(0, 'H');
    qr.addData(text);
    qr.make();

    canvas.width = canvas.height = 300; // Kích thước QRCode
    var qrSize = canvas.width;

    // Vẽ QRCode lên canvas
    qr.renderTo2dContext(ctx, qrSize / qr.getModuleCount());

    // Thêm logo vào QRCode
    if (logoURI) {
        var img = new Image();
        img.src = logoURI;
        img.onload = function() {
            var logoSize = qrSize / 5;
            var logoX = (qrSize - logoSize) / 2;
            var logoY = (qrSize - logoSize) / 2;
            ctx.drawImage(img, logoX, logoY, logoSize, logoSize);
            // Hiển thị QRCode
            document.getElementById('qrcodeImage').src = canvas.toDataURL();
            document.getElementById('qrcodeImage').style.display = 'block';
        };
    } else {
        // Hiển thị QRCode không có logo
        document.getElementById('qrcodeImage').src = canvas.toDataURL();
        document.getElementById('qrcodeImage').style.display = 'block';
    }
}

document.getElementById('qrcodeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var text = document.getElementById('qrcodeText').value;
    var logoFile = document.getElementById('qrcodeLogo').files[0];
    
    if (logoFile) {
        // Nếu người dùng upload logo mới, sử dụng logo đó
        var reader = new FileReader();
        reader.onload = function(e) {
            generateQRCode(text, e.target.result);
        };
        reader.readAsDataURL(logoFile);
    } else {
        // Nếu không có logo mới, sử dụng logo mặc định
        generateQRCode(text, '/images/logo.png');
    }
});

// Tự động tạo QRCode dựa trên URI hiện tại và logo mặc định
window.addEventListener('DOMContentLoaded', function () {
    var fullURI = getFullURI();
    
    document.getElementById('qrcodeText').value = fullURI;

    generateQRCode(fullURI, '/images/logo.png');
});

// Xóa các giá trị cũ khi modal bị đóng
document.getElementById('qrcodeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('qrcodeText').value = '';
    document.getElementById('qrcodeLogo').value = '';
    var canvas = document.getElementById('qrcodeCanvas');
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('qrcodeImage').style.display = 'none';
    document.getElementById('qrcodeImage').src = '';
});
</script>