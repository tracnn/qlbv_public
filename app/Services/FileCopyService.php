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
     * Copy file XML tu exportXml3176 sang mot disk cong ngoai, neu cong do dang bat.
     *
     * Moi cong tu kiem co CUA RIENG NO: tat cong nay khong duoc anh huong cong kia. Do la
     * dieu kien co lap ma luong 3176 dat ra.
     *
     * Chua bat thi tra true chu khong phai false: khong lam gi la ket qua DUNG, khong phai loi.
     * Loi copy that thi copy() da bat va ghi log - mot dia mang hong khong duoc lam chet export.
     *
     * @param string $filePath Duong dan tren disk exportXml3176 (vd: 20250129/MACSKCB/file.xml)
     * @param string $khoaCauHinh Khoa trong config/organization.php
     * @param string $diskMacDinh Ten disk dung khi cau hinh khong khai
     * @return bool True neu copy thanh cong HOAC chuc nang chua bat
     */
    private function copyExportXml3176ToDisk($filePath, $khoaCauHinh, $diskMacDinh)
    {
        $config = config($khoaCauHinh, []);

        if (!($config['enabled'] ?? false)) {
            return true;
        }

        $targetDisk = $config['disk'] ?? $diskMacDinh;

        return $this->copy('exportXml3176', $filePath, $targetDisk, basename($filePath));
    }

    /**
     * Copy sang disk Truc du lieu Y Te (disk dung boi TrucDuLieuYTeXmlScan).
     *
     * @param string $filePath Duong dan file tren disk exportXml3176
     * @return bool True neu copy thanh cong hoac chuc nang chua bat
     */
    public function copyExportXml3176ToTrucDuLieuYTe($filePath)
    {
        return $this->copyExportXml3176ToDisk(
            $filePath, 'organization.truc_du_lieu_y_te', 'trucDuLieuYTe'
        );
    }

    /**
     * Copy sang disk Cong du lieu Y Te tinh Dien Bien (disk dung boi
     * CongDuLieuYTeDienBienXmlScan).
     *
     * Truoc day KHONG co ham nay: lenh quet canh mot thu muc ma luong 3176 khong bao gio ghi
     * vao, nen chang copy bi thieu han.
     *
     * @param string $filePath Duong dan file tren disk exportXml3176
     * @return bool True neu copy thanh cong hoac chuc nang chua bat
     */
    public function copyExportXml3176ToCongDuLieuYTeDienBien($filePath)
    {
        return $this->copyExportXml3176ToDisk(
            $filePath, 'organization.cong_du_lieu_y_te_dien_bien', 'congDuLieuYTeDienBien'
        );
    }
}
