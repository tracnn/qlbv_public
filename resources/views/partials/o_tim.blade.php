{{-- O tim kiem dung chung cho cac man danh sach co nut partials.load_data_button.

     VI SAO LA PARTIAL chu khong phai mot the <input> chep di chep lai: o nay mang theo
     HANH VI, khong chi markup. Enter phai UY NHIEM sang chinh nut Tai du lieu chu khong
     goi thang fetchData() - xem ghi chu trong khoi script ben duoi. Chep tay doan do sang
     man thu hai la nhan ban hanh vi, dung thu partial sinh ra de chan.

     Bien vao:
       $nhan — nhan hien thi tren label (bat buoc, tieng Viet co dau)
       $rong — lop cot Bootstrap, mac dinh 'col-sm-4'
       $goiY — chu mo trong o, mac dinh 'Nhập rồi nhấn Enter'

     YEU CAU: man dung partial nay PHAI co partials.load_data_button tren cung trang,
     vi Enter bam vao #load_data_button. Khong co nut do thi Enter khong lam gi. --}}
@php
    $rong = isset($rong) ? $rong : 'col-sm-4';
    $goiY = isset($goiY) ? $goiY : 'Nhập rồi nhấn Enter';
@endphp
<div class="{{ $rong }}">
    <div class="form-group row">
        <label for="tim">{{ $nhan }}</label>
        <input class="form-control" type="text" id="tim" placeholder="{{ $goiY }}">
    </div>
</div>

@push('after-scripts-o-tim')
<script type="text/javascript">
$(function () {
    // Enter trong o Tim = bam nut Tai du lieu.
    //
    // UY NHIEM sang chinh nut do chu KHONG goi fetchData() thang. Nut chay qua
    // validateAndFetchData() cua partials.load_data_button - noi kiem khoang ngay va
    // bat/tat bieu tuong quay. Goi thang fetchData() se bo qua ca hai, va o Tim se thanh
    // mot duong tai du lieu THU HAI cu xu khac han nut bam: khong canh bao khi tu-ngay
    // lon hon den-ngay, va khong co dau hieu nao cho biet dang tai.
    $('#tim').on('keydown', function (e) {
        // e.which cho trinh duyet cu, e.key cho trinh duyet moi - giu ca hai.
        if (e.which !== 13 && e.key !== 'Enter') {
            return;
        }

        // O nay khong nam trong <form> nen Enter khong gui gi, nhung chan san: dat no vao
        // mot form o lan sua sau se lam ca trang tai lai va mat het bo loc.
        e.preventDefault();

        $('#load_data_button').click();
    });
});
</script>
@endpush
