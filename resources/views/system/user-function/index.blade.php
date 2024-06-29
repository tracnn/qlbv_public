@extends('adminlte::page')

@section('title', 'Kiểm tra chi tiết')

@section('content_header')
{{ csrf_field() }}
@stop

@section('content')

@include('includes.message')
@include('system.user-function.search')

<div class="panel panel-default">
<div class="panel-body table-responsive">
@if(isset($result) && $result)

{{$result->treatment_code}}- {{$result->tdl_patient_code}}- {{$result->tdl_patient_name}} - {{substr($result->tdl_patient_dob,0,4)}} - {{$result->icd_code}} - {{$result->icd_sub_code}}<br>
{{$result->tdl_hein_card_number}}- {{$result->tdl_hein_medi_org_code}} - {{$result->tdl_hein_medi_org_name}}<br>
{{strtodatetime($result->create_time)}} - {{strtodatetime($result->in_time)}} - {{$result->out_time ? strtodatetime($result->out_time) : ''}} - {{$result->fee_lock_time ? strtodatetime($result->fee_lock_time) : ''}}<br>
{{$result->transfer_in_medi_org_code}} - {{$result->transfer_in_medi_org_name}} ; {{$result->medi_org_code}} - {{$result->medi_org_name}}<br>
{{$result->creator}} - {{$result->doctor_loginname}} - {{$result->fee_lock_loginname}}<br>
Treatment end type: {{$result->treatment_end_type_id}}
@else
<center>{{__('insurance.backend.labels.no_information')}}</center>
@endif
</div>
</div>

@stop
