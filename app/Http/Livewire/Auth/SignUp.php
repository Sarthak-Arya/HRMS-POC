<?php

namespace App\Http\Livewire\Auth;

use App\Enums\UserRole;
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

    public function mount(): void
    {
        if (auth()->user()) {
            redirect('/view-companies');
        }

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
        $this->validate();

        $role = UserRoleService::findBySlug($this->role);

        if (!$role) {
            $this->addError('role', 'The selected role is not available. Run: php artisan db:seed');

            return;
        }

        RolePermissionSync::syncRole($role);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole($role);

        auth()->login($user->fresh());

        return redirect()->route('view-companies');
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
