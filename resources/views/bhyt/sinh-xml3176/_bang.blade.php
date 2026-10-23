{{-- Mot bang XML: tab con "Bang" va "XML tho". Bien vao: $tab (loai, loi, phang, cot, nhom, so_dong, tho). Moi gia tri deu duoc thoat. --}}
@php
    $khoaNhom = array_keys($tab['nhom']);
    $coChiaNgay = count($khoaNhom) > 1 || (count($khoaNhom) === 1 && $khoaNhom[0] !== '');
    $idGoc = 'bang-' . $tab['loai'];
@endphp
<ul class="nav nav-pills" style="margin-bottom:10px;">
    <li class="active"><a href="#{{ $idGoc }}-bang" data-toggle="tab">Bảng ({{ $tab['so_dong'] }} dòng)</a></li>
    @if ($tab['tho'] !== null)
        <li><a href="#{{ $idGoc }}-tho" data-toggle="tab">XML thô</a></li>
    @endif
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="{{ $idGoc }}-bang">
        @if ($tab['loi'])
            <div class="alert alert-danger tab-loi">{{ $tab['loi'] }} Các bảng khác vẫn xem được.</div>
        @elseif ($tab['phang'])
            @php($dong = isset($tab['nhom']['']) ? $tab['nhom'][''] : [])
            @if (empty($dong))
                <p class="text-muted">Bảng không có dữ liệu.</p>
            @else
                <table class="table table-bordered table-condensed">
                    <thead><tr><th style="width:30%">Trường</th><th>Giá trị</th></tr></thead>
                    <tbody>
                        @foreach ($dong[0] as $truong => $giaTri)
                            <tr><td>{{ $truong }}</td><td>{{ $giaTri }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif ($coChiaNgay)
            <ul class="nav nav-tabs">
                @foreach ($khoaNhom as $j => $ngay)
                    <li class="{{ $j === 0 ? 'active' : '' }}">
                        <a href="#{{ $idGoc }}-n{{ $j }}" data-toggle="tab">
                            {{ $ngay === '' ? 'Không có ngày' : $ngay }} ({{ count($tab['nhom'][$ngay]) }})
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content" style="padding-top:10px;">
                @foreach ($khoaNhom as $j => $ngay)
                    <div class="tab-pane {{ $j === 0 ? 'active' : '' }}" id="{{ $idGoc }}-n{{ $j }}" data-nhom-ngay="{{ $ngay }}">
                        @include('bhyt.sinh-xml3176._luoi', ['cot' => $tab['cot'], 'dong' => $tab['nhom'][$ngay]])
                    </div>
                @endforeach
            </div>
        @else
            @include('bhyt.sinh-xml3176._luoi', ['cot' => $tab['cot'], 'dong' => isset($tab['nhom']['']) ? $tab['nhom'][''] : []])
        @endif
    </div>
    @if ($tab['tho'] !== null)
        <div class="tab-pane" id="{{ $idGoc }}-tho">
            <pre style="max-height:600px; overflow:auto; white-space:pre-wrap;">{{ $tab['tho'] }}</pre>
        </div>
    @endif
</div>
