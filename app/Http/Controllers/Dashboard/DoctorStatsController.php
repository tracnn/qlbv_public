<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DoctorService;
use Illuminate\Http\Request;

class DoctorStatsController extends Controller
{
    protected $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    /**
     * GET /dashboard/doctor-stats
     * Trang thống kê theo bác sĩ
     */
    public function index()
    {
        return view('dashboard.doctor-stats');
    }

    /**
     * GET /dashboard/doctor-stats/examinations
     * Số lượt khám theo bác sĩ
     */
    public function examinations(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getExaminations(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /dashboard/doctor-stats/revenue
     * Doanh thu theo bác sĩ
     */
    public function revenue(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getRevenue(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /dashboard/doctor-stats/surgeries
     * Ca phẫu thuật theo PTV chính
     */
    public function surgeries(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getSurgeries(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json(['data' => $data]);
    }
}
