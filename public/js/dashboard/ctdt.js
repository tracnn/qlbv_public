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
                soJob = '<span class="hang-doi-chet">' + hd.so_job + ' job đang chờ</span>';
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

    function tai() {
        $.getJSON(R.sucKhoe, thamSo())
            .done(function (kq) {
                veHangDoi(kq.hang_doi);
                veTrangThai(kq.theo_trang_thai);
                veTonDong(kq.ton_dong);
            })
            .fail(function () {
                $('#khoi-hang-doi').html('<div class="text-danger">Không tải được dữ liệu</div>');
            });

        $.getJSON(R.sanLuong, thamSo()).done(veSanLuong);
    }

    $(function () {
        $('#btn-xem').on('click', tai);
        tai();
    });
})(window, jQuery);
