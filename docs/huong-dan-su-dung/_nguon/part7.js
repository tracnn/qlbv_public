const { h1, h2, h3, p, bullet, note, forIt, table, steps } = require('./lib');

module.exports = function part7() {
  return [
    h1('PHẦN VII. DANH MỤC THEO THÔNG TƯ 12/2026'),

    h2('7.1. Chức năng này dùng để làm gì'),
    p('Theo Thông tư 12/2026/BTC, cơ sở khám chữa bệnh phải gửi lên cổng Bảo hiểm xã hội sáu bộ danh mục mô tả năng lực của mình: bộ phận chuyên môn, nhân lực, thuốc, thiết bị y tế, dịch vụ kỹ thuật và thiết bị thực hiện dịch vụ kỹ thuật. Đây là các danh mục cơ sở tự khai, khác với các bộ danh mục do Bảo hiểm xã hội phát hành ở Phần IV.'),
    p('Chức năng Danh mục TT12 nhận sáu bộ danh mục đó dưới dạng tệp Excel, kiểm tra dữ liệu, ký số rồi gửi lên cổng Bảo hiểm xã hội. Sau khi cổng tiếp nhận, dữ liệu được ghi sang các bộ danh mục trong phần mềm và trở thành căn cứ đối chiếu cho việc kiểm hồ sơ XML 3176 ở Phần I.'),
    p('Điểm khác biệt so với Phần IV: danh mục ở Phần IV là bản do Bảo hiểm xã hội ban hành, nhập vào để đối chiếu. Danh mục ở Phần VII là bản do chính cơ sở khai báo, phải gửi lên cổng và được cổng chấp nhận thì mới có hiệu lực.'),
    note('Lưu ý:', 'Chỉ khi cổng Bảo hiểm xã hội trả về mã tiếp nhận thành công thì dữ liệu mới được ghi sang bộ danh mục trong phần mềm. Điều này là cố ý: danh mục dùng để kiểm hồ sơ XML 3176 phải đúng bằng bản mà cơ quan bảo hiểm đã nhận, vì khi giám định họ sẽ so với chính bản đó.'),

    h2('7.2. Mở màn hình và quyền truy cập'),
    p('Nhóm menu Danh mục TT12 nằm trong menu Hồ sơ XML, ngang hàng với Xml 3176 và Chứng từ điện tử. Quyền cần có là xml-man, cùng quyền với hai nhóm kia.'),
    table(
      ['Mục menu', 'Dùng để làm gì', 'Quyền cần có'],
      [
        ['Danh sách hồ sơ', 'Tra cứu, xem chi tiết, ký và gửi, xuất Excel.', 'xml-man'],
        ['Nạp danh mục', 'Tải biểu mẫu Excel và nạp tệp đã điền vào phần mềm.', 'xml-man'],
        ['Nút Xoá hồ sơ', 'Xoá hẳn một hồ sơ danh mục khỏi phần mềm.', 'superadministrator'],
        ['Nút Kiểm lại và Đồng bộ lại danh mục', 'Hai đường cứu hộ khi một bước bị hỏng giữa chừng; xem mục 7.9.', 'superadministrator'],
      ],
      [2600, 4620, 1800],
    ),

    h2('7.3. Sáu mẫu danh mục'),
    p('Mỗi mẫu là một tệp Excel riêng và được gửi tới một đầu mối tiếp nhận riêng của cổng. Không gộp nhiều mẫu vào một tệp.'),
    table(
      ['Mẫu', 'Nội dung khai báo', 'Ghi sang bộ danh mục'],
      [
        ['Mẫu 01/DM', 'Bộ phận chuyên môn khám chữa bệnh bảo hiểm y tế: khoa phòng, số bàn khám, số giường theo từng loại.', 'Danh mục Khoa phòng'],
        ['Mẫu 02/DM', 'Nhân lực thực hiện khám chữa bệnh bảo hiểm y tế: người hành nghề, chứng chỉ, phạm vi hoạt động.', 'Danh mục Nhân viên y tế'],
        ['Mẫu 03/DM', 'Thuốc, máu, chế phẩm máu.', 'Danh mục Thuốc'],
        ['Mẫu 04/DM', 'Thiết bị y tế, tức vật tư y tế.', 'Danh mục Vật tư y tế'],
        ['Mẫu 05/DM', 'Dịch vụ khám bệnh, chữa bệnh, kèm bảng con thuốc và vật tư đi theo dịch vụ.', 'Danh mục Dịch vụ kỹ thuật'],
        ['Mẫu 06/DM', 'Thiết bị y tế thực hiện dịch vụ kỹ thuật.', 'Danh mục Thiết bị'],
      ],
      [1400, 5220, 2400],
    ),

    h2('7.4. Nạp danh mục vào phần mềm'),
    p('Khác với chứng từ điện tử ở Phần VI, chức năng này chỉ có một đường đưa dữ liệu vào: màn hình Nạp danh mục. Không có tiến trình nền quét thư mục.'),

    h3('7.4.1. Tải biểu mẫu Excel'),
    p('Trước khi điền dữ liệu, hãy tải biểu mẫu do phần mềm sinh ra thay vì tự tạo tệp mới. Biểu mẫu có sẵn dòng tiêu đề đúng tên cột và đúng thứ tự mà phần mềm nhận diện.'),
    steps([
      ['1', 'Mở Hồ sơ XML → Danh mục TT12 → Nạp danh mục.', 'Màn hình hiện ô chọn Mẫu và ô chọn Cơ sở khám chữa bệnh.'],
      ['2', 'Chọn Mẫu cần khai.', 'Nút Tải biểu mẫu bên dưới sẵn sàng.'],
      ['3', 'Bấm Tải biểu mẫu.', 'Trình duyệt tải về một tệp Excel rỗng có sẵn dòng tiêu đề của mẫu đã chọn.'],
    ]),
    note('Lưu ý:', 'Đừng đổi tên cột, đừng chèn thêm cột và đừng chèn dòng trống phía trên dòng tiêu đề. Phần mềm nhận diện mẫu bằng chính dòng tiêu đề; sai một tên cột thì tệp bị từ chối ngay khi nạp với thông báo không nhận diện được mẫu.'),

    h3('7.4.2. Các bước nạp tệp đã điền'),
    steps([
      ['1', 'Mở Hồ sơ XML → Danh mục TT12 → Nạp danh mục.', 'Màn hình hiện khung kéo thả.'],
      ['2', 'Chọn Cơ sở khám chữa bệnh. Đây là ô bắt buộc.', 'Mã cơ sở này quyết định tài khoản dùng để gửi lên cổng.'],
      ['3', 'Kéo tệp Excel vào khung, hoặc bấm vào khung để chọn tệp.', 'Mỗi tệp trở thành một hồ sơ riêng. Có thể kéo nhiều tệp một lượt.'],
      ['4', 'Chờ bảng kết quả nạp hiện lên.', 'Mỗi dòng cho biết tệp nạp Thành công hay Thất bại, kèm mã hồ sơ và số dòng đọc được.'],
      ['5', 'Mở Danh sách hồ sơ để xem kết quả kiểm lỗi.', 'Việc kiểm lỗi chạy ngay sau khi nạp, ở hàng đợi nền.'],
    ]),

    h3('7.4.3. Ô chọn Cơ sở KCB và quy tắc khớp mã'),
    p('Mỗi mẫu Excel đều có cột MA_CSKCB ở từng dòng dữ liệu. Trước khi nạp, phần mềm đối chiếu toàn bộ giá trị trong cột đó với mã cơ sở đã chọn ở ô phía trên. Chỉ cần một dòng lệch là cả tệp bị từ chối, không nạp một phần.'),
    p('Quy tắc này chặt vì một lý do cụ thể: mã cơ sở quyết định tài khoản lấy phiên làm việc để gửi lên cổng. Nếu mã trong dữ liệu và mã dùng để gửi thuộc hai cơ sở khác nhau thì cổng vẫn nhận, và danh mục bị ghi sang nhầm đơn vị — hỏng lặng lẽ, không lộ ra cho tới lúc đối chiếu.'),
    note('Lưu ý:', 'Danh sách trong ô Cơ sở khám chữa bệnh được lấy từ phần mềm quản lý bệnh viện và được nhớ tạm trong khoảng một giờ. Nếu vừa có thay đổi về cơ sở mà ô chọn chưa cập nhật, hãy báo bộ phận công nghệ thông tin xoá bộ nhớ tạm.'),

    h2('7.5. Màn hình Danh sách hồ sơ'),

    h3('7.5.1. Bộ lọc'),
    p('Chọn khoảng thời gian rồi bấm Tải dữ liệu. Bảng chỉ nạp sau khi bấm nút này, nên lần đầu mở màn hình bảng sẽ trống.'),
    table(
      ['Ô lọc', 'Ý nghĩa'],
      [
        ['Khoảng thời gian', 'Lọc theo thời điểm nạp hồ sơ vào phần mềm.'],
        ['Mẫu', 'Lọc theo một trong sáu mẫu danh mục.'],
        ['Cơ sở KCB', 'Lọc theo mã cơ sở khám chữa bệnh của hồ sơ.'],
        ['Người nạp', 'Lọc theo tài khoản đã nạp hồ sơ.'],
        ['Tìm', 'Tìm theo mã hồ sơ hoặc tên tệp. Ô này nhận phím Enter.'],
      ],
      [2400, 6620],
    ),

    h3('7.5.2. Các cột của bảng'),
    table(
      ['Cột', 'Ý nghĩa'],
      [
        ['Mã hồ sơ', 'Mã do phần mềm sinh, gồm tên mẫu, mã cơ sở, ngày nạp và số thứ tự trong ngày. Bấm vào để mở chi tiết.'],
        ['Mẫu', 'Một trong sáu mẫu ở mục 7.3.'],
        ['Tên tệp', 'Tên tệp Excel gốc đã nạp.'],
        ['Mã CSKCB', 'Mã cơ sở khám chữa bệnh của hồ sơ.'],
        ['Số dòng', 'Số dòng dữ liệu đọc được từ tệp.'],
        ['Số lỗi', 'Số lỗi ở mức chặn gửi. Khi hồ sơ chưa được kiểm, cột này hiển thị Chưa kiểm chứ không phải số 0.'],
        ['Lỗi nạp', 'Có nghĩa là tệp đọc dở dang. Hồ sơ loại này được giữ lại có chủ đích để người dùng nhìn thấy và xoá đi, không sửa được trên màn hình.'],
        ['Đã kiểm', 'Bộ kiểm lỗi đã chạy xong hay chưa.'],
        ['Đã ký', 'Hồ sơ đã được ký số hay chưa.'],
        ['Mã giao dịch', 'Mã do cổng Bảo hiểm xã hội cấp khi tiếp nhận. Đây là dấu vết đối soát duy nhất, hãy giữ lại khi cần làm việc với cơ quan bảo hiểm.'],
        ['Mã kết quả', 'Mã cổng trả về ở lần gửi gần nhất; xem bảng ở mục 7.7.3.'],
        ['Thời gian tiếp nhận', 'Thời điểm cổng ghi nhận hồ sơ.'],
        ['Đã đồng bộ', 'Dữ liệu đã được ghi sang bộ danh mục trong phần mềm hay chưa; xem mục 7.8.'],
        ['Nạp lúc', 'Thời điểm nạp tệp vào phần mềm.'],
      ],
      [2200, 6820],
    ),

    h2('7.6. Xem chi tiết một hồ sơ'),
    p('Bấm vào mã hồ sơ hoặc nút Chi tiết để mở cửa sổ chi tiết ngay trên màn danh sách, không rời khỏi trang. Giữ phím Ctrl khi bấm nếu muốn mở hồ sơ ra một thẻ trình duyệt mới để so sánh nhiều hồ sơ cùng lúc.'),

    h3('7.6.1. Khối thông tin đầu'),
    p('Khối trên cùng tóm tắt hồ sơ: mẫu, mã cơ sở, tên tệp, số dòng, số lỗi, tình trạng ký, mã giao dịch, mã kết quả và thời điểm tiếp nhận. Nếu hồ sơ gặp sự cố, phần mềm hiện thêm các dải thông báo tương ứng: Lỗi nạp tệp, Lỗi ký số và Lỗi gửi.'),

    h3('7.6.2. Bốn thẻ nội dung'),
    table(
      ['Thẻ', 'Nội dung'],
      [
        ['Dòng dữ liệu', 'Toàn bộ dòng đọc được từ tệp Excel, đúng thứ tự cột của mẫu. Riêng Mẫu 05 có thêm bảng con thuốc và vật tư đi theo từng dịch vụ.'],
        ['Lỗi', 'Danh sách lỗi phát hiện khi kiểm, kèm số thứ tự dòng và tên cột để đối chiếu ngược về tệp Excel.'],
        ['XML đã ký', 'Nội dung tệp XML sau khi ký số. Thẻ này trống cho tới khi hồ sơ được ký.'],
        ['Nhật ký gửi', 'Mỗi lần gửi một dòng: thời điểm, mã kết quả, mã giao dịch và thông điệp nguyên văn của cổng.'],
      ],
      [2200, 6820],
    ),

    h2('7.7. Ký số và gửi lên cổng'),

    h3('7.7.1. Một lần bấm chạy trọn cả hai bước'),
    p('Bấm Ký và gửi một lần là phần mềm xếp cả chuỗi: ký số trước, ký xong tự động gửi lên cổng Bảo hiểm xã hội. Không phải bấm lần thứ hai.'),
    p('Hai bước vẫn chạy ở hàng đợi nền và ở hai hàng đợi riêng, nên kết quả xuất hiện dần: cột Đã ký chuyển trạng thái trước, một lát sau mới có Mã giao dịch và Mã kết quả. Bấm xong không thấy gì đổi ngay là bình thường, hãy bấm Tải dữ liệu lại sau vài giây.'),
    note('Lưu ý:', 'Cổng Bảo hiểm xã hội nhận là nhận thật, không có đường rút lại. Vì một lần bấm nay đi thẳng tới cổng, hãy đối chiếu nội dung ở thẻ Dòng dữ liệu và xác nhận cột Số lỗi bằng 0 TRƯỚC khi bấm.'),
    p('Hồ sơ không đủ điều kiện sẽ bị từ chối kèm lý do, thường gặp là chưa được kiểm, còn lỗi ở mức chặn, hoặc đã được cổng tiếp nhận rồi.'),
    p('Bấm lại trong khi lượt trước còn đang chạy thì phần mềm báo hồ sơ đang được xử lý ở một lượt khác và không làm gì thêm. Đây là chốt chống gửi trùng: một hồ sơ chỉ có một lượt ký và gửi tại một thời điểm.'),
    p('Hồ sơ đã ký rồi mà bấm lại thì vẫn đi qua bước ký một lần nữa trước khi gửi. Điều này vô hại về nội dung, vì mã định danh của bộ danh mục được sinh một lần lúc nạp và giữ nguyên, nên tệp XML dựng lại đúng y bản cũ.'),

    h3('7.7.2. Ký và gửi nhiều hồ sơ cùng lúc'),
    p('Trên màn Danh sách hồ sơ, tích chọn ở cột đầu tiên rồi bấm Ký và gửi đã chọn. Số hồ sơ đang chọn hiển thị ngay trên nút.'),
    p('Mỗi lượt gửi tối đa 50 hồ sơ. Chọn quá số này thì phần mềm từ chối cả lượt chứ không gửi 50 hồ sơ đầu rồi bỏ phần còn lại, để tránh tình huống người dùng tưởng đã gửi hết.'),
    p('Sau khi chạy xong, một bảng kết quả hiện ngay dưới thanh nút: số hồ sơ đã được xếp vào hàng đợi ký và gửi, kèm danh sách hồ sơ bị bỏ qua và lý do từng cái. Hãy đọc kỹ danh sách bỏ qua này, vì nó chính là những hồ sơ chưa đi.'),

    h3('7.7.3. Mã kết quả cổng Bảo hiểm xã hội trả về'),
    table(
      ['Mã', 'Ý nghĩa', 'Cần làm gì'],
      [
        ['200', 'Tiếp nhận thành công.', 'Không cần làm gì. Phần mềm tự ghi dữ liệu sang bộ danh mục; xem mục 7.8.'],
        ['123, 124, 125, 202, 204, 205', 'Lỗi nội dung tệp XML.', 'Đối chiếu lại dữ liệu trong tệp Excel, sửa rồi nạp lại thành hồ sơ mới.'],
        ['401, 402, 403', 'Mã cơ sở chưa đúng, tài khoản hoặc phiên làm việc hết hạn, hoặc không có quyền.', 'Báo bộ phận công nghệ thông tin kiểm tra tài khoản cổng của cơ sở đó.'],
        ['500', 'Lỗi hệ thống phía cổng.', 'Chờ rồi gửi lại. Phần mềm cũng tự thử lại tối đa ba lần.'],
      ],
      [900, 4300, 3820],
    ),

    h2('7.8. Đồng bộ sang bộ danh mục'),
    p('Ngay sau khi cổng trả về mã tiếp nhận thành công, phần mềm ghi toàn bộ dòng của hồ sơ sang bộ danh mục tương ứng ở cột cuối bảng mục 7.3. Cột Đã đồng bộ trên màn danh sách chuyển sang đã đồng bộ.'),
    p('Từ thời điểm đó, dữ liệu vừa gửi trở thành căn cứ đối chiếu cho việc kiểm hồ sơ XML 3176 ở Phần I. Ô Excel để trống được ghi thành giá trị rỗng chứ không phải số 0, vì với cơ quan giám định thì không khai và khai bằng 0 là hai chuyện khác nhau.'),
    p('Danh mục cũ không bị xoá đi. Khi một dòng thay đổi, Thông tư 12 yêu cầu gửi hai dòng: dòng cũ mang ngày kết thúc hiệu lực và dòng mới để trống ngày kết thúc. Cả hai cùng tồn tại trong bộ danh mục.'),

    h2('7.9. Hai nút cứu hộ'),
    p('Hai nút dưới đây chỉ hiện ra khi hồ sơ rơi vào đúng tình huống cần đến chúng, và chỉ tài khoản superadministrator mới dùng được.'),
    table(
      ['Nút', 'Hiện khi nào', 'Làm gì'],
      [
        ['Kiểm lại', 'Hồ sơ vẫn ở trạng thái chưa kiểm dù đã nạp xong.', 'Chạy lại bộ kiểm lỗi. Thao tác này không ghi gì ra ngoài, chạy lại vô hại.'],
        ['Đồng bộ lại danh mục', 'Cổng đã tiếp nhận nhưng cột Đã đồng bộ vẫn trống.', 'Ghi lại toàn bộ dòng của hồ sơ sang bộ danh mục. KHÔNG gửi lại lên cổng.'],
      ],
      [2200, 3200, 3620],
    ),
    p('Tình huống dẫn tới nút thứ hai: phần mềm ghi nhận kết quả tiếp nhận trước rồi mới ghi sang danh mục. Nếu bước ghi danh mục hỏng giữa chừng, hồ sơ sẽ mang mã tiếp nhận thành công mà cột Đã đồng bộ vẫn trống, và các lần thử lại tự động sẽ dừng sớm vì thấy hồ sơ đã được tiếp nhận. Nút này là đường thoát cho đúng trạng thái đó.'),

    h2('7.10. Xuất dữ liệu ra Excel'),
    table(
      ['Nút', 'Nội dung tệp'],
      [
        ['Xuất danh sách', 'Toàn bộ hồ sơ đang hiển thị theo bộ lọc, mỗi hồ sơ một dòng.'],
        ['Xuất lỗi', 'Chi tiết từng lỗi của các hồ sơ đang hiển thị, để gửi cho khoa phòng đối chiếu.'],
        ['Xuất nhật ký gửi', 'Lịch sử các lần gửi trong khoảng thời gian đang chọn, kèm mã kết quả và thông điệp của cổng.'],
      ],
      [2400, 6620],
    ),
    p('Ba tệp xuất đều áp đúng bộ lọc đang hiển thị trên màn hình, nên tệp tải về khớp với bảng đang xem.'),

    h2('7.11. Xử lý sự cố thường gặp'),
    table(
      ['Hiện tượng', 'Nguyên nhân thường gặp', 'Cách xử lý'],
      [
        ['Nạp tệp báo không nhận diện được mẫu', 'Dòng tiêu đề bị sửa tên cột, chèn thêm cột, hoặc có dòng trống phía trên tiêu đề.', 'Tải lại biểu mẫu ở mục 7.4.1 và chép dữ liệu sang.'],
        ['Nạp tệp báo lệch mã cơ sở', 'Cột MA_CSKCB trong tệp có ít nhất một dòng khác mã cơ sở đã chọn ở ô phía trên.', 'Sửa cột MA_CSKCB trong tệp cho thống nhất, hoặc chọn lại đúng cơ sở rồi nạp lại.'],
        ['Hồ sơ nằm mãi ở Chưa kiểm', 'Tiến trình nền xử lý hàng đợi đã dừng.', 'Báo bộ phận công nghệ thông tin. Sau khi tiến trình chạy lại, dùng nút Kiểm lại ở mục 7.9.'],
        ['Bấm Ký và gửi nhưng chưa thấy Mã giao dịch', 'Hai bước chạy ở hàng đợi nền nên kết quả xuất hiện dần.', 'Chờ vài giây rồi bấm Tải dữ liệu. Nếu cột Đã ký có mà Mã kết quả vẫn trống thì xem dòng tiếp theo.'],
        ['Hồ sơ đã ký nhưng không bao giờ được gửi', 'Thiếu dịch vụ chạy hàng đợi gửi trên máy chủ.', 'Báo bộ phận công nghệ thông tin: cần cả ba dịch vụ hàng đợi, xem Phụ lục B.'],
        ['Báo "Hồ sơ đang được xử lý ở một lượt khác"', 'Lượt ký và gửi trước còn đang chạy, hoặc vừa bị gián đoạn.', 'Chờ rồi thử lại. Chốt này tự mở sau một khoảng; nếu kẹt lâu thì báo bộ phận công nghệ thông tin.'],
        ['Dải Lỗi ký số màu đỏ trong màn chi tiết', 'Không kết nối được thiết bị ký số, hoặc chứng thư hết hạn.', 'Báo bộ phận công nghệ thông tin kèm nguyên văn thông điệp trên dải đỏ.'],
        ['Cổng đã tiếp nhận nhưng Đã đồng bộ vẫn trống', 'Bước ghi sang danh mục hỏng giữa chừng.', 'Dùng nút Đồng bộ lại danh mục ở mục 7.9. Không bấm gửi lại.'],
        ['Ô chọn Cơ sở KCB thiếu cơ sở vừa thêm', 'Danh sách cơ sở được nhớ tạm khoảng một giờ.', 'Báo bộ phận công nghệ thông tin xoá bộ nhớ tạm.'],
      ],
      [2600, 3400, 3020],
    ),
    h2('7.12. Dashboard độ phủ danh mục'),
    p('Mở Hồ sơ XML → Danh mục TT12 → Dashboard danh mục. Màn hình trả lời một câu hỏi mà màn Danh sách hồ sơ không trả lời được: trong sáu mẫu của từng cơ sở, cái nào đã được cổng Bảo hiểm xã hội tiếp nhận và cái nào chưa được cổng tiếp nhận.'),
    p('Lưới có sáu hàng là sáu mẫu và mỗi cột là một cơ sở khám chữa bệnh. Ô ghi Đã tiếp nhận kèm ngày và số dòng của lần gửi gần nhất; ô ghi Chưa gửi nghĩa là chưa có hồ sơ nào của mẫu đó được cổng chấp nhận. Ô Chưa gửi gộp chung cả hồ sơ chưa nộp lần nào lẫn hồ sơ đã nộp nhưng bị cổng từ chối, không phân biệt hai trường hợp đó; muốn biết là trường hợp nào thì xem khối Hồ sơ đang dở dang bên dưới. Bấm vào ô nào sẽ mở màn Danh sách hồ sơ đã lọc sẵn theo mẫu và cơ sở tương ứng.'),
    p('Số dòng hiển thị là của riêng lần gửi gần nhất, không cộng dồn qua các lần gửi. Lần gửi sau thay thế lần trước chứ không thêm vào, nên cộng dồn sẽ ra một con số không có ý nghĩa.'),
    p('Khối Hồ sơ đang dở dang bên dưới đếm những hồ sơ đã nạp nhưng chưa được tiếp nhận, tách theo từng trạng thái. Cần đọc cả khối này: lưới chỉ nói về việc đã xong, nên một hồ sơ đang kẹt ở Gửi lỗi sẽ không hiện ra ở lưới.'),
    note('Lưu ý:', 'Màn hình này không có bộ lọc thời gian, và đó là cố ý. Câu hỏi "đã được cổng tiếp nhận hay chưa" là câu hỏi trên toàn bộ thời gian; giới hạn theo khoảng ngày sẽ làm một mẫu gửi từ lâu hiện thành chưa gửi. Cũng vì Thông tư 12 không quy định chu kỳ gửi cố định — danh mục chỉ gửi khi có thay đổi — nên màn hình không đánh dấu "quá hạn" cho bất kỳ ô nào.'),
    forIt('Chức năng này cần khai hai tệp cấu hình nằm ngoài kho mã: khối tt12 trong config/organization.php (bật tắt ký, bật tắt gửi, và BA tên hàng đợi) và đĩa exportTt12 trong config/filesystems.php (nơi ghi tệp XML đã ký). Ngoài ra cần BA dịch vụ chạy hàng đợi: kiểm, ký và gửi. Thiếu đĩa thì phần mềm báo lỗi đúng lúc người dùng bấm nút ký; thiếu dịch vụ kiểm thì hồ sơ nằm im ở Chưa kiểm; thiếu dịch vụ gửi thì hồ sơ ký xong không bao giờ đi. Cả ba đều hỏng lặng lẽ, không có thông báo nào trên màn hình.'),
  ];
};
