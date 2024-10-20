<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu Giấy Khám Sức Khỏe</title>
</head>
<body>
    @extends('layouts.app')

    @section('content')
    <div class="container">
        <h1>Mẫu Giấy Khám Sức Khỏe Dùng Cho Người Từ Đủ 18 Tuổi Trở Lên</h1>
        <h2>Cộng hòa xã hội chủ nghĩa Việt Nam<br>
        Độc lập - Tự do - Hạnh phúc</h2>
        <p>Số: ……/GKSK-……………</p>

        <h3>Giấy Khám Sức Khỏe</h3>
        <div class="photo-placeholder" style="border: 1px solid #000; width: 150px; height: 200px; text-align: center; line-height: 200px;">
            Ảnh (4 x 6 cm)
        </div>
        <p><strong>1. Họ và tên (viết chữ in hoa):</strong> ..............................................................</p>
        <p><strong>2. Giới tính:</strong> Nam <input type="checkbox"> Nữ <input type="checkbox"></p>
        <p><strong>3. Sinh Ngày tháng năm (Tuổi):</strong> ..................................................</p>
        <p><strong>4. Số CMND/CCCD/Hộ chiếu/định danh CD:</strong> ..........................</p>
        <p><strong>5. Cấp ngày:</strong> ......../....../............ Tại ......................................................</p>
        <p><strong>6. Chỗ ở hiện tại:</strong> ...........................................................................</p>
        <p><strong>7. Lý do khám sức khỏe:</strong> ...................................................................</p>

        <h3>Tiền Sử Bệnh Của Đối Tượng Khám Sức Khỏe</h3>
        <h4>1. Tiền sử gia đình:</h4>
        <p>Có ai trong gia đình ông (bà) mắc một trong các bệnh: truyền nhiễm, tim mạch, đái tháo đường, lao, hen phế quản, ung thư, động kinh, rối loạn tâm thần, bệnh khác:</p>
        <p>a) Không <input type="checkbox"> b) Có <input type="checkbox"> ; Nếu "có" đề nghị ghi cụ thể tên bệnh: .......................................</p>

        <h4>2. Tiền sử bản thân:</h4>
        <p>Ông (bà) đã/đang mắc bệnh tình trạng bệnh nào sau đây không:</p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>TT</th>
                    <th>Tên bệnh tật</th>
                    <th>Có</th>
                    <th>Không</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Có bệnh hay bị thương trong 5 năm qua</td>
                    <td><input type="checkbox"></td>
                    <td><input type="checkbox"></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Có bệnh thần kinh hay bị thương ở đầu</td>
                    <td><input type="checkbox"></td>
                    <td><input type="checkbox"></td>
                </tr>
                <!-- Tiếp tục cho các bệnh tật khác -->
            </tbody>
        </table>

        <h4>3. Câu hỏi khác (nếu có):</h4>
        <p>a) Ông (bà) có đang điều trị bệnh gì không? Nếu có xin hãy liệt kê các thuốc đang dùng và liều lượng:</p>
        <p>.......................................................................................................................</p>
        <p>b) Tiền sử thai sản (Đối với phụ nữ): ...............................................</p>

        <h3>I. Khám Thể Lực</h3>
        <p>- Chiều cao: .................... cm; - Cân nặng: ............ kg; - Chỉ số BMI: ............</p>
        <p>- Mạch: ........... lần/phút; - Huyết áp: .............. / .............. mmHg</p>
        <p>Phân loại thể lực: ...............................................................................................</p>

        <h3>II. Khám Lâm Sàng</h3>
        <p><strong>Nội dung khám</strong></p>
        <p>1. Nội khoa:</p>
        <ul>
            <li>Tuần hoàn - Phân loại: ..............................................................</li>
            <li>Hô hấp - Phân loại: ..............................................................</li>
            <!-- Tiếp tục các phân loại khác -->
        </ul>

        <h3>III. Khám Cận Lâm Sàng</h3>
        <p><strong>Nội dung khám</strong></p>
        <p>1. Xét nghiệm máu:</p>
        <ul>
            <li>Công thức máu - Số lượng HC: ...........................................</li>
            <!-- Tiếp tục các xét nghiệm khác -->
        </ul>

        <h3>IV. KếT LUẬN</h3>
        <p>1. Phân loại sức khỏe: ...............................................................................................</p>
        <p>2. Các bệnh tật (nếu có): .....................................................................................</p>

        <p>....... ngày ...... tháng ...... năm ......<br>
        NGƯỜI KẾT LUẬN (Ký ghi rõ họ tên và đóng dấu)</p>
    </div>
    @endsection
</body>
</html>