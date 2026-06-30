@extends('adminlte::page')
@section('title', 'Danh mục giới hạn dịch vụ')
@section('content_header')<h1>Danh mục giới hạn dịch vụ (giới tính/tuổi)</h1>@stop

@section('content')
<div class="box box-primary"><div class="box-body">
  <form id="ref-form" class="row">
    <input type="hidden" id="ref-id">
    <div class="col-md-2"><label>Mã DV *</label><input id="f-code" class="form-control" required></div>
    <div class="col-md-3"><label>Tên DV</label><input id="f-name" class="form-control"></div>
    <div class="col-md-2"><label>Giới tính</label>
      <select id="f-gender" class="form-control"><option value="">Không giới hạn</option><option value="1">Nữ</option><option value="2">Nam</option></select>
    </div>
    <div class="col-md-1"><label>Tuổi từ</label><input id="f-agefrom" type="number" class="form-control"></div>
    <div class="col-md-1"><label>Tuổi đến</label><input id="f-ageto" type="number" class="form-control"></div>
    <div class="col-md-2"><label>Ghi chú</label><input id="f-note" class="form-control"></div>
    <div class="col-md-1"><label>&nbsp;</label><br><button type="submit" class="btn btn-primary">Lưu</button></div>
  </form>
</div></div>

<div class="box"><div class="box-body table-responsive">
  <table id="ref-table" class="table table-bordered table-hover" width="100%">
    <thead><tr><th>Mã DV</th><th>Tên DV</th><th>Giới tính</th><th>Tuổi</th><th>Ghi chú</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
  </table>
</div></div>
@stop

@push('after-scripts')
<script>
var t = null;
function reset(){ $('#ref-id').val(''); $('#f-code').val('').prop('readonly',false); $('#f-name,#f-agefrom,#f-ageto,#f-note').val(''); $('#f-gender').val(''); }
$(function(){
  t = $('#ref-table').DataTable({
    processing:true, serverSide:true,
    ajax:"{{ route('khth.order-check-ref-fetch') }}",
    columns:[{data:'service_code'},{data:'service_name'},{data:'gender_text'},{data:'age_text'},{data:'note'},{data:'active_text'},{data:'actions',orderable:false,searchable:false}]
  });

  $('#ref-form').on('submit', function(e){
    e.preventDefault();
    var id=$('#ref-id').val();
    var url = id ? "{{ url('khth/order-check-ref-index') }}/"+id : "{{ route('khth.order-check-ref-store') }}";
    $.ajax({ url:url, method:'POST', data:{ _token:"{{ csrf_token() }}", service_code:$('#f-code').val(), service_name:$('#f-name').val(), required_gender_id:$('#f-gender').val(), age_from:$('#f-agefrom').val(), age_to:$('#f-ageto').val(), note:$('#f-note').val(), is_active:1 },
      success:function(){ reset(); t.ajax.reload(); },
      error:function(x){ alert(x.responseJSON ? JSON.stringify(x.responseJSON) : 'Lỗi'); }
    });
  });

  $(document).on('click','.ref-edit', function(){
    var row = t.row($(this).closest('tr')).data();
    $('#ref-id').val(row.id); $('#f-code').val(row.service_code).prop('readonly',true);
    $('#f-name').val(row.service_name); $('#f-gender').val(row.required_gender_id||''); $('#f-agefrom').val(row.age_from||''); $('#f-ageto').val(row.age_to||''); $('#f-note').val(row.note||'');
  });

  $(document).on('click','.ref-del', function(){
    if(!confirm('Xóa mục này?')) return;
    var id=$(this).data('id');
    $.ajax({ url:"{{ url('khth/order-check-ref-index') }}/"+id, method:'POST', data:{ _token:"{{ csrf_token() }}", _method:'DELETE' }, success:function(){ t.ajax.reload(); } });
  });
});
</script>
@endpush
