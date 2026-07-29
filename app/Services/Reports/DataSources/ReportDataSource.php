<?php

namespace App\Services\Reports\DataSources;

use App\Enums\Reports\ReportDataSourceKey;
use Illuminate\Database\Eloquent\Builder;

interface ReportDataSource
{
    public function key(): ReportDataSourceKey;

    public function label(): string;

    /**
     * @return array<string, string> column_key => heading label
     */
    public function columns(): array;

    /**
     * @return array<string, string> column_key => format (text|date|currency|number)
     */
    public function columnFormats(): array;

    /**
     * @return list<array{key: string, label: string, type: string, required?: bool}>
     */
    public function parameters(): array;

    /**
     * @return list<string>
     */
    public function allowedFilterFields(): array;

    /**
     * @return list<string>
     */
    public function allowedFilterOperators(): array;

    /**
     * @return list<string>
     */
    public function sortableFields(): array;

    /**
     * Columns that must remain visible for this source (e.g. salary sheet statutory fields).
     *
     * @return list<string>
     */
    public function mandatoryColumnKeys(): array;

    /**
     * Allowed layout.group_by values for builders (empty when not applicable).
     *
     * @return list<string>
     */
    public function allowedGroupByOptions(): array;

    /**
     * Default template config (legacy-compatible) used when creating a company template.
     *
     * @return array<string, mixed>
     */
    public function defaultConfig(): array;

    public function baseQuery(int $companyId): Builder;

    /**
     * @param  array<string, mixed>  $parameters
     * @param  list<array<string, mixed>>  $filterDefinitions
     */
    public function applyFilters(Builder $query, array $parameters, array $filterDefinitions): Builder;

    /**
     * @param  list<array<string, mixed>>  $sortDefinitions
     */
    public function applySorting(Builder $query, array $sortDefinitions): Builder;

    public function eagerLoadsForColumns(array $columnKeys): array;

    public function resolveValue(object $row, string $columnKey): mixed;
}
