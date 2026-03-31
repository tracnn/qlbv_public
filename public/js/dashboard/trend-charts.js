(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var R = (win.TREND_CFG || {}).routes || {};

    function getParams() {
        return {
            from:   $('#from-date').val(),
            to:     $('#to-date').val(),
            mode:   $('input[name="mode"]:checked').val() || 'daily',
            metric: $('#metric').val() || 'examinations'
        };
    }

    // ── Overload Alert ───────────────────────────────────────────────────────

    function loadOverloadAlert() {
        var today = new Date().toISOString().split('T')[0];
        $.ajax({ url: R.overloadAlert, data: { date: today }, dataType: 'json' })
            .done(function (res) {
                var cls = 'alert-' + res.status;
                var labels = {
                    'overload':  '⚠ Quá tải: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')',
                    'underload': '↓ Dưới công suất: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')',
                    'normal':    '✓ Bình thường: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')'
                };
                $('#overload-alert-box').html(
                    '<div class="alert-card ' + cls + '">' + (labels[res.status] || '') + '</div>'
                );
            });
    }

    // ── Trend Chart ──────────────────────────────────────────────────────────

    function loadTrendChart() {
        var p = getParams();
        $.ajax({ url: R.trendChart, data: p, dataType: 'json' })
            .done(function (res) {
                var labels   = res.labels || [];
                var current  = res.current || [];
                var previous = res.previous || [];

                var metricLabel = p.metric === 'revenue' ? 'Doanh thu (đ)' : 'Lượt khám';

                Highcharts.chart('chart-trend', {
                    chart: { type: 'line' },
                    title: { text: 'Xu hướng ' + metricLabel },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: metricLabel }, allowDecimals: false },
                    series: [
                        { name: 'Kỳ hiện tại', data: current, dashStyle: 'Solid' },
                        { name: 'Kỳ trước',    data: previous, dashStyle: 'Dash', color: '#aaa' }
                    ],
                    credits: { enabled: false }
                });
            });
    }

    // ── BN/giờ ───────────────────────────────────────────────────────────────

    function loadPatientsPerHour() {
        var p = getParams();
        $.ajax({ url: R.patientsPerHour, data: { from: p.from, to: p.to }, dataType: 'json' })
            .done(function (res) {
                $('#kpi-avg-per-hour').text(res.average_per_hour + ' BN/giờ');

                var hours  = (res.by_hour || []).map(function (r) { return r.hour + 'h'; });
                var counts = (res.by_hour || []).map(function (r) { return r.count; });

                Highcharts.chart('chart-by-hour', {
                    chart: { type: 'column' },
                    title: { text: 'BN theo khung giờ' },
                    xAxis: { categories: hours, title: { text: 'Giờ' } },
                    yAxis: { title: { text: 'Số BN' }, allowDecimals: false },
                    legend: { enabled: false },
                    series: [{ name: 'Số BN', data: counts }],
                    credits: { enabled: false }
                });
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadTrendChart();
        loadPatientsPerHour();
    }

    $(document).ready(function () {
        loadOverloadAlert();
        loadAll();
        $('#btn-load').on('click', loadAll);
        $('input[name="mode"]').on('change', loadAll);
        $('#metric').on('change', loadAll);
    });

})(window, jQuery);
