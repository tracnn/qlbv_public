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

        // ma_cskcb la 'nullable' chu KHONG phai 'required': co 8 noi trong he thong tro sau
        // vao man nay kem so the / ho ten / ngay sinh nhung khong kem ma co so (bao cao trai
        // tuyen, danh sach XML3176, XML QD130, kiem tra du lieu gui, tra cuu he thong...).
        // De 'required' thi ca 8 duong do bao loi do, trong khi nguoi dung khong lam gi sai.
        // Thieu ma co so thi controller dung lai o man da dien san cho nguoi dung chon,
        // KHONG goi len cong. Con ma SAI thi van bi chan o day.
        return [
            'card-number'   => 'required|min:'.config('__tech.card-number.min').'|max:'.config('__tech.card-number.max'),
            'name'          => 'required',
            'birthday'      => 'required',
            'ma_cskcb'      => 'nullable|in:'.implode(',', $maHopLe),
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
