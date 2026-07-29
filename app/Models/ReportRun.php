<?php

namespace App\Models;

use App\Enums\Reports\ReportRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    protected $fillable = [
        'company_id',
        'template_id',
        'parameters',
        'status',
        'output_format',
        'file_path',
        'row_count',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'status' => ReportRunStatus::class,
        'row_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CompanyReportTemplate::class, 'template_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
