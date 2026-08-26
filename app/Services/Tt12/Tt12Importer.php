<?php

namespace App\Services\Tt12;

use Illuminate\Support\Facades\Log;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\Tt12\Loi\MaCskcbLechException;

/**
 * Diem vao DUY NHAT de nap mot tep danh muc TT12.
 *
 * VI SAO DUY NHAT: trong XML3176, nghiep vu nay tung duoc cai HAI lan - mot lan trong
 * controller tai len tay, mot lan trong lenh console quet thu muc - va hai ban DA LECH
 * NHAU. Cung mot tep cho hai ket qua khac nhau tuy duong vao. Module nay chi co mot
 * duong vao (man hinh tai len), va giu no o day de sau nay them duong thu hai cung
 * khong sinh ban thu hai.
 */
class Tt12Importer
{
    /** @var array header da chuan hoa cua tep dang doc */
    private $headerDaDoc = array();

    /** @var int so o MA_CSKCB de trong da duoc dien theo o chon, de bao lai cho nguoi dung */
    private $soODienThem = 0;

    /** @var Tt12DocExcel */
    private $doc;

    /** @var Tt12LuuHoSo */
    private $luu;

    public function __construct(Tt12DocExcel $doc = null, Tt12LuuHoSo $luu = null)
    {
        $this->doc = $doc ?: new Tt12DocExcel();
        $this->luu = $luu ?: new Tt12LuuHoSo();
    }

    /**
     * @param string $duongDan duong dan tep .xlsx tren dia
     * @param array  $tuyChon  ma_cskcb (bat buoc), ten_tep, imported_by, mau
     * @return Tt12ImportResult
     */
    public function nhapTuTep($duongDan, array $tuyChon = array())
    {
        // RESET TRANG THAI TRUOC MOI TEP. BHYTTt12Controller::uploadData() tao MOT importer
        // roi dung lai cho ca vong foreach nhieu tep - nguoi dung keo hai tep vao Dropzone
        // la mot thao tac binh thuong. Khong reset thi $soODienThem cong don, va man ket
        // qua bao tep thu hai "da tu dien 10 o" trong khi no khong co o trong nao: he
        // thong noi doi rang no da ghi vao 10 o cua mot tep no khong cham.
        $this->soODienThem = 0;
        $this->headerDaDoc = array();

        if (!is_file($duongDan) || !is_readable($duongDan)) {
            return Tt12ImportResult::thatBaiSom('Không đọc được tệp: ' . $duongDan);
        }

        $maCskcb = isset($tuyChon['ma_cskcb']) ? trim((string) $tuyChon['ma_cskcb']) : '';

        if ($maCskcb === '') {
            // Ma co so quyet dinh token dung de gui. Nap mot ho so khong co ma co so la
            // tao mot ho so khong bao gio gui duoc, va khong ai biet vi sao.
            return Tt12ImportResult::thatBaiSom('Thiếu mã cơ sở KCB');
        }

        $tenTep = isset($tuyChon['ten_tep']) ? $tuyChon['ten_tep'] : basename($duongDan);
        $nguoiNap = isset($tuyChon['imported_by']) ? $tuyChon['imported_by'] : null;
        $mauChon = isset($tuyChon['mau']) ? $tuyChon['mau'] : null;

        $mau = null;
        $hoSo = null;
        $tongDong = 0;

        try {
            $tongDong = $this->doc->doc(
                $duongDan,
                function (array $loDong, $sttBatDau) use (&$mau, &$hoSo, $maCskcb, $tenTep, $nguoiNap) {
                    if ($mau === null) {
                        // Header khong khop mau nao (Tt12MauRegistry::nhanDien() tra ve
                        // null) - KHONG phai loi noi bo: day la truong hop tep nguoi dung
                        // tai len sai dinh dang. Khong co lop dac ta de ghi du lieu, nen
                        // bo qua lo nay; vong kiem "$mau === null" sau khi doc xong ca tep
                        // se tra ve thong bao "khong nhan dien duoc" ro rang cho nguoi dung.
                        return;
                    }

                    $lop = Tt12MauRegistry::cho($mau);
                    $viTri = $this->viTriCot($lop);

                    // KIEM MA CO SO TRUOC KHI GHI, ngay tren lo dang doc. Mot ho so TT12
                    // duoc gui bang MOT token cua MOT co so, nen tep lan hai co so la tep
                    // khong co cach gui nao dung. Chan tai day thay vi de buoc kiem bat:
                    // de no nap vao roi bao loi sau chi tao ra mot ho so chet ma nguoi
                    // dung phai tu di xoa.
                    $ketQuaKiem = Tt12MaCskcbTrongTep::kiem(
                        $loDong,
                        isset($viTri['MA_CSKCB']) ? $viTri['MA_CSKCB'] : null,
                        $maCskcb,
                        $sttBatDau
                    );

                    if ($ketQuaKiem['lech'] !== array()) {
                        throw new MaCskcbLechException(
                            Tt12MaCskcbTrongTep::moTaLech($ketQuaKiem['lech'], $maCskcb),
                            $ketQuaKiem['lech']
                        );
                    }

                    $this->soODienThem += $ketQuaKiem['so_o_trong'];

                    if ($hoSo === null) {
                        $hoSo = $this->luu->batDau($mau, $maCskcb, $tenTep, $nguoiNap);
                    }

                    $this->luu->ghiLo(
                        $hoSo, $lop, $loDong, $viTri, $this->viTriCotCon($lop), $sttBatDau
                    );
                },
                function (array $header) use (&$mau, $mauChon) {
                    // Gan headerDaDoc TRUOC khi nhan dien: viTriCot() doc bien nay, va
                    // Tt12DocExcel goi ham nay truoc ham xu ly du lieu trong cung lo dau.
                    $this->headerDaDoc = array_map(
                        function ($o) { return strtoupper(trim((string) $o)); },
                        $header
                    );

                    $mau = $this->nhanDien($this->headerDaDoc, $mauChon);
                }
            );
        } catch (MaCskcbLechException $e) {
            // XOA sach thay vi giu lai kem import_error: ho so nay khong the sua cho dung
            // duoc bang bat ky thao tac nao tren man hinh - nguoi dung phai sua TEP roi
            // nap lai. Giu lai chi la de mot dong rac ma ho phai tu di don.
            Log::warning('TT12 nap tep bi tu choi (ma co so lech): ' . $e->getMessage(), array(
                'tep'      => $tenTep,
                'ma_cskcb' => $maCskcb,
                'so_dong_lech' => count($e->dongLech()),
            ));

            $this->doSach($hoSo);

            return Tt12ImportResult::thatBaiSom($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('TT12 nap tep that bai: ' . $e->getMessage(), array('tep' => $tenTep));

            if ($hoSo !== null) {
                // Giu lai ho so kem ly do de nguoi dung nhin thay va xoa. Xoa ngay o day
                // se lam nguoi dung thay man hinh khong co gi va khong hieu chuyen gi da
                // xay ra.
                $hoSo->update(array('import_error' => $e->getMessage()));

                return Tt12ImportResult::thatBai($hoSo->ma_ho_so, $mau, $e->getMessage());
            }

            return Tt12ImportResult::thatBaiSom($e->getMessage());
        }

        if ($mau === null) {
            return Tt12ImportResult::thatBaiSom(
                'Tệp không nhận diện được là mẫu nào của TT12. Kiểm tra hàng tiêu đề có '
                . 'đúng tên cột theo tệp mẫu không.'
            );
        }

        if ($hoSo === null || $tongDong === 0) {
            if ($hoSo !== null) {
                $hoSo->delete();
            }

            return Tt12ImportResult::thatBaiSom('Tệp không có dòng dữ liệu nào');
        }

        $this->luu->ketThuc($hoSo, $tongDong);

        // Kiem NGAY sau khi nap: nguoi dung mo man danh sach la thay so loi. Doi ho bam
        // mot nut "kiem" nua la them mot buoc de quen, va ho so chua kiem thi khong ky
        // duoc - nguoi dung se tuong chuc nang ky bi hong.
        dispatch(
            (new \App\Jobs\CheckTt12Job($hoSo->ma_ho_so))->onQueue(Tt12HangDoi::kiem())
        );

        return Tt12ImportResult::tot($hoSo->ma_ho_so, $mau, $tongDong, $this->soODienThem);
    }

    /**
     * Nhan dien mau tu header. Neu nguoi dung da CHON mau thi van doi chieu.
     *
     * Nguoi dung chon nham mau la chuyen thuong: sau tep .xlsx nhin gan giong nhau. Tin
     * lua chon cua ho ma khong doi chieu la de ca tep MAU_03 chay vao danh muc vat tu.
     *
     * @throws \RuntimeException khi lua chon cua nguoi dung lech voi noi dung tep
     */
    private function nhanDien(array $header, $mauChon)
    {
        $nhanDuoc = Tt12MauRegistry::nhanDien($header);

        if ($mauChon === null || $mauChon === '') {
            return $nhanDuoc;
        }

        if ($nhanDuoc === null) {
            throw new \RuntimeException(
                'Tệp không khớp mẫu nào; bạn đã chọn ' . $mauChon
                . '. Kiểm tra lại hàng tiêu đề.'
            );
        }

        if ($nhanDuoc !== $mauChon) {
            throw new \RuntimeException(
                'Bạn chọn ' . $mauChon . ' nhưng nội dung tệp là ' . $nhanDuoc . '.'
            );
        }

        return $nhanDuoc;
    }

    /**
     * Vi tri cot cua tung the trong hang du lieu.
     *
     * Thu tu cot trong tep NGUOI DUNG tai len co the khac thu tu dac ta - ho mo Excel va
     * keo cot. Ta da khang dinh o Tt12MauRegistry::nhanDien() rang TAP ten cot khop, nen
     * chi con viec tim vi tri tung cai.
     *
     * @return array [TEN_THE => chi so cot]
     */
    private function viTriCot($lop)
    {
        return $this->viTriTheo($lop::tenThe());
    }

    /** @return array [TEN_THE => chi so cot] cho bang con; rong neu mau khong co bang con */
    private function viTriCotCon($lop)
    {
        if ($lop::cotCon() === array()) {
            return array();
        }

        $viTri = array();

        foreach ($lop::tenTheCon() as $the) {
            $cotExcel = $lop::tienToCon() . $the;
            $chiSo = array_search($cotExcel, $this->headerDaDoc, true);

            if ($chiSo !== false) {
                $viTri[$the] = $chiSo;
            }
        }

        return $viTri;
    }

    /**
     * Don sach ho so da tao do dang.
     *
     * Uy thac cho Tt12XoaHoSo - lop DUY NHAT biet mot ho so treo nhung bang nao. Truoc day
     * doan xoa duoc viet ngay tai day, va ban thu hai trong BHYTTt12Controller::delete()
     * da bo sot tt12_dong_thuoc_px dung nhu ban dau tien tung bo sot. Mot ban cai dat thi
     * khong lech duoc.
     */
    private function doSach($hoSo)
    {
        (new Tt12XoaHoSo())->xoa($hoSo);
    }

    /** @return array [TEN_THE => chi so cot] */
    private function viTriTheo(array $theCanTim)
    {
        $viTri = array();

        foreach ($theCanTim as $the) {
            $chiSo = array_search($the, $this->headerDaDoc, true);

            if ($chiSo !== false) {
                $viTri[$the] = $chiSo;
            }
        }

        return $viTri;
    }
}
