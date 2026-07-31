<?php

namespace App\Models\CheckBHYT;

use Illuminate\Database\Eloquent\Model;

class check_hein_card extends Model
{
    // Thêm các trường vào fillable
    protected $fillable = [
        'ma_lk', 'ma_cskcb', 'ma_tracuu', 'ma_kiemtra', 'ma_ketqua', 'ghi_chu', 'ma_the', 'ho_ten', 'ngay_sinh',
        'dia_chi', 'ma_the_cu', 'ma_the_moi', 'ma_dkbd', 'cq_bhxh', 'gioi_tinh', 'gt_the_tu', 'gt_the_den',
        'ma_kv', 'ngay_du5nam', 'maso_bhxh', 'gt_the_tu_moi', 'gt_the_den_moi', 'ma_dkbd_moi', 'ten_dkbd_moi'
    ];

    /** Ma tra cuu cua ho so hop le */
    const TRA_CUU_SACH = '000';

    /** Ma kiem tra cua ho so hop le */
    const KIEM_TRA_SACH = '00';

    /**
     * Chi cac dong CO VAN DE ve the.
     *
     * Dung dung quy tac ma detail-xml.blade.php dang to do: khac '000' HOAC khac '00'.
     *
     * KHAC voi quy tac cua jobKtTheBHYT (config qd130xml.hein_card_invalid) - quy tac do tra
     * loi cau hoi "co can tra lai khong", con day tra loi "co bat thuong khong". Vi du
     * ma_tracuu '001' (the do BHXH Bo Quoc phong quan ly) la bat thuong nhung khong can tra
     * lai. Man nay de NHIN RA thu bat thuong nen dung quy tac khat khe hon.
     */
    public function scopeChiLoi($q)
    {
        return $q->where(function ($w) {
            $w->where('ma_tracuu', '!=', self::TRA_CUU_SACH)
              ->orWhere('ma_kiemtra', '!=', self::KIEM_TRA_SACH);
        });
    }

    /** Bu voi chiLoi(): tong hai ben bang tong so dong. */
    public function scopeChiHopLe($q)
    {
        return $q->where('ma_tracuu', self::TRA_CUU_SACH)
                 ->where('ma_kiemtra', self::KIEM_TRA_SACH);
    }

    /**
     * Loc theo co so KCB - SO KHOP THANG, khac voi danh muc.
     *
     * Day la du lieu su kien cua mot ho so cu the tai mot co so cu the, khong co khai niem
     * "dong dung chung" nhu danh muc. Dong ma_cskcb rong la du lieu cu truoc khi co cot do,
     * va chi hien khi chon "Tat ca co so".
     *
     * @param string|null $maCskcb rong = khong loc
     */
    public function scopeCuaCoSo($q, $maCskcb)
    {
        $maCskcb = trim((string) $maCskcb);

        return $maCskcb === '' ? $q : $q->where('ma_cskcb', $maCskcb);
    }

    public function xml1()
    {
        return $this->belongsTo('App\Models\BHYT\XML1', 'ma_lk', 'ma_lk');
    }

    public function his_treatment()
    {
        return $this->belongsTo('App\Models\HISPro\HIS_TREATMENT', 'ma_lk', 'treatment_code');
    }
}
