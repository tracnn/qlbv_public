<?php

namespace App\Services\Dashboard\LanhDao;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Kỳ lọc của Dashboard lãnh đạo: khoảng ngày + cơ sở.
 */
class KyLoc
{
    /** @var string YmdHis 00:00:00 */
    public $from;
    /** @var string YmdHis 23:59:59 */
    public $to;
    /** @var string hn|nb|2cs */
    public $coSo;

    /** @var Carbon */
    private $now;

    public function __construct($from, $to, $coSo, Carbon $now = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->coSo = $coSo;
        $this->now = $now ?: Carbon::now();
    }

    public static function tuRequest(array $input, Carbon $now = null)
    {
        $coSo = isset($input['co_so']) && $input['co_so'] !== '' ? $input['co_so'] : '2cs';
        $toiDa = (int) config('organization.dashboard_lanh_dao.so_ngay_toi_da', 92);

        $from = self::docNgay(isset($input['from']) ? $input['from'] : null, 'from');
        $to = self::docNgay(isset($input['to']) ? $input['to'] : null, 'to');

        if (!in_array($coSo, ['hn', 'nb', '2cs'], true)) {
            throw ValidationException::withMessages(['co_so' => ['Cơ sở không hợp lệ.']]);
        }
        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => ['Đến ngày phải sau hoặc bằng Từ ngày.']]);
        }
        if ($from->diffInDays($to) + 1 > $toiDa) {
            throw ValidationException::withMessages(['to' => ["Khoảng lọc tối đa {$toiDa} ngày."]]);
        }

        return new self($from->format('Ymd') . '000000', $to->format('Ymd') . '235959', $coSo, $now);
    }

    private static function docNgay($giaTri, $truong)
    {
        try {
            $ngay = is_string($giaTri) ? Carbon::createFromFormat('!Y-m-d', $giaTri) : false;
        } catch (\InvalidArgumentException $e) {
            $ngay = false;
        }
        // createFromFormat "tràn" ngày sai (2026-02-31) nên so lại chuỗi gốc
        if (!$ngay || $ngay->format('Y-m-d') !== $giaTri) {
            throw ValidationException::withMessages([$truong => ['Ngày không hợp lệ (Y-m-d).']]);
        }

        return $ngay;
    }

    /** @return int[] */
    public function branchIds()
    {
        $cauHinh = config('organization.dashboard_lanh_dao.co_so');
        $khoa = $this->coSo === '2cs' ? ['hn', 'nb'] : [$this->coSo];

        return array_map(function ($k) use ($cauHinh) {
            return (int) $cauHinh[$k]['branch_id'];
        }, $khoa);
    }

    /** min(to, now) dạng YmdHis */
    public function denThucTe()
    {
        $bayGio = $this->now->format('YmdHis');

        return $bayGio < $this->to ? $bayGio : $this->to;
    }

    /** Số ngày từ from đến denThucTe() */
    public function soNgayThucTe()
    {
        $tu = Carbon::createFromFormat('YmdHis', $this->from);
        $den = Carbon::createFromFormat('YmdHis', $this->denThucTe());

        return ($den->getTimestamp() - $tu->getTimestamp()) / 86400;
    }

    /** to < đầu ngày hôm nay */
    public function daKetThuc()
    {
        return substr($this->to, 0, 8) < $this->now->format('Ymd');
    }

    public function khoaCache($phan)
    {
        return "lanh_dao:{$phan}:{$this->from}:{$this->to}:{$this->coSo}";
    }
}
