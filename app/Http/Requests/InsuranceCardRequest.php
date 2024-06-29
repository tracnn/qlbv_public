<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceCardRequest extends FormRequest
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
        return [
            'insurance-number'   => 'required|min:'.config('__tech.card-number.min').'|max:'.config('__tech.card-number.max'),
            'name'          => 'required',
            'birthday'      => 'required|min:10|max:10',
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
            'insurance-number.required'      => __('insurance.backend.exceptions.card-number.required'),
            'insurance-number.min'      => __('insurance.backend.exceptions.card-number.min'),
            'insurance-number.max'      => __('insurance.backend.exceptions.card-number.max'),
            'name.required'      => __('insurance.backend.exceptions.name.required'),
            'birthday.required'      => __('insurance.backend.exceptions.birthday.required'),
            'birthday.min'  => __('insurance.backend.exceptions.birthday.lenght'),
            'birthday.max'  => __('insurance.backend.exceptions.birthday.lenght'),
        ];
    }
}
