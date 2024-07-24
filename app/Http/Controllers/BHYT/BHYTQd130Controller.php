<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Models\BHYT\Qd130Xml1;

use App\Models\BHYT\Qd130XmlErrorResult;
use App\Models\BHYT\Qd130XmlErrorCatalog;
use App\Services\Qd130XmlService;
use App\Services\XmlStructures;

use App\Exports\Qd130ErrorExport;
use App\Exports\Qd130XmlExport;

use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use DB;

class BHYTQd130Controller extends Controller
{
    protected $qd130XmlService;

    public function __construct(Qd130XmlService $qd130XmlService)
    {
        $this->qd130XmlService = $qd130XmlService;
    }

    public function index()
    {   
        return view('bhyt.qd130.index');
    }

    public function importIndex()
    {   
        return view('bhyt.qd130.import');
    }

    public function fetchData(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $treatment_code = $request->input('treatment_code');
        $date_type = $request->input('date_type');
        $xml_filter_status = $request->input('xml_filter_status');

        $qd130_xml_error_catalog_id = $request->input('qd130_xml_error_catalog');

        $hein_card_filter = $request->input('hein_card_filter');
        $payment_date_filter = $request->input('payment_date_filter');
        $treatment_type_fillter = $request->input('treatment_type_fillter');
        $xml_export_status = $request->input('xml_export_status');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($treatment_code) {
            $result = Qd130Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
                ->where('ma_lk', $treatment_code)
                ->with(['check_hein_card' => function($query) {
                    $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
                }, 'Qd130XmlErrorResult' => function($query) {
                    $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                }, 'Qd130XmlInformation' => function($query) {
                    $query->select('ma_lk', 'exported_at');
                }]);           
        } else {
            // Check and convert date format
            if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
                $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
            }

            if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
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

            $result = Qd130Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
            ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

            // Apply relationships: check_hein_card
            $result = $result->with(['check_hein_card' => function($query) {
                $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
            }]);

            // Apply relationships: Qd130XmlInformation
            $result = $result->with(['Qd130XmlInformation' => function($query) {
                $query->select('ma_lk', 'exported_at', 'export_error');
            }]);

            if ($qd130_xml_error_catalog_id) {
                $qd130XmlErrorCatalog = Qd130XmlErrorCatalog::find($qd130_xml_error_catalog_id);
                if ($qd130XmlErrorCatalog) {
                    $result = $result->whereHas('Qd130XmlErrorResult', function($query) use ($qd130XmlErrorCatalog) {
                        $query->where('xml', $qd130XmlErrorCatalog->xml)
                              ->where('error_code', $qd130XmlErrorCatalog->error_code);
                    });
                }
            } else {
                $result = $result->with(['Qd130XmlErrorResult' => function($query) {
                    $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                }]);
            }
            
            // Apply filter based on xml_filter_status
            if ($xml_filter_status === 'has_error') {
                $result = $result->where(function ($query) {
                    $query->whereHas('Qd130XmlErrorResult')
                          ->orWhereHas('check_hein_card', function ($subQuery) {
                              $subQuery->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
                                       ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
                          });
                });
            } elseif ($xml_filter_status === 'no_error') {
                $result = $result->whereDoesntHave('Qd130XmlErrorResult')
                                 ->whereDoesntHave('check_hein_card', function ($subQuery) {
                                     $subQuery->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
                                              ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
                                 });
            } elseif ($xml_filter_status === 'has_error_critical') {
                $result = $result->whereHas('Qd130XmlErrorResult', function ($query) {
                    $query->where('critical_error', true);
                });
            } elseif ($xml_filter_status === 'has_error_warning') {
                $result = $result->whereHas('Qd130XmlErrorResult', function ($query) {
                    $query->where('critical_error', false);
                })->whereDoesntHave('Qd130XmlErrorResult', function ($query) {
                    $query->where('critical_error', true);
                });
            } elseif ($xml_filter_status === 'has_error_hein_card') {
                $result = $result->whereHas('check_hein_card', function ($query) {
                    $query->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
                          ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
                });
            } elseif ($xml_filter_status === 'has_error_hein_card_without_xml') {
                $result = $result->whereHas('check_hein_card', function ($query) {
                    $query->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
                          ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
                })->whereDoesntHave('Qd130XmlErrorResult');
            } elseif ($xml_filter_status === 'no_error_critical') {
                $result = $result->whereDoesntHave('Qd130XmlErrorResult', function ($query) {
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
                $result = $result->whereHas('Qd130XmlInformation', function ($query) {
                    $query->whereNotNull('exported_at');
                });
            } elseif ($xml_export_status === 'no_export') {
                $result = $result->whereHas('Qd130XmlInformation', function ($query) {
                    $query->whereNull('exported_at');
                });
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
            $tooltip = $result->Qd130XmlInformation && $result->Qd130XmlInformation->exported_at 
                ? $result->Qd130XmlInformation->exported_at
                : ($result->Qd130XmlInformation && $result->Qd130XmlInformation->export_error
                    ? $result->Qd130XmlInformation->export_error
                    : 'Not exported');
            $icon = $result->Qd130XmlInformation && $result->Qd130XmlInformation->export_error
                ? '<i class="fa fa-times-circle" text-warning" title="'.$tooltip.'"></i>'
                : ($result->Qd130XmlInformation && $result->Qd130XmlInformation->exported_at
                    ? '<i class="fa fa-check-circle text-success" title="'.$tooltip.'"></i>'
                    : '<i class="fa fa-file-code-o text-secondary" title="'.$tooltip.'"></i>');
            return $icon;
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
                config('qd130xml.hein_card_invalid.check_code')) || in_array($result->check_hein_card->ma_tracuu, 
                    config('qd130xml.hein_card_invalid.result_code')))) {
                $highlight = true;
            }
            if (!$highlight && $result->Qd130XmlErrorResult->isNotEmpty()) {
                $highlight = true;
            }
            return $highlight ? 'highlight-red' : '';
        })
        ->rawColumns(['exported_at', 'action'])
        ->toJson();
    }

    public function detailXml($ma_lk)
    {
        $xml1 = Qd130Xml1::where('ma_lk', $ma_lk)
        ->firstOrFail();

        return view('bhyt.qd130.detail-xml',  compact('xml1')); 
    }

    public function uploadData(Request $request)
    {
        $request->validate([
            'xmls' => 'required',
            'xmls.*' => 'mimes:xml|max:102400',
        ]);

        // Kiểm tra file
        if ($request->hasFile('xmls')) {
            $files = $request->file('xmls');

            $files = is_array($files) ? $files : [$files]; // Ensure $files is an array

            $errors = [];

            // Chunk the files array into smaller pieces of 100 files each
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

                    // Delete the file after processing
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
        $ma_lk = null; // Khởi tạo ma_lk là null

        // Biến lưu trữ các loại file đã xử lý thành công
        $processedFileTypes = [];

        // Check if macskcb exists in the XML data
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
                    $expectedStructure = XmlStructures::$expectedStructures130[$fileType];
                    if (!validateDataStructure($data, $expectedStructure)) {
                        \Log::error('Invalid data structure for ' . $fileType);
                        return false;
                    }
                    
                    $processedFileTypes[] = $fileType;

                    // Lấy ma_lk từ XML1
                    $ma_lk = (string)$data->MA_LK;

                    $this->qd130XmlService->deleteExistingQd130Xml($ma_lk);

                    $this->qd130XmlService->storeQd130Xml1($data, $fileType);

                    break;
                case 'XML2':
                    $this->qd130XmlService->storeQd130Xml2($data, $fileType);
                    break;
                case 'XML3':
                    $this->qd130XmlService->storeQd130Xml3($data, $fileType);
                    break;
                case 'XML4':
                    $this->qd130XmlService->storeQd130Xml4($data, $fileType);
                    break;
                case 'XML5':
                    $this->qd130XmlService->storeQd130Xml5($data, $fileType);
                    break;
                case 'XML6':
                    $this->qd130XmlService->storeQd130Xml6($data, $fileType);
                    break;
                case 'XML7':
                     $this->qd130XmlService->storeQd130Xml7($data, $fileType);
                    break;
                case 'XML8':
                     $this->qd130XmlService->storeQd130Xml8($data, $fileType);
                    break;
                case 'XML9':
                     $this->qd130XmlService->storeQd130Xml9($data, $fileType);
                    break;
                case 'XML10':
                     $this->qd130XmlService->storeQd130Xml10($data, $fileType);
                    break;
                case 'XML11':
                     $this->qd130XmlService->storeQd130Xml11($data, $fileType);
                    break;
                case 'XML12':
                    
                    break;
                case 'XML13':
                     $this->qd130XmlService->storeQd130Xml13($data, $fileType);
                    break;
                case 'XML14':
                     $this->qd130XmlService->storeQd130Xml14($data, $fileType);
                    break;
                case 'XML15':
                     $this->qd130XmlService->storeQd130Xml15($data, $fileType);
                    break;
                case 'XML16':
                        
                    break;
                case 'XML17':
                    
                    break;
                case 'XML18':
                    
                    break;
                default:
                    \Log::warning('Unknown XML type: ' . $file_hs->LOAIHOSO);
                    break;
            }
        }
            
        if ($ma_lk !== null && !empty($processedFileTypes)) {
            $this->qd130XmlService->storeQd130XmlInfomation($ma_lk, $macskcb, 'import', $soluonghoso);
            $this->qd130XmlService->checkQd130XmlComplete($ma_lk);
            if (config('qd130xml.export_qd130_xml_enabled')) {
                $this->qd130XmlService->exportQd130Xml($ma_lk);
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

        $storagePath = 'public/xml130/';

        // Ensure the storage path exists
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $selectedRecord) {
                // Truy vấn dữ liệu từ cơ sở dữ liệu theo hồ sơ đã chọn
                $xmlData = $this->qd130XmlService->getDataForXmlExport($selectedRecord);

                $formattedDateTime = date('Y.m.d_H.i.s');
                // Tạo tên file XML
                $fileName = $formattedDateTime . '_' . $selectedRecord . '.xml';

                $filePath = storage_path('app/' . $storagePath . $fileName);


                // Lưu XML thành file
                if (file_put_contents($filePath, $xmlData) === false) {
                    \Log::error('Failed to write XML file: ' . $filePath);
                    continue;
                }

                $fileNames[] = $filePath;
            }
        }

        // Tạo file ZIP
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

        // Xóa các file XML sau khi nén
        foreach ($fileNames as $file) {
            unlink($file);
        }

        return response()->json(['success' => true, 'file' => asset('storage/xml130/' . $zipFileName)]);
    }

    public function exportQd130XmlErrors(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $xml_filter_status = $request->input('xml_filter_status');
        $date_type = $request->input('date_type');
        $qd130_xml_error_catalog_id = $request->input('qd130_xml_error_catalog');

        $fileName = 'qd130_error_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Qd130ErrorExport($date_from, $date_to, $xml_filter_status, 
            $date_type, $qd130_xml_error_catalog_id), $fileName);
    }

    public function exportQd130XmlXlsx(Request $request)
    {
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $xml_filter_status = $request->input('xml_filter_status');
        $date_type = $request->input('date_type');
        $qd130_xml_error_catalog_id = $request->input('qd130_xml_error_catalog');
        $xml_export_status = $request->input('xml_export_status');

        $fileName = 'qd130_xml_data_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new Qd130XmlExport($date_from, $date_to, $xml_filter_status, 
            $date_type, $qd130_xml_error_catalog_id, $xml_export_status), $fileName);
    }

    public function deleteXml($ma_lk)
    {

        if ($this->qd130XmlService->deleteQd130XmlAndError($ma_lk)) {
            return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);    
        } ;
        return response()->json(['success' => false, 'message' => 'Incomplete.']);
    }

    public function checkJobStatus(Request $request)
    {
        // Query bảng jobs để kiểm tra số lượng job chưa thực hiện
        $jobsCount = DB::table('jobs')
            ->where('queue', config('qd130xml.queue_name'))
            ->whereNull('reserved_at') // Job chưa được bắt đầu
            ->count();

        // Trả về kết quả dưới dạng JSON
        return response()->json(['jobs_count' => $jobsCount]);
    }
          
}
