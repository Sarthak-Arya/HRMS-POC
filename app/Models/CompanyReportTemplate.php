<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyReportTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'data_source',
        'config',
        'default_format',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reportRuns(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'template_id');
    }

    /**
     * @return list<string>
     */
    public function columnKeys(): array
    {
        $columns = $this->config['columns'] ?? [];
        $keys = [];

        foreach ($columns as $column) {
            if (is_string($column)) {
                $keys[] = $column;
                continue;
            }

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
     * @return list<array<string, mixed>>
     */
    public function filterDefinitions(): array
    {
        return $this->config['filters'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sortDefinitions(): array
    {
        return $this->config['sort'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function parameterKeys(): array
    {
        return $this->config['parameters'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutConfig(): array
    {
        $layout = $this->config['layout'] ?? [];

        return is_array($layout) ? $layout : [];
    }

    public function isGlobal(): bool
    {
        return $this->company_id === null;
    }

    public function isOwnedByCompany(int $companyId): bool
    {
        return (int) $this->company_id === $companyId;
    }
}
