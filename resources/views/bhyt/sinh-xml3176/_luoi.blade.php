{{-- Luoi dong x cot. Bien vao: $cot[] (thu tu phan tu trong XML), $dong[] (ten truong => gia tri). --}}
@if (empty($dong))
    <p class="text-muted">Không có dòng dữ liệu.</p>
@else
    <div class="table-responsive" style="max-height:600px; overflow:auto;">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    @foreach ($cot as $c)<th>{{ $c }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dong as $k => $d)
                    <tr>
                        <td>{{ $k + 1 }}</td>
                        @foreach ($cot as $c)<td>{{ isset($d[$c]) ? $d[$c] : '' }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
