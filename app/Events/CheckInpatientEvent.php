<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CheckInpatientEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stt;
    public $count;
    public $hotenbn;
    public $sothe;
    public $ngaysinh;
    public $tenkhp;
    public $trangthai;
    public $doituong;
    public $message;
    public $message1;
    public $channel;
    public $macskcb;
    public $thoihantu;
    public $thoihanden;
    public $ngay;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($stt, $count, $hotenbn, $sothe, $ngaysinh, $macskcb, $thoihantu, $thoihanden, $ngay, $tenkhp, $trangthai, $doituong, $message, $message1, $channel)
    {
        $this->stt = $stt;
        $this->count = $count;
        $this->hotenbn = $hotenbn;
        $this->sothe = $sothe;
        $this->ngaysinh = $ngaysinh;
        $this->tenkhp = $tenkhp;
        $this->trangthai = $trangthai;
        $this->doituong = $doituong;
        $this->message = $message;
        $this->message1 = $message1;
        $this->channel = $channel;
        $this->macskcb = $macskcb;
        $this->thoihantu = $thoihantu;
        $this->thoihanden = $thoihanden;
        $this->ngay = $ngay;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('CheckInpatient'.$this->channel);
    }
}
