<!-- resources/views/partials/patient_history.blade.php -->
@php
    use Illuminate\Support\Facades\Crypt;
@endphp

@if($histories->isNotEmpty())
    <div class="container" id="historyContainer">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Sổ khám chữa bệnh
          </div>
          <div class="panel-body" id="history">
            <div class="list-group">
                @foreach($histories as $history)
                    <div class="list-group-item">
                        <p class="mb-1">Mã điều trị: {{ $history->treatment_code }}</p>
                        <p class="mb-1">Loại điều trị: {{ $history->treatment_type_name }}</p>
                        <p class="mb-1">Kết quả: {{ $history->treatment_result_name }}</p>
                        <p>Ngày khám: {{ strtodatetime($history->in_time) }}</p>
                        @php
                            $createdAt = now()->timestamp;
                            $expiresIn = 7200;
                            $token = Crypt::encryptString(
                            $history->treatment_code . '|' . $param_phone . '|' .
                            $createdAt .'|' . $expiresIn);
                        @endphp
                        <a href="{{ route('view-guide-content',['token' => $token]) }}" 
                            class="btn btn-sm btn-primary" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span> Chi tiết</a>
                    </div>
                @endforeach
                {{ $histories->appends(['code' => $param_code, 'phone' => $param_phone])->links() }}
            </div>
          </div>         
        </div>
    </div>
@else
    <p class="text-center"><label class="text-danger">Nhập Mã và Số điện thoại để tra cứu</label></p>
@endif