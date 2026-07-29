{{-- O chon co so KCB. Dung chung cho man XML3176 va man order-check.
     Bien vao: $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach(). --}}
<div class="col-sm-2">
    <div class="form-group row">
        <label for="ma_cskcb">Cơ sở KCB</label>
        <select id="ma_cskcb" class="form-control select2">
            <option value="">Tất cả cơ sở</option>
            @foreach ($danhSachCoSo as $ma => $nhan)
                <option value="{{ $ma }}">{{ $nhan }}</option>
            @endforeach
        </select>
    </div>
</div>
