<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\DepartmentBedCatalog;

class Xml3176DashboardService
{
    /** Số mã lỗi tối đa hiển thị trên biểu đồ Pareto */
    const TOP_ERRORS_LIMIT = 15;

    /**
     * Map date_type → tên cột. Dùng đúng 4 giá trị của BHYTXml3176Controller@fetchData.
     * Giá trị true = cột timestamp thật, false = cột string dạng YmdHi.
     */
    const DATE_FIELDS = [
        'date_in'      => ['field' => 'ngay_vao',   'is_timestamp' => false],
        'date_out'     => ['field' => 'ngay_ra',    'is_timestamp' => false],
        'date_payment' => ['field' => 'ngay_ttoan', 'is_timestamp' => false],
        'date_create'  => ['field' => 'created_at', 'is_timestamp' => true],
    ];

    /** Định nghĩa 5 bậc phễu: key => nhãn hiển thị */
    const FUNNEL_STEPS = [
        'imported'    => 'Đã import',
        'no_critical' => 'Không lỗi nghiêm trọng',
        'exported'    => 'Đã xuất XML',
        'signed'      => 'Đã ký số',
        'submitted'   => 'Đã gửi BHXH',
    ];

    /**
     * Nhóm tuổi hồ sơ, theo thứ tự hiển thị (từ mới nhất đến cũ nhất).
     * Mỗi phần tử: ['key','label','min','max'] — min/max là SỐ NGÀY; max = null nghĩa là không giới hạn.
     */
    const AGING_BUCKETS = [
        ['key' => '0-7',   'label' => '0–7 ngày',     'min' => 0,  'max' => 7],
        ['key' => '8-15',  'label' => '8–15 ngày',    'min' => 8,  'max' => 15],
        ['key' => '16-30', 'label' => '16–30 ngày',   'min' => 16, 'max' => 30],
        ['key' => '30+',   'label' => 'Trên 30 ngày', 'min' => 31, 'max' => null],
    ];

    /** Mốc "vô cực về quá khứ" cho nhóm >30 ngày (ngay_ra là string YmdHi nên so sánh chuỗi) */
    const AGING_MIN_DATE = '000000000000';

    /**
     * Tính phần trăm an toàn: mẫu số 0 → trả 0 (không chia cho 0).
     */
    public function calcPercent($part, $total): float
    {
        if (!$total || $total <= 0) {
            return 0.0;
        }
        return round($part / $total * 100, 2);
    }

    /**
     * Chuẩn hoá khoảng ngày lọc theo đúng kiểu dữ liệu của từng cột.
     *
     * @return array ['field' => string, 'is_timestamp' => bool, 'from' => string, 'to' => string]
     */
    public function normalizeDateRange(string $dateType, string $dateFrom, string $dateTo): array
    {
        $meta = self::DATE_FIELDS[$dateType] ?? self::DATE_FIELDS['date_out'];

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->endOfDay();

        if ($meta['is_timestamp']) {
            $fromStr = $from->format('Y-m-d H:i:s');
            $toStr   = $to->format('Y-m-d H:i:s');
        } else {
            $fromStr = $from->format('YmdHi');
            $toStr   = $to->format('YmdHi');
        }

        return [
            'field'        => $meta['field'],
            'is_timestamp' => $meta['is_timestamp'],
            'from'         => $fromStr,
            'to'           => $toStr,
        ];
    }

    /**
     * Dựng 5 bậc phễu kèm % so với bậc liền trước.
     * Bậc đầu luôn 100%. Bậc trước = 0 → 0% (không chia cho 0).
     *
     * @param array $counts ['imported'=>int, 'no_critical'=>int, 'exported'=>int, 'signed'=>int, 'submitted'=>int]
     * @return array danh sách ['key','label','count','pct_of_prev']
     */
    public function buildFunnelSteps(array $counts): array
    {
        $steps = [];
        $prev  = null;

        foreach (self::FUNNEL_STEPS as $key => $label) {
            $count = (int) ($counts[$key] ?? 0);

            if ($prev === null) {
                $pct = 100.0; // bậc đầu tiên là mốc so sánh
            } else {
                $pct = $this->calcPercent($count, $prev);
            }

            $steps[] = [
                'key'         => $key,
                'label'       => $label,
                'count'       => $count,
                'pct_of_prev' => $pct,
            ];

            $prev = $count;
        }

        return $steps;
    }

    /**
     * Quy đổi từng nhóm tuổi thành khoảng giá trị `ngay_ra` (định dạng YmdHi).
     * Tuổi N ngày ⇔ ngay_ra = today - N.
     *
     * @return array danh sách ['key','label','from','to']
     */
    public function agingBucketRanges(Carbon $today): array
    {
        $ranges = [];

        foreach (self::AGING_BUCKETS as $bucket) {
            // Tuổi nhỏ nhất ⇒ ngày ra viện GẦN nhất ⇒ cận trên của khoảng
            $to = $today->copy()->subDays($bucket['min'])->endOfDay()->format('YmdHi');

            if ($bucket['max'] === null) {
                $from = self::AGING_MIN_DATE;
            } else {
                // Tuổi lớn nhất ⇒ ngày ra viện XA nhất ⇒ cận dưới của khoảng
                $from = $today->copy()->subDays($bucket['max'])->startOfDay()->format('YmdHi');
            }

            $ranges[] = [
                'key'   => $bucket['key'],
                'label' => $bucket['label'],
                'from'  => $from,
                'to'    => $to,
            ];
        }

        return $ranges;
    }

    /**
     * Dựng dữ liệu Pareto: sắp xếp giảm dần, cắt top 15, tính % tích luỹ.
     *
     * % tích luỹ tính trên tổng của CÁC CỘT ĐƯỢC HIỂN THỊ (sau khi cắt top 15),
     * để cột cuối luôn đạt 100% — đúng quy ước biểu đồ Pareto.
     *
     * @param array $rows       Mỗi phần tử là object có: xml, error_code, total
     * @param array $catalogMap Khoá "xml|error_code" => object có: id, error_name, critical_error
     * @return array danh sách ['catalog_id','xml','error_code','error_name','critical_error','total','cumulative_pct']
     */
    public function buildParetoData(array $rows, array $catalogMap): array
    {
        // saveErrors() luôn upsert vào catalog nên nhánh này gần như không xảy ra;
        // bỏ qua vì không có catalog_id thì không drill-down được
        $items = [];
        foreach ($rows as $row) {
            $key = $row->xml . '|' . $row->error_code;
            if (!isset($catalogMap[$key])) {
                continue;
            }
            $catalog = $catalogMap[$key];

            $items[] = [
                'catalog_id'     => (int) $catalog->id,
                'xml'            => $row->xml,
                'error_code'     => $row->error_code,
                'error_name'     => $catalog->error_name,
                'critical_error' => (bool) $catalog->critical_error,
                'total'          => (int) $row->total,
            ];
        }

        if (empty($items)) {
            return [];
        }

        // Sắp xếp giảm dần theo số hồ sơ
        usort($items, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $items = array_slice($items, 0, self::TOP_ERRORS_LIMIT);

        $grandTotal = array_sum(array_column($items, 'total'));

        $running = 0;
        foreach ($items as $idx => $item) {
            $running += $item['total'];
            $items[$idx]['cumulative_pct'] = $this->calcPercent($running, $grandTotal);
        }

        return $items;
    }

    /**
     * Query nền: hồ sơ XML1 trong khoảng ngày đã lọc.
     */
    private function baseQuery(string $dateType, string $dateFrom, string $dateTo)
    {
        $range = $this->normalizeDateRange($dateType, $dateFrom, $dateTo);

        return Xml3176Xml1::query()
            ->whereBetween($range['field'], [$range['from'], $range['to']]);
    }

    /**
     * Điều kiện: hồ sơ có ít nhất 1 lỗi nghiêm trọng.
     * Trùng khít bộ lọc `has_error_critical` của BHYTXml3176Controller@fetchData.
     */
    private function applyCriticalError($query)
    {
        return $query->whereHas('Xml3176ErrorResult', function ($q) {
            $q->where('critical_error', true);
        });
    }

    /**
     * Điều kiện: hồ sơ chưa gửi BHXH.
     * Trùng khít bộ lọc `not_submit` — CHỈ whereHas, KHÔNG orWhereDoesntHave,
     * nếu không con số sẽ lệch với danh sách sau drill-down.
     */
    private function applyNotSubmitted($query)
    {
        return $query->whereHas('Xml3176Information', function ($q) {
            $q->whereNull('submitted_at');
        });
    }

    /**
     * 5 thẻ KPI + 5 bậc phễu.
     */
    public function getOverview(string $dateType, string $dateFrom, string $dateTo): array
    {
        $total = $this->baseQuery($dateType, $dateFrom, $dateTo)->count();

        $critical = $this->applyCriticalError(
            $this->baseQuery($dateType, $dateFrom, $dateTo)
        )->count();

        $heinCard = $this->baseQuery($dateType, $dateFrom, $dateTo)
            ->whereHas('check_hein_card', function ($q) {
                $q->whereIn('ma_kiemtra', config('xml3176.hein_card_invalid.check_code', []))
                  ->orWhereIn('ma_tracuu', config('xml3176.hein_card_invalid.result_code', []));
            })->count();

        // Chi phí BHYT bị treo: có lỗi nghiêm trọng VÀ chưa gửi. t_bhtt NULL được SUM coi như 0.
        $blockedQuery = $this->applyNotSubmitted(
            $this->applyCriticalError($this->baseQuery($dateType, $dateFrom, $dateTo))
        );
        $blockedAmount = (float) $blockedQuery->sum('t_bhtt');

        $exported = $this->baseQuery($dateType, $dateFrom, $dateTo)
            ->whereHas('Xml3176Information', function ($q) {
                $q->whereNotNull('exported_at');
            })->count();

        $signed = $this->baseQuery($dateType, $dateFrom, $dateTo)
            ->whereHas('Xml3176Information', function ($q) {
                $q->where('is_signed', true);
            })->count();

        // KHÔNG xét submit_error (quyết định D10) để khớp bộ lọc `has_submit`
        $submitted = $this->baseQuery($dateType, $dateFrom, $dateTo)
            ->whereHas('Xml3176Information', function ($q) {
                $q->whereNotNull('submitted_at');
            })->count();

        $funnel = $this->buildFunnelSteps([
            'imported'    => $total,
            'no_critical' => $total - $critical,
            'exported'    => $exported,
            'signed'      => $signed,
            'submitted'   => $submitted,
        ]);

        return [
            'kpi' => [
                'total'            => $total,
                'critical'         => $critical,
                'critical_pct'     => $this->calcPercent($critical, $total),
                'hein_card'        => $heinCard,
                'hein_card_pct'    => $this->calcPercent($heinCard, $total),
                'blocked_amount'   => $blockedAmount,
                'submitted'        => $submitted,
                'submitted_pct'    => $this->calcPercent($submitted, $total),
            ],
            'funnel' => $funnel,
        ];
    }

    /**
     * Pareto top 15 mã lỗi trong kỳ.
     */
    public function getTopErrors(string $dateType, string $dateFrom, string $dateTo): array
    {
        $range = $this->normalizeDateRange($dateType, $dateFrom, $dateTo);

        // Đếm DISTINCT ma_lk = "bao nhiêu HỒ SƠ dính mã lỗi này".
        // Giả định: mỗi ma_lk chỉ có 1 dòng XML1 (đúng theo chuẩn 4750), nên con số này
        // khớp với số dòng của màn hình danh sách. Unique index là (ma_lk, stt) chứ không
        // phải (ma_lk), nên nếu sau này có nhiều dòng XML1/ma_lk thì đây là chỗ lệch đầu tiên.
        $rows = DB::table('xml3176_error_results as er')
            ->join('xml3176_xml1s as x1', 'x1.ma_lk', '=', 'er.ma_lk')
            ->whereBetween('x1.' . $range['field'], [$range['from'], $range['to']])
            ->select('er.xml', 'er.error_code', DB::raw('COUNT(DISTINCT er.ma_lk) as total'))
            ->groupBy('er.xml', 'er.error_code')
            ->get()
            ->all();

        // Map danh mục lỗi: "xml|error_code" => catalog
        $catalogMap = [];
        foreach (Xml3176ErrorCatalog::all() as $catalog) {
            $catalogMap[$catalog->xml . '|' . $catalog->error_code] = $catalog;
        }

        return $this->buildParetoData($rows, $catalogMap);
    }

    /**
     * Tồn đọng theo tuổi hồ sơ: 4 nhóm, chỉ tính hồ sơ CHƯA GỬI.
     *
     * CỐ Ý không nhận bộ lọc kỳ (quyết định D11): tồn đọng là câu hỏi "hiện tại",
     * không phải theo kỳ. Nhờ vậy chỉ còn MỘT điều kiện ngày trên `ngay_ra`, nên
     * màn hình danh sách (chỉ nhận 1 date_type + 1 khoảng) tái hiện được chính xác
     * → số trên cột luôn khớp số dòng sau khi click.
     *
     * Đếm bằng 4 câu COUNT theo khoảng ngay_ra thay vì kéo toàn bộ hồ sơ về PHP.
     */
    public function getAging(): array
    {
        $buckets = $this->agingBucketRanges(Carbon::now());
        $result  = [];

        foreach ($buckets as $bucket) {
            $query = $this->applyNotSubmitted(Xml3176Xml1::query())
                ->whereBetween('ngay_ra', [$bucket['from'], $bucket['to']]);

            $result[] = [
                'key'   => $bucket['key'],
                'label' => $bucket['label'],
                'from'  => $bucket['from'],
                'to'    => $bucket['to'],
                'total' => $query->count(),
            ];
        }

        return $result;
    }

    /**
     * Số hồ sơ CÓ LỖI NGHIÊM TRỌNG theo khoa, sắp xếp giảm dần.
     * (Spec mục 4.5 ghi "hồ sơ lỗi" — chốt là lỗi nghiêm trọng, để nhất quán với KPI chính
     *  và vì chỉ lỗi nghiêm trọng mới chặn gửi hồ sơ.)
     */
    public function getByDepartment(string $dateType, string $dateFrom, string $dateTo): array
    {
        $rows = $this->applyCriticalError(
            $this->baseQuery($dateType, $dateFrom, $dateTo)
        )
            ->select('ma_khoa', DB::raw('COUNT(*) as total'))
            ->groupBy('ma_khoa')
            ->get();

        // Map mã khoa → tên khoa. Danh mục có thể có nhiều dòng cùng ma_khoa
        // (khác ma_loai_kcb) → pluck lấy dòng cuối, chấp nhận được vì tên khoa giống nhau.
        $nameMap = DepartmentBedCatalog::pluck('ten_khoa', 'ma_khoa')->toArray();

        // Gộp theo mã khoa đã chuẩn hoá: MySQL group NULL và '' thành 2 nhóm riêng,
        // nhưng cả hai đều là "không xác định" nên phải cộng dồn làm một
        // (ép kiểu (string) đưa NULL về '' → hai nhóm chung một khoá).
        $merged = [];
        foreach ($rows as $row) {
            $maKhoa = (string) $row->ma_khoa;
            if (!isset($merged[$maKhoa])) {
                $merged[$maKhoa] = [
                    'ma_khoa'  => $maKhoa,
                    'ten_khoa' => $nameMap[$maKhoa] ?? ($maKhoa !== '' ? $maKhoa : 'Không xác định'),
                    'total'    => 0,
                ];
            }
            $merged[$maKhoa]['total'] += (int) $row->total;
        }

        $result = array_values($merged);

        usort($result, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $result;
    }
}
