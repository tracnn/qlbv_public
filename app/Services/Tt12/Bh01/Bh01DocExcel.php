<?php

namespace App\Services\Tt12\Bh01;

use App\Services\Tt12\Tt12DocExcel;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

/**
 * Doc va kiem tep .xlsx 01/BH (dinh dang HSTH01BH.xlsx cua hisnguonmo; spec nhap/xuat Excel muc 3, 4.2-4.5).
 *
 * Doc bang Spout (dang luong, RAM phang); dung lai Tt12DocExcel::chuanHoaO cho moi o.
 *
 * HAI LUOT: kiem() doc het tep va khong ghi gi; duyetLo() doc lai de dua dong da chuan hoa cho
 * Bh01GhiBan. Chi goi duyetLo() sau khi kiem() sach.
 *
 * Doc het tep (tru khi qua 100.000 dong): dem cap (co so, ky) la den cuoi tep (thong bao pham vi
 * day du); loi tung dong chi giu 20 dong dau. Dong bao loi la so dong Excel THAT (ke ca dong trong).
 */
class Bh01DocExcel
{
    const TOI_DA_DONG = 100000;
    const TOI_DA_LOI  = 20;
    const CO_LO       = 500;      // so dong moi lo giao cho duyetLo()

    private static $cot_tien = array(
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
    );

    /**
     * @param string $duongDan
     * @param string $maCskcb co so dang chon
     * @param array  $ky      ket qua Bh01Ky::tu()
     * @return array so_dong, so_dong_canh_bao, loi (<= 20), cot_thua, t_tongchi_bv, t_bhtt
     * @throws Bh01TepExcelHongException khi thu vien doc khong mo duoc tep
     */
    public function kiem($duongDan, $maCskcb, array $ky)
    {
        $kq = array(
            'so_dong' => 0, 'so_dong_canh_bao' => 0, 'loi' => array(), 'cot_thua' => array(),
            't_tongchi_bv' => 0.0, 't_bhtt' => 0.0,
        );
        $loiDong = array();
        $cap = array();          // 'co so|thang|nam' => ['co_so', 'thang', 'nam', 'so', ]
        $dongLa = array();       // so dong Excel cua cac dong lech pham vi, theo thu tu
        $loiTieuDe = array();
        $quaTran = false;
        $anhXa = array();

        $this->doc(
            $duongDan,
            function ($theoTen, $soDong) use (&$kq, &$loiDong, &$cap, &$dongLa, $maCskcb, $ky) {
                $d = self::chuanHoaDong($theoTen);

                // Pham vi (4.3) tren gia tri DA chuan hoa: '1929' da thanh '01929'.
                if ($d['ma_cskcb'] !== (string) $maCskcb || $d['nam_qt'] !== $ky['nam'] || $d['thang_qt'] !== $ky['thang']) {
                    $khoa = $d['ma_cskcb'] . '|' . $d['thang_qt'] . '|' . $d['nam_qt'];
                    if (!isset($cap[$khoa])) {
                        $cap[$khoa] = array('co_so' => $d['ma_cskcb'], 'thang' => $d['thang_qt'], 'nam' => $d['nam_qt'], 'so' => 0);
                    }
                    $cap[$khoa]['so']++;
                    if (count($dongLa) <= self::TOI_DA_LOI) {
                        $dongLa[] = $soDong;
                    }
                }

                $k = Bh01Kiem::dong($d);
                if (count($k['canh_bao']) > 0) {
                    $kq['so_dong_canh_bao']++;
                }
                if (count($k['chan']) > 0 && count($loiDong) < self::TOI_DA_LOI) {
                    $loiDong[] = 'Dòng ' . $soDong . ': ' . implode('; ', $k['chan']);
                }

                $kq['so_dong']++;
                // Tien khong phai so da bi Bh01Kiem chan; chi cong tien hop le.
                if (is_numeric($d['t_tongchi_bv'])) {
                    $kq['t_tongchi_bv'] += (float) $d['t_tongchi_bv'];
                }
                if (is_numeric($d['t_bhtt'])) {
                    $kq['t_bhtt'] += (float) $d['t_bhtt'];
                }
            },
            function ($thieu, $trung, $thua) use (&$loiTieuDe, &$kq) {
                if ($thieu) {
                    $loiTieuDe[] = 'Thiếu cột: ' . implode(', ', $thieu);
                }
                if ($trung) {
                    $loiTieuDe[] = 'Trùng cột: ' . implode(', ', $trung);
                }
                $kq['cot_thua'] = $thua;
            },
            $quaTran
        );

        if ($loiTieuDe) {
            $kq['loi'] = $loiTieuDe;
            return $kq;
        }

        if ($quaTran) {
            $kq['loi'] = array('Tệp có hơn 100.000 dòng.');
            return $kq;
        }

        if ($kq['so_dong'] === 0) {
            $kq['loi'] = array('Tệp không có hồ sơ nào.');
            return $kq;
        }

        $loi = $loiDong;
        if ($cap) {
            $moTa = array();
            $tong = 0;
            foreach ($cap as $c) {
                $moTa[] = $c['co_so'] . ' kỳ ' . $c['thang'] . '/' . $c['nam'] . ' (' . $c['so'] . ' dòng)';
                $tong += $c['so'];
            }
            $dau = array_slice($dongLa, 0, self::TOI_DA_LOI);
            $thongBao = 'Tệp có hồ sơ khác cơ sở/kỳ đang chọn: ' . implode(', ', $moTa)
                . '. Dòng đầu tiên: ' . implode(', ', $dau) . ($tong > count($dau) ? ', …' : '');
            // Giu tong <= 20: nhuong cho thong bao pham vi vi no giai thich nguyen nhan chinh.
            $loi = array_slice($loiDong, 0, self::TOI_DA_LOI - 1);
            $loi[] = $thongBao;
        }
        $kq['loi'] = $loi;

        return $kq;
    }

    /**
     * Goi $nhanLo voi tung lo phan tu dung hinh dang Bh01GhiBan. Chi goi sau khi kiem() sach.
     */
    public function duyetLo($duongDan, callable $nhanLo)
    {
        $quaTran = false;
        $this->doc(
            $duongDan,
            null,
            function () {
            },
            $quaTran,
            function (array $lo) use ($nhanLo) {
                call_user_func($nhanLo, $lo);
            }
        );
    }

    /**
     * 19 khoa thuong tu mot dong khoa theo TEN THE VIET HOA (spec 4.4). Ham thuan.
     */
    public static function chuanHoaDong(array $theoTen)
    {
        $ra = array();
        foreach (Bh01DacTa::the() as $dt) {
            if ($dt['the'] === 'STT') {
                continue;     // STT do Bh01GhiBan danh lai, gia tri trong tep bi bo
            }
            $the = $dt['the'];
            $v = isset($theoTen[$the]) ? Tt12DocExcel::chuanHoaO($theoTen[$the]) : '';

            if ($the === 'MA_CSKCB') {
                // Excel doi '01929' thanh so 1929.
                if (ctype_digit($v) && strlen($v) < 5) {
                    $v = str_pad($v, 5, '0', STR_PAD_LEFT);
                }
            } elseif ($the === 'MA_LOAI_KCB' || $the === 'THANG_QT') {
                if (ctype_digit($v) && strlen($v) === 1) {
                    $v = '0' . $v;
                }
            } elseif ($dt['kieu'] === 'tien') {
                // Khong phai so thi giu chuoi goc de Bh01Kiem chan (khong am tham doi thanh '0').
                if (is_numeric($v)) {
                    $v = Bh01ChuanHoa::tien($v);
                }
            } elseif ($the === 'MA_THE_BHYT') {
                $phan = explode(';', $v);
                $v = trim($phan[0]);
            }

            $ra[strtolower($the)] = $v;
        }

        return $ra;
    }

    /**
     * Doc tep BANG SPOUT (dang luong): anh xa tieu de theo ten, doi tung dong sang mang khoa theo ten the.
     *
     * Vi sao Spout: Maatwebsite/PhpSpreadsheet giu o trong RAM - tep that 21.125 dong mat ~23 s va
     * 112-126 MB moi luot (nhap doc hai luot + ghi => sat tran 128 MB). Spout doc tung dong, RAM phang.
     *
     * Chi doc sheet DAU TIEN. Giu dong trong (setShouldPreserveEmptyRows) de dong dau tep luon la
     * tieu de nhu truoc va so dong Excel dung tuyet doi; dong trong do ta tu bo.
     *
     * @param callable|null $xuLyDong nhan (array $theoTen, int $soDongExcel) - dung cho kiem()
     * @param callable      $nhanTieuDe nhan (thieu[], trung[], thua[]) mot lan
     * @param bool          &$quaTran bao hieu tep vuot TOI_DA_DONG (dung doc ngay)
     * @param callable|null $nhanLo   nhan (array phan_tu) moi lo LO_DUYET dong - dung cho duyetLo()
     */
    private function doc($duongDan, $xuLyDong, callable $nhanTieuDe, &$quaTran, $nhanLo = null)
    {
        $anhXa = null;      // vi tri cot => ten the; null = tieu de loi, bo qua du lieu
        $daDoc = 0;
        $lo = array();
        // Loi nem ra tu callback (vd QueryException khi ghi lo) khong phai loi tep: giu lai de nem nguyen
        // ban, khong boc thanh "tep hong" lam nguoi dung tuong tep sai trong khi that ra loi may chu.
        $loiCallback = null;
        $reader = null;

        try {
            $thuMucTam = storage_path('app/xuat-tam');
            if (!is_dir($thuMucTam) && !@mkdir($thuMucTam, 0777, true) && !is_dir($thuMucTam)) {
                $thuMucTam = sys_get_temp_dir();
            }

            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->setShouldPreserveEmptyRows(true);
            // Shared strings lon duoc Spout ghi ra tep tam thay vi giu trong RAM.
            $reader->setTempFolder($thuMucTam);
            $reader->open($duongDan);

            $daBaoTieuDe = false;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $soDong => $row) {
                    $o = $row->toArray();

                    if (!$daBaoTieuDe) {
                        $daBaoTieuDe = true;
                        $anhXa = $this->anhXaTieuDe(array_values($o), $nhanTieuDe);
                        continue;
                    }
                    if ($anhXa === null) {
                        break;      // tieu de loi: khong can doc du lieu
                    }

                    // Bo dong ma MOI o deu rong (tep sua tay hay keo theo hang tram dong trong).
                    $trong = true;
                    foreach ($o as $gt) {
                        if (Tt12DocExcel::chuanHoaO($gt) !== '') {
                            $trong = false;
                            break;
                        }
                    }
                    if ($trong) {
                        continue;
                    }

                    if ($daDoc + 1 > self::TOI_DA_DONG) {
                        $quaTran = true;
                        break;
                    }
                    $daDoc++;

                    // Spout cat o rong o cuoi dong: vi tri thieu duoc coi la null.
                    $theoTen = array();
                    foreach ($anhXa as $viTri => $the) {
                        $theoTen[$the] = isset($o[$viTri]) ? $o[$viTri] : null;
                    }

                    try {
                        if ($xuLyDong !== null) {
                            call_user_func($xuLyDong, $theoTen, $soDong);
                        } else {
                            $lo[] = array(
                                'du_lieu' => self::chuanHoaDong($theoTen),
                                'ma_lk'   => null,
                                'vi_tri'  => 'Dòng ' . $soDong,
                            );
                            if (count($lo) >= self::CO_LO) {
                                $goi = $lo;
                                $lo = array();
                                call_user_func($nhanLo, $goi);
                            }
                        }
                    } catch (\Exception $e) {
                        $loiCallback = $e;
                        throw $e;
                    }
                }
                break;      // chi sheet dau tien
            }

            if (!$daBaoTieuDe) {
                $this->anhXaTieuDe(array(), $nhanTieuDe);
            }

            if ($nhanLo !== null && $lo) {
                try {
                    call_user_func($nhanLo, $lo);
                } catch (\Exception $e) {
                    $loiCallback = $e;
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            if ($loiCallback !== null) {
                throw $loiCallback;
            }
            throw new Bh01TepExcelHongException('Không đọc được tệp Excel: ' . $e->getMessage(), 0, $e);
        } finally {
            if ($reader !== null) {
                try {
                    $reader->close();
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * Tieu de theo ten (khong phan biet hoa thuong): bao thieu/trung/thua qua $nhanTieuDe.
     *
     * @return array|null vi tri cot => ten the; null khi thieu/trung cot
     */
    private function anhXaTieuDe(array $tieuDe, callable $nhanTieuDe)
    {
        $theHopLe = array();
        foreach (Bh01DacTa::the() as $dt) {
            $theHopLe[$dt['the']] = array();
        }
        $thua = array();
        foreach ($tieuDe as $viTri => $o) {
            $goc = Tt12DocExcel::chuanHoaO($o);
            if ($goc === '') {
                continue;
            }
            $ten = strtoupper($goc);
            if (isset($theHopLe[$ten])) {
                $theHopLe[$ten][] = $viTri;
            } else {
                $thua[] = $goc;
            }
        }

        $thieu = array();
        $trung = array();
        $map = array();
        foreach ($theHopLe as $the => $viTris) {
            if (count($viTris) === 0) {
                $thieu[] = $the;
            } elseif (count($viTris) > 1) {
                $trung[] = $the;
            } else {
                $map[$viTris[0]] = $the;
            }
        }

        call_user_func($nhanTieuDe, $thieu, $trung, $thua);

        return ($thieu || $trung) ? null : $map;
    }
}
