<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176Service;
use App\Services\Xml3176\QuyetDinhGui;

/**
 * Buoc KY cua chuoi kiem -> xuat -> ky -> gui.
 *
 * VI SAO TACH KHOI BUOC XUAT VA BUOC GUI (khuon SignCtdtJob / SignTt12Job): ky hong do ly do
 * CUC BO (USB token bi rut, HSM khong phan hoi) con gui hong do MANG. Gop voi buoc gui thi
 * mang chap mot lan la ky lai ba lan - ma ky la thao tac ton thoi gian nhat.
 *
 * Job nay QUYET DINH chuoi co di tiep toi buoc gui hay khong, qua QuyetDinhGui - thay cho
 * viec truoc day processExportXml() hoi roi moi dispatch job gui.
 */
class SignXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Ky lai it lan: hong ky thuong do ly do cuc bo, thu lai it giup. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 120;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176Service $xmlService)
    {
        // Lop moi: khong co job cu nao cua lop nay trong hang doi, nen ma null cung la het
        // hieu luc.
        if (!$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        $ketQua = $xmlService->kyVaGhiTep($this->ma_lk);

        if ($ketQua === false) {
            Xml3176Information::where('ma_lk', $this->ma_lk)
                ->update(['signed_error' => 'Ký lỗi — không tìm thấy tệp chờ ký']);
            $this->catChuoi();
            return;
        }

        $quyetDinh = QuyetDinhGui::nen(
            config('organization.BHYT.submit_xml_3176_enabled', false),
            $ketQua['isSigned']
        );

        if ($quyetDinh === QuyetDinhGui::GUI) {
            return;
        }

        if ($quyetDinh === QuyetDinhGui::CHUA_KY) {
            // Ghi qua dung nhanh 'submit' san co: submit_error duoc dat va submitted_at de
            // null - dung hinh dang cua mot ho so bi cong tu choi.
            $xmlService->storeXml3176Information($this->ma_lk, $ketQua['macskcb'], 'submit', 1,
                'Hồ sơ chưa ký số, không gửi lên cổng BHXH');
        }

        // KHONG_GUI: chuc nang gui dang tat - khong ghi gi, ghi la bia.
        $this->catChuoi();
    }

    public function failed(\Throwable $e)
    {
        Log::error('SignXml3176Job that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['signed_error' => 'Ký lỗi — ' . $e->getMessage()]);
    }
}
