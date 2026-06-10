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
        U.renderColumnMoney('chart_transaction_types', 'Loại giao dịch', data.transactionTypes, 'Số tiền', data.transactionTotal);
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
  
    // ===== Hồ sơ pie với legend đối tượng tùy biến (mỗi PT = 1 slice) =====
    var HoSoPie = {
      data: null,
      activePtIds: null,
      bound: false,

      drawChart: function () {
        var d = this.data;
        var active = this.activePtIds;
        var palette = Highcharts.getOptions().colors;
        var ptIndex = {};
        (d.patient_types || []).forEach(function (pt, i) { ptIndex[pt.id] = i; });

        var points = (d.patient_types || [])
          .filter(function (pt) { return active[pt.id] && pt.total > 0; })
          .map(function (pt) {
            return { name: pt.name, y: pt.total, color: palette[ptIndex[pt.id] % palette.length] };
          });

        var total = points.reduce(function (a, b) { return a + b.y; }, 0);
        $('#sum_treatment').text(numeral(total).format('0,0'));

        Highcharts.chart('chart_treatment', {
          chart: { type: 'pie', options3d: { enabled: true, alpha: 45, beta: 0 } },
          title: { text: 'Hồ sơ: ' + numeral(total).format('0,0'), style: { fontSize: '18px', fontWeight: 'bold' } },
          tooltip: { pointFormatter: function () { return '<b>' + numeral(this.y).format('0,0') + ' (' + (this.percentage || 0).toFixed(1) + '%)</b>'; }, style: { fontSize: '13px', fontWeight: 'bold' } },
          plotOptions: {
            pie: {
              innerSize: 0, depth: 45,
              dataLabels: { enabled: true, format: '{point.name}: {point.percentage:.1f}%', style: { fontSize: '12px' } }
            }
          },
          series: [{ name: 'Hồ sơ', data: points }]
        });
      },

      buildLegend: function () {
        var d = this.data;
        var active = this.activePtIds;
        var palette = Highcharts.getOptions().colors;
        var html = (d.patient_types || []).map(function (pt, idx) {
          var color = palette[idx % palette.length];
          var isOn = !!active[pt.id];
          return '<span class="treatment-pt-legend-item" data-pt="' + pt.id + '" style="' +
                 'display:inline-block; margin: 0 8px; cursor:pointer; ' +
                 'opacity:' + (isOn ? '1' : '0.35') + '; user-select:none;">' +
                   '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + color + ';margin-right:4px;vertical-align:middle;"></span>' +
                   '<b>' + pt.name + '</b> <span style="color:#888;font-weight:normal">— ' + numeral(pt.total).format('0,0') + '</span>' +
                 '</span>';
        }).join('');
        $('#legend_treatment').html(html + ' <span style="color:#888">(click để ẩn/hiện)</span>');
      },

      bindEvents: function () {
        if (this.bound) return;
        var self = this;
        $(document).on('click', '#legend_treatment .treatment-pt-legend-item', function () {
          var ptId = $(this).data('pt');
          var activeCount = Object.keys(self.activePtIds).filter(function (k) { return self.activePtIds[k]; }).length;
          if (self.activePtIds[ptId] && activeCount <= 1) return;
          self.activePtIds[ptId] = !self.activePtIds[ptId];
          self.buildLegend();
          self.drawChart();
        });
        this.bound = true;
      }
    };

    function renderTreatment(start, end) {
      return API.treatment(start, end).done(function (r) {
        if (!r || !r.patient_types || !r.patient_types.length) {
          $('#sum_treatment').text('0');
          $('#legend_treatment').empty();
          $('#chart_treatment').html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
          return;
        }
        HoSoPie.data = r;
        var prev = HoSoPie.activePtIds || {};
        var next = {};
        r.patient_types.forEach(function (pt) {
          next[pt.id] = (typeof prev[pt.id] === 'boolean') ? prev[pt.id] : true;
        });
        if (!Object.keys(next).some(function (k) { return next[k]; })) {
          Object.keys(next).forEach(function (k) { next[k] = true; });
        }
        HoSoPie.activePtIds = next;
        HoSoPie.buildLegend();
        HoSoPie.bindEvents();
        HoSoPie.drawChart();
      });
    }
  
    function renderNewPatient(start, end) {
      return API.newPatient(start, end).done(function (r) {
        if (r && r.datasets && r.datasets.length) $('#sum_newpatient').text(numeral(r.sum_sl).format('0,0'));
      });
    }
  
    function renderReExamination(start, end) {
      return API.reExamination(start, end).done(function (r) {
        if (r && r.datasets && r.datasets.length) $('#sum_reexamination').text(numeral(r.sum_sl).format('0,0'));
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
  
    // ===== Doanh thu pie với legend đối tượng tùy biến =====
    var DoanhThuPie = {
      data: null,
      activePtIds: null,
      bound: false,

      sumByService: function () {
        var d = this.data;
        var n = d.categories.length;
        var totals = new Array(n).fill(0);
        var active = this.activePtIds;
        Object.keys(d.by_patient_type).forEach(function (ptId) {
          if (!active[ptId]) return;
          var arr = d.by_patient_type[ptId];
          for (var i = 0; i < n; i++) totals[i] += (arr[i] || 0);
        });
        return totals;
      },

      drawChart: function () {
        var d = this.data;
        var totals = this.sumByService();
        var points = d.categories.map(function (name, i) { return { name: name, y: totals[i] }; })
                                 .filter(function (p) { return p.y > 0; });
        var totalDt = totals.reduce(function (a, b) { return a + b; }, 0);
        var roundedTr = Math.round(totalDt / 1e6);
        $('#sum_doanhthu').text(numeral(roundedTr).format('0,0') + ' Tr');

        Highcharts.chart('chart_doanhthu', {
          chart: { type: 'pie', options3d: { enabled: true, alpha: 45, beta: 0 } },
          title: { text: 'Doanh thu: ' + numeral(roundedTr).format('0,0') + ' Tr', style: { fontSize: '18px', fontWeight: 'bold' } },
          tooltip: { pointFormatter: function () { return '<b>' + numeral(this.y).format('0,0') + ' (' + (this.percentage || 0).toFixed(1) + '%)</b>'; }, style: { fontSize: '13px', fontWeight: 'bold' } },
          plotOptions: {
            pie: {
              innerSize: 0,
              depth: 45,
              dataLabels: { enabled: true, format: '{point.name}: {point.percentage:.1f}%', style: { fontSize: '12px' } }
            }
          },
          series: [{ name: 'Doanh thu', data: points }]
        });
      },

      buildLegend: function () {
        var d = this.data;
        var active = this.activePtIds;
        var palette = Highcharts.getOptions().colors;
        var html = (d.patient_types || []).map(function (pt, idx) {
          var color = palette[idx % palette.length];
          var isOn = !!active[pt.id];
          var tr = Math.round((pt.total || 0) / 1e6);
          return '<span class="pt-legend-item" data-pt="' + pt.id + '" style="' +
                 'display:inline-block; margin: 0 8px; cursor:pointer; ' +
                 'opacity:' + (isOn ? '1' : '0.35') + '; user-select:none;">' +
                   '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + color + ';margin-right:4px;vertical-align:middle;"></span>' +
                   '<b>' + pt.name + '</b> <span style="color:#888;font-weight:normal">— ' + numeral(tr).format('0,0') + ' Tr</span>' +
                 '</span>';
        }).join('');
        $('#legend_doanhthu').html(
          html + ' <span style="color:#888">(click để ẩn/hiện)</span>'
        );
      },

      bindEvents: function () {
        if (this.bound) return;
        var self = this;
        $(document).on('click', '#legend_doanhthu .pt-legend-item', function () {
          var ptId = $(this).data('pt');
          // Không cho tắt hết (giữ ít nhất 1)
          var activeCount = Object.keys(self.activePtIds).filter(function (k) { return self.activePtIds[k]; }).length;
          if (self.activePtIds[ptId] && activeCount <= 1) return;
          self.activePtIds[ptId] = !self.activePtIds[ptId];
          self.buildLegend();
          self.drawChart();
        });
        this.bound = true;
      }
    };

    function renderDoanhThu(start, end) {
      if (!CFG.hasFinanceRole) {
        $('#sum_doanhthu').text('Không có quyền');
        U.showNoPermissionPie('chart_doanhthu', 'Doanh thu');
        return $.Deferred().resolve().promise();
      }
      return API.doanhThu(start, end).done(function (r) {
        if (!r || !r.categories || !r.categories.length) {
          $('#sum_doanhthu').text('0 Tr');
          $('#legend_doanhthu').empty();
          $('#chart_doanhthu').html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
          return;
        }
        DoanhThuPie.data = r;
        // Khởi tạo trạng thái active: mặc định bật tất cả (giữ lựa chọn cũ nếu có)
        var prev = DoanhThuPie.activePtIds || {};
        var next = {};
        (r.patient_types || []).forEach(function (pt) {
          next[pt.id] = (typeof prev[pt.id] === 'boolean') ? prev[pt.id] : true;
        });
        // Nếu sau merge không còn cái nào bật thì bật lại tất cả
        var anyOn = Object.keys(next).some(function (k) { return next[k]; });
        if (!anyOn) { Object.keys(next).forEach(function (k) { next[k] = true; }); }
        DoanhThuPie.activePtIds = next;

        DoanhThuPie.buildLegend();
        DoanhThuPie.bindEvents();
        DoanhThuPie.drawChart();
      });
    }
  
    // ===== Doanh thu Overview =====
    // Mỗi patient_type = 1 cặp series cùng màu, share legend (linkedTo):
    //   - column stacked trên Y trái (Số lượng)
    //   - spline trên Y phải (Doanh thu, dashed)
    // Click legend -> ẩn/hiện cả cặp. Subtitle update tổng SL + DT theo series đang hiển thị.
    function sumArr(arr) {
      return (arr || []).reduce(function (a, b) { return a + (Number(b) || 0); }, 0);
    }

    function buildOverviewSubtitle(chart) {
      var totalSl = 0, totalDt = 0;
      chart.series.forEach(function (s) {
        if (!s.visible) return;
        var sum = sumArr(s.options.data);
        if (s.type === 'column') totalSl += sum;
        else if (s.type === 'spline') totalDt += sum;
      });
      var roundedTr = Math.round(totalDt / 1e6);
      return 'Số lượng: <b>' + numeral(totalSl).format('0,0') + '</b>' +
             ' &nbsp;|&nbsp; Doanh thu: <b>' + numeral(roundedTr).format('0,0') + ' Tr</b>' +
             ' <span style="color:#888">(click vào đối tượng ở chú thích để ẩn/hiện)</span>';
    }

    function renderDoanhThuOverview(start, end) {
      if (!CFG.hasFinanceRole) {
        U.showNoPermissionPie('chart_doanhthu_overview', 'Doanh thu');
        return $.Deferred().resolve().promise();
      }
      return API.doanhThuOverview(start, end).done(function (d) {
        if (!d || !d.categories || !d.categories.length) {
          $('#chart_doanhthu_overview').html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
          return;
        }

        var palette = Highcharts.getOptions().colors;
        var series = [];
        (d.patient_types || []).forEach(function (pt, idx) {
          var color = palette[idx % palette.length];
          var pack = d.by_patient_type[pt.id] || { so_luong: [], thanh_tien: [] };
          var ptDt = sumArr(pack.thanh_tien);
          var ptSl = sumArr(pack.so_luong);
          series.push({
            name: pt.name,
            type: 'column',
            yAxis: 0,
            color: color,
            data: pack.so_luong,
            stack: 'sl',
            ptDt: ptDt, // dùng cho labelFormatter của legend
            ptSl: ptSl,
            tooltip: {
              pointFormatter: function () {
                return '<span style="color:' + this.color + '">●</span> ' + this.series.name +
                       ' — SL: <b>' + numeral(this.y).format('0,0') + '</b><br/>';
              }
            }
          });
          series.push({
            name: pt.name + ' (DT)',
            linkedTo: ':previous',
            type: 'spline',
            yAxis: 1,
            color: color,
            dashStyle: 'ShortDot',
            marker: { symbol: 'circle' },
            data: pack.thanh_tien,
            tooltip: {
              pointFormatter: function () {
                return '<span style="color:' + this.color + '">●</span> ' + this.series.options.name +
                       ' — DT: <b>' + numeral(this.y).format('0,0') + ' đ</b><br/>';
              }
            }
          });
        });

        Highcharts.chart('chart_doanhthu_overview', {
          chart: {
            zoomType: 'xy',
            events: {
              load: function () { this.setSubtitle({ text: buildOverviewSubtitle(this) }); },
              redraw: function () { this.setSubtitle({ text: buildOverviewSubtitle(this) }); }
            }
          },
          title: { text: 'Doanh thu & Số lượng theo loại dịch vụ', style: { fontSize: '18px', fontWeight: 'bold' } },
          subtitle: { text: '', useHTML: true, style: { fontSize: '13px' } },
          xAxis: [{
            categories: d.categories,
            crosshair: true,
            title: { text: 'Loại dịch vụ', style: { fontSize: '13px', fontWeight: 'bold' } },
            labels: { style: { fontSize: '12px' } }
          }],
          yAxis: [
            {
              min: 0,
              title: { text: 'Số lượng', style: { color: '#666' } },
              labels: { formatter: function () { return numeral(this.value).format('0,0'); }, style: { fontSize: '12px' } },
              stackLabels: { enabled: true, style: { fontWeight: 'bold', fontSize: '11px' }, formatter: function () { return numeral(this.total).format('0,0'); } }
            },
            {
              min: 0,
              title: { text: 'Doanh thu (đ)', style: { color: '#666' } },
              labels: { formatter: function () { return numeral(this.value).format('0,0'); }, style: { fontSize: '12px' } },
              opposite: true
            }
          ],
          tooltip: { shared: true, useHTML: true },
          legend: {
            layout: 'horizontal', align: 'center', verticalAlign: 'bottom',
            useHTML: true,
            itemStyle: { fontSize: '13px', fontWeight: 'bold' },
            labelFormatter: function () {
              var dt = this.userOptions && this.userOptions.ptDt;
              if (typeof dt === 'undefined') return this.name;
              var tr = Math.round(dt / 1e6);
              return this.name +
                     ' <span style="color:#888;font-weight:normal">— ' +
                     numeral(tr).format('0,0') + ' Tr</span>';
            }
          },
          plotOptions: {
            column: {
              stacking: 'normal',
              borderWidth: 0,
              dataLabels: {
                enabled: true,
                formatter: function () { return this.y > 0 ? numeral(this.y).format('0,0') : ''; },
                style: { fontSize: '10px', fontWeight: 'bold', textOutline: '1px contrast' }
              }
            },
            spline: {
              lineWidth: 2,
              dataLabels: {
                enabled: false
              }
            },
            series: { events: { legendItemClick: function () { /* allow default toggle; redraw event will refresh subtitle */ } } }
          },
          series: series
        });
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
  
    // ----- Số lượng dịch vụ theo máy thực hiện -----
    var sbmLastData = null;       // payload {by_group, by_machine} fetch gần nhất
    var sbmMode = 'group';        // 'group' | 'machine' (mặc định nhóm)
    var sbmGroupFilter = '';      // '' = tất cả nhóm; khác rỗng = chỉ hiện nhóm này
    var SBM_PALETTE = (window.Highcharts && Highcharts.getOptions && Highcharts.getOptions().colors)
      ? Highcharts.getOptions().colors
      : ['#7cb5ec','#434348','#90ed7d','#f7a35c','#8085e9','#f15c80','#e4d354','#2b908f','#f45b5b','#91e8e1'];

    // Nạp danh sách nhóm vào dropdown từ dữ liệu đã fetch; giữ nguyên lựa chọn nếu nhóm còn tồn tại
    function populateSbmGroupFilter() {
      var $sel = $('#sbm-group-filter');
      if (!$sel.length || !sbmLastData) return;
      var groups = (sbmLastData.by_group && sbmLastData.by_group.labels) || [];
      if (sbmGroupFilter && groups.indexOf(sbmGroupFilter) === -1) sbmGroupFilter = '';
      var html = '<option value="">Tất cả nhóm</option>';
      groups.forEach(function (g) {
        html += '<option value="' + g + '"' + (g === sbmGroupFilter ? ' selected' : '') + '>' + g + '</option>';
      });
      $sel.html(html);
    }

    function drawServiceByMachine() {
      var el = 'chart_service_by_machine';
      if (!sbmLastData) return;
      var src = (sbmMode === 'machine') ? sbmLastData.by_machine : sbmLastData.by_group;
      var labels = (src && src.labels) || [];
      var data = (src && src.data) || [];
      var groups = (src && src.groups) || [];

      var isMachine = (sbmMode === 'machine');

      // Lọc theo nhóm máy được chọn (client-side, không gọi lại server).
      // Chế độ "nhóm máy": nhãn cột chính là tên nhóm; chế độ "từng máy": dùng groups[i].
      if (sbmGroupFilter) {
        var fl = [], fd = [], fg = [];
        labels.forEach(function (lab, i) {
          var g = isMachine ? (groups[i] || '(trống)') : lab;
          if (g === sbmGroupFilter) { fl.push(lab); fd.push(data[i]); fg.push(isMachine ? groups[i] : lab); }
        });
        labels = fl; data = fd; groups = fg;
      }
      var total = data.reduce(function (a, b) { return a + b; }, 0);

      if (!labels.length) {
        $('#' + el).html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
        return;
      }

      // Tô màu: chế độ "nhóm máy" -> mỗi nhóm 1 màu; chế độ "từng máy" -> tô theo nhóm
      // (các máy cùng nhóm cùng màu, để nhìn thấy cụm thiết bị).
      var groupColor = {}, gi = 0, points;
      if (isMachine) {
        points = labels.map(function (lab, i) {
          var g = groups[i] || '(trống)';
          if (!(g in groupColor)) { groupColor[g] = SBM_PALETTE[gi++ % SBM_PALETTE.length]; }
          return { y: data[i], color: groupColor[g] };
        });
      } else {
        points = data.map(function (y, i) { return { y: y, color: SBM_PALETTE[i % SBM_PALETTE.length] }; });
      }

      Highcharts.chart(el, {
        chart: { type: 'column' },
        title: {
          text: isMachine ? 'Số lượng dịch vụ theo từng máy' : 'Số lượng dịch vụ theo nhóm máy',
          style: { fontSize: '16px', fontWeight: 'bold' }
        },
        subtitle: { text: 'Tổng: ' + total + (sbmGroupFilter ? ' · nhóm ' + sbmGroupFilter : '') + (isMachine ? ' · màu theo nhóm máy' : '') },
        xAxis: { categories: labels, labels: { rotation: -45, style: { fontSize: '12px' } } },
        yAxis: { min: 0, title: { text: 'Số lượng' } },
        legend: { enabled: false },
        tooltip: {
          formatter: function () {
            var name = (this.key !== undefined && this.key !== null) ? this.key : this.x;
            var s = '<b>' + name + '</b><br/>Số lượng: ' + Highcharts.numberFormat(this.y, 0);
            if (isMachine && groups[this.point.index]) s += '<br/>Nhóm: ' + groups[this.point.index];
            return s;
          }
        },
        plotOptions: { column: { borderWidth: 0, dataLabels: { enabled: false } } },
        series: [{ name: 'Số lượng', data: points }]
      });
    }

    function renderServiceByMachine(start, end) {
      return API.serviceByMachine(start, end).done(function (d) {
        sbmLastData = d;
        populateSbmGroupFilter();
        drawServiceByMachine();
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
          renderReExamination(start, end),
          renderChuyenVien(start, end),
          renderThuThuatPhauThuat(start, end),
          renderOutTreatmentGroupType(start, end),
          renderDoanhThu(start, end),
          renderDoanhThuOverview(start, end),
          renderBuongBenh(start, end),
          renderNoiTru(start, end),
          renderKhamByRoom(start, end),
          renderExamParaclinical(start, end),
          renderDiagImaging(start, end),
          renderServiceByMachine(start, end),
          renderPrescription(start, end),
          renderFee(start, end),
          renderTransaction(start, end),
          CFG.isBieuDoDieuTriNgoaiTru ? renderNgoaiTruBlocks(start, end) : $.Deferred().resolve()
        ].concat(pies));
      },
      setServiceByMachineMode: function (mode) {
        sbmMode = (mode === 'machine') ? 'machine' : 'group';
        if (sbmLastData) drawServiceByMachine();
      },
      setServiceByMachineGroup: function (group) {
        sbmGroupFilter = group || '';
        if (sbmLastData) drawServiceByMachine();
      }
    };

    win.DCharts = DCH;
  })(window, jQuery);