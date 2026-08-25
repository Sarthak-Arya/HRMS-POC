<?php

namespace App\Http\Controllers;

use App\Services\Auth\AuthLandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(AuthLandingService $landing): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            return redirect()->to($landing->homeRoute($user));
        }

        return view('landing.index');
    }
}
