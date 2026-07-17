<!-- resources/views/partials/xml_error_catalog.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="xml3176_error_catalog">Lỗi XML</label>
        <select id="xml3176_error_catalog" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-xml3176-error-catalog')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-bhyt.fetch-xml3176-error-catalog") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#xml3176_error_catalog');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    var optionText = catalog.xml + ' / ' + catalog.error_name;
                    var optionElement = $('<option></option>').val(catalog.id).text(optionText);

                    if (catalog.critical_error) {
                        optionElement.attr('data-icon', 'fa fa-exclamation-triangle text-danger');
                    }

                    select.append(optionElement);
                });

                select.select2({
                    templateResult: formatState,
                    templateSelection: formatState,
                    width: '100%'
                });

                // ── Drill-down từ dashboard XML3176 ──────────────────────────
                // Danh mục lỗi nạp bất đồng bộ, nên phải áp giá trị từ URL
                // TẠI ĐÂY - sau khi đã có options. Nếu áp sớm hơn thì
                // .val() im lặng thất bại (chưa có <option> tương ứng), và
                // select.empty() ở trên cũng sẽ xoá mất lựa chọn.
                // Nhánh này chỉ chạy khi URL có param -> không ảnh hưởng
                // hành vi mặc định của màn hình.
                applyXml3176ErrorCatalogFromUrl(select);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    });

    // Áp giá trị 'xml3176_error_catalog' từ URL sau khi options đã sẵn sàng.
    // Kiểm chứng option thực sự tồn tại: nếu không, cảnh báo rõ ràng thay vì
    // im lặng để danh sách hiện nhiều hơn con số đã bấm trên dashboard.
    function applyXml3176ErrorCatalogFromUrl(select) {
        if (!window.URLSearchParams) { return; }

        var wanted = new URLSearchParams(window.location.search).get('xml3176_error_catalog');
        if (!wanted) { return; }

        // trigger('change') để select2 cập nhật phần hiển thị.
        select.val(wanted).trigger('change');

        if (select.val() !== wanted) {
            // Không tìm thấy mã lỗi trong danh mục -> bộ lọc KHÔNG được áp.
            if (typeof window.xml3176ShowFilterWarning === 'function') {
                window.xml3176ShowFilterWarning(
                    'Không tìm thấy mã lỗi "' + $('<div>').text(wanted).html() + '" trong danh mục. ' +
                    'Danh sách đang <strong>KHÔNG lọc theo mã lỗi</strong>, nên số dòng có thể ' +
                    'nhiều hơn con số đã bấm trên dashboard.'
                );
            } else {
                alert('Không tìm thấy mã lỗi "' + wanted + '" trong danh mục. ' +
                      'Danh sách đang KHÔNG lọc theo mã lỗi, nên số dòng có thể ' +
                      'nhiều hơn con số đã bấm trên dashboard.');
            }
            return; // không reload: không có gì mới để áp
        }

        // Nạp lại bảng để áp bộ lọc vừa set. Lần fetch đầu tiên (từ
        // partials/load_data_button) thường đã chạy xong TRƯỚC khi AJAX
        // danh mục trả về, nên lần đó chưa có bộ lọc này.
        // Nếu bảng chưa khởi tạo, không cần làm gì - lần fetchData() sau đó
        // sẽ tự đọc giá trị select đã set ở trên.
        if (typeof window.xml3176ReloadTable === 'function') {
            window.xml3176ReloadTable();
        }
    }

    function formatState(state) {
        if (!state.id) {
            return state.text;
        }
        var $state = $(
            '<span>' + state.text + (state.element.getAttribute('data-icon') ? ' <i class="' + state.element.getAttribute('data-icon') + '" aria-hidden="true"></i>' : '') + '</span>'
        );
        return $state;
    }
</script>
@endpush