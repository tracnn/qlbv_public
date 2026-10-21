@php
    $groupedErrors = $xml1->Xml3176ErrorResult->sortBy('xml')->groupBy('xml');

    // Ho so nhieu loi (vd 2.792 dong) truoc day sinh ra ~2,7 MB HTML: moi dong lap lai
    // nguyen van "Huong xu ly" (cau SQL dai) va trinh duyet phai dung du 2.792 x 8 o
    // truoc khi DataTables phan trang. Nay chi gui du lieu tho dang JSON; ten loi va
    // huong xu ly gui MOT lan cho moi ma loi; DataTables deferRender chi dung DOM cho
    // trang dang xem.
    $tenLoi = [];
    $goiY = [];
    $duLieu = [];
    foreach ($groupedErrors as $xml => $errors) {
        $dong = [];
        foreach ($errors as $error) {
            $ma = $error->error_code;
            if (!array_key_exists($ma, $tenLoi)) {
                // Ma loi khong co trong danh muc thi quan he la null; truoc
                // day truy cap thang ->error_name se lam chet ca trang.
                $tenLoi[$ma] = optional($error->Xml3176ErrorCatalog)->error_name ?: $ma;
                $goiY[$ma] = getFormattedSuggestion($ma, ['ma_dieu_tri' => $error->ma_lk ?? 'N/A']);
            }
            // [ma loi, nghiem trong, xml, stt, ngay_yl tho, ngay_yl hien thi,
            //  ngay_kq tho, ngay_kq hien thi, mo ta]. Gia tri tho de sap xep dung thu tu
            // thoi gian, chuoi dd/mm/yyyy thi sap theo chu.
            $dong[] = [
                $ma,
                $error->critical_error ? 1 : 0,
                $error->xml,
                $error->stt,
                (string) $error->ngay_yl,
                strtodatetime($error->ngay_yl),
                (string) $error->ngay_kq,
                strtodatetime($error->ngay_kq),
                (string) $error->description,
            ];
        }
        $duLieu[$xml] = $dong;
    }
@endphp

{{-- Noi dung tab, khong kem lop boc .tab-pane: khung vo trong detail-xml.blade.php
     so huu id va class, endpoint chi tra ve phan ben trong. --}}

    @if($groupedErrors->isNotEmpty())
    <ul class="nav nav-tabs xml3176-loi-tabs">
        @foreach($groupedErrors as $xml => $errors)
            @php
                $hasCriticalError = $errors->contains(function($error) {
                    return $error->critical_error;
                });
            @endphp
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_{{ $xml }}">
                    Lỗi: {{ $xml }} <span class="badge">{{ $errors->count() }}</span>
                    @if($hasCriticalError)
                        <i class="fa fa-exclamation-triangle text-danger" aria-hidden="true" title="Critical Error"></i>
                    @else
                        <i class="fa fa-exclamation-triangle text-primary" aria-hidden="true" title="Warning Error"></i>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedErrors as $xml => $errors)
            <div id="tab_{{ $xml }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <table id="xmlErrorChecks_{{ $xml }}" class="table table-hover responsive datatable-xml-errors"
                       data-nhom="{{ $xml }}" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Error Code</th>
                            <th>Loại</th>
                            <th>XML</th>
                            <th>STT</th>
                            <th>Ngày y lệnh</th>
                            <th>Ngày kết quả</th>
                            <th>Description</th>
                            <th>Hướng xử lý</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endforeach
    </div>

    {{-- JSON trong the script kieu du lieu, doc bang JSON.parse: khong chay nhu ma.
         JSON_HEX_TAG doi "<" ">" thanh < > nen mo ta loi (den tu tep XML
         ben ngoai) chua "</script>" cung khong thoat ra duoc. --}}
    <script type="application/json" class="xml3176-loi-data">{!! json_encode(
        ['tenLoi' => $tenLoi, 'goiY' => $goiY, 'duLieu' => $duLieu],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) !!}</script>
    @else
    <p>Không có lỗi XML nào.</p>
    @endif

<script>
    (function () {
        var $nguon = $('script.xml3176-loi-data').last();
        if (!$nguon.length) { return; }
        var goi = JSON.parse($nguon.text());
        var $vung = $nguon.parent();

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Hien thi chuoi da dinh dang, sap xep theo gia tri tho (yyyymmddhhmm).
        function cotNgay(iTho, iHien) {
            return {
                data: null,
                render: function (r, type) {
                    return type === 'sort' || type === 'type' ? r[iTho] : esc(r[iHien]);
                }
            };
        }

        function khoiTao(table) {
            if ($.fn.DataTable.isDataTable(table)) { return; }
            var rows = goi.duLieu[$(table).data('nhom')] || [];
            $(table).DataTable({
                data: rows,
                // Chi dung DOM cho trang dang xem, khong phai ca nghin dong.
                deferRender: true,
                responsive: true,
                autoWidth: false,
                paging: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                searching: true,
                ordering: true,
                columns: [
                    { data: 0, render: function (d, type) {
                        return type === 'display' ? esc(goi.tenLoi[d]) : goi.tenLoi[d] + ' ' + d;
                    } },
                    { data: 1, render: function (d, type) {
                        if (type !== 'display') { return d; }
                        return d
                            ? '<i class="fa fa-exclamation-triangle text-danger" aria-hidden="true" title="Critical Error"></i>'
                            : '<i class="fa fa-exclamation-triangle text-primary" aria-hidden="true" title="Warning Error"></i>';
                    } },
                    { data: 2, render: function (d) { return esc(d); } },
                    { data: 3 },
                    cotNgay(4, 5),
                    cotNgay(6, 7),
                    { data: 8, render: function (d, type) {
                        return type === 'display' ? esc(d).replace(/\r?\n/g, '<br>') : d;
                    } },
                    { data: 0, orderable: false, render: function (d, type) {
                        return type === 'display' ? esc(goi.goiY[d]) : goi.goiY[d];
                    } }
                ]
            });
        }

        // Chi khoi tao bang dang hien; cac nhom khac khoi tao khi bam vao tab cua no
        // (bang an thi responsive cung tinh sai do rong cot).
        $vung.find('.tab-pane.active table.datatable-xml-errors').each(function () { khoiTao(this); });
        $vung.find('.xml3176-loi-tabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
            var table = $($(this).attr('href')).find('table.datatable-xml-errors')[0];
            if (!table) { return; }
            if ($.fn.DataTable.isDataTable(table)) {
                $(table).DataTable().columns.adjust().responsive.recalc();
            } else {
                khoiTao(table);
            }
        });
    })();
</script>
