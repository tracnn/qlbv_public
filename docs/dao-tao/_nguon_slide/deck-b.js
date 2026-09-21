// DECK B — Phòng ban nghiệp vụ (BHYT/Giám định, KHTH, TCKT, đầu mối CNTT)
// Nguồn: Phần I (XML 3176), Phần III (tra thẻ hàng loạt), Phần IV (danh mục),
// Phần VI (chứng từ điện tử PL02), Phần VII (danh mục TT12/2026).

const L = require('./lib');

module.exports = function deckB(PptxGenJS) {
  const { pptx, ctx } = L.newDeck(PptxGenJS, {
    title: 'Đào tạo phòng ban nghiệp vụ — Liên thông dữ liệu với cổng BHXH',
    subject: 'Hướng dẫn sử dụng phần mềm qlbv cho khối phòng ban',
    deck: 'Deck B — Phòng ban nghiệp vụ',
  });

  L.titleSlide(pptx, {
    title: 'Liên thông dữ liệu\nvới cổng Bảo hiểm xã hội',
    subtitle: 'Phần mềm quản lý bệnh viện qlbv',
    audience: 'Dành cho khối phòng ban: BHYT/Giám định, Kế hoạch tổng hợp, Tài chính kế toán, đầu mối CNTT',
    meta: 'Thời lượng: 45–60 phút\nTài liệu gốc: Hướng dẫn sử dụng XML3176 – OrderCheck – Thẻ BHYT – Danh mục, Phần I, III, IV, VI và VII',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục tiêu',
    title: 'Sau buổi này, anh/chị làm được gì',
    bullets: [
      { t: 'Theo dõi được vòng đời một hồ sơ XML 3176 từ lúc nạp tới lúc cổng tiếp nhận', b: true,
        sub: 'Và giải thích được vì sao một hồ sơ cụ thể chưa được gửi' },
      { t: 'Dùng được Dashboard lỗi XML để chọn ưu tiên xử lý và làm số liệu báo cáo', b: true },
      { t: 'Nhập khẩu đúng một bộ danh mục BHYT và đọc được kết quả nhập', b: true,
        sub: 'Kể cả bốn bộ danh mục theo cơ sở — nơi dễ sai nhất' },
      { t: 'Ký và gửi chứng từ điện tử theo Phụ lục 02 mà không tạo ra chứng từ trùng trên cổng', b: true },
      { t: 'Nạp và gửi sáu mẫu danh mục theo Thông tư 12/2026, đọc được Dashboard độ phủ', b: true },
      { t: 'Phân biệt rõ: việc nào phòng ban tự làm, việc nào phải báo công nghệ thông tin', b: true },
    ],
    speaker: 'Buổi này nặng hơn Deck A. Nói trước: sẽ không đi hết mọi ô lọc, chỉ đi những chỗ sai là mất tiền hoặc mất thời gian. Chi tiết còn lại tra tài liệu.',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Bối cảnh',
    title: 'Ba luồng dữ liệu đi lên cổng BHXH',
    intro: 'Cùng một cổng nhưng ba đầu mối tiếp nhận khác nhau, ba màn hình khác nhau, ba mức rủi ro khác nhau.',
    nodes: [
      { head: 'XML 3176', body: 'Hồ sơ giám định chi phí KCB.\nGửi lại được, có mã giao dịch chống trùng.\n\nPhần I', tone: 'primary' },
      { head: 'Chứng từ điện tử\n(Phụ lục 02)', body: 'Giấy ra viện, chứng sinh, báo tử, nghỉ hưởng BHXH.\nKHÔNG có cơ chế chống trùng ở cổng.\n\nPhần VI', tone: 'accent' },
      { head: 'Danh mục TT12/2026', body: 'Sáu bộ danh mục cơ sở tự khai về năng lực của mình.\nGửi khi có thay đổi.\n\nPhần VII' },
    ],
    legend: [
      'Điểm chung: cổng nhận là nhận thật, không có đường rút lại. Mọi việc sửa sau đó phải làm theo quy trình nghiệp vụ với cơ quan bảo hiểm.',
      'Điểm khác nhau lớn nhất: chứng từ Phụ lục 02 gửi hai lần thì cổng ghi nhận thành hai chứng từ — không tự nhận ra bản trùng.',
      'Danh mục ở Phần IV là bản BHXH ban hành (nhập vào để đối chiếu); danh mục ở Phần VII là bản cơ sở tự khai (phải gửi lên và được chấp nhận).',
    ],
    speaker: 'Slide định hướng cả buổi. Nhấn ngay sự khác biệt về chống trùng — đó là lý do toàn bộ màn chứng từ điện tử được thiết kế theo hướng "thà không gửi còn hơn gửi trùng".',
  });

  // ------------------------------------------------------------ Chương 1: XML 3176
  L.sectionSlide(pptx, ctx, {
    no: 1,
    title: 'Hồ sơ XML 3176',
    sub: 'Sáu bước vòng đời · Màn Danh sách hồ sơ · Điều kiện xuất và gửi · Dashboard lỗi · Danh mục mã lỗi',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 1.1',
    title: 'Vòng đời một hồ sơ XML 3176 — sáu bước',
    head: ['Bước', 'Tên bước', 'Ai thực hiện', 'Mô tả'],
    colW: [0.7, 2.0, 2.0, 5.3],
    rows: [
      ['1', 'Nạp hồ sơ', 'Người dùng hoặc hệ thống', 'Tải tệp XML lên bằng tay, hoặc để hệ thống tự quét thư mục và nhập khẩu'],
      ['2', 'Tách hồ sơ', 'Hệ thống', 'Giải mã gói FILEHOSO, tách thành các bảng XML1 đến XML15 theo từng mã điều trị'],
      ['3', 'Kiểm tra tự động', 'Hệ thống (chạy nền)', 'Chạy bộ kiểm lỗi cho từng loại XML, tra cứu thẻ BHYT, đối chiếu chéo giữa các bảng'],
      ['4', { t: 'Xem và xử lý lỗi', b: true }, { t: 'Người dùng', b: true }, { t: 'Lọc hồ sơ có lỗi, xem chi tiết, phối hợp khoa phòng sửa dữ liệu gốc trên HIS', b: true }],
      ['5', 'Ký số – Xuất – Gửi', 'Hệ thống (chạy nền)', 'Hồ sơ hết lỗi nghiêm trọng được ký số, xuất tệp XML và gửi lên cổng'],
      ['6', 'Tra cứu kết quả', 'Người dùng', 'Xem cột Exp, Sub, Ký XML và thông điệp phản hồi của cổng'],
    ],
    note: { label: 'Rất quan trọng:', text: 'Bước 5 diễn ra HOÀN TOÀN TỰ ĐỘNG. Nút "Xuất XML3176" trên màn danh sách CHỈ tải tệp ZIP về máy để đối chiếu, KHÔNG gửi hồ sơ lên cổng. Đừng nhầm hai việc này.', kind: 'danger' },
    speaker: 'Hiểu nhầm về nút Xuất XML3176 là hiểu nhầm phổ biến nhất của cả module. Hỏi lại cả phòng để chắc chắn ai cũng nghe rõ.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 1.2',
    title: 'Nhập khẩu hồ sơ thủ công',
    intro: 'Đường dẫn: Hồ sơ XML → Xml 3176 → Nhập khẩu hồ sơ',
    steps: [
      ['Mở màn Nhập khẩu hồ sơ', 'Hiện khung kéo thả tệp và một bảng trống bên dưới'],
      ['Kéo một hoặc nhiều tệp .xml thả vào khung, hoặc bấm vào khung để chọn tệp', 'Mỗi tệp một dòng, ba cột: Tên tệp, Kích thước, Trạng thái'],
      ['Chờ cột Trạng thái của tất cả các dòng chuyển sang hoàn tất', 'Hộp thoại "Thành công! Đã hoàn thành việc tải lên hồ sơ!"'],
      ['Chuyển sang màn Danh sách hồ sơ để xem kết quả kiểm tra', 'Kết quả kiểm lỗi hiện dần trong vài phút'],
    ],
    note: { label: 'Giới hạn cần biết:', text: 'Chỉ nhận tệp .xml, tối đa 100 MB và 5 phút chờ mỗi tệp. Một tệp chứa nhiều hồ sơ: nếu một hồ sơ hỏng, hệ thống báo rõ "Hồ sơ thứ mấy" và vẫn nhận các hồ sơ còn lại. Tệp rất lớn nên tải vào giờ thấp điểm.' },
    speaker: 'Nhắc: đóng trình duyệt khi đang tải sẽ có hộp hỏi "Bạn có muốn rời trang không?" — chọn ở lại.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 1.3',
    title: 'Đường tự động: hệ thống tự quét thư mục',
    bullets: [
      { t: 'Một tiến trình chạy thường trực trên máy chủ quét hai thư mục được cấu hình sẵn', b: true,
        sub: 'Hai luồng hồ sơ: luồng thanh toán và luồng thường. Nghỉ khoảng 3 giây giữa hai vòng quét' },
      { t: 'Tệp hợp lệ được tách và đưa vào hàng đợi kiểm lỗi giống hệt đường tải lên bằng tay', b: true,
        sub: 'Tệp gốc được dọn khỏi thư mục theo dõi sau khi xử lý xong' },
      { t: 'Tệp không đọc được bị chuyển vào thư mục con "loi" và nằm lại đó cho tới khi có người xử lý', b: true, color: 'C62828' },
      { t: 'Người dùng cần làm ba việc: đặt tệp vào đúng thư mục → chờ một phút → mở Danh sách hồ sơ lọc Ngày tạo = hôm nay', b: true },
      { t: 'Hồ sơ không xuất hiện thì mở thư mục con "loi" kiểm tra — tệp nằm ở đó là hỏng cấu trúc', b: true },
    ],
    note: { label: 'Không có thông báo nào trên màn hình:', text: 'Đường tự động im lặng hoàn toàn. Cách duy nhất biết kết quả là kiểm tra danh sách hồ sơ và thư mục "loi". Thư mục theo dõi ứ đọng nhiều tệp không giảm → rất có thể dịch vụ nền đã dừng, báo ngay công nghệ thông tin.' },
    speaker: 'Nhấn tính im lặng của đường tự động. Đề nghị phòng ban đặt lịch kiểm tra thư mục "loi" hằng tuần.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'Mục 1.4',
    title: 'Màn hình Danh sách hồ sơ — màn làm việc chính',
    shot: 3,
    caption: 'Toàn màn Hồ sơ XML → Xml 3176 → Danh sách hồ sơ.\nChụp đủ: hàng bộ lọc, thanh nút, vài dòng bảng có dòng tô đỏ, và biểu tượng tiến độ ở góc dưới phải.',
    points: [
      'Nháy ĐÚP vào một dòng để mở Chi tiết hồ sơ (các tab XML1–XML15, Thẻ BHYT, Lỗi XML)',
      'Dòng có lỗi được tô nền đỏ',
      'Góc dưới phải: biểu tượng quay tròn kèm con số = số việc còn trong hàng đợi kiểm tra, tự cập nhật mỗi 5 giây',
      'Con số về 0 = đã kiểm xong. Đứng yên rất lâu = báo công nghệ thông tin',
      'Cột Sub có biểu tượng sao chép để chép nguyên văn thông điệp lỗi gửi cho bộ phận hỗ trợ',
    ],
    note: { label: 'Phạm vi xem:', text: 'Tài khoản thông thường CHỈ nhìn thấy hồ sơ do chính mình nhập khẩu. Chỉ tài khoản quản trị mới thấy toàn bộ hồ sơ của đơn vị. Đồng nghiệp nhập hồ sơ mà mình không thấy — đây là nguyên nhân thường gặp nhất.' },
    speaker: 'Phần "phạm vi xem" giải thích được rất nhiều cuộc gọi hỗ trợ. Nhấn mạnh.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 1.4.2',
    title: 'Bộ lọc dùng nhiều nhất',
    intro: 'Màn hình có 14 ô lọc. Sáu ô dưới đây giải quyết hầu hết công việc hằng ngày.',
    head: ['Ô lọc', 'Giá trị nên dùng', 'Dùng khi nào'],
    colW: [2.2, 3.4, 4.4],
    rows: [
      [{ t: 'Lọc theo', b: true }, 'Ngày ra hoặc Ngày thanh toán', 'Quyết định khoảng thời gian áp cho loại ngày nào. Đối chiếu quyết toán thường dùng hai loại này'],
      [{ t: 'Lọc hồ sơ', b: true }, '"Lỗi critical"', 'Ưu tiên xử lý hồ sơ đang bị CHẶN xuất — đây là nhóm làm treo chi phí'],
      [{ t: 'Trạng thái gửi XML', b: true }, '"Chưa gửi" / "Gửi có lỗi"', 'Rà soát hồ sơ tồn chưa lên được cổng'],
      [{ t: 'Trạng thái ký XML', b: true }, '"Ký có lỗi"', 'Rà soát sự cố chữ ký số'],
      [{ t: 'Cơ sở KCB', b: true }, 'Chọn đúng cơ sở', 'BẮT BUỘC chọn đúng khi chuẩn bị số liệu của một cơ sở cụ thể'],
      ['Mã điều trị', 'Một mã cụ thể', 'Lưu ý: khi đã nhập ô này, MỌI bộ lọc khác bị bỏ qua'],
    ],
    note: { label: 'Về nhãn cũ:', text: 'Ô lọc "Trạng thái" vẫn dùng nhãn "Đã Export 4750 / Chưa Export 4750". Trên màn XML 3176 hãy hiểu là "Đã xuất tệp XML / Chưa xuất tệp XML" — nhãn còn sót từ phiên bản trước, không liên quan tới menu Xml 4750.' },
    speaker: 'Nhấn bẫy của ô Mã điều trị: nhập vào là mọi bộ lọc khác bị vô hiệu. Nhiều người tưởng đang lọc theo cơ sở mà thực ra không.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 1.6',
    title: 'Vì sao một hồ sơ chưa được gửi lên cổng',
    bullets: [
      { t: 'Điều kiện để XUẤT tệp XML — cả ba phải đủ:', b: true, color: '1F4E79' },
      { t: 'Chức năng tự động xuất đang được bật trong cấu hình hệ thống', sub: 'Việc của công nghệ thông tin' },
      { t: 'Hồ sơ không còn bất kỳ lỗi nào ở mức NGHIÊM TRỌNG', sub: 'Việc của phòng ban + khoa: lọc "Lỗi critical", sửa dữ liệu gốc trên HIS rồi nạp lại' },
      { t: 'Ngày ra viện của hồ sơ không nằm ở tương lai', sub: 'Thường do HIS ghi sai ngày ra viện' },
      { t: 'Điều kiện để GỬI lên cổng — cả hai phải đủ:', b: true, color: '1F4E79' },
      { t: 'Chức năng tự động gửi đang bật trong cấu hình của cơ sở — mặc định TẮT, phải yêu cầu bật' },
      { t: 'Hồ sơ đã được ký số thành công', sub: 'Chưa ký thì tooltip cột Sub ghi rõ "Hồ sơ chưa ký số, không gửi lên cổng BHXH"' },
    ],
    note: { label: 'Khi gửi thất bại vì lỗi mạng:', text: 'Hệ thống tự thử lại tối đa ba lần trước khi ghi nhận lỗi. Sau khi sửa dữ liệu gốc trên HIS và nạp lại, hồ sơ được kiểm tra lại và gửi lại tự động khi đã hợp lệ.' },
    speaker: 'Đây là slide trả lời câu hỏi hay gặp nhất của phòng BHYT. Dạy họ đi lần lượt 5 điều kiện thay vì gọi điện hỏi ngay.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 1.7',
    title: 'Dashboard lỗi XML — năm chỉ số và bốn biểu đồ',
    head: ['Khối', 'Đọc như thế nào'],
    colW: [3.0, 7.0],
    rows: [
      [{ t: 'Tổng hồ sơ', b: true }, 'Số hồ sơ đã nhập khẩu trong kỳ. So với số liệu HIS để phát hiện hồ sơ chưa nạp'],
      [{ t: 'Lỗi nghiêm trọng', b: true, color: 'C62828' }, 'Số hồ sơ đang bị chặn xuất. Đây là chỉ số cần đưa về 0 TRƯỚC kỳ quyết toán'],
      [{ t: 'Lỗi thẻ BHYT', b: true }, 'Số hồ sơ có kết quả tra thẻ bất thường. Thường phải phối hợp với tiếp đón để sửa'],
      [{ t: 'Chi phí BHYT bị treo', b: true, color: 'C62828' }, 'Tổng chi phí của các hồ sơ chưa gửi được — con số thể hiện rủi ro tài chính của tồn đọng'],
      [{ t: 'Phễu xử lý hồ sơ', b: true }, 'Năm bậc: Đã import → Không lỗi nghiêm trọng → Đã xuất XML → Đã ký số → Đã gửi. Bậc nào tụt mạnh là điểm nghẽn'],
      [{ t: 'Top 15 mã lỗi (Pareto)', b: true }, 'Xử lý vài mã đầu bảng thường giải quyết được phần lớn hồ sơ lỗi'],
      [{ t: 'Tồn đọng theo tuổi hồ sơ', b: true }, 'Nhóm 0–7, 8–15, 16–30 và trên 30 ngày. Nhóm trên 30 ngày xử lý trước'],
      [{ t: 'Lỗi nghiêm trọng theo khoa', b: true }, 'Phân bổ theo khoa thực hiện — dùng để giao việc cho đúng đầu mối'],
    ],
    fontSize: 11.5,
    note: { label: 'Mẹo:', text: 'Bấm vào một cột hoặc một phần của biểu đồ sẽ chuyển sang màn Danh sách hồ sơ với bộ lọc tương ứng đã áp sẵn.', kind: 'ok' },
    speaker: 'Dashboard là công cụ họp giao ban, không phải màn làm việc. Gợi ý: mỗi tuần chụp lại phễu để thấy điểm nghẽn dịch chuyển.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 1.8',
    title: 'Danh mục mã lỗi XML 3176 — nơi quyết định lỗi nào chặn hồ sơ',
    bullets: [
      { t: 'Đường dẫn: Quản lý danh mục → BHYT → DM lỗi Xml 3176', b: true },
      { t: 'Cột "Nghiêm trọng": bật = hồ sơ còn lỗi này sẽ KHÔNG được xuất và không được gửi. Tắt = chỉ cảnh báo', b: true, color: 'C62828' },
      { t: 'Cột "Có kiểm tra": bật = hệ thống vẫn kiểm mã lỗi này. Tắt = ngừng kiểm và ngừng ghi nhận từ lần chạy sau', b: true },
      { t: 'Thay đổi có hiệu lực với các lần kiểm SAU, không hồi tố — muốn xoá lỗi cũ phải nạp lại hồ sơ', b: true },
      { t: 'Mã lỗi mới phát sinh mà chưa có trong danh mục sẽ được mặc định coi là NGHIÊM TRỌNG', b: true, color: 'C62828',
        sub: 'Hệ quả: hồ sơ có thể bị chặn xuất hàng loạt mà không rõ nguyên nhân' },
    ],
    note: { label: 'Việc phải làm sau mỗi lần nâng cấp phần mềm:', text: 'Rà lại màn hình này để đặt đúng mức độ cho các mã lỗi mới. Riêng mã lỗi "mã xã không thuộc tỉnh" được nạp ở trạng thái TẮT sẵn — chỉ bật sau khi danh mục đơn vị hành chính đã chuyển sang hai cấp và đã rà thử một lô hồ sơ.', kind: 'danger' },
    speaker: 'Đây là màn hình quyền lực nhất của module — một ô tích sai có thể chặn hàng nghìn hồ sơ. Đề nghị đơn vị quy định ai được đụng vào.',
  });

  // ------------------------------------------------------------ Chương 2: Thẻ BHYT
  L.sectionSlide(pptx, ctx, {
    no: 2,
    title: 'Thẻ BHYT ở quy mô phòng ban',
    sub: 'Kết quả tra cứu tự động hằng ngày · Tra cứu hàng loạt trước kỳ quyết toán',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 3.3',
    title: 'Kết quả tra cứu thẻ tự động',
    bullets: [
      { t: 'Đường dẫn: Hồ sơ XML → Kết quả tra cứu thẻ', b: true },
      { t: 'Hằng ngày hệ thống tự quét danh sách bệnh nhân đang điều trị có số thẻ, gửi yêu cầu lên cổng và lưu kết quả', b: true },
      { t: 'Hồ sơ đã tra cứu hợp lệ trước đó được bỏ qua để không gọi cổng nhiều lần không cần thiết', b: true },
      { t: 'Thẻ do BHXH Bộ Quốc phòng và Bộ Công an quản lý được bỏ qua theo cấu hình — cổng không trả kết quả cho nhóm này', b: true },
      { t: 'Mỗi mã điều trị giữ MỘT bản ghi mới nhất', b: true },
      { t: 'Thao tác hằng ngày: chọn khoảng ngày → Trạng thái = "Chỉ lỗi" → chọn Cơ sở KCB → Xuất Excel gửi khoa phòng', b: true, color: '1F4E79',
        sub: 'Tệp xuất gồm 26 cột, đúng phạm vi bộ lọc' },
    ],
    note: { label: 'Khi nhiều ngày không có dữ liệu mới:', text: 'Tiến trình tra cứu tự động đã dừng, hoặc không xác định được cơ sở cho các hồ sơ. Báo công nghệ thông tin kiểm tra dịch vụ nền và việc khai mã cơ sở cho các khoa/chi nhánh.' },
    speaker: 'Nhấn quy trình hằng ngày ở gạch đầu dòng cuối — đó là phần việc lặp lại của phòng ban.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 3.4',
    title: 'Tra cứu hàng loạt theo hồ sơ XML',
    intro: 'Dùng trước kỳ quyết toán, khi cần tra lại thẻ cho một nhóm hồ sơ đã có trong hệ thống.',
    steps: [
      ['Đặt bộ lọc: khoảng ngày ra viện, loại khám chữa bệnh, mã thẻ hoặc khoa', 'Xác định phạm vi hồ sơ cần tra cứu'],
      ['Xác nhận thực hiện', 'Toàn bộ hồ sơ trong phạm vi được xếp vào hàng đợi tra cứu'],
      ['Chờ hàng đợi xử lý, sau đó mở màn Kết quả tra cứu thẻ', 'Kết quả mới xuất hiện với thời điểm tra cứu là hiện tại'],
    ],
    note: { label: 'Cảnh báo về phạm vi:', text: 'Mỗi lần tra cứu là một lần gọi lên cổng BHXH. KHÔNG chọn phạm vi quá rộng (ví dụ cả năm) vì sẽ chiếm hàng đợi rất lâu và có thể bị cổng hạn chế truy cập. Chia theo tháng hoặc theo khoa.', kind: 'danger' },
    speaker: 'Kể tình huống thực tế: chọn cả năm rồi hàng đợi tắc một ngày, làm mọi chức năng tra thẻ khác đứng theo.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 3.5',
    title: 'Mã tra cứu do cổng trả về — nhóm hay gặp',
    intro: 'Chỉ mã 000 là hợp lệ. Bảng đầy đủ 23 mã nằm ở mục 3.5 của tài liệu.',
    head: ['Mã', 'Ý nghĩa', 'Hướng xử lý'],
    colW: [0.9, 3.6, 5.5],
    rows: [
      [{ t: '000', b: true, color: '2E7D32' }, 'Thông tin thẻ chính xác', 'Không cần làm gì'],
      [{ t: '003 / 004', b: true }, 'Thẻ cũ đã hết giá trị / còn giá trị, đã được cấp thẻ mới', 'Cập nhật số thẻ mới vào HIS theo thông tin cổng trả về'],
      [{ t: '010', b: true, color: 'C62828' }, 'Thẻ hết giá trị sử dụng', 'Đề nghị người bệnh liên hệ cơ quan BHXH gia hạn. Xác định lại đối tượng thanh toán'],
      [{ t: '050 / 051', b: true }, 'Không có thông tin thẻ / Mã thẻ không đúng', 'Kiểm tra lại số thẻ nhập vào, nhập đúng 15 ký tự'],
      [{ t: '060 / 070', b: true }, 'Sai họ tên / Sai ngày sinh', 'Sửa theo đúng thẻ, chú ý dấu tiếng Việt và năm sinh'],
      [{ t: '110', b: true, color: 'C62828' }, 'Thẻ đã bị thu hồi', 'Xác định lại đối tượng thanh toán, không thanh toán BHYT'],
      [{ t: '054 / 055', b: true, color: 'B26A00' }, 'CCCD hoặc họ tên cán bộ tra cứu không khớp đăng ký của cơ sở', 'Báo công nghệ thông tin cập nhật thông tin cán bộ tra cứu, hoặc đăng ký bổ sung với cơ quan BHXH'],
      [{ t: '401', b: true, color: 'B26A00' }, 'Lỗi xác thực tài khoản', 'Tài khoản cổng của cơ sở sai hoặc hết hạn — báo công nghệ thông tin'],
    ],
    fontSize: 11.5,
    speaker: 'Chia nhóm: 000 là xong; 003/004/060/070 là sửa trên HIS; 010/110 là làm việc với người bệnh; 054/055/401 là việc của CNTT. Bốn nhóm, dễ nhớ hơn 23 mã.',
  });

  // ------------------------------------------------------------ Chương 3: Danh mục
  L.sectionSlide(pptx, ctx, {
    no: 3,
    title: 'Quản lý danh mục BHYT',
    sub: 'Mười một bộ danh mục · Nguyên tắc chỉ đọc · Nhập khẩu và đọc kết quả · Bốn bộ theo cơ sở',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 4.1 – 4.2',
    title: 'Vì sao danh mục là việc ưu tiên',
    bullets: [
      { t: 'Danh mục là nền tảng đối chiếu của toàn bộ hai chức năng XML 3176 và kiểm tra y lệnh', b: true },
      { t: 'Danh mục sai hoặc thiếu → hồ sơ XML báo lỗi hàng loạt và bộ kiểm y lệnh sinh hàng chục nghìn vi phạm giả', b: true, color: 'C62828' },
      { t: 'Mười một bộ danh mục, trong đó BỐN bộ phân biệt theo cơ sở', b: true,
        sub: 'Theo cơ sở: Thuốc · Vật tư y tế · Dịch vụ kỹ thuật · Khoa Phòng Giường' },
      { t: 'Bảy bộ dùng chung: ICD-10 · ICD-YHCT · Nhân viên y tế · Trang thiết bị · Đơn vị hành chính · Cơ sở KCB · Nghề nghiệp', b: true },
      { t: 'Cả mười một màn hình danh mục đều CHỈ ĐỌC — không có nút Thêm/Sửa/Xoá từng dòng', b: true, color: '1F4E79',
        sub: 'Chủ ý thiết kế: nguồn chuẩn là tệp do BHXH phát hành. Cách cập nhật duy nhất là nhập lại tệp Excel' },
    ],
    note: { label: 'Hai cột quyết định tất cả:', text: 'TU_NGAY và DEN_NGAY quyết định danh mục còn hiệu lực hay không. Các quy tắc đối chiếu chỉ chấp nhận dòng còn hiệu lực tại thời điểm phát sinh y lệnh. Nhập danh mục có ngày hiệu lực sai sẽ sinh hàng loạt cảnh báo "mã không có trong danh mục" dù dữ liệu đã nhập.', kind: 'danger' },
    speaker: 'Đặt câu hỏi mở đầu: đơn vị mình cập nhật danh mục bao lâu một lần, ai làm? Câu trả lời thường cho thấy đây là việc không có chủ.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 4.3',
    title: 'Quy trình nhập khẩu danh mục',
    intro: 'Đường dẫn: Quản lý danh mục → Nhập khẩu danh mục — cửa duy nhất để cập nhật cả mười một bộ',
    steps: [
      ['Tải tệp danh mục mới từ cổng giám định BHXH về máy', 'Có tệp Excel nguồn'],
      ['Kiểm tra DÒNG 1 phải là dòng tiêu đề cột — phía trên còn dòng tiêu đề chung thì xoá đi', 'Dữ liệu bắt đầu từ dòng 2. Đây là lỗi phổ biến nhất với tệp tải trực tiếp từ cổng'],
      ['Chưa chắc về cấu trúc: chọn loại danh mục ở khu Tải biểu mẫu rồi bấm Tải biểu mẫu', 'Tệp mẫu rỗng có đúng tên cột. Cột nền vàng là bắt buộc, cột nền xanh là MA_CSKCB không bắt buộc'],
      ['Chọn Cơ sở khám chữa bệnh TRƯỚC khi kéo tệp vào', 'Chỉ có ý nghĩa với bốn bộ danh mục theo cơ sở — xem slide sau'],
      ['Kéo tệp thả vào khung, chờ xử lý xong', 'Hộp thoại kết quả với năm con số và 20 dòng lỗi đầu tiên'],
      ['Có dòng lỗi thì bấm nút Chi tiết (n) để tải tệp Excel ba cột: Dòng Excel, Loại, Lý do', 'Sửa tệp nguồn rồi nhập lại — nhập lại cùng tệp là AN TOÀN, không nhân đôi dữ liệu'],
    ],
    speaker: 'Bước 2 là chỗ mất thời gian nhất của mọi đơn vị. Tệp tải thẳng từ cổng thường có tiêu đề ở dòng 5. Có ghi chú riêng về việc này trong tài liệu.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 4.3.2',
    title: 'Đọc năm con số kết quả nhập khẩu',
    head: ['Con số', 'Ý nghĩa', 'Phải làm gì'],
    colW: [1.6, 4.4, 4.0],
    rows: [
      [{ t: 'Đã thêm', b: true, color: '2E7D32' }, 'Dòng chưa từng có trong hệ thống, được thêm mới', 'Không cần làm gì'],
      [{ t: 'Cập nhật', b: true }, 'Dòng đã có nhưng nội dung khác, được ghi đè bằng dữ liệu mới', 'Không cần làm gì'],
      [{ t: 'Không đổi', b: true }, 'Dòng đã có và giống hệt, hệ thống bỏ qua', 'Bình thường khi nhập lại tệp cũ. Con số này lớn không phải là lỗi'],
      [{ t: 'Bỏ qua', b: true, color: 'B26A00' }, 'Dòng thiếu ít nhất một cột bắt buộc — gồm cả dòng trống ở cuối trang tính', 'Tải tệp Chi tiết, xem là dòng trống hay dòng dữ liệu thật'],
      [{ t: 'Lỗi', b: true, color: 'C62828' }, 'Dòng không ghi được, thường do ô số chứa ký tự lạ hoặc dữ liệu quá dài', 'Bắt buộc tải tệp Chi tiết, sửa tệp nguồn rồi nhập lại'],
    ],
    note: { label: 'Giới hạn kỹ thuật:', text: 'Chỉ nhận .xls/.xlsx tối đa 10 MB. Chỉ đọc trang tính ĐẦU TIÊN. Không cần chọn loại danh mục — hệ thống tự nhận diện theo tên cột. Thứ tự cột không quan trọng, cột thừa bị bỏ qua. Tệp lớn (ICD ~50.000 dòng) xử lý được nhưng chờ tối đa 30 phút.' },
    speaker: 'Nhấn: "Không đổi" lớn là bình thường, nhiều người tưởng nhập hỏng. "Lỗi" khác 0 là bắt buộc phải mở tệp chi tiết.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 4.5',
    title: 'Bốn bộ theo cơ sở — nơi dễ sai nhất',
    intro: 'Cột MA_CSKCB quyết định dòng thuộc cơ sở nào. Ba tình huống:',
    head: ['Tình huống', 'Kết quả'],
    colW: [4.2, 5.8],
    rows: [
      ['Tệp có cột MA_CSKCB và có giá trị', 'Giá trị TRONG TỆP được dùng, bất kể ô Cơ sở KCB trên màn hình chọn gì'],
      ['Tệp không có cột MA_CSKCB (hoặc ô trống) và trên màn hình đã chọn một cơ sở', 'Hệ thống điền mã cơ sở đã chọn cho những dòng bỏ trống'],
      ['Tệp không có MA_CSKCB và màn hình để "Dùng chung cho mọi cơ sở"', { t: 'Dòng được ghi là dùng chung — có hiệu lực với MỌI cơ sở', b: true, color: 'C62828' }],
    ],
    note: { label: 'Hậu quả của việc quên chọn cơ sở:', text: 'Toàn bộ dữ liệu vào dạng dùng chung và áp cho tất cả các cơ sở — giá dịch vụ của cơ sở này bị áp nhầm cho cơ sở khác. Luôn chọn đúng Cơ sở KCB TRƯỚC khi kéo tệp vào.', kind: 'danger' },
    speaker: 'Nếu đơn vị chỉ có một cơ sở thì bỏ qua nhanh. Nếu nhiều cơ sở/chi nhánh thì dừng lại lâu, đây là lỗi âm thầm và chỉ lộ khi đối chiếu giá.',
  });

  L.splitSlide(pptx, ctx, {
    kicker: 'Mục 4.4.2 và 4.7',
    title: 'Hai thao tác có thể gây hậu quả lớn',
    left: {
      head: 'THAY THẾ TRỌN BỘ — Đơn vị hành chính và Cơ sở KCB',
      items: [
        'Hai bộ này nhập theo kiểu thay thế trọn bộ: toàn bộ dữ liệu cũ bị vô hiệu hoá, chỉ các dòng có trong tệp mới được kích hoạt lại',
        'Nhập một tệp thiếu dữ liệu → những đơn vị không có trong tệp BIẾN MẤT khỏi hệ thống',
        'Luôn nhập tệp đầy đủ của toàn quốc, không nhập tệp đã cắt bớt',
        'Danh mục hành chính hai cấp (từ 2025): 3.321 xã thuộc 34 tỉnh, không còn cấp huyện. Việc chuyển đổi làm MỘT LẦN bằng lệnh riêng, không nhập qua màn hình này',
      ],
    },
    right: {
      head: 'XOÁ TOÀN BỘ MỘT DANH MỤC',
      items: [
        'Nằm ở cuối màn Nhập khẩu danh mục, trong khung viền đỏ, chỉ hiện với tài khoản quản trị cao nhất',
        'Bốn bước: chọn bộ → bấm Đếm số dòng sẽ xoá → gõ chữ XOA (viết hoa, không dấu) → bấm Xoá',
        'CHỈ xoá khi tệp thay thế đã sẵn sàng trong tay',
        'Hậu quả thực tế đã ghi nhận trong lúc danh mục trống: hồ sơ XML báo lỗi hàng loạt và bộ kiểm y lệnh sinh hơn 36.000 vi phạm giả chỉ trong vài lượt quét',
      ],
    },
    speaker: 'Hai việc này nên có quy trình nội bộ: ai được làm, làm lúc nào (ngoài giờ), ai xác nhận. Đề nghị đơn vị ghi thành quy định.',
  });

  // ------------------------------------------------------------ Chương 4: Chứng từ điện tử
  L.sectionSlide(pptx, ctx, {
    no: 4,
    title: 'Chứng từ điện tử theo Phụ lục 02',
    sub: 'Giấy ra viện, chứng sinh, báo tử, nghỉ hưởng BHXH · Chín trạng thái gửi · Chống gửi trùng',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 6.1 – 6.2',
    title: 'Điểm khác biệt quyết định cách dùng màn hình này',
    bullets: [
      { t: 'Nhận tệp XML do phần mềm nghiệp vụ sinh ra, kiểm tra dữ liệu, ký số, gửi lên cổng và theo dõi kết quả', b: true },
      { t: 'Chứng từ Phụ lục 02 KHÔNG mang mã giao dịch do phần mềm tự sinh khi gửi', b: true, color: 'C62828',
        sub: 'Nghĩa là gửi hai lần thì cổng ghi nhận thành HAI chứng từ — cổng không có căn cứ nhận ra bản trùng' },
      { t: 'Vì vậy toàn bộ màn hình được thiết kế theo hướng "thà không gửi còn hơn gửi trùng"', b: true, color: '1F4E79' },
      { t: 'Ba dịch vụ liên thông, ba đầu mối tiếp nhận khác nhau:', b: true,
        sub: 'Chứng từ TT25/2025 (giấy nghỉ việc hưởng BHXH, giấy ra viện, giấy chuyển tuyến…) · Giấy chứng sinh · Giấy báo tử' },
      { t: 'Quyền cần có: xml-man — cùng quyền với XML 3176', b: true },
    ],
    note: { label: 'Không có đường rút lại:', text: 'Cổng nhận là nhận thật. Chứng từ đã được cổng tiếp nhận thì việc sửa sai phải làm theo quy trình nghiệp vụ với cơ quan bảo hiểm, không phải bằng thao tác trên phần mềm. Đối chiếu kỹ trước khi bấm gửi.', kind: 'danger' },
    speaker: 'Slide bản lề của chương. Mọi hành vi "khó chịu" của màn hình sau này (hỏi xác nhận, không cho tích chọn, trần 50 hồ sơ) đều quay về đúng lý do này.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 6.5',
    title: 'Chín trạng thái gửi — đọc trước tiên khi hồ sơ chưa lên cổng',
    intro: 'Có năm lý do khác nhau khiến hồ sơ chưa được gửi, mỗi lý do cần một người khác nhau xử lý.',
    head: ['Trạng thái', 'Nghĩa là gì', 'Cần làm gì'],
    colW: [2.2, 3.4, 4.4],
    rows: [
      [{ t: 'Chưa kiểm', b: true }, 'Máy chưa kiểm tra dữ liệu của hồ sơ này', 'Chờ vài phút. Cả loạt đứng ở trạng thái này → báo CNTT kiểm tra tiến trình nền'],
      [{ t: 'Còn lỗi chặn', b: true, color: 'C62828' }, 'Đã kiểm và phát hiện lỗi mức chặn gửi', 'Mở chi tiết, xem tab Lỗi, sửa ở phần mềm sinh XML rồi nạp lại'],
      [{ t: 'Ký số thất bại', b: true }, 'Đã thử ký nhưng không ký được', 'Xem dòng "Lỗi ký số" ở màn chi tiết. Thường do thiết bị ký chưa cắm'],
      [{ t: 'Chưa ký số', b: true }, 'Hồ sơ đủ điều kiện nhưng chưa được ký', 'Mở chi tiết và bấm Ký và gửi'],
      [{ t: 'Chức năng gửi đang tắt', b: true }, 'Cấu hình chặn mọi lượt gửi lên cổng', 'Báo quản trị hệ thống bật cấu hình gửi'],
      [{ t: 'Chờ gửi', b: true }, 'Đã ký, đủ điều kiện, đang chờ đến lượt', 'Chờ. Quá lâu thì xem khối Ba hàng đợi trên Dashboard'],
      [{ t: 'Đã gửi', b: true, color: '2E7D32' }, 'Cổng đã tiếp nhận thành công, đã có mã giao dịch', 'Không cần làm gì'],
      [{ t: 'Cổng từ chối', b: true, color: 'C62828' }, 'Cổng đã trả lời nhưng từ chối tiếp nhận', 'Mở chi tiết, đọc mã kết quả và phản hồi của cổng'],
      [{ t: 'Gửi thất bại', b: true }, 'Không gửi được, thường do lỗi mạng', 'Mở chi tiết đọc dòng "Lỗi gửi", gửi lại khi đường truyền ổn định'],
    ],
    fontSize: 11,
    speaker: 'Nhấn: một hồ sơ vừa còn lỗi vừa chưa ký sẽ hiện "Còn lỗi chặn", vì việc phải làm trước là sửa lỗi. Thứ tự hiển thị ưu tiên lý do gần nhất cần xử lý.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'Mục 6.4.2',
    title: 'Bẫy lớn nhất: cột Số lỗi bằng 0',
    shot: 4,
    caption: 'Màn Chứng từ điện tử → Danh sách hồ sơ.\nChụp một hồ sơ có Số lỗi = 0 nhưng Trạng thái gửi = "Chưa kiểm", nếu có thể chụp kèm một dòng đã kiểm để so sánh.',
    points: [
      'Số lỗi = 0 KHÔNG có nghĩa là hồ sơ đã sạch',
      'Nó chỉ có nghĩa đó khi cột Trạng thái gửi KHÁC "Chưa kiểm"',
      'Hồ sơ vừa nạp chưa được kiểm thì đương nhiên chưa ghi nhận lỗi nào — số 0 lúc đó nghĩa là chưa ai nhìn',
      'Luôn đọc HAI cột này cùng nhau',
      'Cột Số lỗi chỉ đếm lỗi mức CHẶN; nhãn tab Lỗi ở màn chi tiết đếm cả cảnh báo nên thường lớn hơn — lệch nhau là bình thường',
    ],
    note: { label: 'Về ba nút xuất Excel:', text: 'Ba nút dùng bộ lọc của lần Tải dữ liệu GẦN NHẤT, không phải bộ lọc đang hiện trên màn hình. Đổi ô lọc mà chưa bấm Tải dữ liệu thì tệp xuất ra vẫn theo bộ lọc cũ — đúng bằng những gì đang hiển thị.' },
    speaker: 'Dừng lâu ở slide này. Đây là chỗ một người có thể báo cáo "sạch hết rồi" trong khi chưa có gì được kiểm.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 6.7.1 và 6.7.5',
    title: 'Ký và gửi — một hồ sơ và nhiều hồ sơ',
    steps: [
      ['Một hồ sơ: mở chi tiết, kiểm tra tab Lỗi không còn lỗi mức chặn, đối chiếu nội dung với chứng từ giấy', 'Xác nhận hồ sơ sạch'],
      ['Bấm Ký và gửi ở góc dưới phải khối thông tin', 'Nút chuyển sang đang xử lý và không bấm lại được. Hồ sơ đã có mã giao dịch thì nút mang tên "Ký và gửi lại"'],
      ['Chờ thông báo, đóng hộp thoại, bấm Tải dữ liệu', 'Cột Trạng thái gửi đổi theo kết quả'],
      ['Nhiều hồ sơ: lọc ra nhóm cần gửi (ví dụ Trạng thái = "Ký số thất bại") rồi bấm Tải dữ liệu', 'Danh sách chỉ còn nhóm cần xử lý'],
      ['Tích ô ở đầu từng dòng, hoặc tích ô trên tiêu đề để chọn hết dòng của TRANG ĐANG XEM', 'Số trên nút đổi theo, ví dụ "Ký và gửi đã chọn (12)"'],
      ['Đọc kỹ con số trong hộp thoại rồi xác nhận', 'Bảng kết quả: số hồ sơ đã xếp hàng + danh sách hồ sơ bị bỏ qua kèm lý do từng cái'],
    ],
    note: { label: 'Tuyệt đối không bấm nút lần nữa trong lúc chờ:', text: 'Việc ký và gửi xếp vào hàng đợi nền. Thông báo chỉ có nghĩa yêu cầu đã được nhận, chưa có nghĩa cổng đã tiếp nhận. Mỗi lần bấm là một chứng từ có thể được ghi nhận thêm trên cổng.', kind: 'danger' },
    speaker: 'Giải thích vì sao ô tích chỉ chọn được TRANG ĐANG XEM: chọn cả trang chưa xem nghĩa là gửi hồ sơ mình chưa từng nhìn. Trần mỗi lượt 50 hồ sơ; chọn quá thì cả lượt bị từ chối chứ không gửi 50 cái đầu.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 6.7.6 và 6.7.4',
    title: 'Hồ sơ nào tích được, hồ sơ nào không',
    intro: 'Chỉ bốn trạng thái có ô tích: Chưa ký số · Ký số thất bại · Gửi thất bại · Chờ gửi.',
    head: ['Trạng thái KHÔNG tích được', 'Vì sao', 'Xử lý thế nào'],
    colW: [2.6, 4.0, 3.4],
    rows: [
      ['Chưa kiểm', 'Máy chưa kiểm dữ liệu', 'Chờ rồi tải lại danh sách'],
      ['Còn lỗi chặn', 'Phải sửa dữ liệu ở phần mềm sinh XML', 'Sửa nguồn rồi nạp lại hồ sơ'],
      ['Chức năng gửi đang tắt', 'Cấu hình chặn mọi lượt gửi', 'Báo quản trị hệ thống'],
      [{ t: 'Đã gửi', b: true }, 'Cổng đã tiếp nhận và cấp mã giao dịch', 'Mở màn CHI TIẾT bấm "Ký và gửi lại" — không gửi hàng loạt được'],
      [{ t: 'Cổng từ chối', b: true }, 'Luôn cần xác nhận riêng', 'Mở màn CHI TIẾT để xử lý từng hồ sơ'],
    ],
    note: { label: 'Vì sao hai trạng thái cuối không gửi hàng loạt được:', text: 'Ở màn chi tiết người bấm đang nhìn đúng hồ sơ đó và phần mềm còn hỏi xác nhận. Trong một lượt 50 dòng thì không ai nhìn từng cái — một lần bấm sẽ gửi lại im lặng những hồ sơ cổng đã nhận, mỗi cái là một chứng từ trùng trên cổng.', kind: 'danger' },
    speaker: 'Đây là ví dụ tốt về "phần mềm cố tình làm khó". Giải thích lý do thì người dùng chấp nhận, không giải thích thì họ coi là lỗi.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 6.8',
    title: 'Mã lỗi chứng từ điện tử và trường bắt buộc của giấy ra viện',
    head: ['Mã lỗi', 'Mô tả', 'Mức độ'],
    colW: [1.4, 6.6, 2.0],
    rows: [
      ['CTDT001', 'Thiếu trường bắt buộc', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT002', 'Trường ngày sai định dạng', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT003', 'Giới tính ngoài giá trị cho phép', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT004', 'Loại giấy tờ ngoài giá trị cho phép', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT005', 'Trường cờ ngoài giá trị 0 hoặc 1', { t: 'Cảnh báo', color: 'B26A00' }],
      ['CTDT006', 'Ngày kết thúc sớm hơn ngày bắt đầu', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT007', 'Mã cơ sở trong chứng từ lệch với mã cơ sở của hồ sơ', { t: 'Chặn', b: true, color: 'C62828' }],
      ['CTDT008', 'Thiếu mã thẻ bảo hiểm y tế', { t: 'Cảnh báo', color: 'B26A00' }],
    ],
    note: { label: 'Giấy ra viện — trường bắt buộc:', text: 'Người bệnh: Mã BHXH, Họ tên, Ngày sinh, Giới tính, Địa chỉ, Nghề nghiệp, Loại giấy tờ. Đợt điều trị: Mã khoa, Ngày vào, Ngày ra, Chẩn đoán, Mã và Tên bệnh ICD-10, Phương pháp điều trị. Chứng từ: Ngày chứng từ, Thủ trưởng đơn vị, Tên trưởng khoa, Mã chứng chỉ hành nghề của trưởng khoa.' },
    speaker: 'Nhấn: Phương pháp điều trị (PP_DIEUTRI) mới thành bắt buộc từ 07/09/2026 sau khi cổng từ chối một hồ sơ. Khoảng 7% giấy ra viện đang bỏ trống trường này — sẽ chuyển sang "Còn lỗi chặn" sau khi nạp lại. Luật mới chỉ áp cho hồ sơ nạp từ thời điểm nâng cấp trở đi.',
  });

  // ------------------------------------------------------------ Chương 5: TT12
  L.sectionSlide(pptx, ctx, {
    no: 5,
    title: 'Danh mục theo Thông tư 12/2026',
    sub: 'Sáu mẫu cơ sở tự khai · Nạp và gửi · Đồng bộ sang bộ danh mục · Dashboard độ phủ',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 7.1 và 7.3',
    title: 'Sáu mẫu danh mục và nơi chúng đi tới',
    intro: 'Đây là danh mục cơ sở TỰ KHAI về năng lực của mình, khác với danh mục BHXH ban hành ở Phần IV. Mỗi mẫu là một tệp Excel riêng, gửi tới một đầu mối riêng — không gộp nhiều mẫu vào một tệp.',
    head: ['Mẫu', 'Nội dung khai báo', 'Ghi sang bộ danh mục'],
    colW: [1.4, 5.8, 2.8],
    rows: [
      [{ t: 'Mẫu 01/DM', b: true }, 'Bộ phận chuyên môn KCB BHYT: khoa phòng, số bàn khám, số giường theo từng loại', 'Danh mục Khoa phòng'],
      [{ t: 'Mẫu 02/DM', b: true }, 'Nhân lực thực hiện KCB BHYT: người hành nghề, chứng chỉ, phạm vi hoạt động', 'Danh mục Nhân viên y tế'],
      [{ t: 'Mẫu 03/DM', b: true }, 'Thuốc, máu, chế phẩm máu', 'Danh mục Thuốc'],
      [{ t: 'Mẫu 04/DM', b: true }, 'Thiết bị y tế, tức vật tư y tế', 'Danh mục Vật tư y tế'],
      [{ t: 'Mẫu 05/DM', b: true }, 'Dịch vụ khám bệnh, chữa bệnh, kèm bảng con thuốc và vật tư đi theo dịch vụ', 'Danh mục Dịch vụ kỹ thuật'],
      [{ t: 'Mẫu 06/DM', b: true }, 'Thiết bị y tế thực hiện dịch vụ kỹ thuật', 'Danh mục Thiết bị'],
    ],
    note: { label: 'Chỉ khi cổng chấp nhận thì dữ liệu mới có hiệu lực:', text: 'Cổng trả về mã tiếp nhận thành công thì phần mềm mới ghi sang bộ danh mục. Đây là chủ ý: danh mục dùng để kiểm hồ sơ XML 3176 phải đúng bằng bản mà cơ quan bảo hiểm đã nhận, vì khi giám định họ sẽ so với chính bản đó.' },
    speaker: 'Nhấn mối liên hệ ngược về Phần I: gửi TT12 xong là danh mục dùng để kiểm XML 3176 cũng đổi theo. Hai việc không tách rời.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 7.4',
    title: 'Nạp danh mục TT12 vào phần mềm',
    intro: 'Chỉ có MỘT đường đưa dữ liệu vào: màn hình Nạp danh mục. Không có tiến trình nền quét thư mục.',
    steps: [
      ['Mở Hồ sơ XML → Danh mục TT12 → Nạp danh mục, chọn Mẫu rồi bấm Tải biểu mẫu', 'Tải về tệp Excel rỗng có sẵn dòng tiêu đề đúng của mẫu đã chọn'],
      ['Điền dữ liệu vào biểu mẫu — KHÔNG đổi tên cột, không chèn thêm cột, không chèn dòng trống phía trên tiêu đề', 'Phần mềm nhận diện mẫu bằng chính dòng tiêu đề'],
      ['Chọn Cơ sở khám chữa bệnh — đây là ô BẮT BUỘC', 'Mã cơ sở này quyết định tài khoản dùng để gửi lên cổng'],
      ['Kéo tệp Excel vào khung; có thể kéo nhiều tệp một lượt', 'Mỗi tệp trở thành một hồ sơ riêng'],
      ['Chờ bảng kết quả nạp; sau đó mở Danh sách hồ sơ xem kết quả kiểm lỗi', 'Mỗi dòng cho biết Thành công hay Thất bại, kèm mã hồ sơ và số dòng đọc được'],
    ],
    note: { label: 'Quy tắc khớp mã cơ sở rất chặt:', text: 'Phần mềm đối chiếu TOÀN BỘ giá trị cột MA_CSKCB trong tệp với mã cơ sở đã chọn. Chỉ cần MỘT dòng lệch là cả tệp bị từ chối, không nạp một phần. Lý do: mã cơ sở quyết định tài khoản gửi lên cổng — lệch nhau thì cổng vẫn nhận và danh mục bị ghi sang nhầm đơn vị, hỏng lặng lẽ.', kind: 'danger' },
    speaker: 'Nhấn: luôn tải biểu mẫu từ phần mềm thay vì tự tạo tệp. Sửa một tên cột là bị từ chối ngay khi nạp.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 7.7 – 7.9',
    title: 'Ký, gửi, đồng bộ và hai nút cứu hộ',
    bullets: [
      { t: 'Bấm "Ký và gửi" MỘT lần là phần mềm chạy trọn cả hai bước: ký số trước, ký xong tự gửi', b: true },
      { t: 'Hai bước chạy ở hai hàng đợi riêng nên kết quả xuất hiện dần: cột Đã ký đổi trước, lát sau mới có Mã giao dịch', b: true,
        sub: 'Bấm xong không thấy gì đổi ngay là bình thường — chờ vài giây rồi bấm Tải dữ liệu' },
      { t: 'Bấm lại khi lượt trước còn chạy: phần mềm báo "đang xử lý ở một lượt khác" — đây là chốt chống gửi trùng', b: true },
      { t: 'Gửi nhiều: tích chọn rồi bấm "Ký và gửi đã chọn", tối đa 50 hồ sơ mỗi lượt', b: true,
        sub: 'Đọc kỹ danh sách hồ sơ BỊ BỎ QUA trong bảng kết quả — đó chính là những hồ sơ chưa đi' },
      { t: 'Cổng trả mã 200 → phần mềm tự ghi toàn bộ dòng sang bộ danh mục, cột "Đã đồng bộ" chuyển trạng thái', b: true, color: '2E7D32' },
      { t: 'Hai nút cứu hộ (chỉ tài khoản quản trị cao nhất, chỉ hiện đúng lúc cần):', b: true, color: '1F4E79',
        sub: '"Kiểm lại" khi hồ sơ kẹt ở chưa kiểm · "Đồng bộ lại danh mục" khi cổng đã tiếp nhận nhưng cột Đã đồng bộ vẫn trống — KHÔNG gửi lại lên cổng' },
    ],
    note: { label: 'Mã kết quả cổng trả về:', text: '200 = tiếp nhận thành công · 123/124/125/202/204/205 = lỗi nội dung tệp, sửa Excel rồi nạp lại thành hồ sơ mới · 401/402/403 = mã cơ sở, tài khoản hoặc quyền, báo CNTT · 500 = lỗi phía cổng, phần mềm tự thử lại tối đa ba lần.' },
    speaker: 'Nút "Đồng bộ lại danh mục" là đường thoát cho một trạng thái hiếm nhưng gây hoang mang: đã gửi thành công mà danh mục chưa đổi. Nhấn: KHÔNG bấm gửi lại trong tình huống đó.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 7.10bis và 7.12',
    title: 'Xuất XML kèm chữ ký số và Dashboard độ phủ',
    bullets: [
      { t: 'Xuất XML: tích chọn hồ sơ (dùng chung ô tích với nút Ký và gửi) rồi bấm "Xuất XML đã chọn"', b: true,
        sub: 'Một hồ sơ thì tải thẳng tệp .xml; nhiều hồ sơ thì tải về ZIP. Tối đa 50 hồ sơ mỗi lượt' },
      { t: 'Đọc TÊN TỆP để phân biệt hai loại bản xuất:', b: true, color: '1F4E79',
        sub: '…-da-ky.xml = bản THẬT đã ký và đã gửi, dùng để đối chiếu với cơ quan bảo hiểm · …-chua-ky.xml = bản dựng lại để xem trước, thẻ chữ ký để trống' },
      { t: 'Mỗi ZIP luôn kèm tệp _ke-khai.csv liệt kê từng hồ sơ: mã, mẫu, tình trạng và lý do', b: true,
        sub: 'Thiếu tệp XML trong ZIP thì mở tệp kê là biết ngay vì sao' },
      { t: 'Dashboard độ phủ: lưới 6 hàng (sáu mẫu) × các cột là cơ sở KCB', b: true },
      { t: 'Ô "Đã tiếp nhận" kèm ngày và số dòng của LẦN GỬI GẦN NHẤT; ô "Chưa gửi" gộp cả chưa nộp lẫn bị từ chối', b: true,
        sub: 'Muốn biết là trường hợp nào thì xem khối "Hồ sơ đang dở dang" bên dưới' },
    ],
    note: { label: 'Dashboard không có bộ lọc thời gian — đó là cố ý:', text: 'Câu hỏi "đã được cổng tiếp nhận hay chưa" là câu hỏi trên toàn bộ thời gian; giới hạn theo khoảng ngày sẽ làm một mẫu gửi từ lâu hiện thành chưa gửi. Thông tư 12 cũng không quy định chu kỳ gửi cố định — danh mục chỉ gửi khi có thay đổi.' },
    speaker: 'Dashboard độ phủ là màn hình để trả lời lãnh đạo "đã khai đủ chưa". Bấm vào ô nào sẽ mở Danh sách hồ sơ đã lọc sẵn theo mẫu và cơ sở.',
  });

  // ------------------------------------------------------------ Kết
  L.splitSlide(pptx, ctx, {
    kicker: 'Ranh giới trách nhiệm',
    title: 'Việc của phòng ban và việc phải báo công nghệ thông tin',
    left: {
      head: 'PHÒNG BAN NGHIỆP VỤ tự làm',
      items: [
        'Nạp hồ sơ XML, theo dõi Danh sách hồ sơ, lọc "Lỗi critical" và giao việc cho khoa',
        'Nhập khẩu danh mục BHYT, đọc năm con số kết quả, sửa tệp nguồn và nhập lại',
        'Đặt mức Nghiêm trọng / Có kiểm tra cho từng mã lỗi ở DM lỗi Xml 3176',
        'Ký và gửi chứng từ điện tử, xử lý hồ sơ "Còn lỗi chặn" và "Cổng từ chối"',
        'Nạp, ký và gửi sáu mẫu danh mục TT12; theo dõi Dashboard độ phủ',
        'Xuất Excel gửi khoa phòng đối chiếu; giữ mã giao dịch để đối soát với cơ quan bảo hiểm',
      ],
    },
    right: {
      head: 'BÁO CÔNG NGHỆ THÔNG TIN',
      items: [
        'Hàng đợi đứng: con số ở góc dưới phải không giảm, hoặc khối Ba hàng đợi báo chờ nhiều phút',
        'Cả loạt hồ sơ đứng ở "Chưa kiểm" · hồ sơ đã ký nhưng không bao giờ được gửi',
        'Lỗi ký số: HSM/USB Token, chứng thư số hết hạn',
        'Mã 401 / 403 từ cổng · "Thiếu mã cơ sở KCB" · "Mã tỉnh không được cấu hình"',
        'Bật/tắt cấu hình gửi tự động; đặt tệp DUNG-GUI để dừng khẩn cấp luồng chứng từ',
        'Nạp mã lỗi mới sau nâng cấp; lùi mốc quét để kiểm lại một giai đoạn',
      ],
    },
    speaker: 'In slide này ra dán ở phòng. Mục tiêu: giảm số cuộc gọi sai địa chỉ theo cả hai chiều.',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'Tổng hợp mục 1.9, 4.9, 6.13, 7.11',
    title: 'Năm tình huống gặp nhiều nhất',
    cases: [
      { what: 'Không thể xác định loại danh mục. Vui lòng kiểm tra lại cấu trúc file.', why: 'Dòng 1 không phải dòng tiêu đề cột — phổ biến nhất với tệp tải trực tiếp từ cổng', fix: 'Xoá các dòng phía trên để tiêu đề về dòng 1. Hoặc tải biểu mẫu và dán dữ liệu vào' },
      { what: 'Số lỗi hiện 0 nhưng hồ sơ vẫn không gửi được', why: 'Hồ sơ chưa được kiểm; số 0 chỉ có nghĩa là chưa ai nhìn', fix: 'Đọc cột Trạng thái gửi. Nếu là "Chưa kiểm" thì chờ, không phải hồ sơ đã sạch' },
      { what: 'Nạp lại hồ sơ xong thì mã giao dịch biến mất', why: 'Nạp đè xoá kết quả lần gửi trước, vì nội dung đã đổi', fix: 'Đúng thiết kế. Dấu vết lần gửi cũ nằm ở khối Lịch sử gửi trong màn chi tiết' },
      { what: 'Cổng đã tiếp nhận nhưng cột Đã đồng bộ vẫn trống (TT12)', why: 'Bước ghi sang danh mục hỏng giữa chừng', fix: 'Dùng nút "Đồng bộ lại danh mục". KHÔNG bấm gửi lại' },
      { what: 'Tệp Excel xuất ra khác với bảng đang xem', why: 'Đã đổi ô lọc nhưng chưa bấm Tải dữ liệu', fix: 'Bấm Tải dữ liệu rồi xuất lại. Tệp luôn theo bộ lọc của lần tải gần nhất' },
    ],
    speaker: 'Mỗi tình huống 30 giây. Bảng xử lý sự cố đầy đủ nằm ở cuối mỗi phần và ở Phụ lục A.',
  });

  L.closingSlide(pptx, ctx, {
    title: 'Tóm lại — năm việc cần nhớ',
    points: [
      'Nút "Xuất XML3176" chỉ tải ZIP về máy. Việc gửi lên cổng là tự động, không có nút bấm.',
      'Danh mục sai là gốc của mọi lỗi hàng loạt. Chọn đúng Cơ sở KCB TRƯỚC khi kéo tệp vào.',
      'Chứng từ Phụ lục 02 gửi hai lần là hai chứng từ trên cổng. Đọc Lịch sử gửi trước khi xác nhận.',
      'Số lỗi = 0 chỉ có nghĩa khi trạng thái khác "Chưa kiểm". Luôn đọc hai cột cùng nhau.',
      'Danh mục TT12 chỉ có hiệu lực khi cổng trả mã 200 và cột Đã đồng bộ đã chuyển.',
    ],
    contact: 'Tra cứu chi tiết: Hướng dẫn sử dụng XML3176 – OrderCheck – Thẻ BHYT – Danh mục\nPhần I (XML 3176) · Phần III (thẻ BHYT) · Phần IV (danh mục) · Phần VI (chứng từ điện tử) · Phần VII (TT12/2026)\nPhụ lục A (tra cứu sự cố) · Phụ lục B (tiến trình nền)\nHỗ trợ: Phòng Công nghệ thông tin — số máy lẻ: ………',
  });

  return pptx;
};
