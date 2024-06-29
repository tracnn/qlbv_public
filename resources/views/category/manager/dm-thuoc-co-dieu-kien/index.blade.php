@extends('adminlte::page')

@section('title', 'Danh mục Thuốc có điều kiện')

@section('content_header')
<h1>
    Danh mục
    <small>Thuốc có điều kiện</small>
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
                    <th>Mã hoạt chất</th>
                    <th>Điều kiện</th>
                    <th>Kích hoạt</th>
                    <th>Giá trị (ICD)</th>
                    <th>Ngày tạo</th>
                    <th>Ngày sửa</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($models as $key => $value)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$value->pharma_code}}</td>
                    <td>{{$value->pharma_description}}</td>
                    <td>
                        @if($value->pharma_status)
                        <span class="glyphicon glyphicon-check"></span>
                        @else
                        <span class="glyphicon glyphicon-unchecked"></span>
                        @endif
                    </td>
                    <td>{{$value->pharma_val}}</td>
                    <td>{{\Carbon\Carbon::createFromTimestamp(strtotime($value->created_at))->diffForHumans()}}</td>
                    <td>{{\Carbon\Carbon::createFromTimestamp(strtotime($value->updated_at))->diffForHumans()}}</td>
                    <td><button class="edit-modal btn btn-sm btn-info" data-id="{{$value->id}}" data-title="{{$value->pharma_code}}"data-content="{{$value->pharma_val}}"><span class="glyphicon glyphicon-edit"></span> Sửa</button></td>
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
                            <textarea type="text" class="form-control" id="content_edit" autofocus required></textarea>
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
        url: "{{route('danh-muc.update-dm-thuoc-co-dieu-kien')}}",
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