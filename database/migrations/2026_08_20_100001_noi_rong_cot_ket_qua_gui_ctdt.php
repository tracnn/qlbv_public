<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/**
 * Noi rong ba cot nhan ket qua tu cong BHXH.
 *
 * VI SAO CAN: lan gui that dau tien (2026-08-20) cho thay MaGD cong tra ve dai 52 ky tu
 * trong khi cot la VARCHAR(50), vd:
 *
 *   HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75
 *
 * ma_gd la cot TRA CUU QUAN TRONG NHAT khi doi soat voi BHXH - mot ma bi cut thi khong tra
 * duoc giao dich. Phep cat do dai trong SubmitCtdtJob da cuu duoc tinh huong nang hon (neu
 * khong cat, update() nem SQLSTATE[22001] SAU KHI cong da nhan ho so, job that bai, hang doi
 * gui lai - toi da sau lan POST trung mot goi len cong, ma PL02 khong co ma giao dich phia
 * client de cong khu trung). Nhung no doi mot loi nang lay mot loi nhe, va day la luc sua
 * not loi nhe.
 *
 * ma_ket_qua va thoi_gian_tiep_nhan cung duoc noi cung luc: ca hai deu vua khit hoac gan
 * khit voi dinh dang hien tai (thoi_gian_tiep_nhan VARCHAR(14) khop dung 'yyyyMMddHHmmss',
 * khong du mot ky tu). Cong doi dinh dang la lai cut du lieu im lang.
 *
 * VI SAO DUNG SQL THANG chu khong $table->change(): du an khong cai doctrine/dbal, va
 * ->change() bat buoc phai co no. Them mot phu thuoc chi de doi ba do rong cot la khong
 * dang. Cau lenh chi chay tren MySQL - moi truong test dung SQLite, von BO QUA hoan toan
 * gioi han do dai cua VARCHAR nen khong can va khong the chay MODIFY COLUMN.
 *
 * Ba cot deu co index (ma_gd, ma_ket_qua); MODIFY giu nguyen index, va VARCHAR(100) o
 * utf8mb4 la 400 byte - con xa gioi han cua InnoDB.
 */
class NoiRongCotKetQuaGuiCtdt extends Migration
{
    /** @var array ten cot => do rong moi */
    private $cot = [
        'ma_gd'               => 100,
        'ma_ket_qua'          => 20,
        'thoi_gian_tiep_nhan' => 20,
    ];

    public function up()
    {
        if (!$this->chayDuoc()) {
            return;
        }

        foreach ($this->cot as $ten => $doRong) {
            DB::statement('ALTER TABLE ctdt_ho_so MODIFY ' . $ten . ' VARCHAR(' . $doRong . ') NULL');
        }
    }

    /**
     * Thu hep lai co the CAT du lieu da co - va day dung la cot doi soat voi BHXH. Chi lam
     * khi bang khong con gia tri nao vuot do rong cu; nguoc lai nem de nguoi chay biet minh
     * sap mat gi, thay vi cat im lang.
     */
    public function down()
    {
        if (!$this->chayDuoc()) {
            return;
        }

        $cu = ['ma_gd' => 50, 'ma_ket_qua' => 10, 'thoi_gian_tiep_nhan' => 14];

        foreach ($cu as $ten => $doRong) {
            $qua = DB::table('ctdt_ho_so')
                ->whereNotNull($ten)
                ->whereRaw('CHAR_LENGTH(' . $ten . ') > ?', [$doRong])
                ->count();

            if ($qua > 0) {
                throw new \RuntimeException(
                    'Khong the thu hep ' . $ten . ' ve ' . $doRong . ': con ' . $qua
                    . ' ban ghi dai hon the, thu hep se cat mat du lieu doi soat voi BHXH.'
                );
            }
        }

        foreach ($cu as $ten => $doRong) {
            DB::statement('ALTER TABLE ctdt_ho_so MODIFY ' . $ten . ' VARCHAR(' . $doRong . ') NULL');
        }
    }

    /** Chi MySQL. SQLite (moi truong test) bo qua gioi han VARCHAR nen khong can doi gi. */
    private function chayDuoc()
    {
        return Schema::hasTable('ctdt_ho_so')
            && DB::connection()->getDriverName() === 'mysql';
    }
}
