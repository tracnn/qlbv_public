<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    /**
     * Helper method để convert date format
     * Bắt buộc format: YYYYMMDDHHmmss
     */
    private function currentDate($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            // Validate format YYYYMMDDHHmmss
            if (!preg_match('/^\d{14}$/', $startDate)) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $endDate)) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            $from_date = $startDate;
            $to_date = $endDate;
        } else {
            $now = Carbon::now();
            $from_date = $now->copy()->startOfDay()->format('YmdHis');
            $to_date = $now->copy()->endOfDay()->format('YmdHis');
        }
    
        return [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
    }

    /**
     * Helper method để convert date format (từ DashboardController)
     * Bắt buộc format: YYYYMMDDHHmmss
     */
    private function convertDate($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            // Validate format YYYYMMDDHHmmss
            if (!preg_match('/^\d{14}$/', $startDate)) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $endDate)) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            $from_date = $startDate;
            $to_date = $endDate;
        } else {
            $now = Carbon::now();
            $from_date = $now->copy()->startOfDay()->format('YmdHis');
            $to_date = $now->copy()->endOfDay()->format('YmdHis');
        }
    
        return [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
    }

    /**
     * Format API response
     */
    private function apiResponse($data, $success = true, $error = null, $statusCode = 200)
    {
        $response = [
            'success' => $success,
            'meta' => [
                'timestamp' => \Carbon\Carbon::now()->format('YmdHis'),
                'request_id' => uniqid('req_')
            ]
        ];

        if ($success) {
            $response['data'] = $data;
        } else {
            $response['error'] = $error;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * GET /api/dashboard/treatment-stats
     * Logic từ HomeController@fetchTreatment
     */
    public function getTreatmentStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->inTreatment($current_date['from_date'], $current_date['to_date']);

            $sum_sl = $model->sum('so_luong');

            $result = [
                'summary' => [
                    'total_treatments' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'patient_type_name' => $item->patient_type_name,
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê điều trị',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/dashboard/patient-stats
     * Logic từ HomeController@fetchNewpatient
     */
    public function getPatientStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->newpatient($current_date['from_date'], $current_date['to_date']);

            $sum_sl = $model->sum('so_luong');

            $result = [
                'summary' => [
                    'total_new_patients' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'branch_name' => $item->branch_name,
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê bệnh nhân',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/dashboard/revenue-stats
     * Logic từ HomeController@fetchDoanhthu
     */
    public function getRevenueStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->doanhthu($current_date['from_date'], $current_date['to_date']);

            $sum_sl = $model->sum('thanh_tien');

            $result = [
                'summary' => [
                    'total_revenue' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'service_type_name' => $item->service_type_name,
                        'service_type_id' => $item->tdl_service_type_id,
                        'amount' => (int) $item->so_luong,
                        'revenue' => (float) $item->thanh_tien
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê doanh thu',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/dashboard/average-inpatient-days
     * Logic từ HomeController@fetchAverageDayInpatient
     */
    public function getAverageInpatientDays(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getDetailDayCountInpatient($current_date['from_date'], $current_date['to_date']);

            // Chuyển $data (Collection) sang mảng nếu cần
            $dataArray = json_decode(json_encode($data), true);

            // Tính số ngày trung bình (average day_count)
            $total = 0;
            $count = 0;
            foreach ($dataArray as $row) {
                if (isset($row['day_count'])) {
                    $total += $row['day_count'];
                    $count++;
                }
            }
            $average = $count > 0 ? round($total / $count, 2) : 0;

            $result = [
                'summary' => [
                    'average_days' => $average,
                    'total_patients' => $count,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $dataArray
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu số ngày nằm viện trung bình',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    // Private methods từ HomeController (sẽ được thêm trong phần tiếp theo)
    private function getDetailDayCountInpatient($from_date, $to_date)
    {
        $data = DB::connection("HISPro")
        ->table('his_treatment')
        ->select([
            DB::raw("
                CASE
                    WHEN ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) < 4 THEN 0
                    WHEN ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) >= 4 
                         AND ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) < 24 THEN 1
                    ELSE 
                        CASE
                            WHEN MOD((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24, 24) >= 4 
                                THEN FLOOR(TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) + 1
                            ELSE FLOOR(TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS'))
                        END
                END AS day_count
            "),
            'treatment_day_count',
            'in_time',
            'clinical_in_time',
            'out_time',
            'treatment_code'
        ])
        ->whereBetween('out_time', [$from_date, $to_date])
        ->where('tdl_treatment_type_id', 3)
        ->get();

        return $data;
    }

    private function newpatient($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->selectRaw('count(*) as so_luong,branch_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->whereBetween('his_patient.create_time', [$from_date, $to_date])
        ->where('his_patient.is_delete',0)
        ->groupBy('branch_name')
        ->get();
    }

    private function doanhthu($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_type', 'his_sere_serv.tdl_service_type_id', '=', 'his_service_type.id')
        ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_type_id,service_type_name')
        ->whereBetween('tdl_intruction_time', [$from_date, $to_date])
        ->where('his_sere_serv.is_delete', 0)
        ->groupBy('tdl_service_type_id','service_type_name')
        ->orderBy('thanh_tien','desc')
        ->get();
    }

    private function inTreatment($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
        ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
        ->selectRaw('count(*) as so_luong,patient_type_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->where('his_treatment.is_delete',0)
        ->groupBy('patient_type_name')
        ->get();
    }

    /**
     * GET /api/dashboard/transaction-stats
     * Logic từ HomeController@fetchTransaction
     */
    public function getTransactionStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getTransactionDetail($current_date['from_date'], $current_date['to_date']);

            // Khởi tạo mảng để tổng hợp dữ liệu
            $chartData = [
                'cashiers' => [],
                'transactionTypes' => [],
                'payForms' => [],
                'departments' => [],
                'treatmentTypes' => [],
            ];

            // Phân loại và tổng hợp dữ liệu theo từng tiêu chí
            foreach ($data as $item) {
                // Tổng hợp theo cashier_username
                if (isset($chartData['cashiers'][$item->cashier_username])) {
                    $chartData['cashiers'][$item->cashier_username] += $item->amount;
                } else {
                    $chartData['cashiers'][$item->cashier_username] = $item->amount;
                }

                // Tổng hợp theo transaction_type_name
                if (isset($chartData['transactionTypes'][$item->transaction_type_name])) {
                    $chartData['transactionTypes'][$item->transaction_type_name] += $item->amount;
                } else {
                    $chartData['transactionTypes'][$item->transaction_type_name] = $item->amount;
                }

                // Tổng hợp theo pay_form_name
                if (isset($chartData['payForms'][$item->pay_form_name])) {
                    $chartData['payForms'][$item->pay_form_name] += $item->amount;
                } else {
                    $chartData['payForms'][$item->pay_form_name] = $item->amount;
                }

                // Tổng hợp theo department_name
                if (isset($chartData['departments'][$item->department_name])) {
                    $chartData['departments'][$item->department_name] += $item->amount;
                } else {
                    $chartData['departments'][$item->department_name] = $item->amount;
                }

                // Tổng hợp theo treatment_type_name
                if (isset($chartData['treatmentTypes'][$item->treatment_type_name])) {
                    $chartData['treatmentTypes'][$item->treatment_type_name] += $item->amount;
                } else {
                    $chartData['treatmentTypes'][$item->treatment_type_name] = $item->amount;
                }
            }

            // Định dạng dữ liệu cho Highcharts.js
            $formattedData = [
                'cashiers' => [],
                'transactionTypes' => [],
                'payForms' => [],
                'departments' => [],
                'treatmentTypes' => [],
            ];

            // Định dạng lại dữ liệu cho Highcharts
            foreach ($chartData['cashiers'] as $name => $total) {
                $formattedData['cashiers'][] = [
                    'name' => $name,
                    'y' => (float) $total
                ];
            }

            foreach ($chartData['transactionTypes'] as $name => $total) {
                $formattedData['transactionTypes'][] = [
                    'name' => $name,
                    'y' => (float) $total
                ];
            }

            foreach ($chartData['payForms'] as $name => $total) {
                $formattedData['payForms'][] = [
                    'name' => $name,
                    'y' => (float) $total
                ];
            }

            foreach ($chartData['departments'] as $name => $total) {
                $formattedData['departments'][] = [
                    'name' => $name,
                    'y' => (float) $total
                ];
            }

            foreach ($chartData['treatmentTypes'] as $name => $total) {
                $formattedData['treatmentTypes'][] = [
                    'name' => $name,
                    'y' => (float) $total
                ];
            }

            $result = [
                'summary' => [
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => [
                    'cashiers' => $formattedData['cashiers'],
                    'transaction_types' => $formattedData['transactionTypes'],
                    'pay_forms' => $formattedData['payForms'],
                    'departments' => $formattedData['departments'],
                    'treatment_types' => $formattedData['treatmentTypes']
                ]
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê giao dịch',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/dashboard/inpatient-stats
     * Logic từ HomeController@fetchNoitru
     */
    public function getInpatientStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->getTreatmentByTreatmentType(
                $current_date['from_date'], 
                $current_date['to_date'],
                [3,4]
            );

            $sum_sl = $model->sum('so_luong');

            $result = [
                'summary' => [
                    'total_inpatient_treatments' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'department_name' => $item->department_name,
                        'department_id' => $item->last_department_id,
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê nội trú',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/dashboard/outpatient-stats
     * Logic từ HomeController@fetchDieutriNgoaitru
     */
    public function getOutpatientStats(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->getTreatmentByTreatmentType(
                $current_date['from_date'], 
                $current_date['to_date'],
                [2]
            );

            $sum_sl = $model->sum('so_luong');

            $result = [
                'summary' => [
                    'total_outpatient_treatments' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'department_name' => $item->department_name,
                        'department_id' => $item->last_department_id,
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê ngoại trú',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    // Private methods từ HomeController
    private function getTransactionDetail($from_date, $to_date)
    {
        return DB::connection("HISPro")
        ->table('his_transaction')
        ->join('his_transaction_type', 'his_transaction_type.id', '=', 'his_transaction.transaction_type_id')
        ->join('his_pay_form', 'his_pay_form.id', '=', 'his_transaction.pay_form_id')
        ->join('his_treatment', 'his_treatment.id', '=', 'his_transaction.treatment_id')
        ->join('his_treatment_type', 'his_treatment_type.id', '=', 'his_transaction.treatment_type_id')
        ->join('his_department', 'his_department.id', 'his_treatment.last_department_id')
        ->select('his_transaction.tdl_treatment_code',
            'his_transaction.tdl_patient_code',
            'his_transaction.tdl_patient_name',
            'his_transaction.tdl_patient_dob',
            'his_transaction.transaction_code',
            'his_transaction.transaction_time',
            'his_transaction.tdl_patient_address',
            'transaction_time',
            'cashier_username',
            'amount',
            'transaction_type_name',
            'pay_form_name',
            'department_name',
            'treatment_type_name')
        ->whereBetween('his_transaction.transaction_time', [$from_date, $to_date])
        ->where('his_transaction.is_delete', 0)
        ->whereNull('his_transaction.is_cancel')
        ->get();
    }

    private function getTreatmentByTreatmentType($from_date, $to_date, $treatmentTypes)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
        ->selectRaw('count(*) as so_luong,last_department_id,department_name')
        ->whereBetween('in_time', [$from_date, $to_date])
        ->whereIn('tdl_treatment_type_id', $treatmentTypes)
        ->where('his_treatment.is_delete',0)
        ->groupBy('last_department_id','department_name')
        ->orderBy('so_luong','desc')
        ->get();
    }

    /**
     * GET /api/treatments
     * Logic từ DashboardController@fetchTreatmentDetail
     */
    public function getTreatments(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/',
                'dataType' => 'required|string',
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->convertDate($request->input('startDate'), $request->input('endDate'));
            $dataType = $request->input('dataType');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);

            $query = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->join('his_patient_type', 'his_patient_type.id', '=', 'his_treatment.tdl_patient_type_id')
            ->select('his_treatment.treatment_code',
                'his_treatment.tdl_patient_code',
                'his_treatment.tdl_patient_name',
                'his_treatment.in_time',
                'his_treatment.out_time',
                'his_treatment.icd_code',
                'his_treatment.icd_name'
            )
            ->where('his_treatment.is_delete',0);

            switch ($dataType) {
                case 'treatment':
                    $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']]);
                    break;
                case 'newpatient':
                    $query->whereBetween('his_patient.create_time', [$current_date['from_date'], $current_date['to_date']])
                    ->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('his_patient.is_delete',0);
                    break;
                case 'noitru':
                    $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'));
                    break;
                case 'ravien-kham':
                    $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'));
                    break;
                case 'ravien':
                    $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']]);
                    break;
                case 'chuyenvien':
                    $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'));
                    break;
                case 'ravien-noitru':
                    $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'));
                    break;
                case 'ravien-ngoaitru':
                    $query->whereBetween('out_time', [$current_date['from_date'], $current_date['to_date']])
                    ->where('tdl_treatment_type_id', config('__tech.treatment_type_ngoaitru'));
                    break;
                default:
                    $query->whereBetween('in_time', [$current_date['from_date'], $current_date['to_date']]);
                    break;
            }

            $total = $query->count();
            $data = $query->offset(($page - 1) * $limit)
                         ->limit($limit)
                         ->get();

            $result = [
                'data' => $data,
                'pagination' => [
                    'current_page' => intval($page),
                    'per_page' => intval($limit),
                    'total' => intval($total),
                    'last_page' => intval(ceil($total / $limit))
                ]
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu điều trị',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/services
     * Logic từ DashboardController@fetchServiceDetail
     */
    public function getServices(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/',
                'dataType' => 'string',
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->convertDate($request->input('startDate'), $request->input('endDate'));
            $dataType = $request->input('dataType');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);

            $query = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id')
            ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
            ->select('his_service_req.tdl_treatment_code',
                'his_service_req.tdl_patient_code',
                'his_service_req.tdl_patient_name',
                'his_treatment.in_time',
                'his_treatment.out_time',
                'his_sere_serv.tdl_service_name',
                'his_service_req.intruction_time',
                'his_service_req.request_username',
            )
            ->whereBetween('intruction_time', [$current_date['from_date'], $current_date['to_date']])
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0);

            switch ($dataType) {
                case 'phauthuat':
                    $query->where('his_service_req.service_req_type_id', config('__tech.service_req_type_phauthuat'));
                    break;
                case 'thuthuat':
                    $query->where('his_service_req.service_req_type_id', config('__tech.service_req_type_thuthuat'));
                    break;
                default:
                    break;
            }

            $total = $query->count();
            $data = $query->offset(($page - 1) * $limit)
                         ->limit($limit)
                         ->get();

            $result = [
                'data' => $data,
                'pagination' => [
                    'current_page' => intval($page),
                    'per_page' => intval($limit),
                    'total' => intval($total),
                    'last_page' => intval(ceil($total / $limit))
                ]
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu dịch vụ',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/services/by-type/{id}
     * Logic từ HomeController@fetchServiceByType
     */
    public function getServicesByType(Request $request, $id)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $model = $this->serviceByType(
                $current_date['from_date'], 
                $current_date['to_date'],
                $id);

            $sum_sl = $model->sum('so_luong');

            // Nhóm dữ liệu theo `service_req_stt_id`
            $statusData = [
                1 => ['name' => 'Chưa thực hiện', 'y' => 0],
                2 => ['name' => 'Đang thực hiện', 'y' => 0],
                3 => ['name' => 'Đã thực hiện', 'y' => 0]
            ];

            foreach ($model as $item) {
                if (isset($statusData[$item->service_req_stt_id])) {
                    $statusData[$item->service_req_stt_id]['y'] += $item->so_luong;
                }
            }

            $result = [
                'summary' => [
                    'total_services' => $sum_sl,
                    'service_type_id' => $id,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $model->map(function ($item) {
                    return [
                        'status_id' => $item->service_req_stt_id,
                        'status_name' => $this->getServiceStatusName($item->service_req_stt_id),
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu dịch vụ theo loại',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/examinations/paraclinical
     * Logic từ HomeController@fetchExamAndParraclinical
     */
    public function getExamParaclinical(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getExamAndParraclinical($current_date['from_date'], $current_date['to_date']);

            $result = [
                'summary' => [
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $data->map(function ($item) {
                    return [
                        'branch_name' => $item->branch_name,
                        'service_req_type_name' => $item->service_req_type_name,
                        'instruction_time' => $item->intruction_time,
                        'start_time' => $item->start_time,
                        'finish_time' => $item->finish_time
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu khám và cận lâm sàng',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/examinations/imaging
     * Logic từ HomeController@fetchDiagnoticImaging
     */
    public function getDiagnosticImaging(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getDiagnoticImaging($current_date['from_date'], $current_date['to_date']);

            // Gom nhóm theo branch + loại dịch vụ
            $grouped = $data->groupBy(function ($item) {
                return $item->branch_name . '|' . $item->diim_type_name;
            });

            $stats = [];

            foreach ($grouped as $key => $items) {
                [$branchName, $diimTypeName] = explode('|', $key);
                $totalWait = 0;
                $totalExec = 0;
                $count = count($items);

                foreach ($items as $item) {
                    // Bỏ qua nếu thiếu dữ liệu
                    if (!$item->start_time || !$item->intruction_time || !$item->finish_time) {
                        continue;
                    }

                    // Parse các mốc thời gian
                    try {
                        $start = \Carbon\Carbon::createFromFormat('YmdHis', $item->start_time);
                        $instr = \Carbon\Carbon::createFromFormat('YmdHis', $item->intruction_time);
                        $finish = \Carbon\Carbon::createFromFormat('YmdHis', $item->finish_time);
                    } catch (\Exception $e) {
                        continue; // Bỏ qua nếu format sai
                    }

                    // Kiểm tra logic thời gian
                    if ($start->lessThan($instr) || $start->greaterThan($finish)) {
                        continue; // Bỏ qua nếu thời gian không hợp lý
                    }

                    $totalWait += $instr->diffInSeconds($start);
                    $totalExec += $start->diffInSeconds($finish);
                }

                $stats[] = [
                    'branch' => $branchName,
                    'type' => $diimTypeName,
                    'wait' => round($totalWait / $count / 60),
                    'exec' => round($totalExec / $count / 60),
                    'count' => $count
                ];
            }

            $stats = collect($stats);

            // Tập hợp danh sách loại dịch vụ có tổng số lượt
            $diimTypeSummary = $stats
                ->groupBy('type')
                ->map(function ($items) {
                    return number_format($items->sum('count'));
                });

            // Biến `categories` thành dạng: "Tên dịch vụ (số lượt)"
            $diimTypes = $diimTypeSummary->keys()->map(function ($type) use ($diimTypeSummary) {
                return $type . ' (' . $diimTypeSummary[$type] . ')';
            })->values();

            // Gộp series theo từng branch
            $series = [];

            foreach ($stats->groupBy('branch') as $branch => $items) {
                $waitSeries = [
                    'name' => $branch . ' - Thời gian chờ',
                    'data' => $diimTypeSummary->keys()->map(function ($type) use ($items) {
                        $item = $items->firstWhere('type', $type);
                        return $item ? $item['wait'] : 0;
                    })->toArray()
                ];

                $execSeries = [
                    'name' => $branch . ' - Thời gian thực hiện',
                    'data' => $diimTypeSummary->keys()->map(function ($type) use ($items) {
                        $item = $items->firstWhere('type', $type);
                        return $item ? $item['exec'] : 0;
                    })->toArray()
                ];

                $series[] = $waitSeries;
                $series[] = $execSeries;
            }

            $result = [
                'summary' => [
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $data->map(function ($item) {
                    return [
                        'branch_name' => $item->branch_name,
                        'diim_type_name' => $item->diim_type_name,
                        'instruction_time' => $item->intruction_time,
                        'start_time' => $item->start_time,
                        'finish_time' => $item->finish_time
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu chẩn đoán hình ảnh',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/examinations/prescription
     * Logic từ HomeController@fetchPrescription
     */
    public function getPrescription(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getPrescriptionData($current_date['from_date'], $current_date['to_date']);

            // Gom nhóm theo branch + loại dịch vụ
            $grouped = $data->groupBy(function ($item) {
                return $item->branch_name;
            });

            $stats = [];

            foreach ($grouped as $key => $items) {
                $branchName = $key;
                $totalWait = 0;
                $totalExec = 0;
                $count = count($items);

                foreach ($items as $item) {
                    // Bỏ qua nếu thiếu dữ liệu
                    if (!$item->start_time || !$item->intruction_time || !$item->finish_time) {
                        continue;
                    }

                    // Parse các mốc thời gian
                    try {
                        $start = \Carbon\Carbon::createFromFormat('YmdHis', $item->start_time);
                        $instr = \Carbon\Carbon::createFromFormat('YmdHis', $item->intruction_time);
                        $finish = \Carbon\Carbon::createFromFormat('YmdHis', $item->finish_time);
                    } catch (\Exception $e) {
                        continue; // Bỏ qua nếu format sai
                    }

                    // Kiểm tra logic thời gian
                    if ($start->lessThan($instr) || $start->greaterThan($finish)) {
                        continue; // Bỏ qua nếu thời gian không hợp lý
                    }

                    $totalWait += $instr->diffInSeconds($start);
                    $totalExec += $start->diffInSeconds($finish);
                }

                $stats[] = [
                    'branch' => $branchName,
                    'wait' => round($totalWait / $count / 60),
                    'exec' => round($totalExec / $count / 60),
                    'count' => $count
                ];
            }

            $stats = collect($stats);

            // Tập hợp danh sách loại dịch vụ có tổng số lượt
            $branchSummary = $stats
                ->groupBy('branch')
                ->map(function ($items) {
                    return number_format($items->sum('count'));
                });

            // Biến `categories` thành dạng: "Tên dịch vụ (số lượt)"
            $branches = $branchSummary->keys()->map(function ($branch) use ($branchSummary) {
                return $branch . ' (' . $branchSummary[$branch] . ')';
            })->values();

            // Gộp series theo từng branch
            $series = [];

            foreach ($stats->groupBy('branch') as $branch => $items) {
                $waitSeries = [
                    'name' => $branch . ' - Thời gian chờ',
                    'data' => $branchSummary->keys()->map(function ($branch) use ($items) {
                        $item = $items->firstWhere('branch', $branch);
                        return $item ? $item['wait'] : 0;
                    })->toArray()
                ];

                $execSeries = [
                    'name' => $branch . ' - Thời gian thực hiện',
                    'data' => $branchSummary->keys()->map(function ($branch) use ($items) {
                        $item = $items->firstWhere('branch', $branch);
                        return $item ? $item['exec'] : 0;
                    })->toArray()
                ];

                $series[] = $waitSeries;
                $series[] = $execSeries;
            }

            $result = [
                'summary' => [
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $data->map(function ($item) {
                    return [
                        'branch_name' => $item->branch_name,
                        'instruction_time' => $item->intruction_time,
                        'start_time' => $item->start_time,
                        'finish_time' => $item->finish_time
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu đơn thuốc',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/examinations/fee
     * Logic từ HomeController@fetchFee
     */
    public function getFee(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
            $data = $this->getFeeData($current_date['from_date'], $current_date['to_date']);

            // Gom nhóm theo branch + loại dịch vụ
            $grouped = $data->groupBy(function ($item) {
                return $item->branch_name;
            });

            $stats = [];

            foreach ($grouped as $key => $items) {
                $branchName = $key;
                $totalWait = 0;
                $totalExec = 0;
                $count = count($items);

                foreach ($items as $item) {
                    // Bỏ qua nếu thiếu dữ liệu
                    if (!$item->start_time || !$item->intruction_time || !$item->finish_time) {
                        continue;
                    }

                    // Parse các mốc thời gian
                    try {
                        $start = \Carbon\Carbon::createFromFormat('YmdHis', $item->start_time);
                        $instr = \Carbon\Carbon::createFromFormat('YmdHis', $item->intruction_time);
                        $finish = \Carbon\Carbon::createFromFormat('YmdHis', $item->finish_time);
                    } catch (\Exception $e) {
                        continue; // Bỏ qua nếu format sai
                    }

                    // Kiểm tra logic thời gian
                    if ($start->lessThan($instr) || $start->greaterThan($finish)) {
                        continue; // Bỏ qua nếu thời gian không hợp lý
                    }

                    $totalWait += $instr->diffInSeconds($start);
                    $totalExec += $start->diffInSeconds($finish);
                }

                $stats[] = [
                    'branch' => $branchName,
                    'wait' => round($totalWait / $count / 60),
                    'exec' => round($totalExec / $count / 60),
                    'count' => $count
                ];
            }

            $stats = collect($stats);

            // Tập hợp danh sách loại dịch vụ có tổng số lượt
            $branchSummary = $stats
                ->groupBy('branch')
                ->map(function ($items) {
                    return number_format($items->sum('count'));
                });

            // Biến `categories` thành dạng: "Tên dịch vụ (số lượt)"
            $branches = $branchSummary->keys()->map(function ($branch) use ($branchSummary) {
                return $branch . ' (' . $branchSummary[$branch] . ')';
            })->values();

            // Gộp series theo từng branch
            $series = [];

            foreach ($stats->groupBy('branch') as $branch => $items) {
                $waitSeries = [
                    'name' => $branch . ' - Thời gian chờ',
                    'data' => $branchSummary->keys()->map(function ($branch) use ($items) {
                        $item = $items->firstWhere('branch', $branch);
                        return $item ? $item['wait'] : 0;
                    })->toArray()
                ];

                $execSeries = [
                    'name' => $branch . ' - Thời gian thực hiện',
                    'data' => $branchSummary->keys()->map(function ($branch) use ($items) {
                        $item = $items->firstWhere('branch', $branch);
                        return $item ? $item['exec'] : 0;
                    })->toArray()
                ];

                $series[] = $waitSeries;
                $series[] = $execSeries;
            }

            $result = [
                'summary' => [
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $data->map(function ($item) {
                    return [
                        'branch_name' => $item->branch_name,
                        'instruction_time' => $item->intruction_time,
                        'start_time' => $item->start_time,
                        'finish_time' => $item->finish_time
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu phí dịch vụ',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/examinations/by-room
     * Logic từ HomeController@fetchKhamByRoom
     */
    public function getExaminationsByRoom(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|string|regex:/^\d{14}$/',
                'endDate' => 'required|string|regex:/^\d{14}$/'
            ]);
            
            // Validate date format and range
            if (!preg_match('/^\d{14}$/', $request->input('startDate'))) {
                throw new \InvalidArgumentException('startDate phải có format YYYYMMDDHHmmss');
            }
            if (!preg_match('/^\d{14}$/', $request->input('endDate'))) {
                throw new \InvalidArgumentException('endDate phải có format YYYYMMDDHHmmss');
            }
            
            // Validate date range
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            if ($endDate < $startDate) {
                throw new \InvalidArgumentException('endDate phải >= startDate');
            }

            $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));

            $data = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->selectRaw('
                    his_execute_room.execute_room_name,
                    his_service_req.service_req_stt_id,
                    COUNT(*) as so_luong
                ')
                ->whereBetween('intruction_time', [$current_date['from_date'], $current_date['to_date']])
                ->where('his_service_req.service_req_type_id', 1)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->groupBy('his_execute_room.execute_room_name', 'his_service_req.service_req_stt_id')
                ->get();

            $sum_sl = $data->sum('so_luong');

            // Danh sách các trạng thái
            $statusLabels = [
                1 => 'Chưa thực hiện',
                2 => 'Đang thực hiện',
                3 => 'Đã thực hiện',
            ];

            // Biến tạm để gom dữ liệu
            $roomData = [];

            foreach ($data as $item) {
                $room = $item->execute_room_name;
                $status = $item->service_req_stt_id;

                // Khởi tạo mảng nếu chưa có phòng
                if (!isset($roomData[$room])) {
                    $roomData[$room] = [
                        'room' => $room,
                        'Chưa thực hiện' => 0,
                        'Đang thực hiện' => 0,
                        'Đã thực hiện' => 0,
                    ];
                }

                $label = $statusLabels[$status] ?? 'Khác';
                $roomData[$room][$label] += $item->so_luong;
            }

            $result = [
                'summary' => [
                    'total_examinations' => $sum_sl,
                    'period' => [
                        'start_date' => $request->input('startDate'),
                        'end_date' => $request->input('endDate')
                    ]
                ],
                'data' => $data->map(function ($item) {
                    return [
                        'room_name' => $item->execute_room_name,
                        'status_id' => $item->service_req_stt_id,
                        'status_name' => $this->getServiceStatusName($item->service_req_stt_id),
                        'count' => (int) $item->so_luong
                    ];
                })
            ];

            return $this->apiResponse($result);

        } catch (\Exception $e) {
            return $this->apiResponse(null, false, [
                'code' => 'BAD_REQUEST',
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu khám theo phòng',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Helper method để lấy tên trạng thái dịch vụ
     */
    private function getServiceStatusName($statusId)
    {
        $statusNames = [
            1 => 'Chưa thực hiện',
            2 => 'Đang thực hiện',
            3 => 'Đã thực hiện'
        ];
        
        return $statusNames[$statusId] ?? 'Không xác định';
    }

    // Private methods từ HomeController
    private function serviceByType($from_date, $to_date, $serviceType = null)
    {
        return DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id')
        ->selectRaw('count(*) as so_luong, service_req_stt_id')
        ->whereBetween('intruction_time', [$from_date, $to_date])
        ->where('his_service_req.service_req_type_id', $serviceType)
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0)
        ->groupBy('service_req_stt_id')
        ->get();
    }

    private function getExamAndParraclinical($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->join('his_branch', 'his_branch.id', '=', 'his_sere_serv.tdl_execute_branch_id')
        ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
        ->select('his_branch.branch_name',
            'his_service_req_type.service_req_type_name',
            'his_service_req.intruction_time',
            'his_service_req.start_time',
            'his_service_req.finish_time'
        )
        ->whereBetween('intruction_time', [$from_date, $to_date])
        ->whereIn('his_service_req.service_req_type_id', [1,2,3,5,8,9,12,13])
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0)
        ->whereNotNull('finish_time')
        ->get();
    }

    private function getDiagnoticImaging($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_sere_serv')
        ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
        ->join('his_branch', 'his_branch.id', '=', 'his_sere_serv.tdl_execute_branch_id')
        ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
        ->join('his_service', 'his_service.id', '=', 'his_sere_serv.service_id')
        ->leftjoin('his_diim_type', 'his_diim_type.id', '=', 'his_service.diim_type_id')
        ->select('his_branch.branch_name',
            'his_diim_type.diim_type_name',
            'his_service_req.intruction_time',
            'his_service_req.start_time',
            'his_service_req.finish_time'
        )
        ->whereBetween('intruction_time', [$from_date, $to_date])
        ->whereIn('his_service_req.service_req_type_id', [3])
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0)
        ->whereNotNull('finish_time')
        ->get();
    }

    private function getPrescriptionData($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_service_req')
        ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->select('his_branch.branch_name',
            'his_treatment.out_time as intruction_time',
            'his_treatment.fee_lock_time as start_time',
            'his_service_req.finish_time as finish_time'
        )
        ->whereBetween('his_treatment.out_time', [$from_date, $to_date])
        ->whereIn('his_service_req.service_req_type_id', [6])
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0)
        ->whereNotNull('his_treatment.fee_lock_time')
        ->get();
    }

    private function getFeeData($from_date, $to_date)
    {
        return DB::connection('HISPro')
        ->table('his_treatment')
        ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
        ->select('his_branch.branch_name',
            'his_treatment.out_time as intruction_time',
            'his_treatment.fee_lock_time as start_time',
            'his_treatment.fee_lock_time as finish_time'
        )
        ->whereBetween('his_treatment.out_time', [$from_date, $to_date])
        ->where('his_treatment.tdl_treatment_type_id', 1)
        ->whereNotNull('his_treatment.fee_lock_time')
        ->get();
    }
}
