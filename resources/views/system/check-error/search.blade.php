@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Nhập điều kiện lọc</b>
        </div>

        <form method="GET" action="{{route('system.check-error.search')}}">
            
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="malienket">Nhập danh sách mã liên kết</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="malienket" placeholder="Danh sách mã liên kết" value="{{$params['malienket']}}" autofocus="">
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="col-sm-6">
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label for="malienket">Loại hồ sơ</label>
                        </div>
                        <div class="col-sm-8 select2">
                            <select class="form-control" name="loai_hoso">
                                @foreach($loai_hoso as $key => $value)
                                    <option value="{{$key}}" {{ $params['loai_hoso'] == $key ? 'selected':'' }}>{{$value}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>                    
                </div>
                <div class="col-sm-6">
                    <div class="form-group row input-daterange">
                        <div class="col-sm-4">
                            <label for="date">Ngày kiểm tra</label>
                        </div>
                        <div class="col-sm-8">
                            <input class="form-control" type="date" name="date" value="{{ $params['date'] }}">
                        </div>
                    </div>                    
                </div>
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    Tìm kiếm
                </button>
            </div>              
        </form>

    </div>
</div>

@push('after-scripts')
<script src="{{ asset('/vendor/datepicker/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ asset('/vendor/datepicker/locales/bootstrap-datepicker.vi.min.js')}}"></script>
<script>
$(document).ready(function() {
    $('.input-daterange input').each(function() {
        $(this).datepicker({
            format: "yyyy-mm-dd",
            language: "vi",
            daysOfWeekHighlighted: "0,6",
            todayHighlight: true,
            autoclose: true,
        });
    });
    $('.select2 select').each(function() {
        $(this).select2({
            width: '100%',
        });
    });
});
</script>
@endpush
