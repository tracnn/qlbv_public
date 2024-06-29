<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Điều kiện lọc</b>
        </div>
        <form type="GET" action="{{route('ksk.search')}}">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label>Từ</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="tu_ngay" name="tu_ngay" value="{{$ParamNgay['tu_ngay']}}">
                                    </div>
                                </div> 
                            </div>
                           
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label>Đến</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="den_ngay" name="den_ngay" value="{{$ParamNgay['den_ngay']}}">
                                    </div>
                                </div>   
                            </div>
                         
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-sm-12" id="advance_search">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label>Hợp đồng</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control" id="hop_dong" name="hop_dong[]" multiple="">
                                                @foreach($hop_dong as $key => $value)
                                                <option value="{{$value->id}}"
                                                     @if(in_array($value->id, $param_hop_dong)) selected="" @endif
                                                >
                                                    {{$value->ksk_contract_code}} - {{$value->work_place_name}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>
                                </div>
                            </div>
                        </div>                    
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label>Trạng thái</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control" id="trang_thai" name="trang_thai[]" multiple="">
                                                @foreach($trang_thai as $key => $value)
                                                <option value="{{$value->id}}"
                                                     @if(in_array($value->id, $param_trang_thai)) selected="" @endif
                                                >
                                                    {{$value->service_req_stt_name}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>
                                </div>
                            </div>
                        </div>                    
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-eye-open"></i>
                    Tìm kiếm
                </button>
                <a class="btn btn-primary" href="#advance_search" role="button" aria-expanded="false" aria-controls="advance_search">Nâng cao</a>
                <a class="btn btn-success" href="{{route('ksk.export-xls',request()->getQueryString())}}" role="button"><i class="glyphicon glyphicon-download-alt"></i> Tải về XLS</a>
                <a class="btn btn-warning" href="{{route('ksk.check-emr',request()->getQueryString())}}" role="button" target="_blank"><i class="glyphicon glyphicon-check"></i> Kiểm tra EMR</a>
            </div>           
        </form>
    </div>
</div>

@push('after-scripts')
<script type="text/javascript">
$(document).ready(function() {
    $('#trang_thai').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('#hop_dong').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    // Kiểm tra trạng thái khi trang được tải
    var isAdvancedSearchExpanded = localStorage.getItem('isAdvancedSearchExpanded') === 'true';
    toggleAdvanceSearch(isAdvancedSearchExpanded);

    $('a[href="#advance_search"]').on('click', function(e) {
        e.preventDefault();
        var isExpanded = $('#advance_search').is(':visible');
        toggleAdvanceSearch(!isExpanded);
        localStorage.setItem('isAdvancedSearchExpanded', !isExpanded);
    });
});

function toggleAdvanceSearch(expand) {
    if (expand) {
        $('#advance_search').show();
        $('a[href="#advance_search"]').text('Ẩn nâng cao');
    } else {
        $('#advance_search').hide();
        $('a[href="#advance_search"]').text('Nâng cao');
    }
}
</script>
@endpush