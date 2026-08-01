<?php

namespace App\Listeners;

use App\Services\SuperAdminBootstrap;
use Illuminate\Auth\Events\Login;

/**
 * Dat co session mot lan moi phien dang nhap.
 *
 * Middleware CheckFirstLogin cu hoi co so du lieu 2 lan tren MOI request de tra
 * loi mot cau chi doi mot lan trong ca vong doi ban cai. Dat o day thi chi con 1
 * truy van moi lan dang nhap.
 */
class DanhDauCanKhoiTaoSuperAdmin
{
    protected $bootstrap;

    public function __construct(SuperAdminBootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function handle(Login $event)
    {
        session(['setup.can_khoi_tao' => $this->bootstrap->chuaKhoiTao()]);
    }
}
