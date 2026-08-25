{{-- Nhat ky gui cua mot ho so - moi dong la MOT LAN goi cong BHXH, ke ca lan hong.

     Ly do bang tt12_lich_su_gui ton tai: cac cot trang thai tren tt12_ho_so (ma_gd,
     ma_ket_qua, thoi_gian_tiep_nhan...) bi GHI DE o moi lan gui, nen sau lan gui thu hai
     dau vet cua lan thu nhat chi con o day.

     Bien vao:
       $hoSo     — ban ghi Tt12HoSo
       $cacLichSu — Paginator Tt12LichSuGui, sap xep id desc (moi nhat truoc)

     thong_diep va loi chua noi dung tu cong BHXH ben ngoai nen luon dung {{ }}. --}}
@if ($cacLichSu->isEmpty())
    <p class="text-muted">Hồ sơ này chưa từng được gửi lên cổng BHXH.</p>
@else
    <table class="table table-condensed table-bordered">
        <thead>
            <tr>
                <th>Thời điểm gửi</th>
                <th>Người gửi</th>
                <th>Mã kết quả</th>
                <th>Mã giao dịch</th>
                <th>Thời gian tiếp nhận</th>
                <th>Thông điệp</th>
                <th>Lỗi nguyên văn</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cacLichSu as $mot)
            <tr>
                <td>{{ $mot->gui_luc }}</td>
                <td>{{ $mot->gui_boi }}</td>
                <td>{{ $mot->ma_ket_qua }}</td>
                <td>{{ $mot->ma_gd }}</td>
                <td>{{ $mot->thoi_gian_tiep_nhan }}</td>
                <td>{{ $mot->thong_diep }}</td>
                <td>{{ $mot->loi }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $cacLichSu->links() }}
@endif
