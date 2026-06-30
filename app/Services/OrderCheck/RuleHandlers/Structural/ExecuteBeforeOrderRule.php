<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class ExecuteBeforeOrderRule implements RuleHandler
{
    public function code()
    {
        return 'B_EXECUTE_BEFORE_ORDER';
    }

    public function check(OrderContext $c)
    {
        $vios = [];
        foreach ($c->services as $s) {
            $baseline = $s->tdlIntructionTime > 0 ? $s->tdlIntructionTime : $c->intructionTime;
            if ($s->executeTime > 0 && $baseline > 0 && $s->executeTime < $baseline) {
                $vios[] = new Violation(
                    $this->code(), 'sere_serv', $s->sereServId,
                    'Giờ thực hiện (' . $s->executeTime . ') trước giờ y lệnh (' . $baseline . ') - DV ' . $s->serviceCode,
                    ['execute_time' => $s->executeTime, 'baseline' => $baseline, 'service_code' => $s->serviceCode]
                );
            }
        }
        return $vios;
    }
}
