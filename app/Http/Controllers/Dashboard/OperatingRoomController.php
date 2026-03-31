<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OperatingRoomService;
use Illuminate\Http\Request;

class OperatingRoomController extends Controller
{
    protected $orService;

    public function __construct(OperatingRoomService $orService)
    {
        $this->orService = $orService;
    }

    public function index()
    {
        return view('dashboard.operating-room');
    }

    /**
     * GET /dashboard/operating-room/cases-per-room
     */
    public function casesPerRoom(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->orService->getCasesPerRoom(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/operating-room/utilization
     */
    public function utilization(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->orService->getUtilization(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json(['data' => $data]);
    }
}
