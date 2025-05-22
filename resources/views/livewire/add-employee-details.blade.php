<main class="main-content">
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
    <div class="container-fluid py-4" x-data="{ showModal: false, showImportModal: false}">
        <!-- Export button -->
        <div class="d-flex justify-content-end">
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
                                    {{ $importMessage }}
                                </div>
                            @endif
                            @if($importError)
                                <div class="alert alert-danger">
                                    {{ $importError }}
                                </div>
                            @endif
                            <form wire:submit.prevent="importEmployees">
                                <div class="form-group mb-3">
                                    <label class="form-control-label">Department</label>
                                    <select wire:model="importDepartment" class="form-control" required>
                                        <option value="">Select Department</option>
                                        <option value="all">All Departments</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                        @endforeach
                                    </select>
                                    @if($importDepartment === 'all')
                                        <small class="text-muted">If 'All Departments' is selected, your Excel must include a Department column.</small>
                                    @endif
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-control-label">Excel File</label>
                                    <input type="file" wire:model="excelFile" class="form-control" accept=".xlsx,.xls" required>
                                    <small class="text-muted">Supported formats: .xlsx, .xls</small>
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
                            @error('form.firstName') <span class="error">{{ $message }}</span> @enderror
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
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="gender" class="form-control-label">Gender</label>
                        <div>
                            <select wire:model="gender" class="form-control" id="gender">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="company-name" class="form-control-label">Company Name</label>
                        <div>
                            <input wire:model="companyName" class="form-control" type="text" placeholder="Company Name" id="company-name">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="designation" class="form-control-label">Designation</label>
                        <div>
                            <input wire:model="designation" class="form-control" type="text" placeholder="Designation" id="designation">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="department" class="form-control-label">Department</label>
                        <div>
                            <select wire:model="department" class="form-control" id="department" data-live-search="true">
                                @if (count($departments) !=  0)
                                    @foreach ($departments as $department)  
                                    <option value="{{$department->id}}">{{$department->department_name}}</option>
                                    @endforeach
                                @else
                                    <option value="" default>None</option>
                                @endif
                            </select>
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
                <button type="submit" class="btn bg-gradient-dark btn-md mt-4 mb-4">{{ 'Save Changes' }}</button>
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
