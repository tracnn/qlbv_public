# Giai đoạn 5A — Lệnh Console `ctdt:import` chạy tự động trọn chuỗi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một lệnh Artisan chạy **liên tục** như `xml3176import:day`: quét thư mục inbox, nạp mọi tệp XML tìm được, rồi nhặt mọi hồ sơ đã đủ điều kiện mà xếp hàng ký số và gửi lên cổng BHXH — kèm đủ phanh để một thư mục đổ nhầm không biến thành hàng nghìn lần POST thật, và một cái phanh tay dừng được ngay giữa đêm mà không cần khởi động lại dịch vụ.

**Architecture:** Lệnh KHÔNG tự viết lại luồng nào. Nạp thì gọi `CtdtImporter::nhapTuTep()` sẵn có; xếp hàng ký-gửi thì gọi một lớp mới `CtdtXepHangKyGui` được **tách ra từ** `BHYTCtdtController::kyVaGui()` để màn hình và lệnh Console dùng chung đúng một bản (khoá chống bấm trùng, chuỗi `SignCtdtJob → SubmitCtdtJob`, cùng cổng chặn). Vì gửi tự động không có con mắt người, thêm bảng `ctdt_lich_su_gui` ghi từng lần gọi cổng thành bản ghi tra cứu được, thay vì chỉ một cột văn bản tự do.

**Bước nhặt hồ sơ đi bằng TRUY VẤN, không bằng "những mã vừa nạp trong lượt này".** Đây là khác biệt quan trọng nhất so với bản nháp đầu. Hồ sơ vừa nạp gần như luôn ở trạng thái *chưa kiểm* — bộ kiểm còn nằm trong hàng đợi `JobCtdt` — nên nhặt theo danh sách vừa nạp sẽ trượt gần hết, và phải trông chờ lượt sau. Nhặt bằng truy vấn `đã kiểm && sạch && chưa có ma_ket_qua` thì mỗi vòng đều vét đúng những hồ sơ *vừa mới* đủ điều kiện, bất kể lượt nào nạp chúng — kể cả hồ sơ người ta sửa tay trên màn hình.

**Chạy liên tục là chế độ chính**, giống `XML3176Import`. Nhưng khác lệnh đó ở ba chỗ, vì lệnh này POST thật lên cổng BHXH chứ không chỉ ghi tệp ra đĩa: có tệp cờ dừng có hiệu lực ngay, có trần số vòng để tiến trình tự thoát cho nssm dựng lại bản sạch, và vẫn giữ được chế độ một lượt cho chạy tay.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL, hàng đợi driver `database`.

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6 là sàn cứng.** Không cú pháp PHP 8 (không `match`, không thuộc tính nạp từ constructor, không toán tử `?->`, không dấu phẩy sau tham số cuối trong lời gọi hàm). Không có kiểu trả về `: void` trên `setUp()`.
- **Không có `Request::boolean()`** (Laravel thêm từ 5.8). Dùng `filter_var($x, FILTER_VALIDATE_BOOLEAN)`.
- **`config($khoá, $mặc_định)` không lùi về mặc định khi khoá tồn tại với giá trị `null`.** Với tên hàng đợi, luôn dùng `CtdtHangDoi::kiem()` / `::ky()` / `::gui()`.
- **`Cache::add($khoá, $giá_trị, $phút)` nhận PHÚT**, không phải giây, trong Laravel 5.5.
- **Luật "đã kiểm và sạch chưa" chỉ nằm ở `CtdtQuyetDinhGui::nenKy($daKiem, $soLoi)`.** Không nơi nào được chép lại.
- ⛔ **TUYỆT ĐỐI KHÔNG dùng `RefreshDatabase` hay `DatabaseMigrations`.** Hai trait đó gọi `migrate:fresh` — `DROP` toàn bộ bảng. Ngày 2026-08-21 chính bản kế hoạch này đã bắt dùng `DatabaseMigrations` và làm **xoá sạch CSDL phát triển `qlbv`**; không có binlog, không có bản dump nào chứa bảng `ctdt`. Muốn dựng bảng thật trong test thì dùng **`Tests\Support\DungBangCtdtSqlite`** — trait sẵn có, dựng 12 bảng của module trên SQLite bộ nhớ. Xem `tests/Unit/Ctdt/CtdtKyVaGuiTest.php` làm mẫu. `ChotAnToanCsdlTest` sẽ đỏ nếu ai dùng lại hai trait đó.
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
| `docs/organization.php` | Khoá cấu hình mới (`import_gioi_han`, `import_tu_dong_gui`) |
| `docs/chung-tu-dien-tu-pl02.md` | Mục vận hành: năm cái phanh, tệp `DUNG-GUI`, cài dịch vụ nssm |

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
use Tests\Support\DungBangCtdtSqlite;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Gui tu dong khong co nguoi ngoi nhin. Cot van ban tu do lich_su_gui khong tra loi duoc
 * cau hoi van hanh dau tien khi co su co: "dem qua lenh nen gui bao nhieu ho so, bao nhieu
 * cai bi tu choi".
 */
class CtdtLichSuGuiTest extends TestCase
{
    // KHONG DatabaseMigrations: trait do goi migrate:fresh, tuc DROP toan bo bang cua CSDL
    // phat trien. Da xay ra that ngay 2026-08-21. DungBangCtdtSqlite dung bang tren SQLite
    // bo nho va khong bao gio cham toi may chu that.
    use DungBangCtdtSqlite;

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

⚠️ **`DungBangCtdtSqlite` nạp từng migration của module rồi gọi `up()`** — bạn phải thêm migration mới vào danh sách `cacMigrationCtdt()` của trait đó, đúng thứ tự (bảng nhật ký tham chiếu `ctdt_ho_so` nên phải đứng sau nó).

Nếu test `xoa_ho_so_thi_nhat_ky_di_theo` đỏ: SQLite **mặc định tắt** khoá ngoại. **Đừng bật toàn cục** (đổi hành vi của cả bộ test cũ) — thay vào đó xoá test đó và thay bằng test khẳng định migration có khai khoá ngoại:

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
 * GIONG XML3176Import: chay lien tuc duoi mot dich vu nssm (co --lien-tuc). Do la khuon van
 * hanh cua du an nay, va no dung: gui phai bam sat luc ho so vua qua bo kiem, chu khong doi
 * mot lich 15 phut.
 *
 * KHAC XML3176Import o BA cho, vi lenh nay POST THAT len cong BHXH chu khong chi ghi tep:
 *
 *   1. Co TEP CO DUNG chan buoc gui NGAY vong sau. Mot tien trinh song mai giu config trong
 *      bo nho, nen sua import_tu_dong_gui thanh false KHONG an thua cho toi khi ai do nssm
 *      restart - va mot cai phanh chi an sau khi khoi dong lai thi khong phai la phanh.
 *   2. TU THOAT sau --so-vong. PHP chay dai han o gioi han 128MB se phinh; thoat chu dong de
 *      nssm dung lai ban sach thi khac han bi OOM giet giua luc dang goi cong.
 *   3. VAN GIU duoc che do mot luot (khong co --lien-tuc) cho chay tay va --dry-run.
 */
class CtdtImport extends Command
{
    protected $signature = 'ctdt:import
        {--duong-dan= : Thu muc quet, mac dinh lay tu organization.chung_tu_dien_tu.import_path}
        {--gioi-han=200 : Tran so TEP xu ly moi vong}
        {--dry-run : Chi liet ke nhung gi se lam, khong nap khong doi gi}
        {--khong-ky : Nap va kiem, dung truoc buoc ky}
        {--khong-gui : Ky nhung khong gui len cong}
        {--lien-tuc : Chay lap mai, giong xml3176import:day}
        {--nghi=5 : So GIAY nghi giua hai vong, chi dung voi --lien-tuc}
        {--so-vong=1000 : Tu thoat sau bay nhieu vong de nssm dung lai tien trinh sach}';

    protected $description = 'Quet thu muc inbox, nap chung tu dien tu PL02, ky va gui len cong BHXH';

    /** Tep nap xong chuyen vao day */
    const THU_MUC_DA_NAP = 'da-nap';

    /** Tep nap that bai chuyen vao day - KHONG xoa, con du lieu ma dieu tra */
    const THU_MUC_LOI = 'loi';

    /** Khoa chong hai luot chay chong len nhau */
    const KHOA_LUOT = 'ctdt:import:dang-chay';

    /**
     * Thoi han khoa luot, tinh bang PHUT.
     *
     * MOT NGAY chu khong mot gio: o che do --lien-tuc mot tien trinh song rat lau
     * (--so-vong=1000 voi --nghi=5 la khoang 83 phut, va con so do co the duoc nang). Khoa
     * het han giua chung la mo cua cho mot tien trinh thu hai chen vao, va hai tien trinh se
     * cung nhat mot tep len.
     *
     * Khoa mo coi khi tien trinh bi kill cung duoc xu bang Cache::forget() trong finally;
     * truong hop bi kill -9 thi nguoi van hanh xoa tay - cau lenh chep trong tai lieu.
     */
    const KHOA_LUOT_PHUT = 1440;

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

### Task 4: Nhặt hồ sơ đủ điều kiện và xếp hàng ký-gửi

**Files:**
- Modify: `app/Console/Commands/CtdtImport.php`
- Test: `tests/Unit/Ctdt/CtdtImportCommandTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtXepHangKyGui::xep($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)` → `bool` (Task 2); `CtdtQuyetDinhGui::nenKy($daKiem, $soLoi)` → `CHUA_KIEM|CON_LOI|KY`
- Produces: `CtdtImport::nhatVaXepHang($thuMuc)` → `int` số hồ sơ đã xếp; `CtdtImport::duocGui($thuMuc)` → `bool`; hằng `CtdtImport::TRAN_NHAT = 200`

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

    /** @test */
    public function nhat_ho_so_bang_TRUY_VAN_chu_khong_theo_danh_sach_vua_nap()
    {
        // Ho so vua nap gan nhu LUON o trang thai chua kiem - bo kiem con nam trong hang doi
        // JobCtdt. Nhat theo danh sach vua nap se truot gan het, va phai trong cho vong sau.
        // Truy van thang thi moi vong deu vet dung nhung ho so VUA MOI du dieu kien, ke ca
        // ho so nguoi ta sua tay tren man hinh.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertRegExp(
            '/function nhatVaXepHang\(\$thuMuc\)/',
            $nguon,
            'nhatVaXepHang() chi nhan thu muc, KHONG nhan danh sach ma ho so - no phai tu truy van'
        );
    }

    /** @test */
    public function nhat_ho_so_co_tran()
    {
        // Bat gui tren mot CSDL da co san hang nghin ho so sach se xep tat ca vao hang doi
        // trong MOT vong. Tran o day la thu duy nhat dung giua no va mot dot POST hang loat.
        $this->assertGreaterThan(0, CtdtImport::TRAN_NHAT);

        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('TRAN_NHAT', $nguon);
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

Thêm hằng vào lớp:

```php
    /** Tran so ho so nhat len xep hang moi vong */
    const TRAN_NHAT = 200;
```

Trong `quet()`, thay dòng `$this->info('Nap xong: ...');` và `return 0;` cuối hàm bằng:

```php
        $this->info('Nap xong: ' . count($dsMaHoSo) . ' ho so, ' . $soHong . ' tep hong.');

        if ($khoDe || $this->option('khong-ky')) {
            return 0;
        }

        // KHONG truyen $dsMaHoSo vao - xem chu thich nhatVaXepHang(). Chi truyen thu muc,
        // vi Task 5 se dat tep co dung o day.
        $this->nhatVaXepHang($thuMuc);

        return 0;
```

Thêm hai phương thức mới:

```php
    /**
     * Nhat moi ho so DA DU DIEU KIEN ma chua len duoc cong, roi xep hang ky - gui.
     *
     * KHONG NHAN danh sach ma ho so vua nap. Ho so vua nap gan nhu LUON o trang thai chua
     * kiem - bo kiem con nam trong hang doi JobCtdt - nen nhat theo danh sach vua nap se
     * truot gan het, va phai trong cho vong sau. Truy van thang thi moi vong deu vet dung
     * nhung ho so VUA MOI du dieu kien, bat ke vong nao nap chung, ke ca ho so nguoi ta sua
     * tay tren man hinh roi cho bo kiem chay lai.
     *
     * VI SAO KHONG cho tat ca vao hang doi roi de job tu loc: job ky da co cua chan cua no,
     * nhung xep 200 job de 195 cai tu thoat lam nhieu log den muc khong ai doc nua - va ba
     * hang doi thi dai ra ma khong ai biet vi sao.
     *
     * @param  string $thuMuc thu muc inbox - Task 5 dat tep co dung o day
     * @return int so ho so da xep hang
     */
    protected function nhatVaXepHang($thuMuc)
    {
        if (!$this->duocGui($thuMuc)) {
            return 0;
        }

        // Dieu kien o CSDL chi la BO LOC THO de thu hep tap phai doc len; luat that van do
        // CtdtQuyetDinhGui::nenKy() quyet dinh o duoi. Khong nhan doi luat o day: mot ban SQL
        // doc lap se lech voi nenKy() vao ngay ai do sua mot trong hai.
        $ungVien = CtdtHoSo::whereNotNull('checked_at')
            ->where('so_loi', '=', 0)
            ->where(function ($q) {
                $q->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '');
            })
            // Cu truoc moi truoc: ho so nam lau nhat la ho so nguoi ta doi lau nhat.
            ->orderBy('imported_at')
            ->limit(self::TRAN_NHAT)
            ->get();

        if ($ungVien->isEmpty()) {
            return 0;
        }

        $soXep = 0;
        $soBoQua = 0;

        foreach ($ungVien as $hoSo) {
            // Hoi lai bang nenKy() du da loc o SQL: day moi la luat that, va no la MOT NOI
            // duy nhat dung chung voi man hinh va SignCtdtJob.
            if (CtdtQuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi) !== CtdtQuyetDinhGui::KY) {
                $soBoQua++;
                continue;
            }

            // Noi ro nguon la CONSOLE: khong duoc de bang nhat ky suy tu $nguoiGui = null,
            // vi mot cu bam tay khi chua dang nhap cung cho ra null.
            //
            // xep() tra false khi ho so dang co luot xu ly khac - o che do lien tuc day la
            // chuyen THUONG XUYEN, vi vong truoc vua xep chinh no va chuoi con dang chay.
            if (CtdtXepHangKyGui::xep($hoSo->ma_ho_so, null, CtdtLichSuGui::NGUON_CONSOLE)) {
                $soXep++;
            } else {
                $soBoQua++;
            }
        }

        if ($soXep > 0) {
            $this->info('Da xep hang ky va gui: ' . $soXep . ' ho so; bo qua ' . $soBoQua
                . ' (dang xu ly o vong truoc).');
        }

        if ($ungVien->count() >= self::TRAN_NHAT) {
            $this->warn('Cham tran ' . self::TRAN_NHAT . ' ho so trong mot vong. Con ho so '
                . 'du dieu kien chua duoc nhat - vong sau nhat tiep.');
        }

        return $soXep;
    }

    /**
     * Co duoc gui len cong khong. Hoi MOI VONG, khong hoi mot lan roi nho.
     *
     * @param  string $thuMuc thu muc inbox
     * @return bool
     */
    protected function duocGui($thuMuc)
    {
        if ($this->option('khong-gui')) {
            return false;
        }

        // Hai cong tac RIENG. submit_enabled la "cho phep NGUOI bam nut gui"; khoa nay la
        // "cho phep MAY gui khi khong co ai nhin". Hai muc do tin cay khac nhau thi phai hai
        // cong tac khac nhau.
        if (!(bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui')) {
            return false;
        }

        return true;
    }
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: `OK (8 tests)`.

- [ ] **Step 5: Chạy cả bộ test của module**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

- [ ] **Step 6: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên bằng `git checkout -- app/Console/Commands/CtdtImport.php` sau mỗi lần):
1. Đổi `config('organization.chung_tu_dien_tu.import_tu_dong_gui')` thành `config('organization.chung_tu_dien_tu.submit_enabled')` → `KHONG_gui_khi_import_tu_dong_gui_dang_tat` phải ĐỎ.
2. Xoá nhánh gọi `CtdtQuyetDinhGui::nenKy()` trong `nhatVaXepHang()` → `ho_so_chua_kiem_khong_duoc_xep_hang_ky` phải ĐỎ.
3. Đổi `protected function nhatVaXepHang($thuMuc)` thành `protected function nhatVaXepHang(array $dsMaHoSo)` → `nhat_ho_so_bang_TRUY_VAN_chu_khong_theo_danh_sach_vua_nap` phải ĐỎ.
4. Bỏ `->limit(self::TRAN_NHAT)` → `nhat_ho_so_co_tran` phải ĐỎ.

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/CtdtImport.php tests/Unit/Ctdt/CtdtImportCommandTest.php
git commit -m "feat(ctdt): ctdt:import nhat ho so du dieu kien bang truy van"
```

---

### Task 5: Chế độ chạy liên tục + phanh tay

**Vì sao task riêng:** đây là ranh giới mà người duyệt có thể chấp nhận toàn bộ phần trước mà vẫn bác chế độ chạy nền vô hạn. Và ba cái phanh trong task này không phải trang trí — chúng là khác biệt giữa lệnh này với `xml3176import:day`, vì lệnh này POST thật lên cổng BHXH.

**Files:**
- Modify: `app/Console/Commands/CtdtImport.php`
- Modify: `docs/chung-tu-dien-tu-pl02.md`
- Test: `tests/Unit/Ctdt/CtdtImportCommandTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtImport::quet($thuMuc, $gioiHan, $khoDe)` → `int`; `CtdtImport::duocGui($thuMuc)` → `bool` (Task 4)
- Produces:
  - `CtdtImport::TEP_CO_DUNG = 'DUNG-GUI'` — tên tệp cờ đặt trong thư mục inbox
  - `CtdtImport::coDung($thuMuc)` → `bool`
  - `CtdtImport::vongLap($thuMuc, $gioiHan)` → `int`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtImportCommandTest.php`:

```php
    /** @test */
    public function tep_co_dung_chan_ngay_buoc_gui()
    {
        // Mot tien trinh song mai GIU CONFIG TRONG BO NHO. Sua import_tu_dong_gui thanh
        // false KHONG an thua cho toi khi ai do nssm restart - va mot cai phanh chi an sau
        // khi khoi dong lai thi khong phai la phanh. Nguoi truc dem phai dung duoc bang mot
        // thao tac ho lam duoc: tao mot tep rong.
        $thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ctdt-thu-' . mt_rand();
        mkdir($thuMuc);

        $lenh = new CtdtImport();

        $this->assertFalse($lenh->coDung($thuMuc), 'Chua co tep co thi khong duoc dung');

        touch($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::TEP_CO_DUNG);

        $this->assertTrue($lenh->coDung($thuMuc),
            'Co tep DUNG-GUI thi phai dung ngay, khong cho khoi dong lai dich vu');

        unlink($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::TEP_CO_DUNG);
        rmdir($thuMuc);
    }

    /** @test */
    public function che_do_lien_tuc_co_TRAN_SO_VONG()
    {
        // PHP chay dai han o gioi han 128MB se phinh. Thoat chu dong de nssm dung lai ban
        // sach thi khac han bi OOM giet giua luc dang goi cong BHXH.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('so-vong')->getDefault();

        $this->assertNotNull($macDinh, 'Phai co tran so vong');
        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function che_do_lien_tuc_co_nghi_giua_hai_vong()
    {
        // Khong nghi la mot vong lap ban CPU va do log khong ngung.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('nghi')->getDefault();

        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function dry_run_KHONG_duoc_chay_lien_tuc()
    {
        // --dry-run la de nguoi ta NHIN mot lan roi quyet dinh. Gap voi --lien-tuc thi no do
        // log mai ma khong lam gi ca - va che mat dong log that.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('dry-run', $nguon);
        $this->assertRegExp(
            '/lien-tuc.{0,400}dry-run|dry-run.{0,400}lien-tuc/s',
            $nguon,
            'Phai co cho tu choi ket hop --dry-run voi --lien-tuc'
        );
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: bốn test mới ĐỎ (`coDung()` chưa tồn tại, `--so-vong`/`--nghi` chưa có).

- [ ] **Step 3: Thêm hằng và tệp cờ dừng**

Trong `app/Console/Commands/CtdtImport.php`, thêm hằng:

```php
    /**
     * Tep co dat trong thu muc inbox de DUNG BUOC GUI ngay vong sau.
     *
     * VI SAO KHONG dung config: tien trinh chay lien tuc giu config trong bo nho, nen sua
     * import_tu_dong_gui thanh false khong an thua cho toi khi ai do nssm restart. Mot cai
     * phanh chi an sau khi khoi dong lai thi khong phai la phanh.
     *
     * VI SAO la TEP chu khong phai khoa cache: nguoi truc dem tao duoc mot tep rong. Ho
     * khong sua duoc PHP, va co the khong vao duoc Redis.
     */
    const TEP_CO_DUNG = 'DUNG-GUI';
```

và phương thức:

```php
    /**
     * Co tep co dung trong thu muc inbox khong.
     *
     * Cong khai de test goi truc tiep duoc - day la cai phanh tay, no dang duoc kiem ky
     * hon phan con lai.
     *
     * @param  string $thuMuc
     * @return bool
     */
    public function coDung($thuMuc)
    {
        return file_exists(rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . self::TEP_CO_DUNG);
    }
```

Trong `duocGui($thuMuc)` (Task 4 đã cho nó nhận thư mục sẵn), chèn phép hỏi tệp cờ lên **đầu tiên**:

```php
    protected function duocGui($thuMuc)
    {
        // Hoi tep co TRUOC MOI THU: day la phanh tay, no phai thang moi cau hinh.
        if ($this->coDung($thuMuc)) {
            $this->warn('Thay tep ' . self::TEP_CO_DUNG . ' trong thu muc inbox - DUNG GUI. '
                . 'Van tiep tuc nap va kiem. Xoa tep do di de gui lai.');

            return false;
        }

        if ($this->option('khong-gui')) {
            return false;
        }

        if (!(bool) config('organization.chung_tu_dien_tu.import_tu_dong_gui')) {
            return false;
        }

        return true;
    }
```

Không đổi chữ ký nào: Task 4 đã cho `nhatVaXepHang($thuMuc)` và `duocGui($thuMuc)` nhận sẵn thư mục, đúng để chỗ này cắm vào.

- [ ] **Step 4: Thêm vòng lặp**

Trong `handle()`, thay `return $this->quet($thuMuc, (int) $this->option('gioi-han'), $khoDe);` bằng:

```php
            if ($this->option('lien-tuc')) {
                if ($khoDe) {
                    // --dry-run la de nguoi ta NHIN mot lan roi quyet dinh. Gap voi
                    // --lien-tuc thi no do log mai ma khong lam gi ca, va che mat dong log
                    // that cua nhung lenh khac.
                    $this->error('Khong ket hop --dry-run voi --lien-tuc duoc.');

                    return 1;
                }

                return $this->vongLap($thuMuc, (int) $this->option('gioi-han'));
            }

            return $this->quet($thuMuc, (int) $this->option('gioi-han'), $khoDe);
```

Thêm phương thức:

```php
    /**
     * Chay lien tuc, giong xml3176import:day - nhung TU THOAT sau --so-vong.
     *
     * VI SAO tu thoat chu khong `while (true)`: PHP chay dai han o gioi han 128MB se phinh,
     * va viec du an da phai co lenh jobs:restart-stuck cho thay hang doi tung ket that.
     * Thoat chu dong de nssm dung lai mot tien trinh sach thi khac han bi OOM giet GIUA LUC
     * dang goi cong BHXH - luc do khong ai biet cong da nhan hay chua.
     *
     * @param  string $thuMuc
     * @param  int    $gioiHan
     * @return int ma thoat
     */
    protected function vongLap($thuMuc, $gioiHan)
    {
        $soVong = max(1, (int) $this->option('so-vong'));
        $nghi = max(1, (int) $this->option('nghi'));

        $this->info('Chay lien tuc: nghi ' . $nghi . ' giay giua hai vong, tu thoat sau '
            . $soVong . ' vong.');

        for ($i = 1; $i <= $soVong; $i++) {
            try {
                $this->quet($thuMuc, $gioiHan, false);
            } catch (\Exception $e) {
                // Mot vong hong KHONG duoc lam chet ca tien trinh: dung o day nghia la khong
                // ho so nao duoc gui nua cho toi khi co nguoi phat hien ra dich vu da chet.
                Log::error('ctdt:import vong ' . $i . ' hong: ' . $e->getMessage());
                $this->error('Vong ' . $i . ' hong: ' . $e->getMessage());
            }

            if ($i < $soVong) {
                sleep($nghi);
            }
        }

        $this->info('Da chay du ' . $soVong . ' vong, thoat de nssm dung lai tien trinh sach.');

        return 0;
    }
```

⚠️ **Khoá lượt của Task 3 phải đặt ngoài vòng lặp, không đặt trong.** Đặt trong thì vòng thứ hai sẽ tự thấy khoá của chính mình và bỏ qua vĩnh viễn. Kiểm lại `handle()`: `Cache::add(self::KHOA_LUOT, ...)` chạy **một lần** trước khi vào `vongLap()`, và `Cache::forget()` chạy trong `finally` **sau khi** vòng lặp kết thúc.

⚠️ **Kiểm lại thời hạn khoá lượt so với vòng đời tiến trình.** Task 3 đặt `KHOA_LUOT_PHUT = 1440`. Nếu ai đó nâng `--so-vong` hay `--nghi` lên tới mức một tiến trình sống quá một ngày, khoá sẽ hết hạn giữa chừng và một tiến trình thứ hai chen vào được — hai tiến trình sẽ cùng nhặt một tệp lên. Tính lại `so-vong × nghi` và xác nhận nó còn cách xa 1440 phút.

- [ ] **Step 5: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportCommandTest.php`
Kỳ vọng: `OK (12 tests)`.

- [ ] **Step 6: Chạy cả bộ test của module**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

- [ ] **Step 7: Chạy thử vòng lặp ngắn bằng tay**

```bash
php artisan ctdt:import --lien-tuc --so-vong=3 --nghi=2 --khong-ky --duong-dan=storage/app/ctdt-thu
```

Kỳ vọng: chạy đúng 3 vòng rồi thoát, in dòng "Da chay du 3 vong". Trong lúc chạy, tạo tệp `storage/app/ctdt-thu/DUNG-GUI` và xác nhận vòng sau in cảnh báo dừng gửi.

⚠️ Giữ `--khong-ky` trong mọi lần chạy thử trên máy này.

- [ ] **Step 8: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên bằng `git checkout -- app/Console/Commands/CtdtImport.php` sau mỗi lần):
1. Trong `duocGui()`, chuyển lời gọi `$this->coDung($thuMuc)` xuống **sau** phép hỏi config → `tep_co_dung_chan_ngay_buoc_gui` vẫn xanh, nhưng đây là hồi quy thật; **thay bằng** xoá hẳn nhánh `coDung()` → test phải ĐỎ.
2. Đổi `for ($i = 1; $i <= $soVong; $i++)` thành `while (true)` → `che_do_lien_tuc_co_TRAN_SO_VONG` vẫn xanh (nó chỉ đọc định nghĩa tuỳ chọn). Đây là **lỗ hổng đã biết** của test đó: nó bảo vệ *sự tồn tại* của trần chứ không bảo vệ *việc dùng* trần. Ghi vào báo cáo, đừng lặng lẽ bỏ qua.

- [ ] **Step 9: Commit**

```bash
git add app/Console/Commands/CtdtImport.php tests/Unit/Ctdt/CtdtImportCommandTest.php
git commit -m "feat(ctdt): che do chay lien tuc kem tep co dung va tran so vong"
```

---

### Task 6: Tài liệu vận hành

**Files:**
- Modify: `docs/chung-tu-dien-tu-pl02.md`

- [ ] **Step 1: Viết mục vận hành**

Thêm vào `docs/chung-tu-dien-tu-pl02.md`, ngay sau mục nói về ba worker hàng đợi:

````markdown
## Lệnh `ctdt:import` — chạy tự động không cần người trực

```bash
php artisan ctdt:import --lien-tuc
```

Mỗi vòng làm hai việc: **quét** thư mục `organization.chung_tu_dien_tu.import_path`, nạp mọi
tệp `.xml` **ngay trong thư mục gốc** (không quét đệ quy), chuyển tệp đã xử lý sang `da-nap/`
và tệp hỏng sang `loi/`; rồi **nhặt** mọi hồ sơ đã kiểm, sạch, chưa có `ma_ket_qua` mà xếp
hàng ký số và gửi.

Chạy liên tục dưới một dịch vụ nssm, cùng khuôn với `xml3176import:day`. Bỏ `--lien-tuc` thì
lệnh chạy một lượt rồi thoát — dùng khi chạy tay.

### Vì sao bước nhặt đi bằng truy vấn, không theo "vừa nạp"

Hồ sơ vừa nạp gần như **luôn** ở trạng thái *chưa kiểm* — bộ kiểm còn nằm trong hàng đợi
`JobCtdt`. Nếu lệnh chỉ xếp hàng cho những mã nó vừa nạp thì gần như lượt nào cũng trượt, và
hồ sơ phải chờ tới lượt sau mới được nhặt. Truy vấn thẳng thì mỗi vòng vét đúng những hồ sơ
*vừa mới* đủ điều kiện — kể cả hồ sơ người ta sửa tay trên màn hình rồi cho kiểm lại.

### Năm cái phanh

| Phanh | Tác dụng |
|---|---|
| **Tệp `DUNG-GUI`** | Đặt một tệp rỗng tên `DUNG-GUI` trong thư mục inbox là **dừng gửi ngay vòng sau**, vẫn tiếp tục nạp và kiểm. Xoá tệp đi là chạy lại. Đây là phanh tay duy nhất có tác dụng **không cần khởi động lại dịch vụ** — xem khối cảnh báo ngay dưới. |
| `import_tu_dong_gui` | **Cổng riêng, mặc định TẮT.** `submit_enabled` cho phép *người* bấm nút gửi; khoá này cho phép *máy* gửi khi không ai nhìn. Bật cái thứ nhất không kéo theo cái thứ hai. |
| `--gioi-han=200` | Trần số **tệp** nạp mỗi vòng. Một thư mục đổ nhầm 3000 tệp không thành 3000 lần POST trong một vòng. Vượt trần thì lệnh **báo to** rồi cắt, không cắt im lặng. |
| `--so-vong=1000` | Tiến trình **tự thoát** sau bấy nhiêu vòng để nssm dựng lại bản sạch. |
| `--dry-run` / `--khong-ky` / `--khong-gui` | Chỉ liệt kê; hoặc dừng chuỗi ở nạp; hoặc dừng ở ký. `--dry-run` **không** kết hợp được với `--lien-tuc`. |

⚠️ **Sửa `import_tu_dong_gui` KHÔNG dừng được tiến trình đang chạy.** Tiến trình sống lâu giữ
cấu hình trong bộ nhớ; khoá đó chỉ được đọc lại khi dịch vụ khởi động lại. Muốn dừng gửi ngay
thì **tạo tệp `DUNG-GUI`** trong thư mục inbox:

```bat
type nul > D:\XML\ChungTuDienTu\inbox\DUNG-GUI
```

Hồ sơ vẫn được nạp và kiểm bình thường — chỉ bước gửi bị chặn.

### Khoá lượt

Một khoá cache (`ctdt:import:dang-chay`) chặn hai tiến trình chạy chồng lên nhau. Nếu tiến
trình bị kill cứng, khoá còn sót lại và lượt sau sẽ bỏ qua với thông báo *"Mot luot
ctdt:import khac dang chay"*. Xoá tay:

```bash
php artisan tinker --execute="Cache::forget('ctdt:import:dang-chay');"
```

⚠️ **Ba worker hàng đợi vẫn BẮT BUỘC.** Lệnh này chỉ *xếp hàng*; không có worker thì không
gì chạy cả. Chính ba worker đó — chứ không phải vòng lặp của lệnh này — mới là thứ gửi hồ sơ
lên cổng, y như `JobSubmitXml3176` bên XML3176.

### Cài dịch vụ trên máy chủ Windows

```bat
%NSSM_PATH%\nssm install "QLBV CtdtImport" %PHP_PATH% "%LARAVEL_PATH%artisan ctdt:import --lien-tuc"
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppDirectory %LARAVEL_PATH%
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppExit Default Restart
%NSSM_PATH%\nssm set "QLBV CtdtImport" AppRestartDelay 10000
%NSSM_PATH%\nssm start "QLBV CtdtImport"
```

`AppExit Default Restart` là phần bắt buộc: lệnh **cố ý thoát** sau `--so-vong` vòng, và nssm
phải dựng lại nó. `AppRestartDelay 10000` chỉ là 10 giây nghỉ giữa hai tiến trình — nhịp thật
do `--nghi` quyết định.
````

- [ ] **Step 2: Kiểm bảng phanh khớp mã**

Đọc lại `app/Console/Commands/CtdtImport.php` và đối chiếu từng dòng trong bảng "Năm cái phanh"
với tuỳ chọn thật trong `$signature`. Một tài liệu vận hành nói sai tên cờ còn tệ hơn không có:
người trực đêm sẽ gõ theo nó và tưởng mình đã dừng được hệ thống.

- [ ] **Step 3: Commit**

```bash
git add docs/chung-tu-dien-tu-pl02.md
git commit -m "docs(ctdt): muc van hanh cho ctdt:import chay lien tuc"
```

---

## Việc phải nghiệm thu bằng tay — không test nào thay được

1. **Chạy khô trên thư mục thật.** `php artisan ctdt:import --dry-run` với `import_path` trỏ vào inbox thật. Đối chiếu danh sách in ra với `dir` của thư mục đó. Xác nhận không có thư mục `da-nap/` nào được tạo.
2. **Chạy thật với `--khong-gui`** và **đúng một tệp** trong inbox. Xác nhận: tệp chuyển sang `da-nap/`, hồ sơ xuất hiện trên màn danh sách, và trạng thái là "Chưa kiểm" rồi chuyển sang "Chờ gửi"/"Còn lỗi" sau khi worker `JobCtdt` chạy.
3. **Bật `import_tu_dong_gui` rồi chạy thật với đúng một hồ sơ sạch.** Đọc `ma_ket_qua`, và kiểm bảng `ctdt_lich_su_gui` có đúng **một** dòng với `nguon = 'console'`. Hai dòng nghĩa là chuỗi chạy hai lần — dừng ngay và đọc lại khoá.
4. **Thử hai lượt chồng nhau.** Mở hai cửa sổ, chạy `php artisan ctdt:import` gần như đồng thời. Lượt thứ hai phải in `Mot luot ctdt:import khac dang chay`.
5. **Thử phanh tay giữa lúc đang chạy.** Chạy `--lien-tuc --so-vong=20 --nghi=3`, đợi vài vòng rồi tạo tệp `DUNG-GUI` trong inbox. Vòng kế tiếp phải in cảnh báo và **ngừng xếp hàng gửi** trong khi vẫn tiếp tục nạp. Xoá tệp đi, vòng sau phải gửi lại. Đây là thao tác người trực đêm sẽ phải làm dưới áp lực — nếu nó không chạy đúng ngay lần thử đầu thì đừng bật `import_tu_dong_gui` trên máy thật.
6. **Để chạy liên tục qua một đêm với `--khong-gui`**, sáng hôm sau đọc bộ nhớ tiến trình trong Task Manager và đếm số vòng trong log. Đây là phép đo duy nhất cho biết PHP có phình tới mức chạm 128MB trước khi hết `--so-vong` hay không.
