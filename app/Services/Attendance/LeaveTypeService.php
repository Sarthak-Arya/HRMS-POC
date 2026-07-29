<?php

namespace App\Services\Attendance;

use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeaveTypeService
{
    /**
     * @return Collection<int, LeaveType>
     */
    public function listForCompany(int $companyId): Collection
    {
        return LeaveType::query()
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): LeaveType
    {
        $validated = $this->validate($data, $companyId);

        return LeaveType::create(array_merge($validated, ['company_id' => $companyId]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, int $leaveTypeId, array $data): LeaveType
    {
        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($leaveTypeId);
        $validated = $this->validate($data, $companyId, $leaveTypeId);
        $leaveType->update($validated);

        return $leaveType->fresh();
    }

    public function deactivate(int $companyId, int $leaveTypeId): void
    {
        LeaveType::where('company_id', $companyId)->findOrFail($leaveTypeId)->update(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, int $companyId, ?int $leaveTypeId = null): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'is_paid' => 'boolean',
            'annual_quota' => 'nullable|numeric|min:0',
            'carry_forward' => 'boolean',
            'encashable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validator->after(function ($v) use ($data, $companyId, $leaveTypeId) {
            $query = LeaveType::where('company_id', $companyId)
                ->where('code', strtoupper($data['code'] ?? ''));
            if ($leaveTypeId) {
                $query->where('id', '!=', $leaveTypeId);
            }
            if ($query->exists()) {
                $v->errors()->add('code', 'Leave type code already exists.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['code'] = strtoupper($validated['code']);

        return $validated;
    }
}
