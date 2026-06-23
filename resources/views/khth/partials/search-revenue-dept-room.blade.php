{{-- resources/views/khth/partials/search-revenue-dept-room.blade.php --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="department_id">Khoa thực hiện</label>
                    <select id="department_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
                <div class="col-sm-4">
                    <label for="room_type_id">Loại phòng</label>
                    <select id="room_type_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
                <div class="col-sm-4">
                    <label for="room_id">Phòng thực hiện</label>
                    <select id="room_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>
