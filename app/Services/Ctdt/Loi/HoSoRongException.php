<?php

namespace App\Services\Ctdt\Loi;

/**
 * Ho so khong co chung tu nao (`<HOSO/>` rong, hoac tat ca FILEHOSO deu bi loai bo truoc
 * do). Bat buoc tai tang ghi CSDL: "mot ho so phai co it nhat mot chung tu" la bat bien
 * cua tang ghi, khong phai cua parser - moi duong vao tuong lai (Giai doan 2B, lenh
 * Console) deu di qua CtdtLuuHoSo::luu() nen deu duoc bao ve tai day.
 */
class HoSoRongException extends \RuntimeException implements CtdtLoiNap
{
}
