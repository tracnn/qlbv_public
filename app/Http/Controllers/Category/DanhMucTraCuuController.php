<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;

/**
 * Man hinh tra cuu danh muc, LAI BANG CAU HINH.
 *
 * Toan bo khu vuc chi co MOT controller va MOT view: them danh muc moi la them mot muc
 * trong config/danh_muc_tra_cuu.php, khong phai them ham/view/route.
 *
 * CHI XEM. Cac danh muc o day nap tu tep theo kieu thay tron bo, nen sua tay tren man
 * hinh se bi lan nap sau xoa sach - vi vay khong co duong ghi nao o day.
 */
class DanhMucTraCuuController extends Controller
{
    /** Cau hinh cua mot danh muc; khoa la thi 404. */
    private function cauHinh($khoa)
    {
        $dm = config('danh_muc_tra_cuu.' . $khoa);

        if (empty($dm)) {
            abort(404, 'Không có danh mục: ' . $khoa);
        }

        return $dm;
    }

    public function index($khoa)
    {
        $dm = $this->cauHinh($khoa);

        return view('category.danh-muc-tra-cuu.index', [
            'khoa' => $khoa,
            'dm'   => $dm,
        ]);
    }

    public function fetch($khoa)
    {
        $dm = $this->cauHinh($khoa);

        // CHI select cac cot da khai (cong id de DataTables co khoa on dinh): danh muc ve
        // sau co cot nhay cam se khong bi lo chi vi nguoi khai quen giau.
        $cot = array_keys($dm['cot']);
        $model = $dm['model'];
        $query = $model::query()->select(array_merge(['id'], $cot));

        if (!empty($dm['sap_xep'])) {
            $query->orderBy($dm['sap_xep'][0], $dm['sap_xep'][1]);
        }

        return Datatables::of($query)->make(true);
    }
}
