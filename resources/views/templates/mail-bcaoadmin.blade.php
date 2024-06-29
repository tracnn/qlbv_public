<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>

<h2>Xin chào! {{$name}}</h2>

<p>
	Báo cáo quan trọng từ: <b style="color:Blue;">{{$from_date_str}}</b> đến <b style="color:Blue;">{{$to_date_str}}</b>.<br>
	Thời gian gửi báo cáo: <b style="color:Blue;">{{date('d/m/Y H:i')}}</b>.
</p>
<b style="color:Blue;">I. Chuyển viện</b>
<p>
	<b style="color:Blue;">I.1 Chuyển viện theo chi nhánh: {{number_format($count_ds_cv_all)}}</b>
	@if($ds_cv_all->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Chi nhánh</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_all as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->branch_name}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<p>
	<b style="color:Blue;">I.2 Chuyển viện theo Bác sĩ: {{number_format($count_ds_cv_all)}}</b>
	@if($ds_cv_all_bs->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Tên đăng nhập</b></td>
			<td align="center"><b>Tên Bác sĩ</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_all_bs as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->doctor_loginname}}</td>
			<td>{{$value->doctor_username}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<p>
	<b style="color:Blue;">I.3 Chuyển viện theo Người nhập: {{number_format($count_ds_cv_all)}}</b>
	@if($ds_cv_all_nn->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Tên đăng nhập</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_all_nn as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->creator}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<p>
	<b style="color:Blue;">I.4 Chuyển viện nối theo chi nhánh: {{number_format($count_ds_cv_noi)}}</b>
	@if($ds_cv_noi->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Chi nhánh</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_noi as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->branch_name}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<p>
	<b style="color:Blue;">I.5 Chuyển viện nối theo Bác sĩ: {{number_format($count_ds_cv_noi_bs)}}</b>
	@if($ds_cv_noi_bs->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Tên đăng nhập</b></td>
			<td align="center"><b>Tên Bác sĩ</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_noi_bs as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->doctor_loginname}}</td>
			<td>{{$value->doctor_username}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<p>
	<b style="color:Blue;">I.6 Chuyển viện nối theo Người nhập: {{number_format($count_ds_cv_noi_nn)}}</b>
	@if($ds_cv_noi_nn->count())
	<table>
		<tr>
			<td align="center"><b>STT</b></td>
			<td align="center"><b>Tên đăng nhập</b></td>
			<td align="center"><b>Số lượng</b></td>
		</tr>
		@foreach($ds_cv_noi_nn as $key => $value)
		<tr>
			<td align="center">{{$key+1}}</td>
			<td>{{$value->creator}}</td>
			<td align="right">{{number_format($value->so_luong)}}</td>
		</tr>
		@endforeach	
	</table>
	@else
	<p><i>Không có thống kê...</i></p>
	@endif
</p>

<b style="color:Blue;">II. Tổng hợp Người nhập - Người chuyển: {{number_format($tonghop_nguoinhap_nguoichuyen->sum('so_luong'))}}</b>
@if($tonghop_nguoinhap_nguoichuyen->count())
<table>
	<tr>
		<td align="center"><b>STT</b></td>
		<td align="center"><b>Người nhập</b></td>
		<td align="center"><b>Người chuyển</b></td>
		<td align="center"><b>Số lượng</b></td>
		<td align="center"><b>Nối</b></td>
		<td align="center"><b>Hệ thống</b></td>
	</tr>
	@foreach($tonghop_nguoinhap_nguoichuyen as $key => $value)
	<tr>
		<td align="center">{{($key+1)}}</td>
		<td>{{$value->creator}}</td>
		<td>{{$value->doctor_username}}</td>
		<td>{{$value->so_luong}}</td>
		<td>
			@if($value->is_transfer_in)
				&#x2714;
			@endif
		</td>		
		<td>
			@if(in_array($value->creator, array('hangtt-kkb','thomtt-kcccd','hunglm-cccd')) &&
			in_array($value->doctor_loginname, array('anhvt-kkb','sangt m-kn','dungvv-kkb','hinhlc-kcccd','duongdh-kcccd')))
				&#x2714;
			@endif
		</td>
	</tr>
	@endforeach	
</table>
@else
<p><i>Không có thống kê...</i></p>
@endif

<b style="color:Blue;">III. Chuyển viện trái tuyến (không có giấy): {{number_format($danhsach_chuyenvien_traituyen->sum('so_luong'))}}</b>
@if($danhsach_chuyenvien_traituyen->count())
<table>
	<tr>
		<td align="center"><b>STT</b></td>
		<td align="center"><b>Mã điều trị</b></td>
		<td align="center"><b>User tạo</b></td>
		<td align="center"><b>BS thực hiện</b></td>
		<td align="center"><b>Nơi chuyển</b></td>
	</tr>
	@foreach($danhsach_chuyenvien_traituyen as $key => $value)
	<tr>
		<td align="center">{{$key+1}}</td>
		<td>{{$value->treatment_code}}</td>
		<td>{{$value->creator}}</td>
		<td>{{$value->doctor_username}}</td>
		<td>{{$value->medi_org_name}}</td>
	</tr>
	@endforeach	
</table>
@else
<p><i>Không có thống kê...</i></p>
@endif

</body>
</html>