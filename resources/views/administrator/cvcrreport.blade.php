@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    KCB
    <small>Clinic Visit and Cost Report</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="cvcr-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã PK</th>
                    <th>Tên PK</th>
                    <th>SL Khám</th>
                    <th>Tổng Chi Phí</th>
                    <th>SL Khám Có Đơn</th>
                    <th>Chi Phí Đơn</th>
                    <th>TB Chi Phí</th>
                    <th>TB Đơn Thuốc</th>
                    <th>TL Khám Có Thuốc</th>
                    <th>TL Thuốc/Chi Phí</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại

    function fetchData(startDate, endDate) {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        var table = $('#cvcr-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('reports-administrator.fetch-cvcr') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
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
            "columns": [
                { data: 'execute_room_code' },
                { data: 'execute_room_name' },
                { data: 'total_patients', className: 'text-right' },
                { data: 'total_cost', className: 'text-right' },
                { data: 'total_patients_with_drug', className: 'text-right' },
                { data: 'total_drug_cost', className: 'text-right' },
                { data: 'avg_total_cost', className: 'text-right' },
                { data: 'avg_drug_cost', className: 'text-right' },
                { data: 'drug_percentage', className: 'text-right' },
                { data: 'drug_cost_percentage', className: 'text-right' },
            ],
        });

        table.ajax.reload();
    }
</script>
@endpush