@extends('adminlte::page')

@section('title', 'Danh sách Bệnh Nhân')

@section('content_header')
<h1>
    Vaccination
    <small>Danh sách Bệnh nhân</small>
</h1>
@stop

@section('content')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="patient-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Ngày Sinh</th>
                    <th>Giới Tính</th>
                    <th>Thông Tin Liên Lạc</th>
                    <th>Địa Chỉ</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patients as $patient)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $patient->code }}</td>
                    <td>{{ $patient->name }}</td>
                    <td>{{ $patient->date_of_birth }}</td>
                    <td>{{ $patient->gender }}</td>
                    <td>{{ $patient->contact_info }}</td>
                    <td>{{ $patient->address }}</td>
                    <td>
                        <a href="{{ route('vaccination.data', ['patient_code'=>$patient->code]) }}" class="btn btn-sm btn-info">
                                    <span class="glyphicon glyphicon-plus"></span> Tiêm chủng </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script>
    $(document).ready(function() {
        $('#patient-index').DataTable({
            "responsive": true,
            "searching": true, // Enable search
            "language": {
                "search": "Tìm kiếm:", // Customize search label
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Sau",
                    "previous": "Trước"
                }
            }
        });
    });
</script>
@endpush