// DECK A — Khoa lâm sàng (bác sĩ, điều dưỡng, điều dưỡng trưởng khoa)
// Nguồn: Phần II (kiểm tra sai sót y lệnh), Phần V (tra cứu lỗi theo mã điều trị),
// trích Phần III (đọc kết quả tra thẻ BHYT).

const L = require('./lib');

module.exports = function deckA(PptxGenJS) {
  const { pptx, ctx } = L.newDeck(PptxGenJS, {
    title: 'Đào tạo khoa lâm sàng — Sai sót y lệnh và Tra cứu lỗi hồ sơ',
    subject: 'Hướng dẫn sử dụng phần mềm qlbv cho khoa lâm sàng',
    deck: 'Deck A — Khoa lâm sàng',
  });

  L.titleSlide(pptx, {
    title: 'Rà soát sai sót y lệnh\nvà tra cứu lỗi hồ sơ',
    subtitle: 'Phần mềm quản lý bệnh viện qlbv',
    audience: 'Dành cho khoa lâm sàng: bác sĩ, điều dưỡng, điều dưỡng trưởng khoa',
    meta: 'Thời lượng: 45–60 phút\nTài liệu gốc: Hướng dẫn sử dụng XML3176 – OrderCheck – Thẻ BHYT – Danh mục, Phần II, Phần III và Phần V',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục tiêu',
    title: 'Sau buổi này, anh/chị làm được gì',
    bullets: [
      { t: 'Mở được màn Danh sách vi phạm và lọc ra đúng phần việc của khoa mình', b: true,
        sub: 'Lọc theo Khoa thực hiện + Trạng thái "Mới" + Mức độ "Nghiêm trọng"' },
      { t: 'Đọc được một dòng vi phạm và biết phải sửa gì trên HIS', b: true,
        sub: 'Từ cột Nội dung, số Phiếu và Mã điều trị truy ngược về đúng y lệnh' },
      { t: 'Đánh dấu đúng "Đã xử lý" hay "Bỏ qua", kèm ghi chú giải trình', b: true },
      { t: 'Tra được toàn bộ lỗi của một hồ sơ chỉ bằng một lần quét mã vạch', b: true },
      { t: 'In được phiếu lỗi để kẹp bệnh án hoặc gửi lại khoa', b: true },
      { t: 'Biết việc nào là của khoa, việc nào phải báo phòng ban / công nghệ thông tin', b: true },
    ],
    speaker: 'Mở đầu bằng cam kết cụ thể, không nói chung chung. Nhấn: buổi này không dạy cách dùng phần mềm nói chung, mà dạy 6 việc rất hẹp mà khoa phải làm hằng ngày. Hỏi nhanh cả phòng: ai đã từng nhận email danh sách lỗi từ phòng BHYT?',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Bối cảnh',
    title: 'Phần mềm này đứng ở đâu',
    intro: 'Phần mềm không thay thế HIS. Nó đọc dữ liệu HIS, soi lỗi, rồi báo lại để khoa sửa trên chính HIS.',
    nodes: [
      { head: 'HIS', body: 'Bác sĩ ra y lệnh, điều dưỡng thực hiện, dữ liệu phát sinh tại khoa' },
      { head: 'qlbv rà soát', body: 'Máy đọc dữ liệu HIS mỗi ~60 giây, đối chiếu quy tắc, ghi nhận vi phạm', tone: 'primary' },
      { head: 'Khoa sửa trên HIS', body: 'Khoa nhận thông báo, sửa đúng dữ liệu gốc trên HIS', tone: 'accent' },
      { head: 'Cổng BHXH', body: 'Hồ sơ sạch mới được xuất, ký số và gửi lên cổng giám định' },
    ],
    legend: [
      'Phần mềm CHỈ ĐỌC dữ liệu HIS. Không bao giờ ghi, sửa hay xoá dữ liệu HIS.',
      'Vì vậy mọi việc sửa sai đều phải làm trên HIS — bấm nút trên phần mềm này không sửa được y lệnh.',
      'Sửa trên HIS xong, lượt quét kế tiếp mới nhìn thấy; nhưng vi phạm cũ KHÔNG tự biến mất.',
    ],
    speaker: 'Đây là slide quan trọng nhất về tư duy. Câu phải lặp lại nhiều lần trong buổi: phần mềm chỉ đọc, không ghi. Nhiều khoa hiểu nhầm rằng bấm "Đã xử lý" là đã sửa xong — không phải, bấm nút chỉ là đánh dấu đã xử lý, dữ liệu vẫn sai nếu chưa sửa trên HIS.',
  });

  L.cardSlide(pptx, ctx, {
    kicker: 'Nội dung buổi học',
    title: 'Ba màn hình khoa lâm sàng sẽ dùng',
    cards: [
      { head: 'Kiểm tra sai sót y lệnh', tone: 'danger',
        body: 'Danh sách vi phạm phát sinh từ y lệnh của khoa. Dùng hằng ngày, lọc theo khoa mình.\n\nMenu: Kiểm tra sai sót y lệnh → Danh sách vi phạm' },
      { head: 'Tra cứu lỗi hồ sơ', tone: 'ok',
        body: 'Quét mã điều trị trên phiếu, xem ngay toàn bộ lỗi của một hồ sơ từ cả ba nguồn.\n\nMenu: Tra cứu lỗi hồ sơ (ở ngoài cùng thanh menu trái)' },
      { head: 'Thẻ BHYT', tone: 'warn',
        body: 'Biết đọc mã lỗi thẻ để hiểu vì sao hồ sơ bị treo, và biết cái nào khoa sửa được.\n\nMenu: Thẻ BHYT → Tra cứu thẻ BHYT' },
    ],
    note: { label: 'Phạm vi buổi học:', text: 'Những màn hình còn lại (nạp hồ sơ XML, nhập danh mục, chứng từ điện tử, ký số) là việc của phòng ban nghiệp vụ, có buổi đào tạo riêng. Hôm nay không đề cập.' },
    speaker: 'Nói rõ ngay phạm vi để không ai ngồi chờ phần không liên quan. Nhấn menu "Tra cứu lỗi hồ sơ" nằm NGOÀI nhóm Kiểm tra sai sót y lệnh — rất nhiều người tìm không ra.',
  });

  // ------------------------------------------------------------ Chương 1
  L.sectionSlide(pptx, ctx, {
    no: 1,
    title: 'Kiểm tra sai sót y lệnh',
    sub: 'Máy rà soát cái gì · Đọc một dòng vi phạm · Xử lý dứt điểm · Các quy tắc hay gặp',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục 2.1',
    title: 'Máy rà soát như thế nào — 5 điều phải hiểu trước',
    bullets: [
      { t: 'Bộ quét chạy thường trực trên máy chủ, không phụ thuộc việc có ai mở màn hình hay không', b: true },
      { t: 'Chu kỳ khoảng 60 giây một lượt; dữ liệu HIS phát sinh nhiều thì độ trễ có thể vài phút', b: true },
      { t: 'Hệ thống chỉ đọc dữ liệu HIS, không bao giờ ghi hay sửa. Việc sửa vẫn do khoa làm trên HIS', b: true, color: 'C62828' },
      { t: 'Vi phạm đã đánh dấu "Đã xử lý" hoặc "Bỏ qua" sẽ không bao giờ bị dựng lại ở lượt quét sau', b: true,
        sub: 'Nghĩa là có thể yên tâm xử lý dứt điểm từng dòng, không sợ làm đi làm lại' },
      { t: 'Quy tắc bị tắt sẽ không sinh vi phạm mới, nhưng vi phạm cũ vẫn còn trong danh sách', b: true },
    ],
    speaker: 'Gạch đầu dòng thứ 4 là điều khoa thích nghe nhất: làm xong là xong. Gạch thứ 3 là điều khoa hay quên nhất. Nhấn mạnh độ trễ 60 giây để không ai than "tôi vừa sửa mà vẫn thấy lỗi".',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.2.1',
    title: 'Bốn nguồn dữ liệu được quét',
    intro: 'Bộ quét không làm việc theo "hồ sơ" mà theo từng dòng dữ liệu mới. Bệnh nhân nằm viện 30 ngày thì y lệnh ngày thứ 30 vẫn được quét như ngày đầu.',
    head: ['Nguồn dữ liệu', 'Nội dung', 'Cách theo dõi dữ liệu mới'],
    colW: [2.2, 3.0, 4.2],
    rows: [
      ['Phiếu chỉ định', 'Toàn bộ chỉ định dịch vụ, thuốc, cận lâm sàng', 'Theo thời điểm sửa đổi — phiếu sửa lại sau khi tạo sẽ được quét lại'],
      ['Nhật ký tương tác thuốc', 'Cảnh báo tương tác do HIS phát hiện', 'Theo số thứ tự bản ghi tăng dần'],
      ['Chi tiết thuốc cấp phát', 'Liều dùng và số lượng thuốc', 'Theo số thứ tự bản ghi tăng dần'],
      ['Chi tiết dịch vụ', 'Dịch vụ đã chỉ định — kiểm giới hạn giới tính và tuổi', 'Theo số thứ tự bản ghi tăng dần'],
    ],
    note: { label: 'Điểm có lợi cho khoa:', text: 'Phiếu chỉ định được theo dõi theo thời điểm sửa đổi. Vì vậy khi khoa gán người thực hiện vào lúc thực hiện dịch vụ, phiếu đó sẽ được quét lại — sửa đúng là hết lỗi cho lần sau.', kind: 'ok' },
    speaker: 'Giải thích vì sao hồ sơ nội trú dài ngày vẫn được theo dõi đầy đủ. Dùng ví dụ một bệnh nhân hồi sức nằm 3 tuần.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.2.2',
    title: 'Giới hạn phải biết: con trỏ quét chỉ tiến, không lùi',
    intro: 'Một phiếu đã quét sẽ không được đánh giá lại, trừ khi chính phiếu đó bị sửa trên HIS. Ba tình huống dưới đây khiến kết quả tự động chưa phản ánh đúng thực tế.',
    head: ['Tình huống', 'Hệ quả', 'Khoa làm gì'],
    colW: [2.4, 4.2, 3.0],
    rows: [
      ['Y lệnh sau giờ ra viện', 'Lúc quét, bệnh nhân còn nằm viện nên chưa có giờ ra viện; khi ra viện các phiếu cũ không được quét lại', 'Không cần làm gì. Phòng BHYT sẽ đối chiếu lại bằng kiểm tra hồ sơ XML 3176 sau khi hồ sơ kết thúc'],
      ['Chẩn đoán nhập bổ sung sau', 'Phiếu lúc quét chưa có mã ICD bị ghi vi phạm "thiếu chẩn đoán"; bổ sung ICD sau thì vi phạm cũ vẫn còn', 'Đánh dấu "Bỏ qua", ghi chú rõ: đã bổ sung ICD ngày …'],
      ['Danh mục BHYT cập nhật sau', 'Phiếu bị báo "mã không có trong danh mục" do lúc quét danh mục chưa nhập', 'Đánh dấu "Bỏ qua". Việc nhập danh mục là của phòng ban nghiệp vụ'],
    ],
    note: { label: 'Đừng kết luận vội:', text: 'Thấy một vi phạm mà biết chắc mình đã sửa rồi — hãy đối chiếu ba tình huống trên trước khi báo là lỗi phần mềm. Đa số trường hợp rơi vào dòng thứ hai.' },
    speaker: 'Đây là nguồn gây tranh cãi lớn nhất giữa khoa và phòng BHYT. Dạy khoa cách ghi chú đúng, vì ghi chú chính là căn cứ giải trình khi giám định hỏi lại.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'Mục 2.3',
    title: 'Màn hình Danh sách vi phạm',
    shot: 1,
    caption: 'Toàn màn Kiểm tra sai sót y lệnh → Danh sách vi phạm.\nChụp đủ: hàng bộ lọc, 4 ô chỉ số tổng hợp, vài dòng của bảng và hai nút Đã xử lý / Bỏ qua.',
    points: [
      'Màn hình tự tải dữ liệu NGÀY HÔM NAY ngay khi mở',
      'Đổi bộ lọc xong phải bấm Tải dữ liệu, bảng không tự đổi',
      'Bốn ô chỉ số: Tổng · Nghiêm trọng · Cảnh báo · Chưa xử lý — tính theo bộ lọc đang áp',
      'Nút Xuất Excel xuất đúng phạm vi bộ lọc đang chọn',
      'Dòng nghiêm trọng gắn nhãn đỏ ở cột Mức độ',
    ],
    speaker: 'Mở màn hình thật trên máy chiếu nếu được. Nhấn hai điều: (1) mặc định chỉ hiện hôm nay — nhiều người tưởng không có lỗi; (2) phải bấm Tải dữ liệu.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.3.1',
    title: 'Bộ lọc khoa lâm sàng dùng hằng ngày',
    intro: 'Toàn màn có 8 ô lọc. Khoa chỉ cần thuộc 4 ô dưới đây.',
    head: ['Ô lọc', 'Đặt giá trị gì', 'Vì sao'],
    colW: [2.0, 3.0, 4.4],
    rows: [
      [{ t: 'Khoa thực hiện', b: true }, 'Khoa của mình', 'Lọc ra đúng phần việc của khoa, bỏ qua vi phạm của khoa khác'],
      [{ t: 'Trạng thái', b: true }, '"Mới"', 'Chỉ còn việc chưa ai đụng tới. Làm hết là sạch việc trong ngày'],
      [{ t: 'Mức độ', b: true }, '"Nghiêm trọng" trước, sau đó mới tới Cảnh báo', 'Nghiêm trọng là nhóm chặn hồ sơ; xử lý trước sẽ gỡ được hồ sơ đang treo'],
      [{ t: 'Từ ngày / Đến ngày', b: true }, 'Mặc định hôm nay; mở rộng khi cần rà lại', 'Rà cuối tuần hoặc trước kỳ quyết toán thì mở rộng khoảng ngày'],
      ['Tìm BN/BS/ĐT/DV', 'Mã hoặc tên khi cần tra một ca cụ thể', 'Tra theo bệnh nhân, bác sĩ, mã điều trị hoặc dịch vụ'],
    ],
    note: { label: 'Thói quen nên tập:', text: 'Đầu giờ sáng: Khoa = khoa mình, Trạng thái = Mới, Mức độ = Nghiêm trọng → Tải dữ liệu. Làm hết danh sách đó rồi mới chuyển sang Cảnh báo.', kind: 'ok' },
    speaker: 'Biến thành một thói quen 4 bước để khoa nhớ được. Có thể in nhỏ dán ở buồng trực.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 2.4',
    title: 'Quy trình xử lý một vi phạm',
    steps: [
      ['Lọc Trạng thái = "Mới", Mức độ = "Nghiêm trọng", bấm Tải dữ liệu', 'Danh sách rút gọn còn các việc cần làm ngay'],
      ['Đọc cột Nội dung để hiểu sai sót; ghi lại số Phiếu và Mã ĐT', 'Đủ thông tin để tra ngược trên HIS'],
      ['Sửa dữ liệu gốc trên HIS: sửa phiếu chỉ định, bổ sung thông tin còn thiếu', 'Đây là bước duy nhất thực sự sửa được sai sót'],
      ['Quay lại phần mềm, bấm nút Đã xử lý trên dòng tương ứng', 'Hiện ô nhập "Ghi chú (tùy chọn)"'],
      ['Nhập ghi chú, ví dụ: "Khoa Nội đã bổ sung ICD ngày 11/8", rồi xác nhận', 'Hệ thống lưu người xử lý và thời điểm; dòng chuyển sang Đã xử lý'],
      ['Với vi phạm không đúng thực tế: bấm Bỏ qua và ghi RÕ lý do', 'Dòng chuyển sang Bỏ qua và không bao giờ xuất hiện lại'],
    ],
    note: { label: 'Cảnh báo:', text: 'Nút Bỏ qua là quyết định vĩnh viễn với dòng đó. Luôn ghi rõ lý do trong ô ghi chú — đây là căn cứ giải trình khi cơ quan giám định hỏi lại.', kind: 'danger' },
    speaker: 'Nhấn thứ tự: SỬA TRÊN HIS TRƯỚC, đánh dấu SAU. Bấm Đã xử lý trước khi sửa là tự xoá việc của mình mà dữ liệu vẫn sai. Cho học viên nhắc lại thứ tự này.',
  });

  L.splitSlide(pptx, ctx, {
    kicker: 'Mục 2.4',
    title: 'Chọn "Đã xử lý" hay "Bỏ qua"?',
    left: {
      head: 'ĐÃ XỬ LÝ — khi vi phạm là thật',
      items: [
        'Sai sót có thật và đã được sửa trên HIS',
        'Ví dụ: phiếu thiếu ICD → đã bổ sung ICD',
        'Ví dụ: người thực hiện chưa khai chứng chỉ hành nghề → đã khai bổ sung',
        'Ví dụ: giờ y lệnh ghi sai → đã sửa lại giờ',
        'Ghi chú nên nêu: đã sửa gì, ngày nào',
      ],
    },
    right: {
      head: 'BỎ QUA — khi vi phạm không đúng thực tế',
      items: [
        'Máy báo nhưng dữ liệu thực tế không sai',
        'Ví dụ: ICD đã bổ sung sau lượt quét (xem lại giới hạn ở mục 2.2.2)',
        'Ví dụ: mã báo "không có trong danh mục" do danh mục chưa nhập kịp',
        'Ghi chú BẮT BUỘC nêu rõ lý do vì sao không phải là sai sót',
        'Bỏ qua là vĩnh viễn — dòng sẽ không quay lại',
      ],
    },
    note: { label: 'Nguyên tắc:', text: 'Không dùng "Bỏ qua" để dọn màn hình cho gọn. Một dòng bỏ qua không có lý do chính đáng sẽ thành điểm yếu khi cơ quan giám định đối chiếu.' },
    speaker: 'Đây là chỗ dễ lạm dụng nhất. Kể một tình huống thật: giám định hỏi vì sao 40 dòng bị bỏ qua trong cùng một buổi chiều mà ghi chú đều để trống.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.5.1',
    title: 'Các quy tắc đang bật — nhóm cấu trúc và thời gian',
    head: ['Tên quy tắc', 'Phát hiện điều gì', 'Mức độ'],
    colW: [3.0, 5.2, 1.6],
    rows: [
      ['Ngày ra viện trước ngày vào viện', 'Ngày ra viện nhỏ hơn ngày vào viện', { t: 'Nghiêm trọng', b: true, color: 'C62828' }],
      ['Giờ y lệnh ngoài khoảng đợt điều trị', 'Y lệnh có giờ trước giờ vào viện hoặc sau giờ ra viện', { t: 'Cảnh báo', color: 'B26A00' }],
      ['Giờ thực hiện trước giờ y lệnh', 'Dịch vụ ghi nhận thực hiện trước thời điểm bác sĩ ra y lệnh', { t: 'Cảnh báo', color: 'B26A00' }],
      ['Người thực hiện thiếu chứng chỉ hành nghề', 'Tài khoản thực hiện dịch vụ chưa khai hoặc khai không hợp lệ chứng chỉ hành nghề', { t: 'Nghiêm trọng', b: true, color: 'C62828' }],
    ],
    note: { label: 'Hay gặp nhất tại khoa:', text: 'Nhóm "giờ" — do thói quen nhập y lệnh gộp vào cuối ca, hoặc ghi nhận thực hiện trước khi bác sĩ ký y lệnh. Sửa được bằng thay đổi quy trình ghi chép, không cần sửa phần mềm.' },
    speaker: 'Hỏi cả phòng: khoa mình thường ra y lệnh và ghi nhận thực hiện theo thứ tự nào? Đây là chỗ dễ giảm lỗi nhất bằng kỷ luật ghi chép.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.5.2',
    title: 'Các quy tắc đang bật — nhóm lâm sàng',
    head: ['Tên quy tắc', 'Phát hiện điều gì', 'Mức độ'],
    colW: [3.0, 5.2, 1.6],
    rows: [
      ['Tương tác thuốc', 'Cảnh báo tương tác thuốc do HIS phát hiện trong cùng đơn', { t: 'Cảnh báo', color: 'B26A00' }],
      ['Phiếu chỉ định thiếu chẩn đoán ICD', 'Phiếu chỉ định không có mã bệnh. Phiếu khám bệnh được miễn', { t: 'Cảnh báo', color: 'B26A00' }],
      ['Liều nhân ngày không khớp số lượng cấp', 'Tổng liều sáng-trưa-chiều-tối nhân số ngày khác số lượng thuốc đã cấp', { t: 'Thông tin', color: '5A6472' }],
      ['Chỉ định dịch vụ sai giới tính', 'Dịch vụ chỉ dành cho một giới nhưng chỉ định cho giới còn lại', { t: 'Cảnh báo', color: 'B26A00' }],
      ['Chỉ định dịch vụ ngoài ngưỡng tuổi', 'Tuổi bệnh nhân nằm ngoài khoảng tuổi cho phép của dịch vụ', { t: 'Cảnh báo', color: 'B26A00' }],
    ],
    note: { label: 'Còn một nhóm nữa:', text: 'Nhóm đối chiếu danh mục BHYT (mã thuốc / dịch vụ / vật tư không có trong danh mục) hiện đang TẮT và chỉ được bật sau khi phòng ban nhập đủ danh mục. Khi nhóm đó được bật, khoa sẽ thấy thêm một loại vi phạm mới.' },
    speaker: 'Giải thích quy tắc liều nhân ngày bằng ví dụ cụ thể: 1 viên x 3 lần x 5 ngày = 15 viên, cấp 20 viên thì máy báo. Mức Thông tin nên không chặn gì, chỉ để rà soát.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 2.6',
    title: 'Khi nào máy KHÔNG báo — các trường hợp được miễn trừ',
    intro: 'Thấy một trường hợp rõ ràng sai nhưng hệ thống im lặng, hãy đối chiếu bảng này trước khi kết luận là lỗi phần mềm.',
    head: ['Trường hợp được miễn', 'Miễn cho quy tắc nào', 'Vì sao'],
    colW: [3.4, 2.6, 3.8],
    rows: [
      ['Phiếu loại: Khác, Đơn máu, Suất ăn, Ngoài khám chữa bệnh', 'Toàn bộ quy tắc cấp phiếu chỉ định', 'Không phải y lệnh khám chữa bệnh theo nghĩa thông thường'],
      ['Phiếu khám bệnh', 'Quy tắc thiếu chẩn đoán ICD', 'Chẩn đoán chỉ có sau khi khám xong'],
      ['Đơn phòng khám, Đơn tủ trực, Đơn điều trị', 'Quy tắc chứng chỉ hành nghề', 'Không gắn với một người thực hiện cụ thể'],
      ['Bệnh nhân không rõ giới tính / dịch vụ không khai giới hạn', 'Quy tắc giới tính', 'Không đủ căn cứ để kết luận'],
      ['Bệnh nhân thiếu ngày sinh', 'Quy tắc ngưỡng tuổi', 'Không tính được tuổi'],
      ['Bệnh nhân không thuộc đối tượng BHYT', 'Nhóm đối chiếu danh mục BHYT', 'Không phát sinh nghĩa vụ đối chiếu danh mục'],
    ],
    speaker: 'Slide này để trả lời câu hỏi kinh điển "sao ca kia sai mà máy không báo". Không cần học thuộc, chỉ cần biết là có bảng này trong tài liệu, mục 2.6.',
  });

  // ------------------------------------------------------------ Chương 2
  L.sectionSlide(pptx, ctx, {
    no: 2,
    title: 'Tra cứu lỗi hồ sơ theo mã điều trị',
    sub: 'Một lần quét mã vạch — thấy toàn bộ lỗi của hồ sơ từ cả ba nguồn',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Mục 5.1',
    title: 'Chức năng này giải quyết vấn đề gì',
    intro: 'Cùng một đợt điều trị có thể bị ghi lỗi ở ba nơi khác nhau. Trước đây phải mở lần lượt ba màn hình và tự lọc ở từng nơi.',
    nodes: [
      { head: 'Sai sót y lệnh', body: 'Nguồn: bộ quét y lệnh (Phần II)' },
      { head: 'Lỗi tra thẻ BHYT', body: 'Nguồn: tra cứu thẻ tự động lên cổng BHXH' },
      { head: 'Lỗi XML 3176', body: 'Nguồn: kiểm tra hồ sơ và kết quả cổng giám định trả về' },
      { head: 'Một màn hình duy nhất', body: 'Nhập hoặc quét mã điều trị → hiện thông tin hồ sơ + cả ba bảng lỗi', tone: 'primary' },
    ],
    legend: [
      'Menu Tra cứu lỗi hồ sơ nằm ở cấp NGOÀI CÙNG của thanh menu bên trái, không nằm trong nhóm Kiểm tra sai sót y lệnh.',
      'Quyền cần có: tra-cuu-loi-ho-so. Quyền này cấp riêng cho khoa phòng, không cần mở quyền quản trị.',
      'Đây là màn hình tra cứu nhanh tại khoa. Theo dõi theo ngày / theo khoa / theo mức độ vẫn làm ở ba màn hình chuyên trách.',
    ],
    note: { label: 'Lưu ý:', text: 'Màn hình chỉ hiển thị lại lỗi đã được ghi nhận trước đó. Mở màn hình này KHÔNG kích hoạt một lượt kiểm tra mới — hồ sơ vừa phát sinh y lệnh có thể chưa kịp xuất hiện lỗi.' },
    speaker: 'Đây là màn hình khoa thích nhất vì nhanh. Nhấn vị trí menu, vì nó nằm khác chỗ với mọi thứ khác.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 5.3',
    title: 'Hai cách đưa mã điều trị vào',
    intro: 'Ô nhập nằm ngay đầu màn hình và tự động được đặt con trỏ khi mở trang.',
    steps: [
      ['Cách 1 — Gõ tay: gõ mã điều trị vào ô Mã điều trị', 'Mã hiện trong ô'],
      ['Nhấn Enter hoặc bấm nút Tra cứu', 'Kết quả hiện bên dưới sau khoảng một giây'],
      ['Cách 2 — Quét mã vạch: bấm chuột vào ô Mã điều trị (vừa mở trang thì con trỏ đã sẵn ở đó)', 'Con trỏ nhấp nháy trong ô'],
      ['Quét mã vạch in trên phiếu bằng máy quét cầm tay', 'Mã tự điền và kết quả tự hiện ra, không cần bấm nút'],
      ['Quét tiếp phiếu thứ hai', 'Mã cũ tự bị thay bằng mã mới — không cần xoá ô giữa hai lượt quét'],
    ],
    note: { label: 'Không dùng camera:', text: 'Phần mềm không quét mã bằng camera điện thoại hay máy tính. Chức năng đó đã thử nghiệm và gỡ bỏ vì ảnh camera thiết bị thông thường quá thấp để đọc mã vạch. Dùng máy quét cầm tay hoặc gõ tay.' },
    speaker: 'Máy quét cầm tay hoạt động như bàn phím: gõ chuỗi rồi tự gửi Enter — không cần cài đặt gì. Nếu có máy quét, demo ngay tại chỗ, đây là phần gây ấn tượng nhất buổi học.',
  });

  L.shotSlide(pptx, ctx, {
    kicker: 'Mục 5.4 – 5.5',
    title: 'Màn hình kết quả tra cứu',
    shot: 2,
    caption: 'Màn Tra cứu lỗi hồ sơ sau khi tra một mã điều trị có lỗi.\nChụp đủ: ô nhập mã, khối Thông tin hồ sơ, và tiêu đề của cả ba bảng lỗi kèm con số trên tiêu đề.',
    points: [
      'Khối Thông tin hồ sơ lấy trực tiếp từ HIS tại thời điểm tra',
      'Con số trên tiêu đề mỗi bảng là số dòng lỗi đang có',
      'Hồ sơ sạch hiện dải màu xanh "Không phát hiện lỗi trên hồ sơ này"',
      'Cả ba bảng có ô tìm kiếm riêng, bấm tiêu đề cột để sắp xếp, tự phân trang khi quá 10 dòng',
      'Tra lỗi giữa chừng (mất mạng, hết phiên) thì kết quả cũ bị ẩn hết — tránh nhìn nhầm hồ sơ trước',
    ],
    note: { label: 'Hai khái niệm đừng nhầm:', text: '"Nơi ĐKBĐ" là cơ sở ghi trên thẻ của người bệnh; "Cơ sở KCB" là nơi đang điều trị. Hai trường này thường khác nhau, dùng nhầm sẽ dẫn tới kết quả đối chiếu sai.' },
    speaker: 'Nhấn hành vi ẩn kết quả cũ khi lỗi: đó là chủ ý, để khi quét liên tiếp nhiều phiếu không ai nhìn nhầm kết quả của hồ sơ trước.',
  });

  L.cardSlide(pptx, ctx, {
    kicker: 'Mục 5.5',
    title: 'Ba bảng lỗi — đọc cột nào',
    cards: [
      { head: 'Sai sót y lệnh', tone: 'danger',
        body: 'Mức độ · Luật · Nội dung · Phát hiện lúc · Trạng thái · Xử lý\n\nĐây là bảng khoa hành động được ngay. Cột Xử lý cho đổi trạng thái tại chỗ.\n\nKhông hiện các vi phạm đã đánh dấu Bỏ qua.' },
      { head: 'Lỗi tra thẻ BHYT', tone: 'warn',
        body: 'Mã tra cứu · Mã kiểm tra · Kết quả · Ghi chú · Mã thẻ · Tra lúc\n\nChỉ có dòng khi kết quả tra thẻ bất thường. Thẻ hợp lệ thì bảng để trống.\n\nÝ nghĩa mã: xem chương 3.' },
      { head: 'Lỗi XML 3176', tone: 'accent',
        body: 'XML · STT · Mã lỗi · Tên lỗi · Mô tả · Ngày YL\n\nChỉ có sau khi hồ sơ đã được kiểm và cổng đã trả kết quả. Bảng trống KHÔNG có nghĩa là hồ sơ không có lỗi XML.\n\nXử lý chính: phòng BHYT.' },
    ],
    speaker: 'Nhấn câu cuối của bảng thứ ba: bảng trống không có nghĩa là sạch. Hồ sơ chưa gửi thì đương nhiên chưa có lỗi XML.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Mục 5.6 – 5.8',
    title: 'Ba thao tác còn lại trên màn hình này',
    steps: [
      ['Đổi trạng thái một vi phạm: mở ô chọn ở cột Xử lý, chọn Đã xem / Đã xử lý / Bỏ qua', 'Hệ thống ghi nhận và tự tra cứu lại hồ sơ; cột Trạng thái đổi theo'],
      ['In phiếu lỗi: bấm nút In phiếu lỗi', 'Mở trang A4 dọc gồm thông tin hồ sơ và cả ba bảng lỗi, tự gọi hộp thoại in. Mọi tài khoản xem được màn hình đều in được'],
      ['Tra lại thẻ BHYT: bấm nút Tra lại thẻ BHYT', 'Dòng chữ xanh báo đã GỬI yêu cầu — chưa phải đã có kết quả'],
      ['Chờ vài giây rồi bấm Tra cứu lại', 'Bảng Lỗi tra thẻ BHYT hiện kết quả mới'],
    ],
    note: { label: 'Về nút Tra lại thẻ:', text: 'Yêu cầu được xếp vào hàng đợi nền chứ không chạy ngay, và giới hạn 5 lượt mỗi phút cho mỗi tài khoản. Bấm nhiều lần không làm nhanh hơn. Dùng khi nghi ngờ kết quả cũ đã lạc hậu, ví dụ người bệnh vừa gia hạn thẻ.' },
    speaker: 'Phiếu lỗi in ra đánh dấu dòng nghiêm trọng bằng chữ đậm và vạch dọc đầu dòng, không chỉ bằng màu — in đen trắng vẫn phân biệt được. Đây là chi tiết khoa hay hỏi.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 5.2',
    title: 'Vì sao tôi không thấy cột Xử lý?',
    intro: 'Đây là hành vi đúng thiết kế, không phải lỗi hiển thị.',
    head: ['Việc muốn làm', 'Quyền cần có', 'Ai thường được cấp'],
    colW: [4.6, 2.6, 2.6],
    rows: [
      ['Tra cứu hồ sơ, xem ba bảng lỗi, in phiếu lỗi, tra lại thẻ BHYT', { t: 'tra-cuu-loi-ho-so', b: true }, 'Nhân viên khoa phòng'],
      ['Đổi trạng thái một vi phạm y lệnh ngay trên màn hình này', { t: 'tra-cuu-loi-ho-so + order-check', b: true }, 'Điều dưỡng trưởng, đầu mối của khoa'],
    ],
    note: { label: 'Không nhìn thấy menu Tra cứu lỗi hồ sơ?', text: 'Tài khoản chưa được cấp quyền tra-cuu-loi-ho-so. Đề nghị quản trị hệ thống cấp quyền, sau đó đăng xuất rồi đăng nhập lại.' },
    speaker: 'Giải thích vì sao tách quyền: để nhân viên khoa tra cứu được hồ sơ của mình mà không phải mở quyền quản trị toàn bộ danh sách vi phạm.',
  });

  // ------------------------------------------------------------ Chương 3
  L.sectionSlide(pptx, ctx, {
    no: 3,
    title: 'Thẻ BHYT — phần khoa lâm sàng cần biết',
    sub: 'Đọc hai mã kết quả · Biết lỗi nào khoa sửa được trên HIS',
  });

  L.cardSlide(pptx, ctx, {
    kicker: 'Mục 3.3.5',
    title: 'Hai mã hoàn toàn khác nhau — đừng nhầm',
    cards: [
      { head: 'Mã tra cứu\nHợp lệ khi = 000', tone: 'warn',
        body: 'Do CỔNG BHXH trả về.\n\nPhản ánh tình trạng thẻ trong cơ sở dữ liệu của cơ quan bảo hiểm: thẻ còn hạn không, có bị thu hồi không, họ tên và ngày sinh có khớp không.\n\nKhác 000 thường phải làm việc với người bệnh hoặc cơ quan BHXH.' },
      { head: 'Mã kiểm tra\nHợp lệ khi = 00', tone: 'danger',
        body: 'Do PHẦN MỀM tự tính khi so dữ liệu cổng trả về với dữ liệu đang lưu trên HIS.\n\nVí dụ: 08 = giới tính lệch · 09 = nơi đăng ký ban đầu khác cổng · 11 = cổng trả về thiếu dữ liệu.\n\nKhác 00 thường là khoa/tiếp đón sửa được ngay trên HIS.' },
    ],
    note: { label: 'Một dòng bị coi là lỗi khi:', text: 'Mã tra cứu khác 000 HOẶC Mã kiểm tra khác 00. Dòng lỗi được tô nền đỏ nhạt trên màn Kết quả tra cứu thẻ.' },
    speaker: 'Đây là phần khô nhất của buổi. Rút gọn: mã tra cứu = việc của bảo hiểm; mã kiểm tra = việc của mình. Nhắc lại đúng một câu đó.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Mục 3.6',
    title: 'Những mã kiểm tra khoa sửa được ngay trên HIS',
    intro: 'Đây là nhóm có tác động trực tiếp tới mức hưởng và tới việc hồ sơ XML có bị giám định từ chối hay không.',
    head: ['Mã', 'Ý nghĩa', 'Việc phải làm'],
    colW: [0.9, 4.2, 4.9],
    rows: [
      [{ t: '00', b: true, color: '2E7D32' }, 'Mọi thông tin đều khớp', 'Không cần làm gì'],
      [{ t: '06', b: true }, 'Sai họ tên', 'Sửa họ tên trên HIS theo dữ liệu cổng trả về'],
      [{ t: '07', b: true }, 'Sai ngày sinh', 'Sửa ngày sinh trên HIS theo dữ liệu cổng'],
      [{ t: '08', b: true, color: 'C62828' }, 'Sai giới tính', 'Sửa giới tính trên HIS. Đây là lỗi hay bị bỏ sót nhất và sẽ làm hồ sơ XML bị từ chối'],
      [{ t: '09', b: true, color: 'C62828' }, 'Nơi đăng ký ban đầu khác với cổng', 'Cập nhật nơi ĐKBĐ trên HIS — trường này quyết định đúng tuyến hay trái tuyến'],
      [{ t: '03', b: true }, 'Thẻ hết hạn khi bệnh nhân chưa ra viện', 'Xác định phần chi phí sau ngày hết hạn thẻ, thông báo cho người bệnh'],
      [{ t: '05', b: true }, 'Thẻ không có trong cơ sở dữ liệu', 'Kiểm tra lại số thẻ; nếu đúng, đề nghị người bệnh liên hệ cơ quan bảo hiểm'],
    ],
    note: { label: 'Ảnh hưởng tới mức hưởng:', text: 'Trường "Ngày đủ 5 năm liên tục" và "Mã khu vực" khi lệch với cổng phải sửa theo dữ liệu cổng TRƯỚC khi kết xuất hồ sơ XML, nếu không hồ sơ sẽ bị giám định từ chối.' },
    speaker: 'Nhấn mã 08 và 09: hai mã này nhỏ nhưng hậu quả lớn. Giới tính sai nghe rất vô lý nhưng thực tế phát sinh nhiều, chủ yếu do nhập nhanh lúc tiếp đón.',
  });

  // ------------------------------------------------------------ Kết
  L.splitSlide(pptx, ctx, {
    kicker: 'Ranh giới trách nhiệm',
    title: 'Việc của khoa và việc phải báo lên',
    left: {
      head: 'KHOA LÂM SÀNG tự làm',
      items: [
        'Sửa dữ liệu gốc trên HIS: y lệnh, chẩn đoán ICD, giờ y lệnh, người thực hiện',
        'Khai bổ sung chứng chỉ hành nghề cho người thực hiện dịch vụ',
        'Sửa thông tin hành chính lệch với cổng: họ tên, ngày sinh, giới tính, nơi ĐKBĐ',
        'Đánh dấu Đã xử lý / Bỏ qua kèm ghi chú giải trình',
        'In phiếu lỗi kẹp bệnh án, tra cứu hồ sơ bằng mã điều trị',
      ],
    },
    right: {
      head: 'BÁO PHÒNG BAN / CÔNG NGHỆ THÔNG TIN',
      items: [
        'Bộ quét dừng: hộp Thống kê quét có cột "Chạy gần nhất" cách hiện tại quá vài phút, hoặc cột "Lỗi" khác 0',
        'Không nhìn thấy menu hoặc thiếu cột Xử lý → xin cấp quyền',
        '"Không lấy được thông tin từ HIS" → mất kết nối cơ sở dữ liệu HIS',
        'Cần bật/tắt một quy tắc kiểm tra, hoặc cần quét lại một giai đoạn',
        'Mã báo "không có trong danh mục" hàng loạt → danh mục BHYT chưa nhập',
      ],
    },
    speaker: 'Slide này nên để lâu nhất. Mục tiêu: khoa không mất thời gian với việc không phải của mình, và phòng ban không nhận về những việc khoa tự làm được.',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'Mục 2.8 và 5.9',
    title: 'Năm tình huống gặp nhiều nhất',
    cases: [
      { what: 'Bảng trống, không có dòng nào', why: 'Bộ lọc quá hẹp (mặc định chỉ ngày hôm nay), hoặc bộ quét đang dừng', fix: 'Mở rộng khoảng ngày rồi bấm Tải dữ liệu. Vẫn trống thì mở hộp Thống kê quét, xem cột Chạy gần nhất' },
      { what: 'Ba bảng lỗi đều trống nhưng biết chắc hồ sơ có lỗi', why: 'Lỗi chưa được ghi nhận: bộ quét chưa chạy tới, hoặc hồ sơ chưa kiểm XML, hoặc chưa tra thẻ', fix: 'Chờ khoảng một phút cho bộ quét y lệnh. Lỗi XML chỉ có sau khi hồ sơ đã gửi và cổng trả kết quả' },
      { what: 'Không tìm thấy hồ sơ với mã này trên HIS', why: 'Gõ sai mã điều trị, hoặc hồ sơ thuộc cơ sở khác', fix: 'Kiểm tra lại mã. Nếu ba bảng lỗi bên dưới vẫn có dữ liệu thì mã đúng, chỉ là hồ sơ không còn trên HIS' },
      { what: 'Bấm Tra lại thẻ BHYT nhưng kết quả không đổi', why: 'Yêu cầu chạy ở hàng đợi nền, chưa xong', fix: 'Chờ vài giây rồi bấm Tra cứu lại. Sau vài phút vẫn không đổi thì báo công nghệ thông tin' },
      { what: 'Số liệu trên màn hình khác số liệu HIS', why: 'Độ trễ của bộ quét, hoặc quy tắc liên quan đang tắt', fix: 'Chờ vài phút rồi tải lại. Nếu nghi ngờ quy tắc bị tắt thì báo phòng ban nghiệp vụ' },
    ],
    speaker: 'Đi nhanh, mỗi tình huống 30 giây. Nhấn rằng toàn bộ bảng xử lý sự cố đầy đủ nằm ở mục 2.8, 5.9 và Phụ lục A của tài liệu.',
  });

  L.closingSlide(pptx, ctx, {
    title: 'Tóm lại — bốn việc cần nhớ',
    points: [
      'Phần mềm chỉ ĐỌC dữ liệu HIS. Mọi việc sửa sai đều làm trên HIS, sửa xong mới đánh dấu.',
      'Đầu giờ sáng: lọc Khoa mình + Trạng thái "Mới" + Mức độ "Nghiêm trọng" → Tải dữ liệu.',
      '"Bỏ qua" là vĩnh viễn và luôn phải có ghi chú lý do — đó là căn cứ giải trình với giám định.',
      'Cần biết một hồ sơ có lỗi gì: quét mã vạch trên phiếu ở màn Tra cứu lỗi hồ sơ, một lần là đủ.',
    ],
    contact: 'Tra cứu chi tiết: Hướng dẫn sử dụng XML3176 – OrderCheck – Thẻ BHYT – Danh mục\nPhần II (sai sót y lệnh) · Phần III (thẻ BHYT) · Phần V (tra cứu lỗi hồ sơ) · Phụ lục A (tra cứu sự cố)\nHỗ trợ: Phòng Công nghệ thông tin — số máy lẻ: ………',
  });

  return pptx;
};
