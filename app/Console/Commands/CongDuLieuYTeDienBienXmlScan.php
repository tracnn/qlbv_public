<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Services\CongDuLieuYTeDienBienXmlSubmitService;

class CongDuLieuYTeDienBienXmlScan extends Command
{
    protected $signature = 'cong-du-lieu-y-te-dien-bien:scan {--disk=}';
    protected $description = 'Quét thư mục XML và gửi lên Cổng dữ liệu Y tế tỉnh Điện Biên';
    protected $submitService;

    public function __construct(CongDuLieuYTeDienBienXmlSubmitService $submitService)
    {
        parent::__construct();
        $this->submitService = $submitService;
    }

    public function handle()
    {
        $config = Config::get('organization.cong_du_lieu_y_te_dien_bien', []);

        if (!($config['enabled'] ?? false)) {
            $this->warn('Chức năng gửi dữ liệu lên Cổng dữ liệu Y tế tỉnh Điện Biên chưa được bật.');
            return;
        }

        $disk = $this->option('disk') ?? $config['disk'] ?? 'congDuLieuYTeDienBien';
        $sleepInterval = (int) ($config['scan_sleep_interval'] ?? 60);

        $this->info("Bắt đầu quét thư mục XML trên disk: {$disk}");
        $this->info("Sleep interval: {$sleepInterval} giây");

        do {
            try {
                $this->info('Processing congDuLieuYTeDienBien disk');
                $this->submitFilesFromDisk($disk);
                $this->info($this->description);
                sleep($sleepInterval);
            } catch (\Exception $e) {
                $this->error('Lỗi: ' . $e->getMessage());
                Log::error('Cong Du Lieu Y Te Dien Bien XML scan error', [
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

                        // Gửi XML lên Cổng dữ liệu Y tế tỉnh Điện Biên
                        $result = $this->submitService->submitXml($xmlContent);

                        // Kiểm tra kết quả
                        // Status 200 + trangThai = 1: Hồ sơ đúng
                        // Status 201 + trangThai = 2: Hồ sơ lỗi
                        if (isset($result['success']) && $result['success'] === true) {
                            $this->info("Thành công: {$file}");
                            Log::info('Cong Du Lieu Y Te Dien Bien XML submitted', [
                                'file' => $file,
                                'ma_giao_dich' => $result['maGiaoDich'] ?? null,
                                'trang_thai' => $result['trangThai'] ?? null,
                            ]);

                            // Di chuyển file vào thư mục success
                            $this->moveFileToSuccess($disk, $file);
                        } else {
                            $errorMessage = $result['maLoi'] ?? $result['message'] ?? $result['error'] ?? 'Unknown error';
                            $this->error("Lỗi submit {$file}: {$errorMessage}");
                            Log::error('Cong Du Lieu Y Te Dien Bien XML submit failed', [
                                'file' => $file,
                                'error' => $errorMessage,
                                'ma_loi' => $result['maLoi'] ?? null,
                                'trang_thai' => $result['trangThai'] ?? null,
                                'result' => $result,
                            ]);

                            // Di chuyển file vào thư mục error
                            $this->moveFileToError($disk, $file, $errorMessage);
                        }
                    } catch (\Exception $e) {
                        $this->error("Lỗi xử lý {$file}: " . $e->getMessage());
                        Log::error('Cong Du Lieu Y Te Dien Bien XML process error', [
                            'file' => $file,
                            'error' => $e->getMessage(),
                        ]);

                        // Di chuyển file vào thư mục error
                        $this->moveFileToError($disk, $file, $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Cong Du Lieu Y Te Dien Bien scan disk error: ' . $e->getMessage());
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
                Log::warning('Cong Du Lieu Y Te Dien Bien: File không tồn tại khi move to success', [
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
                Log::error('Cong Du Lieu Y Te Dien Bien: File gốc vẫn tồn tại sau khi delete', [
                    'file' => $file,
                    'target' => $targetPath,
                ]);
                // Thử xóa lại
                try {
                    Storage::disk($disk)->delete($file);
                } catch (\Exception $e) {
                    Log::error('Cong Du Lieu Y Te Dien Bien: Không thể xóa file gốc lần 2', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Di chuyển file vào success: {$targetPath}");
            Log::info('Cong Du Lieu Y Te Dien Bien: File moved to success', [
                'file' => $file,
                'target' => $targetPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Cong Du Lieu Y Te Dien Bien move file to success error', [
                'file' => $file,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cố gắng xóa file gốc để tránh lặp lại
            try {
                if (Storage::disk($disk)->exists($file)) {
                    Storage::disk($disk)->delete($file);
                    Log::info('Cong Du Lieu Y Te Dien Bien: Đã xóa file gốc sau khi move fail', ['file' => $file]);
                }
            } catch (\Exception $deleteEx) {
                Log::error('Cong Du Lieu Y Te Dien Bien: Không thể xóa file gốc sau khi move fail', [
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
                Log::warning('Cong Du Lieu Y Te Dien Bien: File không tồn tại khi move to error', [
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
                Log::error('Cong Du Lieu Y Te Dien Bien: File gốc vẫn tồn tại sau khi delete', [
                    'file' => $file,
                    'target' => $targetPath,
                ]);
                // Thử xóa lại
                try {
                    Storage::disk($disk)->delete($file);
                } catch (\Exception $e) {
                    Log::error('Cong Du Lieu Y Te Dien Bien: Không thể xóa file gốc lần 2', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Di chuyển file vào error: {$targetPath}");
            Log::info('Cong Du Lieu Y Te Dien Bien: File moved to error', [
                'file' => $file,
                'target' => $targetPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Cong Du Lieu Y Te Dien Bien move file to error error', [
                'file' => $file,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cố gắng xóa file gốc để tránh lặp lại
            try {
                if (Storage::disk($disk)->exists($file)) {
                    Storage::disk($disk)->delete($file);
                    Log::info('Cong Du Lieu Y Te Dien Bien: Đã xóa file gốc sau khi move fail', ['file' => $file]);
                }
            } catch (\Exception $deleteEx) {
                Log::error('Cong Du Lieu Y Te Dien Bien: Không thể xóa file gốc sau khi move fail', [
                    'file' => $file,
                    'error' => $deleteEx->getMessage(),
                ]);
            }
        }
    }
}
