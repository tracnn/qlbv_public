<?php

namespace Tests\Support;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;

trait Xml3176RuleTestSupport
{
    /** Dựng checker per-type (nhận ErrorService + CommonValidationService) với fake service. */
    protected function makeChecker(string $class)
    {
        return new $class(new FakeXml3176ErrorService(), new CommonValidationService());
    }

    /** Gọi thẳng một method private của checker và trả kết quả. */
    protected function invokePrivate($obj, string $method, ...$args)
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);
        return $m->invoke($obj, ...$args);
    }

    /** Lấy mảng error_code từ Collection lỗi trả về. */
    protected function errorCodes($collection): array
    {
        return collect($collection)->pluck('error_code')->all();
    }

    /**
     * Dựng SQLite in-memory cho test cần truy vấn bảng (XML5 sibling, Complete).
     * Ghi đè CHÍNH kết nối 'mysql'. TUYỆT ĐỐI không dùng RefreshDatabase.
     */
    protected function bootXml3176Sqlite(array $files): void
    {
        config(['database.connections.mysql' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql');
        foreach ($files as $f) {
            $path = database_path('migrations/' . $f);
            require_once $path;
            $className = $this->migrationClassNameFromFile($path);
            (new $className())->up();
        }
    }

    /** Suy ra tên class migration từ tên file (chuẩn Laravel: snake_case sau timestamp -> StudlyCase). */
    private function migrationClassNameFromFile(string $path): string
    {
        $name = basename($path, '.php');
        // Bỏ tiền tố timestamp dạng YYYY_MM_DD_HHMMSS_
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);
        return \Illuminate\Support\Str::studly($name);
    }
}
