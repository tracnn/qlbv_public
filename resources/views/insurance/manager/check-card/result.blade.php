@if(isset($result_insurance))
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{ __('insurance.backend.labels.result') }}</b>
        </div>
        <!-- /title -->
        <div class="table">
        	<table class="table table-condensed table-hover">
        		<tr>
        			<td class="col-md-2">@if($result_insurance['maKetQua'] == '000')
                        {{$insurance_code[$result_insurance['maKetQua']]}}
                        @else
                        <label style="color:red;">{{$insurance_code[$result_insurance['maKetQua']]}}</label>
                        @endif
                    </td>
                    <td>{{$result_insurance['maThe']}}*** {{$result_insurance['ghiChu']}} *** {{$result_insurance['cqBHXH']}} ***</td>
        		</tr>
        	</table>
        </div>
    </div>
</div>
{{-- Chi mot the <a>, KHONG them form: form cua man nay co rang buoc - o chon co so phai nam
     trong form vi luong quet QR tu goi $('#target').submit(). Them form thu hai la cach
     chac chan lam hong luong quet QR dang chay. --}}
{{-- Chi hien khi tra the THANH CONG: tra sai/khong thay thi $params['birthday'] co the rong,
     sang man MCCT se truot luat required cua ngay_sinh. --}}
@if($result_insurance['maKetQua'] == '000')
<div class="form-group">
    <a class="btn btn-default" href="{{ route('insurance.mcct.search', [
        'ma_cskcb' => $params['ma_cskcb'],
        'ma_the' => $params['card-number'],
        'ho_ten' => $params['name'],
        'ngay_sinh' => $params['birthday'],
    ]) }}">Tra tiền cùng chi trả</a>
</div>
@endif
@include('insurance.manager.check-card.includes.detail_history_medical')
@include('insurance.manager.check-card.includes.detail_history_check')
@endif