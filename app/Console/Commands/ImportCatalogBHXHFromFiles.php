<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CatalogImportService;
use Illuminate\Support\Facades\Storage;

class ImportCatalogBHXHFromFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importCatalogBHXH:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from files in a specific directory';
    protected $importService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CatalogImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
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

                $this->info($this->description);
                $this->importFilesFromDisk('CatalogBHXH');

                sleep(10);
            } catch (\Exception $e) {
                $this->info($e->getMessage());
            }
        } while (true);

        $this->info($this->description);
    }

    private function importFilesFromDisk($disk)
    {
        $files = Storage::disk($disk)->files();
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension == 'xls' || $extension == 'xlsx') {
                $this->importService->import(Storage::disk($disk)->path($file));
                Storage::disk($disk)->delete($file); // Xóa file sau khi import thành công
            } else {
                $this->error('Unsupported file type: ' . $file);
            }
        }
    }
}
