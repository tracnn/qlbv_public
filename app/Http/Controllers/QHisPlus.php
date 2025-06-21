<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QHisPlus extends Controller
{
    public function index()
    {
        return view('qhisplus.tra-cuu-ls-kcb.index');
    }

    public function traCuuLsKcb(Request $request)
    {
        $keyword = $request->input('keyword');

        // Truy vấn danh sách đợt khám chữa bệnh (không có chi tiết)
        $hosos = [
            ['id' => 1, 'ngay_kham' => '2025-01-20', 'khoa_phong' => 'Khoa Nội', 'bac_si' => 'BS. A', 'chan_doan' => 'Cảm cúm'],
            ['id' => 2, 'ngay_kham' => '2025-02-05', 'khoa_phong' => 'Khoa Ngoại', 'bac_si' => 'BS. B', 'chan_doan' => 'Đau dạ dày'],
        ];

        return view('qhisplus.tra-cuu-ls-kcb.index', compact('hosos', 'keyword'));
    }

    // GET: /chi-tiet-ho-so/{id}
    public function chiTietHoSo($id)
    {
        // Giả lập nội dung chi tiết
        $html = view('qhisplus.tra-cuu-ls-kcb.partials.chi-tiet-ho-so', [
            'cls' => [
                ['nhom' => 'Xét nghiệm', 'ten' => 'Công thức máu', 'ket_qua' => 'Bình thường'],
                ['nhom' => 'Siêu âm', 'ten' => 'Siêu âm bụng', 'ket_qua' => 'Gan nhiễm mỡ'],
            ],
            'thuoc' => [
                ['ten' => 'Paracetamol', 'dvt' => 'Viên', 'cach_dung' => '2 viên/ngày', 'ghi_chu' => ''],
            ]
        ])->render();

        return response($html);
    }
}
