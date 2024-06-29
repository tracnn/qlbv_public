<?php

namespace App\Listeners;

use App\Events\MedicalRegister;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Mail;
use App\Mail\submitMedReg;

class SendMailMedicalRegister implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MedicalRegister  $event
     * @return void
     */
    public function handle(MedicalRegister $event)
    {
        Mail::to($event->medReg->email)->send(new submitMedReg($event->medReg));
    }
}
