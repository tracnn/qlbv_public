<?php

namespace App\Services\Ctdt\Loi;

/**
 * Dau hieu chung cho moi loi NAP luong truoc duoc: file hong, loai la, the goc lech,
 * thieu ma co so, khong suy duoc khoa ho so.
 *
 * VI SAO LA INTERFACE DANH DAU chu khong phai mot lop cha: doi lop cha se lam do cac test
 * cua Giai doan 1 vang khang dinh cho() nem InvalidArgumentException. Interface them dau
 * hieu ma khong dong toi cay ke thua san co.
 *
 * VI SAO CAN: CtdtImporter bat MOT menh de `catch (CtdtLoiNap $e)` de ghi nhan ho so hong
 * roi di tiep sang ho so ke. Neu moi loi mot lop cha khac nhau, catch mot loai se de loai
 * kia thoat ra va KEO DO CA GOI - dung dieu ma "mot HOSO hong khong keo HOSO khac" cam.
 *
 * Loi KHONG luong truoc (loi lap trinh, mat ket noi CSDL) co y KHONG mang dau hieu nay:
 * chung phai noi len de nguoi van hanh thay, khong duoc ghi thanh "ho so nay hong".
 */
interface CtdtLoiNap
{
}
