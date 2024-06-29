@extends('adminlte::page')

@section('title', 'Thông tin tiêm chủng')

@section('content_header')
<h1>
    Vaccination
    <small>Thông tin tiêm chủng</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Thông Tin Bệnh Nhân</h3>
    </div>
    <div class="box-body">
        <p><strong>Mã:</strong> {{ $patient->code }}</p>
        <p><strong>Tên:</strong> {{ $patient->name }}</p>
        <p><strong>Ngày Sinh:</strong> {{ $patient->date_of_birth }}</p>
        <p><strong>Giới Tính:</strong> {{ $patient->gender }}</p>
        <p><strong>Thông Tin Liên Lạc:</strong> {{ $patient->contact_info }}</p>
        <p><strong>Địa Chỉ:</strong> {{ $patient->address }}</p>
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Khám Trước Tiêm Chủng</h3>
        <a href="{{ route('pre_vaccination_checks.create', $patient->id) }}" class="btn btn-success btn-sm pull-right">
            <span class="glyphicon glyphicon-plus"></span> Thêm Khám </a>
    </div>
    <div class="box-body table-responsive">
        @if ($prevaccinations->isEmpty())
            <p>Không có dữ liệu khám trước tiêm.</p>
        @else
            <table class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Ngày kiểm</th>
                        <th>Loại vắc xin</th>
                        <th>Người kiểm tra</th>
                        <th>Trạng thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prevaccinations as $prevaccination)
                    <tr class="{{ $prevaccination->eligible_for_vaccination ? 'highlight-blue' : 'highlight-red' }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $prevaccination->time }}</td>
                        <td>{{ $prevaccination->vaccine->name }}</td>
                        <td>{{ $prevaccination->administered_by }}</td>
                        <td>{{ $prevaccination->eligible_for_vaccination ? 'Đủ điều kiện' : 'Không đủ điều kiện' }}</td>
                        <td>
                            <a href="{{ route('prevaccination.edit', $prevaccination->id) }}" class="btn btn-primary btn-sm">Sửa</a>
                            <form action="{{ route('prevaccination.destroy', $prevaccination->id) }}" method="POST" style="display:inline-block;">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }}
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Lịch Sử Tiêm Chủng</h3>
        <a href="{{ route('vaccination.create', $patient->id) }}" class="btn btn-success btn-sm pull-right">
            <span class="glyphicon glyphicon-plus"></span> Thêm Tiêm Chủng</a>
    </div>
    <div class="box-body table-responsive">
        @if ($vaccinations->isEmpty())
            <p>Không có dữ liệu tiêm chủng.</p>
        @else
            <table class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Vaccine</th>
                        <th>Ngày Tiêm</th>
                        <th>Liều thứ</th>
                        <th>Liều Lượng</th>
                        <th>Người Tiêm</th>
                        <th>Tác Dụng Phụ</th>
                        <th>Mức Độ</th>
                        <th>Ngày Ghi Nhận</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vaccinations as $vaccination)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $vaccination->vaccine->name }}</td>
                        <td>{{ $vaccination->date_of_vaccination }}</td>
                        <td>{{ $vaccination->dose_number }}</td>
                        <td>{{ $vaccination->administered_amount }} ml</td>
                        <td>{{ $vaccination->administered_by }}</td>
                        <td>{{ $vaccination->description_effect }}</td>
                        <td>{{ $vaccination->severity_effect }}</td>
                        <td>{{ $vaccination->date_noted_effect }}</td>
                        <td>
                            <a href="{{ route('vaccination.edit', $vaccination->id) }}" class="btn btn-primary btn-sm">Sửa</a>
                            <form action="{{ route('vaccination.destroy', $vaccination->id) }}" method="POST" style="display:inline-block;">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }}
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection