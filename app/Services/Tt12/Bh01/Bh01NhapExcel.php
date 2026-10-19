<?php

namespace App\Services\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Nhap tep Excel 01/BH thanh mot BAN moi (kenh du phong khi khong co XML3176).
 *
 * VI SAO 2 LUOT: kiem() doc het tep de chan loi TRUOC khi tao ban; ghi() doc lai tep moi lan thu
 * (Bh01GhiBan co the thu lai khi trung so_ban) va kiem lai tung dong - nen khong bao gio ghi dong chan.
 *
 * TEP NGUON chi chep sang dia SAU khi ghi() tra ve (transaction da commit): neu chep truoc roi
 * rollback se de lai tep mo coi khong ban nao tro toi. Tep la dau vet kiem toan "ban nay tu tep nao".
 */
class Bh01NhapExcel
{
    /**
     * @param string $duongDanTam tep da tai len (thu muc tam)
     * @param string $tenTepGoc   ten tep nguoi dung chon, luu lam dau vet
     * @return array ['ban' => Bh01Ban, 'cot_thua' => string[]]
     * @throws Bh01KhongTaoDuocException khi tep co loi chan
     */
    public function nhap($duongDanTam, $tenTepGoc, $maCskcb, array $ky, $taoBoi)
    {
        $doc = new Bh01DocExcel();

        $kiem = $doc->kiem($duongDanTam, $maCskcb, $ky);
        if (count($kiem['loi']) > 0) {
            throw new Bh01KhongTaoDuocException(implode("\n", $kiem['loi']));
        }

        $ban = (new Bh01GhiBan())->ghi(
            $maCskcb,
            $ky,
            $taoBoi,
            function ($nhanLo) use ($doc, $duongDanTam) {
                $doc->duyetLo($duongDanTam, $nhanLo);
            },
            array('nguon' => Bh01Ban::NGUON_EXCEL, 'ten_tep_nguon' => $tenTepGoc)
        );

        $this->luuTepNguon($ban, $duongDanTam);

        return array('ban' => $ban, 'cot_thua' => $kiem['cot_thua']);
    }

    /**
     * Chep that bai KHONG xoa ban: ban da commit va hop le, chi mat dau vet tep. Ghi canh bao,
     * de duong_dan_tep_nguon rong de man hinh biet khong co tep de tai.
     */
    private function luuTepNguon(Bh01Ban $ban, $duongDanTam)
    {
        $duongDan = 'BH01/nguon/' . $ban->ma_ban . '.xlsx';
        $luong = null;
        try {
            $luong = fopen($duongDanTam, 'rb');
            if ($luong === false || !Storage::disk('exportTt12')->put($duongDan, $luong)) {
                throw new \RuntimeException('Không chép được tệp nguồn.');
            }
            $ban->duong_dan_tep_nguon = $duongDan;
            $ban->save();
        } catch (\Exception $e) {
            Log::warning('Bh01 nhap Excel: khong luu duoc tep nguon cua ban ' . $ban->ma_ban . ': ' . $e->getMessage());
        } finally {
            if (is_resource($luong)) {
                fclose($luong);
            }
        }
    }
}
