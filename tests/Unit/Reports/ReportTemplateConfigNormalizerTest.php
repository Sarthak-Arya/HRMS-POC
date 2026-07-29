<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\ReportDataSourceRegistry;
use App\Services\Reports\ReportTemplateConfigNormalizer;
use App\Services\Reports\ReportTemplateConfigValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportTemplateConfigNormalizerTest extends TestCase
{
    public function test_normalizes_legacy_string_columns_to_versioned_schema(): void
    {
        $normalizer = app(ReportTemplateConfigNormalizer::class);

        $normalized = $normalizer->normalize('employees', [
            'columns' => ['employee_code', 'employee_name'],
            'filters' => [
                ['field' => 'department_id', 'op' => '=', 'param' => 'department_id'],
            ],
            'sort' => [
                ['field' => 'employee_code', 'dir' => 'asc'],
            ],
            'parameters' => ['department_id'],
        ]);

        $this->assertSame(2, $normalized['version']);
        $this->assertCount(2, $normalized['columns']);
        $this->assertSame('employee_code', $normalized['columns'][0]['key']);
        $this->assertTrue($normalized['columns'][0]['visible']);
        $this->assertSame(0, $normalized['columns'][0]['sequence']);
        $this->assertSame('text', $normalized['columns'][0]['format']);
        $this->assertSame(['employee_code', 'employee_name'], $normalizer->visibleColumnKeys($normalized));
    }

    public function test_headings_prefer_custom_labels(): void
    {
        $normalizer = app(ReportTemplateConfigNormalizer::class);
        $sourceColumns = app(ReportDataSourceRegistry::class)->get('employees')->columns();

        $normalized = $normalizer->normalize('employees', [
            'columns' => [
                ['key' => 'employee_code', 'label' => 'Code', 'format' => 'text', 'visible' => true, 'sequence' => 0],
                ['key' => 'employee_name', 'label' => null, 'format' => 'text', 'visible' => true, 'sequence' => 1],
            ],
            'filters' => [],
            'sort' => [],
            'parameters' => [],
        ]);

        $this->assertSame(['Code', 'Employee Name'], $normalizer->headingsForColumns($normalized, $sourceColumns));
    }

    public function test_validator_rejects_invalid_format_and_duplicate_columns(): void
    {
        $validator = app(ReportTemplateConfigValidator::class);

        $this->expectException(ValidationException::class);

        $validator->validate('employees', [
            'columns' => [
                ['key' => 'employee_code', 'format' => 'text'],
                ['key' => 'employee_code', 'format' => 'bogus'],
            ],
            'filters' => [],
            'sort' => [],
            'parameters' => ['department_id'],
        ]);
    }

    public function test_salary_sheet_rejects_removing_mandatory_columns(): void
    {
        $validator = app(ReportTemplateConfigValidator::class);

        try {
            $validator->validate('salary_sheet', [
                'columns' => [
                    'employee.employee_code',
                    'employee.employee_name',
                    'work_days',
                ],
                'filters' => [
                    ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                ],
                'sort' => [],
                'parameters' => ['payroll_run_id', 'group_by', 'format'],
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('columns', $e->errors());
        }
    }

    public function test_salary_sheet_rejects_invalid_group_by(): void
    {
        $validator = app(ReportTemplateConfigValidator::class);

        try {
            $validator->validate('salary_sheet', [
                'columns' => array_keys(app(ReportDataSourceRegistry::class)->get('salary_sheet')->columns()),
                'filters' => [
                    ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                ],
                'sort' => [],
                'parameters' => ['payroll_run_id', 'group_by', 'format'],
                'layout' => ['group_by' => 'team'],
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('layout.group_by', $e->errors());
        }
    }

    public function test_default_config_for_each_source_validates(): void
    {
        $normalizer = app(ReportTemplateConfigNormalizer::class);
        $validator = app(ReportTemplateConfigValidator::class);

        foreach (app(ReportDataSourceRegistry::class)->all() as $source) {
            $config = $normalizer->defaultConfigForSource($source->key()->value);
            $validated = $validator->validate($source->key()->value, $config);
            $this->assertSame(2, $validated['version']);
            $this->assertNotEmpty($validated['columns']);
        }
    }
}
