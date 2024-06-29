<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\XMLService;
use App\Jobs\jobKtTheBHYT;

class UploadXML extends Controller
{
    protected $xmlService;

    public function __construct(XMLService $xmlService)
    {
        $this->xmlService = $xmlService;
    }

    public function uploadXML()
    {
        return view('system.upload-xml.index');
    }
    
    public function doUploadXML(Request $request)
    {
        $request->validate([
            'xmls' => 'required',
            'xmls.*' => 'mimes:xml|max:102400',
        ]);

        // Kiểm tra file
        if ($request->hasFile('xmls')) {
            $files = $request->file('xmls');

            if (!is_array($files)) {
                $files = [$files]; // Nếu không phải mảng, chuyển thành mảng
            }

            foreach ($files as $file) {
                $filePath = storage_path('app/uploads');
                $fileName = $file->getClientOriginalName();
                $file->move($filePath, $fileName);
                $xmldata = simplexml_load_file($filePath . '/' . $fileName);

                foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $value_file_hs) {
                    $fileContent = base64_decode($value_file_hs->NOIDUNGFILE);
                    $data = simplexml_load_string($fileContent);

                    switch ($value_file_hs->LOAIHOSO) {
                        case 'XML1':
                            $this->xmlService->saveXML1($data);

	                        $ngaySinh = (string)$data->NGAY_SINH;
	                        if (strlen($ngaySinh) === 8) {
	                            $ngaySinhFormatted = \DateTime::createFromFormat('Ymd', $ngaySinh)->format('d/m/Y');
	                        } elseif (strlen($ngaySinh) === 12) {
	                            $ngaySinhFormatted = \DateTime::createFromFormat('YmdHi', $ngaySinh)->format('d/m/Y');
	                        } else {
	                            $ngaySinhFormatted = null; // Hoặc xử lý theo cách khác nếu cần
	                        }

                            $maThes = explode(';', (string)$data->MA_THE);
                            $maDKBDs = explode(';', (string)$data->MA_DKBD);

                            foreach ($maThes as $index => $maThe) {
                                $maDKBD = isset($maDKBDs[$index]) ? $maDKBDs[$index] : '';

                                $params = [
                                    'maThe' => $maThe,
                                    'hoTen' => (string)$data->HO_TEN,
                                    'ngaySinh' => $ngaySinhFormatted,
                                    'ma_lk' => (string)$data->MA_LK,
                                    'maCSKCB' => $maDKBD,
                                    'gioiTinh' => (string)$data->GIOI_TINH,
                                    // Thêm các thông tin khác nếu cần
                                ];

                                // Dispatch job
                                jobKtTheBHYT::dispatch($params)
                                ->onQueue('JobKtTheBHYT');
                            }

                            break;
                        case 'XML2':
                            $this->xmlService->saveXML2($data);
                            break;
                        case 'XML3':
                            $this->xmlService->saveXML3($data);
                            break;
                        case 'XML4':
                            $this->xmlService->saveXML4($data);
                            break;
                        case 'XML5':
                            $this->xmlService->saveXML5($data);
                            break;
                        default:
                            break;
                    }
                }
            }

            return response()->json(['message' => 'File uploaded and processed successfully.'], 200);
        }

        return response()->json(['message' => 'File not uploaded.'], 400);
    }

}
