@extends('adminlte::page')

@section('title', 'Tham số hệ thống')

@section('content_header')
<h1>
    Tham số
    <small>hệ thống</small>
</h1>
{{ Breadcrumbs::render('system.sys-param') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="sysparam-list" class="table table-hover responsive" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã tham số</th>
                    <th>Tên tham số</th>
                    <th>Diễn giải</th>
                    <th>Giá trị</th>
                    <th>Ngày tạo</th>
                    <th>Ngày sửa</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sys_params as $key => $value)
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$value->param_code}}</td>
                        <td>{{$value->param_name}}</td>
                        <td>{{$value->param_description}}</td>
                        <td>{{$value->param_value}}</td>
                        <td>{{$value->created_at}}</td>
                        <td>{{$value->updated_at}}</td>
                        <td><button class="edit-modal btn btn-sm btn-info" data-id="{{$value->id}}" data-title="{{$value->param_code}}"data-content="{{$value->param_value}}"><span class="glyphicon glyphicon-edit"></span> Sửa</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('includes.modal-edit-form')
@stop

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#sysparam-list').DataTable({
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
        url: "{{route('system.edit-sys-param')}}",
        type: "POST",
        data: {
            _token: "{{csrf_token()}}",
            id: $("#id_edit").val(),
            value: $('#content_edit').val(),
        },
    })
    .done(function(data) {
        location.reload();
        console.log(data);
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