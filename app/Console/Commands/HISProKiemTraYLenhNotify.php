<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\OrderCheck\OrderCheckNotifier;

class HISProKiemTraYLenhNotify extends Command
{
    protected $signature = 'kiemtraylenh:notify {--once : Chay 1 lan roi thoat (mac dinh lap lien tuc cho nssm service)}';

    protected $description = 'Gui email digest cac vi pham y lenh moi (theo chu ky)';

    public function handle(OrderCheckNotifier $notifier)
    {
        if ($this->option('once')) {
            $this->runOnce($notifier);
            return 0;
        }

        $sleep = (int) config('order_check.notify_sleep_interval', 3600);
        $this->info("Bat dau gui digest dinh ky, sleep {$sleep}s");

        do {
            try {
                $this->runOnce($notifier);
            } catch (\Exception $e) {
                $this->error('Loi: ' . $e->getMessage());
                Log::error('Order check notify error', ['error' => $e->getMessage()]);
            }
            sleep($sleep);
        } while (true);
    }

    protected function runOnce(OrderCheckNotifier $notifier)
    {
        $r = $notifier->run();
        $this->info(sprintf('Digest: %s, %d vi pham, %d nguoi nhan', $r['status'], $r['count'], $r['recipients']));
    }
}
