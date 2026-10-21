(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false }, lang: { thousandsSep: '.', decimalPoint: ',' } });

    var CFG = win.LANH_DAO_CFG || {};
    var COLOR = '#3c8dbc';
    var COLOR_WARN = '#dd4b39';
    var COLOR_B = '#f39c12';

    // Trạng thái nạp theo phần: seq chống kết quả cũ ghi đè, dirty = cần nạp lại khi mở tab
    var seq = {};
    var daNap = {};
    var tabDangMo = 'thanh-toan';
    var bieuDo = {};

    // Bộ lọc đã áp dụng (chụp khi vào trang và khi bấm "Xem"); mọi lần gọi dùng bản này, không đọc ô nhập đang sửa dở
    var boLoc = null;

    function chupBoLoc() {
        boLoc = { from: $('#ld-from').val(), to: $('#ld-to').val(), co_so: $('#ld-co-so').val() };
    }

    function params() {
        return $.extend({}, boLoc);
    }

    function esc(s) {
        return $('<div>').text(s === null || s === undefined ? '' : String(s)).html();
    }

    function fmt(n, dec) {
        if (n === null || n === undefined || isNaN(n)) { return '—'; }
        return Highcharts.numberFormat(n, dec === undefined ? 0 : dec);
    }

    function thongBaoLoi(xhr) {
        var j = xhr && xhr.responseJSON;
        if (j && j.errors) {
            var k = Object.keys(j.errors)[0];
            if (k && j.errors[k] && j.errors[k][0]) { return j.errors[k][0]; }
        }
        if (j && j.message) { return j.message; }
        if (xhr && xhr.status === 0) { return 'Không kết nối được máy chủ'; }
        return 'Không thể tải dữ liệu (mã ' + (xhr ? xhr.status : '?') + ')';
    }

    function goi(phan) {
        var n = (seq[phan] = (seq[phan] || 0) + 1);
        var d = $.Deferred();
        $.ajax({ url: CFG.url + '/' + phan, data: params(), dataType: 'json' })
            .done(function (res) { if (n === seq[phan]) { d.resolve(res); } })
            .fail(function (xhr) { if (n === seq[phan]) { d.reject(xhr); } });
        return d.promise();
    }

    // ── Thẻ số ───────────────────────────────────────────────────────────────

    function dongNho(o, dec, donVi) {
        donVi = donVi || '';
        function v(x) { return x === null || x === undefined ? '—' : fmt(x, dec) + donVi; }
        return 'HN ' + v(o.hn) + ' · NB ' + v(o.nb);
    }

    function setThe(ten, chinh, nho, ghiChu) {
        var $c = $('.ld-card[data-the="' + ten + '"]');
        $c.find('.ld-val').text(chinh);
        $c.find('.ld-sub').text(nho || ' ');
        $c.find('.ld-note.ld-dyn').text(ghiChu ? '(' + ghiChu + ')' : '');
    }

    function moiCu(o) {
        o = o || {};
        return fmt(o.moi) + ' / ' + fmt(o.cu);
    }

    function heNgoai3(o) {
        o = o || {};
        return fmt(o.y_lenh_pt) + '·' + fmt(o.ca_pt) + '·' + fmt(o.can_thiep_tm);
    }

    function veTheSo(r) {
        var a = r.ngay_dieu_tri || {};
        setThe('ngay_dieu_tri', a.gia_tri === null ? '—' : fmt(a.gia_tri, 2) + ' ngày', dongNho(a, 2));

        var b = r.cong_suat_giuong || {};
        setThe('cong_suat_giuong', b.gia_tri === null ? '—' : fmt(b.gia_tri, 1) + '%', dongNho(b, 1, '%'));

        var c = r.tg_kham_phut || {};
        setThe('tg_kham_phut', c.gia_tri === null ? '—' : fmt(c.gia_tri, 1) + ' phút',
            dongNho(c, 1) + ' · ' + fmt(c.so_ho_so) + ' hồ sơ');

        var d = r.ke_don_mua_thuoc_phut || {};
        setThe('ke_don_mua_thuoc_phut', d.gia_tri === null ? '—' : fmt(d.gia_tri, 1) + ' phút',
            dongNho(d, 1) + ' · ' + fmt(d.so_phieu) + ' phiếu');

        var m = r.kham_moi_cu || {};
        setThe('kham_moi_cu', fmt(m.moi) + ' / ' + fmt(m.cu),
            'HN ' + moiCu(m.hn) + ' · NB ' + moiCu(m.nb),
            'mới ' + (m.ty_le_moi === null || m.ty_le_moi === undefined ? '—' : fmt(m.ty_le_moi, 1) + '%'));

        var h = r.he_ngoai || {};
        setThe('he_ngoai', fmt(h.y_lenh_pt) + ' · ' + fmt(h.ca_pt) + ' · ' + fmt(h.can_thiep_tm),
            'HN ' + heNgoai3(h.hn) + ' · NB ' + heNgoai3(h.nb));
    }

    function xoaThe() {
        $('.ld-card').each(function () {
            $(this).find('.ld-val').text('—');
            $(this).find('.ld-sub').text(' ');
            $(this).find('.ld-note.ld-dyn').text('');
        });
    }

    function napTheSo() {
        $('#ld-error-the-so').hide();
        // Không để số của kỳ trước nằm dưới kỳ mới trong lúc nạp (11–30 s khi cache nguội)
        $('.ld-card').each(function () {
            $(this).find('.ld-val').text('…');
            $(this).find('.ld-sub').text(' ');
            $(this).find('.ld-note.ld-dyn').text('');
        });
        goi('the-so')
            .done(veTheSo)
            .fail(function (xhr) {
                xoaThe();
                $('#ld-error-the-so').text('Thẻ số: ' + thongBaoLoi(xhr)).show();
            });
    }

    // ── Biểu đồ ──────────────────────────────────────────────────────────────

    /** Cột ngang (nhiều khoa) hoặc cột dọc (ít mục), cao theo số mục. */
    function veCot(id, opt) {
        var cats = opt.categories || [];
        var ngang = opt.ngang !== false;
        var $el = $('#' + id);
        if (bieuDo[id]) { bieuDo[id].destroy(); delete bieuDo[id]; }
        if (!cats.length) {
            $el.html('<div class="ld-loading">Không có dữ liệu trong kỳ</div>');
            return;
        }
        $el.empty();
        if (ngang) { $el.css('height', Math.max(320, cats.length * 24 + 90) + 'px'); }

        bieuDo[id] = Highcharts.chart(id, {
            chart: { type: ngang ? 'bar' : 'column' },
            title: { text: null },
            credits: { enabled: false },
            legend: { enabled: !!opt.legend },
            xAxis: { categories: cats, title: { text: opt.titleX || null }, labels: { style: { fontSize: '11px' } } },
            yAxis: [{
                title: { text: opt.titleY || null },
                min: 0,
                max: opt.maxY,
                softMax: opt.softMaxY,
                plotLines: opt.plotLines || [],
                labels: { format: opt.nhanY || '{value}' }
            }].concat(opt.yAxis2 ? [opt.yAxis2] : []),
            tooltip: { useHTML: true, shared: !!opt.shared, formatter: opt.tooltip },
            plotOptions: {
                series: { borderWidth: 0 },
                column: { stacking: opt.stacking }
            },
            series: opt.series
        });
    }

    function tenDiem(pt) {
        return '<b>' + esc(pt.key !== undefined ? pt.key : pt.x) + '</b>';
    }

    function veThanhToan(r) {
        function ve(id, ch, tenGd, mauCot) {
            var data = (ch.ty_le || []).map(function (v, i) {
                return { y: v, soGd: ch.so_gd[i], tongGd: ch.tong_gd[i], tien: ch.tien[i], tongTien: ch.tong_tien[i] };
            });
            veCot(id, {
                categories: ch.categories,
                maxY: 100,
                nhanY: '{value}%',
                tooltip: function () {
                    var p = this.point;
                    return tenDiem(this) + '<br/>Tỷ lệ: <b>' + fmt(p.y, 1) + '%</b>' +
                        '<br/>' + tenGd + ': ' + fmt(p.soGd) + ' / ' + fmt(p.tongGd) + ' giao dịch' +
                        '<br/>Số tiền: ' + fmt(p.tien) + ' / ' + fmt(p.tongTien) + ' đ';
                },
                series: [{ name: 'Tỷ lệ', data: data, color: mauCot }]
            });
        }
        ve('ld-chart-qr', r.qr || {}, 'GD QR', COLOR);
        ve('ld-chart-hoan', r.hoan_online || {}, 'Hoàn ứng online', '#00a65a');
    }

    function veDieuTri(r) {
        var nd = r.ngay_dieu_tri || {};
        veCot('ld-chart-ngay-dt', {
            categories: nd.categories,
            titleY: 'Ngày',
            tooltip: function () {
                var i = this.point.index;
                return tenDiem(this) + '<br/>Ngày điều trị TB: <b>' + fmt(this.y, 2) + '</b>' +
                    '<br/>Số hồ sơ: ' + fmt(nd.so_ho_so[i]);
            },
            series: [{ name: 'Ngày điều trị TB', data: nd.gia_tri || [], color: COLOR }]
        });

        var cs = r.cong_suat || {};
        var data = (cs.gia_tri || []).map(function (v) {
            return { y: v, color: v !== null && v > 100 ? COLOR_WARN : COLOR };
        });
        veCot('ld-chart-cong-suat', {
            categories: cs.categories,
            titleY: 'Công suất (%)',
            nhanY: '{value}%',
            softMaxY: 100,
            plotLines: [{ value: 100, color: COLOR_WARN, width: 2, dashStyle: 'Dash', zIndex: 5,
                label: { text: '100%', style: { color: COLOR_WARN } } }],
            tooltip: function () {
                var i = this.point.index;
                return tenDiem(this) + '<br/>Công suất: <b>' + fmt(this.y, 1) + '%</b>' +
                    '<br/>Ngày-giường: ' + fmt(cs.ngay_giuong[i], 1) +
                    '<br/>Giường phê duyệt: ' + fmt(cs.giuong_pd[i]);
            },
            series: [{ name: 'Công suất', data: data, color: COLOR }]
        });

        var $tb = $('#ld-tbl-giuong tbody').empty();
        var bang = r.giuong_hien_tai || [];
        if (!bang.length) {
            $tb.append('<tr><td colspan="5" class="text-center text-muted">Không có dữ liệu</td></tr>');
        }
        bang.forEach(function (d) {
            var am = d.con_trong < 0;
            $tb.append(
                '<tr><td>' + esc(d.don_vi) + '</td>' +
                '<td class="text-right">' + fmt(d.giuong_pd) + '</td>' +
                '<td class="text-right">' + fmt(d.dang_nam) + '</td>' +
                '<td class="text-right' + (am ? ' ld-am' : '') + '">' + fmt(d.con_trong) + '</td>' +
                '<td class="text-right">' + fmt(d.cong_suat, 1) + '%</td></tr>'
            );
        });
    }

    function veKhamThuoc(r) {
        var tg = r.tg_kham_theo_gio || { gio: [], trung_vi: [], so_ho_so: [] };
        var n = tg.gio.length;
        // Ruling R10: cột đầu gộp mọi giờ <= giờ đầu, cột cuối gộp mọi giờ >= giờ cuối
        var nhan = tg.gio.map(function (g, i) {
            if (i === 0) { return '≤' + g + 'h'; }
            if (i === n - 1) { return '≥' + g + 'h'; }
            return g + 'h';
        });
        veCot('ld-chart-tg-kham', {
            ngang: false,
            categories: nhan,
            legend: true,
            titleX: 'Giờ tiếp đón',
            titleY: 'Trung vị (phút)',
            shared: true,
            yAxis2: { title: { text: 'Số hồ sơ' }, opposite: true, min: 0 },
            tooltip: function () {
                var s = '<b>' + esc(this.x) + '</b>';
                this.points.forEach(function (p) {
                    s += '<br/>' + esc(p.series.name) + ': <b>' + (p.y === null ? '—' : fmt(p.y, p.series.name === 'Số hồ sơ' ? 0 : 1)) + '</b>';
                });
                return s;
            },
            series: [
                { name: 'Trung vị (phút)', type: 'column', data: tg.trung_vi, color: COLOR },
                { name: 'Số hồ sơ', type: 'line', yAxis: 1, data: tg.so_ho_so, color: COLOR_B }
            ]
        });

        var kd = r.ke_don_mua_thuoc || { nhom: [], so_phieu: [] };
        var tong = kd.so_phieu.reduce(function (a, b) { return a + b; }, 0);
        veCot('ld-chart-ke-don', {
            ngang: false,
            categories: kd.nhom.map(function (x) { return x + ' phút'; }),
            titleY: 'Số phiếu',
            tooltip: function () {
                return '<b>' + esc(this.x) + '</b><br/>Số phiếu: <b>' + fmt(this.y) + '</b>' +
                    (tong ? ' (' + fmt(this.y * 100 / tong, 1) + '%)' : '');
            },
            series: [{ name: 'Số phiếu', data: kd.so_phieu, color: COLOR }]
        });

        var mc = r.moi_cu || { co_so: [], moi: [], cu: [] };
        veCot('ld-chart-moi-cu', {
            ngang: false,
            categories: mc.co_so,
            legend: true,
            stacking: 'percent',
            nhanY: '{value}%',
            shared: true,
            tooltip: function () {
                var s = '<b>' + esc(this.x) + '</b>';
                this.points.forEach(function (p) {
                    s += '<br/>' + esc(p.series.name) + ': <b>' + fmt(p.y) + '</b> (' + fmt(p.percentage, 1) + '%)';
                });
                return s;
            },
            series: [
                { name: 'Mới', data: mc.moi, color: COLOR },
                { name: 'Cũ', data: mc.cu, color: '#bbb' }
            ]
        });
    }

    function veHeNgoai(r) {
        var tk = r.theo_khoa || { categories: [], y_lenh: [], da_thuc_hien: [] };
        veCot('ld-chart-pt', {
            categories: tk.categories,
            legend: true,
            shared: true,
            tooltip: function () {
                var s = '<b>' + esc(this.x) + '</b>';
                this.points.forEach(function (p) {
                    s += '<br/>' + esc(p.series.name) + ': <b>' + fmt(p.y) + '</b>';
                });
                return s;
            },
            series: [
                { name: 'Y lệnh PT', data: tk.y_lenh, color: COLOR },
                { name: 'Đã thực hiện', data: tk.da_thuc_hien, color: '#00a65a' }
            ]
        });

        var ct = r.can_thiep || { categories: [], so_ca: [] };
        veCot('ld-chart-can-thiep', {
            categories: ct.categories,
            titleY: 'Số ca',
            tooltip: function () {
                return tenDiem(this) + '<br/>Số ca can thiệp: <b>' + fmt(this.y) + '</b>';
            },
            series: [{ name: 'Số ca', data: ct.so_ca, color: COLOR }]
        });
    }

    var VE = {
        'thanh-toan': veThanhToan,
        'dieu-tri-giuong': veDieuTri,
        'kham-thuoc': veKhamThuoc,
        'he-ngoai': veHeNgoai
    };

    // ── Nạp tab ──────────────────────────────────────────────────────────────

    function napTab(phan) {
        var $msg = $('.ld-msg[data-phan="' + phan + '"]');
        $msg.html('<div class="ld-loading"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>');
        daNap[phan] = true;
        goi(phan)
            .done(function (res) {
                $msg.empty();
                VE[phan](res);
            })
            .fail(function (xhr) {
                daNap[phan] = false;
                // Bỏ biểu đồ cũ để không hiện số của bộ lọc trước cạnh thông báo lỗi
                $('#ld-tab-' + phan + ' .chart-box').each(function () {
                    if (bieuDo[this.id]) { bieuDo[this.id].destroy(); delete bieuDo[this.id]; }
                    $(this).empty();
                });
                $('#ld-tab-' + phan + ' tbody').empty();
                $msg.html('<div class="alert alert-danger">' + esc(thongBaoLoi(xhr)) + '</div>');
            });
    }

    function napTatCa() {
        chupBoLoc();
        daNap = {};
        napTheSo();
        napTab(tabDangMo);
    }

    $(function () {
        $('#ld-btn-load').on('click', napTatCa);

        $('#ld-tabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
            tabDangMo = $(this).data('phan');
            if (!daNap[tabDangMo]) { napTab(tabDangMo); }
            // Biểu đồ dựng trong tab ẩn có thể sai kích thước
            $(win).trigger('resize');
        });

        napTatCa();
    });
})(window, jQuery);
