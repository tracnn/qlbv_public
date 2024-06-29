<?php

namespace App;

use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\BHYT\XML4;
use App\Models\BHYT\XML5;

class XML4210
{
    public static function import_xml1($data1)
    {
        try {
            $delete_xml1 = XML1::where('MA_LK',$data1->MA_LK);
            $delete_xml1->delete();
            $new_xml1 = new XML1;
            $new_xml1->MA_LK = $data1->MA_LK;
            $new_xml1->STT = $data1->STT;
            $new_xml1->MA_BN = $data1->MA_BN;
            $new_xml1->HO_TEN = mb_strtoupper($data1->HO_TEN);
            $new_xml1->NGAY_SINH = $data1->NGAY_SINH;
            $new_xml1->GIOI_TINH = $data1->GIOI_TINH;
            $new_xml1->DIA_CHI = $data1->DIA_CHI;
            $new_xml1->MA_THE = $data1->MA_THE;
            $new_xml1->MA_DKBD = $data1->MA_DKBD;
            $new_xml1->GT_THE_TU = $data1->GT_THE_TU;
            $new_xml1->GT_THE_DEN = $data1->GT_THE_DEN;
            $new_xml1->MIEN_CUNG_CT = $data1->MIEN_CUNG_CT;
            $new_xml1->TEN_BENH = $data1->TEN_BENH;
            $new_xml1->MA_BENH = $data1->MA_BENH;
            $new_xml1->MA_BENHKHAC = $data1->MA_BENHKHAC;
            $new_xml1->MA_LYDO_VVIEN = $data1->MA_LYDO_VVIEN;
            $new_xml1->MA_NOI_CHUYEN = $data1->MA_NOI_CHUYEN;
            $new_xml1->MA_TAI_NAN = intval($data1->MA_TAI_NAN) ? intval($data1->MA_TAI_NAN) : null;
            $new_xml1->NGAY_VAO = $data1->NGAY_VAO;
            $new_xml1->NGAY_RA = $data1->NGAY_RA;
            $new_xml1->SO_NGAY_DTRI = $data1->SO_NGAY_DTRI;
            $new_xml1->KET_QUA_DTRI = $data1->KET_QUA_DTRI;
            $new_xml1->TINH_TRANG_RV = $data1->TINH_TRANG_RV;
            $new_xml1->NGAY_TTOAN = $data1->NGAY_TTOAN;
            $new_xml1->T_THUOC = doubleval($data1->T_THUOC) ? doubleval($data1->T_THUOC) : null;
            $new_xml1->T_VTYT = doubleval($data1->T_VTYT) ? doubleval($data1->T_VTYT) : null;
            $new_xml1->T_TONGCHI = $data1->T_TONGCHI;
            $new_xml1->T_BNTT = doubleval($data1->T_BNTT) ? doubleval($data1->T_BNTT) : null;
            $new_xml1->T_BNCCT = doubleval($data1->T_BNCCT) ? doubleval($data1->T_BNCCT) : null;
            $new_xml1->T_BHTT = $data1->T_BHTT;
            $new_xml1->T_NGUONKHAC = doubleval($data1->T_NGUONKHAC) ? doubleval($data1->T_NGUONKHAC) : null;
            $new_xml1->T_NGOAIDS = doubleval($data1->T_NGOAIDS) ? doubleval($data1->T_NGOAIDS) : null;
            $new_xml1->NAM_QT = $data1->NAM_QT;
            $new_xml1->THANG_QT = $data1->THANG_QT;
            $new_xml1->MA_LOAI_KCB = $data1->MA_LOAI_KCB;
            $new_xml1->MA_KHOA = $data1->MA_KHOA;
            $new_xml1->MA_CSKCB = $data1->MA_CSKCB;
            $new_xml1->MA_KHUVUC = $data1->MA_KHUVUC;
            $new_xml1->MA_PTTT_QT = $data1->MA_PTTT_QT;
            $new_xml1->CAN_NANG = doubleval($data1->CAN_NANG) ? doubleval($data1->CAN_NANG) : null;
            $new_xml1->save();          
        } catch (\Exception $e) {
            return null;
        }
        return strval($data1->MA_LK);
    }

    public static function import_xml2($data2)
    {
        try {
            $delete_xml2 = XML2::where('MA_LK',$data2->CHI_TIET_THUOC[0]->MA_LK);
            $delete_xml2->delete();
            foreach ($data2->CHI_TIET_THUOC as $key_CHI_TIET_THUOC => $value_CHI_TIET_THUOC) {
                $new_xml2 = new XML2;
                $new_xml2->MA_LK = $value_CHI_TIET_THUOC->MA_LK;
                $new_xml2->STT = intval($value_CHI_TIET_THUOC->STT) ? intval($value_CHI_TIET_THUOC->STT) : null;
                $new_xml2->MA_THUOC = $value_CHI_TIET_THUOC->MA_THUOC;
                $new_xml2->MA_NHOM = $value_CHI_TIET_THUOC->MA_NHOM;
                $new_xml2->TEN_THUOC = $value_CHI_TIET_THUOC->TEN_THUOC;
                $new_xml2->DON_VI_TINH = $value_CHI_TIET_THUOC->DON_VI_TINH;
                $new_xml2->HAM_LUONG = $value_CHI_TIET_THUOC->HAM_LUONG;
                $new_xml2->DUONG_DUNG = $value_CHI_TIET_THUOC->DUONG_DUNG;
                $new_xml2->LIEU_DUNG = $value_CHI_TIET_THUOC->LIEU_DUNG;
                $new_xml2->SO_DANG_KY = $value_CHI_TIET_THUOC->SO_DANG_KY;
                $new_xml2->TT_THAU = $value_CHI_TIET_THUOC->TT_THAU;
                $new_xml2->PHAM_VI = $value_CHI_TIET_THUOC->PHAM_VI;
                $new_xml2->SO_LUONG = $value_CHI_TIET_THUOC->SO_LUONG;
                $new_xml2->DON_GIA = $value_CHI_TIET_THUOC->DON_GIA;
                $new_xml2->TYLE_TT = $value_CHI_TIET_THUOC->TYLE_TT;
                $new_xml2->THANH_TIEN = $value_CHI_TIET_THUOC->THANH_TIEN;
                $new_xml2->MUC_HUONG = $value_CHI_TIET_THUOC->MUC_HUONG;
                $new_xml2->T_NGUON_KHAC = doubleval($value_CHI_TIET_THUOC->T_NGUON_KHAC) ? 
                    doubleval($value_CHI_TIET_THUOC->T_NGUON_KHAC) : null;
                $new_xml2->T_BNTT = doubleval($value_CHI_TIET_THUOC->T_BNTT) ? 
                    doubleval($value_CHI_TIET_THUOC->T_BNTT) : null;
                $new_xml2->T_BHTT = $value_CHI_TIET_THUOC->T_BHTT;
                $new_xml2->T_BNCCT = doubleval($value_CHI_TIET_THUOC->T_BNCCT) ? 
                    doubleval($value_CHI_TIET_THUOC->T_BNCCT) : null;
                $new_xml2->T_NGOAIDS = doubleval($value_CHI_TIET_THUOC->T_NGOAIDS) ? 
                    doubleval($value_CHI_TIET_THUOC->T_NGOAIDS) : null;
                $new_xml2->MA_KHOA = $value_CHI_TIET_THUOC->MA_KHOA;
                $new_xml2->MA_BAC_SI = $value_CHI_TIET_THUOC->MA_BAC_SI;
                $new_xml2->MA_BENH = $value_CHI_TIET_THUOC->MA_BENH;
                $new_xml2->NGAY_YL = $value_CHI_TIET_THUOC->NGAY_YL;
                $new_xml2->MA_PTTT = $value_CHI_TIET_THUOC->MA_PTTT;
                $new_xml2->save();
            }     
        } catch (\Exception $e) {
            return false;
        }
        return true;               
    }

    public static function import_xml3($data3)
    {
        try {
            $delete_xml3 = XML3::where('MA_LK',$data3->CHI_TIET_DVKT[0]->MA_LK);
            $delete_xml3->delete();

            foreach ($data3->CHI_TIET_DVKT as $key_CHI_TIET_DVKT => $value_CHI_TIET_DVKT) {
                $new_xml3 = new XML3;
                $new_xml3->MA_LK = $value_CHI_TIET_DVKT->MA_LK;
                $new_xml3->STT = intval($value_CHI_TIET_DVKT->STT) ? intval($value_CHI_TIET_DVKT->STT) : null;
                $new_xml3->MA_DICH_VU = $value_CHI_TIET_DVKT->MA_DICH_VU;
                $new_xml3->MA_VAT_TU = $value_CHI_TIET_DVKT->MA_VAT_TU;
                $new_xml3->MA_NHOM = $value_CHI_TIET_DVKT->MA_NHOM;
                $new_xml3->GOI_VTYT = $value_CHI_TIET_DVKT->GOI_VTYT;
                $new_xml3->TEN_VAT_TU = $value_CHI_TIET_DVKT->TEN_VAT_TU;
                $new_xml3->TEN_DICH_VU = $value_CHI_TIET_DVKT->TEN_DICH_VU;
                $new_xml3->DON_VI_TINH = $value_CHI_TIET_DVKT->DON_VI_TINH;
                $new_xml3->PHAM_VI = $value_CHI_TIET_DVKT->PHAM_VI;
                $new_xml3->SO_LUONG = $value_CHI_TIET_DVKT->SO_LUONG;
                $new_xml3->DON_GIA = $value_CHI_TIET_DVKT->DON_GIA;
                $new_xml3->TT_THAU = $value_CHI_TIET_DVKT->TT_THAU;
                $new_xml3->TYLE_TT = $value_CHI_TIET_DVKT->TYLE_TT;
                $new_xml3->THANH_TIEN = $value_CHI_TIET_DVKT->THANH_TIEN;
                $new_xml3->T_TRANTT = doubleval($value_CHI_TIET_DVKT->T_TRANTT) ? 
                    doubleval($value_CHI_TIET_DVKT->T_TRANTT) : null;
                $new_xml3->MUC_HUONG = $value_CHI_TIET_DVKT->MUC_HUONG;
                $new_xml3->T_NGUONKHAC = doubleval($value_CHI_TIET_DVKT->T_NGUONKHAC) ? 
                    doubleval($value_CHI_TIET_DVKT->T_NGUONKHAC) : null;
                $new_xml3->T_BNTT = doubleval($value_CHI_TIET_DVKT->T_BNTT) ? 
                    doubleval($value_CHI_TIET_DVKT->T_BNTT) : null;
                $new_xml3->T_BHTT = $value_CHI_TIET_DVKT->T_BHTT;
                $new_xml3->T_BNCCT = doubleval($value_CHI_TIET_DVKT->T_BNCCT) ? 
                    doubleval($value_CHI_TIET_DVKT->T_BNCCT) : null;
                $new_xml3->T_NGOAIDS = doubleval($value_CHI_TIET_DVKT->T_NGOAIDS) ? 
                    doubleval($value_CHI_TIET_DVKT->T_NGOAIDS) : null;
                $new_xml3->MA_KHOA = $value_CHI_TIET_DVKT->MA_KHOA;
                $new_xml3->MA_GIUONG = $value_CHI_TIET_DVKT->MA_GIUONG;
                $new_xml3->MA_BAC_SI = $value_CHI_TIET_DVKT->MA_BAC_SI;
                $new_xml3->MA_BENH = $value_CHI_TIET_DVKT->MA_BENH;
                $new_xml3->NGAY_YL = $value_CHI_TIET_DVKT->NGAY_YL;
                $new_xml3->NGAY_KQ = $value_CHI_TIET_DVKT->NGAY_KQ;
                $new_xml3->MA_PTTT = $value_CHI_TIET_DVKT->MA_PTTT;
                $new_xml3->save();
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function import_xml4($data4)
    {
        try {
            $delete_xml4 = XML4::where('MA_LK',$data4->CHI_TIET_CLS[0]->MA_LK);
            $delete_xml4->delete();
            foreach ($data4->CHI_TIET_CLS as $key_CHI_TIET_CLS => $value_CHI_TIET_CLS) {
                $new_xml4 = new XML4;
                $new_xml4->MA_LK = $value_CHI_TIET_CLS->MA_LK;
                $new_xml4->STT = intval($value_CHI_TIET_CLS->STT) ? intval($value_CHI_TIET_CLS->STT) : null;
                $new_xml4->MA_DICH_VU = $value_CHI_TIET_CLS->MA_DICH_VU;
                $new_xml4->MA_CHI_SO = $value_CHI_TIET_CLS->MA_CHI_SO;
                $new_xml4->TEN_CHI_SO = $value_CHI_TIET_CLS->TEN_CHI_SO;
                $new_xml4->GIA_TRI = $value_CHI_TIET_CLS->GIA_TRI;
                $new_xml4->MA_MAY = $value_CHI_TIET_CLS->MA_MAY;
                $new_xml4->MO_TA = $value_CHI_TIET_CLS->MO_TA;
                $new_xml4->KET_LUAN = $value_CHI_TIET_CLS->KET_LUAN;
                $new_xml4->NGAY_KQ = $value_CHI_TIET_CLS->NGAY_KQ;
                $new_xml4->save();
            }            
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function import_xml5($data5)
    {
        try {
            $delete_xml5 = XML5::where('MA_LK',$data5->CHI_TIET_DIEN_BIEN_BENH[0]->MA_LK);
            $delete_xml5->delete();
            foreach ($data5->CHI_TIET_DIEN_BIEN_BENH as $key_CHI_TIET_DIEN_BIEN_BENH => $value_CHI_TIET_DIEN_BIEN_BENH) {
                $new_xml5 = new XML5;
                $new_xml5->MA_LK = $value_CHI_TIET_DIEN_BIEN_BENH->MA_LK;
                $new_xml5->STT = intval($value_CHI_TIET_DIEN_BIEN_BENH->STT) ? 
                    intval($value_CHI_TIET_DIEN_BIEN_BENH->STT) : null;
                $new_xml5->DIEN_BIEN = $value_CHI_TIET_DIEN_BIEN_BENH->DIEN_BIEN;
                $new_xml5->HOI_CHAN = $value_CHI_TIET_DIEN_BIEN_BENH->HOI_CHAN;
                $new_xml5->PHAU_THUAT = $value_CHI_TIET_DIEN_BIEN_BENH->PHAU_THUAT;
                $new_xml5->NGAY_YL = $value_CHI_TIET_DIEN_BIEN_BENH->NGAY_YL;
                $new_xml5->save();
            }            
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}