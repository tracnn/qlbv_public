<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        View::composer('adminlte::page', function ($view) {
            $user = Auth::user();

            // Lấy cấu hình menu từ file cấu hình
            $menu = config('adminlte.menu');

            // Dùng hàm đệ quy để xử lý menu phân cấp
            $filteredMenu = $this->filterMenu($menu, $user);

            // Ghi đè menu trong cấu hình
            config(['adminlte.menu' => $filteredMenu]);
        });
    }

    protected function filterMenu($items, $user)
    {
        // Nếu người dùng có role 'superadministrator', trả về toàn bộ menu mà không lọc
        if ($user->hasRole('superadministrator')) {
            return $items;
        }

        foreach ($items as $key => $item) {
            // Kiểm tra role hoặc permission ở mức hiện tại
            if (isset($item['can']) && !$user->can($item['can'])) {
                unset($items[$key]);
                continue;
            }

            // Kiểm tra 'checkrole' nếu có
            if (isset($item['checkrole']) && !$user->hasRole($item['checkrole'])) {
                unset($items[$key]);
                continue;
            }

            // Nếu có submenu, áp dụng đệ quy
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $items[$key]['submenu'] = $this->filterMenu($item['submenu'], $user);
                // Nếu submenu trở nên rỗng sau khi lọc, xóa cả menu chính
                if (empty($items[$key]['submenu'])) {
                    unset($items[$key]);
                }
            }
        }

        // Trả về menu đã được lọc
        return array_values($items); // array_values để reset lại các index nếu có phần tử bị loại bỏ
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
