<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\MaBenh;

/**
 * Khung chung cho hai luat doi chieu ma benh voi danh muc.
 *
 * Bang danh muc RONG thi quy tac IM LANG - cung ly do voi BhytCatalogRule. Day chinh la la
 * chan ma XML3176 thieu: no dang sinh 31.492 vi pham gia vi medical_staffs rong.
 *
 * Xet CA HAI truong: chan doan chinh va chan doan phu. Chan doan phu co ti le sai CAO HON
 * chan doan chinh (12,71% so voi 9,68% tren 7 ngay that), bo qua la mat mot nua so loi.
 *
 * Khong loc theo ngay hieu luc: hai bang ICD chi co is_active, khong co tu_ngay/den_ngay.
 */
abstract class IcdCatalogRule implements RuleHandler
{
    /** @var CatalogLookup */
    protected $danhMuc;

    public function __construct(CatalogLookup $danhMuc = null)
    {
        $this->danhMuc = $danhMuc ?: new CatalogLookup(
            $this->bang(), 'icd_code', null, null, null, ['is_active' => 1]
        );
    }

    /** Ten bang danh muc */
    abstract protected function bang();

    /** Nhan hien thi trong thong diep, vi du 'danh mục ICD10' */
    abstract protected function nhan();

    /** Ma benh chinh cua phieu */
    abstract protected function maChinh(OrderContext $c);

    /** Chuoi ma benh phu cua phieu */
    abstract protected function maPhu(OrderContext $c);

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nap - im lang thay vi bao oan toan bo
        }

        $ma = MaBenh::gom($this->maChinh($c), $this->maPhu($c));

        if (empty($ma)) {
            return [];
        }

        // Mot truy van cho ca phieu.
        $this->danhMuc->nap(array_keys($ma));

        $vi = [];

        foreach ($ma as $m => $viTri) {
            if ($this->danhMuc->coTrongDanhMuc($m)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'Mã bệnh không có trong ' . $this->nhan() . ': ' . $m
                    . ' (' . MaBenh::nhanViTri($viTri) . ')',
                [
                    'service_req_code' => $c->serviceReqCode,
                    'ma_benh' => $m,
                    'vi_tri' => $viTri,
                ],
                (string) $m
            );
        }

        return $vi;
    }
}
