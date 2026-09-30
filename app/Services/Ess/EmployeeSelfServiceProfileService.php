<?php

namespace App\Services\Ess;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSelfServiceProfileService
{
    /**
     * Fields employees may update themselves.
     *
     * @var list<string>
     */
    public const EDITABLE = [
        'phone',
        'present_address_line1',
        'present_address_line2',
        'present_city',
        'present_state',
        'present_pincode',
        'present_country',
        'permanent_address_line1',
        'permanent_address_line2',
        'permanent_city',
        'permanent_state',
        'permanent_pincode',
        'permanent_country',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Employee $employee, array $input): Employee
    {
        $payload = collect($input)
            ->only(self::EDITABLE)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        if (isset($payload['phone']) && $payload['phone'] !== null && strlen((string) $payload['phone']) > 30) {
            throw ValidationException::withMessages([
                'phone' => 'Phone number must be 30 characters or fewer.',
            ]);
        }

        return DB::transaction(function () use ($employee, $payload) {
            $employee->fill($payload);
            $employee->save();

            return $employee->fresh(['department', 'designation', 'location', 'manager']);
        });
    }
}
