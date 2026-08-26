<?php

namespace Tests\Unit\BHYT;

use Tests\TestCase;

/**
 * Cac man ky va gui len cong BHXH phai dung MOT kieu hop thoai.
 *
 * VI SAO QUAN TRONG hon la chuyen tham my: confirm() cua trinh duyet la hop thoai CHAN
 * luong, khong phan biet duoc voi hop thoai cua mot trang khac, va tren mot so trinh duyet
 * co the bi tat hoan toan bang o "khong cho trang nay hien hop thoai nua". Neu confirm() bi
 * tat thi no tra ve false lang le - nut "Ky va gui" khong lam gi ca va khong ai biet tai
 * sao. Swal la thanh phan trong trang, khong tat duoc kieu do.
 *
 * Doi voi alert(): no chan ca vong lap su kien, nen khong the vua bao vua lam viec khac -
 * do chinh la ly do JS dung chung phai phat su kien BEN TRONG .then() thay vi ngay sau alert.
 *
 * LocComment khong dung duoc o day: no chay token_get_all() tren ma PHP, con day la tep
 * blade nen moi thu ngoai <?php la T_INLINE_HTML - chu thich JS khong bi bo. Tep nay tu loc.
 */
class HopThoaiSwalTest extends TestCase
{
    /** Cac man co nut day du lieu len cong BHXH */
    public function cacManBhxh()
    {
        return array(
            array('bhyt/tt12/index.blade.php'),
            array('bhyt/tt12/import.blade.php'),
            array('bhyt/tt12/partials/js-chi-tiet.blade.php'),
            array('bhyt/ctdt/index.blade.php'),
            array('bhyt/ctdt/partials/js-chi-tiet.blade.php'),
        );
    }

    private function nguon($tep)
    {
        $duongDan = resource_path('views/' . $tep);

        $this->assertFileExists($duongDan);

        return file_get_contents($duongDan);
    }

    /**
     * Bo chu thich Blade va JS.
     *
     * BAT BUOC phai loc: ctdt/index.blade.php co mot chu thich giai thich lo hong XSS, trong
     * do viet nguyen van 'A" onmouseover=alert(1) x="'. Khong loc thi chinh cau chu thich
     * canh bao ve alert() lam test cam alert() do - dung cai bay ma LocComment sinh ra de
     * tranh.
     *
     * '//' chi tinh la mo chu thich khi KHONG dung sau dau ':' - de khong cat nham
     * 'https://...' trong mot chuoi.
     */
    private function boChuThich($nguon)
    {
        $nguon = preg_replace('/\{\{--.*?--\}\}/s', '', $nguon);
        $nguon = preg_replace('#/\*.*?\*/#s', '', $nguon);

        $ra = array();

        foreach (explode("\n", $nguon) as $dong) {
            $viTri = 0;

            while (($viTri = strpos($dong, '//', $viTri)) !== false) {
                if ($viTri > 0 && $dong[$viTri - 1] === ':') {
                    $viTri += 2;
                    continue;
                }

                $dong = substr($dong, 0, $viTri);
                break;
            }

            $ra[] = $dong;
        }

        return implode("\n", $ra);
    }

    /** @test */
    public function bo_loc_chu_thich_that_su_hoat_dong()
    {
        // Test cho chinh cong cu cua test. Bo loc hong thi moi khang dinh duoi day thanh vo
        // nghia ma van xanh - va do la kieu hong khong ai phat hien ra.
        $ra = $this->boChuThich(
            "{{-- alert(1) trong chu thich blade --}}\n"
            . "// alert(2) trong chu thich dong\n"
            . "/* alert(3) trong chu thich khoi */\n"
            . "var u = 'https://vi-du.vn/x'; alert(4);\n"
        );

        $this->assertNotContains('alert(1)', $ra, 'chua bo chu thich blade');
        $this->assertNotContains('alert(2)', $ra, 'chua bo chu thich dong');
        $this->assertNotContains('alert(3)', $ra, 'chua bo chu thich khoi');

        $this->assertContains('alert(4)', $ra, 'da cat nham ma that');
        $this->assertContains("'https://vi-du.vn/x'", $ra, 'da cat nham URL trong chuoi');
    }

    /**
     * @test
     * @dataProvider cacManBhxh
     */
    public function khong_con_hop_thoai_tron_cua_trinh_duyet($tep)
    {
        $ma = $this->boChuThich($this->nguon($tep));

        $this->assertNotContains('confirm(', $ma, $tep . ': con dung confirm() cua trinh duyet');
        $this->assertNotContains('alert(', $ma, $tep . ': con dung alert() cua trinh duyet');
    }

    /**
     * @test
     * @dataProvider cacManBhxh
     */
    public function moi_man_deu_dung_Swal($tep)
    {
        // Khang dinh nguoc lai voi test tren: bo confirm/alert ma khong thay bang gi thi test
        // kia van xanh, con nguoi dung mat sach xac nhan truoc khi gui len cong that.
        $this->assertContains('Swal.fire', $this->boChuThich($this->nguon($tep)),
            $tep . ': bo confirm/alert nhung khong thay bang Swal');
    }

    /**
     * @test
     * @dataProvider cacManBhxh
     */
    public function khong_do_du_lieu_may_chu_vao_Swal_duoi_dang_html($tep)
    {
        // Swal 'text' hien thi nhu van ban thuan; 'html' thi dien giai the. Ma ho so va
        // thong diep tu cong BHXH la du lieu NGOAI - do vao 'html' la mo duong cho the
        // chay ngay trong phien cua nguoi co quyen bam "Ky va gui".
        $this->assertNotContains('html:', $this->boChuThich($this->nguon($tep)),
            $tep . ': dung text: chu khong phai html: cho du lieu tu may chu');
    }

    /** @test */
    public function ca_hai_man_deu_canh_bao_tran_gui_nhieu_o_trinh_duyet()
    {
        // Chot that nam o may chu; canh bao nay chi la tien nghi - nhung thieu no thi nguoi
        // dung tich 200 dong, bam gui, cho vong quay, roi nhan mot thong bao tu choi ma
        // khong hieu tai sao. Canh o day de hai man khong lech nhau.
        $cap = array(
            'bhyt/tt12/index.blade.php' => 'BHYTTt12Controller::TRAN_GUI_NHIEU',
            'bhyt/ctdt/index.blade.php' => 'BHYTCtdtController::TRAN_GUI_NHIEU',
        );

        foreach ($cap as $tep => $hangSo) {
            $ma = $this->boChuThich($this->nguon($tep));

            $this->assertContains($hangSo, $ma,
                $tep . ': phai doc tran tu hang so cua controller, khong go cung con so');
        }
    }

    /** @test */
    public function tt12_bao_thanh_cong_TRUOC_khi_phat_su_kien()
    {
        // alert() chan luong nen su kien tu nhien phat sau khi nguoi dung bam OK. Swal thi
        // KHONG chan: phat su kien ngay se dong modal va nap lai bang trong luc hop thong
        // bao con dang hien - man hinh giat sau lung hop thoai. Nen trigger phai nam trong
        // .then() cua hop bao thanh cong.
        $ma = $this->boChuThich(
            $this->nguon('bhyt/tt12/partials/js-chi-tiet.blade.php')
        );

        foreach (array('tt12:da-xep-hang', 'tt12:da-cuu-ho') as $suKien) {
            $viTriPhat = strpos($ma, "trigger('" . $suKien . "'");
            $this->assertNotFalse($viTriPhat, 'thieu cho phat ' . $suKien);

            // Nguoc ve tu cho phat: thu GAN NHAT phai la mo .then(), khong phai Swal.fire.
            // Khang dinh "co Swal.fire nao do phia tren" thi long leo - no van xanh khi ai
            // do keo trigger ra ngoai .then(), vi hop XAC NHAN o dau ham cung la Swal.fire.
            // So VI TRI chu khong soi mot cua so ky tu: ma o day thut rat sau nen cua so
            // hep se cat ngang chinh chuoi dang tim.
            $truocDo = substr($ma, 0, $viTriPhat);
            $viThen = strrpos($truocDo, '.then(function () {');
            $viSwal = strrpos($truocDo, 'Swal.fire');

            $this->assertNotFalse($viThen, $suKien . ': trigger khong nam trong .then() nao');
            $this->assertTrue($viThen > $viSwal,
                $suKien . ': trigger phai nam TRONG .then() cua hop bao thanh cong, '
                . 'khong phai ngay sau Swal.fire');
        }

        // Va phai co dung hai hop bao thanh cong - mot cho ky va gui, mot cho cuu ho.
        $this->assertSame(2, substr_count($ma, "icon: 'success'"),
            'moi nhanh thanh cong phai co hop bao rieng');
    }
}
