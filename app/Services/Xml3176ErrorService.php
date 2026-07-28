<?php

namespace App\Services;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Support\Collection;

class Xml3176ErrorService
{
    /** Kich thuoc lo khi chen. */
    const CO_LO = 500;

    /** @var bool */
    private $dangGom = false;

    /** @var array */
    private $boDem = [];

    public function batDauGom(): void
    {
        $this->dangGom = true;
        $this->boDem = [];
    }

    public function dangGom(): bool
    {
        return $this->dangGom;
    }

    public function soDongTrongBoDem(): int
    {
        return count($this->boDem);
    }

    /**
     * Ghi toan bo bo dem roi tat che do gom.
     *
     * Goi hai lan lien tiep khong nem loi - job goi trong finally nen phai chiu duoc.
     */
    public function ketThucGom(): void
    {
        $boDem = $this->boDem;
        $this->boDem = [];
        $this->dangGom = false;

        if (empty($boDem)) {
            return;
        }

        $ma = array_values(array_unique(array_column($boDem, 'error_code')));

        // Mot truy van thay cho mot truy van moi loi.
        $maBiTat = Xml3176ErrorCatalog::whereIn('error_code', $ma)
            ->where('is_check', false)
            ->pluck('error_code')
            ->all();

        $sanSang = self::chuanBiGhi($boDem, $maBiTat, now()->toDateTimeString());

        foreach ($sanSang['nhom'] as $dsDong) {
            foreach (array_chunk($dsDong, self::CO_LO) as $lo) {
                Xml3176ErrorResult::insert($lo);
            }
        }

        foreach ($sanSang['danhMuc'] as $dm) {
            Xml3176ErrorCatalog::createOrUpdate(
                $dm['xml'], $dm['error_code'], $dm['error_name'], $dm['critical_error']
            );
        }
    }

    /**
     * Bien bo dem thanh du lieu san sang ghi.
     *
     * Tach rieng thanh ham THUAN vi ba cai bay deu nam o day:
     *   - insert() khong tu dien created_at/updated_at nhu create()
     *   - insert() nhieu dong lay ten cot tu DONG DAU TIEN, nen phai gom theo BO COT
     *   - danh muc chi can ghi mot lan cho moi cap (xml, ma loi) khac nhau
     *
     * @param array  $boDem    Cac phan tu co: xml, ma_lk, stt, error_code, description,
     *                         critical_error, error_name, them
     * @param array  $maBiTat  Cac ma loi bi tat kiem tra
     * @param string $thoiDiem Dau thoi gian dung cho ca lo
     * @return array ['nhom' => array<string, array>, 'danhMuc' => array]
     */
    public static function chuanBiGhi(array $boDem, array $maBiTat, string $thoiDiem): array
    {
        $maBiTat = array_flip($maBiTat);
        $nhom = [];
        $danhMuc = [];

        foreach ($boDem as $d) {
            if (isset($maBiTat[$d['error_code']])) {
                continue;
            }

            $dong = [
                'xml'            => $d['xml'],
                'ma_lk'          => $d['ma_lk'],
                'stt'            => $d['stt'],
                'error_code'     => $d['error_code'],
                'description'    => $d['description'],
                'critical_error' => $d['critical_error'],
            ];

            if (!empty($d['them'])) {
                $dong = array_merge($dong, $d['them']);
            }

            $dong['created_at'] = $thoiDiem;
            $dong['updated_at'] = $thoiDiem;

            $khoaNhom = implode(',', array_keys($dong));
            $nhom[$khoaNhom][] = $dong;

            $khoaDanhMuc = $d['xml'] . '|' . $d['error_code'];

            if (!isset($danhMuc[$khoaDanhMuc])) {
                $danhMuc[$khoaDanhMuc] = [
                    'xml'            => $d['xml'],
                    'error_code'     => $d['error_code'],
                    'error_name'     => $d['error_name'],
                    'critical_error' => $d['critical_error'],
                ];
            }
        }

        return ['nhom' => $nhom, 'danhMuc' => array_values($danhMuc)];
    }

    /**
    * Xóa các lỗi cũ và lưu các lỗi mới
    *
    * @param string $xmlType
    * @param string $ma_lk
    * @param int $stt
    * @param Collection $errors
    * @return void
    **/
    public function deleteErrors(string $ma_lk): void
    {
        // Delete old errors
        Xml3176ErrorResult::where('ma_lk', $ma_lk)
        ->delete();
    }

    public function getCriticalErrorStatus($errorCode)
    {
        // Tìm bản ghi trong Xml3176ErrorCatalog theo error_code
        $errorCatalog = Xml3176ErrorCatalog::where('error_code', $errorCode)->first();

        // Trả về critical_error nếu có, nếu không thì trả về true
        return $errorCatalog ? $errorCatalog->critical_error : true;
    }

    public function saveErrors(string $xmlType, string $ma_lk, int $stt, Collection $errors,  array $additionalData = []): void
    {
        // Dang gom: chi day vao bo dem, khong cham co so du lieu. Ban cu ban BA truy van
        // cho MOI loi (tra danh muc, chen dong, ghi lai danh muc), ma saveErrors duoc goi
        // TUNG DONG tu ben trong checker.
        if ($this->dangGom) {
            foreach ($errors as $error) {
                $this->boDem[] = [
                    'xml'            => $xmlType,
                    'ma_lk'          => $ma_lk,
                    'stt'            => $stt,
                    'error_code'     => $error->error_code,
                    'description'    => $error->description,
                    'critical_error' => $error->critical_error ?? false,
                    'error_name'     => $error->error_name ?? null,
                    'them'           => $additionalData,
                ];
            }

            return;
        }

        // Save errors to xml_error_checks table
        foreach ($errors as $error) {
            // Xem lỗi này có được đánh dấu kiểm tra không
            $skipCheck = Xml3176ErrorCatalog::where('error_code', $error->error_code)
                ->where('is_check', false)
                ->exists();

            // Nếu lỗi được đánh dấu là không kiểm tra thì bỏ qua
            if ($skipCheck) {
                continue;
            }

            $data = [
                'xml' => $xmlType,
                'ma_lk' => $ma_lk,
                'stt' => $stt,
                'error_code' => $error->error_code,
                'description' => $error->description,
                'critical_error' => $error->critical_error ?? false
            ];

            // Merge additional data if provided
            if (!empty($additionalData)) {
                $data = array_merge($data, $additionalData);
            }
            
            Xml3176ErrorResult::create($data);

            // Create or update in Xml3176ErrorCatalog
            Xml3176ErrorCatalog::createOrUpdate($xmlType, $error->error_code, $error->error_name ?? null, $error->critical_error ?? false);
        }
    }
}