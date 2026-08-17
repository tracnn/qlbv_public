{{-- Nhan tieng Viet dung chung cho DataTables.
     Truoc day moi man tu khai mot ban DT_VI giong het nhau (order-check, on-time-result);
     doi chu o mot noi khong keo theo cac noi kia. Man nao can thi @include partial nay
     TRUOC khoi <script> co dung DT_VI. --}}
<script>
var DT_VI = {
  search: 'Tìm:',
  lengthMenu: 'Hiện _MENU_ dòng',
  info: 'Hiển thị _START_-_END_ / _TOTAL_',
  infoEmpty: 'Không có dữ liệu',
  infoFiltered: '(lọc từ _MAX_ dòng)',
  zeroRecords: 'Không tìm thấy',
  emptyTable: 'Không có',
  paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
};
</script>
