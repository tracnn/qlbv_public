<?php

namespace App\Services\Dashboard\LanhDao;

use App\Services\BHYT\LocCoSo;
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

    /**
     * Đọc kỳ lọc từ request. Cơ sở nhận MÃ CSKCB (`ma_cskcb`) từ ô chọn dùng chung
     * partials/ma_cskcb; rỗng hoặc mã lạ = tất cả cơ sở (cùng quy ước LocCoSo::maHopLe).
     */
    public static function tuRequest(array $input, Carbon $now = null)
    {
        $coSo = self::coSoTuMa(isset($input['ma_cskcb']) ? $input['ma_cskcb'] : null);
        $toiDa = (int) config('dashboard_lanh_dao.so_ngay_toi_da', 92);

        $from = self::docNgay(isset($input['from']) ? $input['from'] : null, 'from');
        $to = self::docNgay(isset($input['to']) ? $input['to'] : null, 'to');

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => ['Đến ngày phải sau hoặc bằng Từ ngày.']]);
        }
        if ($from->diffInDays($to) + 1 > $toiDa) {
            throw ValidationException::withMessages(['to' => ["Khoảng lọc tối đa {$toiDa} ngày."]]);
        }

        return new self($from->format('Ymd') . '000000', $to->format('Ymd') . '235959', $coSo, $now);
    }

    /** @return array ma_cskcb => khoá cơ sở (hn|nb) theo config */
    private static function maTheoCoSo()
    {
        $ra = [];
        foreach ((array) config('dashboard_lanh_dao.co_so') as $khoa => $cs) {
            $ra[(string) $cs['ma_cskcb']] = $khoa;
        }

        return $ra;
    }

    /** Mã CSKCB → hn|nb; rỗng/lạ → 2cs */
    private static function coSoTuMa($ma)
    {
        $bang = self::maTheoCoSo();
        $ma = LocCoSo::maHopLe($ma, $bang);

        return $ma === '' ? '2cs' : $bang[$ma];
    }

    /**
     * Lựa chọn cho ô cơ sở: chỉ các mã CSKCB dashboard đã cấu hình, nhãn lấy từ
     * DanhSachCoSo (HIS); HIS lỗi (mảng rỗng) thì dùng nhãn ngắn trong config.
     *
     * @param array $danhSachHis ma => nhãn, từ DanhSachCoSo::danhSach()
     * @return array ma => nhãn
     */
    public static function luaChonCoSo(array $danhSachHis)
    {
        $ra = [];
        foreach ((array) config('dashboard_lanh_dao.co_so') as $cs) {
            $ma = (string) $cs['ma_cskcb'];
            $ra[$ma] = isset($danhSachHis[$ma]) ? $danhSachHis[$ma] : $ma . ' — ' . $cs['nhan'];
        }
        ksort($ra);

        return $ra;
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
        $cauHinh = config('dashboard_lanh_dao.co_so');
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
