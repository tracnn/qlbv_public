<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Tt12Dong extends Model
{
    protected $table = 'tt12_dong';

    protected $fillable = ['ho_so_id', 'stt', 'du_lieu', 'ma', 'ten', 'tu_ngay', 'den_ngay'];

    // Cast 'array' thay cho kieu cot json: xem ghi chu trong migration.
    protected $casts = [
        'du_lieu' => 'array',
        'stt'     => 'integer',
    ];

    public function hoSo()
    {
        return $this->belongsTo(Tt12HoSo::class, 'ho_so_id');
    }

    public function thuocPx()
    {
        return $this->hasMany(Tt12DongThuocPx::class, 'dong_id')->orderBy('id');
    }

    /**
     * Gia tri cua mot the, luon tra ve CHUOI.
     *
     * Excel co the tra ve int/float cho o so; noi thang ket qua do vao XML se cho ra
     * '15000.0' thay vi '15000'. Ep chuoi mot cho de moi noi doc deu giong nhau.
     *
     * @return string
     */
    public function the($ten)
    {
        $duLieu = $this->du_lieu;

        if (!is_array($duLieu) || !array_key_exists($ten, $duLieu)) {
            return '';
        }

        return $duLieu[$ten] === null ? '' : (string) $duLieu[$ten];
    }
}
