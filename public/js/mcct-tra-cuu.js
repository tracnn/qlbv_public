/*
 * Tra cuu tien cung chi tra (MCCT) bang AJAX - DUNG CHUNG cho hai man hinh:
 * modal tren man tra cuu the BHYT, va man tra cuu MCCT rieng.
 *
 * VI SAO TACH RA MOT TEP: hai man dung y het mot khoi ket qua va y het cach xu ly cho.
 * Chep doi thi lan sua sau chac chan lech mot ben - da xay ra that voi hai con so timeout
 * (40000 trong blade canh 30 trong config).
 *
 * Cach dung:
 *
 *   var mcct = McctTraCuu.tao({
 *       url: '...',              // duong dan endpoint JSON, da kem tham so
 *       timeoutMs: 70000,
 *       o: {                     // cac vung hien thi, dang selector hoac jQuery object
 *           dangTai: '#mcct-dang-tai',
 *           giay: '#mcct-giay',
 *           cauCho: '#mcct-cau-cho',
 *           loi: '#mcct-loi',
 *           loiNoiDung: '#mcct-loi-noi-dung',
 *           ketQua: '#mcct-ket-qua'
 *       },
 *       khoa: function (dangKhoa) { ... }   // tuy man tu quyet dinh khoa cai gi
 *   });
 *   mcct.goi();
 */
window.McctTraCuu = (function ($) {
    'use strict';

    // Sau moc nay thi doi cau chu: nguoi dung can biet CHAM la binh thuong, khong phai hong.
    // De 25 chu khong 15: cong da tung mat hon 30 giay, noi "cham" tu giay thu 15 la bao
    // dong gia - nghe mai thanh quen roi khong ai tin nua.
    var MOC_CHAM_GIAY = 25;

    function tien(x) {
        var n = Math.round(Number(x) || 0);

        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /* Y-m-d (may chu tra ve) -> dd/mm/yyyy cho nguoi doc */
    function ngayVn(s) {
        var p = String(s || '').split('-');

        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : String(s || '');
    }

    /* Moi chuoi tu cong BHXH deu di qua day truoc khi vao HTML */
    function thoat(s) {
        return $('<div>').text(s === null || s === undefined ? '' : s).html();
    }

    /* '2026-09-03 16:23:35' -> '03/09/2026 16:23' */
    function gioVn(s) {
        var p = String(s || '').split(' ');

        if (p.length !== 2) {
            return String(s || '');
        }

        return ngayVn(p[0]) + ' ' + p[1].substring(0, 5);
    }

    function soLe(x) {
        return (Number(x) || 0).toFixed(2).replace('.', ',');
    }

    function bangChiPhi(dong) {
        if (!dong || dong.length === 0) {
            return '';
        }

        var h = '<div class="table-responsive"><table class="table table-condensed table-hover">'
            + '<tr><th>Mã CSKCB</th><th>Ngày vào</th><th>Ngày ra</th><th>Đối tượng</th>'
            + '<th class="text-right">Tiền CCT thuộc diện miễn</th>'
            + '<th class="text-right">Lũy kế</th><th>Ngày nhận</th></tr>';

        // Giu NGUYEN thu tu cong tra (da giam dan theo ngay ra vien), khong sap lai.
        for (var i = 0; i < dong.length; i++) {
            var d = dong[i];

            h += '<tr><td>' + thoat(d.ma_cskcb) + '</td>'
                + '<td>' + thoat(d.ngay_vao) + '</td>'
                + '<td>' + thoat(d.ngay_ra) + '</td>'
                + '<td>' + thoat(d.ma_doi_tuong_kcb) + '</td>'
                + '<td class="text-right">' + tien(d.t_bn_cct_mcct) + '</td>'
                + '<td class="text-right">' + tien(d.t_bn_cct_luy_ke) + '</td>'
                + '<td>' + thoat(d.ngay_nhan) + '</td></tr>';
        }

        return h + '</table></div>';
    }

    function bangLichSu(ds) {
        if (!ds || ds.length === 0) {
            return '';
        }

        var h = '<div class="panel panel-default"><div class="panel-body">'
            + '<div class="form-group"><b>Lịch sử tra cứu thẻ này</b></div>'
            + '<table class="table table-condensed"><tr><th>Thời điểm</th><th>Cơ sở</th>'
            + '<th>Kết quả</th><th class="text-right">Lũy kế</th>'
            + '<th class="text-right">Ngưỡng khi tra</th><th>Người tra</th></tr>';

        for (var i = 0; i < ds.length; i++) {
            var r = ds[i];

            h += '<tr><td>' + thoat(r.tra_luc) + '</td>'
                + '<td>' + thoat(r.ma_cskcb) + '</td>'
                + '<td>' + thoat(r.ma_ket_qua) + '</td>'
                + '<td class="text-right">' + tien(r.luy_ke_lon_nhat) + '</td>'
                + '<td class="text-right">' + tien(r.nguong_ap_dung) + '</td>'
                + '<td>' + thoat(r.tra_boi) + '</td></tr>';
        }

        return h + '</table></div></div>';
    }

    function dungKetQua(kq) {
        var the = kq.thong_tin_the || {};
        var h = '';

        if (the.ho_ten) {
            h += '<table class="table table-condensed"><tr>'
                + '<td>Họ tên: <b>' + thoat(the.ho_ten) + '</b></td>'
                + '<td>Ngày sinh: ' + thoat(the.ngay_sinh) + '</td>'
                + '<td>Mã số BHXH: ' + thoat(the.ma_bhxh) + '</td>'
                + '<td>Thẻ hết hạn: ' + thoat(the.ngay_ket_thuc) + '</td>'
                + '</tr></table>';
        }

        // Muc mien tinh theo diem c khoan 2 Dieu 18 ND 188/2025. May chu luon gui khoi nay khi
        // tra cuu thanh cong; van phong ho de mot phan hoi cu trong cache khong lam vo man hinh.
        var muc = kq.muc || {};
        var conPhaiDong = Number(muc.so_tien_con_phai_dong) || 0;
        var tongNguong = Number(muc.tong_nguong_ca_nam) || 0;
        var thieu = Number(muc.con_thieu) || 0;

        var nhan = kq.du_dieu_kien
            ? '<span class="label label-success">ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ</span>'
            : '<span class="label label-warning">CÒN THIẾU ' + tien(thieu) + ' đ</span>';

        // Hien TONG NGUONG CA NAM chu khong phai so con phai dong: chi con so nay moi so sanh
        // duoc truc tiep voi luy ke ben canh, vi ca hai cung tinh tu 01/01.
        h += '<div class="well well-sm"><table class="table table-condensed"><tr>'
            + '<td>Lũy kế cùng chi trả: <b>' + tien(kq.luy_ke) + ' đ</b></td>'
            + '<td>Ngưỡng cả năm: <b>' + tien(tongNguong) + ' đ</b></td>'
            + '<td>' + nhan + '</td></tr></table>';

        // API MCCT khong tra ve du kien 5 nam lien tuc, nen man hinh KHONG duoc ket luan thay
        // ca dieu kien do. Nhan o tren chi ghi "du nguong"; ve con lai phai co nguoi kiem.
        if (kq.du_dieu_kien) {
            h += '<div class="alert alert-info" style="padding: 6px 10px; margin-bottom: 8px;">'
                + 'Mới chỉ đạt <b>ngưỡng tiền</b>. Cần kiểm tra thêm điều kiện <b>tham gia BHYT '
                + 'đủ 5 năm liên tục</b> mới đủ điều kiện miễn cùng chi trả — dữ kiện này cổng '
                + 'không trả về.</div>';
        }

        // Chi hien khi trong nam CO moc doi luong co so. Khong co dong nay, nguoi dung se tu
        // tinh 6 x luong hien hanh tru luy ke roi tuong phan mem sai.
        if (muc.co_doi_luong) {
            var thangCu = Number(muc.luong_truoc_moc) > 0
                ? Number(muc.da_dong_truoc_moc) / Number(muc.luong_truoc_moc) : 0;

            h += '<div class="text-muted" style="margin-bottom: 6px;">Lương cơ sở đổi ngày '
                + thoat(ngayVn(muc.moc_doi_luong)) + '. Đã cùng chi trả '
                + tien(muc.da_dong_truoc_moc) + ' đ trước mốc, tương đương '
                + soLe(thangCu) + ' tháng lương cũ (' + tien(muc.luong_truoc_moc)
                + ' đ); còn phải cùng chi trả ' + soLe(muc.so_thang_con_lai)
                + ' tháng × ' + tien(muc.luong_hien_tai) + ' đ = <b>'
                + tien(conPhaiDong) + ' đ</b>; cộng phần đã đóng trước mốc thành ngưỡng cả năm <b>'
                + tien(tongNguong) + ' đ</b>.</div>';
        }

        // GhiChu NGUYEN VAN: no ghi du lieu cong "tinh den" thoi diem nao. So lieu cong co do
        // tre, nguoi dung phai thay moc do TRUOC khi ket luan voi nguoi benh.
        h += '<small class="text-muted">' + thoat(kq.ghi_chu) + '</small></div>';

        h += bangChiPhi(kq.dong);

        if (kq.loi_luu) {
            h += '<div class="alert alert-warning">' + thoat(kq.loi_luu) + '</div>';
        }

        h += bangLichSu(kq.lich_su);

        return h;
    }

    function tao(caiDat) {
        var o = caiDat.o || {};
        var $dangTai = $(o.dangTai);
        var $loi = $(o.loi);
        var $loiNoiDung = $(o.loiNoiDung);
        var $ketQua = $(o.ketQua);
        var $giay = $(o.giay);
        var $cauCho = $(o.cauCho);

        var timeoutMs = caiDat.timeoutMs || 70000;
        var khoa = caiDat.khoa || function () {};

        var demGio = null;
        var yeuCau = null;

        function batDauDemGio() {
            var batDau = Date.now();

            $giay.text('0');
            $cauCho.text('Đang hỏi cổng BHXH…');

            demGio = setInterval(function () {
                var giay = Math.floor((Date.now() - batDau) / 1000);

                $giay.text(giay);

                if (giay === MOC_CHAM_GIAY) {
                    $cauCho.text('Cổng BHXH đang phản hồi chậm, vẫn đang chờ…');
                }
            }, 1000);
        }

        function dungDemGio() {
            if (demGio !== null) {
                clearInterval(demGio);
                demGio = null;
            }
        }

        function hienKhoi(ten) {
            $dangTai.toggle(ten === 'tai');
            $loi.toggle(ten === 'loi');
            $ketQua.toggle(ten === 'ket-qua');
        }

        function hienLoi(thongBao) {
            $loiNoiDung.text(thongBao);
            hienKhoi('loi');
        }

        function goi(url) {
            hienKhoi('tai');
            khoa(true);
            batDauDemGio();

            yeuCau = $.ajax({
                type: 'GET',
                url: url || caiDat.url,
                dataType: 'json',
                timeout: timeoutMs
            }).done(function (kq) {
                if (!kq || typeof kq.ok === 'undefined') {
                    hienLoi('Máy chủ trả về dữ liệu không đọc được.');

                    return;
                }

                // Ma 204/400/500 KHONG phai loi he thong: hien dung thong bao cua may chu.
                if (!kq.ok) {
                    hienLoi(kq.thong_bao || 'Tra cứu không thành công.');

                    return;
                }

                $ketQua.html(dungKetQua(kq));
                hienKhoi('ket-qua');
            }).fail(function (xhr, trangThai) {
                if (trangThai === 'abort') {
                    return;
                }

                if (trangThai === 'timeout') {
                    hienLoi('Cổng BHXH không trả lời sau ' + Math.round(timeoutMs / 1000)
                        + ' giây. Thử lại sau ít phút.');

                    return;
                }

                // 422: McctRequest chan dau vao. Hien dung cau bao loi cua no.
                if (xhr.status === 422 && xhr.responseJSON) {
                    var ds = [];

                    $.each(xhr.responseJSON.errors || xhr.responseJSON, function (k, v) {
                        ds.push($.isArray(v) ? v.join(' ') : v);
                    });

                    hienLoi(ds.join(' ') || 'Thông tin tra cứu không hợp lệ.');

                    return;
                }

                hienLoi('Lỗi khi gọi máy chủ (' + xhr.status + ').');
            }).always(function () {
                dungDemGio();
                khoa(false);
                yeuCau = null;
            });
        }

        // KHONG con buoc hien ket qua da luu truoc (moDau) tu 15/9/2026: moi lan tra deu goi
        // cong de so lieu luon moi nhat. Dung them lai thi nguoi dung se doc so lieu cu ma
        // tuong la vua tra.

        /* Doi duong dan giua cac lan tra - man rieng doc lai o nhap moi lan bam */
        function capNhat(moi) {
            if (moi.url) {
                caiDat.url = moi.url;
            }
        }

        function huy() {
            if (yeuCau !== null) {
                yeuCau.abort();
            }

            dungDemGio();
            khoa(false);
        }

        return { goi: goi, capNhat: capNhat, huy: huy, dangGoi: function () { return yeuCau !== null; } };
    }

    return { tao: tao, tien: tien, thoat: thoat };
})(jQuery);
