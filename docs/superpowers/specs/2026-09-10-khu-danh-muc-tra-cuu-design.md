# Khu vực "Danh mục tra cứu" — màn hình xem danh mục lái bằng cấu hình

**Ngày:** 2026-09-10
**Module:** Quản lý danh mục
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Dựng một khu vực màn hình **tách khỏi DM BHYT** để tra cứu các danh mục nội bộ, với màn hình **lái bằng cấu hình**: thêm một danh mục về sau chỉ là **thêm một mục trong sổ đăng ký**, không phải thêm controller, view, route hay mục menu.

Danh mục đầu tiên: **DVKT bắt buộc có mã máy** (`dvkt_can_ma_may`), hiện chưa có chỗ nào xem được.

## 2. Hiện trạng (đã kiểm chứng trên mã)

| Khu vực | Thực tế |
|---|---|
| **DM BHYT** | `Category\CategoryBHYTController` — **11+ danh mục trong một controller**, mỗi danh mục một cặp `index/fetch` và một view riêng. Menu: submenu "BHYT". Quyền `category-manager`. Đây là mô hình cần tránh lặp lại |
| **`danh-muc/`** | `Category\Manager\CategoryController`, 3 màn (DVKT có điều kiện, Thuốc có điều kiện, DM Khoa phòng). Quyền **`superadministrator`**. Menu phẳng, không gom nhóm |
| Generic cũ `category/{category}` | Có route nhưng ánh xạ bằng `switch` cứng cho 5 model hệ thống cũ, phân trang Blade, đòi model cài `getSearchResults()`. Không phải nền để xây tiếp |

**Hai cạm bẫy phải né:**

1. **Route bắt-tất `{category}`** nằm trong prefix `category/` (`routes/web.php:371`). Mọi route **một đoạn** thêm vào prefix đó sẽ bị nuốt. → Khu mới dùng prefix riêng.
2. **Thứ tự nạp config.** `config/adminlte.php` đã ghi chú sẵn cảnh báo này cho `organization`: Laravel nạp các tệp config theo thứ tự chữ cái, `adminlte` chạy **trước**, nên `config('danh_muc_tra_cuu')` gọi bên trong `adminlte.php` sẽ trả `null` — **menu rỗng mà không ai phát hiện**. → Phải `require` thẳng tệp config.

## 3. Phạm vi — đã chốt với người dùng

**Trong phạm vi:**
- Sổ đăng ký danh mục + controller generic + một view generic + route + menu sinh tự động.
- Đăng ký danh mục đầu tiên: `dvkt_can_ma_may`.

**Ngoài phạm vi (quyết định đã chốt):**
- **Chỉ xem, không sửa.** Các danh mục ở đây nạp từ tệp theo kiểu *thay trọn bộ*; sửa tay trên màn hình sẽ bị lần nạp sau xoá sạch. Cho sửa là dựng sẵn một cái bẫy.
- **Không đụng 3 màn `danh-muc/` sẵn có.** Chuyển chúng sang khu mới đồng nghĩa nới quyền từ `superadministrator` xuống `category-manager` — một quyết định về an toàn, cần bàn riêng.
- **Không đụng** khu DM BHYT và generic cũ.

**Quyền truy cập:** `checkrole:category-manager` — cùng quyền với các màn DM BHYT, để người đang quản danh mục xem được ngay mà không phải cấp quyền mới.

## 4. Kiến trúc

### 4.1 Sổ đăng ký `config/danh_muc_tra_cuu.php`

Nguồn sự thật duy nhất cho cả màn hình lẫn menu:

```php
return [
    'dvkt_can_ma_may' => [
        'ten'      => 'DVKT cần mã máy',
        'model'    => App\Models\BHYT\DvktCanMaMay::class,
        'cot'      => [
            'ma_dvkt'   => 'Mã DVKT',
            'ten_dvkt'  => 'Tên DVKT',
            'is_active' => 'Đang dùng',
        ],
        'cot_tim'  => ['ma_dvkt', 'ten_dvkt'],
        'sap_xep'  => ['ma_dvkt', 'asc'],
    ],
];
```

Khoá mảng (`dvkt_can_ma_may`) là **slug trên URL**.

### 4.2 Controller `Category\DanhMucTraCuuController`

Đúng hai hàm:

- `index($khoa)` — tra sổ đăng ký; khoá lạ thì `abort(404)`; trả view kèm cấu hình của danh mục đó.
- `fetch($khoa)` — tra sổ; khoá lạ thì `abort(404)`; dựng query từ `model`, chỉ `select` các cột khai trong `cot` (cộng `id`), áp `sap_xep`, trả `Datatables::of(...)->make(true)`.

**Chỉ đọc cột đã khai** là chốt an toàn: một danh mục về sau có cột nhạy cảm sẽ không bị lộ chỉ vì người khai quên giấu.

### 4.3 View generic `resources/views/category/danh-muc-tra-cuu/index.blade.php`

Một tệp duy nhất, dựng `<thead>` và mảng `columns` của DataTables từ `cot`. Bám khuôn các màn danh mục sẵn có: `@extends('adminlte::page')`, DataTables **server-side**, `ajax` trỏ route `fetch` của đúng khoá.

Cột `is_active` hiển thị dạng chữ ("Đang dùng" / "Ngừng") thay vì 0/1.

**Không** kèm hộp thoại chi tiết: partial `category.bhyt._chi_tiet` gắn với DM BHYT, kéo sang đây là buộc khu mới phụ thuộc khu cũ.

### 4.4 Route

Prefix **mới** `danh-muc-tra-cuu/`, middleware `checkrole:category-manager`:

```php
Route::group(['prefix' => 'danh-muc-tra-cuu/', 'middleware' => ['checkrole:category-manager']], function () {
    Route::get('{khoa}', 'Category\DanhMucTraCuuController@index')->name('danh-muc-tra-cuu.index');
    Route::get('{khoa}/du-lieu', 'Category\DanhMucTraCuuController@fetch')->name('danh-muc-tra-cuu.fetch');
});
```

Cố ý **không** đặt vào `category/` (bị route bắt-tất nuốt) và **không** dùng `danh-muc/` (khu superadmin, dễ lẫn quyền).

### 4.5 Menu sinh tự động — kèm cách né bẫy thứ tự nạp

Trong `config/adminlte.php`, **trước** mảng `return`, đọc sổ đăng ký bằng `require`:

```php
// KHONG dung config('danh_muc_tra_cuu') o day: Laravel nap config theo thu tu chu cai,
// 'adminlte' chay TRUOC nen khoa do chua ton tai va se tra ve null - menu se RONG ma
// khong ai phat hien. Cung ly do da ghi cho 'organization' o dau tep nay.
$danhMucTraCuu = require __DIR__ . '/danh_muc_tra_cuu.php';

$menuDanhMucTraCuu = [];
foreach ($danhMucTraCuu as $khoa => $dm) {
    $menuDanhMucTraCuu[] = [
        'text'   => $dm['ten'],
        'icon'   => 'book',
        'url'    => 'danh-muc-tra-cuu/' . $khoa,
        'active' => ['danh-muc-tra-cuu/' . $khoa . '*'],
    ];
}
```

Rồi chèn một mục submenu **ngang hàng** với submenu "BHYT":

```php
[
    'text'    => 'Danh mục tra cứu',
    'icon'    => 'book',
    'submenu' => $menuDanhMucTraCuu,
],
```

Dùng `url` chứ không `route`: tên route được sinh ở tầng route, còn đây chỉ có slug — ghép URL thẳng là đủ và không phụ thuộc thứ tự nạp.

## 5. Thêm một danh mục mới về sau

Đúng **một** thao tác: thêm một mục vào `config/danh_muc_tra_cuu.php`. Màn hình, route và mục menu đều tự có.

Điều kiện duy nhất: danh mục phải có model Eloquent và các cột khai trong `cot` phải tồn tại thật trong bảng.

## 6. Xử lý biên / guard

1. Slug không có trong sổ đăng ký → `abort(404)` ở cả `index` lẫn `fetch`.
2. Sổ đăng ký rỗng → submenu "Danh mục tra cứu" rỗng. Chấp nhận được: không có danh mục nào để xem thì không có gì để hiện.
3. `fetch` chỉ `select` các cột đã khai — không trả cả bảng.
4. Người không có quyền `category-manager` → middleware chặn, giống mọi màn danh mục khác.

## 7. Kế hoạch kiểm thử

**Unit thuần — đọc sổ đăng ký:**
- Mọi mục trong sổ phải có đủ bốn khoá `ten`, `model`, `cot`, `cot_tim`.
- Lớp khai ở `model` phải tồn tại và là lớp con của Eloquent Model.
- Mọi cột trong `cot_tim` phải nằm trong `cot`.

**Unit — menu sinh từ sổ:** số mục submenu bằng số mục trong sổ; mỗi mục có `text` và `url` đúng slug. Test này bắt được đúng cái bẫy thứ tự nạp: nếu ai đó đổi `require` thành `config()`, submenu thành rỗng và test đỏ.

**Feature — controller:**
- Slug lạ → 404 ở cả hai route.
- Slug hợp lệ → `index` trả 200; `fetch` trả JSON có khoá `data`.
- Người dùng không đủ quyền → bị chặn.

**Hồi quy:** các bộ test danh mục sẵn có (`tests/Unit/Import`, `tests/Unit/DanhMucCoSoTest.php`, `CatalogTemplateSelfDetectTest`) phải xanh.

## 8. Rủi ro

- **Bẫy thứ tự nạp config** là rủi ro lớn nhất và hỏng *lặng lẽ*: dùng nhầm `config()` thay vì `require` sẽ cho menu rỗng, không lỗi, không cảnh báo. Đã có test menu ở §7 làm lưới an toàn.
- **Cột khai sai tên** → DataTables hiện cột trống hoặc truy vấn lỗi. Biểu hiện rõ ngay khi mở màn hình, không âm thầm.
- **Trùng slug với route khác** — không xảy ra vì prefix `danh-muc-tra-cuu/` là mới và không có route bắt-tất nào ở đó.
