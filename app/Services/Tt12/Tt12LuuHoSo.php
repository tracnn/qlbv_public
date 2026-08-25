<?php

namespace App\Services\Tt12;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12DongThuocPx;

/**
 * Ghi ho so va cac dong xuong CSDL.
 *
 * VI SAO GHI THEO LO CHU KHONG MOT TRANSACTION BAO TRUM: mot danh muc thuoc that co the
 * vai nghin dong. Gom het vao bo nho roi ghi mot lan la dung lai dung cai bay ma
 * CatalogImportService da ghi lai - 128 MB va 346 giay chi rieng phan doc. Ghi theo lo
 * ngay khi doc xong lo do.
 *
 * VI SAO KHONG CO TRANSACTION BAO TRUM CA TEP: ho so duoc danh dau hoan tat o buoc
 * ketThuc(). Neu tien trinh chet giua chung thi ho so nam lai voi so_dong = 0 va
 * imported_at rong - man danh sach loc duoc va nguoi dung xoa duoc. Mot transaction dai
 * hang phut tren MySQL lai khoa bang va lam ca he thong cham.
 */
class Tt12LuuHoSo
{
    /**
     * Tao ban ghi ho so RONG, chua co dong nao.
     *
     * @return Tt12HoSo
     */
    public function batDau($mau, $maCskcb, $tenTep, $importedBy)
    {
        $lop = Tt12MauRegistry::cho($mau);

        return Tt12HoSo::create(array(
            'ma_ho_so'     => Tt12MaHoSo::keTiep($mau, $maCskcb),
            'mau'          => $mau,
            'loai_hs'      => $lop::loaiHs(),
            'ma_cskcb'     => $maCskcb,
            'ten_tep'      => $tenTep,
            'so_dong'      => 0,

            // Sinh MOT LAN o day. Chu ky XMLDSig tham chieu #Id nay; sinh lai luc ky thi
            // ban ky lan hai khac ban lan mot.
            //
            // Dung Ramsey truc tiep chu KHONG Illuminate\Support\Str::uuid(): ham do
            // chi co tu Laravel 5.6, du an nay chay 5.5 nen goi no la fatal error.
            'id_danh_sach' => 'Id-' . \Ramsey\Uuid\Uuid::uuid4()->toString(),

            'imported_by'  => $importedBy,
        ));
    }

    /**
     * Ghi mot lo dong.
     *
     * @param Tt12HoSo $hoSo
     * @param string   $lop        ten lop dac ta mau
     * @param array    $loDong     mang cac mang chi so, dung thu tu cot cua tep
     * @param array    $viTri      [TEN_THE => chi so cot] cho dong cha
     * @param array    $viTriCon   [TEN_THE => chi so cot] cho bang con, rong neu khong co
     * @param int      $sttBatDau
     * @return int so dong da ghi
     */
    public function ghiLo(Tt12HoSo $hoSo, $lop, array $loDong, array $viTri, array $viTriCon, $sttBatDau)
    {
        $stt = $sttBatDau;
        $daGhi = 0;

        DB::transaction(function () use ($hoSo, $lop, $loDong, $viTri, $viTriCon, &$stt, &$daGhi) {
            foreach ($loDong as $dongTho) {
                $duLieu = array();

                foreach ($viTri as $the => $chiSo) {
                    $duLieu[$the] = array_key_exists($chiSo, $dongTho)
                        ? Tt12DocExcel::chuanHoaO($dongTho[$chiSo])
                        : '';
                }

                // Dien MA_CSKCB con trong theo co so cua ho so. Tt12MaCskcbTrongTep da
                // khang dinh moi o CO GIA TRI deu trung co so nay, nen dien vao la lam ro
                // dieu von da dung chu khong phai doan.
                //
                // Dien o day chu khong de trong roi bat loi o buoc kiem: MA_CSKCB la the
                // BAT BUOC, de trong thi moi dong sinh mot loi THIEU_BAT_BUOC va nguoi
                // dung phai tu di sua ca tep cho mot cot ma ho da chon tren man hinh.
                if (array_key_exists('MA_CSKCB', $duLieu) && $duLieu['MA_CSKCB'] === '') {
                    $duLieu['MA_CSKCB'] = (string) $hoSo->ma_cskcb;
                }

                $dong = Tt12Dong::create(array(
                    'ho_so_id' => $hoSo->id,
                    'stt'      => $stt,
                    'du_lieu'  => $duLieu,
                    'ma'       => $this->maCua($lop, $duLieu),
                    'ten'      => $this->tenCua($lop, $duLieu),
                    'tu_ngay'  => isset($duLieu['TU_NGAY']) ? $duLieu['TU_NGAY'] : null,
                    'den_ngay' => isset($duLieu['DEN_NGAY']) ? $duLieu['DEN_NGAY'] : null,
                ));

                $this->ghiBangCon($dong, $dongTho, $viTriCon);

                $stt++;
                $daGhi++;
            }
        });

        return $daGhi;
    }

    /** Danh dau ho so da nap xong */
    public function ketThuc(Tt12HoSo $hoSo, $soDong)
    {
        $hoSo->update(array(
            'so_dong'     => $soDong,
            'imported_at' => Carbon::now(),
        ));
    }

    /**
     * Ghi mot dong bang con, CHI khi dong do co du lieu.
     *
     * Dong MAU_05 khong co thuoc phong xa se de trong ca 12 cot THUOCPX_*. Sinh ban ghi
     * rong cho nhung dong do se lam XML co the <TT_THUOCPX> rong o moi dich vu ky thuat
     * thong thuong.
     */
    private function ghiBangCon(Tt12Dong $dong, array $dongTho, array $viTriCon)
    {
        if ($viTriCon === array()) {
            return;
        }

        $duLieu = array();
        $coGiaTri = false;

        foreach ($viTriCon as $the => $chiSo) {
            $giaTri = array_key_exists($chiSo, $dongTho)
                ? Tt12DocExcel::chuanHoaO($dongTho[$chiSo])
                : '';

            $duLieu[strtolower($the)] = $giaTri;

            // STT khong tinh la "co du lieu": nguoi dung thuong danh so thu tu san cho ca
            // cot rong. Tinh no vao thi moi dong deu sinh mot ban ghi con rong.
            if ($giaTri !== '' && $the !== 'STT') {
                $coGiaTri = true;
            }
        }

        if (!$coGiaTri) {
            return;
        }

        $duLieu['dong_id'] = $dong->id;
        $duLieu['stt']     = $duLieu['stt'] === '' ? 1 : (int) $duLieu['stt'];

        Tt12DongThuocPx::create($duLieu);
    }

    /** Gia tri cot 'ma' rut ra, hoi lop dac ta chu khong doan theo vi tri */
    private function maCua($lop, array $duLieu)
    {
        $the = $lop::theMa();

        return isset($duLieu[$the]) ? $duLieu[$the] : null;
    }

    /** Gia tri cot 'ten' rut ra */
    private function tenCua($lop, array $duLieu)
    {
        $the = $lop::theTen();

        return isset($duLieu[$the]) ? $duLieu[$the] : null;
    }
}
