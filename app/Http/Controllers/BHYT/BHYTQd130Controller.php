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

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($treatment_code) {
            $result = Qd130Xml1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the_bhyt', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
            ->where('ma_lk', $treatment_code)
            ->with(['check_hein_card' => function($query) {
                $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
            }])
            ->with(['Qd130XmlErrorResult' => function($query) {
                $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
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

            // Apply relationships
            $result = $result->with(['check_hein_card' => function($query) {
                $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
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
                        $subQuery->where('ma_kiemtra', '<>', '00')
                        ->orWhere('ma_tracuu', '<>', '000');
                    });
                });
            } elseif ($xml_filter_status === 'no_error') {
                $result = $result->whereDoesntHave('Qd130XmlErrorResult')
                ->whereDoesntHave('check_hein_card', function ($subQuery) {
                    $subQuery->where('ma_kiemtra', '<>', '00')
                    ->orWhere('ma_tracuu', '<>', '000');
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
        ->addColumn('action', function ($result) {
            return '<a href="' . route('insurance.check-card.search',['card-number' => $result->ma_the_bhyt, 'name' => $result->ho_ten, 'birthday' => dob($result->ngay_sinh,0,8)]) . '" class="btn btn-sm btn-success" target="_blank"><span class="glyphicon glyphicon-check"></span> Tra thẻ</a>
                <a href="javascript:void(0);" onclick="deleteXML(\'' . $result->ma_lk . '\');" class="btn btn-sm btn-danger">
                                    <span class="glyphicon glyphicon-trash"></span> Xóa</a>
                <a href="' .route('treatment-result.search',['treatment_code'=>$result->ma_lk]) .'" class="btn btn-sm btn-primary" target="_blank">
                                    <span class="glyphicon glyphicon-envelope"></span> EMR</a>';
        })
        ->setRowClass(function ($result) {
            $highlight = false;
            if ($result->check_hein_card && ($result->check_hein_card->ma_kiemtra !== '00' || $result->check_hein_card->ma_tracuu !== '000')) {
                $highlight = true;
            }
            if (!$highlight && $result->Qd130XmlErrorResult->isNotEmpty()) {
                $highlight = true;
            }
            return $highlight ? 'highlight-red' : '';
        })
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

            foreach ($files as $file) {
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
        foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs) {
            $fileContent = base64_decode($file_hs->NOIDUNGFILE);
            $data = simplexml_load_string($fileContent);

            $fileType = (string)$file_hs->LOAIHOSO;

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
                    $this->qd130XmlService->storeQd130Xml1($data);
                    break;
                case 'XML2':
                    $this->qd130XmlService->storeQd130Xml2($data);
                    break;
                case 'XML3':
                    $this->qd130XmlService->storeQd130Xml3($data);
                    break;
                case 'XML4':
                    $this->qd130XmlService->storeQd130Xml4($data);
                    break;
                case 'XML5':
                    $this->qd130XmlService->storeQd130Xml5($data);
                    break;
                case 'XML6':
                    $this->qd130XmlService->storeQd130Xml6($data);
                    break;
                case 'XML7':
                     $this->qd130XmlService->storeQd130Xml7($data);
                    break;
                case 'XML8':
                     $this->qd130XmlService->storeQd130Xml8($data);
                    break;
                case 'XML9':
                     $this->qd130XmlService->storeQd130Xml9($data);
                    break;
                case 'XML10':
                     $this->qd130XmlService->storeQd130Xml10($data);
                    break;
                case 'XML11':
                     $this->qd130XmlService->storeQd130Xml11($data);
                    break;
                case 'XML12':
                    
                    break;
                case 'XML13':
                     $this->qd130XmlService->storeQd130Xml13($data);
                    break;
                case 'XML14':
                     $this->qd130XmlService->storeQd130Xml14($data);
                    break;
                case 'XML15':
                     $this->qd130XmlService->storeQd130Xml15($data);
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

                $formattedDateTime = date('d.m.Y_H.i.s');
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
        $zipFileName = 'exported_xml_' . date('d.m.Y_H.i.s') . '.zip';
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
}
