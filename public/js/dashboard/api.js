(function (win, $) {
    'use strict';
    var R = (win.DASHBOARD_CFG || {}).routes || {};
  
    function get(url, params) {
      return $.ajax({ url: url, type: 'GET', dataType: 'json', data: params || {} });
    }
  
    var API = {
      txSummary: function (start, end) { return get(R.fetchTransaction, { startDate: start, endDate: end }); },
      prescription: function (start, end) { return get(R.fetchPrescription, { startDate: start, endDate: end }); },
      fee: function (start, end) { return get(R.fetchFee, { startDate: start, endDate: end }); },
      examParaclinical: function (start, end) { return get(R.fetchExamParaclinical, { startDate: start, endDate: end }); },
      diagImaging: function (start, end) { return get(R.fetchDiagImaging, { startDate: start, endDate: end }); },
      serviceByType: function (serviceId, start, end) { return get(R.fetchServiceByTypeBase + '/' + serviceId, { startDate: start, endDate: end }); },
      averageDayInpatient: function (start, end) { return get(R.fetchAverageDayInpatient, { startDate: start, endDate: end }); },
      treatment: function (start, end) { return get(R.fetchTreatment, { startDate: start, endDate: end }); },
      newPatient: function (start, end) { return get(R.fetchNewPatient, { startDate: start, endDate: end }); },
      chuyenVien: function (start, end) { return get(R.fetchChuyenVien, { startDate: start, endDate: end }); },
      outTreatmentGroupType: function (start, end) { return get(R.fetchOutTreatmentGroupType, { startDate: start, endDate: end }); },
      doanhThu: function (start, end) { return get(R.fetchDoanhThu, { startDate: start, endDate: end }); },
      buongBenh: function (start, end) { return get(R.chartBuongBenh, { startDate: start, endDate: end }); },
      khamByRoom: function (start, end) { return get(R.fetchKhamByRoom, { startDate: start, endDate: end }); },
      noiTru: function (start, end) { return get(R.fetchNoiTru, { startDate: start, endDate: end }); },
      dieuTriNgoaiTru: function (start, end) { return get(R.fetchDieuTriNgoaiTru, { startDate: start, endDate: end }); },
      patientInRoomNgoaiTru: function (start, end) { return get(R.fetchPatientInRoomNgoaiTru, { startDate: start, endDate: end }); }
    };
  
    win.DAPI = API;
  })(window, jQuery);