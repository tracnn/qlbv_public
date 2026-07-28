<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\Xml3176Service;
use App\Services\Xml3176\Xml3176Importer;

class XML3176Import extends Command
{
    protected $signature = 'xml3176import:day';
    protected $description = 'Import XML3176';
    protected $xml3176Service;
    protected $importer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Xml3176Service $xml3176Service, Xml3176Importer $importer)
    {
        parent::__construct();
        $this->xml3176Service = $xml3176Service;
        $this->importer = $importer;
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
        // Chinh sach rieng cua luong quet dia O LAI DAY - importer khong biet gi ve $disk.
        $choPhepXuat = !($disk === 'xml3176tt' && config('xml3176.exportable_tt') == false);

        try {
            $files = Storage::disk($disk)->allFiles();
        } catch (\Exception $e) {
            \Log::error('Khong doc duoc thu muc ' . $disk . ': ' . $e->getMessage());

            return;
        }

        foreach ($files as $file) {
            // Moi file mot luot doc lap: file hong thi BO QUA FILE DO va di tiep.
            // Truoc day dung 'return false' nen mot file hong lam dung ca luot quet,
            // ma no lai khong bi xoa, nen luot sau lai vap dung no - tac vinh vien.
            try {
                if (Storage::disk($disk)->mimeType($file) != 'text/xml') {
                    continue;
                }

                $kq = $this->importer->nhapTuChuoi(
                    Storage::disk($disk)->get($file),
                    ['cho_phep_xuat' => $choPhepXuat]
                );

                if (!$kq->thanhCong) {
                    // Giu nguyen hanh vi hien tai: KHONG xoa file hong, de con du lieu
                    // ma dieu tra. Giai doan 2 chuyen no sang thu muc rieng.
                    \Log::error('Import that bai ' . $disk . '/' . $file . ': ' . $kq->lyDoThatBai);
                    continue;
                }

                $this->info($kq->maLk);

                Storage::disk($disk)->delete($file);
            } catch (\Exception $e) {
                \Log::error('Loi khi xu ly ' . $disk . '/' . $file . ': ' . $e->getMessage());
            }
        }
    }

}
