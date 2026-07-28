<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Models\BHYT\Xml3176Xml1;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176Service;
use App\Services\XmlStructures;
use App\Services\Xml3176\Xml3176ErrorIndex;
use App\Services\Xml3176\Xml3176DetailTabs;
use App\Services\Xml3176\Xml3176Importer;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176XmlExport;
use App\Exports\Xml3176Xml7980aExport;

use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use DB;

class BHYTXml3176Controller extends Controller
{
    /**
     * Cac cot duoc phep di ra ngoai trong JSON cua DataTables.
     *
     * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay
     * se khong tu dong lot ra ngoai lam payload phinh lai. Xml3176DatatableColumnsTest
     * khoa danh sach nay khop dung cac cot blade doc.
     */
    const DATATABLE_COLUMNS = [
        'ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh',
        'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at',
        'exported_at', 'submitted_at', 'is_signed', 'imported_by', 'action',
    ];

    /**
     * Cac tab duoc nap khi nguoi dung bam vao, khong nap san cung vo modal.
     *
     * Gia tri la ten blade trong bhyt/xml3176. Danh sach TRANG: {xml} den tu URL.
     */
    const TAB_TAI_LUOI = [
        'XML7'  => 'detail-xml-7',
        'XML8'  => 'detail-xml-8',
        'XML9'  => 'detail-xml-9',
        'XML10' => 'detail-xml-10',
        'XML11' => 'detail-xml-11',
        'XML13' => 'detail-xml-13',
        'XML14' => 'detail-xml-14',
        'XML15' => 'detail-xml-15',
        'HEIN'  => 'detail-xml-hein-card',
        'ERR'   => 'detail-xml-errors',
    ];

    protected $xml3176Service;
    protected $importer;

    public function __construct(Xml3176Service $xml3176Service, Xml3176Importer $importer)
    {
        $this->xml3176Service = $xml3176Service;
        $this->importer = $importer;
    }

    public function index()
    {   
        return view('bhyt.xml3176.index');
    }

    public function importIndex()
    {   
        return view('bhyt.xml3176.import');
    }

    public function fetchData(Request $request)
    {
        // if (!$request->ajax()) {
        //     return redirect()->route('home');
        // }
        
        $treatment_code = $request->input('treatment_code');
        $patient_code = $request->input('patient_code');
        $date_type = $request->input('date_type');
        $xml_filter_status = $request->input('xml_filter_status');

        $xml3176_error_catalog_id = $request->input('xml3176_error_catalog');

        $hein_card_filter = $request->input('hein_card_filter');
        $payment_date_filter = $request->input('payment_date_filter');
        $treatment_type_fillter = $request->input('treatment_type_fillter');
        $xml_export_status = $request->input('xml_export_status');
        $xml_submit_status = $request->input('xml_submit_status');
        $xml_sign_status = $request->input('xml_sign_status');
        $imported_by = $request->input('imported_by');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($treatment_code) {
            $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                ->where('ma_lk', $treatment_code)
                // Chi can biet CO loi hay khong (setRowClass), khong can noi dung loi.
                // Eager-load ca tap loi keo ve cot description kieu TEXT cho tung dong.
                ->withCount('Xml3176ErrorResult')
                ->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }, 'Xml3176Information' => function($query) {
                    $query->select('ma_lk', 
                    'exported_at', 
                    'imported_by', 
                    'is_signed', 
                    'sign_method',
                    'submitted_at', 
                    'submit_error', 
                    'signed_error', 
                    'submitted_message');
                }]);

                // Kiểm tra role của user
                if (!\Auth::user()->hasRole(['superadministrator', 'administrator'])) {
                    $result = $result->whereHas('Xml3176Information', function($query) {
                        $query->where('imported_by', \Auth::user()->loginname);
                    });
                }                      
        } else {
            if ($patient_code) {
                $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                    'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                    ->where('ma_bn', $patient_code)
                    // Chi can biet CO loi hay khong (setRowClass), khong can noi dung loi.
                    ->withCount('Xml3176ErrorResult')
                    ->with(['check_hein_card' => function($query) {
                        $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                    }, 'Xml3176Information' => function($query) {
                        $query->select('ma_lk', 
                        'exported_at', 
                        'imported_by', 
                        'is_signed', 
                        'sign_method',
                        'submitted_at', 
                        'submit_error', 
                        'signed_error', 
                        'submitted_message');
                    }]);
                    // Kiểm tra role của user
                    if (!\Auth::user()->hasRole(['superadministrator', 'administrator'])) {
                        $result = $result->whereHas('Xml3176Information', function($query) {
                            $query->where('imported_by', \Auth::user()->loginname);
                        });
                    }
            } else {
                // Check and convert date format
                if (strlen($dateFrom) == 10) {
                    $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
                }

                if (strlen($dateTo) == 10) {
                    $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
                }

                // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
                $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
                $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

                // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
                $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
                $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

                // Define the date field based on date_type
                switch ($date_type) {
                    case 'date_in':
                        $dateField = 'ngay_vao';
                        $formattedDateFrom = $formattedDateFromForFields;
                        $formattedDateTo = $formattedDateToForFields;
                        break;
                    case 'date_out':
                        $dateField = 'ngay_ra';
                        $formattedDateFrom = $formattedDateFromForFields;
                        $formattedDateTo = $formattedDateToForFields;
                        break;
                    case 'date_payment':
                        $dateField = 'ngay_ttoan';
                        $formattedDateFrom = $formattedDateFromForFields;
                        $formattedDateTo = $formattedDateToForFields;
                        break;
                    case 'date_create':
                        $dateField = 'created_at';
                        $formattedDateFrom = $formattedDateFromForTimestamp;
                        $formattedDateTo = $formattedDateToForTimestamp;
                        break;
                    case 'date_update':
                        $dateField = 'updated_at';
                        $formattedDateFrom = $formattedDateFromForTimestamp;
                        $formattedDateTo = $formattedDateToForTimestamp;
                        break;
                    default:
                        $dateField = 'ngay_ttoan';
                        $formattedDateFrom = $formattedDateFromForFields;
                        $formattedDateTo = $formattedDateToForFields;
                        break;
                }

                $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                    'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                // Ap dung cho CA hai nhanh ben duoi, ke ca nhanh loc theo ma loi von
                // khong eager-load - khien setRowClass() lazy-load mot truy van MOI dong.
                ->withCount('Xml3176ErrorResult')
                ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

                // Apply relationships
                $result = $result->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }]);

                $result = $result->with(['Xml3176Information' => function($query) {
                    $query->select('ma_lk', 
                    'exported_at', 
                    'export_error', 
                    'imported_by', 
                    'is_signed', 
                    'sign_method',
                    'submitted_at', 
                    'submit_error', 
                    'signed_error', 
                    'submitted_message');
                }]);

                if ($xml3176_error_catalog_id) {
                    $xml3176ErrorCatalog = Xml3176ErrorCatalog::find($xml3176_error_catalog_id);
                    if ($xml3176ErrorCatalog) {
                        $result = $result->whereHas('Xml3176ErrorResult', function($query) use ($xml3176ErrorCatalog) {
                            $query->where('xml', $xml3176ErrorCatalog->xml)
                                  ->where('error_code', $xml3176ErrorCatalog->error_code);
                        });
                    }
                }

                
                // Apply filter based on xml_filter_status
                if ($xml_filter_status === 'has_error') {
                    $result = $result->where(function ($query) {
                        $query->whereHas('Xml3176ErrorResult')
                        ->orWhereHas('check_hein_card', function ($subQuery) {
                            $subQuery->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code', []))
                                    ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code', []));
                        });
                    });
                } elseif ($xml_filter_status === 'no_error') {
                    $result = $result->whereDoesntHave('Xml3176ErrorResult')
                    ->whereDoesntHave('check_hein_card', function ($subQuery) {
                        $subQuery->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code', []))
                                ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code', []));
                    });
                } elseif ($xml_filter_status === 'has_error_critical') {
                    $result = $result->whereHas('Xml3176ErrorResult', function ($query) {
                        $query->where('critical_error', true);
                    });
                } elseif ($xml_filter_status === 'has_error_warning') {
                    $result = $result->whereHas('Xml3176ErrorResult', function ($query) {
                        $query->where('critical_error', false);
                    })->whereDoesntHave('Xml3176ErrorResult', function ($query) {
                        $query->where('critical_error', true);
                    });
                } elseif ($xml_filter_status === 'has_error_hein_card') {
                    $result = $result->whereHas('check_hein_card', function ($query) {
                        $query->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code'))
                              ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code'));
                    });
                } elseif ($xml_filter_status === 'has_error_hein_card_without_xml') {
                    $result = $result->whereHas('check_hein_card', function ($query) {
                        $query->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code'))
                              ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code'));
                    })->whereDoesntHave('Xml3176ErrorResult');
                } elseif ($xml_filter_status === 'no_error_critical') {
                    $result = $result->whereDoesntHave('Xml3176ErrorResult', function ($query) {
                        $query->where('critical_error', true);
                    });
                }

                // Apply filter based on has_hein_card
                if ($hein_card_filter === 'has_hein_card') {
                    $result = $result->where('ma_the_bhyt', '<>', '');
                } elseif ($hein_card_filter === 'no_hein_card') {
                    $result = $result->where('ma_the_bhyt', '=', '');
                }

                // Apply filter based on payment_date_filter
                if ($payment_date_filter === 'has_payment_date') {
                    $result = $result->where('ngay_ttoan', '<>', '');
                } elseif ($payment_date_filter === 'no_payment_date') {
                    $result = $result->where('ngay_ttoan', '=', '');
                }

                // Apply filter based on treatment_type_fillter
                if ($treatment_type_fillter) {
                    $result = $result->where('ma_loai_kcb', $treatment_type_fillter);
                }

                //Apply filter based on xml_export_status
                if ($xml_export_status === 'has_export') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNotNull('exported_at');
                    });
                } elseif ($xml_export_status === 'no_export') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNull('exported_at');
                    });
                }

                //Apply filter based on xml_submit_status
                if ($xml_submit_status === 'has_submit') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNotNull('submitted_at');
                    });
                } elseif ($xml_submit_status === 'not_submit') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNull('submitted_at');
                    });
                }  elseif ($xml_submit_status === 'has_submit_error') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNotNull('submit_error');
                    });
                }

                //Apply filter based on xml_sign_status
                if ($xml_sign_status === 'has_sign') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->where('is_signed', true);
                    });
                } elseif ($xml_sign_status === 'not_sign') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->where('is_signed', false);
                    });
                } elseif ($xml_sign_status === 'has_sign_error') {
                    $result = $result->whereHas('Xml3176Information', function ($query) {
                        $query->whereNotNull('signed_error');
                    });
                }

                // Apply filter based on imported_by
                if (!empty($imported_by)) {
                    $result = $result->whereHas('Xml3176Information', function ($query) use ($imported_by) {
                        $query->where('imported_by', $imported_by);
                    });
                }
            }
        }

        return Datatables::of($result)
        ->editColumn('ngay_sinh', function($result) {
            return dob($result->ngay_sinh);
        })
        ->editColumn('ngay_vao', function($result) {
            return strtodatetime($result->ngay_vao);
        })
        ->editColumn('ngay_ra', function($result) {
            return $result->ngay_ra ? strtodatetime($result->ngay_ra) : $result->ngay_ra;
        })
        ->editColumn('ngay_ttoan', function($result) {
            return $result->ngay_ttoan ? strtodatetime($result->ngay_ttoan) : $result->ngay_ttoan;
        })
        ->addColumn('exported_at', function ($result) {
            $tooltip = $result->Xml3176Information && $result->Xml3176Information->exported_at 
                ? $result->Xml3176Information->exported_at
                : ($result->Xml3176Information && $result->Xml3176Information->export_error
                    ? $result->Xml3176Information->export_error
                    : 'Not exported');
            $icon = $result->Xml3176Information && $result->Xml3176Information->export_error
                ? '<i class="fa fa-times-circle text-warning" title="'.$tooltip.'"></i>'
                : ($result->Xml3176Information && $result->Xml3176Information->exported_at
                    ? '<i class="fa fa-check-circle text-success" title="'.$tooltip.'"></i>'
                    : '<i class="fa fa-file-code-o text-secondary" title="'.$tooltip.'"></i>');
            return $icon;
        })
        ->addColumn('submitted_at', function ($result) {
            $tooltip = $result->Xml3176Information && $result->Xml3176Information->submitted_at 
                ? $result->Xml3176Information->submitted_message
                : ($result->Xml3176Information && $result->Xml3176Information->submit_error
                    ? $result->Xml3176Information->submit_error
                    : 'Not submitted');
            $icon = $result->Xml3176Information && $result->Xml3176Information->submit_error
                ? '<i class="fa fa-times-circle text-warning" title="'.$tooltip.'"></i>'
                : ($result->Xml3176Information && $result->Xml3176Information->submitted_at
                    ? '<i class="fa fa-check-circle text-success" title="'.$tooltip.'"></i>'
                    : '<i class="fa fa-file-code-o text-secondary" title="'.$tooltip.'"></i>');
            
            $submittedMessage = $result->Xml3176Information && !empty($result->Xml3176Information->submitted_message)
                ? trim($result->Xml3176Information->submitted_message)
                : null;
            $copyIcon = $submittedMessage 
                ? '<i class="fa fa-copy copy-tooltip-btn" style="margin-left: 5px; cursor: pointer; font-size: 12px;" data-copy-text="'.htmlspecialchars($submittedMessage, ENT_QUOTES, 'UTF-8').'" title="Click để copy"></i>'
                : '';
            
            return '<span style="white-space: nowrap;">' . $icon . $copyIcon . '</span>';
        })
        ->addColumn('is_signed', function ($result) {
            $signMethod = $result->Xml3176Information && $result->Xml3176Information->sign_method 
                ? ' (' . $result->Xml3176Information->sign_method . ')' 
                : '';
            return $result->Xml3176Information && $result->Xml3176Information->is_signed ? 
            '<i class="fa fa-check-circle text-success" title="Đã ký số' . $signMethod . '"></i>' : 
            ($result->Xml3176Information && $result->Xml3176Information->signed_error ? '<i class="fa fa-times-circle text-danger" title="'.$result->Xml3176Information->signed_error.'"></i>' : '<i class="fa fa-times-circle text-danger" title="Chưa ký số"></i>');
        })
        ->addColumn('imported_by', function ($result) {
            return $result->Xml3176Information->imported_by ?? null;
        })
        ->addColumn('action', function ($result) {
            return '<a href="' . route('insurance.check-card.search',['card-number' => $result->ma_the_bhyt, 'name' => $result->ho_ten, 'birthday' => dob($result->ngay_sinh,0,8)]) . '" class="btn btn-sm btn-success" target="_blank"><span class="glyphicon glyphicon-check"></span> Tra thẻ</a>
                <a href="javascript:void(0);" onclick="deleteXML(\'' . $result->ma_lk . '\');" class="btn btn-sm btn-danger">
                                    <span class="glyphicon glyphicon-trash"></span> Xóa</a>
                <a href="' .route('treatment-result.search',['treatment_code'=>$result->ma_lk]) .'" class="btn btn-sm btn-primary" target="_blank">
                                    <span class="glyphicon glyphicon-envelope"></span> EMR</a>';
        })
        ->setRowClass(function ($result) {
            $highlight = false;
            if ($result->check_hein_card && (in_array($result->check_hein_card->ma_kiemtra, 
                config('xml3176.hein_card_invalid.check_code', [])) || in_array($result->check_hein_card->ma_tracuu, 
                    config('xml3176.hein_card_invalid.result_code', [])))) {
                $highlight = true;
            }
            // Dung so dem thay vi tap loi: khong dung quan he o day thi Eloquent
            // se lazy-load mot truy van cho MOI dong.
            if (!$highlight && $result->xml3176_error_result_count > 0) {
                $highlight = true;
            }
            return $highlight ? 'highlight-red' : '';
        })
        // Cat moi thu ngoai danh sach trang khoi JSON. Truoc day ba quan he long
        // (Xml3176ErrorResult, check_hein_card, Xml3176Information) di theo phan hoi
        // ra trinh duyet du KHONG cot nao doc chung, va yajra chay array_dot() + e()
        // len tung gia tri long ben trong, tung dong mot.
        // Dat truoc rawColumns: trong DataProcessor::process() thu tu la
        // addColumns -> editColumns -> setupRowVariables -> selectOnlyNeededColumns,
        // nen setRowClass() (nam trong setupRowVariables) da chay xong truoc khi cat,
        // va DT_RowClass nam trong danh sach mien tru nen khong bi cat nham.
        ->only(self::DATATABLE_COLUMNS)
        ->rawColumns(['exported_at', 'is_signed', 'action', 'submitted_at'])
        ->toJson();
    }

    public function detailXml($ma_lk)
    {
        $soBang = [2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15];

        // withCount thay vi with: vo modal chi can BIET moi bang co bao nhieu dong (de
        // an/hien tab va tinh huy hieu), khong can noi dung dong. Noi dung nap theo tab.
        $demQuanHe = [];
        foreach ($soBang as $n) {
            $demQuanHe[] = 'Xml3176Xml' . $n;
        }

        // check_hein_card duoc thanh tab doc de to mau -> nap tuong minh thay vi de
        // blade lazy-load ngam.
        $xml1 = Xml3176Xml1::with(['Xml3176ErrorResult', 'check_hein_card'])
        ->withCount($demQuanHe)
        ->where('ma_lk', $ma_lk)
        ->firstOrFail();

        $soDong = [];
        foreach ($soBang as $n) {
            $soDong['XML' . $n] = (int) $xml1->{'xml3176_xml' . $n . '_count'};
        }

        // Mot truy van moi bang nhieu dong cho ra CA hai thu: danh sach stt (huy hieu)
        // va cac khoa nhom (thanh tab con). Chi lay so/chuoi, khong dung model.
        $dsStt = [];
        $dsNhom = [];
        foreach (Xml3176DetailTabs::BANG_NHIEU_DONG as $xml => $ch) {
            $model = $ch['model'];
            $map = $model::where('ma_lk', $ma_lk)->pluck($ch['cot_nhom'], 'stt');
            $dsStt[$xml]  = $map->keys();
            $dsNhom[$xml] = $map->values();
        }

        return view('bhyt.xml3176.detail-xml', [
            'xml1'      => $xml1,
            'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
            'soDong'    => $soDong,
            'dsStt'     => $dsStt,
            'dsNhom'    => $dsNhom,
        ]);
    }

    /**
     * Noi dung mot tab cua modal chi tiet (cac tab bieu mau mot dong, The BHYT, Loi XML).
     */
    public function detailXmlTab($ma_lk, $xml)
    {
        if (!isset(self::TAB_TAI_LUOI[$xml])) {
            abort(404);
        }

        // Rieng tab Loi XML doc $error->Xml3176ErrorCatalog->error_name cho TUNG dong,
        // nen phai nap kem - khong thi thanh mot truy van moi dong loi.
        // Cac tab khac khong dung danh muc nay nen khong nap cho phi.
        $quanHe = $xml === 'ERR'
            ? ['Xml3176ErrorResult.Xml3176ErrorCatalog']
            : ['Xml3176ErrorResult'];

        $xml1 = Xml3176Xml1::with($quanHe)
        ->where('ma_lk', $ma_lk)
        ->firstOrFail();

        return view('bhyt.xml3176.' . self::TAB_TAI_LUOI[$xml], [
            'xml1'      => $xml1,
            'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
        ]);
    }

    /**
     * Mot trang cua mot nhom, cho cac bang nhieu dong (XML2..XML5).
     *
     * Tra ve dung mot <table> cong thanh phan trang - khong phai ca tab.
     */
    public function detailXmlRows(Request $request, $ma_lk, $xml)
    {
        $cauHinh = Xml3176DetailTabs::cauHinh($xml);   // abort(404) neu ngoai danh sach trang
        $model   = $cauHinh['model'];
        $cot     = $cauHinh['cot_nhom'];
        $cat     = $cauHinh['cat'];
        $nhom    = (string) $request->input('nhom', '');

        $truyVan = $model::where('ma_lk', $ma_lk);

        // Ten cot lay tu dang ky (hang so), khong phai tu tham so URL.
        if ($cat > 0) {
            $truyVan->where($cot, 'like', $nhom . '%');
        } else {
            $truyVan->where($cot, $nhom);
        }

        $rows = $truyVan->orderBy('stt')->paginate(Xml3176DetailTabs::CO_TRANG);

        // Chi lay loi cua rieng xml nay - du de to do va dung tooltip.
        $chiMucLoi = Xml3176ErrorIndex::tu(
            Xml3176ErrorResult::where('ma_lk', $ma_lk)->where('xml', $xml)->get()
        );

        return view('bhyt.xml3176.detail-xml-' . substr($xml, 3) . '-rows', [
            'rows'      => $rows,
            'chiMucLoi' => $chiMucLoi,
            'urlTrang'  => route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $ma_lk, 'xml' => $xml])
                            . '?nhom=' . urlencode($nhom),
        ]);
    }

    public function uploadData(Request $request)
    {
        $request->validate([
            'xmls' => 'required',
            'xmls.*' => 'mimes:xml|max:102400',
        ]);

        if ($request->hasFile('xmls')) {
            $files = $request->file('xmls');
            $files = is_array($files) ? $files : [$files];

            $errors = [];
            $fileChunks = array_chunk($files, 100);

            foreach ($fileChunks as $chunk) {
                foreach ($chunk as $file) {
                    $filePath = storage_path('app/uploads');
                    $fileName = $file->getClientOriginalName();
                    $file->move($filePath, $fileName);
                    $fileFullPath = $filePath . '/' . $fileName;
                    $kq = $this->importer->nhapTuChuoi(file_get_contents($fileFullPath));

                    if (!$kq->thanhCong) {
                        // Neu ly do cu the thay vi luon la "has invalid structure" nhu truoc.
                        $errors[] = "File {$fileName}: {$kq->lyDoThatBai}";
                    }

                    if (file_exists($fileFullPath)) {
                        unlink($fileFullPath);
                    }
                }
            }

            if (empty($errors)) {
                return response()
                ->json(['message' => 'File uploaded and processed successfully.'], 200);
            } else {
                return response()
                ->json(['message' => 'File(s) not processed due to invalid structure.', 'errors' => $errors], 400);
            }
        }

        return response()->json(['message' => 'File not uploaded.'], 400);
    }

    public function exportXml(Request $request)
    {
        $selectedRecords = $request->input('records');
        $fileNames = [];
        $chunkSize = 50;
        $chunks = array_chunk($selectedRecords, $chunkSize);

        $storagePath = 'public/xml3176/';

        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $selectedRecord) {
                $xmlData = $this->xml3176Service->getDataForXmlExport($selectedRecord);

                $formattedDateTime = date('Y.m.d_H.i.s');
                $fileName = $formattedDateTime . '_' . $selectedRecord . '.xml';
                $filePath = storage_path('app/' . $storagePath . $fileName);

                if (file_put_contents($filePath, $xmlData) === false) {
                    \Log::error('Failed to write XML file: ' . $filePath);
                    continue;
                }

                $fileNames[] = $filePath;
            }
        }

        $zipFileName = 'exported_xml_' . date('Y.m.d_H.i.s') . '.zip';
        $zipFilePath = storage_path('app/' . $storagePath . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($fileNames as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to create ZIP file.'], 500);
        }

        foreach ($fileNames as $file) {
            unlink($file);
        }

        return response()->json(['success' => true, 'file' => asset('storage/xml3176/' . $zipFileName)]);
    }

    public function exportXml3176XmlErrors(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $xml_filter_status = $request->input('xml_filter_status');
        $date_type = $request->input('date_type');
        $xml3176_error_catalog_id = $request->input('xml3176_error_catalog');
        $payment_date_filter = $request->input('payment_date_filter');
        $imported_by = $request->input('imported_by');
        $xml_submit_status = $request->input('xml_submit_status');
        $xml_sign_status = $request->input('xml_sign_status');

        $fileName = 'xml3176_error_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Xml3176ErrorMultiSheetExport($date_from, $date_to, $xml_filter_status, 
            $date_type, 
            $xml3176_error_catalog_id, 
            $payment_date_filter, 
            $imported_by, 
            $xml_submit_status, 
            $xml_sign_status), $fileName);
    }

    public function export7980aData(Request $request)
    {
        $fileName = '7980a_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Xml3176Xml7980aExport($request), $fileName);
    }

    public function exportXml3176XmlXlsx(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $xml_filter_status = $request->input('xml_filter_status');
        $date_type = $request->input('date_type');
        $xml3176_error_catalog_id = $request->input('xml3176_error_catalog');
        $xml_export_status = $request->input('xml_export_status');
        $payment_date_filter = $request->input('payment_date_filter');
        $imported_by = $request->input('imported_by');
        $xml_submit_status = $request->input('xml_submit_status');
        $xml_sign_status = $request->input('xml_sign_status');

        $fileName = 'xml3176_xml_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Xml3176XmlExport($date_from, $date_to, $xml_filter_status, 
            $date_type, 
            $xml3176_error_catalog_id, 
            $xml_export_status, 
            $payment_date_filter, 
            $imported_by, 
            $xml_submit_status, 
            $xml_sign_status), $fileName);
    }

    public function deleteXml($ma_lk)
    {
        if ($this->xml3176Service->deleteXml3176XmlAndError($ma_lk)) {
            return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);    
        }
        return response()->json(['success' => false, 'message' => 'Incomplete.']);
    }

    public function checkJobStatus(Request $request)
    {
        $jobsCount = DB::table('jobs')
            ->where('queue', config('xml3176.queue_name'))
            ->whereNull('reserved_at')
            ->count();

        return response()->json(['jobs_count' => $jobsCount]);
    }
}
