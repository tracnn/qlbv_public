<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DataTables;
use DB;

class NurseController extends Controller
{
    public function executeMedicationOrderIndex()
    {
        return view('nurse.execute-medication-order-index');
    }

    public function fetchDataNurseExecute(Request $request)
    {
        
    }
}
