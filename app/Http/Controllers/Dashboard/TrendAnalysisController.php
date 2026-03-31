<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\TrendService;
use Illuminate\Http\Request;

class TrendAnalysisController extends Controller
{
    protected $trendService;

    public function __construct(TrendService $trendService)
    {
        $this->trendService = $trendService;
    }

    public function index()
    {
        return view('dashboard.trend-analysis');
    }

    /**
     * GET /dashboard/trends/chart
     */
    public function trendChart(Request $request)
    {
        $request->validate([
            'from'   => 'required|date',
            'to'     => 'required|date|after_or_equal:from',
            'mode'   => 'required|in:daily,monthly',
            'metric' => 'required|in:examinations,revenue',
        ]);

        $data = $this->trendService->getTrendChart(
            $request->input('from'),
            $request->input('to'),
            $request->input('mode'),
            $request->input('metric')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/trends/patients-per-hour
     */
    public function patientsPerHour(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->trendService->getPatientsPerHour(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/trends/overload-alert
     */
    public function overloadAlert(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $data = $this->trendService->getOverloadAlert($request->input('date'));

        return response()->json($data);
    }
}
