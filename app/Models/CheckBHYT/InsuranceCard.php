<?php

namespace App\Models\CheckBHYT;

use Illuminate\Database\Eloquent\Model;

class InsuranceCard extends Model
{
    protected $fillable = [
        'maKetQua',
        'ghiChu',
        'gioiTinh',
        'diaChi',
        'maDKBD',
        'cqBHXH',
        'gtTheTu',
        'gtTheDen',
        'maKV',
        'ngayDu5Nam',
        'maSoBHXH',
        'maTheCu',
        'maTheMoi',
        'gtTheTuMoi',
        'gtTheDenMoi',
        'maDKBDMoi',
        'tenDKBDMoi',
    ];

    public function getSearchResults($params)
    {
        return $this->select()
            ->where('maThe', 'LIKE', '%'. $params['insurance-number'] .'%')
            ->where('hoTen', 'LIKE', '%'. $params['name'] .'%')
            ->where('created_at', '>=', $params['date']['from'] ? $params['date']['from']:'1970-01-01')
            ->where('created_at', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')
            ->orderBy($params['order_by'],$params['order_type']);
    }
}
