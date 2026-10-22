<?php

namespace App\Services\SinhXml3176;

use App\Services\SinhXml3176\Bang\DangKyBang;
use App\Services\SinhXml3176\Bang\LoiDungBang;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use Illuminate\Support\Facades\Log;

/**
 * Dieu phoi mot luot sinh XML3176: chon ung vien -> loc da sinh -> nap lo -> kiem dieu kien ->
 * dung bang -> phong bi -> ghi tep -> ghi theo doi.
 *
 * Phan trang keyset (moc, id) trong mot luot: luot lui moc chongLanPhut() phut nen co the doc lai
 * nhieu ho so da sinh; neu chi lay kichThuocLo dong moi luot thi khi so ho so trong cua so chong lan
 * vuot kichThuocLo, moc khong bao gio tien. Moi trang xu ly xong moi doc trang sau; moc dat MOT lan
 * sau khi het trang (gia tri lon nhat da gap, khong bao gio lui).
 * Loi cua mot ho so (LoiDungBang hay bat ky \Throwable nao) ghi trang thai 'loi' + Log::warning, khong dung luot.
 * Rieng loi moi truong (LoiGhiTep, loi TheoDoiSinh::ghi) dung ca luot, khong ghi theo doi, khong tien moc.
 */
class LuotQuet
{
    const DA_GHI = 'da_ghi';
    const BI_LOAI = 'bi_loai';
    const LOI = 'loi';

    private $cauHinh;
    private $ungVien;
    private $napHoSo;
    private $theoDoi;
    private $dangKy;
    private $kiem;

    /** @var HisDanhMuc danh muc cua luot hien tai */
    private $danhMuc;

    public function __construct(
        CauHinhSinhXml3176 $cauHinh,
        HisUngVien $ungVien,
        HisNapHoSo $napHoSo,
        TheoDoiSinh $theoDoi,
        DangKyBang $dangKy,
        KiemDieuKien $kiem
    ) {
        $this->cauHinh = $cauHinh;
        $this->ungVien = $ungVien;
        $this->napHoSo = $napHoSo;
        $this->theoDoi = $theoDoi;
        $this->dangKy = $dangKy;
        $this->kiem = $kiem;
    }

    /**
     * Luot quet theo moc.
     *
     * @param string $loaiBan CauHinhSinhXml3176::CHINH_THUC | THONG_TUYEN
     * @return array ['ung_vien','da_ghi','bi_loai','loi','bo_qua'] => int
     */
    public function chay($loaiBan)
    {
        $this->batDauLuot();
        $kq = self::ketQuaRong();

        $mocCu = (int) $this->theoDoi->docMoc($loaiBan);
        $mocMax = $mocCu;
        $theoDoi = $this->theoDoi;

        $this->duyetTrang($loaiBan, self::luiMoc($mocCu, $this->cauHinh->chongLanPhut()), null,
            function (array $lo) use ($loaiBan, $theoDoi, &$kq, &$mocMax) {
                $can = [];
                foreach ($lo as $u) {
                    if ($theoDoi->canSinh((int) $u->id, $loaiBan, $u->fee_lock_time, $u->out_time)) {
                        $can[] = $u;
                    } else {
                        $kq['bo_qua']++;
                    }
                }
                $this->xuLyLo($can, $loaiBan, $kq);
                foreach ($lo as $u) {
                    $mocMax = max($mocMax, self::mocCua($u, $loaiBan));
                }
            }, $kq);

        if ($mocMax > $mocCu) {
            $this->theoDoi->datMoc($loaiBan, $mocMax);
        }

        return $kq;
    }

    /**
     * Chay bu theo TREATMENT_CODE: bo qua canSinh, khong dung moc.
     * Ket qua co them 'khong_tim_thay' (ma khong co trong HIS hoac khong qua bo loc/dieu kien loai ban).
     */
    public function chayChoMa(array $treatmentCodes, $loaiBan)
    {
        $this->batDauLuot();
        $kq = self::ketQuaRong();

        $lo = $this->ungVien->layTheoMa($loaiBan, $treatmentCodes);
        $kq['ung_vien'] = count($lo);

        $thay = [];
        foreach ($lo as $u) {
            $thay[trim((string) $u->treatment_code)] = true;
        }
        $khongThay = [];
        foreach ($treatmentCodes as $ma) {
            $ma = trim((string) $ma);
            if ($ma !== '' && !isset($thay[$ma]) && !in_array($ma, $khongThay, true)) {
                $khongThay[] = $ma;
            }
        }

        foreach (array_chunk($lo, max(1, $this->cauHinh->kichThuocLo())) as $phan) {
            $this->xuLyLo($phan, $loaiBan, $kq);
        }
        $kq['khong_tim_thay'] = $khongThay;

        return $kq;
    }

    /**
     * Chay bu theo khoang moc (yyyyMMddHHmmss, gom hai dau) tren cot moc cua loai ban:
     * bo qua canSinh, khong dung moc. Phan trang keyset nhu chay().
     */
    public function chayChoKhoangNgay($tu, $den, $loaiBan)
    {
        $this->batDauLuot();
        $kq = self::ketQuaRong();

        $this->duyetTrang($loaiBan, (int) $tu, (int) $den, function (array $lo) use ($loaiBan, &$kq) {
            $this->xuLyLo($lo, $loaiBan, $kq);
        }, $kq);

        return $kq;
    }

    /**
     * Lui moc N phut theo lich (khong tru so hoc). Moc 0 giu nguyen 0.
     *
     * @return int
     */
    public static function luiMoc($moc, $phut)
    {
        $moc = (int) $moc;
        if ($moc === 0 || (int) $phut <= 0) {
            return $moc;
        }
        $t = \DateTime::createFromFormat('YmdHis', (string) $moc);
        if ($t === false) {
            throw new \UnexpectedValueException('Moc quet khong hop le: ' . $moc);
        }
        $t->modify('-' . (int) $phut . ' minutes');

        return (int) $t->format('YmdHis');
    }

    private static function ketQuaRong()
    {
        return ['ung_vien' => 0, 'da_ghi' => 0, 'bi_loai' => 0, 'loi' => 0, 'bo_qua' => 0];
    }

    private static function mocCua($u, $loaiBan)
    {
        $cot = HisUngVien::cotMoc($loaiBan);

        return (int) $u->$cot;
    }

    /** Danh muc + doi ma cau hinh mot lan dau luot. */
    private function batDauLuot()
    {
        $this->danhMuc = HisDanhMuc::napChoLuot($this->cauHinh->ketNoiHis());
        $this->ungVien->doiMa();
    }

    /** Duyet cac trang keyset tu (tuMoc, id 0) den khi trang ngan hon kichThuocLo. */
    private function duyetTrang($loaiBan, $tuMoc, $denMoc, callable $xuLy, array &$kq)
    {
        $gioiHan = max(1, $this->cauHinh->kichThuocLo());
        $sauId = 0;
        do {
            $lo = $this->ungVien->layLo($loaiBan, $tuMoc, $gioiHan, $sauId, $denMoc);
            $kq['ung_vien'] += count($lo);
            if (count($lo) > 0) {
                $xuLy($lo);
                $cuoi = $lo[count($lo) - 1];
                $tuMoc = self::mocCua($cuoi, $loaiBan);
                $sauId = (int) $cuoi->id;
            }
        } while (count($lo) >= $gioiHan);
    }

    /** Nap ca lo roi xu ly tung ho so theo thu tu ung vien. */
    private function xuLyLo(array $ungVien, $loaiBan, array &$kq)
    {
        if (empty($ungVien)) {
            return;
        }
        $ids = [];
        foreach ($ungVien as $u) {
            $ids[] = (int) $u->id;
        }
        $duLieu = $this->napHoSo->nap($ids);

        foreach ($ungVien as $u) {
            $id = (int) $u->id;
            $ketQua = $this->motHoSo($u, $loaiBan, isset($duLieu[$id]) ? $duLieu[$id] : null);
            $kq[$ketQua['trang_thai']]++;
            $this->theoDoi->ghi($ketQua);
            if ($ketQua['trang_thai'] === self::LOI) {
                Log::warning('sinhxml3176 ' . $loaiBan . ' ho so ' . $ketQua['treatment_code'] . ' loi: ' . $ketQua['ly_do']);
            }
        }
    }

    /** @return array dong sinh_xml3176_lan_sinh */
    private function motHoSo($u, $loaiBan, DuLieuHoSo $d = null)
    {
        $ketQua = [
            'treatment_id'   => (int) $u->id,
            'treatment_code' => (string) $u->treatment_code,
            'loai_ban'       => $loaiBan,
            'fee_lock_time'  => $u->fee_lock_time === null || $u->fee_lock_time === '' ? null : (int) $u->fee_lock_time,
            'out_time'       => $u->out_time === null || $u->out_time === '' ? null : (int) $u->out_time,
            'trang_thai'     => self::LOI,
            'ly_do'          => null,
            'ten_tep'        => null,
            'thu_muc'        => null,
            'cac_bang'       => null,
        ];

        try {
            if ($d === null) {
                throw new \RuntimeException('Không nạp được hồ sơ từ HIS (v_his_treatment_12)');
            }

            $lyDo = $this->kiem->lyDoLoai($d, $loaiBan, $this->danhMuc);
            if ($lyDo !== null) {
                $ketQua['trang_thai'] = self::BI_LOAI;
                $ketQua['ly_do'] = $lyDo;

                return $ketQua;
            }

            $maCskcb = $this->ungVien->maCskcb($d->hoSo->branch_id);
            if ($maCskcb === null) {
                throw new \RuntimeException('Không xác định được MACSKCB: cơ sở ID ' . $d->hoSo->branch_id
                    . ' không có HEIN_MEDI_ORG_CODE trong HIS_BRANCH');
            }

            $daDung = [];
            foreach ($this->dangKy->cacDung() as $dung) {
                $daDung[$dung->loai()] = $dung->dung($d, $this->danhMuc, $daDung);
            }
            $xmlCon = [];
            foreach ($daDung as $loai => $dong) {
                if (!empty($dong)) {
                    $xmlCon[$loai] = XmlCon::chuoi($loai, $dong);
                }
            }
            $cacBang = array_keys($xmlCon);
            usort($cacBang, function ($a, $b) {
                return (int) substr($a, 3) - (int) substr($b, 3);
            });

            // Mot moc cho ca NGAYLAP va ten tep
            $luc = new \DateTime();
            $thuMuc = $this->cauHinh->thuMuc($loaiBan);
            $ten = GhiTep::ghi($thuMuc, $ketQua['treatment_code'], $loaiBan,
                PhongBi::chuoi($maCskcb, $xmlCon, $luc), $luc);

            $ketQua['trang_thai'] = self::DA_GHI;
            $ketQua['ten_tep'] = $ten;
            $ketQua['thu_muc'] = $thuMuc;
            $ketQua['cac_bang'] = implode(',', $cacBang);
        } catch (LoiGhiTep $e) {
            // Loi moi truong: khong danh dau ho so, dung ca luot (moc khong tien), luot sau thu lai
            throw $e;
        } catch (LoiDungBang $e) {
            $ketQua['trang_thai'] = self::LOI;
            $ketQua['ly_do'] = $e->loaiBang() . ': ' . $e->getMessage();
        } catch (\Throwable $e) {
            $ketQua['trang_thai'] = self::LOI;
            $ketQua['ly_do'] = $e->getMessage() !== '' ? $e->getMessage() : get_class($e);
        }

        return $ketQua;
    }
}
