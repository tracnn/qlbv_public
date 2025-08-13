/* utils.js - hàm dùng chung */
(function (win, $) {
    'use strict';
  
    // Locale numeral
    if (typeof numeral !== 'undefined') {
      numeral.locale('vi');
    }
  
    var Utils = {
      formatNumber: function (num) {
        if (num == null) return '0';
        try { return new Intl.NumberFormat('en-US').format(num); }
        catch (e) { return (num + '').replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
      },
      numberFormatVN: function (num) {
        return Highcharts ? Highcharts.numberFormat(num || 0, 0, ',', '.') : Utils.formatNumber(num);
      },
      showNoPermissionPie: function (containerId, title) {
        Highcharts.chart(containerId, {
          chart: { type: 'pie' },
          title: { text: title, style: { fontSize: '16px', fontWeight: 'bold' } },
          series: [{ data: [{ name: 'Không có quyền', y: 1, color: '#f5f5f5' }] }],
          plotOptions: {
            pie: {
              dataLabels: { enabled: true, format: 'Không có quyền xem dữ liệu', style: { fontSize: '14px', fontWeight: 'bold' } }
            }
          }
        });
      },
      // Highcharts factory
      renderPie3D: function (containerId, title, seriesName, points, subtitleSuffix) {
        Highcharts.chart(containerId, {
          chart: { type: 'pie', options3d: { enabled: true, alpha: 45, beta: 0 } },
          title: { text: title, style: { fontSize: '18px', fontWeight: 'bold' } },
          tooltip: { pointFormat: '<b>{point.y} ({point.percentage:.1f}%)</b>', style: { fontSize: '13px', fontWeight: 'bold' } },
          plotOptions: {
            pie: {
              innerSize: 0,
              depth: 45,
              dataLabels: { enabled: true, format: '{point.name}: {point.percentage:.1f}%', style: { fontSize: '12px' } }
            }
          },
          series: [{ name: seriesName, data: points }]
        });
      },
      renderColumnMoney: function (containerId, title, data, yTitle) {
        var total = (data || []).reduce(function (t, i) { return t + (i.y || 0); }, 0);
        Highcharts.chart(containerId, {
          chart: { type: 'column', backgroundColor: '#fff' },
          title: { text: title + ': ' + Utils.formatNumber(total), style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: { type: 'category', labels: { style: { fontSize: '13px' } } },
          yAxis: {
            min: 0,
            title: { text: yTitle || 'Số tiền', style: { fontSize: '13px' } },
            labels: { formatter: function () { return Utils.formatNumber(this.value); }, style: { fontSize: '13px' } }
          },
          tooltip: { pointFormat: '<b>{point.y:,.0f}</b>', style: { fontSize: '13px' } },
          plotOptions: {
            column: {
              colorByPoint: true,
              dataLabels: { enabled: true, formatter: function () { return Utils.formatNumber(this.y); }, style: { fontSize: '12px' } }
            }
          },
          legend: { enabled: false },
          series: [{ name: 'Giá trị', data: data }]
        });
      },
      renderStackedColumns: function (containerId, cfg) {
        Highcharts.chart(containerId, {
          chart: { type: 'column', height: 'auto' },
          title: { text: cfg.title, style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: {
            categories: cfg.categories,
            title: { text: cfg.xTitle || null, style: { fontSize: '13px', fontWeight: 'bold' } },
            labels: { rotation: -45, style: { fontSize: '13px', fontFamily: 'Verdana, sans-serif' } }
          },
          yAxis: {
            min: 0,
            title: { text: cfg.yTitle || '', style: { fontSize: '13px', fontWeight: 'bold' } },
            stackLabels: { enabled: true, style: { fontWeight: 'bold', fontSize: '11px' } },
            labels: { formatter: function () { return Utils.numberFormatVN(this.value); } }
          },
          tooltip: { shared: true, pointFormat: '<span style="color:{series.color}">●</span> {series.name}: <b>{point.y}</b><br/>', style: { fontSize: '13px', fontWeight: 'bold' } },
          plotOptions: {
            column: {
              stacking: 'normal',
              dataLabels: { enabled: true, formatter: function () { return this.y > 0 ? this.y : ''; }, style: { fontSize: '11px', fontWeight: 'bold' } }
            }
          },
          legend: { enabled: true, itemStyle: { fontSize: '13px', fontWeight: 'bold' } },
          series: cfg.series
        });
      },
      renderSimpleCategoryColumn: function (containerId, cfg) {
        Highcharts.chart(containerId, {
          chart: { type: cfg.type || 'column' },
          title: { text: cfg.title, style: { fontSize: '18px', fontWeight: 'bold' } },
          xAxis: {
            categories: cfg.categories,
            title: { text: cfg.xTitle || '', style: { fontSize: '13px', fontWeight: 'bold' } },
            labels: { rotation: 0, style: { fontSize: '13px', fontFamily: 'Verdana, sans-serif' } }
          },
          yAxis: {
            min: 0,
            title: { text: cfg.yTitle || '', style: { fontSize: '13px', fontWeight: 'bold' } },
            labels: { formatter: function () { return Utils.numberFormatVN(this.value); } }
          },
          tooltip: { pointFormat: cfg.pointFormat || '<b>{point.y}</b>', style: { fontSize: '13px', fontWeight: 'bold' } },
          legend: { enabled: false },
          series: [{ name: cfg.seriesName || 'Số lượng', data: cfg.data, colorByPoint: true, dataLabels: { enabled: true, format: '{point.y}', style: { fontSize: '13px', fontWeight: 'bold' } } }]
        });
      },
      // jQuery global spinner (đã có trong view)
      bindAjaxSpinner: function () {
        $(document).ajaxStart(function () { $('#ajax-spinner').show(); });
        $(document).ajaxStop(function () { $('#ajax-spinner').hide(); });
      }
    };
  
    win.DUtils = Utils;
  })(window, jQuery);