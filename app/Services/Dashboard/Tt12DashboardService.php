<?php

namespace App\Services\Dashboard;

use App\Services\Tt12\Tt12DanhSach;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\BHYT\DanhSachCoSo;
use App\Models\BHYT\Tt12\Tt12HoSo;

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
     * @return array ['luoi' => [ma_mau => [ma_cskcb => o]], 'dang_do_dang' => [ma => so],
     *                'doc_duoc_his' => bool]
     */
    public function doPhu()
    {
        // Doc DanhSachCoSo::danhSach() DUNG MOT LAN cho ca luoi() lan co 'doc_duoc_his' tra
        // ve view: goi lai lan thu hai co the trung dung luc cache 60 phut het han, sinh ra
        // hai cau tra loi khac nhau cho cung mot cau hoi "HIS co doc duoc khong" trong cung
        // mot lan tai trang.
        $dsHis = DanhSachCoSo::danhSach();

        return array(
            'luoi'         => $this->luoi($dsHis),
            'dang_do_dang' => $this->dangDoDang(),
            'doc_duoc_his' => !empty($dsHis),
        );
    }

    /**
     * Luoi DAY DU sau mau x moi co so, ke ca o chua co du lieu.
     *
     * Dung lai vong lap tu Tt12MauRegistry va DanhSachCoSo chu khong gom theo du lieu da co:
     * gom theo du lieu thi mau chua bao gio gui khong xuat hien, ma do chinh la thu can thay.
     *
     * @param array $dsHis ket qua DanhSachCoSo::danhSach(), ma_cskcb => ten
     */
    private function luoi(array $dsHis)
    {
        $trongHis = array_keys($dsHis);

        // HIS hong (mang rong) khac han HIS bao "khong co co so nao": khi do KHONG biet co so
        // nao con hanh hay khong, nen KHONG duoc danh dau ngoai_danh_sach cho bat ky o nao -
        // danh dau het se noi doi rang moi co so da ngung hoat dong, trong khi that ra chi la
        // khong hoi duoc HIS. Xem chu thich Tt12DashboardManHinhTest ve ca nay.
        $docDuocHis = !empty($dsHis);

        // Co so co du lieu ma khong con trong HIS van phai hien - xem chu thich o motO().
        $coDuLieu = Tt12HoSo::distinct()->pluck('ma_cskcb')->all();

        $tatCaCoSo = array_values(array_unique(array_merge($trongHis, $coDuLieu)));
        sort($tatCaCoSo);

        $ra = array();

        foreach (array_keys(Tt12MauRegistry::tatCa()) as $maMau) {
            foreach ($tatCaCoSo as $maCs) {
                $ngoaiDanhSach = $docDuocHis && !in_array($maCs, $trongHis, true);
                $ra[$maMau][$maCs] = $this->motO($maMau, $maCs, $ngoaiDanhSach);
            }
        }

        return $ra;
    }

    /**
     * Dem ho so CHUA duoc tiep nhan, tach theo tung trang thai.
     *
     * Bo 'da_gui' ra khoi dai nay: no la trang thai DA XONG, va luoi ben tren da noi ve no
     * roi. De lai la mot con so bi doc hai lan o hai cho voi hai y nghia khac nhau.
     */
    private function dangDoDang()
    {
        $ra = array();

        foreach (array_keys(Tt12DanhSach::cacTrangThai()) as $ma) {
            if ($ma === 'da_gui') {
                continue;
            }

            $ra[$ma] = (int) Tt12DanhSach::truyVan(array('trang_thai' => $ma))->count();
        }

        return $ra;
    }

    /**
     * @param string $maMau
     * @param string $maCs
     * @param bool   $ngoaiDanhSach co so khong con trong danh sach HIS hien hanh
     * @return array
     */
    private function motO($maMau, $maCs, $ngoaiDanhSach = false)
    {
        // sap xep theo submitted_at/id truoc, thoi_gian_tiep_nhan chi la khoa PHU: cot do doc
        // tu phan hoi cong va co the NULL (xem Tt12SubmitService::docPhanHoi), va NULL xep
        // CUOI tren ca MySQL lan SQLite - dat no lam khoa CHINH se day ho so moi nhat (nhung
        // thieu khoa nay) xuong duoi, lam o hien so dong cua lan gui CU hon.
        $hoSo = Tt12DanhSach::truyVan(array(
            'mau'        => $maMau,
            'ma_cskcb'   => $maCs,
            'trang_thai' => 'da_gui',
        ), false)
        ->orderBy('submitted_at', 'desc')
        ->orderBy('thoi_gian_tiep_nhan', 'desc')
        ->orderBy('id', 'desc')
        ->first();

        if ($hoSo === null) {
            return array(
                'da_tiep_nhan'    => false,
                'tiep_nhan_luc'   => null,
                'so_dong'         => 0,
                'ngoai_danh_sach' => $ngoaiDanhSach,
            );
        }

        return array(
            'da_tiep_nhan'    => true,
            'tiep_nhan_luc'   => $hoSo->thoi_gian_tiep_nhan,
            'so_dong'         => (int) $hoSo->so_dong,
            'ngoai_danh_sach' => $ngoaiDanhSach,
        );
    }
}
