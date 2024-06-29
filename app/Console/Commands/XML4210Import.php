<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Jobs\JobKtTheBHYT;
use App\Services\XMLService;

class XML4210Import extends Command
{
    protected $signature = 'xml4210import:day';
    protected $description = 'Import XML4210';
    protected $xmlService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(XMLService $xmlService)
    {
        parent::__construct();
        $this->xmlService = $xmlService;
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


                $this->info('Processing xml4210tt disk');
                $this->importFilesFromDisk('xml4210tt');

                $this->info('Processing xml4210 disk');
                $this->importFilesFromDisk('xml4210');

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
        $files = Storage::disk($disk)->allFiles();

        foreach ($files as $file) {
            if (Storage::disk($disk)->mimeType($file) != 'text/xml') {
                continue;
            }

            $xmldata = simplexml_load_string(Storage::disk($disk)->get($file));

            foreach ($xmldata->THONGTINHOSO[0]->DANHSACHHOSO[0]->HOSO[0]->FILEHOSO as $value_file_hs) {
                $fileContent = base64_decode($value_file_hs->NOIDUNGFILE);
                $data = simplexml_load_string($fileContent);

                $ma_lk = (string)$data->MA_LK;

                switch ($value_file_hs->LOAIHOSO) {
                    case 'XML1':
                        $this->xmlService->saveXML1($data);

                        // Lấy các thông tin cần thiết cho job từ $data
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
                            JobKtTheBHYT::dispatch($params)
                                ->onQueue('JobKtTheBHYT');
                        }

                        $this->info('Imported MA_LK: ' . $ma_lk);

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

                // Delete file after import
                Storage::disk($disk)->delete($file);
            }
        }
    }

}