<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml4;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176\DanhMucTraCuu;
use App\Services\Xml3176\Support\NhoTheoHoSo;
use Illuminate\Support\Collection;

class Xml3176Xml4Checker
{
    use NhoTheoHoSo;

    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Xml3176ErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML4';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Xml3176Xml4 Errors
     *
     * @param Xml3176Xml4 $data
     * @return void
     */
    public function checkErrors(Xml3176Xml4 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));

        $errors = $errors->merge($this->checkChiSo($data));

        $additionalData = [
            'ngay_yl' => $data->ngay_yl
        ];

        if (!empty($data->ngay_kq)) {
            $additionalData['ngay_kq'] = $data->ngay_kq;
        }

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors, $additionalData);
    }

    /**
     * Tap ma_dich_vu (khoa so sanh SQL) cua XML3 cung ho so thuoc nhom bat buoc mo ta/ket luan.
     * Mot truy van moi ho so thay cho exists() moi dong; loc ma_nhom van de SQL lam.
     */
    private function dvktCanKetLuan($maLk): array
    {
        $nhom = config('xml3176.xml4.xml3_ma_nhom_require_ket_luan');

        return $this->theoHoSo($maLk, 'xml3_can_ket_luan|' . serialize($nhom), function () use ($maLk, $nhom) {
            $tap = [];
            foreach (Xml3176Xml3::where('ma_lk', $maLk)->whereIn('ma_nhom', $nhom)->pluck('ma_dich_vu') as $ma) {
                if ($ma !== null) { // ma_dich_vu = ? khong bao gio khop NULL
                    $tap[self::khoaChuoiSql($ma)] = true;
                }
            }

            return $tap;
        });
    }

    /**
     * #1274 mã chỉ số trống, #1276 tên chỉ số trống, #2521 XN không nhập giá trị+kết quả.
     *
     * #1274/#1276 gộp một mã XML4_CHI_SO_EMPTY (15/10/2026): LIS thường thiếu cả hai nên
     * 350.508 dòng từng ra hai lỗi cho cùng một sai sót.
     */
    private function checkChiSo(Xml3176Xml4 $data): Collection
    {
        $errors = collect();

        $coGiaTriDo = (trim((string) $data->gia_tri) !== '') || (trim((string) $data->don_vi_do) !== '');
        $thieu = [];
        if ($coGiaTriDo && empty($data->ma_chi_so)) {
            $thieu[] = 'mã chỉ số';
        }
        if ($coGiaTriDo && empty($data->ten_chi_so)) {
            $thieu[] = 'tên chỉ số';
        }
        if ($thieu) {
            $code = $this->generateErrorCode('CHI_SO_EMPTY');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Mã/tên chỉ số để trống',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Dòng có giá trị/đơn vị đo nhưng thiếu ' . implode(' và ', $thieu) . '. Dịch vụ: ' . $data->ma_dich_vu,
            ]);
        }

        $laChiSo = (trim((string) $data->ma_chi_so) !== '') || (trim((string) $data->ten_chi_so) !== '');
        $rongHet = trim((string) $data->gia_tri) === ''
            && trim((string) $data->mo_ta) === ''
            && trim((string) $data->ket_luan) === '';
        // Ra soat 15/10/2026: DVKT nhom bat buoc mo ta/ket luan da co INFO_ERROR_MO_TA_EMPTY/
        // KET_LUAN_EMPTY bao (dung dieu kien cua infoChecker) thi nhuong, khong ra loi thu ba.
        $quyTacCuDaBao = $laChiSo && $rongHet && !empty($data->ma_dich_vu)
            && (empty($data->mo_ta) || empty($data->ket_luan))
            && isset($this->dvktCanKetLuan($data->ma_lk)[self::khoaChuoiSql($data->ma_dich_vu)]);
        if ($laChiSo && $rongHet && !$quyTacCuDaBao) {
            $code = $this->generateErrorCode('XN_MISSING_VALUE_RESULT');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Xét nghiệm không nhập giá trị và kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Chỉ số ' . ($data->ten_chi_so ?: $data->ma_chi_so) . ' không có giá trị/mô tả/kết luận. Dịch vụ: ' . $data->ma_dich_vu,
            ]);
        }

        return $errors;
    }

    /**
     * Check for outpatient bed day errors
     *
     * @param Xml3176Xml4 $data
     * @return Collection
     */
    private function infoChecker(Xml3176Xml4 $data): Collection
    {
        $errors = collect();


        if (empty($data->ma_dich_vu)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_DICH_VU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã dịch vụ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã dịch vụ không được để trống'
            ]);
        } else {
            if (strlen($data->ma_dich_vu) > 20) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_DICH_VU_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã dịch vụ quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã dịch vụ không được lớn hơn 20 kí tự: ' . $data->ma_dich_vu
                ]);
            }

            // Danh muc DVKT cap theo CO SO; dong khong gan ma co so dung chung.
            $data->loadMissing('Xml3176Xml1');
            $maCskcb = $data->Xml3176Xml1 ? $data->Xml3176Xml1->ma_cskcb : null;

            if (!DanhMucTraCuu::dvktTonTai($maCskcb, $data->ma_dich_vu)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_DICH_VU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã dịch vụ không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã dịch vụ không tồn tại trong danh mục DVKT: ' . $data->ma_dich_vu
                ]);
            }
        }

        //ket_luan & mo_ta không được để trống
        if (!empty($data->ma_dich_vu)) {
            $require_mo_ta_ket_luan = isset($this->dvktCanKetLuan($data->ma_lk)[self::khoaChuoiSql($data->ma_dich_vu)]);

            if ($require_mo_ta_ket_luan && empty($data->mo_ta)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MO_TA_EMPTY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mô tả không được để trống',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mô tả không được để trống đối với DVKT: ' . $data->ma_dich_vu
                ]);
            }

            if ($require_mo_ta_ket_luan && empty($data->ket_luan)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_KET_LUAN_EMPTY');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Kết luận không được để trống',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Kết luận không được để trống đối với DVKT: ' . $data->ma_dich_vu
                ]);
            }
        }

        if (!empty($data->ma_chi_so)) {
            if (strlen($data->ma_chi_so) > 50) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_CHI_SO_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã chỉ số quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã chỉ số không được lớn hơn 50 kí tự: ' . $data->ma_chi_so
                ]);
            }
        }

        if (!empty($data->ten_chi_so)) {
            if (strlen($data->ten_chi_so) > 255) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_TEN_CHI_SO_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Tên chỉ số quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Tên chỉ số không được lớn hơn 255 kí tự: ' . $data->ten_chi_so
                ]);
            }
        }

        if (!empty($data->gia_tri)) {
            if (strlen($data->gia_tri) > 50) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_GIA_TRI_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Giá trị quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Giá trị không được lớn hơn 50 kí tự: ' . $data->gia_tri
                ]);
            }
        }

        if (!empty($data->don_vi_do)) {
            if (strlen($data->don_vi_do) > 50) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_DON_VI_DO_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Đơn vị đo quá dài',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Đơn vị đo không được lớn hơn 50 kí tự: ' . $data->don_vi_do
                ]);
            }
        }
        
        if (empty($data->ngay_kq)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_KQ');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày trả kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày trả kết quả không được để trống'
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}