<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\System\email_receive_report;
use App\Models\CheckBHYT\check_hein_card;
use App\Models\BHYT\Qd130XmlErrorResult;

class sendQd130XmlErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendqd130xmlerrors:day {--yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Daily email to all users with a word and its meaning';

    /**
     * Create a new command instance.
     *
     * @param Xml1Checker $xml1Checker
     * @param Xml2Checker $xml2Checker
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
        $this->checkJobQueue();

        $this->info('Bắt đầu kiểm tra hồ sơ');
            
        // Lấy ngày hiện tại hoặc ngày hôm qua
        $currentDate = new \DateTime();
        if ($this->option('yesterday')) {
            $currentDate->modify('-1 day');
        }

        $fromDateTime = $currentDate->format('Y-m-d 00:00:00');
        $toDateTime = $currentDate->format('Y-m-d 23:59:59');

        $fromDate = $currentDate->format('Ymd') . '0000';
        $toDate = $currentDate->format('Ymd') . '2359';


        $this->info('Kiểm tra Thông tin thẻ lỗi');
        // Gọi hàm kiểm tra thẻ lỗi
        $errorsHeinCard = $this->getCheckHeinCardErrors($fromDateTime, $toDateTime);

        $this->info('Kiểm tra lỗi XML');
        // Gọi hàm kiểm tra lỗi XML
        $errorsXml = $this->getCheckXmlErrors($fromDateTime, $toDateTime);

        // Gọi hàm gửi email
        $this->sendEmails($fromDate, $toDate, 
            $errorsHeinCard, 
            $errorsXml
        );

        $this->info('Đã gửi báo cáo đến tất cả người nhận');
    }

    /**
     * Check Job Queue and Send Notification if needed.
     */
    private function checkJobQueue()
    {
        $i = 1;
        do {
            if (\Queue::size('JobKtTheBHYT') == 0) {
                $i = 10;
            } else {
                $this->info('Chưa hoàn thành việc kiểm tra thẻ BHYT');
                try {
                    Mail::raw("Chưa hoàn thành việc kiểm tra thẻ BHYT. Hệ thống hỗ trợ GĐ BHYT sẽ chạy lại sau 5 phút kể từ " . now(), function ($mail) {
                        $mail->to('tracnn20021979@gmail.com')
                             ->subject('Trạng thái kiểm tra thẻ BHYT ' . now());
                    });
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
                sleep(300);
            }
        } while ($i < 10);
    }

    /**
     * Get Check Hein Card Errors
     *
     * @param string $fromDateTime
     * @param string $toDateTime
     * @return \Illuminate\Support\Collection
     */
    private function getCheckHeinCardErrors($fromDateTime, $toDateTime)
    {
        return check_hein_card::where(function($query) {
            $query->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
            ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
        })
        ->whereBetween('updated_at', [$fromDateTime, $toDateTime])
        ->get();
    }

    private function getCheckXmlErrors($fromDateTime, $toDateTime)
    {
        return Qd130XmlErrorResult::whereBetween('updated_at', [$fromDateTime, $toDateTime])
        ->get();
    }

    /**
     * Send Emails
     *
     * @param string $fromDate
     * @param string $toDate
     * @param \Illuminate\Support\Collection $errorsHeinCard
     * @return void
     */
    private function sendEmails($fromDate, $toDate, 
        $errorsHeinCard,
        $errorsXml
    )
    {
        $emails = email_receive_report::where('active', 1)
            ->where('bcaobhxh', 1)->get();

        foreach ($emails as $key => $value) { 
            $i = 1;
            do {
                try {
                    Mail::send('templates.mail-qd130-errors', array(
                        'name' => $value->name,
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                        'email' => $value->email,
                        'errorsHeinCard' => $errorsHeinCard,
                        'errorsXml' => $errorsXml
                    ),
                    function ($message) use ($value, $fromDate, $toDate) {        
                        $message->to($value->email);
                        $message->subject('Hệ thống Hỗ trợ Giám định BHYT - Từ: ' . strtodate($fromDate) .' đến: ' . strtodate($toDate) . ' (' . date('H:i d/m') . ')');
                    });
                    $this->info('Đã gửi tới: ' . $value->email);                    
                    $i = 10;
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    $i++;
                }
            } while ($i < 10);
        }
    }
}
