<?php

namespace App\Services\Tt12;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12DongThuocPx;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Models\BHYT\Tt12\Tt12LichSuGui;

/**
 * Xoa mot ho so TT12 va TAT CA nhung gi treo vao no. Lop DUY NHAT lam viec nay.
 *
 * VI SAO PHAI DUY NHAT: nghiep vu nay tung duoc cai HAI lan - mot lan trong
 * Tt12Importer::doSach() khi tep bi tu choi giua chung, mot lan trong
 * BHYTTt12Controller::delete() khi nguoi dung bam xoa. Ban thu nhat da duoc sua de xoa ca
 * tt12_dong_thuoc_px; ban thu hai KHONG, va cung mot lo hong "ban ghi con mo coi" tai xuat
 * o duong thu hai. Hai ban cai dat song song chinh la co che sinh ra loi do.
 *
 * THU TU XOA la tu CON len CHA, nguoc voi thu tu ghi: dong_thuoc_px tro toi dong qua
 * dong_id, dong tro toi ho so qua ho_so_id. Migration khong khai khoa ngoai va khong co
 * ON DELETE CASCADE, nen xoa cha truoc la mat duong tim ra con - chung nam lai vinh vien,
 * tro toi mot dong_id khong con ton tai, khong truy van nao tim ra duoc nua.
 *
 * TEP TREN DIA XOA SAU CUNG, NGOAI TRANSACTION: he tep khong rollback duoc. Xoa tep truoc
 * roi transaction rollback la mat ban da ky cua mot ho so van con ton tai - te hon nhieu
 * so voi bo lai mot tep rac ma khong ban ghi nao tro toi.
 */
class Tt12XoaHoSo
{
    /**
     * So dong id moi lan lay tu CSDL de xoa bang con, tranh nap hang chuc nghin id vao
     * mot mang PHP mot luc (may chu san xuat gioi han 128 MB).
     */
    const CO_LO_XOA = 1000;

    /**
     * @param Tt12HoSo|null $hoSo nhan null de cho goi duoc tu duong don dep nua chung
     * @return bool da xoa hay khong
     */
    public function xoa($hoSo)
    {
        if ($hoSo === null) {
            return false;
        }

        // Doc TRUOC khi xoa ban ghi: sau khi $hoSo->delete() thi thuoc tinh van con trong
        // doi tuong PHP, nhung doc ra bien o day de y do khong phu thuoc vao chi tiet do.
        $duongDanDaKy = $hoSo->duong_dan_da_ky;

        DB::transaction(function () use ($hoSo) {
            Tt12Dong::where('ho_so_id', $hoSo->id)
                ->select('id')
                ->chunkById(self::CO_LO_XOA, function ($dongs) {
                    Tt12DongThuocPx::whereIn('dong_id', $dongs->pluck('id')->all())->delete();
                });

            Tt12Dong::where('ho_so_id', $hoSo->id)->delete();
            Tt12Loi::where('ho_so_id', $hoSo->id)->delete();
            Tt12LichSuGui::where('ho_so_id', $hoSo->id)->delete();

            $hoSo->delete();
        });

        $this->xoaTep($duongDanDaKy);

        return true;
    }

    /**
     * Xoa tep XML da ky.
     *
     * KHONG nem khi tep khong xoa duoc: CSDL da commit xong, ho so da bien mat khoi man
     * hinh, nem o day chi lam nguoi dung thay mot loi cho mot viec THUC RA DA XONG. Ghi
     * log de nguoi van hanh don tay.
     */
    private function xoaTep($duongDan)
    {
        if (empty($duongDan)) {
            return;
        }

        try {
            $dia = Storage::disk('exportTt12');

            if ($dia->exists($duongDan)) {
                $dia->delete($duongDan);
            }
        } catch (\Exception $e) {
            Log::warning('Tt12XoaHoSo: khong xoa duoc tep da ky', array(
                'duong_dan' => $duongDan,
                'loi'       => $e->getMessage(),
            ));
        }
    }
}
