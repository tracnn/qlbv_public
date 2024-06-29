@extends('adminlte::page')

@section('title', 'Kiểm tra thiết lập KSK - EMR')

@section('content_header')
<h1>
    Khám sức khỏe
    <small>Kiểm tra thiết lập EMR</small>
</h1>
{{ Breadcrumbs::render('ksk.check-emr') }}
@stop

@section('content')
@include('includes.message')
<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách hồ sơ
    </div>
    <div class="panel-body table-responsive">
        <table id="check-emr" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <th>#</th>
                <th>STT</th>
                <th>Trạng thái</th>
                <th>Mã điều trị</th>
                <th>Tên BN</th>
                <th>Năm sinh</th>
                <th>Giới tính</th>
                <th>Thiết lập EMR</th>
                <th>NVYT chưa ký</th>
                <th>Action</th>
            </thead>
            <tbody id="load_data">
            </tbody>
        </table>
    </div>
    <img class="center-block" id="loading" src="{{asset('images/loading.gif')}}" style="display: none; padding: 10px;" />  
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
$(document).ready(function() {
    str = window.location.search;
    str = str.replace(/%5B/g,'').replace(/%5D/g,'');
    const parameters = new URLSearchParams(str);
    var loaded = false;
    var scroll = 0;
    load_check_emr();
    $(window).scroll(function(){
        if($(window).scrollTop() + $(window).height() >= $(document).height()) {
            if (loaded) {
                load_check_emr();
            }
        }
    })

    function load_check_emr() {
        loaded = false;
        $('#loading').show();
        $.ajax({
            type: 'GET',
            url: '{{route("ksk.get-check-emr")}}',
            data: {
                'scroll': scroll,
                'tu_ngay': parameters.get('tu_ngay'),
                'den_ngay': parameters.get('den_ngay'),
                'hop_dong': parameters.getAll('hop_dong'),
                'trang_thai': parameters.getAll('trang_thai'),
            },
            success: function(data) {
                switch (data.maKetqua)
                {
                    case '500': {
                        toastr.error(data.noiDung);
                        break;
                    }
                    case '400': {
                        toastr.warning(data.noiDung);
                        break;
                    }
                    default : {
                        scroll = scroll + data.scroll;
                        $.each(data.data, function(idx, val){
                            $('#load_data').append(val)
                        })
                        loaded = true;
                    }
                }
                $('#loading').hide();
            },
        });  
    }
})
</script>
@endpush