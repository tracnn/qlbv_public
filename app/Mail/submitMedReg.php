<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\MedReg\MedReg;

class submitMedReg extends Mailable
{
    use Queueable, SerializesModels;

    public $medReg;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(MedReg $medReg)
    {
        $this->medReg = $medReg;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('medreg.labels.title') .' ' .$this->medReg->name)->view('templates.mail');
    }
}
