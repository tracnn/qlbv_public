<?php

namespace App\Services\Tt12;

use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;

/**
 * Doc cac dong cua mot ho so ve dung dang ma Tt12PhongBi::dung() nhan.
 *
 * VI SAO KHONG DE TRONG Tt12PhongBi: Tt12PhongBi la HAM THUAN va phai giu nguyen nhu
 * vay - no la lop duy nhat quyet dinh noi dung gui len cong nen phai kiem duoc ma khong
 * can CSDL. Viec doc bang tach ra day.
 *
 * VI SAO KHONG DOC THEO LO: ca goi XML phai nam tron trong bo nho truoc khi ky (dich vu
 * ky nhan mot chuoi base64 duy nhat), nen doc theo lo khong tiet kiem duoc gi o buoc
 * nay. Diem that that su la kich thuoc goi - xem ma loi 1001 va ghi chu o Tt12SubmitService.
 */
class Tt12DocDong
{
    /**
     * @param Tt12HoSo $hoSo
     * @return array [['du_lieu' => array, 'con' => array], ...]
     */
    public function choPhongBi(Tt12HoSo $hoSo)
    {
        $ketQua = array();

        $cacDong = Tt12Dong::where('ho_so_id', $hoSo->id)
            ->with('thuocPx')
            ->orderBy('stt')
            ->get();

        foreach ($cacDong as $dong) {
            $con = array();

            foreach ($dong->thuocPx as $motCon) {
                $con[] = $motCon->toArray();
            }

            $ketQua[] = array(
                'du_lieu' => is_array($dong->du_lieu) ? $dong->du_lieu : array(),
                'con'     => $con,
            );
        }

        return $ketQua;
    }
}
