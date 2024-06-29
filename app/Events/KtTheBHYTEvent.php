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

class KtTheBHYTEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stt;
    public $count;
    public $hotenbn;
    public $sothe;
    public $ngaysinh;
    public $message;
    public $message1;
    public $channel;
    public $macskcb;
    public $thoihantu;
    public $thoihanden;

    public $ma_lk;
    public $ngay_vao;
    public $ngay_ra;
    public $ngay_ttoan;
    public $ma_khoa;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($stt, $count, $ma_lk, $ngay_vao, 
        $ngay_ra, $ngay_ttoan, $ma_khoa, $hotenbn,
        $sothe, $ngaysinh, $macskcb, $thoihantu, $thoihanden, 
        $message, $message1, $channel)
    {
        $this->stt = $stt;
        $this->count = $count;
        $this->ma_lk = $ma_lk;
        $this->ngay_vao = $ngay_vao;
        $this->ngay_ra = $ngay_ra;
        $this->ngay_ttoan = $ngay_ttoan;
        $this->ma_khoa = $ma_khoa;
        $this->hotenbn = $hotenbn;
        $this->sothe = $sothe;
        $this->ngaysinh = $ngaysinh;
        $this->message = $message;
        $this->message1 = $message1;
        $this->channel = $channel;
        $this->macskcb = $macskcb;
        $this->thoihantu = $thoihantu;
        $this->thoihanden = $thoihanden;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('KtTheBHYT'.$this->channel);
    }
}
