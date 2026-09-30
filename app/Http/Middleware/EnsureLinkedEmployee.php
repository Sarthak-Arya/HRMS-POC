<?php

namespace App\Http\Middleware;

use App\Services\Ess\EmployeeContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLinkedEmployee
{
    public function __construct(
        private readonly EmployeeContext $employeeContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $companyId = $request->route('company_id');

        if (! $user || ! $companyId) {
            abort(403, 'Employee portal access requires a linked company.');
        }

        $employee = $this->employeeContext->findForUser($user, $companyId);

        if (! $employee) {
            abort(403, 'Your login is not linked to an active employee record for this company. Ask HR to invite you to the portal.');
        }

        $request->attributes->set('ess.employee', $employee);
        $request->attributes->set('ess.employee_id', $employee->id);

        return $next($request);
    }
}
