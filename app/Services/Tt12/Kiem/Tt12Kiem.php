<?php

namespace App\Services\Tt12\Kiem;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12Loi as LoiModel;
use App\Services\Tt12\Tt12MauRegistry;

/**
 * Ghep cac luat lai, chay tren mot ho so, ghi ket qua xuong tt12_loi.
 *
 * VI SAO LUAT HO SO PHAI DOC HET DONG: LuatHoSo can nhin toan bo tep de biet STT nao
 * trung va ma nao co hai dong. Doc theo lo cho luat theo o/theo dong nhung phai gom
 * (stt, ma, tu_ngay, den_ngay) cua moi dong cho luat theo ho so - do la BON truong chu
 * khong phai ca dong, nen mot tep 50.000 dong ton khoang vai MB, khong phai vai chuc.
 *
 * VI SAO GHI LOI NGAY TRONG TUNG LO: chunk() chi tiet kiem phan DOC. Ban truoc gom toan
 * bo doi tuong Tt12Loi vao mot mang roi ghi mot lan sau khi doc het - mot thao tac Excel
 * sai duy nhat (de trong ca cot TU_NGAY) tren tep 30.000 dong sinh 30.000 doi tuong loi,
 * moi cai mang mot chuoi mo ta. Tren may chu 128 MB job chet va ho so ket o CHUA_KIEM.
 */
class Tt12Kiem
{
    /** Doc bao nhieu dong moi lan. Nho hon co lo doc Excel vi moi dong o day nang hon. */
    const CO_LO = 2000;

    /** Bao nhieu hang moi lan insert xuong tt12_loi */
    const CO_LO_GHI = 500;

    /**
     * Tran so ban ghi loi ghi lai cho MOT ho so.
     *
     * Qua con so nay thi danh sach loi khong con la thu de doc nua - nguoi dung se sua TEP
     * chu khong sua tung dong. Ghi tiep chi ton bo nho va lam bang tt12_loi phinh vo ich.
     */
    const TOI_DA_GHI_LOI = 10000;

    /** @var int tran thuc dung; nhan qua ham dung de kiem duoc ma khong phai tao 10.001 dong */
    private $toiDaGhi;

    public function __construct($toiDaGhi = self::TOI_DA_GHI_LOI)
    {
        $this->toiDaGhi = (int) $toiDaGhi;
    }

    /**
     * @param Tt12HoSo $hoSo
     * @return int so loi MUC 'loi' (khong tinh canh bao)
     */
    public function kiem(Tt12HoSo $hoSo)
    {
        // Xoa loi cu MOT LAN, truoc vong lap. Xoa truoc BAT BUOC: kiem lai mot ho so ma
        // khong xoa se cong don loi qua tung lan kiem, va so_loi phinh len mai.
        LoiModel::where('ho_so_id', $hoSo->id)->delete();

        // HA CO NGAY, TRUOC VONG LAP - khong phai chi nang co sau khi xong.
        //
        // Ghi theo lo DANH DOI tinh nguyen tu lay bo nho: ban truoc boc ca xoa + chen +
        // ghi co trong MOT transaction, ban nay khong the. Cai gia do phai tra bang mot
        // trang thai trung gian TRUNG THUC: tu luc nay den luc chot(), ho so dung la "chua
        // kiem" - loi cu da mat, loi moi chua du. Chet giua chung (het bo nho o lo 20, mat
        // ket noi) ma van giu checked_at/so_loi cu la man hinh bao "Da kiem, 5 loi" trong
        // khi tt12_loi rong.
        //
        // VA DAY MOI LA CHO CHET: nut "Kiem lai" tren man chi tiet chi hien khi checked_at
        // rong. Giu co cu la con loi TU GIAU dung duong cuu cua chinh no - nguoi dung ket
        // hoan toan, khong thao tac nao tren man hinh go duoc.
        $hoSo->update(array('checked_at' => null, 'so_loi' => 0));

        // dem['so_loi'] la TONG THAT muc 'loi', dem ca phan khong ghi xuong - nguoi dung
        // can biet quy mo that de quyet dinh sua tep hay sua quy trinh.
        $dem = array('so_loi' => 0, 'da_ghi' => 0, 'bo_qua' => 0);

        if (!Tt12MauRegistry::co($hoSo->mau)) {
            $this->ghiLo($hoSo, array(Tt12Loi::loi(
                'MAU_LA',
                'Hồ sơ mang mẫu không nằm trong đăng ký: ' . $hoSo->mau
            )), $dem);

            return $this->chot($hoSo, $dem);
        }

        $lop = Tt12MauRegistry::cho($hoSo->mau);

        $tomTat = array();

        Tt12Dong::where('ho_so_id', $hoSo->id)
            ->with('thuocPx')
            ->orderBy('stt')
            ->chunk(self::CO_LO, function ($cacDong) use ($lop, $hoSo, &$dem, &$tomTat) {
                $loi = array();

                foreach ($cacDong as $dong) {
                    $duLieu = is_array($dong->du_lieu) ? $dong->du_lieu : array();

                    $duLieuCon = array();

                    foreach ($dong->thuocPx as $con) {
                        $duLieuCon[] = $con->toArray();
                    }

                    $loi = array_merge(
                        $loi,
                        LuatO::kiem($lop, $duLieu, $dong->stt),
                        LuatDong::kiem($lop, $duLieu, $dong->stt, $hoSo->ma_cskcb),
                        LuatRiengMau::kiem($lop, $duLieu, $dong->stt, $duLieuCon)
                    );

                    // Chi giu BON truong cho luat theo ho so, khong giu ca dong.
                    $tomTat[] = array(
                        'stt'    => $dong->stt,
                        'du_lieu' => array(
                            $lop::theMa() => isset($duLieu[$lop::theMa()]) ? $duLieu[$lop::theMa()] : '',
                            'TU_NGAY'     => isset($duLieu['TU_NGAY']) ? $duLieu['TU_NGAY'] : '',
                            'DEN_NGAY'    => isset($duLieu['DEN_NGAY']) ? $duLieu['DEN_NGAY'] : '',
                            'STT'         => isset($duLieu['STT']) ? $duLieu['STT'] : '',
                        ),
                    );
                }

                // GHI NGAY, roi bo $loi. Day la ca diem cua viec doc theo lo.
                $this->ghiLo($hoSo, $loi, $dem);
            });

        $this->ghiLo($hoSo, LuatHoSo::kiem($lop, $tomTat), $dem);

        return $this->chot($hoSo, $dem);
    }

    /**
     * Dem va ghi mot lo loi.
     *
     * DEM TRUOC KHI XET TRAN: so_loi phai la tong that, khong phai so da ghi.
     */
    protected function ghiLo(Tt12HoSo $hoSo, array $loi, array &$dem)
    {
        if ($loi === array()) {
            return;
        }

        $hang = array();

        foreach ($loi as $mot) {
            if ($mot->laLoi()) {
                $dem['so_loi']++;
            }

            if ($dem['da_ghi'] >= $this->toiDaGhi) {
                $dem['bo_qua']++;
                continue;
            }

            $hang[] = $this->thanhHang($mot, $hoSo->id);
            $dem['da_ghi']++;
        }

        $this->chen($hang);
    }

    /** @return int so loi muc 'loi' */
    private function chot(Tt12HoSo $hoSo, array $dem)
    {
        if ($dem['bo_qua'] > 0) {
            // MOT ban ghi tong ket, o muc canh bao de khong lam phong so_loi. Khong co no
            // thi danh sach loi bi cat cut ma khong dau hieu nao bao rang no da bi cat.
            $this->chen(array($this->thanhHang(Tt12Loi::canhBao(
                'VUOT_TRAN_LOI',
                'Và ' . $dem['bo_qua'] . ' lỗi khác không được liệt kê (đã đạt giới hạn '
                . $this->toiDaGhi . ' dòng). Sửa tệp Excel rồi nạp lại thay vì sửa từng dòng.'
            ), $hoSo->id)));
        }

        $hoSo->update(array(
            'checked_at' => Carbon::now(),
            'so_loi'     => $dem['so_loi'],
        ));

        return $dem['so_loi'];
    }

    /** @return array mot hang de insert thang vao tt12_loi */
    private function thanhHang(Tt12Loi $loi, $hoSoId)
    {
        return array_merge($loi->thanhMang($hoSoId), array(
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ));
    }

    private function chen(array $hang)
    {
        if ($hang === array()) {
            return;
        }

        DB::transaction(function () use ($hang) {
            foreach (array_chunk($hang, self::CO_LO_GHI) as $lo) {
                LoiModel::insert($lo);
            }
        });
    }
}
