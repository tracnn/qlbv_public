<?php

namespace Tests\Support;

use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Dung chuoi XML goi chung tu dien tu cho test.
 *
 * VI SAO DUNG BANG MA chu khong de tep fixture: moi test can mot bien the khac nhau
 * (thieu the, sai the goc, hai ho so, ho so khong co MA_YTE). Voi tep fixture thi moi
 * bien the la mot tep, va sua dac ta se phai sua muoi may tep. Ham dung cho phep test
 * noi ro NO KHAC gi so voi goi hop le - dieu ma mot tep fixture khong noi duoc.
 */
trait GoiCtdtMau
{
    /**
     * Bo truong CT03 DU 18 TRUONG BAT BUOC - dung cho test can mot ho so HOP LE.
     *
     * VI SAO TAP TRUNG MOT CHO: truoc day moi test tu go danh sach truong cua no. Dot siet
     * 2026-09-07 them muoi truong bat buoc, va 14 test do cung mot luc - moi test deu "dung"
     * vao ngay hom truoc. Gom ve mot ham thi lan siet sau chi phai sua o day.
     *
     * Test nao CO Y dung mot ho so THIEU truong thi dung tu go danh sach nhu cu, dung goi
     * ham nay - de doc len thay ngay no thieu gi.
     *
     * @param array $ghiDe De trong mot truong: truyen ['PP_DIEUTRI' => '']
     */
    protected function truongCt03HopLe(array $ghiDe = [])
    {
        return array_merge([
            'MA_YTE'    => 'YT001',
            'MA_BHXH'   => '0123456789',
            'MA_KHOA'   => 'K01',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'GIOI_TINH' => '1',
            'DIA_CHI'   => 'Ha Noi',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
            'MA_THE'    => 'DN1234567890',

            // Muoi truong them o dot 2026-09-07 - xem docblock CtdtTruongBatBuoc.
            'PP_DIEUTRI'         => 'Dieu tri noi khoa',
            'CHAN_DOAN'          => 'U ac tinh o dai trang(C18.9)',
            'BENHICD10_ID'       => 'C18.9',
            'TENBENHICD10'       => 'U ac tinh o dai trang',
            'NGAY_CHUNG_TU'      => '20260907',
            'THU_TRUONG_DVI'     => 'Pham Cam Phuong',
            'TEN_TRUONGKHOA'     => 'Pham Van Dung',
            'MA_CCHN_TRUONGKHOA' => '004929/HNO-GPHN',
            'LOAI_GIAYTO'        => '1',
            'NGHE_NGHIEP'        => 'Khong xac dinh',
        ], $ghiDe);
    }

    /**
     * Mot chung tu de dua vao goiCt2025().
     *
     * @param string $loaiHoSo gia tri LOAIHOSO, vd 'CT03'
     * @param array  $truong   [TEN_THE => gia tri]
     */
    protected function chungTu($loaiHoSo, array $truong)
    {
        return ['loai_ho_so' => $loaiHoSo, 'truong' => $truong];
    }

    /**
     * Goi HSCHUNGTU.
     *
     * @param array $hoSo      Mang cac ho so; moi ho so la mang ket qua cua chungTu()
     * @param array $tuyChon   macskcb, ngay_lap, so_luong_ho_so, id, the_goc_ghi_de
     */
    protected function goiCt2025(array $hoSo, array $tuyChon = [])
    {
        $macskcb = array_key_exists('macskcb', $tuyChon) ? $tuyChon['macskcb'] : '01929';
        $ngayLap = array_key_exists('ngay_lap', $tuyChon) ? $tuyChon['ngay_lap'] : '20251101';
        $id      = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-goi-mau';
        $soLuong = array_key_exists('so_luong_ho_so', $tuyChon)
            ? $tuyChon['so_luong_ho_so']
            : count($hoSo);

        $khoiHoSo = '';

        foreach ($hoSo as $mot) {
            $khoiFile = '';

            foreach ($mot as $ct) {
                $theGoc = array_key_exists('the_goc_ghi_de', $tuyChon)
                    ? $tuyChon['the_goc_ghi_de']
                    : $this->theGocCua($ct['loai_ho_so']);

                $noiDung = $this->theChungTu($theGoc, $ct['truong']);

                $khoiFile .= '<FILEHOSO>'
                    . '<LOAIHOSO>' . $ct['loai_ho_so'] . '</LOAIHOSO>'
                    . '<NOIDUNGFILE>' . base64_encode($noiDung) . '</NOIDUNGFILE>'
                    . '</FILEHOSO>';
            }

            $khoiHoSo .= '<HOSO>' . $khoiFile . '</HOSO>';
        }

        $khoiDonVi = $macskcb === null
            ? '<THONGTINDONVI/>'
            : '<THONGTINDONVI><MACSKCB>' . $macskcb . '</MACSKCB></THONGTINDONVI>';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSCHUNGTU>'
            . $khoiDonVi
            . '<THONGTINHOSO Id="' . $id . '">'
            . '<NGAYLAP>' . $ngayLap . '</NGAYLAP>'
            . '<SOLUONGHOSO>' . $soLuong . '</SOLUONGHOSO>'
            . '<DANHSACHHOSO>' . $khoiHoSo . '</DANHSACHHOSO>'
            . '</THONGTINHOSO>'
            . '</HSCHUNGTU>';
    }

    /** Goi HSDLGBT - mot giay bao tu, noi dung nam thang trong the, khong base64. */
    protected function goiGbt(array $truong, array $tuyChon = [])
    {
        $id = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-gbt-mau';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSDLGBT>'
            . $this->theChungTu('GIAYBAOTU', $truong, ' Id="' . $id . '"')
            . '</HSDLGBT>';
    }

    /** Goi HSDLGCS - mot giay chung sinh. */
    protected function goiGcs(array $truong, array $tuyChon = [])
    {
        $id = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-gcs-mau';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSDLGCS>'
            . $this->theChungTu('GIAYCHUNGSINH', $truong, ' Id="' . $id . '"')
            . '</HSDLGCS>';
    }

    /** Ten the goc that su cua mot LOAIHOSO - lay tu chinh registry de test khong lech. */
    protected function theGocCua($loaiHoSo)
    {
        $lop = CtdtLoaiRegistry::cho($loaiHoSo);

        return $lop::theGoc();
    }

    private function theChungTu($theGoc, array $truong, $thuocTinh = '')
    {
        $ben = '';

        foreach ($truong as $ten => $giaTri) {
            $ben .= '<' . $ten . '>' . htmlspecialchars((string) $giaTri, ENT_XML1) . '</' . $ten . '>';
        }

        return '<' . $theGoc . $thuocTinh . '>' . $ben . '</' . $theGoc . '>';
    }
}
