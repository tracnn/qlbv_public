(function (win, $) {
    'use strict';
    var CFG = win.DASHBOARD_CFG || {};
    var API = win.DAPI;
    var U = win.DUtils;
  
    function renderTransaction(start, end) {
      if (!CFG.hasFinanceRole) {
        ['chart_transaction_types','chart_pay_forms','chart_cashiers','chart_treatment_types']
          .forEach(function(id){ U.showNoPermissionPie(id, 'Không có quyền'); });
        return $.Deferred().resolve().promise();
      }
      return API.txSummary(start, end).done(function (data) {
        U.renderColumnMoney('chart_transaction_types', 'Loại giao dịch', data.transactionTypes, 'Số tiền');
        U.renderColumnMoney('chart_pay_forms', 'Hình thức thanh toán', data.payForms, 'Số tiền');
        U.renderColumnMoney('chart_cashiers', 'Thu ngân', data.cashiers, 'Số tiền');
        U.renderColumnMoney('chart_treatment_types', 'Diện điều trị', data.treatmentTypes, 'Số tiền');
      });
    }
  
    function renderServicePie(serviceId, elId, title, start, end) {
      return API.serviceByType(serviceId, start, end).done(function (resp) {
        if (!resp || !resp.chartData || !resp.chartData.length) return;
        U.renderPie3D(elId, title + ': ' + numeral(resp.sum_sl).format('0,0'), 'Số lượng', resp.chartData);
      });
    }
  
    function renderAverageInpatient(start, end) {
      return API.averageDayInpatient(start, end).done(function (r) {
        if (r && typeof r.avg_day_count !== 'undefined') $('#average_inpatient').text(r.avg_day_count);
      });
    }
  
    function renderTreatment(start, end) {
      return API.treatment(start, end).done(function (r) {
        if (!r || !r.datasets || !r.datasets.length) return;
        $('#sum_treatment').text(numeral(r.sum_sl).format('0,0'));
        var points = r.labels.map(function (label, i) { return { name: label, y: r.datasets[0].data[i] }; });
        U.renderPie3D('chart_treatment', r.title + ': ' + numeral(r.sum_sl).format('0,0'), 'Hồ sơ', points);
      });
    }
  
    function renderNewPatient(start, end) {
      return API.newPatient(start, end).done(function (r) {
        if (r && r.datasets && r.datasets.length) $('#sum_newpatient').text(numeral(r.sum_sl).format('0,0'));
      });
    }
  
    function renderChuyenVien(start, end) {
      return API.chuyenVien(start, end).done(function (r) {
        if (r && r.datasets && r.datasets.length) $('#sum_chuyenvien').text(numeral(r.sum_sl).format('0,0'));
      });
    }
  
    function renderThuThuatPhauThuat(start, end) {
      var p1 = API.serviceByType(10, start, end).done(function (r) {
        if (r && r.sum_sl > 0) $('#sum_phauthuat').text(numeral(r.sum_sl).format('0,0'));
      });
      var p2 = API.serviceByType(4, start, end).done(function (r) {
        if (r && r.sum_sl > 0) $('#sum_thuthuat').text(numeral(r.sum_sl).format('0,0'));
      });
      return $.when(p1, p2);
    }
  
    function renderOutTreatmentGroupType(start, end) {
      return API.outTreatmentGroupType(start, end).done(function (r) {
        if (!r || !r.total) return;
        $('#sum_ravien').text(numeral(r.total).format('0,0'));
        $('#sum_ravien_noitru').text(numeral(r.noitru).format('0,0'));
        $('#sum_ravien_ngoaitru').text(numeral(r.ngoaitru).format('0,0'));
        $('#sum_ravien_kham').text(numeral(r.kham).format('0,0'));
      });
    }
  
    function renderDoanhThu(start, end) {
      if (!CFG.hasFinanceRole) {
        $('#sum_doanhthu').text('Không có quyền');
        U.showNoPermissionPie('chart_doanhthu', 'Doanh thu');
        return $.Deferred().resolve().promise();
      }
      return API.doanhThu(start, end).done(function (r) {
        if (!r || !r.datasets || !r.datasets.length) return;
        var roundedTr = Math.round((r.sum_sl || 0) / 1e6);
        $('#sum_doanhthu').text(numeral(roundedTr).format('0,0') + ' Tr');
        var points = r.labels.map(function (label, i) { return { name: label, y: r.datasets[0].data[i] }; });
        U.renderPie3D('chart_doanhthu', r.title + ': ' + numeral(roundedTr).format('0,0') + ' Tr', 'Doanh thu', points);
      });
    }
  
    function renderBuongBenh(start, end) {
      return API.buongBenh(start, end).done(function (r) {
        $.each(r, function (k, data) {
          U.renderSimpleCategoryColumn('chart_buongbenh', {
            type: data.type,
            title: data.title,
            categories: data.labels,
            xTitle: 'Khoa điều trị',
            yTitle: 'Số lượng bệnh nhân',
            seriesName: 'Số lượng',
            data: data.datasets[0].data,
            pointFormat: '{series.name}: <b>{point.y}</b>'
          });
        });
      });
    }
  
    function renderKhamByRoom(start, end) {
      return API.khamByRoom(start, end).done(function (r) {
        var items = r.chartData || [];
        var sum = r.sum_sl || 0;
        var categories = items.map(function (x) { return x.room; });
        var statuses = ['Chưa thực hiện', 'Đang thực hiện', 'Đã thực hiện'];
        var colors = { 'Chưa thực hiện': '#f45b5b', 'Đang thực hiện': '#f7a35c', 'Đã thực hiện': '#90ed7d' };
        var series = statuses.map(function (s) { return { name: s, data: items.map(function (it) { return it[s] || 0; }), color: colors[s] }; });
  
        U.renderStackedColumns('chart_kham_by_room', {
          title:
            '<div style="text-align:center"><div><b>Tổng số lượt khám:</b> ' +
            U.numberFormatVN(sum) + '</div><div><b>Tổng số phòng thực hiện:</b> ' + items.length + '</div></div>',
          categories: categories, xTitle: 'Phòng thực hiện', yTitle: 'Số lượng bệnh nhân', series: series
        });
      });
    }
  
    function renderNoiTru(start, end) {
      return API.noiTru(start, end).done(function (r) {
        var dataObj = Array.isArray(r) ? r[0] : r;
        $('#sum_noitru').text(numeral(dataObj.sum_sl).format('0,0'));
        U.renderSimpleCategoryColumn('chart_noitru', {
          type: dataObj.type, title: dataObj.title, categories: dataObj.labels,
          xTitle: 'Khoa điều trị', yTitle: 'Số lượng bệnh nhân', data: dataObj.datasets[0].data
        });
      });
    }
  
    function renderNgoaiTruBlocks(start, end) {
      if (!CFG.isBieuDoDieuTriNgoaiTru) return $.Deferred().resolve().promise();
      var p1 = API.dieuTriNgoaiTru(start, end).done(function (r) {
        var d = Array.isArray(r) ? r[0] : r;
        U.renderSimpleCategoryColumn('chart_vaovien_dieutringoaitru', {
          type: d.type, title: d.title, categories: d.labels,
          xTitle: 'Khoa điều trị', yTitle: 'Số lượng bệnh nhân', data: d.datasets[0].data
        });
      });
      var p2 = API.patientInRoomNgoaiTru(start, end).done(function (r) {
        var d = Array.isArray(r) ? r[0] : r;
        U.renderSimpleCategoryColumn('chart_buongbenh_dieutringoaitru', {
          type: d.type, title: d.title, categories: d.labels,
          xTitle: 'Khoa điều trị', yTitle: 'Số lượng bệnh nhân', data: d.datasets[0].data
        });
      });
      return $.when(p1, p2);
    }
  
    function renderExamParaclinical(start, end) {
      return API.examParaclinical(start, end).done(function (d) {
        Highcharts.chart('chart_exam_paraclinical_time', {
          chart: { type: 'column' },
          title: { text: 'Trung bình Thời gian chờ & Thực hiện theo Loại dịch vụ', style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: { categories: d.categories, title: { text: 'Loại dịch vụ', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          yAxis: { min: 0, title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          tooltip: { shared: true, valueSuffix: ' phút', style: { fontSize: '13px' } },
          plotOptions: { column: { pointPadding: 0.1, groupPadding: 0.2, borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 'bold' } } } },
          series: d.series
        });
      });
    }
  
    function renderDiagImaging(start, end) {
      return API.diagImaging(start, end).done(function (d) {
        Highcharts.chart('chart_diagnotic_imaging_time', {
          chart: { type: 'column' },
          title: { text: 'Trung bình Thời gian chờ & Thực hiện CĐHA', style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: { categories: d.categories, title: { text: 'Loại dịch vụ', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          yAxis: { min: 0, title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          tooltip: { shared: true, valueSuffix: ' phút', style: { fontSize: '13px' } },
          plotOptions: { column: { pointPadding: 0.1, groupPadding: 0.2, borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 'bold' } } } },
          series: d.series
        });
      });
    }
  
    function renderPrescription(start, end) {
      return API.prescription(start, end).done(function (d) {
        Highcharts.chart('chart_prescription_time', {
          chart: { type: 'column' },
          title: { text: 'Trung bình chờ (kết thúc -> khóa VP) & lĩnh thuốc (khóa VP -> thực xuất)', style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: { categories: d.categories, title: { text: 'Đơn thuốc phòng khám', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          yAxis: { min: 0, title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          tooltip: { shared: true, valueSuffix: ' phút', style: { fontSize: '13px' } },
          plotOptions: { column: { pointPadding: 0.1, groupPadding: 0.2, borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 'bold' } } } },
          series: d.series
        });
      });
    }
  
    function renderFee(start, end) {
      return API.fee(start, end).done(function (d) {
        Highcharts.chart('chart_fee_time', {
          chart: { type: 'column' },
          title: { text: 'Trung bình Thời gian chờ thanh toán viện phí (Khám)', style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: { categories: d.categories, title: { text: 'Thanh toán viện phí (Khám)', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          yAxis: { min: 0, title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } }, labels: { style: { fontSize: '13px' } } },
          tooltip: { shared: true, valueSuffix: ' phút', style: { fontSize: '13px' } },
          plotOptions: { column: { pointPadding: 0.1, groupPadding: 0.2, borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 'bold' } } } },
          series: d.series
        });
      });
    }
  
    // Public API
    var DCH = {
      renderAll: function (start, end) {
        if (!start || !end) {
          var now = moment();
          start = now.clone().startOf('day').format('YYYY-MM-DD HH:mm:ss');
          end = now.clone().endOf('day').format('YYYY-MM-DD HH:mm:ss');
        }
  
        var pieConfigs = [
          { id: 5, el: 'chart_tdcn', title: 'TDCN' },
          { id: 3, el: 'chart_cdha', title: 'CĐHA' },
          { id: 1, el: 'chart_kham', title: 'Khám' },
          { id: 2, el: 'chart_xetnghiem', title: 'Xét nghiệm' },
          { id: 8, el: 'chart_ns', title: 'Nội soi' },
          { id: 9, el: 'chart_sa', title: 'Siêu âm' },
          { id: 4, el: 'chart_tt', title: 'Thủ thuật' },
          { id: 10, el: 'chart_pt', title: 'Phẫu thuật' }
        ];
        // chèn GPB theo flag
        if (!CFG.disableGpbChart) pieConfigs.splice(4, 0, { id: 13, el: 'chart_gpb', title: 'GPB' });
  
        // gọi song song
        var pies = pieConfigs.map(function (c) { return renderServicePie(c.id, c.el, c.title, start, end); });
  
        return $.when.apply($, [
          renderAverageInpatient(start, end),
          renderTreatment(start, end),
          renderNewPatient(start, end),
          renderChuyenVien(start, end),
          renderThuThuatPhauThuat(start, end),
          renderOutTreatmentGroupType(start, end),
          renderDoanhThu(start, end),
          renderBuongBenh(start, end),
          renderNoiTru(start, end),
          renderKhamByRoom(start, end),
          renderExamParaclinical(start, end),
          renderDiagImaging(start, end),
          renderPrescription(start, end),
          renderFee(start, end),
          renderTransaction(start, end),
          CFG.isBieuDoDieuTriNgoaiTru ? renderNgoaiTruBlocks(start, end) : $.Deferred().resolve()
        ].concat(pies));
      }
    };
  
    win.DCharts = DCH;
  })(window, jQuery);