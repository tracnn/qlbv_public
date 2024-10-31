<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestartStuckJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:restart-stuck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart jobs that have reached 8 or more attempts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::table('jobs')
        ->where('attempts', '>=', 8)
        ->update(['attempts' => 0]);

        $this->info($this->description);
    }
}
