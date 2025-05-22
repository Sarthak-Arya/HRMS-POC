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

    <div class="container-fluid py-4">
        @if($structures->isEmpty())
            <div class="text-center py-5">
                <h4 class="mb-4">No Compensation Structures Found</h4>
                <p class="text-muted mb-4">Start by creating a new compensation structure for your employees.</p>
                <button class="btn bg-gradient-dark" wire:click="$set('showModal', true)">Add a New Compensation Structure</button>
            </div>
        @else
            <div class="row">
                @foreach($structures as $structure)
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{{ $structure->designation->designation_name }} - {{ $structure->department->department_name }}</h5>
                                <div class="mt-3">
                                    @foreach(json_decode($structure->structure) as $comp)
                                        <span class="badge bg-gradient-dark me-2 mb-2">
                                            {{$comp->type}}:{{$comp->percentage}}% 
                                        </span>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-
                                     btn-primary me-2" wire:click="editCompensation({{ $structure->id }})">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" wire:click="deleteCompensation({{ $structure->id }})">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn bg-gradient-dark" wire:click="$set('showModal', true)">Add New Structure</button>
            </div>
        @endif

        <!-- Modal -->
        <div x-data="{ showModal: @entangle('showModal') }" @keydown.escape.window="showModal = false">
            <div class="modal" :class="{ 'show d-block': showModal }" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editMode ? 'Edit' : 'Add' }} Compensation Structure</h5>
                            <button type="button" class="btn-close" @click="showModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <form wire:submit.prevent="save">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="designation" class="form-control-label">Designation</label>
                                            <select wire:model="designation_id" class="form-control" id="designation">
                                                <option value="">Select Designation</option>
                                                @foreach($designations as $designation)
                                                    <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('designation_id') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="department" class="form-control-label">Department</label>
                                            <select wire:model="department_id" class="form-control" id="department">
                                                <option value="">Select Department</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('department_id') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h6>Compensation Details</h6>
                                    @foreach($compensations as $index => $compensation)
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <select wire:model="compensations.{{ $index }}.type" class="form-control">
                                                    <option value="">Select Type</option>
                                                    @foreach($availableTypes as $type)
                                                        <option value="{{ $type }}">{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                                @error("compensations.{$index}.type") <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" wire:model="compensations.{{ $index }}.percentage" class="form-control" placeholder="Percentage">
                                                @error("compensations.{$index}.percentage") <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger" wire:click="removeCompensationRow({{ $index }})">X</button>
                                            </div>
                                        </div>
                                    @endforeach
                                    @error('compensations') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary" wire:click="addCompensationRow">Add Row</button>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" @click="showModal = false">Close</button>
                                    <button type="submit" class="btn bg-gradient-dark">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Backdrop -->
            <div class="modal-backdrop fade" :class="{ 'show': showModal }" x-show="showModal"></div>
        </div>
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
        display: none;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('modal', () => ({
            showModal: false,
            init() {
                this.$watch('showModal', value => {
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