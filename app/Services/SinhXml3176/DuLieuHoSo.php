<?php

namespace App\Services\SinhXml3176;

/**
 * Goi du lieu HIS cua mot ho so (mot treatment). Moi dong la stdClass, ten cot viet thuong.
 * Cac bang con mac dinh la mang rong, khong bao gio null.
 */
class DuLieuHoSo
{
    /** @var \stdClass ban ghi v_his_treatment_12 */
    public $hoSo;

    /** v_his_patient_type_alter, da sap log_time desc, id desc */
    public $dienDieuTri = [];

    /** v_his_sere_serv_2, da loc is_delete va doi tuong thanh toan (idDoiTuongTt) */
    public $dichVu = [];

    /** his_ekip_user cua cac ekip_id ma $dichVu tham chieu */
    public $ekip = [];

    /** v_his_bed_log */
    public $giuong = [];

    /** v_his_sere_serv_tein (chi so CLS, thong tin may) */
    public $ketQuaCls = [];

    /** v_his_sere_serv_pttt */
    public $pttt = [];

    /** his_dhst */
    public $dhst = [];

    /** v_his_baby */
    public $soSinh = [];

    /** his_exp_medimate_used (tach dong XML2 theo lan dung thuoc) */
    public $suDungThuoc = [];

    public function __construct($hoSo)
    {
        $this->hoSo = $hoSo;
    }
}
