<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;

class ExcludesDashboardPatientTypesTest extends TestCase
{
    /** Test double phơi bày method protected của trait. */
    private function subject()
    {
        return new class {
            use ExcludesDashboardPatientTypes;
            public function codes(): array { return $this->excludedPatientTypeCodes(); }
            public function ids(): array { return $this->excludedPatientTypeIds(); }
        };
    }

    public function test_reads_codes_from_config_as_strings()
    {
        config(['organization.dashboard.exclude_patient_type_codes' => ['03', 97]]);
        // Ép về string, reindex; KHÔNG resolve id (không chạm DB).
        $this->assertSame(['03', '97'], $this->subject()->codes());
    }

    public function test_codes_empty_when_not_configured()
    {
        config(['organization.dashboard.exclude_patient_type_codes' => []]);
        $this->assertSame([], $this->subject()->codes());
    }

    public function test_codes_empty_when_key_missing()
    {
        config(['organization' => ['foo' => 'bar']]);
        $this->assertSame([], $this->subject()->codes());
    }

    public function test_ids_empty_without_db_when_no_codes()
    {
        // Không có code cấu hình => trả [] theo đường tắt, KHÔNG query his_patient_type.
        config(['organization.dashboard.exclude_patient_type_codes' => []]);
        $this->assertSame([], $this->subject()->ids());
    }
}
