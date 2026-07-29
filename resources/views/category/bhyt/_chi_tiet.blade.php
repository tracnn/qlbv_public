{{-- Modal xem chi tiet mot ban ghi danh muc — CHI DOC, dung chung cho ca 11 man. --}}
<div class="modal fade" id="modal-chi-tiet" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-chi-tiet-ten">Chi tiết</h4>
            </div>
            <div class="modal-body table-responsive">
                <table class="table table-bordered table-condensed">
                    <tbody id="modal-chi-tiet-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')
<script type="text/javascript">
// Dung bang khoa-gia tri tu JSON tra ve. Khong biet truoc danh muc nao co cot gi, nen
// duyet thang mang 'truong' may chu gui xuong.
function xemChiTiet(loai, id) {
    var url = "{{ url('category/bhyt/chi-tiet') }}/" + loai + "/" + id;

    $.getJSON(url, function (r) {
        $('#modal-chi-tiet-ten').text(r.ten);

        var html = '';
        for (var i = 0; i < r.truong.length; i++) {
            html += '<tr><th style="width:34%">' + $('<div>').text(r.truong[i].nhan).html()
                 + '</th><td>' + $('<div>').text(r.truong[i].gia_tri).html() + '</td></tr>';
        }

        $('#modal-chi-tiet-body').html(html);
        $('#modal-chi-tiet').modal('show');
    }).fail(function (x) {
        alert(x.responseJSON && x.responseJSON.message ? x.responseJSON.message : 'Không tải được chi tiết');
    });
}

// Uy quyen su kien: DataTable ve lai dong moi lan phan trang nen khong bind truc tiep.
$(document).on('click', '.nut-chi-tiet', function () {
    xemChiTiet($(this).data('loai'), $(this).data('id'));
});
</script>
@endpush
