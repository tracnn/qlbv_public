<?php

namespace App\Services\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Tao BAN (anh chup dong bang) 01/BH cho mot (co so, ky) tu xml3176_xml1s - spec 4.1-4.3, 5.1, 5.2.
 *
 * VI SAO CHUP DONG BANG: ban da ky/gui khong duoc doi khi XML1 duoc sua ve sau; nen chep gia tri
 * DA CHUAN HOA vao bh01_dong va tinh tong tu chinh cac dong da ghi (khong truy van tong rieng).
 *
 * VI SAO HAI LUOT: luot 1 chi doc + kiem de dung som neu co dong chan (khong ghi gi); luot 2 moi
 * ghi, trong mot transaction. Ca hai doc theo lo (chunk) vi mot ky co the hang chuc nghin dong
 * va PHP chi 128MB. Thu tu on dinh nho truyVan ket thuc bang ma_lk.
 */
class Bh01TaoBan
{
    const LO_DOC     = 500;
    const LO_GHI     = 500;
    const THU_LAI    = 3;
    const MAU_TOI_DA = 20;

    private static $cot_tien = array(
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
    );

    /** Tong hop nguon + so dong chan/canh bao + toi da 20 loi chan dau (dang "MA_LK x: ly do"). */
    public function xemTruoc($maCskcb, array $ky)
    {
        $kq = Bh01Nguon::tongHop($maCskcb, $ky);
        $kiem = $this->duyetKiem($maCskcb, $ky, false);

        return $kq + array(
            'so_dong_chan'     => $kiem['so_dong_chan'],
            'so_dong_canh_bao' => $kiem['so_dong_canh_bao'],
            'loi_chan_mau'     => $kiem['loi_chan_mau'],
        );
    }

    public function tao($maCskcb, array $ky, $taoBoi)
    {
        $kiem = $this->duyetKiem($maCskcb, $ky, true);

        if ($kiem['so_dong'] === 0) {
            throw new Bh01KhongTaoDuocException(
                'Kỳ ' . $ky['thang'] . '/' . $ky['nam'] . ' của cơ sở ' . $maCskcb . ' không có hồ sơ BHYT nào.'
            );
        }
        if ($kiem['so_dong_chan'] > 0) {
            throw new Bh01KhongTaoDuocException(
                'Có ' . $kiem['so_dong_chan'] . ' hồ sơ bị chặn, chưa tạo được bản.'
            );
        }

        for ($lan = 1; ; $lan++) {
            $soBan = $this->soBanKeTiep($maCskcb, $ky);
            try {
                return DB::transaction(function () use ($maCskcb, $ky, $taoBoi, $soBan) {
                    return $this->ghi($maCskcb, $ky, $taoBoi, $soBan);
                });
            } catch (QueryException $e) {
                // Hai nguoi tao cung luc: nguoi sau trung (co so, ky, so_ban) -> doc lai so ke tiep.
                // Chi bat vi pham rang buoc duy nhat (SQLSTATE 23000); loi khac nem lai.
                if (!$this->laTrungKhoa($e) || $lan >= self::THU_LAI) {
                    throw $e;
                }
            }
        }
    }

    protected function soBanKeTiep($maCskcb, array $ky)
    {
        $max = Bh01Ban::where('ma_cskcb', $maCskcb)
            ->where('nam_qt', $ky['nam'])
            ->where('thang_qt', $ky['thang'])
            ->max('so_ban');

        return (int) $max + 1;
    }

    private function laTrungKhoa(QueryException $e)
    {
        $ma = isset($e->errorInfo[0]) ? $e->errorInfo[0] : $e->getCode();

        return (string) $ma === '23000';
    }

    /** Luot 1: duyet theo lo, dem va kiem. $dungSom: dung ngay khi lo hien tai co dong chan (khong can mau). */
    private function duyetKiem($maCskcb, array $ky, $dungSom)
    {
        $kq = array('so_dong' => 0, 'so_dong_chan' => 0, 'so_dong_canh_bao' => 0, 'loi_chan_mau' => array());

        Bh01Nguon::truyVan($maCskcb, $ky)->chunk(self::LO_DOC, function ($lo) use (&$kq, $dungSom) {
            foreach ($lo as $xml1) {
                $kq['so_dong']++;
                $k = Bh01Kiem::dong(Bh01ChuanHoa::dong($xml1));

                if (count($k['chan']) > 0) {
                    $kq['so_dong_chan']++;
                    if (count($kq['loi_chan_mau']) < self::MAU_TOI_DA) {
                        $kq['loi_chan_mau'][] = 'MA_LK ' . $xml1->ma_lk . ': ' . implode('; ', $k['chan']);
                    }
                } elseif (count($k['canh_bao']) > 0) {
                    $kq['so_dong_canh_bao']++;
                }
            }

            if ($dungSom && $kq['so_dong_chan'] > 0) {
                return false;
            }
        });

        return $kq;
    }

    /** Luot 2: ghi ban + dong, chay trong transaction cua tao(). Tong tinh tu gia tri da chuan hoa. */
    private function ghi($maCskcb, array $ky, $taoBoi, $soBan)
    {
        $ban = Bh01Ban::create(array(
            'ma_ban'        => sprintf('BH01_%s_%s_%02d', $maCskcb, $ky['ky_qt'], $soBan),
            'ma_cskcb'      => $maCskcb,
            'nam_qt'        => $ky['nam'],
            'thang_qt'      => $ky['thang'],
            'so_ban'        => $soBan,
            'so_dong'       => 0,
            'id_ds_chitiet' => 'Id-' . \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'tao_boi'       => $taoBoi,
            'tao_luc'       => date('Y-m-d H:i:s'),
            'trang_thai_ky' => Bh01Ban::KY_CHO,
        ));

        $tong = array_fill_keys(self::$cot_tien, 0.0);
        $stt = 0;
        $canhBao = 0;

        Bh01Nguon::truyVan($maCskcb, $ky)->chunk(self::LO_DOC, function ($lo) use (&$tong, &$stt, &$canhBao, $ban) {
            $moi = array();
            foreach ($lo as $xml1) {
                $d = Bh01ChuanHoa::dong($xml1);
                $k = Bh01Kiem::dong($d);
                if (count($k['canh_bao']) > 0) {
                    $canhBao++;
                }
                foreach (self::$cot_tien as $cot) {
                    $tong[$cot] += (float) $d[$cot];
                }
                $stt++;
                $moi[] = $d + array(
                    'ban_id'   => $ban->id,
                    'stt'      => $stt,
                    'ma_lk'    => $xml1->ma_lk,
                    'canh_bao' => count($k['canh_bao']) > 0 ? implode('; ', $k['canh_bao']) : null,
                );
            }
            foreach (array_chunk($moi, self::LO_GHI) as $phan) {
                DB::table('bh01_dong')->insert($phan);
            }
        });

        $ban->so_dong = $stt;
        $ban->so_canh_bao = $canhBao;
        foreach (self::$cot_tien as $cot) {
            $ban->$cot = round($tong[$cot], 2);
        }
        $ban->save();

        return $ban;
    }
}
