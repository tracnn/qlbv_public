<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml3;
use App\Models\BHYT\Qd130Xml4;
use App\Models\BHYT\Qd130Xml5;
use App\Services\XmlStructures;

use App\Jobs\CheckQd130XmlErrorsJob;
use App\Jobs\jobKtTheBHYT;

class Qd130XmlService
{
    protected $queueName = 'JobQd130XmlCheckError';

    public function storeQd130Xml1($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML1'];

        if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML1');
            return;
        }

        try {

            $attributes = [
                'ma_lk' => $data->MA_LK,
                'stt' => $data->STT,
            ];
            $values = [
                'ma_bn' => $data->MA_BN ?: null,
                'ho_ten' => $data->HO_TEN ?: null,
                'so_cccd' => $data->SO_CCCD ?: null,
                'ngay_sinh' => $data->NGAY_SINH ?: null,
                'gioi_tinh' => intval($data->GIOI_TINH) ?: null,
                'nhom_mau' => $data->NHOM_MAU ?: null,
                'ma_quoctich' => $data->MA_QUOCTICH ?: null,
                'ma_dantoc' => $data->MA_DANTOC ?: null,
                'ma_nghe_nghiep' => $data->MA_NGHE_NGHIEP ?: null,
                'dia_chi' => $data->DIA_CHI ?: null,
                'matinh_cu_tru' => $data->MATINH_CU_TRU ?: null,
                'mahuyen_cu_tru' => $data->MAHUYEN_CU_TRU ?: null,
                'maxa_cu_tru' => $data->MAXA_CU_TRU ?: null,
                'dien_thoai' => $data->DIEN_THOAI ?: null,
                'ma_the_bhyt' => $data->MA_THE_BHYT ?: null,
                'ma_dkbd' => $data->MA_DKBD ?: null,
                'gt_the_tu' => $data->GT_THE_TU ?: null,
                'gt_the_den' => $data->GT_THE_DEN ?: null,
                'ngay_mien_cct' => $data->NGAY_MIEN_CCT ?: null,
                'ly_do_vv' => $data->LY_DO_VV ?: null,
                'ly_do_vnt' => $data->LY_DO_VNT ?: null,
                'ma_ly_do_vnt' => $data->MA_LY_DO_VNT ?: null,
                'chan_doan_vao' => $data->CHAN_DOAN_VAO ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'ma_benh_chinh' => $data->MA_BENH_CHINH ?: null,
                'ma_benh_kt' => $data->MA_BENH_KT ?: null,
                'ma_benh_yhct' => $data->MA_BENH_YHCT ?: null,
                'ma_pttt_qt' => $data->MA_PTTT_QT ?: null,
                'ma_doituong_kcb' => $data->MA_DOITUONG_KCB ?: null,
                'ma_noi_di' => $data->MA_NOI_DI ?: null,
                'ma_noi_den' => $data->MA_NOI_DEN ?: null,
                'ma_tai_nan' => $data->MA_TAI_NAN ?: null,
                'ngay_vao' => $data->NGAY_VAO ?: null,
                'ngay_vao_noi_tru' => $data->NGAY_VAO_NOI_TRU ?: null,
                'ngay_ra' => $data->NGAY_RA ?: null,
                'giay_chuyen_tuyen' => $data->GIAY_CHUYEN_TUYEN ?: null,
                'so_ngay_dtri' => $data->SO_NGAY_DTRI ?: null,
                'pp_dieu_tri' => $data->PP_DIEU_TRI ?: null,
                'ket_qua_dtri' => $data->KET_QUA_DTRI ?: null,
                'ma_loai_rv' => $data->MA_LOAI_RV ?: null,
                'ghi_chu' => $data->GHI_CHU ?: null,
                'ngay_ttoan' => $data->NGAY_TTOAN ?: null,
                't_thuoc' => doubleval($data->T_THUOC) ?: null,
                't_vtyt' => doubleval($data->T_VTYT) ?: null,
                't_tongchi_bv' => doubleval($data->T_TONGCHI_BV) ?: null,
                't_tongchi_bh' => doubleval($data->T_TONGCHI_BH) ?: null,
                't_bntt' => doubleval($data->T_BNTT) ?: null,
                't_bncct' => doubleval($data->T_BNCCT) ?: null,
                't_bhtt' => doubleval($data->T_BHTT) ?: null,
                't_nguonkhac' => doubleval($data->T_NGUONKHAC) ?: null,
                't_bhtt_gdv' => doubleval($data->T_BHTT_GDV) ?: null,
                'nam_qt' => $data->NAM_QT ?: null,
                'thang_qt' => $data->THANG_QT ?: null,
                'ma_loai_kcb' => $data->MA_LOAI_KCB ?: null,
                'ma_khoa' => $data->MA_KHOA ?: null,
                'ma_cskcb' => $data->MA_CSKCB ?: null,
                'ma_khuvuc' => $data->MA_KHUVUC ?: null,
                'can_nang' => $data->CAN_NANG ?: null,
                'can_nang_con' => $data->CAN_NANG_CON ?: null,
                'nam_nam_lien_tuc' => $data->NAM_NAM_LIEN_TUC ?: null,
                'ngay_tai_kham' => $data->NGAY_TAI_KHAM ?: null,
                'ma_hsba' => $data->MA_HSBA ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml1 = Qd130Xml1::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            $this->processQd130Xml1CheckBHYT($xml1);
            CheckQd130XmlErrorsJob::dispatch($xml1, 'XML1')
            ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml1: ' . $e->getMessage());
        }
    }

    public function storeQd130Xml2($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML2'];

        if (isset($data->DSACH_CHI_TIET_THUOC->CHI_TIET_THUOC) && is_iterable($data->DSACH_CHI_TIET_THUOC->CHI_TIET_THUOC)) {
            foreach ($data->DSACH_CHI_TIET_THUOC->CHI_TIET_THUOC as $thuoc) {
                if (!validateDataStructure($thuoc, $expectedStructure)) {
                    \Log::error('Invalid data structure for XML2');
                    continue;
                }

                try {

                    $attributes = [
                        'ma_lk' => $thuoc->MA_LK,
                        'stt' => $thuoc->STT,
                    ];

                    $values = [
                        'ma_thuoc' => $thuoc->MA_THUOC ?: null,
                        'ma_pp_chebien' => $thuoc->MA_PP_CHEBIEN ?: null,
                        'ma_cskcb_thuoc' => $thuoc->MA_CSKCB_THUOC ?: null,
                        'ma_nhom' => $thuoc->MA_NHOM ?: null,
                        'ten_thuoc' => $thuoc->TEN_THUOC ?: null,
                        'don_vi_tinh' => $thuoc->DON_VI_TINH ?: null,
                        'ham_luong' => $thuoc->HAM_LUONG ?: null,
                        'duong_dung' => $thuoc->DUONG_DUNG ?: null,
                        'dang_bao_che' => $thuoc->DANG_BAO_CHE ?: null,
                        'lieu_dung' => $thuoc->LIEU_DUNG ?: null,
                        'cach_dung' => $thuoc->CACH_DUNG ?: null,
                        'so_dang_ky' => $thuoc->SO_DANG_KY ?: null,
                        'tt_thau' => $thuoc->TT_THAU ?: null,
                        'pham_vi' => $thuoc->PHAM_VI ?: null,
                        'tyle_tt_bh' => doubleval($thuoc->TYLE_TT_BH) ?: null,
                        'so_luong' => doubleval($thuoc->SO_LUONG) ?: null,
                        'don_gia' => doubleval($thuoc->DON_GIA) ?: null,
                        'thanh_tien_bv' => doubleval($thuoc->THANH_TIEN_BV) ?: null,
                        'thanh_tien_bh' => doubleval($thuoc->THANH_TIEN_BH) ?: null,
                        't_nguonkhac_nsnn' => doubleval($thuoc->T_NGUONKHAC_NSNN) ?: null,
                        't_nguonkhac_vtnn' => doubleval($thuoc->T_NGUONKHAC_VTNN) ?: null,
                        't_nguonkhac_vttn' => doubleval($thuoc->T_NGUONKHAC_VTTN) ?: null,
                        't_nguonkhac_cl' => doubleval($thuoc->T_NGUONKHAC_CL) ?: null,
                        't_nguonkhac' => doubleval($thuoc->T_NGUONKHAC) ?: null,
                        'muc_huong' => doubleval($thuoc->MUC_HUONG) ?: null,
                        't_bhtt' => doubleval($thuoc->T_BHTT) ?: null,
                        't_bncct' => doubleval($thuoc->T_BNCCT) ?: null,
                        't_bntt' => doubleval($thuoc->T_BNTT) ?: null,
                        'ma_khoa' => $thuoc->MA_KHOA ?: null,
                        'ma_bac_si' => $thuoc->MA_BAC_SI ?: null,
                        'ma_dich_vu' => $thuoc->MA_DICH_VU ?: null,
                        'ngay_yl' => $thuoc->NGAY_YL ?: null,
                        'ngay_th_yl' => $thuoc->NGAY_TH_YL ?: null,
                        'ma_pttt' => $thuoc->MA_PTTT ?: null,
                        'nguon_ctra' => $thuoc->NGUON_CTRA ?: null,
                        'vet_thuong_tp' => $thuoc->VET_THUONG_TP ?: null,
                        'du_phong' => $thuoc->DU_PHONG ?: null,
                    ];

                    $xml2 = Qd130Xml2::updateOrCreate($attributes, $values);

                    //Đẩy công việc kiểm tra vào hàng đợi
                    CheckQd130XmlErrorsJob::dispatch($xml2, 'XML2')
                    ->onQueue($this->queueName);
                } catch (\Exception $e) {
                    \Log::error('Error in storeQd130Xml2: ' . $e->getMessage());
                }
            }
        }
        
    }

    public function storeQd130Xml3($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML3'];

        if (isset($data->DSACH_CHI_TIET_DVKT->CHI_TIET_DVKT) && is_iterable($data->DSACH_CHI_TIET_DVKT->CHI_TIET_DVKT)) {
            foreach ($data->DSACH_CHI_TIET_DVKT->CHI_TIET_DVKT as $dvkt) {
                if (!validateDataStructure($dvkt, $expectedStructure)) {
                    \Log::error('Invalid data structure for XML3');
                    continue;
                }

                try {
                    $attributes = [
                        'ma_lk' => $dvkt->MA_LK,
                        'stt' => $dvkt->STT,
                    ];

                    $values = [
                        'ma_dich_vu' => $dvkt->MA_DICH_VU ?: null,
                        'ma_pttt_qt' => $dvkt->MA_PTTT_QT ?: null,
                        'ma_vat_tu' => $dvkt->MA_VAT_TU ?: null,
                        'ma_nhom' => $dvkt->MA_NHOM ?: null,
                        'goi_vtyt' => $dvkt->GOI_VTYT ?: null,
                        'ten_vat_tu' => $dvkt->TEN_VAT_TU ?: null,
                        'ten_dich_vu' => $dvkt->TEN_DICH_VU ?: null,
                        'ma_xang_dau' => $dvkt->MA_XANG_DAU ?: null,
                        'don_vi_tinh' => $dvkt->DON_VI_TINH ?: null,
                        'pham_vi' => $dvkt->PHAM_VI ?: null,
                        'so_luong' => doubleval($dvkt->SO_LUONG) ?: null,
                        'don_gia_bv' => doubleval($dvkt->DON_GIA_BV) ?: null,
                        'don_gia_bh' => doubleval($dvkt->DON_GIA_BH) ?: null,
                        'tt_thau' => $dvkt->TT_THAU ?: null,
                        'tyle_tt_dv' => doubleval($dvkt->TYLE_TT_DV) ?: null,
                        'tyle_tt_bh' => doubleval($dvkt->TYLE_TT_BH) ?: null,
                        'thanh_tien_bv' => doubleval($dvkt->THANH_TIEN_BV) ?: null,
                        'thanh_tien_bh' => doubleval($dvkt->THANH_TIEN_BH) ?: null,
                        't_trantt' => doubleval($dvkt->T_TRANTT) ?: null,
                        'muc_huong' => doubleval($dvkt->MUC_HUONG) ?: null,
                        't_nguonkhac_nsnn' => doubleval($dvkt->T_NGUONKHAC_NSNN) ?: null,
                        't_nguonkhac_vtnn' => doubleval($dvkt->T_NGUONKHAC_VTNN) ?: null,
                        't_nguonkhac_vttn' => doubleval($dvkt->T_NGUONKHAC_VTTN) ?: null,
                        't_nguonkhac_cl' => doubleval($dvkt->T_NGUONKHAC_CL) ?: null,
                        't_nguonkhac' => doubleval($dvkt->T_NGUONKHAC) ?: null,
                        't_bhtt' => doubleval($dvkt->T_BHTT) ?: null,
                        't_bntt' => doubleval($dvkt->T_BNTT) ?: null,
                        't_bncct' => doubleval($dvkt->T_BNCCT) ?: null,
                        'ma_khoa' => $dvkt->MA_KHOA ?: null,
                        'ma_giuong' => $dvkt->MA_GIUONG ?: null,
                        'ma_bac_si' => $dvkt->MA_BAC_SI ?: null,
                        'nguoi_thuc_hien' => $dvkt->NGUOI_THUC_HIEN ?: null,
                        'ma_benh' => $dvkt->MA_BENH ?: null,
                        'ma_benh_yhct' => $dvkt->MA_BENH_YHCT ?: null,
                        'ngay_yl' => $dvkt->NGAY_YL ?: null,
                        'ngay_th_yl' => $dvkt->NGAY_TH_YL ?: null,
                        'ngay_kq' => $dvkt->NGAY_KQ ?: null,
                        'ma_pttt' => $dvkt->MA_PTTT ?: null,
                        'vet_thuong_tp' => $dvkt->VET_THUONG_TP ?: null,
                        'pp_vo_cam' => $dvkt->PP_VO_CAM ?: null,
                        'vi_tri_th_dvkt' => $dvkt->VI_TRI_TH_DVKT ?: null,
                        'ma_may' => $dvkt->MA_MAY ?: null,
                        'ma_hieu_sp' => $dvkt->MA_HIEU_SP ?: null,
                        'tai_su_dung' => $dvkt->TAI_SU_DUNG ?: null,
                        'du_phong' => $dvkt->DU_PHONG ?: null,
                    ];

                    $xml3 = Qd130Xml3::updateOrCreate($attributes, $values);

                    // Đẩy công việc kiểm tra vào hàng đợi
                    CheckQd130XmlErrorsJob::dispatch($xml3, 'XML3')
                    ->onQueue($this->queueName);

                } catch (\Exception $e) {
                    \Log::error('Error in storeQd130Xml3: ' . $e->getMessage());
                }
            }
        }
        
    }

    public function storeQd130Xml4($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML4'];

        if (isset($data->DSACH_CHI_TIET_CLS->CHI_TIET_CLS) && is_iterable($data->DSACH_CHI_TIET_CLS->CHI_TIET_CLS)) {
            foreach ($data->DSACH_CHI_TIET_CLS->CHI_TIET_CLS as $cls) {
                if (!validateDataStructure($cls, $expectedStructure)) {
                    \Log::error('Invalid data structure for XML4');
                    continue;
                }

                try {
                    $attributes = [
                        'ma_lk' => $cls->MA_LK,
                        'stt' => $cls->STT,
                    ];

                    $values = [
                        'ma_dich_vu' => $cls->MA_DICH_VU ?: null,
                        'ma_chi_so' => $cls->MA_CHI_SO ?: null,
                        'ten_chi_so' => $cls->TEN_CHI_SO ?: null,
                        'gia_tri' => $cls->GIA_TRI ?: null,
                        'don_vi_do' => $cls->DON_VI_DO ?: null,
                        'mo_ta' => $cls->MO_TA ?: null,
                        'ket_luan' => $cls->KET_LUAN ?: null,
                        'ngay_kq' => $cls->NGAY_KQ ?: null,
                        'ma_bs_doc_kq' => $cls->MA_BS_DOC_KQ ?: null,
                        'du_phong' => $cls->DU_PHONG ?: null,
                    ];

                    $xml4 = Qd130Xml4::updateOrCreate($attributes, $values);

                } catch (\Exception $e) {
                    \Log::error('Error in storeQd130Xml4: ' . $e->getMessage());
                }
            }
        }
    }

    public function storeQd130Xml5($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML5'];

        if (isset($data->DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH) && 
            is_iterable($data->DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH)){
            foreach ($data->DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH as $dienbien) {
                if (!validateDataStructure($dienbien, $expectedStructure)) {
                    \Log::error('Invalid data structure for XML5');
                    continue;
                }

                try {
                    $attributes = [
                    'ma_lk' => $dienbien->MA_LK,
                    'stt' => $dienbien->STT,
                ];

                $values = [
                    'dien_bien_ls' => $dienbien->DIEN_BIEN_LS ?: null,
                    'giai_doan_benh' => $dienbien->GIAI_DOAN_BENH ?: null,
                    'hoi_chan' => $dienbien->HOI_CHAN ?: null,
                    'phau_thuat' => $dienbien->PHAU_THUAT ?: null,
                    'thoi_diem_dbls' => $dienbien->THOI_DIEM_DBLS ?: null,
                    'nguoi_thuc_hien' => $dienbien->NGUOI_THUC_HIEN ?: null,
                    'du_phong' => $dienbien->DU_PHONG ?: null,
                ];

                    $xml5 = Qd130Xml5::updateOrCreate($attributes, $values);

                } catch (\Exception $e) {
                    \Log::error('Error in storeQd130Xml5: ' . $e->getMessage());
                }
            }
        }

    }

    private function processQd130Xml1CheckBHYT($data): void
    {
        $ngaySinh = (string)$data->ngay_sinh;
        $ngaySinhFormatted = dob($ngaySinh);
        $maThes = explode(';', $data->ma_the_bhyt);
        $maDKBDs = explode(';', $data->ma_dkbd);
        $hoTen = (string)$data->ho_ten;
        $ma_lk = (string)$data->ma_lk;
        $gioiTinh = $data->gioi_tinh;
        
        foreach ($maThes as $index => $maThe) {
            if (empty($maThe)) {
                continue; // Bỏ qua nếu maThe trống
            }
            $maDKBD = $maDKBDs[$index] ?? '';

            $params = [
                'maThe' => $maThe,
                'hoTen' => $hoTen,
                'ngaySinh' => $ngaySinhFormatted,
                'ma_lk' => $ma_lk,
                'maCSKCB' => $maDKBD,
                'gioiTinh' => $gioiTinh,
                // Add other necessary fields here
            ];
            // Dispatch job
            jobKtTheBHYT::dispatch($params)->onQueue('JobKtTheBHYT');
        }
        
    }
}