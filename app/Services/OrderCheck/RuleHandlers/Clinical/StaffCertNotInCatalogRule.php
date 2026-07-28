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

    public function __construct(CatalogLookup $traCchn = null, CatalogLookup $traMaBhxh = null)
    {
        $this->traCchn = $traCchn ?: new CatalogLookup('medical_staffs', 'macchn');
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup('medical_staffs', 'ma_bhxh');
    }

    public function code()
    {
        return 'A_STAFF_CERT_NOT_IN_CATALOG';
    }

    public function check(OrderContext $c)
    {
        if (!$this->traCchn->sanSang()) {
            return [];   // danh muc chua nap - im lang thay vi bao oan toan bo
        }

        $ngay = NgayHieuLuc::tuMocHis($c->intructionTime);

        if ($ngay === null) {
            return [];
        }

        // Luat nay xet CA HAI vai tro o MOI loai phieu. Danh sach loai tru theo loai phieu
        // (practice_cert_exclude_type_ids) chi ap cho B_DOCTOR_NO_PRACTICE_CERT.
        $vaiTro = [
            'bs' => ['nhan' => 'bác sĩ chỉ định', 'cchn' => trim((string) $c->requestDiploma)],
            'th' => ['nhan' => 'người thực hiện', 'cchn' => trim((string) $c->executeDiploma)],
        ];

        $can = [];

        foreach ($vaiTro as $v) {
            if ($v['cchn'] !== '') {
                $can[] = $v['cchn'];
            }
        }

        if (empty($can)) {
            return [];   // thieu CCHN da la viec cua B_DOCTOR_NO_PRACTICE_CERT
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
