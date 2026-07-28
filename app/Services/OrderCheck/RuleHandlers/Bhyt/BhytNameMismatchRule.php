<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

/**
 * Doi chieu TEN BHYT cua dong y lenh voi ten trong danh muc con hieu luc.
 *
 * BHXH tu choi ca khi ten lech, khong chi khi ma sai. Bo kiem XML3176 da bat viec nay
 * (INVALID_DRUG_NAME, INVALID_MATERIAL_NAME) nhung chi chay SAU khi ho so da khoa va xuat
 * XML - luc do sua duoc rat it. Quy tac nay bat cung loai loi ngay tren y lenh dang phat
 * sinh, som hon nhieu ngay.
 *
 * KHAC XML3176 o mot diem cot loi: XML3176 khoa duoc DUNG MOT dong danh muc bang bon khoa
 * ma_thuoc + ham_luong + so_dang_ky + tt_thau roi so ten cua dong do. O muc y lenh, HIS chi
 * co ma va ten - khong co ham luong, so dang ky, TT thau. Nen phep so duy nhat dung la:
 * ten khai phai trung ten cua IT NHAT MOT dong danh muc mang ma do va con hieu luc. Do
 * tren HIS that: 593 ma BHYT dang duoc nhieu dich vu HIS dung chung voi ten khac nhau, ca
 * biet mot ma co 226 ten - so voi "dong duy nhat" se bao sai hang loat o nhom thuoc.
 *
 * So TUYET DOI, chi trim. Thong nhat voi Xml3176Xml2Checker.
 */
abstract class BhytNameMismatchRule extends BhytCatalogRule
{
    /** So ten danh muc toi da liet ke trong mo ta; co ma mang toi 226 ten */
    const TOI_DA_NEU_TEN = 3;

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nhap - im lang thay vi bao oan toan bo
        }

        $dong = $this->dongTrongPhamVi($c);

        if (empty($dong)) {
            return [];
        }

        // Mot truy van cho ca phieu, khong tra tung dong.
        $this->danhMuc->nap(array_map(function ($d) {
            return $d[2];
        }, $dong));

        $vi = [];

        foreach ($dong as $d) {
            list($s, $ngay, $ma) = $d;

            $tenKhai = trim((string) $s->bhytName);

            if ($tenKhai === '') {
                continue;   // do duoc 0 dong thieu ten, khong lam quy tac "thieu ten"
            }

            $tenDanhMuc = $this->danhMuc->tenTheoMa($ma, $ngay);

            if (empty($tenDanhMuc)) {
                continue;   // ma khong co hoac het hieu luc - quy tac MA lo, khong bao chong
            }

            if (in_array($tenKhai, $tenDanhMuc, true)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                $this->nhan() . ' lệch danh mục BHYT. Mã ' . $ma
                    . '; khai "' . $tenKhai . '"; danh mục: ' . $this->neuTen($tenDanhMuc),
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                    'bhyt_code' => $ma,
                    'bhyt_name' => $tenKhai,
                    'ngay_chi_dinh' => $ngay,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }

    protected function neuTen(array $ten)
    {
        $cat = array_slice($ten, 0, self::TOI_DA_NEU_TEN);
        $chuoi = '"' . implode('", "', $cat) . '"';

        if (count($ten) > self::TOI_DA_NEU_TEN) {
            $chuoi .= ' …';
        }

        return $chuoi;
    }
}
