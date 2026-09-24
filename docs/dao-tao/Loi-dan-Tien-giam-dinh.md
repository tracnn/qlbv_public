# Lời dẫn cho giảng viên — Slide đào tạo Giải pháp Tiền giám định

Tài liệu đi kèm `Slide-dao-tao-Tien-giam-dinh.pptx` (80 slide). Số slide ghi ở đây trùng với số trang ở chân mỗi slide.

## Cách dùng

- Mỗi mục dưới đây là **lời dẫn nhập** cho một slide, đọc TRƯỚC khi cho học viên nhìn vào nội dung chi tiết. Mục đích là tạo lý do để họ muốn đọc slide, chứ không thay slide.
- Mỗi đoạn dài khoảng 30 đến 60 giây nói. Đọc chậm, dừng ở các câu hỏi để lớp trả lời, dù chỉ vài giây.
- Không phải slide nào cũng có lời dẫn. Slide bảng tra cứu, bảng mã lỗi thì chỉ cần nói một câu "bảng này để tra, không cần thuộc" rồi đi tiếp.
- Cuối mỗi đoạn có **câu chuyển** để nối sang slide kế. Nếu bỏ qua slide nào, bỏ luôn câu chuyển của slide trước đó.
- Các tình huống kể trong lời dẫn lấy từ ghi chú của slide và từ vận hành thực tế. Nếu đơn vị có ví dụ riêng sinh động hơn, thay vào.

---

## Mở đầu buổi học (Slide 1 và 2)

**Lời dẫn khi mở slide 1:**

Trước khi vào bài, tôi muốn hỏi các anh chị một câu. Một hồ sơ bảo hiểm y tế bị cơ quan giám định trả về, thông thường là bao lâu sau khi người bệnh ra viện? Một tuần? Một tháng? Có khi là cuối quý, khi đã quyết toán. Đến lúc đó, người bệnh đã về nhà từ lâu, bác sĩ ra y lệnh cũng không còn nhớ ca đó, và điều dưỡng thì đã đi qua hàng trăm phiếu khác. Sửa một lỗi nhỏ lúc này tốn gấp mười lần so với sửa ngay hôm phát sinh.

Toàn bộ buổi hôm nay xoay quanh đúng một ý: **kéo thời điểm phát hiện lỗi về sớm nhất có thể**. Sớm đến mức nào? Khoảng một phút sau khi bác sĩ ra y lệnh. Đó là điều phần mềm Tiền giám định làm.

**Chuyển sang slide 2:** Trước hết, để mọi người biết mình sẽ ngồi đây bao lâu và học phần nào, ta xem lộ trình.

**Lời dẫn slide 2 (Lộ trình):**

Buổi học có hai khối người dùng ngồi chung: các khoa lâm sàng và các phòng ban chức năng. Hai khối này dùng những màn hình khác nhau, nhưng cùng chạm vào một hồ sơ. Khoa sửa dữ liệu gốc, phòng ban nạp và gửi hồ sơ. Vì vậy bảng này có hai cột chấm tròn. Chấm đen là phần bắt buộc với khối mình, chấm trắng là phần nên ngồi nghe để hiểu người kia đang làm gì và vì sao họ hay gọi điện cho mình.

Một điều nữa: chỉ khoảng một phần tư thời gian là lý thuyết. Còn lại là thực hành trên phần mềm thật, với dữ liệu thật. Nên ngay bây giờ, xin mọi người mở máy.

**Chuyển sang slide 3:** Tài khoản và địa chỉ đăng nhập ở slide kế.

---

## Chương 01 — Tổng quan

### Slide 3 — Truy cập Cổng Tiền giám định

**Lời dẫn:**

Tôi cho cả lớp đăng nhập ngay lúc này, chưa giảng gì cả. Lý do: máy nào không vào được thì phải biết bây giờ, chứ để đến phần thực hành mới phát hiện thì mất nửa buổi. Trong lúc mọi người gõ địa chỉ, tôi nói ba điều về tài khoản này. Thứ nhất, đây là tài khoản dùng chung cho cả lớp, nên ai đổi mật khẩu là khoá luôn cả phòng. Thứ hai, dữ liệu trên Cổng là dữ liệu người bệnh thật, không phải dữ liệu giả lập, nên không chụp màn hình mang ra ngoài. Thứ ba, trong lúc thực hành chỉ xem và lọc. Không bấm Ký và gửi, không bấm Xoá, không nhập danh mục. Ai đã vào được, giơ tay cho tôi đếm.

**Chuyển sang slide 4:** Vào được rồi thì ta trả lời câu hỏi đầu tiên: phần mềm này nằm ở đâu trong quy trình mình đang làm hằng ngày?

### Slide 4 — Vị trí trong quy trình

**Lời dẫn:**

Các anh chị hãy nhìn quy trình hiện tại của bệnh viện: bác sĩ ra y lệnh, hồ sơ được kết xuất thành XML, ký số, gửi lên cổng bảo hiểm. Bốn bước đó không có gì mới. Cái mới là một ô chen vào giữa: kiểm lỗi tại bệnh viện, trước khi ký số. Và một ô nữa nhỏ hơn, nằm ngay ở bước đầu tiên, ngay lúc ra y lệnh.

Nhưng tôi muốn các anh chị nhìn vào cái vòng màu cam ở giữa slide chứ không phải các ô. Vòng đó nói rằng: khi máy tìm thấy lỗi, nó không sửa hộ. Lỗi được đưa về đúng nơi phát sinh, sửa trên HIS, rồi nạp lại. Phần mềm này là người chỉ ra chỗ sai, không phải người sửa. Ai còn nghĩ "cài phần mềm này xong là hết lỗi" thì hôm nay sẽ thay đổi suy nghĩ đó.

**Chuyển sang slide 5:** Vậy một hồ sơ đi từ HIS lên cổng qua mấy bước, ai làm bước nào? Slide kế trả lời.

### Slide 5 — Năm bước hồ sơ đi qua

**Lời dẫn:**

Năm bước này sẽ xuất hiện lại trong tất cả các chương sau, nên tôi xin mọi người nhớ theo cách này: hai bước đầu và bước cuối là việc của người, hai bước giữa là việc của máy. Nạp hồ sơ là người. Đọc lỗi và sửa là người. Ký số và gửi cổng là máy, hoàn toàn tự động, không có nút bấm nào cả. Theo dõi kết quả lại là người.

Cái bẫy nằm ở dải màu cam phía dưới. Trên màn hình có một nút tên là Xuất XML3176. Rất nhiều người bấm nút đó và tin rằng mình vừa gửi hồ sơ lên cổng. Không phải. Nút đó chỉ tải một tệp nén về máy để đối chiếu. Gửi cổng là việc máy tự làm ở bước bốn. Tôi sẽ còn nhắc lại điều này ở chương ba.

**Chuyển sang slide 6:** Năm bước là chung, nhưng phần mềm có ba luồng hồ sơ và mỗi luồng chạy khác nhau ở bước ba và bốn.

### Slide 8 — Ba nguyên tắc cần hiểu trước khi dùng

**Lời dẫn:**

Nếu buổi hôm nay bị cắt ngắn và tôi chỉ được nói ba điều, thì đó là ba tấm thẻ trên slide này. Tôi kể cho mỗi tấm một tình huống.

Tấm thứ nhất. Một điều dưỡng thấy vi phạm trên màn hình, bấm Đã xử lý, rồi báo với trưởng khoa là xong. Hôm sau hồ sơ vẫn lỗi. Vì bấm Đã xử lý không sửa được dữ liệu. Phần mềm chỉ đọc HIS, không bao giờ ghi vào HIS.

Tấm thứ hai. Có một màn hình mà một ô tích sai có thể chặn hàng nghìn hồ sơ cùng lúc. Đó là màn danh mục mã lỗi, nơi quyết định lỗi nào là nghiêm trọng. Tôi sẽ chỉ cho các anh chị ở chương ba.

Tấm thứ ba. Chứng từ điện tử gửi lên cổng hai lần thì cổng ghi nhận thành hai chứng từ, và không có nút thu hồi. Cổng nhận là nhận thật.

**Chuyển sang slide 9:** Và để mọi người có kỳ vọng đúng, tôi phải nói thẳng những gì phần mềm chưa làm được.

### Slide 9 — Những gì phần mềm chưa làm được

**Lời dẫn:**

Tôi nói phần này không phải để hạ thấp phần mềm, mà vì một lý do rất thực tế: người tin tuyệt đối vào màu xanh trên màn hình là người bị bất ngờ nhiều nhất khi cổng trả hồ sơ về. Hồ sơ qua được Tiền giám định vẫn có thể bị giám định từ chối. Vì bộ quy tắc ở đây chỉ bắt được những lỗi đã được viết thành quy tắc. Quy định mới ban hành thì luôn có độ trễ.

Và có một điểm liên quan trực tiếp đến phòng ban: kết quả kiểm phụ thuộc vào danh mục. Danh mục thiếu, hoặc sai ngày hiệu lực, thì máy sinh lỗi giả hàng loạt. Lúc đó không phải khoa sai, không phải phần mềm sai, mà là danh mục chưa được nhập đúng. Ai giữ danh mục thì giữ luôn độ tin cậy của cả hệ thống.

**Chuyển sang slide 10:** Hết phần tổng quan. Bây giờ vào phần đầu tiên dành cho các khoa lâm sàng: máy bắt lỗi y lệnh như thế nào.

---

## Chương 02 — Kiểm tra sai sót y lệnh

### Slide 10 — Mở chương

**Lời dẫn:**

Các anh chị ở khoa, phần này là của mình. Tôi hỏi trước một câu: khoa mình biết một y lệnh có sai sót vào lúc nào? Thường là lúc phòng bảo hiểm gọi xuống, tức là vài tuần sau. Chương này nói về việc rút khoảng thời gian đó xuống còn khoảng một phút. Một phút sau khi bác sĩ ra y lệnh, nếu phiếu thiếu chẩn đoán, nếu người thực hiện chưa có chứng chỉ hành nghề, nếu giờ thực hiện ghi trước giờ y lệnh, thì máy đã ghi nhận rồi. Vấn đề còn lại là khoa có mở màn hình lên xem hay không.

**Chuyển sang slide 11:** Nhưng trước khi xem màn hình, có năm điều về cách máy làm việc mà nếu không hiểu thì sẽ hiểu sai mọi thứ nhìn thấy sau đó.

### Slide 11 — Máy rà soát như thế nào: 5 điều phải hiểu trước

**Lời dẫn:**

Tôi nhận được câu này rất nhiều: "Tôi vừa sửa xong mà máy vẫn báo lỗi, phần mềm sai rồi." Chín trên mười lần, người nói câu đó vừa sửa xong chưa đầy một phút. Bộ quét chạy theo chu kỳ khoảng 60 giây. Dữ liệu nhiều thì có thể vài phút. Nên hãy uống xong cốc nước rồi hãy tải lại.

Điều thứ hai tôi muốn nhấn là gạch đầu dòng thứ tư, vì đây là điều khoa thích nghe nhất. Một dòng vi phạm đã được đánh dấu Đã xử lý hoặc Bỏ qua thì không bao giờ bị dựng lại ở lượt quét sau. Nghĩa là làm xong là xong, không có chuyện sáng mai mở lên lại thấy đúng dòng đó. Còn điều khoa hay quên nhất là gạch thứ ba, màu đỏ: máy chỉ đọc HIS, không sửa. Sửa vẫn là việc của khoa.

**Chuyển sang slide 12:** Vậy máy đọc những gì trên HIS? Có bốn nguồn.

### Slide 13 — Giới hạn phải biết: con trỏ quét chỉ tiến, không lùi

**Lời dẫn:**

Đây là slide gây tranh cãi nhiều nhất giữa khoa và phòng bảo hiểm, nên tôi xin dừng lâu hơn. Tình huống thật: bác sĩ ra y lệnh cận lâm sàng trước, chẩn đoán nhập sau vì còn chờ kết quả. Máy quét phiếu vào đúng lúc chưa có mã bệnh, ghi vi phạm "thiếu chẩn đoán". Một giờ sau, ICD được bổ sung. Nhưng vi phạm cũ vẫn nằm đó. Vì con trỏ quét chỉ tiến, không quay lại đánh giá phiếu đã quét, trừ khi chính phiếu đó bị sửa.

Lúc này khoa có hai lựa chọn. Một là cãi với phòng bảo hiểm rằng phần mềm sai. Hai là bấm Bỏ qua và ghi chú "đã bổ sung ICD ngày mấy". Cách thứ hai mất mười giây, và dòng ghi chú đó chính là bằng chứng giải trình nếu sau này giám định hỏi lại. Bảng trên slide liệt kê ba tình huống như vậy. Tôi đề nghị các anh chị đọc kỹ dòng thứ hai, vì phần lớn trường hợp rơi vào đó.

**Chuyển sang slide 14:** Bây giờ ta mở màn hình thật để xem một dòng vi phạm trông như thế nào.

### Slide 14 — Màn hình Danh sách vi phạm

**Lời dẫn:**

Tôi mở màn hình thật trên máy chiếu. Trước khi nhìn vào bảng, các anh chị để ý hai điều mà người mới hay vấp. Thứ nhất, màn hình vừa mở là chỉ hiện dữ liệu của ngày hôm nay. Nhiều người mở lên thấy trống, kết luận khoa mình không có lỗi. Không phải. Là hôm nay chưa có lỗi mới, còn lỗi tuần trước vẫn nằm đó nếu mở rộng khoảng ngày. Thứ hai, đổi bộ lọc xong phải bấm nút Tải dữ liệu. Bảng không tự đổi. Đây là chủ ý, để không phải chờ máy tải lại mỗi lần đổi một ô. Ai đã quen dùng thì thấy bình thường, ai mới dùng thì ngồi nhìn bảng không đổi và tưởng máy treo.

**Chuyển sang slide 15:** Toàn màn có tám ô lọc, nhưng khoa chỉ cần thuộc bốn.

### Slide 16 — Quy trình xử lý một vi phạm

**Lời dẫn:**

Sáu bước trên slide, nhưng tôi chỉ cần mọi người nhớ một thứ tự: sửa trên HIS trước, đánh dấu sau. Tôi hỏi lại cả lớp: sửa trước hay đánh dấu trước? Sửa trước. Vì sao? Vì nếu bấm Đã xử lý trước rồi quên sửa, dòng đó biến mất khỏi danh sách việc của mình, trong khi dữ liệu vẫn sai. Mình vừa tự xoá việc của mình mà lỗi vẫn nguyên. Đến kỳ quyết toán, hồ sơ đó vẫn bị chặn, và lúc đó không còn ai nhớ ra vì sao.

Bước năm là ghi chú. Ghi chú không bắt buộc với Đã xử lý, nhưng tôi khuyên luôn ghi, ngắn thôi: sửa gì, ngày nào. Vì hệ thống lưu người và thời điểm, ghi chú là phần duy nhất máy không tự điền được.

**Chuyển sang slide 17:** Còn với dòng máy báo mà thực tế không sai thì sao? Đó là lúc cần nút Bỏ qua, và cũng là chỗ dễ lạm dụng nhất.

### Slide 17 — Chọn "Đã xử lý" hay "Bỏ qua"?

**Lời dẫn:**

Tôi kể một tình huống có thật. Cơ quan giám định vào đối chiếu, mở danh sách vi phạm, thấy một khoa có bốn mươi dòng được đánh dấu Bỏ qua trong cùng một buổi chiều. Bốn mươi dòng, ô ghi chú đều trống. Câu hỏi của giám định viên rất đơn giản: "Vì sao bỏ qua?" Và không ai trả lời được, vì người bấm hôm đó chỉ muốn dọn màn hình cho gọn trước khi giao ca.

Nút Bỏ qua là quyết định vĩnh viễn. Dòng đó không bao giờ quay lại. Nên nguyên tắc chỉ có một: Bỏ qua là khi máy báo nhưng thực tế không sai, và bắt buộc ghi lý do. Cột trái là Đã xử lý, cột phải là Bỏ qua. Các anh chị đọc ví dụ ở hai cột, rồi tự hỏi: khoa mình hay rơi vào cột nào hơn?

**Chuyển sang slide 18:** Vậy máy đang bật những quy tắc gì? Có hai nhóm, và nhóm đầu là nơi khoa có thể tự giảm lỗi mà không cần phần mềm giúp.

### Slide 18 — Các quy tắc đang bật: nhóm cấu trúc và thời gian

**Lời dẫn:**

Tôi hỏi cả phòng một câu, và xin trả lời thật: ở khoa mình, bác sĩ ra y lệnh trước rồi điều dưỡng thực hiện, hay điều dưỡng làm trước rồi cuối ca bác sĩ mới ký gộp một loạt? Không cần giơ tay. Nhưng nếu câu trả lời là vế sau, thì khoa mình sẽ thấy rất nhiều vi phạm "giờ thực hiện trước giờ y lệnh" và "giờ y lệnh ngoài đợt điều trị".

Điểm hay ở chỗ này: đây là nhóm lỗi không cần sửa phần mềm, không cần sửa HIS, chỉ cần đổi thói quen ghi chép. Ra y lệnh đúng lúc, ghi nhận thực hiện sau y lệnh. Khoa nào làm được điều đó thì tự nhiên mất đi một nửa số dòng cảnh báo mỗi ngày.

**Chuyển sang slide 19:** Nhóm thứ hai là nhóm lâm sàng, trong đó có quy tắc liều nhân ngày mà tôi sẽ giải thích bằng một phép tính đơn giản.

### Slide 20 — Khi nào máy KHÔNG báo

**Lời dẫn:**

Slide này tồn tại để trả lời một câu hỏi kinh điển: "Ca kia rõ ràng sai mà sao máy không báo?" Câu trả lời thường nằm trong bảng miễn trừ này. Phiếu khám bệnh không bị bắt lỗi thiếu chẩn đoán, vì chẩn đoán chỉ có sau khi khám xong. Đơn tủ trực không bị kiểm chứng chỉ hành nghề, vì không gắn với một người thực hiện cụ thể. Không cần học thuộc. Chỉ cần biết rằng có bảng này, nằm ở mục 2.6 của tài liệu, và trước khi kết luận "phần mềm bỏ sót" thì mở ra đối chiếu.

**Chuyển sang slide 21:** Hết phần của khoa về y lệnh. Bây giờ đổi vai: hồ sơ rời khoa, đến tay phòng bảo hiểm dưới dạng tệp XML 3176.

---

## Chương 03 — Hồ sơ XML 3176

### Slide 21 — Mở chương

**Lời dẫn:**

Các anh chị phòng bảo hiểm, phòng kế hoạch, đây là phần chính của mình. Nếu chương hai là lỗi ở cấp từng y lệnh, thì chương này là lỗi ở cấp cả hồ sơ, khi mọi thứ đã được gom lại thành mười lăm bảng XML. Ở đây phần mềm làm nhiều việc nhất và cũng tự động nhiều nhất. Tự động đến mức người dùng đôi khi không biết máy đang làm gì, và đó chính là nguồn gốc của phần lớn cuộc gọi hỗ trợ. Chương này sẽ đi qua vòng đời một hồ sơ, màn hình làm việc chính, và câu hỏi được hỏi nhiều nhất: vì sao hồ sơ của tôi chưa lên cổng.

**Chuyển sang slide 22:** Bắt đầu từ vòng đời, sáu bước.

### Slide 22 — Vòng đời một hồ sơ XML 3176

**Lời dẫn:**

Sáu bước, nhưng chỉ có hai bước là người làm: nạp hồ sơ ở đầu và xử lý lỗi ở giữa. Còn lại là máy. Riêng bước năm, ký số, xuất và gửi, là hoàn toàn tự động. Không có nút. Máy thấy hồ sơ hết lỗi nghiêm trọng là tự ký và tự gửi.

Và đây là hiểu nhầm phổ biến nhất của cả module, tôi nói lại lần thứ hai trong buổi này. Trên màn danh sách có nút Xuất XML3176. Nút đó chỉ tải một tệp nén về máy tính của mình. Không gửi gì lên cổng cả. Tôi hỏi lại: nút Xuất XML3176 có gửi hồ sơ lên cổng không? Không. Xin mọi người nói to lên để tôi chắc là ai cũng nghe.

**Chuyển sang slide 23:** Vậy bước một, nạp hồ sơ, làm thế nào? Có hai đường: bằng tay và tự động.

### Slide 25 — Màn hình Danh sách hồ sơ

**Lời dẫn:**

Đây là màn hình mà phòng bảo hiểm sẽ mở nhiều nhất. Nhưng trước khi nhìn vào bảng, tôi giải thích một điều giúp giảm được rất nhiều cuộc gọi. Tình huống: chị A nhập một lô hồ sơ, anh B mở màn hình lên không thấy gì, kết luận là nhập hỏng. Không hỏng. Tài khoản thông thường chỉ nhìn thấy hồ sơ do chính mình nhập. Chỉ tài khoản quản trị mới thấy toàn bộ. Nhớ điều này trước, rồi hãy nhìn vào góc dưới bên phải màn hình.

Ở góc đó có một biểu tượng quay tròn kèm con số. Con số là số việc còn trong hàng đợi kiểm tra. Nó về 0 nghĩa là máy kiểm xong. Nó đứng yên rất lâu, cùng một con số suốt mười lăm phút, nghĩa là tiến trình nền đã dừng, và đó là lúc gọi công nghệ thông tin.

**Chuyển sang slide 26:** Màn hình có mười bốn ô lọc. Sáu ô giải quyết hầu hết công việc, và một ô là cái bẫy.

### Slide 27 — Vì sao một hồ sơ chưa được gửi lên cổng

**Lời dẫn:**

Đây là câu hỏi số một mà bộ phận hỗ trợ nhận được từ phòng bảo hiểm. Và tôi muốn hôm nay các anh chị mang về một thói quen: trước khi gọi điện, đi qua năm điều kiện trên slide này theo thứ tự. Ba điều kiện để xuất, hai điều kiện để gửi.

Phần lớn trường hợp dừng ở điều kiện thứ hai: hồ sơ còn lỗi nghiêm trọng. Lọc "Lỗi critical" là thấy ngay. Trường hợp thứ hai hay gặp là ngày ra viện nằm ở tương lai, do HIS ghi sai ngày. Và trường hợp mà nhiều đơn vị mới triển khai không ngờ tới: chức năng tự động gửi mặc định là tắt. Phải yêu cầu bật cho từng cơ sở. Hồ sơ sạch, đã ký, nhưng cơ sở chưa bật tự gửi thì nó vẫn nằm đó mãi.

**Chuyển sang slide 28:** Khi có hàng nghìn hồ sơ thì không lọc từng cái được. Lúc đó cần Dashboard.

### Slide 29 — Danh mục mã lỗi XML 3176

**Lời dẫn:**

Tôi gọi đây là màn hình quyền lực nhất của toàn bộ phần mềm. Nó không nhập gì, không gửi gì, chỉ có vài cột tích chọn. Nhưng một ô tích ở cột Nghiêm trọng quyết định hàng nghìn hồ sơ được đi hay bị chặn. Tích nhầm một mã lỗi phổ biến thành nghiêm trọng, sáng hôm sau cả bệnh viện không xuất được hồ sơ nào.

Có một tình huống âm thầm hơn. Sau mỗi lần nâng cấp phần mềm, có thể xuất hiện mã lỗi mới. Mã mới chưa có trong danh mục thì mặc định được coi là nghiêm trọng. Hệ quả: hồ sơ bị chặn hàng loạt mà không ai hiểu vì sao, vì không ai đụng gì cả. Nên việc phải làm sau mỗi lần nâng cấp là mở màn hình này ra rà lại. Và tôi đề nghị đơn vị quy định rõ ai được đụng vào màn hình này. Không phải ai có tài khoản cũng nên có quyền tích vào đây.

**Chuyển sang slide 30:** Hết chương XML. Chương tiếp theo ngắn nhưng chạm cả hai khối: thẻ bảo hiểm y tế.

---

## Chương 04 — Thẻ BHYT

### Slide 30 — Mở chương

**Lời dẫn:**

Chương này là phần khô nhất của buổi học, tôi nói thật. Toàn là mã số. Nên tôi sẽ rút gọn xuống còn đúng một câu để mọi người mang về, và câu đó nằm ở slide kế. Trước khi sang, tôi hỏi: khi tra thẻ trả về kết quả lỗi, các anh chị nghĩ lỗi đó là của ai? Của bảo hiểm, hay của mình? Câu trả lời là tuỳ vào mã nào. Và có hai loại mã hoàn toàn khác nhau.

**Chuyển sang slide 31:** Hai mã đó là gì.

### Slide 31 — Hai mã hoàn toàn khác nhau

**Lời dẫn:**

Câu duy nhất cần nhớ của chương này: mã tra cứu là việc của bảo hiểm, mã kiểm tra là việc của mình.

Mã tra cứu do cổng bảo hiểm trả về. Nó nói về tình trạng thẻ trong cơ sở dữ liệu của cơ quan bảo hiểm: thẻ còn hạn không, có bị thu hồi không. Khác 000 thì thường phải làm việc với người bệnh hoặc cơ quan bảo hiểm, mình không tự sửa được. Mã kiểm tra thì ngược lại, do chính phần mềm tính, bằng cách so dữ liệu cổng trả về với dữ liệu đang lưu trên HIS. Khác 00 nghĩa là HIS của mình đang ghi khác với bảo hiểm, và phần lớn là tiếp đón hoặc khoa sửa được ngay. Tôi nhắc lại: mã tra cứu là việc của bảo hiểm, mã kiểm tra là việc của mình.

**Chuyển sang slide 32:** Trong nhóm mã kiểm tra, có hai mã nhỏ mà hậu quả lớn.

### Slide 32 — Những mã kiểm tra khoa sửa được ngay trên HIS

**Lời dẫn:**

Các anh chị nhìn hai dòng màu đỏ: mã 08 và mã 09. Mã 08 là sai giới tính. Nghe rất vô lý, làm sao nhập sai giới tính được? Nhưng thực tế phát sinh nhiều, chủ yếu do tiếp đón nhập nhanh lúc đông người, bấm nhầm một ô. Và hồ sơ XML có giới tính lệch với thẻ sẽ bị giám định từ chối. Một cú bấm nhầm mười giây, một hồ sơ bị trả về sau một tháng.

Mã 09 là nơi đăng ký ban đầu khác với cổng. Trường này quyết định người bệnh đi đúng tuyến hay trái tuyến, tức là quyết định mức hưởng. Sai trường này không chỉ là hồ sơ bị trả, mà là tính sai tiền cho người bệnh. Hai mã này khoa và tiếp đón sửa được ngay trên HIS, không cần chờ ai.

**Chuyển sang slide 33:** Đó là góc nhìn từng ca. Còn phòng ban thì nhìn cả nghìn thẻ mỗi ngày, bằng tra cứu tự động.

### Slide 34 — Tra cứu hàng loạt theo hồ sơ XML

**Lời dẫn:**

Chức năng này hữu ích trước kỳ quyết toán, nhưng tôi kể một tình huống thật trước khi ai đó bấm. Một đơn vị chọn phạm vi tra cứu là cả năm, xác nhận, rồi đi ăn trưa. Hàng đợi tra thẻ tắc suốt một ngày. Trong một ngày đó, mọi chức năng khác có tra thẻ đều đứng theo: tra thẻ tự động buổi sáng không chạy, nút Tra lại thẻ ở khoa bấm không có kết quả. Và cổng bảo hiểm có thể hạn chế tài khoản của cơ sở vì gọi quá nhiều.

Mỗi lần tra là một lượt gọi thật lên cổng. Nên nguyên tắc: chia theo tháng, hoặc theo khoa. Không bao giờ chọn cả năm.

**Chuyển sang slide 35:** Bảng mã tra cứu ở slide kế có hai mươi ba mã, tôi chỉ chia thành bốn nhóm cho dễ nhớ.

---

## Chương 05 — Tra cứu tiền cùng chi trả

### Slide 36 — Mở chương

**Lời dẫn:**

Phần này dành riêng cho phòng tài chính kế toán, bộ phận viện phí, và phòng bảo hiểm. Các khoa có thể nghỉ giải lao. Đây là chức năng người bệnh hỏi trực tiếp tại quầy: "Tôi đã đóng đủ chưa, tôi có được miễn cùng chi trả không?" Và câu trả lời của cán bộ tại quầy có thể làm người bệnh vui hoặc khiếu nại. Nên chương này không chỉ dạy bấm nút. Nó dạy cách trả lời người bệnh sao cho đúng và không hứa quá.

**Chuyển sang slide 37:** Bắt đầu từ điều quan trọng nhất của cả chương, và tôi sẽ nhắc lại nhiều lần.

### Slide 37 — Điều kiện miễn cùng chi trả gồm HAI vế

**Lời dẫn:**

Nếu chỉ được nhớ một câu từ chương này, thì là câu này: đạt ngưỡng tiền mới là một nửa điều kiện. Điều kiện miễn cùng chi trả có hai vế. Vế một, tham gia bảo hiểm y tế đủ năm năm liên tục. Vế hai, số tiền cùng chi trả trong năm lớn hơn sáu tháng lương cơ sở. Phần mềm chỉ tra được vế hai. Vì hàm tra cứu của cổng bảo hiểm không trả về dữ kiện năm năm liên tục.

Cho nên khi màn hình hiện dòng chữ xanh "Đủ ngưỡng sáu tháng lương cơ sở", đó không phải là kết luận "được miễn". Đó mới là một nửa. Tôi hỏi cả phòng: tại đơn vị mình, vế năm năm liên tục hiện đang kiểm bằng cách nào? Ai đang làm? Xin mọi người thảo luận một phút.

**Chuyển sang slide 38:** Trước khi bấm tra, cần biết con số trên màn hình từ đâu ra, vì nó giải thích trước ba hiểu nhầm.

### Slide 38 — Số liệu đến từ đâu

**Lời dẫn:**

Phần mềm không tự tính một đồng nào. Nó hỏi cổng bảo hiểm và hiển thị lại đúng những gì cổng trả về. Điều này kéo theo ba hệ quả mà nếu không nói trước thì người bệnh sẽ thắc mắc ngay tại quầy.

Một: người bệnh vừa ra viện sáng nay, đợt khám đó thường chưa có trong lũy kế, vì bệnh viện chưa gửi hồ sơ đề nghị thanh toán lên cổng. Hai: lũy kế bao gồm cả các đợt khám ở bệnh viện khác. Người bệnh ngạc nhiên "tôi có khám ở đây đâu mà sao có tiền", là vì họ khám ở nơi khác. Ba: số liệu có độ trễ, nên phải đọc mốc thời gian trước khi nói con số. Ba điều này tôi sẽ còn lặp lại khi đến phần đọc kết quả.

**Chuyển sang slide 39:** Có hai cách tra. Cách một là cách nên dùng khi người bệnh đang đứng trước mặt.

### Slide 43 — Đọc kết quả: khối kết luận

**Lời dẫn:**

Tôi dừng lâu ở slide này. Trên màn hình có ba con số lớn và một nhãn kết luận. Nhưng thứ quan trọng nhất lại là dòng chữ nhỏ ở dưới cùng, cái mà mắt thường lướt qua. Dòng đó ghi: nguồn dữ liệu, và tính đến ngày giờ nào. Đây là mốc thời gian của cổng bảo hiểm, không phải giờ hiện tại.

Tôi đề nghị các anh chị tập một câu mẫu khi trả lời người bệnh, và tập cho thành phản xạ: "Theo số liệu của cổng bảo hiểm tính đến ngày này, bác đã cùng chi trả từng này đồng." Luôn có mốc thời gian đi trước con số. Nếu người bệnh hỏi vì sao đợt hôm qua chưa có, câu trả lời đã nằm sẵn trong mốc thời gian. Còn nếu nói con số mà không có mốc, thì mình đang hứa một thứ mình không kiểm soát được.

**Chuyển sang slide 44:** Có một tình huống làm nhiều cán bộ và người bệnh tranh cãi nhau: ngưỡng trên màn hình không bằng sáu lần lương cơ sở mới.

### Slide 44 — Khi lương cơ sở thay đổi giữa năm

**Lời dẫn:**

Từ tháng bảy năm nay lương cơ sở tăng. Ai cũng tính nhẩm: sáu nhân lương mới, ra một con số. Rồi mở phần mềm lên thấy ngưỡng thấp hơn con số đó, và kết luận phần mềm tính sai. Không sai. Theo nghị định, phần tiền người bệnh đã cùng chi trả trước ngày đổi lương được quy đổi ra số tháng theo lương cũ. Phần còn thiếu mới tính theo lương mới. Nên ngưỡng cả năm của mỗi người là khác nhau, tuỳ họ đã đóng bao nhiêu trước mốc đổi lương.

Tôi không yêu cầu ai thuộc công thức. Màn hình tự tính và hiện đủ các bước. Mục tiêu duy nhất của slide này là để khi người bệnh hoặc đồng nghiệp hỏi "sao không phải là sáu lần lương mới", các anh chị trả lời được trong một câu: vì có phần đã đóng theo lương cũ.

**Chuyển sang slide 45:** Bên dưới khối kết luận là bảng chi tiết từng đợt, trong đó có một cột giải thích được câu hỏi "tôi có khám ở đây đâu".

### Slide 46 — Vì sao phải chờ, và vì sao không được bấm nhiều lần

**Lời dẫn:**

Slide này nói về một hành vi rất con người: sốt ruột. Bấm Tra cứu, đợi mười giây chưa thấy gì, bấm lại. Đợi thêm, bấm lại lần nữa. Ở hầu hết phần mềm, việc đó vô hại. Ở đây thì không. Mỗi lần bấm là một lượt gọi thật lên cổng bảo hiểm. Cơ quan bảo hiểm giới hạn số lượt tra của mỗi tài khoản, và có danh sách tài khoản bị hạn chế. Nghĩa là một người sốt ruột ở một quầy có thể làm cả bệnh viện mất quyền tra cứu.

Vì vậy phần mềm khoá nút và các ô nhập trong lúc chờ. Đồng hồ đếm giây còn chạy nghĩa là máy đang làm việc bình thường. Sau hai mươi lăm giây, dòng chữ đổi thành "cổng đang phản hồi chậm". Vẫn là bình thường, không phải lỗi. Đổi lại việc phải chờ, mỗi lần tra đều là số liệu mới nhất cổng có, không phải số cũ lưu lại.

**Chuyển sang slide 47:** Các thông báo hay gặp, tôi chia làm hai nhóm: nhóm cán bộ tự xử lý được, và nhóm phải báo lên.

### Slide 50 — Sáu điều cần lưu ý khi trả lời người bệnh

**Lời dẫn:**

Slide cuối của chương, tôi đọc chậm từng dòng, và xin mọi người đọc cùng. Vì đây là ranh giới giữa hai việc: cung cấp thông tin, và kết luận quyền lợi. Cán bộ tra cứu chỉ làm việc thứ nhất. Kết quả trên màn hình là căn cứ tham khảo. Việc xác định đủ điều kiện miễn và cấp giấy chứng nhận vẫn theo quy định hiện hành, có thủ tục riêng. Nói cho người bệnh biết con số, kèm mốc thời gian, kèm lưu ý về vế năm năm liên tục, rồi hướng dẫn họ làm thủ tục. Đó là đủ. Nói thêm một câu "bác được miễn rồi" là mình đã bước sang việc thứ hai, và đó là việc không thuộc thẩm quyền của người đứng quầy.

**Chuyển sang slide 51:** Các khoa quay lại. Chương tiếp theo là màn hình mà khoa thích nhất, vì nhanh.

---

## Chương 06 — Tra cứu lỗi hồ sơ theo mã điều trị

### Slide 51 — Mở chương

**Lời dẫn:**

Tôi bắt đầu bằng cách mô tả một buổi sáng ở khoa trước khi có chức năng này. Điều dưỡng trưởng muốn biết hồ sơ của một bệnh nhân sắp ra viện có lỗi gì không. Cô ấy phải mở màn hình sai sót y lệnh, lọc theo mã. Rồi mở màn hình kết quả tra thẻ, lọc lại. Rồi hỏi phòng bảo hiểm xem hồ sơ XML có lỗi không. Ba nơi, ba lần lọc, cho một bệnh nhân. Chương này gom ba nơi đó về một màn hình, và thao tác duy nhất là quét mã vạch trên phiếu.

**Chuyển sang slide 52:** Ba nguồn lỗi đó là gì, và màn hình mới nằm ở đâu trên menu.

### Slide 52 — Chức năng này giải quyết vấn đề gì

**Lời dẫn:**

Điều tôi muốn nhấn ở slide này là vị trí menu, vì nó nằm khác chỗ với mọi thứ khác. Menu Tra cứu lỗi hồ sơ nằm ở cấp ngoài cùng của thanh bên trái, không nằm trong nhóm Kiểm tra sai sót y lệnh. Nhiều người tìm mãi trong nhóm y lệnh không thấy, tưởng chưa được cấp quyền. Và quyền của nó cũng riêng, cấp cho khoa phòng mà không cần mở quyền quản trị.

Một lưu ý ở khung dưới cùng: màn hình này chỉ hiển thị lại lỗi đã được ghi nhận. Mở nó lên không kích hoạt một lượt kiểm tra mới. Hồ sơ vừa có y lệnh cách đây ba mươi giây thì có thể chưa kịp có lỗi.

**Chuyển sang slide 53:** Bây giờ là phần gây ấn tượng nhất buổi học, nếu phòng có máy quét mã vạch.

### Slide 53 — Hai cách đưa mã điều trị vào

**Lời dẫn:**

Nếu có máy quét cầm tay ở đây, tôi làm ngay. Mở màn hình, con trỏ đã nằm sẵn ở ô nhập. Cầm phiếu, quét. Kết quả hiện ra. Cầm phiếu thứ hai, quét tiếp. Không cần xoá ô, không cần bấm nút. Máy quét cầm tay hoạt động như một bàn phím: nó gõ chuỗi mã rồi tự nhấn Enter. Không cần cài đặt gì.

Có một câu hỏi tôi nhận được nhiều: "Sao không quét bằng camera điện thoại?" Chức năng đó đã từng thử và đã gỡ bỏ. Ảnh camera của thiết bị thông thường không đủ nét để đọc mã vạch trên phiếu in, đọc sai nhiều hơn đọc đúng. Nên hai cách: máy quét cầm tay, hoặc gõ tay.

**Chuyển sang slide 54:** Quét xong thì màn hình kết quả trông thế nào.

### Slide 55 — Ba bảng lỗi: đọc cột nào

**Lời dẫn:**

Ba bảng, ba màu, và một câu ở bảng thứ ba mà tôi cần mọi người nhớ: bảng trống không có nghĩa là sạch. Bảng lỗi XML 3176 chỉ có dữ liệu sau khi hồ sơ đã được kiểm và cổng đã trả kết quả. Bệnh nhân còn nằm viện, hồ sơ chưa gửi, thì bảng đó đương nhiên trống. Trống vì chưa ai kiểm, không phải trống vì không có lỗi.

Bảng đầu tiên, sai sót y lệnh, là bảng khoa hành động được ngay tại chỗ. Cột Xử lý cho phép đổi trạng thái mà không cần quay về màn hình danh sách vi phạm. Bảng thứ hai, lỗi tra thẻ, chỉ có dòng khi kết quả bất thường. Thẻ hợp lệ thì để trống, và lần này trống nghĩa là tốt.

**Chuyển sang slide 56:** Còn ba thao tác nữa trên màn hình này, trong đó có in phiếu lỗi để mang xuống buồng bệnh.

---

## Chương 07 — Quản lý danh mục BHYT

### Slide 58 — Mở chương

**Lời dẫn:**

Tôi mở chương này bằng một câu hỏi cho phòng ban, và xin trả lời thật: ở đơn vị mình, danh mục bảo hiểm y tế được cập nhật bao lâu một lần, và ai làm? Thường thì câu trả lời là im lặng, hoặc "chắc là phòng nào đó". Đây là việc không có chủ ở rất nhiều đơn vị. Trong khi toàn bộ kết quả kiểm hồ sơ và kiểm y lệnh đều dựa trên danh mục. Danh mục sai thì mọi thứ phía sau sai theo, và sai hàng loạt.

**Chuyển sang slide 59:** Vì sao tôi nói danh mục là việc ưu tiên.

### Slide 59 — Vì sao danh mục là việc ưu tiên

**Lời dẫn:**

Tôi kể một con số thật. Trong một lần danh mục bị trống, chỉ trong vài lượt quét, bộ kiểm y lệnh sinh ra hơn ba mươi sáu nghìn vi phạm giả. Ba mươi sáu nghìn dòng, các khoa mở màn hình lên tưởng mình sai hết. Không ai sai cả. Chỉ là danh mục chưa có dữ liệu để đối chiếu.

Còn một điểm ở khung dưới cùng rất dễ bỏ qua: hai cột ngày hiệu lực. Quy tắc đối chiếu chỉ chấp nhận dòng còn hiệu lực tại thời điểm ra y lệnh. Nhập danh mục đầy đủ nhưng để ngày hiệu lực sai, thì với máy, danh mục đó vẫn như chưa có. Và một điều nữa: cả mười một màn hình danh mục đều chỉ đọc, không có nút thêm sửa xoá từng dòng. Đó là chủ ý. Nguồn chuẩn là tệp bảo hiểm phát hành. Muốn cập nhật thì nhập lại tệp.

**Chuyển sang slide 60:** Quy trình nhập, sáu bước, và bước hai là nơi mọi đơn vị mất thời gian nhất.

### Slide 62 — Bốn bộ theo cơ sở: nơi dễ sai nhất

**Lời dẫn:**

Nếu đơn vị chỉ có một cơ sở, slide này đi qua nhanh. Nếu có nhiều cơ sở hoặc chi nhánh, xin dừng lại. Đây là lỗi âm thầm nhất trong cả phần mềm, vì lúc nhập không báo gì, và chỉ lộ ra khi đối chiếu giá.

Tình huống: nhân viên nhập danh mục dịch vụ kỹ thuật cho cơ sở A, nhưng quên chọn cơ sở trên màn hình, để mặc định "Dùng chung cho mọi cơ sở". Nhập thành công, năm con số đẹp. Một tháng sau, giá dịch vụ của cơ sở A đang được áp cho cơ sở B, C, D. Không ai biết cho tới khi bảo hiểm hỏi. Quy tắc chỉ có một dòng: chọn đúng cơ sở trước khi kéo tệp vào.

**Chuyển sang slide 63:** Còn hai thao tác nữa mà tôi đề nghị đơn vị ghi thành quy định nội bộ.

### Slide 63 — Hai thao tác có thể gây hậu quả lớn

**Lời dẫn:**

Hai việc trên slide này không nên ai cũng được làm, và không nên làm trong giờ hành chính. Bên trái là nhập thay thế trọn bộ cho danh mục đơn vị hành chính và cơ sở khám chữa bệnh. Nhập một tệp đã bị cắt bớt, thì những đơn vị không có trong tệp biến mất khỏi hệ thống. Bên phải là nút xoá toàn bộ một danh mục, phải gõ chữ XOA viết hoa để xác nhận. Con số ba mươi sáu nghìn vi phạm giả tôi kể lúc nãy chính là từ một lần danh mục trống như thế.

Tôi đề nghị đơn vị viết thành quy định: ai được làm, làm lúc nào, ai xác nhận, và tệp thay thế phải sẵn trong tay trước khi bấm xoá. Đây không phải đề xuất kỹ thuật, đây là đề xuất quản lý.

**Chuyển sang slide 64:** Chương tiếp theo là một luồng hồ sơ khác hẳn, nơi phần mềm cố tình làm khó người dùng, và có lý do.

---

## Chương 08 — Chứng từ điện tử theo Phụ lục 02

### Slide 64 — Mở chương

**Lời dẫn:**

Giấy ra viện, giấy chứng sinh, giấy báo tử, giấy nghỉ hưởng bảo hiểm xã hội. Những giấy này bây giờ đi lên cổng dưới dạng chứng từ điện tử. Nghe giống hồ sơ XML 3176, cũng nạp, cũng kiểm, cũng ký, cũng gửi. Nhưng có một khác biệt ở tầng dưới làm thay đổi toàn bộ cách dùng màn hình. Và nếu không hiểu khác biệt đó, các anh chị sẽ thấy màn hình này rất khó chịu: hỏi xác nhận liên tục, không cho tích chọn một số dòng, giới hạn năm mươi hồ sơ. Tất cả đều có lý do, và lý do nằm ở slide kế.

**Chuyển sang slide 65:** Khác biệt đó là gì.

### Slide 65 — Điểm khác biệt quyết định cách dùng màn hình này

**Lời dẫn:**

Hồ sơ XML 3176 có mã giao dịch. Gửi hai lần, cổng nhận ra bản trùng. Chứng từ Phụ lục 02 không có cái đó. Gửi hai lần là cổng ghi nhận hai chứng từ. Hai giấy ra viện cho cùng một người bệnh, cùng một đợt điều trị, nằm trên hệ thống của cơ quan bảo hiểm. Và không có đường rút lại. Muốn sửa thì làm việc với cơ quan bảo hiểm theo quy trình nghiệp vụ, không phải bằng thao tác trên phần mềm.

Vì vậy toàn bộ màn hình được thiết kế theo một triết lý: thà không gửi còn hơn gửi trùng. Mọi hành vi "khó chịu" mà các anh chị sẽ gặp trong chương này đều quay về câu đó. Tôi nói trước để khi gặp, mọi người biết đó là chủ ý chứ không phải lỗi.

**Chuyển sang slide 66:** Trước tiên là chín trạng thái gửi, vì có năm lý do khác nhau khiến hồ sơ chưa lên cổng, và mỗi lý do cần một người khác xử lý.

### Slide 67 — Bẫy lớn nhất: cột Số lỗi bằng 0

**Lời dẫn:**

Tôi dừng lâu ở slide này vì đây là chỗ một người có thể báo cáo với lãnh đạo "sạch hết rồi" trong khi chưa có hồ sơ nào được kiểm. Tình huống: nạp một lô năm trăm chứng từ, mở danh sách, cột Số lỗi toàn số 0. Nhìn rất đẹp. Nhưng cột Trạng thái gửi ghi "Chưa kiểm". Số 0 lúc đó không có nghĩa là không có lỗi. Nó có nghĩa là chưa ai nhìn.

Quy tắc đọc: luôn đọc hai cột cùng nhau. Số lỗi chỉ có ý nghĩa khi trạng thái khác "Chưa kiểm". Và một điểm nhỏ ở khung dưới: ba nút xuất Excel dùng bộ lọc của lần Tải dữ liệu gần nhất, không phải bộ lọc đang hiện trên màn hình. Đổi ô lọc mà chưa bấm Tải dữ liệu thì tệp xuất ra vẫn là dữ liệu cũ.

**Chuyển sang slide 68:** Hồ sơ sạch rồi thì ký và gửi. Một hồ sơ và nhiều hồ sơ có hai cách khác nhau.

### Slide 69 — Hồ sơ nào tích được, hồ sơ nào không

**Lời dẫn:**

Đây là ví dụ tốt nhất về việc phần mềm cố tình làm khó. Hai trạng thái "Đã gửi" và "Cổng từ chối" không có ô tích để gửi hàng loạt. Muốn gửi lại phải mở màn chi tiết, từng hồ sơ một, và phần mềm còn hỏi xác nhận. Người dùng phàn nàn: "Sao không cho tích hết rồi gửi một lần?"

Câu trả lời: vì trong một lượt năm mươi dòng, không ai nhìn từng dòng. Nếu cho tích, một cú bấm sẽ gửi lại im lặng những hồ sơ cổng đã nhận, và mỗi hồ sơ đó thành một chứng từ trùng trên cổng. Ở màn chi tiết, người bấm đang nhìn đúng hồ sơ đó, và có cơ hội dừng lại. Giải thích lý do thì người dùng chấp nhận. Không giải thích thì họ coi là lỗi. Nên các anh chị về đơn vị, ai hỏi thì hãy giải thích như vậy.

**Chuyển sang slide 70:** Bảng mã lỗi ở slide kế để tra. Có một trường mới thành bắt buộc từ tháng chín, tôi sẽ nói riêng.

---

## Chương 09 — Danh mục theo Thông tư 12/2026

### Slide 71 — Mở chương

**Lời dẫn:**

Chương bảy là danh mục do bảo hiểm ban hành, mình nhận về. Chương này ngược lại: danh mục do bệnh viện tự khai về năng lực của mình, gửi lên. Khoa phòng có bao nhiêu giường, ai hành nghề với chứng chỉ gì, thuốc gì, dịch vụ gì, thiết bị gì. Sáu mẫu, sáu tệp Excel. Và điểm quan trọng: khi cổng chấp nhận, dữ liệu đó được ghi ngược vào bộ danh mục mà phần mềm dùng để kiểm hồ sơ XML 3176. Nghĩa là gửi Thông tư 12 xong, cách kiểm hồ sơ cũng đổi theo. Hai việc không tách rời nhau.

**Chuyển sang slide 72:** Sáu mẫu đó là gì, và mỗi mẫu đi về đâu.

### Slide 72 — Sáu mẫu danh mục và nơi chúng đi tới

**Lời dẫn:**

Cột cuối của bảng là cột tôi muốn mọi người nhìn. Mỗi mẫu gửi lên, khi được cổng tiếp nhận, sẽ ghi sang một bộ danh mục cụ thể trong phần mềm. Vì sao phải chờ cổng tiếp nhận mới ghi? Vì khi giám định, cơ quan bảo hiểm so hồ sơ với chính bản họ đã nhận. Nếu phần mềm ghi danh mục theo bản mình gửi mà cổng từ chối, thì mình đang kiểm hồ sơ bằng một danh mục bảo hiểm không công nhận. Đó là lý do bước đồng bộ chỉ chạy sau mã 200.

**Chuyển sang slide 73:** Cách nạp, và một quy tắc rất chặt về mã cơ sở.

### Slide 73 — Nạp danh mục TT12 vào phần mềm

**Lời dẫn:**

Hai điều ở slide này. Thứ nhất, luôn tải biểu mẫu từ phần mềm, đừng tự tạo tệp. Phần mềm nhận diện mẫu bằng chính dòng tiêu đề. Sửa một tên cột, chèn một dòng trống phía trên, là bị từ chối ngay khi nạp.

Thứ hai, quy tắc khớp mã cơ sở. Phần mềm đối chiếu toàn bộ cột mã cơ sở trong tệp với cơ sở đã chọn trên màn hình. Chỉ cần một dòng lệch là cả tệp bị từ chối, không nạp một phần. Nghe khắt khe, nhưng lý do là: mã cơ sở quyết định tài khoản dùng để gửi lên cổng. Nếu cho nạp một phần, dòng lệch sẽ đi lên cổng bằng tài khoản của cơ sở khác, cổng vẫn nhận, và danh mục bị ghi nhầm sang đơn vị khác. Hỏng lặng lẽ, không ai phát hiện. Thà từ chối cả tệp còn hơn.

**Chuyển sang slide 74:** Ký và gửi ở luồng này là một nút chạy cả hai việc, và có hai nút cứu hộ chỉ hiện đúng lúc cần.

---

## Chương 10 — Phân công và tình huống thường gặp

### Slide 76 — Mở chương

**Lời dẫn:**

Chúng ta đã đi qua chín chương, nhiều màn hình, nhiều nút. Phần cuối này không dạy thêm nút nào. Nó trả lời hai câu hỏi thực tế hơn: khi có chuyện, việc này của ai, và khi gặp tình huống lạ, đọc ở đâu trước khi gọi điện. Mục tiêu rất cụ thể: không ai mất thời gian với việc không phải của mình, và không ai gọi hỗ trợ sai địa chỉ.

**Chuyển sang slide 77:** Bảng phân công, hai khối, hai cột.

### Slide 77 — Ai làm gì

**Lời dẫn:**

Bảng này gộp ranh giới trách nhiệm của cả hai khối. Cột giữa là việc tự làm, cột phải là việc báo lên. Tôi muốn nhấn dòng ghi chú ở dưới: khi báo lên, gửi kèm gì. Nguyên văn thông báo, tốt nhất là ảnh chụp màn hình. Mã điều trị hoặc mã hồ sơ. Cơ sở. Thời điểm. Bốn thứ đó. Thiếu một thứ là bộ phận hỗ trợ phải gọi lại hỏi, và mất thêm nửa ngày.

Một mẹo nhỏ: ở cột Sub trên màn danh sách hồ sơ có nút sao chép nguyên văn thông điệp cổng. Bấm nút đó, dán vào tin nhắn, còn hơn mô tả bằng lời "nó báo cái gì đó về xác thực".

**Chuyển sang slide 78:** Hai slide tiếp là các tình huống thường gặp, tôi đi nhanh, mỗi tình huống ba mươi giây.

### Slide 80 — Tóm lại: năm điều mang về

**Lời dẫn:**

Tôi kết thúc bằng năm câu, và xin mọi người đọc cùng tôi, vì đây là năm câu duy nhất tôi mong còn lại trong đầu mọi người khi ra khỏi phòng này.

Một: lỗi phải sửa tại nơi phát sinh, trên HIS. Phần mềm chỉ ra, không sửa hộ. Hai: sửa xong phải nạp lại, kết quả mới đổi. Ba: Bỏ qua là vĩnh viễn, luôn ghi lý do. Bốn: cổng nhận là nhận thật, chứng từ gửi hai lần là hai chứng từ. Năm: đủ ngưỡng tiền mới là một nửa điều kiện miễn, luôn đọc mốc "tính đến".

Nếu sau buổi hôm nay số cuộc gọi lên phòng công nghệ thông tin giảm đi một nửa, thì tôi coi như buổi học thành công. Cảm ơn các anh chị. Bây giờ là phần hỏi đáp.
