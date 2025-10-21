<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailReceiveReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('email_receive_reports', 'email')->ignore($this->route('email_receive_report'))
            ],
            'active' => 'boolean',
            'bcaobhxh' => 'boolean',
            'bcaoqtri' => 'boolean',
            'qtri_tckt' => 'boolean',
            'qtri_hsdt' => 'boolean',
            'qtri_dvkt' => 'boolean',
            'qtri_canhbao' => 'boolean',
            'period' => 'boolean'
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Tên người nhận báo cáo không được để trống.',
            'name.string' => 'Tên người nhận báo cáo phải là chuỗi ký tự.',
            'name.max' => 'Tên người nhận báo cáo không được vượt quá 255 ký tự.',
            
            'email.required' => 'Địa chỉ email không được để trống.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.max' => 'Địa chỉ email không được vượt quá 255 ký tự.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
            
            'active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
            'bcaobhxh.boolean' => 'Trạng thái nhận báo cáo BHXH phải là true hoặc false.',
            'bcaoqtri.boolean' => 'Trạng thái nhận báo cáo quản trị phải là true hoặc false.',
            'qtri_tckt.boolean' => 'Trạng thái nhận thống kê chi tiết phải là true hoặc false.',
            'qtri_hsdt.boolean' => 'Trạng thái nhận hồ sơ đăng ký phải là true hoặc false.',
            'qtri_dvkt.boolean' => 'Trạng thái nhận dịch vụ kỹ thuật phải là true hoặc false.',
            'qtri_canhbao.boolean' => 'Trạng thái nhận cảnh báo phải là true hoặc false.',
            
            'period.boolean' => 'Trạng thái nhận báo cáo đặc thù phải là true hoặc false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'Tên người nhận báo cáo',
            'email' => 'Địa chỉ email',
            'active' => 'Trạng thái hoạt động',
            'bcaobhxh' => 'Báo cáo BHXH',
            'bcaoqtri' => 'Báo cáo quản trị',
            'qtri_tckt' => 'Thống kê chi tiết',
            'qtri_hsdt' => 'Hồ sơ đăng ký',
            'qtri_dvkt' => 'Dịch vụ kỹ thuật',
            'qtri_canhbao' => 'Cảnh báo',
            'period' => 'Báo cáo đặc thù'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Chuyển đổi các checkbox values thành boolean
        $this->merge([
            'active' => $this->boolean('active'),
            'bcaobhxh' => $this->boolean('bcaobhxh'),
            'bcaoqtri' => $this->boolean('bcaoqtri'),
            'qtri_tckt' => $this->boolean('qtri_tckt'),
            'qtri_hsdt' => $this->boolean('qtri_hsdt'),
            'qtri_dvkt' => $this->boolean('qtri_dvkt'),
            'qtri_canhbao' => $this->boolean('qtri_canhbao'),
        ]);
    }
}
