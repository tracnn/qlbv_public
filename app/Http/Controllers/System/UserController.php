<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    protected $searchParams;

    public function __construct()
    {
    	$this->middleware(['auth','role:superadministrator']);

        $this->searchParams = [
            'date' => [
                'from' => date_format(now(),'Y-m-d'),
                'to' => date_format(now(),'Y-m-d')
            ],
        ];      
    }
}
