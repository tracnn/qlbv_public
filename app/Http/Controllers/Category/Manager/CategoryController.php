<?php

namespace App\Http\Controllers\Category\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\System\City;
use App\Models\System\Clinic;
use App\Models\System\District;
use App\Models\System\Symptom;
use App\Models\System\Ward;

use App\Models\BHYT\cat_cond_service;
use App\Models\BHYT\department;
use App\Models\BHYT\cat_cond_pharma;

class CategoryController extends Controller
{
    protected $searchParams;

    public function __construct()
    {
        $this->searchParams = [
            'code' => null,
            'name' => null,
        ];
    }

	/* Index */
    public function index(Request $request, $category)
    {
    	
    	$params = $this->searchParams;

    	$model = $this->getCategoryModel($category);

    	if($model) {
    		$model = $model->getSearchResults($params)->paginate(config('__tech.pagination_count'));
    	}

        $model->setPath($category.'/search');
    	
    	return view('category.manager.index', compact('model','params'));
    }

    public function search(Request $request, $category)
    {
        $params = $this->__getSearchParam($request);
        
        $model = $this->getCategoryModel($category);

        if($model) {
            $model = $model->getSearchResults($params)->paginate(config('__tech.pagination_count'));
        }

        return view('category.manager.index', compact('model','params'));
    }

    private function __getSearchParam($request)
    {
        return [
            'code' => $request->get('code'),
            'name' => $request->get('name'),
        ];
    }

    private function getCategoryModel($category)
    {
    	$model = null;
    	switch ($category) {
    		case 'city':
    			$model = new City;
    			break;
    		case 'district':
    			$model = new District;
    			break;
    		case 'ward':
    			$model = new Ward;
    			break;
    		case 'symptom':
    			$model = new Symptom;
    			break;
    		case 'clinic':
    			$model = new Clinic;
    			break;    			
    		default:
    			# code...
    			break;
    	}

    	return $model;

    }

    public function dvktCoDieuKien() {
        $cond_service = cat_cond_service::all();
        return view('category.manager.dvkt-co-dieu-kien.index',
            compact('cond_service'));
    }

    public function dmtCoDieuKien() {
        $models = cat_cond_pharma::all();
        return view('category.manager.dm-thuoc-co-dieu-kien.index',
            compact('models'));
    }

    public function dmKhoaphong() {
        $models = department::all();
        return view('category.manager.dm-khoa-phong.index', 
            compact('models'));
    }

    public function updateDvktCoDieuKien(Request $request) {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        $update_model = cat_cond_service::find($request->id);
        $update_model->cond_val = $request->value;
        $update_model->save();
    }

    public function updateDmtCoDieuKien(Request $request) {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        $update_model = cat_cond_pharma::find($request->id);
        $update_model->pharma_val = $request->value;
        $update_model->save();
    }
}
