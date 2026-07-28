<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\NgayHieuLuc;

/**
 * Khung chung cho cac quy tac doi chieu danh muc BHXH.
 *
 * Bang danh muc RONG thi quy tac IM LANG. Khong the thi don vi chua nhap danh muc se
 * thay MOI dich vu thanh vi pham - sai ma trong nhu dung.
 *
 * Loc theo LOAI dich vu la bat buoc, khong phai toi uu: khong loc thi quy tac thuoc doi
 * chieu ca xet nghiem voi medicine_catalogs. Do tren 7 ngay that: 53.288 dong bat oan cho
 * quy tac thuoc, 77.500 cho quy tac vat tu, 53.258 cho quy tac dich vu.
 *
 * Loc theo HIEU LUC cung bat buoc: danh muc BHXH doi theo tung dot trung thau, doi chieu y
 * lenh thang 3 voi dong danh muc chi co hieu luc tu thang 6 la sai ca hai chieu.
 */
abstract class BhytCatalogRule implements RuleHandler
{
    /** Loai Thuoc trong his_service_type */
    const LOAI_THUOC = 6;

    /** Loai Vat tu trong his_service_type */
    const LOAI_VAT_TU = 7;

    /** @var CatalogLookup */
    protected $danhMuc;

    public function __construct(CatalogLookup $danhMuc = null)
    {
        $this->danhMuc = $danhMuc ?: new CatalogLookup($this->bang(), $this->cot(), $this->cotTen());
    }

    /** Ten bang danh muc cuc bo */
    abstract protected function bang();

    /** Cot chua ma BHXH trong bang do */
    abstract protected function cot();

    /** Cot chua ten trong bang do */
    abstract protected function cotTen();

    /** Nhan hien thi trong thong diep vi pham */
    abstract protected function nhan();

    /**
     * Loai dich vu thuoc pham vi quy tac.
     *
     * Tra null nghia la PHAN BU: moi loai TRU Thuoc va Vat tu. Dung phan bu thay vi liet
     * ke 14 id de loai dich vu moi phat sinh trong HIS van duoc xet, thay vi lang le roi
     * ra ngoai pham vi kiem tra.
     *
     * @return array|null
     */
    abstract protected function loaiDichVu();

    protected function trongPhamViLoai($loai)
    {
        $ds = $this->loaiDichVu();

        if ($ds === null) {
            return !in_array((int) $loai, [self::LOAI_THUOC, self::LOAI_VAT_TU], true);
        }

        return in_array((int) $loai, $ds, true);
    }

    /**
     * Dong BHYT, dung loai, co ma BHYT, co moc chi dinh doc duoc.
     *
     * Ma tra ve da TRIM: du lieu HIS that co ma dinh ky tu tab (vi du "\t40.1021"). Tra
     * cuu van dung vi CatalogLookup cung trim, nhung thong diep vi pham thi phai sach.
     *
     * @return array danh sach [OrderService, int ngayYmd, string ma]
     */
    protected function dongTrongPhamVi(OrderContext $c)
    {
        $ra = [];

        foreach (BhytScope::locDongBhyt($c->services) as $s) {
            $ma = trim((string) $s->bhytCode);

            if ($ma === '') {
                continue;   // de quy tac A_BHYT_CODE_MISSING lo
            }

            if (!$this->trongPhamViLoai($s->serviceTypeId)) {
                continue;
            }

            // execute_time rong 100% tren his_sere_serv nen tdl_intruction_time la moc duy
            // nhat. Khong doc duoc thi khong biet doi chieu voi danh muc cua thoi diem nao.
            $ngay = NgayHieuLuc::tuMocHis($s->tdlIntructionTime);

            if ($ngay === null) {
                continue;
            }

            $ra[] = [$s, $ngay, $ma];
        }

        return $ra;
    }

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nhap - im lang thay vi bao oan toan bo
        }

        $dong = $this->dongTrongPhamVi($c);

        if (empty($dong)) {
            return [];
        }

        // Mot truy van cho ca phieu, khong tra tung dong.
        $this->danhMuc->nap(array_map(function ($d) {
            return $d[2];
        }, $dong));

        $vi = [];

        foreach ($dong as $d) {
            list($s, $ngay, $ma) = $d;

            if ($this->danhMuc->coTrongDanhMuc($ma, $ngay)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                $this->nhan() . ' không có trong danh mục BHYT còn hiệu lực: ' . $ma
                    . ' (' . $s->serviceName . ')',
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                    'bhyt_code' => $ma,
                    'ngay_chi_dinh' => $ngay,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }
}
