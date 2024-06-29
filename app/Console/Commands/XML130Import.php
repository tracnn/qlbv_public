<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\Qd130XmlService;
use App\Services\XmlStructures;

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

                $this->info($this->description);

                sleep(10);
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

            foreach ($files as $file) {
                if (Storage::disk($disk)->mimeType($file) != 'text/xml') {
                    continue;
                }

                $xmldata = simplexml_load_string(Storage::disk($disk)->get($file));

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
                            break;
                        case 'XML7':
                            
                            break;
                        case 'XML8':
                            
                            break;
                        case 'XML9':
                            
                            break;
                        case 'XML10':
                            
                            break;
                        case 'XML11':
                            
                            break;
                        case 'XML12':
                            
                            break;
                        case 'XML13':
                            
                            break;
                        case 'XML14':
                            
                            break;
                        case 'XML15':
                            
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

                    // Delete file after import
                    Storage::disk($disk)->delete($file);
                }
            }
        } catch (Exception $e) {
            \Log::error($e->getMessage());
        }
        

    }

}