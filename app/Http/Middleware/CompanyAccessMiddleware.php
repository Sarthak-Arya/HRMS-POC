<?php

namespace App\Http\Middleware;

use App\Services\Observability\DomainTelemetry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanyAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $companyId = $request->route('company_id');

        if (!$companyId) {
            abort(404, message: 'Company ID not found in URL.');
        }

        $company = Company::where('id', $companyId)->first();

        if (!$company) {
            abort(404, 'Company not found.');
        } else {
            session(key: ['company_id' => $companyId]);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        if (!$user->canAccessCompany($company)) {
            app(DomainTelemetry::class)->securityDenied('access.company.denied', [
                'company.id' => (int) $companyId,
                'route.template' => '/'.ltrim((string) optional($request->route())->uri(), '/'),
                'policy' => 'canAccessCompany',
            ]);
            abort(403, 'You do not have permission to access this company.');
        }

        return $next($request);
    }
}
