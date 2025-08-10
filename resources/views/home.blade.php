@extends('adminlte::page')

@section('title', __('manager.backend.labels.title'))

@section('content_header')
<h1>
    Dashboard
    <small>Control panel</small>
</h1>
<!-- <button id="btn-refresh-dashboard" class="btn btn-primary btn-sm">
    <i class="fa fa-refresh"></i>
</button> -->
{{ Breadcrumbs::render('dashboard') }}

@push('after-styles')
<style>
    .refresh-after {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 5px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .refresh-after select {
        height: 28px;
        font-size: 12px;
        padding: 2px 5px;
    }

    .refresh-icon {
        font-size: 14px;
        color: #007bff;
    }

    #refresh-after .form-control {
        font-size: 12px;
        height: 28px;
        padding: 2px 5px;
    }
    #refresh-after #dateRangePicker {
        min-width: 220px;
        font-size: 12px;
        padding: 2px 5px;
    }

    .small-box-clickable {
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .small-box-clickable:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        opacity: 0.9;
    }
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('css/daterangepicker.css') }}" />
@endpush

@stop

@section('content')
@if(auth()->user()->hasRole('dashboard'))
<div class="panel panel-default">
    <div class="panel-body">
      <div class="row">

        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua small-box-clickable"
            data-type="doanhthu"
            data-route="{{ route('dashboard.doanhthu-detail') }}">

            <div class="inner">
              <h3 id="sum_doanhthu">0</h3>
              <p>Doanh thu</p>
            </div>
            <div class="icon">
              <i class="ion ion-bag"></i>
            </div>
          </div>
        </div>
      
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green small-box-clickable"
            data-type="treatment"
            data-route="{{ route('dashboard.treatment-detail') }}">

            <div class="inner">
              <h3 id="sum_treatment">0</h3>
              <p>BN đăng ký khám</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow small-box-clickable"
            data-type="newpatient"
            data-route="{{ route('dashboard.treatment-detail') }}">

            <div class="inner">
              <h3 id="sum_newpatient">0</h3>
              <p>BN khám lần đầu</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red small-box-clickable"
            data-type="noitru"
            data-route="{{ route('dashboard.treatment-detail') }}">
            
            <div class="inner">
              <h3 id="sum_noitru">0</h3>
              <p>VV: Nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua small-box-clickable"
            data-type="ravien-kham"
            data-route="{{ route('dashboard.treatment-detail') }}">
            
            <div class="inner">
              <h3 id="sum_ravien_kham">0</h3>
              <p>Kết thúc khám</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green small-box-clickable"
            data-type="chuyenvien"
            data-route="{{ route('dashboard.treatment-detail') }}">

            <div class="inner">
              <h3 id="sum_chuyenvien">0</h3>
              <p>Chuyển viện</p>
            </div>
            <div class="icon">
              <i class="ion ion-card"></i>
            </div>
          </div>
        </div>

      </div>

    <!-- Dòng thứ 2 -->
    <div class="row">
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua small-box-clickable"
            data-type="ravien"
            data-route="{{ route('dashboard.treatment-detail') }}">
            
            <div class="inner">
              <h3 id="sum_ravien">0</h3>
              <p>BN ra viện</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
      
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green small-box-clickable"
            data-type="ravien-noitru"
            data-route="{{ route('dashboard.treatment-detail') }}">
            
            <div class="inner">
              <h3 id="sum_ravien_noitru">0</h3>
              <p>RV: nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow small-box-clickable"
            data-type="average-inpatient"
            data-route="{{ route('dashboard.average-inpatient-detail') }}">
            
            <div class="inner">
              <h3 id="average_inpatient">0</h3>
              <p>Ngày điều trị TB</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red small-box-clickable"
            data-type="ravien-ngoaitru"
            data-route="{{ route('dashboard.treatment-detail') }}">

            <div class="inner">
              <h3 id="sum_ravien_ngoaitru">0</h3>
              <p>RV: ngoại trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua small-box-clickable"
            data-type="phauthuat"
            data-route="{{ route('dashboard.service-detail') }}">
            
            <div class="inner">
              <h3 id="sum_phauthuat">0</h3>
              <p>Phẫu thuật</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>

        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green small-box-clickable"
            data-type="thuthuat"
            data-route="{{ route('dashboard.service-detail') }}">
            
            <div class="inner">
              <h3 id="sum_thuthuat">0</h3>
              <p>Thủ thuật</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
    </div>
    <!-- Dòng thứ 2 -->

    <div class="row">
        <div class="col-lg-6 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_buongbenh" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_noitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(config("organization.is_bieudo_dieutringoaitru"))
    <div class="row">
        <div class="col-lg-6 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_buongbenh_dieutringoaitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_vaovien_dieutringoaitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-12 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_exam_paraclinical_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_diagnotic_imaging_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_fee_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_prescription_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

      <div class="row">
        <div class="col-lg-12 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_kham_by_room" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

      </div>

      <div class="row">
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_doanhthu" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_treatment" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>

      </div>

    <div class="row">
        <div class="col-lg-12 connectedSortable">
          <div class="row">
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_transaction_types" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_pay_forms" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_treatment_types" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_cashiers" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
          </div>  
        </div>
    </div>
    
    <!-- CLS 1 -->
        <div class="row">
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_kham" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_xetnghiem" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_cdha" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>

          </div>

          <!-- CLS 2 -->
          <div class="row">
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_tdcn" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_gpb" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_ns" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
          </div>      

          <div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_sa" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_tt" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_pt" style="width: 100%; height: 200px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="refresh-after" class="refresh-after">
    <i id="ajax-spinner" class="fa fa-spinner fa-spin" style="display:none; font-size:16px; color:#007bff;"></i>
    <span id="countdown-timer">00:00</span>
    <select id="refreshInterval" class="form-control">
        <option value="300000">5 min</option>
        <option value="600000">10 min</option>
        <option value="900000">15 min</option>
        <option value="1800000">30 min</option>
    </select>
    <input type="text" id="dateRangePicker" class="form-control" style="width: 180px;" />
</div>

@endif

@stop

@push('after-scripts')
  {{-- Vendor đã có của bạn giữ nguyên: numeral, highcharts, moment, daterangepicker... --}}
  <script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
  <script src="{{ asset('vendor/numeral/locales.js') }}"></script>

  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script src="https://code.highcharts.com/highcharts-3d.js"></script>
  <script src="https://code.highcharts.com/modules/exporting.js"></script>
  <script src="https://code.highcharts.com/modules/export-data.js"></script>
  <script src="https://code.highcharts.com/modules/accessibility.js"></script>

  <script src="{{ asset('js/moment.min.js') }}"></script>
  <script src="{{ asset('js/daterangepicker.min.js') }}"></script>

  {{-- Cấu hình được đẩy từ server sang client --}}
  <script>
    window.DASHBOARD_CFG = {
      canDashboard: @json(auth()->user()->hasRole('dashboard')),
      hasFinanceRole: @json(auth()->user()->hasRole('thungan-tonghop')),
      isBieuDoDieuTriNgoaiTru: @json(config('organization.is_bieudo_dieutringoaitru')),
      disableGpbChart: @json(config('organization.disable_gpb_chart')),
      // Map tất cả routes dùng AJAX vào 1 nơi
      routes: {
        fetchTransaction: "{{ route('fetch-transaction') }}",
        fetchPrescription: "{{ route('fetch-prescription') }}",
        fetchFee: "{{ route('fetch-fee') }}",
        fetchExamParaclinical: "{{ route('fetch-exam-paraclinical') }}",
        fetchDiagImaging: "{{ route('fetch-diagnotic-imaging') }}",
        fetchServiceByTypeBase: "{{ route('fetch-service-by-type', '') }}", // sẽ nối /{id} ở JS
        fetchAverageDayInpatient: "{{ route('fetch-average-day-inpatient') }}",
        fetchTreatment: "{{ route('fetch-treatment') }}",
        fetchNewPatient: "{{ route('fetch-new-patient') }}",
        fetchChuyenVien: "{{ route('fetch-chuyen-vien') }}",
        fetchOutTreatmentGroupType: "{{ route('fetch-out-treatment-group-treatment-type') }}",
        fetchDoanhThu: "{{ route('fetch-doanh-thu') }}",
        chartBuongBenh: "{{ route('home.xml_chart') }}",
        fetchKhamByRoom: "{{ route('fetch-kham-by-room') }}",
        fetchNoiTru: "{{ route('fetch-noi-tru') }}",
        fetchDieuTriNgoaiTru: "{{ route('fetch-dieu-tri-ngoai-tru') }}",
        fetchPatientInRoomNgoaiTru: "{{ route('fetch-patient-in-room-ngoai-tru') }}",
        listPatientPT: "{{ route('reports-administrator.list-patient-pt') }}",
      }
    };
  </script>

  {{-- File JS đã tách nhỏ --}}
  <script src="{{ asset('js/dashboard/utils.js') }}"></script>
  <script src="{{ asset('js/dashboard/api.js') }}"></script>
  <script src="{{ asset('js/dashboard/charts.js') }}"></script>
  <script src="{{ asset('js/dashboard/autorefresh.js') }}"></script>
  <script src="{{ asset('js/dashboard/init.js') }}"></script>
@endpush