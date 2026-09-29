<?php

namespace App\Jobs\Concerns;

use App\Models\BHYT\Xml3176Information;

/**
 * Phan chung cua moi job trong chuoi kiem -> xuat -> ky -> gui cua mot ho so XML3176.
 *
 * MA PHIEN (chain_token): moi lan dung mot chuoi moi cho ho so - do nap, hoac do lenh
 * xml3176:chay-lai-tu-xuat - sinh mot ma moi, ghi len ho so. Moi job mang ma cua chuoi da
 * sinh ra no. Viec DAU TIEN cua moi job la so ma: lech nghia la ho so da co chuoi moi hon,
 * job nay thoi va KHONG GHI GI - ho so thuoc ve chuoi moi.
 *
 * Vi sao can: nguoi dung nap lai rat thuong xuyen, hang doi kiem co the ton 90 phut
 * (do 29/09/2026). Job xuat cua lan nap truoc con nam cho se thay "khong co loi nghiem
 * trong" - vi nap lai vua xoa sach loi cu - va xuat du lieu moi chua ai kiem.
 *
 * JOB CU: job serialize boi ma truoc khi co chuoi (chainToken = null). Moi job tu quyet
 * dinh lam gi voi job cu - xem tung lop.
 *
 * CAT CHUOI: Laravel 5.5 chi day job ke tiep khi handle() chay xong khong nem
 * (CallQueuedHandler), va chi khi $chained con phan tu. Dung co chu dich thi dat
 * $chained = [] roi thoat binh thuong - khong nem, vi nem la ton luot thu va vao
 * failed_jobs cho mot viec khong he hong.
 */
trait ThuocChuoiXml3176
{
    /** @var string|null Ma phien cua chuoi da sinh ra job; null = job cu truoc nang cap */
    protected $chainToken = null;

    protected function laJobCu()
    {
        return $this->chainToken === null;
    }

    protected function conHieuLuc($maLk)
    {
        if ($this->chainToken === null) {
            return false;
        }

        return Xml3176Information::where('ma_lk', $maLk)->value('chain_token') === $this->chainToken;
    }

    protected function catChuoi()
    {
        $this->chained = [];
    }

    /**
     * Ghi len ho so CHI KHI chuoi nay con hieu luc. Dung trong failed(): loi cua chuoi cu
     * khong duoc de len trang thai cua chuoi moi.
     */
    protected function ghiNeuConHieuLuc($maLk, array $cot)
    {
        if ($this->conHieuLuc($maLk)) {
            Xml3176Information::where('ma_lk', $maLk)->update($cot);
        }
    }
}
