<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Services\TrucDuLieuYTeXmlSubmitService;

class TrucDuLieuYTeXmlScan extends Command
{
    protected $signature = 'truc-du-lieu-y-te:scan {--disk=}';
    protected $description = 'Quét thư mục XML và gửi lên Trục dữ liệu Y Tế';
    protected $submitService;

    public function __construct(TrucDuLieuYTeXmlSubmitService $submitService)
    {
        parent::__construct();
        $this->submitService = $submitService;
    }

    public function handle()
    {
        $config = Config::get('organization.truc_du_lieu_y_te', []);

        if (!($config['enabled'] ?? false)) {
            $this->warn('Chức năng gửi dữ liệu lên Trục dữ liệu Y Tế chưa được bật.');
            return;
        }

        $disk = $this->option('disk') ?? $config['disk'] ?? 'trucDuLieuYTe';
        $sleepInterval = (int) ($config['scan_sleep_interval'] ?? 60);

        $this->info("Bắt đầu quét thư mục XML trên disk: {$disk}");
        $this->info("Sleep interval: {$sleepInterval} giây");

        do {
            try {
                $this->info('Processing trucDuLieuYTe disk');
                $this->submitFilesFromDisk($disk);
                $this->info($this->description);
                sleep($sleepInterval);
            } catch (\Exception $e) {
                $this->error('Lỗi: ' . $e->getMessage());
                Log::error('Truc Du Lieu Y Te XML scan error', [
                    'disk' => $disk,
                    'error' => $e->getMessage(),
                ]);
                sleep($sleepInterval);
            }
        } while (true);
    }

    protected function submitFilesFromDisk($disk)
    {
        try {
            $files = Storage::disk($disk)->allFiles();
            $fileChunks = array_chunk($files, 50);

            foreach ($fileChunks as $chunk) {
                foreach ($chunk as $file) {
                    // Kiểm tra extension
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if ($extension !== 'xml') {
                        continue;
                    }

                    // Bỏ qua file trong thư mục success và error
                    $normalizedPath = str_replace('\\', '/', $file);
                    if (strpos($normalizedPath, 'success/') !== false || 
                        strpos($normalizedPath, 'error/') !== false) {
                        continue;
                    }

                    $this->info("Processing: {$file}");

                    try {
                        // Đọc nội dung XML
                        $xmlContent = Storage::disk($disk)->get($file);

                        if (empty($xmlContent)) {
                            $this->warn("File rỗng: {$file}");
                            $this->moveFileToError($disk, $file, 'File XML rỗng');
                            continue;
                        }

                        // Gửi XML lên Trục
                        $result = $this->submitService->submitXml($xmlContent);

                        // Kiểm tra kết quả
                        if (isset($result['maGiaoDich']) || 
                            (isset($result['statusCode']) && $result['statusCode'] == 200) ||
                            (isset($result['success']) && $result['success'] === true)) {
                            
                            $this->info("Thành công: {$file}");
                            Log::info('Truc Du Lieu Y Te XML submitted', [
                                'file' => $file,
                                'ma_giao_dich' => $result['maGiaoDich'] ?? null,
                            ]);

                            // Di chuyển file vào thư mục success
                            $this->moveFileToSuccess($disk, $file);
                        } else {
                            $errorMessage = $result['thongDiep'] ?? $result['message'] ?? $result['error'] ?? 'Unknown error';
                            $this->error("Lỗi submit {$file}: {$errorMessage}");
                            Log::error('Truc Du Lieu Y Te XML submit failed', [
                                'file' => $file,
                                'error' => $errorMessage,
                                'result' => $result,
                            ]);

                            // Di chuyển file vào thư mục error
                            $this->moveFileToError($disk, $file, $errorMessage);
                        }
                    } catch (\Exception $e) {
                        $this->error("Lỗi xử lý {$file}: " . $e->getMessage());
                        Log::error('Truc Du Lieu Y Te XML process error', [
                            'file' => $file,
                            'error' => $e->getMessage(),
                        ]);

                        // Di chuyển file vào thư mục error
                        $this->moveFileToError($disk, $file, $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Truc Du Lieu Y Te scan disk error: ' . $e->getMessage());
        }
    }

    /**
     * Di chuyển file vào thư mục success
     */
    protected function moveFileToSuccess($disk, $file)
    {
        try {
            // Kiểm tra file tồn tại
            if (!Storage::disk($disk)->exists($file)) {
                Log::warning('Truc Du Lieu Y Te: File không tồn tại khi move to success', [
                    'file' => $file,
                    'disk' => $disk,
                ]);
                return;
            }

            $fileName = basename($file);
            $targetPath = 'success/' . $fileName;

            // Tạo thư mục success nếu chưa tồn tại
            if (!Storage::disk($disk)->exists('success')) {
                Storage::disk($disk)->makeDirectory('success');
            }

            // Trên Laravel 5.5, đặc biệt Windows, move() có thể không hoạt động đúng
            // Sử dụng copy + delete để đảm bảo file được xử lý
            $content = Storage::disk($disk)->get($file);
            Storage::disk($disk)->put($targetPath, $content);
            
            // Xóa file gốc
            Storage::disk($disk)->delete($file);

            // Verify file gốc đã bị xóa
            if (Storage::disk($disk)->exists($file)) {
                Log::error('Truc Du Lieu Y Te: File gốc vẫn tồn tại sau khi delete', [
                    'file' => $file,
                    'target' => $targetPath,
                ]);
                // Thử xóa lại
                try {
                    Storage::disk($disk)->delete($file);
                } catch (\Exception $e) {
                    Log::error('Truc Du Lieu Y Te: Không thể xóa file gốc lần 2', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Di chuyển file vào success: {$targetPath}");
            Log::info('Truc Du Lieu Y Te: File moved to success', [
                'file' => $file,
                'target' => $targetPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Truc Du Lieu Y Te move file to success error', [
                'file' => $file,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cố gắng xóa file gốc để tránh lặp lại
            try {
                if (Storage::disk($disk)->exists($file)) {
                    Storage::disk($disk)->delete($file);
                    Log::info('Truc Du Lieu Y Te: Đã xóa file gốc sau khi move fail', ['file' => $file]);
                }
            } catch (\Exception $deleteEx) {
                Log::error('Truc Du Lieu Y Te: Không thể xóa file gốc sau khi move fail', [
                    'file' => $file,
                    'error' => $deleteEx->getMessage(),
                ]);
            }
        }
    }

    /**
     * Di chuyển file vào thư mục error và tạo file thông điệp lỗi
     */
    protected function moveFileToError($disk, $file, $errorMessage)
    {
        try {
            // Kiểm tra file tồn tại
            if (!Storage::disk($disk)->exists($file)) {
                Log::warning('Truc Du Lieu Y Te: File không tồn tại khi move to error', [
                    'file' => $file,
                    'disk' => $disk,
                ]);
                return;
            }

            $fileName = basename($file);
            $targetPath = 'error/' . $fileName;
            $errorFilePath = 'error/' . $fileName . '.error.txt';

            // Tạo thư mục error nếu chưa tồn tại
            if (!Storage::disk($disk)->exists('error')) {
                Storage::disk($disk)->makeDirectory('error');
            }

            // Trên Laravel 5.5, đặc biệt Windows, move() có thể không hoạt động đúng
            // Sử dụng copy + delete để đảm bảo file được xử lý
            $content = Storage::disk($disk)->get($file);
            Storage::disk($disk)->put($targetPath, $content);
            
            // Xóa file gốc
            Storage::disk($disk)->delete($file);

            // Tạo file thông điệp lỗi
            $errorContent = "Thời gian: " . date('Y-m-d H:i:s') . "\n";
            $errorContent .= "File: {$fileName}\n";
            $errorContent .= "Lỗi: {$errorMessage}\n";
            Storage::disk($disk)->put($errorFilePath, $errorContent);

            // Verify file gốc đã bị xóa
            if (Storage::disk($disk)->exists($file)) {
                Log::error('Truc Du Lieu Y Te: File gốc vẫn tồn tại sau khi delete', [
                    'file' => $file,
                    'target' => $targetPath,
                ]);
                // Thử xóa lại
                try {
                    Storage::disk($disk)->delete($file);
                } catch (\Exception $e) {
                    Log::error('Truc Du Lieu Y Te: Không thể xóa file gốc lần 2', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Di chuyển file vào error: {$targetPath}");
            Log::info('Truc Du Lieu Y Te: File moved to error', [
                'file' => $file,
                'target' => $targetPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Truc Du Lieu Y Te move file to error error', [
                'file' => $file,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cố gắng xóa file gốc để tránh lặp lại
            try {
                if (Storage::disk($disk)->exists($file)) {
                    Storage::disk($disk)->delete($file);
                    Log::info('Truc Du Lieu Y Te: Đã xóa file gốc sau khi move fail', ['file' => $file]);
                }
            } catch (\Exception $deleteEx) {
                Log::error('Truc Du Lieu Y Te: Không thể xóa file gốc sau khi move fail', [
                    'file' => $file,
                    'error' => $deleteEx->getMessage(),
                ]);
            }
        }
    }
}
