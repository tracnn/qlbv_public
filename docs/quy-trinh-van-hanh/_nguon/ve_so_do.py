"""Vẽ hai sơ đồ cho tài liệu quy trình vận hành XML3176 (PyMuPDF + phông Arial của Windows).

Chạy:  python ve_so_do.py   -> anh/so-do-luong-tong-the.png, anh/so-do-xu-ly-loi.png
Không dùng SVG: bộ dựng SVG của MuPDF không có phông phủ tiếng Việt.
"""
import os
import pymupdf

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, 'anh')
FONT = 'C:/Windows/Fonts/arial.ttf'
FONT_B = 'C:/Windows/Fonts/arialbd.ttf'

NAVY = (0.12, 0.22, 0.39)
BLUE = (0.18, 0.33, 0.59)
SOFT = (0.91, 0.94, 0.97)
LANE = (0.97, 0.98, 0.99)
LINE = (0.55, 0.66, 0.83)
RED = (0.75, 0.16, 0.16)
SOFT_RED = (0.99, 0.91, 0.91)
ORANGE = (0.80, 0.45, 0.05)
SOFT_ORANGE = (1.0, 0.95, 0.85)
GREEN = (0.12, 0.50, 0.25)
SOFT_GREEN = (0.89, 0.96, 0.90)
GREY = (0.35, 0.35, 0.35)
WHITE = (1, 1, 1)


class Ve:
    def __init__(self, w, h):
        self.doc = pymupdf.open()
        self.pg = self.doc.new_page(width=w, height=h)
        self.pg.insert_font(fontname='ar', fontfile=FONT)
        self.pg.insert_font(fontname='arb', fontfile=FONT_B)

    def chu(self, rect, text, size=11, bold=False, color=GREY, align=1):
        r = pymupdf.Rect(rect)
        # Dò cỡ chữ lớn nhất vừa khung (không để tràn im lặng).
        s = size
        while s >= 6:
            tmp = pymupdf.open()
            tp = tmp.new_page(width=r.width + 10, height=r.height + 10)
            tp.insert_font(fontname='f', fontfile=FONT_B if bold else FONT)
            rc = tp.insert_textbox(pymupdf.Rect(0, 0, r.width, r.height), text, fontname='f', fontsize=s, align=align)
            tmp.close()
            if rc >= 0:
                break
            s -= 0.5
        # căn giữa theo chiều dọc
        tmp = pymupdf.open(); tp = tmp.new_page(width=r.width + 10, height=r.height + 10)
        tp.insert_font(fontname='f', fontfile=FONT_B if bold else FONT)
        du = tp.insert_textbox(pymupdf.Rect(0, 0, r.width, r.height), text, fontname='f', fontsize=s, align=align)
        tmp.close()
        dy = max(0, du / 2)
        self.pg.insert_textbox(pymupdf.Rect(r.x0, r.y0 + dy, r.x1, r.y1 + dy), text,
                               fontname='arb' if bold else 'ar', fontsize=s, color=color, align=align)

    def hop(self, rect, text, fill=SOFT, stroke=BLUE, color=NAVY, size=11, bold=False, width=1.2, radius=0.12):
        r = pymupdf.Rect(rect)
        self.pg.draw_rect(r, color=stroke, fill=fill, width=width, radius=radius)
        self.chu(pymupdf.Rect(r.x0 + 6, r.y0 + 4, r.x1 - 6, r.y1 - 4), text, size=size, bold=bold, color=color)
        return r

    def thoi(self, cx, cy, w, h, text, fill=SOFT_ORANGE, stroke=ORANGE, size=10.5):
        pts = [pymupdf.Point(cx, cy - h / 2), pymupdf.Point(cx + w / 2, cy), pymupdf.Point(cx, cy + h / 2), pymupdf.Point(cx - w / 2, cy)]
        self.pg.draw_polyline(pts + [pts[0]], color=stroke, fill=fill, width=1.2)
        self.chu(pymupdf.Rect(cx - w / 3, cy - h / 3, cx + w / 3, cy + h / 3), text, size=size, bold=True, color=(0.45, 0.25, 0.0))

    def mui_ten(self, pts, color=BLUE, width=1.4, nhan=None, nhan_pos=None, nhan_color=None):
        pts = [pymupdf.Point(p) for p in pts]
        for a, b in zip(pts, pts[1:]):
            self.pg.draw_line(a, b, color=color, width=width)
        a, b = pts[-2], pts[-1]
        v = (b - a)
        L = (v.x ** 2 + v.y ** 2) ** 0.5 or 1
        ux, uy = v.x / L, v.y / L
        s = 7
        p1 = pymupdf.Point(b.x - ux * s - uy * s * 0.55, b.y - uy * s + ux * s * 0.55)
        p2 = pymupdf.Point(b.x - ux * s + uy * s * 0.55, b.y - uy * s - ux * s * 0.55)
        self.pg.draw_polyline([b, p1, p2, b], color=color, fill=color, width=0.5)
        if nhan:
            x, y = nhan_pos
            self.chu(pymupdf.Rect(x - 70, y - 9, x + 70, y + 9), nhan, size=9.5, bold=True, color=nhan_color or color)

    def luu(self, ten, dpi=200):
        os.makedirs(OUT, exist_ok=True)
        self.pg.get_pixmap(dpi=dpi).save(os.path.join(OUT, ten))
        self.doc.close()


def so_do_tong_the():
    W, H = 1180, 652
    v = Ve(W, H)
    lanes = [
        ('Khoa lâm sàng\nTiếp đón', 20, 150),
        ('Hệ thống Tiền\ngiám định', 150, 330),
        ('Phòng BHYT /\nGiám định', 330, 490),
        ('Cổng BHXH', 490, 610),
    ]
    for ten, y0, y1 in lanes:
        v.pg.draw_rect(pymupdf.Rect(20, y0, W - 20, y1), color=LINE, fill=LANE, width=0.8)
        v.pg.draw_rect(pymupdf.Rect(20, y0, 120, y1), color=LINE, fill=SOFT, width=0.8)
        v.chu(pymupdf.Rect(24, y0 + 4, 116, y1 - 4), ten, size=11, bold=True, color=NAVY)

    # Giai đoạn (tiêu đề cột)
    for ten, x0, x1 in [('TRONG ĐỢT ĐIỀU TRỊ', 130, 330), ('CHỐT HỒ SƠ — TRƯỚC KHI GỬI', 340, 920), ('SAU KHI GỬI', 930, 1160)]:
        v.chu(pymupdf.Rect(x0, 624, x1, 646), ten, size=10.5, bold=True, color=BLUE)
        v.pg.draw_line(pymupdf.Point(x0, 620), pymupdf.Point(x1, 620), color=BLUE, width=1.5)

    # Khoa
    k1 = v.hop((140, 40, 320, 130), 'Nhập liệu đúng trên HIS:\nthẻ, nơi ĐKBĐ, mã đối tượng,\ny lệnh, loại kết thúc điều trị', size=10)
    ksua = v.hop((560, 40, 790, 130), 'Sửa lỗi TRÊN HIS\ntại đơn vị phát sinh\n(hạn đề xuất: 2 ngày làm việc)', fill=SOFT_RED, stroke=RED, color=RED, size=10)

    # Hệ thống
    s0 = v.hop((140, 170, 320, 240), 'Kiểm y lệnh (~60 giây)\nTra thẻ BHYT hằng ngày', size=10)
    s1 = v.hop((350, 250, 460, 310), '1. Nạp hồ sơ', bold=True, size=11)
    s2 = v.hop((490, 250, 600, 310), '2. Kiểm tra', bold=True, size=11)
    v.thoi(690, 280, 130, 90, 'Còn lỗi\nNghiêm trọng?')
    s3 = v.hop((815, 250, 905, 310), '3. Ký số', bold=True, size=11, fill=SOFT_GREEN, stroke=GREEN, color=GREEN)
    s4 = v.hop((935, 250, 1045, 310), '4. Gửi cổng', bold=True, size=11, fill=SOFT_GREEN, stroke=GREEN, color=GREEN)

    # Phòng BHYT
    b1 = v.hop((560, 380, 790, 450), 'Đọc lỗi, giao việc cho khoa\n(Tra cứu lỗi hồ sơ theo mã điều trị)', size=10)
    b5 = v.hop((1000, 355, 1155, 475), '5. Theo dõi kết quả\nLọc "Gửi có lỗi", Dashboard,\nPareto mã lỗi', bold=False, size=10)

    # Cổng
    c1 = v.hop((935, 520, 1045, 590), 'Tiếp nhận\nvà giám định', size=10.5, fill=WHITE, stroke=GREY, color=GREY)

    # Mũi tên
    v.mui_ten([(230, 130), (230, 170)])                                   # khoa -> kiểm sớm
    v.mui_ten([(320, 205), (405, 205), (405, 250)])                       # kiểm sớm -> nạp
    v.mui_ten([(460, 280), (490, 280)])                                   # nạp -> kiểm tra
    v.mui_ten([(600, 280), (625, 280)])                                   # kiểm tra -> thoi
    v.mui_ten([(755, 280), (815, 280)], color=GREEN, nhan='Không', nhan_pos=(797, 266), nhan_color=GREEN)
    v.mui_ten([(905, 280), (935, 280)], color=GREEN)
    v.mui_ten([(690, 325), (690, 380)], color=RED, nhan='Có', nhan_pos=(710, 350), nhan_color=RED)  # thoi -> giao lỗi
    v.mui_ten([(775, 380), (775, 130)], color=RED)                         # giao lỗi -> khoa sửa
    v.mui_ten([(560, 85), (405, 85), (405, 250)], color=RED, nhan='Nạp lại', nhan_pos=(470, 73), nhan_color=RED)  # sửa -> nạp lại
    v.mui_ten([(990, 310), (990, 520)], color=GREEN)                       # gửi -> cổng
    v.mui_ten([(1045, 555), (1077, 555), (1077, 475)], color=GREY, nhan='Kết quả / lỗi trả về', nhan_pos=(1100, 505), nhan_color=GREY)
    v.mui_ten([(1155, 415), (1168, 415), (1168, 20), (700, 20), (700, 40)], color=RED, width=1.2)  # lỗi cổng -> khoa sửa
    v.chu(pymupdf.Rect(900, 4, 1160, 20), 'Cổng trả lỗi: giao khoa sửa trên HIS, nạp lại, gửi lại', size=9, bold=True, color=RED)

    v.luu('so-do-luong-tong-the.png')


def so_do_xu_ly_loi():
    W, H = 1000, 560
    v = Ve(W, H)
    a = v.hop((380, 20, 620, 70), 'Hồ sơ có lỗi sau bước 2. Kiểm tra', bold=True, size=11)
    v.thoi(500, 135, 230, 90, 'Quy tắc báo đúng\n(lỗi thật)?')
    v.mui_ten([(500, 70), (500, 90)])

    # Báo nhầm
    bn = v.hop((720, 100, 980, 170), 'BÁO NHẦM: ghi mã hồ sơ, gửi CNTT\nsửa quy tắc. KHÔNG tắt mã lỗi\ntrong danh mục để "cho qua"', fill=SOFT, stroke=BLUE, size=10)
    v.mui_ten([(615, 135), (720, 135)], color=BLUE, nhan='Không', nhan_pos=(665, 123))

    v.thoi(500, 255, 200, 80, 'Mức lỗi?')
    v.mui_ten([(500, 180), (500, 215)], nhan='Đúng', nhan_pos=(525, 197))

    # Nghiêm trọng
    n1 = v.hop((60, 310, 330, 370), 'NGHIÊM TRỌNG: chặn xuất, ký và gửi', bold=True, fill=SOFT_RED, stroke=RED, color=RED, size=10.5)
    n2 = v.hop((60, 395, 330, 450), 'Phòng BHYT giao khoa; khoa sửa trên HIS\n(hạn đề xuất: 2 ngày làm việc)', fill=SOFT_RED, stroke=RED, color=RED, size=10)
    n3 = v.hop((60, 475, 330, 530), 'Nạp lại → hết lỗi Nghiêm trọng\n→ 3. Ký số → 4. Gửi cổng', fill=SOFT_GREEN, stroke=GREEN, color=GREEN, size=10)
    v.mui_ten([(400, 255), (195, 255), (195, 310)], color=RED, nhan='Nghiêm trọng', nhan_pos=(300, 243), nhan_color=RED)
    v.mui_ten([(195, 370), (195, 395)], color=RED)
    v.mui_ten([(195, 450), (195, 475)], color=GREEN)

    # Cảnh báo
    c1 = v.hop((670, 310, 940, 370), 'CẢNH BÁO: hồ sơ vẫn được ký và gửi', bold=True, fill=SOFT_ORANGE, stroke=ORANGE, color=(0.45, 0.25, 0.0), size=10.5)
    c2 = v.hop((670, 395, 940, 450), 'Phòng BHYT tổng hợp theo tuần\n(Dashboard, Pareto mã lỗi)', fill=SOFT_ORANGE, stroke=ORANGE, color=(0.45, 0.25, 0.0), size=10)
    c3 = v.hop((670, 475, 940, 530), 'Lặp lại nhiều → nhắc khoa, đào tạo lại;\ncân nhắc nâng lên Nghiêm trọng', fill=SOFT_ORANGE, stroke=ORANGE, color=(0.45, 0.25, 0.0), size=10)
    v.mui_ten([(600, 255), (805, 255), (805, 310)], color=ORANGE, nhan='Cảnh báo', nhan_pos=(700, 243), nhan_color=ORANGE)
    v.mui_ten([(805, 370), (805, 395)], color=ORANGE)
    v.mui_ten([(805, 450), (805, 475)], color=ORANGE)

    v.luu('so-do-xu-ly-loi.png')


if __name__ == '__main__':
    so_do_tong_the()
    so_do_xu_ly_loi()
    print('OK ->', OUT)
