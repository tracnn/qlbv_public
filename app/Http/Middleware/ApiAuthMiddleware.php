<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Xac thuc token API bang BAN BAM SHA-256.
 *
 * Config chi chua hash; token goc khong ton tai o bat ky dau trong ma nguon hay cau
 * hinh. Lo tep config (backup, log, xem nham) khong du de goi API.
 */
class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return $this->tuChoi(
                $request,
                'thieu_header',
                'Authorization header is required',
                'Please include \'Authorization: Bearer {token}\' in your request headers'
            );
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $khop)) {
            return $this->tuChoi(
                $request,
                'sai_dinh_dang',
                'Invalid authorization format',
                'Authorization header must be in format: Bearer {token}'
            );
        }

        $token = $khop[1];
        // GIA TRI cua khoa nay la BAN BAM SHA-256, khong phai token tho. Giu nguyen TEN
        // khoa cu de ban cai da trien khai chi phai sua GIA TRI cua mot dong san co,
        // khong phai them khoa moi vao dung cho trong tep.
        $hashCauHinh = (string) config('organization.api.access_token');

        // Thieu cau hinh => TU CHOI. config/organization.php khong nam trong git nen ban
        // cai chua cap nhat se thieu khoa nay; trang thai an toan duy nhat la 401.
        // Thong diep giong het nhanh sai token - khong de lo cho nguoi do biet he thong
        // dang thieu cau hinh.
        if ($hashCauHinh === '') {
            return $this->tuChoi(
                $request,
                'chua_cau_hinh',
                'Invalid access token',
                'The provided token is not valid or has expired'
            );
        }

        // hash_equals: thoi gian so sanh khong phu thuoc so ky tu trung dau chuoi.
        if (!hash_equals($hashCauHinh, hash('sha256', $token))) {
            return $this->tuChoi(
                $request,
                'sai_token',
                'Invalid access token',
                'The provided token is not valid or has expired'
            );
        }

        // Muc debug chu khong phai info: truoc day moi request thanh cong deu ghi mot
        // dong info, lam ngap nhung dong that su can doc.
        \Log::debug('API xac thuc thanh cong', [
            'endpoint' => $request->path(),
            'request_id' => $this->maYeuCau(),
        ]);

        return $next($request);
    }

    protected function tuChoi(Request $request, $lyDo, $message, $details)
    {
        // KHONG ghi token duoi bat ky dang nao - ke ca mot phan, ke ca ban bam.
        \Log::warning('API xac thuc that bai', [
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'ly_do' => $lyDo,
        ]);

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => now()->format('YmdHis'),
                'request_id' => $this->maYeuCau(),
            ],
        ], 401);
    }

    protected function maYeuCau()
    {
        return uniqid('req_');
    }
}
