# Giai đoạn 5A — Lệnh Console `ctdt:import` chạy tự động trọn chuỗi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một lệnh Artisan quét thư mục inbox, nạp mọi tệp XML tìm được, rồi xếp hàng ký số và gửi lên cổng BHXH mà không cần người trực — kèm đủ phanh để một thư mục đổ nhầm không biến thành hàng nghìn lần POST thật.

**Architecture:** Lệnh KHÔNG tự viết lại luồng nào. Nạp thì gọi `CtdtImporter::nhapTuTep()` sẵn có; xếp hàng ký-gửi thì gọi một lớp mới `CtdtXepHangKyGui` được **tách ra từ** `BHYTCtdtController::kyVaGui()` để màn hình và lệnh Console dùng chung đúng một bản (khoá chống bấm trùng, chuỗi `SignCtdtJob → SubmitCtdtJob`, cùng cổng chặn). Vì gửi tự động không có con mắt người, thêm bảng `ctdt_lich_su_gui` ghi từng lần gọi cổng thành bản ghi tra cứu được, thay vì chỉ một cột văn bản tự do.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL (production) / SQLite (test), hàng đợi driver `database`.

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6 là sàn cứng.** Không cú pháp PHP 8 (không `match`, không thuộc tính nạp từ constructor, không toán tử `?->`, không dấu phẩy sau tham số cuối trong lời gọi hàm). Không có kiểu trả về `: void` trên `setUp()`.
- **Không có `Request::boolean()`** (Laravel thêm từ 5.8). Dùng `filter_var($x, FILTER_VALIDATE_BOOLEAN)`.
- **`config($khoá, $mặc_định)` không lùi về mặc định khi khoá tồn tại với giá trị `null`.** Với tên hàng đợi, luôn dùng `CtdtHangDoi::kiem()` / `::ky()` / `::gui()`.
- **`Cache::add($khoá, $giá_trị, $phút)` nhận PHÚT**, không phải giây, trong Laravel 5.5.
- **Luật "đã kiểm và sạch chưa" chỉ nằm ở `CtdtQuyetDinhGui::nenKy($daKiem, $soLoi)`.** Không nơi nào được chép lại.
- **Máy phát triển này đã bật `submit_enabled = true` và đã gửi thật lên cổng BHXH.** Không chạy `SignCtdtJob`, `SubmitCtdtJob`, không chạy `php artisan queue:work`, và không chạy lệnh mới mà thiếu `--dry-run` trong lúc phát triển.
- **Baseline test:** `php vendor/bin/phpunit tests/Unit/Ctdt` phải đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`, đỏ có chủ đích vì đọc `config/organization.php` của máy. Hai test đỏ trở lên là dấu hiệu vỡ.
- **`config/organization.php` nằm trong `.gitignore`.** Khoá mới phải thêm vào `docs/organization.php` (bản mẫu có track) và tài liệu, không chỉ tệp máy.
- Chú thích trong mã viết **không dấu**, tài liệu Markdown viết **có dấu** — theo đúng lệ đang có trong module.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php` | Bảng nhật ký từng lần gọi cổng |
| `app/Models/BHYT/Ctdt/CtdtLichSuGui.php` | Model của bảng đó |
| `app/Services/Ctdt/CtdtXepHangKyGui.php` | Đặt khoá + xếp chuỗi ký-gửi. Một bản dùng chung cho màn hình và Console |
| `app/Console/Commands/CtdtImport.php` | Quét thư mục, nạp, chuyển tệp, gọi `CtdtXepHangKyGui` |
| `app/Jobs/SubmitCtdtJob.php` | *(sửa)* ghi thêm một dòng `ctdt_lich_su_gui` mỗi lần gọi cổng |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | *(sửa)* `kyVaGui()` gọi `CtdtXepHangKyGui` thay vì tự làm |
| `docs/organization.php`, `docs/chung-tu-dien-tu-pl02.md` | Khoá cấu hình mới + mục vận hành |

---

### Task 1: Bảng nhật ký gửi

**Vì sao task này đi trước:** gửi tự động không có người ngồi nhìn. Hiện dấu vết duy nhất của một lần gọi cổng là cột văn bản tự do `ctdt_ho_so.lich_su_gui` — không lọc được theo ngày, không đếm được, không biết lần nào do người bấm và lần nào do lệnh nền. Câu hỏi vận hành đầu tiên khi có sự cố là *"đêm qua lệnh nền gửi bao nhiêu hồ sơ, bao nhiêu cái bị từ chối"*, và cột văn bản không trả lời được.

**Files:**
- Create: `database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php`
- Create: `app/Models/BHYT/Ctdt/CtdtLichSuGui.php`
- Modify: `app/Jobs/SubmitCtdtJob.php` (trong `ghiKetQua()`, sau `$hoSo->update($thuocTinh);`)
- Test: `tests/Unit/Ctdt/CtdtLichSuGuiTest.php`

**Interfaces:**
- Consumes: `CtdtHoSo` (khoá ngoại `ho_so_id`), `SubmitCtdtJob::$nguoiGui`, `SubmitCtdtJob::$nguon`
- Produces:
  - Bảng `ctdt_lich_su_gui` với các cột: `id`, `ho_so_id` (unsignedInteger, index, FK cascade), `ma_ho_so` (string 100, index), `nguoi_gui` (string, nullable, index), `nguon` (string 10, index — `'man_hinh'` hoặc `'console'`), `ma_gd` (string 100, nullable), `ma_ket_qua` (string 20, nullable, index), `thoi_gian_tiep_nhan` (string 20, nullable), `thanh_cong` (boolean, index), `thong_diep` (text, nullable), `created_at`/`updated_at`
  - Model `App\Models\BHYT\Ctdt\CtdtLichSuGui` với `$table = 'ctdt_lich_su_gui'` và `$fillable` gồm đúng chín cột trên (trừ `id` và timestamps)
  - `CtdtLichSuGui::NGUON_MAN_HINH = 'man_hinh'`, `CtdtLichSuGui::NGUON_CONSOLE = 'console'`

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtLichSuGuiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Gui tu dong khong co nguoi ngoi nhin. Cot van ban tu do lich_su_gui khong tra loi duoc
 * cau hoi van hanh dau tien khi co su co: "dem qua lenh nen gui bao nhieu ho so, bao nhieu
 * cai bi tu choi".
 */
class CtdtLichSuGuiTest extends TestCase
{
    use DatabaseMigrations;

    private function hoSo()
    {
        return CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb'  => '01001',
        ]);
    }

    /** @test */
    public function ghi_duoc_mot_dong_nhat_ky()
    {
        $hoSo = $this->hoSo();

        CtdtLichSuGui::create([
            'ho_so_id'            => $hoSo->id,
            'ma_ho_so'            => $hoSo->ma_ho_so,
            'nguoi_gui'           => 'bsnguyen',
            'nguon'               => CtdtLichSuGui::NGUON_CONSOLE,
            'ma_gd'               => 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260821083000',
            'thanh_cong'          => true,
            'thong_diep'          => '{"MaKetQua":"200"}',
        ]);

        $dong = CtdtLichSuGui::where('ma_ho_so', 'YT001')->first();

        $this->assertNotNull($dong);
        $this->assertSame(CtdtLichSuGui::NGUON_CONSOLE, $dong->nguon);
        $this->assertTrue((bool) $dong->thanh_cong);
    }

    /** @test */
    public function ma_gd_dai_52_ky_tu_khong_bi_cat()
    {
        // Lan gui that dau tien (2026-08-20) cho thay MaGD cong tra ve dai 52 ky tu, trong
        // khi cot cu la VARCHAR(50). Cot moi phai rong tu dau chu khong doi mot lan gui that
        // nua moi biet.
        $hoSo = $this->hoSo();
        $maGd = 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75';

        CtdtLichSuGui::create([
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => 'YT001',
            'nguon' => CtdtLichSuGui::NGUON_MAN_HINH,
            'ma_gd' => $maGd, 'thanh_cong' => true,
        ]);

        $this->assertSame($maGd, CtdtLichSuGui::where('ma_ho_so', 'YT001')->value('ma_gd'));
    }

    /** @test */
    public function xoa_ho_so_thi_nhat_ky_di_theo()
    {
        // Khoa ngoai cascade: de lai dong mo coi thi bang nhat ky phinh mai va khong join
        // nguoc ve ho so duoc nua.
        $hoSo = $this->hoSo();

        CtdtLichSuGui::create([
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => 'YT001',
            'nguon' => CtdtLichSuGui::NGUON_MAN_HINH, 'thanh_cong' => false,
        ]);

        $hoSo->delete();

        $this->assertSame(0, CtdtLichSuGui::where('ma_ho_so', 'YT001')->count());
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLichSuGuiTest.php`
Kỳ vọng: ĐỎ với `Class 'App\Models\BHYT\Ctdt\CtdtLichSuGui' not found`.

- [ ] **Step 3: Viết migration**

Tạo `database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Nhat ky TUNG LAN goi cong BHXH.
 *
 * VI SAO KHONG DUNG cot ctdt_ho_so.lich_su_gui san co: cot do la van ban tu do, moi lan gui
 * noi them mot dong chu. Khong loc duoc theo ngay, khong dem duoc, khong biet lan nao do
 * nguoi bam va lan nao do lenh nen. Tu Giai doan 5A lenh ctdt:import gui tu dong khong co
 * nguoi truc, nen cau hoi "dem qua gui bao nhieu, bao nhieu cai bi tu choi" tro thanh cau
 * hoi van hanh thuong xuyen.
 *
 * Cot lich_su_gui GIU NGUYEN: no con mang dau vet cua nhung lan gui truoc khi co bang nay,
 * va khong the dung lai duoc tu van ban tu do.
 *
 * Do rong cot lay theo migration 2026_08_20_100001 da noi rong ctdt_ho_so: ma_gd 100 (cong
 * tra ve 52 ky tu that), ma_ket_qua 20, thoi_gian_tiep_nhan 20.
 */
class CreateCtdtLichSuGuiTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_lich_su_gui', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();

            // Giu ca ma_ho_so ben canh khoa ngoai: doc nhat ky ma phai join moi biet ho so
            // nao la mot buoc thua trong moi truy van van hanh.
            $table->string('ma_ho_so', 100)->index();

            $table->string('nguoi_gui')->nullable()->index();
            $table->string('nguon', 10)->index();   // man_hinh | console

            $table->string('ma_gd', 100)->nullable();
            $table->string('ma_ket_qua', 20)->nullable()->index();
            $table->string('thoi_gian_tiep_nhan', 20)->nullable();

            $table->boolean('thanh_cong')->index();
            $table->text('thong_diep')->nullable();

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_lich_su_gui');
    }
}
```

- [ ] **Step 4: Viết model**

Tạo `app/Models/BHYT/Ctdt/CtdtLichSuGui.php`:

```php
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

/**
 * Mot dong = mot lan goi cong BHXH. Xem chu thich migration ve vi sao khong dung cot
 * ctdt_ho_so.lich_su_gui.
 */
class CtdtLichSuGui extends Model
{
    /** Nguoi bam nut tren man chi tiet */
    const NGUON_MAN_HINH = 'man_hinh';

    /** Lenh ctdt:import chay nen */
    const NGUON_CONSOLE = 'console';

    protected $table = 'ctdt_lich_su_gui';

    protected $fillable = [
        'ho_so_id', 'ma_ho_so', 'nguoi_gui', 'nguon',
        'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan',
        'thanh_cong', 'thong_diep',
    ];

    protected $casts = ['thanh_cong' => 'boolean'];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id');
    }
}
```

- [ ] **Step 5: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLichSuGuiTest.php`
Kỳ vọng: `OK (3 tests)`.

Nếu test `xoa_ho_so_thi_nhat_ky_di_theo` đỏ trên SQLite: SQLite **mặc định tắt** khoá ngoại. Kiểm tra `tests/TestCase.php` hoặc `config/database.php` xem đã có `PRAGMA foreign_keys=ON` chưa. Nếu chưa, **đừng bật toàn cục** (đổi hành vi của cả bộ test cũ) — thay vào đó xoá test đó và thay bằng test khẳng định migration có khai khoá ngoại:

```php
    /** @test */
    public function khai_khoa_ngoai_cascade_ve_ctdt_ho_so()
    {
        $migration = file_get_contents(base_path(
            'database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php'
        ));

        $this->assertContains("->onDelete('cascade')", $migration,
            'Dong nhat ky mo coi lam bang phinh mai va khong join nguoc ve ho so duoc');
    }
```

- [ ] **Step 6: Nối `SubmitCtdtJob` ghi nhật ký**

Trong `app/Jobs/SubmitCtdtJob.php`:

1. Thêm `use App\Models\BHYT\Ctdt\CtdtLichSuGui;` vào khối `use`.
2. Thêm thuộc tính và tham số constructor thứ ba:

```php
    /**
     * Ai gay ra lan gui nay: 'man_hinh' hay 'console'.
     *
     * Co GIA TRI MAC DINH nen payload cua nhung job da nam san trong hang doi truoc khi
     * trien khai van giai tuan tu duoc - PHP dat lai gia tri mac dinh cua lop cho thuoc tinh
     * vang mat trong chuoi da serialize.
     *
     * @var string
     */
    public $nguon = CtdtLichSuGui::NGUON_MAN_HINH;
```

và trong `__construct($maHoSo, $nguoiGui = null)` đổi thành `__construct($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)`, gán `$this->nguon = $nguon;`.

3. Trong `ghiKetQua()` chèn NGAY SAU dòng `$hoSo->update($thuocTinh);`:

```php
        // Ghi nhat ky TRUOC moi nhanh return phia duoi: mot lan goi cong da xay ra roi thi
        // phai co dau vet, ke ca khi cong tu choi. Dung create() chu khong updateOrCreate:
        // moi lan gui la MOT dong, gui lai lan hai khong duoc de len lan mot.
        CtdtLichSuGui::create([
            'ho_so_id'            => $hoSo->id,
            'ma_ho_so'            => $hoSo->ma_ho_so,
            'nguoi_gui'           => $this->nguoiGui,
            // Nguon la tham so TUONG MINH, khong suy tu $nguoiGui === null: kyVaGui() cung
            // truyen null khi auth()->check() tra false, nen suy se ghi mot cu bam tay thanh
            // "lenh nen". Quy sai mot lan gui khong nguoi truc cho mot con nguoi la kieu noi
            // doi te nhat mot bang nhat ky co the mac.
            'nguon'               => $this->nguon,
            'ma_gd'               => isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null,
            'ma_ket_qua'          => isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null,
            'thoi_gian_tiep_nhan' => isset($ketQua['thoi_gian_tiep_nhan'])
                ? $ketQua['thoi_gian_tiep_nhan'] : null,
            'thanh_cong'          => $thanhCong,
            'thong_diep'          => isset($ketQua['thong_diep']) ? $ketQua['thong_diep'] : null,
        ]);
```

⚠️ **Cột nhật ký KHÔNG được cắt độ dài như `ghiKetQua()` cắt cột hồ sơ.** Cột `ma_gd` ở đây rộng 100 ngay từ đầu và `thong_diep` là `text`. Nếu cổng trả về giá trị dài hơn 100 thì `create()` sẽ ném — và nó ném **sau khi cổng đã nhận**. Bọc riêng lời gọi này trong `try/catch`:

```php
        try {
            CtdtLichSuGui::create([ /* ... như trên ... */ ]);
        } catch (\Exception $e) {
            // Mat mot dong nhat ky con hon nem sau khi cong DA NHAN ho so: nem o day lam job
            // that bai, hang doi gui lai, va cong nhan LAN HAI cung mot goi - ma PL02 khong co
            // ma giao dich phia client de cong khu trung.
            Log::error('CTDT khong ghi duoc lich su gui ' . $hoSo->ma_ho_so . ': ' . $e->getMessage());
        }
```

- [ ] **Step 7: Test rằng ghi nhật ký không bao giờ làm hỏng lần gửi**

Thêm vào `tests/Unit/Ctdt/CtdtLichSuGuiTest.php`:

```php
    /** @test */
    public function loi_ghi_nhat_ky_khong_duoc_lam_job_gui_that_bai()
    {
        // Nem o day lam job that bai SAU KHI cong da nhan ho so; hang doi gui lai va cong
        // nhan LAN HAI cung mot goi. PL02 khong co ma giao dich phia client nen cong khong
        // khu trung duoc - day dung la kieu loi da tung xay ra voi cot ma_gd bi tran.
        $nguon = file_get_contents(base_path('app/Jobs/SubmitCtdtJob.php'));

        $this->assertRegExp(
            '/try\s*\{\s*CtdtLichSuGui::create\(/s',
            $nguon,
            'Loi ghi nhat ky phai duoc nuot, khong duoc de nem ra khoi ghiKetQua()'
        );
    }
```

- [ ] **Step 8: Chạy cả nhóm test và kiểm đột biến**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLichSuGuiTest.php` và `php vendor/bin/phpunit tests/Unit/Ctdt/SubmitCtdtJobTest.php`
Kỳ vọng: cả hai `OK`.

**Đột biến bắt buộc** (chứng minh test có răng — commit TRƯỚC khi đột biến, vì tệp chưa track thì `git checkout --` không cứu được):
1. Bỏ `try {` quanh `CtdtLichSuGui::create(` trong `SubmitCtdtJob.php` → `loi_ghi_nhat_ky_khong_duoc_lam_job_gui_that_bai` phải ĐỎ.
2. Đổi `->onDelete('cascade')` thành `->onDelete('restrict')` trong migration → test khoá ngoại phải ĐỎ.

Hoàn nguyên bằng `git checkout -- <đúng đường dẫn>`, **từng tệp một**. Không `git checkout -- .`, không `git stash`.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php app/Models/BHYT/Ctdt/CtdtLichSuGui.php app/Jobs/SubmitCtdtJob.php tests/Unit/Ctdt/CtdtLichSuGuiTest.php
git commit -m "feat(ctdt): bang nhat ky tung lan goi cong BHXH"
```

---

### Task 2: Tách `CtdtXepHangKyGui` khỏi controller

**Vì sao:** `BHYTCtdtController::kyVaGui()` đang giữ ba thứ mà lệnh Console cũng cần y hệt — khoá chống xử lý trùng (`Cache::add(self::KHOA_XU_LY . $ma_ho_so, true, self::KHOA_XU_LY_PHUT)`), chuỗi `SignCtdtJob::withChain([SubmitCtdtJob])`, và hai tên hàng đợi. Để lệnh Console tự viết bản thứ hai là **đúng cái sai đã xảy ra thật với XML3176**, và ghi chú ngay trong `CtdtImporter::nhapTuTep()` đã cảnh báo điều đó.

**Files:**
- Create: `app/Services/Ctdt/CtdtXepHangKyGui.php`
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (`kyVaGui()`, khối đặt khoá + dispatch)
- Test: `tests/Unit/Ctdt/CtdtXepHangKyGuiTest.php`

**Interfaces:**
- Consumes: `App\Jobs\SignCtdtJob`, `App\Jobs\SubmitCtdtJob`, `App\Services\Ctdt\CtdtHangDoi`
- Produces:
  - `CtdtXepHangKyGui::KHOA = 'ctdt:dang-xu-ly:'` — tiền tố khoá cache
  - `CtdtXepHangKyGui::KHOA_PHUT = 30` — thời hạn khoá, tính bằng **phút**
  - `CtdtXepHangKyGui::xep($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)` → `bool` — `true` nếu đã xếp hàng, `false` nếu hồ sơ đang có lượt xử lý khác
  - `CtdtXepHangKyGui::dangXuLy($maHoSo)` → `bool` — chỉ hỏi, không đặt khoá

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtXepHangKyGuiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SignCtdtJob;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtXepHangKyGui;

/**
 * Man hinh va lenh Console phai xep hang bang DUNG MOT BAN. Hai ban se lech nhau - dung
 * dieu da xay ra that voi XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao.
 */
class CtdtXepHangKyGuiTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function xep_hang_lan_dau_thi_thanh_cong()
    {
        Bus::fake();

        $this->assertTrue(CtdtXepHangKyGui::xep('YT001', 'bsnguyen'));

        Bus::assertDispatched(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_dang_xu_ly_thi_bi_tu_choi()
    {
        // Bam hai lan = hai lan POST that len cong. PL02 khong co ma giao dich phia client
        // nen cong khong khu trung duoc.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        $this->assertFalse(CtdtXepHangKyGui::xep('YT001'),
            'Ho so dang xu ly phai bi tu choi, khong duoc xep hang lan hai');
    }

    /** @test */
    public function khoa_theo_TUNG_ma_ho_so_chu_khong_phai_mot_khoa_chung()
    {
        // Mot khoa chung se khoa ca he thong lai chi vi mot ho so dang chay - lenh Console
        // xu 200 ho so mot luot se chi xep duoc dung mot cai.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        $this->assertTrue(CtdtXepHangKyGui::xep('YT002'),
            'Ho so KHAC phai xep hang duoc binh thuong');
    }

    /** @test */
    public function dangXuLy_chi_hoi_chu_KHONG_dat_khoa()
    {
        // Cache::add() vua hoi vua dat. Dung no lam phep tham do se lam chinh nguoi hoi
        // chiem mat khoa, va lan xep hang that ngay sau do bi tu choi boi chinh minh.
        Bus::fake();

        $this->assertFalse(CtdtXepHangKyGui::dangXuLy('YT001'));
        $this->assertTrue(CtdtXepHangKyGui::xep('YT001'),
            'dangXuLy() khong duoc chiem khoa');
    }

    /** @test */
    public function xep_dung_hai_hang_doi_rieng()
    {
        // Ky hong vi ly do CUC BO (USB token bi rut), gui hong vi MANG. Gop chung thi mot
        // lan mang chap keo theo ba lan ky lai.
        Bus::fake();

        CtdtXepHangKyGui::xep('YT001');

        Bus::assertDispatched(SignCtdtJob::class, function ($job) {
            return $job->queue === CtdtHangDoi::ky();
        });
    }

    /** @test */
    public function khoa_tinh_bang_PHUT_chu_khong_phai_giay()
    {
        // Cache::add($khoa, $giaTri, $phut) trong Laravel 5.5 nhan PHUT. Truyen giay vao se
        // khoa ho so lai 30 tieng thay vi 30 phut.
        $this->assertSame(30, CtdtXepHangKyGui::KHOA_PHUT);
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXepHangKyGuiTest.php`
Kỳ vọng: ĐỎ với `Class 'App\Services\Ctdt\CtdtXepHangKyGui' not found`.

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/Ctdt/CtdtXepHangKyGui.php`:

```php
<?php

namespace App\Services\Ctdt;

use Illuminate\Support\Facades\Cache;
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Dat khoa chong xu ly trung roi xep chuoi ky so - gui len cong.
 *
 * VI SAO LOP RIENG: man chi tiet (nut "Ky va gui") va lenh Console ctdt:import deu can dung
 * mot viec nay. De moi ben tu viet thi hai ban se lech nhau - dung dieu da xay ra that voi
 * XML3176, va ghi chu trong CtdtImporter::nhapTuTep() da canh bao truoc.
 *
 * KHONG kiem dieu kien gui o day: noi goi phai tu hoi CtdtQuyetDinhGui truoc, vi hai noi goi
 * bao loi cho hai doi tuong khac nhau - man hinh tra JSON cho nguoi bam, Console in ra man
 * hinh va ghi log.
 */
class CtdtXepHangKyGui
{
    /** Tien to khoa cache, ghep them ma ho so */
    const KHOA = 'ctdt:dang-xu-ly:';

    /**
     * Thoi han khoa, tinh bang PHUT - Cache::add() cua Laravel 5.5 nhan phut.
     *
     * 30 phut phai LON HON tong ngan sach thu lai cua ca chuoi: SignCtdtJob co tries = 2,
     * timeout = 120; SubmitCtdtJob co tries = 3, timeout = 90; queue.connections.database
     * .retry_after = 300. Khoa ngan hon la mo cua cho lan bam thu hai trong khi chuoi thu
     * nhat con dang chay.
     */
    const KHOA_PHUT = 30;

    /**
     * @param  string      $maHoSo
     * @param  string|null $nguoiGui loginname nguoi bam, co the null ca khi nguoi do bam tay
     * @param  string      $nguon    CtdtLichSuGui::NGUON_MAN_HINH hoac NGUON_CONSOLE
     * @return bool false khi ho so dang co luot xu ly khac
     */
    public static function xep($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)
    {
        // Cache::add() tra false khi khoa DA ton tai - do chinh la phep thu "da co ai xep
        // chua". Khoa theo TUNG ma ho so: mot khoa chung se khoa ca he thong lai chi vi mot
        // ho so dang chay, va lenh Console xu 200 ho so mot luot se chi xep duoc dung mot.
        if (!Cache::add(self::KHOA . $maHoSo, true, self::KHOA_PHUT)) {
            return false;
        }

        // Chuoi chu khong hai lan dispatch roi rac: job gui co the chay truoc job ky va luon
        // thay is_signed = false.
        SignCtdtJob::withChain([
            (new SubmitCtdtJob($maHoSo, $nguoiGui, $nguon))->onQueue(CtdtHangDoi::gui()),
        ])
        ->dispatch($maHoSo)
        ->onQueue(CtdtHangDoi::ky());

        return true;
    }

    /**
     * Chi HOI, khong dat khoa.
     *
     * Cache::add() vua hoi vua dat, nen dung no lam phep tham do se lam chinh nguoi hoi
     * chiem mat khoa - va lan xep hang that ngay sau do bi tu choi boi chinh minh.
     *
     * @param  string $maHoSo
     * @return bool
     */
    public static function dangXuLy($maHoSo)
    {
        return Cache::has(self::KHOA . $maHoSo);
    }
}
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXepHangKyGuiTest.php`
Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 5: Cho controller gọi lớp mới**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`:

1. Thêm `use App\Services\Ctdt\CtdtXepHangKyGui;` cạnh các `use App\Services\Ctdt\...` đang có.
2. Xoá hai hằng `const KHOA_XU_LY = 'ctdt:dang-xu-ly:';` và `const KHOA_XU_LY_PHUT = 30;`.
3. Trong `kyVaGui()`, thay **cả khối** từ `$hangDoiKy  = CtdtHangDoi::ky();` cho tới hết lời gọi `->onQueue($hangDoiKy);` bằng:

```php
        // Xep hang qua CtdtXepHangKyGui chu khong tu dat khoa va tu dispatch: lenh Console
        // ctdt:import can dung mot viec nay, va hai ban se lech nhau.
        //
        // Dat khoa NGAY TRUOC dispatch, SAU moi nhanh tu choi phia tren: mot lan bam bi tu
        // choi khong lam gi ca, giu khoa se khoa nguoi dung ra ngoai het thoi han ma khong
        // duoc gi.
        if (!CtdtXepHangKyGui::xep($ma_ho_so, $nguoiGui)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Hồ sơ này đang xử lý. Chờ ít phút rồi tải lại trang để xem kết quả.',
            ]);
        }

        return response()->json([
            'thanh_cong' => true,
            'thong_diep' => 'Đã xếp hàng ký số và gửi. Tải lại trang sau ít phút để xem kết quả.',
        ]);
```

4. Nếu `use Illuminate\Support\Facades\Cache;`, `use App\Jobs\SignCtdtJob;`, `use App\Jobs\SubmitCtdtJob;` hoặc `use App\Services\Ctdt\CtdtHangDoi;` không còn chỗ dùng nào khác trong tệp thì xoá — kiểm bằng `grep -n "Cache::\|SignCtdtJob\|SubmitCtdtJob\|CtdtHangDoi" app/Http/Controllers/BHYT/BHYTCtdtController.php` trước khi xoá từng cái.

- [ ] **Step 6: Chạy lại các test của controller**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKyVaGuiTest.php`
Kỳ vọng: `OK`. Test nào tham chiếu `BHYTCtdtController::KHOA_XU_LY` thì đổi sang `CtdtXepHangKyGui::KHOA` — **đổi tham chiếu, đừng đổi khẳng định**: thứ các test đó bảo vệ (bấm hai lần bị chặn) vẫn phải được bảo vệ y nguyên.

- [ ] **Step 7: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên từng tệp một bằng `git checkout -- <đường dẫn>` sau mỗi lần):
1. Đổi `Cache::add(...)` thành `Cache::put(...)` trong `xep()` — `put()` luôn ghi đè và trả về `null` → `ho_so_dang_xu_ly_thi_bi_tu_choi` phải ĐỎ.
2. Đổi `self::KHOA . $maHoSo` thành `self::KHOA` (khoá chung) → `khoa_theo_TUNG_ma_ho_so_chu_khong_phai_mot_khoa_chung` phải ĐỎ.
3. Đổi `Cache::has(...)` trong `dangXuLy()` thành `Cache::add(self::KHOA . $maHoSo, true, self::KHOA_PHUT)` → `dangXuLy_chi_hoi_chu_KHONG_dat_khoa` phải ĐỎ.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Ctdt/CtdtXepHangKyGui.php app/Http/Controllers/BHYT/BHYTCtdtController.php tests/Unit/Ctdt/CtdtXepHangKyGuiTest.php tests/Unit/Ctdt/CtdtKyVaGuiTest.php
git commit -m "refactor(ctdt): tach CtdtXepHangKyGui de Console va man hinh dung mot ban"
```

---

### Task 3: Lệnh `ctdt:import` — quét và nạp

**Phạm vi task này dừng ở NẠP.** Ký và gửi là Task 4. Tách ra vì đây là ranh giới mà người duyệt có thể chấp nhận phần quét-nạp mà vẫn bác phần tự động gửi.

**Files:**
- Create: `app/Console/Commands/CtdtImport.php`
- Modify: `docs/organization.php` (thêm hai khoá cấu hình)
- Test: `tests/Unit/Ctdt/CtdtImportCommandTest.php`

**Interfaces:**
- Consumes: `CtdtImporter::nhapTuTep($duongDan, array $tuyChon = [])` → `CtdtImportFileResult` (có `$thanhCong`, `$lyDoThatBai`, `$soThanhCong`, `$soThatBai`, `$dsMaHoSo`, `$dsGhiDeDaGui`)
- Produces:
  - Lệnh `ctdt:import` với chữ ký:
    `ctdt:import {--duong-dan= : Thu muc quet, mac dinh lay tu cau hinh} {--gioi-han=200 : Tran so TEP xu ly moi luot} {--dry-run : Chi liet ke, khong nap khong doi gi} {--khong-ky : Nap va kiem, dung truoc buoc ky} {--khong-gui : Ky nhung khong gui len cong}`
  - Hằng `CtdtImport::THU_MUC_DA_NAP = 'da-nap'`, `CtdtImport::THU_MUC_LOI = 'loi'`
  - Khoá cấu hình mới trong `organization.chung_tu_dien_tu`: `import_gioi_han` (int, mặc định `200`), `import_tu_dong_gui` (bool, mặc định `false`)

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtImportCommandTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Console\Commands\CtdtImport;

/**
 * Lenh chay NEN, khong co nguoi ngoi nhin. Moi cai phanh o day deu la thu duy nhat dung
 * giua mot thu muc do nham va hang nghin lan POST that len cong BHXH.
 */
class CtdtImportCommandTest extends TestCase
{
    /** @test */
    public function co_du_nam_tuy_chon_phanh()
    {
        $lenh = new CtdtImport();
        $dinhNghia = $lenh->getDefinition();

        foreach (['duong-dan', 'gioi-han', 'dry-run', 'khong-ky', 'khong-gui'] as $ten) {
            $this->assertTrue($dinhNghia->hasOption($ten), 'Thieu tuy chon --' . $ten);
        }
    }

    /** @test */
    public function gioi_han_mac_dinh_khong_duoc_de_trong()
    {
        // Khong co tran thi mot thu muc do nham 3000 tep thanh 3000 lan POST that len cong,
        // trong MOT luot chay, khong ai kip dung lai.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('gioi-han')->getDefault();

        $this->assertNotNull($macDinh, 'Tuy chon --gioi-han phai co gia tri mac dinh');
        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function ten_lenh_dung_tien_to_ctdt()
    {
        $this->assertSame('ctdt:import', (new CtdtImport())->getName());
    }

    /** @test */
    public function quet_bo_qua_thu_muc_da_nap_va_loi()
    {
        // Storage quet DE QUY. Khong loai hai thu muc con nay ra thi tep da xu ly bi nhat
        // len lai o luot sau - va voi ho so da gui, do la mot lan POST that nua.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains(CtdtImport::THU_MUC_DA_NAP, $nguon);
        $this->assertContains(CtdtImport::THU_MUC_LOI, $nguon);
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: ĐỎ với `Class 'App\Console\Commands\CtdtImport' not found`.

- [ ] **Step 3: Viết lệnh (phần quét và nạp)**

Tạo `app/Console/Commands/CtdtImport.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Ctdt\CtdtImporter;

/**
 * Quet thu muc inbox, nap moi tep XML tim duoc, roi (o Task 4) xep hang ky so va gui.
 *
 * KHAC XML3176Import: lenh do chay `do { ... sleep(3) } while (true)` - mot vong lap vo tan
 * trong mot tien trinh nssm. Lenh nay chay MOT LUOT roi thoat, de lich chay (nssm hoac
 * Task Scheduler) quyet dinh nhip. Ly do: lenh nay GUI THAT len cong BHXH, va mot tien
 * trinh song mai la thu khong ai nho tat khi can dung gap.
 */
class CtdtImport extends Command
{
    protected $signature = 'ctdt:import
        {--duong-dan= : Thu muc quet, mac dinh lay tu organization.chung_tu_dien_tu.import_path}
        {--gioi-han=200 : Tran so TEP xu ly moi luot}
        {--dry-run : Chi liet ke nhung gi se lam, khong nap khong doi gi}
        {--khong-ky : Nap va kiem, dung truoc buoc ky}
        {--khong-gui : Ky nhung khong gui len cong}';

    protected $description = 'Quet thu muc inbox, nap chung tu dien tu PL02, ky va gui len cong BHXH';

    /** Tep nap xong chuyen vao day */
    const THU_MUC_DA_NAP = 'da-nap';

    /** Tep nap that bai chuyen vao day - KHONG xoa, con du lieu ma dieu tra */
    const THU_MUC_LOI = 'loi';

    /** Khoa chong hai luot chay chong len nhau */
    const KHOA_LUOT = 'ctdt:import:dang-chay';

    /** Thoi han khoa luot, tinh bang PHUT */
    const KHOA_LUOT_PHUT = 60;

    /** @var CtdtImporter */
    protected $importer;

    public function __construct(CtdtImporter $importer = null)
    {
        parent::__construct();

        // Lui ve new: Laravel 5.5 tiem qua container khi chay that, nhung test dung
        // `new CtdtImport()` de doc dinh nghia tuy chon ma khong dung toi importer.
        $this->importer = $importer ?: new CtdtImporter();
    }

    public function handle()
    {
        $thuMuc = $this->option('duong-dan')
            ?: config('organization.chung_tu_dien_tu.import_path');

        if (empty($thuMuc)) {
            $this->error('Chua cau hinh organization.chung_tu_dien_tu.import_path');

            return 1;
        }

        if (!is_dir($thuMuc)) {
            $this->error('Khong phai thu muc: ' . $thuMuc);

            return 1;
        }

        $khoDe = $this->option('dry-run');

        // Khoa luot: hai luot chay chong len nhau se cung nhat mot tep len va nap hai lan.
        // Bo qua khoa khi --dry-run vi luot do khong doi gi ca.
        if (!$khoDe && !Cache::add(self::KHOA_LUOT, true, self::KHOA_LUOT_PHUT)) {
            $this->warn('Mot luot ctdt:import khac dang chay. Bo qua luot nay.');

            return 0;
        }

        try {
            return $this->quet($thuMuc, (int) $this->option('gioi-han'), $khoDe);
        } finally {
            if (!$khoDe) {
                Cache::forget(self::KHOA_LUOT);
            }
        }
    }

    /**
     * @param  string $thuMuc
     * @param  int    $gioiHan
     * @param  bool   $khoDe   --dry-run
     * @return int ma thoat
     */
    protected function quet($thuMuc, $gioiHan, $khoDe)
    {
        $tep = $this->dsTep($thuMuc);

        if (empty($tep)) {
            $this->info('Khong co tep nao trong ' . $thuMuc);

            return 0;
        }

        if (count($tep) > $gioiHan) {
            // Bao TO, khong chi ghi log: cat bot im lang doc y het chay het.
            $this->warn('Tim thay ' . count($tep) . ' tep, chi xu ly ' . $gioiHan
                . ' tep trong luot nay (--gioi-han). Chay lai de xu tiep.');

            $tep = array_slice($tep, 0, $gioiHan);
        }

        $dsMaHoSo = [];
        $soHong = 0;

        foreach ($tep as $duongDan) {
            if ($khoDe) {
                $this->line('[dry-run] se nap: ' . $duongDan);
                continue;
            }

            $kq = $this->napMotTep($duongDan, $thuMuc);

            if ($kq === null) {
                $soHong++;
                continue;
            }

            $dsMaHoSo = array_merge($dsMaHoSo, $kq);
        }

        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        return 0;
    }

    /**
     * Nap mot tep, chuyen no sang da-nap/ hoac loi/.
     *
     * @return array|null danh sach ma ho so, hoac null khi tep hong
     */
    protected function napMotTep($duongDan, $thuMuc)
    {
        try {
            $kq = $this->importer->nhapTuTep($duongDan);
        } catch (\Exception $e) {
            // Mot tep hong KHONG duoc lam dung ca luot quet. XML3176Import truoc day dung
            // `return false` nen mot tep hong lam tac vinh vien - va no khong bi chuyen di
            // nen luot sau lai vap dung no.
            Log::error('ctdt:import loi khi xu ly ' . $duongDan . ': ' . $e->getMessage());
            $this->error(basename($duongDan) . ': ' . $e->getMessage());
            $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return null;
        }

        if (!$kq->thanhCong) {
            Log::error('ctdt:import nap that bai ' . $duongDan . ': ' . $kq->lyDoThatBai);
            $this->error(basename($duongDan) . ': ' . $kq->lyDoThatBai);
            $this->chuyen($duongDan, $thuMuc, self::THU_MUC_LOI);

            return null;
        }

        if (!empty($kq->dsGhiDeDaGui)) {
            // Ho so DA duoc cong nhan ma bi nap de: dau vet ma_gd bi xoa. Phai keu to, vi
            // sau day chuoi tu dong se GUI LAI chinh no.
            $this->warn(basename($duongDan) . ': ghi de ' . count($kq->dsGhiDeDaGui)
                . ' ho so DA TUNG GUI - ' . implode(', ', $kq->dsGhiDeDaGui));
            Log::warning('ctdt:import ghi de ho so da gui: ' . implode(', ', $kq->dsGhiDeDaGui));
        }

        $this->line(basename($duongDan) . ': ' . $kq->soThanhCong . ' ho so, '
            . $kq->soThatBai . ' hong');

        $this->chuyen($duongDan, $thuMuc, self::THU_MUC_DA_NAP);

        return $kq->dsMaHoSo;
    }

    /**
     * Liet ke tep XML trong thu muc, BO QUA hai thu muc con da-nap/ va loi/.
     *
     * @return array duong dan tuyet doi, sap xep de thu tu on dinh giua cac luot
     */
    protected function dsTep($thuMuc)
    {
        // KHONG quet de quy: chi lay tep ngay trong thu muc goc. Quet de quy se nhat lai
        // chinh nhung tep vua chuyen vao da-nap/ - va voi ho so da gui, do la mot lan POST
        // that nua len cong BHXH.
        $tim = glob(rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . '*.[xX][mM][lL]');

        if ($tim === false) {
            return [];
        }

        $tim = array_filter($tim, 'is_file');
        sort($tim);

        return array_values($tim);
    }

    /** Chuyen tep sang thu muc con, tao thu muc neu chua co */
    protected function chuyen($duongDan, $thuMuc, $thuMucCon)
    {
        $dich = rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . $thuMucCon;

        if (!is_dir($dich) && !@mkdir($dich, 0775, true) && !is_dir($dich)) {
            Log::error('ctdt:import khong tao duoc thu muc ' . $dich);

            return;
        }

        $tepDich = $dich . DIRECTORY_SEPARATOR . basename($duongDan);

        // Trung ten thi ghep thoi diem vao, KHONG ghi de: tep nguon la bang chung goc, ghi
        // de mot cai la mat vinh vien.
        if (file_exists($tepDich)) {
            $tepDich = $dich . DIRECTORY_SEPARATOR
                . pathinfo($duongDan, PATHINFO_FILENAME)
                . '-' . now()->format('YmdHis') . '.xml';
        }

        if (!@rename($duongDan, $tepDich)) {
            Log::error('ctdt:import khong chuyen duoc ' . $duongDan . ' sang ' . $tepDich);
        }
    }
}
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: `OK (4 tests)`.

- [ ] **Step 5: Thêm hai khoá cấu hình vào bản mẫu**

Trong `docs/organization.php`, ngay dưới dòng `'import_path' => 'D:\XML\ChungTuDienTu\inbox',`, thêm:

```php
        // Tran so TEP lenh ctdt:import xu ly moi luot. Khong co tran thi mot thu muc do nham
        // 3000 tep thanh 3000 lan POST that len cong trong MOT luot, khong ai kip dung lai.
        'import_gioi_han' => 200,

        // Lenh ctdt:import co duoc GUI len cong khong. TACH RIENG khoi submit_enabled: bat
        // submit_enabled la cho phep NGUOI bam nut gui; bat khoa nay la cho phep MAY gui khi
        // khong co ai nhin. Hai muc do tin cay khac nhau thi phai hai cong tac khac nhau.
        'import_tu_dong_gui' => false,
```

Ghi cùng nội dung đó vào `config/organization.php` của máy phát triển (tệp gitignore) để chạy thử được, nhưng **giữ `import_tu_dong_gui => false`**.

- [ ] **Step 6: Chạy thử bằng tay ở chế độ khô**

```bash
php artisan ctdt:import --dry-run --duong-dan=storage/app/ctdt-thu
```

Tạo trước thư mục đó và bỏ vào một tệp XML mẫu bất kỳ lấy từ `tests/`. Kỳ vọng: in `[dry-run] se nap: ...` và **không** tạo thư mục `da-nap`, **không** thêm bản ghi nào vào `ctdt_ho_so`.

⚠️ Không chạy lệnh mà thiếu `--dry-run` trên máy này.

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/CtdtImport.php tests/Unit/Ctdt/CtdtImportCommandTest.php docs/organization.php
git commit -m "feat(ctdt): lenh ctdt:import quet va nap thu muc inbox"
```

---

### Task 4: Nối chuỗi ký và gửi vào lệnh

**Files:**
- Modify: `app/Console/Commands/CtdtImport.php`
- Modify: `docs/chung-tu-dien-tu-pl02.md`
- Test: `tests/Unit/Ctdt/CtdtImportCommandTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtXepHangKyGui::xep($maHoSo, $nguoiGui = null)` → `bool` (Task 2); `CtdtQuyetDinhGui::nenKy($daKiem, $soLoi)` → `CHUA_KIEM|CON_LOI|KY`
- Produces: không có API mới; lệnh có thêm bước 2 sau khi nạp

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtImportCommandTest.php`:

```php
    /** @test */
    public function KHONG_gui_khi_import_tu_dong_gui_dang_tat()
    {
        // Hai cong tac RIENG: submit_enabled cho phep NGUOI bam nut gui; import_tu_dong_gui
        // cho phep MAY gui khi khong co ai nhin. Bat cai thu nhat KHONG duoc keo theo cai
        // thu hai - do la khac biet giua "toi tin cai nut nay" va "toi tin de may tu chay
        // luc 2 gio sang".
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('import_tu_dong_gui', $nguon,
            'Lenh phai hoi khoa import_tu_dong_gui rieng, khong duoc chi dua vao submit_enabled');
    }

    /** @test */
    public function ho_so_chua_kiem_khong_duoc_xep_hang_ky()
    {
        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach - no co nghia la chua ai
        // nhin. Lenh chay ngay sau khi nap, luc bo kiem con dang nam trong hang doi, nen day
        // KHONG phai truong hop hiem: no la truong hop THUONG GAP.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('CtdtQuyetDinhGui::nenKy', $nguon,
            'Phai hoi CtdtQuyetDinhGui::nenKy() chu khong tu viet lai luat da-kiem-va-sach');
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: hai test mới ĐỎ.

- [ ] **Step 3: Nối bước ký-gửi vào lệnh**

Trong `app/Console/Commands/CtdtImport.php`, thêm vào khối `use`:

```php
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtXepHangKyGui;
```

Trong `quet()`, thay dòng `$this->info('Nap xong: ...');` và `return 0;` cuối hàm bằng:

```php
        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        if ($khoDe || $this->option('khong-ky')) {
            return 0;
        }

        return $this->kyVaGui($dsMaHoSo);
```

Thêm hai phương thức mới:

```php
    /**
     * Xep hang ky so - gui cho nhung ho so DA KIEM va SACH.
     *
     * VI SAO KHONG cho vao hang doi het roi de job tu loc: job ky da co cua chan cua no,
     * nhung xep 200 job de 195 cai tu thoat lam nhieu log den muc khong ai doc nua - va ba
     * hang doi thi dai ra ma khong ai biet vi sao.
     *
     * @param  array $dsMaHoSo
     * @return int ma thoat
     */
    protected function kyVaGui(array $dsMaHoSo)
    {
        if (empty($dsMaHoSo)) {
            return 0;
        }

        // Hai cong tac RIENG. submit_enabled la "cho phep NGUOI bam nut gui"; khoa nay la
        // "cho phep MAY gui khi khong co ai nhin". Hai muc do tin cay khac nhau.
        $duocGui = (bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui')
            && !$this->option('khong-gui');

        if (!$duocGui) {
            $this->warn('Khong gui: import_tu_dong_gui dang tat hoac co --khong-gui. '
                . 'Ho so da nap va se duoc kiem, nhung dung lai o do.');

            return 0;
        }

        $soXep = 0;
        $soBoQua = 0;

        foreach ($dsMaHoSo as $maHoSo) {
            $hoSo = CtdtHoSo::where('ma_ho_so', $maHoSo)->first();

            if ($hoSo === null) {
                continue;
            }

            // Lenh nay chay NGAY SAU khi nap, luc bo kiem con nam trong hang doi JobCtdt.
            // Nen "chua kiem" o day khong phai truong hop hiem - no la truong hop THUONG GAP,
            // va bo qua la dung: luot chay sau se nhat lai.
            $nenKy = CtdtQuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi);

            if ($nenKy !== CtdtQuyetDinhGui::KY) {
                $soBoQua++;
                continue;
            }

            // Noi ro nguon la CONSOLE: khong duoc de bang nhat ky suy tu $nguoiGui = null,
            // vi mot cu bam tay khi chua dang nhap cung cho ra null.
            if (CtdtXepHangKyGui::xep($maHoSo, null, CtdtLichSuGui::NGUON_CONSOLE)) {
                $soXep++;
            } else {
                $soBoQua++;
            }
        }

        $this->info('Da xep hang ky va gui: ' . $soXep . ' ho so; bo qua ' . $soBoQua . '.');

        if ($soBoQua > 0) {
            $this->line('Ho so bo qua thuong la CHUA KIEM (bo kiem con trong hang doi '
                . 'JobCtdt) hoac CON LOI. Chay lai lenh o luot sau de nhat tiep.');
        }

        return 0;
    }
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 5: Chạy cả bộ test của module**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

- [ ] **Step 6: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên bằng `git checkout -- app/Console/Commands/CtdtImport.php` sau mỗi lần):
1. Đổi `config('organization.chung_tu_dien_tu.import_tu_dong_gui')` thành `config('organization.chung_tu_dien_tu.submit_enabled')` → `KHONG_gui_khi_import_tu_dong_gui_dang_tat` phải ĐỎ.
2. Xoá nhánh `if ($nenKy !== CtdtQuyetDinhGui::KY)` → `ho_so_chua_kiem_khong_duoc_xep_hang_ky` phải ĐỎ.

- [ ] **Step 7: Viết mục vận hành vào tài liệu**

Thêm vào `docs/chung-tu-dien-tu-pl02.md`, ngay sau mục nói về ba worker hàng đợi:

````markdown
## Lệnh `ctdt:import` — chạy tự động không cần người trực

```bash
php artisan ctdt:import
```

Quét thư mục `organization.chung_tu_dien_tu.import_path`, nạp mọi tệp `.xml` **ngay trong
thư mục gốc** (không quét đệ quy), chuyển tệp đã xử lý sang `da-nap/`, tệp hỏng sang `loi/`,
rồi xếp hàng ký số và gửi cho những hồ sơ **đã kiểm và sạch**.

**Chạy MỘT LƯỢT rồi thoát**, khác `xml3176import:day` vốn lặp vô tận trong một tiến trình
nssm. Lý do: lệnh này **gửi thật lên cổng BHXH**, và một tiến trình sống mãi là thứ không ai
nhớ tắt khi cần dừng gấp. Nhịp chạy do lịch quyết định, không do lệnh.

### Bốn cái phanh

| Phanh | Tác dụng |
|---|---|
| `import_tu_dong_gui` | **Cổng riêng, mặc định TẮT.** `submit_enabled` cho phép *người* bấm nút gửi; khoá này cho phép *máy* gửi khi không ai nhìn. Bật cái thứ nhất không kéo theo cái thứ hai. |
| `--gioi-han=200` | Trần số **tệp** mỗi lượt. Một thư mục đổ nhầm 3000 tệp không thành 3000 lần POST trong một lượt. Vượt trần thì lệnh **báo to** rồi cắt, không cắt im lặng. |
| `--dry-run` | Chỉ liệt kê sẽ nạp gì. Không nạp, không chuyển tệp, không đặt khoá. |
| `--khong-ky` / `--khong-gui` | Dừng chuỗi ở nạp, hoặc ở ký. Dùng khi muốn nạp hàng loạt rồi tự mắt duyệt trước khi gửi. |

Thêm một khoá lượt (`ctdt:import:dang-chay`, hạn 60 phút) chặn hai lượt chạy chồng lên nhau —
hai lượt sẽ cùng nhặt một tệp và nạp hai lần.

### Hồ sơ "chưa kiểm" bị bỏ qua là chuyện BÌNH THƯỜNG

Lệnh xếp hàng ký ngay sau khi nạp, lúc bộ kiểm còn nằm trong hàng đợi `JobCtdt`. Nên phần
lớn hồ sơ vừa nạp sẽ **chưa kiểm** và bị bỏ qua — lượt chạy sau nhặt tiếp. Đây là lý do lệnh
nên chạy theo lịch lặp lại (ví dụ 15 phút một lần) chứ không phải mỗi ngày một lần.

⚠️ **Ba worker hàng đợi vẫn BẮT BUỘC.** Lệnh này chỉ *xếp hàng*; không có worker thì không
gì chạy cả.

### Cài lịch chạy trên máy chủ Windows

```bat
%NSSM_PATH%\nssm install "QLBV CtdtImport" %PHP_PATH% "%LARAVEL_PATH%artisan ctdt:import"
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppDirectory %LARAVEL_PATH%
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppExit Default Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppRestartDelay 900000
%NSSM_PATH%\nssm start "QLBV CtdtImport"
```

`AppRestartDelay 900000` = 15 phút giữa hai lượt. Đây là chỗ nhịp chạy được quyết định.
````

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/CtdtImport.php tests/Unit/Ctdt/CtdtImportCommandTest.php docs/chung-tu-dien-tu-pl02.md
git commit -m "feat(ctdt): ctdt:import xep hang ky va gui, kem bon cai phanh"
```

---

## Việc phải nghiệm thu bằng tay — không test nào thay được

1. **Chạy khô trên thư mục thật.** `php artisan ctdt:import --dry-run` với `import_path` trỏ vào inbox thật. Đối chiếu danh sách in ra với `dir` của thư mục đó. Xác nhận không có thư mục `da-nap/` nào được tạo.
2. **Chạy thật với `--khong-gui`** và **đúng một tệp** trong inbox. Xác nhận: tệp chuyển sang `da-nap/`, hồ sơ xuất hiện trên màn danh sách, và trạng thái là "Chưa kiểm" rồi chuyển sang "Chờ gửi"/"Còn lỗi" sau khi worker `JobCtdt` chạy.
3. **Bật `import_tu_dong_gui` rồi chạy thật với đúng một hồ sơ sạch.** Đọc `ma_ket_qua`, và kiểm bảng `ctdt_lich_su_gui` có đúng **một** dòng với `nguon = 'console'`. Hai dòng nghĩa là chuỗi chạy hai lần — dừng ngay và đọc lại khoá.
4. **Thử hai lượt chồng nhau.** Mở hai cửa sổ, chạy `php artisan ctdt:import` gần như đồng thời. Lượt thứ hai phải in `Mot luot ctdt:import khac dang chay`.
