<input type="hidden" name="_method" value="PUT">
<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <label for="name">Tên người nhận <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control" 
                   id="name" 
                   name="name" 
                   value="{{ old('name', $emailReport->name) }}" 
                   placeholder="Nhập tên người nhận báo cáo"
                   required>
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <label for="email">Địa chỉ email <span class="text-danger">*</span></label>
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   name="email" 
                   value="{{ old('email', $emailReport->email) }}" 
                   placeholder="Nhập địa chỉ email"
                   required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" 
                           id="active" 
                           name="active" 
                           value="1" 
                           {{ old('active', $emailReport->active) ? 'checked' : '' }}>
                    Kích hoạt
                </label>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <input type="hidden" name="period" value="0">
                    <input type="checkbox" 
                           id="period" 
                           name="period" 
                           value="1" 
                           {{ old('period', $emailReport->period) ? 'checked' : '' }}>
                    Nhận báo cáo đặc thù
                </label>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <h5>Loại báo cáo nhận:</h5>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="bcaobhxh" value="0">
                        <input type="checkbox" 
                               id="bcaobhxh" 
                               name="bcaobhxh" 
                               value="1" 
                               {{ old('bcaobhxh', $emailReport->bcaobhxh) ? 'checked' : '' }}>
                        Báo cáo BHXH
                    </label>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="bcaoqtri" value="0">
                        <input type="checkbox" 
                               id="bcaoqtri" 
                               name="bcaoqtri" 
                               value="1" 
                               {{ old('bcaoqtri', $emailReport->bcaoqtri) ? 'checked' : '' }}>
                        Báo cáo quản trị
                    </label>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="qtri_tckt" value="0">
                        <input type="checkbox" 
                               id="qtri_tckt" 
                               name="qtri_tckt" 
                               value="1" 
                               {{ old('qtri_tckt', $emailReport->qtri_tckt) ? 'checked' : '' }}>
                        Thống kê chi tiết
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="qtri_hsdt" value="0">
                        <input type="checkbox" 
                               id="qtri_hsdt" 
                               name="qtri_hsdt" 
                               value="1" 
                               {{ old('qtri_hsdt', $emailReport->qtri_hsdt) ? 'checked' : '' }}>
                        Hồ sơ đăng ký
                    </label>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="qtri_dvkt" value="0">
                        <input type="checkbox" 
                               id="qtri_dvkt" 
                               name="qtri_dvkt" 
                               value="1" 
                               {{ old('qtri_dvkt', $emailReport->qtri_dvkt) ? 'checked' : '' }}>
                        Dịch vụ kỹ thuật
                    </label>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="qtri_canhbao" value="0">
                        <input type="checkbox" 
                               id="qtri_canhbao" 
                               name="qtri_canhbao" 
                               value="1" 
                               {{ old('qtri_canhbao', $emailReport->qtri_canhbao) ? 'checked' : '' }}>
                        Cảnh báo
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
