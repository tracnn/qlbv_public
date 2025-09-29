<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Services\FtpService;

class PdfFlipController extends Controller
{
    /**
     * Hiển thị trang flip PDF với xác thực token (thay thế viewMergePdfByToken)
     */
    public function show(Request $request)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                abort(400, 'Thiếu token');
            }

            $decrypted = Crypt::decryptString($token);
            [$treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);

            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }  

            // Tạo mergeId từ treatmentCode
            $mergeId = md5($treatmentCode . $createdAt . $expiresIn);
            
            // Tạo link PDF đã mã hóa cho flip viewer
            $tokenEncrypted = Crypt::encryptString("{$treatmentCode}|{$createdAt}|{$expiresIn}");
            $pdfUrl = route('pdf.flip.file', ['mergeId' => $mergeId, 'token' => $tokenEncrypted]);
            
            return view('pdf.flip', compact('pdfUrl'));
            
        } catch (\Exception $e) {
            abort(403, 'Token không hợp lệ');
        }
    }

    /**
     * Stream PDF đã gộp ra cho pdf.js (thay thế mergePdfFilesSecure)
     */
    public function file(Request $request, $mergeId)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                return response()->json(['error' => 'Thiếu token'], 400);
            }

            $decrypted = Crypt::decryptString($token);
            [$treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);

            $expiredAt = \Carbon\Carbon::createFromTimestamp($createdAt)->addSeconds($expiresIn);
            if (now()->greaterThan($expiredAt)) {
                return abort(403, 'Đã hết thời hạn xem hồ sơ, đề nghị bạn vào trang tra cứu');
            }

            // Kiểm tra cache file merged
            $path = "merged/{$mergeId}.pdf";
            
            if (!Storage::disk('local')->exists($path)) {
                // Tạo file merged nếu chưa có
                $this->createMergedFile($treatmentCode, $path);
            }

            $full = storage_path("app/{$path}");
            return response()->file($full, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="merged.pdf"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Link không hợp lệ hoặc đã hết hạn'], 403);
        }
    }

    /**
     * Tạo file PDF đã gộp (logic từ mergePdfFilesSecure)
     */
    private function createMergedFile($treatmentCode, $outputPath)
    {
        // Lấy danh sách file PDF theo treatmentCode
        $filePaths = $this->getFilePaths($treatmentCode, null);

        if (empty($filePaths) || !$filePaths instanceof \Illuminate\Support\Collection || $filePaths->isEmpty()) {
            throw new \Exception('Không tìm thấy văn bản nào trong hồ sơ này');
        }

        $pdf = new \setasign\Fpdi\Fpdi();

        $tempDir = storage_path('app/temp/');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Tạo thư mục merged nếu chưa có
        $mergedDir = storage_path('app/merged');
        if (!is_dir($mergedDir)) {
            mkdir($mergedDir, 0755, true);
        }

        $ftp = new FtpService();
        $ftp->connect();

        try {
            foreach ($filePaths as $filePath) {
                $resultUrl = str_replace('\\', '/', $filePath->last_version_url);
                $localPath = $tempDir . basename($resultUrl);
                
                $ftp->download($resultUrl, $localPath);
                
                $pageCount = $pdf->setSourceFile($localPath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tplIdx = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }

                @unlink($localPath);
            }

            $ftp->close();

            // Lưu file merged
            $output = $pdf->Output('S');
            Storage::disk('local')->put($outputPath, $output);
            
        } catch (\Exception $e) {
            $ftp->close();
            throw $e;
        }
    }

    /**
     * Lấy danh sách file paths (tương tự EmrController)
     */
    private function getFilePaths($treatmentCode, $ParamDocumentType = null)
    {
        $query = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $treatmentCode);

        if (!empty($ParamDocumentType)) {
            $query->whereIn('emr_document.document_type_id', (array) $ParamDocumentType);
        }

        return $query
            ->orderBy('emr_document_type.num_order', 'DESC')
            ->orderBy('emr_document.document_time', 'ASC')
            ->get();
    }
}