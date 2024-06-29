<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\WordOfTheDay::class,
        Commands\HISProBaoCaoQuanTri::class,
        Commands\HISProBaoCaoAdmin::class,
        Commands\HISProUpdateCV::class,
        Commands\HISProBaoCaoCacKhoa::class,
        Commands\LISRSDaySmsBs::class,
        Commands\LISRSDayKQBN::class,
        Commands\HISProBaoCaoDinhDuong::class,
        Commands\HISProSmsCSKH::class,
        Commands\EMRRSVanBanChoKy::class,
        Commands\HISProKiemTraTheBHYT::class,
        Commands\XML4210Import::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->everyMinute();
        $schedule->command('word:day')
            ->dailyAt('07:00');
        $schedule->command('baocaoquantri:day')
            ->dailyAt('07:00');
        $schedule->command('baocaoadmin:day')
            ->dailyAt('07:00');
        $schedule->command('updatecv:day')
            ->dailyAt('07:00');
        $schedule->command('baocaocackhoa:day')
            ->dailyAt('07:00');
        $schedule->command('daysmsbs:day')
            ->dailyAt('07:00');
        $schedule->command('daykqbn:day')
            ->dailyAt('07:00');
        $schedule->command('baocaodinhduong:day')
            ->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
