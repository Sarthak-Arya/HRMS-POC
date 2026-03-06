<main class="main-content">
    <div wire:loading class="position-fixed top-0 start-0 w-100 h-100"
        style="background: rgba(255,255,255,0.6); z-index: 1055;">
        <div class="position-absolute top-50 start-50 translate-middle">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

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
        <div class="alert alert-danger" role="alert">
            <div class="fw-bold mb-1">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="container-fluid py-4" x-data="{ showModal: false, showImportModal: false}">
        <!-- Action buttons -->
        <div class="d-flex justify-content-end">
            <a href="{{ route('download.template') }}" class="btn bg-gradient-info btn-md me-2">
                <i class="fas fa-download me-2"></i>Import Excel Template
            </a>
            <button class="btn bg-gradient-dark btn-md me-2" @click="showModal = true">{{ 'Export Details' }}</button>
            <button class="btn bg-gradient-dark btn-md" @click="showImportModal = true">{{ 'Import Details' }}</button>
        </div>
        <!-- Export Modal -->
        <div  @keydown.escape.window="showModal = false">
            <!-- Modal -->
            <div class="modal" :class="{ 'show d-block': showModal }" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Export Employee Data</h5>
                            <button type="button" class="btn-close" @click="showModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="export-department" class="form-control-label">Select Department</label>
                                <select wire:model="selectedDepartment" class="form-control" id="export-department">
                                    <option value="">All Departments</option>
                                    @if (count($departments) != 0)
                                        @foreach ($departments as $department)  
                                        <option value="{{$department->id}}">{{$department->department_name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showModal = false">Close</button>
                            <button type="button" wire:click="exportToExcel" class="btn bg-gradient-dark">Export to Excel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Backdrop -->
            <div class="modal-backdrop fade" :class="{ 'show': showModal }" x-show="showModal"></div>

        </div>

        <!-- Import Modal -->
        <div @keydown.escape.window="showImportModal = false">
            <!-- Modal -->
            <div class="modal" :class="{ 'show d-block': showImportModal }" tabindex="-1" x-show="showImportModal" x-cloak>
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Employee Data</h5>
                            <button type="button" class="btn-close" @click="showImportModal = false"></button>
                        </div>
                        <div class="modal-body">
                            @if($importMessage)
                                <div class="alert alert-success">
                                    <h6 class="alert-heading">Import Results</h6>
                                    @php
                                        $lines = explode("\n", $importMessage);
                                        $summary = array_shift($lines); // Get the first line (summary)
                                        $customErrors = array_filter($lines); // Get remaining lines (errors)
                                    @endphp
                                    <p class="mb-2">{{ $summary }}</p>
                                    @if(!empty($customErrors))
                                        <hr>
                                        <h6 class="mb-2">Errors Found:</h6>
                                        <ul class="mb-0">
                                            @foreach($customErrors as $error)
                                                <li class="text-danger">{{ trim($error) }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                            @if($importError)
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading">Import Failed</h6>
                                    @php
                                        $errorLines = explode("\n", $importError);
                                    @endphp
                                    @foreach($errorLines as $line)
                                        @if(trim($line))
                                            <p class="mb-1">{{ trim($line) }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <form wire:submit.prevent="importEmployees">
                                <div class="form-group mb-3">
                                    <label class="form-control-label">Excel File</label>
                                    <input type="file" wire:model="excelFile" class="form-control" accept=".xlsx,.xls,.csv" required>
                                    <small class="text-muted">
                                        Supported formats: .xlsx, .xls, .csv. Missing fields are auto-filled where possible (department/designation/location will be created if missing).
                                    </small>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" @click="showImportModal = false">Cancel</button>
                                    <button type="submit" class="btn bg-gradient-dark" wire:loading.attr="disabled">
                                        <span wire:loading.remove>Import Data</span>
                                        <span wire:loading>Importing...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Backdrop -->
            <div class="modal-backdrop fade" :class="{ 'show': showImportModal }" x-show="showImportModal" x-cloak></div>
        </div>

        <form wire:submit.prevent="save" x-data="{ scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); } }" @submit="scrollToTop()">
            @if (isset($employeeId) && $employeeId)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-sm text-muted">Editing employee</div>
                    <a href="{{ route('employee-details', ['company_id' => $companyId, 'employee_id' => $employeeId]) }}" class="btn btn-sm btn-outline-dark">
                        Back to view
                    </a>
                </div>
            @endif
            <h5>Personal Details</h5>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="first-name" class="form-control-label">First Name</label>
                        {{-- <div class="@error('user.name')border border-danger rounded-3 @enderror">
                    <input wire:model="user.name" class="form-control" type="text" placeholder="Name"
                        id="user-name">
                </div> --}}
                        {{-- @error('user.name') <div class="text-danger">{{ $message }}</div> @enderror --}}

                        <div>
                            <input wire:model="firstName" class="form-control" type="text" placeholder="First Name" id="first-name">
                        </div>
                        <div>
                            @error('firstName') <span class="error">{{ $message }}</span> @enderror
                        </div>

                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="middle-name" class="form-control-label">Middle Name</label>
                        <div>
                            <input wire:model="middleName" class="form-control" type="text" placeholder="Middle Name" id="middle-name">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="last-name" class="form-control-label">Last Name</label>
                        <div>
                            <input wire:model="lastName" class="form-control" type="text" placeholder="Last Name" id="last-name">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="father-name" class="form-control-label">Father Name</label>
                        <div>
                            <input wire:model="fatherName" class="form-control" type="text" placeholder="Father Name" id="father-name">
                        </div>
                        <div>
                            @error('fatherName') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="gender" class="form-control-label">Gender</label>
                        <div>
                            <select wire:model="gender" class="form-control" id="gender">
                                <option value="" disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            @error('gender') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="dob" class="form-control-label">Date of Birth</label>
                        <div>
                            <input wire:model="dob" class="form-control" type="date" id="dob">
                        </div>
                    </div>
                </div>
            </div>
            <h5 class="mt-3">Professional Details</h5>
            <hr>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="company-name" class="form-control-label">Company Name</label>
                        <div>
                            <input wire:model="companyName" class="form-control" type="text" placeholder="Company Name" id="company-name">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="designation" class="form-control-label">Designation</label>
                        <div>
                            <input
                                wire:model="designation"
                                class="form-control"
                                type="text"
                                placeholder="Designation"
                                id="designation"
                                list="designation-options"
                                autocomplete="off"
                            >
                            <datalist id="designation-options">
                                @if (isset($designationOptions) && count($designationOptions) != 0)
                                    @foreach ($designationOptions as $option)
                                        <option value="{{ $option }}"></option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div>
                            @error('designation') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="department" class="form-control-label">Department</label>
                        <div>
                            <input
                                wire:model="department"
                                class="form-control"
                                type="text"
                                placeholder="Department"
                                id="department"
                                list="department-options"
                                autocomplete="off"
                            >
                            <datalist id="department-options">
                                @if (isset($departmentOptions) && count($departmentOptions) != 0)
                                    @foreach ($departmentOptions as $option)
                                        <option value="{{ $option }}"></option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div>
                            @error('department') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="location" class="form-control-label">Location</label>
                        <div>
                            <input
                                wire:model="location"
                                class="form-control"
                                type="text"
                                placeholder="Location"
                                id="location"
                                list="location-options"
                                autocomplete="off"
                            >
                            <datalist id="location-options">
                                @if (isset($locationOptions) && count($locationOptions) != 0)
                                    @foreach ($locationOptions as $option)
                                        <option value="{{ $option }}"></option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div>
                            @error('location') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="employee-company-code" class="form-control-label">Employee Company Code</label>
                        <div>
                            <input wire:model="employeeCompanyCode" class="form-control" type="text" placeholder="Employee Company Code" id="employee-company-code">
                        </div>
                        <div>
                            @error('employeeCompanyCode') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="joining-date" class="form-control-label">Joining Date</label>
                        <div>
                            <input wire:model="joiningDate" class="form-control" type="date" id="joining-date">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="leaving-date" class="form-control-label">Leaving Date</label>
                        <div>
                            <input wire:model="leavingDate" class="form-control" type="date" id="leaving-date">
                        </div>
                    </div>
                </div>
            </div>
            <h5 class="mt-3">ESI/PF Details</h5>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="esi-no" class="form-control-label">ESI Number</label>
                        <div>
                            <input wire:model="esiNo" class="form-control" type="text" placeholder="ESI Number" id="esi-no">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pf-no" class="form-control-label">PF Number</label>
                        <div>
                            <input wire:model="pfNo" class="form-control" type="text" placeholder="PF Number" id="pf-no">
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-3">Bank Details</h5>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="account-no" class="form-control-label">Account Number</label>
                        <div>
                            <input wire:model="accountNo" class="form-control" type="text" placeholder="Account Number" id="account-no">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank-name" class="form-control-label">Bank Name</label>
                        <div>
                            <input wire:model="bankName" class="form-control" type="text" placeholder="Bank Name" id="bank-name">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="ifsc-code" class="form-control-label">IFSC Code</label>
                        <div>
                            <input wire:model="ifscCode" class="form-control" type="text" placeholder="IFSC Code" id="ifsc-code">
                        </div>
                    </div>
                </div>
            </div>
            

            <div class="d-flex justify-content-end">
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                    class="btn bg-gradient-dark btn-md mt-4 mb-4">
                    {{ 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</main>

<style>
    body.modal-open {
        overflow: hidden;
    }

    .modal-backdrop.show {
        opacity: 0.5;
    }

    .modal {
        background-color: rgba(0, 0, 0, 0.5);
    }

    [x-cloak] {
        display: none !important;
    }

    .modal.show {
        display: block !important;
    }

    .modal-backdrop.show {
        display: block !important;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('modal', () => ({
            showModal: false,
            showImportModal: false,
            init() {
                this.$watch('showModal', value => {
                    if (value) {
                        document.body.classList.add('modal-open');
                    } else {
                        document.body.classList.remove('modal-open');
                    }
                });
                this.$watch('showImportModal', value => {
                    if (value) {
                        document.body.classList.add('modal-open');
                    } else {
                        document.body.classList.remove('modal-open');
                    }
                });
            }
        }));
    });
</script>
