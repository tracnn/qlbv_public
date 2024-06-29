@extends('adminlte::page')

@section('title', 'Xem đơn mua ngoài')

@section('content_header')
<h1>
    Đơn thuốc
    <small>mua ngoài</small>
</h1>

@stop

@section('content')

@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        @if(isset($organizedResults))
            @foreach ($organizedResults as $serviceReqId => $serviceReqData)
                <div>
                    <p><strong style="color: red;">Ngày y lệnh: {{ strtodatetime($serviceReqData['his_service_req']->intruction_time) }}
                        @if(in_array($serviceReqData['his_service_req']->service_req_stt_id, [2, 3]))
                            <span style="color: green;">&#10003;</span> <!-- Ký tự tick màu xanh -->
                        @endif
                        </strong>
                    </p>
                    <ul>
                        @foreach ($serviceReqData['his_service_req_mety'] as $mety)
                            <li>
                                <strong>{{ $mety->medicine_type_name }}</strong><br>
                                Đơn vị tính: {{ $mety->unit_name }}<br>
                                Số lượng: {{ number_format($mety->amount) }}<br>
                                Hướng dẫn: {{ $mety->tutorial }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @else
        <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>

@stop