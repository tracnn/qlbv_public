{{-- resources/views/khth/partials/search-on-time-result.blade.php --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="execute_room_id">Khoa/Phòng thực hiện</label>
                    <select id="execute_room_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
                <div class="col-sm-4">
                    <label for="service_type_id">Loại dịch vụ</label>
                    <select id="service_type_id" class="form-control select2">
                        <option value="">-- Tất cả --</option>
                        <option value="2">Xét nghiệm</option>
                        <option value="3">Chẩn đoán hình ảnh</option>
                        <option value="5">Thăm dò chức năng</option>
                        <option value="10">Siêu âm</option>
                    </select>
                </div>
            </div>
        </div>
        <input type="hidden" id="drill_service_id" value="">
        <input type="hidden" id="drill_status" value="">
        @include('partials.load_data_button')
    </div>
</div>
