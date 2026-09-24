<?php

namespace App\Services\BHYT;

use App\Jobs\jobKtTheBHYT;
use App\Services\OrderCheck\TreatmentProfileService;
use App\Services\Xml3176\Support\TheTamSoSinh;
use Illuminate\Support\Facades\Log;

/**
 * Gui yeu cau tra lai the BHYT cho MOT ho so - dung chung cho man Tra cuu loi ho so va man
 * Ket qua tra cuu the. Mot noi duy nhat kiem ho so HIS truoc khi day job: tung co hai ban
 * tu viet, sua mot ben quen ben kia.
 */
class TraLaiThe
{
    protected $hoSo;

    public function __construct(TreatmentProfileService $hoSo)
    {
        $this->hoSo = $hoSo;
    }

    /**
     * @param string $treatmentCode ma dieu tri (= ma_lk)
     * @return array ['ok' => bool, 'message' => string]
     */
    public function gui($treatmentCode)
    {
        $ma = trim((string) $treatmentCode);

        if ($ma === '') {
            return $this->kq(false, 'Chưa nhập mã điều trị');
        }

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra lai the: loi doc HIS', ['treatment_code' => $ma, 'loi' => $e->getMessage()]);

            return $this->kq(false, 'Không lấy được thông tin từ HIS');
        }

        if (!$hoSo) {
            return $this->kq(false, 'Không tìm thấy hồ sơ với mã này trên HIS');
        }

        if (trim((string) $hoSo['hein_card_number']) === '') {
            return $this->kq(false, 'Hồ sơ không có mã thẻ BHYT');
        }

        // The tam: VAN day job - job khong goi cong ma xoa ket qua loi cu cua ho so, nen
        // nut bam cung la cach don tung dong. Khong can gioi tinh/co so vi job thoat truoc.
        if (TheTamSoSinh::la($hoSo['hein_card_number'], $hoSo['hein_medi_org_code'])) {
            $this->day($hoSo);

            return $this->kq(true, 'Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ ' . trim((string) $hoSo['hein_medi_org_code'])
                . '), không tra cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ');
        }

        // Left join his_gender (xem TreatmentProfileService) nen gioi tinh co the rong.
        // Gui rong len cong chi doi mot loi ro rang lay mot ket qua sai.
        if (trim((string) $hoSo['gender_code']) === '') {
            return $this->kq(false, 'Hồ sơ thiếu giới tính');
        }

        $maCskcb = trim((string) $hoSo['ma_cskcb']);
        $dsCoSo = config('organization.BHYT_CO_SO', []);

        if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
            return $this->kq(false, 'Không xác định được cơ sở của hồ sơ');
        }

        $this->day($hoSo);

        return $this->kq(true, 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả');
    }

    protected function day(array $hoSo)
    {
        jobKtTheBHYT::dispatch([
            'maThe'    => $hoSo['hein_card_number'],
            'hoTen'    => $hoSo['patient_name'],
            'ngaySinh' => dob($hoSo['patient_dob']),
            'ma_lk'    => $hoSo['treatment_code'],
            // maCskcb = co so DIEU TRI (chon tai khoan cong); maDkbd = noi DKBD tren THE.
            'maCskcb'  => trim((string) $hoSo['ma_cskcb']),
            'maDkbd'   => $hoSo['hein_medi_org_code'],
            'gioiTinh' => $this->gioiTinhCongBhxh($hoSo['gender_code']),
        // checkOldValue = false: de mac dinh true thi job thay ket qua cu con hop le va
        // thoat ngay - dung nghia "bam nut xong khong co gi xay ra".
        ], false)->onQueue('JobKtTheBHYT');
    }

    /**
     * HIS dung gender_code 1 = Nam, 2 = Nu; cong BHXH dung quy uoc nguoc lai. Lenh quet
     * HISProKiemTraTheBHYT dao o cung cho nay - bo qua thi cong tra ve ket qua sai.
     */
    protected function gioiTinhCongBhxh($genderCode)
    {
        $g = (int) $genderCode;

        if ($g === 1) {
            return 2;
        }

        if ($g === 2) {
            return 1;
        }

        return $g;
    }

    protected function kq($ok, $message)
    {
        return ['ok' => $ok, 'message' => $message];
    }
}
