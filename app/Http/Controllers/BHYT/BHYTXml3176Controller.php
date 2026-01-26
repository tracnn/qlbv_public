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
    protected $xml3176Service;

    public function __construct(Xml3176Service $xml3176Service)
    {
        $this->xml3176Service = $xml3176Service;
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
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
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
        $imported_by = $request->input('imported_by');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($treatment_code) {
            $result = Xml3176Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                ->where('ma_lk', $treatment_code)
                ->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }, 'Xml3176ErrorResult' => function($query) {
                    $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                }, 'Xml3176Information' => function($query) {
                    $query->select('ma_lk', 
                    'exported_at', 
                    'imported_by', 
                    'is_signed', 
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
                    ->with(['check_hein_card' => function($query) {
                        $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                    }, 'Xml3176ErrorResult' => function($query) {
                        $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                    }, 'Xml3176Information' => function($query) {
                        $query->select('ma_lk', 
                        'exported_at', 
                        'imported_by', 
                        'is_signed', 
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
                } else {
                    $result = $result->with(['Xml3176ErrorResult' => function($query) {
                        $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                    }]);
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
            return $result->Xml3176Information && $result->Xml3176Information->is_signed ? 
            '<i class="fa fa-check-circle text-success" title="Ký số"></i>' : 
            ($result->Xml3176Information && $result->Xml3176Information->signed_error ? '<i class="fa fa-times-circle text-danger" title="'.$result->Xml3176Information->signed_error.'"></i>' : '<i class="fa fa-times-circle text-danger" title="Không ký số"></i>');
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
            if (!$highlight && $result->Xml3176ErrorResult && $result->Xml3176ErrorResult->isNotEmpty()) {
                $highlight = true;
            }
            return $highlight ? 'highlight-red' : '';
        })
        ->rawColumns(['exported_at', 'is_signed', 'action', 'submitted_at'])
        ->toJson();
    }

    public function detailXml($ma_lk)
    {
        $xml1 = Xml3176Xml1::where('ma_lk', $ma_lk)
        ->firstOrFail();

        return view('bhyt.xml3176.detail-xml',  compact('xml1')); 
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
                    $xmldata = simplexml_load_file($fileFullPath);
                    
                    if (!$this->processXmlData($xmldata)) {
                        $errors[] = "File {$fileName} has invalid structure.";
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

    private function processXmlData($xmldata)
    {
        $ma_lk = null;
        $processedFileTypes = [];

        if (!isset($xmldata->THONGTINDONVI->MACSKCB) || empty($xmldata->THONGTINDONVI->MACSKCB)) {
            \Log::error('MACSKCB not found or is empty in XML data');
            return false;
        }

        $macskcb = (string)$xmldata->THONGTINDONVI->MACSKCB;

        foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs) {
            $fileContent = base64_decode($file_hs->NOIDUNGFILE);
            $data = simplexml_load_string($fileContent);

            $fileType = (string)$file_hs->LOAIHOSO;
            $soluonghoso = count($xmldata->THONGTINHOSO->SOLUONGHOSO);

            if (!is_string($fileType)) {
                \Log::error('Invalid file type or missing expected structure for ' . $fileType);
                return false;
            }

            switch ($fileType) {
                case 'XML1':
                    $expectedStructure = XmlStructures::$expectedStructures3176[$fileType] ?? [];
                    if (!empty($expectedStructure) && !validateDataStructure($data, $expectedStructure)) {
                        \Log::error('Invalid data structure for ' . $fileType);
                        return false;
                    }
                    
                    $processedFileTypes[] = $fileType;
                    $ma_lk = (string)$data->MA_LK;

                    $this->xml3176Service->deleteExistingXml3176($ma_lk);
                    $this->xml3176Service->storeXml3176Xml1($data, $fileType);
                    break;
                case 'XML2':
                    $this->xml3176Service->storeXml3176Xml2($data, $fileType);
                    break;
                case 'XML3':
                    $this->xml3176Service->storeXml3176Xml3($data, $fileType);
                    break;
                case 'XML4':
                    $this->xml3176Service->storeXml3176Xml4($data, $fileType);
                    break;
                case 'XML5':
                    $this->xml3176Service->storeXml3176Xml5($data, $fileType);
                    break;
                case 'XML6':
                    $this->xml3176Service->storeXml3176Xml6($data, $fileType);
                    break;
                case 'XML7':
                    $this->xml3176Service->storeXml3176Xml7($data, $fileType);
                    break;
                case 'XML8':
                    $this->xml3176Service->storeXml3176Xml8($data, $fileType);
                    break;
                case 'XML9':
                    $this->xml3176Service->storeXml3176Xml9($data, $fileType);
                    break;
                case 'XML10':
                    $this->xml3176Service->storeXml3176Xml10($data, $fileType);
                    break;
                case 'XML11':
                    $this->xml3176Service->storeXml3176Xml11($data, $fileType);
                    break;
                case 'XML12':
                    break;
                case 'XML13':
                    $this->xml3176Service->storeXml3176Xml13($data, $fileType);
                    break;
                case 'XML14':
                    $this->xml3176Service->storeXml3176Xml14($data, $fileType);
                    break;
                case 'XML15':
                    $this->xml3176Service->storeXml3176Xml15($data, $fileType);
                    break;
                default:
                    \Log::warning('Unknown XML type: ' . $file_hs->LOAIHOSO);
                    break;
            }
        }
            
        if ($ma_lk !== null && !empty($processedFileTypes)) {
            $this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);
            if (!config('organization.xml_3176_not_check', false)) {
                $this->xml3176Service->checkXml3176Complete($ma_lk);
                
                if (config('xml3176.export_xml3176_enabled')) {
                    $this->xml3176Service->exportXml3176($ma_lk);
                }
            }
        }

        return true;
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
        
        $fileName = 'xml3176_error_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Xml3176ErrorMultiSheetExport($date_from, $date_to, $xml_filter_status, 
            $date_type, $xml3176_error_catalog_id, $payment_date_filter, $imported_by, $xml_submit_status), $fileName);
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

        $fileName = 'xml3176_xml_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Xml3176XmlExport($date_from, $date_to, $xml_filter_status, 
            $date_type, $xml3176_error_catalog_id, $xml_export_status, $payment_date_filter, $imported_by, $xml_submit_status), $fileName);
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
