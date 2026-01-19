<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PdfFetchService
{
    private $httpClient;
    private $cacheEnabled;
    private $cacheTtl;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false
        ]);
        $this->cacheEnabled = config('app.pdf_cache_enabled', true);
        $this->cacheTtl = config('app.pdf_cache_ttl', 60); // minutes
    }

    /**
     * Lấy file PDF từ URL và chuyển thành base64
     *
     * @param string $url URL của file PDF
     * @param array $options Các tùy chọn bổ sung (headers, cache key, etc.)
     * @return array
     * @throws \Exception
     */
    public function getPdfAsBase64($url, $options = [])
    {
        $cacheKey = $this->generateCacheKey($url, $options);

        // Kiểm tra cache nếu được bật
        if ($this->cacheEnabled && isset($options['cache']) && $options['cache'] === true) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                Log::info('PDF fetched from cache', ['url' => $url]);
                return $cachedData;
            }
        }

        try {
            // Gọi API để lấy file PDF
            $response = $this->httpClient->get($url, [
                'headers' => $options['headers'] ?? [],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                Log::error('Failed to fetch PDF', [
                    'url' => $url,
                    'status_code' => $statusCode
                ]);
                throw new \Exception("Failed to fetch PDF. HTTP Status: {$statusCode}");
            }

            // Lấy nội dung file dạng binary
            $binaryContent = $response->getBody()->getContents();

            // Chuyển đổi sang base64
            $base64Data = base64_encode($binaryContent);

            // Lấy content type
            $contentType = $response->getHeaderLine('Content-Type') ?: 'application/pdf';

            $result = [
                'base64' => $base64Data,
                'contentType' => $contentType,
                'size' => strlen($binaryContent),
                'url' => $url
            ];

            // Lưu vào cache nếu được bật
            if ($this->cacheEnabled && isset($options['cache']) && $options['cache'] === true) {
                $cacheTtl = $options['cache_ttl'] ?? $this->cacheTtl;
                Cache::put($cacheKey, $result, $cacheTtl);
                Log::info('PDF cached', ['url' => $url, 'ttl' => $cacheTtl]);
            }

            Log::info('PDF fetched successfully', [
                'url' => $url,
                'size' => $result['size']
            ]);

            return $result;

        } catch (GuzzleException $e) {
            Log::error('PDF Fetch API Error: ' . $e->getMessage(), [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch PDF from URL: ' . $e->getMessage());
        }
    }

    /**
     * Lấy nhiều file PDF từ nhiều URL
     *
     * @param array $urls Mảng các URL
     * @param array $options Các tùy chọn bổ sung
     * @return array
     */
    public function getMultiplePdfsAsBase64($urls, $options = [])
    {
        $results = [];
        $errors = [];

        foreach ($urls as $key => $url) {
            try {
                $results[$key] = $this->getPdfAsBase64($url, $options);
            } catch (\Exception $e) {
                $errors[$key] = [
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
                Log::error('Failed to fetch PDF in batch', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Lấy file PDF và lưu trực tiếp vào storage
     *
     * @param string $url URL của file PDF
     * @param string $storagePath Đường dẫn lưu file trong storage
     * @param array $options Các tùy chọn bổ sung
     * @return string Đường dẫn file đã lưu
     * @throws \Exception
     */
    public function downloadPdfToStorage($url, $storagePath, $options = [])
    {
        try {
            $response = $this->httpClient->get($url, [
                'headers' => $options['headers'] ?? [],
            ]);

            $binaryContent = $response->getBody()->getContents();

            // Lưu file vào storage
            $fullPath = storage_path('app/' . $storagePath);
            $directory = dirname($fullPath);

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($fullPath, $binaryContent);

            Log::info('PDF downloaded to storage', [
                'url' => $url,
                'path' => $storagePath,
                'size' => strlen($binaryContent)
            ]);

            return $storagePath;

        } catch (GuzzleException $e) {
            Log::error('PDF Download Error: ' . $e->getMessage(), [
                'url' => $url,
                'path' => $storagePath
            ]);
            throw new \Exception('Failed to download PDF: ' . $e->getMessage());
        }
    }

    /**
     * Xóa cache của một URL cụ thể
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function clearCache($url, $options = [])
    {
        $cacheKey = $this->generateCacheKey($url, $options);
        Cache::forget($cacheKey);
        Log::info('PDF cache cleared', ['url' => $url]);
        return true;
    }

    /**
     * Xóa tất cả cache PDF
     *
     * @return bool
     */
    public function clearAllCache()
    {
        // Laravel 5.5 không hỗ trợ tags cho file cache driver
        // Nếu dùng Redis thì có thể dùng tags
        Log::info('Clear all PDF cache requested');
        return true;
    }

    /**
     * Tạo cache key từ URL và options
     *
     * @param string $url
     * @param array $options
     * @return string
     */
    private function generateCacheKey($url, $options = [])
    {
        $key = 'pdf_fetch_' . md5($url . json_encode($options));
        return $key;
    }

    /**
     * Kiểm tra xem file có phải PDF không
     *
     * @param string $url
     * @return bool
     */
    public function isPdfUrl($url)
    {
        try {
            $response = $this->httpClient->head($url);
            $contentType = $response->getHeaderLine('Content-Type');
            return strpos($contentType, 'application/pdf') !== false;
        } catch (\Exception $e) {
            Log::error('Failed to check PDF URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy thông tin file PDF (không tải về)
     *
     * @param string $url
     * @return array
     */
    public function getPdfInfo($url)
    {
        try {
            $response = $this->httpClient->head($url);

            return [
                'contentType' => $response->getHeaderLine('Content-Type'),
                'contentLength' => $response->getHeaderLine('Content-Length'),
                'lastModified' => $response->getHeaderLine('Last-Modified'),
                'exists' => $response->getStatusCode() === 200
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get PDF info', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
