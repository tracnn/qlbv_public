<?php

namespace App\Services\OrderCheck\Contracts;

use App\Services\OrderCheck\Support\OrderContext;

interface RuleHandler
{
    /**
     * Mã luật, trùng với cột code trong order_check_rules.
     * @return string
     */
    public function code();

    /**
     * Kiểm tra một ngữ cảnh y lệnh.
     * @param OrderContext $context
     * @return \App\Services\OrderCheck\Support\Violation[]
     */
    public function check(OrderContext $context);
}
