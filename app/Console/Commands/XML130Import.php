<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\Qd130XmlService;
use App\Services\XmlStructures;

use App\Jobs\CheckCompleteQd130RecordJob;

class XML130Import extends Command
{
    protected $signature = 'xml130import:day';
    protected $description = 'Import XML130';
    protected $qd130XmlService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Qd130XmlService $qd130XmlService)
    {
        parent::__construct();
        $this->qd130XmlService = $qd130XmlService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        do {
            try {


                $this->info('Processing xml130tt disk');
                $this->importFilesFromDisk('xml130tt');

                $this->info('Processing xml130 disk');
                $this->importFilesFromDisk('xml130');

                $this->info('Processing xml130 google drive disk');
                $this->importFilesFromDisk('xml130GoogleDrive');

                $this->info($this->description);

                sleep(3);
            } catch (\Exception $e) {
                $this->info($e->getMessage());
            }
        } while (true);

        $this->info($this->description);
    }

    protected function importFilesFromDisk($disk)
    {
        try {
            $files = Storage::disk($disk)->allFiles();

            $fileChunks = array_chunk($files, 100);

            foreach ($fileChunks as $chunk) {
                foreach ($chunk as $file) {
                    if (Storage::disk($disk)->mimeType($file) != 'text/xml') {
                        continue;
                    }

                    $ma_lk = null; // Khởi tạo ma_lk là null

                    // Biến lưu trữ các loại file đã xử lý thành công
                    $processedFileTypes = [];

                    $xmldata = simplexml_load_string(Storage::disk($disk)->get($file));

                    // Check if macskcb exists in the XML data
                    if (!isset($xmldata->THONGTINDONVI->MACSKCB) || empty($xmldata->THONGTINDONVI->MACSKCB)) {
                        \Log::error('MACSKCB not found or is empty in XML data');
                        return false;
                    }

                    $macskcb = (string)$xmldata->THONGTINDONVI->MACSKCB;
                    $soluonghoso = count($xmldata->THONGTINHOSO->SOLUONGHOSO);

                    foreach ($xmldata->THONGTINHOSO[0]->DANHSACHHOSO[0]->HOSO[0]->FILEHOSO as $file_hs) {
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

                                $this->info($data->MA_LK);
                                // Lấy ma_lk từ XML1
                                $ma_lk = (string)$data->MA_LK;
                                $processedFileTypes[] = $fileType;

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

                    // Sau khi hoàn thành import hồ sơ thì mới check nghiệp vụ tổng thể liên quan tới hồ sơ đó
                    if ($ma_lk !== null && !empty($processedFileTypes)) {
                        $this->qd130XmlService->storeQd130XmlInfomation($ma_lk, $macskcb, 'import', $soluonghoso);
                        $this->qd130XmlService->checkQd130XmlComplete($ma_lk);
                        if (config('qd130xml.export_qd130_xml_enabled')) {
                            //Kiểm tra nếu là XML thông tuyến và exportable_tt = false thì không thực hiện exportQd130Xml
                            if (!($disk === 'xml130tt' && config('qd130xml.exportable_tt') == false)) {
                                $this->qd130XmlService->exportQd130Xml($ma_lk);
                            }                          
                        } 
                    }

                    // Delete file after import
                    Storage::disk($disk)->delete($file);           
                }
            }

        } catch (Exception $e) {
            \Log::error($e->getMessage());
        }

    }

}