@extends('adminlte::page')

@section('title', 'Danh mục Khoa phòng')

@section('content_header')
<h1>
    Danh mục
    <small>Khoa phòng</small>
</h1>
{{ Breadcrumbs::render('system.sys-param') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-heading">
        <a href="javascript:" class="add-modal btn btn-success"><i class="fa fa-plus"> Thêm mới</i></a>
    </div> 
    <div class="panel-body table-responsive">
        <table id="cond-service-list" class="table table-hover responsive" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Kích hoạt</th>
                    <th>Ngày tạo</th>
                    <th>Ngày sửa</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($models as $key => $value)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$value->MA_KHOA}}</td>
                    <td>{{$value->TEN_KHOA}}</td>
                    <td>
                        @if($value->ACTIVE)
                        <span class="glyphicon glyphicon-check"></span>
                        @else
                        <span class="glyphicon glyphicon-unchecked"></span>
                        @endif
                    </td>
                    <td>{{\Carbon\Carbon::createFromTimestamp(strtotime($value->created_at))->diffForHumans()}}</td>
                    <td>{{\Carbon\Carbon::createFromTimestamp(strtotime($value->updated_at))->diffForHumans()}}</td>
                    <td></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#cond-service-list').DataTable({
    });

});

$(document).on('click', '.edit-modal', function() {
    $('.modal-title').text('Sửa');
    $('#id_edit').val($(this).data('id'));
    $('#title_edit').val($(this).data('title'));
    $('#content_edit').val($(this).data('content'));
    id = $('#id_edit').val();
    $('#editModal').modal('show');
});

function edit_entry() {
    $.ajax({
        url: "",
        type: "GET",
        data: {
            _token: "{{csrf_token()}}",
            id: $("#id_edit").val(),
            value: $('#content_edit').val(),
        },
    })
    .done(function(data) {
        location.reload();
        console.log("success");
    })
    .fail(function() {
        console.log("error");
    })
    .always(function() {
        console.log("complete");
    });
}
</script>
@endpush