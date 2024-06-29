<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>

<h2>Xin chào! {{$nsd->name}}</h2>

<p>
	Báo cáo suất ăn: <b style="color:Blue;">{{$title}}</b>.<br>
	Thời gian gửi báo cáo: <b style="color:Blue;">{{date('d/m/Y H:i')}}</b>.
</p>

@if($nsd->dinh_duong)
<h3>Khoa Tiết chế - dinh dưỡng</h3>
<b style="color:Blue;">Suất ăn bệnh lý: {{number_format($sum_dinh_duong)}}</b><br>
@if($count_dinh_duong->count())
<b style="color:DodgerBlue;">Tổng hợp</b>
<table>
	<tr>
		<td align="center"><b>STT</b></td>
		<td align="center"><b>Mã suất ăn</b></td>
		<td align="center"><b>Tên suất ăn</b></td>
		<td align="center"><b>Số lượng</b></td>
	</tr>
	@foreach($count_dinh_duong_tong as $key => $value)
	<tr>
		<td align="center">{{$key+1}}</td>
		<td>{{$value->tdl_service_code}}</td>
		<td>{{$value->tdl_service_name}}</td>
		<td align="right">{{number_format($value->quality)}}</td>
	</tr>
	@endforeach	
</table>

<b style="color:DodgerBlue;">Chi tiết</b>
<table>
	<tr>
		<td align="center"><b>STT</b></td>
		<td align="center"><b>Khoa chỉ định</b></td>
		<td align="center"><b>Buổi</b></td>
		<td align="center"><b>Suất ăn</b></td>
		<td align="center"><b>Số lượng</b></td>
	</tr>
	@foreach($count_dinh_duong as $key => $value)
	<tr>
		<td align="center">{{$key+1}}</td>
		<td>{{$value->department_name}}</td>
		<td>{{$value->ration_time_name}}</td>
		<td>{{$value->tdl_service_name}}</td>
		<td align="right">{{number_format($value->quality)}}</td>
	</tr>
	@endforeach	
</table>
@else
<p><i>Không có thống kê...</i></p>
@endif
@endif
</body>
</html>