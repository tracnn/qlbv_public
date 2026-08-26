<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;

/**
 * Canh cau hinh module chung tu dien tu: ba dich vu phai khai du va dung theo PL02.
 *
 * VI SAO CAN TEST CHO MOT TEP CAU HINH: loai_hs va the_goc la thu duy nhat phan biet
 * ba dich vu. Go nham 60 thanh 61 thi ho so gui di van duoc cong nhan (cung URL) nhung
 * vao sai loai - hong IM LANG, khong co dau hieu gi cho toi luc doi soat.
 */
use App\Services\Ctdt\CtdtHangDoi;

class CtdtCauHinhTest extends TestCase
{
    public function cacDichVu()
    {
        return [
            ['CT2025', 'HSCHUNGTU', '39', '/api/chungtugw/GuiHoSoChungTu2025'],
            ['GBT',    'HSDLGBT',   '60', '/api/hososuckhoe/guiGiayToDienTu'],
            ['GCS',    'HSDLGCS',   '61', '/api/hososuckhoe/guiGiayToDienTu'],
        ];
    }

    /** @test */
    public function ba_dich_vu_khai_dung_the_goc_loai_hs_va_url()
    {
        $dichVu = config('ctdt.dich_vu');

        $this->assertInternalType('array', $dichVu, 'Thieu config ctdt.dich_vu');
        $this->assertCount(3, $dichVu, 'Phai co dung ba dich vu');

        foreach ($this->cacDichVu() as list($ma, $theGoc, $loaiHs, $url)) {
            $this->assertArrayHasKey($ma, $dichVu, 'Thieu dich vu ' . $ma);
            $this->assertSame($theGoc, $dichVu[$ma]['the_goc'], $ma . ': sai the goc');
            $this->assertSame($loaiHs, $dichVu[$ma]['loai_hs'], $ma . ': sai loai_hs');
            // Khang dinh DUONG DAN, khong phai URL day du - xem ghi chu cung loai trong
            // Tt12CauHinhTest. Host o organization.BHYT.base_url, doi theo moi truong.
            $this->assertSame($url, $dichVu[$ma]['duong_dan'], $ma . ': sai duong dan');
            $this->assertNotEmpty($dichVu[$ma]['ten'], $ma . ': thieu ten hien thi');
        }
    }

    /** @test */
    public function loai_hs_la_chuoi_khong_phai_so()
    {
        // '39' khac 39: form_params cua Guzzle se gui so thanh "39" nen chay duoc,
        // nhung so sanh trong ma se lech im lang. Ghim kieu ngay tu cau hinh.
        foreach (config('ctdt.dich_vu') as $ma => $cauHinh) {
            $this->assertInternalType('string', $cauHinh['loai_hs'], $ma . ': loai_hs phai la chuoi');
        }
    }

    /** @test */
    public function nam_ma_ket_qua_theo_pl02()
    {
        $maKetQua = config('ctdt.ma_ket_qua');

        foreach (['200', '205', '401', '500', '1001'] as $ma) {
            $this->assertArrayHasKey($ma, $maKetQua, 'Thieu ma ket qua ' . $ma);
            $this->assertNotEmpty($maKetQua[$ma], 'Ma ket qua ' . $ma . ' khong co mo ta');
        }
    }

    /** @test */
    public function gui_len_cong_mac_dinh_tat()
    {
        // Cong that cua BHXH nhan la nhan that. Mac dinh phai TAT de mot lan chay thu
        // khong tro thanh mot lan gui that.
        $this->assertFalse((bool) config('organization.chung_tu_dien_tu.submit_enabled'),
            'organization.chung_tu_dien_tu.submit_enabled phai mac dinh false');
    }

    /** @test */
    public function tham_so_theo_co_so_nam_trong_organization()
    {
        $khoi = config('organization.chung_tu_dien_tu');

        $this->assertInternalType('array', $khoi, 'Thieu khoi organization.chung_tu_dien_tu');

        foreach (['submit_enabled', 'import_enabled', 'sign_enabled',
                  'queue_name', 'sign_queue_name', 'submit_queue_name'] as $khoa) {
            $this->assertArrayHasKey($khoa, $khoi, 'organization.chung_tu_dien_tu thieu ' . $khoa);
        }

        // Duong dan he tep KHONG nam o day nua - no da chuyen sang filesystems.disks de
        // dung cho voi exportCtdt. Hai nguon su that cho cung mot duong dan la cach chac
        // chan de mot ngay nao do lenh Console quet mot thu muc con nguoi ta do tep vao
        // thu muc khac.
        $this->assertArrayNotHasKey('import_path', $khoi,
            'import_path da chuyen sang filesystems.disks.importCtdt.root');

        $this->assertNotEmpty($khoi['queue_name']);
        $this->assertNotEmpty($khoi['sign_queue_name']);
        $this->assertNotEmpty($khoi['submit_queue_name']);

        // Ba hang doi RIENG BIET. Trung ten thi ky so cham se chan viec gui va nguoc lai -
        // dung thu ma viec tach hang doi sinh ra de tranh.
        $this->assertCount(3, array_unique([
            $khoi['queue_name'], $khoi['sign_queue_name'], $khoi['submit_queue_name'],
        ]), 'Ba hang doi phai khac ten nhau');
    }

    /** @test */
    public function install_service_bat_cai_dung_hang_doi_ma_code_day_job_vao()
    {
        // Ten ba hang doi giu trong CtdtHangDoi; install_service.bat KHONG doc duoc PHP nen
        // van phai go tay. Lech nhau thi worker nghe MOT hang doi con job vao hang doi KHAC -
        // khong nem, khong log, khong co dau hieu gi; ho so nam mai trong hang doi va cot
        // "So loi" tren man danh sach vinh vien bang 0, trong y het nhu moi ho so deu sach.
        //
        // config/organization.php nam trong .gitignore nen KHONG lay lam chuan duoc: chuan
        // la hang so trong ma nguon, thu duy nhat co mat tren moi may. Doc hang so chu khong
        // so khop chuoi nguon: doi ten hang doi trong CtdtHangDoi ma quen sua .bat thi test
        // nay do, dung cai no sinh ra de bat.
        $bat = file_get_contents(base_path('install_service.bat'));

        $this->assertNotFalse($bat, 'Khong doc duoc install_service.bat');

        $hangDoi = [
            'KIEM' => CtdtHangDoi::KIEM,
            'KY'   => CtdtHangDoi::KY,
            'GUI'  => CtdtHangDoi::GUI,
        ];

        foreach ($hangDoi as $ten => $giaTri) {
            // Khang dinh CA DAU NHAY DONG cuoi lenh: '--queue=JobCtdt' khong thoi van khop
            // voi '--queue=JobCtdtSai', tuc test se xanh cho dung cai lech no phai bat.
            $this->assertContains(
                '--queue=' . $giaTri . '"',
                $bat,
                'install_service.bat phai cai worker nghe hang doi ' . $giaTri
                . ' - dung ten trong CtdtHangDoi::' . $ten
            );
        }
    }

    /** @test */
    public function config_ctdt_khong_giu_tham_so_theo_co_so()
    {
        // Hai nguon su that cho cung mot co bat/tat la cach chac chan de mot ngay nao do
        // co so bat gui o mot noi ma noi kia van tat - hoac te hon, nguoc lai.
        foreach (['submit_enabled', 'import_enabled', 'sign_enabled',
                  'queue_name', 'sign_queue_name', 'submit_queue_name'] as $khoa) {
            $this->assertNull(config('ctdt.' . $khoa),
                'ctdt.' . $khoa . ' phai nam o organization.chung_tu_dien_tu, khong phai config/ctdt.php');
        }
    }

    /** @test */
    public function co_disk_import_ctdt_rieng_va_doc_duoc_tu_env()
    {
        // Duong dan he tep cua module nam CANH NHAU trong filesystems.disks, de nguoi trien
        // khai cho don vi moi chi phai nhin mot cho.
        $disk = config('filesystems.disks.importCtdt');

        $this->assertInternalType('array', $disk, 'Thieu disk importCtdt');
        $this->assertSame('local', $disk['driver']);
        $this->assertNotEmpty($disk['root']);

        // Khang dinh tren BAN MAU docs/filesystems.php chu KHONG phai config/filesystems.php:
        // tep config nam trong .gitignore, nen mot khang dinh tren no se xanh o may nay va
        // KHONG CO NGHIA GI o mot ban sao moi. Ban mau moi la thu don vi moi chep sang.
        //
        // Thieu disk trong ban mau thi Storage::disk() nem InvalidArgumentException - va no
        // nem luc nguoi ta bam nut, khong phai luc trien khai.
        $mau = file_get_contents(base_path('docs/filesystems.php'));

        $this->assertContains("'importCtdt' => [", $mau, 'Ban mau thieu disk importCtdt');
        $this->assertContains("'exportCtdt' => [", $mau, 'Ban mau thieu disk exportCtdt');

        // KHONG con doi hai duong dan phai boc trong env(). Bo lop do ngay 2026-08-26 la co
        // chu dinh: config/filesystems.php VON DA nam trong .gitignore, tuc da la tep rieng
        // cua tung may - them mot lop .env nua chi la hai cho phai sua thay vi mot.
        //
        // Phan con lai cua test van giu nguyen gia tri: thieu disk trong ban mau thi
        // Storage::disk() nem InvalidArgumentException luc nguoi ta bam nut.
        $this->assertNotEmpty($disk['root'], 'importCtdt phai co duong dan cu the');
    }

    /** @test */
    public function don_vi_moi_khong_co_khoi_chung_tu_dien_tu_thi_KHONG_gay_loi()
    {
        // Trien khai cho don vi moi: config/organization.php cua ho co the khong he co khoi
        // chung_tu_dien_tu. Moi noi doc khoa do PHAI lui ve mot gia tri an toan, khong duoc
        // nem - va "an toan" nghia la TAT, khong phai bat.
        config(['organization.chung_tu_dien_tu' => null]);

        $this->assertFalse((bool) config('organization.chung_tu_dien_tu.submit_enabled'),
            'Thieu cau hinh phai la TAT gui, khong duoc mac dinh bat');
        $this->assertFalse((bool) config('organization.chung_tu_dien_tu.sign_enabled', false));
        $this->assertFalse((bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui'));

        // Ten hang doi lui ve hang so trong ma nguon, khong phai null - ->onQueue(null) day
        // job vao hang doi 'default' ma khong worker nao nghe.
        $this->assertSame(\App\Services\Ctdt\CtdtHangDoi::KIEM, \App\Services\Ctdt\CtdtHangDoi::kiem());
        $this->assertSame(\App\Services\Ctdt\CtdtHangDoi::KY, \App\Services\Ctdt\CtdtHangDoi::ky());
        $this->assertSame(\App\Services\Ctdt\CtdtHangDoi::GUI, \App\Services\Ctdt\CtdtHangDoi::gui());

        // Duong dan quet van con vi no da chuyen sang filesystems.disks.
        $this->assertNotEmpty(config('filesystems.disks.importCtdt.root'));
    }

    /** @test */
    public function co_disk_export_ctdt_rieng()
    {
        $disk = config('filesystems.disks.exportCtdt');

        $this->assertInternalType('array', $disk, 'Thieu disk exportCtdt');
        $this->assertSame('local', $disk['driver']);
        $this->assertNotEmpty($disk['root']);
        $this->assertNotSame(
            config('filesystems.disks.exportXml3176.root'),
            $disk['root'],
            'exportCtdt phai tro thu muc khac exportXml3176'
        );
    }

    /** @test */
    public function retry_after_phai_lon_hon_timeout_cua_moi_job_ctdt()
    {
        // Quy tac cua Laravel: retry_after phai LON HON thoi gian chay lau nhat cua job.
        // Nho hon thi hang doi giao lai job cho worker thu hai trong khi worker thu nhat van
        // dang chay - voi SubmitCtdtJob do la hai lan POST that cung mot goi len cong BHXH.
        //
        // Canh o day chu khong chi ghi chu thich: hai con so nam o hai tep khac nhau
        // (config/queue.php va app/Jobs/*), khong co gi buoc chung phai di cung nhau.
        //
        // Canh connection 'database' chu KHONG phai config('queue.default'): phpunit.xml ep
        // QUEUE_DRIVER=sync cho test, ma driver sync chay job ngay trong tien trinh web nen
        // khong co retry_after. 'database' moi la connection cac worker that dang dung.
        $retryAfter = (int) config('queue.connections.database.retry_after');

        $this->assertGreaterThan(0, $retryAfter, 'Phai khai retry_after');

        foreach ([\App\Jobs\SignCtdtJob::class, \App\Jobs\SubmitCtdtJob::class] as $lop) {
            $timeout = (new \ReflectionClass($lop))->getDefaultProperties()['timeout'];

            $this->assertGreaterThan(
                (int) $timeout,
                $retryAfter,
                $lop . ': timeout (' . $timeout . 's) phai NHO HON retry_after (' . $retryAfter . 's)'
            );
        }
    }
}
