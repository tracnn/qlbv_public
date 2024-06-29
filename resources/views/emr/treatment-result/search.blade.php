<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Nhập mã điều trị</b>
        </div>

        <form method="GET" action="{{route('treatment-result.search')}}">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-3">
                                <label for="treatment_code">Mã điều trị</label>
                            </div>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" id="treatment_code" name="treatment_code" placeholder="Nhập vào mã điều trị" value="{{$params['treatment_code']}}" onchange="entry(this.value)">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label for="khoa">Loại văn bản</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control document_type" name="document_type[]" multiple="">
                                                @foreach($document_type as $key_document_type => $value_document_type)
                                                <option value="{{ $value_document_type->document_type_code }}" @if(in_array($value_document_type->document_type_code, $ParamDocumentType)) selected="" @endif>{{ $value_document_type->document_type_name }}</option>
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
                <i class="glyphicon glyphicon-search"></i>
                    Tìm kiếm
                </button>
            </div>  
        </form>
    </div>
</div>
@push('after-scripts')
<script>
    $(document).ready( function () {
        $('.document_type').select2({ 
            width: '100%',
            allowClear: true,
            placeholder: 'Tất cả'
        });
    });
    function entry(data) {
        var char = '';
        for (var i = data.length; i <= 11; i++) {
            char = char + '0';
        }
        value = char + data;
        $(treatment_code).prop('value', value);
    }
</script>
@endpush