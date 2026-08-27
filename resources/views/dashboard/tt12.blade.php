@extends('adminlte::page')

@section('title', 'Dashboard danh mục TT12')

@section('content_header')
<h1>Độ phủ danh mục TT12</h1>
@stop

@section('content')
@include('includes.message')

{{-- Nhan hien thi tra tu registry ngay trong view: service chi tra MA, de doi ten hien thi
     khong phai sua service. --}}
@php
    $tenMau = [];
    foreach (\App\Services\Tt12\Tt12MauRegistry::tatCa() as $ma => $lop) {
        $tenMau[$ma] = $lop::ten();
    }
    $tenCoSo = \App\Services\BHYT\DanhSachCoSo::danhSach();
@endphp

{{-- Canh bao "khong doc duoc HIS" phai lay tu CHINH phan hoi cua
     bhyt.tt12.dashboard.do-phu (kq.doc_duoc_his), khong tu mot lan goi
     DanhSachCoSo::danhSach() rieng luc render trang: cache 60 phut co the het han
     giua hai lan doc, sinh ra HAI cau tra loi khac nhau cho cung mot cau hoi trong
     cung mot lan tai trang - banner noi "HIS on" trong khi luoi ben duoi lai danh
     dau moi o la "ngoai danh sach". Container nay rong luc render, JS ben duoi to
     no khi va chi khi kq.doc_duoc_his === false. --}}
<div class="alert alert-warning" id="canh-bao-his" style="display:none;">
    <strong>Không đọc được danh sách cơ sở</strong> từ phần mềm quản lý bệnh viện.
    Lưới bên dưới có thể thiếu cột. Báo bộ phận công nghệ thông tin kiểm tra kết nối HIS.
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Sáu mẫu × các cơ sở</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered" id="bang-do-phu">
            <thead><tr><th>Mẫu</th></tr></thead>
            <tbody><tr><td><p class="text-muted">Đang tải…</p></td></tr></tbody>
        </table>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Hồ sơ đang dở dang</h3>
    </div>
    <div class="box-body" id="dai-do-dang">
        <p class="text-muted">Đang tải…</p>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    var tenMau  = @json($tenMau);
    var tenCoSo = @json($tenCoSo);
    var nhanTrangThai = @json(\App\Services\Tt12\Tt12DanhSach::cacTrangThai());
    var urlDanhSach = "{{ route('bhyt.tt12.index') }}";

    $.get("{{ route('bhyt.tt12.dashboard.do-phu') }}")
        .done(function (kq) {
            $('#canh-bao-his').toggle(kq.doc_duoc_his === false);
            veLuoi(kq.luoi);
            veDai(kq.dang_do_dang);
        })
        .fail(function () {
            $('#bang-do-phu tbody').html(
                '<tr><td class="text-danger">Không tải được dữ liệu. Thử lại sau.</td></tr>');
            $('#dai-do-dang').html('<p class="text-danger">Không tải được dữ liệu.</p>');
        });

    function veLuoi(luoi) {
        var maMau = Object.keys(luoi);

        if (!maMau.length) {
            $('#bang-do-phu tbody').html('<tr><td class="text-muted">Chưa có dữ liệu.</td></tr>');
            return;
        }

        // Truc co so lay tu chinh du lieu tra ve, khong tu tenCoSo: co so ngoai danh sach HIS
        // van phai co cot rieng - giau di la mat dau vet mot bo danh muc da gui len cong.
        var maCoSo = Object.keys(luoi[maMau[0]]);

        var $th = $('<tr>').append($('<th>').text('Mẫu'));

        $.each(maCoSo, function (i, ma) {
            $th.append($('<th>').text(tenCoSo[ma] || ma));
        });

        var $tb = $('<tbody>');

        $.each(maMau, function (i, mau) {
            var $tr = $('<tr>').append($('<td>').text(tenMau[mau] || mau));

            $.each(maCoSo, function (j, cs) {
                $tr.append(veO(luoi[mau][cs], mau, cs));
            });

            $tb.append($tr);
        });

        $('#bang-do-phu thead').html($th);
        $('#bang-do-phu tbody').replaceWith($tb);
    }

    // Danh sach loc theo IMPORTED_AT (thoi diem NAP tep), con o luoi noi ve
    // THOI_GIAN_TIEP_NHAN (thoi diem CONG nhan) - hai moc khac nhau. Bam mot o phai
    // ra danh sach co chua ho so cua o do bat ke ho so duoc nap luc nao, nen keo theo
    // ca khoang ngay RONG het muc thay vi de man danh sach tu dat "Hom nay" (mac dinh
    // cua partials.date_range) - "hom nay" se loc mat moi ho so nap tu truoc.
    var TU_NGAY_VO_HAN = '2000-01-01';
    var DEN_NGAY_VO_HAN = '2099-12-31';

    function veO(o, mau, cs) {
        var $td = $('<td>');

        // .text() chu KHONG noi chuoi vao HTML: tiep_nhan_luc den tu phan hoi cua cong BHXH,
        // tuc du lieu ngoai.
        var $a = $('<a>')
            .attr('href', urlDanhSach + '?mau=' + encodeURIComponent(mau)
                          + '&ma_cskcb=' + encodeURIComponent(cs)
                          + '&tu_ngay=' + TU_NGAY_VO_HAN + '&den_ngay=' + DEN_NGAY_VO_HAN);

        if (o.da_tiep_nhan) {
            $a.append($('<span>').addClass('label label-success').text('Đã tiếp nhận'));

            // thoi_gian_tiep_nhan co the RONG (cong khong tra khoa nay trong phan hoi -
            // xem Tt12SubmitService::docPhanHoi) ngay ca khi ma_ket_qua = '200'. Bo dau
            // "·" khi khong co ngay, thay vi in mot chuoi cut nhu " · 10 dòng".
            var ngay = doiNgay(o.tiep_nhan_luc);
            var dong = o.so_dong + ' dòng';
            $a.append($('<div>').addClass('small').text(ngay ? (ngay + ' · ' + dong) : dong));
        } else {
            $a.append($('<span>').addClass('label label-default').text('Chưa gửi'));
        }

        $td.append($a);

        if (o.ngoai_danh_sach) {
            $td.append($('<div>').addClass('small text-muted')
                .text('(cơ sở không còn trong danh sách hiện hành)'));
        }

        return $td;
    }

    /** Cong tra dang YYYYMMDDHHmmss. Khong dung Date() de tranh lech mui gio. */
    function doiNgay(s) {
        if (!s || s.length < 8) {
            return s || '';
        }

        return s.substr(6, 2) + '/' + s.substr(4, 2) + '/' + s.substr(0, 4);
    }

    function veDai(dai) {
        var $ul = $('<ul>').addClass('list-inline');
        var tong = 0;

        $.each(dai, function (ma, so) {
            tong += so;

            $ul.append($('<li>').append(
                $('<a>').attr('href', urlDanhSach + '?trang_thai=' + encodeURIComponent(ma)
                              + '&tu_ngay=' + TU_NGAY_VO_HAN + '&den_ngay=' + DEN_NGAY_VO_HAN)
                    .text((nhanTrangThai[ma] || ma) + ': ' + so)
            ));
        });

        $('#dai-do-dang').html(tong === 0
            ? '<p class="text-muted">Không có hồ sơ nào đang dở dang.</p>'
            : $ul);
    }
});
</script>
@endpush
