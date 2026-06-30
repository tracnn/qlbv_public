<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class OrderTimeOutOfStayRule implements RuleHandler
{
    public function code()
    {
        return 'B_ORDER_TIME_OUT_OF_STAY';
    }

    public function check(OrderContext $c)
    {
        $vios = [];
        if ($c->intructionTime > 0 && $c->inTime > 0 && $c->intructionTime < $c->inTime) {
            $vios[] = new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Giờ y lệnh (' . $c->intructionTime . ') trước giờ vào viện (' . $c->inTime . ')',
                ['intruction_time' => $c->intructionTime, 'in_time' => $c->inTime],
                'before_in'
            );
        }
        if ($c->intructionTime > 0 && $c->outTime > 0 && $c->intructionTime > $c->outTime) {
            $vios[] = new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Giờ y lệnh (' . $c->intructionTime . ') sau giờ ra viện (' . $c->outTime . ')',
                ['intruction_time' => $c->intructionTime, 'out_time' => $c->outTime],
                'after_out'
            );
        }
        return $vios;
    }
}
