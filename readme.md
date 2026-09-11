# 11/09/2026

- **Chứng từ điện tử: sửa được XML gốc ngay trong phần mềm.** Khi hồ sơ còn lỗi chặn mà phần mềm sinh XML chưa kịp sửa nguồn, người có quyền sửa thẳng nội dung XML ở thẻ **XML gốc** trong màn chi tiết để kịp ký số và gửi cổng. Sửa xong phần mềm **kiểm lỗi lại ngay** và báo số lỗi mới.
- Chức năng cần **quyền riêng `ctdt-sua-xml`**, tách khỏi `xml-man` và **không tự cấp cho ai** — quản trị cấp tay cho vài người. Nội dung sửa ở đây được ký số và gửi thẳng lên cổng, nên không phải ai xem được danh sách cũng sửa được.
- **Sửa xong thì chữ ký cũ bị vô hiệu**, hồ sơ cần ký và gửi lại. Nhưng **mã giao dịch được giữ nguyên**, khác đường nạp lại vốn xoá nó — giữ lại thì trạng thái vẫn là "Đã gửi", đúng sự thật, thay vì hiện "Chưa ký số" cho một hồ sơ cổng đã nhận.
- **Sáu điều phần mềm không cho sửa**, mỗi điều chặn một cách hỏng riêng: khai báo DOCTYPE (có thể khiến máy chủ đọc tệp nội bộ rồi **gửi nội dung đó lên cổng**), XML sai cú pháp, đổi thẻ gốc sang loại khác, **đổi mã định danh chứng từ** (sẽ làm lần nạp sau tạo ra hồ sơ thứ hai thay vì ghi đè), hồ sơ đang trong lượt ký và gửi, và nội dung vượt 256 KB.
- **Mỗi lần sửa đều được ghi nhật ký kèm nội dung trước và sau.** Người dùng sửa văn bản thô, một lần dán đè là mất hẳn bản gốc do phần mềm nguồn sinh ra — và đây là chứng từ pháp lý, khi bản trên cổng và bản tại đơn vị lệch nhau thì câu hỏi đầu tiên là lệch ở chỗ nào.
- Tài liệu hướng dẫn sử dụng lên phiên bản 1.11.

- **Bộ quy tắc mới dựa trên Danh mục mã đối tượng khám chữa bệnh do Bộ Y tế ban hành (10 mã lỗi).** Trước nay phần mềm gần như không dùng tới trường *mã đối tượng khám chữa bệnh*, dù đó là trường khai **người bệnh đến bằng đường nào** — đúng cơ sở đăng ký ban đầu, có phiếu chuyển, theo phiếu hẹn khám lại, hay tự đến. Danh mục gồm **27 mã**, và mỗi mã tự nó khẳng định một số điều mà phần còn lại của hồ sơ phải nhất quán theo.

- **Danh mục là 27 mã, không phải 28.** Số thứ tự trong văn bản chạy từ 1 đến 28 nhưng **nhảy qua số 22**. Đã kiểm bằng cả trích văn bản lẫn trích bảng. Bản thiết kế đầu tiên ghi nhầm 28 mã và tự bịa ra một mã `7.1` không tồn tại — đã sửa trước khi viết mã nguồn.

- **Sáu quy tắc trên bảng hành chính:** mã ngoài danh mục; mã 1.3 (có phiếu chuyển cơ sở) mà bỏ trống nơi chuyển đi; mã tự đến mà lại khai có nơi chuyển đi; đề nghị quỹ bảo hiểm thanh toán nhưng không có mã thẻ; đến đúng nơi đăng ký ban đầu nhưng khai mã khẳng định đến từ nơi khác; và mã 3.1 khám ngoại trú mà vẫn có tiền bảo hiểm thanh toán. **Ba quy tắc mức hưởng** trên bảng tổng hợp: mã có mức hưởng cố định, mã đổi mức hưởng theo mốc thời gian, và mã chỉ lĩnh thuốc mà vẫn có tiền công khám.

- **Sửa một lỗi cũ phát hiện trong lúc làm: mã trái tuyến khớp tiền tố thay vì khớp đúng bằng.** Cấu hình cũ khai `'3'` và hai chỗ dùng nó đều so tiền tố, nên gom nhầm cả bốn mã 3.1, 3.2, 3.3, 3.6. Theo danh mục, **chỉ mã 3.1** bị giảm mức hưởng — ba mã còn lại hưởng 100%. Nay khai đủ mã và khớp đúng bằng.

- **Gỡ bỏ quy tắc "có nơi đi nhưng thiếu giấy chuyển tuyến hoặc hẹn khám lại".** Quy tắc này sai từ gốc: nó suy ra *giấy tờ đầu vào* từ **mã loại ra viện**, vốn là kết quả khi **kết thúc** điều trị. Hai thứ không liên quan nhau.

- **Thêm quy tắc: mã 1.3 và 1.5 phải ghi số ở trường Giấy chuyển tuyến.** Chuẩn dữ liệu quy định trường này mang *số giấy chuyển cơ sở khám chữa bệnh* **hoặc** *số giấy hẹn khám lại*. Mã 1.3 là "đến khám có phiếu chuyển cơ sở" và mã 1.5 là "đến khám theo phiếu hẹn khám lại" — với hai mã này tờ phiếu tồn tại theo đúng định nghĩa của chính mã đó, nên số phiếu phải được ghi.

- **Quy tắc này sẽ báo khoảng 938 lỗi, và đó là kết quả mong muốn.** Đo trên 1.213 hồ sơ thật: **758 trên 761 hồ sơ mã 1.5 (99,6%)** và **180 trên 184 hồ sơ mã 1.3 (97,8%)** đang bỏ trống trường này; 7 hồ sơ có giá trị đều do nhập tay trong cùng một ngày. Tức **bộ xuất của phần mềm bệnh viện chưa ghép trường này** — đó chính là thứ quy tắc cần chỉ ra, không phải lý do bỏ quy tắc. Mã lỗi được nạp ở mức **cảnh báo, không chặn xuất hồ sơ, không chặn ký số, không chặn gửi cổng**.

- **Đây là một quyết định bị đảo ngược, ghi lại để khỏi đảo lần nữa.** Bản thiết kế ban đầu đã *bỏ* trường này với lý do "chỉ 0,6% hồ sơ có giá trị nên không đủ căn cứ". Lập luận đó sai: nếu quy định thật sự bắt buộc thì 99,4% hồ sơ thiếu nghĩa là **bộ xuất sai một cách hệ thống**, chứ không phải quy tắc sai. Tỷ lệ thiếu cao là bằng chứng ủng hộ quy tắc, không phải bằng chứng chống lại nó.

- **Sửa một test đỏ có sẵn trên nhánh chính.** Bộ danh mục bảo hiểm y tế đã tăng từ 11 lên 12 khi thêm *DVKT cần mã máy*, nhưng con số chốt cứng trong test không được nâng theo. Hai cấu hình vẫn khớp khoá hoàn toàn, chỉ con số bị bỏ quên. Con số này **cố ý chốt cứng chứ không đọc từ cấu hình**: nó là cái bẫy buộc người thêm danh mục mới phải kiểm lại cả cấu hình nhập khẩu — đổi thành đếm tự động thì test luôn xanh và mất sạch tác dụng.

- **Cài đặt:**

```bash
php artisan migrate && php artisan config:clear && php artisan queue:restart
```

  Cả ba lệnh đều bắt buộc. Thiếu `migrate` thì mã lỗi mới chưa có trong danh mục sẽ được **mặc định coi là nghiêm trọng** và chặn xuất khoảng 938 hồ sơ — chạy lệnh sau đó cũng không gỡ được các hồ sơ đã bị đánh dấu. Thiếu `queue:restart` thì tiến trình hàng đợi vẫn chạy mã cũ và mọi thay đổi trong đợt này **không có tác dụng gì**, trong im lặng.

- **Việc còn treo, không phải lỗi phần mềm: 1.153 trên 1.213 hồ sơ đang bị chặn xuất vì bốn danh mục gần như rỗng** — nhân viên y tế (18 dòng), dịch vụ kỹ thuật (49 dòng), vật tư y tế (0 dòng), thiết bị (0 dòng). Nạp đủ bốn danh mục này là việc cần làm trước khi đánh giá bất cứ con số lỗi nào khác.

# 10/09/2026

- **Bổ sung 24 quy tắc kiểm hồ sơ XML 3176, lấy căn cứ từ chuẩn dữ liệu đầu ra của Bộ Y tế** (Quyết định 130/QĐ-BYT, phần đính chính và sửa đổi theo Quyết định 4750/QĐ-BYT). Đây là lần đầu bộ quy tắc được đối chiếu với *toàn văn chuẩn dữ liệu* thay vì với danh sách lỗi Bảo hiểm xã hội gửi về. Đối chiếu từng trường của 15 bảng với 266 quy tắc sẵn có cho thấy chỗ thiếu tập trung ở ba nhóm.

- **Nhóm một — cấu trúc mã dịch vụ (6 quy tắc).** Chuẩn quy định mã dịch vụ mang thông tin trong chính hình dạng của nó, nhưng trước nay phần mềm chưa đọc: hậu tố `_TB` nghĩa là *đã chỉ định nhưng không thực hiện được* nên đơn giá bảo hiểm lẫn đơn giá bệnh viện phải bằng 0; mã kết thúc `0000` nghĩa là *chưa được quy định mức giá* nên đơn giá bảo hiểm phải bằng 0; `VC.XXXXX` là vận chuyển người bệnh nên phải kèm mã xăng dầu và XXXXX phải là mã cơ sở có thật; dạng `XX.YYYY.ZZZZ.K.WWWWW` là chuyển mẫu bệnh phẩm sang cơ sở khác. Chuẩn cũng chỉ định nghĩa đúng hai hậu tố `_TB` và `_GT`, nên hậu tố lạ nay bị bắt.

- **Nhóm hai — công thức tiền của TỪNG DÒNG (15 quy tắc).** Phần mềm vốn đã kiểm tổng cấp hồ sơ (tiền thuốc, tiền vật tư, tổng chi, tiền bảo hiểm thanh toán trong bảng tổng hợp có khớp tổng của bảng thuốc và bảng dịch vụ không). Nhưng **tổng khớp không suy ra từng dòng đúng**: hai dòng sai ngược chiều nhau vẫn cho tổng đúng, và người vận hành thấy báo tổng lệch mà không lần ra được dòng nào hỏng. Nay mỗi dòng thuốc và mỗi dòng dịch vụ được kiểm theo đúng công thức của chuẩn, cộng các tập giá trị hợp lệ: phạm vi chỉ được 1/2/3, mã tái sử dụng chỉ được để trống hoặc bằng 1, nguồn chi trả thuốc chỉ được 1/2/3/4, mã khu vực chỉ được K1/K2/K3.

- **Nhóm ba — ràng buộc liên bảng (3 quy tắc).** Hồ sơ có ngày tái khám thì phải có giấy hẹn khám lại, và ngày trên giấy hẹn phải nằm trong tập ngày tái khám. Hồ sơ có cân nặng con thì phải có giấy chứng sinh.

- **CẢNH BÁO QUAN TRỌNG: chưa quy tắc nào trong 24 quy tắc này được kiểm chứng trên dữ liệu thật.** Cơ sở dữ liệu thử nghiệm chỉ còn 4 hồ sơ được rà sau khi loại hồ sơ dịch vụ, và **chỉ 1 dòng có tiền**. Cả 24 quy tắc vì vậy được xây trên căn cứ văn bản, không trên số đo. Chúng được nạp ở mức **cảnh báo, không chặn xuất hồ sơ** — kể cả quy tắc hậu tố `_TB` từng đo được vi phạm 15 trên 15 dòng, vì một quy tắc báo oan mà chặn xuất thì làm tê liệt việc gửi hồ sơ.

- **Trước khi tin bất kỳ quy tắc nào, phải nạp một lô hồ sơ bảo hiểm y tế thật có phần tiền rồi đo lại.** Hai mốc để nhìn: quy tắc nào báo trên **20% số dòng** thì dừng lại xem xét — gần như chắc chắn là báo oan hoặc bộ xuất sai hệ thống, chứ không phải 20% hồ sơ sai; và hãy đếm cả **số dòng lọt qua được vào tới thân quy tắc**, không chỉ đếm số lỗi, vì phần mềm quy giá trị 0 về rỗng nên "0 lỗi" có thể nghĩa là quy tắc chưa từng chạy chứ không phải dữ liệu sạch.

- **Một điều cần cơ sở tự xác minh, không phải lỗi phần mềm: 761 trên 762 dòng dịch vụ đang khai phạm vi bằng 2.** Theo diễn giải sửa đổi của Quyết định 4750, mã 2 nghĩa là *dịch vụ do người bệnh tự trả*. Nếu bộ xuất đang gán mặc định như vậy cho mọi dòng thì khi hồ sơ có tiền thật, quy tắc sẽ báo hàng loạt — và chỗ cần sửa là bộ xuất, không phải quy tắc.

- **Sai số so tiền là 1 đồng, nới được mà không cần sửa mã.** Chuẩn bắt làm tròn hai chữ số thập phân ở từng phép nhân nên chênh lệch hợp lệ chỉ ở mức xu. Nếu bộ xuất làm tròn ở bước khác chuẩn (ví dụ làm tròn đơn giá trước khi nhân), biểu hiện sẽ là một quy tắc thành tiền báo gần như toàn bộ dòng — khi đó nới khoá `xml3176.tien.sai_so` trong tệp cấu hình.

- **Có những thứ cố ý KHÔNG làm, kèm lý do.** Kiểm kích thước tối đa và kiểm định dạng ngày: đo trên toàn bộ trường của mọi bảng có dữ liệu đều cho **0 vi phạm**, nên dựng một bộ kiểm định dạng lái bằng danh mục sẽ tốn công mà trả về 0 lỗi. Mã tai nạn thương tích, vị trí thực hiện thủ thuật, mã hiệu sản phẩm vật tư: đều cần danh mục mà hệ thống chưa có, hoặc chính chuẩn ghi rõ là chưa ban hành — thiếu căn cứ thì im lặng còn hơn đoán.

- **Cài đặt: KHÔNG có lệnh nạp danh mục mã lỗi thứ bảy.** Khác với các đợt trước, lần này danh mục 24 mã lỗi được nạp tự động bằng migration, nên chỉ cần:

```bash
php artisan migrate && php artisan config:clear
```

  Việc đổi cách làm là có chủ đích: điều kiện "phải chạy lệnh nạp danh mục trước" trước nay chỉ tồn tại trong trí nhớ người triển khai, mà hậu quả khi quên thì không lùi lại được — mã lỗi lạ chưa có trong danh mục được mặc định coi là nghiêm trọng, nên quy tắc nổ lần đầu sẽ **tự ghi dòng danh mục ở mức nghiêm trọng** và chặn xuất cả lô; chạy lệnh nạp sau đó cũng không gỡ được các hồ sơ đã bị đánh dấu.

- **Hồ sơ không phải bảo hiểm y tế (mã đối tượng khám chữa bệnh bằng 9) nay không bị rà lỗi nữa.** Module nhận cả hồ sơ dịch vụ, trong khi toàn bộ bộ quy tắc được viết cho hồ sơ bảo hiểm — áp lên hồ sơ dịch vụ chỉ sinh nhiễu. Số đo lúc thiết kế: **49 trên 51 hồ sơ** trong cơ sở dữ liệu thử nghiệm là đối tượng 9, và chúng sinh **6.136 trên 6.331 lỗi — 97% toàn bộ lỗi của hệ thống**.

- **Việc xuất hồ sơ, ký số và gửi cổng Bảo hiểm xã hội KHÔNG bị ảnh hưởng** — cổng chặn chỉ bọc phần rà lỗi. Hồ sơ dịch vụ vẫn đi trọn dây chuyền như cũ.

- **Lỗi cũ không tự biến mất.** Cổng chặn chỉ ngăn sinh lỗi mới; những lỗi đã ghi vẫn nằm nguyên và — nếu chúng ở mức nghiêm trọng — vẫn chặn xuất hồ sơ. Dọn bằng câu lệnh sau, nhớ sao lưu trước:

```bash
DELETE e FROM xml3176_error_results e JOIN xml3176_xml1s a ON a.ma_lk = e.ma_lk WHERE a.ma_doituong_kcb = '9' OR a.ma_doituong_kcb LIKE '9.%';
```

- **Danh sách mã đối tượng được loại trừ nằm trong tệp cấu hình**, thêm mã khác về sau không phải sửa mã nguồn. Mã 9 bao cả nhánh con 9.1, 9.2… nhưng không nuốt nhầm mã 91. Nếu hồ sơ không đọc được mã đối tượng thì **vẫn rà bình thường** — bỏ sót là mất bảo vệ trong im lặng, còn rà thừa chỉ là nhiễu nhìn thấy được.

- **Quy tắc "thiếu mã máy" đổi căn cứ: từ nhóm dịch vụ sang danh mục do Bảo hiểm xã hội ban hành.** Trước nay phần mềm bắt lỗi khi dịch vụ thuộc nhóm 1, 2 hoặc 3 mà không khai mã máy — một phép suy đoán từ nhóm sang tính chất dịch vụ. Nay căn cứ vào danh mục liệt kê đích danh từng mã dịch vụ bắt buộc có mã máy. Đo trên 762 dòng thật: **355 lỗi giảm còn 307**, trong đó **64 báo oan biến mất** (nhiều nhất là 56 dòng xét nghiệm "Đo hoạt độ" AST/ALT/GGT/Lipase thuộc nhóm 1) và **16 lỗi bỏ sót được bắt thêm** — toàn bộ thuộc nhóm 18 nội soi, vốn lọt lưới vì không nằm trong nhóm 1, 2, 3.

- **Đã kiểm chứng khác biệt là thật, không phải lỗi khớp mã**: danh mục *có* 168 mã xét nghiệm khác nhưng cố ý *không có* các mã "Đo hoạt độ" — nó phân biệt có chủ đích. Quy tắc cũng khớp cả mã đã bỏ hậu tố, nếu không sẽ mất 11 trên 307 lỗi đo được.

- **Chưa nạp danh mục thì quy tắc tự lùi về cách cũ.** Không có khoảng trống mất bảo vệ: bảng rỗng thì phần mềm dùng lại quy tắc theo nhóm, nạp danh mục xong thì tự chuyển sang quy tắc mới. Danh mục nạp qua màn *Nhập danh mục* sẵn có, chọn thẳng tệp Bảo hiểm xã hội gửi, không cần chuyển đổi. Đối chiếu sau khi nạp: phải có **3.471 dòng đang dùng**. Nếu số lỗi "thiếu mã máy" **không đổi** so với trước thì gần như chắc chắn danh mục chưa nạp được.

- **Có khu màn hình mới "Danh mục tra cứu", tách khỏi Danh mục BHYT.** Đây là chỗ xem các danh mục nội bộ mà trước nay không có màn hình nào — danh mục đầu tiên là *DVKT cần mã máy* vừa nói ở trên. Màn hình lái bằng cấu hình: thêm một danh mục mới về sau chỉ là thêm một mục khai báo, không phải dựng thêm màn hình, đường dẫn hay mục menu.

- **Các danh mục ở khu này CHỈ XEM, không sửa** — có chủ đích. Chúng được nạp từ tệp theo kiểu *thay trọn bộ*, nên sửa tay trên màn hình sẽ bị lần nạp sau xoá sạch; cho sửa là dựng sẵn một cái bẫy.

- **Danh mục Khoa phòng chuyển vào khu này.** Kèm theo một thay đổi cần biết: quyền truy cập **nới từ quản trị hệ thống xuống quản lý danh mục** — đó là hệ quả của việc chuyển khu, không phải sơ suất. Hai mục *DVKT có điều kiện* và *Thuốc có điều kiện* được bỏ khỏi menu nhưng vẫn giữ nguyên đường dẫn và chức năng sửa, vì khu mới cố ý không có chức năng sửa.

- **Lưu ý vận hành: khu này đọc cấu hình, nên sau khi cài đặt phải chạy `php artisan config:clear`**, bằng không menu sẽ rỗng mà không báo lỗi gì.

- **Ba quy tắc báo oan bị bắt tại chỗ ngay khi có dữ liệu thật, xoá khoảng 40.300 lỗi giả.** Lô hồ sơ bảo hiểm thật được nạp cuối ngày đã làm đúng việc mà mục cảnh báo phía trên kêu gọi: đo lại. Kết quả là ba quy tắc lộ ra sai căn cứ chứ không phải dữ liệu sai.

- **Thanh toán toàn bộ cho phẫu thuật thủ thuật lần hai trong ngày: 24.954 lỗi còn 1.773.** Quy tắc cũ coi *mọi dòng có khai mã phẫu thuật thủ thuật* là một ca phẫu thuật thủ thuật, trong khi trường đó được khai cả ở những dòng không phải. Nay căn cứ vào **nhóm dịch vụ 8 và 18** — định nghĩa chính thức của phẫu thuật thủ thuật theo chuẩn.

- **Định dạng liều dùng: 15.585 lỗi còn 0, và ba quy tắc câm lâu nay được đánh thức.** Bộ đọc liều dùng cũ chỉ biết một hình dạng `số * số * số` — một dạng mà **chuẩn không hề nêu ra**; nó bác bỏ cả ba ví dụ in trong chính chuẩn và báo lỗi 100% số dòng thuốc. Chuẩn định nghĩa **ba** hình dạng hợp lệ: ba phần, hai phần (thuốc dùng ngoài không xác định được liều lượng), và theo buổi. Nghiêm trọng hơn con số 15.585: đọc hỏng làm quy tắc thoát sớm, nên ba quy tắc phía sau — đối chiếu số lượng, đơn vị tính, số ngày kê — **chưa từng chạy lần nào**.

- **Một lần tự sửa giữa chừng đáng ghi lại.** Bản viết lại đầu tiên có suy ra tổng lượng cả đợt từ phần trong ngoặc vuông nhân số ngày, sinh 506 khác biệt. Đo lại thì **457 trên 506 (90%) là ảo**: 187 do đơn vị trong ngoặc khác đơn vị thanh toán (`80 Ml` thanh toán theo ml nhưng ngoặc ghi `0,80 Chai/ngày`), 270 do giá trị trong ngoặc đã bị làm tròn rồi nhân lên nhiều ngày (`[1,3 Viên/ngày]` × 30 = 39 trong khi số thật là 40). Ngoặc là **giá trị hiển thị đã làm tròn**, không phải căn cứ đối chiếu — nên quy tắc đối chiếu số lượng được cho im lặng ở dạng này.

- **Ngưỡng kê thuốc nâng từ 30 lên 90 ngày theo Thông tư 26/2025/TT-BYT: 1.609 lỗi còn 1.** Thông tư này hiệu lực 01/7/2025, thay thế Thông tư 52/2017 và 18/2018: mặc định vẫn 30 ngày, nhưng **252 bệnh mạn tính tại Phụ lục VII được kê tới 90 ngày**. Đo trên 1.609 dòng vượt 30 ngày: 99,5% là hồ sơ ngoại trú với mã bệnh chính toàn bệnh mạn tính — tim thiếu máu cục bộ, đặt stent, lupus, suy tim, đái tháo đường, tăng huyết áp — tức kê hợp lệ. **Đây là giải pháp tạm và cần biết rõ:** đặt 90 là nới cho *mọi* bệnh, kể cả bệnh cấp tính lẽ ra chỉ được 30 ngày. Cách đúng là nạp danh mục 252 mã bệnh của Phụ lục VII rồi cho quy tắc dùng 30 ngày mặc định, 90 ngày khi bệnh chính thuộc danh mục đó.

- **Bài học chung của ba ca: một quy tắc báo trên 20% số dòng thì gần như chắc chắn quy tắc sai, không phải dữ liệu sai** — đúng mốc đã ghi trong mục cảnh báo phía trên. Cả ba đều vượt xa mốc đó.

- **Lưu ý vận hành đã cắn một lần trong ngày:** sửa mã xong mà không chạy `php artisan queue:restart` thì tiến trình xử lý hàng đợi vẫn chạy mã cũ, và số lỗi **không đổi chút nào** sau khi nạp lại hồ sơ. Triệu chứng này rất dễ bị đọc nhầm thành "bản sửa không ăn thua".

# 07/09/2026

- **Giấy ra viện: siết mười trường bắt buộc.** Trong đó **Phương pháp điều trị** được đưa lên mức chặn sau khi cổng Bảo hiểm xã hội **từ chối một hồ sơ** vì thiếu trường này. Đây là lần đầu một trường được đưa thẳng lên mức chặn trong khi dữ liệu thật còn thiếu, và là có chủ đích: đo trên 1.050 giấy ra viện đã nạp thì **78 hồ sơ (7,4%) đang bỏ trống** trường này — chúng chính là những hồ sơ cổng sẽ từ chối, nên khoá lại là kết quả mong muốn.
- Chín trường còn lại — Chẩn đoán, Mã bệnh ICD-10, Tên bệnh ICD-10, Ngày chứng từ, Thủ trưởng đơn vị, Tên trưởng khoa, Mã chứng chỉ hành nghề trưởng khoa, Loại giấy tờ, Nghề nghiệp — **đo được rỗng 0%** nên chặn mà không khoá thêm hồ sơ nào. Chạy lại bộ kiểm trên toàn bộ 3.048 chứng từ thật xác nhận: đúng 78 hồ sơ bị chặn, và chỉ do Phương pháp điều trị.
- Riêng **Loại giấy tờ** trước đó đã có luật kiểm giá trị có hợp lệ không, nhưng không ai kiểm nó rỗng — nay bịt nốt kẽ hở đó.
- **Trường này ở Tóm tắt hồ sơ bệnh án vẫn chỉ cảnh báo, không chặn.** Cổng từ chối *giấy ra viện*, không phải tóm tắt bệnh án — hai biểu mẫu khác nhau, và loại kia chưa có bằng chứng nào. Nâng cả hai sẽ khoá thêm 78 hồ sơ mà không có căn cứ. Sự không nhất quán này là có chủ đích và đã được ghi rõ trong mã lẫn tài liệu.
- **Lưu ý vận hành: luật mới không tự áp lên hồ sơ cũ.** Module chứng từ điện tử không có chức năng kiểm lại — bộ kiểm chỉ chạy lúc nạp. Nên 78 hồ sơ nói trên vẫn giữ kết quả kiểm cũ và vẫn gửi được, cho tới khi được **nạp lại**.
- Tài liệu hướng dẫn sử dụng lên phiên bản 1.10, bổ sung mục liệt kê đầy đủ trường bắt buộc của giấy ra viện.

- **Đơn vị hành chính chuyển từ ba cấp sang hai cấp Tỉnh/Xã.** Danh mục cũ có 10.542 xã thuộc 699 huyện của 63 tỉnh; danh mục mới có **3.321 xã thuộc 34 tỉnh**, không còn cấp huyện. Phần mềm nay kiểm cư trú theo hai cấp, và **thêm một quy tắc mới: mã xã phải thuộc mã tỉnh đã khai** — quan hệ lồng nhau duy nhất còn lại sau khi bỏ cấp huyện. Trước đây phần mềm chỉ kiểm hai mã tồn tại rời rạc, nên hồ sơ khai tỉnh Hà Nội kèm xã của Cà Mau vẫn lọt.

- **Có lệnh riêng để thay danh mục: `php artisan hanh-chinh:chuyen-2-cap <tệp Excel>`.** Lệnh hỏi xác nhận, chạy trọn trong một giao dịch, và in số liệu trước/sau để đối chiếu. Dòng của danh mục cũ **không bị xoá** mà chuyển sang trạng thái ngừng dùng — sai thì còn đường lùi, còn xoá thì không.

- **Lệnh dừng và hoàn tác nếu tệp có bất kỳ dòng hỏng nào.** Đây là chỗ suýt sai nguy hiểm: khung nhập danh mục vốn *nuốt* lỗi từng dòng vào bản báo cáo thay vì ném ra ngoài, nên nếu lệnh không tự kiểm thì một lần nhập hỏng hoàn toàn vẫn được ghi nhận thành công — trong khi toàn bộ danh mục đang dùng đã bị tắt từ đầu quy trình. Kết quả sẽ là **danh mục rỗng mà màn hình báo chạy xong**. Nay lệnh đọc kết quả nhập và ném lỗi để hoàn tác.

- **Trước khi chạy lệnh phải chạy migration.** Hai cột mã/tên huyện được nới thành để trống được; chưa nới thì mọi dòng mới đều chèn hỏng — đúng kịch bản vừa nói ở trên. Nên **sao lưu bảng danh mục** trước khi chạy lệnh, và sau khi chạy hãy kiểm một số liệu: số dòng đang dùng mà vẫn còn mã huyện phải bằng **0**.

- **Quy tắc mới được nạp ở trạng thái TẮT, cố ý.** Nó chỉ đúng khi danh mục đã là hai cấp thuần; bật lúc danh mục còn cũ sẽ báo sai hàng loạt vì cặp tỉnh–xã của hai danh mục khác hẳn nhau. Sau khi đổi danh mục xong và rà thử một lô hồ sơ, người vận hành tự bật bằng ô tích **"Có kiểm tra"** ở màn *Danh mục mã lỗi XML 3176*.

- **Module theo Quyết định 130 cố ý không sửa** (đã dừng dùng). Sau khi đổi danh mục, nếu ai đó nhập hồ sơ vào màn đó sẽ thấy báo lỗi cấp huyện hàng loạt — đó là hệ quả đã biết và chấp nhận, không phải hỏng hóc mới.

- **Hồ sơ trước thời điểm sáp nhập sẽ báo sai nếu nhập lại.** Đây là đánh đổi có chủ ý để khỏi phải làm cơ chế danh mục có hiệu lực theo từng mốc thời gian. Dòng cũ vẫn nằm trong bảng ở trạng thái ngừng dùng, nên nếu về sau phát sinh nhu cầu quyết toán bổ sung kỳ cũ thì vẫn còn đường làm tiếp.

- **Cài đặt: nay là SÁU lệnh nạp danh mục mã lỗi, không phải năm.**

```bash
php artisan db:seed --class=Xml3176ErrorCatalogHanhChinhSeeder
```

  Bổ sung vào năm lệnh đã liệt kê ở mục 04/09/2026. Quên lệnh này thì mã lỗi mới **tự bật ở mức nghiêm trọng và chặn xuất hồ sơ** — vì mã lỗi lạ chưa có trong danh mục luôn được mặc định coi là nghiêm trọng.

# 04/09/2026

- **Bổ sung tám quy tắc kiểm hồ sơ XML 3176, bám theo danh sách lỗi tự động do Bảo hiểm xã hội gửi về.** Nguồn là các tệp giám định tháng 08 và 09/2026 của cơ sở 01929 — hơn 4.300 dòng chi phí bị xuất toán, cùng một bảng tổng hợp 60 loại lỗi. Đối chiếu từng loại với quy tắc sẵn có cho thấy khoảng 50 loại phần mềm đã bắt từ trước; tám quy tắc thêm lần này nhắm đúng phần còn thiếu, ưu tiên theo số tiền chứ không theo số dòng.

- **Mức hưởng khai sai — nhóm lớn nhất, 1.044 dòng bị xuất toán.** Phần mềm nay tự tính mức hưởng đúng rồi so với mức cơ sở khai. Hai trường hợp: vào viện **đúng tuyến** mà chi phí một lần khám chữa bệnh từ 15% lương cơ sở trở lên thì phải hưởng theo **quyền lợi ghi trên thẻ** (ký tự thứ ba của mã thẻ), không được khai 100%; vào viện **trái tuyến điều trị nội trú tuyến trung ương** thì mức hưởng là 40%. Thực tế cơ sở khai 100% cho cả hai, nên bị trừ 20% và 60% tương ứng. Bảng quy đổi ký tự quyền lợi sang phần trăm để trong tệp cấu hình, sửa được mà không cần đụng mã.

- **Quy tắc trái tuyến chỉ chạy khi biết chắc cơ sở thuộc tuyến trung ương.** Nếu danh mục cơ sở khám chữa bệnh chưa ghi tuyến chuyên môn kỹ thuật thì quy tắc **im lặng** thay vì đoán. Bỏ sót còn hơn báo oan hàng loạt trên một nhóm hồ sơ lớn — nhưng nghĩa là **phải rà lại danh mục cơ sở**, bằng không 241 dòng trái tuyến sẽ không được cảnh báo trước khi gửi.

- **Vật tư y tế thanh toán sai tỷ lệ — 47 dòng.** Danh mục vật tư của cơ sở vốn đã có sẵn cột tỷ lệ thanh toán bảo hiểm, chỉ là trước nay phần mềm không đối chiếu. Nay so tỷ lệ khai trong hồ sơ với tỷ lệ đã duyệt trong danh mục. Không phải thêm cột nào.

- **Tổng ngày giường thấp hơn hướng dẫn của Bộ Y tế (Thông tư 39) — 166 lỗi trên 27 hồ sơ.** Đây là lỗi **thiếu** ngày giường chứ không phải thừa, nên các quy tắc ngày giường cũ (vốn chỉ bắt thừa) không thấy. Phần mềm tính lại số ngày điều trị theo đúng cách của Thông tư 39 — đếm theo **ngày dương lịch**, cộng thêm một ngày cho các trường hợp tử vong, chuyển viện, nặng xin về, và bỏ qua ca lưu trú dưới 4 giờ — rồi so với tổng ngày giường đã khai.

- **Riêng quy tắc ngày giường này là CẢNH BÁO, không chặn xuất hồ sơ**, đúng bản chất mà cơ quan bảo hiểm xếp loại: đây là dấu hiệu dữ liệu bất nhất, không phải khoản trừ tiền. **Nhưng nó chỉ là cảnh báo nếu danh mục mã lỗi đã được nạp** — chưa nạp thì phần mềm mặc định coi mọi mã lỗi lạ là lỗi nghiêm trọng và sẽ **chặn xuất XML**. Xem mục cài đặt cuối bài.

- **Ba quy tắc về công khám cho hồ sơ ngoại trú.** Trước nay phần mềm chỉ kiểm thừa công khám với hồ sơ nội trú, nên hồ sơ loại "Khám bệnh" lọt lưới. Nay bắt: **một dịch vụ khám bị tính quá một lần**; **tổng tiền khám vượt quá hai lần mức giá một lần khám**; và **từ lần khám thứ hai trở đi chưa hạ xuống 30% mức giá**. Ba quy tắc bổ trợ nhau chứ không trùng — khai hai lần khám cùng giá đầy đủ thì tổng đúng bằng trần nên quy tắc trần không thấy, phải quy tắc 30% mới bắt được.

- **Khám nhiều chuyên khoa khác nhau vẫn hoàn toàn hợp lệ và không bị bắt.** Thông tư 39 cho phép rõ điều này, chỉ hạ giá từ lần thứ hai. Quy tắc chỉ soi **cùng một mã dịch vụ** lặp lại, và soi trên **số tiền thực đề nghị** chứ không soi đơn giá — để đúng dù cơ sở ghi phần giảm ở đơn giá hay ở tỷ lệ.

- **Hai dịch vụ phẫu thuật, thủ thuật chồng thời gian thực hiện trong cùng một hồ sơ.** Một người bệnh không thể trải qua hai ca cùng lúc. Cố ý chỉ soi nhóm phẫu thuật, thủ thuật: xét nghiệm hay chẩn đoán hình ảnh của một đợt thường được ghi cùng một khung giờ, soi rộng ra sẽ báo oan hàng loạt. Hai dịch vụ nối tiếp nhau — ca này kết thúc đúng lúc ca kia bắt đầu — không bị coi là chồng.

- **Phần việc bằng mã đã gần cạn; phần còn lại vướng dữ liệu chứ không vướng phần mềm.** Rà hết 60 loại lỗi, những loại chưa bắt được đều thiếu **nguồn dữ liệu đối chiếu**, không phải thiếu quy tắc:
  - **Mã bác sĩ chưa phải trưởng khoa, phó khoa được ủy quyền — 48 lỗi.** Biểu mẫu 02 của Thông tư 12 **có** trường Vị trí và đường nạp danh mục đã nhận trường này, nhưng quy định để trường đó **không bắt buộc** nên bộ phận lập danh mục đang bỏ trống — kiểm tra hai tệp Mẫu 02 gần nhất và cả cơ sở dữ liệu đều trống 100%. **Điền trường Vị trí rồi nạp lại danh mục là mở khoá được 48 lỗi này**, không cần sửa phần mềm. Đây là việc đáng làm nhất hiện nay.
  - **Thuốc thanh toán sai tỷ lệ.** Không làm được qua danh mục: bản thân **Biểu mẫu 03 không có trường tỷ lệ thanh toán** — khác với biểu mẫu vật tư vốn có. Tỷ lệ thanh toán thuốc thuộc danh mục quốc gia theo Thông tư 20/2022 của Bộ Y tế, muốn kiểm phải nạp một bảng tham chiếu riêng.
  - **Dịch vụ trẻ em chỉ định cho người lớn, thời gian tối thiểu của chẩn đoán hình ảnh, thuốc cản quang, và các cờ mã bệnh.** Cùng lý do — biểu mẫu 05 và danh mục mã bệnh hiện hành không mang các thông tin này.

- **Cài đặt: phải chạy năm lệnh nạp danh mục mã lỗi thì các quy tắc mới có hiệu lực.**

```bash
php artisan db:seed --class=Xml3176ErrorCatalogTyLeVtytSeeder
php artisan db:seed --class=Xml3176ErrorCatalogMucHuongSeeder
php artisan db:seed --class=Xml3176ErrorCatalogBedDaysTT39Seeder
php artisan db:seed --class=Xml3176ErrorCatalogExaminationSeeder
php artisan db:seed --class=Xml3176ErrorCatalogOverlapServiceSeeder
```

  Lệnh thứ ba là **bắt buộc**, không phải tuỳ chọn: bỏ qua thì quy tắc ngày giường vốn là cảnh báo mềm sẽ bị hiểu thành lỗi nghiêm trọng và chặn xuất XML. Cả năm lệnh chạy lại nhiều lần đều an toàn.

- **Sau khi bật, nên chạy thử một lô hồ sơ thật rồi xem tỷ lệ cảnh báo trước khi tin.** Các ngưỡng đều nằm trong tệp cấu hình — dung sai ngày giường, hệ số trần tiền khám, nhóm dịch vụ xét chồng giờ, bảng quyền lợi thẻ — chỉnh được mà không phải sửa mã.

# 27/08/2026

- **Danh mục TT12: màn Dashboard độ phủ danh mục.** Menu **Hồ sơ XML → Danh mục TT12 → Dashboard danh mục**. Lưới sáu hàng là sáu mẫu, mỗi cột một cơ sở khám chữa bệnh; ô xanh ghi "Đã tiếp nhận" kèm ngày và số dòng của lần gửi gần nhất, ô xám ghi "Chưa gửi". Màn Danh sách hồ sơ trả lời được "hồ sơ này đang ở đâu", nhưng không trả lời được câu hỏi mà người phụ trách danh mục thực sự cần: **trong sáu mẫu của từng cơ sở, cái nào cổng đã nhận và cái nào chưa**. Không có màn này thì để sót cả một mẫu chưa gửi bao giờ mà không ai biết, vì thứ không tồn tại thì không hiện ra ở bất kỳ danh sách nào.
- **Số dòng hiển thị là của riêng lần gửi gần nhất, không cộng dồn.** Lần gửi sau thay thế lần trước chứ không thêm vào, nên cộng dồn qua các lần gửi sẽ ra một con số không có nghĩa gì.
- **Màn hình này cố ý không có bộ lọc thời gian, và cố ý không đánh dấu "quá hạn".** "Đã được cổng tiếp nhận hay chưa" là câu hỏi trên toàn bộ thời gian — giới hạn theo khoảng ngày sẽ làm một mẫu gửi từ tháng trước hiện thành chưa gửi, tức báo động giả cho đúng những cơ sở đang làm đúng. Còn Thông tư 12 không quy định chu kỳ gửi cố định: danh mục chỉ gửi khi có thay đổi, nên không có căn cứ nào để gọi một mẫu là trễ hạn.
- **Bấm vào một ô sẽ mở màn Danh sách hồ sơ đã lọc sẵn theo đúng mẫu và cơ sở của ô đó**, kèm khoảng ngày mở rộng toàn thời gian. Lưu ý phần khoảng ngày: màn Danh sách mặc định lọc "Hôm nay", nên nếu drill-down không nới khoảng ngày ra thì bấm vào một ô xanh ghi ngày của tháng trước sẽ ra **danh sách rỗng** — đúng cái ô vừa nói là đã gửi thành công. Ô ngày trên màn Danh sách vì thế sẽ hiện một khoảng rất rộng sau khi bấm từ Dashboard sang; đó là chủ ý, không phải lỗi.
- **Cột của cơ sở không còn trong danh sách hiện hành được đánh dấu riêng** — dữ liệu cũ vẫn phải xem được, nhưng người đọc cần biết mình đang nhìn một cơ sở không còn hoạt động. Khi không đọc được danh sách cơ sở từ hệ thống HIS thì **không ô nào bị đánh dấu**, và màn hình báo rõ là đang thiếu danh sách. "Không biết" và "biết là không còn" là hai chuyện khác nhau; đánh dấu nhầm cả bảng khi HIS tạm dừng còn tệ hơn không đánh dấu gì.
- **Khối "Hồ sơ đang dở dang"** bên dưới lưới đếm hồ sơ đã nạp nhưng chưa được tiếp nhận, tách theo từng trạng thái. Phải đọc cả khối này: lưới chỉ nói về việc đã xong, nên một hồ sơ đang kẹt ở "Gửi lỗi" sẽ không hiện ra ở lưới.
- Tài liệu hướng dẫn sử dụng lên **phiên bản 1.6** (mục 7.12 mới).

# 26/08/2026

- **Hồ sơ danh mục TT12 đầu tiên được cổng Bảo hiểm xã hội tiếp nhận thật** — mã kết quả 200, lúc 10:41 ngày 26/08, và đồng bộ được sang bộ danh mục dùng để kiểm hồ sơ. Lần gửi này đóng **hai hạng mục nghiệm thu treo lâu nay** mà trước đó chỉ suy từ tài liệu chứ chưa có bằng chứng: tên các trường trong thân yêu cầu là đúng, và cổng chấp nhận dạng chữ ký chỉ có **một** thẻ tham chiếu. Bản PDF tài liệu kỹ thuật của Thông tư 12 hỏng ở nhiều chỗ nên hai điểm này trước đó đều là phỏng đoán.
- **Trước đó chức năng gửi chưa từng gửi được lần nào**, vì một cái bẫy đã cắn kho mã này **ba lần**: khai kiểu dữ liệu cho tham số của tiến trình nền khiến hệ thống tự dựng sẵn một dịch vụ rỗng, và dịch vụ rỗng đó không có mã cơ sở khám chữa bệnh nên mọi lần gửi đều dừng ngay ở bước lấy vé đăng nhập. Tám phép kiểm tự động không bắt được vì tất cả đều truyền dịch vụ giả bằng tay, tức chưa bao giờ đi qua đúng con đường mà bản chạy thật đi. Nay có thêm phép kiểm đi đúng đường đó, kèm một phép kiểm "chim hoàng yến" sẽ đỏ lên nếu bản Laravel sau này đổi hành vi.
- **Nút "Ký và gửi" nay chỉ cần bấm MỘT lần cho cả hai bước.** Trước đây mỗi lần bấm chỉ làm một việc: chưa ký thì đẩy vào hàng ký rồi dừng, phải bấm lần thứ hai mới gửi. Tên nút nói một đằng, hành vi một nẻo — và ở đường gửi hàng loạt thì câu "đã đẩy 30 hồ sơ vào hàng đợi ký và 0 hồ sơ vào hàng đợi gửi" rất dễ đọc lướt thành "đã gửi xong 30". Nay một lần bấm chạy trọn chuỗi ký rồi gửi. Đây là **giảm thao tác cho điều dưỡng**, không phải đổi cho gọn mã.
- **Rút từ hai lần bấm xuống một làm rộng cửa sổ bấm trùng**, nên đi kèm là khoá chống xử lý trùng dùng **ràng buộc duy nhất ở cơ sở dữ liệu** chứ không phải bộ nhớ đệm dạng tệp — ở dạng tệp thì thao tác "đặt khoá nếu chưa có" không nguyên tử, bài học đã rút một lần ở module chứng từ điện tử. Trước đây người dùng phải bấm đúng hai lần đúng thứ tự, bản thân việc đó đã là một cái phanh; bỏ phanh đi thì hai lần bấm nhanh là hai lần gửi thật lên cổng, và nút gửi hàng loạt nhân nó lên theo số hồ sơ một lô.
- **Tách ba hàng đợi riêng cho module: kiểm, ký, và gửi.** **Khi triển khai phải cài thêm hai dịch vụ mới** (`QLBV JobSignTt12` và `QLBV JobSubmitTt12`) — ba tệp `install_service.bat`, `update.bat`, `remove_service.bat` đã khớp nhau đủ 21 dịch vụ. **Đây là thay đổi phá vỡ**: thiếu hai dịch vụ này thì hồ sơ ký xong nằm im, không bao giờ đi, và không có thông báo nào trên màn hình.
- **Trần 50 hồ sơ mỗi lượt gửi nhiều**, chốt ở cả trình duyệt lẫn máy chủ, và chỉ chọn được trong trang đang xem — cùng lý do đã áp cho chứng từ điện tử: cổng không có đường rút lại.
- **Màn chi tiết hồ sơ chuyển sang dạng hộp thoại** mở ngay trên màn danh sách, giữ nguyên trang và bộ lọc phía sau; hộp xác nhận và thông báo dùng chung một kiểu với chứng từ điện tử.
- **Bỏ ba quy tắc kiểm lỗi xét trên toàn tệp** theo yêu cầu nghiệp vụ: trùng số thứ tự, hai dòng cùng mở hiệu lực, và hiệu lực chồng lấn. Kèm theo là một cải thiện bộ nhớ thật: ba quy tắc đó buộc phần mềm phải giữ **một mảng cho mỗi dòng của cả tệp** xuyên suốt quá trình kiểm — bỏ đi thì tệp 30.000 dòng chỉ còn giữ đúng một lô tại mỗi thời điểm, đúng điều mà cơ chế đọc theo lô sinh ra để đạt trên máy chủ 128 MB.
- **Nới giới hạn độ dài hai cột theo dữ liệu thật**, sau khi đối chiếu 12 tệp Excel của hai cơ sở: Mẫu 03 cột `MA_CSKCB_THUOC` từ 5 lên 7 ký tự, Mẫu 06 cột `SO_LUU_HANH` từ 20 lên 100. Hai giới hạn cũ lấy từ bảng đặc tả trong bản PDF vốn hỏng ở nhiều chỗ, nên số đo từ tệp thật của cơ sở đáng tin hơn. Nới nghĩa là phần mềm không chặn sớm nữa; nếu cổng thật sự từ chối thì lộ ra ở mã kết quả kèm thông điệp của cổng, chỗ đọc được lý do.
- **Tách địa chỉ cổng Bảo hiểm xã hội ra tệp cấu hình riêng** để đổi giữa môi trường thử và môi trường thật mà không phải sửa mã.
- Tài liệu hướng dẫn sử dụng lên **1.4** (bổ sung Phần VII — Danh mục TT12), rồi **1.5** (một lần bấm ký và gửi).

# 25/08/2026

- **Module Danh mục theo Thông tư 12/2026 đi vào hoạt động.** Menu **Hồ sơ XML → Danh mục TT12**, quyền `xml-man` (cùng quyền với XML 3176 vì cùng nhóm người dùng). Quy trình đủ vòng: tải biểu mẫu Excel rỗng cho từng mẫu, điền rồi nạp lên, phần mềm kiểm tính đúng sai, ký số, gửi lên cổng, và **đồng bộ sang chính bộ danh mục mà phần mềm dùng để kiểm hồ sơ XML 3176** — tức danh mục đã gửi cho bảo hiểm và danh mục dùng để tự kiểm là một, không còn hai bản lệch nhau.
- **Sáu mẫu danh mục** theo Thông tư 12, mỗi mẫu có biểu mẫu Excel riêng tải thẳng từ màn hình, nên người điền không phải tự dựng tiêu đề cột.
- **Tệp Excel được đọc theo lô chứ không nạp cả tệp vào bộ nhớ** — máy chủ giới hạn 128 MB và 120 giây, mà một tệp danh mục thuốc thật có thể hàng chục nghìn dòng. Cùng lý do, việc kiểm lỗi cũng ghi theo từng lô.
- **Nạp đè một tệp sẽ xoá sạch dữ liệu cũ của hồ sơ đó ở cả ba tầng**, kể cả các dòng con mồ côi khi tệp bị từ chối giữa chừng — nạp một tệp hỏng rồi nạp lại tệp đúng thì không còn sót dòng của lần trước.
- **Tab Nhật ký gửi và xuất Excel nhật ký theo khoảng ngày**: truy vết ai gửi hồ sơ nào, lúc nào, cổng trả lời ra sao.
- **Hai nút cứu hộ — Kiểm lại và Đồng bộ lại danh mục** — cho hai tình huống hay gặp: hồ sơ kẹt vì lượt kiểm trước hỏng giữa chừng, và hồ sơ cổng đã tiếp nhận nhưng bước ghi sang danh mục hỏng. Trường hợp thứ hai **không được bấm gửi lại**: cổng đã nhận rồi, gửi lại là gửi trùng.
- **Hai tệp cấu hình của module nằm ngoài kho mã và phải khai tay trên từng máy**: khối `tt12` trong `config/organization.php` (bật tắt ký, bật tắt gửi, ba tên hàng đợi) và đĩa `exportTt12` trong `config/filesystems.php` (nơi ghi tệp XML đã ký). Thiếu đĩa thì báo lỗi đúng lúc người dùng bấm ký; thiếu hàng đợi kiểm thì hồ sơ nằm im ở "Chưa kiểm". Cả hai đều hỏng lặng lẽ.

# 24/08/2026

- **Chứng từ điện tử: tích chọn nhiều hồ sơ rồi ký số và gửi bằng một lần bấm.** Trước đây phải mở từng hồ sơ bấm "Ký và gửi" — với một loạt hồ sơ ký hỏng hoặc gửi hỏng thì đó là hàng chục lần mở rồi đóng. Nay cột đầu bảng có ô tích, ô tích trên tiêu đề chọn hết trang, và nút hiện luôn số đang chọn. Sau khi gửi có bảng kết quả chia hai phần: đã xếp hàng, và bị bỏ qua kèm **lý do từng hồ sơ** — một hồ sơ không đủ điều kiện không làm cả lượt thất bại.
- **Chỉ chọn được trong trang đang xem, tối đa 50 hồ sơ mỗi lượt.** Không làm "chọn tất cả hồ sơ khớp bộ lọc": người bấm sẽ gửi những hồ sơ mình chưa từng nhìn, và chỉ cần sai bộ lọc một chút là gửi sai hàng loạt lên cổng — nơi không có đường rút lại. Trần 50 kiểm ở máy chủ chứ không chỉ ở trình duyệt.
- Hai loại hồ sơ **vẫn gửi lại được ở màn chi tiết nhưng không gửi hàng loạt được**: hồ sơ cổng đã tiếp nhận và còn giữ mã giao dịch, và hồ sơ cổng đã từ chối. Cả hai cần người đọc lịch sử gửi của **từng cái** trước khi quyết định. Riêng loại thứ nhất là chỗ dễ sót: điều kiện hỏi xác nhận hiện có chỉ áp cho hồ sơ đã bị nạp đè xoá mất mã giao dịch, nên một hồ sơ **còn giữ** mã giao dịch không bị hỏi gì cả — ở màn chi tiết thì ổn vì nhãn nút đã đổi thành "Ký và gửi lại" và người bấm đang nhìn đúng hồ sơ đó, nhưng trong một lượt 50 dòng thì nó thành gửi lại im lặng.
- **Đổi khoá chống xử lý trùng sang cơ chế nguyên tử thật.** Khoá cũ dựa trên bộ nhớ đệm dạng tệp, mà ở dạng đó thao tác "đặt khoá nếu chưa có" không nguyên tử — có một khe rất hẹp cho hai luồng cùng thấy "chưa có khoá" rồi cùng xếp hàng, tức **hai lần gửi thật cho cùng một hồ sơ**. Khe đó trước đây hẹp; nút gửi hàng loạt làm nó rộng ra theo số hồ sơ trong một lượt. Nay dùng ràng buộc duy nhất ở cơ sở dữ liệu, và vì thế **nút gửi đơn lẻ cùng tiến trình nạp tự động cũng được vá theo**.
- **Màn danh sách thêm cột Số CCCD và Mã BHXH**, tìm được theo cả hai — gõ được cả một phần, ví dụ bốn số cuối của căn cước. Ô Tìm nay nhận phím Enter, không phải di chuột sang nút Tải dữ liệu. Tệp Excel "Xuất danh sách" thêm hai cột tương ứng.
- Rà tên trường trên 3.048 chứng từ đã nạp thật cho thấy số căn cước có ở 98% bản ghi và mã bảo hiểm xã hội có ở 100%. Dữ liệu cũ đã được điền đủ khi nâng cấp, không phải chờ nạp lại.
- **Tài liệu hướng dẫn sử dụng lên phiên bản 1.3** với các mục mới về gửi hàng loạt và hai cột nói trên.

# 22/08/2026

- **Tài liệu hướng dẫn sử dụng lên phiên bản 1.2**, bổ sung Phần VI — Chứng từ điện tử theo Phụ lục 02: nạp hồ sơ, chín trạng thái gửi, ký số và gửi lên cổng, ba bảng xuất Excel và màn Dashboard chứng từ. Cùng đợt, tài liệu bắt đầu **đánh số phiên bản theo từng đợt**: có mục "Lịch sử cập nhật tài liệu" liệt kê các bản đã phát hành, và số phiên bản in ở chân **mọi trang** — một tờ in rời ra khỏi tập vẫn biết thuộc bản nào. Trước đây số phiên bản chỉ nằm ở trang bìa nên mỗi đợt bổ sung lại phải nhớ sửa tay, mà quên sửa thì tài liệu vẫn dựng ra bình thường, chỉ mang số cũ.
- Phụ lục B của tài liệu (danh sách tiến trình nền) trước đó liệt kê mọi tiến trình **trừ** bốn tiến trình của module chứng từ điện tử. Nay đã đủ, kèm cột "Dấu hiệu khi dừng" cho từng cái.
- **Lệnh quét chứng từ điện tử được đưa vào bộ cài dịch vụ.** Trước đây `remove_service.bat` không gỡ **bất kỳ** dịch vụ chứng từ điện tử nào, kể cả ba hàng đợi đã có từ trước — gỡ không hết thì lần cài lại gặp dịch vụ cũ còn trỏ tới đường dẫn cũ, và công cụ quản lý dịch vụ không báo gì cả. Nay ba tệp `install_service.bat`, `update.bat`, `remove_service.bat` khớp nhau đủ 18 dịch vụ.

# 21/08/2026

- **Hai lỗi sai tên trường làm mất dữ liệu âm thầm.** Rà lại toàn bộ 3.048 chứng từ đã nạp thật (chứ không đọc theo tài liệu Phụ lục 02) phát hiện phần mềm đang đọc sai tên hai trường: tên bệnh theo ICD-10 ở chứng từ CT03 sai ở **1.050/1.050** hồ sơ, và mã bệnh ICD-10 của giấy điều trị nội trú sai ở **924/924** hồ sơ. Sai tên trường thì phần mềm đọc ra rỗng và gửi đi rỗng — không có thông báo lỗi nào, vì với nó trường đó vốn không tồn tại. Cả hai đã sửa theo đúng tên trong hồ sơ thật.
- **Chứng từ điện tử: nạp và gửi tự động liên tục.** Phần mềm nghiệp vụ chỉ cần ghi tệp XML vào một thư mục quy ước; tiến trình nền quét khoảng 5 giây một lượt, tự nạp và kiểm lỗi. Việc **tự ký và tự gửi lên cổng là một công tắc riêng, mặc định tắt** — cho phép người bấm nút gửi và cho phép máy gửi khi không có ai nhìn là hai mức tin cậy khác nhau nên dùng hai công tắc khác nhau. Khi bật, một hồ sơ mất khoảng 10–15 giây từ lúc tệp xuất hiện tới lúc gọi lên cổng.
- Kèm theo là hai hàng rào **chống gửi trùng**, thứ nguy hiểm nhất ở nghiệp vụ này vì chứng từ Phụ lục 02 không mang mã giao dịch phía người gửi, nên cổng Bảo hiểm xã hội **không có căn cứ để nhận ra bản trùng**. Thứ nhất: mỗi hồ sơ chỉ được tiến trình tự động gửi **đúng một lần** — gửi hỏng thì nằm lại chờ người xử lý tay, máy không tự thử lại. Nếu không có hàng rào này, một hồ sơ mà cổng đã nhận nhưng phản hồi bị thất lạc sẽ bị gửi lại vô hạn, cứ 5 giây một lần. Thứ hai: đặt một tệp rỗng tên `DUNG-GUI` vào thư mục quét là dừng gửi ngay lượt sau, không cần khởi động lại dịch vụ và không cần quyền quản trị máy chủ.
- **Xem chi tiết hồ sơ chuyển sang dạng hộp thoại** mở ngay trên màn danh sách, thay vì chuyển sang trang riêng rồi bấm quay lại. Danh sách phía sau giữ nguyên trang và bộ lọc đang xem.
- **Ba nút xuất Excel** ở màn danh sách: xuất danh sách hồ sơ (đối chiếu với bảng kê của cơ quan bảo hiểm), xuất bảng lỗi (mỗi lỗi một dòng, gửi cho bộ phận nhập liệu đi sửa), xuất nhật ký gửi (truy vết ai gửi hồ sơ nào, lúc nào, cổng trả lời ra sao). Cả ba xuất theo **đúng bộ lọc của lần bấm "Tải dữ liệu" gần nhất**, không phải bộ lọc đang hiện trên màn hình — để tệp xuất luôn khớp với bảng người dùng đang nhìn. Đổi ô lọc mà chưa tải lại thì tệp vẫn theo bộ lọc cũ, đúng bằng những gì đang hiển thị.
- **Màn Dashboard chứng từ** trả lời câu hỏi "hệ thống có đang chạy đúng không": ba hàng đợi kèm **việc chờ lâu nhất đã bao nhiêu phút**, hồ sơ theo trạng thái, tồn đọng kèm **hồ sơ cũ nhất đã nằm bao nhiêu ngày**, sản lượng theo ngày, mã lỗi hay gặp và cơ sở sai nhiều nhất. Hai con số "bao nhiêu phút" và "bao nhiêu ngày" mới là chỗ đáng đọc: một hàng đợi đã chết hiển thị số 0 việc đang chờ, trông y hệt một hàng đợi khoẻ đang rảnh; còn tồn đọng tăng dần thì bắt được tình trạng hỏng chậm mà biểu đồ sản lượng không chỉ ra, vì lượng nạp mỗi ngày vẫn bình thường.
- **Bộ kiểm tra tự động của phần mềm không còn động được vào cơ sở dữ liệu đang chạy.** Một lượt chạy kiểm thử đã xoá sạch cơ sở dữ liệu phát triển. Nay có ba lớp chặn độc lập: bộ kiểm thử bị ép trỏ sang một cơ sở dữ liệu vứt đi, bị chặn theo tên cơ sở dữ liệu, và có một phép kiểm quét mã nguồn tìm những lệnh có khả năng làm mới toàn bộ cấu trúc bảng.

# 20/08/2026

- **Module Chứng từ điện tử theo Phụ lục 02** đi vào hoạt động: nạp hồ sơ, kiểm tra dữ liệu, ký số và gửi lên cổng Bảo hiểm xã hội cho ba nhóm giấy tờ — chứng từ theo Thông tư 25/2025, giấy chứng sinh và giấy báo tử. Menu **Hồ sơ XML → Chứng từ điện tử**, quyền `xml-man` (cùng quyền với XML 3176 vì cùng nhóm người dùng và cùng nghiệp vụ).
- **Cột trạng thái gửi phân biệt chín tình huống thay vì gộp thành "chưa gửi".** Chưa kiểm, còn lỗi chặn, ký số thất bại, chưa ký số, chức năng gửi đang tắt, chờ gửi, đã gửi, cổng từ chối, gửi thất bại. Mỗi tình huống cần một người khác nhau xử lý: hồ sơ "chưa kiểm" là việc của bộ phận công nghệ thông tin, "còn lỗi chặn" là việc của người nhập liệu, "chức năng gửi đang tắt" là việc của quản trị. Gộp cả năm lý do chưa gửi thành một chữ là để người ta ngồi chờ một hồ sơ vĩnh viễn không bao giờ được gửi.
- Trong đó, **"chưa kiểm" được tách hẳn khỏi "đã kiểm và sạch"** dù cả hai đều hiện số lỗi bằng 0. Với hồ sơ chưa kiểm, con số 0 không có nghĩa là sạch — nó có nghĩa là chưa ai nhìn. Không tách ra thì cửa chặn gửi chỉ đóng khi tiến trình kiểm đang chạy, và **không có gì báo cho ai biết khi nó không chạy**.
- **Chống bấm hai lần nút "Ký và gửi".** Bấm lần thứ hai trong lúc lần thứ nhất còn đang chạy sẽ tạo ra một chứng từ trùng trên cổng mà không có cách nào rút lại. Nút nay bị khoá theo từng hồ sơ trong suốt thời gian chuỗi ký — gửi còn chạy.
- **Cảnh báo khi gửi lại một hồ sơ mà cổng có thể đã nhận.** Nạp đè một hồ sơ sẽ xoá mã giao dịch của lần gửi trước, vì nội dung đã đổi thì kết quả cũ nói về một bản khác. Hồ sơ như vậy hiện lại là "chưa ký số" và gửi lại được bình thường — dấu vết duy nhất còn nằm ở phần Lịch sử gửi trong màn chi tiết. Nay bấm gửi một hồ sơ như vậy sẽ bị hỏi xác nhận. Cảnh báo chứ không chặn cứng: gửi lại sau khi sửa nội dung là việc hợp lệ, chỉ cần người bấm nhìn thấy mình đang làm gì.
- **Điều chỉnh danh sách trường bắt buộc theo công văn 2076**, đo trên hồ sơ thật trước khi siết: bỏ `MA_YTE` khỏi nhóm bắt buộc, `MA_THE` thôi sinh cảnh báo, `MA_BHXH` thành bắt buộc ở năm loại giấy tờ và cảnh báo ở ba loại còn lại, trường ngày sinh chấp nhận dạng chỉ có năm. Siết mà không đo trước thì cả loạt hồ sơ hợp lệ sẽ bị chặn gửi.
- Cùng đợt: nới cột lưu mã giao dịch lên 100 ký tự — mã thật cổng trả về dài 52 ký tự, cột cũ không chứa đủ. Và nâng thời hạn hàng đợi giao lại việc lên 300 giây, để một chuỗi ký — gửi đang chạy không bị giao cho worker thứ hai làm lại từ đầu.
- **Bịt một lỗ hổng chèn mã ở màn danh sách XML 3176** phát hiện khi rà cùng khuôn với module mới: dữ liệu hiển thị trên bảng chưa được lược thẻ HTML.

# 17/08/2026

- **Màn hình Tra cứu lỗi hồ sơ theo mã điều trị.** Cùng một đợt điều trị có thể bị ghi nhận lỗi ở ba nơi khác nhau: sai sót y lệnh, lỗi tra thẻ bảo hiểm y tế và lỗi hồ sơ XML 3176 do cổng trả về. Trước đây muốn biết một hồ sơ có vấn đề gì thì phải mở lần lượt ba màn hình và tự lọc ở từng nơi. Nay nhập hoặc **quét mã vạch** trên phiếu là ra đủ ba bảng lỗi kèm thông tin hành chính của hồ sơ, in được thành phiếu A4 để kẹp bệnh án, và tra lại thẻ bảo hiểm y tế ngay tại chỗ.
- Quyền `tra-cuu-loi-ho-so` được cấp riêng, tách khỏi quyền `order-check`, để nhân viên khoa phòng tra cứu được hồ sơ của mình mà không cần mở quyền quản trị toàn bộ danh sách vi phạm.
- Một lượt tra cứu bị lỗi sẽ **ẩn toàn bộ kết quả cũ** thay vì để nguyên trên màn hình. Khi quét liên tiếp nhiều phiếu, người dùng phải không bao giờ nhìn thấy kết quả của hồ sơ trước mà tưởng là hồ sơ vừa quét.
- Chức năng **quét mã bằng camera** của điện thoại và máy tính đã được làm rồi **gỡ bỏ**: qua sáu lần thử nới các ràng buộc, camera của thiết bị thông thường vẫn cho ảnh quá thấp so với mức cần thiết để đọc mã vạch. Dùng máy quét cầm tay hoặc gõ tay.
- Tài liệu hướng dẫn sử dụng bổ sung Phần V cho màn hình này.

# 11/08/2026

- **Báo cáo giao ban - trình chiếu: ghi chú khoa quá dài không còn nuốt mất số liệu**. Trước đây khối ghi chú không bị giới hạn chiều cao nên nó ép bảng tiêu chí của khoa co lại; đo trên dữ liệu thật ngày 11/08 ở khung 1600×900: ghi chú dài 787–1518 ký tự chiếm 518–947px trên màn cao 900px, bảng tiêu chí chỉ còn 0–84px và bị cắt mất 266–312px. Nghĩa là **số liệu của khoa biến mất khỏi màn chiếu**, mà bản thân ghi chú vẫn bị cắt cụt ở đáy. Nay ghi chú luôn nằm ở slide riêng ngay sau slide của khoa, và tự cắt thành nhiều slide nối tiếp khi dài — tiêu đề ghi "Khoa X — Ghi chú (2/3)", nút "☰ Khoa" liệt kê đủ các trang để nhảy thẳng tới. Không cắt giữa một đoạn nên một bệnh nhân không bị đứt đôi giữa hai slide. Ghi chú chung ở màn Tổng quan cũng theo cùng cách.
- Cùng đợt: bảng tiêu chí thôi chia đôi chiều cao cứng với khối "BS trực / ĐD trực" bên dưới. Trước đây khoa **không** có ghi chú dài (Khoa Khám bệnh) vẫn bị cắt mất 89px nội dung bảng dù còn thừa chỗ trống.
- Ghi chú gõ liền một mạch trong **một đoạn duy nhất, ngăn dòng bằng phím Enter mềm** (có khoa đang gõ cả 1417 ký tự như vậy) nay vẫn cắt trang được. Trước khi sửa, đoạn kiểu đó là một khối không cắt được: hôm nay vừa đúng một trang, dài thêm là phần dư bị ẩn mà không có dấu hiệu gì.
- **Bản xuất PowerPoint: sửa hai lỗi lộ ra khi chạy trên dữ liệu thật**. Khối chỉ tiêu dạng văn bản (BS trực, ĐD trực, diễn biến) hiện nguyên thẻ định dạng ra chữ trong tệp xuất, vì nội dung soạn bằng trình soạn thảo có kèm thẻ HTML mà bản xuất chưa lược. Và ghi chú bị in hai lần sau khi nó có slide riêng. Cả hai đã xử lý; tệp xuất nay chỉ còn chữ sạch.
- **Sửa lỗi vỡ bố cục ở tệp PowerPoint xuất ra**: trên slide của khoa, dòng "BS trực" và "ĐD trực" bị in đè lên hai hàng cuối của bảng tiêu chí. Nguyên nhân: bản xuất tính mỗi hàng bảng cao 0,3 inch rồi đặt khối kế tiếp ngay dưới con số đó, nhưng 0,3 inch chỉ đúng khi mọi ô vừa **một dòng** — Khoa Khám bệnh có tiêu chí "Trong đó lượt khám tại PK Sơn Lương" phải xuống 2 dòng nên bảng cao 2,42 inch thay vì 1,80 và đè lên khối bên dưới 0,44 inch. Các khoa còn lại chỉ hở 0,02 inch, tức chỉ cần thêm một chữ vào tên tiêu chí là cũng đè. Nay bản xuất ước lượng chiều cao thật của từng hàng theo bề rộng cột và cỡ chữ rồi mới xếp khối kế tiếp.
- Cùng đợt: bảng tiêu chí và bảng Hoạt động điều trị dài quá một slide nay **tự tách sang slide nối tiếp** "(2/3)" thay vì tràn xuống dưới đáy slide. Hàng tiêu đề lặp lại ở mỗi trang, dòng TỔNG CỘNG ở trang cuối, khối "BS trực / ĐD trực" cũng chỉ in ở trang cuối. Tiêu đề slide tự thu nhỏ cỡ chữ để luôn nằm một dòng — tên khoa dài kèm số trang trước đây xuống hai dòng và đè lên đường kẻ.
- **Tài liệu hướng dẫn sử dụng cho người dùng nghiệp vụ** (tệp Word 53 trang, `docs/huong-dan-su-dung/`): gồm XML3176, Kiểm tra sai sót y lệnh, tra cứu thẻ BHYT và quản lý danh mục, kèm hai phụ lục. Bổ sung các mục tự động quét thư mục nhập hồ sơ XML, quét định kỳ hồ sơ nội trú, và hai quy trình bổ sung quy tắc kiểm tra dành cho bộ phận CNTT.

# 10/08/2026

- **Báo cáo giao ban - trình chiếu: thêm nút xuất tệp PowerPoint**. Nút "⬇ PPTX" trên thanh điều khiển dưới sinh ra tệp `.pptx` của đúng buổi giao ban đang xem, để chiếu lại ở cuộc họp khác hoặc chỉnh sửa thêm. Mỗi slide là **đối tượng PowerPoint thật** chứ không phải ảnh chụp: bảng là bảng, chữ là hộp văn bản, công suất giường là biểu đồ tròn và biểu đồ cột gốc của PowerPoint — người nhận sửa được từng ô, từng dòng, từng con số. Màu nền của tệp đi theo nền sáng/tối đang chiếu. Tên tệp đặt theo ngày báo cáo, ví dụ `giao-ban-2026-08-11.pptx`.
- Việc tạo tệp chạy ngay trên máy đang trình chiếu, không gửi gì lên máy chủ nên không phụ thuộc giới hạn bộ nhớ và thời gian chạy của máy chủ. Thư viện tạo tệp chỉ được tải ở lần bấm đầu tiên, người chỉ chiếu mà không xuất thì không phải tải. Trong lúc tạo, nút chuyển sang "Đang xuất…" và bị khoá để không bấm chồng; nếu lỗi thì chỉ nút báo "Xuất lỗi" rồi tự trở lại sau 3 giây, **màn đang chiếu không bị ảnh hưởng**.

# 07/08/2026

- **Đổi nhãn "Chỉ tiêu" thành "Tiêu chí" trên toàn bộ module giao ban**: màn trình chiếu, màn Cấu hình giao ban (cột bảng, nút mở danh sách, cửa sổ khai báo) và các thông báo lỗi khi lưu. Không đổi dữ liệu hay cấu hình đã khai, chỉ đổi chữ hiển thị.
- Báo cáo giao ban - trình chiếu: ô số liệu ở bảng "Hoạt động điều trị" nay **căn phải** như bảng tiêu chí, ô chưa có số liệu hiện dấu gạch và căn trái. Trước đây bảng này căn giữa toàn bộ nên các cột số khó dóng hàng để so sánh.

# 06/08/2026

- **Báo cáo giao ban - trình chiếu: chỉ tiêu hiển thị dạng bảng có viền thay cho thẻ số to**, áp dụng cho cả màn Tổng quan lẫn màn của từng khoa. Bảng 4 cột, mỗi dòng hai cặp "Tiêu chí | Số liệu" để tận dụng bề ngang máy chiếu; tiêu đề cột căn giữa, tên tiêu chí căn trái, số và phần trăm căn phải, mọi ô có viền. Cỡ chữ tự thu nhỏ theo số dòng và vẫn phóng được bằng nút A−/A+.
- **Thêm nút chuyển nền sáng / nền tối** cho màn trình chiếu, đặt cạnh nhóm nút phóng chữ. Lựa chọn được ghi nhớ trên chính máy đang chiếu nên lần sau không phải chỉnh lại; mặc định vẫn là nền tối như trước. Bảng màu nền sáng được thiết kế riêng cho phòng họp sáng chứ không phải đảo màu máy móc, và đã rà lại độ tương phản của toàn bộ chữ trên mọi slide.
- **API tra cứu lỗi theo đợt điều trị**: một lời gọi trả về ba nhóm lỗi của cùng một đợt — lỗi y lệnh, lỗi tra cứu thẻ BHYT và lỗi XML3176 — theo một khuôn dữ liệu thống nhất, kèm phần tóm tắt số lượng từng nhóm. Giới hạn 500 dòng mỗi nhóm. Mã thẻ BHYT được che bớt trong kết quả trả về.
- **Thay đổi phá vỡ tương thích ở API trên**: chỉ còn tra cứu theo `treatment_code`; yêu cầu chỉ gửi `treatment_id` nay bị từ chối với mã 422. Ba nguồn dữ liệu đều khoá theo `treatment_code` nên đường suy ngược từ `treatment_id` không còn đáng tin. Bên gọi đang dùng `treatment_id` cần chuyển sang `treatment_code`.
- **Siết bảo mật API**: token không còn lưu nguyên văn trong cấu hình mà lưu **bản băm SHA-256**, và việc so khớp dùng hàm chống dò theo thời gian. Thêm lệnh `php artisan api:generate` để sinh token mới và ghi bản băm vào cấu hình — token thật chỉ hiện một lần khi chạy lệnh, sau đó hệ thống không đọc lại được. Hạ mức ghi log của các lần gọi hỏng để không đổ đầy nhật ký. Thêm chỉ mục `treatment_code` cho bảng vi phạm y lệnh.
- **Sửa lỗi vi phạm tương tác thuốc không tìm được bằng ô tìm kiếm**: bản ghi loại này trước đây không lưu mã đợt điều trị, mã và tên bệnh nhân, nên bộ lọc từ khoá của màn hình **không bao giờ** tìm ra dòng tương tác thuốc nào. Nay ghi đủ, kèm tên bác sĩ chỉ định.

# 04/08/2026

- **Báo cáo giao ban - trình chiếu: thêm nút phóng chữ**. Nút A− / A+ và ô phần trăm trên thanh dưới (bấm vào ô để về 100%), phím tắt `+`, `−`, `0`; bước 10%, giới hạn từ 70% đến 200%. Mức phóng được nhớ trên máy đang chiếu, không thuộc về báo cáo. Chỉ chữ nội dung phóng to, thanh điều khiển giữ nguyên — để nó phóng theo thì ở mức 200% thanh này chiếm mất khoảng một phần tư chiều cao màn chiếu.
- Báo cáo giao ban - trình chiếu: **thứ tự cột của màn "Hoạt động điều trị" nay khai báo được** trong màn Cấu hình giao ban. Trước đây thứ tự là "cột nào xuất hiện trước thì đứng trước", phụ thuộc khoa nào tình cờ khai nhãn đó sớm nhất nên nhìn như ngẫu nhiên. Nhiều khoa khai cùng nhãn thì lấy số nhỏ nhất; cột không khai xếp sau và giữ thứ tự cũ.
- Báo cáo giao ban - trình chiếu: bỏ slide "Lượt khám theo phòng khám", và tăng cỡ chữ 25% cho toàn bộ trình chiếu. Riêng bảng "Hoạt động điều trị" chỉ tăng 15% vì bảng này chật, tăng mạnh là phải cuộn — mà chiếu lên tường thì phần phải cuộn coi như mất.
- Khắc phục việc `php artisan route:list` không chạy được: hai controller lớn truy vấn toàn bộ danh mục dùng chung ngay khi khởi tạo, nên mọi thao tác đều gánh các truy vấn đó dù không dùng tới. Chuyển sang nạp khi cần; mỗi lần gọi vẫn chỉ truy vấn tối đa một lần. Sau khi sửa, `route:list` in đủ 488 route mà không phải nới giới hạn bộ nhớ.

# 01/08/2026

- **Thay cơ chế cấp quyền quản trị viên đầu tiên**. Trước đây hệ thống gán quyền cao nhất cho **người đăng nhập đầu tiên** — mà tài khoản đăng nhập lấy từ HIS nên đó có thể là bất kỳ nhân viên nào, không phải người cài đặt. Nay có màn khởi tạo riêng tại `/setup/quan-tri-dau-tien`, chỉ mở khi hệ thống chưa có quản trị viên nào và đóng lại sau lần đầu. Đoạn kiểm tra cũ chạy trên mọi trang đã được gỡ bỏ.
- **Lưu ý khi cài đặt**: chạy seeder trước rồi mới tạo quản trị viên đầu tiên; nếu chạy lại `php artisan db:seed` sau đó thì màn khởi tạo có thể mở lại vì trạng thái được suy ra từ cơ sở dữ liệu chứ không chốt cứng.
- Gỡ cơ chế làm mới cơ sở dữ liệu khỏi bộ kiểm thử tự động. Dự án chưa có môi trường kiểm thử riêng nên bộ test chạy thẳng vào cơ sở dữ liệu `qlbv` đang dùng để phát triển, và cơ chế đó **xoá sạch toàn bộ bảng** — chỉ cần chạy test một lần là mất dữ liệu. Nguyên nhân gốc (thiếu môi trường test riêng) vẫn còn, chưa xử lý.

# 31/07/2026

- **Báo cáo giao ban: người được phân công khoa nay tự lấy được số liệu từ HIS**, không phải chờ KHTH bấm hộ ngoài giờ hành chính. Kèm ràng buộc: người khoa chỉ lấy được khi báo cáo còn trống, tránh việc lấy lại đè lên số liệu các khoa khác đã nhập. Quản trị viên vẫn lấy lại tuỳ ý.
- Báo cáo giao ban: khung giờ lấy số liệu của báo cáo đã lưu nay được điền sẵn khi mở lại, thay vì mỗi lần đổi ngày lại quay về mặc định. Trước đây người khoa bấm "Lấy số liệu" có thể vô tình đưa khung giờ về mặc định, làm tính lại số tự động toàn viện trong khi số các khoa đã nhập tay vẫn giữ theo khung cũ.
- Báo cáo giao ban: nút "Lấy số liệu" tự ẩn khi báo cáo đã có dữ liệu, và thông báo khi bị từ chối quyền nay nói đúng nguyên nhân thay vì báo nhầm thành "Lỗi lấy số liệu từ HIS".
- Báo cáo giao ban: thêm dòng "Chế độ: …" trên màn hình để người dùng tự biết tài khoản đang xem toàn viện hay chỉ vài khoa được phân công, không phải hỏi KHTH. Kèm tài liệu 5 bước khoanh vùng nguyên nhân cho phản ánh "đã phân quyền khoa nhưng đăng nhập vẫn thấy tất cả khoa" (`docs/giaoban-chan-doan-phan-quyen-khoa.md`).
- Báo cáo giao ban: **màn "Hoạt động điều trị" nay chỉ hiện những cột được đánh dấu** trong Cấu hình giao ban, thay vì tự lấy hết mọi chỉ tiêu dạng số. Cờ chọn cột chỉ có tác dụng ở khối Điều trị nội trú; bật ở khối khác sẽ bị báo lỗi ngay lúc khai báo thay vì im lặng không có tác dụng.
- Báo cáo giao ban - trình chiếu: bỏ dòng "lệch cân đối" khỏi màn Tổng quan. Thông tin này vẫn hiện ở slide của từng khoa và ở màn nhập liệu — hai chỗ có đủ ngữ cảnh để xử lý, còn ở màn tổng hợp thì chỉ làm nhiễu.
- **Thêm màn "Kết quả tra cứu thẻ BHYT"** (danh sách). Trước đây kết quả tra thẻ của từng hồ sơ chỉ xem được rải rác trong tab chi tiết của từng hồ sơ XML, không có chỗ nào nhìn tổng thể. Màn mới có bộ lọc từ ngày / đến ngày, trạng thái (Tất cả / Chỉ lỗi / Chỉ hợp lệ), cơ sở KCB và ô tìm theo mã hồ sơ, số thẻ hoặc họ tên; bấm vào dòng để xem chi tiết.
- Màn trên có nút **xuất Excel theo đúng bộ lọc đang chọn** — tệp xuất ra bằng đúng thứ đang hiện trên màn hình, và xuất đầy đủ 26 cột (nhiều hơn số cột hiển thị) để soi và đối chiếu. Không giới hạn số dòng: giới hạn ngầm là cắt bớt im lặng mà người dùng tưởng đã xuất đủ.
- **Sửa lỗi trắng trang khi gặp mã lỗi thẻ chưa có trong bảng nhãn**. Phạm vi rộng hơn phản ánh ban đầu: 22 chỗ trong 11 tệp. Nguy hiểm nhất là bốn mẫu email — hồ sơ có mã lạ làm công việc gửi mail **chết lặng trong hàng đợi**, không ai thấy trang trắng để mà biết. Nay mã không có nhãn thì hiện chính mã đó, không vỡ trang.
- **Bốn màn danh mục theo cơ sở KCB (thuốc, vật tư, dịch vụ kỹ thuật, khoa phòng giường) có thêm ô lọc theo cơ sở**. Chọn một cơ sở sẽ hiện danh mục **có hiệu lực** cho cơ sở đó, tức dòng riêng của cơ sở cộng với dòng dùng chung mọi cơ sở — đúng quy tắc hệ thống áp dụng khi kiểm hồ sơ, nên danh sách nhìn thấy luôn khớp với thứ thực tế được dùng.
- Số phiên bản và dòng bản quyền ở chân trang nay đọc từ cấu hình (`config/adminlte.php`, khoá `version` và `footer`). Trước đây số phiên bản viết cứng trong giao diện; dòng bản quyền thì nhìn như cấu hình được nhưng thực tế không. Sửa xong chạy `php artisan config:clear`.
- **Sự cố cần ghi nhớ**: đợt này có thử bật nén gzip và cache tệp tĩnh trong `public/.htaccess`, và **đã làm sập máy chủ thật** (Apache trả lỗi 500 toàn site). Nguyên nhân là khối cấu hình bọc sai điều kiện module. Bản vá đã lồng đúng cả hai điều kiện và thử qua sáu tổ hợp module đều chạy được, nhưng theo yêu cầu, phần nén đã được **gỡ bỏ hoàn toàn**, trả `.htaccess` về đúng trạng thái trước đó. Nếu sau này làm lại: phải lồng cả hai module, và phải thử tổ hợp trung gian chứ không chỉ "tắt hết" với "bật hết" — tổ hợp trung gian mới là cái gây sập. Số đo vẫn còn giá trị: trang HTML 37,3 KB, riêng menu chiếm 28,9 KB (77%), nén gzip còn 5,2 KB.

# 30/07/2026 (cập nhật 3)

- **Hồ sơ chưa ký số nay không được gửi lên cổng BHXH**. Gửi hồ sơ chưa ký thì cổng cũng từ chối, nên chặn tại chỗ vừa đỡ một vòng gọi mạng vừa cho thông báo rõ hơn thông báo của cổng. Áp dụng cho cả luồng XML3176 và QĐ130.
- **Lưu ý quan trọng khi triển khai mục trên**: chức năng gửi đang BẬT trong khi cả hai phương thức ký số đều đang TẮT. Với cấu hình đó, mọi hồ sơ xuất ra sẽ không gửi được và mang một dòng báo lỗi gửi. Muốn hồ sơ đi được thì phải bật ký số — cắm USB token và bật `usb_token_sign.enabled`, hoặc bật `xml_sign.enabled` với thông tin HSM đúng. Đây là thay đổi hành vi thật so với trước.
- **Danh mục Khoa Phòng Giường: bổ sung cột ngày hiệu lực (TU_NGAY) và tách theo từng cơ sở KCB**. Tệp BHXH cấp cho danh mục này có cột TU_NGAY nhưng bảng chưa có chỗ lưu nên cột bị bỏ qua im lặng — nhập xong không báo lỗi gì, chỉ là mất dữ liệu. Đo trên tệp thật: 91 dòng, 14 dòng có TU_NGAY. Khoá của bảng đổi thành cặp (mã khoa, mã cơ sở) để hai cơ sở dùng trùng mã khoa không đè lên nhau. Biểu mẫu tải về tự có cột mới, không phải sửa tay. **Khi triển khai**: chạy `php artisan migrate` và `php artisan config:clear`.
- **Sửa lỗi 8 liên kết trỏ vào màn tra cứu thẻ đều báo đỏ** "Phải chọn cơ sở KCB trước khi tra cứu" trong khi người dùng không làm gì sai — các liên kết này mang theo số thẻ / họ tên / ngày sinh nhưng không mang mã cơ sở. Nay thiếu mã cơ sở thì màn hình dừng lại ở bước đã điền sẵn và nhắc nhẹ, không gọi lên cổng; mã cơ sở sai vẫn bị chặn như cũ. Hai màn danh sách XML nay truyền sẵn cả mã cơ sở nên bấm một phát ra kết quả.

# 30/07/2026 (cập nhật 2)

- **Mỗi cơ sở KCB nay dùng tài khoản cổng BHXH riêng**: `config/organization.php` bổ sung khoá
  `BHYT_CO_SO`, khai theo mã cơ sở KCB, mỗi mã một khối `username`/`password`/`ho_ten_cb`/`cccd_cb`.
  Giá trị điền thẳng vào tệp (không qua `env()`); tệp này nằm trong `.gitignore` nên không lên kho
  mã. Cơ sở nào **không có trong khối `BHYT_CO_SO`** thì hồ sơ của cơ sở đó **bị bỏ qua và ghi
  log** ngay từ lệnh quét. Cơ sở **có khai nhưng để trống** `username`/`password` thì job **báo
  lỗi** khi chạy, chứ không âm thầm tra bằng tài khoản của cơ sở khác — tra nhầm tài khoản chính
  là thứ làm kết quả không hợp lệ. Cả hai đường đều lộ lỗi ra, không có đường nào hỏng im lặng.
- **Sửa lỗi job kiểm thẻ BHYT gửi sai mã cơ sở**: trước đây tham số `maCSKCB` gửi lên cổng lấy theo
  nơi đăng ký khám chữa bệnh ban đầu (ĐKBĐ) của bệnh nhân, không phải nơi bệnh nhân đang điều trị.
  Đo trên 45.995 hồ sơ: trước khi sửa `maCSKCB` có 4.194 giá trị khác nhau, sau khi sửa chỉ còn 2
  giá trị (đúng hai cơ sở điều trị thật) — nghĩa là khoảng 99,5% lời gọi trước đây khai sai cơ sở.
  Nghiệm thu bằng `php artisan kiemtrathebhyt:day --thu` trên dữ liệu thật: cơ sở `01929` có 4.336
  hồ sơ, cơ sở `37470` có 990 hồ sơ, không hồ sơ nào bị bỏ qua vì thiếu cơ sở.
- Bổ sung cột `ma_cskcb` (varchar 20, có index) vào bảng `check_hein_cards` để lưu đúng cơ sở điều
  trị của từng hồ sơ. **Khi triển khai**: điền tài khoản từng cơ sở vào `config/organization.php`
  (khối `BHYT_CO_SO`), sau đó chạy `php artisan migrate` và `php artisan config:clear`.

# 30/07/2026

- **Module Kiểm tra sai sót y lệnh — miễn luật chứng chỉ hành nghề theo tài khoản**: ba tài khoản `mitalab` (tích hợp máy xét nghiệm), `vietrad` (chẩn đoán hình ảnh) và `sys` (hệ thống) không còn bị luật `B_DOCTOR_NO_PRACTICE_CERT` báo vi phạm. Đây không phải người nên không thể có chứng chỉ hành nghề — luật báo vi phạm cho ba tài khoản này là báo oan. Đo ngày 30/07/2026: ba tài khoản này chiếm khoảng 99,2% số vi phạm của luật, phần còn lại đều là người thật thực sự thiếu CCHN trong HIS nên vẫn bị bắt như cũ. Khoảng 5.800 vi phạm sẽ ngừng sinh thêm từ lần quét sau; các vi phạm cũ đã ghi không bị xoá. Danh sách miễn sửa được trong cấu hình (`ORDER_CHECK_PRACTICE_CERT_EXCLUDE_LOGINS`), để rỗng là bắt lại toàn bộ như cũ.

# 28/07/2026

- **Báo cáo giao ban - trình chiếu: bổ sung màn "Hoạt động điều trị"**, đặt ngay sau màn Tổng quan. Bảng tổng hợp các khoa thuộc khối Điều trị nội trú: mỗi khoa một dòng, mỗi chỉ tiêu một cột, kèm dòng TỔNG CỘNG — xem được toàn cảnh cả khối trong một màn thay vì phải nhớ số của từng slide khoa để so sánh. Cột **tự sinh theo chỉ tiêu các khoa đã khai**, không cố định: khoa nào khai thêm chỉ tiêu mới thì bảng tự có thêm cột, khoa nào không khai chỉ tiêu đó thì ô hiện 0. Không cần khai báo hay thiết lập gì thêm.
- Bảng trên chỉ lấy **chỉ tiêu dạng số**; chỉ tiêu nhập tay dạng văn bản (ví dụ "Danh sách mổ phiên") không đưa vào bảng vì không cộng được, vẫn hiển thị ở màn riêng của từng khoa như cũ. Với chỉ tiêu kiểu phần trăm thì ô TỔNG CỘNG để trống thay vì cộng dồn. Cỡ chữ tự thu nhỏ theo số cột để bảng vừa một màn chiếu.
- Hai khoa khai cùng **tên chỉ tiêu** sẽ gộp chung một cột, kể cả khi đặt mã khác nhau. Ngược lại, nếu hai khoa đặt tên khác nhau cho cùng một việc thì sẽ ra hai cột riêng — muốn gộp thì sửa cho tên trùng nhau trong màn Cấu hình giao ban.
- Báo cáo giao ban - trình chiếu: đưa khối "Kíp trực lãnh đạo" lên đầu màn Tổng quan.
- Tối ưu màn hình Danh sách hồ sơ XML (Hồ sơ XML → Xml 3176 → Danh sách hồ sơ): khắc phục lỗi hết bộ nhớ khi chọn cỡ trang lớn trên khoảng thời gian dài. Dữ liệu trả về chỉ còn đúng các cột hiển thị trên bảng (trước đây kèm theo toàn bộ danh sách lỗi, thông tin thẻ và thông tin gửi/ký của từng hồ sơ dù không cột nào dùng tới); số lỗi của mỗi hồ sơ chuyển sang đếm thay vì tải về toàn bộ bản ghi lỗi. Mỗi lần bấm "Tải dữ liệu" nay chỉ gửi một yêu cầu thay vì hai.
- Sửa lỗi mất lựa chọn khi chuyển trang ở Danh sách hồ sơ XML: trước đây chọn hồ sơ ở trang 1 rồi sang trang 2 tích thêm là mất sạch lựa chọn trang 1, và nút "Xuất XML3176" chỉ nhận được các hồ sơ đang hiển thị. Đây cũng là lý do người dùng phải đặt cỡ trang 2000 để chọn hàng loạt. Nay lựa chọn được giữ xuyên suốt các trang.
- Sửa lỗi nút "Tải xuống 7980a" bỏ qua bộ lọc "Trạng thái xuất XML": lọc "Đã xuất XML" trên màn hình rồi tải 79/80a vẫn nhận về cả hồ sơ chưa xuất.
- Tối ưu màn hình Chi tiết hồ sơ XML (bấm đúp vào một dòng hồ sơ): trước đây mở hồ sơ điều trị dài ngày rất chậm hoặc treo. Ba thay đổi — bỏ việc truy vấn cơ sở dữ liệu riêng cho từng dòng khi tô đỏ dòng có lỗi; chỉ tải nội dung của tab người dùng đang xem thay vì dựng sẵn cả 15 tab; các bảng nhiều dòng (thuốc, VTYT-DVKT, cận lâm sàng, diễn biến) chia trang 100 dòng và chỉ tải khi bấm vào từng ngày/nhóm. Cách chia tab theo ngày/nhóm giữ nguyên như cũ. Riêng tab dịch vụ kỹ thuật (XML3) nay xếp các nhóm theo thứ tự mã nhóm tăng dần (trước đây không có thứ tự cố định).
- Tối ưu tab "Lỗi XML" trong màn Chi tiết hồ sơ: bỏ truy vấn danh mục lỗi lặp cho từng dòng, và bật lại phân trang cho bảng lỗi (25 dòng/trang) — trước đây toàn bộ dòng lỗi được dựng cùng lúc nên hồ sơ nhiều lỗi làm đơ trình duyệt. Sửa kèm lỗi trắng trang khi gặp mã lỗi chưa có trong danh mục.
- Báo cáo giao ban - trình chiếu: bỏ biểu đồ "BN vào / ra theo khoa" ở màn Công suất giường. Biểu đồ này lọc theo mã chỉ tiêu cố định của bộ mẫu cũ nên từ khi chuyển sang cấu hình chỉ tiêu tự đặt tên thì không khớp mã nào và tự ẩn mà không báo lỗi. Thông tin vào/ra vẫn có trên slide của từng khoa.
- Ghi bổ sung địa chỉ trang, phương thức và tham số vào nhật ký lỗi hệ thống: lỗi nghiêm trọng của PHP (hết bộ nhớ, quá thời gian chạy) trước đây không cho biết thao tác nào gây ra, khiến việc truy nguyên phải phỏng đoán.
- **Sửa lỗi mất hồ sơ khi nhập XML 3176**: một tệp XML chứa nhiều hồ sơ nhưng hệ thống chỉ nhập hồ sơ **đầu tiên**, các hồ sơ còn lại bị bỏ im lặng mà không báo gì. Đã kiểm chứng trên tệp thật của đơn vị: tệp khai 2 hồ sơ, trước đây chỉ vào 1. Nay nhập đủ mọi hồ sơ trong tệp; mỗi hồ sơ được bọc trong một giao dịch riêng nên một hồ sơ lỗi không kéo đổ các hồ sơ khác. Bổ sung đối chiếu số hồ sơ thực tế với số khai báo trong tệp: thiếu thì từ chối cả tệp, thừa thì vẫn nhập và ghi cảnh báo. **Lưu ý**: lỗi tương tự vẫn còn ở luồng nhập QĐ130 và XML4210, chưa xử lý trong đợt này.
- Gộp hai đường nhập XML 3176 (nhập qua giao diện và quét thư mục tự động) về dùng chung một bộ xử lý. Trước đây hai đường có mã riêng nên sửa một bên không tự áp cho bên kia. Tệp hỏng khi quét thư mục nay được chuyển sang thư mục lỗi thay vì thử đi thử lại vô hạn và làm tắc cả lượt quét.
- Nới giới hạn bộ nhớ và thời gian chạy riêng cho chức năng tải tệp XML lên, khắc phục lỗi hết bộ nhớ khi nhập tệp lớn trên máy chủ mới.
- Tăng tốc khâu kiểm lỗi XML 3176: chia thành từng việc theo cặp (hồ sơ, loại XML) thay vì một việc cho toàn bộ hồ sơ, và gom việc ghi lỗi theo lô 500 dòng thay vì ghi từng dòng. Mỗi việc tự dọn phần lỗi cũ của mình nên chạy lại nhiều lần vẫn cho cùng kết quả.
- Logo trên giao diện nay lấy theo thiết lập của đơn vị (`config/organization.php`, mục `organization_logo`); không thiết lập thì dùng `public/images/logo.png`. Áp dụng cho cả màn hình đăng nhập.
- **Module Kiểm tra sai sót y lệnh — nhóm luật đối chiếu danh mục BHYT (7 luật, mặc định TẮT)**: kiểm dòng dịch vụ thuộc đối tượng BHYT xem đã khai mã BHYT chưa, mã và **tên** DVKT/thuốc/vật tư có khớp danh mục BHYT hay không. Bảo hiểm từ chối cả khi tên lệch chứ không riêng mã sai; bộ kiểm XML 3176 đã bắt lỗi này nhưng chỉ sau khi hồ sơ đã khoá và xuất XML, còn nhóm luật này bắt ngay trên y lệnh đang phát sinh. Việc đối chiếu chỉ tính trên các dòng danh mục **còn hiệu lực tại ngày chỉ định** của y lệnh, vì danh mục BHYT thay đổi theo từng đợt trúng thầu. Bảng danh mục chưa nhập thì luật tương ứng tự im lặng, không báo oan.
- Trước khi bật nhóm luật trên, chạy `php artisan kiemtraylenh:thu --ngay=7` để **đếm thử mà không ghi gì**: lệnh in số vi phạm dự kiến của từng luật, phân bố theo khoa, đồng thời cảnh báo nếu cột ngày hiệu lực trong danh mục không đọc được (khi đó việc lọc theo hiệu lực sẽ không có tác dụng). Có con số rồi mới bật từng luật trên màn Quản lý quy tắc.
- **Kiểm lỗi XML 3176 nay bắt cả lỗi tên dịch vụ kỹ thuật** (`XML3_INVALID_TEN_DICH_VU` — "Tên dịch vụ kỹ thuật khác tên được phê duyệt"). Trước đây thuốc và vật tư đã có kiểm tên, riêng DVKT thì chưa. Một mã DVKT có thể có nhiều dòng trong danh mục (nhiều đợt phê duyệt, nhiều quy trình khác nhau) nên tên khai chỉ cần trùng **một** dòng còn hiệu lực là hợp lệ; mô tả lỗi liệt kê tối đa 3 tên đã được phê duyệt để đối chiếu. Cách so khớp giống hệt luật tên bên module Kiểm tra sai sót y lệnh, nên hai nơi không đưa ra hai kết luận khác nhau cho cùng một hồ sơ.
- **Lưu ý khi dùng kiểm tra tên DVKT**: mã lỗi này đang được xếp mức **lỗi nghiêm trọng**, trong khi lỗi tên vật tư chỉ ở mức cảnh báo. Hồ sơ lệch tên DVKT vì thế sẽ không nằm trong bộ lọc "Không có lỗi nghiêm trọng" — bộ lọc thường dùng để chọn hồ sơ xuất XML. Nếu muốn xếp về mức cảnh báo cho đồng bộ, hoặc muốn tắt hẳn kiểm tra này, sửa trực tiếp trên màn Danh mục lỗi XML mà không cần cài đặt lại phần mềm. Chưa ước lượng được số hồ sơ sẽ bị bắt, nên chạy kiểm lại trên một ít hồ sơ trước khi áp cho toàn bộ.
- **Module Kiểm tra sai sót y lệnh — thêm 3 luật đối chiếu mã bệnh và chứng chỉ hành nghề (mặc định TẮT)**: mã bệnh ICD10 và mã bệnh YHCT của phiếu chỉ định phải có trong danh mục tương ứng; chứng chỉ hành nghề của bác sĩ chỉ định và người thực hiện phải có trong danh mục nhân viên y tế còn hiệu lực. Luật mã bệnh kiểm **cả chẩn đoán chính lẫn chẩn đoán phụ** — đo trên dữ liệu thật thì chẩn đoán phụ còn sai nhiều hơn chẩn đoán chính (12,7% so với 9,7% số phiếu). Một mã sai khai ở cả hai chỗ chỉ báo một lần, và mô tả lỗi ghi rõ mã đó nằm ở chẩn đoán chính hay phụ.
- **Quy mô dự kiến của luật mã bệnh ICD10**: khoảng 16% số phiếu chỉ định có mã bệnh ngoài danh mục, nhưng **chỉ do 287 mã gây ra** — sửa khai báo 287 mã này trong HIS là dứt điểm, không phải xử lý từng phiếu. Nguyên nhân chủ yếu là HIS khai mã chi tiết hơn danh mục BHYT (ví dụ `M47.86` trong khi danh mục chỉ có `M47.8`). Vì số lượng lớn, nên chạy `php artisan kiemtraylenh:thu --ngay=7` xem con số rồi bật dần theo khoa thay vì bật toàn viện ngay.
- **Luật mã bệnh YHCT sẽ không báo lỗi nào** trên dữ liệu hiện tại — toàn bộ mã YHCT đang dùng đều hợp lệ. Đây là kết quả đúng chứ không phải luật hỏng; luật vẫn được thêm để chặn trường hợp nhập mã YHCT mới hoặc danh mục thay đổi về sau.
- **Luật chứng chỉ hành nghề chỉ có tác dụng sau khi nhập danh mục nhân viên y tế**; danh mục chưa nhập thì luật tự im lặng. Cũng vì danh mục này đang trống mà màn hình lỗi XML 3176 hiện có rất nhiều dòng báo "mã bác sĩ / người thực hiện không tồn tại" — đó là báo nhầm do thiếu danh mục, không phải hồ sơ sai. Nhập danh mục nhân viên y tế sẽ giải quyết cả hai chỗ.
- **Ngừng bắt lỗi chứng chỉ hành nghề của người thực hiện ở ba loại đơn thuốc**: Đơn phòng khám, Đơn tủ trực và Đơn điều trị. Người thực hiện của các phiếu này là dược sĩ hoặc điều dưỡng cấp phát, không phải người mà nghiệp vụ đòi chứng chỉ hành nghề. Đo trên dữ liệu thật của 60.000 phiếu: giảm từ 4.072 vi phạm xuống 0, trong đó Đơn phòng khám chiếm 3.391 và Đơn điều trị 681 — nghĩa là luật này trước đây gần như chỉ báo lỗi ở ba loại đơn thuốc. Danh sách loại trừ sửa được trong cấu hình (`ORDER_CHECK_PRACTICE_CERT_EXCLUDE_TYPES`), để rỗng là bắt lại toàn bộ như cũ.
- **Lưu ý**: phần lớn số vi phạm nói trên thực chất do **một nhân viên chưa được khai chứng chỉ hành nghề trong HIS**, không phải do bản thân các phiếu sai. Việc loại trừ làm con số biến mất khỏi màn hình nhưng ô dữ liệu trong HIS vẫn trống và vẫn ảnh hưởng tới các kiểm tra khác dùng chung trường này, nên vẫn cần khai bổ sung.

# 20/07/2026

- Bổ sung Dashboard chuyên biệt cho module kiểm tra lỗi XML 3176 (Hồ sơ XML → Xml 3176 → Dashboard lỗi XML): 5 thẻ KPI (tổng hồ sơ, lỗi nghiêm trọng, lỗi thẻ BHYT, chi phí BHYT bị treo, đã gửi BHXH) và 4 biểu đồ — phễu pipeline 5 bậc (import → không lỗi nghiêm trọng → xuất XML → ký số → gửi BHXH, kèm % rơi rụng từng bậc), Pareto top 15 mã lỗi hay gặp (kèm % tích luỹ, phân biệt lỗi nghiêm trọng/cảnh báo), tồn đọng theo tuổi hồ sơ chưa gửi (0–7/8–15/16–30/>30 ngày) và lỗi nghiêm trọng theo khoa. Lọc theo loại ngày (vào/ra/thanh toán/tạo) như màn hình danh sách. Bấm vào mỗi con số hoặc cột biểu đồ sẽ mở màn hình danh sách hồ sơ với bộ lọc áp sẵn, số trên dashboard khớp đúng số dòng trong danh sách. Riêng biểu đồ tồn đọng luôn tính theo ngày ra viện so với hôm nay, không phụ thuộc khoảng ngày đang chọn (đã ghi chú rõ trên biểu đồ).
- Sửa lỗi cache token khi gửi hồ sơ lên Cổng dữ liệu Y tế Điện Biên và Trục dữ liệu Y tế: thời gian lưu token bị tính sai đơn vị nên giữ lâu gấp 60 lần thực tế, dẫn tới dùng token đã hết hạn và hồ sơ hợp lệ bị đẩy vào thư mục lỗi vĩnh viễn. Bổ sung tự đăng nhập lại và gửi lại một lần khi cổng từ chối token (401), kiểm tra hạn theo nội dung token, và giới hạn tuổi thọ token Điện Biên tối đa 15 phút cho chắc. Sửa thêm lỗi thiếu biến khi ghi log đăng nhập Trục dữ liệu Y tế.

# 09/07/2026

- Báo cáo giao ban - trình chiếu mục tổng hợp: bổ sung công suất giường (donut toàn viện Tổng/Đang dùng/Trống + thanh công suất % theo khoa, màu cảnh báo ≥90% đỏ/≥80% cam/≥60% teal) — dữ liệu chụp snapshot cùng thời điểm "Lấy số liệu", lưu bảng giaoban_report_beds; mở rộng lưới KPI tổng quan lên 8 ô (thêm Vào viện/Ra viện/Chuyển viện/Tử vong/Cấp cứu/PT-Đẻ, ẩn ô không có số liệu); tách thành 2 slide (Tổng quan toàn viện + Công suất & biến động theo khoa). Slide công suất theo khoa ưu tiên khoa báo cáo khối điều trị, nếu chưa cấu hình thì hiển thị theo từng khoa HIS có giường (kèm tên khoa, như dashboard home). Bổ sung hiển thị Ghi chú chung (general_note, rich text) trên slide Tổng quan. Đối chiếu HIS: 831 giường, 506 đang dùng, công suất 60,9% (18 khoa).
- Báo cáo giao ban: bổ sung Kíp trực lãnh đạo — cấu hình danh mục chức danh trực; nhập người trực chọn từ danh mục nhân viên HIS (his_employee, tự điền SĐT), cho phép nhiều người/chức danh, nút sao chép kíp ngày trước; phân quyền cập nhật kíp trực theo user (danh sách người được cập nhật, ngoài admin); hiển thị trên trình chiếu.

# 08/07/2026 (cập nhật 6)

- Báo cáo giao ban - khối Khám ngoại trú: thống kê thêm theo loại ra viện (his_treatment.treatment_end_type_id) — Cấp toa cho về, Chuyển viện, Hẹn khám lại; gom lại migration giao ban còn 5 file cho gọn.

# 08/07/2026 (cập nhật 5)

- Ghi chú khoa và ghi chú chung của Báo cáo giao ban chuyển sang soạn thảo rich text (CKEditor) qua popup; lưu HTML đã làm sạch (HTMLPurifier chống XSS); trình chiếu hiển thị định dạng đẹp; xuất Excel tự bỏ thẻ HTML.

# 08/07/2026 (cập nhật 4)

- Nâng cấp cấu hình Báo cáo giao ban: 1 khoa báo cáo gộp nhiều khoa HIS (loại trừ chuyển nội bộ); phân loại khối Điều trị/Khám/Cận lâm sàng với cách thống kê riêng (census, lượt khám theo tdl_treatment_type_id/tdl_patient_type_id, đếm dịch vụ CLS theo khoa thực hiện); gán tài khoản bằng tài khoản HIS (acs_user) qua ô tìm kiếm; thêm chỉ tiêu cho khoa CĐHA/Xét nghiệm.

# 08/07/2026 (cập nhật 3)

- Bổ sung chế độ Trình chiếu (Present) cho Báo cáo giao ban: mở trang slide toàn màn hình (tổng quan toàn viện + mỗi khoa 1 slide), điều hướng bằng phím/click, nút nhảy nhanh tới khoa, nền tối chuyên nghiệp; đổi nút "Xem" thành "Làm mới".

# 08/07/2026 (cập nhật 2)

- Bổ sung Báo cáo giao ban bệnh viện (KHTH): tự động tính số liệu theo khoa từ HIS (BN cũ/vào/chuyển/ra/hiện có, PTTT, giường YC, XN/CĐHA...) theo khoảng giờ tùy chọn; cho sửa tay từng ô theo phân quyền khoa (giaoban_khoa/giaoban_admin); chốt báo cáo + xuất Excel theo biểu mẫu; màn cấu hình động khoa/chỉ tiêu và gán tài khoản↔khoa.

# 08/07/2026 (cập nhật 1)
- Bổ tài biểu mẫu import bộ danh mục dịch vụ

# 01/07/2026

- Module Kiểm tra sai sót y lệnh (giai đoạn 7): bổ sung trang Quản lý quy tắc (KHTH) — bật/tắt từng luật, sửa mức độ và tên hiển thị ngay trên giao diện, không cần vào database; gom nhóm menu "Kiểm tra sai sót y lệnh" đặt ngang hàng ngay dưới mục "Thống kê".
- Module Kiểm tra sai sót y lệnh: bộ lọc "Loại dịch vụ" trên dashboard nạp từ danh mục HIS (dropdown, giống bộ lọc Khoa) thay vì gõ tay; bỏ cột "Mã DV" khỏi bảng vi phạm vì chỉ có dữ liệu với luật giới tính/tuổi nên hầu như luôn trống.
- Module Kiểm tra sai sót y lệnh: tách luật cấp phiếu chỉ định theo từng loại dịch vụ (luật dùng chung + bộ luật riêng cho Đơn thuốc, Đơn phòng khám, Chẩn đoán hình ảnh, Đơn máu...) để mỗi loại có tiêu chí kiểm tra phù hợp và dễ bổ sung luật mới. Sửa lỗi API tra cứu vi phạm khiến `route:cache` không chạy được.

# 30/06/2026 (cập nhật 5)

- Module Kiểm tra sai sót y lệnh (giai đoạn 6): thêm danh mục tự quản "Giới hạn dịch vụ" (giới tính/tuổi) + màn nhập (KHTH) + 2 luật A_GENDER_MISMATCH, A_AGE_OUT_OF_RANGE đối chiếu chỉ định với giới tính/tuổi bệnh nhân. Luật chỉ phát hiện khi danh mục đã được nhập (HIS không có sẵn dữ liệu giới hạn).

# 30/06/2026 (cập nhật 4)

- Module Kiểm tra sai sót y lệnh (giai đoạn 5): bổ sung luật cấp đợt điều trị — A3 trùng dịch vụ, A2 trùng hoạt chất (HIS_EXP_MEST_MEDICINE + HIS_MEDICINE), A5 liều×ngày không khớp số lượng cấp. Quét incremental theo hoạt động mới rồi re-evaluate cả đợt; bật/tắt trong order_check_rules.

# 30/06/2026 (cập nhật 3)

- Module Kiểm tra sai sót y lệnh (giai đoạn 4): gửi email digest định kỳ các vi phạm mới tới danh sách người nhận (email_receive_report), theo ngưỡng mức độ cấu hình; chạy bằng service `kiemtraylenh:notify`. Mặc định TẮT (bật qua ORDER_CHECK_NOTIFY_ENABLED).

# 30/06/2026 (cập nhật 2)

- Module Kiểm tra sai sót y lệnh (giai đoạn 3): dashboard KHTH "Kiểm tra sai sót y lệnh" (lọc theo ngày/khoa/mức độ/loại luật/trạng thái + KPI + DataTables), quy trình xử lý (đã xử lý/bỏ qua + ghi chú + người xử lý), xuất Excel, và API JSON tra cứu vi phạm theo đợt điều trị.

# 30/06/2026 (cập nhật)

- Module Kiểm tra sai sót y lệnh (giai đoạn 2): tổng quát hóa engine đa-nguồn (multi-scanner); bổ sung luật A1 nạp cảnh báo tương tác thuốc do HIS phát hiện (HIS_MEDICINE_INTERACTIVE) và A4 phát hiện phiếu chỉ định thiếu chẩn đoán ICD. Các luật bật/tắt trong order_check_rules.

# 30/06/2026

- Bổ sung module Kiểm tra sai sót y lệnh (giai đoạn 1): quét incremental phiếu chỉ định từ HIS (HIS_SERVICE_REQ) theo watermark, chạy 4 quy tắc hợp lệ cấu trúc/thời gian & hành nghề (ngày ra<vào, giờ y lệnh ngoài đợt, giờ thực hiện trước y lệnh, BS thiếu chứng chỉ), lưu vi phạm vào order_check_violations. Chạy bằng `php artisan kiemtraylenh:scan` (lập lịch mỗi 1–5 phút qua Windows Task/nssm).

# 23/06/2026

- Bổ sung báo cáo KHTH "Doanh thu theo khoa/phòng thực hiện": lọc theo giai đoạn/khoa/phòng, biểu đồ + bảng doanh thu theo khoa, chi tiết theo phòng (DataTables) + xuất Excel; doanh thu tính theo vir_price (amount × vir_price)
- Bổ sung biểu đồ "Tình trạng giường theo khoa" trên Home dashboard: cột nhóm giường đã sử dụng / còn trống + công suất % theo từng khoa (his_bed, his_treatment_bed_room), trạng thái hiện tại (real-time, không lọc ngày)

# 22/06/2026

- Bổ sung biểu đồ "Doanh thu theo khoa thực hiện" trên Home dashboard: biểu đồ cột doanh thu theo khoa (his_department), mỗi khoa một màu, đơn vị triệu (Tr), loại bỏ khoa không có doanh thu, lọc theo khoảng ngày của dashboard

# 10/06/2026

- Bổ sung biểu đồ "Số lượng dịch vụ theo máy thực hiện" trên Home dashboard: thống kê số lượng dịch vụ theo máy (his_machine), có nút chuyển xem theo nhóm máy / từng máy, lọc theo khoảng ngày của dashboard

# 09/06/2026

- Bổ sung dashboard Tỷ lệ trả kết quả đúng hẹn (KHTH): tính % trả KQ đúng hẹn/trễ hẹn theo thời gian hẹn (ESTIMATE_DURATION) của dịch vụ cận lâm sàng; có tổng hợp theo loại DV/khoa-phòng/dịch vụ/ngày, xem chi tiết, drill-down và export Excel

# 26/05/2026

- Bổ sung kiểm tra mã bệnh thuộc nhóm cảnh báo không được thanh toán BHYT

# 20/05/2026

- Bổ sung thiết lập Logo theo từng đơn vị trên giao diện trả kết quả KCB

# 11/05/2026

- Bổ sung hiển thị Doanh thu / Số lương theo đối tượng

# 06/05/2026

- Update giao diện màn hình hiển thị thông tin chờ khám của bệnh nhân - Dashboard sử dụng cho màn hình lớn

# 24/04/2026

- Update giao diện hiển thị kết quả CĐHA từ PACS VNPT
- Bổ sung thông tin thời gian ra viện cho bệnh nhân nội trú

# 08/04/2026

- Bổ sung báo cáo khảo sát TG khám bệnh

# 31/03/2026

- Update bổ sung báo cáo doanh thu dịch vụ y tế
- Update bổ sung biểu đồ phân tích dữ liệu cho lãnh đạo CSKCB

# 26/03/2026

- Update tích hợp ký số XML3176 bằng USB Token, bổ sung thêm 1 service ký số trên Windows

# 25/03/2026

- Update bổ sung báo cáo doanh thu dịch vụ y tế

# 24/03/2026

- Update bổ sung chức năng xem kết quả CĐHA PACS VNPT

# 20/03/2026

- Update bổ sung tính năng auto update

# 30/01/2026

- Update tối ưu tốc độ xử lý export XML, submit XML lên BHXH, ký số HSM
- Update tự động đẩy hồ sơ XML3176 từ hệ thống export tiền giám định sang module gửi lên cổng dữ liệu Sở y tế Hà Nội

# 20/01/2026

- Cập nhật bổ sung XML3176 - Tương đương với các chức năng của XML4750 hiện có

# 17/01/2026

- Cập nhật, bổ sung chức năng gửi XML4750 lên cổng dữ liệu tỉnh Điện Biên
- Cập nhật bổ sung chức năng gửi XML3176 lên cổng dữ liệu Sở y tế HN

# 16/12/2025

- Bổ sung chức năng gửi hồ sơ XML từ phần mềm tiền giám định
- Quản lý trạng thái hồ sơ đã gửi dựa vào kết quả trả về từ Cổng BHXH

# 29/11/2025

- Sửa chức năng import danh mục do danh mục excel thay đổi cấu trúc cột
- Bổ sung quy tắc bắt lỗi trùng khít ngày y lệnh, ngày thực hiện, ngày kq của XML3 loại PTTT
- Bổ sung báo cáo thống kê danh sách NVYT có chỉ định y lệnh teo thời gian

# 27/11/2025

- Bổ sung báo cáo thống kê Thuốc/VTYT tiêu hao

# 20/10/2025

- Nâng cấp ký số XML sử dụng HSM của VietSens

# 12/11/2024

- Bổ sung import/export XML6 (HIV), XML15 (Lao)

# 05/11/2024

- Bổ sung kiểm tra chỉ cho phép tối đa 1 công khám đối với điều trị nội trú

# 31/10/2024

- Bổ sung kiểm tra Xml3 đối với một dịch vụ có nhiều giá hợp lệ

# 28/10/2024

- Bổ sung chức năng kiểm tra trùng lặp giường: Key kiểm tra ma_giuong + ma_khoa + ngay_th_yl

# 25/10/2024

- Bổ sung chức năng kiểm tra thẻ: Bỏ qua không kiểm tra những thẻ BHYT của CBCS (CA, QN, CY)
- Bổ sung kiểm tra thời gian y lệnh của VTYT trong gói có khớp với thời gian y lệnh của DVKT không

# 24/10/2024

- Bổ sung lọc Hồ sơ XML4750 theo người import

# 23/10/2024

- Export Excel lỗi: Bổ sung sheet lỗi thẻ
- Update logic Kiểm tra mã thẻ tạm

# 22/10/2024

- Bổ sung Import/Export XML10 (Giấy nghỉ việc dưỡng thai)
  - Bổ sung Import
  - Bổ sung Export
  - Bổ sung form hiển thị thông tin
  - Bổ sung các quy tắc giám định

# 21/10/2024

- Bổ sung kiểm tra bắt buộc phải có mã máy đối với những dịch vụ thuộc nhóm cần kiểm tra (XN/CĐHA...)

# 18/11/2024

- Bổ sung lấy thông tin người dùng nào đã import/export hồ sơ (Đối với trường hợp import/export hồ sơ tự động thì để trống)
- Bổ sung tải về 7980a (Tải về file excel)

# 11/10/2024

- Bổ sung kiểm tra VTYT kèm theo DVKT phải có y lệnh trùng ngày y lệnh DVKT.
- Bổ sung cho phép người dùng lựa chọn Lỗi Xml:
  - Cho phép kiểm tra/không kiểm tra lỗi.
  - Cho phép lựa chọn lỗi đó là lỗi critical hoặc warning.
- Bổ sung hướng xử lý trên form hiển thị lỗi Xml: Hướng dẫn cách xử lý một số lỗi cơ bản
- Bổ sung chức năng import danh mục trên giao diện người dùng

# 30/09/2024

- Bổ sung export data BN nợ viện phí dạng xlsx
- Bổ sung chức năng gán quyền superadmin cho user đầu tiên đăng nhập hệ thống:
  Phục vụ cho việc triển khai tool ở một đơn vị mới: Nếu lần đầu tiên đăng nhập, hệ thống sẽ kiểm tra xem đã có User nào được gán quyền superadmin chưa. Nếu chưa có thì sẽ gán cho user đăng nhập hiện tại, nếu có rồi thì bỏ qua.

# 28/09/2024

- Bổ sung báo cáo BN nợ viện phí (thêm thông tin và cải thiện tốc độ xử lý so với HIS)

# 23/09/2024

- Sửa export Xml130 ra thư mục với Ma CSKCB tương ứng trong XmlContent
- Bổ sung lọc Xml4750 theo mã bệnh nhân

# 18/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Bổ sung kiểm tra BBHC thuốc có dấu sao

# 17/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Bổ sung kiểm tra BBHC PTTT
  - Bổ sung kiểm tra BBHC DVKT

# 16/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Kiểm tra nợ viện phí
  - Hiển thị đơn thuốc phòng khám

# 15/09/2024

- Bổ sung Chức năng kiểm tra hồ sơ Emr
  - Kiểm tra chữ ký của BN trên bảng kê thanh toán

# 12/09/2024

- Bổ sung kiểm tra tính hợp lệ của giấy nghỉ việc hưởng BHXH
  - Bổ sung Qd130Xml11Checker kiểm tra tu_ngay không được lớn hơn qd130_xml1.ngay_ra
  - Bổ sung Qd130Xml11Checker kiểm tra den_ngay không được nhỏ hơn qd130_xml1.ngay_ra

# 10/09/2024

- Sửa Qd130Xml3Checker
  - Bổ sung $this->serviceDisplay ưu tiên lấy ten_vat_tu nếu không có mới lấy ten_dich_vu
  - Phù hợp với export Xml3 của HisPro Vietsens

# 09/09/2024

- Sửa mail gửi lỗi thẻ BHYT, bổ sung Mã thẻ HISPro của Vietsens trong trường hợp không tra cứu được thông tin
  - Bổ sung mối quan hệ Models\CheckBHYT\check_hein_card với bảng his_treatment của Hispro
  - Sửa template gửi email resources\templates\mail-qd130-errors.blade

# 06/09/2024

- Bổ sung chức năng tự động import danh mục cơ sở khám chữa bệnh
  - Bổ sung fillable trong Models\MedicalOrganization
  - Tải danh mục đơn vị hành chính từ trang: https://gdbhyt.baohiemxahoi.gov.vn/DM_COSOKCB
  - Sửa Artisan Command ImportCatalogBHXHFromFiles
    - Bổ sung kiểm tra cấu trúc file danh mục
    - Bổ sung thêm case $firstRow === $expectedMedicalOrganizationColumns:
- Sửa Service Check Xml lọc MedicalOrganization với is_active = true
  - Sửa Service Qd130Xml1Checker

# 04/09/2024

- Bổ sung chức năng tự động import danh mục đơn vị hành chính
  - Bổ sung fillable trong Models\AdministrativeUnit
  - Tải danh mục đơn vị hành chính từ trang: https://danhmuchanhchinh.gso.gov.vn/Default.aspx
  - Sửa Artisan Command ImportCatalogBHXHFromFiles
    - Bổ sung kiểm tra cấu trúc file danh mục
    - Bổ sung thêm case $firstRow === $expectedAdministrativeUnitsColumns:
- Sửa Service Check Xml lọc AdministrativeUnit với is_active = true
  - Sửa Service Qd130Xml1Checker

# 27/08/2024

- Tối ưu chức năng tự động quét thẻ BHYT
  - Đối với những thẻ bị sai thông tin được quy định trong config qd130xml.hein_card_invalid.check_code và qd130xml.hein_card_invalid.result_code thì thực hiện quét lại thẻ BHYT, kể cả không có sự thay đổi thông tin thì vẫn cập nhật updated_at tại thời điểm kiểm tra nhằm mục đích gửi thông báo tới các khoa phòng liên quan để sửa lỗi thông tin thẻ
  - Sửa job jobKtTheBHYT: phương thức handle() và phương thức addCheckHeinCard()
- Bổ sung kiểm tra tyle_tt_dv và tyle_tt_bh trong Xml2 và Xml3 chỉ được nằm trong khoảng từ 0 đến 100
  - Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml2Checker
  - Bổ sung thêm trong phương thức infoChecker() của Services Qd130Xml3Checker

# 24/08/2024

- Bổ sung tự động quét kiểm tra thẻ BHYT đối với BN đang điều trị (His Pro Vietsens)
  - Bổ sung artisan command HISProKiemTraTheBHYT
  - Chỉ quét một lần trong suốt quá trình điều trị đối với thẻ đúng
  - Thực hiện quét lại đối với thẻ sai
  - Cho phép cấu hình thời gian chạy quét bằng task schedule (Windows) hoặc supersivor (Linux/Unix)

# 23/08/2024

- Bổ sung kiểm tra quy tắc kiểm tra Khoa chỉ định không hợp lệ (Warning)
  - Khoa khám bệnh (K01) chỉ định dịch vụ/vtyt (xml3) và thuốc (xml2) cho BN nội trú - trái tuyến
  - Thêm key trong config.qd130xml
  - Bổ sung quy tắc trong Qd130Xml2Checker và Qd130Xml3Checker
- Bổ sung kiểm tra quy tắc TT_THAU đúng định dạng Gx;Nx trong Xml2 và Xml3 nếu có (Warning)
  - thêm key trong config.qd130xml
  - Bổ sung quy tắc infoChecker trong Qd130Xml2Checker và Qd130Xml3Checker

# 15/08/2024

- Cập nhật kiểm tra Xml9 Thông tin trẻ sơ sinh (Critical)

# 07/08/2024

- Cập nhật kiểm tra Xml5, thời điểm dbls phải nằm trong khoảng thời gian vào và ra (Critical)

# 31/07/2024

- Cập nhật phần kiểm tra Qd130XmlCompleteChecker: t_bhtt_gdv, bổ sung qd130xml config, không check đối với những mã thẻ là QN, CY, CA

1. Chi phí của các đối tượng có mã thẻ quân nhân (QN), cơ yếu (CY), công an (CA);
2. Chi phí vận chuyển người bệnh có thẻ BHYT;
3. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng dịch vụ kỹ thuật thận nhân tạo chu kỳ hoặc dịch vụ kỹ thuật lọc màng bụng hoặc dịch lọc màng bụng:
4. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc chống ung thư hoặc dịch vụ can thiệp điều trị bệnh ung thư đối với người bệnh được chẩn đoán bệnh ung thư gồm các mã từ C00 đến 297 và các mã từ 00 đến D09 thuộc bộ mã Phân loại bệnh quốc tế lần thứ X ( sau đây viết tắt là ICD - 10);
5. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc điều trị Hemophilia hoặc máu hoặc chế phẩm của máu đối với người bệnh được chẩn đoán bệnh Hemophilia gồm các mã D60, D67, D68 thuộc bộ mã ICD - 10;
6. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc chống thải ghép đối với người bệnh ghép tạng;
7. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc điều trị viêm gan C của người bệnh bị bệnh viên gan C;
8. Toàn bộ chi phí của lần khám bệnh, chữa bệnh BHYT có sử dụng thuốc kháng HIV hoặc dịch vụ xét nghiệm tải lượng HIV của người bệnh có thẻ BHYT được chẩn đoán bệnh HIV.

- Cập nhật kiểm tra trường KET_LUAN trong Xml4
  Bổ sung mã nhóm trong Xml3 bắt buộc phải có KET_LUAN trong Xml4: config.qd130xml.xml4.xml3_ma_nhom_require_ket_luan
  Bổ sung kiểm tra bắt buộc phải có trường KET_LUAN trong Qd130Xml4Checker
- Bổ sung kiểm tra Ngày trả kết quả trong Xml3 đối với DVKT < Ngày y lệnh (Critical)

# 28/07/2024

Cập nhật API tra cứu thẻ BHYT 2024: KQNhanLichSuKCB2024

- Bổ sung thêm config organization.BHYT.hoTenCb và organization.BHYT.cccdCb
- Sửa hàm tra cứu -> chức năng tra cứu thẻ: App\BHYT.php
- Sửa job thực hiện tra cứu khi import hồ sơ: App\Job\jobKtTheBHYT

# 25/05/2024

Cập nhật bổ sung kiểm tra quy tắc Xml4 (Cận lâm sàng)

- Sửa code kiểm tra các quy tắc: Services/Qd130Xml4Checker
- Kiểm tra cấu trúc trường dữ liệu

# 24/07/2024

Cập nhật kiểm tra cấu trúc và tính đúng đắn mã máy trong Xml3

- Mở rộng trường ma_may trong Xml3 thành dạng text
- Sửa code chức năng kiểm tra quy tắc trong Xml3: Services/Qd130Xml3Checker
