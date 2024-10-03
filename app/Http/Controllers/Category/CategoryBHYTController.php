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

class CategoryBHYTController extends Controller
{
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
        ->editColumn('is_check', function ($row) {
            return '<input type="checkbox" class="is-check-toggle" data-id="' . $row->id . '" ' . ($row->is_check ? 'checked' : '') . '>';
        })
        ->rawColumns(['critical_error', 'is_check']) // Đảm bảo các cột này được render HTML
        ->toJson();
    }

    public function updateQd130XmlErrorCatalog(Request $request)
    {
        $id = $request->input('id');
        $isCheck = $request->input('is_check');

        // Tìm và cập nhật giá trị is_not_check (chuyển đổi giữa is_check và is_not_check)
        $catalog = Qd130XmlErrorCatalog::find($id);

        if ($catalog) {
            // Cập nhật is_not_check, giá trị ngược với is_check
            $catalog->is_check = $isCheck;
            $catalog->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }
}
