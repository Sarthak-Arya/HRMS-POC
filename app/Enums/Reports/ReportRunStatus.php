<?php

namespace App\Enums\Reports;

enum ReportRunStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
