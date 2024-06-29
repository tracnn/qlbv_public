<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>

<h2>Xin chào!</h2>
<h3>Bệnh viện Đa khoa Nông nghiệp xin trân trọng gửi kết quả xét nghiệm của {{$patient_name}}</h3>
<h3>Mọi chi tiết xin liên hệ với Bác sĩ điều trị</h3>
@foreach($result as $key_0 => $detail)
	<b style="color:Blue;">{{$detail[0]->service_name}}</b></td>
	<table>
	<tr>
		<td align="center"><b>STT</b></td>
		<td align="center"><b>Tên chỉ số</b></td>
		<td align="center"><b>Đơn vị</b></td>
		<td align="center"><b>Giá trị</b></td>
		<td align="center"><b>Giá trị bình thường</b></td>
	</tr>
	@foreach($detail as $key_1 => $value)
	<tr>
		<td>{{$key_1+1}}</td>
		<td>{{$value->test_index_name}}</td>
		<td>{{$value->test_index_unit_symbol}}</td>
		<td align="right">{{$value->value}}</td>
		<td align="right">{{$value->description}}</td>
	</tr>
	@endforeach
	</table>

@endforeach	

</body>
</html>