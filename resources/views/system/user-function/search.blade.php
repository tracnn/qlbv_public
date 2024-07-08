@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Nhập mã điều trị</b>
        </div>

        <form method="GET" action="{{route('system.user-function.search')}}">
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="treatment_code">Mã điều trị</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" id="treatment_code" name="treatment_code" placeholder="Nhập vào mã điều trị" value="{{$params['treatment_code']}}" onchange="entry(this.value)" autofocus>
                    </div>
                </div>
            </div>
        </form>
        <div class="col-sm-6">
            <div class="form-group row">
            <div class="col-sm-12">
                @if(isset($result) && $result)
                @if($result->treatment_end_type_id == 2 && ($result->is_lock_hein || $result->is_active == 0))
                <button class="btn btn-danger" onclick="entry_remove($(treatment_code).val())">
                <i class="glyphicon glyphicon-lock"></i>
                    Khóa
                </button>
                @endif
                @if($result->treatment_end_type_id == 4 && !empty($result->medi_org_code))
                <button class="btn btn-primary" onclick="entry_update($(treatment_code).val())">
                <i class="glyphicon glyphicon-ok"></i>
                    Mở khóa
                </button>
                @else
                    @if(($result->is_lock_hein || $result->is_active == 0) && $result->fee_lock_time)
                    <button class="btn btn-warning" id="open">
                    <i class="glyphicon glyphicon-credit-card"></i>
                    </button>
                    @endif  
                @endif
				
				@if($result->treatment_end_type_id == 2)
				    <button class="btn btn-primary" id="minus">
                        <i class="glyphicon glyphicon-minus"></i>
                    </button>
					<button class="btn btn-primary" id="plus">
					    <i class="glyphicon glyphicon-plus"></i>
					</button>													
				@endif
                    <a href="{{route('insurance.check-card.search',['card-number' => $result->tdl_hein_card_number, 'name' => $result->tdl_patient_name, 'birthday' => substr($result->tdl_patient_dob,0,4)])}}" class="btn btn-success" target="_blank"><span class="glyphicon glyphicon-check"></span> Tra thẻ</a><br>
                @endif
            </div>
            </div>            
        </div>
    </div>
</div>
@push('after-scripts')
<script>
$(document).ready( function () {
	$('#plus').click(function(){
        if (confirm('Are you sure ?')) {
            $.ajax({
                url: "{{route('system.entry-plus')}}",
                type: "POST",
                data: {
                    _token: "{{csrf_token()}}",
                    treatment_code: $(treatment_code).val(),
                },
            })
            .done(function(data) {
                toastr.success('Thực hiện thành công!');
                location.reload();
                console.log(data);
            })
            .fail(function() {
                console.log("error");
            });
        }
	});	
	
	$('#minus').click(function(){
        if (confirm('Are you sure ?')) {
            $.ajax({
                url: "{{route('system.entry-minus')}}",
                type: "POST",
                data: {
                    _token: "{{csrf_token()}}",
                    treatment_code: $(treatment_code).val(),
                },
            })
            .done(function(data) {
                toastr.success('Thực hiện thành công!');
                location.reload();
                console.log(data);
            })
            .fail(function() {
                console.log("error");
            });
        }
	});	

    $('#open').click(function(){
        if (confirm('Are you sure ?')) {
            $.ajax({
                url: "{{route('system.entry-open')}}",
                type: "POST",
                data: {
                    _token: "{{csrf_token()}}",
                    treatment_code: $(treatment_code).val(),
                },
            })
            .done(function(data) {
                toastr.success('Thực hiện thành công!');
                location.reload();
                console.log(data);
            })
            .fail(function() {
                console.log("error");
            });
        }
    }); 

});
    function entry(data) {
        var char = '';
        data = data.trim();
        for (var i = data.length; i <= 11; i++) {
            char = char + '0';
        }
        value = char + data;
        $(treatment_code).prop('value', value);
    }

    function entry_remove(data) {
        $.ajax({
            url: "{{route('system.entry-remove')}}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                treatment_code: data,
            },
        })
        .done(function() {
            toastr.success('Thực hiện thành công!');
            location.reload();
            console.log('done');
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            console.log("complete");
        });
    }

    function entry_update(data) {
        $.ajax({
            url: "{{route('system.entry-update')}}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                treatment_code: data,
            },
        })
        .done(function() {
            toastr.success('Thực hiện thành công!');
            location.reload();
            console.log('done');
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            console.log("complete");
        });
    }
</script>
@endpush