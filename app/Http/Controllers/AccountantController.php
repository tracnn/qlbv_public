<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentExport;
use Carbon\Carbon;

use DataTables;

class AccountantController extends Controller
{
    public function broadcast()
    {
        return view('emr.broadcast.index');
    }

    public function clearBroadcast()
    {
        $dataToSend = [
            'qrString' => '', // Chuỗi dùng để tạo QR code
            'amount' => 0, // Số tiền cần nộp
            'total_patient_price' => 0,
            'tam_ung' => 0,
            'hoan_ung' => 0,
            'da_thanh_toan' => 0,
        ];
        $jsonData = json_encode($dataToSend);

        $channelPusher = 'thu-ngan-' . \Auth::user()->id;

        event(new \App\Events\DemoPusherEvent($jsonData, $channelPusher));
    }

    public function checkout($id, Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {

            $treatment = DB::connection('HISPro')
            ->table('his_treatment')
            ->leftJoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->select('his_treatment.id', 'treatment_code', 'tdl_patient_name', 'tdl_patient_dob', 'tdl_patient_address',
                'tdl_patient_mobile', 'tdl_patient_phone', 'tdl_patient_relative_mobile', 'tdl_patient_relative_phone',
                'department_name')
            ->where('treatment_code', $id)
            ->first();

            if (!$treatment) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Treatment not found'
                ]);
            }

            $sere_serv_total =DB::connection('HISPro')
            ->table('his_sere_serv')
            ->where('tdl_treatment_id', $treatment->id)
            ->where('is_delete', 0)
            ->whereNull('is_expend')
            ->whereNull('is_no_pay')
            ->whereNull('is_no_execute')
            ->selectRaw('SUM(vir_total_price) AS total_price, SUM(vir_total_hein_price) AS total_hein_price, 
                SUM(vir_total_patient_price) AS total_patient_price')
            ->first();

            $transactions = DB::connection('HISPro')
            ->table('his_transaction')
            ->where('treatment_id', $treatment->id)
            ->whereNull('is_cancel')
            ->where('is_delete', 0)
            ->selectRaw('
                SUM(CASE WHEN transaction_type_id = 1 THEN amount ELSE 0 END) AS tam_ung,
                SUM(CASE WHEN transaction_type_id = 2 THEN amount ELSE 0 END) AS hoan_ung,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id IS NULL THEN amount ELSE 0 END) AS da_thanh_toan,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 2 THEN amount ELSE 0 END) AS tu_nhap,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 1 THEN amount ELSE 0 END) AS xuat_ban,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 3 THEN amount ELSE 0 END) AS vitamin_a
            ')
            ->first();

            $can_thanh_toan = floor($sere_serv_total->total_patient_price - 
            ($transactions->tam_ung - $transactions->hoan_ung + $transactions->da_thanh_toan));

            $phoneNumber = $treatment->tdl_patient_mobile ?: $treatment->tdl_patient_phone ?: 
                $treatment->tdl_patient_relative_mobile ?: $treatment->tdl_patient_relative_phone ?: '';

            $qrString = $this->QrSelfBuilder($can_thanh_toan < 0 ? 0 : $can_thanh_toan, 
                $treatment->treatment_code, 'Thanh Toan', $phoneNumber);
            // Kết hợp dữ liệu vào một đối tượng
            $dataToSend = [
                'qrString' => $qrString, // Chuỗi dùng để tạo QR code
                'amount' => $can_thanh_toan, // Số tiền cần nộp
                'total_patient_price' => $sere_serv_total->total_patient_price,
                'tam_ung' => $transactions->tam_ung,
                'hoan_ung' => $transactions->hoan_ung,
                'da_thanh_toan' => $transactions->da_thanh_toan,
                'is_payment' => true,
            ];

            // Chuyển đổi dữ liệu thành chuỗi JSON
            $jsonData = json_encode($dataToSend);
            
            $channelPusher = 'thu-ngan-' . \Auth::user()->id;

            event(new \App\Events\DemoPusherEvent($jsonData, $channelPusher));

            $partialView = view('emr.partials.payment_info', [
                'total_price' => $sere_serv_total->total_price,
                'total_hein_price' => $sere_serv_total->total_hein_price,
                'total_patient_price' => $sere_serv_total->total_patient_price,
                'tam_ung' => $transactions->tam_ung,
                'hoan_ung' => $transactions->hoan_ung,
                'da_thanh_toan' => $transactions->da_thanh_toan,
                'tu_nhap' => $transactions->tu_nhap,
                'can_thanh_toan' => $can_thanh_toan,
                // Bổ sung thông tin hành chính
                'treatment_code' => $treatment->treatment_code,
                'tdl_patient_name' => $treatment->tdl_patient_name,
                'tdl_patient_dob' => $treatment->tdl_patient_dob,
                'tdl_patient_address' => $treatment->tdl_patient_address,
                'tdl_patient_mobile' => $treatment->tdl_patient_mobile,
                'tdl_patient_phone' => $treatment->tdl_patient_phone,
                'tdl_patient_relative_mobile' => $treatment->tdl_patient_relative_mobile,
                'tdl_patient_relative_phone' => $treatment->tdl_patient_relative_phone,
                'department_name' =>$treatment->department_name,
                'qrString' => $qrString,
                'is_payment' => true,
            ])->render();
            
            /* Xử lý tạo QR Code động dựa vào số tiền BN cần thanh toán để tạo QR code động cho BN sử dụng Internet Banking*/
            
            return response()->json([
                'success' => true, 
                'message' => 'Thông tin thanh toán',
                'html' => $partialView
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        
    }

    public function deposit($id, Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {

            $treatment = DB::connection('HISPro')
            ->table('his_treatment')
            ->leftjoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->select('his_treatment.id', 'treatment_code', 'tdl_patient_name', 'tdl_patient_dob', 'tdl_patient_address',
                'tdl_patient_mobile', 'tdl_patient_phone', 'tdl_patient_relative_mobile', 'tdl_patient_relative_phone',
                'department_name')
            ->where('treatment_code', $id)
            ->first();

            if (!$treatment) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Treatment not found'
                ]);
            }

            $sere_serv_total =DB::connection('HISPro')
            ->table('his_sere_serv')
            ->where('tdl_treatment_id', $treatment->id)
            ->where('is_delete', 0)
            ->whereNull('is_expend')
            ->whereNull('is_no_pay')
            ->whereNull('is_no_execute')
            ->selectRaw('SUM(vir_total_price) AS total_price, SUM(vir_total_hein_price) AS total_hein_price, 
                SUM(vir_total_patient_price) AS total_patient_price')
            ->first();

            $transactions = DB::connection('HISPro')
            ->table('his_transaction')
            ->where('treatment_id', $treatment->id)
            ->whereNull('is_cancel')
            ->where('is_delete', 0)
            ->selectRaw('
                SUM(CASE WHEN transaction_type_id = 1 THEN amount ELSE 0 END) AS tam_ung,
                SUM(CASE WHEN transaction_type_id = 2 THEN amount ELSE 0 END) AS hoan_ung,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id IS NULL THEN amount ELSE 0 END) AS da_thanh_toan,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 2 THEN amount ELSE 0 END) AS tu_nhap,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 1 THEN amount ELSE 0 END) AS xuat_ban,
                SUM(CASE WHEN transaction_type_id = 3 AND sale_type_id = 3 THEN amount ELSE 0 END) AS vitamin_a
            ')
            ->first();

            $deposit_req =DB::connection('HISPro')
            ->table('his_deposit_req')
            ->where('treatment_id', $treatment->id)
            ->where('is_delete', 0)
            ->select('description','amount')
            ->orderBy('create_time', 'DESC')
            ->first();

            $can_thanh_toan = floor($deposit_req ? $deposit_req->amount : 0);

            $phoneNumber = $treatment->tdl_patient_mobile ?: $treatment->tdl_patient_phone ?: 
                $treatment->tdl_patient_relative_mobile ?: $treatment->tdl_patient_relative_phone ?: '';

            $qrString = $this->QrSelfBuilder($can_thanh_toan < 0 ? 0 : $can_thanh_toan, 
                $treatment->treatment_code, 'Tam Thu' , $phoneNumber);
            // Kết hợp dữ liệu vào một đối tượng
            $dataToSend = [
                'qrString' => $qrString, // Chuỗi dùng để tạo QR code
                'amount' => $can_thanh_toan, // Số tiền cần nộp
                'total_patient_price' => $sere_serv_total->total_patient_price,
                'tam_ung' => $transactions->tam_ung,
                'hoan_ung' => $transactions->hoan_ung,
                'da_thanh_toan' => $transactions->da_thanh_toan,
                'is_payment' => false,
            ];

            // Chuyển đổi dữ liệu thành chuỗi JSON
            $jsonData = json_encode($dataToSend);
            
            $channelPusher = 'thu-ngan-' . \Auth::user()->id;

            event(new \App\Events\DemoPusherEvent($jsonData, $channelPusher));

            $partialView = view('emr.partials.payment_info', [
                'total_price' => $sere_serv_total->total_price,
                'total_hein_price' => $sere_serv_total->total_hein_price,
                'total_patient_price' => $sere_serv_total->total_patient_price,
                'tam_ung' => $transactions->tam_ung,
                'hoan_ung' => $transactions->hoan_ung,
                'da_thanh_toan' => $transactions->da_thanh_toan,
                'tu_nhap' => $transactions->tu_nhap,
                'can_thanh_toan' => $can_thanh_toan,
                // Bổ sung thông tin hành chính
                'treatment_code' => $treatment->treatment_code,
                'tdl_patient_name' => $treatment->tdl_patient_name,
                'tdl_patient_dob' => $treatment->tdl_patient_dob,
                'tdl_patient_address' => $treatment->tdl_patient_address,
                'tdl_patient_mobile' => $treatment->tdl_patient_mobile,
                'tdl_patient_phone' => $treatment->tdl_patient_phone,
                'tdl_patient_relative_mobile' => $treatment->tdl_patient_relative_mobile,
                'tdl_patient_relative_phone' => $treatment->tdl_patient_relative_phone,
                'department_name' => $treatment->department_name,
                'qrString' => $qrString,
                'is_payment' => false,
            ])->render();
            
            /* Xử lý tạo QR Code động dựa vào số tiền BN cần thanh toán để tạo QR code động cho BN sử dụng Internet Banking*/
            
            return response()->json([
                'success' => true, 
                'message' => 'Thông tin thanh toán',
                'html' => $partialView
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        
    }

    private function QrSelfBuilder($amount, $treatmentCode, $purpose, $comsumerData)
    {
        /*
            ID (2 ký tự bắt đầu 00 -> nn) + Độ dài ký tự (2 ký tự ví dụ 02) + Giá trị của ID
            Ví dụ: $payLoad = '000201';
        */

        $payLoad = $this->buildFormattedString('00', '01'); // mặc định 01
        
        $pointOIMethod = $this->buildFormattedString('01', '12'); // mặc định

        /* Trung gian */
        $merchantCode = $this->buildFormattedString('00', 'A000000727'); // BIDV cung cấp cho merchant
        $merchantId = $this->buildFormattedString('01', '000697041801108609098888'); // BIDV cung cấp cho merchant
        $merchantCC = $this->buildFormattedString('02','QRIBFTTA'); // BIDV cung cấp cho merchant (loại hình doanh nghiệp)
        /* End */
        
        /*
            Thông tin định danh ĐVCNTT ID 38
        */
        $merchant = $this->buildFormattedString('38', $merchantCode . $merchantId . $merchantCC);
        
        
        $ccy = $this->buildFormattedString('53','704'); // cố định

        $amount = $this->buildFormattedString('54', $amount); // merchant tự điền

        $countryCode = $this->buildFormattedString('58', 'VN'); // mặc định
        $merchantCity = $this->buildFormattedString('60', 'HANOI'); // merchant tự điền theo chuẩn
        $portalCode = $this->buildFormattedString('61', '10000');

        /* Trung gian */
        $billNumber = $this->buildFormattedString('01', $treatmentCode); // số hóa đơn do merchant tự điền
        $terminalName = $this->buildFormattedString('03', 'BENH VIEN NN');
        $terminalID = $this->buildFormattedString('07', 'BVNN'); //mã điểm bán merchant tự đièn
        $purpose = $this->buildFormattedString('08', $treatmentCode . ' ' . $comsumerData . ' ' . $purpose); // mục đích của qr code (không quá 19 kí tự)
        $comsumerData = $this->buildFormattedString('09', $comsumerData);
        /* End */
        $additionalData = $this->buildFormattedString('62', $purpose);

        $checkSumCrc16 = $this->calculateCRC16(
            $payLoad .
            $pointOIMethod .
            $merchant .
            $ccy .
            $amount .
            $countryCode .
            $merchantCity .
            $portalCode .
            $additionalData .
            '6304'
        );

        $checkSumCode = $this->buildFormattedString('63', $checkSumCrc16);

        return (
            $payLoad .
            $pointOIMethod .
            $merchant .
            $ccy .
            $amount .
            $countryCode .
            $merchantCity .
            $portalCode .
            $additionalData .
            $checkSumCode
        );

    }

    // private function QrBuilder($amount, $billNumber, $purpose, $comsumerData)
    // {
    //     /*
    //         ID (2 ký tự bắt đầu 00 -> nn) + Độ dài ký tự (2 ký tự ví dụ 02) + Giá trị của ID
    //         Ví dụ: $payLoad = '000201';
    //     */

    //     $payLoad = $this->buildFormattedString('00', '01'); // mặc định 01
        
    //     $pointOIMethod = $this->buildFormattedString('01', '12'); // mặc định

    //     /* Trung gian */
    //     $merchantCode = $this->buildFormattedString('00', '970488'); // BIDV cung cấp cho merchant
    //     $merchantId = $this->buildFormattedString('01', '12345678'); // BIDV cung cấp cho merchant
    //     /* End */
        
    //     /*
    //         Thông tin định danh ĐVCNTT ID 38
    //     */
    //     $merchant = $this->buildFormattedString('26', $merchantCode . $merchantId);
        
    //     $merchantCC = $this->buildFormattedString('52','1234'); // BIDV cung cấp cho merchant (loại hình doanh nghiệp)
        
    //     $ccy = $this->buildFormattedString('53','704'); // cố định

    //     $amount = $this->buildFormattedString('54', $amount); // merchant tự điền

    //     $countryCode = $this->buildFormattedString('58', 'VN'); // mặc định
    //     $merchantName = $this->buildFormattedString('59', 'Test Benh Vien');
    //     $merchantCity = $this->buildFormattedString('60', 'HANOI'); // merchant tự điền theo chuẩn
    //     $portalCode = $this->buildFormattedString('61', '10000');

    //     /* Trung gian */
    //     $billNumber = $this->buildFormattedString('01', $billNumber); // số hóa đơn do merchant tự điền
    //     $terminalName = $this->buildFormattedString('03', 'BENH VIEN NN');
    //     $terminalID = $this->buildFormattedString('07', 'BVNN'); //mã điểm bán merchant tự đièn
    //     $purpose = $this->buildFormattedString('08', $purpose); // mục đích của qr code (không quá 19 kí tự)
    //     $comsumerData = $this->buildFormattedString('09', $comsumerData);
    //     /* End */
    //     $additionalData = $this->buildFormattedString('62', 
    //         $billNumber . 
    //         $terminalName .
    //         $terminalID .
    //         $purpose .
    //         $comsumerData
    //     );

    //     $checkSumCrc16 = $this->calculateCRC16(
    //         $payLoad .
    //         $pointOIMethod .
    //         $merchant .
    //         $merchantCC .
    //         $ccy .
    //         $amount .
    //         $countryCode .
    //         $merchantName .
    //         $merchantCity .
    //         $portalCode .
    //         $additionalData
    //     );

    //     $checkSumCode = $this->buildFormattedString('63', $checkSumCrc16);

    //     return (
    //         $payLoad .
    //         $pointOIMethod .
    //         $merchant .
    //         $merchantCC .
    //         $ccy .
    //         $amount .
    //         $countryCode .
    //         $merchantName .
    //         $merchantCity .
    //         $portalCode .
    //         $additionalData .
    //         $checkSumCode
    //     );

    // }

    private function calculateCRC16($message) {
        $crc = 0xFFFF; // Giá trị ban đầu
        $polynomial = 0x1021; // Đa thức 1021 (hex)

        for ($i = 0; $i < strlen($message); $i++) {
            $b = ord($message[$i]);
            for ($j = 0; $j < 8; $j++) {
                $bit = (($b >> (7 - $j)) & 1) == 1;
                $c15 = (($crc >> 15) & 1) == 1;
                $crc <<= 1;
                if ($c15 ^ $bit) {
                    $crc ^= $polynomial;
                }
            }
        }

        $crc &= 0xffff; // Giữ lại chỉ 16 bits
        return strtoupper(sprintf('%04X', $crc)); // Trả về giá trị hexa in hoa
    }

    private function buildFormattedString($id, $inputValue)
    {
        if (!$inputValue) {
            return '';
        }

        // Ensure the input value is a string
        $inputValueStr = (string) $inputValue;
        // Calculate the length of the input value string
        $length = strlen($inputValueStr);
        // Format the length to a 2-digit string
        $lengthStr = str_pad($length, 2, '0', STR_PAD_LEFT);
        // Concatenate ID, length and input value
        $formattedString = $id . $lengthStr . $inputValueStr;

        return $formattedString;
    }

    public function savePayment(Request $request) 
    {
        // Xác thực dữ liệu nhận được (nên xác thực dữ liệu ở đây)
        $validated = $request->validate([
            'treatment_code' => 'required',
            'tdl_patient_name' => 'required',
            'tdl_patient_dob' => 'required|date',
            'tdl_patient_address' => 'required',
            'tdl_patient_mobile' => 'nullable',
            'tdl_patient_relative_mobile' => 'nullable',
            'can_thanh_toan' => 'required|numeric',
            'department_name' => 'nullable',
            // Định nghĩa thêm các quy tắc xác thực nếu cần
        ]);

        try {
            // Lưu dữ liệu vào database
            $payment = new \App\Payment();
            $payment->treatment_code = $request->treatment_code;
            $payment->patient_name = $request->tdl_patient_name;
            $payment->patient_dob = $request->tdl_patient_dob;
            $payment->patient_address = $request->tdl_patient_address;
            $payment->patient_mobile = $request->tdl_patient_mobile;
            $payment->patient_relative_mobile = $request->tdl_patient_relative_mobile;
            $payment->is_payment = $request->is_payment ?: 0;
            $payment->amount = $request->can_thanh_toan;
            $payment->login_name = \Auth::user()->loginname;
            $payment->user_name = \Auth::user()->username;
            $payment->department_name = $request->department_name;
            $payment->save();
            return response()->json(['success' => true, 'message' => 'Hệ thống đã ghi nhận thành công !']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function paymentReport(Request $request)
    {
        return view('emr.accountant.reports.payment');
    }

    public function getPayment(Request $request)
    {
        $fromDate = $request['date_from'] ? date_format(date_create($request['date_from']),'Ymd000000') : 
        date_format(now(),'Ymd000000');
        $toDate = $request['date_to'] ? date_format(date_create($request['date_to']),'Ymd235959') : 
        date_format(now(),'Ymd235959');

        if ($request->has('treatment_code') && $request->get('treatment_code') != '') {
            $query = \App\Payment::where('treatment_code', $request->get('treatment_code'));
        } else {
            $query = \App\Payment::whereBetween('created_at', [$fromDate, $toDate]);
        }

        // Kiểm tra vai trò của người dùng và tùy chỉnh truy vấn
        if (\Auth::user()->hasRole(['superadministrator', 'thungan-tckt'])) {
        } elseif (\Auth::user()->hasRole('thungan')) {
            $query->where('login_name', \Auth::user()->loginname);
        } 
        elseif (!\Auth::user()->hasRole('superadministrator') && !\Auth::user()->hasRole('thungan-tckt')) {
            // Nếu người dùng không có vai trò 'superadministrator' hoặc 'thungan-tonghop', không trả về dữ liệu nào
            $query->where('id', null); // Điều kiện không tồn tại để không trả về bản ghi nào
        }
        // Đối với 'superadministrator' và 'thungan-tonghop', không cần điều chỉnh truy vấn vì họ có thể xem tất cả dữ liệu


        return DataTables::of($query)
            ->editColumn('is_payment', function ($payment) {
                return $payment->is_payment == 1 ? 'Thanh toán' : 'Tạm thu';
            })
            ->editColumn('patient_mobile', function ($payment) {
                return $payment->patient_mobile ?: $payment->patient_relative_mobile ?: '';
            })
            ->editColumn('amount', function ($payment) {
                return number_format($payment->amount);
            })
            ->editColumn('patient_address', function ($payment) {
                    // Chia chuỗi thành các phần, không cần giới hạn số lượng phần
                $addressParts = explode(',', $payment->patient_address);
                // Giới hạn số lượng phần địa chỉ tối đa là 4
                $addressParts = array_slice($addressParts, 0, 4);
                return implode(',', $addressParts); // Gộp lại thành chuỗi với tối đa 4 cấp địa chỉ
            })
            ->toJson();
    }

    public function exportPaymentExcel(Request $request)
    {
        $treatmentCode = $request->get('treatment_code');
        $fromDate = $request->get('tu_ngay');
        $toDate = $request->get('den_ngay');

        $fromDate = $fromDate ? Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay() : null;
        $toDate = $toDate ? Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay() : null;

        $fileName = 'payments_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new PaymentExport($treatmentCode, $fromDate, $toDate), $fileName);
    }

}
