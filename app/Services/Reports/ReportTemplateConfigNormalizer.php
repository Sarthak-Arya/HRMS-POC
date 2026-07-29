<?php

namespace App\Services\Reports;

use App\Enums\Reports\ReportColumnFormat;
use App\Services\Reports\DataSources\ReportDataSource;

class ReportTemplateConfigNormalizer
{
    public const SCHEMA_VERSION = 2;

    public function __construct(
        private readonly ReportDataSourceRegistry $registry,
    ) {}

    /**
     * Normalize a template config (legacy or v2) into the versioned schema.
     *
     * @param  array<string, mixed>  $config
     * @return array{
     *     version: int,
     *     columns: list<array{key: string, label: ?string, format: string, visible: bool, sequence: int}>,
     *     filters: list<array{field: string, op: string, param?: string, sequence: int}>,
     *     sort: list<array{field: string, dir: string}>,
     *     parameters: list<string>,
     *     layout: array<string, mixed>
     * }
     */
    public function normalize(string $dataSourceKey, array $config): array
    {
        $source = $this->registry->get($dataSourceKey);
        $available = $source->columns();
        $defaultFormats = $source->columnFormats();

        $rawColumns = $config['columns'] ?? [];
        $normalizedColumns = [];

        if (is_array($rawColumns)) {
            $sequence = 0;
            foreach ($rawColumns as $column) {
                if (is_string($column)) {
                    $key = $column;
                    $normalizedColumns[] = [
                        'key' => $key,
                        'label' => null,
                        'format' => $defaultFormats[$key] ?? ReportColumnFormat::Text->value,
                        'visible' => true,
                        'sequence' => $sequence,
                    ];
                    $sequence++;
                    continue;
                }

                if (! is_array($column)) {
                    continue;
                }

                $key = (string) ($column['key'] ?? '');
                if ($key === '') {
                    continue;
                }

                $format = (string) ($column['format'] ?? $defaultFormats[$key] ?? ReportColumnFormat::Text->value);
                if (! in_array($format, ReportColumnFormat::values(), true)) {
                    $format = $defaultFormats[$key] ?? ReportColumnFormat::Text->value;
                }

                $normalizedColumns[] = [
                    'key' => $key,
                    'label' => isset($column['label']) && $column['label'] !== ''
                        ? (string) $column['label']
                        : null,
                    'format' => $format,
                    'visible' => array_key_exists('visible', $column) ? (bool) $column['visible'] : true,
                    'sequence' => isset($column['sequence']) ? (int) $column['sequence'] : $sequence,
                ];
                $sequence++;
            }
        }

        usort($normalizedColumns, fn (array $a, array $b) => $a['sequence'] <=> $b['sequence']);

        foreach ($normalizedColumns as $index => &$column) {
            $column['sequence'] = $index;
            if ($column['label'] === null && isset($available[$column['key']])) {
                // Keep null so runner falls back to source label; store nothing extra.
            }
        }
        unset($column);

        $rawFilters = $config['filters'] ?? [];
        $normalizedFilters = [];
        if (is_array($rawFilters)) {
            foreach (array_values($rawFilters) as $index => $filter) {
                if (! is_array($filter)) {
                    continue;
                }

                $normalizedFilters[] = [
                    'field' => (string) ($filter['field'] ?? ''),
                    'op' => (string) ($filter['op'] ?? '='),
                    'param' => isset($filter['param']) ? (string) $filter['param'] : null,
                    'sequence' => isset($filter['sequence']) ? (int) $filter['sequence'] : $index,
                ];
            }
        }

        usort($normalizedFilters, fn (array $a, array $b) => $a['sequence'] <=> $b['sequence']);
        foreach ($normalizedFilters as $index => &$filter) {
            $filter['sequence'] = $index;
            if ($filter['param'] === null) {
                unset($filter['param']);
            }
        }
        unset($filter);

        $rawSort = $config['sort'] ?? [];
        $normalizedSort = [];
        if (is_array($rawSort)) {
            foreach ($rawSort as $sortRule) {
                if (! is_array($sortRule)) {
                    continue;
                }

                $normalizedSort[] = [
                    'field' => (string) ($sortRule['field'] ?? ''),
                    'dir' => strtolower((string) ($sortRule['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc',
                ];
            }
        }

        $parameters = $config['parameters'] ?? [];
        if (! is_array($parameters)) {
            $parameters = [];
        }
        $parameters = array_values(array_map('strval', $parameters));

        $layout = $config['layout'] ?? [];
        if (! is_array($layout)) {
            $layout = [];
        }

        if ($dataSourceKey === 'salary_sheet' && ! isset($layout['group_by'])) {
            $layout['group_by'] = 'company';
        }

        return [
            'version' => self::SCHEMA_VERSION,
            'columns' => $normalizedColumns,
            'filters' => $normalizedFilters,
            'sort' => $normalizedSort,
            'parameters' => $parameters,
            'layout' => $layout,
        ];
    }

    /**
     * Flatten normalized columns to the legacy string-key list used by existing query helpers.
     *
     * @param  array<string, mixed>  $normalizedConfig
     * @return list<string>
     */
    public function visibleColumnKeys(array $normalizedConfig): array
    {
        $keys = [];

        foreach ($normalizedConfig['columns'] ?? [] as $column) {
            if (! is_array($column)) {
                continue;
            }

            if (($column['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($column['key'] ?? '');
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $normalizedConfig
     * @param  array<string, string>  $sourceColumns
     * @return list<string>
     */
    public function headingsForColumns(array $normalizedConfig, array $sourceColumns): array
    {
        $headings = [];

        foreach ($normalizedConfig['columns'] ?? [] as $column) {
            if (! is_array($column) || ($column['visible'] ?? true) === false) {
                continue;
            }

            $key = (string) ($column['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $customLabel = $column['label'] ?? null;
            $headings[] = is_string($customLabel) && $customLabel !== ''
                ? $customLabel
                : ($sourceColumns[$key] ?? $key);
        }

        return $headings;
    }

    /**
     * Build a default v2 config for creating a company template from a curated source.
     *
     * @return array<string, mixed>
     */
    public function defaultConfigForSource(string $dataSourceKey): array
    {
        $source = $this->registry->get($dataSourceKey);

        return $this->normalize($dataSourceKey, $source->defaultConfig());
    }
}
