<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Hai chuyen, cung mot goc: mot gia tri go tay o nhieu noi thi som muon cung lech, va ca hai
 * kieu lech o day deu KHONG NEM, KHONG LOG - chi de lai mot man hinh trong y het luc binh
 * thuong.
 */
class CtdtHangDoiTest extends TestCase
{
    /** @var array ba ham truy cap, kem khoa cau hinh va ten mac dinh cua no */
    private function bo()
    {
        return [
            'kiem' => ['queue_name', CtdtHangDoi::KIEM],
            'ky'   => ['sign_queue_name', CtdtHangDoi::KY],
            'gui'  => ['submit_queue_name', CtdtHangDoi::GUI],
        ];
    }

    /** @test */
    public function ba_hang_doi_khac_ten_nhau()
    {
        // Trung ten thi mot worker lay ra job cua khau khac va bo qua im lang - hoac te hon,
        // worker ky lay job gui roi tha ra, quay vong mai.
        $this->assertCount(3, array_unique([
            CtdtHangDoi::KIEM, CtdtHangDoi::KY, CtdtHangDoi::GUI,
        ]), 'Ba hang doi phai khac ten nhau');
    }

    /** @test */
    public function doc_dung_gia_tri_nguoi_van_hanh_dat()
    {
        foreach ($this->bo() as $ham => $doi) {
            list($khoa, $macDinh) = $doi;

            config(['organization.chung_tu_dien_tu.' . $khoa => 'HangDoiRieng']);

            $this->assertSame(
                'HangDoiRieng',
                CtdtHangDoi::{$ham}(),
                $ham . '() phai ton trong ten hang doi co so tu dat'
            );
        }
    }

    /**
     * @test
     * @dataProvider giaTriRong
     */
    public function lui_ve_mac_dinh_voi_moi_kieu_de_trong($giaTri, $moTa)
    {
        // config($khoa, $macDinh) CHI lui ve $macDinh khi khoa KHONG TON TAI (Arr::get dung
        // array_key_exists). Khoa ton tai nhung gia tri rong - dung canh mot nguoi sao chep
        // khoi cau hinh roi xoa gia tri - van tra ve chinh gia tri rong do, va ->onQueue(null)
        // day job vao hang doi 'default' ma khong worker nao nghe.
        foreach ($this->bo() as $ham => $doi) {
            list($khoa, $macDinh) = $doi;

            config(['organization.chung_tu_dien_tu.' . $khoa => $giaTri]);

            $this->assertSame(
                $macDinh,
                CtdtHangDoi::{$ham}(),
                $ham . '() phai lui ve ' . $macDinh . ' khi gia tri la ' . $moTa
            );
        }
    }

    public function giaTriRong()
    {
        return [
            'null'          => [null, 'null'],
            'chuoi rong'    => ['', 'chuoi rong'],
            'toan khoang trang' => ['   ', 'chuoi toan khoang trang'],
            'false'         => [false, 'false'],
        ];
    }

    /** @test */
    public function lui_ve_mac_dinh_khi_thieu_han_khoa()
    {
        // May chua cap nhat config/organization.php - tep nam trong .gitignore nen chuyen
        // thieu khoa la binh thuong sau moi lan them khoa moi.
        config(['organization.chung_tu_dien_tu' => []]);

        $this->assertSame(CtdtHangDoi::KIEM, CtdtHangDoi::kiem());
        $this->assertSame(CtdtHangDoi::KY, CtdtHangDoi::ky());
        $this->assertSame(CtdtHangDoi::GUI, CtdtHangDoi::gui());
    }

    /**
     * Luat "da kiem va sach chua" tung duoc chep tay o ba noi. Gio ca ba deu hoi
     * CtdtQuyetDinhGui::nenKy(), va test nay khoa chung lai: doi mot ban ma quen hai ban kia
     * thi day la cho do len.
     *
     * @test
     * @dataProvider maTranKiem
     */
    public function ba_noi_doc_luat_da_kiem_va_sach_giong_nhau($daKiem, $soLoi, $nenKy)
    {
        $this->assertSame(
            $nenKy,
            CtdtQuyetDinhGui::nenKy($daKiem, $soLoi),
            'nenKy() la ban goc cua luat'
        );

        // nen() phai uy quyen chu khong tu quyet: bat chuc nang gui va coi ho so DA KY, luc
        // do ket qua chi con phu thuoc vao luat kiem.
        $this->assertSame(
            $nenKy === CtdtQuyetDinhGui::KY ? CtdtQuyetDinhGui::GUI : $nenKy,
            CtdtQuyetDinhGui::nen(true, $daKiem, $soLoi, true),
            'nen() phai uy quyen cho nenKy()'
        );

        // CtdtTrangThaiGui dung hang so RIENG cua no; anh xa tuong minh chu khong dua vao
        // viec hai lop tinh co dung chung chuoi.
        $anhXa = [
            CtdtQuyetDinhGui::CHUA_KIEM => CtdtTrangThaiGui::CHUA_KIEM,
            CtdtQuyetDinhGui::CON_LOI   => CtdtTrangThaiGui::CON_LOI,
            CtdtQuyetDinhGui::KY        => CtdtTrangThaiGui::DA_GUI,
        ];

        $hoSo = new CtdtHoSo();
        $hoSo->checked_at  = $daKiem;
        $hoSo->so_loi      = $soLoi;
        $hoSo->is_signed   = true;
        $hoSo->ma_ket_qua  = '200';

        $this->assertSame(
            $anhXa[$nenKy],
            CtdtTrangThaiGui::cua($hoSo),
            'CtdtTrangThaiGui::cua() phai doc luat kiem giong nenKy()'
        );
    }

    public function maTranKiem()
    {
        return [
            'chua kiem, sach'        => [null, 0, CtdtQuyetDinhGui::CHUA_KIEM],
            'chua kiem, con loi'     => [null, 3, CtdtQuyetDinhGui::CHUA_KIEM],
            'chua kiem (chuoi rong)' => ['', 0, CtdtQuyetDinhGui::CHUA_KIEM],
            'da kiem, con loi'       => ['2026-08-20 08:00:00', 1, CtdtQuyetDinhGui::CON_LOI],
            'da kiem, sach'          => ['2026-08-20 08:00:00', 0, CtdtQuyetDinhGui::KY],
            'da kiem, so_loi null'   => ['2026-08-20 08:00:00', null, CtdtQuyetDinhGui::KY],
            'da kiem, so_loi chuoi'  => ['2026-08-20 08:00:00', '2', CtdtQuyetDinhGui::CON_LOI],
        ];
    }
}
