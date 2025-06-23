<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class QHisPlus extends Controller
{
    public function index()
    {
        return view('qhisplus.tra-cuu-ls-kcb.index');
    }

    public function traCuuLsKcb(Request $request)
    {
        $keyword = $request->input('keyword');

        // Validate keyword theo yêu cầu
        $isValid = preg_match('/^\d{12}$/', $keyword) || preg_match('/^[A-Z]{2}\d{13}$/', $keyword);

        if (!$isValid) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['keyword' => 'Vui lòng nhập CCCD gồm 12 chữ số hoặc mã BHYT gồm 2 chữ cái in hoa và 13 chữ số.']);
        }

        $baseUrl = config('organization.q_his_plus_url');
        $endpoint = $baseUrl . '/qd3176/xml1s/' . urlencode($keyword);

        try {
            $client = new Client();
            $response = $client->request('GET', $endpoint);
            $body = json_decode($response->getBody(), true);

            $hosos = $body['data'] ?? [];
            
            $benhNhan = null;

            if (count($hosos)) {
                $first = $hosos[0];
                $benhNhan = [
                    'hoTen' => $first['hoTen'],
                    'ngaySinh' => dob($first['ngaySinh']),
                    'gioiTinh' => $first['gioiTinh'] == 1 ? 'Nam' : 'Nữ',
                    'diaChi' => $first['diaChi'],
                    'soCccd' => $first['soCccd'],
                    'maTheBhyt' => $first['maTheBhyt'],
                    'sdt' => $first['dienThoai'],
                ];
            }
            
            // Map lại nếu cần đơn giản
            $mapped = collect($hosos)->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'hoTen' => $item['hoTen'],
                    'ngaySinh' =>dob($item['ngaySinh']),
                    'ngayVao' =>strtodatetime($item['ngayVao']),
                    'ngayRa' => strtodatetime($item['ngayRa']),
                    'ghiChu' => $item['ghiChu'],
                    'chanDoanRv' => $item['chanDoanRv'],
                ];
            });

            return view('qhisplus.tra-cuu-ls-kcb.index', [
                'hosos' => $mapped,
                'keyword' => $keyword,
                'benhNhan' => $benhNhan
            ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['keyword' => 'Lỗi khi truy xuất dữ liệu: ' . $e->getMessage()]);
        }

        return view('qhisplus.tra-cuu-ls-kcb.index', compact('hosos', 'keyword', 'benhNhan'));
    }

    // GET: /chi-tiet-ho-so/{id}
    public function chiTietHoSo($id)
    {
        try {
            $baseUrl = config('organization.q_his_plus_url');
            
            $client = new Client();
            
            $response = $client->request('GET', "{$baseUrl}/qd3176/xml2s/{$id}");

            $data = json_decode($response->getBody(), true);
            $thuocs = $data['data'] ?? [];

            $resDvkt = $client->request('GET', "{$baseUrl}/qd3176/xml3s/{$id}");
            $dvktRaw = json_decode($resDvkt->getBody(), true)['data'] ?? [];

            $dvktInfoMap = collect($dvktRaw)->keyBy('maDichVu')->map(function ($item) {
                return [
                    'tenDichVu' => $item['tenDichVu'] ?? '(Không rõ)',
                    'maNhom'    => $item['maNhom'] ?? null,
                ];
            });

            $resCls = $client->request('GET', "{$baseUrl}/qd3176/xml4s/{$id}");
            $clsRaw = json_decode($resCls->getBody(), true)['data'] ?? [];

            $clsWithInfo = collect($clsRaw)->map(function ($item) use ($dvktInfoMap) {
                $info = $dvktInfoMap->get($item['maDichVu'], [
                    'tenDichVu' => '(Không rõ tên dịch vụ)',
                    'maNhom' => null
                ]);

                $item['tenDichVu'] = $info['tenDichVu'];
                $item['maNhom'] = $info['maNhom'];
                return $item;
            });
            $clsGrouped = $clsWithInfo->groupBy('maNhom');

            // Render view partial
            $html = view('qhisplus.tra-cuu-ls-kcb.partials.chi-tiet-ho-so', [
                'thuocs' => $thuocs,
                'clsGrouped' => $clsGrouped,
            ])->render();

            return response($html);
        } catch (\Exception $e) {
            return response('<div class="text-danger">Lỗi khi lấy chi tiết thuốc: ' . $e->getMessage() . '</div>', 500);
        }
    }
}
