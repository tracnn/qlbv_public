<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Imports\CatalogChunkImport;
use App\Services\Import\GhiTheoLo;
use App\Services\Import\KetQuaNhapDanhMuc;

class CatalogImportService
{
    protected $columnMapper;
    protected $catalogConfigs;

    /** @var KetQuaNhapDanhMuc dat lai moi lan import() */
    protected $ketQua;

    /** @var array bang => [ten cot so]; nho de khong hoi lai lieu do moi lo */
    protected $cotSo = [];

    /** @var array bang => [ten tat ca cot]; doc cung luot voi cotSo */
    protected $cot = [];

    public function __construct(ExcelColumnMapper $columnMapper)
    {
        $this->columnMapper = $columnMapper;
        $this->catalogConfigs = config('catalog_import_mapping');
    }

    /** Ba danh muc do BHXH cap RIENG cho tung co so kham chua benh */
    const DANH_MUC_THEO_CO_SO = ['medicine', 'medical_supply', 'service'];

    /**
     * Danh muc di duong GHI THEO LO: bo nho phang theo lo, khong dung ca tep.
     *
     * Duong con lai ("gom") dung TOAN BO dong trong mot Collection roi moi ghi tung dong bang
     * updateOrCreate. Do tren tep ICD that cua cong BHXH - 52.551 dong x 15 cot, nen 1,1 MB
     * nhung bung ra 29 MB XML + 5,8 MB sharedStrings - CHI RIENG PHAN DOC da an 128 MB va 346
     * giay, chua tinh ~105.000 truy van. May chu dat PHP 128 MB / 120 giay nen tien trinh chet
     * vi fatal error; fatal error khong phai \Exception nen nguoi dung chi thay "Loi khi tai
     * len" khong kem ly do.
     *
     * Danh muc nao co ten bang trong bangCua() va khoa duy nhat trong cau hinh thi dua vao day.
     */
    const GHI_THEO_LO = ['medicine', 'medical_supply', 'service', 'icd10', 'icd_yhct',
                         'administrative_unit', 'medical_organization', 'medical_staff',
                         'department_bed', 'equipment', 'job_categories'];

    /**
     * Danh muc LAM MOI TRON BO: tat is_active cua toan bo ban ghi cu roi bat lai cho dong co
     * trong tep, tuc dong khong con trong tep nam lai o trang thai tat.
     *
     * Chi hai danh muc dung chung toan quoc nay theo ngu nghia do. Dua danh muc khac vao day
     * la tat is_active cua du lieu cu ma khong bat lai.
     */
    const LAM_MOI_TRON_BO = ['administrative_unit', 'medical_organization'];

    /**
     * Bat lai trang thai dang dung cho dong co trong tep.
     *
     * Ham THUAN de kiem duoc. Dung 1 chu khong phai true: gia tri di thang vao DB::table()
     * nen phai la thu MySQL nhan cho cot tinyint.
     */
    public static function ganDangDung(array $duLieu, $type)
    {
        if (in_array($type, self::LAM_MOI_TRON_BO, true)) {
            $duLieu['is_active'] = 1;
        }

        return $duLieu;
    }

    /**
     * Khoa duy nhat THUC SU dung cho mot lan nhap.
     *
     * Mau moi cua BHXH cho nhan vien y te khong con cot MA_BHXH: bam theo ma_bhxh se cho ra
     * khoa rong o moi dong, tuc moi dong bi coi la ban ghi moi va chen trung lap moi lan nhap.
     * Cau hinh khai unique_keys_alt cho truong hop do.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function khoaDungCho(array $cfg, array $mapping)
    {
        $chinh = $cfg['unique_keys'];

        if (empty($cfg['unique_keys_alt'])) {
            return $chinh;
        }

        foreach ($chinh as $k) {
            if (!isset($mapping[$k])) {
                // Chi doi khi tep co du khoa thay the; khong thi giu khoa chinh de bao loi ro.
                foreach ($cfg['unique_keys_alt'] as $kt) {
                    if (!isset($mapping[$kt])) {
                        return $chinh;
                    }
                }

                return $cfg['unique_keys_alt'];
            }
        }

        return $chinh;
    }

    /**
     * Quy NGAYCAP_CCHN ve chuoi Ymd.
     *
     * Excel tra ve SO SERIAL cho o dinh dang ngay, con cot ngaycap_cchn la varchar va XML giam
     * dinh doi dang YYYYMMDD. O khong doc duoc thi GIU NGUYEN de dong do bao loi: nem ra day se
     * lam chet ca lan nhap vi mot o hong.
     *
     * O de trong phai giu nguyen chuoi rong: Carbon::parse('') tra ve HOM NAY, tuc ghi lang le
     * mot ngay cap chung chi hanh nghe sai.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function chuanHoaNgayCchn(array $duLieu)
    {
        if (!array_key_exists('ngaycap_cchn', $duLieu)) {
            return $duLieu;
        }

        $v = $duLieu['ngaycap_cchn'];

        if ($v === null || trim((string) $v) === '') {
            return $duLieu;
        }

        // DA dung dang thi giu nguyen. Phai xet TRUOC so serial: '20230415' vua la 8 chu so
        // hop le vua la mot so, va doc nham nhu serial cho ra 2008-12-27.
        if (preg_match('/^\d{8}$/', (string) $v) && self::laNgayDung('Ymd', $v)) {
            return $duLieu;
        }

        if (is_numeric($v) && (float) $v > 3000) {
            // So serial cua Excel. Nguong 3000 de khong doi mot chuoi so ngan thanh ngay.
            try {
                $duLieu['ngaycap_cchn'] = Carbon::instance(Date::excelToDateTimeObject($v))->format('Ymd');

                return $duLieu;
            } catch (\Throwable $e) {
                // Roi xuong cac cach doc chuoi ben duoi.
            }
        }

        foreach (['m/d/Y H:i', 'd/m/Y', 'd/m/Y H:i'] as $dang) {
            if (self::laNgayDung($dang, $v)) {
                $duLieu['ngaycap_cchn'] = \DateTime::createFromFormat($dang, (string) $v)->format('Ymd');

                return $duLieu;
            }
        }

        try {
            $duLieu['ngaycap_cchn'] = Carbon::parse($v)->format('Ymd');
        } catch (\Throwable $e) {
            // Giu nguyen gia tri goc: dong do se bao loi, cac dong khac van vao.
        }

        return $duLieu;
    }

    /**
     * Gia tri co dung DUNG mot dang ngay khong.
     *
     * createFromFormat khong tra false cho '20239999' ma tu "tran" sang thang sau roi ghi mot
     * warning - phai xet warning_count moi biet la sai.
     */
    private static function laNgayDung($dang, $v)
    {
        $ngay = \DateTime::createFromFormat($dang, (string) $v);

        if ($ngay === false) {
            return false;
        }

        $loi = \DateTime::getLastErrors();

        return $loi['warning_count'] === 0 && $loi['error_count'] === 0;
    }

    /**
     * Bo cac truong khong phai cot that cua bang.
     *
     * Duong cu ghi qua Eloquent nen fillable am tham loc bo truong la. Ghi theo lo dung
     * DB::table() thang: de nguyen mot truong la la loi "Unknown column" cho CA lo, tuc mat
     * ca lan nhap chu khong phai mot dong.
     *
     * Da co that: anh xa cua medical_organization khai tuen_cmkt va hang_benh_vien, con bang
     * medical_organizations khong co hai cot do.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function giuCotCoThat(array $duLieu, array $cotBang)
    {
        return array_intersect_key($duLieu, array_flip($cotBang));
    }

    /**
     * Ma co so THUC SU ap cho mot loai danh muc.
     *
     * ganCoSo() them khoa ma_cskcb vao du lieu ghi. Chi ba danh muc theo co so co cot do;
     * bang icd10_categories khong co nen ap cho ICD la hong ca lo chen.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function maCoSoApDung($type, $maCskcb)
    {
        return in_array($type, self::DANH_MUC_THEO_CO_SO, true) ? $maCskcb : null;
    }

    /**
     * Quy co "benh man tinh" ve boolean.
     *
     * Cot is_chronic la boolean con gia tri tu Excel la chuoi tu do ('x', 'Co', '1'...).
     * O de trong cho ra CHUOI RONG nen phai ra false, KHONG duoc de chuanHoaSo bien thanh
     * null - cot do NOT NULL.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function chuanHoaManTinh(array $duLieu)
    {
        if (!array_key_exists('is_chronic', $duLieu)) {
            return $duLieu;
        }

        $v = mb_strtolower(trim((string) $duLieu['is_chronic']));
        $duLieu['is_chronic'] = in_array($v, ['1', 'true', 'x', 'co', 'có', 'yes'], true);

        return $duLieu;
    }

    /**
     * Gan ma co so cho khoa duy nhat khi dong khong tu khai.
     *
     * Ham thuan de kiem duoc. Gia tri trong TEP luon thang: mot tep co the chua nhieu co
     * so, con o chon tren man nhap chi la mac dinh cho dong bo trong.
     */
    public static function ganCoSo(array $uniqueKeys, $maCskcb)
    {
        $maCskcb = trim((string) $maCskcb);

        if ($maCskcb === '') {
            return $uniqueKeys;
        }

        if (!isset($uniqueKeys['ma_cskcb']) || trim((string) $uniqueKeys['ma_cskcb']) === '') {
            $uniqueKeys['ma_cskcb'] = $maCskcb;
        }

        return $uniqueKeys;
    }

    /**
     * @param string $filePath
     * @param string|null $maCskcb Ma CSKCB chon tren man nhap; chi ap cho ba danh muc
     *                             theo co so, va chi cho dong khong tu khai MA_CSKCB.
     */
    public function import($filePath, $maCskcb = null)
    {
        $this->ketQua = new KetQuaNhapDanhMuc();

        $tt = ['type' => null, 'mapping' => null, 'ghi' => null, 'lo' => []];

        // Doc THEO LO: Excel::toCollection nap toan bo tep, do duoc tep 1,3 MB (10.000 dong
        // x 23 cot) lam DINH bo nho 208 MB.
        $imp = new CatalogChunkImport(function ($rows, $dongDau, $laLoDau) use (&$tt, $maCskcb) {
            if ($laLoDau) {
                $this->nhanDienTuLoDau($rows, $tt);
                $rows = $rows->slice(1);
                $dongDau = 2;
            }

            // Tep rong: nhanDienTuLoDau ra ve tay khong, chua co cau hinh nao de xu ly lo.
            if ($tt['type'] === null) {
                return;
            }

            $this->xuLyLo($rows, $dongDau, $tt, $maCskcb);
        });

        Excel::import($imp, $filePath);

        if ($tt['type'] === null) {
            throw new \Exception('File không chứa dữ liệu');
        }

        $tt['ghi']->ghi($tt['lo']);   // lo cuoi con du

        return $this->ketQua;
    }

    /** Nhan dien loai danh muc va dung anh xa cot tu LO DAU (dong tieu de chi co o day). */
    protected function nhanDienTuLoDau($rows, array &$tt)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $tt['header'] = $rows->first();
        $headerRow = $rows->first()->values()->toArray();

        $tt['type'] = $this->columnMapper->detectCatalogType($headerRow, $this->catalogConfigs);

        if (!$tt['type']) {
            throw new \Exception('Không thể xác định loại danh mục. Vui lòng kiểm tra lại cấu trúc file.');
        }

        $cfg = $this->catalogConfigs[$tt['type']];
        $tt['mapping'] = $this->columnMapper->createFieldMapping($headerRow, $cfg['mapping']);

        $batBuoc = isset($cfg['required_fields']) ? $cfg['required_fields'] : [];
        $thieu = array_diff($batBuoc, array_keys($tt['mapping']));

        if (!empty($thieu)) {
            throw new \Exception(
                'Thiếu các cột bắt buộc: ' . implode(', ', $thieu) .
                '. Vui lòng kiểm tra lại file Excel.'
            );
        }

        if (in_array($tt['type'], self::GHI_THEO_LO, true)) {
            $tt['ghi'] = new GhiTheoLo($this->bangCua($tt['type']),
                self::khoaDungCho($cfg, $tt['mapping']), $this->ketQua);
        }

        // Lam moi tron bo: tat het MOT LAN o day, sau khi da chac tep dung dinh dang. Tat
        // truoc khi nhan dien la co nguy co tat sach du lieu dang dung roi bao "khong xac dinh
        // duoc loai danh muc".
        if (in_array($tt['type'], self::LAM_MOI_TRON_BO, true)) {
            DB::table($this->bangCua($tt['type']))->where('is_active', 1)->update(['is_active' => 0]);
        }
    }

    /** Ten bang cua cac danh muc ghi theo lo */
    protected function bangCua($type)
    {
        $map = [
            'medicine' => 'medicine_catalogs',
            'medical_supply' => 'medical_supply_catalogs',
            'service' => 'service_catalogs',
            'icd10' => 'icd10_categories',
            'icd_yhct' => 'icd_yhct_categories',
            'administrative_unit' => 'administrative_units',
            'medical_organization' => 'medical_organizations',
            'medical_staff' => 'medical_staffs',
            'department_bed' => 'department_bed_catalogs',
            'equipment' => 'equipment_catalogs',
            'job_categories' => 'job_categories',
        ];

        return $map[$type];
    }

    /**
     * Xu ly mot lo: dung du lieu roi gom vao lo ghi.
     *
     * Doi Collection sang mang MOT LAN moi dong: getRowValue cu goi toArray() moi truong
     * moi dong - cham 20 lan o doan do.
     */
    protected function xuLyLo($rows, $dongDau, array &$tt, $maCskcb)
    {
        $cfg = $this->catalogConfigs[$tt['type']];
        $maCoSo = self::maCoSoApDung($tt['type'], $maCskcb);
        $bang = $this->bangCua($tt['type']);
        $i = 0;

        foreach ($rows as $row) {
            $dongExcel = $dongDau + $i;
            $i++;

            $mang = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array) $row;

            if (!$this->hasRequiredFields($mang, $cfg['required_fields'], $tt['mapping'])) {
                $this->ketQua->themBoQua($dongExcel, 'Thiếu trường bắt buộc');
                continue;
            }

            $duLieu = [];

            foreach ($cfg['mapping'] as $field => $x) {
                $v = $this->getRowValue($mang, $field, $tt['mapping']);

                if ($v !== null) {
                    $duLieu[$field] = $v;
                }
            }

            if ($tt['type'] === 'service') {
                // Giu nguyen hanh vi cu cua importService.
                $duLieu['cskcb_cgkt'] = null;
                $duLieu['cskcb_cls'] = null;
            }

            $duLieu = self::catKhoangTrang($duLieu);
            $duLieu = self::ganCoSo($duLieu, $maCoSo);
            $duLieu = self::chuanHoaNgayCchn($duLieu);
            $duLieu = self::chuanHoaSo($duLieu, $this->cotSoCua($bang));
            // SAU chuanHoaSo: is_chronic la cot tinyint nen chuanHoaSo quy o de trong ve null,
            // ma cot do NOT NULL. Dao thu tu se lam chinh gia tri false bi quy ve null.
            $duLieu = self::chuanHoaManTinh($duLieu);
            $duLieu = self::ganDangDung($duLieu, $tt['type']);
            // Cuoi cung: mot truong khong phai cot that la loi "Unknown column" cho CA lo.
            $duLieu = self::giuCotCoThat($duLieu, $this->cotCua($bang));
            $tt['lo'][] = ['dong_excel' => $dongExcel, 'du_lieu' => $duLieu];

            if (count($tt['lo']) >= 500) {
                $tt['ghi']->ghi($tt['lo']);
                $tt['lo'] = [];
            }
        }
    }

    /**
     * Cat khoang trang thua o MOI gia tri chuoi truoc khi ghi.
     *
     * O Excel hay dinh TAB hoac dau cach o cuoi. Truoc day gia tri do duoc luu nguyen, ma
     * khoa so khop lai dung trim() cua PHP - hai ben lech nhau nen dong da co bi coi la
     * moi va chen them MOI LAN nhap. Da gap that: ma '24.0019.1685.K.01910' dinh tab sinh
     * 5 ban trong service_catalogs.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function catKhoangTrang(array $duLieu)
    {
        foreach ($duLieu as $cot => $v) {
            if (is_string($v)) {
                $duLieu[$cot] = trim($v);
            }
        }

        return $duLieu;
    }

    /**
     * O SO de trong trong Excel cho ra CHUOI RONG, khong phai null.
     *
     * MySQL o che do STRICT_TRANS_TABLES tu choi thang: "Incorrect decimal value: ''".
     * Dong do bi bo va nguoi dung chi thay mot con so loi. Quy ve null de cot nullable
     * nhan duoc.
     *
     * Ham THUAN de kiem duoc.
     *
     * @param array $duLieu
     * @param array $cotSo ten cac cot kieu so
     */
    public static function chuanHoaSo(array $duLieu, array $cotSo)
    {
        foreach ($cotSo as $c) {
            if (array_key_exists($c, $duLieu) && trim((string) $duLieu[$c]) === '') {
                $duLieu[$c] = null;
            }
        }

        return $duLieu;
    }

    /**
     * Ten cac cot kieu so cua mot bang, doc tu chinh CSDL.
     *
     * Doc tu schema thay vi liet ke tay: bang danh muc co nhieu cot so ngoai gia
     * (so_luong, dinh_muc, tyle_tt_bh, loai_thau, ht_thau...) va tat ca deu dinh cung mot
     * loi khi o Excel de trong.
     */
    protected function cotSoCua($bang)
    {
        $this->doCot($bang);

        return $this->cotSo[$bang];
    }

    /** Ten TAT CA cot cua mot bang, de loai truong khong phai cot that truoc khi ghi */
    protected function cotCua($bang)
    {
        $this->doCot($bang);

        return $this->cot[$bang];
    }

    /** Mot lan SHOW COLUMNS cho ca hai danh sach; nho de khong hoi lai moi lo */
    private function doCot($bang)
    {
        if (isset($this->cot[$bang])) {
            return;
        }

        $tatCa = [];
        $so = [];

        foreach (DB::select('SHOW COLUMNS FROM ' . $bang) as $c) {
            $tatCa[] = $c->Field;

            if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double)/i', $c->Type)) {
                $so[] = $c->Field;
            }
        }

        $this->cot[$bang] = $tatCa;
        $this->cotSo[$bang] = $so;
    }

    /**
     * Lấy giá trị từ row dựa trên field mapping
     *
     * @param array|\Illuminate\Support\Collection $row
     * @param string $field
     * @param array $fieldMapping
     * @return mixed|null
     */
    private function getRowValue($row, string $field, array $fieldMapping)
    {
        if (!isset($fieldMapping[$field])) {
            return null;
        }

        $index = $fieldMapping[$field];
        
        // Convert collection to array if needed
        if ($row instanceof \Illuminate\Support\Collection) {
            $row = $row->toArray();
        }
        
        return $row[$index] ?? null;
    }

    /**
     * Kiểm tra các trường bắt buộc có giá trị không
     *
     * @param array|\Illuminate\Support\Collection $row
     * @param array $requiredFields
     * @param array $fieldMapping
     * @return bool
     */
    private function hasRequiredFields($row, array $requiredFields, array $fieldMapping): bool
    {
        foreach ($requiredFields as $field) {
            $value = $this->getRowValue($row, $field, $fieldMapping);
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }

}