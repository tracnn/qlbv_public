<?php

namespace App\Services\Tt12;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;

/**
 * Day cac dong cua mot ho so DA DUOC TIEP NHAN sang bang danh muc.
 *
 * VI SAO CHI SAU KHI 200: danh muc la nguon cho buoc kiem XML3176, va giam dinh se doi
 * chieu ho so KCB voi chinh bo danh muc ma co so DA GUI LEN. Ghi truoc khi gui thi ta
 * kiem theo mot ban BHXH chua co, va moi loi tim duoc deu co the sai.
 *
 * VI SAO GHI THEO LO: danh muc thuoc that co the vai nghin dong. Ghi chu trong
 * CatalogImportService da do: tep ICD 52.551 dong ngon 128 MB va 346 giay CHI RIENG phan
 * doc, trong khi may chu dat PHP 128 MB / 120 giay.
 */
class Tt12DongBoDanhMuc
{
    const CO_LO = 1000;

    /**
     * @param Tt12HoSo $hoSo
     * @return int so dong da ghi; 0 neu ho so chua duoc tiep nhan
     */
    public function dongBo(Tt12HoSo $hoSo)
    {
        if (!Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua)) {
            Log::info('Tt12DongBoDanhMuc: ho so chua duoc tiep nhan, bo qua', array(
                'ma_ho_so'   => $hoSo->ma_ho_so,
                'ma_ket_qua' => $hoSo->ma_ket_qua,
            ));

            return 0;
        }

        $lop = Tt12MauRegistry::cho($hoSo->mau);

        $bang    = $lop::bangDanhMuc();
        $anhXa   = $lop::cotDanhMuc();
        $khoa    = $this->khoaDuyNhat($lop);
        $daGhi   = 0;

        Tt12Dong::where('ho_so_id', $hoSo->id)
            ->orderBy('stt')
            ->chunk(self::CO_LO, function ($cacDong) use ($bang, $anhXa, $khoa, &$daGhi) {
                DB::transaction(function () use ($cacDong, $bang, $anhXa, $khoa, &$daGhi) {
                    foreach ($cacDong as $dong) {
                        $this->ghiMotDong($bang, $anhXa, $khoa, $dong);
                        $daGhi++;
                    }
                });
            });

        $hoSo->update(array(
            'dong_bo_at'      => Carbon::now(),
            'dong_bo_so_dong' => $daGhi,
        ));

        Log::info('Tt12DongBoDanhMuc: da dong bo', array(
            'ma_ho_so' => $hoSo->ma_ho_so,
            'bang'     => $bang,
            'so_dong'  => $daGhi,
        ));

        return $daGhi;
    }

    /**
     * Khoa duy nhat lay tu config/catalog_import_mapping.php, KHONG go cung o day.
     *
     * Cau hinh do cung la thu luong import thu cong dung. Go cung o day la de hai duong
     * ghi vao cung mot bang theo hai khoa khac nhau - va bang se co ban ghi trung ma
     * khong ai biet duong nao tao ra.
     *
     * @return array ten cot
     */
    private function khoaDuyNhat($lop)
    {
        $khoa = config('catalog_import_mapping.' . $lop::danhMuc() . '.unique_keys');

        if (!is_array($khoa) || $khoa === array()) {
            throw new \RuntimeException(
                'Thieu unique_keys cho danh muc ' . $lop::danhMuc()
                . ' trong config/catalog_import_mapping.php'
            );
        }

        return $khoa;
    }

    /**
     * Gia tri dem ghi vao bang danh muc: o TRONG thanh NULL.
     *
     * VI SAO CAN: nam cot so cua department_bed_catalogs (ban_kham, giuong_*) va cac cot
     * tuong tu o nam bang con lai deu la `int(11) NULL`. O Excel trong duoc doc thanh chuoi
     * rong, ma MySQL che do nghiem ngat tu choi '' cho cot so:
     *
     *   SQLSTATE[22007]: Incorrect integer value: '' for column 'ban_kham'
     *
     * Do THAT tren cong ngay 2026-08-26: ho so da duoc tiep nhan (maKetQua 200) roi buoc
     * dong bo ngay sau do no, va ho so nam lai voi dong_bo_at rong.
     *
     * Ve nghia: o trong nghia la "khong khai", ma trong bang danh muc "khong khai" la NULL
     * chu khong phai chuoi rong. CatalogImportService:135 dung cung quy uoc cho luong nhap
     * thu cong.
     *
     * CHI o rong moi thanh NULL. So 0 GIU NGUYEN - "0 giuong" va "khong khai" la hai chuyen
     * khac nhau voi co quan giam dinh.
     *
     * @param mixed $gia
     * @return mixed
     */
    private static function giaTriGhi($gia)
    {
        if ($gia === null) {
            return null;
        }

        return trim((string) $gia) === '' ? null : $gia;
    }

    private function ghiMotDong($bang, array $anhXa, array $khoa, Tt12Dong $dong)
    {
        $duLieu = is_array($dong->du_lieu) ? $dong->du_lieu : array();

        $hang = array();

        foreach ($anhXa as $the => $cot) {
            $hang[$cot] = self::giaTriGhi(
                array_key_exists($the, $duLieu) ? $duLieu[$the] : null
            );
        }

        $dieuKien = array();

        foreach ($khoa as $cot) {
            // Cot khoa khong co trong anh xa (vi du ma_loai_kcb) thi khop voi null - dung
            // gia tri that trong bang cho dong do vi ta khong ghi cot do bao gio.
            $dieuKien[$cot] = array_key_exists($cot, $hang) ? $hang[$cot] : null;
        }

        DB::table($bang)->updateOrInsert(
            $dieuKien,
            array_merge($hang, array('updated_at' => Carbon::now()))
        );
    }
}
