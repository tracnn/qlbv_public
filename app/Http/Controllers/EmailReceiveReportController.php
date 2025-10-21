<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\EmailReceiveReport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use DataTables;

class EmailReceiveReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('checkrole:superadministrator');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('email-receive-reports.index');
    }

    /**
     * Get data for DataTable
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('email-receive-reports.index');
        }

        $query = EmailReceiveReport::query();

        return DataTables::of($query)
            ->editColumn('active', function ($result) {
                return $result->active == 1
                    ? '<span class="text-success glyphicon glyphicon-ok"></span>'
                    : '<span class="text-danger glyphicon glyphicon-remove"></span>';
            })
            ->editColumn('period', function ($result) {
                return $result->period == 1
                    ? '<span class="label label-success">Có</span>'
                    : '<span class="label label-default">Không</span>';
            })
            ->editColumn('report_types', function ($result) {
                $types = [];
                if ($result->bcaobhxh) $types[] = '<span class="label label-default">BHXH</span>';
                if ($result->bcaoqtri) $types[] = '<span class="label label-primary">Quản trị</span>';
                if ($result->qtri_tckt) $types[] = '<span class="label label-info">Thống kê</span>';
                if ($result->qtri_hsdt) $types[] = '<span class="label label-success">Hồ sơ</span>';
                if ($result->qtri_dvkt) $types[] = '<span class="label label-warning">Dịch vụ</span>';
                if ($result->qtri_canhbao) $types[] = '<span class="label label-danger">Cảnh báo</span>';
                if ($result->bcaoadmin) $types[] = '<span class="label label-purple">Admin</span>';
                if ($result->khoa_san) $types[] = '<span class="label label-maroon">Khoa sản</span>';
                if ($result->dinh_duong) $types[] = '<span class="label label-navy">Dinh dưỡng</span>';
                return implode(' ', $types);
            })
            ->editColumn('created_at', function ($result) {
                return $result->created_at->format('d/m/Y H:i');
            })
            ->addColumn('action', function ($result) {
                $editUrl = route('email-receive-reports.edit', $result->id);
                $deleteUrl = route('email-receive-reports.destroy', $result->id);
                $toggleUrl = route('email-receive-reports.toggle-status', $result->id);
                
                return '<div class="btn-group">
                    <button class="btn btn-xs btn-warning edit-email" data-id="' . $result->id . '" 
                            data-edit-url="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" 
                            data-toggle="modal" data-target="#editEmailModal">
                        <span class="glyphicon glyphicon-edit"></span> Sửa
                    </button>
                    <button class="btn btn-xs btn-' . ($result->active ? 'default' : 'success') . ' toggle-status" 
                            data-id="' . $result->id . '" 
                            data-toggle-url="' . htmlspecialchars($toggleUrl, ENT_QUOTES, 'UTF-8') . '"
                            data-active="' . ($result->active ? '1' : '0') . '">
                        <span class="glyphicon glyphicon-' . ($result->active ? 'pause' : 'play') . '"></span>
                    </button>
                    <button class="btn btn-xs btn-danger delete-email" 
                            data-id="' . $result->id . '" 
                            data-delete-url="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                </div>';
            })
            ->rawColumns(['action', 'active', 'period', 'report_types'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $html = view('email-receive-reports.partials.create_form')->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:email_receive_reports,email',
            'active' => 'boolean',
            'bcaobhxh' => 'boolean',
            'bcaoqtri' => 'boolean',
            'qtri_tckt' => 'boolean',
            'qtri_hsdt' => 'boolean',
            'qtri_dvkt' => 'boolean',
            'qtri_canhbao' => 'boolean',
            'period' => 'boolean',
            'bcaoadmin' => 'boolean',
            'khoa_san' => 'boolean',
            'dinh_duong' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()]);
        }

        try {
            // Đảm bảo tất cả các trường boolean có giá trị mặc định
            $data = $request->all();
            $booleanFields = ['active', 'bcaobhxh', 'bcaoqtri', 'qtri_tckt', 'qtri_hsdt', 'qtri_dvkt', 'qtri_canhbao', 'period', 'bcaoadmin', 'khoa_san', 'dinh_duong'];
            
            foreach ($booleanFields as $field) {
                if (!isset($data[$field])) {
                    $data[$field] = false;
                } else {
                    $data[$field] = (bool) $data[$field];
                }
            }

            EmailReceiveReport::create($data);
            return response()->json(['success' => true, 'message' => 'Thêm email nhận báo cáo thành công!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $emailReport = EmailReceiveReport::findOrFail($id);
        $html = view('email-receive-reports.partials.edit_form', compact('emailReport'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $emailReport = EmailReceiveReport::findOrFail($id);


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('email_receive_reports', 'email')->ignore($id, 'id')
            ],
            'active' => 'boolean',
            'bcaobhxh' => 'boolean',
            'bcaoqtri' => 'boolean',
            'qtri_tckt' => 'boolean',
            'qtri_hsdt' => 'boolean',
            'qtri_dvkt' => 'boolean',
            'qtri_canhbao' => 'boolean',
            'period' => 'boolean',
            'bcaoadmin' => 'boolean',
            'khoa_san' => 'boolean',
            'dinh_duong' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()]);
        }

        try {
            // Đảm bảo tất cả các trường boolean có giá trị mặc định
            $data = $request->all();
            $booleanFields = ['active', 'bcaobhxh', 'bcaoqtri', 'qtri_tckt', 'qtri_hsdt', 'qtri_dvkt', 'qtri_canhbao', 'period', 'bcaoadmin', 'khoa_san', 'dinh_duong'];
            
            foreach ($booleanFields as $field) {
                if (!isset($data[$field])) {
                    $data[$field] = false;
                } else {
                    $data[$field] = (bool) $data[$field];
                }
            }

            $emailReport->update($data);
            return response()->json(['success' => true, 'message' => 'Cập nhật email nhận báo cáo thành công!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $emailReport = EmailReceiveReport::findOrFail($id);
            $emailReport->delete();
            return response()->json(['success' => true, 'message' => 'Xóa email nhận báo cáo thành công!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle status of the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        try {
            $emailReport = EmailReceiveReport::findOrFail($id);
            $emailReport->update(['active' => !$emailReport->active]);
            
            $status = $emailReport->active ? 'kích hoạt' : 'vô hiệu hóa';
            return response()->json(['success' => true, 'message' => "Đã {$status} email nhận báo cáo thành công!"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Get emails by report type for API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmailsByReportType(Request $request)
    {
        $reportType = $request->get('type');
        $includeSpecial = $request->get('include_special', false);

        switch ($reportType) {
            case 'bcaobhxh':
                $emails = EmailReceiveReport::getEmailsForBHXHReport($includeSpecial);
                break;
            case 'bcaoqtri':
                $emails = EmailReceiveReport::getEmailsForAdminReport($includeSpecial);
                break;
            case 'bcaoadmin':
                $emails = EmailReceiveReport::getEmailsForBcaoAdminReport($includeSpecial);
                break;
            case 'khoa_san':
                $emails = EmailReceiveReport::getEmailsForKhoaSanReport($includeSpecial);
                break;
            case 'dinh_duong':
                $emails = EmailReceiveReport::getEmailsForDinhDuongReport($includeSpecial);
                break;
            case 'qtri_tckt':
                $emails = EmailReceiveReport::getEmailsForStatisticalReport($includeSpecial);
                break;
            case 'qtri_hsdt':
                $emails = EmailReceiveReport::getEmailsForRegistrationReport($includeSpecial);
                break;
            case 'qtri_dvkt':
                $emails = EmailReceiveReport::getEmailsForServiceReport($includeSpecial);
                break;
            case 'qtri_canhbao':
                $emails = EmailReceiveReport::getEmailsForAlertReport($includeSpecial);
                break;
            case 'special':
                $emails = EmailReceiveReport::getEmailsForSpecialReport();
                break;
            default:
                $emails = [];
        }

        return response()->json(['emails' => $emails]);
    }
}