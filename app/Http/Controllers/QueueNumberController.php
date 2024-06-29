<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\QueueNumber;
use App\Http\Controllers\SMSController;

class QueueNumberController extends Controller
{
    public function index(Request $request)
    {
        $currentQueueNumber = number_format($this->maxQueueNumber(strtoupper($request->maKhoa)));

        return view('queue.index', compact('currentQueueNumber'));
    }
    
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'maKhoa' => 'required|string|max:255',
            'phoneNumber' => 'required|string|max:255',
        ]);
        
        $currentQueueNumber = $this->maxQueueNumber(strtoupper($request->maKhoa));

        if ($this->isPatientAlreadyQueued($request->phoneNumber)) {
            return ['maKetQua' => '2',
                'soHienTai' => number_format($currentQueueNumber),
                'noiDung' => 'Số điện thoại ' . $request->phoneNumber . ' đã đăng ký rồi'
            ];
        }

        $queueNumber = QueueNumber::create([
            'department_code' => strtoupper($request->maKhoa),
            'phone_number' => $request->phoneNumber,
            'number' => $currentQueueNumber + 1,
            'is_sms_sended' => false,
        ]);

        if ($queueNumber) {
            $content = $queueNumber->department_code . ' - SO THU TU DANG KY CUA BAN LA: ' . $queueNumber->number
            . ' (AP DUNG NGAY ' . $queueNumber->created_at->format('d/m/Y');
            $content = $queueNumber->number . 'la ma xac minh dang ky Baotrixemay cua ban';
            //return SMSController::sendSMS('ESMS', $queueNumber->number, $content);
            return ['maKetQua' => '1',
                'soHienTai' => number_format($queueNumber->number),
                'noiDung' => 'Số thứ tự đăng ký khám đã gửi vào số điện thoại ' .$request->phoneNumber
            ];
        }  
    }

    private function maxQueueNumber($maKhoa)
    {
        $from = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $to = date('Y-m-d H:i:s', mktime(23, 59, 59, date("m"), date("d"), date("Y")));
        $maxQueueNumber = DB::table('queue_numbers')
        ->where('department_code', $maKhoa)
        ->whereBetween('created_at', [$from, $to])
        ->max('number');
        
        if ($maxQueueNumber !== null) {
            return $maxQueueNumber;
        } else {
            return 0;
        }
    }

    private function isPatientAlreadyQueued($phoneNumber)
    {
        $from = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $to = date('Y-m-d H:i:s', mktime(23, 59, 59, date("m"), date("d"), date("Y")));
        
        return DB::table('queue_numbers')
        ->where('phone_number', $phoneNumber) 
        ->whereBetween('created_at', [$from, $to])
        ->exists();
    }

    private function checkEligibility(Request $request) {

    }

    public function manager()
    {
        $queueNumbers = QueueNumber::orderBy('created_at', 'desc')->paginate(10);
        return view('queue.manage', compact('queueNumbers'));
    }
}
