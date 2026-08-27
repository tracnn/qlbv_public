<?php

namespace App\Services\Dashboard;

use App\Services\Tt12\Tt12DanhSach;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\BHYT\DanhSachCoSo;

/**
 * Truy van cho man dashboard do phu danh muc TT12.
 *
 * NGUYEN TAC: lop nay KHONG viet lai bat ky luat dem nao da co noi khac. Dem theo trang
 * thai thi goi lai Tt12DanhSach::truyVan(['trang_thai' => ...]) - ban SQL do da duoc test
 * rang voi Tt12QuyetDinhGui. Viet ban SQL thu hai o day la tao ra mot man hinh bao so KHAC
 * voi chinh bo loc ngay ben canh no, va nguoi dung se thoi tin ca hai.
 *
 * KHONG co bo loc thoi gian, khac han dashboard CTDT. Cau hoi "mau nay da bao gio duoc gui
 * chua" la cau hoi tren TOAN BO thoi gian; mac dinh lui 30 ngay se lam mot mau gui ba thang
 * truoc hien thanh "chua gui" - sai dung vao dieu man hinh sinh ra de tra loi. Bo duoc ma
 * khong lo quet bang vi tt12_ho_so co MOT dong cho moi TEP Excel, khong phai moi dong du
 * lieu: do thuc te 20 dong tt12_ho_so so voi 2.076 dong tt12_dong.
 *
 * KHONG khai kieu tra ve: Mockery 0.9.11 vo khi mock phuong thuc co return type tren PHP 7.4.
 */
class Tt12DashboardService
{
    /**
     * @return array ['luoi' => [ma_mau => [ma_cskcb => o]], 'dang_do_dang' => [ma => so]]
     */
    public function doPhu()
    {
        return array(
            'luoi'         => $this->luoi(),
            'dang_do_dang' => array(),
        );
    }

    /**
     * Luoi DAY DU sau mau x moi co so, ke ca o chua co du lieu.
     *
     * Dung lai vong lap tu Tt12MauRegistry va DanhSachCoSo chu khong gom theo du lieu da co:
     * gom theo du lieu thi mau chua bao gio gui khong xuat hien, ma do chinh la thu can thay.
     */
    private function luoi()
    {
        $ra = array();

        foreach (array_keys(Tt12MauRegistry::tatCa()) as $maMau) {
            foreach (array_keys(DanhSachCoSo::danhSach()) as $maCs) {
                $ra[$maMau][$maCs] = $this->motO($maMau, $maCs);
            }
        }

        return $ra;
    }

    /**
     * @param string $maMau
     * @param string $maCs
     * @return array
     */
    private function motO($maMau, $maCs)
    {
        $hoSo = Tt12DanhSach::truyVan(array(
            'mau'        => $maMau,
            'ma_cskcb'   => $maCs,
            'trang_thai' => 'da_gui',
        ))
        ->orderBy('thoi_gian_tiep_nhan', 'desc')
        ->first();

        if ($hoSo === null) {
            return array('da_tiep_nhan' => false, 'tiep_nhan_luc' => null, 'so_dong' => 0);
        }

        return array(
            'da_tiep_nhan'  => true,
            'tiep_nhan_luc' => $hoSo->thoi_gian_tiep_nhan,
            'so_dong'       => (int) $hoSo->so_dong,
        );
    }
}
