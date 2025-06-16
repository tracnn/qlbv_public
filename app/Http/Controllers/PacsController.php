<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class PacsController extends Controller
{
    public function getPacsViewerLink(Request $request)
    {
        $serviceID = $request->query('serviceID');
        if (!$serviceID) {
            return response()->json(['error' => 'Thiếu serviceID'], 400);
        }
        // Lấy base url từ config
        $baseUrl = config('organization.base_pacs_url'); // 'http://bvdkhadong.dynu.com:88/ris/his/viewer?serviceID='

        // Trả về link hoàn chỉnh
        return response()->json([
            'viewerUrl' => $baseUrl . urlencode($serviceID)
        ]);
    }
}
