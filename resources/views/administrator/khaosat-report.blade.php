@extends('adminlte::page')

@section('title', 'Khảo sát thời gian khám bệnh')

@section('content_header')
<h1>
    Khảo sát thời gian khám bệnh ngoại trú
</h1>
@stop

@section('content')

@include('administrator.partials.search-khaosat')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="khaosat-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th rowspan="2">Mã Điều Trị</th>
                    <th rowspan="2">Họ Tên BN</th>
                    <th rowspan="2">Năm Sinh</th>
                    <th rowspan="2">Giới Tính</th>
                    <th rowspan="2">TG Tiếp Đón</th>
                    <th rowspan="2">TG Khám</th>
                    <th rowspan="2">Phòng Khám</th>
                    <th rowspan="2">Bác Sỹ</th>
                    <th rowspan="2">TG Khám (phút)</th>
                    <th rowspan="2">CLS</th>
                    <th rowspan="2">TG Chờ (phút)</th>
                    <th rowspan="2">Mã Bệnh</th>
                    <th rowspan="2">Chẩn Đoán</th>
                    <th colspan="2">XN Huyết học</th>
                    <th colspan="2">XN Vi sinh</th>
                    <th colspan="2">XN Sinh hóa</th>
                    <th colspan="2">XN Miễn dịch</th>
                    <th colspan="2">XN Nước tiểu</th>
                    <th colspan="2">XN Khác</th>
                    <th colspan="2">CĐHA X-Quang</th>
                    <th colspan="2">CĐHA CT</th>
                    <th colspan="2">CĐHA MRI</th>
                    <th colspan="2">CĐHA Khác</th>
                    <th colspan="2">Siêu âm</th>
                    <th colspan="2">Nội soi</th>
                    <th colspan="2">TDCN</th>
                    <th colspan="2">Giải phẫu bệnh</th>
                </tr>
                <tr>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                    <th>CĐ</th><th>KQ</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null;

    function fetchData(startDate, endDate) {
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        var table = $('#khaosat-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": false,
            "scrollX": true,
            "ajax": {
                url: "{{ route('reports-administrator.fetch-khaosat') }}",
                type: "POST",
                data: function(d) {
                    d._token = '{{ csrf_token() }}';
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.execute_room_id = $('#execute_room_id').val();
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                }
            },
            "columns": [
                { data: 'tdl_treatment_code' },
                { data: 'tdl_patient_name' },
                { data: 'tdl_patient_dob' },
                { data: 'tdl_patient_gender_name' },
                { data: 'tiep_don_time' },
                { data: 'kham_time' },
                { data: 'phong_kham' },
                { data: 'bac_sy' },
                { data: 'thoi_gian_kham' },
                { data: 'co_cls' },
                { data: 'thoi_gian_cho' },
                { data: 'ma_benh' },
                { data: 'chan_doan' },
                { data: 'xn_hh_cd' }, { data: 'xn_hh_kq' },
                { data: 'xn_vs_cd' }, { data: 'xn_vs_kq' },
                { data: 'xn_sh_cd' }, { data: 'xn_sh_kq' },
                { data: 'xn_md_cd' }, { data: 'xn_md_kq' },
                { data: 'xn_nt_cd' }, { data: 'xn_nt_kq' },
                { data: 'xn_khac_cd' }, { data: 'xn_khac_kq' },
                { data: 'cdha_xq_cd' }, { data: 'cdha_xq_kq' },
                { data: 'cdha_ct_cd' }, { data: 'cdha_ct_kq' },
                { data: 'cdha_mri_cd' }, { data: 'cdha_mri_kq' },
                { data: 'cdha_khac_cd' }, { data: 'cdha_khac_kq' },
                { data: 'sa_cd' }, { data: 'sa_kq' },
                { data: 'ns_cd' }, { data: 'ns_kq' },
                { data: 'tdcn_cd' }, { data: 'tdcn_kq' },
                { data: 'gpb_cd' }, { data: 'gpb_kq' },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var execute_room_id = $('#execute_room_id').val();

            var href = '{{ route("reports-administrator.export-khaosat") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'execute_room_id': execute_room_id
            });

            window.location.href = href;
        });
    });
</script>
@endpush
