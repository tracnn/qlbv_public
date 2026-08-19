<?php

namespace App\Services\Ctdt\Loi;

/**
 * Ma co so KCB da phan giai duoc (tu XML, tuy chon, hoac cau hinh don vi) nhung vuot qua
 * do dai cot ctdt_ho_so.macskcb varchar(5). SQLite cua test khong cuong che do dai nen loi
 * nay khong tu lo ra o do; tren MySQL strict mode se la QueryException khong mang
 * CtdtLoiNap, con che do long se cat cut im lang va gui sai ma co so len cong BHXH. Ca hai
 * deu te hon tu choi som tai day.
 */
class MacskcbKhongHopLeException extends \RuntimeException implements CtdtLoiNap
{
}
