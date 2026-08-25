<?php

namespace App\Services\Tt12\Kiem;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12Loi as LoiModel;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Ghep cac luat lai, chay tren mot ho so, ghi ket qua xuong tt12_loi.
 *
 * VI SAO LUAT HO SO PHAI DOC HET DONG: LuatHoSo can nhin toan bo tep de biet STT nao
 * trung va ma nao co hai dong. Doc theo lo cho luat theo o/theo dong nhung phai gom
 * (stt, ma, tu_ngay, den_ngay) cua moi dong cho luat theo ho so - do la BON truong chu
 * khong phai ca dong, nen mot tep 50.000 dong ton khoang vai MB, khong phai vai chuc.
 */
class Tt12Kiem
{
    /** Doc bao nhieu dong moi lan. Nho hon co lo doc Excel vi moi dong o day nang hon. */
    const CO_LO = 2000;

    /**
     * @param Tt12HoSo $hoSo
     * @return int so loi MUC 'loi' (khong tinh canh bao)
     */
    public function kiem(Tt12HoSo $hoSo)
    {
        if (!Tt12MauRegistry::co($hoSo->mau)) {
            $this->ghi($hoSo, array(Tt12Loi::loi(
                'MAU_LA',
                'Hồ sơ mang mẫu không nằm trong đăng ký: ' . $hoSo->mau
            )));

            return 1;
        }

        $lop = Tt12MauRegistry::cho($hoSo->mau);

        $loi = array();
        $tomTat = array();

        Tt12Dong::where('ho_so_id', $hoSo->id)
            ->with('thuocPx')
            ->orderBy('stt')
            ->chunk(self::CO_LO, function ($cacDong) use ($lop, $hoSo, &$loi, &$tomTat) {
                foreach ($cacDong as $dong) {
                    $duLieu = is_array($dong->du_lieu) ? $dong->du_lieu : array();

                    $duLieuCon = array();

                    foreach ($dong->thuocPx as $con) {
                        $duLieuCon[] = $con->toArray();
                    }

                    $loi = array_merge(
                        $loi,
                        LuatO::kiem($lop, $duLieu, $dong->stt),
                        LuatDong::kiem($lop, $duLieu, $dong->stt, $hoSo->ma_cskcb),
                        LuatRiengMau::kiem($lop, $duLieu, $dong->stt, $duLieuCon)
                    );

                    // Chi giu BON truong cho luat theo ho so, khong giu ca dong.
                    $tomTat[] = array(
                        'stt'    => $dong->stt,
                        'du_lieu' => array(
                            $lop::theMa() => isset($duLieu[$lop::theMa()]) ? $duLieu[$lop::theMa()] : '',
                            'TU_NGAY'     => isset($duLieu['TU_NGAY']) ? $duLieu['TU_NGAY'] : '',
                            'DEN_NGAY'    => isset($duLieu['DEN_NGAY']) ? $duLieu['DEN_NGAY'] : '',
                        ),
                    );
                }
            });

        $loi = array_merge($loi, LuatHoSo::kiem($lop, $tomTat));

        return $this->ghi($hoSo, $loi);
    }

    /**
     * Xoa loi cu roi ghi loi moi, trong mot transaction.
     *
     * Xoa truoc BAT BUOC: kiem lai mot ho so ma khong xoa se cong don loi qua tung lan
     * kiem, va so_loi phinh len mai.
     *
     * @return int so loi muc 'loi'
     */
    private function ghi(Tt12HoSo $hoSo, array $loi)
    {
        $soLoi = 0;

        foreach ($loi as $mot) {
            if ($mot->laLoi()) {
                $soLoi++;
            }
        }

        DB::transaction(function () use ($hoSo, $loi, $soLoi) {
            LoiModel::where('ho_so_id', $hoSo->id)->delete();

            foreach (array_chunk($loi, 500) as $lo) {
                $hang = array();

                foreach ($lo as $mot) {
                    $hang[] = array_merge($mot->thanhMang($hoSo->id), array(
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ));
                }

                LoiModel::insert($hang);
            }

            $hoSo->update(array(
                'checked_at' => Carbon::now(),
                'so_loi'     => $soLoi,
            ));
        });

        return $soLoi;
    }
}
