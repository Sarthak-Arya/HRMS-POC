<div x-data="{ isLoading: true }" x-init="() => { setTimeout(() => isLoading = false, 500) }">
    <div x-show="isLoading" class="loading-overlay">
        <!-- Loading spinner or message here -->
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div x-show="!isLoading" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <main class="main-content">
            <div x-data="{ open: false }" class="container-fluid py-4">
                <form wire:submit.prevent="validateForm" action="#" method="POST" role="form text-left">
                    @if($alertMessage)
                        <div class="alert alert-{{ $alertType === 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                            {{ $alertMessage }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <h5 class="mt-3">Company Details</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="company-name" class="form-control-label">Company Name</label>
                                <div>
                                    <input wire:model="companyName" class="form-control" type="text" placeholder="Company Name" id="company-name">
                                </div>
                                @error('companyName') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gst-number" class="form-control-label">GST Number</label>
                                <div>
                                    <input wire:model="gstNumber" class="form-control" type="text" placeholder="GST Number" id="gst-number">
                                </div>
                                @error('gstNumber') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="address" class="form-control-label">Address</label>
                                <div>
                                    <input wire:model="address" class="form-control" type="text" placeholder="Address" id="address">
                                </div>
                                @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="zip-code" class="form-control-label">Zip Code</label>
                                <div>
                                    <input wire:model="zipCode" class="form-control" type="text" placeholder="Zip Code" id="zip-code">
                                </div>
                                @error('zipCode') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state" class="form-control-label">State</label>
                                <div>
                                    <input wire:model="state" class="form-control" type="text" placeholder="State" id="state">
                                </div>
                                @error('state') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country" class="form-control-label">Country</label>
                                <div>
                                    <input wire:model="country" class="form-control" type="text" placeholder="Country" id="country">
                                </div>
                                @error('country') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div x-data="{ isVisible: @entangle('is_esi') }">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="showEsiDetails" x-model="isVisible" wire:model="is_esi">
                            <label class="form-check-label" for="showEsiDetails">
                                Show ESI Details
                            </label>
                        </div>
                        <div x-show="isVisible">
                            <h5 class="mt-3">ESI Details</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="esi-code" class="form-control-label">ESI Code</label>
                                        <div>
                                            <input wire:model="esiCode" class="form-control" type="text" placeholder="ESI Code" id="esi-code">
                                        </div>
                                        @error('esiCode') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="esi-contribution" class="form-control-label">ESI Contribution</label>
                                        <div>
                                            <input wire:model="esiContribution" class="form-control" type="number" step="0.01"
                                                placeholder="ESI Contribution" id="esi-contribution">
                                        </div>
                                        @error('esiContribution') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="esi-coverage-end-date" class="form-control-label">ESI Coverage End
                                            Date</label>
                                        <div>
                                            <input wire:model="esiCoverageEndDate" class='form-control' type='date' placeholder="ESI Coverage End Date"
                                                id='esi-coverage-end-date'>
                                        </div>
                                        @error('esiCoverageEndDate') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="esi-coverage-start-date" class="form-control-label">ESI Coverage Start
                                            Date</label>
                                        <div>
                                            <input wire:model="esiCoverageStartDate" class='form-control' type='date' placeholder="ESI Coverage Start Date"
                                                id='esi-coverage-start-date'>
                                        </div>
                                        @error('esiCoverageStartDate') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ isVisible: @entangle('is_pf') }">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="showPfDetails" x-model="isVisible" wire:model="is_pf">
                            <label class="form-check-label" for="showPfDetails">
                                Show PF Details
                            </label>
                        </div>
                        <div x-show="isVisible">
                            <h5 class="mt-3">PF Details</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="pf-code" class="form-control-label">PF Code</label>
                                        <div>
                                            <input wire:model="pfCode" class='form-control' type='text' placeholder="PF Code" id='pf-code'>
                                        </div>
                                        @error('pfCode') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="pf-coverage-start-date" class="form-control-label">PF Coverage Start
                                            Date</label>
                                        <div>
                                            <input wire:model="pfCoverageStartDate" class='form-control' type='date' placeholder="PF Coverage Start Date"
                                                id='pf-coverage-start-date'>
                                        </div>
                                        @error('pfCoverageStartDate') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="pf-coverage-end-date" class="form-control-label">PF Coverage End
                                            Date</label>
                                        <div>
                                            <input wire:model="pfCoverageEndDate" class='form-control' type='date' placeholder="PF Coverage End Date"
                                                id='pf-coverage-end-date'>
                                        </div>
                                        @error('pfCoverageEndDate') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="pf-contribution" class="form-control-label">PF Contribution</label>
                                        <div>
                                            <input wire:model="pfContribution" class="form-control" type="number" step="0.01"
                                                placeholder="PF Contribution" id="pf-contribution">
                                        </div>
                                        @error('pfContribution') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-3">Director Details</h5>
                    <hr>

                    <div x-data="{ directorCount: 0, showForm: false, currentDirector: {} }">
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" @click="showForm = true; currentDirector = {}">
                                Add Director
                            </button>
                        </div>

                        <livewire:director-list />


                        <!-- Popup Form -->
                        <div x-show="showForm" class="position-fixed top-0 start-0 w-100 h-100"
                            style="background-color: rgba(0, 0, 0, 0.5);" style="z-index: 50; ">
                            <div class=" position-absolute top-50 start-50 translate-middle bg-white p-4 rounded shadow-lg w-100"
                                style="max-width: 500px;">
                                <h3 class="fs-4 fw-medium mb-4">Add Director</h3>
                                <div>
                                    <div class="mb-3">
                                        <label for="directorName" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="directorName"
                                            x-model="currentDirector.name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="directorEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="directorEmail"
                                            x-model="currentDirector.email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="directorPhone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="directorPhone"
                                            x-model="currentDirector.phone" required>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2"
                                            @click="showForm = false">Cancel</button>
                                        <button type="button" class="btn btn-primary" @click="showForm = false">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="">Services Opted for</label>
                                <livewire:searchable-dropdown />
                            </div>
	                            <div class="d-flex justify-content-end gap-3">
	                                <div class="d-flex align-items-center gap-2 mt-4 mb-4">
	                                    <input type="file" wire:model="file" class="form-control" style="max-width: 260px;">
	                                    <button type="button" class="btn btn-primary" wire:click="import">Import finalise</button>
	                                </div>
	                                <button type="button" wire:click="validateForm" class="btn bg-gradient-dark btn-md mt-4 mb-4">
	                                    {{ 'Save Changes' }}
	                                </button>
	                            </div>
	                        </div>
	                    </div>
                    @if($showConfirmPopup)

                        <div>
                            <div class="modal" style="display: block;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Are You Sure?</h5>
                                            <button wire:click="$set('showConfirmPopup', false)" type="button" class="btn-close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to save these changes?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" wire:click="$set('showConfirmPopup', false)" class="btn btn-secondary">Discard</button>
                                            <button type="button" wire:click="save" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-backdrop fade show"></div>
                        </div>
                    @endif
                </form>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('directorForm', () => ({
            showForm: false,
            currentDirector: {},
        }))
    })
</script>

<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000000;
    }
</style>
