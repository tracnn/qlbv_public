<?php

declare(strict_types=1);

namespace App\Services;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service để merge nhiều file PDF thành một file
 *
 * LƯU Ý: FPDI có giới hạn khi merge PDF:
 * - Chữ ký số (digital signature) sẽ mất tính hợp lệ sau khi merge
 * - Tuy nhiên, HÌNH ẢNH của chữ ký (appearance) sẽ được giữ lại nếu nó đã được
 *   embed trong content stream của PDF
 * - Nếu hình ảnh chữ ký được lưu dưới dạng annotation riêng biệt, có thể bị mất
 *
 * Để đảm bảo hiển thị tốt nhất:
 * - PDF gốc nên có signature appearance đã được flatten
 * - Hoặc signature được render sẵn trong content layer
 */
class PdfMergeService
{
    /**
     * Merge nhiều file PDF từ local paths
     *
     * @param array $filePaths Mảng các đường dẫn file PDF local
     * @param array $options Tùy chọn merge:
     *   - 'preserve_orientation' => true/false (giữ nguyên orientation của từng page)
     *   - 'compression' => true/false (nén output PDF)
     * @return string PDF content dưới dạng binary string
     * @throws Exception
     */
    public function mergeFromLocalFiles(array $filePaths, array $options = []): string
    {
        $preserveOrientation = $options['preserve_orientation'] ?? true;
        $compression = $options['compression'] ?? false;

        try {
            $pdf = new Fpdi();

            // Enable compression nếu cần
            if ($compression) {
                $pdf->SetCompression(true);
            }

            foreach ($filePaths as $index => $filePath) {
                if (!file_exists($filePath)) {
                    Log::warning("PDF file not found: {$filePath}");
                    continue;
                }

                try {
                    // Set source file
                    $pageCount = $pdf->setSourceFile($filePath);

                    // Import từng page
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);

                        // Xác định orientation
                        if ($preserveOrientation) {
                            $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        } else {
                            $pdf->AddPage();
                        }

                        // Use template - đây là bước import visual content
                        // NOTE: Signature appearance sẽ được giữ nếu nó nằm trong content stream
                        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);
                    }

                    $fileNumber = $index + 1;
                    $totalFiles = count($filePaths);
                    Log::info("Merged PDF file {$fileNumber}/{$totalFiles}: {$filePath} ({$pageCount} pages)");

                } catch (Exception $e) {
                    Log::error("Error processing PDF file {$filePath}: " . $e->getMessage());
                    // Continue với file tiếp theo thay vì throw exception
                    continue;
                }
            }

            // Output PDF dưới dạng string
            return $pdf->Output('S');

        } catch (Exception $e) {
            Log::error('PDF Merge Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to merge PDF files: ' . $e->getMessage());
        }
    }

    /**
     * Merge nhiều file PDF từ FTP server
     *
     * @param array $ftpPaths Mảng các đường dẫn file PDF trên FTP
     * @param FtpService $ftpService Instance của FtpService đã connect
     * @param string $tempDir Thư mục tạm để download files
     * @param array $options Tùy chọn merge (giống mergeFromLocalFiles)
     * @return string PDF content dưới dạng binary string
     * @throws Exception
     */
    public function mergeFromFtpFiles(
        array $ftpPaths,
        FtpService $ftpService,
        string $tempDir,
        array $options = []
    ): string {
        // Đảm bảo temp directory tồn tại
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $localFiles = [];

        try {
            // Download tất cả files từ FTP
            foreach ($ftpPaths as $ftpPath) {
                $ftpPath = str_replace('\\', '/', $ftpPath);
                $localPath = $tempDir . '/' . basename($ftpPath);

                try {
                    $ftpService->download($ftpPath, $localPath);
                    $localFiles[] = $localPath;
                    Log::info("Downloaded from FTP: {$ftpPath} -> {$localPath}");
                } catch (Exception $e) {
                    Log::error("Failed to download from FTP: {$ftpPath} - " . $e->getMessage());
                    continue;
                }
            }

            if (empty($localFiles)) {
                throw new Exception('No PDF files downloaded successfully from FTP');
            }

            // Merge các file local
            $mergedPdf = $this->mergeFromLocalFiles($localFiles, $options);

            return $mergedPdf;

        } finally {
            // Cleanup: xóa các file tạm
            foreach ($localFiles as $localFile) {
                if (file_exists($localFile)) {
                    @unlink($localFile);
                }
            }
        }
    }

    /**
     * Merge PDF từ binary content
     *
     * @param array $pdfContents Mảng các PDF content (binary string)
     * @param array $options Tùy chọn merge
     * @return string PDF content dưới dạng binary string
     * @throws Exception
     */
    public function mergeFromContents(array $pdfContents, array $options = []): string
    {
        $preserveOrientation = $options['preserve_orientation'] ?? true;
        $compression = $options['compression'] ?? false;

        try {
            $pdf = new Fpdi();

            if ($compression) {
                $pdf->SetCompression(true);
            }

            foreach ($pdfContents as $index => $content) {
                try {
                    // Create stream reader from content
                    $streamReader = StreamReader::createByString($content);
                    $pageCount = $pdf->setSourceFile($streamReader);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);

                        if ($preserveOrientation) {
                            $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        } else {
                            $pdf->AddPage();
                        }

                        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);
                    }

                    $contentNumber = $index + 1;
                    $totalContents = count($pdfContents);
                    Log::info("Merged PDF content {$contentNumber}/{$totalContents} ({$pageCount} pages)");

                } catch (Exception $e) {
                    Log::error("Error processing PDF content #{$index}: " . $e->getMessage());
                    continue;
                }
            }

            return $pdf->Output('S');

        } catch (Exception $e) {
            Log::error('PDF Merge Error: ' . $e->getMessage());
            throw new Exception('Failed to merge PDF contents: ' . $e->getMessage());
        }
    }

    /**
     * Kiểm tra xem PDF có chứa signature fields không
     * NOTE: Đây chỉ là estimated check, không phải 100% accurate
     *
     * @param string $pdfPath Đường dẫn file PDF
     * @return bool
     */
    public function hasSignatureFields(string $pdfPath): bool
    {
        try {
            $content = file_get_contents($pdfPath);

            // Tìm kiếm signature field indicators trong PDF content
            $hasSignature = (
                strpos($content, '/Type/Sig') !== false ||
                strpos($content, '/FT/Sig') !== false ||
                strpos($content, '/SubFilter/adbe.pkcs7') !== false
            );

            return $hasSignature;
        } catch (Exception $e) {
            Log::warning("Cannot check signature fields: " . $e->getMessage());
            return false;
        }
    }
}
