<?php

namespace App\Services\OrderCheck\Support;

class OrderService
{
    /** @var int */ public $sereServId;
    /** @var string */ public $serviceCode;
    /** @var string */ public $serviceName;
    /** @var int */ public $executeTime = 0;
    /** @var int */ public $tdlIntructionTime = 0;

    /** @var int|null Doi tuong cua RIENG dong nay (his_sere_serv.patient_type_id) */
    public $patientTypeId;

    /** @var string|null Ma BHYT cua dich vu (his_service.hein_service_bhyt_code) */
    public $bhytCode;

    /** @var string|null Ten BHYT cua dich vu (his_service.hein_service_bhyt_name) */
    public $bhytName;

    /** @var int|null Loai dich vu (his_service.service_type_id): 6 Thuoc, 7 Vat tu */
    public $serviceTypeId;
}
