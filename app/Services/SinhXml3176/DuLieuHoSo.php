<?php

namespace App\Services\SinhXml3176;

/**
 * Goi du lieu HIS cua mot ho so (mot treatment). Moi dong la stdClass, ten cot viet thuong.
 * Cac bang con mac dinh la mang rong, khong bao gio null (tru hivTreatment/dieuTriLao: mot ban ghi hoac null).
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

    /** his_tracking (to dieu tri, XML5), da sap tracking_time, id */
    public $theoDoi = [];

    /** his_debate (hoi chan, XML5) */
    public $hoiChan = [];

    /** v_his_sere_serv_suin (chi so CDHA/TDCN, XML4 khoi C), gan theo tdl_treatment_id, chua loc theo $dichVu */
    public $chiSoCdha = [];

    /** his_hiv_treatment: ban ghi ID lon nhat cua ho so (XML6); khong co => null */
    public $hivTreatment;

    /** his_tuberculosis_treat: ban ghi cuoi theo id tang cua ho so (XML15); khong co => null */
    public $dieuTriLao;

    public function __construct($hoSo)
    {
        $this->hoSo = $hoSo;
    }
}
