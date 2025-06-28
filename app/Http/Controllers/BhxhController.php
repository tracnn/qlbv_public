<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\BhxhEmrPermission;

class BhxhController extends Controller
{
    public function index()
    {
        return view('bhxh.index');
    }

    public function listEmrChecker(Request $request)
    {
        $data = BhxhEmrPermission::where('allow_view_at', '>=', now())->get();
        dd($data);

        return response()->json([
            'data' => 'test'
        ]);
    }
}
