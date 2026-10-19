<?php

namespace App\Services\Tt12\Bh01;

/**
 * Tao BAN (anh chup dong bang) 01/BH cho mot (co so, ky) tu xml3176_xml1s - spec 4.1-4.3, 5.1, 5.2.
 *
 * VI SAO CHUP DONG BANG: ban da ky/gui khong duoc doi khi XML1 duoc sua ve sau; nen chep gia tri
 * DA CHUAN HOA vao bh01_dong va tinh tong tu chinh cac dong da ghi (khong truy van tong rieng).
 * Phan GHI (so ban, thu lai khi trung khoa, transaction, tong) nam o Bh01GhiBan de nguon Excel dung chung.
 *
 * VI SAO HAI LUOT: luot 1 chi doc + kiem de dung som neu co dong chan (khong ghi gi); luot 2 moi
 * ghi, trong mot transaction. Ca hai doc theo lo (Bh01Nguon::docTheoLo, KHONG dung chunk() - xem
 * ly do o do) vi mot ky co the hang chuc nghin dong va PHP chi 128MB.
 */
class Bh01TaoBan
{
    const LO_DOC     = 500;
    const MAU_TOI_DA = 20;

    private $ghiBan;

    /** VI SAO tiem duoc: test thay bang lop con cua Bh01GhiBan de gai seam soBanKeTiep. */
    public function __construct(Bh01GhiBan $ghiBan = null)
    {
        $this->ghiBan = $ghiBan ?: new Bh01GhiBan();
    }

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

        // Nguon doc lai tu CSDL moi lan goi nen vong thu lai cua Bh01GhiBan duyet lai duoc.
        return $this->ghiBan->ghi($maCskcb, $ky, $taoBoi, function ($nhanLo) use ($maCskcb, $ky) {
            Bh01Nguon::docTheoLo($maCskcb, $ky, self::LO_DOC, function ($lo) use ($nhanLo) {
                $cacDong = array();
                foreach ($lo as $xml1) {
                    $cacDong[] = array(
                        'du_lieu' => Bh01ChuanHoa::dong($xml1),
                        'ma_lk'   => $xml1->ma_lk,
                        'vi_tri'  => 'MA_LK ' . $xml1->ma_lk,
                    );
                }
                $nhanLo($cacDong);
            });
        });
    }

    /** Luot 1: duyet theo lo, dem va kiem. $dungSom: dung ngay khi lo hien tai co dong chan (khong can mau). */
    private function duyetKiem($maCskcb, array $ky, $dungSom)
    {
        $kq = array('so_dong' => 0, 'so_dong_chan' => 0, 'so_dong_canh_bao' => 0, 'loi_chan_mau' => array());

        Bh01Nguon::docTheoLo($maCskcb, $ky, self::LO_DOC, function ($lo) use (&$kq, $dungSom) {
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
}
