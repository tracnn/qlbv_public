<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;
use Exception;

class InvoicePdfService
{
    private $config;
    private $soapClient = null;

    public function __construct()
    {
        $this->config = Config::get('organization.e_invoice', []);
    }

    /**
     * Download invoice PDF từ BKAV
     *
     * @param string $invoiceLookupCode Mã tra cứu hóa đơn
     * @return string PDF content dưới dạng base64
     * @throws Exception
     */
    public function downloadPdf(string $invoiceLookupCode): string
    {
        try {
            Log::info('Downloading PDF for invoice code: ' . $invoiceLookupCode);

            $bkavConfig = $this->getBkavConfig();
            
            if (!$bkavConfig['enabled']) {
                throw new Exception('E-Invoice BKAV is not enabled');
            }

            $commandData = [
                'CmdType' => 808,
                'CommandObject' => $invoiceLookupCode,
            ];

            $encryptedCommandData = $this->encryptCommandData(
                $commandData,
                $bkavConfig['partner_token']
            );

            $client = $this->getSoapClient($bkavConfig);
            
            $result = $client->ExecCommand([
                'partnerGUID' => $bkavConfig['partner_guid'],
                'CommandData' => $encryptedCommandData
            ]);

            // Xử lý response từ SOAP (có thể là object hoặc array)
            $execCommandResult = null;
            if (is_object($result)) {
                $execCommandResult = isset($result->ExecCommandResult) ? $result->ExecCommandResult : $result;
            } elseif (is_array($result)) {
                $execCommandResult = isset($result['ExecCommandResult']) ? $result['ExecCommandResult'] : (isset($result[0]) ? $result[0] : null);
            } else {
                $execCommandResult = $result;
            }

            // Chuyển đổi sang string nếu cần
            if (is_object($execCommandResult)) {
                $execCommandResult = (string) $execCommandResult;
            } elseif (is_array($execCommandResult)) {
                $execCommandResult = isset($execCommandResult[0]) ? (string) $execCommandResult[0] : json_encode($execCommandResult);
            }

            if (!is_string($execCommandResult) || empty($execCommandResult)) {
                throw new Exception('Invalid response format from BKAV API: expected string but got ' . gettype($execCommandResult));
            }

            $decryptedResult = $this->decryptExecCommandResult(
                $execCommandResult,
                $bkavConfig['partner_token']
            );

            $parsedResult = json_decode($decryptedResult, true);
            
            if ($parsedResult === null || !is_array($parsedResult)) {
                throw new Exception('Failed to parse JSON response from BKAV API');
            }
            
            if (isset($parsedResult['isError']) && $parsedResult['isError'] === true) {
                $errorMessage = $parsedResult['Object'] ?? 'Unknown error from BKAV API';
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }
                Log::error('BKAV API Error: ' . $errorMessage);
                throw new Exception('BKAV API Error: ' . $errorMessage);
            }

            if (!isset($parsedResult['Object'])) {
                throw new Exception('Invalid response format from BKAV API: missing Object');
            }

            // Object có thể là JSON string hoặc array
            $objectData = null;
            if (is_string($parsedResult['Object'])) {
                $objectData = json_decode($parsedResult['Object'], true);
                if ($objectData === null) {
                    throw new Exception('Failed to parse Object JSON from BKAV API response');
                }
            } else {
                $objectData = $parsedResult['Object'];
            }

            if (!is_array($objectData) || !isset($objectData['PDF'])) {
                throw new Exception('Invalid response format from BKAV API: missing PDF in Object');
            }

            $pdfBase64 = $objectData['PDF'];
            Log::info('Successfully downloaded PDF, size: ' . strlen(base64_decode($pdfBase64)) . ' bytes');
            
            return $pdfBase64;
        } catch (SoapFault $e) {
            Log::error('SOAP Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new Exception('Failed to call BKAV SOAP API: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Failed to download PDF: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy PDF content dưới dạng binary string
     *
     * @param string $invoiceLookupCode Mã tra cứu hóa đơn
     * @return string PDF content dưới dạng binary
     * @throws Exception
     */
    public function downloadPdfBinary(string $invoiceLookupCode): string
    {
        $pdfBase64 = $this->downloadPdf($invoiceLookupCode);
        return base64_decode($pdfBase64);
    }

    /**
     * Lấy cấu hình BKAV
     *
     * @return array
     * @throws Exception
     */
    private function getBkavConfig(): array
    {
        $config = $this->config['bkav'] ?? [];
        
        if (empty($config)) {
            throw new Exception('BKAV configuration is missing');
        }

        $apiUrl = $config['api_url'] ?? '';
        $partnerGuid = $config['partner_guid'] ?? '';
        $partnerToken = $config['partner_token'] ?? '';
        $enabled = $config['enabled'] ?? false;

        if (empty($apiUrl) || empty($partnerGuid) || empty($partnerToken)) {
            throw new Exception('BKAV configuration is incomplete');
        }

        return [
            'api_url' => $apiUrl,
            'partner_guid' => $partnerGuid,
            'partner_token' => $partnerToken,
            'enabled' => $enabled,
        ];
    }

    /**
     * Lấy SOAP client (có cache)
     *
     * @param array $config
     * @return SoapClient
     * @throws Exception
     */
    private function getSoapClient(array $config): SoapClient
    {
        if ($this->soapClient !== null) {
            return $this->soapClient;
        }

        try {
            $endpointUrl = $this->getEndpointUrl($config['api_url']);
            $wsdlUrl = $this->getWsdlUrl($endpointUrl);
            
            Log::info('Creating SOAP client from WSDL: ' . $wsdlUrl);

            $this->soapClient = new SoapClient($wsdlUrl, [
                'location' => $endpointUrl,
                'uri' => $endpointUrl,
                'soap_version' => SOAP_1_2,
                'trace' => true,
                'exceptions' => true,
            ]);

            return $this->soapClient;
        } catch (SoapFault $e) {
            Log::error('Failed to create SOAP client: ' . $e->getMessage());
            throw new Exception('Failed to create SOAP client: ' . $e->getMessage());
        }
    }

    /**
     * Lấy endpoint URL
     *
     * @param string $baseUrl
     * @return string
     */
    private function getEndpointUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        $baseUrl = rtrim($baseUrl, '/');
        
        if (substr($baseUrl, -5) === '.asmx') {
            return $baseUrl;
        }

        return $baseUrl . '/WSPublicEHoaDon.asmx';
    }

    /**
     * Lấy WSDL URL
     *
     * @param string $endpointUrl
     * @return string
     */
    private function getWsdlUrl(string $endpointUrl): string
    {
        if (substr($endpointUrl, -5) === '?WSDL') {
            return $endpointUrl;
        }

        return $endpointUrl . '?WSDL';
    }

    /**
     * Parse partner token thành key và IV
     * Format: Base64(Key):Base64(IV)
     *
     * @param string $partnerToken
     * @return array ['key' => string, 'iv' => string]
     * @throws Exception
     */
    private function parsePartnerToken(string $partnerToken): array
    {
        $parts = explode(':', $partnerToken);
        
        if (count($parts) !== 2) {
            throw new Exception('partnerToken must be "Base64(Key):Base64(IV)"');
        }

        $key = base64_decode($parts[0]);
        $iv = base64_decode($parts[1]);

        if ($key === false || strlen($key) !== 32) {
            throw new Exception('Invalid AES key length: ' . strlen($key) . ' (expected 32 for AES-256)');
        }

        if ($iv === false || strlen($iv) !== 16) {
            throw new Exception('Invalid AES IV length: ' . strlen($iv) . ' (expected 16 for AES-CBC)');
        }

        return ['key' => $key, 'iv' => $iv];
    }

    /**
     * Mã hóa CommandData:
     * 1) JSON -> UTF8 bytes
     * 2) gzip
     * 3) AES-256-CBC (PKCS#7 padding)
     * 4) Base64
     *
     * @param array|string $plain
     * @param string $partnerToken
     * @return string
     * @throws Exception
     */
    private function encryptCommandData($plain, string $partnerToken): string
    {
        $tokenParts = $this->parsePartnerToken($partnerToken);
        $key = $tokenParts['key'];
        $iv = $tokenParts['iv'];

        $plainJson = is_string($plain) ? $plain : json_encode($plain);
        $plainBuf = $plainJson;

        // gzip compress
        $gz = gzencode($plainBuf, 9);

        // AES-256-CBC encrypt
        $encrypted = openssl_encrypt(
            $gz,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception('Failed to encrypt command data');
        }

        return base64_encode($encrypted);
    }

    /**
     * Giải mã ExecCommandResult:
     * 1) Base64 -> bytes
     * 2) AES-256-CBC decrypt
     * 3) gunzip
     * 4) UTF8 string (JSON)
     *
     * @param string $execCommandResultBase64
     * @param string $partnerToken
     * @return string
     * @throws Exception
     */
    private function decryptExecCommandResult(string $execCommandResultBase64, string $partnerToken): string
    {
        $tokenParts = $this->parsePartnerToken($partnerToken);
        $key = $tokenParts['key'];
        $iv = $tokenParts['iv'];

        $encrypted = base64_decode($execCommandResultBase64);

        if ($encrypted === false) {
            throw new Exception('Failed to decode base64 encrypted data');
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('Failed to decrypt command result');
        }

        $ungz = gzdecode($decrypted);

        if ($ungz === false) {
            throw new Exception('Failed to decompress gzip data');
        }

        return $ungz;
    }
}

