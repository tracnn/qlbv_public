(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var CFG = win.CTDT_DASHBOARD_CFG || {};
    var R = CFG.routes || {};

    function thamSo() {
        return {
            tu_ngay: $('#tu-ngay').val(),
            den_ngay: $('#den-ngay').val(),
            dich_vu: $('#dich-vu').val()
        };
    }

    function veHangDoi(ds) {
        var html = '<div class="row">';

        $.each(ds, function (i, hd) {
            var soJob;

            if (hd.so_job === null) {
                // Khong dem duoc KHAC voi bang 0. Bao "0 job" o day la noi doi voi nguoi doc.
                soJob = '<span class="text-muted">không đếm được</span>';
            } else if (hd.so_job > 0) {
                // TUOI moi la dau hieu worker chet, khong phai SO LUONG: mot hang doi khoe
                // dang ban cung co job dang cho, nhung no tieu het trong vai giay.
                soJob = '<span class="hang-doi-chet">' + hd.so_job + ' job đang chờ</span>';

                if (hd.cho_lau_nhat_phut !== null && hd.cho_lau_nhat_phut !== undefined) {
                    soJob += '<br><span class="hang-doi-chet">job cũ nhất đã chờ '
                        + hd.cho_lau_nhat_phut + ' phút</span>';
                }
            } else {
                soJob = '<span class="text-green">trống</span>';
            }

            html += '<div class="col-md-4"><strong>' + hd.ten + '</strong><br>' + soJob + '</div>';
        });

        $('#khoi-hang-doi').html(html + '</div>'
            + '<p class="text-muted" style="margin-top:10px">'
            + 'Hàng đợi đầy mà không vơi nghĩa là worker của nó chưa chạy. Cả ba đều bắt buộc.'
            + '</p>');
    }

    function veTrangThai(ds) {
        Highcharts.chart('chart-trang-thai', {
            chart: { type: 'bar' },
            title: { text: null },
            xAxis: { categories: $.map(ds, function (d) { return d.nhan; }) },
            yAxis: { title: { text: 'Số hồ sơ' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Hồ sơ',
                data: $.map(ds, function (d) { return d.so_luong; })
            }]
        });
    }

    function veTonDong(t) {
        if (!t.so_ho_so) {
            $('#khoi-ton-dong').html('<p class="text-green">Không có hồ sơ tồn đọng.</p>');
            return;
        }

        $('#khoi-ton-dong').html(
            '<h3>' + t.so_ho_so + '</h3>'
            + '<p>hồ sơ đã nạp nhưng chưa lên được cổng.</p>'
            + (t.cu_nhat_ngay === null ? ''
                : '<p>Cái cũ nhất đã nằm <strong>' + t.cu_nhat_ngay + ' ngày</strong>.</p>')
        );
    }

    function veSanLuong(kq) {
        $('#canh-bao-san-luong').html(kq.truc_bi_rut_ngan
            ? '<div class="alert alert-warning" style="padding:6px 10px">'
                + 'Khoảng ngày dài hơn 366 ngày nên trục đã bị cắt: '
                + 'biểu đồ chỉ vẽ 366 ngày đầu.'
                + '</div>'
            : '');

        Highcharts.chart('chart-san-luong', {
            chart: { type: 'line' },
            title: { text: null },
            xAxis: { categories: kq.ngay },
            yAxis: { title: { text: 'Số hồ sơ' }, allowDecimals: false },
            credits: { enabled: false },
            series: $.map(kq.chuoi, function (c) {
                return { name: c.ten, data: c.du_lieu };
            })
        });
    }

    function veChatLuong(kq) {
        // NOI RA khi so lieu chi con mot phan: mot bieu do ve tren tap da bi cat ma khong
        // bao gi thi doc y het mot bieu do day du.
        $('#canh-bao-chat-luong').html(kq.bi_cat
            ? '<div class="alert alert-warning" style="padding:6px 10px">'
                + 'Khoảng ngày này vượt trần hồ sơ của màn này. '
                + 'Biểu đồ chỉ tính trên những hồ sơ MỚI NHẤT, '
                + 'không phải toàn bộ. Thu hẹp khoảng ngày để xem đầy đủ.'
                + '</div>'
            : '');

        Highcharts.chart('chart-ma-loi', {
            chart: { type: 'bar' },
            title: { text: null },
            xAxis: {
                categories: $.map(kq.theo_ma_loi, function (d) {
                    // Ghep muc do vao nhan: mot ma loi CANH BAO xep tren mot ma loi CHAN se
                    // dua nguoi ta di sua sai cho.
                    return d.ma_loi + ' · ' + d.ten_truong
                        + (d.muc_do === 'chan' ? ' (chặn)' : ' (cảnh báo)');
                })
            },
            yAxis: { title: { text: 'Số lỗi' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Số lỗi',
                data: $.map(kq.theo_ma_loi, function (d) { return d.so_luong; })
            }]
        });

        Highcharts.chart('chart-cskcb', {
            chart: { type: 'column' },
            title: { text: null },
            xAxis: { categories: $.map(kq.theo_cskcb, function (d) { return d.macskcb; }) },
            yAxis: { title: { text: 'Số lỗi' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Số lỗi',
                data: $.map(kq.theo_cskcb, function (d) { return d.so_loi; })
            }]
        });
    }

    // Mot truy van 500 - hoac qua 120 giay tren may chu PHP 128MB - de lai mot div RONG:
    // khong co ca chu "Dang tai...". Doc y het "khong co ma loi nao", tuc dung nguoc su that.
    function baoLoi(o) {
        $(o).html('<div class="text-danger">Không tải được dữ liệu. '
            + 'Thử thu hẹp khoảng ngày rồi bấm Xem lại.</div>');
    }

    function dangTai(o) {
        $(o).html('<div class="text-muted">Đang tải…</div>');
    }

    function tai() {
        dangTai('#khoi-hang-doi');
        dangTai('#khoi-ton-dong');
        dangTai('#chart-trang-thai');
        dangTai('#chart-san-luong');
        dangTai('#chart-ma-loi');
        dangTai('#chart-cskcb');
        $('#canh-bao-chat-luong').empty();
        $('#canh-bao-san-luong').empty();

        $.getJSON(R.sucKhoe, thamSo())
            .done(function (kq) {
                veHangDoi(kq.hang_doi);
                veTrangThai(kq.theo_trang_thai);
                veTonDong(kq.ton_dong);
            })
            .fail(function () {
                baoLoi('#khoi-hang-doi');
                baoLoi('#khoi-ton-dong');
                baoLoi('#chart-trang-thai');
            });

        $.getJSON(R.sanLuong, thamSo())
            .done(veSanLuong)
            .fail(function () {
                baoLoi('#chart-san-luong');
            });

        $.getJSON(R.chatLuong, thamSo())
            .done(veChatLuong)
            .fail(function () {
                baoLoi('#chart-ma-loi');
                baoLoi('#chart-cskcb');
            });
    }

    $(function () {
        $('#btn-xem').on('click', tai);
        tai();
    });
})(window, jQuery);
