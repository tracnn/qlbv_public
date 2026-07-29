<?php

namespace App\Services\Import;

/**
 * Gom ket qua mot lan nhap danh muc.
 *
 * Ly do ton tai: truoc day ba vong lap nhap deu catch { Log::error; continue; },
 * hasRequiredFields cung continue ma khong dem, va controller luon tra
 * 'File da upload va xu ly thanh cong!'. Mot tep co the nhap 0 dong ma giao dien van bao
 * thanh cong.
 */
class KetQuaNhapDanhMuc
{
    /**
     * So dong loi giu lai de bao cao; van DEM DU du vuot gioi han.
     *
     * 1.000 du cho moi tinh huong thuc te - do tren du lieu that, tep nhieu loi nhat moi
     * co 27 dong bo qua.
     */
    const TOI_DA_DONG_LOI = 1000;

    /** Loai dong hong */
    const LOAI_BO_QUA = 'bo_qua';
    const LOAI_LOI = 'loi';

    protected $soDaNhap = 0;
    protected $soDaCapNhat = 0;
    protected $soKhongDoi = 0;
    protected $soBoQua = 0;
    protected $soLoi = 0;

    /** @var array [['dong' => int, 'loai' => string, 'ly_do' => string], ...] */
    protected $dongLoi = [];

    public function themNhap()     { $this->soDaNhap++; }
    public function themCapNhat()  { $this->soDaCapNhat++; }
    public function themKhongDoi() { $this->soKhongDoi++; }

    /** @param int $dongExcel vi tri that trong tep, dong tieu de la 1 */
    public function themBoQua($dongExcel, $lyDo)
    {
        $this->soBoQua++;
        $this->ghiDongLoi($dongExcel, $lyDo, self::LOAI_BO_QUA);
    }

    public function themLoi($dongExcel, $lyDo)
    {
        $this->soLoi++;
        $this->ghiDongLoi($dongExcel, $lyDo, self::LOAI_LOI);
    }

    protected function ghiDongLoi($dongExcel, $lyDo, $loai)
    {
        if (count($this->dongLoi) >= self::TOI_DA_DONG_LOI) {
            return;
        }

        // Tach BO QUA (thieu truong bat buoc) khoi LOI (nem ngoai le khi ghi): hai loai nay
        // sua theo hai cach khac nhau.
        $this->dongLoi[] = [
            'dong' => (int) $dongExcel,
            'loai' => $loai,
            'ly_do' => (string) $lyDo,
        ];
    }

    /** Co ghi duoc gi vao co so du lieu khong */
    public function coGhi()
    {
        return $this->soDaNhap > 0 || $this->soDaCapNhat > 0;
    }

    public function toArray()
    {
        return [
            'so_da_nhap' => $this->soDaNhap,
            'so_da_cap_nhat' => $this->soDaCapNhat,
            'so_khong_doi' => $this->soKhongDoi,
            'so_bo_qua' => $this->soBoQua,
            'so_loi' => $this->soLoi,
            'dong_loi' => $this->dongLoi,
        ];
    }

    public function tomTat()
    {
        return sprintf(
            'Đã thêm %d, cập nhật %d, không đổi %d, bỏ qua %d, lỗi %d.',
            $this->soDaNhap, $this->soDaCapNhat, $this->soKhongDoi, $this->soBoQua, $this->soLoi
        );
    }
}
