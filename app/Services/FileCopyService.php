<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileCopyService
{
    /**
     * Copy file từ disk nguồn sang disk đích.
     *
     * @param string $sourceDisk Tên disk nguồn
     * @param string $sourcePath Đường dẫn file trên disk nguồn (relative)
     * @param string $targetDisk Tên disk đích
     * @param string|null $targetPath Đường dẫn đích; null = giữ nguyên sourcePath
     * @return bool True nếu copy thành công
     */
    public function copy(string $sourceDisk, string $sourcePath, string $targetDisk, $targetPath = null)
    {
        $targetPath = $targetPath ?? $sourcePath;

        try {
            if (!Storage::disk($sourceDisk)->exists($sourcePath)) {
                Log::warning('FileCopyService: file nguồn không tồn tại', [
                    'disk' => $sourceDisk,
                    'path' => $sourcePath,
                ]);
                return false;
            }

            $content = Storage::disk($sourceDisk)->get($sourcePath);

            return Storage::disk($targetDisk)->put($targetPath, $content);
        } catch (\Exception $e) {
            Log::error('FileCopyService: lỗi khi copy file', [
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
                'target_disk' => $targetDisk,
                'target_path' => $targetPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Copy file XML từ exportXml3176 (sau processExportXml) sang disk Trục dữ liệu Y Tế
     * (disk dùng bởi TrucDuLieuYTeXmlScan).
     *
     * @param string $filePath Đường dẫn file trên disk exportXml3176 (vd: 20250129/MACSKCB/file.xml)
     * @return bool True nếu copy thành công hoặc chức năng chưa bật
     */
    public function copyExportXml3176ToTrucDuLieuYTe($filePath)
    {
        $config = config('organization.truc_du_lieu_y_te', []);

        if (!($config['enabled'] ?? false)) {
            return true;
        }

        $targetDisk = $config['disk'] ?? 'trucDuLieuYTe';

        return $this->copy('exportXml3176', $filePath, $targetDisk, basename($filePath));
    }
}
