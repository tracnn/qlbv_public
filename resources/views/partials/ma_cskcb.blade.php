{{-- O chon co so KCB. Dung chung cho man XML3176 va man order-check.

     Bien vao:
       $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach() (bat buoc)
       $colClass     — lop cot Bootstrap, mac dinh 'col-sm-2' (khuon cua ho partial XML3176)
       $formGroup    — co boc them <div class="form-group row"> hay khong, mac dinh true

     Hai man dung hai khuon khac nhau nen phai tham so hoa:
       - XML3176 (partials/imported_by.blade.php va ho hang): col-sm-2 + form-group row
       - order-check: col-md-3, label va select la con TRUC TIEP cua cot
     Boc 'row' ben trong mot cot se sinh margin am hai ben va lam vo hang o man order-check. --}}
@php
    $colClass = isset($colClass) ? $colClass : 'col-sm-2';
    $formGroup = isset($formGroup) ? $formGroup : true;
@endphp
<div class="{{ $colClass }}">
    @if ($formGroup)
    <div class="form-group row">
    @endif
        <label for="ma_cskcb">Cơ sở KCB</label>
        <select id="ma_cskcb" class="form-control select2">
            <option value="">Tất cả cơ sở</option>
            @foreach ($danhSachCoSo as $ma => $nhan)
                <option value="{{ $ma }}">{{ $nhan }}</option>
            @endforeach
        </select>
    @if ($formGroup)
    </div>
    @endif
</div>
