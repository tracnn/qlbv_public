@extends('adminlte::page')

@section('title', 'Báo cáo doanh thu dịch vụ')

@section('content_header')
<h1>
    Báo cáo
    <small>Doanh thu dịch vụ chi tiết</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-sere-serv-revenue')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="sere-serv-revenue-table" class="table table-hover table-bordered datatable" width="100%">
            <thead>
                <tr>
                    <th rowspan="3" style="vertical-align: middle;">Khoa chỉ định</th>
                    <th rowspan="3" style="vertical-align: middle;">Loại dịch vụ</th>
                    @foreach($patientTypes as $pt)
                        <th colspan="{{ $treatmentTypes->count() * 2 }}" class="text-center" style="background-color: #f3f3f3;">{{ $pt->patient_type_name }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($patientTypes as $pt)
                        @foreach($treatmentTypes as $tt)
                            <th colspan="2" class="text-center" style="background-color: #f9f9f9; font-size: 0.9em;">{{ $tt->treatment_type_name }}</th>
                        @endforeach
                    @endforeach
                </tr>
                <tr>
                    @foreach($patientTypes as $pt)
                        @foreach($treatmentTypes as $tt)
                            <th class="text-center">SL</th>
                            <th class="text-center">Thành tiền</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.2/css/rowGroup.dataTables.min.css">
<script src="https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js"></script>
<script type="text/javascript">
    var currentAjaxRequest = null;

    function fetchData(startDate, endDate) {
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        var departmentId = $('#department_catalog').val();
        var patientTypeId = $('#patient_type').val();
        var treatmentTypeId = $('#treatment_type').val();

        var table = $('#sere-serv-revenue-table').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "ajax": {
                url: "{{ route('reports-administrator.fetch-sere-serv-revenue') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.department_id = departmentId;
                    d.patient_type_id = patientTypeId;
                    d.treatment_type_id = treatmentTypeId;
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                }
            },
            "columns": [
                { data: 'department_name', visible: false },
                { data: 'service_type_name' },
                @foreach($patientTypes as $pt)
                    @foreach($treatmentTypes as $tt)
                        { data: 'sl_{{ $pt->id }}_{{ $tt->id }}', className: 'text-right' },
                        { data: 'tt_{{ $pt->id }}_{{ $tt->id }}', className: 'text-right' },
                    @endforeach
                @endforeach
            ],
            "rowGroup": {
                "dataSrc": "department_name",
                "startRender": function(rows, group) {
                    var totals = {};
                    var grandTotal = 0;
                    @foreach($patientTypes as $pt)
                        @foreach($treatmentTypes as $tt)
                            var ttKey = 'tt_{{ $pt->id }}_{{ $tt->id }}';
                            var colTotal = rows.data().pluck(ttKey).reduce(function(a, b) {
                                if (typeof b === 'string') {
                                    b = b.replace(/[^\d.-]/g, '') * 1;
                                }
                                return a + (b || 0);
                            }, 0);
                            totals[ttKey] = colTotal;
                            grandTotal += colTotal;
                        @endforeach
                    @endforeach

                    var tr = $('<tr/>')
                        .append('<td style="background-color: #d1ecf1; color: #0c5460; font-weight: bold; border-top: 2px solid #bee5eb; font-size: 1.1em;">' + group + ' - Tổng thu: ' + grandTotal.toLocaleString('en-US') + '</td>');
                    
                    @foreach($patientTypes as $pt)
                        @foreach($treatmentTypes as $tt)
                            var ttVal = totals['tt_{{ $pt->id }}_{{ $tt->id }}'];
                            var ttDisplay = ttVal === 0 ? '' : ttVal.toLocaleString('en-US');

                            tr.append('<td style="background-color: #d1ecf1; border-top: 2px solid #bee5eb;"></td>');
                            tr.append('<td class="text-right" style="background-color: #d1ecf1; color: #0c5460; font-weight: bold; border-top: 2px solid #bee5eb; font-size: 1.1em;">' + ttDisplay + '</td>');
                        @endforeach
                    @endforeach
                    
                    return tr;
                }
            },
            "order": [[0, 'asc']],
            "pageLength": 25,
        });
    }

</script>
@endpush
