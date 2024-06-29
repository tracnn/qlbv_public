<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{ __('insurance.backend.labels.result') }}</b>
        </div>
        <!-- /title -->
        @if(isset($insurance_result))
        <div class="table table-responsive">
        	<table class="table table-condensed table-hover">
        		<tr>
        			<td class="col-md-2">@if($insurance_result['maKetQua'] == '000')
                        {{$insurance_code[$insurance_result['maKetQua']]}}
                        @else
                        <label style="color:red;">{{$insurance_code[$insurance_result['maKetQua']]}}</label>
                        @endif
                    </td>
                    <td>{{$insurance_result['ghiChu']}}</td>
        		</tr>
        	</table>
        </div>
        	
        @else
        	<center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>