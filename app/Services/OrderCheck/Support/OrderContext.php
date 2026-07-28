<?php

namespace App\Services\OrderCheck\Support;

class OrderContext
{
    /** @var int */ public $serviceReqId;
    /** @var string */ public $serviceReqCode;
    /** @var int|null */ public $serviceReqTypeId;
    /** @var int */ public $treatmentId;
    /** @var string */ public $treatmentCode;
    /** @var string */ public $patientCode;
    /** @var string */ public $patientName;
    /** @var int|null */ public $departmentId;
    /** @var string|null */ public $doctorLoginname;   // người chỉ định (request)
    /** @var string|null */ public $doctorUsername;
    /** @var string|null */ public $executeLoginname;  // người thực hiện
    /** @var string|null */ public $executeUsername;
    /** @var string|null */ public $executeDiploma;    // CCHN của người thực hiện
    /** @var int */ public $intructionTime = 0;
    /** @var int */ public $inTime = 0;
    /** @var int */ public $outTime = 0;
    /** @var string|null */ public $icdCode;

    /** @var string|null Chan doan phu; chuoi nhieu ma ngan boi ';' va CO dau ';' dan dau */
    public $icdSubCode;

    /** @var string|null Chan doan YHCT chinh */
    public $traditionalIcdCode;

    /** @var string|null Chan doan YHCT phu; cung quy uoc chuoi ghep */
    public $traditionalIcdSubCode;

    /** @var string|null CCHN cua bac si CHI DINH; executeDiploma la cua nguoi thuc hien */
    public $requestDiploma;

    /** @var OrderService[] */ public $services = [];
}
