<?php

namespace App\Services;

use App\CustomUser;
use App\Exceptions\DaKhoiTaoException;
use App\Role;
use App\RoleUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cho duy nhat giu luat khoi tao quan tri vien dau tien.
 *
 * Thay cho middleware CheckFirstLogin cu, von chay tren moi route da xac thuc va
 * gan superadministrator cho bat ky ai dang nhap dau tien. Vi App\CustomUser tro
 * vao acs_user cua HIS nen "nguoi dau tien" la bat ky nhan vien nao, khong phai
 * nguoi cai dat.
 */
class SuperAdminBootstrap
{
    const TEN_VAI_TRO = 'superadministrator';

    /**
     * Dung value('id') chu khong first()->id: ban cai moi chua chay
     * laratrust:seeder thi bang roles rong, va ma cu doc thuoc tinh tren null o
     * dung cho nay.
     */
    public function chuaKhoiTao(): bool
    {
        $roleId = Role::where('name', self::TEN_VAI_TRO)->value('id');

        if (! $roleId) {
            return true;
        }

        return ! RoleUser::where('role_id', $roleId)
            ->where('user_type', $this->userType())
            ->exists();
    }

    /**
     * Tu tao vai tro neu thieu: nguoi cai o benh vien khong chay duoc artisan nen
     * khong the yeu cau ho chay laratrust:seeder. Chi tao vai tro, khong tao
     * permission - phan do van thuoc seeder.
     */
    public function vaiTro(): Role
    {
        return Role::firstOrCreate(
            ['name' => self::TEN_VAI_TRO],
            [
                'display_name' => 'Super Administrator',
                'description'  => 'Highest level administrator',
            ]
        );
    }

    /**
     * Kiem tra lai ben trong transaction: co session chi de hien thi, ranh gioi
     * bao mat that nam o day.
     */
    public function gan(CustomUser $nguoiDung): void
    {
        DB::connection('mysql')->transaction(function () use ($nguoiDung) {
            if (! $this->chuaKhoiTao()) {
                throw new DaKhoiTaoException('He thong da co quan tri vien.');
            }

            $nguoiDung->attachRole($this->vaiTro());
        });

        Log::info('Khoi tao quan tri vien dau tien', [
            'user_id'    => $nguoiDung->getKey(),
            'loginname'  => $nguoiDung->loginname,
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Mot nguon duy nhat cho user_type, dung o ca luc doc lan luc ghi. Ma cu ghi
     * cung 'App\CustomUser' khi doc nhung lay config khi ghi - hai ben lech nguon.
     *
     * getMorphClass() chu khong phai ten lop tho: day dung la gia tri ma
     * attachRole() ghi vao cot user_type, nen hai ben chac chan khop.
     */
    private function userType(): string
    {
        $lop = config('auth.providers.users.model');

        return (new $lop)->getMorphClass();
    }
}
