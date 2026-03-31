(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var R = (win.OR_CFG || {}).routes || {};

    function getParams() {
        return { from: $('#from-date').val(), to: $('#to-date').val() };
    }

    // ── Heatmap ──────────────────────────────────────────────────────────────

    function showError(containerId, msg) {
        $('#' + containerId).html('<div class="text-center text-danger" style="padding:40px">' + (msg || 'Không thể tải dữ liệu') + '</div>');
    }

    function loadCasesPerRoom() {
        $.ajax({ url: R.casesPerRoom, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-heatmap', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var series = (res.rooms || []).map(function (room, idx) {
                    return { name: room, data: (res.matrix || [])[idx] || [] };
                });

                Highcharts.chart('chart-heatmap', {
                    chart: { type: 'column' },
                    title: { text: 'Ca PT theo phòng / ngày' },
                    xAxis: { categories: res.dates || [], crosshair: true },
                    yAxis: { title: { text: 'Số ca' }, allowDecimals: false },
                    plotOptions: { column: { grouping: true, shadow: false } },
                    series: series,
                    credits: { enabled: false }
                });
            });
    }

    // ── Utilization Bar Chart ─────────────────────────────────────────────────

    function loadUtilization() {
        $.ajax({ url: R.utilization, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-utilization', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var data = res.data || [];
                var rooms  = data.map(function (r) { return r.room_name; });
                var values = data.map(function (r) { return r.utilization_pct; });
                var colors = data.map(function (r) {
                    if (r.status === 'overload')  return '#d9534f';
                    if (r.status === 'optimal')   return '#5cb85c';
                    return '#f0ad4e';
                });

                Highcharts.chart('chart-utilization', {
                    chart: { type: 'bar' },
                    title: { text: '% Công suất sử dụng phòng mổ' },
                    xAxis: { categories: rooms },
                    yAxis: {
                        title: { text: '% Sử dụng' },
                        max: 150,
                        plotLines: [{
                            value: 100, color: '#d9534f', width: 2,
                            label: { text: '100% (tối đa)', align: 'right' }
                        }, {
                            value: 70, color: '#f0ad4e', width: 1, dashStyle: 'Dash',
                            label: { text: '70% (ngưỡng tối ưu)', align: 'right' }
                        }]
                    },
                    legend: { enabled: false },
                    series: [{
                        name: '% Công suất',
                        data: values.map(function (v, i) { return { y: v, color: colors[i] }; })
                    }],
                    credits: { enabled: false }
                });

                var statusLabel = {
                    overload:  '<span class="status-overload">Quá tải</span>',
                    optimal:   '<span class="status-optimal">Tối ưu</span>',
                    underload: '<span class="status-underload">Chưa khai thác</span>'
                };

                var tbody = '';
                data.forEach(function (r) {
                    tbody += '<tr>'
                        + '<td>' + r.room_name + '</td>'
                        + '<td>' + r.total_cases + '</td>'
                        + '<td>' + r.total_minutes + ' phút</td>'
                        + '<td>' + r.working_days + '</td>'
                        + '<td>' + r.utilization_pct + '%</td>'
                        + '<td>' + (statusLabel[r.status] || r.status) + '</td>'
                        + '</tr>';
                });
                $('#tbl-utilization tbody').html(tbody || '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>');
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadCasesPerRoom();
        loadUtilization();
    }

    $(document).ready(function () {
        $('#btn-load').on('click', loadAll);
        loadAll();
    });

})(window, jQuery);
