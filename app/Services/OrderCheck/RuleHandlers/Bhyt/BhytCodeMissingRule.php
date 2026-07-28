<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\BhytScope;

/**
 * Dong dich vu thuoc doi tuong BHYT nhung dich vu KHONG khai ma BHXH trong HIS.
 *
 * Quy tac nay KHONG phu thuoc danh muc da nhap nen chay duoc ngay va khong the sai vi
 * danh muc thieu.
 *
 * LUU Y ve quy mo: 21.778 dich vu HIS dang hoat dong, chi 10.552 khai ma BHXH (48%).
 * Chay thu bang lenh kiemtraylenh:thu truoc khi bat.
 */
class BhytCodeMissingRule implements RuleHandler
{
    public function code()
    {
        return 'A_BHYT_CODE_MISSING';
    }

    public function check(OrderContext $c)
    {
        $vi = [];

        foreach (BhytScope::locDongBhyt($c->services) as $s) {
            if (trim((string) $s->bhytCode) !== '') {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                'Dịch vụ chỉ định cho đối tượng BHYT nhưng chưa khai mã BHXH: ' . $s->serviceName,
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }
}
