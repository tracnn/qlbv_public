{{-- XML nguyen van cua tung chung tu.

     Khi cong bao 205 (fileBase64Str khong hop le), doi chieu noi dung da gui la cach duy
     nhat tim ra minh sai o dau. --}}
@if ($chungTu->isEmpty())
    <p class="text-muted">Hồ sơ không có chứng từ nào.</p>
@else
    @foreach ($chungTu as $ct)
    <div class="panel panel-default">
        <div class="panel-heading">{{ $ct->loai_ho_so }} — {{ $ct->ma_chung_tu ?: '(không có mã)' }}</div>
        <div class="panel-body">
            <pre style="white-space:pre-wrap; max-height:400px; overflow:auto">{{ $ct->noi_dung_goc }}</pre>
        </div>
    </div>
    @endforeach
@endif
