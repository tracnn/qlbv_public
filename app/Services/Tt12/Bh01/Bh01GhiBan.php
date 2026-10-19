<?php

namespace App\Services\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Ghi mot BAN 01/BH (bh01_ban + bh01_dong) tu mot NGUON dong bat ky - dung chung cho ban chup tu
 * XML3176 (Bh01TaoBan) va ban nhap tu Excel.
 *
 * VI SAO TACH RIENG: so ban ke tiep, vong thu lai khi trung khoa, transaction, tong tien tinh tu
 * dong da ghi va kiem lai tung dong la mot khoi; hai nguon khac nhau chi o cho LAY dong. Chep ra
 * hai noi se de lech nhau o khau ghi ban se ky va gui len cong.
 *
 * NGUON ($duyetLo) PHAI DUYET LAI DUOC: vong thu lai goi no moi lan thu, nen moi lan phai duyet
 * lai tu dau (Bh01Nguon::docTheoLo thoa; tep Excel doc lai tu dia cung thoa).
 *
 * Hop dong cua $duyetLo: function (callable $nhanLo) - goi $nhanLo(array $cacDong) cho tung lo,
 * moi phan tu ['du_lieu' => 19 khoa thuong da chuan hoa, 'ma_lk' => string|null, 'vi_tri' => string].
 */
class Bh01GhiBan
{
    const LO_GHI  = 500;
    const THU_LAI = 3;

    private static $cot_tien = array(
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
    );

    /**
     * @param array $thuocTinhBan Chi cac cot cua bh01_ban (vd nguon, ten_tep_nguon); gop vao luc tao.
     */
    public function ghi($maCskcb, array $ky, $taoBoi, callable $duyetLo, array $thuocTinhBan = array())
    {
        for ($lan = 1; ; $lan++) {
            $soBan = $this->soBanKeTiep($maCskcb, $ky);
            try {
                return DB::transaction(function () use ($maCskcb, $ky, $taoBoi, $duyetLo, $thuocTinhBan, $soBan) {
                    return $this->ghiTrongGiaoDich($maCskcb, $ky, $taoBoi, $duyetLo, $thuocTinhBan, $soBan);
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

    /** Chay trong transaction cua ghi(). Tong tinh tu gia tri da chuan hoa. */
    private function ghiTrongGiaoDich($maCskcb, array $ky, $taoBoi, callable $duyetLo, array $thuocTinhBan, $soBan)
    {
        $ban = Bh01Ban::create($thuocTinhBan + array(
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

        $duyetLo(function (array $cacDong) use (&$tong, &$stt, &$canhBao, $ban) {
            $moi = array();
            foreach ($cacDong as $dong) {
                $d = $dong['du_lieu'];
                $k = Bh01Kiem::dong($d);
                // Nguon co the doi giua luot kiem va luot ghi: kiem lai, nem de rollback ca ban -
                // khong bao gio ghi mot dong chan vao ban se ky va gui.
                if (count($k['chan']) > 0) {
                    throw new Bh01KhongTaoDuocException(
                        $dong['vi_tri'] . ' bị chặn: ' . implode('; ', $k['chan']) . '. Chưa tạo được bản.'
                    );
                }
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
                    'ma_lk'    => $dong['ma_lk'],
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
