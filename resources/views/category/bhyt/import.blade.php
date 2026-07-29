@extends('adminlte::page')

@section('title', 'Nhập khẩu danh mục')

@section('content_header')
<h1>
    Nhập khẩu
    <small>danh mục</small>
</h1>
<ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Dashboard</li>
</ol>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-inline">
            <label for="template_type" style="margin-right:8px;">Tải biểu mẫu:</label>
            <select id="template_type" class="form-control" style="min-width:260px;margin-right:8px;">
                <option value="medicine">Danh mục Thuốc</option>
                <option value="medical_supply">Danh mục Vật tư y tế</option>
                <option value="service">Danh mục Dịch vụ kỹ thuật</option>
                <option value="medical_staff">Danh mục Nhân viên y tế</option>
                <option value="department_bed">Danh mục Khoa/Phòng/Giường</option>
                <option value="equipment">Danh mục Trang thiết bị</option>
                <option value="administrative_unit">Danh mục Đơn vị hành chính</option>
                <option value="medical_organization">Danh mục Cơ sở y tế</option>
                <option value="job_categories">Danh mục Nghề nghiệp</option>
                <option value="icd10">Danh mục ICD-10</option>
                <option value="icd_yhct">Danh mục ICD-YHCT</option>
            </select>
            <button type="button" id="btn_download_template" class="btn btn-success">
                <i class="fa fa-download"></i> Tải biểu mẫu
            </button>
            <p class="help-block" style="margin-top:6px;">
                Cột bôi <span style="background:#FFF2CC;padding:0 6px;">vàng</span> là bắt buộc.
                Cột bôi <span style="background:#DDEBF7;padding:0 6px;">xanh</span> là mã cơ sở khám chữa bệnh —
                không bắt buộc, bỏ trống thì danh mục dùng chung cho mọi cơ sở.
                Điền dữ liệu từ dòng 2 rồi tải lên ở khung bên dưới.
            </p>
        </div>
        <hr>
        <div class="form-inline">
            <label for="ma_cskcb" style="margin-right:8px;">Cơ sở khám chữa bệnh:</label>
            <select id="ma_cskcb" class="form-control" style="min-width:360px;">
                <option value="">— Dùng chung cho mọi cơ sở —</option>
                @foreach ($danhSachCoSo as $ma => $nhan)
                    <option value="{{ $ma }}">{{ $nhan }}</option>
                @endforeach
            </select>
            <p class="help-block" style="margin-top:6px;">
                Chỉ áp dụng cho danh mục <b>Thuốc</b>, <b>Vật tư y tế</b> và <b>Dịch vụ kỹ thuật</b> — đây là ba
                danh mục BHXH cấp riêng cho từng cơ sở. Dòng nào trong tệp đã có sẵn cột <code>MA_CSKCB</code>
                thì lấy theo tệp; ô này chỉ điền cho những dòng bỏ trống.
                @if (empty($danhSachCoSo))
                    <br><span class="text-danger">Chưa đọc được danh sách cơ sở từ HIS.</span>
                @endif
            </p>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <form action="{{ route('category-bhyt.import') }}" method="POST" class="dropzone" id="my-dropzone">
            {{ csrf_field() }}
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div id="uploadStatus" class="text-center h3 text-primary">Hồ sơ đã tải lên</div>
        <div class="table-responsive">
            <table class="table display table-hover responsive" id="fileListTable">
                <thead>
                    <tr>
                        <th>Tên tệp</th>
                        <th>Kích thước</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>    
    </div>
</div>

@if (auth()->check() && auth()->user()->hasRole('superadministrator'))
<div class="panel panel-danger">
    <div class="panel-heading"><strong>Xoá toàn bộ một danh mục</strong></div>
    <div class="panel-body">
        <div class="alert alert-warning" style="margin-bottom:12px;">
            Xoá xong mà <strong>chưa nhập lại</strong> thì XML3176 và kiểm tra y lệnh sẽ báo
            <strong>mọi mã đều sai</strong> — đã đo được ba danh mục rỗng sinh khoảng 36.100
            vi phạm giả. Nên xoá và nhập lại liền tay.
        </div>

        <div class="form-inline" style="margin-bottom:10px;">
            <label style="margin-right:8px;">Danh mục:</label>
            <select id="xoa_loai" class="form-control" style="min-width:260px;margin-right:8px;">
                @foreach (config('danh_muc_bhyt') as $ma => $x)
                    <option value="{{ $ma }}" data-theo-co-so="{{ $x['theo_co_so'] ? 1 : 0 }}">{{ $x['ten'] }}</option>
                @endforeach
            </select>

            <span id="xoa_co_so_wrap" style="display:none;">
                <label style="margin-right:8px;">Cơ sở:</label>
                <select id="xoa_ma_cskcb" class="form-control" style="min-width:220px;margin-right:8px;">
                    <option value="">Tất cả cơ sở</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
            </span>

            <button type="button" id="xoa_dem" class="btn btn-default">Đếm số dòng sẽ xoá</button>
        </div>

        <div id="xoa_ket_qua" style="margin-bottom:10px;"></div>

        <div class="form-inline">
            <label style="margin-right:8px;">Gõ <code>XOA</code> để xác nhận:</label>
            <input type="text" id="xoa_xac_nhan" class="form-control" style="width:120px;margin-right:8px;">
            <button type="button" id="xoa_thuc_hien" class="btn btn-danger" disabled>Xoá</button>
        </div>
    </div>
</div>
@endif
@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script>
    Dropzone.autoDiscover = false; // Disable auto discover to prevent multiple instances
    // Khởi tạo Dropzone
    var myDropzone = new Dropzone("#my-dropzone", {
        paramName: "import_file", // Tên của field gửi lên server
        maxFilesize: 10, // MB
        acceptedFiles: ".xls,.xlsx", // Giới hạn các loại file
        timeout: 300000, // 5 phút
        uploadMultiple: false, // Upload từng file một, để đơn giản hóa việc xử lý
        parallelUploads: 1, // Chỉ tải lên một file tại một thời điểm
        previewTemplate: '<div></div>', // Tắt giao diện preview mặc định
        init: function () {
            // Gom ket qua cua moi tep trong mot lan tai len.
            var tong = { nhap: 0, capNhat: 0, khongDoi: 0, boQua: 0, loi: 0, dongLoi: [] };

            function tongHopKetQua(kq) {
                tong.nhap += kq.so_da_nhap || 0;
                tong.capNhat += kq.so_da_cap_nhat || 0;
                tong.khongDoi += kq.so_khong_doi || 0;
                tong.boQua += kq.so_bo_qua || 0;
                tong.loi += kq.so_loi || 0;
                (kq.dong_loi || []).forEach(function (x) {
                    // Hop thoai chi hien 20 dong dau; ban day tai bang nut Chi tiet.
                    if (tong.dongLoi.length < 20) { tong.dongLoi.push(x); }
                });
            }

            // Xuat danh sach dong hong ra .xlsx. Gui lai chinh du lieu da nhan trong JSON
            // nen may chu khong phai luu gi; dung form POST de trinh duyet tu tai tep ve.
            function taiChiTietLoi(tenTep, dongLoi) {
                var f = document.createElement("form");

                f.method = "POST";
                f.action = "{{ route('category-bhyt.import-loi') }}";
                f.style.display = "none";

                function them(ten, giaTri) {
                    var o = document.createElement("input");

                    o.type = "hidden";
                    o.name = ten;
                    o.value = giaTri;
                    f.appendChild(o);
                }

                them("_token", "{{ csrf_token() }}");
                them("ten_tep", tenTep);
                them("dong_loi", JSON.stringify(dongLoi));

                document.body.appendChild(f);
                f.submit();
                document.body.removeChild(f);
            }

            function ganNutTaiLoi(file, kq) {
                if (!kq || !kq.dong_loi || !kq.dong_loi.length) {
                    return;
                }

                var row = findFileRow(file.name);
                if (!row) { return; }

                var nut = document.createElement('button');
                nut.className = 'btn btn-xs btn-warning';
                nut.style.marginLeft = '8px';
                nut.innerHTML = '<i class="fa fa-file-excel-o"></i> Chi tiết (' + kq.dong_loi.length + ')';
                nut.onclick = function () { taiChiTietLoi(file.name, kq.dong_loi); };

                row.cells[2].appendChild(nut);
            }

            function moTaKetQua() {
                var h = '<div style="text-align:left">'
                    + 'Đã thêm: <b>' + tong.nhap + '</b><br>'
                    + 'Cập nhật: <b>' + tong.capNhat + '</b><br>'
                    + 'Không đổi: <b>' + tong.khongDoi + '</b><br>'
                    + 'Bỏ qua (thiếu trường bắt buộc): <b>' + tong.boQua + '</b><br>'
                    + 'Lỗi: <b>' + tong.loi + '</b>';

                if (tong.dongLoi.length) {
                    h += '<hr><div style="font-size:12px">Bấm nút <b>Chi tiết</b> ở từng tệp để tải danh sách đầy đủ.</div>';
                    h += '<div style="max-height:200px;overflow:auto;font-size:12px">';
                    tong.dongLoi.forEach(function (x) {
                        h += 'Dòng ' + x.dong + ' (' + (x.loai === 'bo_qua' ? 'bỏ qua' : 'lỗi') + '): '
                            + $('<div>').text(x.ly_do).html() + '<br>';
                    });
                    h += '</div>';
                }

                return h + '</div>';
            }

            // Gui kem co so da chon; doc tai thoi diem gui de doi lua chon giua chung
            // cac tep van co tac dung.
            this.on("sending", function (file, xhr, formData) {
                formData.append("ma_cskcb", document.getElementById('ma_cskcb').value);
            });

            var totalFiles = 0;
            var uploadedFiles = 0;
            var isUploading = false;

            // Khi file được thêm vào Dropzone
            this.on("addedfile", function (file) {
                totalFiles++;
                updateProgress();
                addFileToTable(file);
                isUploading = true;
            });

            // Khi upload thành công
            this.on("success", function (file, response) {
                uploadedFiles++;
                updateProgress();
                updateFileStatus(file, 'success', response.message || "Tải lên thành công");

                var kq = response.ket_qua || null;
                if (kq) {
                    tongHopKetQua(kq);
                    ganNutTaiLoi(file, kq);
                }

                if (uploadedFiles === totalFiles) {
                    isUploading = false;

                    // Truoc day luon bao 'Thanh cong' du co the nhap 0 dong.
                    var coGhi = tong.nhap > 0 || tong.capNhat > 0;

                    Swal.fire({
                        title: coGhi ? 'Đã nhập xong' : 'Không ghi được dòng nào',
                        html: moTaKetQua(),
                        icon: coGhi ? 'success' : 'warning',
                    });
                }
            });

            // Khi gặp lỗi trong quá trình upload
            this.on("error", function (file, response) {
                updateFileStatus(file, 'error', response.message || "Lỗi khi tải lên");
                isUploading = false;
            });

            // Cảnh báo khi người dùng cố gắng thoát trang trong quá trình upload
            window.onbeforeunload = function () {
                if (isUploading) {
                    return "Hồ sơ đang được tải lên. Bạn có chắc chắn muốn rời khỏi trang này?";
                }
            };

            // Cập nhật tiến trình tải lên
            function updateProgress() {
                var progressText = uploadedFiles + '/' + totalFiles + ' Hồ sơ đã tải lên';
                document.getElementById('uploadStatus').innerText = progressText;
            }

            // Thêm file vào bảng trạng thái
            function addFileToTable(file) {
                var table = document.getElementById('fileListTable').getElementsByTagName('tbody')[0];
                var existingRow = findFileRow(file.name);
                if (existingRow) {
                    updateFileRow(existingRow, file, 'pending');
                } else {
                    var newRow = table.insertRow();
                    var nameCell = newRow.insertCell(0);
                    var sizeCell = newRow.insertCell(1);
                    var statusCell = newRow.insertCell(2);

                    nameCell.innerText = file.name;
                    sizeCell.innerText = (file.size / 1024).toFixed(2) + ' KB'; // Hiển thị kích thước theo KB
                    statusCell.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; // Thêm icon spinner trong khi upload
                    statusCell.classList.add('status');
                }
            }

            // Cập nhật trạng thái của file (thành công hoặc lỗi)
            function updateFileStatus(file, status, message = '') {
                var row = findFileRow(file.name);
                if (row) {
                    updateFileRow(row, file, status, message);
                }
            }

            // Tìm hàng tương ứng với tên file
            function findFileRow(fileName) {
                var rows = document.getElementById('fileListTable').getElementsByTagName('tbody')[0].rows;
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].cells[0].innerText === fileName) {
                        return rows[i];
                    }
                }
                return null;
            }

            // Cập nhật thông tin của hàng tương ứng với trạng thái tải lên
            function updateFileRow(row, file, status, message = '') {
                if (status === 'success') {
                    row.cells[2].innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i> ' + message;
                } else if (status === 'error') {
                    row.cells[2].innerHTML = '<i class="fa fa-times-circle" style="color: red;"></i> ' + message;
                } else if (status === 'pending') {
                    row.cells[2].innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                }
            }
        }
    });
</script>
<script>
    document.getElementById('btn_download_template').addEventListener('click', function () {
        var type = document.getElementById('template_type').value;
        window.location.href = "{{ route('category-bhyt.import-template') }}?type=" + encodeURIComponent(type);
    });
</script>
<script>
    // Khoi xoa danh muc — chi ton tai voi superadministrator nen kiem su ton tai truoc.
    if (document.getElementById('xoa_loai')) {
        function xoaThamSo() {
            var $l = $('#xoa_loai option:selected');
            return {
                loai: $l.val(),
                ma_cskcb: $l.data('theo-co-so') == 1 ? $('#xoa_ma_cskcb').val() : ''
            };
        }

        function xoaDatLaiNut() {
            $('#xoa_thuc_hien').prop('disabled', $('#xoa_xac_nhan').val().trim() !== 'XOA');
        }

        $('#xoa_loai').on('change', function () {
            $('#xoa_co_so_wrap').toggle($('#xoa_loai option:selected').data('theo-co-so') == 1);
            $('#xoa_ket_qua').html('');
        }).trigger('change');

        $('#xoa_ma_cskcb').on('change', function () { $('#xoa_ket_qua').html(''); });
        $('#xoa_xac_nhan').on('input', xoaDatLaiNut);

        $('#xoa_dem').on('click', function () {
            $.getJSON("{{ route('category-bhyt.xoa-danh-muc-dem') }}", xoaThamSo(), function (r) {
                $('#xoa_ket_qua').html('<div class="alert alert-info">Sẽ xoá <strong>'
                    + r.so_dong + '</strong> dòng.</div>');
            }).fail(function (x) {
                $('#xoa_ket_qua').html('<div class="alert alert-danger">'
                    + ((x.responseJSON && x.responseJSON.message) || 'Lỗi') + '</div>');
            });
        });

        $('#xoa_thuc_hien').on('click', function () {
            var d = xoaThamSo();
            d._token = "{{ csrf_token() }}";
            d.xac_nhan = $('#xoa_xac_nhan').val().trim();

            $.post("{{ route('category-bhyt.xoa-danh-muc') }}", d, function (r) {
                $('#xoa_ket_qua').html('<div class="alert alert-success">Đã xoá <strong>'
                    + r.so_dong + '</strong> dòng.</div>');
                $('#xoa_xac_nhan').val('');
                xoaDatLaiNut();
            }, 'json').fail(function (x) {
                $('#xoa_ket_qua').html('<div class="alert alert-danger">'
                    + ((x.responseJSON && x.responseJSON.message) || 'Lỗi') + '</div>');
            });
        });
    }
</script>
@endpush