<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Truy van cho man hinh dashboard chung tu dien tu.
 *
 * NGUYEN TAC: khong lop nay viet lai bat ky luat nao da co noi khac. Dem theo trang thai thi
 * goi lai CtdtDanhSach::truyVan(['trang_thai_gui' => ...]) - ban SQL da duoc mot test tinh
 * chat rang voi CtdtTrangThaiGui::cua(). Viet ban SQL thu ba o day la tao ra mot man hinh
 * bao so khac voi chinh bo loc ngay ben canh no, va nguoi dung se thoi tin ca hai.
 *
 * Chin truy van dem thay vi mot cau GROUP BY: doi lay viec KHONG chep luat. Cac cot
 * so_loi, ma_ket_qua, submit_error co index; checked_at va is_signed thi KHONG - doi
 * schema khong thuoc pham vi task nay, chi ghi dung su that o day.
 */
class CtdtDashboardService
{
    /**
     * @param  array $loc cung dinh dang bo loc cua CtdtDanhSach::truyVan()
     * @return array theo_trang_thai, hang_doi, ton_dong
     */
    public function sucKhoe(array $loc)
    {
        return [
            'theo_trang_thai' => $this->demTheoTrangThai($loc),
            'hang_doi'        => $this->doSauHangDoi(),
            'ton_dong'        => $this->tonDong($loc),
        ];
    }

    /**
     * Liet ke DU moi trang thai, ke ca cai dang bang 0.
     *
     * Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc:
     * "Ky so that bai: 0" la mot thong tin, mot cot vang la mot cau hoi.
     */
    protected function demTheoTrangThai(array $loc)
    {
        $ket = [];

        foreach (array_keys(CtdtTrangThaiGui::NHAN) as $ma) {
            $ket[] = [
                'ma'       => $ma,
                'nhan'     => CtdtTrangThaiGui::nhan($ma),
                'so_luong' => (int) CtdtDanhSach::truyVan(
                    array_merge($loc, ['trang_thai_gui' => $ma])
                )->count(),
            ];
        }

        return $ket;
    }

    /**
     * Do sau ba hang doi.
     *
     * Tra null - KHONG phai 0 - khi khong dem duoc. Tra 0 la noi doi: nguoi doc se thay
     * "0 job dang cho" va yen tam, trong khi that ra man hinh khong biet gi ca.
     */
    protected function doSauHangDoi()
    {
        $ten = [CtdtHangDoi::kiem(), CtdtHangDoi::ky(), CtdtHangDoi::gui()];
        $demDuoc = config('queue.default') === 'database';

        $ket = [];

        foreach ($ten as $hd) {
            $soJob = null;

            if ($demDuoc) {
                try {
                    $soJob = (int) DB::table('jobs')->where('queue', $hd)->count();
                } catch (\Exception $e) {
                    // Bang jobs chua migrate - van la "khong dem duoc", khong phai "bang 0".
                    $soJob = null;
                }
            }

            $ket[] = ['ten' => $hd, 'so_job' => $soJob];
        }

        return $ket;
    }

    /**
     * Ho so da nap nhung chua len duoc cong, va cai cu nhat da nam bao nhieu ngay.
     *
     * "Cu nhat da nam bao lau" la cau hoi bat duoc mot hang doi chet CHAM - thu ma bieu do
     * so luong khong bao gio chi ra, vi so luong nap moi ngay van binh thuong.
     */
    protected function tonDong(array $loc)
    {
        // "Chua co ket qua tu cong" phai khop CHINH XAC voi dinh nghia duy nhat cua no o
        // CtdtDanhSach::chuaCoKetQua() (NULL / '' / '0') - khong duoc viet lai o day. Mot
        // ban sao thu ba tung bo sot chuoi '0' va khien khoi ton dong bao thieu, dung luc
        // can chinh xac nhat.
        $soHoSo = (int) CtdtDanhSach::chuaCoKetQua(CtdtDanhSach::truyVan($loc))->count();

        if ($soHoSo === 0) {
            return ['so_ho_so' => 0, 'cu_nhat_ngay' => null];
        }

        $cuNhat = CtdtDanhSach::chuaCoKetQua(CtdtDanhSach::truyVan($loc))
            ->min('imported_at');

        return [
            'so_ho_so'     => $soHoSo,
            'cu_nhat_ngay' => $cuNhat === null
                ? null
                : (int) now()->diffInDays(\Carbon\Carbon::parse($cuNhat)),
        ];
    }
}
