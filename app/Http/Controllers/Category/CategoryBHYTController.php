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
use App\Services\CatalogImportService;

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
        return view('category.bhyt.import');
    }

    public function import(Request $request)
    {
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
                    $this->importService->import($file);
                } catch (\Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
            }

            return response()->json(['message' => 'File đã upload và xử lý thành công!'], 200);
        }

        return response()->json(['message' => 'Chưa chọn file để import'], 422);
    }

}
