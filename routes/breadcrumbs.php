<?php

// Home
Breadcrumbs::register('home', function ($breadcrumbs) {
    $breadcrumbs->push('Home', route('home'));
});

// Home > Dashboard
Breadcrumbs::register('dashboard', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Dashboard', route('home'));
});

// Home > Hồ sơ XML
Breadcrumbs::register('bhyt.index', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
    $breadcrumbs->push('Hồ sơ XML', route('bhyt.index'));
});

// Home > Chi tiết hồ sơ
Breadcrumbs::register('bhyt.detailxml', function ($breadcrumbs, $xml1) {
	$breadcrumbs->parent('bhyt.index');
    $breadcrumbs->push('Chi tiết hồ sơ KCB: ' .$xml1->ho_ten , route('bhyt.detailxml'));
});

// Home > Kiểm tra thẻ
Breadcrumbs::register('bhyt.check-card', function ($breadcrumbs) {
	$breadcrumbs->parent('bhyt.index');
    $breadcrumbs->push('Kiểm tra thẻ BHYT', route('bhyt.check-card'));
});

// Home > Nhập thẻ BHYT
Breadcrumbs::register('insurance-card.add-new', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
    $breadcrumbs->push('Nhập thẻ BHYT', route('insurance-card.add-new'));
});

// Home > Danh sách thẻ BHYT
Breadcrumbs::register('insurance-card.index', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
    $breadcrumbs->push('Danh sách thẻ BHYT', route('insurance-card.index'));
});

// Home > Tra cứu thông tin Thuốc - Thầu
Breadcrumbs::register('insurance.medicine-search', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Tra cứu thông tin Thuốc - Thầu', route('insurance.medicine-search'));
});

// Home > Vaccination - Thông tin tiêm chủng
Breadcrumbs::register('vaccination.index', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Vaccination - Thông tin tiêm chủng', route('vaccination.index'));
});

// Home > Emr - Tra soát hồ sơ bệnh án
Breadcrumbs::register('emr.index', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Bệnh án điện tử - Tra soát hồ sơ', route('emr.index'));
});

// Home > Ksk - Khám sức khỏe
Breadcrumbs::register('ksk.index', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Khám sức khỏe - Danh sách', route('ksk.index'));
});

Breadcrumbs::register('ksk.check-emr', function ($breadcrumbs) {
    $breadcrumbs->parent('ksk.index');
    $breadcrumbs->push('Kiểm tra EMR', route('ksk.check-emr'));
});

// Home > Tham số hệ thống
Breadcrumbs::register('system.sys-param', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
    $breadcrumbs->push('Tham số hệ thống', route('system.sys-param'));
});

// Home > Quản trị hệ thống
Breadcrumbs::register('system.sys-man', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Quản trị hệ thống', route('system.sys-man'));
});

// Home > Treatment result - Trả kết quả cho bệnh nhân
Breadcrumbs::register('treatment-result.index', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Trả kết quả - Cho bệnh nhân', route('treatment-result.index'));
});

// Home > Quiz
Breadcrumbs::register('quizlist', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
    $breadcrumbs->push('Danh sách bài thi', route('quizlist'));
});

// Home > Quiz > Add Quiz
Breadcrumbs::register('addquiz', function ($breadcrumbs) {
	$breadcrumbs->parent('quizlist');
    $breadcrumbs->push('Thêm mới bài thi', route('addquiz'));
});

// Home > Quiz > Edit Quiz
Breadcrumbs::register('editquiz', function ($breadcrumbs, $current_quiz) {
	$breadcrumbs->parent('quizlist');
    $breadcrumbs->push($current_quiz->quiz_name, route('editquiz', $current_quiz->id));
});

// Home > Quiz > Result Quiz
Breadcrumbs::register('resultquiz', function ($breadcrumbs, $current_quiz) {
	$breadcrumbs->parent('quizlist');
    $breadcrumbs->push($current_quiz->quiz_name, route('resultquiz', $current_quiz->id));
});

// Home > Quiz > Start quiz
Breadcrumbs::register('startquiz', function ($breadcrumbs, $current_quiz) {
	$breadcrumbs->parent('quizlist');
    $breadcrumbs->push($current_quiz->quiz_name, route('startquiz', $current_quiz->id));
});

Breadcrumbs::register('queue.manage', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Quản lý xếp hàng', route('queue.manage'));
});