<main class="main-content ui-page">
    <div wire:loading class="position-fixed top-0 start-0 w-100 h-100 ui-page-loading" style="z-index: 1055;">
        <div class="position-absolute top-50 start-50 translate-middle">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4" x-data="{ showModal: false, showImportModal: false }">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="ui-alert-banner mb-4" role="alert">
                <span class="ui-alert-banner-icon material-symbols-outlined">error</span>
                <div>
                    <p class="ui-alert-banner-title mb-1">Please fix the following</p>
                    <ul class="ui-alert-banner-body mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">{{ $employeeId ? 'Edit Employee' : 'Add Employee' }}</h1>
                <p class="ui-page-subtitle">
                    {{ $pageCompanyName }}<span class="ui-page-subtitle-sep">|</span>
                    {{ $employeeId ? 'Update employee profile and statutory details.' : 'Create a new employee record or import from Excel.' }}
                </p>
            </div>
            <div class="ui-page-actions">
                <a href="{{ route('download.template') }}" class="ui-btn-secondary">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">download</span>
                    Import Template
                </a>
                <button type="button" class="ui-btn-secondary" @click="showModal = true">Export</button>
                <button type="button" class="ui-btn-secondary" @click="showImportModal = true">Import</button>
                @if ($employeeId)
                    <a href="{{ route('employee-compensation', ['company_id' => $companyId, 'employee_id' => $employeeId]) }}" class="ui-btn-secondary">
                        Compensation
                    </a>
                    <a href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employeeId]) }}" class="ui-btn-secondary">
                        View Profile
                    </a>
                @endif
            </div>
        </section>

        {{-- Export modal --}}
        <div x-show="showModal" x-cloak class="ui-modal-backdrop" @keydown.escape.window="showModal = false" @click.self="showModal = false">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">Export Employee Data</h2>
                        <button type="button" class="ui-icon-btn" @click="showModal = false" aria-label="Close">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
                        <div class="mb-3">
                            <label for="export-department" class="form-label">Department</label>
                            <select wire:model="selectedDepartment" class="form-control form-select" id="export-department">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn-secondary" @click="showModal = false">Cancel</button>
                        <button type="button" wire:click="exportToExcel" class="ui-btn-primary">Export to Excel</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import modal --}}
        <div x-show="showImportModal" x-cloak class="ui-modal-backdrop" @keydown.escape.window="showImportModal = false" @click.self="showImportModal = false">
            <div class="ui-modal-dialog">
                <div class="ui-modal-content">
                    <div class="ui-modal-header">
                        <h2 class="ui-modal-title">Import Employee Data</h2>
                        <button type="button" class="ui-icon-btn" @click="showImportModal = false" aria-label="Close">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="ui-modal-body ui-form-body">
                        @if($importMessage)
                            <div class="ui-readiness ui-readiness--ok mb-3">
                                @php
                                    $lines = explode("\n", $importMessage);
                                    $summary = array_shift($lines);
                                    $customErrors = array_filter($lines);
                                @endphp
                                <strong>{{ $summary }}</strong>
                                @if(!empty($customErrors))
                                    <ul class="mb-0 mt-2 ps-3">
                                        @foreach($customErrors as $error)
                                            <li>{{ trim($error) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @if($importError)
                            <div class="ui-readiness ui-readiness--warn mb-3">
                                @foreach(explode("\n", $importError) as $line)
                                    @if(trim($line))
                                        <p class="mb-1">{{ trim($line) }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <form wire:submit.prevent="importEmployees">
                            <div class="mb-3">
                                <label class="form-label">Excel File</label>
                                <input type="file" wire:model="excelFile" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <p class="ui-grid-help px-0 mb-0 mt-2">
                                    Supported: .xlsx, .xls, .csv. Missing fields are auto-filled; department, designation, and location are created if needed.
                                </p>
                            </div>
                            <div class="ui-modal-footer px-0 pb-0 border-0 bg-transparent">
                                <button type="button" class="ui-btn-secondary" @click="showImportModal = false">Cancel</button>
                                <button type="submit" class="ui-btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Import Data</span>
                                    <span wire:loading>Importing...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save" x-data="{ scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); } }" @submit="scrollToTop()">
            <section class="ui-card mb-4">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Personal Details</h2>
                        <p class="ui-card-desc mb-0">Identity and demographic information.</p>
                    </div>
                </div>
                <div class="ui-form-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="first-name" class="form-label">First Name</label>
                            <input wire:model="firstName" class="form-control" type="text" placeholder="First Name" id="first-name">
                            @error('firstName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="middle-name" class="form-label">Middle Name</label>
                            <input wire:model="middleName" class="form-control" type="text" placeholder="Middle Name" id="middle-name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last-name" class="form-label">Last Name</label>
                            <input wire:model="lastName" class="form-control" type="text" placeholder="Last Name" id="last-name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="father-name" class="form-label">Father Name</label>
                            <input wire:model="fatherName" class="form-control" type="text" placeholder="Father Name" id="father-name">
                            @error('fatherName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select wire:model="gender" class="form-control form-select" id="gender">
                                <option value="" disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input wire:model="dob" class="form-control" type="date" id="dob">
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-card mb-4">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Professional Details</h2>
                        <p class="ui-card-desc mb-0">Role, org structure, and employment dates.</p>
                    </div>
                </div>
                <div class="ui-form-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="company-name" class="form-label">Company Name</label>
                            <input wire:model="companyName" class="form-control" type="text" placeholder="Company Name" id="company-name">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input wire:model="designation" class="form-control" type="text" placeholder="Designation" id="designation" list="designation-options" autocomplete="off">
                            <datalist id="designation-options">
                                @foreach ($designationOptions as $option)
                                    <option value="{{ $option }}"></option>
                                @endforeach
                            </datalist>
                            @error('designation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input wire:model="department" class="form-control" type="text" placeholder="Department" id="department" list="department-options" autocomplete="off">
                            <datalist id="department-options">
                                @foreach ($departmentOptions as $option)
                                    <option value="{{ $option }}"></option>
                                @endforeach
                            </datalist>
                            @error('department') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input wire:model="location" class="form-control" type="text" placeholder="Location" id="location" list="location-options" autocomplete="off">
                            <datalist id="location-options">
                                @foreach ($locationOptions as $option)
                                    <option value="{{ $option }}"></option>
                                @endforeach
                            </datalist>
                            @error('location') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="employee-company-code" class="form-label">Employee Company Code</label>
                            <input wire:model="employeeCompanyCode" class="form-control" type="text" placeholder="Employee Company Code" id="employee-company-code">
                            @error('employeeCompanyCode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="joining-date" class="form-label">Joining Date</label>
                            <input wire:model="joiningDate" class="form-control" type="date" id="joining-date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="leaving-date" class="form-label">Leaving Date</label>
                            <input wire:model="leavingDate" class="form-control" type="date" id="leaving-date">
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-card mb-4">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">ESI / PF Details</h2>
                        <p class="ui-card-desc mb-0">Statutory registration numbers.</p>
                    </div>
                </div>
                <div class="ui-form-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="esi-no" class="form-label">ESI Number</label>
                            <input wire:model="esiNo" class="form-control" type="text" placeholder="ESI Number" id="esi-no">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pf-no" class="form-label">PF Number</label>
                            <input wire:model="pfNo" class="form-control" type="text" placeholder="PF Number" id="pf-no">
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-card mb-4">
                <div class="ui-card-header">
                    <div>
                        <h2 class="ui-card-title">Bank Details</h2>
                        <p class="ui-card-desc mb-0">Salary disbursement account information.</p>
                    </div>
                </div>
                <div class="ui-form-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="account-no" class="form-label">Account Number</label>
                            <input wire:model="accountNo" class="form-control" type="text" placeholder="Account Number" id="account-no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bank-name" class="form-label">Bank Name</label>
                            <input wire:model="bankName" class="form-control" type="text" placeholder="Bank Name" id="bank-name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="ifsc-code" class="form-label">IFSC Code</label>
                            <input wire:model="ifscCode" class="form-control" type="text" placeholder="IFSC Code" id="ifsc-code">
                        </div>
                    </div>
                </div>
            </section>

            <div class="d-flex justify-content-end">
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="ui-btn-primary">
                    <span wire:loading.remove wire:target="save">Save Employee</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</main>

<style>
    [x-cloak] { display: none !important; }

    .ui-page-loading {
        background: rgba(250, 248, 255, 0.72);
    }

    html[data-theme="dark"] .ui-page-loading,
    body.theme-dark .ui-page-loading {
        background: rgba(19, 19, 20, 0.72);
    }
</style>
