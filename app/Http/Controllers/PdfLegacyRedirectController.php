<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PdfLegacyRedirectController extends Controller
{
    public function viewMergePdf(Request $request)
    {
        $token = $request->get('token');
        if ($token) {
            return redirect()->route('pdf.flip.show', ['token' => $token]);
        }
        abort(400, 'Thiếu token');
    }

    public function mergePdfSecure(Request $request)
    {
        $token = $request->get('token');
        if (!$token) {
            return response()->json(['error' => 'Thiếu token'], 400);
        }

        try {
            $decrypted = Crypt::decryptString($token);
            [$treatmentCode, $createdAt, $expiresIn] = explode('|', $decrypted);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token không hợp lệ'], 400);
        }

        $mergeId = md5($treatmentCode . $createdAt . $expiresIn);

        return redirect()->route('pdf.flip.file', [
            'mergeId' => $mergeId,
            'token'   => $token,
        ]);
    }
}