(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var CFG = win.XML3176_CFG || {};
    var R = CFG.routes || {};

    function getParams() {
        return {
            date_type: $('#date-type').val(),
            date_from: $('#from-date').val(),
            date_to: $('#to-date').val()
        };
    }

    function showError(containerId, msg) {
        $('#' + containerId).html('<div class="text-center text-danger" style="padding:40px">' + (msg || 'Không thể tải dữ liệu') + '</div>');
    }

    function showEmpty(containerId) {
        $('#' + containerId).html('<div class="text-center text-muted" style="padding:40px">Không có dữ liệu</div>');
    }

    function formatMoney(v) {
        return (v || 0).toLocaleString('vi-VN');
    }

    // ── Drill-down ────────────────────────────────────────────────────────────

    // Mở màn hình danh sách XML3176 với bộ lọc áp sẵn.
    // Luôn kèm date_type/date_from/date_to để số click khớp danh sách.
    function openList(extraParams) {
        var p = getParams();
        var qs = $.param($.extend({}, p, extraParams || {}));
        win.open(R.listScreen + '?' + qs, '_blank');
    }

    var KPI_DRILLDOWN = {
        total:     {},
        critical:  { xml_filter_status: 'has_error_critical' },
        hein_card: { xml_filter_status: 'has_error_hein_card' },
        blocked:   { xml_filter_status: 'has_error_critical', xml_submit_status: 'not_submit' },
        submitted: { xml_submit_status: 'has_submit' }
    };

    var FUNNEL_DRILLDOWN = {
        imported:    {},
        no_critical: { xml_filter_status: 'no_error_critical' },
        exported:    { xml_export_status: 'has_export' },
        signed:      { xml_sign_status: 'has_sign' },
        submitted:   { xml_submit_status: 'has_submit' }
    };

    // ── Overview: KPI + phễu ──────────────────────────────────────────────────

    function loadOverview() {
        $.ajax({ url: R.overview, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-funnel', 'Không thể tải dữ liệu tổng quan'); })
            .done(function (res) {
                var k = res.kpi || {};
                $('#kpi-total').text((k.total || 0).toLocaleString('vi-VN'));
                $('#kpi-critical').text((k.critical || 0).toLocaleString('vi-VN') + ' (' + (k.critical_pct || 0) + '%)');
                $('#kpi-hein-card').text((k.hein_card || 0).toLocaleString('vi-VN') + ' (' + (k.hein_card_pct || 0) + '%)');
                $('#kpi-blocked').text(formatMoney(k.blocked_amount));
                $('#kpi-submitted').text((k.submitted || 0).toLocaleString('vi-VN') + ' (' + (k.submitted_pct || 0) + '%)');

                var steps = res.funnel || [];
                if (!steps.length) { showEmpty('chart-funnel'); return; }

                Highcharts.chart('chart-funnel', {
                    chart: { type: 'bar' },
                    title: { text: null },
                    xAxis: { categories: steps.map(function (s) { return s.label; }) },
                    yAxis: { title: { text: 'Số hồ sơ' }, allowDecimals: false },
                    tooltip: {
                        formatter: function () {
                            var s = steps[this.point.index];
                            return '<b>' + s.label + '</b><br/>Số hồ sơ: ' + s.count +
                                   '<br/>So bậc trước: ' + s.pct_of_prev + '%';
                        }
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return steps[this.point.index].pct_of_prev + '%';
                                }
                            },
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        openList(FUNNEL_DRILLDOWN[steps[this.index].key] || {});
                                    }
                                }
                            }
                        }
                    },
                    series: [{
                        name: 'Số hồ sơ',
                        data: steps.map(function (s) { return s.count; }),
                        color: '#3c8dbc'
                    }],
                    credits: { enabled: false }
                });
            });
    }

    // ── Pareto ────────────────────────────────────────────────────────────────

    function loadTopErrors() {
        $.ajax({ url: R.topErrors, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-pareto', 'Không thể tải danh sách lỗi'); })
            .done(function (res) {
                var data = res.data || [];
                if (!data.length) { showEmpty('chart-pareto'); return; }

                Highcharts.chart('chart-pareto', {
                    chart: { zoomType: 'xy' },
                    title: { text: null },
                    xAxis: {
                        categories: data.map(function (d) { return d.error_code; }),
                        labels: { rotation: -45 }
                    },
                    yAxis: [
                        { title: { text: 'Số hồ sơ' }, allowDecimals: false },
                        { title: { text: '% tích luỹ' }, opposite: true, max: 100 }
                    ],
                    tooltip: {
                        formatter: function () {
                            var d = data[this.point.index];
                            return '<b>' + d.error_code + '</b> — ' + (d.error_name || '') +
                                   '<br/>Số hồ sơ: ' + d.total +
                                   '<br/>Tích luỹ: ' + d.cumulative_pct + '%' +
                                   '<br/>' + (d.critical_error ? 'Nghiêm trọng' : 'Cảnh báo');
                        }
                    },
                    plotOptions: {
                        column: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        openList({ xml3176_error_catalog: data[this.index].catalog_id });
                                    }
                                }
                            }
                        }
                    },
                    series: [
                        {
                            type: 'column',
                            name: 'Số hồ sơ',
                            data: data.map(function (d) {
                                return { y: d.total, color: d.critical_error ? '#d9534f' : '#f0ad4e' };
                            })
                        },
                        {
                            type: 'line',
                            name: '% tích luỹ',
                            yAxis: 1,
                            data: data.map(function (d) { return d.cumulative_pct; }),
                            color: '#3c8dbc',
                            marker: { enabled: true }
                        }
                    ],
                    credits: { enabled: false }
                });
            });
    }

    // ── Tồn đọng theo tuổi hồ sơ ──────────────────────────────────────────────
    //
    // ⚠️ D11: endpoint aging KHÔNG nhận và KHÔNG validate date_type/date_from/date_to
    // (xem Xml3176DashboardController@aging, Xml3176DashboardService@getAging).
    // KHÔNG được gửi getParams() ở đây — chỉ gọi endpoint không tham số, nếu không
    // rủi ro nhất là một sửa đổi sau này ở controller bắt đầu validate và trả 422.
    // Panel này CỐ Ý không đổi theo bộ lọc kỳ đã chọn (xem ghi chú trong box-header).

    function loadAging() {
        $.ajax({ url: R.aging, dataType: 'json' })
            .fail(function () { showError('chart-aging', 'Không thể tải dữ liệu tồn đọng'); })
            .done(function (res) {
                var data = res.data || [];
                if (!data.length) { showEmpty('chart-aging'); return; }

                Highcharts.chart('chart-aging', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories: data.map(function (d) { return d.label; }) },
                    yAxis: { title: { text: 'Số hồ sơ chưa gửi' }, allowDecimals: false },
                    plotOptions: {
                        column: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        var b = data[this.index];
                                        // Nhóm tuổi định nghĩa theo ngay_ra, không phụ thuộc kỳ đã chọn (D11)
                                        // → KHÔNG dùng openList() (nó tự kèm getParams()), build URL riêng.
                                        var qs = $.param({
                                            date_type: 'date_out',
                                            date_from: ymdhiToDateInput(b.from),
                                            date_to: ymdhiToDateInput(b.to),
                                            xml_submit_status: 'not_submit'
                                        });
                                        win.open(R.listScreen + '?' + qs, '_blank');
                                    }
                                }
                            }
                        }
                    },
                    series: [{
                        name: 'Hồ sơ chưa gửi',
                        data: data.map(function (d) { return d.total; }),
                        color: '#f39c12'
                    }],
                    credits: { enabled: false }
                });
            });
    }

    // '202607100000' → '2026-07-10'. Mốc '000000000000' (>30 ngày) → '2000-01-01'.
    //
    // ⚠️ HÀM NÀY LÀ LOAD-BEARING, KHÔNG ĐƯỢC BỎ. Nhóm ">30 ngày" có from='000000000000'
    // (sentinel "vô cực về quá khứ"). Nếu gửi thẳng chuỗi đó sang màn hình danh sách,
    // BHYTXml3176Controller sẽ chạy Carbon::createFromFormat('Y-m-d H:i:s', '000000000000')
    // và NÉM EXCEPTION (chuỗi 12 ký tự không lọt nhánh strlen==10). Phải map sang ngày thật.
    function ymdhiToDateInput(v) {
        var s = String(v || '');
        if (s.length < 8 || s.substr(0, 4) === '0000') {
            return '2000-01-01';
        }
        return s.substr(0, 4) + '-' + s.substr(4, 2) + '-' + s.substr(6, 2);
    }

    // ── Lỗi theo khoa (không drill-down) ──────────────────────────────────────

    function loadByDepartment() {
        $.ajax({ url: R.byDepartment, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-department', 'Không thể tải dữ liệu theo khoa'); })
            .done(function (res) {
                var data = (res.data || []).slice(0, 15);
                if (!data.length) { showEmpty('chart-department'); return; }

                Highcharts.chart('chart-department', {
                    chart: { type: 'bar' },
                    title: { text: null },
                    xAxis: { categories: data.map(function (d) { return d.ten_khoa; }) },
                    yAxis: { title: { text: 'Số hồ sơ lỗi nghiêm trọng' }, allowDecimals: false },
                    series: [{
                        name: 'Hồ sơ lỗi nghiêm trọng',
                        data: data.map(function (d) { return d.total; }),
                        color: '#00c0ef'
                    }],
                    credits: { enabled: false }
                });
            });
    }

    function loadAll() {
        loadOverview();
        loadTopErrors();
        loadAging();
        loadByDepartment();
    }

    $(document).ready(function () {
        $('#btn-load').on('click', loadAll);

        $('.kpi-link').on('click', function (e) {
            e.preventDefault();
            openList(KPI_DRILLDOWN[$(this).data('kpi')] || {});
        });

        loadAll();
    });

})(window, jQuery);
