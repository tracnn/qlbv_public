<?php

namespace Tests\Support;

use App\Services\BHYT\KhoaDieuTriHis;

/**
 * Ban gia KhoaDieuTriHis: KHONG cham Oracle. Thay dung truy van HIS (traLo) - phan chia lo,
 * bo nho dem va bat loi cua service van la ma that.
 */
class FakeKhoaDieuTriHis extends KhoaDieuTriHis
{
    /** Cac lo da gui sang "HIS". */
    public $cacLo = [];

    /** @var array ma_lk => ['ma_khoa' => ..., 'ten_khoa' => ...] */
    protected $bang;

    /** @var \Throwable|null nem ra o moi lan tra */
    protected $loi;

    public function __construct(array $bang = [], \Throwable $loi = null)
    {
        $this->bang = $bang;
        $this->loi = $loi;
    }

    protected function traLo(array $lo)
    {
        $this->cacLo[] = $lo;

        if ($this->loi) {
            throw $this->loi;
        }

        return array_intersect_key($this->bang, array_flip($lo));
    }
}
