$(window).on('load', function() {
    //$('#loader').hide();
});

$(document).ready(function() {
    // Hiển thị spinner khi một lệnh gọi AJAX bắt đầu
    $(document).ajaxStart(function() {
        $("#loading_center").show();
    });

    // Ẩn spinner khi tất cả lệnh gọi AJAX hoàn tất
    $(document).ajaxStop(function() {
        $("#loading_center").hide();
    });
});