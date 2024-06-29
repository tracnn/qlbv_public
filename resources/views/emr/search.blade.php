<div class="panel panel-default">
    <div class="panel-body">
        <form type="GET" action="{{route('emr.search')}}">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <label for="treatment_code">Mã điều trị</label>
                            <input class="form-control" type="text" id="treatment_code" name="treatment_code" placeholder="Nhập vào mã điều trị" value="{{$params['treatment_code']}}" onchange="entry(this.value)" autofocus>

                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <label for="date_type">Lọc theo</label>
                            <select class="form-control" id="date_type">
                                <option value="date_in">Ngày vào</option>
                                <option value="date_out">Ngày ra</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="date_from">Từ</label>
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="date_from" name="date[from]" value="{{$params['date']['from']}}">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="date_to">Đến</label>
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="date_to" name="date[to]" value="{{$params['date']['to']}}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="department">Khoa điều trị</label>
                            <select class="form-control department" id="department" name="department[]" multiple="">
                                @foreach($department as $key_department => $value_department)
                                <option value="{{ $value_department->department_code }}" @if(in_array($value_department->department_code, $ParamDepartment)) selected="" @endif>{{ $value_department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>                    
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="treatment_type">Diện điều trị</label>
                            <select class="form-control treatment_type" id="treatment_type" name="treatment_type[]" multiple="">
                                @foreach($treatment_type as $key_treatment_type => $value_treatment_type)
                                <option value="{{ $value_treatment_type->treatment_type_code }}" @if(in_array($value_treatment_type->treatment_type_code, $ParamTreatmentType)) selected="" @endif>{{ $value_treatment_type->treatment_type_name }}</option>
                                @endforeach
                            </select>
                        </div>                    
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="patient_type">Diện đối tượng</label>
                            <select class="form-control patient_type" id="patient_type" name="patient_type[]" multiple="">
                                @foreach($patient_type as $key_patient_type => $value_patient_type)
                                <option value="{{ $value_patient_type->patient_type_code }}" @if(in_array($value_patient_type->patient_type_code, $ParamPatientType)) selected="" @endif>{{ $value_patient_type->patient_type_name }}</option>
                                @endforeach
                            </select>
                        </div>                    
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group row">
                    <button class="btn btn-info">
                    <i class="glyphicon glyphicon-eye-open"></i>
                        Tải dữ liệu...
                    </button>
                </div>
            </div>              
        </form>
    </div>
</div>

@push('after-scripts')
<script>
function entry(data) {
    if (data) {
        var char = '';
        for (var i = data.length; i <= 11; i++) {
            char = char + '0';
        }
        value = char + data;
        $(treatment_code).prop('value', value);
    }
}

$(document).ready(function() {
    $('.department').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('.treatment_type').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('.patient_type').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
});
</script>
@endpush