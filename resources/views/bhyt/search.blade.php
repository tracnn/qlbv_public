@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <form type="GET" action="{{route('bhyt.search')}}">
            <div class="col-sm-6 row">
                <div class="form-group">
                    <label for="ngay_ttoan">Ngày ra</label>
                    <div class="col-sm-12 row">
                        <div class="col-sm-6 row">
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="ngay_ttoan_tu" name="ngay_ttoan_tu" value="{{ $params['ngay_ttoan_tu'] }}">
                            </div>                            
                        </div>
                        <div class="col-sm-6 row">
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="ngay_ttoan_den" name="ngay_ttoan_den" value="{{ $params['ngay_ttoan_den'] }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 row">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label for="loai_kcb">Loại KCB</label>
                        <select class="form-control loai_kcb" name="loai_kcb" id="loai_kcb">
                            <option value="" selected hidden>Tất cả</option>
                            @foreach($loai_kcb as $key => $value)
                                <option value="{{ $key }}" {{ $params['loai_kcb'] == $key ? 'selected':'' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 row">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label for="ma_the">Mã thẻ</label>
                        <input class="form-control card-number" type="text" id="ma_the" name="ma_the" placeholder="Tìm kiếm mã thẻ" value="{{ $params['ma_the'] }}">
                    </div>
                </div>
            </div>
            <div class="col-sm-6 row">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label for="khoa">Khoa kết thúc</label>
                        <select class="form-control khoa" name="khoa" id="khoa">
                            <option value="" selected hidden>Tất cả</option>
                            @foreach($department as $key_dep => $value_dep)
                            <option value="{{ $value_dep->MA_KHOA }}" {{ $params['khoa'] == $value_dep->MA_KHOA ? 'selected':'' }}>{{ $value_dep->TEN_KHOA }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 row">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    Tìm kiếm
                </button>
            </div>              
        </form>
    </div>
    <div class="panel-footer">
        <div class="row">
            <div class="col-sm-12">
                <a href="{{route('bhyt.check-card',['ngay_ttoan_tu'=>Request('ngay_ttoan_tu'),'ngay_ttoan_den'=>Request('ngay_ttoan_den'),'loai_kcb'=>Request('loai_kcb'),'ma_the'=>Request('ma_the'),'khoa'=>Request('khoa')])}}" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra thẻ BHYT
                </a>
                
                <a href="{{route('bhyt.kcb-trai-tuyen',['ngay_ttoan_tu'=>Request('ngay_ttoan_tu'),'ngay_ttoan_den'=>Request('ngay_ttoan_den'),'loai_kcb'=>Request('loai_kcb'),'ma_the'=>Request('ma_the'),'khoa'=>Request('khoa')])}}" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    KCB trái tuyến
                </a>
                <a href="{{route('bhyt.dvkt-co-dieu-kien',['ngay_ttoan_tu'=>Request('ngay_ttoan_tu'),'ngay_ttoan_den'=>Request('ngay_ttoan_den'),'loai_kcb'=>Request('loai_kcb'),'ma_the'=>Request('ma_the'),'khoa'=>Request('khoa')])}}" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    DVKT có điều kiện
                </a>

                <a href="{{route('bhyt.thuoc-co-dieu-kien',['ngay_ttoan_tu'=>Request('ngay_ttoan_tu'),'ngay_ttoan_den'=>Request('ngay_ttoan_den'),'loai_kcb'=>Request('loai_kcb'),'ma_the'=>Request('ma_the'),'khoa'=>Request('khoa')])}}" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Thuốc có điều kiện
                </a>

                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Thông tin thầu
                </a>
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    DVKT không được phê duyệt
                </a>                
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Chứng chỉ hành nghề
                </a>   
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra cân nặng
                </a>   
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra chuyển tuyến
                </a>
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra thời gian
                </a>
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra thành tiền
                </a>
                <a href="" target="_blank" class="btn btn-xs btn-warning">
                    <i class="glyphicon glyphicon-check"></i>
                    Kiểm tra mã thuốc
                </a>

            </div>
        </div>
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
    $('.khoa').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('.loai_kcb').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
});
</script>
@endpush