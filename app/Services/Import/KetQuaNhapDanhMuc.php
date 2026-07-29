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
    /** So dong loi giu lai de hien thi; van dem du o soLoi */
    const TOI_DA_DONG_LOI = 20;

    protected $soDaNhap = 0;
    protected $soDaCapNhat = 0;
    protected $soKhongDoi = 0;
    protected $soBoQua = 0;
    protected $soLoi = 0;

    /** @var array [['dong' => int, 'ly_do' => string], ...] */
    protected $dongLoi = [];

    public function themNhap()     { $this->soDaNhap++; }
    public function themCapNhat()  { $this->soDaCapNhat++; }
    public function themKhongDoi() { $this->soKhongDoi++; }

    /** @param int $dongExcel vi tri that trong tep, dong tieu de la 1 */
    public function themBoQua($dongExcel, $lyDo)
    {
        $this->soBoQua++;
        $this->ghiDongLoi($dongExcel, $lyDo);
    }

    public function themLoi($dongExcel, $lyDo)
    {
        $this->soLoi++;
        $this->ghiDongLoi($dongExcel, $lyDo);
    }

    protected function ghiDongLoi($dongExcel, $lyDo)
    {
        if (count($this->dongLoi) >= self::TOI_DA_DONG_LOI) {
            return;
        }

        $this->dongLoi[] = ['dong' => (int) $dongExcel, 'ly_do' => (string) $lyDo];
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
