const { h1, h2, h3, p, bullet, note, forIt, table, steps, errors } = require('./lib');

module.exports = function part6() {
  return [
    h1('PHẦN VI. CHỨNG TỪ ĐIỆN TỬ (PHỤ LỤC 02)'),

    h2('6.1. Chức năng này dùng để làm gì'),
    p('Ngoài hồ sơ XML 3176 dùng cho việc giám định chi phí khám chữa bệnh (Phần I), cơ sở y tế còn phải liên thông lên cổng Bảo hiểm xã hội một nhóm giấy tờ khác: giấy chứng sinh, giấy báo tử, giấy chứng nhận nghỉ việc hưởng bảo hiểm xã hội, giấy ra viện và các chứng từ theo Thông tư 25/2025. Nhóm giấy tờ này gọi chung là chứng từ điện tử theo Phụ lục 02, viết tắt trong tài liệu là hồ sơ chứng từ.'),
    p('Chức năng Chứng từ điện tử nhận các tệp XML do phần mềm nghiệp vụ sinh ra, kiểm tra dữ liệu trước khi gửi, ký số rồi gửi lên cổng Bảo hiểm xã hội, và theo dõi kết quả cổng trả về.'),
    p('Điểm khác biệt quan trọng nhất so với XML 3176: chứng từ điện tử theo Phụ lục 02 không mang mã giao dịch do phần mềm tự sinh ra khi gửi. Nghĩa là nếu cùng một hồ sơ được gửi hai lần, cổng Bảo hiểm xã hội không có căn cứ để nhận ra đó là bản trùng và sẽ ghi nhận thành hai chứng từ. Vì lẽ đó, toàn bộ màn hình và các bước tự động trong phần này đều được thiết kế theo hướng thà không gửi còn hơn gửi trùng.'),
    note('Lưu ý:', 'Cổng Bảo hiểm xã hội nhận là nhận thật, không có đường rút lại. Một chứng từ đã được cổng tiếp nhận thì việc sửa sai phải làm theo quy trình nghiệp vụ với cơ quan bảo hiểm, không phải bằng thao tác trên phần mềm. Hãy đối chiếu kỹ trước khi bấm nút gửi.'),

    h2('6.2. Mở màn hình và quyền truy cập'),
    p('Nhóm menu Chứng từ điện tử nằm trong menu Hồ sơ XML, ngang hàng với Xml 3176. Quyền cần có là xml-man — cùng quyền với XML 3176, vì đây là cùng một nhóm người dùng và cùng nghiệp vụ liên thông với Bảo hiểm xã hội.'),
    table(
      ['Mục menu', 'Dùng để làm gì', 'Quyền cần có'],
      [
        ['Danh sách hồ sơ', 'Tra cứu, xem chi tiết, ký và gửi, xuất Excel.', 'xml-man'],
        ['Nạp hồ sơ', 'Kéo thả tệp XML để đưa hồ sơ vào phần mềm.', 'xml-man'],
        ['Dashboard chứng từ', 'Theo dõi sức khoẻ vận hành, sản lượng và chất lượng dữ liệu.', 'xml-man'],
        ['Nút Xóa hồ sơ (trong màn chi tiết)', 'Xoá hẳn một hồ sơ khỏi phần mềm.', 'superadministrator'],
      ],
      [2400, 4820, 1800],
    ),
    p('Ba dịch vụ liên thông đang được hỗ trợ, tương ứng ba đầu mối tiếp nhận khác nhau của cổng Bảo hiểm xã hội:'),
    table(
      ['Dịch vụ', 'Nội dung gửi'],
      [
        ['Chứng từ TT25/2025', 'Các chứng từ theo Thông tư 25/2025: giấy chứng nhận nghỉ việc hưởng bảo hiểm xã hội, giấy ra viện, giấy chuyển tuyến và các mẫu liên quan.'],
        ['Giấy chứng sinh', 'Giấy chứng sinh của trẻ sơ sinh.'],
        ['Giấy báo tử', 'Giấy báo tử.'],
      ],
      [2400, 6620],
    ),

    h2('6.3. Nạp hồ sơ vào phần mềm'),
    p('Có hai đường đưa hồ sơ vào phần mềm. Người dùng nghiệp vụ dùng đường thứ nhất; đường thứ hai do bộ phận công nghệ thông tin cài đặt và chạy ngầm, mô tả ở mục 6.12.'),

    h3('6.3.1. Kéo thả tệp trên màn Nạp hồ sơ'),
    steps([
      ['1', 'Mở Hồ sơ XML → Chứng từ điện tử → Nạp hồ sơ.', 'Màn hình hiện khung kéo thả.'],
      ['2', 'Nếu gói tệp không mang mã cơ sở khám chữa bệnh, chọn Cơ sở KCB ở ô phía trên.', 'Mã đã chọn sẽ được ghi vào hồ sơ thay cho giá trị mặc định của đơn vị.'],
      ['3', 'Kéo một hoặc nhiều tệp XML thả vào khung, hoặc bấm vào khung để chọn tệp.', 'Tệp bắt đầu được tải lên.'],
      ['4', 'Chờ bảng Kết quả nạp hiện ra bên dưới.', 'Mỗi tệp một dòng, kèm số hồ sơ vào được và số hồ sơ hỏng.'],
    ]),
    p('Bảng Kết quả nạp có bốn cột: Tệp, Hồ sơ vào được, Hồ sơ hỏng, Ghi chú. Cột Ghi chú nêu lý do của những hồ sơ không vào được, ví dụ tệp không đúng cấu trúc hoặc thiếu thẻ gốc.'),
    p('Chỉ nhận tệp có phần mở rộng .xml, mỗi tệp tối đa 100 MB. Gói nhiều hồ sơ trong một tệp là bình thường; phần mềm tự tách ra thành từng hồ sơ riêng.'),
    note('Lưu ý:', 'Nạp lại một hồ sơ đã có sẽ ghi đè bản cũ và xoá mã giao dịch, mã kết quả của lần gửi trước. Sau khi ghi đè, hồ sơ quay về trạng thái chưa ký và có thể gửi lại được. Nếu hồ sơ đó đã từng được cổng tiếp nhận, phần mềm sẽ hỏi xác nhận trước khi cho gửi lần nữa — đọc kỹ hộp thoại đó thay vì bấm đồng ý theo phản xạ.'),

    h3('6.3.2. Ô chọn Cơ sở KCB khi nạp'),
    p('Phần lớn gói tệp đã mang sẵn mã cơ sở khám chữa bệnh bên trong, khi đó để trống ô này. Riêng gói giấy chứng sinh không mang mã cơ sở, nên cần chọn thủ công ở đây nếu đơn vị phục vụ nhiều cơ sở.'),
    p('Để trống ô này thì phần mềm lấy mã cơ sở theo thứ tự: mã ghi trong chính tệp, nếu không có thì lấy cấu hình mặc định của đơn vị.'),

    h2('6.4. Màn hình Danh sách hồ sơ'),
    p('Đây là màn hình làm việc chính. Mở bằng Hồ sơ XML → Chứng từ điện tử → Danh sách hồ sơ.'),

    h3('6.4.1. Bộ lọc'),
    p('Bộ lọc nằm ở khối trên cùng, dùng chung khuôn với màn XML 3176 nên cách vận hành giống hệt: chọn điều kiện rồi bấm nút Tải dữ liệu, danh sách mới hiện ra. Danh sách không tự tải lại khi đổi ô lọc.'),
    p('Riêng ô Tìm nhận phím Enter: gõ xong nhấn Enter là danh sách tải lại ngay, không phải di chuột sang nút Tải dữ liệu.'),
    table(
      ['Ô lọc', 'Ý nghĩa'],
      [
        ['Từ ngày — Đến ngày', 'Lọc theo thời điểm nạp hồ sơ vào phần mềm, không phải ngày phát sinh chứng từ.'],
        ['Dịch vụ', 'Một trong ba dịch vụ liên thông ở mục 6.2.'],
        ['Loại chứng từ', 'Loại giấy tờ cụ thể trong dịch vụ, ví dụ giấy chứng sinh, giấy điều trị nội trú.'],
        ['Cơ sở KCB', 'Mã cơ sở khám chữa bệnh của hồ sơ. Mặc định là tất cả cơ sở.'],
        ['Người nạp', 'Tài khoản đã nạp hồ sơ. Hồ sơ do tiến trình tự động nạp thì ô này trống.'],
        ['Trạng thái gửi', 'Một trong chín trạng thái ở mục 6.5.'],
        ['Chỉ hồ sơ còn lỗi', 'Chọn "Có" để chỉ hiện hồ sơ đang có lỗi chặn gửi.'],
        ['Tìm mã hồ sơ / mã thẻ / họ tên / số CCCD / mã BHXH', 'Gõ một trong năm thông tin rồi nhấn phím Enter. Tìm được cả một phần, ví dụ gõ bốn số cuối của căn cước.'],
      ],
      [2600, 6420],
    ),
    note('Lưu ý:', 'Ba nút xuất Excel dùng đúng bộ lọc của lần Tải dữ liệu gần nhất, không phải bộ lọc đang hiện trên màn hình. Đổi ô lọc mà chưa bấm Tải dữ liệu thì tệp xuất ra vẫn theo bộ lọc cũ — đúng bằng những gì đang hiển thị. Đây là chủ ý, để tệp xuất luôn khớp với bảng người dùng đang nhìn.'),

    h3('6.4.2. Các cột của bảng'),
    table(
      ['Cột', 'Ý nghĩa'],
      [
        ['Mã hồ sơ', 'Mã định danh hồ sơ chứng từ trong phần mềm. Bấm vào để mở chi tiết.'],
        ['Dịch vụ', 'Đầu mối tiếp nhận của cổng Bảo hiểm xã hội.'],
        ['Mã CSKCB', 'Mã cơ sở khám chữa bệnh của hồ sơ.'],
        ['Họ tên', 'Họ tên trên chứng từ đầu tiên của hồ sơ.'],
        ['Mã thẻ', 'Số thẻ bảo hiểm y tế ghi trên chứng từ.'],
        ['Số CCCD', 'Số căn cước công dân. Với giấy báo tử là số giấy tờ tuỳ thân, có thể là hộ chiếu. Với giấy chứng sinh là căn cước của người mẹ.'],
        ['Mã BHXH', 'Mã số bảo hiểm xã hội. Với giấy chứng sinh là mã của người mẹ.'],
        ['Số CT', 'Số chứng từ chứa trong hồ sơ. Một hồ sơ có thể gồm nhiều chứng từ.'],
        ['Số lỗi', 'Số lỗi mức chặn gửi. Xem giải thích quan trọng ở ngay dưới bảng này.'],
        ['Ký số', 'Đã ký hoặc chưa ký.'],
        ['Trạng thái gửi', 'Một trong chín trạng thái ở mục 6.5.'],
        ['MaGD', 'Mã giao dịch do cổng Bảo hiểm xã hội cấp khi tiếp nhận thành công.'],
        ['Thời điểm tiếp nhận', 'Thời điểm cổng ghi nhận, do cổng trả về.'],
        ['Nạp lúc', 'Thời điểm hồ sơ được đưa vào phần mềm.'],
      ],
      [2200, 6820],
    ),
    note('Lưu ý:', 'Cột Số lỗi bằng 0 KHÔNG có nghĩa là hồ sơ đã sạch. Nó chỉ có nghĩa đó khi cột Trạng thái gửi không phải là "Chưa kiểm". Hồ sơ vừa nạp xong chưa được máy kiểm thì đương nhiên chưa ghi nhận lỗi nào, và con số 0 lúc đó có nghĩa là chưa ai nhìn. Luôn đọc hai cột này cùng nhau.'),
    p('Cột Số lỗi chỉ đếm lỗi mức chặn gửi. Số trên nhãn tab Lỗi ở màn chi tiết đếm cả lỗi cảnh báo nên thường lớn hơn; hai con số lệch nhau là bình thường, không phải sai sót hiển thị.'),

    h2('6.5. Chín trạng thái gửi'),
    p('Cột Trạng thái gửi là chỗ cần đọc trước tiên khi một hồ sơ chưa lên được cổng. Có năm lý do khác nhau khiến một hồ sơ chưa được gửi, và mỗi lý do cần một người khác nhau xử lý. Gộp cả năm thành một chữ "chưa gửi" sẽ dẫn tới việc ngồi chờ một hồ sơ vĩnh viễn không bao giờ được gửi.'),
    table(
      ['Trạng thái', 'Nghĩa là gì', 'Cần làm gì'],
      [
        ['Chưa kiểm', 'Máy chưa kiểm tra dữ liệu của hồ sơ này.', 'Chờ vài phút. Nếu cả loạt hồ sơ đứng ở trạng thái này, báo bộ phận công nghệ thông tin kiểm tra tiến trình nền.'],
        ['Còn lỗi chặn', 'Đã kiểm và phát hiện lỗi mức chặn gửi.', 'Mở chi tiết, xem tab Lỗi, sửa dữ liệu ở phần mềm sinh ra XML rồi nạp lại.'],
        ['Ký số thất bại', 'Đã thử ký nhưng không ký được.', 'Xem dòng "Lỗi ký số" trong màn chi tiết. Thường do thiết bị ký số chưa cắm hoặc dịch vụ ký không phản hồi.'],
        ['Chưa ký số', 'Hồ sơ đủ điều kiện nhưng chưa được ký.', 'Mở chi tiết và bấm Ký và gửi.'],
        ['Chức năng gửi đang tắt', 'Cấu hình chặn mọi lượt gửi lên cổng.', 'Báo quản trị hệ thống bật cấu hình gửi.'],
        ['Chờ gửi', 'Đã ký, đủ điều kiện, đang chờ đến lượt gửi.', 'Chờ. Nếu chờ quá lâu, xem khối Ba hàng đợi trên Dashboard.'],
        ['Đã gửi', 'Cổng đã tiếp nhận thành công, đã có mã giao dịch.', 'Không cần làm gì.'],
        ['Cổng từ chối', 'Cổng đã trả lời nhưng từ chối tiếp nhận.', 'Mở chi tiết, đọc mã kết quả và phản hồi của cổng. Tra ý nghĩa mã ở mục 6.7.3.'],
        ['Gửi thất bại', 'Không gửi được, thường do lỗi mạng hoặc cổng không phản hồi.', 'Mở chi tiết đọc dòng "Lỗi gửi". Có thể bấm gửi lại sau khi đường truyền ổn định.'],
      ],
      [2000, 3510, 3510],
    ),
    p('Thứ tự hiển thị ưu tiên lý do gần nhất cần xử lý. Một hồ sơ vừa còn lỗi vừa chưa ký sẽ hiện "Còn lỗi chặn", vì việc phải làm trước là sửa lỗi.'),

    h2('6.6. Xem chi tiết một hồ sơ'),
    p('Bấm vào mã hồ sơ ở cột đầu tiên. Chi tiết mở ra dạng hộp thoại ngay trên màn danh sách, không chuyển sang trang khác. Đóng hộp thoại bằng dấu × ở góc trên bên phải hoặc phím Esc; danh sách phía sau giữ nguyên trang và bộ lọc đang xem.'),

    h3('6.6.1. Khối thông tin đầu'),
    p('Khối trên cùng gồm: Dịch vụ, Mã CSKCB, Số chứng từ, Lỗi chặn gửi, Nạp lúc, Ký số, MaGD, Mã kết quả, Tiếp nhận.'),
    p('Ô Lỗi chặn gửi hiện chữ "Chưa kiểm" thay vì con số khi máy chưa kiểm hồ sơ — đây chính là chỗ tránh hiểu nhầm đã nêu ở mục 6.4.2.'),
    p('Bên dưới có thể xuất hiện thêm các khối sau, tuỳ tình trạng hồ sơ:'),
    bullet('Lịch sử gửi — liệt kê các lượt gửi trước đó của hồ sơ này.'),
    bullet('Lỗi ký số (nền đỏ) — nguyên văn thông báo từ dịch vụ ký số.'),
    bullet('Lỗi gửi (nền vàng) — nguyên văn lỗi của lượt gửi gần nhất.'),
    bullet('Phản hồi của cổng — nguyên văn nội dung cổng Bảo hiểm xã hội trả về, kể cả khi gửi thành công.'),
    p('Khi cả hai khối lỗi cùng xuất hiện, đọc khối Lỗi ký số trước: hồ sơ chưa ký được thì lỗi gửi chỉ là hệ quả.'),

    h3('6.6.2. Các thẻ nội dung'),
    p('Phía dưới là các thẻ (tab). Số hồ sơ trong mỗi loại chứng từ hiện thành con số nhỏ bên cạnh tên thẻ khi có nhiều hơn một.'),
    table(
      ['Thẻ', 'Nội dung'],
      [
        ['Các thẻ đầu (theo loại chứng từ)', 'Nội dung chứng từ đã được diễn giải thành bảng dễ đọc, mỗi loại giấy tờ một thẻ riêng.'],
        ['Lỗi', 'Danh sách lỗi của hồ sơ: mã lỗi, trường dữ liệu bị lỗi, mức độ và mô tả. Thẻ này luôn hiện, kể cả khi không có lỗi, để người dùng xác nhận được là hồ sơ sạch chứ không phải màn hình chưa tải xong.'],
        ['XML gốc', 'Nội dung XML nguyên văn, dùng khi cần đối chiếu với phần mềm sinh ra tệp.'],
      ],
      [2800, 6220],
    ),

    h2('6.7. Ký số và gửi lên cổng'),

    h3('6.7.1. Thao tác'),
    steps([
      ['1', 'Mở chi tiết hồ sơ cần gửi.', 'Hộp thoại chi tiết hiện ra.'],
      ['2', 'Kiểm tra thẻ Lỗi không còn lỗi mức chặn, và các thẻ nội dung đúng với chứng từ giấy.', 'Xác nhận hồ sơ sạch.'],
      ['3', 'Bấm nút Ký và gửi ở góc dưới bên phải khối thông tin.', 'Nút chuyển sang trạng thái đang xử lý và không bấm lại được.'],
      ['4', 'Chờ thông báo, rồi đóng hộp thoại và bấm Tải dữ liệu để xem trạng thái mới.', 'Cột Trạng thái gửi đổi theo kết quả.'],
    ]),
    p('Với hồ sơ đã có mã giao dịch, nút mang tên Ký và gửi lại.'),
    note('Lưu ý:', 'Việc ký và gửi được xếp vào hàng đợi nền chứ không chạy ngay khi bấm nút. Thông báo hiện ra chỉ có nghĩa là yêu cầu đã được nhận, chưa có nghĩa là cổng đã tiếp nhận. Phải tải lại danh sách mới thấy kết quả thật. Trong lúc chờ, tuyệt đối không bấm nút lần nữa — mỗi lần bấm là một chứng từ có thể được ghi nhận thêm trên cổng.'),

    h3('6.7.2. Khi nút từ chối thực hiện'),
    p('Nút kiểm tra điều kiện trước khi xếp hàng. Các thông báo có thể gặp:'),
    table(
      ['Thông báo', 'Nguyên nhân và cách xử lý'],
      [
        ['Chức năng gửi đang tắt trong cấu hình. Liên hệ quản trị để bật.', 'Cấu hình gửi đang tắt. Đây là trạng thái mặc định khi phần mềm mới được cài đặt. Báo quản trị hệ thống.'],
        ['Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi. Thử lại sau ít phút.', 'Máy chưa kiểm xong hồ sơ. Chờ rồi thử lại. Nếu kéo dài, xem khối Ba hàng đợi trên Dashboard.'],
        ['Hồ sơ còn lỗi chặn gửi. Xem tab Lỗi, sửa ở phần mềm sinh XML rồi nạp lại.', 'Phải sửa dữ liệu tại nguồn rồi nạp lại hồ sơ. Không có cách bỏ qua lỗi chặn.'],
        ['Chức năng ký số đang tắt trong cấu hình, và hồ sơ này chưa ký.', 'Báo quản trị hệ thống bật chức năng ký. Hồ sơ đã ký từ trước vẫn gửi lại được bình thường.'],
        ['Hồ sơ này đã từng được gửi lên cổng BHXH…', 'Hộp thoại xác nhận, không phải lỗi. Xem mục 6.7.4.'],
      ],
      [3400, 5620],
    ),

    h3('6.7.3. Mã kết quả cổng Bảo hiểm xã hội trả về'),
    table(
      ['Mã', 'Ý nghĩa', 'Cách xử lý'],
      [
        ['200', 'Thành công.', 'Hồ sơ đã được tiếp nhận, có mã giao dịch. Không cần làm gì.'],
        ['205', 'Nội dung tệp gửi lên không hợp lệ.', 'Báo bộ phận công nghệ thông tin; thường do khâu ký số hoặc đóng gói.'],
        ['401', 'Lỗi xác thực tài khoản.', 'Tài khoản kết nối cổng sai hoặc đã hết hiệu lực. Báo bộ phận công nghệ thông tin.'],
        ['500', 'Lỗi phía máy chủ của cổng.', 'Không phải lỗi của đơn vị. Chờ rồi gửi lại sau.'],
        ['1001', 'Tệp gửi lên quá lớn.', 'Báo bộ phận công nghệ thông tin để tách gói nhỏ hơn.'],
      ],
      [900, 3560, 4560],
    ),

    h3('6.7.4. Hộp thoại xác nhận gửi lại'),
    p('Khi một hồ sơ được nạp đè, mã giao dịch của lần gửi trước bị xoá theo — vì nội dung đã đổi thì kết quả cũ nói về một bản khác. Dấu vết duy nhất còn lại nằm ở khối Lịch sử gửi trong màn chi tiết.'),
    p('Nếu bấm gửi một hồ sơ như vậy, phần mềm dừng lại và hỏi xác nhận. Đây không phải lỗi: gửi lại sau khi sửa nội dung là việc hợp lệ. Mục đích của hộp thoại chỉ là để người bấm nhìn thấy rằng mình đang gửi lại một hồ sơ mà cổng có thể đã tiếp nhận trước đó.'),
    note('Lưu ý:', 'Trước khi xác nhận, hãy mở khối Lịch sử gửi để biết lần trước đã gửi khi nào và kết quả ra sao. Xác nhận nhầm sẽ tạo ra một chứng từ trùng trên cổng, và cổng không có cách tự loại bỏ bản trùng đó.'),

    h3('6.7.5. Ký và gửi nhiều hồ sơ cùng lúc'),
    p('Khi có một loạt hồ sơ cần gửi — thường là sau khi sửa xong lỗi, hoặc sau một đợt ký số thất bại vì thiết bị ký chưa sẵn sàng — không cần mở từng hồ sơ. Cột đầu tiên của bảng có ô tích; tích xong bấm nút Ký và gửi đã chọn.'),
    steps([
        ['1', 'Lọc ra nhóm hồ sơ cần gửi, ví dụ chọn Trạng thái gửi là "Ký số thất bại", rồi bấm Tải dữ liệu.', 'Danh sách chỉ còn nhóm cần xử lý.'],
        ['2', 'Tích vào ô ở đầu từng dòng, hoặc tích ô trên tiêu đề để chọn hết các dòng của trang.', 'Số trên nút đổi theo, ví dụ "Ký và gửi đã chọn (12)".'],
        ['3', 'Bấm Ký và gửi đã chọn.', 'Hộp thoại hỏi lại kèm số lượng.'],
        ['4', 'Đọc kỹ con số rồi xác nhận.', 'Bảng kết quả hiện ra bên dưới các nút, và danh sách tự tải lại.'],
    ]),
    note('Lưu ý:', 'Ô tích chỉ chọn được trong TRANG ĐANG XEM, không có chức năng chọn tất cả hồ sơ khớp bộ lọc. Đây là chủ ý: chọn cả những trang chưa xem nghĩa là gửi những hồ sơ mình chưa từng nhìn, và chỉ cần sai bộ lọc một chút là gửi sai hàng loạt lên cổng — nơi không có đường rút lại. Muốn xử lý nhiều thì tăng số dòng mỗi trang rồi làm từng trang.'),
    p('Mỗi lượt gửi tối đa 50 hồ sơ. Chọn quá số này thì cả lượt bị từ chối chứ không gửi 50 cái đầu rồi bỏ phần còn lại — nửa vời sẽ khiến người bấm tưởng đã gửi hết.'),

    p('Bảng kết quả chia hai phần: số hồ sơ đã xếp hàng, và danh sách hồ sơ bị bỏ qua kèm lý do từng cái. Một hồ sơ không đủ điều kiện không làm cả lượt thất bại — những hồ sơ còn lại vẫn được gửi.'),

    h3('6.7.6. Hồ sơ nào tích được, hồ sơ nào không'),
    p('Chỉ bốn trạng thái có ô tích: Chưa ký số, Ký số thất bại, Gửi thất bại và Chờ gửi. Các dòng còn lại không hiện ô tích — không cho tích cái không gửi được, thay vì hiện ô tích rồi báo lỗi sau khi bấm.'),
    table(
        ['Trạng thái không tích được', 'Vì sao'],
        [
            ['Chưa kiểm', 'Máy chưa kiểm dữ liệu. Chờ rồi tải lại danh sách.'],
            ['Còn lỗi chặn', 'Phải sửa dữ liệu ở phần mềm sinh XML rồi nạp lại.'],
            ['Chức năng gửi đang tắt', 'Cấu hình chặn mọi lượt gửi. Báo quản trị hệ thống.'],
            ['Đã gửi', 'Cổng đã tiếp nhận và đã cấp mã giao dịch. Muốn gửi lại thì mở màn chi tiết bấm "Ký và gửi lại" — xem giải thích ngay dưới.'],
            ['Cổng từ chối', 'Hồ sơ này luôn cần xác nhận riêng như mục 6.7.4. Mở màn chi tiết để xử lý.'],
        ],
        [2600, 6420],
    ),
    note('Lưu ý:', 'Hai trạng thái cuối bảng trên vẫn gửi lại được ở màn chi tiết nhưng KHÔNG gửi hàng loạt được, và đó là chủ ý. Ở màn chi tiết người bấm đang nhìn đúng hồ sơ đó, nhãn nút đã đổi thành "Ký và gửi lại", và với hồ sơ cổng từ chối thì phần mềm còn hỏi xác nhận. Trong một lượt 50 dòng thì không ai nhìn từng cái, nên một lần bấm sẽ gửi lại im lặng những hồ sơ cổng đã nhận — mỗi cái là một chứng từ trùng trên cổng.'),
    p('Nếu một hồ sơ đang được xử lý ở một lượt khác — chẳng hạn tiến trình nạp tự động vừa xếp hàng chính nó vài giây trước — thì nó cũng bị bỏ qua kèm lý do, không bị gửi thêm lần nữa.'),

    h2('6.8. Bảng mã lỗi chứng từ điện tử'),
    p('Các lỗi do phần mềm tự phát hiện trước khi gửi mang mã bắt đầu bằng CTDT. Lỗi mức chặn làm hồ sơ không gửi được; lỗi mức cảnh báo chỉ để lưu ý, không chặn.'),
    table(
      ['Mã lỗi', 'Mô tả', 'Mức độ'],
      [
        ['CTDT001', 'Thiếu trường bắt buộc.', 'Chặn'],
        ['CTDT002', 'Trường ngày sai định dạng.', 'Chặn'],
        ['CTDT003', 'Giới tính ngoài giá trị cho phép.', 'Chặn'],
        ['CTDT004', 'Loại giấy tờ ngoài giá trị cho phép.', 'Chặn'],
        ['CTDT005', 'Trường cờ ngoài giá trị 0 hoặc 1.', 'Cảnh báo'],
        ['CTDT006', 'Ngày kết thúc sớm hơn ngày bắt đầu.', 'Chặn'],
        ['CTDT007', 'Mã cơ sở trong chứng từ lệch với mã cơ sở của hồ sơ.', 'Chặn'],
        ['CTDT008', 'Thiếu mã thẻ bảo hiểm y tế.', 'Cảnh báo'],
      ],
      [1400, 6120, 1500],
    ),
    p('Mọi lỗi trong bảng trên đều phải sửa ở phần mềm sinh ra tệp XML rồi nạp lại hồ sơ. Không có chức năng sửa dữ liệu chứng từ trực tiếp trên màn hình này — sửa tại đây sẽ làm nội dung lệch với chữ ký số và với bản gốc lưu tại đơn vị.'),

    h2('6.9. Xuất dữ liệu ra Excel'),
    p('Ba nút ở đầu bảng danh sách xuất ba tệp Excel khác nhau, phục vụ ba việc khác nhau.'),
    table(
      ['Nút', 'Nội dung tệp', 'Dùng khi nào'],
      [
        ['Xuất danh sách', 'Mỗi hồ sơ một dòng, gồm mã hồ sơ, dịch vụ, loại, cơ sở, họ tên, số thẻ, số CCCD, mã BHXH, số chứng từ, số lỗi, trạng thái gửi, mã giao dịch, mã kết quả, thời gian tiếp nhận, lỗi ký số, lỗi gửi, người nạp, thời điểm nạp và thời điểm gửi.', 'Đối chiếu tổng thể với bảng kê của cơ quan bảo hiểm.'],
        ['Xuất bảng lỗi', 'Mỗi lỗi một dòng, gồm mã hồ sơ, cơ sở, họ tên, số thẻ, loại chứng từ, mã lỗi, trường bị lỗi, mức độ và mô tả.', 'Gửi cho bộ phận nhập liệu đi sửa dữ liệu. Một hồ sơ có ba lỗi sẽ thành ba dòng.'],
        ['Xuất nhật ký gửi', 'Mỗi lượt gửi một dòng, gồm thời điểm, mã hồ sơ, nguồn gửi, người gửi, kết quả, mã kết quả, mã giao dịch, thời gian tiếp nhận và phản hồi của cổng.', 'Truy vết khi cần biết ai đã gửi hồ sơ nào, lúc nào, và cổng trả lời ra sao.'],
      ],
      [1900, 4560, 2560],
    ),
    p('Cột Nguồn trong nhật ký gửi cho biết lượt gửi đó do người bấm nút trên màn hình hay do tiến trình tự động thực hiện. Với lượt gửi tự động, cột Người gửi để trống.'),
    p('Tên tệp tự sinh theo thời điểm xuất, ví dụ chung-tu-dien-tu-20260822-143005.xlsx. Riêng nhật ký gửi mang thêm khoảng ngày trong tên tệp.'),
    note('Lưu ý:', 'Ba nút này xuất theo bộ lọc của lần Tải dữ liệu gần nhất. Muốn tệp phản ánh điều kiện lọc mới, phải bấm Tải dữ liệu trước rồi mới bấm nút xuất.'),

    h2('6.10. Dashboard chứng từ'),
    p('Mở bằng Hồ sơ XML → Chứng từ điện tử → Dashboard chứng từ. Màn hình này trả lời câu hỏi "hệ thống có đang chạy đúng không", không phải để tra cứu từng hồ sơ.'),
    p('Ba ô chọn ở đầu màn hình: Từ ngày, Đến ngày và Dịch vụ. Bấm Xem để tải lại toàn bộ các khối bên dưới.'),

    h3('6.10.1. Khối sức khoẻ vận hành'),
    table(
      ['Khối', 'Đọc thế nào'],
      [
        ['Ba hàng đợi', 'Mỗi hàng đợi hiện số việc đang chờ và việc chờ lâu nhất đã bao nhiêu phút. Con số phút mới là chỗ đáng đọc: hàng đợi khoẻ tiêu hết việc trong vài giây, nên "chờ 40 phút" nghĩa là tiến trình xử lý đã dừng.'],
        ['Hồ sơ theo trạng thái', 'Biểu đồ chia hồ sơ theo chín trạng thái ở mục 6.5. Một cột bất thường cao ở "Chưa kiểm" hoặc "Chờ gửi" là dấu hiệu tắc nghẽn.'],
        ['Tồn đọng', 'Số hồ sơ đã nạp nhưng chưa nhận được kết quả từ cổng, kèm hồ sơ cũ nhất đã nằm bao nhiêu ngày. Số ngày bắt được tình trạng hỏng chậm mà biểu đồ sản lượng không chỉ ra, vì lượng nạp mỗi ngày vẫn bình thường.'],
      ],
      [2200, 6820],
    ),
    note('Lưu ý:', 'Hàng đợi trống hiển thị số 0 và trông y hệt một hàng đợi khoẻ đang rảnh. Vì vậy hãy đọc kèm khối Tồn đọng: hàng đợi trống mà tồn đọng vẫn tăng nghĩa là tiến trình nền không chạy, chứ không phải không có việc.'),

    h3('6.10.2. Khối sản lượng và chất lượng'),
    table(
      ['Khối', 'Đọc thế nào'],
      [
        ['Sản lượng theo ngày', 'Số hồ sơ nạp mỗi ngày, tách theo dịch vụ. Khung ngày được vẽ đầy đủ, nên ngày hệ thống dừng hoàn toàn hiện thành điểm bằng 0 chứ không biến mất khỏi trục.'],
        ['Mã lỗi hay gặp', 'Xếp hạng mã lỗi theo số lần xuất hiện. Dùng để chọn lỗi nào đáng sửa tận gốc ở phần mềm nguồn thay vì sửa từng hồ sơ.'],
        ['Cơ sở sai nhiều nhất', 'Xếp hạng cơ sở khám chữa bệnh theo số lỗi. Dùng để biết cần tập huấn lại cho nơi nào.'],
      ],
      [2200, 6820],
    ),
    p('Khi dữ liệu quá lớn, hai khối xếp hạng chỉ lấy một phần và hiện dòng cảnh báo cho biết số liệu đã bị cắt bớt. Thu hẹp khoảng ngày để có con số đầy đủ.'),

    h2('6.11. Xoá một hồ sơ'),
    p('Nút Xóa hồ sơ nằm trong màn chi tiết và chỉ hiện với tài khoản quản trị cao nhất. Xoá là xoá hẳn: hồ sơ và toàn bộ chứng từ, lỗi kèm theo đều biến mất khỏi phần mềm.'),
    note('Lưu ý:', 'Xoá một hồ sơ đã có mã giao dịch là xoá mất dấu vết đối soát với cơ quan bảo hiểm xã hội, trong khi chứng từ vẫn tồn tại trên cổng. Chỉ xoá hồ sơ nạp nhầm và chưa từng gửi. Với hồ sơ đã gửi, hãy nạp đè bản đúng thay vì xoá.'),

    h2('6.12. Nạp và gửi tự động'),
    p('Ngoài đường kéo thả tệp, phần mềm có một tiến trình chạy ngầm: cứ vài giây lại quét một thư mục quy ước trên máy chủ, thấy tệp XML mới thì tự nạp và tự kiểm lỗi. Nhờ đó phần mềm nghiệp vụ chỉ cần ghi tệp vào thư mục đó, không cần ai ngồi kéo thả.'),
    p('Việc tự động ký và gửi lên cổng là một công tắc RIÊNG, mặc định tắt. Khi công tắc này tắt, tiến trình chỉ nạp và kiểm lỗi; mọi lượt gửi vẫn phải do người bấm nút trên màn chi tiết. Đây là lựa chọn có chủ đích: cho phép người bấm nút gửi và cho phép máy tự gửi khi không có ai nhìn là hai mức tin cậy khác nhau, nên dùng hai công tắc khác nhau.'),
    p('Khi công tắc tự động gửi được bật, một hồ sơ mới thường mất khoảng mười đến mười lăm giây kể từ lúc tệp xuất hiện trong thư mục cho tới lúc phần mềm gọi lên cổng, cộng thêm thời gian ký số.'),
    p('Hai điểm người vận hành cần biết:'),
    bullet('Mỗi hồ sơ chỉ được tiến trình tự động gửi ĐÚNG MỘT LẦN. Gửi hỏng thì hồ sơ nằm lại chờ người xử lý tay, máy không tự thử lại. Đây là hàng rào chặn tình huống gửi trùng lặp vô hạn khi cổng đã nhận nhưng phản hồi bị thất lạc.'),
    bullet('Có một cách dừng khẩn cấp: đặt một tệp rỗng tên DUNG-GUI vào thư mục quét. Từ lượt quét kế tiếp, tiến trình vẫn nạp và kiểm lỗi bình thường nhưng ngừng gửi. Xoá tệp đó đi là gửi lại. Không cần khởi động lại dịch vụ và không cần quyền quản trị máy chủ.'),
    forIt('Lệnh chạy nền là ctdt:import --lien-tuc, cài thành dịch vụ Windows bằng install_service.bat. Ba hàng đợi JobCtdt, JobSignCtdt và JobSubmitCtdt phải cùng chạy thì chuỗi kiểm — ký — gửi mới thông. Thiếu hàng đợi nào thì hồ sơ dừng khựng đúng ở bước đó, và cột Số lỗi trên màn danh sách đứng yên ở 0 — trông y hệt như mọi hồ sơ đều sạch. Các công tắc nằm ở khối chung_tu_dien_tu trong config/organization.php: submit_enabled cho phép người gửi, import_tu_dong_gui cho phép máy gửi, sign_enabled bật chức năng ký. Thư mục quét khai ở config/filesystems.php, đổi bằng biến CTDT_IMPORT_PATH.'),

    h2('6.13. Xử lý sự cố thường gặp'),
    errors([
      ['Không nhìn thấy menu Chứng từ điện tử', 'Tài khoản chưa có quyền xml-man.', 'Đề nghị quản trị hệ thống cấp quyền, sau đó đăng xuất rồi đăng nhập lại.'],
      ['Cả loạt hồ sơ đứng ở trạng thái "Chưa kiểm"', 'Tiến trình kiểm lỗi nền không chạy.', 'Mở Dashboard chứng từ, xem khối Ba hàng đợi. Báo bộ phận công nghệ thông tin kèm số liệu ở khối đó.'],
      ['Số lỗi hiện 0 nhưng hồ sơ vẫn không gửi được', 'Hồ sơ chưa được kiểm, con số 0 chỉ có nghĩa là chưa ai nhìn.', 'Đọc cột Trạng thái gửi. Nếu là "Chưa kiểm" thì chờ, không phải hồ sơ đã sạch.'],
      ['Bấm Ký và gửi nhưng trạng thái không đổi', 'Việc ký và gửi chạy ở hàng đợi nền, chưa tới lượt.', 'Chờ rồi bấm Tải dữ liệu. Nếu sau vài phút vẫn không đổi, xem khối Ba hàng đợi trên Dashboard.'],
      ['Trạng thái "Ký số thất bại"', 'Thiết bị ký số chưa sẵn sàng hoặc dịch vụ ký không phản hồi.', 'Mở chi tiết đọc dòng Lỗi ký số. Kiểm tra thiết bị ký đã cắm và phần mềm ký đang chạy, rồi bấm gửi lại.'],
      ['Trạng thái "Cổng từ chối"', 'Cổng đã trả lời nhưng không tiếp nhận.', 'Mở chi tiết, đọc mã kết quả và phản hồi của cổng, tra ý nghĩa ở mục 6.7.3.'],
      ['Tệp Excel xuất ra khác với bảng đang xem', 'Đã đổi ô lọc nhưng chưa bấm Tải dữ liệu.', 'Bấm Tải dữ liệu rồi xuất lại. Tệp luôn theo bộ lọc của lần tải gần nhất.'],
      ['Nạp lại hồ sơ xong thì mã giao dịch biến mất', 'Nạp đè xoá kết quả của lần gửi trước, vì nội dung đã đổi.', 'Đây là hành vi đúng thiết kế. Dấu vết lần gửi cũ nằm ở khối Lịch sử gửi trong màn chi tiết.'],
      ['Phần mềm hỏi xác nhận khi bấm gửi lại', 'Hồ sơ đã từng được gửi lên cổng.', 'Mở khối Lịch sử gửi kiểm tra lần gửi trước rồi mới quyết định. Xác nhận nhầm sẽ tạo chứng từ trùng trên cổng.'],
      ['Dòng cần gửi không có ô tích', 'Hồ sơ không thuộc bốn trạng thái gửi hàng loạt được.', 'Đọc cột Trạng thái gửi rồi tra bảng ở mục 6.7.6. Với "Đã gửi" và "Cổng từ chối" thì mở màn chi tiết để xử lý từng hồ sơ.'],
      ['Bấm Ký và gửi đã chọn nhưng cả lượt bị bỏ qua', 'Không hồ sơ nào trong lượt đủ điều kiện gửi.', 'Đọc cột Lý do trong bảng kết quả — mỗi hồ sơ một dòng, nêu rõ vướng ở đâu.'],
      ['Báo "Mỗi lượt chỉ gửi tối đa 50 hồ sơ"', 'Đang chọn quá 50 dòng.', 'Bỏ tích bớt, hoặc giảm số dòng mỗi trang rồi làm thành nhiều lượt.'],
      ['Hồ sơ tự động nạp vào nhưng không tự gửi', 'Công tắc tự động gửi đang tắt, hoặc có tệp DUNG-GUI trong thư mục quét.', 'Đây có thể là hành vi đúng thiết kế. Liên hệ bộ phận công nghệ thông tin để xác nhận trước khi coi là lỗi.'],
    ]),
  ];
};
