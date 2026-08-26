<?php

namespace Tests\Unit\BHYT;

use Tests\TestCase;
use App\Services\BHYT\CongBhxh;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Canh viec doi MOT khoa base_url la doi TRON VEN moi duong goi cong BHXH.
 *
 * VI SAO QUAN TRONG: token den tu organization.BHYT.login_url, con ho so gui len URL cua
 * tung module. Neu chi doi host cho ctdt/tt12 ma login van tro cong that, he thong se lay
 * token o mot moi truong roi gui ho so len moi truong kia - cong tu choi, va ly do rat kho
 * lan ra. Mot switch nua voi con te hon khong co switch.
 */
class CauHinhCongBhxhTest extends TestCase
{
    /**
     * Bon URL cua khoi BHYT duoc ghep NGAY LUC NAP tep config, khong ghep lai luc doc.
     * Nen o day khang dinh chung NHAT QUAN voi base_url, chu khong khang dinh chung doi
     * theo khi ta goi config() luc chay - dieu do se khong bao gio dung.
     *
     * @return array ten khoa
     */
    public function cacKhoaUrlBhyt()
    {
        return array(
            array('login_url'),
            array('check_card_url'),
            array('check_card_url_2024'),
            array('submit_xml_url'),
            array('submit_xml_3176_url'),
        );
    }

    /**
     * @test
     * @dataProvider cacKhoaUrlBhyt
     */
    public function moi_url_cua_khoi_BHYT_deu_bat_dau_bang_base_url($khoa)
    {
        // Bat dung truong hop: ai do doi base_url nhung quen mot dong, de lai mot URL go
        // cung tro ve moi truong cu. Ho so se di den hai noi khac nhau.
        $base = rtrim((string) config('organization.BHYT.base_url'), '/');

        $this->assertNotSame('', $base, 'Chua khai organization.BHYT.base_url');
        $this->assertStringStartsWith(
            $base,
            (string) config('organization.BHYT.' . $khoa),
            'organization.BHYT.' . $khoa . ' khong ghep tu base_url'
        );
    }

    /** @test */
    public function doi_base_url_thi_ca_sau_mau_TT12_doi_theo()
    {
        // Sau mau TT12 ghep URL LUC DOC qua CongBhxh, nen doi base_url luc chay la doi
        // duoc ngay - day chinh la thu chung minh viec chuyen moi truong co tac dung.
        config(array('organization.BHYT.base_url' => 'https://thu-nghiem.example.vn'));

        foreach (Tt12MauRegistry::tatCa() as $ma => $lop) {
            $this->assertStringStartsWith(
                'https://thu-nghiem.example.vn/',
                $lop::url(),
                $ma . ': URL khong doi theo base_url'
            );
        }
    }

    /** @test */
    public function doi_base_url_thi_ca_ba_dich_vu_CTDT_doi_theo()
    {
        config(array('organization.BHYT.base_url' => 'https://thu-nghiem.example.vn'));

        foreach (config('ctdt.dich_vu') as $ma => $cauHinh) {
            $this->assertStringStartsWith(
                'https://thu-nghiem.example.vn/',
                CongBhxh::url($cauHinh['duong_dan']),
                $ma . ': URL khong doi theo base_url'
            );
        }
    }

    /** @test */
    public function hai_tep_cau_hinh_giao_thuc_khong_con_go_cung_host()
    {
        // Duong dan la hang so giao thuc do BHXH quy dinh - chung di theo kho ma. Host
        // thi doi theo moi truong - no o organization.php. Con mot chuoi 'https://' nao
        // trong hai tep nay nghia la mot duong goi khong theo switch.
        foreach (array('config/ctdt.php', 'config/tt12.php') as $tep) {
            $noiDung = file_get_contents(base_path($tep));

            $this->assertNotContains('https://', $noiDung,
                $tep . ' con go cung host - phai chuyen sang duong dan tuong doi');
        }
    }
}
