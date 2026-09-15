{{-- KHONG dung <form> submit: nop form se nap lai ca trang va cho cong 5-60 giay voi mot
     trinh duyet trang. Nut goi AJAX, ket qua dung ngay tai cho. --}}
<div class="panel panel-default">
    <div class="panel-body">
        {{-- O quet QR de RIENG mot hang tren cung va autofocus: may quet ma vach go rat nhanh
             roi ban phim Enter/Tab, nen o nhan phai san sang truoc khi nguoi dung cham vao
             chuot. Giong het bo cuc man tra cuu the BHYT. --}}
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-1">
                    <label for="qrcode">Quét QR</label>
                </div>
                <div class="col-sm-11">
                    <input class="form-control" type="text" name="qrcode" id="qrcode"
                        placeholder="Quét mã QR trên thẻ BHYT hoặc căn cước công dân" autofocus>
                </div>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                <label for="ma_cskcb">Cơ sở KCB</label>
                <select class="form-control mcct-nhap" name="ma_cskcb" id="ma_cskcb">
                    <option value="">-- Chọn cơ sở --</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}" {{ (string) $params['ma_cskcb'] === (string) $ma ? 'selected' : '' }}>{{ $nhan }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                <label for="ma_the">Mã thẻ BHYT/CCCD</label>
                <input class="form-control mcct-nhap" type="text" name="ma_the" id="ma_the"
                    value="{{ $params['ma_the'] }}" placeholder="10, 12, 15 hoặc 17 ký tự">
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                <label for="ho_ten">Họ và tên</label>
                <input class="form-control mcct-nhap" type="text" name="ho_ten" id="ho_ten"
                    value="{{ $params['ho_ten'] }}">
            </div>
        </div>

        <div class="col-sm-2">
            <div class="form-group">
                <label for="ngay_sinh">Ngày sinh</label>
                <input class="form-control mcct-nhap" type="text" name="ngay_sinh" id="ngay_sinh"
                    value="{{ $params['ngay_sinh'] }}" placeholder="dd/mm/yyyy">
            </div>
        </div>

        <div class="col-sm-1">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-primary form-control" id="mcct-tra">Tra cứu</button>
            </div>
        </div>
    </div>
</div>
