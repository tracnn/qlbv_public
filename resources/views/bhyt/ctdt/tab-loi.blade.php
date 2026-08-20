{{-- Danh sach loi kiem cua mot ho so.

     Bien vao:
       $hoSo — ban ghi CtdtHoSo (dung checked_at de phan biet "chua kiem" voi "khong loi")
       $loi  — Collection CtdtLoi, da sap loi muc chan len truoc

     mo_ta chua gia tri trich tu the XML ben ngoai nen moi cho hien deu dung {{ }}. --}}
@if ($loi->isEmpty())
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
    @php
        // Dem thang tu tap dang hien thi, khong lay $hoSo->so_loi: so_loi la con so CHOT
        // luc job chay, con $loi dem SONG. Hai nguon lech nhau la hien ra so cảnh báo am.
        $soChan = $loi->where('muc_do', 'chan')->count();
        $soCanhBao = $loi->count() - $soChan;
    @endphp
    <p class="text-muted">
        Kiểm lúc {{ $hoSo->checked_at }} —
        <strong>{{ $soChan }}</strong> lỗi chặn gửi,
        {{ $soCanhBao }} cảnh báo.
    </p>
    <table class="table table-condensed table-bordered">
        <thead>
            <tr>
                <th style="width:10%">Mức</th>
                <th style="width:10%">Mã lỗi</th>
                <th style="width:15%">Chứng từ</th>
                <th style="width:15%">Trường</th>
                <th>Mô tả</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loi as $mot)
            <tr>
                <td>
                    @if ($mot->muc_do === 'chan')
                        <span class="label label-danger">Chặn gửi</span>
                    @else
                        <span class="label label-warning">Cảnh báo</span>
                    @endif
                </td>
                <td>{{ $mot->ma_loi }}</td>
                <td>{{ $mot->chungTu ? $mot->chungTu->loai_ho_so : '—' }}</td>
                <td>{{ $mot->ten_truong ?: '—' }}</td>
                <td>{{ $mot->mo_ta }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
