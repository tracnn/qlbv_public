<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\OrderCheck\OrderCheckEngine;

class HISProKiemTraYLenh extends Command
{
    protected $signature = 'kiemtraylenh:scan
        {--limit= : So phieu toi da moi lan quet}
        {--once : Chay 1 lan roi thoat (mac dinh lap lien tuc cho nssm service)}';

    protected $description = 'Quet y lenh moi tu HIS va kiem tra sai sot (Ho luat B)';

    public function handle(OrderCheckEngine $engine)
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($this->option('once')) {
            $this->runOnce($engine, $limit);
            return 0;
        }

        $sleepInterval = (int) config('order_check.scan_sleep_interval', 60);
        $this->info("Bat dau quet y lenh dinh ky, sleep {$sleepInterval}s");

        do {
            try {
                $this->runOnce($engine, $limit);
            } catch (\Exception $e) {
                $this->error('Loi: ' . $e->getMessage());
                Log::error('Order check scan error', ['error' => $e->getMessage()]);
            }
            sleep($sleepInterval);
        } while (true);
    }

    protected function runOnce(OrderCheckEngine $engine, $limit)
    {
        $start = microtime(true);
        $result = $engine->run($limit);
        $sec = round(microtime(true) - $start, 2);

        $this->info(sprintf(
            'Quet xong: %d phieu, %d vi pham moi, %ss',
            $result['scanned'], $result['violations'], $sec
        ));
    }
}
