<?php

namespace App\Http\Controllers\Category;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;

use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Models\BHYT\XmlErrorCatalog;
use App\Models\BHYT\Qd130XmlErrorCatalog;
use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Services\CatalogImportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CatalogTemplateExport;

class CategoryBHYTController extends Controller
{
    protected $importService;
    
    public function __construct(CatalogImportService $importService)
    {
        $this->importService = $importService;
    }

    public function indexMedicineCatalog()
    {
        return view('category.bhyt.medicine_catalog');
    }

    public function fetchMedicineCatalog()
    {
        $result = MedicineCatalog::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexMedicalSupplyCatalog()
    {
        return view('category.bhyt.medical_supply_catalog');
    }

    public function fetchMedicalSupplyCatalog()
    {
        $result = MedicalSupplyCatalog::query();
        
        return Datatables::of($result)
        ->make(true);
    }

    public function indexServiceCatalog()
    {
        return view('category.bhyt.service_catalog');
    }

    public function fetchServiceCatalog()
    {
        $result = ServiceCatalog::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexIcd10Catalog()
    {
        return view('category.bhyt.icd10_catalog');
    }

    public function fetchIcd10Catalog()
    {
        $result = Icd10Category::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexIcdYhctCatalog()
    {
        return view('category.bhyt.icd_yhct_catalog');
    }

    public function fetchIcdYhctCatalog()
    {
        $result = IcdYhctCategory::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexMedicalStaff()
    {
        return view('category.bhyt.medical_staff');
    }

    public function fetchMedicalStaff()
    {
        $result = MedicalStaff::query();
        
        return Datatables::of($result)
        ->make(true);
    }

    public function indexDepartmentBedCatalog()
    {
        return view('category.bhyt.department_bed_catalog');
    }

    public function fetchDepartmentBedCatalog()
    {
        $result = DepartmentBedCatalog::query();
        
        return Datatables::of($result)
        ->make(true);
    }

    public function indexEquipmentCatalog()
    {
        return view('category.bhyt.equipment_catalog');
    }

    public function fetchEquipmentCatalog()
    {
        $result = EquipmentCatalog::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexAdministrativeUnit()
    {
        return view('category.bhyt.administrative_unit');
    }

    public function fetchAdministrativeUnit()
    {
        $result = \App\Models\BHYT\AdministrativeUnit::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexMedicalOrganization()
    {
        return view('category.bhyt.medical_organization');
    }

    public function fetchMedicalOrganization()
    {
        $result = \App\Models\BHYT\MedicalOrganization::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexJobCategory()
    {
        return view('category.bhyt.job_category');
    }

    public function fetchJobCategory()
    {
        $result = \App\Models\BHYT\JobCategory::query();

        return Datatables::of($result)
        ->make(true);
    }

    /**
     * Chi tiet mot ban ghi danh muc — CHI DOC, dung chung cho ca 11 bo.
     *
     * Man danh sach chi hien duoc vai cot (medicine_catalogs co 26 cot ma danh sach chi
     * hien 11), nen day moi la cho xem duoc day du.
     */
    public function chiTietDanhMuc($loai, $id)
    {
        $so = config('danh_muc_bhyt.' . $loai);

        if (!$so) {
            return response()->json(['message' => 'Loại danh mục không hợp lệ'], 404);
        }

        $model = $so['model'];
        $ban = $model::find($id);

        if (!$ban) {
            return response()->json(['message' => 'Không tìm thấy bản ghi'], 404);
        }

        $truong = [];

        foreach ($ban->toArray() as $cot => $giaTri) {
            $truong[] = [
                'nhan' => \App\Services\Category\NhanTruong::cua($loai, $cot),
                'gia_tri' => is_null($giaTri) ? '' : (string) $giaTri,
            ];
        }

        return response()->json(['ten' => $so['ten'], 'truong' => $truong]);
    }

    public function fetchXmlErrorCatalog()
    {
        $xmlErrorCatalogs = XmlErrorCatalog::orderBy('xml')->get();
        return response()->json($xmlErrorCatalogs);
    }
    public function fetchQd130XmlErrorCatalog()
    {
        $qd130XmlErrorCatalogs = Qd130XmlErrorCatalog::orderBy('xml')->get();
        return response()->json($qd130XmlErrorCatalogs);
    }

    public function indexQd130XmlErrorCatalog()
    {
        return view('category.bhyt.qd130_xml_error_catalog');
    }

    public function fetchQd130XmlErrorCatalogDatatable()
    {
        $result = Qd130XmlErrorCatalog::query();

        return Datatables::of($result)
        ->editColumn('critical_error', function ($row) {
            return '<input type="checkbox" ' . ($row->critical_error ? 'checked' : '') . ' disabled>';
        })
        ->editColumn('critical_error', function ($row) {
            return '<input type="checkbox" class="critical-error-toggle" data-id="' . $row->id . '" ' . ($row->critical_error ? 'checked' : '') . '>';
        })
        ->editColumn('is_check', function ($row) {
            return '<input type="checkbox" class="is-check-toggle" data-id="' . $row->id . '" ' . ($row->is_check ? 'checked' : '') . '>';
        })
        ->rawColumns(['critical_error', 'is_check']) // Đảm bảo các cột này được render HTML
        ->toJson();
    }

    public function updateQd130XmlErrorCatalog(Request $request)
    {
        $id = $request->input('id');

        // Tìm và cập nhật giá trị is_not_check (chuyển đổi giữa is_check và is_not_check)
        $catalog = Qd130XmlErrorCatalog::find($id);

        if ($catalog) {
            if ($request->has('is_check')) {
                $catalog->is_check = $request->is_check;
            }
            if ($request->has('critical_error')) {
                $catalog->critical_error = $request->critical_error;
            }
            $catalog->save();
            
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function fetchXml3176ErrorCatalog()
    {
        $xml3176ErrorCatalogs = Xml3176ErrorCatalog::orderBy('xml')->get();
        return response()->json($xml3176ErrorCatalogs);
    }

    public function indexXml3176ErrorCatalog()
    {
        return view('category.bhyt.xml3176_error_catalog');
    }

    public function fetchXml3176ErrorCatalogDatatable()
    {
        $result = Xml3176ErrorCatalog::query();

        return Datatables::of($result)
        ->editColumn('critical_error', function ($row) {
            return '<input type="checkbox" ' . ($row->critical_error ? 'checked' : '') . ' disabled>';
        })
        ->editColumn('critical_error', function ($row) {
            return '<input type="checkbox" class="critical-error-toggle" data-id="' . $row->id . '" ' . ($row->critical_error ? 'checked' : '') . '>';
        })
        ->editColumn('is_check', function ($row) {
            return '<input type="checkbox" class="is-check-toggle" data-id="' . $row->id . '" ' . ($row->is_check ? 'checked' : '') . '>';
        })
        ->rawColumns(['critical_error', 'is_check'])
        ->toJson();
    }

    public function updateXml3176ErrorCatalog(Request $request)
    {
        $id = $request->input('id');

        $catalog = Xml3176ErrorCatalog::find($id);

        if ($catalog) {
            if ($request->has('is_check')) {
                $catalog->is_check = $request->is_check;
            }
            if ($request->has('critical_error')) {
                $catalog->critical_error = $request->critical_error;
            }
            $catalog->save();
            
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function importIndex()
    {
        return view('category.bhyt.import', [
            'danhSachCoSo' => \App\Services\BHYT\DanhSachCoSo::danhSach(),
        ]);
    }

    public function downloadTemplate(Request $request)
    {
        $type = $request->get('type');
        $validTypes = array_keys(config('catalog_import_mapping', []));

        if (!in_array($type, $validTypes, true)) {
            abort(404, 'Loại danh mục không hợp lệ');
        }

        return Excel::download(new CatalogTemplateExport($type), $type . '_bieu_mau.xlsx');
    }

    /**
     * Xuat danh sach dong hong cua mot lan nhap ra .xlsx.
     *
     * Nhan lai chinh du lieu da tra ve trong JSON cua import() thay vi luu tren may chu:
     * khong phai them bang, khong phai don tep cu.
     */
    public function taiLoiNhap(Request $request)
    {
        $ds = json_decode($request->input('dong_loi'), true);

        if (!is_array($ds) || empty($ds)) {
            abort(404, 'Không có dòng lỗi nào để xuất');
        }

        $tenTep = (string) $request->input('ten_tep', 'danh-muc');
        $goc = pathinfo($tenTep, PATHINFO_FILENAME);
        $ten = 'loi-nhap-' . preg_replace('/[^\p{L}\p{N}._-]+/u', '-', $goc);

        return Excel::download(new \App\Exports\LoiNhapDanhMucExport($ds, $tenTep), $ten . '.xlsx');
    }

    public function import(Request $request)
    {
        // Ma co so chon tren man nhap; chi ap cho ba danh muc theo co so, va chi cho dong
        // khong tu khai MA_CSKCB trong tep.
        $maCskcb = trim((string) $request->input('ma_cskcb'));

        if ($maCskcb !== '' && !array_key_exists($maCskcb, \App\Services\BHYT\DanhSachCoSo::danhSach())) {
            return response()->json(['message' => 'Cơ sở khám chữa bệnh không hợp lệ'], 422);
        }

        if ($request->hasFile('import_file')) {
            $files = $request->file('import_file'); // Nhận tất cả các file được gửi lên

            // Nếu $files không phải là mảng, chuyển thành mảng
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                // Kiểm tra và xử lý từng file
                $extension = $file->getClientOriginalExtension();
                
                if (!in_array($extension, ['xls', 'xlsx'])) {
                    return response()->json(['message' => 'Định dạng file không hợp lệ. Vui lòng chọn file Excel (.xls hoặc .xlsx)'], 422);
                }

                try {
                    // Xử lý import file tại đây
                    $ketQua = $this->importService->import($file, $maCskcb);
                } catch (\Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
            }

            // Truoc day luon tra 'thanh cong' du co the nhap 0 dong: nguoi dung khong co
            // cach nao biet bao nhieu dong vao, bao nhieu bi bo.
            $tomTat = $ketQua->tomTat();

            if (!$ketQua->coGhi()) {
                $tomTat = 'Không ghi được dòng nào. ' . $tomTat;
            }

            return response()->json([
                'message' => $tomTat,
                'ket_qua' => $ketQua->toArray(),
            ], 200);
        }

        return response()->json(['message' => 'Chưa chọn file để import'], 422);
    }

}
