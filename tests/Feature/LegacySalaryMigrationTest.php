<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacySalaryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_salary_columns_are_removed_after_migration(): void
    {
        $this->assertFalse(Schema::hasColumn('employees', 'basic_salary'));
        $this->assertFalse(Schema::hasColumn('employees', 'hra'));
        $this->assertFalse(Schema::hasColumn('employees', 'conveyance'));
        $this->assertFalse(Schema::hasColumn('employees', 'cca'));
        $this->assertFalse(Schema::hasColumn('employees', 'da'));
    }
}
