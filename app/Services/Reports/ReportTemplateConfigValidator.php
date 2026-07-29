<?php

namespace App\Services\Reports;

use App\Enums\Reports\ReportColumnFormat;
use App\Services\Reports\DataSources\ReportDataSource;
use Illuminate\Validation\ValidationException;

class ReportTemplateConfigValidator
{
    public function __construct(
        private readonly ReportDataSourceRegistry $registry,
        private readonly ReportTemplateConfigNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed> normalized config
     */
    public function validate(string $dataSourceKey, array $config): array
    {
        $source = $this->registry->get($dataSourceKey);
        $normalized = $this->normalizer->normalize($dataSourceKey, $config);
        $errors = [];

        $availableColumns = array_keys($source->columns());
        $visibleKeys = [];
        $seenKeys = [];

        foreach ($normalized['columns'] as $index => $column) {
            $key = $column['key'];

            if (! in_array($key, $availableColumns, true)) {
                $errors["columns.{$index}.key"] = "Invalid column [{$key}].";
                continue;
            }

            if (isset($seenKeys[$key])) {
                $errors["columns.{$index}.key"] = "Duplicate column [{$key}].";
                continue;
            }
            $seenKeys[$key] = true;

            if (! in_array($column['format'], ReportColumnFormat::values(), true)) {
                $errors["columns.{$index}.format"] = "Invalid format for column [{$key}].";
            }

            if ($column['label'] !== null && mb_strlen($column['label']) > 100) {
                $errors["columns.{$index}.label"] = "Column label for [{$key}] must be 100 characters or fewer.";
            }

            if ($column['visible']) {
                $visibleKeys[] = $key;
            }
        }

        if ($visibleKeys === []) {
            $errors['columns'] = 'At least one visible column is required.';
        }

        foreach ($source->mandatoryColumnKeys() as $mandatoryKey) {
            if (! in_array($mandatoryKey, $visibleKeys, true)) {
                $errors['columns'] = "Mandatory column [{$mandatoryKey}] must remain visible for this data source.";
                break;
            }
        }

        foreach ($normalized['filters'] as $index => $filter) {
            $field = $filter['field'];
            $operator = $filter['op'];

            if ($field === '') {
                $errors["filters.{$index}.field"] = 'Filter field is required.';
                continue;
            }

            if (! in_array($field, $source->allowedFilterFields(), true)) {
                $errors["filters.{$index}.field"] = "Filter field [{$field}] is not allowed.";
            }

            if (! in_array($operator, $source->allowedFilterOperators(), true)) {
                $errors["filters.{$index}.op"] = "Filter operator [{$operator}] is not allowed.";
            }

            $param = $filter['param'] ?? null;
            if ($param !== null && $param !== '') {
                $allowedParams = array_column($source->parameters(), 'key');
                if (! in_array($param, $allowedParams, true)) {
                    $errors["filters.{$index}.param"] = "Filter parameter [{$param}] is not allowed.";
                }
            }
        }

        $allowedSortFields = $this->allowedSortFields($source);
        foreach ($normalized['sort'] as $index => $sortRule) {
            $field = $sortRule['field'];
            $dir = $sortRule['dir'];

            if ($field === '') {
                $errors["sort.{$index}.field"] = 'Sort field is required.';
                continue;
            }

            if (! in_array($field, $allowedSortFields, true)) {
                $errors["sort.{$index}.field"] = "Sort field [{$field}] is not allowed.";
            }

            if (! in_array($dir, ['asc', 'desc'], true)) {
                $errors["sort.{$index}.dir"] = 'Sort direction must be asc or desc.';
            }
        }

        $allowedParams = array_column($source->parameters(), 'key');
        $invalidParams = array_diff($normalized['parameters'], $allowedParams);
        if ($invalidParams !== []) {
            $errors['parameters'] = 'Invalid parameters: '.implode(', ', $invalidParams);
        }

        foreach ($source->parameters() as $definition) {
            if (($definition['required'] ?? false) && ! in_array($definition['key'], $normalized['parameters'], true)) {
                $errors['parameters'] = "Required parameter [{$definition['key']}] must be included.";
                break;
            }
        }

        $layout = $normalized['layout'];
        $allowedGroupBy = $source->allowedGroupByOptions();
        if ($allowedGroupBy !== []) {
            $groupBy = (string) ($layout['group_by'] ?? 'company');
            if (! in_array($groupBy, $allowedGroupBy, true)) {
                $errors['layout.group_by'] = 'Invalid group_by option ['.$groupBy.'].';
            }
        } elseif (isset($layout['group_by'])) {
            $errors['layout.group_by'] = 'group_by is not supported for this data source.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function allowedSortFields(ReportDataSource $source): array
    {
        return array_values(array_unique([
            ...$source->sortableFields(),
            ...array_keys($source->columns()),
            ...$source->allowedFilterFields(),
        ]));
    }

    /**
     * @return array<string, string>
     */
    public function availableColumns(string $dataSourceKey): array
    {
        return $this->registry->get($dataSourceKey)->columns();
    }

    /**
     * Metadata for the report builder UI.
     *
     * @return array{
     *     columns: array<string, array{label: string, format: string}>,
     *     filters: list<string>,
     *     operators: list<string>,
     *     sortable: list<string>,
     *     parameters: list<array{key: string, label: string, type: string, required?: bool}>,
     *     mandatory_columns: list<string>,
     *     group_by_options: list<string>
     * }
     */
    public function builderMetadata(string $dataSourceKey): array
    {
        $source = $this->registry->get($dataSourceKey);
        $formats = $source->columnFormats();
        $columns = [];

        foreach ($source->columns() as $key => $label) {
            $columns[$key] = [
                'label' => $label,
                'format' => $formats[$key] ?? ReportColumnFormat::Text->value,
            ];
        }

        return [
            'columns' => $columns,
            'filters' => $source->allowedFilterFields(),
            'operators' => $source->allowedFilterOperators(),
            'sortable' => $source->sortableFields(),
            'parameters' => $source->parameters(),
            'mandatory_columns' => $source->mandatoryColumnKeys(),
            'group_by_options' => $source->allowedGroupByOptions(),
        ];
    }
}
