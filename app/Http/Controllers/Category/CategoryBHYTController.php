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
}
