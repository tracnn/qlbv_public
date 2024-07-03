<?php

namespace App\Jobs;

use App\Services\Qd130Xml1Checker;
use App\Services\Qd130Xml2Checker;
use App\Services\Qd130Xml3Checker;


use App\Services\Qd130Xml13Checker;
use App\Services\Qd130Xml14Checker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckQd130XmlErrorsJob implements ShouldQueue
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
                $checker = app(Qd130Xml1Checker::class);
                break;
            case 'XML2':
                $checker = app(Qd130Xml2Checker::class);
                break;
            case 'XML3':
                $checker = app(Qd130Xml3Checker::class);
                break;
            case 'XML13':
                $checker = app(Qd130Xml13Checker::class);
                break;
            case 'XML14':
                $checker = app(Qd130Xml14Checker::class);
                break;
            // Add more cases for other XML types
            default:
                throw new \Exception("Unknown XML type: " . $this->xmlType);
        }

        $checker->checkErrors($this->xmlData);
    }
}
