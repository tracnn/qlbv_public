<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\Xml3176DashboardService;
use Illuminate\Http\Request;

class Xml3176DashboardController extends Controller
{
    protected $service;

    public function __construct(Xml3176DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('dashboard.xml3176');
    }

    /**
     * GET /dashboard/xml3176/overview
     */
    public function overview(Request $request)
    {
        $this->validateFilters($request);

        return response()->json(
            $this->service->getOverview(
                $request->input('date_type'),
                $request->input('date_from'),
                $request->input('date_to')
            )
        );
    }

    /**
     * GET /dashboard/xml3176/top-errors
     */
    public function topErrors(Request $request)
    {
        $this->validateFilters($request);

        return response()->json([
            'data' => $this->service->getTopErrors(
                $request->input('date_type'),
                $request->input('date_from'),
                $request->input('date_to')
            ),
        ]);
    }

    /**
     * GET /dashboard/xml3176/aging
     *
     * KHÔNG validate bộ lọc kỳ vì khối tồn đọng không dùng tới (quyết định D11):
     * nó luôn tính theo ngay_ra so với hôm nay.
     */
    public function aging()
    {
        return response()->json([
            'data' => $this->service->getAging(),
        ]);
    }

    /**
     * GET /dashboard/xml3176/by-department
     */
    public function byDepartment(Request $request)
    {
        $this->validateFilters($request);

        return response()->json([
            'data' => $this->service->getByDepartment(
                $request->input('date_type'),
                $request->input('date_from'),
                $request->input('date_to')
            ),
        ]);
    }

    /**
     * Bộ lọc dùng chung. date_type dùng đúng 4 giá trị của màn hình danh sách
     * XML3176 để drill-down khớp bộ lọc.
     */
    private function validateFilters(Request $request)
    {
        $request->validate([
            'date_type' => 'required|in:date_in,date_out,date_payment,date_create',
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);
    }
}
