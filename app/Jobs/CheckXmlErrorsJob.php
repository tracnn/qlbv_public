<?php

namespace App\Jobs;

use App\Models\BHYT\XML1;
use App\Services\Xml1Checker;

use App\Models\BHYT\XML2;
use App\Services\Xml2Checker;

use App\Models\BHYT\XML3;
use App\Services\Xml3Checker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckXmlErrorsJob implements ShouldQueue
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
                $checker = app(Xml1Checker::class);
                break;
            case 'XML2':
                $checker = app(Xml2Checker::class);
                break;
            case 'XML3':
                $checker = app(Xml3Checker::class);
                break;
            // Add more cases for other XML types
            default:
                throw new \Exception("Unknown XML type: " . $this->xmlType);
        }

        $checker->checkErrors($this->xmlData);
    }
}
