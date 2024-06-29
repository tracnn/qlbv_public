<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                <!-- Chọn khoảng thời gian -->
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="treatment_code">Mã điều trị</label>
                        <input class="form-control" type="text" id="treatment_code" pattern="\d*">
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="tu_ngay">Từ</label>
                        <div class="input-daterange">
                            <input class="form-control" type="date" id="tu_ngay">
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="den_ngay">Đến</label>
                        <div class="input-daterange">
                            <input class="form-control" type="date" id="den_ngay">
                        </div>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group row">
                        <label for="export_xlsx">XLS</label>
                        <button type="button" class="btn btn-success form-control" id="export_xlsx">Export...</button>
                    </div>
                </div>
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary form-control" id="load_data_button">Tải dữ liệu...</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')

<script type="text/javascript">
    $(document).ready(function() {
        setDefaultDates();

        function setDefaultDates() {
            var today = new Date();
            var formattedDate = formatDate(today);
            $('#tu_ngay').val(formattedDate);
            $('#den_ngay').val(formattedDate);
        }

        function formatDate(date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) 
                month = '0' + month;
            if (day.length < 2) 
                day = '0' + day;

            return [year, month, day].join('-');
        }
        
        $('#treatment_code').focus();
        
        // Event khi giá trị của treatment_code thay đổi
        $('#treatment_code').on('change', function() {
            // Lấy giá trị đã nhập và chỉ giữ lại các chữ số
            var input = $(this).val().replace(/\D/g, '');

            // Điền thêm số 0 vào đầu cho đủ 12 ký tự
            input = input.padStart(12, '0');

            // Cắt chuỗi để chỉ lấy 12 ký tự đầu tiên, trong trường hợp người dùng nhập quá số ký tự cho phép
            input = input.substring(0, 12);

            // Đặt lại giá trị cho input
            $(this).val(input);

            $('#load_data_button').trigger('click');

            $(this).val('');
        });

    });
</script>

@endpush