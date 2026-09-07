<?php

namespace App\Http\Requests;

use App\Services\BHYT\CoSoTraCuu;
use Illuminate\Foundation\Http\FormRequest;

class McctRequest extends FormRequest
{
    /**
     * Ba dinh dang ngay sinh cong chap nhan: dd/mm/yyyy, mm/yyyy, yyyy.
     *
     * MOT CHO DUY NHAT dinh nghia chuoi nay: man web (rules() ben duoi) va API
     * (McctApiController::thieuThamSoLamMoi()) phai doc CUNG mot regex - du an nay da bi
     * chep doi can ba lan (xem qlbv-test-infra-gotchas / bay-tiem-container).
     */
    const REGEX_NGAY_SINH = '#^((0[1-9]|[12]\d|3[01])/(0[1-9]|1[0-2])/\d{4}|(0[1-9]|1[0-2])/\d{4}|\d{4})$#';

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
        // KHONG tin trinh duyet: o chon sua duoc tu phia nguoi dung. Ma ngoai danh sach phai
        // bi chan o day, truoc khi cham toi cong BHXH.
        $maHopLe = CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh());

        return [
            // ma_cskcb 'nullable' chu KHONG 'required', giong InsuranceRequest: thieu ma thi
            // controller dung lai o man da dien san cho nguoi dung chon, khong bao loi do.
            // Con ma SAI thi van bi chan o day.
            'ma_cskcb' => 'nullable|in:' . implode(',', $maHopLe),

            // Phu luc: do dai hop le sau khi bo khoang trang la 10, 12 hoac 15.
            // Khai bang MANG chu khong phai chuoi: luat regex chua dau '|' se bi Laravel
            // cat nham thanh nhieu luat neu viet dang chuoi 'required|regex:...'.
            'ma_the' => ['required', 'regex:/^[A-Za-z0-9]{10}$|^[A-Za-z0-9]{12}$|^[A-Za-z0-9]{15}$/'],

            'ho_ten' => 'required',

            // Ba dinh dang cong chap nhan: dd/MM/yyyy, MM/yyyy, yyyy. Dung regex thay vi
            // date_format vi Laravel 5.5 chi nhan MOT dinh dang cho date_format, khong the
            // liet ke nhieu dinh dang bang dau phay. Regex rang buoc mien ngay/thang de
            // 1/1/1990, 1990-01-01, 01-01-1990, 32/01/1990 deu bi chan.
            // Danh doi co chu dich: 31/02/1990 (ngay khong ton tai nhung dung mien 01-31/01-12)
            // van lot qua luat nay - kiem tra ngay thuc (checkdate) khong thuoc pham vi rules().
            'ngay_sinh' => ['required', 'regex:' . self::REGEX_NGAY_SINH],
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
            'ma_cskcb.in' => 'Cơ sở khám chữa bệnh không hợp lệ.',
            'ma_the.required' => 'Chưa nhập mã thẻ BHYT.',
            'ma_the.regex' => 'Mã thẻ BHYT phải có 10, 12 hoặc 15 ký tự.',
            'ho_ten.required' => 'Chưa nhập họ và tên.',
            'ngay_sinh.required' => 'Chưa nhập ngày sinh.',
            'ngay_sinh.regex' => 'Ngày sinh phải theo dd/mm/yyyy, mm/yyyy hoặc yyyy.',
        ];
    }

    /**
     * Chuan hoa ma the TRUOC khi kiem.
     *
     * Cong tu bo khoang trang truoc khi do do dai, nen mot ma go co dau cach van hop le voi
     * cong. Neu khong chuan hoa o day thi luat regex se chan oan va bao "phai co 10, 12 hoac
     * 15 ky tu" - mot cau sai su that, va nguoi dung khong biet phai sua gi.
     */
    protected function prepareForValidation()
    {
        $this->merge(['ma_the' => self::chuanHoaMaThe($this->get('ma_the'))]);
    }

    /**
     * Bo MOI khoang trang va viet hoa.
     *
     * Cong tu bo khoang trang truoc khi do do dai, nen mot ma go co dau cach van hop le voi
     * cong nhung se truot luat regex o tren neu khong chuan hoa truoc.
     *
     * @param string $maThe
     * @return string
     */
    public static function chuanHoaMaThe($maThe)
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $maThe));
    }
}
