<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

/**
 * Man hinh tra cuu danh muc, LAI BANG CAU HINH.
 *
 * Toan bo khu vuc chi co MOT controller va MOT view: them danh muc moi la them mot muc
 * trong config/danh_muc_tra_cuu.php, khong phai them ham/view/route.
 *
 * CHI XEM. Cac danh muc o day nap tu tep theo kieu thay tron bo, nen sua tay tren man
 * hinh se bi lan nap sau xoa sach - vi vay khong co duong ghi nao o day.
 *
 * Cot ngoai danh sach 'cot' duoc chan bang whitelist() cua Yajra chu KHONG phai bang
 * select(): xem chu thich trong fetch().
 */
class DanhMucTraCuuController extends Controller
{
    /**
     * Cau hinh cua mot danh muc; khoa la thi 404.
     *
     * Tra bang array_key_exists chu KHONG noi chuoi vao config('danh_muc_tra_cuu.'.$khoa):
     * slug dang 'dvkt_can_ma_may.model' se di xuyen qua dot-notation, khong khop nhanh
     * abort() va lam vo o cho khac (500 thay vi 404).
     */
    private function cauHinh($khoa)
    {
        $so = config('danh_muc_tra_cuu', []);

        if (!array_key_exists($khoa, $so)) {
            abort(404, 'Không có danh mục: ' . $khoa);
        }

        return $so[$khoa];
    }

    public function index($khoa)
    {
        $dm = $this->cauHinh($khoa);

        return view('category.danh-muc-tra-cuu.index', [
            'khoa' => $khoa,
            'dm'   => $dm,
        ]);
    }

    public function fetch(Request $request, $khoa)
    {
        $dm = $this->cauHinh($khoa);
        $cot = array_keys($dm['cot']);

        $model = $dm['model'];
        $bang = new $model();

        // Khoa chinh lay tu model chu khong hard-code 'id': bang danh muc co the dung
        // khoa khac (departments dung 'ID' viet hoa) hoac khong co cot ten 'id' nao.
        $khoaChinh = isset($dm['khoa_chinh']) ? $dm['khoa_chinh'] : $bang->getKeyName();

        $query = $model::query()->select(array_unique(array_merge([$khoaChinh], $cot)));

        // CHI ap sap xep mac dinh khi client CHUA yeu cau sap xep. Yajra chi NOI THEM menh
        // de order, nen gan orderBy vo dieu kien se cho 'order by <mac dinh>, <cua client>'
        // - cot mac dinh gan nhu duy nhat thi bam tieu de cot khong doi gi.
        if (!empty($dm['sap_xep']) && !$request->has('order')) {
            $query->orderBy($dm['sap_xep'][0], $dm['sap_xep'][1]);
        }

        // whitelist la chot an toan THAT SU, khong phai select.
        //
        // Yajra lay ten cot tu REQUEST cua client, con config/datatables.php dat
        // whitelist='*'. Chi select cot da khai KHONG chan duoc client sap xep/tim kiem
        // theo cot AN: dieu do sinh ra mot oracle mu doc duoc noi dung moi cot trong bang
        // qua so ban ghi khop. Gioi han whitelist ve dung cac cot da khai moi dong duoc.
        return Datatables::of($query)->whitelist($cot)->make(true);
    }
}
