<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\NgayHieuLuc;

/**
 * CCHN cua bac si chi dinh / nguoi thuc hien khong co trong danh muc nhan vien y te.
 *
 * Tra hai cot khoa macchn HOAC ma_bhxh - giu nguyen ngu nghia cua
 * CommonValidationService::isMedicalStaffValid de order-check va XML3176 khong cho hai ket
 * luan khac nhau tren cung mot ho so.
 *
 * Dung HAI thuc the CatalogLookup thay vi tong quat hoa lop do thanh nhieu khoa: doi lai
 * hai truy van moi lo, nhung giu CatalogLookup don gian va khong dung toi bay luat BHYT
 * dang dung no.
 *
 * KHAC XML3176 hai diem, ca hai la sua chu khong phai lech chuan:
 *   1. CO loc hieu luc theo tu_ngay/den_ngay; isMedicalStaffValid chi exists().
 *   2. Danh muc rong thi IM LANG; XML3176 thieu la chan nay va dang sinh 31.492 vi pham
 *      gia - dung bang 100% so dong xml3176_xml3s.
 */
class StaffCertNotInCatalogRule implements RuleHandler
{
    /** @var CatalogLookup */
    protected $traCchn;

    /** @var CatalogLookup */
    protected $traMaBhxh;

    /** @var int[] loai phieu khong xet vai tro nguoi thuc hien */
    protected $excludeTypeIds;

    public function __construct(
        CatalogLookup $traCchn = null,
        CatalogLookup $traMaBhxh = null,
        array $excludeTypeIds = null
    ) {
        $this->traCchn = $traCchn ?: new CatalogLookup('medical_staffs', 'macchn');
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup('medical_staffs', 'ma_bhxh');

        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
    }

    public function code()
    {
        return 'A_STAFF_CERT_NOT_IN_CATALOG';
    }

    public function check(OrderContext $c)
    {
        $ngay = NgayHieuLuc::tuMocHis($c->intructionTime);

        if ($ngay === null) {
            return [];
        }

        $vaiTro = [
            'bs' => ['nhan' => 'bác sĩ chỉ định', 'cchn' => trim((string) $c->requestDiploma)],
            'th' => ['nhan' => 'người thực hiện', 'cchn' => trim((string) $c->executeDiploma)],
        ];

        // Don thuoc (Don phong kham, Don tu truc, Don dieu tri): nguoi thuc hien la duoc si
        // hoac dieu duong cap phat. Chi bo vai tro do - bac si ra don van phai co CCHN hop
        // le, nen KHAC B_DOCTOR_NO_PRACTICE_CERT von bo qua ca phieu.
        if ($c->serviceReqTypeId !== null
            && in_array((int) $c->serviceReqTypeId, $this->excludeTypeIds, true)) {
            unset($vaiTro['th']);
        }

        $can = [];

        foreach ($vaiTro as $v) {
            if ($v['cchn'] !== '') {
                $can[] = $v['cchn'];
            }
        }

        if (empty($can)) {
            return [];   // thieu CCHN da la viec cua B_DOCTOR_NO_PRACTICE_CERT
        }

        // Kiem san sang SAU khi da loc: phieu bi loai tru het vai tro thi khong cham CSDL.
        if (!$this->traCchn->sanSang()) {
            return [];   // danh muc chua nap - im lang thay vi bao oan toan bo
        }

        // Mot truy van moi bang tra, cho ca phieu.
        $this->traCchn->nap($can);
        $this->traMaBhxh->nap($can);

        $vi = [];

        foreach ($vaiTro as $khoa => $v) {
            if ($v['cchn'] === '') {
                continue;
            }

            if ($this->traCchn->coTrongDanhMuc($v['cchn'], $ngay)
                || $this->traMaBhxh->coTrongDanhMuc($v['cchn'], $ngay)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'CCHN ' . $v['nhan'] . ' không có trong danh mục nhân viên y tế còn hiệu lực: '
                    . $v['cchn'],
                [
                    'service_req_code' => $c->serviceReqCode,
                    'vai_tro' => $khoa,
                    'cchn' => $v['cchn'],
                    'ngay_chi_dinh' => $ngay,
                ],
                $khoa . ':' . $v['cchn']
            );
        }

        return $vi;
    }
}
