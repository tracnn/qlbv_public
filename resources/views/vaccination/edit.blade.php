@extends('adminlte::page')

@section('title', 'Chỉnh sửa Tiêm chủng')

@section('content_header')
<h1>
    Vaccination
    <small>Chỉnh sửa tiêm chủng</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Thông Tin Bệnh Nhân</h3>
    </div>
    <div class="box-body">
        <p><strong>Mã:</strong> {{ $vaccination->patient->code }}</p>
        <p><strong>Tên:</strong> {{ $vaccination->patient->name }}</p>
        <p><strong>Ngày Sinh:</strong> {{ $vaccination->patient->date_of_birth }}</p>
        <p><strong>Giới Tính:</strong> {{ $vaccination->patient->gender }}</p>
        <p><strong>Thông Tin Liên Lạc:</strong> {{ $vaccination->patient->contact_info }}</p>
        <p><strong>Địa Chỉ:</strong> {{ $vaccination->patient->address }}</p>
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Chỉnh Sửa Thông Tin Tiêm Chủng</h3>
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
    <form role="form" action="{{ route('vaccination.update', $vaccination->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div class="box-body">
            <input type="hidden" class="form-control" id="patient_code" name="patient_code" 
            value="{{ $vaccination->patient->code }}">
            <div class="form-group">
                <label for="vaccine_id">Vắc Xin</label>
                <select class="form-control select2" id="vaccine_id" name="vaccine_id" required>
                    @foreach($vaccines as $vaccine)
                        <option value="{{ $vaccine->id }}" {{ $vaccine->id == $vaccination->vaccine_id ? 'selected' : '' }}>
                            {{ $vaccine->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="date_of_vaccination">Ngày Tiêm</label>
                <input type="date" class="form-control" id="date_of_vaccination" name="date_of_vaccination" value="{{ $vaccination->date_of_vaccination }}" required>
            </div>
            <div class="form-group">
                <label for="dose_number">Liều thứ</label>
                <input type="number" class="form-control" id="dose_number" name="dose_number" value="{{ $vaccination->dose_number }}" required>
            </div>
            <div class="form-group">
                <label for="administered_amount">Liều Lượng</label>
                <input type="text" class="form-control" id="administered_amount" name="administered_amount" value="{{ $vaccination->administered_amount }}" required>
            </div>
            <div class="form-group">
                <label for="administered_by">Người Tiêm</label>
                <select class="form-control select2" id="administered_by" name="administered_by">
                    <option value="">Chọn người tiêm</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->tdl_username }}" {{ $doctor->tdl_username == $vaccination->administered_by ? 'selected' : '' }}>
                            {{ $doctor->tdl_username }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="description_effect">Mô tả tác dụng phụ</label>
                <textarea class="form-control" id="description_effect" name="description_effect">{{ $vaccination->description_effect }}</textarea>
            </div>
            <div class="form-group">
                <label for="severity_effect">Mức độ nghiêm trọng</label>
                <input type="text" class="form-control" id="severity_effect" name="severity_effect" value="{{ $vaccination->severity_effect }}">
            </div>
            <div class="form-group">
                <label for="date_noted_effect">Ngày ghi nhận tác dụng phụ</label>
                <input type="date" class="form-control" id="date_noted_effect" name="date_noted_effect" value="{{ $vaccination->date_noted_effect }}">
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Cập Nhật</button>
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