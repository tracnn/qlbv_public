<?php

namespace App\Services\OrderCheck\Support;

class OrderContext
{
    /** @var int */ public $serviceReqId;
    /** @var string */ public $serviceReqCode;
    /** @var int */ public $treatmentId;
    /** @var string */ public $treatmentCode;
    /** @var string */ public $patientCode;
    /** @var string */ public $patientName;
    /** @var int|null */ public $departmentId;
    /** @var string|null */ public $doctorLoginname;
    /** @var string|null */ public $doctorUsername;
    /** @var string|null */ public $doctorPracticeScope;
    /** @var int */ public $intructionTime = 0;
    /** @var int */ public $inTime = 0;
    /** @var int */ public $outTime = 0;
    /** @var string|null */ public $icdCode;

    /** @var OrderService[] */ public $services = [];
}
