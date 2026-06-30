<?php

namespace App\Services\OrderCheck\Support;

class Violation
{
    public $ruleCode;
    public $orderRefType;
    public $orderRefId;
    public $message;
    public $detail;
    public $subKey;

    public function __construct($ruleCode, $orderRefType, $orderRefId, $message, array $detail = [], $subKey = '')
    {
        $this->ruleCode = $ruleCode;
        $this->orderRefType = $orderRefType;
        $this->orderRefId = $orderRefId;
        $this->message = $message;
        $this->detail = $detail;
        $this->subKey = $subKey;
    }

    public function dedupKey()
    {
        return $this->ruleCode . ':' . $this->orderRefType . ':' . $this->orderRefId . ':' . $this->subKey;
    }
}
