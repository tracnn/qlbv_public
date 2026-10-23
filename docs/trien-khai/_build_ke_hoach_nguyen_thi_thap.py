import sys
from datetime import date
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.worksheet.datavalidation import DataValidation
from openpyxl.formatting.rule import CellIsRule, FormulaRule
from openpyxl.comments import Comment

OUT = sys.argv[1]
F = 'Arial'
NAVY = '1F3864'
fill_head = PatternFill('solid', fgColor=NAVY)
fill_grp = PatternFill('solid', fgColor='D9E2F3')
fill_in = PatternFill('solid', fgColor='FFFF00')
fill_note = PatternFill('solid', fgColor='F2F2F2')
thin = Side(style='thin', color='A6A6A6')
box = Border(left=thin, right=thin, top=thin, bottom=thin)
wrap = Alignment(wrap_text=True, vertical='top')
center = Alignment(horizontal='center', vertical='top', wrap_text=True)
TT = ['Chưa làm', 'Đang làm', 'Xong', 'Vướng']

wb = Workbook()


def title(ws, text, sub=None, ncol=8):
    ws['A1'] = text
    ws['A1'].font = Font(name=F, size=15, bold=True, color=NAVY)
    ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=ncol)
    if sub:
        ws['A2'] = sub
        ws['A2'].font = Font(name=F, size=10, italic=True, color='595959')
        ws['A2'].alignment = Alignment(wrap_text=True, vertical='top')
        ws.merge_cells(start_row=2, start_column=1, end_row=2, end_column=ncol)
        ws.row_dimensions[2].height = 30


def header(ws, row, cols, widths):
    for i, (c, w) in enumerate(zip(cols, widths), 1):
        cell = ws.cell(row=row, column=i, value=c)
        cell.font = Font(name=F, bold=True, color='FFFFFF', size=10)
        cell.fill = fill_head
        cell.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
        cell.border = box
        ws.column_dimensions[cell.column_letter].width = w
    ws.row_dimensions[row].height = 32
    ws.freeze_panes = ws.cell(row=row + 1, column=1)


def put(ws, row, values, grp=False, centers=()):
    for i, v in enumerate(values, 1):
        cell = ws.cell(row=row, column=i, value=v)
        cell.font = Font(name=F, size=10, bold=grp)
        cell.alignment = center if i in centers else wrap
        cell.border = box
        if grp:
            cell.fill = fill_grp


def status_dv(ws, rng):
    dv = DataValidation(type='list', formula1='"' + ','.join(TT) + '"', allow_blank=True)
    ws.add_data_validation(dv)
    dv.add(rng)
    col = rng.split(':')[0].rstrip('0123456789')
    first = rng.split(':')[0]
    ws.conditional_formatting.add(rng, CellIsRule(operator='equal', formula=['"Xong"'], fill=PatternFill('solid', fgColor='C6EFCE')))
    ws.conditional_formatting.add(rng, CellIsRule(operator='equal', formula=['"Vướng"'], fill=PatternFill('solid', fgColor='FFC7CE')))
    ws.conditional_formatting.add(rng, CellIsRule(operator='equal', formula=['"Đang làm"'], fill=PatternFill('solid', fgColor='FFEB9C')))


def mark_input(cell):
    cell.fill = fill_in
    cell.font = Font(name=F, size=10, color='0000FF')


# =====================================================================================
# 1. Tổng quan
# =====================================================================================
ws = wb.active
ws.title = 'Tong quan'
title(ws, 'KẾ HOẠCH TRIỂN KHAI TIỀN GIÁM ĐỊNH HỒ SƠ XML 3176 — BỆNH VIỆN NGUYỄN THỊ THẬP',
      'Căn cứ: Tài liệu tổng hợp XML3176 & Order-Check (mục 5, 9, 10), Quy trình vận hành XML 3176, deck Giới thiệu Tiền giám định. '
      'Phạm vi: module XML 3176 (nạp – kiểm lỗi – tra thẻ – ký số; gửi cổng TẮT trong thời gian chạy thử).', ncol=6)
for col, w in zip('ABCDEF', [34, 22, 22, 16, 44, 4]):
    ws.column_dimensions[col].width = w

r = 4
ws.cell(row=r, column=1, value='CÁCH DÙNG BẢNG').font = Font(name=F, bold=True, color=NAVY)
legend = [
    'Ô nền vàng chữ xanh = ô cần nhập. Mọi ngày tháng trong các sheet tự tính từ ô "Ngày N" bên dưới.',
    'Ngày N = ngày Công ty nhận ĐỦ thông tin BV chuyển giao (đủ mọi dòng "Bắt buộc" ở sheet BV chuan bi ở trạng thái Xong).',
    'Cột Trạng thái chọn: Chưa làm / Đang làm / Xong / Vướng. Bảng tiến độ bên dưới tự đếm.',
    'Ngày làm việc tính bằng WORKDAY (bỏ thứ Bảy, Chủ nhật; chưa trừ ngày lễ — nếu trùng lễ, dời Ngày N).',
]
for t in legend:
    r += 1
    c = ws.cell(row=r, column=1, value='• ' + t)
    c.font = Font(name=F, size=10)
    c.alignment = Alignment(wrap_text=True, vertical='top')
    ws.merge_cells(start_row=r, start_column=1, end_row=r, end_column=5)
    ws.row_dimensions[r].height = 28

r = 11
ws.cell(row=r, column=1, value='Ngày N (nhận đủ thông tin)').font = Font(name=F, bold=True)
n = ws.cell(row=r, column=2, value=date(2026, 11, 2))
n.number_format = 'dd/mm/yyyy'
mark_input(n)
n.comment = Comment('Ví dụ. Thay bằng ngày thực tế Công ty nhận đủ thông tin BV chuyển giao.', 'KH')
ws.cell(row=r, column=5, value='← ví dụ, thay bằng ngày thực tế').font = Font(name=F, size=9, italic=True, color='808080')
N_REF = "'Tong quan'!$B$11"

r = 13
header_row = r
for i, h in enumerate(['Giai đoạn', 'Bắt đầu', 'Kết thúc', 'Số ngày', 'Kết quả đầu ra'], 1):
    c = ws.cell(row=r, column=i, value=h)
    c.font = Font(name=F, bold=True, color='FFFFFF', size=10)
    c.fill = fill_head
    c.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
    c.border = box
ws.freeze_panes = None
phases = [
    ('0. BV chuẩn bị (điều kiện tiên quyết)', 'Trước Ngày N', '=WORKDAY(B11,-1)', None,
     'Máy chủ, mạng, tài khoản cổng BHXH, ký số, danh mục, nhân sự sẵn sàng — xem sheet BV chuan bi'),
    ('1. Ngày 1 — Cài đặt hạ tầng', '=B11', '=B15', '=NETWORKDAYS(B15,C15)',
     'Phần mềm chạy trên máy chủ BV; đủ dịch vụ hàng đợi; kết nối cổng BHXH, ký số thử thành công'),
    ('2. Ngày 2 — Danh mục & cấu hình', '=WORKDAY(B11,1)', '=B16', '=NETWORKDAYS(B16,C16)',
     'Đủ 13 bộ danh mục; cấu hình cơ sở KCB; danh mục mã lỗi đã duyệt mức; chạy lô hồ sơ mẫu'),
    ('3. Ngày 3 — Đào tạo & đưa vào chạy thử', '=WORKDAY(B11,2)', '=B17', '=NETWORKDAYS(B17,C17)',
     'Người dùng được đào tạo; quy trình vận hành ban hành; biên bản bắt đầu chạy thử'),
    ('4. Chạy thử 1 tháng (song song quy trình cũ)', '=WORKDAY(B11,3)', '=EDATE(B18,1)-1', '=C18-B18+1',
     'Số liệu 4 tuần; danh mục và mức lỗi đã hiệu chỉnh; báo cáo tuần'),
    ('5. Đánh giá & quyết định', '=WORKDAY(C18,1)', '=WORKDAY(C18,3)', '=NETWORKDAYS(B19,C19)',
     'Bảng đánh giá (sheet Danh gia) + biên bản: Đạt → bật gửi cổng/nhân rộng; Chưa đạt → kéo dài, khắc phục'),
]
for p in phases:
    r += 1
    put(ws, r, list(p), centers=(2, 3, 4))
    for col in (2, 3):
        ws.cell(row=r, column=col).number_format = 'dd/mm/yyyy'

r += 2  # 20
ws.cell(row=r, column=1, value='TIẾN ĐỘ (tự đếm theo cột Trạng thái)').font = Font(name=F, bold=True, color=NAVY)
r += 1
for i, h in enumerate(['Sheet', 'Tổng việc', 'Xong', 'Vướng', '% hoàn thành'], 1):
    c = ws.cell(row=r, column=i, value=h)
    c.font = Font(name=F, bold=True, color='FFFFFF', size=10)
    c.fill = fill_head
    c.border = box
    c.alignment = Alignment(horizontal='center')
prog_rows_start = r + 1
PROG = []  # filled later: (sheet, status col, first, last)

r_prog = r
ws_tq = ws

# =====================================================================================
# 2. BV chuẩn bị
# =====================================================================================
ws = wb.create_sheet('BV chuan bi')
title(ws, 'DANH MỤC BỆNH VIỆN CẦN CHUẨN BỊ TRƯỚC NGÀY N',
      'Đủ các dòng "Bắt buộc" thì mới tính Ngày N. Thông tin bí mật (mật khẩu cổng, mã PIN token) KHÔNG ghi vào tệp này — bàn giao trực tiếp cho cán bộ cài đặt.', ncol=9)
cols = ['STT', 'Nhóm', 'Hạng mục', 'Yêu cầu chi tiết', 'BV phụ trách', 'Bắt buộc', 'Hạn', 'Trạng thái', 'Ghi chú / căn cứ']
header(ws, 4, cols, [5, 14, 26, 58, 16, 9, 12, 11, 40])
groups = [
    ('A. MÁY CHỦ', [
        ('Máy chủ ứng dụng', 'Windows Server 2016 trở lên (khuyến nghị — các dịch vụ chạy nền cài bằng nssm) hoặc Linux/Docker. '
         'Tối thiểu 4 vCPU / 8 GB RAM / SSD 200 GB; khuyến nghị 8 vCPU / 16 GB RAM / SSD 500 GB cho BV hạng I.', 'CNTT', 'Có',
         'Giả định cấu hình theo khối lượng hồ sơ BV hạng I; một lần xuất danh sách lỗi ngày lớn cần ~2,5 GB RAM (Quy trình vận hành, Phụ lục B).'),
        ('Phần mềm nền', 'PHP 7.4 (memory_limit ≥ 512M, max_execution_time ≥ 600), MySQL 8.0, Redis 6, IIS/Nginx/Apache; quyền quản trị (Administrator) để cài dịch vụ.', 'CNTT', 'Có',
         'config/xml3176.php: import_time_limit 600 s, import_memory_limit 512M. Kinh nghiệm: PHP 128MB/120s làm hỏng nạp và xuất.'),
        ('Truy cập từ xa', 'Tài khoản Remote Desktop/VPN cho Công ty trong 3 ngày triển khai và 1 tháng chạy thử.', 'CNTT', 'Có', ''),
        ('Sao lưu', 'Lịch sao lưu CSDL qlbv hằng ngày; nơi lưu bản sao lưu ngoài máy chủ.', 'CNTT', 'Không', ''),
    ]),
    ('B. MẠNG & KẾT NỐI', [
        ('Truy cập nội bộ', 'Địa chỉ IP/tên miền nội bộ cho máy chủ; người dùng các khoa, phòng truy cập được bằng trình duyệt.', 'CNTT', 'Có', ''),
        ('Ra Internet tới cổng BHXH', 'Máy chủ gọi được HTTPS (443) tới Cổng giám định BHXH (lấy token, tra thẻ, gửi hồ sơ).', 'CNTT', 'Có',
         'Kết nối ra ngoài duy nhất là cổng BHXH (deck giới thiệu, slide An toàn).'),
        ('Thư mục nhận XML từ HIS', 'Thư mục chia sẻ mà HIS xuất tệp XML 3176 vào (ví dụ D:\\XML\\3176); máy chủ có quyền đọc + xoá tệp sau khi nạp.', 'CNTT + Nhà cung cấp HIS', 'Có',
         'Lệnh xml3176import:day quét thư mục, nạp xong xoá tệp gốc; tệp hỏng chuyển vào thư mục con "loi".'),
        ('Thư mục xuất XML đã ký', 'Thư mục lưu XML đã xuất/ký (ví dụ D:\\XML\\ExportXml3176).', 'CNTT', 'Có', ''),
        ('Máy chủ mail (SMTP)', 'Tài khoản SMTP để gửi email báo lỗi hằng ngày.', 'CNTT', 'Không', 'Bảng email_receive_report.'),
    ]),
    ('C. HIS & DỮ LIỆU', [
        ('HIS xuất XML 3176', 'HIS xuất được đủ XML1–XML15 theo QĐ 3176 cho hồ sơ BHYT; xác nhận thời điểm xuất (khi ra viện / khi duyệt khoá viện phí).', 'Nhà cung cấp HIS', 'Có',
         'Module XML3176 dùng được với mọi HIS vì chỉ nhận tệp chuẩn.'),
        ('Bộ hồ sơ mẫu', 'Tệp XML của ít nhất 1 ngày ra viện (ngoại trú + nội trú) và kết quả giám định cổng của các hồ sơ đó để đối chiếu.', 'P. BHYT / Giám định', 'Có', 'Dùng ở Ngày 2 và làm số liệu nền.'),
        ('Số liệu nền (baseline)', 'Số hồ sơ gửi cổng/tháng, số hồ sơ bị cổng trả về, số tiền xuất toán của 3 tháng gần nhất.', 'P. BHYT / Giám định', 'Có', 'Để so sánh khi đánh giá.'),
        ('Tài khoản đọc CSDL HIS', 'Chỉ cần nếu sau này triển khai Kiểm sai sót y lệnh; không bắt buộc cho XML 3176.', 'CNTT', 'Không', 'Ngoài phạm vi đợt này.'),
    ]),
    ('D. CỔNG BHXH & KÝ SỐ', [
        ('Mã cơ sở KCB', 'Mã CSKCB của BV và các cơ sở trực thuộc (nếu có). Mã tỉnh tự lấy từ 2 ký tự đầu.', 'P. BHYT', 'Có', 'config organization.BHYT_CO_SO — mỗi cơ sở một tài khoản.'),
        ('Tài khoản cổng giám định', 'Tên đăng nhập + mật khẩu cổng cho từng cơ sở; họ tên và CCCD cán bộ tra cứu thẻ.', 'P. BHYT', 'Có', 'Bàn giao trực tiếp, không ghi vào tệp.'),
        ('Danh sách mã ĐKBĐ đúng tuyến', 'Mã nơi đăng ký KCB ban đầu được coi là đúng tuyến.', 'P. BHYT', 'Có', 'organization.correct_facility_code.'),
        ('Chứng thư số', 'USB Token cắm cố định vào máy chủ (kèm driver, mã PIN) hoặc thông tin kết nối HSM.', 'CNTT + Lãnh đạo', 'Có',
         'Rút token = mọi hồ sơ dừng ở bước ký.'),
    ]),
    ('E. NHÂN SỰ & TỔ CHỨC', [
        ('Quyết định tổ triển khai', 'Lãnh đạo bảo trợ; đầu mối KHTH/P. BHYT (chủ trì); 1 người phụ trách danh mục BHYT; 1 cán bộ CNTT.', 'Ban Giám đốc', 'Có',
         'Tài liệu tổng hợp mục 7; deck giới thiệu slide Đề xuất.'),
        ('Danh sách người dùng', 'Họ tên, khoa/phòng, vai trò, email của người được cấp tài khoản (P. BHYT, KHTH, TCKT, CNTT, đầu mối khoa).', 'P. BHYT', 'Có', 'Quyền màn hình XML 3176: role xml-man.'),
        ('Lịch đào tạo Ngày 3', 'Phòng họp có máy chiếu + máy tính truy cập phần mềm; triệu tập học viên theo 4 nhóm.', 'KHTH', 'Có', ''),
        ('Chủ sở hữu danh mục mã lỗi', 'Chỉ định người duyệt mức Nghiêm trọng/Cảnh báo của từng mã lỗi.', 'P. BHYT', 'Có', 'Tài liệu tổng hợp mục 8.'),
        ('Danh mục khoa phòng', 'Danh sách mã khoa trên HIS, khoa không kiểm giường/không đối chiếu thuốc (nếu có).', 'KHTH', 'Không', 'organization.exclude_department, xml3176.drug_exclude_department.'),
    ]),
]
r = 4
stt = 0
for g, items in groups:
    r += 1
    put(ws, r, [None, g] + [None] * 7, grp=True)
    ws.merge_cells(start_row=r, start_column=2, end_row=r, end_column=9)
    for name, req, who, must, note in items:
        r += 1
        stt += 1
        put(ws, r, [stt, g.split('. ')[1].title(), name, req, who, must, f'=WORKDAY({N_REF},-1)', 'Chưa làm', note], centers=(1, 6, 7, 8))
        ws.cell(row=r, column=7).number_format = 'dd/mm/yyyy'
first, last = 5, r
status_dv(ws, f'H{first}:H{last}')
PROG.append(('BV chuan bi', 'H', first, last))
r += 2
c = ws.cell(row=r, column=3, value='Số mục Bắt buộc chưa Xong:')
c.font = Font(name=F, bold=True)
c = ws.cell(row=r, column=4, value=f'=COUNTIFS(F{first}:F{last},"Có",H{first}:H{last},"<>Xong")')
c.font = Font(name=F, bold=True, color='C00000')
c = ws.cell(row=r + 1, column=3, value='Đủ điều kiện tính Ngày N?')
c.font = Font(name=F, bold=True)
c = ws.cell(row=r + 1, column=4, value=f'=IF(D{r}=0,"ĐỦ — chốt Ngày N","CHƯA — còn "&D{r}&" mục bắt buộc")')
c.font = Font(name=F, bold=True)

# =====================================================================================
# 3. Danh mục
# =====================================================================================
ws = wb.create_sheet('Danh muc')
title(ws, 'DANH MỤC BHYT CẦN NẠP (13 bộ — config/danh_muc_bhyt.php)',
      'Danh mục thiếu hoặc sai ngày hiệu lực sinh lỗi giả hàng loạt (đã đo: 3 danh mục rỗng → ~36.100 vi phạm giả). '
      'Tệp tải thẳng từ cổng BHXH có tiêu đề ở dòng 5, không nhập trực tiếp được — phải chuyển về biểu mẫu nhập của phần mềm.', ncol=9)
cols = ['STT', 'Danh mục', 'Theo cơ sở KCB', 'Nguồn', 'Kiểm tra sau khi nạp', 'BV cung cấp', 'Công ty nạp', 'Trạng thái', 'Ghi chú']
header(ws, 4, cols, [5, 30, 11, 40, 42, 16, 14, 11, 34])
dm = [
    ('DM thuốc BHYT', 'Có', 'Danh mục thuốc BV đã được BHXH duyệt (cổng giám định) — gồm số đăng ký, hàm lượng, giá, thông tin thầu', 'Số dòng khớp cổng; còn hiệu lực; mã thuốc trong hồ sơ mẫu đều tra ra', 'Hàm lượng/thầu sai là nguyên nhân lớn nhất của báo nhầm "sai thầu".'),
    ('DM Vật tư y tế', 'Có', 'Danh mục VTYT đã duyệt trên cổng', 'Số dòng; hiệu lực; mã VTYT trong hồ sơ mẫu tra ra', ''),
    ('DM Dịch vụ kỹ thuật', 'Có', 'Danh mục DVKT đã duyệt trên cổng (kể cả mã có hậu tố _GT/_BS)', 'Số dòng; giá; hiệu lực; mã DVKT trong hồ sơ mẫu tra ra', 'Mã có hậu tố tra nguyên văn rồi mới tra mã gốc.'),
    ('DM ICD-10', 'Không', 'Bộ Y tế / cổng BHXH', 'Số dòng', ''),
    ('DM ICD-YHCT', 'Không', 'Bộ Y tế / cổng BHXH', 'Số dòng', 'Cần nếu BV có khoa YHCT.'),
    ('DM Nhân viên y tế', 'Không', 'Danh sách NVYT đã khai trên cổng (mã BHXH người hành nghề, CCHN, phạm vi)', 'NVYT trong hồ sơ mẫu đều có; CCHN còn hiệu lực', 'Thiếu NVYT sinh lỗi XMLComplete_NVYT_*.'),
    ('DM Khoa Phòng Giường', 'Không', 'Danh mục khoa/phòng/giường đã khai trên cổng', 'Mã khoa, mã giường trong hồ sơ mẫu tra ra', ''),
    ('DM Trang thiết bị', 'Không', 'Danh mục trang thiết bị (mã máy) đã khai trên cổng', 'Số dòng', ''),
    ('DM Đơn vị hành chính', 'Không', 'Danh mục hành chính bản ký số (danhmuchanhchinh.nso.gov.vn)', 'Số dòng; mã xã trong hồ sơ mẫu tra ra', 'Không dùng bản PDF dự thảo — lệch ~120 mã.'),
    ('DM Cơ sở KCB', 'Không', 'Danh mục cơ sở KCB toàn quốc', 'Mã nơi ĐKBĐ, nơi chuyển đi/đến trong hồ sơ mẫu tra ra', ''),
    ('DM Nghề nghiệp', 'Không', 'Danh mục nghề nghiệp theo QĐ 3176', 'Số dòng', ''),
    ('DM DVKT cần mã máy', 'Không', 'Danh sách DVKT bắt buộc kèm mã máy', 'Số dòng', ''),
    ('DM bệnh PL1 TT01 cấp chuyên sâu', 'Không', 'Phụ lục 1 Thông tư 01 — bệnh cấp chuyên sâu', 'Số dòng', ''),
]
r = 4
for i, (name, cs, src, chk, note) in enumerate(dm, 1):
    r += 1
    put(ws, r, [i, name, cs, src, chk, f'=WORKDAY({N_REF},-1)', f'=WORKDAY({N_REF},1)', 'Chưa làm', note], centers=(1, 3, 6, 7, 8))
    for col in (6, 7):
        ws.cell(row=r, column=col).number_format = 'dd/mm/yyyy'
status_dv(ws, f'H5:H{r}')
PROG.append(('Danh muc', 'H', 5, r))
r += 2
c = ws.cell(row=r, column=2, value='Danh mục mã lỗi XML 3176')
c.font = Font(name=F, bold=True)
c = ws.cell(row=r, column=4, value='Phần mềm tự sinh khi chạy hồ sơ; mã mới chưa có trong danh mục mặc định là Nghiêm trọng. Ngày 2: P. BHYT cùng Công ty duyệt mức Nghiêm trọng/Cảnh báo cho từng mã.')
c.alignment = wrap
c.font = Font(name=F, size=10)
ws.merge_cells(start_row=r, start_column=4, end_row=r, end_column=9)
ws.row_dimensions[r].height = 30

# =====================================================================================
# 4. Cấu hình
# =====================================================================================
ws = wb.create_sheet('Cau hinh')
title(ws, 'CẤU HÌNH CẦN THIẾT LẬP',
      'Giá trị đề xuất cho giai đoạn chạy thử. Cột "Giá trị BV" ghi giá trị thực tế khi cài (trừ mật khẩu). Sau mỗi lần sửa cấu hình: php artisan config:cache rồi queue:restart.', ncol=7)
cols = ['STT', 'Nhóm', 'Khoá cấu hình / thành phần', 'Ý nghĩa', 'Đề xuất khi chạy thử', 'Giá trị BV (ghi khi cài)', 'Trạng thái']
header(ws, 4, cols, [5, 16, 40, 46, 34, 26, 11])
cfg = [
    ('Đơn vị', 'organization.organization_name / organization_logo', 'Tên, logo hiển thị trên phần mềm và phiếu in', 'Bệnh viện Nguyễn Thị Thập'),
    ('Đơn vị', 'organization.BHYT_CO_SO[mã CSKCB]', 'Tài khoản cổng theo từng cơ sở (username, password, ho_ten_cb, cccd_cb); chưa khai thì tra thẻ báo lỗi, không dùng tài khoản khác', 'Khai đủ mọi cơ sở'),
    ('Đơn vị', 'organization.correct_facility_code', 'Danh sách mã nơi ĐKBĐ đúng tuyến', 'Theo P. BHYT'),
    ('Đơn vị', 'organization.exclude_department', 'Mã khoa không kiểm chỉ định giường (XML3)', 'Theo KHTH (mặc định rỗng)'),
    ('Đơn vị', 'xml3176.drug_exclude_department', 'Mã khoa không đối chiếu danh mục thuốc', 'Rỗng'),
    ('Kiểm lỗi', 'organization.xml_3176_not_check', 'true = bỏ kiểm tra chéo toàn hồ sơ', 'false'),
    ('Kiểm lỗi', 'organization.export_xml_not_check', 'true = vẫn xuất/ký hồ sơ còn lỗi Nghiêm trọng', 'false'),
    ('Kiểm lỗi', 'Danh mục mã lỗi XML 3176 (is_check, critical_error)', 'Bật/tắt từng mã lỗi; đặt mức Nghiêm trọng/Cảnh báo', 'Duyệt Ngày 2; chỉ P. BHYT đổi, ghi lý do'),
    ('Thẻ BHYT', 'organization.BHYT.enableCheck', 'Tra thẻ BHYT online khi nạp XML1', 'true'),
    ('Xuất/ký/gửi', 'xml3176.export_xml3176_enabled', 'Tự xuất XML cho hồ sơ hết lỗi Nghiêm trọng', 'true'),
    ('Xuất/ký/gửi', 'Ký số (USB Token / HSM)', 'Chứng thư dùng ký XML trước khi gửi', 'Bật, ký thử ở Ngày 1'),
    ('Xuất/ký/gửi', 'organization.BHYT.submit_xml_3176_enabled', 'Tự gửi hồ sơ lên cổng BHXH', 'false — TẮT suốt thời gian chạy thử; chỉ bật khi đánh giá Đạt'),
    ('Thư mục', 'Đường dẫn nạp (config/filesystems.php)', 'Thư mục HIS xuất XML 3176', 'Theo sheet BV chuan bi mục B'),
    ('Thư mục', 'Đường dẫn xuất (ExportXml3176)', 'Thư mục lưu XML đã ký', 'Theo sheet BV chuan bi mục B'),
    ('Dịch vụ nền', 'QLBV XMLImport3176 (xml3176import:day)', 'Quét thư mục, nạp hồ sơ tự động', 'Chạy thường trực'),
    ('Dịch vụ nền', 'QLBV JobXml3176', 'Hàng đợi kiểm lỗi', 'Chạy thường trực'),
    ('Dịch vụ nền', 'QLBV JobKtTheBHYT', 'Hàng đợi tra thẻ BHYT', 'Chạy thường trực'),
    ('Dịch vụ nền', 'QLBV JobExportXml3176', 'Hàng đợi xuất XML', 'Chạy thường trực'),
    ('Dịch vụ nền', 'QLBV JobSignXml3176', 'Hàng đợi ký số', 'Chạy thường trực; dừng thì hồ sơ kẹt ở bước ký'),
    ('Dịch vụ nền', 'QLBV JobSubmitXml3176', 'Hàng đợi gửi cổng', 'Cài sẵn; không có việc khi gửi đang tắt'),
    ('Dịch vụ nền', 'QLBV JobXuatTepXml3176 (kết nối xuat_tep)', 'Xuất danh sách lỗi ra Excel chạy nền', 'Chạy thường trực'),
    ('Phân quyền', 'Role xml-man, superadministrator', 'Quyền màn hình XML 3176 / quản trị danh mục', 'Theo danh sách người dùng'),
    ('Thông báo', 'Bảng email_receive_report', 'Người nhận email lỗi thẻ BHYT + lỗi XML 3176 hằng ngày', 'Lãnh đạo KHTH, P. BHYT, CNTT'),
]
r = 4
for i, (g, k, y, d) in enumerate(cfg, 1):
    r += 1
    put(ws, r, [i, g, k, y, d, None, 'Chưa làm'], centers=(1, 7))
    ws.cell(row=r, column=6).fill = fill_in
status_dv(ws, f'G5:G{r}')
PROG.append(('Cau hinh', 'G', 5, r))

# =====================================================================================
# 5. Triển khai 3 ngày
# =====================================================================================
ws = wb.create_sheet('Trien khai 3 ngay')
title(ws, 'KẾ HOẠCH TRIỂN KHAI 3 NGÀY (tính từ Ngày N)', 'Ngày tự tính theo ô Ngày N ở sheet Tong quan.', ncol=9)
cols = ['STT', 'Ngày', 'Buổi', 'Công việc', 'Công ty', 'BV phối hợp', 'Kết quả / tiêu chí xong', 'Trạng thái', 'Ghi chú']
header(ws, 4, cols, [5, 12, 8, 50, 14, 18, 44, 11, 26])
days = [
    ('NGÀY 1 — CÀI ĐẶT HẠ TẦNG', 0, [
        ('Sáng', 'Họp khởi động: thống nhất phạm vi, lịch, đầu mối; rà lại sheet BV chuan bi', 'Trưởng nhóm TK', 'Tổ triển khai BV', 'Biên bản khởi động'),
        ('Sáng', 'Kiểm tra máy chủ (CPU/RAM/ổ đĩa), cài PHP/MySQL/Redis/web server, cấu hình php.ini', 'Kỹ sư', 'CNTT', 'php -v, mysql, redis chạy; memory_limit ≥ 512M'),
        ('Sáng', 'Triển khai mã nguồn, tạo .env, php artisan migrate, tạo tài khoản quản trị', 'Kỹ sư', 'CNTT', 'Đăng nhập được giao diện web'),
        ('Chiều', 'Cài các dịch vụ nền (install_service.bat / nssm), đặt tự khởi động', 'Kỹ sư', 'CNTT', 'Đủ 7 dịch vụ XML 3176 ở trạng thái Running'),
        ('Chiều', 'Nối thư mục HIS xuất XML; thả 1 tệp thử, kiểm tra nạp và xoá tệp gốc', 'Kỹ sư', 'CNTT + NCC HIS', 'Hồ sơ hiện ở Danh sách hồ sơ'),
        ('Chiều', 'Khai tài khoản cổng theo cơ sở; lấy token, tra thử 5 thẻ BHYT', 'Kỹ sư', 'P. BHYT', 'Tra thẻ trả kết quả, không lỗi đăng nhập'),
        ('Chiều', 'Cài driver USB Token/HSM; ký thử 1 tệp XML', 'Kỹ sư', 'CNTT', 'Cột "Ký XML" = Đã ký số'),
        ('Chiều', 'Truy cập từ máy trạm các khoa, phòng; cấu hình sao lưu', 'Kỹ sư', 'CNTT', 'Mở được phần mềm từ ≥ 3 máy trạm'),
    ]),
    ('NGÀY 2 — DANH MỤC & CẤU HÌNH', 1, [
        ('Sáng', 'Nạp 13 bộ danh mục (sheet Danh muc); đối chiếu số dòng với cổng', 'Kỹ sư', 'Người phụ trách DM', 'Đủ 13 bộ, số dòng khớp'),
        ('Sáng', 'Nhập cấu hình đơn vị, khoa loại trừ, mã ĐKBĐ đúng tuyến (sheet Cau hinh)', 'Kỹ sư', 'P. BHYT, KHTH', 'config:cache; queue:restart'),
        ('Sáng', 'Tạo tài khoản người dùng, phân quyền; khai người nhận email báo lỗi', 'Kỹ sư', 'P. BHYT', 'Người dùng đăng nhập được'),
        ('Chiều', 'Nạp bộ hồ sơ mẫu (1 ngày ra viện); chờ hàng đợi về 0', 'Kỹ sư', 'P. BHYT', 'Mọi hồ sơ mẫu có kết quả kiểm'),
        ('Chiều', 'Rà kết quả kiểm cùng P. BHYT: phân loại lỗi thật / báo nhầm do danh mục, cấu hình', 'Trưởng nhóm TK', 'P. BHYT / Giám định', 'Danh sách báo nhầm + hướng xử lý'),
        ('Chiều', 'Duyệt mức Nghiêm trọng/Cảnh báo cho danh mục mã lỗi; sửa danh mục, nạp lại hồ sơ mẫu', 'Trưởng nhóm TK', 'Chủ sở hữu DM mã lỗi', 'Biên bản duyệt mức lỗi'),
        ('Chiều', 'Đối chiếu hồ sơ mẫu với kết quả giám định thực tế của cổng → số liệu nền', 'Trưởng nhóm TK', 'P. BHYT', 'Bảng đối chiếu ban đầu'),
    ]),
    ('NGÀY 3 — ĐÀO TẠO & ĐƯA VÀO CHẠY THỬ', 2, [
        ('Sáng', 'Giới thiệu cho lãnh đạo BV, lãnh đạo KHTH (deck giới thiệu, ~15–30 phút): mục tiêu, cách đánh giá', 'Trưởng nhóm TK', 'Ban Giám đốc, KHTH', 'Lãnh đạo thống nhất tiêu chí đánh giá'),
        ('Sáng', 'Đào tạo sâu P. BHYT / Giám định: danh sách hồ sơ, tab lỗi, danh mục mã lỗi, dashboard, kết quả tra thẻ, xuất Excel', 'Chuyên viên ĐT', 'P. BHYT / Giám định', 'Học viên tự lọc, giao được lỗi'),
        ('Sáng', 'Đào tạo TCKT: tra cứu tiền cùng chi trả, đối chiếu chi phí', 'Chuyên viên ĐT', 'P. TCKT', ''),
        ('Chiều', 'Đào tạo đầu mối các khoa lâm sàng: tra lỗi theo mã điều trị (quét mã vạch), in phiếu lỗi, sửa trên HIS rồi nạp lại', 'Chuyên viên ĐT', 'Đầu mối khoa', 'Mỗi khoa ≥ 1 người dùng được'),
        ('Chiều', 'Đào tạo CNTT vận hành: dịch vụ nền, hàng đợi, ký số, xử lý sự cố, lệnh chạy lại', 'Kỹ sư', 'CNTT', 'CNTT tự khởi động lại dịch vụ'),
        ('Chiều', 'Ban hành Quy trình vận hành XML 3176 (SOP, SLA sửa lỗi 2 ngày làm việc), lịch họp tuần', 'Trưởng nhóm TK', 'Lãnh đạo KHTH', 'Văn bản ban hành'),
        ('Chiều', 'Bật nạp tự động toàn viện; ký biên bản bắt đầu chạy thử (gửi cổng vẫn TẮT)', 'Trưởng nhóm TK', 'Tổ triển khai BV', 'Biên bản bắt đầu chạy thử'),
    ]),
]
r = 4
stt = 0
for g, off, items in days:
    r += 1
    put(ws, r, [None, g] + [None] * 7, grp=True)
    ws.merge_cells(start_row=r, start_column=2, end_row=r, end_column=9)
    for buoi, job, cty, bv, kq in items:
        r += 1
        stt += 1
        put(ws, r, [stt, f'=WORKDAY({N_REF},{off})' if off else f'={N_REF}', buoi, job, cty, bv, kq, 'Chưa làm', None], centers=(1, 2, 3, 8))
        ws.cell(row=r, column=2).number_format = 'dd/mm/yyyy'
status_dv(ws, f'H5:H{r}')
PROG.append(('Trien khai 3 ngay', 'H', 5, r))

# =====================================================================================
# 6. Chạy thử 1 tháng
# =====================================================================================
ws = wb.create_sheet('Chay thu 1 thang')
title(ws, 'CHẠY THỬ 1 THÁNG — SONG SONG QUY TRÌNH CŨ',
      'Hồ sơ vẫn gửi cổng bằng đường hiện tại; Tiền giám định kiểm trước để BV sửa và để đo. Không bật tự gửi cổng trong giai đoạn này.', ncol=9)
cols = ['STT', 'Tuần', 'Từ ngày', 'Đến ngày', 'Công việc', 'Phụ trách', 'Đầu ra', 'Trạng thái', 'Ghi chú']
header(ws, 4, cols, [5, 10, 12, 12, 54, 20, 36, 11, 24])
S = f'WORKDAY({N_REF},3)'
weeks = [
    ('Tuần 1', f'={S}', f'={S}+6', [
        ('Theo dõi hằng ngày: hàng đợi, dịch vụ nền, email báo lỗi; xử lý sự cố trong ngày', 'CNTT + Công ty', 'Nhật ký sự cố'),
        ('Hiệu chỉnh báo nhầm phát sinh (danh mục, cấu hình, quy tắc) — không tắt mã lỗi để "cho qua"', 'Công ty + P. BHYT', 'Danh sách báo nhầm đã xử lý'),
        ('Hỗ trợ tại chỗ/từ xa người dùng; ghi câu hỏi thường gặp', 'Công ty', 'Sổ hỗ trợ'),
        ('Đo độ phủ: số hồ sơ HIS xuất / số hồ sơ đã nạp', 'P. BHYT', 'Báo cáo tuần 1'),
    ]),
    ('Tuần 2', f'={S}+7', f'={S}+13', [
        ('Áp dụng SOP: P. BHYT giao lỗi Nghiêm trọng cho khoa hằng sáng; khoa sửa trên HIS, nạp lại (SLA 2 ngày làm việc)', 'P. BHYT + khoa', 'Hồ sơ lỗi được xử lý'),
        ('Họp tuần theo Pareto mã lỗi trên Dashboard; đào tạo lại khoa nhiều lỗi', 'KHTH + P. BHYT', 'Biên bản họp tuần'),
        ('Rà lại mức Nghiêm trọng/Cảnh báo theo số liệu thật', 'Chủ sở hữu DM mã lỗi', 'Nhật ký thay đổi mức lỗi'),
    ]),
    ('Tuần 3', f'={S}+14', f'={S}+20', [
        ('Đối chiếu kết quả cổng BHXH trả về (hồ sơ gửi bằng đường cũ) với lỗi Tiền giám định đã báo trước', 'P. BHYT + Công ty', 'Tỷ lệ phát hiện trước'),
        ('Ghi các lỗi cổng trả về mà phần mềm chưa bắt → đề xuất quy tắc mới', 'P. BHYT → Công ty', 'Danh sách đề xuất quy tắc'),
        ('Kiểm thử chuỗi xuất → ký số với khối lượng thật (không gửi)', 'CNTT + Công ty', 'Tỷ lệ ký thành công'),
    ]),
    ('Tuần 4', f'={S}+21', f'=EDATE({S},1)-1', [
        ('Tổng hợp số liệu các chỉ số ở sheet Danh gia', 'P. BHYT + Công ty', 'Số liệu điền vào sheet Danh gia'),
        ('Khảo sát người dùng (P. BHYT, đầu mối khoa, CNTT)', 'KHTH', 'Kết quả khảo sát'),
        ('Lập báo cáo chạy thử: kết quả, tồn tại, đề xuất (bật gửi cổng / nhân rộng / kéo dài)', 'Công ty', 'Báo cáo chạy thử'),
    ]),
]
r = 4
stt = 0
for wk, a, b, items in weeks:
    for job, who, out in items:
        r += 1
        stt += 1
        put(ws, r, [stt, wk, a, b, job, who, out, 'Chưa làm', None], centers=(1, 2, 3, 4, 8))
        for col in (3, 4):
            ws.cell(row=r, column=col).number_format = 'dd/mm/yyyy'
status_dv(ws, f'H5:H{r}')
PROG.append(('Chay thu 1 thang', 'H', 5, r))
r += 2
c = ws.cell(row=r, column=5, value='Việc hằng ngày trong suốt chạy thử (theo Quy trình vận hành mục 7):')
c.font = Font(name=F, bold=True)
for t in ['Sáng — khoa: xử lý lỗi được giao; quét mã vạch tra lỗi hồ sơ trước khi cho ra viện.',
          'Sáng — P. BHYT: xem hồ sơ có lỗi Nghiêm trọng của các ngày ra viện gần nhất, giao khoa; xem Kết quả tra cứu thẻ (lọc "Chỉ lỗi"), Tra lại thẻ đã sửa.',
          'Hằng ngày — CNTT: kiểm hàng đợi, bộ quét thư mục, ký số; đọc email báo lỗi.',
          'Hằng tuần — P. BHYT: đối chiếu số hồ sơ lên cổng với số hồ sơ đã qua Tiền giám định để phát hiện hồ sơ gửi vòng qua.']:
    r += 1
    c = ws.cell(row=r, column=5, value='• ' + t)
    c.font = Font(name=F, size=10)
    c.alignment = wrap
    ws.merge_cells(start_row=r, start_column=5, end_row=r, end_column=9)
    ws.row_dimensions[r].height = 28

# =====================================================================================
# 7. Đánh giá
# =====================================================================================
ws = wb.create_sheet('Danh gia')
title(ws, 'ĐÁNH GIÁ KẾT QUẢ CHẠY THỬ — CÓ ĐẠT YÊU CẦU KHÔNG?',
      'Điền cột "Giá trị đo" (ô vàng) cuối tuần 4. Phần trăm nhập dạng số thập phân hoặc có dấu % (95% = 0,95). '
      'Ngưỡng là đề xuất của Công ty — chốt cùng lãnh đạo BV ở buổi sáng Ngày 3, sửa trực tiếp ô ngưỡng nếu thống nhất khác.', ncol=10)
cols = ['STT', 'Nhóm', 'Chỉ số', 'Cách đo', 'So sánh', 'Ngưỡng đạt', 'Bắt buộc', 'Giá trị đo', 'Kết quả', 'Căn cứ ngưỡng']
header(ws, 4, cols, [5, 14, 30, 50, 8, 11, 9, 12, 11, 38])
P, NUM = '0%', '0.0'
crit = [
    ('Độ phủ', 'Hồ sơ BHYT đã qua Tiền giám định', 'Số hồ sơ đã nạp / số hồ sơ BHYT HIS xuất trong kỳ', '>=', 1.0, P, 'Có', 'Quy trình vận hành mục 8: mục tiêu 100%.'),
    ('Hệ thống', 'Ngày vận hành không gián đoạn', 'Số ngày không có sự cố dừng nạp/kiểm quá 2 giờ / số ngày chạy thử', '>=', 0.95, P, 'Có', 'Đề xuất của Công ty.'),
    ('Hệ thống', 'Thời gian có kết quả kiểm', 'Thời gian từ khi nạp tới khi hồ sơ có kết quả kiểm (phút, trung vị ngày thường)', '<=', 30, '0', 'Có', 'Đề xuất của Công ty; đo bằng created_at hồ sơ và lỗi.'),
    ('Hệ thống', 'Tra thẻ BHYT thành công', 'Lượt tra có phản hồi từ cổng / tổng lượt tra', '>=', 0.95, P, 'Không', 'Đề xuất; phụ thuộc cả cổng BHXH.'),
    ('Hệ thống', 'Ký số thành công', 'Hồ sơ ký thành công / hồ sơ đưa vào ký (tuần 3–4)', '>=', 0.99, P, 'Có', 'Đề xuất của Công ty.'),
    ('Hệ thống', 'Sự cố ảnh hưởng HIS hoặc lộ dữ liệu', 'Số sự cố trong kỳ', '<=', 0, '0', 'Có', 'Nguyên tắc chỉ đọc HIS, dữ liệu ở lại BV.'),
    ('Chính xác', 'Tỷ lệ báo nhầm', 'Trên mẫu ≥ 200 lỗi chọn ngẫu nhiên tuần 4: số lỗi P. BHYT xác nhận báo nhầm / số lỗi rà', '<=', 0.05, P, 'Có', 'Đề xuất; báo nhầm cao khiến người dùng bỏ qua cảnh báo thật.'),
    ('Chính xác', 'Phát hiện trước lỗi cổng trả về', 'Số hồ sơ cổng trả về mà Tiền giám định đã báo lỗi trước / số hồ sơ cổng trả về', '>=', 0.7, P, 'Có', 'Đề xuất; phần mềm chưa trùng toàn bộ quy tắc BHXH (deck giới thiệu).'),
    ('Hiệu quả', 'Hồ sơ còn lỗi Nghiêm trọng khi gửi', 'Tỷ lệ hồ sơ còn lỗi Nghiêm trọng tại thời điểm gửi, tuần 4 so với tuần 1 (tuần 4 / tuần 1)', '<=', 0.7, P, 'Không', 'Đề xuất: giảm ≥ 30%. Quy trình vận hành mục 8.'),
    ('Hiệu quả', 'Thời gian sửa một hồ sơ lỗi', 'Từ lúc giao lỗi tới lúc nạp lại hết lỗi Nghiêm trọng (ngày làm việc, trung bình)', '<=', 2, NUM, 'Không', 'SLA 2 ngày làm việc — Quy trình vận hành mục 6, 8.'),
    ('Hiệu quả', 'Tỷ lệ hồ sơ bị cổng trả về', 'Tỷ lệ trả về tháng chạy thử / tỷ lệ trả về bình quân 3 tháng trước', '<=', 0.9, P, 'Không', 'Đề xuất: giảm ≥ 10% so với số liệu nền.'),
    ('Con người', 'Khoa có đầu mối sử dụng được', 'Số khoa có đầu mối tự tra lỗi, in phiếu lỗi / tổng số khoa có BN BHYT', '>=', 0.9, P, 'Không', 'Đề xuất của Công ty.'),
    ('Con người', 'Mức hài lòng người dùng', 'Điểm khảo sát trung bình (thang 5)', '>=', 4, NUM, 'Không', 'Đề xuất của Công ty.'),
]
r = 4
for i, (g, name, how, op, thr, fmt, must, why) in enumerate(crit, 1):
    r += 1
    res = (f'=IF(H{r}="","Chưa đo",IF(E{r}=">=",IF(H{r}>=F{r},"Đạt","Không đạt"),IF(H{r}<=F{r},"Đạt","Không đạt")))')
    put(ws, r, [i, g, name, how, op, thr, must, None, res, why], centers=(1, 5, 6, 7, 8, 9))
    ws.cell(row=r, column=6).number_format = fmt
    ws.cell(row=r, column=6).font = Font(name=F, size=10, color='0000FF')
    h = ws.cell(row=r, column=8)
    mark_input(h)
    h.number_format = fmt
    h.border = box
first, last = 5, r
dv = DataValidation(type='list', formula1='"Có,Không"', allow_blank=False)
ws.add_data_validation(dv)
dv.add(f'G{first}:G{last}')
dv2 = DataValidation(type='list', formula1='">=,<="', allow_blank=False)
ws.add_data_validation(dv2)
dv2.add(f'E{first}:E{last}')
rng = f'I{first}:I{last}'
ws.conditional_formatting.add(rng, CellIsRule(operator='equal', formula=['"Đạt"'], fill=PatternFill('solid', fgColor='C6EFCE')))
ws.conditional_formatting.add(rng, CellIsRule(operator='equal', formula=['"Không đạt"'], fill=PatternFill('solid', fgColor='FFC7CE')))

r += 2
rows_sum = [
    ('Chỉ số bắt buộc', f'=COUNTIF(G{first}:G{last},"Có")'),
    ('  — đạt', f'=COUNTIFS(G{first}:G{last},"Có",I{first}:I{last},"Đạt")'),
    ('  — chưa đo', f'=COUNTIFS(G{first}:G{last},"Có",I{first}:I{last},"Chưa đo")'),
    ('Chỉ số tham khảo', f'=COUNTIF(G{first}:G{last},"Không")'),
    ('  — đạt', f'=COUNTIFS(G{first}:G{last},"Không",I{first}:I{last},"Đạt")'),
    ('Tỷ lệ tham khảo tối thiểu phải đạt', 0.6),
]
s0 = r
for lab, v in rows_sum:
    c = ws.cell(row=r, column=3, value=lab)
    c.font = Font(name=F, bold=True, size=10)
    c = ws.cell(row=r, column=4, value=v)
    c.font = Font(name=F, size=10)
    c.alignment = Alignment(horizontal='left')
    r += 1
th = ws.cell(row=s0 + 5, column=4)
th.number_format = '0%'
mark_input(th)
th.comment = Comment('Đề xuất: ≥ 60% chỉ số tham khảo đạt. Chốt cùng lãnh đạo BV.', 'KH')
r += 1
c = ws.cell(row=r, column=3, value='KẾT LUẬN')
c.font = Font(name=F, bold=True, size=12, color=NAVY)
c = ws.cell(row=r, column=4, value=(
    f'=IF(D{s0+2}>0,"CHƯA ĐỦ SỐ LIỆU — còn "&D{s0+2}&" chỉ số bắt buộc chưa đo",'
    f'IF(AND(D{s0+1}=D{s0},D{s0+4}>=D{s0+5}*D{s0+3}),'
    f'"ĐẠT — đề xuất bật gửi cổng có kiểm soát và đưa vào vận hành thường quy",'
    f'"CHƯA ĐẠT — kéo dài chạy thử 2–4 tuần, khắc phục các chỉ số Không đạt"))'))
c.font = Font(name=F, bold=True, size=12)
c.alignment = Alignment(wrap_text=True, vertical='center')
ws.merge_cells(start_row=r, start_column=4, end_row=r, end_column=10)
ws.row_dimensions[r].height = 30
concl = r
ws.conditional_formatting.add(f'D{r}', FormulaRule(formula=[f'LEFT(D{r},3)="ĐẠT"'], fill=PatternFill('solid', fgColor='C6EFCE')))
ws.conditional_formatting.add(f'D{r}', FormulaRule(formula=[f'LEFT(D{r},4)="CHƯA"'], fill=PatternFill('solid', fgColor='FFC7CE')))
r += 2
c = ws.cell(row=r, column=3, value='Quy tắc kết luận: ĐẠT khi mọi chỉ số Bắt buộc đạt VÀ số chỉ số tham khảo đạt ≥ tỷ lệ tối thiểu. '
        'Khi Đạt, bật gửi cổng theo từng cơ sở và theo dõi thêm 2 tuần trước khi tắt quy trình cũ.')
c.font = Font(name=F, size=10, italic=True)
c.alignment = wrap
ws.merge_cells(start_row=r, start_column=3, end_row=r, end_column=10)
ws.row_dimensions[r].height = 30

# =====================================================================================
# 8. Rủi ro
# =====================================================================================
ws = wb.create_sheet('Rui ro')
title(ws, 'RỦI RO & BIỆN PHÁP', None, ncol=6)
cols = ['STT', 'Rủi ro', 'Dấu hiệu', 'Ảnh hưởng', 'Biện pháp', 'Phụ trách']
header(ws, 3, cols, [5, 32, 36, 30, 52, 16])
risks = [
    ('BV chưa đủ thông tin chuyển giao', 'Còn mục Bắt buộc ở sheet BV chuan bi', 'Lùi Ngày N', 'Rà sheet BV chuan bi hằng tuần trước Ngày N; Ngày N chỉ chốt khi đủ', 'Tổ TK BV'),
    ('Danh mục thiếu/sai hiệu lực', 'Một mã lỗi danh mục xuất hiện ở hàng loạt hồ sơ', 'Báo nhầm hàng loạt, người dùng mất tin tưởng', 'Đối chiếu số dòng với cổng khi nạp; rà Pareto mã lỗi mỗi ngày tuần 1', 'Người phụ trách DM'),
    ('HIS không xuất đủ hồ sơ', 'Độ phủ < 100%', 'Hồ sơ lên cổng không qua kiểm', 'Đối chiếu số hồ sơ hằng tuần; tắt/bắt buộc mọi đường gửi khác đi qua Tiền giám định', 'P. BHYT + NCC HIS'),
    ('Cấu hình PHP/RAM máy chủ thấp', 'Nạp/xuất lỗi hết bộ nhớ, quá thời gian', 'Hồ sơ không được kiểm', 'Kiểm php.ini Ngày 1 (memory_limit ≥ 512M, max_execution_time ≥ 600)', 'CNTT'),
    ('USB Token bị rút / HSM không phản hồi', 'Hồ sơ kẹt ở bước ký', 'Không xuất được XML đã ký', 'Cố định token vào máy chủ; dùng lệnh xml3176:chay-lai-tu-xuat sau khi khắc phục', 'CNTT'),
    ('Tài khoản cổng sai/hết hạn', 'Tra thẻ báo lỗi đăng nhập', 'Không tra được thẻ', 'Khai đúng tài khoản từng cơ sở; P. BHYT báo khi đổi mật khẩu', 'P. BHYT'),
    ('Nạp lô lớn làm hàng đợi tồn lâu', 'Hàng đợi JobXml3176 tăng, chậm có kết quả', 'Kết quả kiểm trễ', 'Nạp lô ngoài giờ; theo dõi hàng đợi; tăng số worker nếu cần', 'CNTT + Công ty'),
    ('Người dùng bỏ qua cảnh báo', 'Lỗi giao khoa không được sửa quá SLA', 'Không cải thiện chỉ số', 'SOP + SLA trong quy chế; họp tuần theo Pareto; đào tạo lại khoa nhiều lỗi', 'KHTH'),
    ('Tắt mã lỗi để "cho qua"', 'Danh mục mã lỗi đổi không có lý do', 'Che lỗi thật', 'Chỉ P. BHYT đổi mức lỗi, ghi lý do; báo nhầm gửi Công ty sửa quy tắc', 'P. BHYT'),
]
r = 3
for i, x in enumerate(risks, 1):
    r += 1
    put(ws, r, [i] + list(x), centers=(1,))

# =====================================================================================
# Tiến độ trên Tổng quan
# =====================================================================================
ws = ws_tq
r = r_prog
for sh, col, a, b in PROG:
    r += 1
    rg = f"'{sh}'!{col}{a}:{col}{b}"
    vals = [sh, f'=COUNTA({rg})', f'=COUNTIF({rg},"Xong")', f'=COUNTIF({rg},"Vướng")', f'=IF(B{r}=0,0,C{r}/B{r})']
    for i, v in enumerate(vals, 1):
        c = ws.cell(row=r, column=i, value=v)
        c.font = Font(name=F, size=10)
        c.border = box
        c.alignment = Alignment(horizontal='left' if i == 1 else 'center')
    ws.cell(row=r, column=5).number_format = '0%'
r += 2
ws.cell(row=r, column=1, value='Kết luận đánh giá (từ sheet Danh gia)').font = Font(name=F, bold=True)
c = ws.cell(row=r, column=2, value=f"='Danh gia'!D{concl}")
c.font = Font(name=F, bold=True)
c.alignment = Alignment(wrap_text=True)
ws.merge_cells(start_row=r, start_column=2, end_row=r, end_column=5)
ws.row_dimensions[r].height = 30
for row in ws.iter_rows(min_row=14, max_row=19):
    ws.row_dimensions[row[0].row].height = 30

for s in wb.worksheets:
    s.sheet_view.showGridLines = False
    s.page_setup.orientation = 'landscape'
    s.page_setup.fitToWidth = 1
    s.page_setup.fitToHeight = 0
    s.sheet_properties.pageSetUpPr.fitToPage = True

wb.calculation.fullCalcOnLoad = True
wb.save(OUT)
print('saved', OUT)
