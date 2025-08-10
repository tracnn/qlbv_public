(function (win, $) {
    'use strict';
  
    var interval = 300000; // default 5m
    var countdown = interval / 1000;
    var timer = null;
  
    function updateCountdownUI() {
      var m = Math.floor(countdown / 60), s = countdown % 60;
      $('#countdown-timer').text((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
    }
  
    function parseDateRange() {
      var val = $('#dateRangePicker').val() || '';
      if (val.indexOf(' - ') === -1) return { start: null, end: null };
      var parts = val.split(' - ');
      return { start: parts[0], end: parts[1] };
    }
  
    function start(firstRun) {
      clearInterval(timer);
      countdown = interval / 1000;
  
      if (firstRun) {
        var d = parseDateRange();
        win.DCharts.renderAll(d.start, d.end);
      }
      updateCountdownUI();
  
      timer = setInterval(function () {
        countdown--; updateCountdownUI();
        if (countdown <= 0) {
          var d = parseDateRange();
          win.DCharts.renderAll(d.start, d.end);
          countdown = interval / 1000;
        }
      }, 1000);
    }
  
    function bind() {
      $('#refreshInterval').on('change', function () {
        interval = parseInt($(this).val(), 10) || 300000;
        start(false);
      });
  
      // Khi chọn lại range -> refresh ngay
      $('#dateRangePicker').on('apply.daterangepicker', function (ev, picker) {
        var startDate = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
        var endDate = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
        win.DCharts.renderAll(startDate, endDate);
      });
    }
  
    win.DAutoRefresh = { start: start, bind: bind, parseDateRange: parseDateRange };
  })(window, jQuery);