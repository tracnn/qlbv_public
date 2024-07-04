<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Qd130Xml2;
use App\Models\BHYT\Qd130Xml3;
use App\Models\BHYT\Qd130Xml4;
use App\Models\BHYT\Qd130Xml5;
use App\Models\BHYT\Qd130Xml6;
use App\Models\BHYT\Qd130Xml7;
use App\Models\BHYT\Qd130Xml8;
use App\Models\BHYT\Qd130Xml9;
use App\Models\BHYT\Qd130Xml10;
use App\Models\BHYT\Qd130Xml11;
use App\Models\BHYT\Qd130Xml13;
use App\Models\BHYT\Qd130Xml14;
use App\Models\BHYT\Qd130Xml15;
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

    public function storeQd130Xml6($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML6'];
    }

    public function storeQd130Xml7($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML7'];

         if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML7');
            return;
        }

        try {
            $attributes = [
                'ma_lk' => $data->MA_LK,
            ];
            $values = [
                'so_luu_tru' => $data->SO_LUU_TRU ?: null,
                'ma_yte' => $data->MA_YTE ?: null,
                'ma_khoa_rv' => $data->MA_KHOA_RV ?: null,
                'ngay_vao' => $data->NGAY_VAO ?: null,
                'ngay_ra' => $data->NGAY_RA ?: null,
                'ma_dinh_chi_thai' => $data->MA_DINH_CHI_THAI,
                'nguyennhan_dinhchi' => $data->NGUYENNHAN_DINHCHI ?: null,
                'thoigian_dinhchi' => $data->THOIGIAN_DINHCHI ?: null,
                'tuoi_thai' => intval($data->TUOI_THAI) ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'pp_dieutri' => $data->PP_DIEUTRI ?: null,
                'ghi_chu' => $data->GHI_CHU ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'ma_bs' => $data->MA_BS ?: null,
                'ten_bs' => $data->TEN_BS ?: null,
                'ngay_ct' => $data->NGAY_CT ?: null,
                'ma_cha' => $data->MA_CHA ?: null,
                'ma_me' => $data->MA_ME ?: null,
                'ma_the_tam' => $data->MA_THE_TAM ?: null,
                'ho_ten_cha' => $data->HO_TEN_CHA ?: null,
                'ho_ten_me' => $data->HO_TEN_ME ?: null,
                'so_ngay_nghi' => intval($data->SO_NGAY_NGHI) ?: null,
                'ngoaitru_tungay' => $data->NGOAITRU_TUNGAY ?: null,
                'ngoaitru_denngay' => $data->NGOAITRU_DENNGAY ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml7 = Qd130Xml7::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            // CheckQd130XmlErrorsJob::dispatch($xml7, 'XML7')
            //     ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml7: ' . $e->getMessage());
        }
    }

    public function storeQd130Xml8($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML8'];

        if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML8');
            return;
        }

        try {
            $attributes = [
                'ma_lk' => $data->MA_LK,
            ];
            $values = [
                'ma_loai_kcb' => $data->MA_LOAI_KCB ?: null,
                'ho_ten_cha' => $data->HO_TEN_CHA ?: null,
                'ho_ten_me' => $data->HO_TEN_ME ?: null,
                'nguoi_giam_ho' => $data->NGUOI_GIAM_HO ?: null,
                'don_vi' => $data->DON_VI ?: null,
                'ngay_vao' => $data->NGAY_VAO ?: null,
                'ngay_ra' => $data->NGAY_RA ?: null,
                'chan_doan_vao' => $data->CHAN_DOAN_VAO ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'qt_benhly' => $data->QT_BENHLY ?: null,
                'tomtat_kq' => $data->TOMTAT_KQ ?: null,
                'pp_dieutri' => $data->PP_DIEUTRI ?: null,
                'ngay_sinhcon' => $data->NGAY_SINHCON ?: null,
                'ngay_conchet' => $data->NGAY_CONCHET ?: null,
                'so_conchet' => intval($data->SO_CONCHET) ?: null,
                'ket_qua_dtri' => intval($data->KET_QUA_DTRI) ?: null,
                'ghi_chu' => $data->GHI_CHU ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'ngay_ct' => $data->NGAY_CT ?: null,
                'ma_the_tam' => $data->MA_THE_TAM ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml8 = Qd130Xml8::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            // CheckQd130XmlErrorsJob::dispatch($xml8, 'XML8')
            //     ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml8: ' . $e->getMessage());
        }

    }

    public function storeQd130Xml9($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML9'];

        if (isset($data->DSACH_GIAYCHUNGSINH->DU_LIEU_GIAY_CHUNG_SINH) && 
            is_iterable($data->DSACH_GIAYCHUNGSINH->DU_LIEU_GIAY_CHUNG_SINH)){
            foreach ($data->DSACH_GIAYCHUNGSINH->DU_LIEU_GIAY_CHUNG_SINH as $chungSinh) {
                if (!validateDataStructure($chungSinh, $expectedStructure)) {
                    \Log::error('Invalid data structure for XML9');
                    continue;
                }

                try {
                    $attributes = [
                        'ma_lk' => $chungSinh->MA_LK,
                    ];

                    $values = [
                        'ma_bhxh_nnd' => $chungSinh->MA_BHXH_NND ?: null,
                        'ma_the_nnd' => $chungSinh->MA_THE_NND ?: null,
                        'ho_ten_nnd' => $chungSinh->HO_TEN_NND ?: null,
                        'ngaysinh_nnd' => $chungSinh->NGAYSINH_NND ?: null,
                        'ma_dantoc_nnd' => $chungSinh->MA_DANTOC_NND ?: null,
                        'so_cccd_nnd' => $chungSinh->SO_CCCD_NND ?: null,
                        'ngaycap_cccd_nnd' => $chungSinh->NGAYCAP_CCCD_NND ?: null,
                        'noicap_cccd_nnd' => $chungSinh->NOICAP_CCCD_NND ?: null,
                        'noi_cu_tru_nnd' => $chungSinh->NOI_CU_TRU_NND ?: null,
                        'ma_quoctich' => $chungSinh->MA_QUOCTICH ?: null,
                        'matinh_cu_tru' => $chungSinh->MATINH_CU_TRU ?: null,
                        'mahuyen_cu_tru' => $chungSinh->MAHUYEN_CU_TRU ?: null,
                        'maxa_cu_tru' => $chungSinh->MAXA_CU_TRU ?: null,
                        'ho_ten_cha' => $chungSinh->HO_TEN_CHA ?: null,
                        'ma_the_tam' => $chungSinh->MA_THE_TAM ?: null,
                        'ho_ten_con' => $chungSinh->HO_TEN_CON ?: null,
                        'gioi_tinh_con' => intval($chungSinh->GIOI_TINH_CON) ?: null,
                        'so_con' => intval($chungSinh->SO_CON) ?: null,
                        'lan_sinh' => intval($chungSinh->LAN_SINH) ?: null,
                        'so_con_song' => intval($chungSinh->SO_CON_SONG) ?: null,
                        'can_nang_con' => intval($chungSinh->CAN_NANG_CON) ?: null,
                        'ngay_sinh_con' => $chungSinh->NGAY_SINH_CON ?: null,
                        'noi_sinh_con' => $chungSinh->NOI_SINH_CON ?: null,
                        'tinh_trang_con' => $chungSinh->TINH_TRANG_CON ?: null,
                        'sinhcon_phauthuat' => intval($chungSinh->SINHCON_PHAUTHUAT) ?: null,
                        'sinhcon_duoi32tuan' => intval($chungSinh->SINHCON_DUOI32TUAN) ?: null,
                        'ghi_chu' => $chungSinh->GHI_CHU ?: null,
                        'nguoi_do_de' => $chungSinh->NGUOI_DO_DE ?: null,
                        'nguoi_ghi_phieu' => $chungSinh->NGUOI_GHI_PHIEU ?: null,
                        'ngay_ct' => $chungSinh->NGAY_CT ?: null,
                        'so' => $chungSinh->SO ?: null,
                        'quyen_so' => $chungSinh->QUYEN_SO ?: null,
                        'ma_ttdv' => $chungSinh->MA_TTDV ?: null,
                        'du_phong' => $chungSinh->DU_PHONG ?: null,
                    ];

                    $xml9 = Qd130Xml9::updateOrCreate($attributes, $values);

                } catch (\Exception $e) {
                    \Log::error('Error in storeQd130Xml9: ' . $e->getMessage());
                }
            }
        }
    }

    public function storeQd130Xml10($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML10'];
    }

    public function storeQd130Xml11($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML11'];

        if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML11');
            return;
        }

        try {
            $attributes = [
                'ma_lk' => $data->MA_LK,
            ];
            $values = [
                'so_seri' => $data->SO_SERI ?: null,
                'so_ct' => $data->SO_CT ?: null,
                'so_kcb' => $data->SO_KCB ?: null,
                'don_vi' => $data->DON_VI ?: null,
                'ma_bhxh' => $data->MA_BHXH ?: null,
                'ma_the_bhyt' => $data->MA_THE_BHYT ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'pp_dieutri' => $data->PP_DIEUTRI ?: null,
                'ma_dinh_chi_thai' => intval($data->MA_DINH_CHI_THAI) ?: null,
                'nguyennhan_dinhchi' => $data->NGUYENNHAN_DINHCHI ?: null,
                'tuoi_thai' => intval($data->TUOI_THAI) ?: null,
                'so_ngay_nghi' => intval($data->SO_NGAY_NGHI) ?: null,
                'tu_ngay' => $data->TU_NGAY ?: null,
                'den_ngay' => $data->DEN_NGAY ?: null,
                'ho_ten_cha' => $data->HO_TEN_CHA ?: null,
                'ho_ten_me' => $data->HO_TEN_ME ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'ma_bs' => $data->MA_BS ?: null,
                'ngay_ct' => $data->NGAY_CT ?: null,
                'ma_the_tam' => $data->MA_THE_TAM ?: null,
                'mau_so' => $data->MAU_SO ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml11 = Qd130Xml11::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            // CheckQd130XmlErrorsJob::dispatch($xml11, 'XML11')
            //     ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml11: ' . $e->getMessage());
        }
    }

    public function storeQd130Xml13($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML13'];

        if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML13');
            return;
        }

        try {
            $attributes = [
                'ma_lk' => $data->MA_LK,
            ];
            $values = [
                'so_hoso' => $data->SO_HOSO ?: null,
                'so_chuyentuyen' => $data->SO_CHUYENTUYEN ?: null,
                'giay_chuyen_tuyen' => $data->GIAY_CHUYEN_TUYEN ?: null,
                'ma_cskcb' => $data->MA_CSKCB ?: null,
                'ma_noi_di' => $data->MA_NOI_DI ?: null,
                'ma_noi_den' => $data->MA_NOI_DEN ?: null,
                'ho_ten' => $data->HO_TEN ?: null,
                'ngay_sinh' => $data->NGAY_SINH ?: null,
                'gioi_tinh' => intval($data->GIOI_TINH) ?: null,
                'ma_quoctich' => $data->MA_QUOCTICH ?: null,
                'ma_dantoc' => $data->MA_DANTOC ?: null,
                'ma_nghe_nghiep' => $data->MA_NGHE_NGHIEP ?: null,
                'dia_chi' => $data->DIA_CHI ?: null,
                'ma_the_bhyt' => $data->MA_THE_BHYT ?: null,
                'gt_the_den' => $data->GT_THE_DEN ?: null,
                'ngay_vao' => $data->NGAY_VAO ?: null,
                'ngay_vao_noi_tru' => $data->NGAY_VAO_NOI_TRU ?: null,
                'ngay_ra' => $data->NGAY_RA ?: null,
                'dau_hieu_ls' => $data->DAU_HIEU_LS ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'qt_benhly' => $data->QT_BENHLY ?: null,
                'tomtat_kq' => $data->TOMTAT_KQ ?: null,
                'pp_dieutri' => $data->PP_DIEUTRI ?: null,
                'ma_benh_chinh' => $data->MA_BENH_CHINH ?: null,
                'ma_benh_kt' => $data->MA_BENH_KT ?: null,
                'ma_benh_yhct' => $data->MA_BENH_YHCT ?: null,
                'ten_dich_vu' => $data->TEN_DICH_VU ?: null,
                'ten_thuoc' => $data->TEN_THUOC ?: null,
                'pp_dieu_tri' => $data->PP_DIEU_TRI ?: null,
                'ma_loai_rv' => intval($data->MA_LOAI_RV) ?: null,
                'ma_lydo_ct' => intval($data->MA_LYDO_CT) ?: null,
                'huong_dieu_tri' => $data->HUONG_DIEU_TRI ?: null,
                'phuongtien_vc' => $data->PHUONGTIEN_VC ?: null,
                'hoten_nguoi_ht' => $data->HOTEN_NGUOI_HT ?: null,
                'chucdanh_nguoi_ht' => $data->CHUCDANH_NGUOI_HT ?: null,
                'ma_bac_si' => $data->MA_BAC_SI ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml13 = Qd130Xml13::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            CheckQd130XmlErrorsJob::dispatch($xml13, 'XML13')
            ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml13: ' . $e->getMessage());
        }
    }

    public function storeQd130Xml14($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML14'];

        if (!validateDataStructure($data, $expectedStructure)) {
            \Log::error('Invalid data structure for XML14');
            return;
        }

         try {
            $attributes = [
                'ma_lk' => $data->MA_LK,
            ];

            $values = [
                'so_giayhen_kl' => $data->SO_GIAYHEN_KL ?: null,
                'ma_cskcb' => $data->MA_CSKCB ?: null,
                'ho_ten' => $data->HO_TEN ?: null,
                'ngay_sinh' => $data->NGAY_SINH ?: null,
                'gioi_tinh' => intval($data->GIOI_TINH) ?: null,
                'dia_chi' => $data->DIA_CHI ?: null,
                'ma_the_bhyt' => $data->MA_THE_BHYT ?: null,
                'gt_the_den' => $data->GT_THE_DEN ?: null,
                'ngay_vao' => $data->NGAY_VAO ?: null,
                'ngay_vao_noi_tru' => $data->NGAY_VAO_NOI_TRU ?: null,
                'ngay_ra' => $data->NGAY_RA ?: null,
                'ngay_hen_kl' => $data->NGAY_HEN_KL ?: null,
                'chan_doan_rv' => $data->CHAN_DOAN_RV ?: null,
                'ma_benh_chinh' => $data->MA_BENH_CHINH ?: null,
                'ma_benh_kt' => $data->MA_BENH_KT ?: null,
                'ma_benh_yhct' => $data->MA_BENH_YHCT ?: null,
                'ma_doituong_kcb' => $data->MA_DOITUONG_KCB ?: null,
                'ma_bac_si' => $data->MA_BAC_SI ?: null,
                'ma_ttdv' => $data->MA_TTDV ?: null,
                'ngay_ct' => $data->NGAY_CT ?: null,
                'du_phong' => $data->DU_PHONG ?: null,
            ];

            $xml14 = Qd130Xml14::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            CheckQd130XmlErrorsJob::dispatch($xml14, 'XML14')
            ->onQueue($this->queueName);

        } catch (\Exception $e) {
            \Log::error('Error in storeQd130Xml14: ' . $e->getMessage());
        }
    }

    public function storeQd130Xml15($data)
    {
        $expectedStructure = XmlStructures::$expectedStructures130['XML15'];
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

    public function getDataForXmlExport($selectedRecord)
    {
        $xmlData = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><GIAMDINHHS xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></GIAMDINHHS>');

        $thongTinDonVi = $xmlData->addChild('THONGTINDONVI');
        $thongTinDonVi->addChild('MACSKCB', '01013'); // example value

        $thongTinHoSo = $xmlData->addChild('THONGTINHOSO');
        $thongTinHoSo->addChild('NGAYLAP', date('Ymd')); // current date
        $thongTinHoSo->addChild('SOLUONGHOSO', 1);

        $danhSachHoSo = $thongTinHoSo->addChild('DANHSACHHOSO');
        $hoso = $danhSachHoSo->addChild('HOSO');

        $noidungFiles = $this->getContentsForRecord($selectedRecord); // Implement this function based on your logic
        foreach ($noidungFiles as $noidungFile) {
            $fileHoso = $hoso->addChild('FILEHOSO');
            $fileHoso->addChild('LOAIHOSO', $noidungFile['type']); // example value
            $fileHoso->addChild('NOIDUNGFILE', base64_encode($noidungFile['content']));
        }

        $chuKyDonVi = $xmlData->addChild('CHUKYDONVI');
        
        // Convert SimpleXMLElement to DOMDocument for pretty print
        $dom = dom_import_simplexml($xmlData)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    private function getContentsForRecord($ma_lk)
    {
        $types = [
            'XML1' => Qd130Xml1::class,
            'XML2' => Qd130Xml2::class,
            'XML3' => Qd130Xml3::class,
            'XML4' => Qd130Xml4::class,
            'XML5' => Qd130Xml5::class,
            'XML6' => Qd130Xml6::class,
            'XML7' => Qd130Xml7::class,
            'XML8' => Qd130Xml8::class,
            'XML9' => Qd130Xml9::class,
            'XML10' => Qd130Xml10::class,
            'XML11' => Qd130Xml11::class,
            'XML13' => Qd130Xml13::class,
            'XML14' => Qd130Xml14::class,
            'XML15' => Qd130Xml15::class,
        ];

        $contents = [];

        foreach ($types as $type => $model) {
            $records = $model::where('ma_lk', $ma_lk)->get();
            if ($records->isNotEmpty()) {
                $content = $this->generateContent($type, $records);
                $contents[] = ['type' => $type, 'content' => $content];
            }
        }

        return $contents;
    }

    private function generateContent($type, $records)
    {
        switch ($type) {
            case 'XML1':
                $xmlContent = new \SimpleXMLElement('<TONG_HOP></TONG_HOP>');

                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('STT', $record->stt);
                    $xmlContent->addChild('MA_BN', $record->ma_bn);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN', $record->ho_ten);
                    $xmlContent->addChild('SO_CCCD', $record->so_cccd);
                    $xmlContent->addChild('NGAY_SINH', $record->ngay_sinh);
                    $xmlContent->addChild('GIOI_TINH', $record->gioi_tinh);
                    $xmlContent->addChild('NHOM_MAU', $record->nhom_mau);
                    $xmlContent->addChild('MA_QUOCTICH', $record->ma_quoctich);
                    $xmlContent->addChild('MA_DANTOC', $record->ma_dantoc);
                    $this->addChildWithCDATA($xmlContent, 'MA_NGHE_NGHIEP', $record->ma_nghe_nghiep);
                    $this->addChildWithCDATA($xmlContent, 'DIA_CHI', $record->dia_chi);
                    $xmlContent->addChild('MATINH_CU_TRU', $record->matinh_cu_tru);
                    $xmlContent->addChild('MAHUYEN_CU_TRU', $record->mahuyen_cu_tru);
                    $xmlContent->addChild('MAXA_CU_TRU', $record->maxa_cu_tru);
                    $xmlContent->addChild('DIEN_THOAI', $record->dien_thoai);
                    $xmlContent->addChild('MA_THE_BHYT', $record->ma_the_bhyt);
                    $xmlContent->addChild('MA_DKBD', $record->ma_dkbd);
                    $xmlContent->addChild('GT_THE_TU', $record->gt_the_tu);
                    $xmlContent->addChild('GT_THE_DEN', $record->gt_the_den);
                    $xmlContent->addChild('NGAY_MIEN_CCT', $record->ngay_mien_cct);
                    $this->addChildWithCDATA($xmlContent, 'LY_DO_VV', $record->ly_do_vv);
                    $this->addChildWithCDATA($xmlContent, 'LY_DO_VNT', $record->ly_do_vnt);
                    $xmlContent->addChild('MA_LY_DO_VNT', $record->ma_ly_do_vnt);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_VAO', $record->chan_doan_vao);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $xmlContent->addChild('MA_BENH_CHINH', $record->ma_benh_chinh);
                    $xmlContent->addChild('MA_BENH_KT', $record->ma_benh_kt);
                    $xmlContent->addChild('MA_BENH_YHCT', $record->ma_benh_yhct);
                    $xmlContent->addChild('MA_PTTT_QT', $record->ma_pttt_qt);
                    $xmlContent->addChild('MA_DOITUONG_KCB', $record->ma_doituong_kcb);
                    $xmlContent->addChild('MA_NOI_DI', $record->ma_noi_di);
                    $xmlContent->addChild('MA_NOI_DEN', $record->ma_noi_den);
                    $xmlContent->addChild('MA_TAI_NAN', $record->ma_tai_nan);
                    $xmlContent->addChild('NGAY_VAO', $record->ngay_vao);
                    $xmlContent->addChild('NGAY_VAO_NOI_TRU', $record->ngay_vao_noi_tru);
                    $xmlContent->addChild('NGAY_RA', $record->ngay_ra);
                    $xmlContent->addChild('GIAY_CHUYEN_TUYEN', $record->giay_chuyen_tuyen);
                    $xmlContent->addChild('SO_NGAY_DTRI', $record->so_ngay_dtri);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEU_TRI', $record->pp_dieu_tri);
                    $xmlContent->addChild('KET_QUA_DTRI', $record->ket_qua_dtri);
                    $xmlContent->addChild('MA_LOAI_RV', $record->ma_loai_rv);
                    $this->addChildWithCDATA($xmlContent, 'GHI_CHU', $record->ghi_chu);
                    $xmlContent->addChild('NGAY_TTOAN', $record->ngay_ttoan);
                    $xmlContent->addChild('T_THUOC', $record->t_thuoc ?? 0);
                    $xmlContent->addChild('T_VTYT', $record->t_vtyt ?? 0);
                    $xmlContent->addChild('T_TONGCHI_BV', $record->t_tongchi_bv ?? 0);
                    $xmlContent->addChild('T_TONGCHI_BH', $record->t_tongchi_bh ?? 0);
                    $xmlContent->addChild('T_BNTT', $record->t_bntt ?? 0);
                    $xmlContent->addChild('T_BNCCT', $record->t_bncct ?? 0);
                    $xmlContent->addChild('T_BHTT', $record->t_bhtt ?? 0);
                    $xmlContent->addChild('T_NGUONKHAC', $record->t_nguonkhac ?? 0);
                    $xmlContent->addChild('T_BHTT_GDV', $record->t_bhtt_gdv ?? 0);
                    $xmlContent->addChild('NAM_QT', $record->nam_qt);
                    $xmlContent->addChild('THANG_QT', $record->thang_qt);
                    $xmlContent->addChild('MA_LOAI_KCB', $record->ma_loai_kcb);
                    $xmlContent->addChild('MA_KHOA', $record->ma_khoa);
                    $xmlContent->addChild('MA_CSKCB', $record->ma_cskcb);
                    $xmlContent->addChild('MA_KHUVUC', $record->ma_khuvuc);
                    $xmlContent->addChild('CAN_NANG', $record->can_nang);
                    $xmlContent->addChild('CAN_NANG_CON', $record->can_nang_con);
                    $xmlContent->addChild('NAM_NAM_LIEN_TUC', $record->nam_nam_lien_tuc);
                    $xmlContent->addChild('NGAY_TAI_KHAM', $record->ngay_tai_kham);
                    $xmlContent->addChild('MA_HSBA', $record->ma_hsba);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML2':
                $xmlContent = new \SimpleXMLElement('<CHITIEU_CHITIET_THUOC></CHITIEU_CHITIET_THUOC>');

                $dsachChiTietThuoc = $xmlContent->addChild('DSACH_CHI_TIET_THUOC');
                foreach ($records as $record) {
                    $chiTietThuoc = $dsachChiTietThuoc->addChild('CHI_TIET_THUOC');
                    $chiTietThuoc->addChild('MA_LK', $record->ma_lk);
                    $chiTietThuoc->addChild('STT', $record->stt);
                    $chiTietThuoc->addChild('MA_THUOC', $record->ma_thuoc);
                    $this->addChildWithCDATA($chiTietThuoc, 'MA_PP_CHEBIEN', $record->ma_pp_chebien);
                    $chiTietThuoc->addChild('MA_CSKCB_THUOC', $record->ma_cskcb_thuoc);
                    $chiTietThuoc->addChild('MA_NHOM', $record->ma_nhom);
                    $this->addChildWithCDATA($chiTietThuoc, 'TEN_THUOC', $record->ten_thuoc);
                    $chiTietThuoc->addChild('DON_VI_TINH', $record->don_vi_tinh);
                    $this->addChildWithCDATA($chiTietThuoc, 'HAM_LUONG', $record->ham_luong);
                    $this->addChildWithCDATA($chiTietThuoc, 'DUONG_DUNG', $record->duong_dung);
                    $this->addChildWithCDATA($chiTietThuoc, 'DANG_BAO_CHE', $record->dang_bao_che);
                    $this->addChildWithCDATA($chiTietThuoc, 'LIEU_DUNG', $record->lieu_dung);
                    $this->addChildWithCDATA($chiTietThuoc, 'CACH_DUNG', $record->cach_dung);
                    $this->addChildWithCDATA($chiTietThuoc, 'SO_DANG_KY', $record->so_dang_ky);
                    $this->addChildWithCDATA($chiTietThuoc, 'TT_THAU', $record->tt_thau);
                    $chiTietThuoc->addChild('PHAM_VI', $record->pham_vi);
                    $chiTietThuoc->addChild('TYLE_TT_BH', $record->tyle_tt_bh ?? 0);
                    $chiTietThuoc->addChild('SO_LUONG', $record->so_luong ?? 0);
                    $chiTietThuoc->addChild('DON_GIA', $record->don_gia ?? 0);
                    $chiTietThuoc->addChild('THANH_TIEN_BV', $record->thanh_tien_bv ?? 0);
                    $chiTietThuoc->addChild('THANH_TIEN_BH', $record->thanh_tien_bh ?? 0);
                    $chiTietThuoc->addChild('T_NGUONKHAC_NSNN', $record->t_nguonkhac_nsnn ?? 0);
                    $chiTietThuoc->addChild('T_NGUONKHAC_VTNN', $record->t_nguonkhac_vtnn ?? 0);
                    $chiTietThuoc->addChild('T_NGUONKHAC_VTTN', $record->t_nguonkhac_vttn ?? 0);
                    $chiTietThuoc->addChild('T_NGUONKHAC_CL', $record->t_nguonkhac_cl ?? 0);
                    $chiTietThuoc->addChild('T_NGUONKHAC', $record->t_nguonkhac ?? 0);
                    $chiTietThuoc->addChild('MUC_HUONG', $record->muc_huong ?? 0);
                    $chiTietThuoc->addChild('T_BHTT', $record->t_bhtt ?? 0);
                    $chiTietThuoc->addChild('T_BNCCT', $record->t_bncct ?? 0);
                    $chiTietThuoc->addChild('T_BNTT', $record->t_bntt ?? 0);
                    $chiTietThuoc->addChild('MA_KHOA', $record->ma_khoa);
                    $chiTietThuoc->addChild('MA_BAC_SI', $record->ma_bac_si);
                    $chiTietThuoc->addChild('MA_DICH_VU', $record->ma_dich_vu);
                    $chiTietThuoc->addChild('NGAY_YL', $record->ngay_yl);
                    $chiTietThuoc->addChild('NGAY_TH_YL', $record->ngay_th_yl);
                    $chiTietThuoc->addChild('MA_PTTT', $record->ma_pttt);
                    $chiTietThuoc->addChild('NGUON_CTRA', $record->nguon_ctra);
                    $this->addChildWithCDATA($chiTietThuoc, 'VET_THUONG_TP', $record->vet_thuong_tp);
                    $this->addChildWithCDATA($chiTietThuoc, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML3':
                $xmlContent = new \SimpleXMLElement('<CHITIEU_CHITIET_DVKT_VTYT></CHITIEU_CHITIET_DVKT_VTYT>');

                $dsachChiTietDvkt = $xmlContent->addChild('DSACH_CHI_TIET_DVKT');
                foreach ($records as $record) {
                    $chiTietDvkt = $dsachChiTietDvkt->addChild('CHI_TIET_DVKT');
                    $chiTietDvkt->addChild('MA_LK', $record->ma_lk);
                    $chiTietDvkt->addChild('STT', $record->stt);
                    $chiTietDvkt->addChild('MA_DICH_VU', $record->ma_dich_vu);
                    $chiTietDvkt->addChild('MA_PTTT_QT', $record->ma_pttt_qt);
                    $chiTietDvkt->addChild('MA_VAT_TU', $record->ma_vat_tu);
                    $chiTietDvkt->addChild('MA_NHOM', $record->ma_nhom);
                    $chiTietDvkt->addChild('GOI_VTYT', $record->goi_vtyt);
                    $this->addChildWithCDATA($chiTietDvkt, 'TEN_VAT_TU', $record->ten_vat_tu);
                    $this->addChildWithCDATA($chiTietDvkt, 'TEN_DICH_VU', $record->ten_dich_vu);
                    $chiTietDvkt->addChild('MA_XANG_DAU', $record->ma_xang_dau);
                    $this->addChildWithCDATA($chiTietDvkt, 'DON_VI_TINH', $record->don_vi_tinh);
                    $chiTietDvkt->addChild('PHAM_VI', $record->pham_vi);
                    $chiTietDvkt->addChild('SO_LUONG', $record->so_luong);
                    $chiTietDvkt->addChild('DON_GIA_BV', $record->don_gia_bv);
                    $chiTietDvkt->addChild('DON_GIA_BH', $record->don_gia_bh);
                    $this->addChildWithCDATA($chiTietDvkt, 'TT_THAU', $record->tt_thau);
                    $chiTietDvkt->addChild('TYLE_TT_DV', $record->tyle_tt_dv ?? 0);
                    $chiTietDvkt->addChild('TYLE_TT_BH', $record->tyle_tt_bh ?? 0);
                    $chiTietDvkt->addChild('THANH_TIEN_BV', $record->thanh_tien_bv ?? 0);
                    $chiTietDvkt->addChild('THANH_TIEN_BH', $record->thanh_tien_bh ?? 0);
                    $chiTietDvkt->addChild('T_TRANTT', $record->t_trantt ?? 0);
                    $chiTietDvkt->addChild('MUC_HUONG', $record->muc_huong ?? 0);
                    $chiTietDvkt->addChild('T_NGUONKHAC_NSNN', $record->t_nguonkhac_nsnn ?? 0);
                    $chiTietDvkt->addChild('T_NGUONKHAC_VTNN', $record->t_nguonkhac_vtnn ?? 0);
                    $chiTietDvkt->addChild('T_NGUONKHAC_VTTN', $record->t_nguonkhac_vttn ?? 0);
                    $chiTietDvkt->addChild('T_NGUONKHAC_CL', $record->t_nguonkhac_cl ?? 0);
                    $chiTietDvkt->addChild('T_NGUONKHAC', $record->t_nguonkhac ?? 0);
                    $chiTietDvkt->addChild('T_BHTT', $record->t_bhtt ?? 0);
                    $chiTietDvkt->addChild('T_BNTT', $record->t_bntt ?? 0);
                    $chiTietDvkt->addChild('T_BNCCT', $record->t_bncct ?? 0);
                    $chiTietDvkt->addChild('MA_KHOA', $record->ma_khoa);
                    $chiTietDvkt->addChild('MA_GIUONG', $record->ma_giuong);
                    $chiTietDvkt->addChild('MA_BAC_SI', $record->ma_bac_si);
                    $chiTietDvkt->addChild('NGUOI_THUC_HIEN', $record->nguoi_thuc_hien);
                    $chiTietDvkt->addChild('MA_BENH', $record->ma_benh);
                    $chiTietDvkt->addChild('MA_BENH_YHCT', $record->ma_benh_yhct);
                    $chiTietDvkt->addChild('NGAY_YL', $record->ngay_yl);
                    $chiTietDvkt->addChild('NGAY_TH_YL', $record->ngay_th_yl);
                    $chiTietDvkt->addChild('NGAY_KQ', $record->ngay_kq);
                    $chiTietDvkt->addChild('MA_PTTT', $record->ma_pttt);
                    $chiTietDvkt->addChild('VET_THUONG_TP', $record->vet_thuong_tp);
                    $this->addChildWithCDATA($chiTietDvkt, 'PP_VO_CAM', $record->pp_vo_cam);
                    $this->addChildWithCDATA($chiTietDvkt, 'VI_TRI_TH_DVKT', $record->vi_tri_th_dvkt);
                    $this->addChildWithCDATA($chiTietDvkt, 'MA_MAY', $record->ma_may);
                    $this->addChildWithCDATA($chiTietDvkt, 'MA_HIEU_SP', $record->ma_hieu_sp);
                    $this->addChildWithCDATA($chiTietDvkt, 'TAI_SU_DUNG', $record->tai_su_dung);
                    $this->addChildWithCDATA($chiTietDvkt, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML4':
                $xmlContent = new \SimpleXMLElement('<CHITIEU_CHITIET_DICHVUCANLAMSANG></CHITIEU_CHITIET_DICHVUCANLAMSANG>');
                $dsachChiTietCls = $xmlContent->addChild('DSACH_CHI_TIET_CLS');
                foreach ($records as $record) {
                    $chiTietCls = $dsachChiTietCls->addChild('CHI_TIET_CLS');
                    $chiTietCls->addChild('MA_LK', $record->ma_lk);
                    $chiTietCls->addChild('STT', $record->stt);
                    $chiTietCls->addChild('MA_DICH_VU', $record->ma_dich_vu);
                    $chiTietCls->addChild('MA_CHI_SO', $record->ma_chi_so);
                    $this->addChildWithCDATA($chiTietCls, 'TEN_CHI_SO', $record->ten_chi_so);
                    $this->addChildWithCDATA($chiTietCls, 'GIA_TRI', $record->gia_tri);
                    $this->addChildWithCDATA($chiTietCls, 'DON_VI_DO', $record->don_vi_do);
                    $this->addChildWithCDATA($chiTietCls, 'MO_TA', $record->mo_ta);
                    $this->addChildWithCDATA($chiTietCls, 'KET_LUAN', $record->ket_luan);
                    $chiTietCls->addChild('NGAY_KQ', $record->ngay_kq);
                    $chiTietCls->addChild('MA_BS_DOC_KQ', $record->ma_bs_doc_kq);
                    $this->addChildWithCDATA($chiTietCls, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML5':
                $xmlContent = new \SimpleXMLElement('<CHITIEU_CHITIET_DIENBIENLAMSANG></CHITIEU_CHITIET_DIENBIENLAMSANG>');
                $dsachChiTietDbls = $xmlContent->addChild('DSACH_CHI_TIET_DIEN_BIEN_BENH');
                foreach ($records as $record) {
                    $chiTietDbls = $dsachChiTietDbls->addChild('CHI_TIET_DIEN_BIEN_BENH');
                    $chiTietDbls->addChild('MA_LK', $record->ma_lk);
                    $chiTietDbls->addChild('STT', $record->stt);
                    $this->addChildWithCDATA($chiTietDbls, 'DIEN_BIEN_LS', $record->dien_bien_ls);
                    $this->addChildWithCDATA($chiTietDbls, 'GIAI_DOAN_BENH', $record->giai_doan_benh);
                    $this->addChildWithCDATA($chiTietDbls, 'HOI_CHAN', $record->hoi_chan);
                    $this->addChildWithCDATA($chiTietDbls, 'PHAU_THUAT', $record->phau_thuat);
                    $chiTietDbls->addChild('THOI_DIEM_DBLS', $record->thoi_diem_dbls);
                    $chiTietDbls->addChild('NGUOI_THUC_HIEN', $record->nguoi_thuc_hien);
                    $this->addChildWithCDATA($chiTietDbls, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML7':
                $xmlContent = new \SimpleXMLElement('<CHI_TIEU_DU_LIEU_GIAY_RA_VIEN></CHI_TIEU_DU_LIEU_GIAY_RA_VIEN>');
                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('SO_LUU_TRU', $record->so_luu_tru);
                    $xmlContent->addChild('MA_YTE', $record->ma_yte);
                    $xmlContent->addChild('MA_KHOA_RV', $record->ma_khoa_rv);
                    $xmlContent->addChild('NGAY_VAO', $record->ngay_vao);
                    $xmlContent->addChild('NGAY_RA', $record->ngay_ra);
                    $xmlContent->addChild('MA_DINH_CHI_THAI', $record->ma_dinh_chi_thai);
                    $this->addChildWithCDATA($xmlContent, 'NGUYENNHAN_DINHCHI', $record->nguyennhan_dinhchi);
                    $this->addChildWithCDATA($xmlContent, 'THOIGIAN_DINHCHI', $record->thoigian_dinhchi);
                    $this->addChildWithCDATA($xmlContent, 'TUOI_THAI', $record->tuoi_thai);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEUTRI', $record->pp_dieutri);
                    $this->addChildWithCDATA($xmlContent, 'GHI_CHU', $record->ghi_chu);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $xmlContent->addChild('MA_BS', $record->ma_bs);
                    $this->addChildWithCDATA($xmlContent, 'TEN_BS', $record->ten_bs);
                    $xmlContent->addChild('NGAY_CT', $record->ngay_ct);
                    $this->addChildWithCDATA($xmlContent, 'MA_CHA', $record->ma_cha);
                    $this->addChildWithCDATA($xmlContent, 'MA_ME', $record->ma_me);
                    $this->addChildWithCDATA($xmlContent, 'MA_THE_TAM', $record->ma_the_tam);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_CHA', $record->ho_ten_cha);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_ME', $record->ho_ten_me);
                    $xmlContent->addChild('SO_NGAY_NGHI', $record->so_ngay_nghi);
                    $this->addChildWithCDATA($xmlContent, 'NGOAITRU_TUNGAY', $record->ngoaitru_tungay);
                    $this->addChildWithCDATA($xmlContent, 'NGOAITRU_DENNGAY', $record->ngoaitru_denngay);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML8':
                $xmlContent = new \SimpleXMLElement('<CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN></CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN>');

                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('MA_LOAI_KCB', $record->ma_loai_kcb);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_CHA', $record->ho_ten_cha);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_ME', $record->ho_ten_me);
                    $this->addChildWithCDATA($xmlContent, 'NGUOI_GIAM_HO', $record->nguoi_giam_ho);
                    $this->addChildWithCDATA($xmlContent, 'DON_VI', $record->don_vi);
                    $xmlContent->addChild('NGAY_VAO', $record->ngay_vao);
                    $xmlContent->addChild('NGAY_RA', $record->ngay_ra);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_VAO', $record->chan_doan_vao);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $this->addChildWithCDATA($xmlContent, 'QT_BENHLY', $record->qt_benhly);
                    $this->addChildWithCDATA($xmlContent, 'TOMTAT_KQ', $record->tomtat_kq);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEUTRI', $record->pp_dieutri);
                    $this->addChildWithCDATA($xmlContent, 'NGAY_SINHCON', $record->ngay_sinhcon);
                    $this->addChildWithCDATA($xmlContent, 'NGAY_CONCHET', $record->ngay_conchet);
                    $this->addChildWithCDATA($xmlContent, 'SO_CONCHET', $record->so_conchet);
                    $this->addChildWithCDATA($xmlContent, 'KET_QUA_DTRI', $record->ket_qua_dtri);
                    $this->addChildWithCDATA($xmlContent, 'GHI_CHU', $record->ghi_chu);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $xmlContent->addChild('NGAY_CT', $record->ngay_ct);
                    $this->addChildWithCDATA($xmlContent, 'MA_THE_TAM', $record->ma_the_tam);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

                case 'XML9':
                    $xmlContent = new \SimpleXMLElement('<CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH></CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH>');

                    $dsachGiayChungSinh = $xmlContent->addChild('DSACH_GIAYCHUNGSINH');
                    foreach ($records as $record) {
                        $duLieuGiayChungSinh = $dsachGiayChungSinh->addChild('DU_LIEU_GIAY_CHUNG_SINH');
                        $duLieuGiayChungSinh->addChild('MA_LK', $record->ma_lk);
                        $duLieuGiayChungSinh->addChild('MA_BHXH_NND', $record->ma_bhxh_nnd);
                        $duLieuGiayChungSinh->addChild('MA_THE_NND', $record->ma_the_nnd);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'HO_TEN_NND', $record->ho_ten_nnd);
                        $duLieuGiayChungSinh->addChild('NGAYSINH_NND', $record->ngaysinh_nnd);
                        $duLieuGiayChungSinh->addChild('MA_DANTOC_NND', $record->ma_dantoc_nnd);
                        $duLieuGiayChungSinh->addChild('SO_CCCD_NND', $record->so_cccd_nnd);
                        $duLieuGiayChungSinh->addChild('NGAYCAP_CCCD_NND', $record->ngaycap_cccd_nnd);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'NOICAP_CCCD_NND', $record->noicap_cccd_nnd);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'NOI_CU_TRU_NND', $record->noi_cu_tru_nnd);
                        $duLieuGiayChungSinh->addChild('MA_QUOCTICH', $record->ma_quoctich);
                        $duLieuGiayChungSinh->addChild('MATINH_CU_TRU', $record->matinh_cu_tru);
                        $duLieuGiayChungSinh->addChild('MAHUYEN_CU_TRU', $record->mahuyen_cu_tru);
                        $duLieuGiayChungSinh->addChild('MAXA_CU_TRU', $record->maxa_cu_tru);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'HO_TEN_CHA', $record->ho_ten_cha);
                        $duLieuGiayChungSinh->addChild('MA_THE_TAM', $record->ma_the_tam);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'HO_TEN_CON', $record->ho_ten_con);
                        $duLieuGiayChungSinh->addChild('GIOI_TINH_CON', $record->gioi_tinh_con);
                        $duLieuGiayChungSinh->addChild('SO_CON', $record->so_con);
                        $duLieuGiayChungSinh->addChild('LAN_SINH', $record->lan_sinh);
                        $duLieuGiayChungSinh->addChild('SO_CON_SONG', $record->so_con_song);
                        $duLieuGiayChungSinh->addChild('CAN_NANG_CON', $record->can_nang_con);
                        $duLieuGiayChungSinh->addChild('NGAY_SINH_CON', $record->ngay_sinh_con);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'NOI_SINH_CON', $record->noi_sinh_con);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'TINH_TRANG_CON', $record->tinh_trang_con);
                        $duLieuGiayChungSinh->addChild('SINHCON_PHAUTHUAT', $record->sinhcon_phauthuat ?? 0);
                        $duLieuGiayChungSinh->addChild('SINHCON_DUOI32TUAN', $record->sinhcon_duoi32tuan ?? 0);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'GHI_CHU', $record->ghi_chu);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'NGUOI_DO_DE', $record->nguoi_do_de);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'NGUOI_GHI_PHIEU', $record->nguoi_ghi_phieu);
                        $duLieuGiayChungSinh->addChild('NGAY_CT', $record->ngay_ct);
                        $duLieuGiayChungSinh->addChild('SO', $record->so);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'QUYEN_SO', $record->quyen_so);
                        $duLieuGiayChungSinh->addChild('MA_TTDV', $record->ma_ttdv);
                        $this->addChildWithCDATA($duLieuGiayChungSinh, 'DU_PHONG', $record->du_phong);
                    }
                    break;

            case 'XML11':
                $xmlContent = new \SimpleXMLElement('<CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI></CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI>');
                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('SO_SERI', $record->so_seri);
                    $xmlContent->addChild('SO_CT', $record->so_ct);
                    $xmlContent->addChild('SO_KCB', $record->so_kcb);
                    $this->addChildWithCDATA($xmlContent, 'DON_VI', $record->don_vi);
                    $xmlContent->addChild('MA_BHXH', $record->ma_bhxh);
                    $xmlContent->addChild('MA_THE_BHYT', $record->ma_the_bhyt);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEUTRI', $record->pp_dieutri);
                    $xmlContent->addChild('MA_DINH_CHI_THAI', $record->ma_dinh_chi_thai);
                    $this->addChildWithCDATA($xmlContent, 'NGUYENNHAN_DINHCHI', $record->nguyennhan_dinhchi);
                    $this->addChildWithCDATA($xmlContent, 'TUOI_THAI', $record->tuoi_thai);
                    $xmlContent->addChild('SO_NGAY_NGHI', $record->so_ngay_nghi);
                    $xmlContent->addChild('TU_NGAY', $record->tu_ngay);
                    $xmlContent->addChild('DEN_NGAY', $record->den_ngay);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_CHA', $record->ho_ten_cha);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN_ME', $record->ho_ten_me);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $xmlContent->addChild('MA_BS', $record->ma_bs);
                    $xmlContent->addChild('NGAY_CT', $record->ngay_ct);
                    $this->addChildWithCDATA($xmlContent, 'MA_THE_TAM', $record->ma_the_tam);
                    $xmlContent->addChild('MAU_SO', $record->mau_so);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML13':
                $xmlContent = new \SimpleXMLElement('<CHI_TIEU_GIAYCHUYENTUYEN></CHI_TIEU_GIAYCHUYENTUYEN>');
    
                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('SO_HOSO', $record->so_hoso);
                    $xmlContent->addChild('SO_CHUYENTUYEN', $record->so_chuyentuyen);
                    $xmlContent->addChild('GIAY_CHUYEN_TUYEN', $record->giay_chuyen_tuyen);
                    $xmlContent->addChild('MA_CSKCB', $record->ma_cskcb);
                    $xmlContent->addChild('MA_NOI_DI', $record->ma_noi_di);
                    $xmlContent->addChild('MA_NOI_DEN', $record->ma_noi_den);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN', $record->ho_ten);
                    $xmlContent->addChild('NGAY_SINH', $record->ngay_sinh);
                    $xmlContent->addChild('GIOI_TINH', $record->gioi_tinh);
                    $xmlContent->addChild('MA_QUOCTICH', $record->ma_quoctich);
                    $xmlContent->addChild('MA_DANTOC', $record->ma_dantoc);
                    $this->addChildWithCDATA($xmlContent, 'MA_NGHE_NGHIEP', $record->ma_nghe_nghiep);
                    $this->addChildWithCDATA($xmlContent, 'DIA_CHI', $record->dia_chi);
                    $xmlContent->addChild('MA_THE_BHYT', $record->ma_the_bhyt);
                    $xmlContent->addChild('GT_THE_DEN', $record->gt_the_den);
                    $xmlContent->addChild('NGAY_VAO', $record->ngay_vao);
                    $xmlContent->addChild('NGAY_VAO_NOI_TRU', $record->ngay_vao_noi_tru);
                    $xmlContent->addChild('NGAY_RA', $record->ngay_ra);
                    $this->addChildWithCDATA($xmlContent, 'DAU_HIEU_LS', $record->dau_hieu_ls);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $this->addChildWithCDATA($xmlContent, 'QT_BENHLY', $record->qt_benhly);
                    $this->addChildWithCDATA($xmlContent, 'TOMTAT_KQ', $record->tomtat_kq);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEUTRI', $record->pp_dieutri);
                    $xmlContent->addChild('MA_BENH_CHINH', $record->ma_benh_chinh);
                    $xmlContent->addChild('MA_BENH_KT', $record->ma_benh_kt);
                    $xmlContent->addChild('MA_BENH_YHCT', $record->ma_benh_yhct);
                    $this->addChildWithCDATA($xmlContent, 'TEN_DICH_VU', $record->ten_dich_vu);
                    $this->addChildWithCDATA($xmlContent, 'TEN_THUOC', $record->ten_thuoc);
                    $this->addChildWithCDATA($xmlContent, 'PP_DIEU_TRI', $record->pp_dieu_tri);
                    $xmlContent->addChild('MA_LOAI_RV', $record->ma_loai_rv);
                    $xmlContent->addChild('MA_LYDO_CT', $record->ma_lydo_ct);
                    $this->addChildWithCDATA($xmlContent, 'HUONG_DIEU_TRI', $record->huong_dieu_tri);
                    $this->addChildWithCDATA($xmlContent, 'PHUONGTIEN_VC', $record->phuongtien_vc);
                    $this->addChildWithCDATA($xmlContent, 'HOTEN_NGUOI_HT', $record->hoten_nguoi_ht);
                    $this->addChildWithCDATA($xmlContent, 'CHUCDANH_NGUOI_HT', $record->chucdanh_nguoi_ht);
                    $xmlContent->addChild('MA_BAC_SI', $record->ma_bac_si);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

            case 'XML14':
                $xmlContent = new \SimpleXMLElement('<CHI_TIEU_GIAYHEN_KHAMLAI></CHI_TIEU_GIAYHEN_KHAMLAI>');

                foreach ($records as $record) {
                    $xmlContent->addChild('MA_LK', $record->ma_lk);
                    $xmlContent->addChild('SO_GIAYHEN_KL', $record->so_giayhen_kl);
                    $xmlContent->addChild('MA_CSKCB', $record->ma_cskcb);
                    $this->addChildWithCDATA($xmlContent, 'HO_TEN', $record->ho_ten);
                    $xmlContent->addChild('NGAY_SINH', $record->ngay_sinh);
                    $xmlContent->addChild('GIOI_TINH', $record->gioi_tinh);
                    $this->addChildWithCDATA($xmlContent, 'DIA_CHI', $record->dia_chi);
                    $xmlContent->addChild('MA_THE_BHYT', $record->ma_the_bhyt);
                    $xmlContent->addChild('GT_THE_DEN', $record->gt_the_den);
                    $xmlContent->addChild('NGAY_VAO', $record->ngay_vao);
                    $xmlContent->addChild('NGAY_VAO_NOI_TRU', $record->ngay_vao_noi_tru);
                    $xmlContent->addChild('NGAY_RA', $record->ngay_ra);
                    $xmlContent->addChild('NGAY_HEN_KL', $record->ngay_hen_kl);
                    $this->addChildWithCDATA($xmlContent, 'CHAN_DOAN_RV', $record->chan_doan_rv);
                    $xmlContent->addChild('MA_BENH_CHINH', $record->ma_benh_chinh);
                    $xmlContent->addChild('MA_BENH_KT', $record->ma_benh_kt);
                    $xmlContent->addChild('MA_BENH_YHCT', $record->ma_benh_yhct);
                    $xmlContent->addChild('MA_DOITUONG_KCB', $record->ma_doituong_kcb);
                    $xmlContent->addChild('MA_BAC_SI', $record->ma_bac_si);
                    $xmlContent->addChild('MA_TTDV', $record->ma_ttdv);
                    $xmlContent->addChild('NGAY_CT', $record->ngay_ct);
                    $this->addChildWithCDATA($xmlContent, 'DU_PHONG', $record->du_phong);
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown XML type: $type");
        }

        $dom = dom_import_simplexml($xmlContent)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    private function addChildWithCDATA($xmlElement, $name, $value)
    {
        $child = $xmlElement->addChild($name);
        $node = dom_import_simplexml($child);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($value));
    }

}