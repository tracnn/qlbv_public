<div class="panel panel-default">
    <div class="panel-body">
        <form type="GET" action="{{ route('insurance.mcct.search') }}">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ma_cskcb">Cơ sở KCB</label>
                    <select class="form-control" name="ma_cskcb" id="ma_cskcb">
                        <option value="">-- Chọn cơ sở --</option>
                        @foreach ($danhSachCoSo as $ma => $nhan)
                            <option value="{{ $ma }}" {{ (string) $params['ma_cskcb'] === (string) $ma ? 'selected' : '' }}>{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ma_the">Mã thẻ BHYT</label>
                    <input class="form-control" type="text" name="ma_the" value="{{ $params['ma_the'] }}" placeholder="10, 12 hoặc 15 ký tự">
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ho_ten">Họ và tên</label>
                    <input class="form-control" type="text" name="ho_ten" value="{{ $params['ho_ten'] }}">
                </div>
            </div>

            <div class="col-sm-2">
                <div class="form-group">
                    <label for="ngay_sinh">Ngày sinh</label>
                    <input class="form-control" type="text" name="ngay_sinh" value="{{ $params['ngay_sinh'] }}" placeholder="dd/mm/yyyy">
                </div>
            </div>

            <div class="col-sm-1">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">Tra cứu</button>
                </div>
            </div>
        </form>
    </div>
</div>
