// Nội dung phần tra cứu tiền cùng chi trả (MCCT) — dành cho Phòng Tài chính kế toán (viện phí) và
// Phòng BHYT, thuộc khối các phòng ban chức năng.
// Nguồn: docs/huong-dan-su-dung/_nguon_mcct/build.js. Không mở chương: deck-tgd.js mở.
const L = require('./lib');

// Toàn bộ nội dung MCCT: điều kiện, hai cách tra, đọc kết quả, thông báo.
function mcct(pptx, ctx, no) {
  L.cardSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 1',
    title: 'Điều kiện miễn cùng chi trả gồm HAI vế',
    cards: [
      { head: 'Vế 1 — Tham gia BHYT đủ 5 năm liên tục trở lên', tone: 'warn',
        body: 'PHẦN MỀM KHÔNG KIỂM ĐƯỢC VẾ NÀY.\n\nHàm tra cứu của cổng BHXH không trả về dữ kiện 5 năm liên tục.\n\nCán bộ phải kiểm tra riêng vế này.' },
      { head: 'Vế 2 — Lũy kế cùng chi trả trong năm LỚN HƠN 6 tháng lương cơ sở', tone: 'ok',
        body: 'Đây là vế phần mềm tra được.\n\nBằng đúng mức đó thì CHƯA ĐỦ — phải lớn hơn.\n\nKhi đạt, màn hình chỉ ghi "ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ", không kết luận là đã đủ điều kiện miễn.' },
    ],
    note: { label: 'Căn cứ pháp lý:', text: 'Điểm b khoản 2 Điều 18 Nghị định số 188/2025/NĐ-CP ngày 01/7/2025 · Công văn số 1839/CNTT-PM ngày 18/8/2026 của Trung tâm Công nghệ thông tin và Chuyển đổi số, Bảo hiểm xã hội Việt Nam.' },
    speaker: 'Slide quan trọng nhất của buổi. Câu phải nhắc lại nhiều lần: đạt ngưỡng tiền MỚI LÀ MỘT NỬA điều kiện. Hỏi cả phòng cách kiểm tra vế 5 năm liên tục tại đơn vị mình.',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Bối cảnh',
    title: 'Số liệu đến từ đâu',
    intro: 'Phần mềm không tự tính số tiền. Nó hỏi cổng BHXH và hiển thị lại đúng những gì cổng trả về.',
    nodes: [
      { head: 'Các cơ sở KCB', body: 'Gửi hồ sơ đề nghị thanh toán KCB BHYT lên Hệ thống thông tin giám định' },
      { head: 'Cổng BHXH', body: 'Cộng dồn tiền cùng chi trả của người bệnh trong năm, từ MỌI cơ sở', tone: 'primary' },
      { head: 'qlbv hỏi cổng', body: 'Mỗi lần tra là một lượt gọi thật, lấy số liệu mới nhất cổng đang có', tone: 'accent' },
      { head: 'Cán bộ trả lời người bệnh', body: 'Kèm mốc thời gian "tính đến …" và lưu ý về điều kiện 5 năm liên tục' },
    ],
    legend: [
      'Hệ quả 1: đợt khám vừa kết thúc hôm nay thường CHƯA có trong lũy kế, vì cơ sở chưa kịp gửi hồ sơ đề nghị thanh toán.',
      'Hệ quả 2: lũy kế bao gồm cả các đợt khám ở cơ sở khác — không chỉ riêng bệnh viện mình.',
      'Hệ quả 3: số liệu có độ trễ, nên luôn phải đọc mốc thời gian trước khi kết luận với người bệnh.',
    ],
    speaker: 'Slide này giải thích trước ba hiểu nhầm lớn nhất, để phần sau không phải dừng lại giải thích từng cái.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 3.1',
    title: 'Cách 1 — Tra ngay trên màn Tra cứu thẻ BHYT (khuyến nghị)',
    intro: 'Nhanh nhất khi đang làm việc trực tiếp với người bệnh, vì thông tin thẻ đã có sẵn, không phải nhập lại.',
    steps: [
      ['Vào menu Thẻ BHYT → Tra cứu thẻ BHYT', 'Màn hình tra cứu thẻ hiện ra'],
      ['Chọn cơ sở KCB, nhập mã thẻ BHYT/CCCD, họ tên, ngày sinh rồi bấm Tra cứu', 'Kết quả tra thẻ hiện ra'],
      ['Khi tra thẻ THÀNH CÔNG, nút "Tra tiền cùng chi trả" (màu xanh lá) hiện ra cạnh nút Tra cứu. Bấm vào đó', 'Một cửa sổ hiện lên và bắt đầu hỏi cổng BHXH'],
      ['Chờ cho tới khi có kết quả', 'Đồng hồ đếm giây chạy trong lúc chờ — xem mục về thời gian chờ'],
    ],
    note: { label: 'Nút chỉ xuất hiện khi tra thẻ thành công:', text: 'Nếu tra thẻ báo lỗi hoặc không tìm thấy thẻ thì nút không hiện, vì khi đó thông tin thẻ chưa đủ tin cậy để tra tiếp. Đây không phải lỗi hiển thị.' },
    speaker: 'Nhấn: đây là cách nên dùng mặc định khi người bệnh đang ở quầy. Cách 2 chỉ dùng khi cần tra độc lập.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 3.1',
    title: 'Nút "Tra tiền cùng chi trả" nằm ở đâu',
    shot: 6,
    caption: 'Màn Thẻ BHYT → Tra cứu thẻ BHYT SAU KHI tra thẻ thành công.\nChụp cận khu vực hai nút: nút Tra cứu và nút Tra tiền cùng chi trả màu xanh lá ngay bên cạnh.',
    points: [
      'Nút chỉ hiện khi tra thẻ THÀNH CÔNG',
      'Tra thẻ lỗi hoặc không tìm thấy thẻ → nút không hiện, đây không phải lỗi hiển thị',
      'Bấm nút mở một cửa sổ riêng và bắt đầu hỏi cổng BHXH ngay',
      'Không phải nhập lại mã thẻ, họ tên, ngày sinh — đã lấy từ lần tra thẻ',
      'Đây là cách nên dùng mặc định khi đang làm việc với người bệnh',
    ],
    speaker: 'Nhiều người không biết có nút này nên vẫn sang màn hình riêng gõ lại từ đầu. Chỉ rõ vị trí trên ảnh.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 3.2',
    title: 'Cách 2 — Tra trên màn hình riêng',
    intro: 'Dùng khi cần tra độc lập, không đi từ màn tra cứu thẻ.',
    steps: [
      ['Vào menu Thẻ BHYT → Tra cứu tiền cùng chi trả', 'Hàng trên là ô Quét QR; hàng dưới là Cơ sở KCB, Mã thẻ/CCCD, Họ và tên, Ngày sinh và nút Tra cứu'],
      ['Kiểm tra ô Cơ sở KCB — thông thường ô này đã được chọn sẵn', 'Phần mềm ghi nhớ lựa chọn từ lần trước'],
      ['Cách nhanh: con trỏ đã nằm sẵn ở ô Quét QR, chỉ cần quét mã QR trên thẻ BHYT hoặc CCCD', 'Mã thẻ, họ tên, ngày sinh tự điền và phần mềm TỰ tra cứu luôn, không phải bấm thêm'],
      ['Cách thủ công: nhập mã thẻ/CCCD, họ tên, ngày sinh rồi bấm Tra cứu (hoặc nhấn Enter ở bất kỳ ô nào)', 'Yêu cầu được gửi lên cổng'],
      ['Chờ cổng BHXH trả lời', 'Kết quả hiện ra ngay bên dưới, trang KHÔNG tải lại'],
    ],
    note: { label: 'Về mã QR:', text: 'Mã QR trên thẻ thường ghi ngày sinh thành 8 chữ số liền (ví dụ 20101964). Phần mềm tự chuyển sang dạng 20/10/1964 trước khi tra, nên không bị báo sai định dạng.', kind: 'ok' },
    speaker: 'Thanh địa chỉ trình duyệt tự cập nhật theo thẻ đang tra — sao chép được đường dẫn gửi cho người khác, hoặc F5 để mở lại. Nhưng nhớ: mở lại đường dẫn CŨNG là một lần tra mới trên cổng.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 4',
    title: 'Quy tắc nhập liệu — bốn ô, bốn quy tắc',
    head: ['Trường', 'Yêu cầu', 'Ghi chú'],
    colW: [2.0, 2.6, 5.4],
    rows: [
      [{ t: 'Cơ sở KCB', b: true }, 'Bắt buộc chọn', 'Chỉ hiện những cơ sở đã khai tài khoản cổng BHXH. Chưa chọn thì phần mềm nhắc chọn chứ không báo lỗi. Lựa chọn được ghi nhớ cho lần sau'],
      [{ t: 'Mã thẻ BHYT/CCCD', b: true }, 'Đúng 10, 12, 15 hoặc 17 ký tự', 'Mã thẻ BHYT 15 hoặc 17 ký tự; mã số BHXH / số CCCD 10 hoặc 12 ký tự. Gõ kèm dấu cách cho dễ đọc cũng được — phần mềm tự bỏ khoảng trắng'],
      [{ t: 'Họ và tên', b: true }, 'Bắt buộc', 'Tự chuyển thành chữ IN HOA ngay khi gõ, kể cả họ tên điền từ mã QR. Không cần bật Caps Lock'],
      [{ t: 'Ngày sinh', b: true }, '20/10/1964 hoặc 10/1964 hoặc 1964', 'Phải đủ HAI chữ số cho ngày và tháng. Gõ 1/1/1964 sẽ bị báo sai — phải gõ 01/01/1964'],
    ],
    note: { label: 'Về việc ghi nhớ cơ sở:', text: 'Màn Tra cứu tiền cùng chi trả và màn Tra cứu thẻ BHYT dùng chung lựa chọn này. Việc ghi nhớ gắn với từng máy tính và từng trình duyệt — đổi máy, đổi trình duyệt, dùng cửa sổ ẩn danh hoặc xoá dữ liệu duyệt web thì phải chọn lại một lần. Phần mềm KHÔNG lưu tài khoản, mật khẩu hay thông tin người bệnh trên trình duyệt.' },
    speaker: 'Lỗi nhập ngày sinh thiếu số 0 là lỗi gặp nhiều nhất. Cho học viên gõ thử 1/1/1964 để thấy thông báo.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 5.1 – 5.2',
    title: 'Đọc kết quả — khối kết luận',
    shot: 5,
    caption: 'Màn Tra cứu tiền cùng chi trả sau khi có kết quả.\nChụp đủ: thông tin thẻ, khối kết luận với ba con số, nhãn kết luận, và DÒNG CHỮ NHỎ ghi nguồn dữ liệu bên dưới.',
    points: [
      'Lũy kế cùng chi trả — tổng tiền người bệnh đã cùng chi trả trong năm',
      'Ngưỡng cả năm — số tiền cần đạt. KHÔNG phải lúc nào cũng bằng 6 tháng lương cơ sở hiện hành',
      'Kết luận — nhãn xanh "ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ" hoặc nhãn vàng "CÒN THIẾU" kèm số tiền',
      'Hai số đầu đều tính từ 01/01 nên so sánh trực tiếp được với nhau',
      'Khi đạt ngưỡng, màn hình hiện thêm khung nhắc kiểm tra điều kiện 5 năm liên tục — đọc kỹ trước khi trả lời',
    ],
    note: { label: 'BẮT BUỘC đọc dòng chữ nhỏ bên dưới:', text: 'Dòng đó ghi nguồn dữ liệu và mốc thời gian, ví dụ "Nguồn DL lấy từ các CSKCB đề nghị thanh toán KCB BHYT trên HTTTGĐ BHYT tính đến: 14/08/2026 14:41". Số liệu của cổng CÓ ĐỘ TRỄ — đợt KCB mà cơ sở chưa gửi hồ sơ đề nghị thanh toán thì chưa được tính vào lũy kế.', kind: 'danger' },
    speaker: 'Dừng lâu ở đây. Dạy học viên thói quen: đọc mốc thời gian TRƯỚC khi nói con số cho người bệnh. Câu mẫu: "Theo số liệu cổng bảo hiểm tính đến ngày …, bác đã cùng chi trả … đồng".',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 5.3',
    title: 'Khi lương cơ sở thay đổi giữa năm — ngưỡng KHÔNG phải 6 × lương mới',
    bullets: [
      { t: 'Năm 2026 lương cơ sở đổi từ 2.340.000 đ lên 2.530.000 đ kể từ 01/7/2026', b: true },
      { t: 'Ngưỡng cả năm KHÔNG phải là 6 × 2.530.000 = 15.180.000 đ', b: true, color: 'C62828' },
      { t: 'Theo điểm c khoản 2 Điều 18 Nghị định 188/2025/NĐ-CP: phần tiền đã cùng chi trả trước ngày đổi lương được quy đổi ra số tháng theo LƯƠNG CŨ; phần còn thiếu mới tính theo LƯƠNG MỚI', b: true },
      { t: 'Số tiền còn phải cùng chi trả = ( 6 − Tiền đã đóng trước 01/7 ÷ Lương cũ ) × Lương mới', b: true, color: '1F4E79' },
      { t: 'Ngưỡng cả năm = Tiền đã đóng trước 01/7 + Số tiền còn phải cùng chi trả', b: true, color: '1F4E79' },
      { t: 'Ví dụ: đã cùng chi trả 13.000.000 đ tính đến 30/6/2026', b: true,
        sub: 'Còn phải đóng = (6 − 13.000.000 ÷ 2.340.000) × 2.530.000 = 1.124.444 đ → Ngưỡng cả năm = 14.124.444 đ, CHƯA đủ miễn' },
    ],
    note: { label: 'Không phải tính tay:', text: 'Màn hình tự tính và hiện đầy đủ các bước này ngay dưới khối kết luận. Nếu người bệnh đã đóng đủ 6 tháng lương CŨ trước ngày đổi lương thì được hưởng quyền lợi ngay, không áp dụng công thức. Đợt KCB được xếp trước hay sau mốc đổi lương theo NGÀY RA VIỆN.', kind: 'ok' },
    speaker: 'Không bắt học viên thuộc công thức. Mục tiêu duy nhất: hiểu vì sao con số ngưỡng trên màn hình lại khác 15.180.000, để không cãi nhau với người bệnh hoặc với đồng nghiệp.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 5.4 – 5.5',
    title: 'Bảng chi tiết các đợt KCB và lịch sử tra cứu',
    intro: 'Bảng chi tiết liệt kê từng đợt KCB có phát sinh cùng chi trả, sắp xếp giảm dần theo ngày ra viện.',
    head: ['Cột', 'Ý nghĩa'],
    colW: [3.0, 7.0],
    rows: [
      [{ t: 'Mã CSKCB', b: true }, 'Cơ sở nơi phát sinh đợt KCB đó — CÓ THỂ LÀ CƠ SỞ KHÁC, không phải cơ sở đang tra'],
      ['Ngày vào / Ngày ra', 'Ngày vào viện và ngày ra viện của đợt đó'],
      ['Đối tượng', 'Mã đối tượng khi đi khám chữa bệnh của đợt đó'],
      ['Tiền CCT thuộc diện miễn', 'Số tiền cùng chi trả của riêng đợt đó được tính vào diện xét miễn'],
      [{ t: 'Lũy kế', b: true }, 'Số tiền cùng chi trả cộng dồn tính đến hết đợt đó — đây là con số dùng để xét ngưỡng'],
      ['Ngày nhận', 'Ngày cổng tiếp nhận hồ sơ của đợt đó'],
    ],
    note: { label: 'Lịch sử tra cứu:', text: 'Cuối trang (trên màn hình riêng) hiển thị vài lần tra gần nhất của chính thẻ đó: thời điểm, cơ sở, kết quả, lũy kế và NGƯỠNG ÁP DỤNG LÚC ĐÓ. Khi lương cơ sở tăng, các bản ghi cũ vẫn giữ nguyên kết luận cũ — đúng như tại thời điểm đó.' },
    speaker: 'Nhấn cột Mã CSKCB: lũy kế bao gồm cả các đợt khám ở cơ sở khác. Đây là điều người bệnh hay không hiểu và cán bộ cần giải thích được.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 6',
    title: 'Vì sao phải chờ, và vì sao không được bấm nhiều lần',
    bullets: [
      { t: 'Cổng BHXH thường trả lời trong khoảng 5 đến 30 giây, đôi khi lâu hơn', b: true },
      { t: 'Trong lúc chờ, màn hình hiển thị đồng hồ đếm số giây. Đồng hồ còn chạy nghĩa là hệ thống vẫn làm việc bình thường', b: true },
      { t: 'Sau 25 giây, dòng chữ tự đổi thành "Cổng BHXH đang phản hồi chậm, vẫn đang chờ…" — vẫn là trạng thái BÌNH THƯỜNG, không phải lỗi', b: true },
      { t: 'KHÔNG bấm lại nhiều lần. Cơ quan BHXH giới hạn số lượt tra cứu của mỗi tài khoản và có danh sách tài khoản bị hạn chế tra cứu', b: true, color: 'C62828',
        sub: 'Mỗi lần bấm là một lượt gọi thật. Vì vậy trong lúc chờ, phần mềm tự khoá nút và các ô nhập liệu' },
      { t: 'Mỗi lần tra là một lượt hỏi cổng thật, kể cả khi mở lại đúng thẻ vừa tra. Chỉ tra khi cần', b: true },
      { t: 'Phần mềm chờ cổng tối đa 120 giây; quá thời gian đó thì dừng chờ và báo lỗi — đợi vài phút rồi bấm Thử lại', b: true },
    ],
    note: { label: 'Đổi lại việc phải chờ:', text: 'Mỗi lần tra đều lấy số liệu MỚI NHẤT từ cổng, không phải số liệu đã lưu từ lần tra trước. Các lần tra trước của thẻ vẫn xem được ở bảng Lịch sử tra cứu cuối trang.', kind: 'ok' },
    speaker: 'Đây là slide giải quyết hành vi xấu phổ biến nhất: sốt ruột nên bấm đi bấm lại. Giải thích hậu quả thật: tài khoản của cơ sở có thể bị cổng hạn chế tra cứu.',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 7',
    title: 'Các thông báo hay gặp — phần cán bộ tự xử lý',
    cases: [
      { what: 'Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, hoặc thẻ chưa phát sinh chi phí cùng chi trả.', why: 'Cổng dùng CHUNG một mã cho hai tình huống khác nhau', fix: 'Kiểm tra lại mã thẻ, họ tên, ngày sinh. Thông tin đã đúng thì nghĩa là thẻ chưa phát sinh chi phí cùng chi trả trong năm — KHÔNG phải lỗi' },
      { what: 'Chọn cơ sở khám chữa bệnh rồi bấm Tra cứu', why: 'Chưa chọn cơ sở', fix: 'Chọn cơ sở trong ô đầu tiên rồi tra lại' },
      { what: 'Mã thẻ BHYT/CCCD phải có 10, 12, 15 hoặc 17 ký tự', why: 'Nhập sai số ký tự', fix: 'Kiểm tra lại mã trên thẻ BHYT hoặc trên căn cước công dân' },
      { what: 'Ngày sinh phải theo dd/mm/yyyy, mm/yyyy hoặc yyyy', why: 'Nhập thiếu chữ số', fix: 'Phải gõ 01/01/1964 chứ không gõ 1/1/1964' },
      { what: 'Cổng BHXH không trả lời sau 120 giây…', why: 'Cổng vẫn hoạt động nhưng trả lời quá chậm', fix: 'KHÔNG phải sự cố đường truyền của bệnh viện. Đợi vài phút rồi bấm Thử lại' },
    ],
    speaker: 'Năm thông báo này chiếm phần lớn số lần gọi hỗ trợ. Nếu học viên thuộc năm dòng này thì phòng CNTT đỡ được rất nhiều.',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 7',
    title: 'Các thông báo phải báo lên — không tự xử lý được',
    cases: [
      { what: 'Không xác thực được với cổng BHXH…', why: 'Phiên làm việc hết hạn, hoặc địa chỉ IP của máy chủ khác IP đã đăng ký với cơ quan BHXH', fix: 'Báo bộ phận công nghệ thông tin' },
      { what: 'Tài khoản của cơ sở … đang bị cổng hạn chế tra cứu', why: 'Vấn đề tài khoản, KHÔNG phải lỗi phần mềm', fix: 'Liên hệ cơ quan BHXH tỉnh để được mở lại' },
      { what: 'Cơ sở … chưa khai tài khoản cổng BHXH…', why: 'Cơ sở chưa được cấu hình', fix: 'Báo bộ phận công nghệ thông tin khai tài khoản cho cơ sở đó' },
      { what: 'Không kết nối được cổng BHXH / Cổng BHXH báo lỗi (500)…', why: 'Sự cố đường truyền, cổng bảo trì, hoặc lỗi phía hệ thống của cổng', fix: 'Chờ vài phút rồi thử lại. Kéo dài thì báo công nghệ thông tin / cơ quan BHXH' },
      { what: 'Đã tra cứu được nhưng không lưu được lịch sử tra cứu.', why: 'Không ghi được vào cơ sở dữ liệu, thường do chưa chạy lệnh tạo bảng', fix: 'Kết quả hiển thị VẪN ĐÚNG và dùng được. Báo công nghệ thông tin để khắc phục phần lưu lịch sử' },
    ],
    note: { label: 'Khi báo lên, cung cấp đủ:', text: 'Nguyên văn thông báo trên màn hình, cơ sở KCB đã chọn, thời điểm tra và mã thẻ đã tra. Thiếu những thông tin này thì bộ phận hỗ trợ phải hỏi lại và mất thêm thời gian.' },
    speaker: 'Dạy học viên chụp màn hình thông báo thay vì mô tả bằng lời. Một ảnh chụp tiết kiệm được cả buổi hỏi đi hỏi lại.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Phụ lục',
    title: 'Mức lương cơ sở và 6 tháng lương cơ sở',
    head: ['Áp dụng từ ngày', 'Lương cơ sở', '6 tháng lương cơ sở'],
    colW: [3.3, 3.3, 3.4],
    rows: [
      ['01/7/2023', '1.800.000 đ', '10.800.000 đ'],
      ['01/7/2024', '2.340.000 đ', '14.040.000 đ'],
      [{ t: '01/7/2026', b: true }, { t: '2.530.000 đ', b: true }, { t: '15.180.000 đ', b: true }],
    ],
    fontSize: 14,
    note: { label: 'Nhắc lại:', text: 'Con số 15.180.000 đ chỉ đúng khi người bệnh không có khoản cùng chi trả nào trước 01/7/2026. Có phát sinh trước mốc đó thì ngưỡng cả năm được tính theo công thức ở mục 5.3 và luôn thấp hơn 15.180.000 đ.' },
    speaker: 'Đây là bảng nên in ra dán tại quầy. Nhắc: mỗi lần Nhà nước điều chỉnh lương cơ sở, phòng CNTT phải bổ sung mốc mới vào cấu hình phần mềm.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'HDSD MCCT · Mục 8',
    title: 'Sáu điều cần lưu ý khi trả lời người bệnh',
    bullets: [
      { t: 'Số liệu do cổng BHXH cung cấp, phần mềm KHÔNG tự tính. Luôn đối chiếu mốc thời gian "tính đến …" trước khi trả lời', b: true },
      { t: 'Lũy kế chỉ gồm các đợt KCB mà cơ sở đã gửi hồ sơ đề nghị thanh toán lên cổng. Đợt vừa kết thúc hôm nay thường CHƯA có trong số liệu', b: true, color: 'C62828' },
      { t: 'Lũy kế tính theo năm tài chính và bao gồm cả các đợt KCB tại CƠ SỞ KHÁC, không chỉ riêng cơ sở đang tra', b: true },
      { t: 'Mọi lần tra cứu đều được ghi lại, kể cả lần không thành công, phục vụ đối chiếu về sau', b: true },
      { t: 'Mỗi lần tra đều hỏi thẳng cổng nên kết quả là số liệu mới nhất cổng có tại thời điểm tra', b: true },
      { t: 'Kết quả tra cứu là căn cứ THAM KHẢO, không thay thế thủ tục. Việc xác định đủ điều kiện miễn và cấp giấy chứng nhận thực hiện theo quy định hiện hành', b: true, color: '1F4E79' },
    ],
    speaker: 'Slide này nên đọc chậm từng dòng. Đây là ranh giới giữa "cung cấp thông tin" và "kết luận quyền lợi" — cán bộ tra cứu chỉ làm việc thứ nhất.',
  });
}

module.exports = { mcct };
