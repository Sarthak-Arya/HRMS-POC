<?php

namespace App\Http\Middleware;

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

        $company = Company::where('company_id', $companyId)->first();

        if (!$company) {
            abort(404, 'Company not found.');
        } else {
            // Set company ID in session
            session(key: ['company_id' => $companyId]);
        }


        // Check if the authenticated user is allowed to access this company
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $handledBy = $company['handled_by'];
        if ($user->id != $handledBy) {
            abort(403, 'You do not have permission to access this company.');
        }

        return $next($request);
    }
}