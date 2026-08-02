<?php

namespace App\Services\Settings;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Services\Observability\DomainTelemetry;
use App\Support\Settings\CompanySettingsDefaults;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationStructureService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

    /**
     * @return Collection<int, Department>
     */
    public function listDepartments(int $companyId): Collection
    {
        return Department::query()
            ->where('company_id', $companyId)
            ->orderBy('department_name')
            ->get();
    }

    /**
     * @return Collection<int, Designation>
     */
    public function listDesignations(int $companyId): Collection
    {
        return Designation::query()
            ->where('company_id', $companyId)
            ->orderBy('designation_name')
            ->get();
    }

    /**
     * @return Collection<int, Location>
     */
    public function listLocations(int $companyId): Collection
    {
        return Location::query()
            ->where('company_id', $companyId)
            ->orderBy('location_name')
            ->get();
    }

    public function createDepartment(int $companyId, string $name): Department
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Department name is required.']);
        }

        $adapter = app(Adapters\OrganizationSettingsAdapter::class);
        if (! $adapter->read($companyId)['allowDuplicateDepartmentNames']) {
            $exists = Department::query()
                ->where('company_id', $companyId)
                ->where('department_name', $name)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['name' => 'A department with this name already exists.']);
            }
        }

        $department = Department::create([
            'company_id' => $companyId,
            'department_name' => $name,
        ]);

        $this->emitOrganizationChanged($companyId, 'department');

        return $department;
    }

    public function updateDepartment(int $companyId, int $departmentId, string $name): Department
    {
        $department = $this->findDepartment($companyId, $departmentId);
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Department name is required.']);
        }

        $department->update(['department_name' => $name]);

        $this->emitOrganizationChanged($companyId, 'department');

        return $department->refresh();
    }

    public function deleteDepartment(int $companyId, int $departmentId): void
    {
        $department = $this->findDepartment($companyId, $departmentId);

        if ($department->employees()->exists()) {
            throw ValidationException::withMessages([
                'department' => 'Cannot delete a department that has employees assigned.',
            ]);
        }

        $department->delete();

        $this->emitOrganizationChanged($companyId, 'department');
    }

    public function createDesignation(int $companyId, string $name): Designation
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Designation name is required.']);
        }

        $designation = Designation::create([
            'company_id' => $companyId,
            'designation_name' => $name,
        ]);

        $this->emitOrganizationChanged($companyId, 'designation');

        return $designation;
    }

    public function updateDesignation(int $companyId, int $designationId, string $name): Designation
    {
        $designation = $this->findDesignation($companyId, $designationId);
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Designation name is required.']);
        }

        $designation->update(['designation_name' => $name]);

        $this->emitOrganizationChanged($companyId, 'designation');

        return $designation->refresh();
    }

    public function deleteDesignation(int $companyId, int $designationId): void
    {
        $designation = $this->findDesignation($companyId, $designationId);

        if ($designation->employees()->exists()) {
            throw ValidationException::withMessages([
                'designation' => 'Cannot delete a designation that has employees assigned.',
            ]);
        }

        $designation->delete();

        $this->emitOrganizationChanged($companyId, 'designation');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createLocation(int $companyId, array $payload): Location
    {
        $name = trim((string) ($payload['location_name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['location_name' => 'Location name is required.']);
        }

        $location = Location::create([
            'company_id' => $companyId,
            'location_name' => $name,
            'location_code' => $payload['location_code'] ?? null,
            'location_address' => $payload['location_address'] ?? null,
            'location_city' => $payload['location_city'] ?? null,
            'location_state' => $payload['location_state'] ?? null,
            'location_pincode' => $payload['location_pincode'] ?? null,
            'location_country' => $payload['location_country'] ?? null,
            'location_phone' => $payload['location_phone'] ?? null,
            'location_email' => $payload['location_email'] ?? null,
        ]);

        $this->emitOrganizationChanged($companyId, 'location');

        return $location;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateLocation(int $companyId, int $locationId, array $payload): Location
    {
        $location = $this->findLocation($companyId, $locationId);
        $name = trim((string) ($payload['location_name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['location_name' => 'Location name is required.']);
        }

        $location->update([
            'location_name' => $name,
            'location_code' => $payload['location_code'] ?? null,
            'location_address' => $payload['location_address'] ?? null,
            'location_city' => $payload['location_city'] ?? null,
            'location_state' => $payload['location_state'] ?? null,
            'location_pincode' => $payload['location_pincode'] ?? null,
            'location_country' => $payload['location_country'] ?? null,
            'location_phone' => $payload['location_phone'] ?? null,
            'location_email' => $payload['location_email'] ?? null,
        ]);

        $this->emitOrganizationChanged($companyId, 'location');

        return $location->refresh();
    }

    public function deleteLocation(int $companyId, int $locationId): void
    {
        $location = $this->findLocation($companyId, $locationId);

        if ($location->employees()->exists()) {
            throw ValidationException::withMessages([
                'location' => 'Cannot delete a location that has employees assigned.',
            ]);
        }

        $location->delete();

        $this->emitOrganizationChanged($companyId, 'location');
    }

    private function emitOrganizationChanged(int $companyId, string $section): void
    {
        $this->telemetry->emit('settings.organization.changed', 'audit', 'success', [
            'company.id' => $companyId,
            'section' => $section,
        ]);
    }

    private function findDepartment(int $companyId, int $departmentId): Department
    {
        return Department::query()
            ->where('company_id', $companyId)
            ->where('id', $departmentId)
            ->firstOrFail();
    }

    private function findDesignation(int $companyId, int $designationId): Designation
    {
        return Designation::query()
            ->where('company_id', $companyId)
            ->where('id', $designationId)
            ->firstOrFail();
    }

    private function findLocation(int $companyId, int $locationId): Location
    {
        return Location::query()
            ->where('company_id', $companyId)
            ->where('id', $locationId)
            ->firstOrFail();
    }
}
