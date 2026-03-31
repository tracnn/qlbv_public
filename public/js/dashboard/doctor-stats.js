(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var R = (win.DOCTOR_STATS_CFG || {}).routes || {};
    var dtExam, dtRevenue, dtSurgery;

    var DT_LANG = {
        search:          'Tìm kiếm:',
        lengthMenu:      'Hiển thị _MENU_ dòng',
        info:            'Từ _START_ đến _END_ / _TOTAL_ bác sĩ',
        infoEmpty:       'Không có dữ liệu',
        infoFiltered:    '(lọc từ _MAX_ dòng)',
        zeroRecords:     'Không tìm thấy kết quả',
        emptyTable:      'Chưa có dữ liệu – nhấn Xem để tải',
        paginate: {
            first:    'Đầu',
            last:     'Cuối',
            next:     'Sau',
            previous: 'Trước'
        }
    };

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getParams() {
        return {
            from: $('#from-date').val(),
            to:   $('#to-date').val()
        };
    }

    function formatMoney(num) {
        if (!num) return '0';
        return Number(num).toLocaleString('vi-VN') + ' đ';
    }

    function showError(containerId, msg) {
        $('#' + containerId).html(
            '<div class="text-center text-danger" style="padding:40px">' +
            (msg || 'Không thể tải dữ liệu') + '</div>'
        );
    }

    function showBarChart(containerId, title, categories, data, yTitle) {
        Highcharts.chart(containerId, {
            chart: { type: 'bar' },
            title: { text: title },
            xAxis: { categories: categories, title: { text: null } },
            yAxis: { title: { text: yTitle }, allowDecimals: false },
            legend: { enabled: false },
            series: [{ name: yTitle, data: data, colorByPoint: true }],
            credits: { enabled: false }
        });
    }

    // ── Tab: Lượt khám ───────────────────────────────────────────────────────

    function loadExaminations() {
        $.ajax({ url: R.examinations, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-exam', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows   = (res.data || []).slice(0, 10);
                var names  = rows.map(function (r) { return r.username; });
                var counts = rows.map(function (r) { return r.total_exams; });

                showBarChart('chart-exam', 'Top bác sĩ – Lượt khám', names, counts, 'Lượt khám');

                dtExam.clear();
                (res.data || []).forEach(function (r) {
                    dtExam.row.add([
                        r.username,
                        r.total_exams,
                        r.total_patients
                    ]);
                });
                dtExam.draw();
            });
    }

    // ── Tab: Doanh thu ───────────────────────────────────────────────────────

    function loadRevenue() {
        $.ajax({ url: R.revenue, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-revenue', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows   = (res.data || []).slice(0, 10);
                var names  = rows.map(function (r) { return r.username; });
                var values = rows.map(function (r) { return r.total_revenue; });

                showBarChart('chart-revenue', 'Top bác sĩ – Doanh thu', names, values, 'Doanh thu (đ)');

                dtRevenue.clear();
                (res.data || []).forEach(function (r) {
                    dtRevenue.row.add([
                        r.username,
                        formatMoney(r.total_revenue),
                        r.total_patients
                    ]);
                });
                dtRevenue.draw();
            });
    }

    // ── Tab: Phẫu thuật ──────────────────────────────────────────────────────

    function loadSurgeries() {
        $.ajax({ url: R.surgeries, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-surgery', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows   = (res.data || []).slice(0, 10);
                var names  = rows.map(function (r) { return r.username; });
                var counts = rows.map(function (r) { return r.total_surgeries; });

                showBarChart('chart-surgery', 'Top PTV chính – Số ca mổ', names, counts, 'Số ca');

                dtSurgery.clear();
                (res.data || []).forEach(function (r) {
                    dtSurgery.row.add([
                        r.username,
                        r.total_surgeries
                    ]);
                });
                dtSurgery.draw();
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadExaminations();
        loadRevenue();
        loadSurgeries();
    }

    $(document).ready(function () {

        dtExam = $('#tbl-exam').DataTable({
            language:   DT_LANG,
            order:      [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 15, 25, 50],
            columns: [
                { title: 'Bác sĩ' },
                { title: 'Lượt khám', className: 'text-right' },
                { title: 'BN',        className: 'text-right' }
            ]
        });

        dtRevenue = $('#tbl-revenue').DataTable({
            language:   DT_LANG,
            order:      [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 15, 25, 50],
            columns: [
                { title: 'Bác sĩ' },
                { title: 'Doanh thu', className: 'text-right' },
                { title: 'BN',        className: 'text-right' }
            ]
        });

        dtSurgery = $('#tbl-surgery').DataTable({
            language:   DT_LANG,
            order:      [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 15, 25, 50],
            columns: [
                { title: 'PTV chính' },
                { title: 'Số ca', className: 'text-right' }
            ]
        });

        $('#btn-load').on('click', loadAll);
        loadAll();
    });

})(window, jQuery);
