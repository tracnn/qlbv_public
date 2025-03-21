<div class="col-sm-12">
    <div class="form-group row">
        <div class="input-group-append">
            <button type="button" class="btn btn-primary form-control" id="load_data_button">
                <i class="fa fa-refresh	fa-spin" aria-hidden="true" style="display:none;"></i> Tải dữ liệu...
            </button>
        </div>
    </div>
</div>
@push('after-scripts-load-data-button-without-date-range')
<script type="text/javascript">
    $(document).ready(function() {
		var isFetchDataLoading = false; // Đặt cờ khi fetchData được gọi
		// Sự kiện toàn cục cho khi bất kỳ yêu cầu AJAX nào bắt đầu
		$(document).ajaxStart(function() {
			if (isFetchDataLoading) {
				startLoading(); // Chỉ hiển thị loading khi fetchData đang gọi
			}
		});

		// Sự kiện toàn cục cho khi tất cả các yêu cầu AJAX hoàn tất
		$(document).ajaxStop(function() {
			if (isFetchDataLoading) {
				stopLoading(); // Chỉ ẩn loading khi fetchData đã hoàn tất
				isFetchDataLoading = false; // Reset trạng thái
                //enableButton();
			}
		});
		
        function startLoading() {
            $('#load_data_button i').show(); // Hiển thị biểu tượng refresh
        }

        function stopLoading() {
            $('#load_data_button i').hide(); // Ẩn biểu tượng refresh
        }
		
        function disableButton() {
            $('#load_data_button').attr('disabled', true); // Vô hiệu hóa nút
        }

        function enableButton() {
            $('#load_data_button').attr('disabled', false); // Kích hoạt lại nút
        }
        // Gắn sự kiện click với nút load_data_button
        $('#load_data_button').click(function() {
            // Kiểm tra xem hàm fetchData có tồn tại hay không
            if (typeof fetchData === 'function') {
                //disableButton();
                isFetchDataLoading = true;
                // Nếu kiểm tra hợp lệ, gọi hàm fetchData
                fetchData();
            } else {
                console.error('Hàm fetchData không tồn tại.');
                alert('Có lỗi xảy ra: Hàm fetchData không tồn tại.');
            }
        });

    });
</script>
@endpush