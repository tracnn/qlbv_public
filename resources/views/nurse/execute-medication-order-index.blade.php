@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@push('after-styles')
<style>
    .group-header {
        background-color: #f0f0f0;
        font-weight: bold;
        cursor: pointer;
    }
    .group-header .toggle-icon {
        margin-right: 10px;
    }
</style>
@endpush

@section('content_header')
<h1>
    Điều dưỡng
    <small>Thực hiện y lệnh</small>
</h1>
@stop

@section('content')
@include('nurse.partials.search')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="nurse-index" class="table table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Khoa</th> <!-- department_name -->
                    <th>Phòng</th> <!-- bed_room_name -->
                    <th>Mã điều trị</th>
                    <th>Mã bệnh nhân</th>
                    <th>Họ và tên</th>
                    <th>Ngày sinh</th>
                    <th>Giới tính</th>
                    <th>Số thẻ BHYT</th>
                    <th>Ngày vào</th>
                    <th>Ngày nhập buồng</th>
                    <th>Giường</th>
                    <th>Số điện thoại</th>
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
    var table = null;

    function fetchData(startDate, endDate) {
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#nurse-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "ajax": {
                url: "{{ route('nurse.execute.medication.fetch.data') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.date_type = $('#date_type').val();
                    d.department_catalog = $('#department_catalog').val();
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "lengthMenu": [
                [10, 25, 50, 100, 200, 500, 1000, 2000], 
                [10, 25, 50, 100, 200, 500, 1000, 2000]
            ],
            "columns": [
                { "data": "department_name", "visible": false }, // Not shown, but used for grouping
                { "data": "bed_room_name", "visible": false },   // Group by this, not shown as a column
                { "data": "treatment_code" },
                { "data": "tdl_patient_code" },
                { "data": "tdl_patient_name" },
                { "data": "tdl_patient_dob" },
                { "data": "tdl_patient_gender_name" },
                { "data": "tdl_hein_card_number" },
                { "data": "in_time" },
                { "data": "add_time" },
                { "data": "bed_name" }, // This could be the bed name within the room
                { "data": "tdl_patient_phone" },
            ],
            "rowGroup": {
                "dataSrc": ['department_name', 'bed_room_name'],
                "startRender": function(rows, group, level) {
                    if (level === 0) {
                        // Group by department_name, count distinct bed_room_name and total patients
                        var distinctRooms = rows
                            .data()
                            .pluck('bed_room_name')
                            .unique()
                            .count(); // Counting distinct bed_room_name

                        var totalPatients = rows
                            .data()
                            .pluck('tdl_patient_code')
                            .count(); // Counting total patients

                        return $('<tr/>')
                            .append('<td colspan="12" class="group-header" style="background-color:#d9edf7;">' +
                                    '<span class="glyphicon glyphicon-briefcase toggle-icon"></span> ' + 
                                    group + ' (' + distinctRooms + ' phòng, ' + totalPatients + ' bệnh nhân)' +
                                    '</td>')
                            .attr('data-name', group);
                    } else if (level === 1) {
                        // Group by bed_room_name within department
                        return $('<tr/>')
                            .append('<td colspan="12" class="group-header" style="background-color:#f0f0f0;">' +
                                    '<span class="glyphicon glyphicon-bed toggle-icon"></span> ' + 
                                    group + ' (' + rows.count() + ' bệnh nhân)' +
                                    '</td>')
                            .attr('data-name', group);
                    }
                }
            },
            "order": [[0, 'asc'], [1, 'asc']], // Order by department_name, then bed_room_name
        });

        table.ajax.reload();
    }
</script>
@endpush