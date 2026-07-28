<?php

namespace App\Jobs;

use App\Services\Xml3176Xml1Checker;
use App\Services\Xml3176Xml2Checker;
use App\Services\Xml3176Xml3Checker;
use App\Services\Xml3176Xml4Checker;
use App\Services\Xml3176Xml5Checker;

use App\Services\Xml3176Xml7Checker;
use App\Services\Xml3176Xml8Checker;
use App\Services\Xml3176Xml9Checker;
use App\Services\Xml3176Xml10Checker;
use App\Services\Xml3176Xml11Checker;
use App\Services\Xml3176Xml13Checker;
use App\Services\Xml3176Xml14Checker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * KHONG CON DUOC DISPATCH. Giu lai de hang doi rut can.
 *
 * Tu 2026-07-28, viec kiem loi do CheckXml3176TypeJob dam nhan: mot job cho moi cap
 * (ho so, loai XML) thay vi mot job moi DONG. Xem
 * docs/superpowers/specs/2026-07-28-xml3176-import-pha-4-hang-doi-kiem-loi-design.md
 *
 * Lop nay tung bi xoa han, va viec do gay loi that tren san xuat: tai thoi diem deploy,
 * hang doi con job cu dang cho: mat lop thi chung KHONG unserialize duoc, roi vao
 * failed_jobs, keo theo mat ket qua kiem loi cua nhung ho so vua nhap.
 *
 * CHI duoc xoa khi da chac chan hang doi khong con job nao thuoc lop nay:
 *   SELECT COUNT(*) FROM jobs        WHERE queue = 'JobXml3176' AND payload LIKE '%CheckXml3176ErrorsJob%';
 *   SELECT COUNT(*) FROM failed_jobs WHERE payload LIKE '%CheckXml3176ErrorsJob%';
 * Ca hai deu bang 0 thi moi duoc bo.
 */
class CheckXml3176ErrorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $xmlData;
    protected $xmlType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($xmlData, $xmlType)
    {
        $this->xmlData = $xmlData;
        $this->xmlType = $xmlType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->xmlType) {
            case 'XML1':
                $checker = app(Xml3176Xml1Checker::class);
                break;
            case 'XML2':
                $checker = app(Xml3176Xml2Checker::class);
                break;
            case 'XML3':
                $checker = app(Xml3176Xml3Checker::class);
                break;
            case 'XML4':
                $checker = app(Xml3176Xml4Checker::class);
                break;
            case 'XML5':
                $checker = app(Xml3176Xml5Checker::class);
                break;
            case 'XML7':
                $checker = app(Xml3176Xml7Checker::class);
                break;
            case 'XML8':
                $checker = app(Xml3176Xml8Checker::class);
                break;
            case 'XML9':
                $checker = app(Xml3176Xml9Checker::class);
                break;
            case 'XML10':
                $checker = app(Xml3176Xml10Checker::class);
                break;
            case 'XML11':
                $checker = app(Xml3176Xml11Checker::class);
                break;
            case 'XML13':
                $checker = app(Xml3176Xml13Checker::class);
                break;
            case 'XML14':
                $checker = app(Xml3176Xml14Checker::class);
                break;
            // Add more cases for other XML types
            default:
                throw new \Exception("Unknown XML type: " . $this->xmlType);
        }

        $checker->checkErrors($this->xmlData);
    }
}
