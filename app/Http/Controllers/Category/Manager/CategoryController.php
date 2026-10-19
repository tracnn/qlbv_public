<?php

namespace App\Http\Controllers\Category\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\System\City;
use App\Models\System\Clinic;
use App\Models\System\District;
use App\Models\System\Symptom;
use App\Models\System\Ward;


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

}
