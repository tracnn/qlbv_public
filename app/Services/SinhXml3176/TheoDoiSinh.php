<?php

namespace App\Services\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use App\Models\SinhXml3176\Moc;

/**
 * Quyet dinh co (sinh lai) XML3176 hay khong, ghi ket qua va doc/ghi moc quet.
 */
class TheoDoiSinh
{
    /** Dong theo doi moi nhat (id lon nhat) cua (treatment_id, loai_ban); chua co thi null. */
    public function dongCuoi($treatmentId, $loaiBan)
    {
        return LanSinh::where('treatment_id', $treatmentId)
            ->where('loai_ban', $loaiBan)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Co can nap va dung lai ho so khong: false CHI khi dong cuoi co modify_time (khac null)
     * bang modify_time hien tai. Chua co dong, dong cu chua luu modify_time, hoac khac thi true.
     */
    public function canXet($treatmentId, $loaiBan, $modifyTime)
    {
        $dong = $this->dongCuoi($treatmentId, $loaiBan);

        if ($dong === null || $dong->modify_time === null || $modifyTime === null) {
            return true;
        }

        return (int) $dong->modify_time !== (int) $modifyTime;
    }

    /** Chi cap nhat modify_time cua dong do (noi dung khong doi), khong them dong moi. */
    public function capNhatModify(LanSinh $dong, $modifyTime)
    {
        $dong->modify_time = $modifyTime === null ? null : (int) $modifyTime;
        $dong->save();
    }

    /** $ketQua co the kem khoa modify_time va noi_dung_hash (null duoc). */
    public function ghi(array $ketQua)
    {
        LanSinh::create($ketQua);
    }

    /** Da co dong moc cho loai ban chua (phan biet "chua co" voi moc da luu = 0). */
    public function coMoc($loaiBan)
    {
        return Moc::find($loaiBan) !== null;
    }

    /** Moc da luu; chua co dong thi 0 (dung coMoc() de phan biet). */
    public function docMoc($loaiBan)
    {
        $dong = Moc::find($loaiBan);

        return $dong === null ? 0 : (int) $dong->moc;
    }

    public function datMoc($loaiBan, $moc)
    {
        $dong = Moc::find($loaiBan);
        if ($dong === null) {
            $dong = new Moc();
            $dong->loai_ban = $loaiBan;
        }
        $dong->moc = (int) $moc;
        $dong->updated_at = date('Y-m-d H:i:s');
        $dong->save();
    }
}
