const { h1, h2, h3, p, bullet, note, table, steps, errors } = require('./lib');

module.exports = function part5() {
  return [
    h1('PHẦN V. TRA CỨU LỖI HỒ SƠ THEO MÃ ĐIỀU TRỊ'),

    h2('5.1. Chức năng này dùng để làm gì'),
    p('Cùng một đợt điều trị có thể bị ghi nhận lỗi ở ba nơi khác nhau của phần mềm: sai sót y lệnh (Phần II), lỗi tra thẻ bảo hiểm y tế (Phần III) và lỗi hồ sơ XML 3176 do cổng giám định trả về (Phần I). Trước đây, muốn biết một hồ sơ có vấn đề gì thì phải mở lần lượt ba màn hình và tự lọc theo mã điều trị ở từng nơi.'),
    p('Màn hình Tra cứu lỗi hồ sơ gộp cả ba nguồn vào một lần tra: nhập hoặc quét mã điều trị, hệ thống hiển thị thông tin hành chính của hồ sơ kèm toàn bộ lỗi đang có, chia thành ba bảng.'),
    p('Đây là màn hình tra cứu nhanh, phục vụ trả lời câu hỏi "hồ sơ này có lỗi gì" ngay tại khoa phòng. Việc theo dõi lỗi theo khoảng ngày, theo khoa, theo mức độ vẫn thực hiện trên ba màn hình chuyên trách đã mô tả ở các phần trước.'),
    note('Lưu ý:', 'Màn hình chỉ đọc và hiển thị lại những lỗi đã được các bộ phận khác của phần mềm ghi nhận trước đó. Mở màn hình này không kích hoạt một lượt kiểm tra mới, vì vậy hồ sơ vừa phát sinh y lệnh có thể chưa kịp xuất hiện lỗi.'),

    h2('5.2. Mở màn hình và quyền truy cập'),
    p('Menu Tra cứu lỗi hồ sơ nằm ở cấp ngoài cùng của thanh menu bên trái, không nằm trong nhóm Kiểm tra sai sót y lệnh. Quyền cần có là tra-cuu-loi-ho-so.'),
    p('Quyền này được cấp riêng, tách khỏi quyền order-check, để nhân viên khoa phòng tra cứu được hồ sơ của mình mà không cần mở quyền quản trị toàn bộ danh sách vi phạm. Khi phần mềm được nâng cấp, những tài khoản đang có quyền order-check được cấp sẵn quyền mới này.'),
    table(
      ['Việc muốn làm', 'Quyền cần có'],
      [
        ['Tra cứu hồ sơ, xem ba bảng lỗi, in phiếu lỗi, tra lại thẻ bảo hiểm y tế', 'tra-cuu-loi-ho-so'],
        ['Đổi trạng thái một vi phạm y lệnh ngay trên màn hình này', 'tra-cuu-loi-ho-so và order-check'],
      ],
      [6400, 2620],
    ),
    p('Tài khoản chỉ có quyền tra-cuu-loi-ho-so sẽ không nhìn thấy cột Xử lý ở bảng sai sót y lệnh. Đây là chủ ý, không phải lỗi hiển thị.'),

    h2('5.3. Nhập hoặc quét mã điều trị'),
    p('Ô nhập nằm ngay đầu màn hình và tự động được đặt con trỏ khi mở trang. Có hai cách đưa mã điều trị vào ô này.'),
    h3('5.3.1. Gõ tay'),
    steps([
      ['1', 'Gõ mã điều trị vào ô Mã điều trị.', 'Mã hiện trong ô.'],
      ['2', 'Nhấn phím Enter hoặc bấm nút Tra cứu.', 'Kết quả hiện bên dưới sau khoảng một giây.'],
    ]),
    h3('5.3.2. Quét bằng máy quét mã vạch cầm tay'),
    p('Mã điều trị được phần mềm in ra dưới dạng mã vạch trên phiếu, nên có thể dùng máy quét mã vạch cầm tay thông thường. Máy quét hoạt động như một bàn phím: nó gõ chuỗi mã rồi tự gửi phím Enter, do đó không cần cài đặt gì thêm và không cần thao tác nào khác.'),
    steps([
      ['1', 'Bấm chuột vào ô Mã điều trị (nếu vừa mở trang thì con trỏ đã sẵn ở đó).', 'Con trỏ nhấp nháy trong ô.'],
      ['2', 'Quét mã vạch trên phiếu.', 'Mã tự điền vào ô và kết quả tự hiện ra, không cần bấm nút.'],
      ['3', 'Quét tiếp phiếu thứ hai.', 'Mã cũ tự bị thay bằng mã mới. Không cần xoá ô giữa hai lượt quét.'],
    ]),
    note('Lưu ý:', 'Phần mềm không quét mã bằng camera của điện thoại hay máy tính. Chức năng đó đã được thử nghiệm và gỡ bỏ vì camera của thiết bị thông thường cho ảnh quá thấp so với mức cần thiết để đọc mã vạch. Hãy dùng máy quét cầm tay hoặc gõ tay.'),
    p('Nếu một lượt tra cứu bị lỗi (mất kết nối, hết phiên đăng nhập), toàn bộ kết quả cũ trên màn hình sẽ được ẩn đi và chỉ hiện dòng báo lỗi màu đỏ dưới ô nhập. Đây là chủ ý: khi quét liên tiếp nhiều phiếu, người dùng phải không bao giờ nhìn thấy kết quả của hồ sơ trước mà tưởng là hồ sơ vừa quét.'),

    h2('5.4. Khối Thông tin hồ sơ'),
    p('Khối đầu tiên hiện các thông tin hành chính lấy trực tiếp từ phần mềm HIS tại thời điểm tra cứu.'),
    table(
      ['Trường', 'Ý nghĩa'],
      [
        ['Mã điều trị', 'Mã liên kết của đợt điều trị, chính là mã vừa tra.'],
        ['Họ tên, Ngày sinh, Giới tính', 'Thông tin hành chính của người bệnh.'],
        ['Mã thẻ BHYT', 'Số thẻ bảo hiểm y tế ghi trên hồ sơ.'],
        ['Nơi ĐKBĐ', 'Mã cơ sở đăng ký khám chữa bệnh ban đầu ghi trên thẻ của người bệnh.'],
        ['Hạn thẻ từ / đến', 'Khoảng thời gian thẻ có giá trị sử dụng.'],
        ['Khoa', 'Khoa hiện tại của hồ sơ.'],
        ['Loại điều trị', 'Nội trú, ngoại trú, điều trị ban ngày…'],
        ['Vào lúc / Ra lúc', 'Thời điểm vào viện và ra viện. Hồ sơ đang điều trị thì Ra lúc để trống.'],
        ['Cơ sở KCB', 'Mã cơ sở khám chữa bệnh nơi người bệnh đang điều trị.'],
      ],
      [2400, 6620],
    ),
    note('Lưu ý:', 'Nơi ĐKBĐ và Cơ sở KCB là hai khái niệm khác nhau và thường có giá trị khác nhau. Nơi ĐKBĐ là cơ sở ghi trên thẻ của người bệnh; Cơ sở KCB là nơi đang điều trị. Khi đối chiếu với cổng Bảo hiểm xã hội, dùng nhầm hai trường này sẽ dẫn tới kết quả sai.'),
    h3('5.4.1. Khi khối thông tin hồ sơ không hiện đủ'),
    table(
      ['Dòng chữ hiện ra', 'Ý nghĩa'],
      [
        ['Không tìm thấy hồ sơ với mã này trên HIS', 'Mã điều trị không có trên HIS. Có thể gõ sai mã, hoặc hồ sơ thuộc cơ sở khác. Ba bảng lỗi bên dưới vẫn hiển thị nếu phần mềm còn lưu lỗi của mã này.'],
        ['Không lấy được thông tin từ HIS', 'Không kết nối được tới cơ sở dữ liệu HIS. Ba bảng lỗi vẫn hiển thị bình thường vì chúng lấy từ nguồn khác. Báo bộ phận công nghệ thông tin.'],
        ['Một vài ô để trống dấu gạch ngang', 'Hồ sơ trên HIS thiếu chính thông tin đó. Màn hình cố ý vẫn hiện hồ sơ thay vì báo không tìm thấy, vì hồ sơ khuyết dữ liệu thường chính là hồ sơ cần xem.'],
      ],
      [2900, 6120],
    ),

    h2('5.5. Ba bảng lỗi'),
    p('Dưới khối thông tin hồ sơ là ba bảng, mỗi bảng một nguồn lỗi. Con số trên tiêu đề mỗi bảng là số dòng lỗi đang có. Hồ sơ hoàn toàn sạch sẽ hiện dải màu xanh "Không phát hiện lỗi trên hồ sơ này".'),
    p('Cả ba bảng đều có ô tìm kiếm riêng, bấm được vào tiêu đề cột để sắp xếp, và tự phân trang khi quá mười dòng.'),
    h3('5.5.1. Bảng Sai sót y lệnh'),
    p('Nguồn: chức năng Kiểm tra sai sót y lệnh, mô tả đầy đủ ở Phần II.'),
    table(
      ['Cột', 'Ý nghĩa'],
      [
        ['Mức độ', 'Nghiêm trọng, Cảnh báo hoặc Thông tin. Dòng nghiêm trọng được gắn nhãn đỏ.'],
        ['Luật', 'Mã quy tắc kiểm tra đã phát hiện ra vi phạm. Tra ý nghĩa từng mã ở mục 2.5.'],
        ['Nội dung', 'Diễn giải vi phạm.'],
        ['Phát hiện lúc', 'Thời điểm bộ quét ghi nhận vi phạm.'],
        ['Trạng thái', 'Mới, Đã xem, Đã xử lý hoặc Bỏ qua.'],
        ['Xử lý', 'Ô chọn để đổi trạng thái. Chỉ hiện với tài khoản có quyền order-check.'],
      ],
      [2000, 7020],
    ),
    p('Bảng này không hiển thị các vi phạm đã được đánh dấu Bỏ qua ở màn Danh sách vi phạm.'),
    h3('5.5.2. Bảng Lỗi tra thẻ BHYT'),
    p('Nguồn: kết quả tra cứu thẻ tự động, mô tả ở mục 3.2. Bảng chỉ hiện dòng khi kết quả tra thẻ có bất thường; thẻ hợp lệ thì bảng để trống.'),
    table(
      ['Cột', 'Ý nghĩa'],
      [
        ['Mã tra cứu', 'Mã kết quả do cổng Bảo hiểm xã hội trả về. Tra ý nghĩa ở mục 3.4.'],
        ['Mã kiểm tra', 'Mã kiểm tra thẻ do cổng trả về. Tra ý nghĩa ở mục 3.5.'],
        ['Kết quả', 'Diễn giải của cổng, ví dụ thẻ hết hạn sử dụng.'],
        ['Ghi chú', 'Thông tin bổ sung do cổng trả về.'],
        ['Mã thẻ', 'Số thẻ đã dùng để tra.'],
        ['Tra lúc', 'Thời điểm tra cứu gần nhất.'],
      ],
      [2000, 7020],
    ),
    h3('5.5.3. Bảng Lỗi XML3176'),
    p('Nguồn: kết quả kiểm tra hồ sơ XML 3176, mô tả ở Phần I.'),
    table(
      ['Cột', 'Ý nghĩa'],
      [
        ['XML', 'Bảng XML chứa lỗi, ví dụ XML1, XML2.'],
        ['STT', 'Số thứ tự dòng trong bảng XML đó.'],
        ['Mã lỗi', 'Mã lỗi XML 3176. Mã của lỗi nghiêm trọng được gắn nhãn đỏ.'],
        ['Tên lỗi', 'Tên lỗi theo danh mục mã lỗi. Để trống nếu mã lỗi chưa có trong danh mục.'],
        ['Mô tả', 'Diễn giải chi tiết.'],
        ['Ngày YL', 'Ngày y lệnh của dòng bị lỗi.'],
      ],
      [2000, 7020],
    ),
    note('Lưu ý:', 'Lỗi XML 3176 chỉ có được sau khi hồ sơ đã được kiểm tra và cổng giám định đã trả kết quả về. Hồ sơ chưa gửi hoặc chưa nhận kết quả thì bảng này trống, điều đó không có nghĩa là hồ sơ không có lỗi XML.'),

    h2('5.6. Đổi trạng thái một vi phạm y lệnh'),
    p('Thao tác này chỉ dành cho tài khoản có thêm quyền order-check và chỉ áp dụng cho bảng Sai sót y lệnh. Hai bảng còn lại không có khái niệm trạng thái.'),
    steps([
      ['1', 'Tại dòng vi phạm cần xử lý, mở ô chọn ở cột Xử lý.', 'Danh sách trạng thái hiện ra.'],
      ['2', 'Chọn Đã xem, Đã xử lý hoặc Bỏ qua.', 'Hệ thống ghi nhận và tự tra cứu lại hồ sơ; cột Trạng thái đổi theo.'],
    ]),
    p('Ý nghĩa của từng trạng thái và nguyên tắc chọn trạng thái nào cho tình huống nào đã mô tả ở mục 2.4; màn hình này chỉ là một lối vào khác cho cùng thao tác đó.'),
    p('Màn hình này đổi trạng thái từng dòng một, không có chọn nhiều dòng cùng lúc. Cần xử lý hàng loạt thì dùng màn Danh sách vi phạm.'),

    h2('5.7. In phiếu lỗi'),
    p('Nút In phiếu lỗi mở một trang riêng, khổ A4 dọc, gồm thông tin hồ sơ và cả ba bảng lỗi, và tự động gọi hộp thoại in của trình duyệt. Trang in dành cho việc kẹp vào bệnh án hoặc gửi khoa phòng.'),
    p('Dòng lỗi nghiêm trọng trên phiếu in được đánh dấu bằng chữ đậm và vạch dọc ở đầu dòng chứ không chỉ bằng màu, để in đen trắng vẫn phân biệt được.'),
    p('Mọi tài khoản xem được màn hình đều in được phiếu.'),

    h2('5.8. Tra lại thẻ BHYT'),
    p('Nút Tra lại thẻ BHYT gửi một yêu cầu tra cứu mới lên cổng Bảo hiểm xã hội cho đúng hồ sơ đang xem, kể cả khi hồ sơ đã có kết quả tra thẻ cũ. Dùng khi nghi ngờ kết quả cũ đã lạc hậu, ví dụ người bệnh vừa gia hạn thẻ.'),
    steps([
      ['1', 'Tra cứu hồ sơ cần kiểm tra lại.', 'Kết quả hiện ra.'],
      ['2', 'Bấm Tra lại thẻ BHYT.', 'Dòng chữ xanh báo đã gửi yêu cầu.'],
      ['3', 'Chờ vài giây rồi bấm Tra cứu lại.', 'Bảng Lỗi tra thẻ BHYT hiện kết quả mới.'],
    ]),
    note('Lưu ý:', 'Yêu cầu được xếp vào hàng đợi nền chứ không chạy ngay. Dòng chữ xanh chỉ có nghĩa là yêu cầu đã được nhận, không có nghĩa là đã có kết quả mới. Phải bấm Tra cứu lại thì mới thấy kết quả.'),
    p('Để tránh gửi dồn dập lên cổng Bảo hiểm xã hội, nút này giới hạn 5 lượt mỗi phút cho mỗi tài khoản. Vượt quá thì các lượt tiếp theo bị từ chối cho đến hết phút đó.'),
    table(
      ['Thông báo', 'Nguyên nhân'],
      [
        ['Hồ sơ không có mã thẻ BHYT', 'Hồ sơ trên HIS không ghi số thẻ. Không có gì để tra.'],
        ['Hồ sơ thiếu giới tính', 'Hồ sơ trên HIS khuyết giới tính. Cổng cần thông tin này để đối chiếu, gửi thiếu sẽ nhận về kết quả sai nên hệ thống chặn lại.'],
        ['Không xác định được cơ sở của hồ sơ', 'Không xác định được cơ sở điều trị, hoặc cơ sở đó chưa được khai báo tài khoản kết nối cổng Bảo hiểm xã hội. Báo bộ phận công nghệ thông tin.'],
      ],
      [3000, 6020],
    ),
    p('Ba trường hợp trên đều cần sửa dữ liệu trên HIS, hoặc cần bộ phận công nghệ thông tin bổ sung cấu hình; bấm lại nút nhiều lần không giải quyết được.'),

    h2('5.9. Xử lý sự cố thường gặp'),
    errors([
      ['Không nhìn thấy menu Tra cứu lỗi hồ sơ', 'Tài khoản chưa được cấp quyền tra-cuu-loi-ho-so.', 'Đề nghị quản trị hệ thống cấp quyền. Sau khi cấp, có thể phải đăng xuất rồi đăng nhập lại.'],
      ['Không tìm thấy hồ sơ với mã này trên HIS', 'Gõ sai mã điều trị, hoặc hồ sơ thuộc cơ sở khác.', 'Kiểm tra lại mã. Nếu ba bảng lỗi bên dưới vẫn có dữ liệu thì mã là đúng, chỉ là hồ sơ không còn trên HIS.'],
      ['Ba bảng lỗi đều trống nhưng biết chắc hồ sơ có lỗi', 'Lỗi chưa được ghi nhận: bộ quét y lệnh chưa chạy tới, hoặc hồ sơ chưa được kiểm XML 3176, hoặc chưa tra thẻ.', 'Chờ bộ quét chạy (khoảng một phút cho y lệnh). Lỗi XML 3176 chỉ có sau khi hồ sơ đã gửi và cổng đã trả kết quả.'],
      ['Không thấy cột Xử lý ở bảng sai sót y lệnh', 'Tài khoản chưa có quyền order-check.', 'Đây là hành vi đúng thiết kế. Cần đổi trạng thái vi phạm thì đề nghị cấp thêm quyền order-check.'],
      ['Không đổi được trạng thái', 'Tài khoản không có quyền order-check, hoặc vi phạm đã bị xoá.', 'Kiểm tra quyền. Nếu đã có quyền mà vẫn lỗi, tra lại hồ sơ để làm mới danh sách.'],
      ['Bấm Tra lại thẻ BHYT nhưng kết quả không đổi', 'Yêu cầu chạy ở hàng đợi nền, chưa xong.', 'Chờ vài giây rồi bấm Tra cứu lại. Nếu sau vài phút vẫn không đổi, báo bộ phận công nghệ thông tin kiểm tra hàng đợi.'],
      ['Không lấy được thông tin từ HIS', 'Mất kết nối tới cơ sở dữ liệu HIS.', 'Báo bộ phận công nghệ thông tin. Trong lúc chờ, ba bảng lỗi vẫn dùng được bình thường.'],
    ]),
  ];
};
