<?php

namespace Tests\Unit\Xml3176\Chuoi;

use Tests\TestCase;

/**
 * Chot an toan cho moi job trong chuoi.
 *
 * $tries: queue:work mac dinh --tries=0 la thu lai VO HAN; moi hang doi chi mot worker nen
 * mot ho so doc chan dung ca hang doi.
 *
 * $timeout < retry_after: lon hon thi hang doi giao lai job cho luot thu hai khi luot dau con
 * chay - voi job gui la HAI lan POST that len cong BHXH (xem chu thich config/queue.php).
 */
class ThoiHanJobChuoiTest extends TestCase
{
    private $cacJob = [
        \App\Jobs\CheckXml3176TypeJob::class,
        \App\Jobs\CheckCompleteXml3176RecordJob::class,
        \App\Jobs\ExportXml3176Job::class,
        \App\Jobs\SignXml3176Job::class,
        \App\Jobs\SubmitXml3176Job::class,
    ];

    private function mau($lop)
    {
        return (new \ReflectionClass($lop))->newInstanceWithoutConstructor();
    }

    /** @test */
    public function moi_job_thu_lai_huu_han()
    {
        foreach ($this->cacJob as $lop) {
            $this->assertGreaterThanOrEqual(1, (int) $this->mau($lop)->tries,
                class_basename($lop) . ' khong khai $tries - se thu lai vo han');
        }
    }

    /** @test */
    public function moi_job_het_han_truoc_retry_after()
    {
        $retryAfter = config('queue.connections.database.retry_after');
        $this->assertSame(300, $retryAfter, 'retry_after doi - doc lai chu thich config/queue.php');

        foreach ($this->cacJob as $lop) {
            $timeout = (int) $this->mau($lop)->timeout;
            $this->assertGreaterThan(0, $timeout, class_basename($lop) . ' khong khai $timeout');
            $this->assertLessThan($retryAfter, $timeout, class_basename($lop) . ' co the bi chay hai lan');
        }
    }
}
