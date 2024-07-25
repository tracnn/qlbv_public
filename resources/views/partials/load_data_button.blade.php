<div class="col-sm-12">
    <div class="form-group row">
        <div class="input-group-append">
            <button type="button" class="btn btn-primary form-control" id="load_data_button">
                <i class="fa fa-refresh" aria-hidden="true"></i> Tải dữ liệu...
            </button>
        </div>
    </div>
</div>
@push('after-scripts-load-data-button')
<script type="text/javascript">
    $(document).ready(function() {
        function validateAndFetchData() {
            var dateRange = $('#date_range').data('daterangepicker');

            if (!dateRange) {
                alert('Vui lòng chọn khoảng thời gian hợp lệ.');
                return;
            }

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');

            // Kiểm tra nếu 'startDate' lớn hơn 'endDate'
            if (startDate > endDate) {
                alert('TỪ NGÀY không được lớn hơn ĐẾN NGÀY.');
                return; // Dừng thực thi hàm nếu điều kiện không hợp lệ
            }
            // Kiểm tra xem hàm fetchData có tồn tại hay không
            if (typeof fetchData === 'function') {
                // Nếu kiểm tra hợp lệ, gọi hàm fetchData
                fetchData(startDate, endDate);
            } else {
                console.error('Hàm fetchData không tồn tại.');
                alert('Có lỗi xảy ra: Hàm fetchData không tồn tại.');
            }
        }
        
        // Gắn sự kiện click với nút load_data_button
        $('#load_data_button').click(function() {
            validateAndFetchData(); // Gọi hàm validateAndFetchData khi nút được nhấp
        });

        // Gọi hàm validateAndFetchData khi trang được tải
        validateAndFetchData();
    });
</script>
@endpush