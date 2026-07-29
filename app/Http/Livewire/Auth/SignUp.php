<?php

namespace App\Http\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\B2bFirm;
use App\Services\Auth\AuthLandingService;
use App\Services\Auth\RolePermissionSync;
use App\Services\Auth\UserRoleService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SignUp extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = '';

    public function mount(AuthLandingService $landing): void
    {
        if (auth()->user()) {
            redirect()->to($landing->homeRoute(auth()->user()));
        }

        UserRoleService::ensureSelfRegisterableRolesExist();
        $this->role = UserRole::CompanyAdmin->value;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => [
                'required',
                Rule::in(array_map(fn (UserRole $role) => $role->value, UserRole::selfRegisterable())),
            ],
        ];
    }

    public function register()
    {
        // #region agent log
        $debugLogPath = base_path('debug-058eed.log');
        $writeDebugLog = static function (string $hypothesisId, string $location, string $message, array $data = []) use ($debugLogPath): void {
            file_put_contents($debugLogPath, json_encode([
                'sessionId' => '058eed',
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        };
        $normalizedEmail = strtolower(trim((string) $this->email));
        $existingUser = User::query()->where('email', $this->email)->first();
        $existingUserNormalized = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();
        $writeDebugLog('A', 'SignUp.php:register:entry', 'register called', [
            'email' => $this->email,
            'emailLen' => strlen((string) $this->email),
            'normalizedEmail' => $normalizedEmail,
            'existingExactId' => $existingUser?->id,
            'existingNormalizedId' => $existingUserNormalized?->id,
            'usersTableCount' => User::query()->count(),
            'dbName' => config('database.connections.' . config('database.default') . '.database'),
        ]);
        // #endregion

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // #region agent log
            $writeDebugLog('B', 'SignUp.php:register:validation_failed', 'validation failed', [
                'errors' => $e->errors(),
            ]);
            // #endregion
            throw $e;
        }

        // #region agent log
        $writeDebugLog('C', 'SignUp.php:register:validation_passed', 'validation passed', [
            'email' => $this->email,
        ]);
        // #endregion

        $role = UserRoleService::findBySlug($this->role);

        if (!$role) {
            $this->addError('role', 'The selected role is not available. Run: php artisan db:seed');

            return;
        }

        RolePermissionSync::syncRole($role);

        $selectedRole = UserRole::tryFrom($this->role);
        $b2bFirmId = null;

        if ($selectedRole === UserRole::B2bAdmin) {
            $firm = B2bFirm::create([
                'name' => trim($this->name) !== '' ? $this->name.' Firm' : 'New Firm',
            ]);
            $b2bFirmId = $firm->id;
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'b2b_firm_id' => $b2bFirmId,
        ]);

        // #region agent log
        $writeDebugLog('D', 'SignUp.php:register:user_created', 'user created', [
            'userId' => $user->id,
            'email' => $user->email,
        ]);
        // #endregion

        $user->assignRole($role);

        $user = $user->fresh();
        auth()->login($user);

        return redirect()->to(app(AuthLandingService::class)->homeRoute($user));
    }

    public function render()
    {
        return view('livewire.auth.sign-up', [
            'registerableRoles' => UserRoleService::selfRegisterableRoles(),
            'roleDescriptions' => collect(UserRole::selfRegisterable())
                ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->description()])
                ->all(),
        ]);
    }
}
