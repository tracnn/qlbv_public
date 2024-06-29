<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MedRegRequest extends FormRequest
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
            'birthday'      => 'required|date|before:tomorrow',
            'healthcaredate'      => 'required|date|after:today',
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
            'birthday.required'      => __('medreg.exceptions.birthday.required'),
            'birthday.before'      => __('medreg.exceptions.birthday.before'),
            'healthcaredate.required'      => __('medreg.exceptions.symptom.required'),
            'healthcaredate.after'      => __('medreg.exceptions.healthcaredate.after'),
        ];
    }
}
