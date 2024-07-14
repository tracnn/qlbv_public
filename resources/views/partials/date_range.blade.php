@push('after-styles')
<!-- Include daterangepicker CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

<div class="col-sm-12">
    <div class="form-group row">
        <div class="col-sm-2">
            <div class="form-group row">
                <label for="default_range">Kiểu lọc</label>
                <select id="default_range" class="form-control select2">
                    <option value="day">Ngày</option>
                    <option value="week">Tuần</option>
                    <option value="month">Tháng</option>
                    <option value="year">Năm</option>
                </select>
            </div> 
        </div>
        @if (isset($showDateType) ? $showDateType : false)
            <div class="col-sm-2">
                <div class="form-group row">
                    <label for="date_type">Lọc theo</label>
                    <select id="date_type" class="form-control">
                        <option value="date_in">Ngày vào</option>
                        <option value="date_out">Ngày ra</option>
                        <option value="date_payment">Ngày thanh toán</option>
                        <option value="date_intruction">Ngày chỉ định</option>
                        <option value="date_create">Ngày tạo</option>
                        <option value="date_update">Ngày sửa</option>
                    </select>
                </div> 
            </div>
        @endif
        <div class="col-sm-4">
            <div class="form-group row">
                <label for="date_range">Chọn khoảng thời gian</label>
                <input class="form-control" type="text" id="date_range">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group row">
                <label for="export_xlsx">XLS</label>
                <button type="button" class="btn btn-success form-control" id="export_xlsx">Export...</button>
            </div>
        </div>
    </div>
</div>

@push('after-scripts-date-range')

<!-- Include daterangepicker JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        // Truyền biến từ Blade view vào JavaScript
         var showDateType = @json(isset($showDateType) ? $showDateType : false);

        // Kiểm tra nếu showDateType là true
        if (showDateType) {
            // Get saved default range from localStorage or set to 'day' if not found
            var savedDateType = localStorage.getItem('date_type') || 'date_in';
            
            // Set the select value to the saved range
            $('#date_type').val(savedDateType);

            $('#date_type').select2();

            $('#date_type').change(function() {
                var selectedDateType = $(this).val();
                localStorage.setItem('date_type', selectedDateType);
            });
        }

        // Set default dates when the page loads
        setDefaultDates('day');

        // Update default dates and save to localStorage when the user changes the selection
        $('#default_range').change(function() {
            var selectedRange = $(this).val();
            //localStorage.setItem('default_range', selectedRange);
            setDefaultDates(selectedRange);
        });

        // Function to set default dates based on the selected range
        function setDefaultDates(range) {
            var startDate, endDate;
            var today = moment().startOf('day');
            
            if (range === 'day') {
                startDate = today.clone().startOf('day');
                endDate = today.clone().endOf('day');
            } else if (range === 'week') {
                startDate = today.clone().startOf('isoWeek');
                endDate = today.clone().endOf('isoWeek');
            } else if (range === 'month') {
                startDate = today.clone().startOf('month');
                endDate = today.clone().endOf('month');
            } else if (range === 'year') {
                startDate = today.clone().startOf('year');
                endDate = today.clone().endOf('year');
            }

            $('#date_range').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                    firstDay: 1
                }
            });
        }
    });
</script>
@endpush