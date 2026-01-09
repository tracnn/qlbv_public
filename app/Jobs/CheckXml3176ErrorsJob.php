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
