<?php

namespace App\Models\Mcct;

use Illuminate\Database\Eloquent\Model;

class McctChiPhi extends Model
{
    protected $table = 'mcct_chi_phi';

    protected $fillable = [
        'tra_cuu_id', 'id_cong', 'ma_the', 'ma_cskcb',
        'ngay_vao', 'ngay_ra', 'ma_doi_tuong_kcb',
        't_bn_cct_mcct', 't_bn_cct_luy_ke',
        'ngay_nhan_cong', 'ngay_nhan', 'ngay_tra_cuu',
    ];

    public function traCuu()
    {
        return $this->belongsTo(McctTraCuu::class, 'tra_cuu_id');
    }
}
