<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hai cot rut gon nua cho man danh sach: so_cccd va ma_bhxh.
 *
 * VI SAO LAI TRUNG LAP: cung ly do voi ma_the/ho_ten da co san trong bang nay. Chin loai
 * chung tu dat ten truong khac nhau (SO_CCCD, SO_GIAYTO, SO_CCCD_NND), nen tim theo can
 * cuoc ma khong co cot rut gon thi moi lan tim la mot UNION chin nhanh sang chin bang chi
 * tiet - va them mot loai chung tu la phai sua cau truy van do.
 *
 * DO DAI COT: can cuoc cong dan 12 so, chung minh nhan dan cu 9 so, nhung giay bao tu doc
 * SO_GIAYTO - o do co the la so ho chieu hoac giay to khac. Lay 30 cho du, khong lay 12.
 *
 * MA_BHXH la 10 so, lay 20.
 *
 * CO INDEX: ca hai cot deu la thu de TIM, va tim bang LIKE '%x%' tren mot bang khong index
 * se quet toan bang moi lan go phim.
 */
class AddCccdMaBhxhToCtdtChungTuTable extends Migration
{
    public function up()
    {
        Schema::table('ctdt_chung_tu', function (Blueprint $table) {
            $table->string('so_cccd', 30)->nullable()->index()->after('ma_the');
            $table->string('ma_bhxh', 20)->nullable()->index()->after('so_cccd');
        });

        $this->buDuLieuCu();
    }

    /**
     * Bu hai cot moi cho cac chung tu da nap TRUOC lan nang cap nay.
     *
     * KHONG BU thi hai cot moi rong tren toan bo du lieu cu, va man danh sach hien mot cot
     * trong ma khong co dau hieu gi cho biet do la "chua bu" chu khong phai "ho so khong co
     * can cuoc". Nguoi dung tim theo can cuoc se khong ra ket qua nao va ket luan la chuc
     * nang hong.
     *
     * Doc lai noi_dung_goc chu khong doc bang chi tiet: noi_dung_goc la XML NGUYEN VAN cua
     * chung tu, dung mot duong voi luc nap moi - khong phai suy ra tu chin bang chi tiet voi
     * chin cach dat ten cot khac nhau.
     */
    private function buDuLieuCu()
    {
        $registry = \App\Services\Ctdt\CtdtLoaiRegistry::tatCa();

        DB::table('ctdt_chung_tu')
            ->select('id', 'loai_ho_so', 'noi_dung_goc')
            ->whereNotNull('noi_dung_goc')
            ->orderBy('id')
            ->chunk(200, function ($dong) use ($registry) {
                foreach ($dong as $ct) {
                    if (!isset($registry[$ct->loai_ho_so])) {
                        continue;
                    }

                    $gia = $this->docHaiTruong($registry[$ct->loai_ho_so], $ct->noi_dung_goc);

                    if ($gia === null) {
                        continue;
                    }

                    DB::table('ctdt_chung_tu')->where('id', $ct->id)->update($gia);
                }
            });
    }

    /**
     * @return array|null Mang ['so_cccd' =>, 'ma_bhxh' =>], hoac null neu khong doc duoc
     */
    private function docHaiTruong($lop, $xmlTho)
    {
        // TAT bao loi cua libxml roi tu kiem: mot chung tu cu co XML hong khong duoc lam
        // do ca lan nang cap. Bo qua dong do va di tiep - cot cua no o lai rong, dung bang
        // trang thai truoc khi nang cap.
        $truoc = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($xmlTho);
        } catch (\Exception $e) {
            $xml = false;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($truoc);

        if ($xml === false) {
            return null;
        }

        $rutGon = $lop::rutGon($xml);

        return [
            'so_cccd' => isset($rutGon['so_cccd']) ? $rutGon['so_cccd'] : null,
            'ma_bhxh' => isset($rutGon['ma_bhxh']) ? $rutGon['ma_bhxh'] : null,
        ];
    }

    public function down()
    {
        Schema::table('ctdt_chung_tu', function (Blueprint $table) {
            // Bo index truoc roi moi bo cot: tren MySQL, drop mot cot con index se nem.
            $table->dropIndex(['so_cccd']);
            $table->dropIndex(['ma_bhxh']);
            $table->dropColumn(['so_cccd', 'ma_bhxh']);
        });
    }
}
