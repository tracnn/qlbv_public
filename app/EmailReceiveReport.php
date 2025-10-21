<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailReceiveReport extends Model
{

    protected $table = 'email_receive_reports';

    protected $fillable = [
        'name', 
        'email', 
        'active', 
        'bcaobhxh', 
        'bcaoqtri', 
        'qtri_tckt', 
        'qtri_hsdt', 
        'qtri_dvkt', 
        'qtri_canhbao', 
        'period',
        'bcaoadmin',
        'khoa_san',
        'dinh_duong'
    ];

    protected $casts = [
        'active' => 'boolean',
        'bcaobhxh' => 'boolean',
        'bcaoqtri' => 'boolean',
        'qtri_tckt' => 'boolean',
        'qtri_hsdt' => 'boolean',
        'qtri_dvkt' => 'boolean',
        'qtri_canhbao' => 'boolean',
        'period' => 'boolean',
        'bcaoadmin' => 'boolean',
        'khoa_san' => 'boolean',
        'dinh_duong' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Scope để lấy các email đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope để lấy email theo loại báo cáo
     */
    public function scopeForReportType($query, $reportType)
    {
        return $query->where($reportType, true);
    }

    /**
     * Scope để lấy email nhận báo cáo đặc thù
     */
    public function scopeForSpecialReport($query)
    {
        return $query->where('period', true);
    }

    /**
     * Lấy danh sách email cho báo cáo BHXH
     */
    public static function getEmailsForBHXHReport($includeSpecial = false)
    {
        $query = self::active()->forReportType('bcaobhxh');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho báo cáo quản trị
     */
    public static function getEmailsForAdminReport($includeSpecial = false)
    {
        $query = self::active()->forReportType('bcaoqtri');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho thống kê chi tiết
     */
    public static function getEmailsForDetailStats($includeSpecial = false)
    {
        $query = self::active()->forReportType('qtri_tckt');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho hồ sơ đăng ký
     */
    public static function getEmailsForRegistrationFiles($includeSpecial = false)
    {
        $query = self::active()->forReportType('qtri_hsdt');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho dịch vụ kỹ thuật
     */
    public static function getEmailsForTechnicalServices($includeSpecial = false)
    {
        $query = self::active()->forReportType('qtri_dvkt');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho cảnh báo
     */
    public static function getEmailsForAlerts($includeSpecial = false)
    {
        $query = self::active()->forReportType('qtri_canhbao');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email nhận báo cáo đặc thù
     */
    public static function getEmailsForSpecialReport()
    {
        return self::active()
            ->forSpecialReport()
            ->pluck('email', 'name')
            ->toArray();
    }

    /**
     * Lấy danh sách email cho báo cáo admin (bcaoadmin)
     */
    public static function getEmailsForBcaoAdminReport($includeSpecial = false)
    {
        $query = self::active()->forReportType('bcaoadmin');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho báo cáo khoa sản
     */
    public static function getEmailsForKhoaSanReport($includeSpecial = false)
    {
        $query = self::active()->forReportType('khoa_san');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Lấy danh sách email cho báo cáo dinh dưỡng
     */
    public static function getEmailsForDinhDuongReport($includeSpecial = false)
    {
        $query = self::active()->forReportType('dinh_duong');
        
        if ($includeSpecial) {
            $query->orWhere('period', true);
        }
        
        return $query->pluck('email', 'name')->toArray();
    }

    /**
     * Validation rules
     */
    public static function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:email_receive_reports,email',
            'active' => 'boolean',
            'bcaobhxh' => 'boolean',
            'bcaoqtri' => 'boolean',
            'qtri_tckt' => 'boolean',
            'qtri_hsdt' => 'boolean',
            'qtri_dvkt' => 'boolean',
            'qtri_canhbao' => 'boolean',
            'period' => 'boolean',
            'bcaoadmin' => 'boolean',
            'khoa_san' => 'boolean',
            'dinh_duong' => 'boolean'
        ];
    }

    /**
     * Validation rules for update
     */
    public static function getUpdateValidationRules($id)
    {
        $rules = self::getValidationRules();
        $rules['email'] = 'required|email|max:255|unique:email_receive_reports,email,' . $id;
        return $rules;
    }
}
