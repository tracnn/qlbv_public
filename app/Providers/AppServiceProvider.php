<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Khi chay sau reverse proxy (Cloudflare) ma proxy khong day duoc
        // X-Forwarded-Proto toi PHP, bat FORCE_HTTPS=true trong .env de ep
        // moi url()/route()/asset() sinh ra scheme https, tranh Mixed Content.
        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        $this->apDungLogoDonVi();

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
    /**
     * Ghi de logo AdminLTE bang logo rieng cua don vi, neu co cau hinh.
     *
     * PHAI lam o boot() chu khong o config/adminlte.php: Laravel nap cac file config theo
     * thu tu ksort tu nhien, ma 'adminlte' dung TRUOC 'organization', nen doc
     * config('organization.organization_logo') ngay trong adminlte.php se tra ve null.
     * Toi boot() thi moi file config deu da nap xong.
     *
     * Cung KHONG dat trong View::composer('adminlte::page') nhu phan menu ben duoi: man
     * dang nhap (adminlte::login) cung doc config('adminlte.logo').
     */
    private function apDungLogoDonVi()
    {
        $logo = trim((string) config('organization.organization_logo'));

        if ($logo === '') {
            return;   // giu logo mac dinh khai trong config/adminlte.php
        }

        // Gia tri den tu cau hinh/.env ma view in bang {!! !!} - boc lai truoc khi nhung.
        $img = '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8')
             . '" alt="GĐBHYT" style="height: 50px;">';

        config(['adminlte.logo' => $img, 'adminlte.logo_mini' => $img]);
    }

    public function register()
    {
        // Che do gom cua Xml3176ErrorService chi hoat dong khi job va checker dung CHUNG
        // mot thuc the: bo dem la TRANG THAI CUA DOI TUONG. Laravel mac dinh khong dung
        // singleton cho lop thuong, nen thieu dong nay thi checker nhan mot thuc the khac
        // voi cai job vua bat gom -> che do gom IM LANG khong co tac dung, khong loi,
        // khong canh bao, chi la cai tien khong xay ra.
        $this->app->singleton(\App\Services\Xml3176ErrorService::class);
    }
}
