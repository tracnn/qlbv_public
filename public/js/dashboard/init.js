(function (win, $) {
    'use strict';
    var CFG = win.DASHBOARD_CFG || {};
    var R = (CFG.routes || {});
  
    function initDateRange() {
      var startDate = moment().startOf('day');
      var endDate = moment().endOf('day');
  
      $('#dateRangePicker').daterangepicker({
        startDate: startDate,
        endDate: endDate,
        timePicker: true,
        timePicker24Hour: true,
        timePickerSeconds: true,
        drops: 'up',
        locale: { format: 'YYYY-MM-DD HH:mm:ss', firstDay: 1, applyLabel: 'Áp dụng', cancelLabel: 'Hủy' }
      }, function (start, end) {
        $('#dateRangePicker').val(start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss'));
      });
  
      // set initial text
      $('#dateRangePicker').val(startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + endDate.format('YYYY-MM-DD HH:mm:ss'));
    }
  
    function bindClicks() {
      // click small-box -> điều hướng chi tiết
      $(document).on('click', '.small-box-clickable', function () {
        var route = $(this).data('route');
        var type = $(this).data('type');
        var val = $('#dateRangePicker').val();
        if (!route || !type) return alert('Chưa cấu hình đầy đủ!');
        if (!val || val.indexOf(' - ') === -1) return alert('Vui lòng chọn khoảng thời gian!');
  
        var p = val.split(' - ');
        var url = route + '?from_date=' + encodeURIComponent(p[0]) + '&to_date=' + encodeURIComponent(p[1]) + '&data_type=' + encodeURIComponent(type);
        window.open(url, '_self');
      });
  
      // link danh sách PT
      $('#link-list-patient-pt').on('click', function (e) {
        e.preventDefault();
        var val = $('#dateRangePicker').val();
        if (!val || val.indexOf(' - ') === -1) return alert('Vui lòng chọn khoảng thời gian!');
        var p = val.split(' - ');
        var url = R.listPatientPT + '?date_from=' + encodeURIComponent(p[0]) + '&date_to=' + encodeURIComponent(p[1]) + '&date_type=' + encodeURIComponent('date_intruction');
        window.open(url, '_blank');
      });
    }
  
    $(function () {
      DUtils.bindAjaxSpinner();
      initDateRange();
      bindClicks();
      // auto refresh
      DAutoRefresh.bind();
      DAutoRefresh.start(true);
    });
  })(window, jQuery);