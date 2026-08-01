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
     *
     * KHONG khoa dong: listener goi ham nay tren moi lan dang nhap, chi de hien
     * thi. Duong co khoa nam o kiemTra(true), chi dung ben trong transaction cua
     * gan().
     */
    public function chuaKhoiTao(): bool
    {
        return $this->kiemTra(false);
    }

    /**
     * @param bool $khoa Dat SELECT ... FOR UPDATE len lan doc role_user.
     *                   Chi bat ben trong transaction cua gan(): hai nguoi POST
     *                   cung luc thi lan doc thu hai phai cho lan thu nhat commit,
     *                   neu khong ca hai deu doc 0 dong, ca hai deu qua, va khoa
     *                   chinh (user_id, role_id, user_type) khong va cham vi
     *                   user_id khac nhau.
     *                   Tren SQLite (test in-memory) day la no-op cam: Laravel 5.5
     *                   SQLiteGrammar khong ghi de compileLock, va
     *                   Grammar::compileLock tra '' cho gia tri lock khong phai
     *                   chuoi. Chi co tac dung that tren MySQL.
     */
    private function kiemTra(bool $khoa): bool
    {
        $roleId = Role::where('name', self::TEN_VAI_TRO)->value('id');

        if (! $roleId) {
            return true;
        }

        $truyVan = RoleUser::where('role_id', $roleId)
            ->where('user_type', $this->userType());

        if ($khoa) {
            // count() chu khong exists(): exists() bi bien thanh
            // "select exists(select * ... for update)", tuc menh de khoa nam trong
            // subquery. count() giu "for update" o muc cau lenh ngoai cung, khong
            // phu thuoc vao cach may chu xu ly khoa trong subquery.
            return $truyVan->lockForUpdate()->count() === 0;
        }

        return ! $truyVan->exists();
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
     *
     * Lan kiem tra nay co khoa dong (kiemTra(true)) - neu khong, hai nguoi POST
     * cung luc deu doc 0 dong roi deu duoc cap quyen.
     */
    public function gan(CustomUser $nguoiDung): void
    {
        DB::connection('mysql')->transaction(function () use ($nguoiDung) {
            if (! $this->kiemTra(true)) {
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
     *
     * Phu thuoc: config('laratrust.use_morph_map') dang la false, nen
     * getMorphClass() tra ve ten lop day du ('App\CustomUser'). Neu bat morph map
     * len thi gia tri nay doi theo ban do da khai bao - van khop voi attachRole(),
     * nhung se lech voi cac ban ghi role_user cu da ghi bang ten lop.
     */
    private function userType(): string
    {
        $lop = config('auth.providers.users.model');

        return (new $lop)->getMorphClass();
    }
}
