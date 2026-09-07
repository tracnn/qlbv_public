/**
 * Phiên bản tài liệu — NGUỒN DUY NHẤT.
 *
 * Trang bìa, bảng "Lịch sử cập nhật tài liệu" và chân trang đều đọc từ đây. Trước khi tách
 * tệp này ra, số phiên bản nằm cứng trong chuỗi trang bìa của front.js, nên mỗi đợt bổ sung
 * nội dung lại phải nhớ sửa tay đúng một chỗ giữa hàng nghìn dòng — và không sửa thì tài
 * liệu vẫn dựng ra bình thường, chỉ mang số phiên bản cũ. Đó là kiểu sai không ai phát hiện
 * cho tới lúc hai người mở hai bản khác nhau mà thấy cùng một số.
 *
 * QUY TẮC ĐÁNH SỐ:
 *   - Tăng số phụ (1.1 → 1.2) khi bổ sung một phần mới hoặc cập nhật nội dung một phần.
 *   - Tăng số chính (1.x → 2.0) khi thay đổi cấu trúc tài liệu hoặc phạm vi đối tượng đọc.
 *
 * MỖI ĐỢT PHÁT HÀNH phải thêm MỘT dòng vào LICH_SU (dòng mới lên đầu) và sửa PHIEN_BAN,
 * NGAY_PHAT_HANH cho khớp dòng đó.
 */

const PHIEN_BAN = '1.8';

const NGAY_PHAT_HANH = 'Tháng 9 năm 2026';

/**
 * Lịch sử phát hành, MỚI NHẤT LÊN ĐẦU.
 *
 * Cột "Nội dung thay đổi" viết cho người đọc nghiệp vụ: nói cái gì mới dùng được, không nói
 * tệp nào được sửa. Cột "Phần liên quan" để người đã đọc bản cũ biết cần đọc lại chỗ nào
 * thay vì đọc lại cả tài liệu.
 */
const LICH_SU = [
  {
    ban: '1.8',
    ngay: '04/09/2026',
    noi_dung:
      'Bổ sung tám quy tắc kiểm hồ sơ XML 3176 theo danh sách lỗi tự động do cơ quan Bảo hiểm xã hội gửi về: mức hưởng khai sai, tỷ lệ thanh toán vật tư y tế, tổng ngày giường theo Thông tư 39, ba quy tắc về công khám ngoại trú, và hai dịch vụ phẫu thuật chồng thời gian thực hiện. Sửa bộ lọc "Có thẻ BHYT (nhiều)" ở màn Danh sách hồ sơ — trước đây chọn mục này không lọc gì mà trả về toàn bộ hồ sơ; đồng thời đổi nhãn "Có thẻ (một)" thành "Có thẻ (bất kỳ)" cho đúng với cách lọc thực tế.',
    lien_quan: 'Phần I, mục 1.4.2 và 1.8.',
  },
  {
    ban: '1.7',
    ngay: '27/08/2026',
    noi_dung:
      'Danh mục TT12: nói rõ hai điểm ở màn hình Dashboard danh mục mà bản trước chưa mô tả — vì sao khoảng ngày tự nới rộng khi bấm từ Dashboard sang màn Danh sách hồ sơ, và dấu hiệu đánh dấu cơ sở không còn trong danh sách hiện hành.',
    lien_quan: 'Phần VII, mục 7.12.',
  },
  {
    ban: '1.6',
    ngay: '26/08/2026',
    noi_dung:
      'Danh mục TT12: thêm màn hình Dashboard danh mục cho biết sáu mẫu của từng cơ sở, cái nào đã được cổng Bảo hiểm xã hội tiếp nhận và cái nào chưa được cổng tiếp nhận, kèm khối hồ sơ đang dở dang.',
    lien_quan: 'Phần VII, mục 7.12 (mới); Chương 0 cập nhật bản đồ menu.',
  },
  {
    ban: '1.5',
    ngay: '26/08/2026',
    noi_dung:
      'Danh mục TT12: nút Ký và gửi nay chỉ cần bấm MỘT lần cho cả hai bước ký số và gửi lên cổng, thay vì hai lần như trước. Kèm theo là chốt chống gửi trùng khi lượt trước còn đang chạy.',
    lien_quan: 'Phần VII, các mục 7.7.1, 7.7.2 và 7.11; Phụ lục B tách ba hàng đợi của module.',
  },
  {
    ban: '1.4',
    ngay: '26/08/2026',
    noi_dung:
      'Bổ sung Phần VII — Danh mục theo Thông tư 12/2026: tải biểu mẫu Excel cho sáu mẫu danh mục, nạp và kiểm dữ liệu, ký số và gửi lên cổng Bảo hiểm xã hội, đồng bộ sang bộ danh mục dùng để kiểm hồ sơ XML 3176, kèm hai đường cứu hộ Kiểm lại và Đồng bộ lại danh mục.',
    lien_quan: 'Phần VII (mới); Chương 0 cập nhật bản đồ menu và thứ tự đọc; Phụ lục A và B bổ sung mục tra cứu sự cố và tiến trình nền.',
  },
  {
    ban: '1.3',
    ngay: '24/08/2026',
    noi_dung:
      'Chứng từ điện tử: tích chọn nhiều hồ sơ rồi ký số và gửi bằng một lần bấm; màn danh sách thêm cột Số CCCD và Mã BHXH, tìm được theo cả hai; ô Tìm nhận phím Enter; tệp xuất Excel danh sách thêm hai cột tương ứng.',
    lien_quan: 'Phần VI, các mục 6.4.1, 6.4.2, 6.7.5, 6.7.6, 6.9 và 6.13.',
  },
  {
    ban: '1.2',
    ngay: '22/08/2026',
    noi_dung:
      'Bổ sung Phần VI — Chứng từ điện tử theo Phụ lục 02: nạp hồ sơ, theo dõi chín trạng thái gửi, ký số và gửi lên cổng Bảo hiểm xã hội, ba bảng xuất Excel và màn hình Dashboard chứng từ.',
    lien_quan: 'Phần VI (mới); Chương 0 cập nhật bản đồ menu và thứ tự đọc.',
  },
  {
    ban: '1.1',
    ngay: '17/08/2026',
    noi_dung:
      'Bổ sung Phần V — Tra cứu lỗi hồ sơ theo mã điều trị: gộp lỗi sai sót y lệnh, lỗi tra thẻ và lỗi XML 3176 vào một lần tra cứu, kèm in phiếu lỗi.',
    lien_quan: 'Phần V (mới).',
  },
  {
    ban: '1.0',
    ngay: '11/08/2026',
    noi_dung:
      'Ban hành lần đầu. Gồm Phần I Hồ sơ XML 3176, Phần II Kiểm tra sai sót y lệnh, Phần III Thẻ BHYT, Phần IV Quản lý danh mục, cùng hai phụ lục tra cứu sự cố và tiến trình nền.',
    lien_quan: 'Toàn bộ tài liệu.',
  },
];

module.exports = { PHIEN_BAN, NGAY_PHAT_HANH, LICH_SU };
