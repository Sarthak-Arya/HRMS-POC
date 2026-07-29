<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceScopeType;
use App\Models\AttendancePolicyAssignment;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttendancePolicyAssignmentService
{
    /**
     * @return Collection<int, AttendancePolicyAssignment>
     */
    public function listForCompany(int $companyId, ?AttendanceScopeType $scopeType = null): Collection
    {
        return AttendancePolicyAssignment::with('policy')
            ->where('company_id', $companyId)
            ->when($scopeType, fn ($q) => $q->where('scope_type', $scopeType->value))
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(int $companyId, array $data): AttendancePolicyAssignment
    {
        $validated = $this->validate($data);
        $scopeType = AttendanceScopeType::from($validated['scope_type']);
        $scopeId = $validated['scope_id'] ?? null;

        $this->assertScopeBelongsToCompany($companyId, $scopeType, $scopeId);
        $this->assertNoOverlap(
            $companyId,
            $scopeType,
            $scopeId,
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
        );

        $this->closeOpenAssignments($companyId, $scopeType, $scopeId, Carbon::parse($validated['effective_from']));

        return AttendancePolicyAssignment::create([
            'company_id' => $companyId,
            'scope_type' => $scopeType->value,
            'scope_id' => $scopeType === AttendanceScopeType::COMPANY ? null : $scopeId,
            'policy_id' => $validated['policy_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $scopeIds
     * @return array{created: Collection<int, AttendancePolicyAssignment>, failed: list<array{scope_id: int|null, message: string}>}
     */
    public function assignBulk(int $companyId, array $data, array $scopeIds): array
    {
        $scopeType = AttendanceScopeType::from($data['scope_type']);
        $created = collect();
        $failed = [];

        if ($scopeType === AttendanceScopeType::COMPANY) {
            try {
                $created->push($this->assign($companyId, array_merge($data, ['scope_id' => null])));
            } catch (ValidationException $e) {
                $failed[] = [
                    'scope_id' => null,
                    'message' => (string) collect($e->errors())->flatten()->first(),
                ];
            }

            return ['created' => $created, 'failed' => $failed];
        }

        foreach ($scopeIds as $scopeId) {
            try {
                $created->push($this->assign($companyId, array_merge($data, ['scope_id' => (int) $scopeId])));
            } catch (ValidationException $e) {
                $failed[] = [
                    'scope_id' => (int) $scopeId,
                    'message' => (string) collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }

    public function delete(int $companyId, int $assignmentId): void
    {
        AttendancePolicyAssignment::where('company_id', $companyId)->findOrFail($assignmentId)->delete();
    }

    /**
     * @return array<string, ?array{policy_name: string, mode: string, source: string}>
     */
    public function inheritanceChainForEmployee(Employee $employee, ?Carbon $asOf = null): array
    {
        return app(AttendancePolicyResolver::class)->policyInheritanceChain($employee, $asOf);
    }

    private function assertScopeBelongsToCompany(int $companyId, AttendanceScopeType $scopeType, ?int $scopeId): void
    {
        if ($scopeType === AttendanceScopeType::COMPANY) {
            return;
        }

        if (! $scopeId) {
            throw ValidationException::withMessages(['scope_id' => 'Scope ID is required for this scope type.']);
        }

        $valid = match ($scopeType) {
            AttendanceScopeType::LOCATION => Location::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            AttendanceScopeType::DEPARTMENT => Department::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            AttendanceScopeType::DESIGNATION => Designation::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            AttendanceScopeType::EMPLOYEE => Employee::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['scope_id' => 'The selected scope does not belong to this company.']);
        }
    }

    private function assertNoOverlap(
        int $companyId,
        AttendanceScopeType $scopeType,
        ?int $scopeId,
        string $effectiveFrom,
        ?string $effectiveTo,
    ): void {
        $from = Carbon::parse($effectiveFrom);
        $to = $effectiveTo ? Carbon::parse($effectiveTo) : null;

        $overlap = AttendancePolicyAssignment::where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === AttendanceScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->where(function ($query) use ($from, $to) {
                $query->where(function ($q) use ($from) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from);
                });
                if ($to) {
                    $query->where('effective_from', '<=', $to);
                }
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'An overlapping assignment already exists for this scope.',
            ]);
        }
    }

    private function closeOpenAssignments(
        int $companyId,
        AttendanceScopeType $scopeType,
        ?int $scopeId,
        Carbon $newEffectiveFrom,
    ): void {
        AttendancePolicyAssignment::where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === AttendanceScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->whereNull('effective_to')
            ->where('effective_from', '<', $newEffectiveFrom)
            ->update(['effective_to' => $newEffectiveFrom->copy()->subDay()->toDateString()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'scope_type' => 'required|in:company,location,department,designation,employee',
            'scope_id' => 'nullable|integer',
            'policy_id' => 'required|exists:attendance_policies,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
