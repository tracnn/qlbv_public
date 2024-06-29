@extends('adminlte::page')

@section('title', 'Danh mục DVKT có điều kiện')

@section('content_header')
<h1>
    DM DVKT
    <small>có điều kiện</small>
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
                    <th>Mã dịch vụ</th>
                    <th>Điều kiện</th>
                    <th>Kích hoạt</th>
                    <th>Giá trị</th>
                    <th>Khoảng cách</th>
                    <th>Kích hoạt</th>
                    <th>Giá trị</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cond_service as $key => $value)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$value->service_code}}</td>
                    <td>{{$value->cond_des}}</td>
                    <td>
                        @if($value->cond_status)
                        <span class="glyphicon glyphicon-check"></span>
                        @else
                        <span class="glyphicon glyphicon-unchecked"></span>
                        @endif
                    </td>
                    <td>{{$value->cond_val}}</td>
                    <td>{{$value->day_limit_des}}</td>
                    <td>{{$value->day_limit_status}}</td>
                    <td>{{$value->day_limit_val}}</td>
                    <td>
                        <button class="edit-modal btn btn-sm btn-info" data-id="{{$value->id}}" data-title="{{$value->service_code}}"data-content="{{$value->cond_val}}"><span class="glyphicon glyphicon-edit"></span> Sửa</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal form to edit a form -->
<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group" hidden="">
                        <label class="control-label col-sm-2" for="id">ID:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="id_edit" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="title">Mã:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="title_edit" disabled required>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="content">Giá trị:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="content_edit" autofocus required>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="edit_entry($('#id_edit').val())">
                        <span class='glyphicon glyphicon-check'></span> Update
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal">
                        <span class='glyphicon glyphicon-remove'></span> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to edit a form -->
@stop

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#cond-service-list').DataTable({
        "stateSave": true,
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
        url: "{{route('danh-muc.update-dvkt-co-dieu-kien')}}",
        type: "POST",
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