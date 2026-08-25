{{-- Dau bang DONG DU LIEU cua ho so, dung dong theo dac ta mau.

     Bien vao:
       $hoSo    — ban ghi Tt12HoSo
       $cotBang — Tt12DetailTabs::cotBang($hoSo->mau), [['the' => ..., 'nhan' => ...], ...]
       $cacDong — Paginator Tt12Dong

     $dong->the($cot['the']) LUON tra ve chuoi va tra '' khi thieu khoa - xem Tt12Dong::the().
     Truy cap thang $dong->du_lieu[$cot['the']] se nem Undefined index voi moi ho so nap tu
     tep thieu cot. --}}
<div class="table-responsive">
    <table class="table table-sm table-bordered table-striped">
        <thead>
            <tr>
                <th>STT</th>
                @foreach ($cotBang as $cot)
                    @continue($cot['the'] === 'STT')
                    <th>{{ $cot['nhan'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($cacDong as $dong)
                <tr>
                    <td>{{ $dong->stt }}</td>
                    @foreach ($cotBang as $cot)
                        @continue($cot['the'] === 'STT')
                        <td>{{ $dong->the($cot['the']) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($cotBang) }}" class="text-muted">Không có dòng dữ liệu nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $cacDong->links() }}
