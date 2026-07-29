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

    /**
     * @var string|null Ma BHYT cua dong: voi thuoc la ma hoat chat
     * (his_medicine_type.active_ingr_bhyt_code), voi DVKT va vat tu la ma dich vu
     * (his_service.hein_service_bhyt_code)
     */
    public $bhytCode;

    /**
     * @var string|null Ten BHYT cua dong, DONG NGUON voi bhytCode: voi thuoc la ten hoat
     * chat (his_medicine_type.active_ingr_bhyt_name), voi DVKT va vat tu la ten dich vu
     * (his_service.hein_service_bhyt_name)
     */
    public $bhytName;

    /** @var int|null Loai dich vu (his_service.service_type_id): 6 Thuoc, 7 Vat tu */
    public $serviceTypeId;
}
