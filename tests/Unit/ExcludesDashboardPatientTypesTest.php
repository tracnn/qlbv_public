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
            public function ids(): array { return $this->excludedPatientTypeIds(); }
        };
    }

    public function test_reads_config_and_casts_to_int()
    {
        config(['organization.dashboard.exclude_patient_type_ids' => ['43', 102]]);
        $this->assertSame([43, 102], $this->subject()->ids());
    }

    public function test_returns_empty_when_not_configured()
    {
        config(['organization.dashboard.exclude_patient_type_ids' => []]);
        $this->assertSame([], $this->subject()->ids());
    }

    public function test_returns_empty_when_key_missing()
    {
        config(['organization' => ['foo' => 'bar']]);
        $this->assertSame([], $this->subject()->ids());
    }
}
