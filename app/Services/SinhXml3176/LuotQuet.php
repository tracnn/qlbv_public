<?php

namespace App\Services\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
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
    const KHONG_DOI = 'khong_doi';

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
     * @return array ['ung_vien','da_ghi','bi_loai','loi','bo_qua','khong_doi'] => int; lan dau (chua co moc)
     *               them 'khoi_tao_moc' => moc vua dat, khong quet gi
     */
    public function chay($loaiBan)
    {
        $this->kiemThuMucThat();
        $kq = self::ketQuaRong();

        // Lan dau (chua co dong moc): KHONG quet lich su. Dat moc = bay gio roi thoat;
        // hoan tat ton dong bang chay bu --tu-ngay/--den-ngay.
        if (!$this->theoDoi->coMoc($loaiBan)) {
            $bayGio = (int) (new \DateTime())->format('YmdHis');
            $this->theoDoi->datMoc($loaiBan, $bayGio);
            Log::info('sinhxml3176 ' . $loaiBan . ': chua co moc, khoi tao moc = ' . $bayGio
                . ' (khong quet lich su; ton dong dung --tu-ngay/--den-ngay)');
            $kq['khoi_tao_moc'] = $bayGio;

            return $kq;
        }

        $this->batDauLuot();

        $mocCu = (int) $this->theoDoi->docMoc($loaiBan);
        $mocMax = $mocCu;
        $theoDoi = $this->theoDoi;

        $this->duyetTrang($loaiBan, self::luiMoc($mocCu, $this->cauHinh->chongLanPhut()), null, false,
            function (array $lo) use ($loaiBan, $theoDoi, &$kq, &$mocMax) {
                $can = [];
                foreach ($lo as $u) {
                    if ($theoDoi->canXet((int) $u->id, $loaiBan, $u->modify_time)) {
                        $can[] = $u;
                    } else {
                        $kq['bo_qua']++;
                    }
                }
                $this->xuLyLo($can, $loaiBan, $kq, false);
                // Chan moc: modify_time o tuong lai (lech dong ho/du lieu hong) khong duoc keo moc di qua gio that
                $tran = self::tranMoc();
                foreach ($lo as $u) {
                    $m = self::mocCua($u, $loaiBan, false);
                    if ($m > $tran) {
                        Log::warning('sinhxml3176 ' . $loaiBan . ' ho so id ' . (int) $u->id
                            . ' co modify_time ' . $m . ' o tuong lai (> ' . $tran . '), khong tien moc theo gia tri nay');
                        continue;
                    }
                    $mocMax = max($mocMax, $m);
                }
            }, $kq);

        if ($mocMax > $mocCu) {
            $this->theoDoi->datMoc($loaiBan, $mocMax);
        }

        return $kq;
    }

    /**
     * Chay bu theo TREATMENT_CODE: bo qua canXet (luon dung lai + ghi tep), khong dung moc.
     * Ket qua co them 'khong_tim_thay' (ma khong co trong HIS hoac khong qua bo loc/dieu kien loai ban).
     */
    public function chayChoMa(array $treatmentCodes, $loaiBan)
    {
        $this->kiemThuMucThat();
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
            $this->xuLyLo($phan, $loaiBan, $kq, true);
            self::ghiBoNho('sau trang');
        }
        $kq['khong_tim_thay'] = $khongThay;

        return $kq;
    }

    /**
     * Chay bu theo khoang moc (yyyyMMddHHmmss, gom hai dau) tren cot moc cua loai ban:
     * luon dung lai + ghi tep, khong dung moc. Phan trang keyset nhu chay() nhung theo cot ngay cua loai ban.
     */
    public function chayChoKhoangNgay($tu, $den, $loaiBan)
    {
        $this->kiemThuMucThat();
        $this->batDauLuot();
        $kq = self::ketQuaRong();

        $this->duyetTrang($loaiBan, (int) $tu, (int) $den, true, function (array $lo) use ($loaiBan, &$kq) {
            $this->xuLyLo($lo, $loaiBan, $kq, true);
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
        return ['ung_vien' => 0, 'da_ghi' => 0, 'bi_loai' => 0, 'loi' => 0, 'bo_qua' => 0, 'khong_doi' => 0];
    }

    /** Moc cua dong ung vien theo cot ma layLo da dung: modify_time (quet) hoac cot ngay cua loai ban (chay bu). */
    /** Tran cua moc quet: bay gio + 1 ngay (dang yyyyMMddHHmmss); bay gio lay tu HisUngVien::$homNayChung khi test dat. */
    private static function tranMoc()
    {
        $t = HisUngVien::$homNayChung !== null ? clone HisUngVien::$homNayChung : new \DateTime();
        $t->modify('+1 day');

        return (int) $t->format('YmdHis');
    }

    private static function mocCua($u, $loaiBan, $theoNgay)
    {
        $cot = $theoNgay ? HisUngVien::cotMoc($loaiBan) : HisUngVien::COT_QUET;

        return (int) $u->$cot;
    }

    /**
     * Chot an toan thu hai (ngoai lenh): ghi_thu_muc_that = true ma chua du bang thi tu choi,
     * de moi duong goi LuotQuet deu khong the ghi thu muc that khi thieu bang.
     */
    private function kiemThuMucThat()
    {
        if ($this->cauHinh->ghiThuMucThat() && !$this->dangKy->daDu()) {
            throw new \RuntimeException('Tu choi ghi vao thu muc that: chua du 14 bang. Thieu: '
                . implode(', ', $this->dangKy->thieu()));
        }
    }

    /** Danh muc + doi ma cau hinh mot lan dau luot. */
    private function batDauLuot()
    {
        $this->danhMuc = HisDanhMuc::napChoLuot($this->cauHinh->ketNoiHis());
        self::ghiBoNho('sau nap danh muc');
        $this->ungVien->doiMa();
    }

    private static function ghiBoNho($luc)
    {
        Log::info('sinhxml3176 bo nho dinh ' . $luc . ': ' . round(memory_get_peak_usage(true) / 1048576, 1) . 'MB');
    }

    /** Duyet cac trang keyset tu (tuMoc, id 0) den khi trang ngan hon kichThuocLo. */
    private function duyetTrang($loaiBan, $tuMoc, $denMoc, $theoNgay, callable $xuLy, array &$kq)
    {
        $gioiHan = max(1, $this->cauHinh->kichThuocLo());
        $sauId = 0;
        do {
            $lo = $this->ungVien->layLo($loaiBan, $tuMoc, $gioiHan, $sauId, $denMoc, $theoNgay);
            $kq['ung_vien'] += count($lo);
            if (count($lo) > 0) {
                $xuLy($lo);
                self::ghiBoNho('sau trang');
                $cuoi = $lo[count($lo) - 1];
                $tuMoc = self::mocCua($cuoi, $loaiBan, $theoNgay);
                $sauId = (int) $cuoi->id;
            }
        } while (count($lo) >= $gioiHan);
    }

    /**
     * Nap ca lo roi xu ly tung ho so theo thu tu ung vien.
     * Luot quet ($chayBu = false): noi dung trung ban da ghi => khong_doi, ho so bi loai cung ly do => chi
     * cap nhat modify_time cua dong cuoi, khong ghi dong moi. Chay bu ($chayBu = true) luon ghi.
     */
    private function xuLyLo(array $ungVien, $loaiBan, array &$kq, $chayBu)
    {
        if (empty($ungVien)) {
            return;
        }
        $ids = [];
        foreach ($ungVien as $u) {
            $ids[] = (int) $u->id;
        }
        XuLyChetLuot::datDangNap($ids);
        $duLieu = $this->napHoSo->nap($ids);

        foreach ($ungVien as $u) {
            $id = (int) $u->id;
            $dongCuoi = $chayBu ? null : $this->theoDoi->dongCuoi($id, $loaiBan);
            $ketQua = $this->motHoSo($u, $loaiBan, isset($duLieu[$id]) ? $duLieu[$id] : null, $dongCuoi);
            // Phong bi da ghi (chi co voi ban da_ghi): chi dung de luu ban sao, khong phai cot cua bang theo doi
            $noiDung = isset($ketQua['_noi_dung']) ? $ketQua['_noi_dung'] : null;
            unset($ketQua['_noi_dung']);
            $kq[$ketQua['trang_thai']]++;
            // Giai phong du lieu ho so ngay sau khi xu ly (khong giu ca lo trong bo nho)
            unset($duLieu[$id]);
            if ($ketQua['trang_thai'] === self::KHONG_DOI
                || ($dongCuoi !== null && $ketQua['trang_thai'] === self::BI_LOAI
                    && $dongCuoi->trang_thai === self::BI_LOAI && (string) $dongCuoi->ly_do === (string) $ketQua['ly_do'])) {
                $this->theoDoi->capNhatModify($dongCuoi, $ketQua['modify_time']);
            } else {
                $dongMoi = $this->theoDoi->ghi($ketQua);
                if ($ketQua['trang_thai'] === self::DA_GHI && $noiDung !== null && $dongMoi instanceof LanSinh) {
                    $this->luuBanSao($dongMoi, $noiDung);
                }
            }
            $noiDung = null;
            if ($ketQua['trang_thai'] === self::LOI) {
                Log::warning('sinhxml3176 ' . $loaiBan . ' ho so ' . $ketQua['treatment_code'] . ' loi: ' . $ketQua['ly_do']);
            }
        }
        unset($duLieu);
    }

    /**
     * @param \App\Models\SinhXml3176\LanSinh|null $dongCuoi dong theo doi cuoi cua loai ban (null khi chay bu):
     *        noi dung trung ban da_ghi cuoi => trang_thai khong_doi, khong ghi tep
     * @return array dong sinh_xml3176_lan_sinh (kem modify_time, noi_dung_hash)
     */
    private function motHoSo($u, $loaiBan, DuLieuHoSo $d = null, $dongCuoi = null)
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
            'modify_time'    => $u->modify_time === null || $u->modify_time === '' ? null : (int) $u->modify_time,
            'noi_dung_hash'  => null,
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

            // Dau van tay tinh tu CHINH $xmlCon se ghi (truoc phong bi: khong gom NGAYLAP va ten tep)
            $hash = self::dauVanTay($maCskcb, $xmlCon, $cacBang);
            $ketQua['noi_dung_hash'] = $hash;
            $thuMuc = $this->cauHinh->thuMuc($loaiBan);
            // khong_doi chi khi ban da ghi cuoi co CUNG noi dung VA nam o CUNG thu muc dich hien tai
            // (doi thu muc, vd thu -> that sau khi bat ghi_thu_muc_that, thi phai ghi lai vao thu muc moi)
            // va, voi ban chinh thuc, khong co ban thong tuyen da ghi moi hon (ban vao sau thang: phai ghi de len).
            if ($dongCuoi !== null && $dongCuoi->trang_thai === self::DA_GHI && $dongCuoi->noi_dung_hash !== null
                && $dongCuoi->noi_dung_hash === $hash
                && (string) $dongCuoi->thu_muc === (string) $thuMuc
                && !($loaiBan === CauHinhSinhXml3176::CHINH_THUC
                    && $this->theoDoi->coThongTuyenDaGhiMoiHon((int) $u->id, (int) $dongCuoi->id))) {
                $ketQua['trang_thai'] = self::KHONG_DOI;

                return $ketQua;
            }

            // Mot moc cho ca NGAYLAP va ten tep
            $luc = new \DateTime();
            $noiDung = PhongBi::chuoi($maCskcb, $xmlCon, $luc);
            $ten = GhiTep::ghi($thuMuc, $ketQua['treatment_code'], $loaiBan, $noiDung, $luc, $this->cauHinh->thuMucTam($thuMuc));

            $ketQua['trang_thai'] = self::DA_GHI;
            $ketQua['ten_tep'] = $ten;
            $ketQua['thu_muc'] = $thuMuc;
            $ketQua['cac_bang'] = implode(',', $cacBang);
            $ketQua['_noi_dung'] = $noiDung;
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

        // Hash chi co nghia voi ban da ghi (loi/bi_loai khong co noi dung de so sanh)
        if ($ketQua['trang_thai'] !== self::DA_GHI && $ketQua['trang_thai'] !== self::KHONG_DOI) {
            $ketQua['noi_dung_hash'] = null;
        }

        return $ketQua;
    }

    /**
     * Luu ban sao phong bi cua dong da_ghi vua tao; co duong thi cap nhat tep_luu. Khong bao gio nem:
     * luu tru loi (LuuTruXml da Log::warning) thi lan sinh van la da_ghi, tep_luu de trong.
     */
    private function luuBanSao($dong, $noiDung)
    {
        $tuongDoi = (new LuuTruXml($this->cauHinh->thuMucLuu()))->luu($dong, $noiDung);
        if ($tuongDoi === null) {
            return;
        }
        try {
            $dong->tep_luu = $tuongDoi;
            $dong->save();
        } catch (\Throwable $e) {
            Log::warning('sinhxml3176 luu tru ban sao dong ' . $dong->id . ': khong cap nhat duoc tep_luu: ' . $e->getMessage());
        }
    }

    /**
     * sha1 cua ma CSKCB + cac cap (loai, XML con) cua bang khac rong, theo so bang tang dan.
     * Dung serialize de ranh gioi giua cac bang khong the mo ho (ghep chuoi tran co the trung khi doi cho cat).
     */
    public static function dauVanTay($maCskcb, array $xmlCon, array $cacBang)
    {
        $cap = [];
        foreach ($cacBang as $loai) {
            $cap[] = [(string) $loai, (string) $xmlCon[$loai]];
        }

        return sha1(serialize([(string) $maCskcb, $cap]));
    }
}
