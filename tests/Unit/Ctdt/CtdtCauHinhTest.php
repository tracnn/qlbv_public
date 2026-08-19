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
class CtdtCauHinhTest extends TestCase
{
    public function cacDichVu()
    {
        return [
            ['CT2025', 'HSCHUNGTU', '39', 'https://egw.baohiemxahoi.gov.vn/api/chungtugw/GuiHoSoChungTu2025'],
            ['GBT',    'HSDLGBT',   '60', 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu'],
            ['GCS',    'HSDLGCS',   '61', 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu'],
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
            $this->assertSame($url, $dichVu[$ma]['url'], $ma . ': sai url');
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
        $this->assertFalse((bool) config('ctdt.submit_enabled'),
            'ctdt.submit_enabled phai mac dinh false');
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
}
