<?php

namespace App\Services\Mcct;

/**
 * Cong tu choi xac thuc (HTTP 401) KE CA sau khi da lam moi token va goi lai.
 *
 * Can mot lop RIENG chu khong dung \Exception chung: phan hoi 401 cua cong co than RONG,
 * tu no khong noi duoc gi. Controller phai phan biet duoc truong hop nay voi loi mang de
 * hien dung nguyen nhan hay gap nhat - IP may goi khac IP luc lay token.
 */
class McctXacThucException extends \RuntimeException
{
}
