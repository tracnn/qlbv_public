{{-- MOT blade chung cho ca chin loai chung tu.

     Moi lop loai da khai truong() la anh xa TEN THE => cot, nen chin blade gan giong nhau
     la chin ban se troi khoi nhau. Bien vao:
       $nhan   — nhan loai chung tu
       $banGhi — mang cac chung tu; moi chung tu la mang ['nhan' =>, 'gia_tri' =>] --}}
@if (empty($banGhi))
    <p class="text-muted">Không có dữ liệu cho {{ $nhan }}.</p>
@else
    @foreach ($banGhi as $i => $dong)
    <div class="panel panel-default">
        <div class="panel-heading">
            {{ $nhan }}@if (count($banGhi) > 1) — bản {{ $i + 1 }}/{{ count($banGhi) }}@endif
        </div>
        <div class="panel-body">
            <table class="table table-condensed table-bordered">
                <tbody>
                    @foreach ($dong as $o)
                    <tr>
                        <th style="width:35%">{{ $o['nhan'] }}</th>
                        <td>{{ $o['gia_tri'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
@endif
