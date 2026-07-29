<?php

namespace App\Http\Livewire\Auth;

use App\Services\Auth\AuthLandingService;
use App\Services\Auth\UserRoleService;
use App\Services\Observability\DomainTelemetry;
use Livewire\Component;
use App\Models\User;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember_me = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function mount(AuthLandingService $landing)
    {
        if (auth()->user()) {
            return redirect()->to($landing->homeRoute(auth()->user()));
        }
    }

    public function login(AuthLandingService $landing, DomainTelemetry $telemetry)
    {
        $credentials = $this->validate();
        if (auth()->attempt(['email' => $this->email, 'password' => $this->password], $this->remember_me)) {
            $user = User::where(['email' => $this->email])->first();
            $user = UserRoleService::ensureDefaultRole($user);
            auth()->login($user, $this->remember_me);

            $telemetry->emit('auth.login.succeeded', 'security', 'success', [
                'actor.user_id' => $user->id,
            ]);

            return redirect()->intended($landing->homeRoute($user));
        }

        $telemetry->emit('auth.login.failed', 'security', 'failure', [], 'warning');

        return $this->addError('email', trans('auth.failed'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
