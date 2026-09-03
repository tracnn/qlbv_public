<!-- resources/views/partials/xml3176_ma_khoa_filter.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="ma_khoa">Khoa (XML1)</label>
        <select id="ma_khoa" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div>
</div>

@push('after-scripts-xml3176-ma-khoa')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("bhyt.xml3176.department-options") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#ma_khoa');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, khoa) {
                    var text = khoa.ten_khoa ? (khoa.ma_khoa + ' - ' + khoa.ten_khoa) : khoa.ma_khoa;
                    select.append($('<option></option>').val(khoa.ma_khoa).text(text));
                });
                select.select2({ width: '100%' });

                // ── Drill-down "Lỗi theo khoa" từ dashboard XML3176 ──────────
                // Danh sách khoa nạp bất đồng bộ, nên áp giá trị từ URL TẠI ĐÂY -
                // sau khi đã có options. Nhánh chỉ chạy khi URL có param ma_khoa,
                // không ảnh hưởng hành vi mặc định của màn hình.
                applyXml3176MaKhoaFromUrl(select);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error (ma_khoa): " + textStatus + ' : ' + errorThrown);
            }
        });
    });

    // Áp giá trị 'ma_khoa' từ URL sau khi options đã sẵn sàng. Kiểm chứng option
    // thực sự tồn tại: nếu khoa không có trong danh mục thì cảnh báo rõ ràng thay
    // vì im lặng để danh sách hiện nhiều hơn con số đã bấm trên dashboard.
    function applyXml3176MaKhoaFromUrl(select) {
        if (!window.URLSearchParams) { return; }

        var wanted = new URLSearchParams(window.location.search).get('ma_khoa');
        if (!wanted) { return; }

        select.val(wanted).trigger('change');

        if (select.val() !== wanted) {
            var msg = 'Không tìm thấy khoa "' + wanted + '" trong danh mục. ' +
                      'Danh sách đang KHÔNG lọc theo khoa, nên số dòng có thể ' +
                      'nhiều hơn con số đã bấm trên dashboard.';
            if (typeof window.xml3176ShowFilterWarning === 'function') {
                window.xml3176ShowFilterWarning(
                    'Không tìm thấy khoa "' + $('<div>').text(wanted).html() + '" trong danh mục. ' +
                    'Danh sách đang <strong>KHÔNG lọc theo khoa</strong>, nên số dòng có thể ' +
                    'nhiều hơn con số đã bấm trên dashboard.'
                );
            } else {
                alert(msg);
            }
            return;
        }

        // Nạp lại bảng để áp bộ lọc vừa set (lần fetch đầu thường chạy TRƯỚC khi
        // AJAX danh mục khoa trả về). Nếu bảng chưa khởi tạo thì lần fetchData()
        // sau sẽ tự đọc giá trị select đã set ở trên.
        if (typeof window.xml3176ReloadTable === 'function') {
            window.xml3176ReloadTable();
        }
    }
</script>
@endpush
