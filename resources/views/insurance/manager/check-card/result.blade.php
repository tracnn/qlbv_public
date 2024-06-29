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
@include('insurance.manager.check-card.includes.detail_history_medical')
@include('insurance.manager.check-card.includes.detail_history_check')
@endif