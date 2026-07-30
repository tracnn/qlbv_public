<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BHYT\CoSoTraCuu;

class InsuranceRequest extends FormRequest
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
        // KHONG tin trinh duyet: o chon va localStorage deu sua duoc tu phia nguoi dung.
        // Ma ngoai danh sach phai bi chan o day, truoc khi cham toi cong BHXH.
        $maHopLe = CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh());

        return [
            'card-number'   => 'required|min:'.config('__tech.card-number.min').'|max:'.config('__tech.card-number.max'),
            'name'          => 'required',
            'birthday'      => 'required',
            'ma_cskcb'      => 'required|in:'.implode(',', $maHopLe),
        ];
    }

    /**
     * Get the message validation rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'card-number.required'      => __('insurance.backend.exceptions.card-number.required'),
            'card-number.min'      => __('insurance.backend.exceptions.card-number.min'),
            'card-number.max'      => __('insurance.backend.exceptions.card-number.max'),
            'name.required'      => __('insurance.backend.exceptions.name.required'),
            'birthday.required'      => __('insurance.backend.exceptions.birthday.required'),
            'ma_cskcb.required'      => __('insurance.backend.exceptions.ma_cskcb.required'),
            'ma_cskcb.in'            => __('insurance.backend.exceptions.ma_cskcb.in'),
        ];
    }
}
