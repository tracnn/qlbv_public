<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\Xml3176Service;
use App\Services\XmlStructures;

use App\Jobs\CheckCompleteXml3176RecordJob;

class XML3176Import extends Command
{
    protected $signature = 'xml3176import:day';
    protected $description = 'Import XML3176';
    protected $xml3176Service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Xml3176Service $xml3176Service)
    {
        parent::__construct();
        $this->xml3176Service = $xml3176Service;
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


                $this->info('Processing xml3176tt disk');
                $this->importFilesFromDisk('xml3176tt');

                $this->info('Processing xml3176 disk');
                $this->importFilesFromDisk('xml3176');

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
                                $expectedStructure = XmlStructures::$expectedStructures3176[$fileType];
                                if (!validateDataStructure($data, $expectedStructure)) {
                                    \Log::error('Invalid data structure for ' . $fileType);
                                    return false;
                                }

                                $this->info($data->MA_LK);
                                // Lấy ma_lk từ XML1
                                $ma_lk = (string)$data->MA_LK;
                                $processedFileTypes[] = $fileType;

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
                        $this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);
                        if (!config('organization.xml_3176_not_check')) {
                            $this->xml3176Service->checkXml3176Complete($ma_lk);
                           
                            if (config('xml3176.export_xml3176_enabled')) {
                                //Kiểm tra nếu là XML thông tuyến và exportable_tt = false thì không thực hiện exportXml3176
                                if (!($disk === 'xml3176tt' && config('xml3176.exportable_tt') == false)) {
                                    $this->xml3176Service->exportXml3176($ma_lk);
                                }                          
                            }
                        }
                    }

                    // Delete file after import
                    Storage::disk($disk)->delete($file);           
                }
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

    }

}
