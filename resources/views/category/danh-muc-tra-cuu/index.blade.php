@extends('adminlte::page')

@section('title', $dm['ten'])

@section('content_header')
  <h1>
    Danh mục tra cứu
    <small>{{ $dm['ten'] }}</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="bang-danh-muc" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    @foreach ($dm['cot'] as $nhan)
                        <th>{{ $nhan }}</th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('#bang-danh-muc').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "ajax": { url: "{{ route('danh-muc-tra-cuu.fetch', ['khoa' => $khoa]) }}" },
            "columns": [
                @php $coKhong = isset($dm['cot_co_khong']) ? $dm['cot_co_khong'] : []; @endphp
                @foreach (array_keys($dm['cot']) as $ten)
                    @if (in_array($ten, $coKhong))
                        { "data": {!! json_encode($ten) !!}, "render": function (d) { return Number(d) === 1 ? 'Có' : 'Không'; } },
                    @else
                        { "data": {!! json_encode($ten) !!} },
                    @endif
                @endforeach
            ],
        });
    });
</script>
@endpush
