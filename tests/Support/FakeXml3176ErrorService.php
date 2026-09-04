<?php

namespace Tests\Support;

use App\Services\Xml3176ErrorService;

/**
 * ErrorService giả cho test: getCriticalErrorStatus trả true (không truy vấn catalog).
 * Xml3176ErrorService KHÔNG có constructor nên kế thừa trực tiếp là đủ.
 */
class FakeXml3176ErrorService extends Xml3176ErrorService
{
    public function getCriticalErrorStatus($errorCode)
    {
        return true;
    }
}
