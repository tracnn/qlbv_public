@extends('adminlte::page')
@section('title', 'Quản lý quy tắc kiểm tra')
@section('content_header')<h1>Quản lý quy tắc kiểm tra y lệnh</h1>@stop

@section('content')
<div class="box box-primary"><div class="box-body">
  <form id="rule-form" class="row">
    <input type="hidden" id="rule-id">
    <div class="col-md-2"><label>Mã luật</label><input id="f-code" class="form-control" readonly></div>
    <div class="col-md-4"><label>Tên hiển thị *</label><input id="f-name" class="form-control" required></div>
    <div class="col-md-2"><label>Mức độ</label>
      <select id="f-severity" class="form-control">
        <option value="info">Thông tin</option><option value="warning">Cảnh báo</option><option value="critical">Nghiêm trọng</option>
      </select>
    </div>
    <div class="col-md-2"><label>Trạng thái</label><br><label><input type="checkbox" id="f-active"> Bật</label></div>
    <div class="col-md-2"><label>&nbsp;</label><br>
      <button type="submit" class="btn btn-primary">Lưu</button>
      <button type="button" id="f-cancel" class="btn btn-default">Hủy</button>
    </div>
  </form>
  <p class="text-muted" style="margin-top:8px">Bấm <b>Sửa</b> ở bảng dưới để chọn quy tắc; chỉ sửa được Tên/Mức độ/Trạng thái. Mã luật &amp; class xử lý cố định theo code.</p>
</div></div>

<div class="box"><div class="box-body table-responsive">
  <table id="rule-table" class="table table-bordered table-hover" width="100%">
    <thead><tr><th>Họ</th><th>Mã luật</th><th>Loại (class)</th><th>Tên</th><th>Mức độ</th><th>Trạng thái</th><th>Cập nhật</th><th>Thao tác</th></tr></thead>
  </table>
</div></div>
@stop

@push('after-scripts')
<script>
var t = null;
function reset(){ $('#rule-id').val(''); $('#f-code').val(''); $('#f-name').val(''); $('#f-severity').val('warning'); $('#f-active').prop('checked', true); }

$(function(){
  t = $('#rule-table').DataTable({
    processing:true, serverSide:true, order:[[0,'asc']],
    ajax:"{{ route('khth.order-check-rule-fetch') }}",
    columns:[
      {data:'family'},{data:'code'},{data:'rule_type'},{data:'name'},
      {data:'severity_badge'},{data:'active_text'},{data:'updated_at'},
      {data:'actions',orderable:false,searchable:false}
    ]
  });

  $('#rule-form').on('submit', function(e){
    e.preventDefault();
    var id=$('#rule-id').val();
    if(!id){ alert('Chọn một quy tắc để sửa (bấm "Sửa" ở bảng dưới).'); return; }
    $.ajax({ url:"{{ url('khth/order-check-rule-index') }}/"+id, method:'POST',
      data:{ _token:"{{ csrf_token() }}", name:$('#f-name').val(), severity:$('#f-severity').val(), is_active:$('#f-active').is(':checked')?1:0 },
      success:function(){ reset(); t.ajax.reload(); },
      error:function(x){ alert(x.responseJSON ? JSON.stringify(x.responseJSON) : 'Lỗi'); }
    });
  });

  $('#f-cancel').on('click', reset);

  $(document).on('click','.rule-edit', function(){
    var row = t.row($(this).closest('tr')).data();
    $('#rule-id').val(row.id); $('#f-code').val(row.code);
    $('#f-name').val(row.name); $('#f-severity').val(row.severity);
    $('#f-active').prop('checked', row.is_active===true || row.is_active==1);
  });

  $(document).on('click','.rule-toggle', function(){
    var id=$(this).data('id');
    $.ajax({ url:"{{ url('khth/order-check-rule-index') }}/"+id+"/toggle", method:'POST',
      data:{ _token:"{{ csrf_token() }}" }, success:function(){ t.ajax.reload(); },
      error:function(){ alert('Lỗi cập nhật trạng thái'); }
    });
  });
});
</script>
@endpush
