{{-- Danh sach loi kiem cua mot ho so.

     Bien vao:
       $hoSo   — ban ghi Tt12HoSo (dung checked_at de phan biet "chua kiem" voi "khong loi")
       $cacLoi — Collection/Paginator Tt12Loi

     mo_ta chua gia tri trich tu tep Excel ben ngoai nen moi cho hien deu dung {{ }}. --}}
@if ($cacLoi->isEmpty())
    @if (empty($hoSo->checked_at))
        <p class="text-muted">
            Hồ sơ <strong>chưa được kiểm</strong> — công việc kiểm còn nằm trong hàng đợi.
        </p>
    @else
        <p class="text-success">
            <i class="fa fa-check"></i>
            Không có lỗi. Kiểm lúc {{ $hoSo->checked_at }}.
        </p>
    @endif
@else
    <table class="table table-condensed table-bordered">
        <thead>
            <tr>
                <th style="width:8%">Dòng</th>
                <th style="width:15%">Cột</th>
                <th style="width:10%">Mã lỗi</th>
                <th style="width:12%">Mức độ</th>
                <th>Mô tả</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cacLoi as $mot)
            <tr>
                <td>{{ $mot->stt_dong }}</td>
                <td>{{ $mot->cot }}</td>
                <td>{{ $mot->ma_loi }}</td>
                <td>
                    @if ($mot->muc_do === 'loi')
                        <span class="label label-danger">Lỗi</span>
                    @else
                        <span class="label label-warning">Cảnh báo</span>
                    @endif
                </td>
                <td>{{ $mot->mo_ta }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $cacLoi->links() }}
@endif
