<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Thao tac tren bang tra vat pham kem DVKT me tu man quan tri. Loi dau vao nem
 * InvalidArgumentException (controller doi thanh 422). Moi thao tac ghi vet nguoi sua.
 */
class VatPhamKemDvktQuanTri
{
    const THANG_KIEM_KHOP = 12;
    const TOI_DA_TEN_KHOP = 50;
    const TOI_DA_TIM = 30;

    public function datHieuLuc(Xml3176VatPhamKemDvkt $m, bool $hieuLuc, $ghiChu, $nguoiSuaId): void
    {
        $ghiChu = self::ghiChuHopLe($ghiChu);
        if (!$hieuLuc && $ghiChu === null) {
            throw new \InvalidArgumentException('Cần ghi lý do khi tắt');
        }
        $m->fill(['hieu_luc' => $hieuLuc, 'ghi_chu' => $ghiChu] + self::vet($nguoiSuaId))->save();
    }

    public function datGhiChu(Xml3176VatPhamKemDvkt $m, $ghiChu, $nguoiSuaId): void
    {
        $m->fill(['ghi_chu' => self::ghiChuHopLe($ghiChu)] + self::vet($nguoiSuaId))->save();
    }

    public function xoa(Xml3176VatPhamKemDvkt $m): void
    {
        if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_TAY) {
            // Xoa dong HOC thi lan hoc sau them lai - chi cho tat.
            throw new \InvalidArgumentException('Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực');
        }
        $m->delete();
    }

    public function themDongTay($loai, $ma, $ten, array $dsMaDvkt, array $tenDvkt, $ghiChu, $nguoiSuaId): array
    {
        $loai = self::loaiHopLe($loai);
        $ma = trim((string) $ma);
        if ($ma === '' || mb_strlen($ma, 'UTF-8') > 100) {
            throw new \InvalidArgumentException('Mã vật phẩm phải từ 1 đến 100 ký tự');
        }
        $ten = trim((string) $ten);
        if ($loai === Xml3176VatPhamKemDvkt::LOAI_VTYT && VatPhamKemDvktKey::laMaNhomChung($ma) && $ten === '') {
            throw new \InvalidArgumentException('VTYT mã nhóm chung cần tên vật phẩm đúng như trên XML');
        }
        $ghiChu = self::ghiChuHopLe($ghiChu);

        $maDv = [];
        foreach ($dsMaDvkt as $x) {
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($x);
            if ($k === '' || mb_strlen($k, 'UTF-8') > 50) {
                throw new \InvalidArgumentException('Mã DVKT phải từ 1 đến 50 ký tự');
            }
            $maDv[$k] = true;
        }
        if (empty($maDv)) {
            throw new \InvalidArgumentException('Chưa chọn DVKT mẹ');
        }
        $maDv = array_keys($maDv);

        $tenKhop = VatPhamKemDvktKey::tenKhop($loai, $ma, $ten);
        $tenDanhMuc = ServiceCatalog::whereIn('ma_dich_vu', $maDv)->pluck('ten_dich_vu', 'ma_dich_vu')->all();
        $kq = ['them' => [], 'da_co' => []];

        DB::transaction(function () use ($loai, $ma, $ten, $tenKhop, $maDv, $tenDvkt, $tenDanhMuc, $ghiChu, $nguoiSuaId, &$kq) {
            foreach ($maDv as $k) {
                $co = Xml3176VatPhamKemDvkt::where([
                    'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_khop' => $tenKhop, 'ma_dvkt' => $k,
                ])->exists();
                if ($co) {
                    $kq['da_co'][] = $k;
                    continue;
                }
                Xml3176VatPhamKemDvkt::create([
                    'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_vat_pham' => $ten === '' ? null : $ten,
                    'ten_khop' => $tenKhop, 'ma_dvkt' => $k,
                    'ten_dvkt' => $tenDvkt[$k] ?? $tenDanhMuc[$k] ?? null,
                    'nguon' => Xml3176VatPhamKemDvkt::NGUON_TAY, 'so_lan_gan' => 0, 'ty_le_gan' => null,
                    'hieu_luc' => true, 'ghi_chu' => $ghiChu,
                ] + self::vet($nguoiSuaId));
                $kq['them'][] = $k;
            }
        });

        if (empty($kq['them'])) {
            throw new \InvalidArgumentException('Tất cả cặp đã có trong bảng tra: ' . implode(', ', $kq['da_co']));
        }

        return $kq;
    }

    /**
     * Ten that cua ma vat pham tren XML 12 thang gan nhat. VTYT ma nhom chung khop theo ma+ten,
     * nen nguoi duyet phai chon dung mot ten o day - go ten lech thi dong tay khong bao gio khop.
     */
    public function kiemKhop($loai, $ma, Carbon $bayGio = null): array
    {
        $loai = self::loaiHopLe($loai);
        $ma = trim((string) $ma);
        if ($ma === '') {
            throw new \InvalidArgumentException('Mã vật phẩm phải từ 1 đến 100 ký tự');
        }
        $n = VatPhamKemDvktLearner::NGUON[$loai];
        $tu = ($bayGio ?: Carbon::now())->copy()->subMonths(self::THANG_KIEM_KHOP)->format('Ymd');

        $rows = DB::table($n['bang'])
            ->selectRaw("{$n['ten']} as ten, COUNT(*) as so_dong, "
                . 'SUM(CASE WHEN t_bhtt > 0 THEN 1 ELSE 0 END) as so_dong_bhyt, COUNT(DISTINCT ma_lk) as so_ho_so')
            ->where($n['ma'], $ma)
            ->where('ngay_yl', '>=', $tu)
            ->groupBy($n['ten'])
            ->orderBy('so_dong', 'desc')
            ->limit(self::TOI_DA_TEN_KHOP)
            ->get();

        $ds = [];
        foreach ($rows as $r) {
            $ds[] = [
                'ten' => (string) $r->ten,
                'ten_khop' => VatPhamKemDvktKey::tenKhop($loai, $ma, $r->ten),
                'so_dong' => (int) $r->so_dong,
                'so_dong_bhyt' => (int) $r->so_dong_bhyt,
                'so_ho_so' => (int) $r->so_ho_so,
            ];
        }

        return [
            'ma_nhom_chung' => $loai === Xml3176VatPhamKemDvkt::LOAI_VTYT && VatPhamKemDvktKey::laMaNhomChung($ma),
            'ds' => $ds,
        ];
    }

    public function timVatPham($loai, $q): array
    {
        $loai = self::loaiHopLe($loai);
        if ($loai === Xml3176VatPhamKemDvkt::LOAI_VTYT) {
            return self::tim(MedicalSupplyCatalog::query(), 'ma_vat_tu', 'ten_vat_tu', $q);
        }

        return self::tim(MedicineCatalog::query(), 'ma_thuoc', 'ten_thuoc', $q);
    }

    public function timDvkt($q): array
    {
        return self::tim(ServiceCatalog::query(), 'ma_dich_vu', 'ten_dich_vu', $q);
    }

    private static function tim($query, string $cotMa, string $cotTen, $q): array
    {
        $q = trim((string) $q);
        if ($q === '') {
            return [];
        }

        $rows = $query->select($cotMa . ' as ma', $cotTen . ' as ten')
            ->where(function ($w) use ($cotMa, $cotTen, $q) {
                $w->where($cotMa, 'like', $q . '%')->orWhere($cotTen, 'like', '%' . $q . '%');
            })
            ->groupBy($cotMa, $cotTen)
            ->orderBy($cotMa)
            ->limit(self::TOI_DA_TIM)
            ->get();

        $ra = [];
        foreach ($rows as $r) {
            $ra[] = ['id' => (string) $r->ma, 'text' => $r->ma . ' — ' . $r->ten];
        }

        return $ra;
    }

    private static function loaiHopLe($loai): string
    {
        $loai = strtoupper(trim((string) $loai));
        if (!in_array($loai, [Xml3176VatPhamKemDvkt::LOAI_VTYT, Xml3176VatPhamKemDvkt::LOAI_THUOC], true)) {
            throw new \InvalidArgumentException('Loại không hợp lệ (VTYT hoặc THUOC)');
        }

        return $loai;
    }

    private static function ghiChuHopLe($ghiChu)
    {
        $ghiChu = trim((string) $ghiChu);
        if ($ghiChu === '') {
            return null;
        }
        if (mb_strlen($ghiChu, 'UTF-8') > 1024) {
            throw new \InvalidArgumentException('Ghi chú tối đa 1024 ký tự');
        }

        return $ghiChu;
    }

    private static function vet($nguoiSuaId): array
    {
        return ['nguoi_sua_id' => $nguoiSuaId === null ? null : (int) $nguoiSuaId, 'sua_luc' => Carbon::now()];
    }
}
