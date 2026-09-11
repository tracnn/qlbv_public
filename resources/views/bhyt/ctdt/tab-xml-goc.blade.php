{{-- XML nguyen van cua tung chung tu, kem duong SUA cho tai khoan co quyen.

     Khi cong bao 205 (fileBase64Str khong hop le), doi chieu noi dung da gui la cach duy
     nhat tim ra minh sai o dau.

     TEP NAY KHONG DUOC CHUA THE SCRIPT. No duoc nap bang AJAX vao modal tren man danh sach,
     va moi khoi @push trong mot fragment nap kieu do se bi bo di KHONG MOT LOI BAO - nut
     van hien, van bam duoc, va khong co gi xay ra. Moi hanh vi nam o
     bhyt.ctdt.partials.js-chi-tiet, gan bang uy nhiem su kien.

     Bien vao: $hoSo, $chungTu, $coQuyenSuaXml --}}
@if ($chungTu->isEmpty())
    <p class="text-muted">Hồ sơ không có chứng từ nào.</p>
@else
    @if ($coQuyenSuaXml)
    <div class="alert alert-warning" style="margin-bottom:10px">
        <strong>Sửa XML gốc:</strong> nội dung dưới đây được ký số và gửi thẳng lên cổng
        Bảo hiểm xã hội. Sửa xong thì chữ ký cũ bị vô hiệu và hồ sơ cần ký lại.
    </div>
    @endif

    @foreach ($chungTu as $ct)
    <div class="panel panel-default ctdt-khoi-xml" data-chung-tu-id="{{ $ct->id }}">
        <div class="panel-heading">
            {{ $ct->loai_ho_so }} — {{ $ct->ma_chung_tu ?: '(không có mã)' }}

            @if ($coQuyenSuaXml)
            <span class="pull-right">
                <button type="button" class="btn btn-xs btn-default ctdt-mo-sua-xml">
                    <i class="fa fa-pencil"></i> Sửa
                </button>
                <button type="button" class="btn btn-xs btn-primary ctdt-luu-xml" style="display:none">
                    <i class="fa fa-save"></i> Lưu
                </button>
                <button type="button" class="btn btn-xs btn-default ctdt-huy-sua-xml" style="display:none">
                    Huỷ
                </button>
            </span>
            @endif
        </div>
        <div class="panel-body">
            {{-- Ban CHI DOC luon hien; o nhap chi hien khi bam Sua. Hai khoi rieng chu khong
                 mot textarea readonly: nguoi chi xem khong duoc thay mot o nhap moi goi, va
                 textarea readonly van cho boi den va go vao ma khong luu duoc. --}}
            <pre class="ctdt-xml-xem"
                 style="white-space:pre-wrap; max-height:400px; overflow:auto">{{ $ct->noi_dung_goc }}</pre>

            @if ($coQuyenSuaXml)
            {{-- spellcheck=false: trinh duyet gach do moi the XML, nhin nhu ca tep bi loi. --}}
            <textarea class="form-control ctdt-xml-sua" rows="18" spellcheck="false"
                      style="display:none; font-family:monospace; font-size:12px"
                      >{{ $ct->noi_dung_goc }}</textarea>
            <div class="ctdt-xml-thong-bao" style="margin-top:6px"></div>
            @endif
        </div>
    </div>
    @endforeach
@endif
