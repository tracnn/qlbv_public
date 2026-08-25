<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Tt12HoSo extends Model
{
    protected $table = 'tt12_ho_so';

    protected $fillable = [
        'ma_ho_so', 'mau', 'loai_hs', 'ma_cskcb', 'ten_tep', 'so_dong', 'id_danh_sach',
        'imported_at', 'imported_by', 'import_error',
        'checked_at', 'so_loi',
        'is_signed', 'sign_method', 'signed_at', 'signed_error', 'duong_dan_da_ky',
        'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
        'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan',
        'dong_bo_at', 'dong_bo_so_dong',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'so_dong'   => 'integer',
        'so_loi'    => 'integer',
    ];

    protected $dates = ['imported_at', 'checked_at', 'signed_at', 'submitted_at', 'dong_bo_at'];

    public function dong()
    {
        return $this->hasMany(Tt12Dong::class, 'ho_so_id')->orderBy('stt');
    }

    public function loi()
    {
        return $this->hasMany(Tt12Loi::class, 'ho_so_id');
    }

    public function lichSuGui()
    {
        return $this->hasMany(Tt12LichSuGui::class, 'ho_so_id')->orderBy('id', 'desc');
    }

    /** @return string|null ten lop dac ta mau, null neu mau la */
    public function lopMau()
    {
        $dangKy = \App\Services\Tt12\Tt12MauRegistry::tatCa();

        return isset($dangKy[$this->mau]) ? $dangKy[$this->mau] : null;
    }
}
