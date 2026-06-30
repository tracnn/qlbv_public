<?php

namespace App\Services\OrderCheck\Contracts;

use App\Services\OrderCheck\OrderCheckEngine;

interface Scanner
{
    /** Khóa nguồn, dùng cho watermark + rule_log. @return string */
    public function sourceKey();

    /**
     * Quét 1 lô từ nguồn của scanner, ghi violation qua $engine.
     * @param OrderCheckEngine $engine
     * @param int $limit
     * @return array ['scanned' => int, 'violations' => int]
     */
    public function scan(OrderCheckEngine $engine, $limit);
}
