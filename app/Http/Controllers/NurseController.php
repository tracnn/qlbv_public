<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NurseController extends Controller
{
    public function executeMedicationOrderIndex()
    {
        return view('nurse.execute-medication-order-index');
    }
}
