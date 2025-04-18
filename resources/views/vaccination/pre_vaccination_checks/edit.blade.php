@extends('adminlte::page')

@section('title', 'Sửa Khám trước tiêm chủng')

@section('content_header')
<h1>
    Vaccination
    <small>Sửa Khám trước tiêm chủng</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Thông Tin Bệnh Nhân</h3>
    </div>
    <div class="box-body">
        <p><strong>Mã:</strong> {{ $check->patient->code }}</p>
        <p><strong>Tên:</strong> {{ $check->patient->name }}</p>
        <p><strong>Ngày Sinh:</strong> {{ $check->patient->date_of_birth }}</p>
        <p><strong>Giới Tính:</strong> {{ $check->patient->gender }}</p>
        <p><strong>Thông Tin Liên Lạc:</strong> {{ $check->patient->contact_info }}</p>
        <p><strong>Địa Chỉ:</strong> {{ $check->patient->address }}</p>
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Chỉnh Sửa Thông Tin Khám trước tiêm chủng</h3>
    </div>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form role="form" action="{{ route('pre_vaccination_checks.update', ['patient' => $check->patient->id, 'pre_vaccination_check' => $check->id]) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div class="box-body">
            <input type="hidden" class="form-control" id="patient_code" name="patient_code" 
            value="{{ $check->patient->code }}">
            <div class="form-group">
                <label for="vaccine_id">Vắc Xin</label>
                <select class="form-control select2" id="vaccine_id" name="vaccine_id" required>
                    <option value="">Chọn vaccine</option>
                    @foreach($vaccines as $vaccine)
                        <option value="{{ $vaccine->id }}" {{ $check->vaccine_id == $vaccine->id ? 'selected' : '' }}>{{ $vaccine->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="time">Ngày Khám</label>
                <input type="datetime-local" class="form-control" id="time" name="time" required    
                value="{{ date('Y-m-d\TH:i', strtotime($check->time)) }}">
            </div>
            <div class="form-group">
                <label for="weight">Cân nặng (kg)</label>
                <input type="text" class="form-control" id="weight" name="weight" value="{{ $check->weight }}">
            </div>
            <div class="form-group">
                <label for="temperature">Nhiệt độ (°C)</label>
                <input type="text" class="form-control" id="temperature" name="temperature" value="{{ $check->temperature }}">
            </div>
            <div class="form-group">
                <label for="anaphylactic_reaction">Phản ứng phản vệ mức độ III trở lên sau lần tiêm chủng trước</label>
                <select class="form-control" id="anaphylactic_reaction" name="anaphylactic_reaction">
                    <option value="0" {{ $check->anaphylactic_reaction == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->anaphylactic_reaction == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="acute_or_chronic_disease">Đang mắc bệnh cấp tính hoặc mạn tính tiến triển</label>
                <select class="form-control" id="acute_or_chronic_disease" name="acute_or_chronic_disease">
                    <option value="0" {{ $check->acute_or_chronic_disease == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->acute_or_chronic_disease == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="corticosteroids">Đang hoặc mới kết thúc điều trị corticosteroids</label>
                <select class="form-control" id="corticosteroids" name="corticosteroids">
                    <option value="0" {{ $check->corticosteroids == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->corticosteroids == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fever_or_hypothermia">Bệnh nhân có sốt hoặc hạ thân nhiệt</label>
                <select class="form-control" id="fever_or_hypothermia" name="fever_or_hypothermia">
                    <option value="0" {{ $check->fever_or_hypothermia == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->fever_or_hypothermia == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="immune_deficiency">Suy giảm miễn dịch</label>
                <select class="form-control" id="immune_deficiency" name="immune_deficiency">
                    <option value="0" {{ $check->immune_deficiency == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->immune_deficiency == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="abnormal_heart">Bất thường về nghe tim</label>
                <select class="form-control" id="abnormal_heart" name="abnormal_heart">
                    <option value="0" {{ $check->abnormal_heart == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->abnormal_heart == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="abnormal_lungs">Bất thường về nhịp thở/nghe phổi</label>
                <select class="form-control" id="abnormal_lungs" name="abnormal_lungs">
                    <option value="0" {{ $check->abnormal_lungs == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->abnormal_lungs == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="abnormal_consciousness">Bất thường về tri giác</label>
                <select class="form-control" id="abnormal_consciousness" name="abnormal_consciousness">
                    <option value="0" {{ $check->abnormal_consciousness == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->abnormal_consciousness == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="other_contraindications">Các chống chỉ định khác</label>
                <textarea class="form-control" id="other_contraindications" name="other_contraindications">{{ $check->other_contraindications }}</textarea>
            </div>
            <div class="form-group">
                <label for="specialist_exam">Khám sàng lọc theo chuyên khoa</label>
                <select class="form-control" id="specialist_exam" name="specialist_exam">
                    <option value="0" {{ $check->specialist_exam == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->specialist_exam == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="specialist_exam_details">Chi tiết khám sàng lọc chuyên khoa</label>
                <textarea class="form-control" id="specialist_exam_details" name="specialist_exam_details">{{ $check->specialist_exam_details }}</textarea>
            </div>
            <div class="form-group">
                <label for="eligible_for_vaccination">Đủ điều kiện tiêm chủng</label>
                <select class="form-control" id="eligible_for_vaccination" name="eligible_for_vaccination">
                    <option value="1" {{ $check->eligible_for_vaccination == 1 ? 'selected' : '' }}>Đủ điều kiện</option>
                    <option value="0" {{ $check->eligible_for_vaccination == 0 ? 'selected' : '' }}>Không đủ điều kiện</option>
                </select>
            </div>
            <div class="form-group">
                <label for="contraindication">Chống chỉ định</label>
                <select class="form-control" id="contraindication" name="contraindication">
                    <option value="0" {{ $check->contraindication == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->contraindication == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="postponed">Tạm hoãn</label>
                <select class="form-control" id="postponed" name="postponed">
                    <option value="0" {{ $check->postponed == 0 ? 'selected' : '' }}>Không</option>
                    <option value="1" {{ $check->postponed == 1 ? 'selected' : '' }}>Có</option>
                </select>
            </div>
            <div class="form-group">
                <label for="administered_by">Người Khám</label>
                <select class="form-control select2" id="administered_by" name="administered_by">
                    <option value="">Chọn người khám</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->tdl_username }}" {{ $check->administered_by == $doctor->tdl_username ? 'selected' : '' }}>{{ $doctor->tdl_username }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Chọn giá trị",
            allowClear: true
        });
    });
</script>
@endpush