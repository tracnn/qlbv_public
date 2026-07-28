{{-- Thanh phan trang cho cac bang nhieu dong cua modal chi tiet.
     Tu dung thay vi dung $rows->links(): ban mac dinh cua Laravel render the <a href>
     that, bam vao se dieu huong ca trang thay vi nap trong modal. --}}
@if($rows->lastPage() > 1)
<div class="text-center" style="margin-top:8px;">
    <ul class="pagination" style="margin:0;">
        <li class="{{ $rows->currentPage() <= 1 ? 'disabled' : '' }}">
            <a href="javascript:void(0);" class="xml3176-trang"
               data-url="{{ $urlTrang }}&page={{ max(1, $rows->currentPage() - 1) }}">&laquo;</a>
        </li>
        <li class="disabled">
            <a href="javascript:void(0);">Trang {{ $rows->currentPage() }}/{{ $rows->lastPage() }}
                &mdash; {{ $rows->total() }} dòng</a>
        </li>
        <li class="{{ $rows->currentPage() >= $rows->lastPage() ? 'disabled' : '' }}">
            <a href="javascript:void(0);" class="xml3176-trang"
               data-url="{{ $urlTrang }}&page={{ min($rows->lastPage(), $rows->currentPage() + 1) }}">&raquo;</a>
        </li>
    </ul>
</div>
@endif
