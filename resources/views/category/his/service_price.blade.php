@extends('adminlte::page')

@section('title', 'Tra cứu giá dịch vụ HIS')

@section('content_header')
  <h1>
    Danh mục
    <small>Tra cứu giá dịch vụ HIS</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="his-service-price-list" class="table display table-hover responsive datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã dịch vụ</th>
                    <th>Tên dịch vụ</th>
                    <th>Mã loại DV</th>
                    <th>Tên loại DV</th>
                    <th>Mã ĐVT</th>
                    <th>Tên ĐVT</th>
                    <th>Ngày hiệu lực</th>
                    @foreach($patientTypes as $pt)
                        <th>Giá {{ $pt->patient_type_name }}</th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null;
    var table = null;

    function initServicePriceTable() {
        if (currentAjaxRequest !== null) {
            currentAjaxRequest.abort();
        }

        var priceColumns = [
            @foreach($patientTypes as $pt)
                { data: 'price_{{ $pt->id }}', name: 'price_{{ $pt->id }}', className: 'text-right', orderable: false, searchable: false },
            @endforeach
        ];

        var fixedColumns = [
            { data: 'service_code', name: 'service_code' },
            { data: 'service_name', name: 'service_name' },
            { data: 'service_type_code', name: 'service_type_code' },
            { data: 'service_type_name', name: 'service_type_name' },
            { data: 'service_unit_code', name: 'service_unit_code' },
            { data: 'service_unit_name', name: 'service_unit_name' },
            { data: 'from_time', name: 'from_time' },
        ];

        table = $('#his-service-price-list').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            responsive: true,
            scrollX: true,
            ajax: {
                url: "{{ route('category-his.fetch-service-price') }}",
                type: "GET",
                beforeSend: function (xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function () {
                    currentAjaxRequest = null;
                }
            },
            columns: fixedColumns.concat(priceColumns)
        });
    }

    $(document).ready(function () {
        initServicePriceTable();
    });
</script>
@endpush
