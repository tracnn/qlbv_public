<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\System\email_receive_report;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderCheckNotifier
{
    /** Thứ hạng mức độ (lớn hơn = nặng hơn). */
    const SEVERITY_RANK = ['info' => 1, 'warning' => 2, 'critical' => 3];

    /**
     * Danh sách severity ≥ ngưỡng. Ngưỡng không hợp lệ → mặc định 'warning'.
     * @return string[]
     */
    public function severitiesToNotify($min)
    {
        $minRank = isset(self::SEVERITY_RANK[$min]) ? self::SEVERITY_RANK[$min] : self::SEVERITY_RANK['warning'];
        $out = [];
        foreach (self::SEVERITY_RANK as $sev => $rank) {
            if ($rank >= $minRank) {
                $out[] = $sev;
            }
        }
        return $out;
    }

    /** Vi phạm mới chưa thông báo, theo ngưỡng cấu hình. */
    public function pendingViolations()
    {
        $severities = $this->severitiesToNotify(config('order_check.notify_min_severity'));
        return OrderCheckViolation::whereNull('notified_at')
            ->where('status', 'new')
            ->whereIn('severity', $severities)
            ->orderByRaw("FIELD(severity,'critical','warning','info')")
            ->orderBy('detected_at')
            ->get();
    }

    /** Email người nhận (active). */
    public function recipients()
    {
        return email_receive_report::where('active', 1)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Gửi 1 lượt digest. Trả mảng tóm tắt.
     * @return array
     */
    public function run()
    {
        if (!config('order_check.notify_enabled')) {
            return ['status' => 'disabled', 'count' => 0, 'recipients' => 0];
        }

        $vios = $this->pendingViolations();
        if ($vios->isEmpty()) {
            return ['status' => 'empty', 'count' => 0, 'recipients' => 0];
        }

        $emails = $this->recipients();
        if (empty($emails)) {
            // Không có người nhận → KHÔNG đánh dấu, để gửi khi đã cấu hình.
            return ['status' => 'no_recipients', 'count' => $vios->count(), 'recipients' => 0];
        }

        $data = [
            'violations' => $vios,
            'total' => $vios->count(),
            'critical' => $vios->where('severity', 'critical')->count(),
            'warning' => $vios->where('severity', 'warning')->count(),
            'info' => $vios->where('severity', 'info')->count(),
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $subject = '[Sai sót y lệnh] ' . $data['total'] . ' vi phạm mới (' . $data['generatedAt'] . ')';

        foreach ($emails as $email) {
            Mail::send('templates.mail-order-check-digest', $data, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        }

        OrderCheckViolation::whereIn('id', $vios->pluck('id')->all())
            ->update(['notified_at' => Carbon::now()]);

        Log::info('Order check digest sent', ['count' => $data['total'], 'recipients' => count($emails)]);

        return ['status' => 'sent', 'count' => $data['total'], 'recipients' => count($emails)];
    }
}
