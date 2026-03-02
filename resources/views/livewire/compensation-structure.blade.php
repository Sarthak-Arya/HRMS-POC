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
                                <h5 class="card-title">{{ $structure->name }}</h5>
                                <div class="mb-2">
                                    <span class="badge bg-secondary">Applies to: {{ ucfirst($structure->applies_to_type) }} {{ $structure->applies_to_id }}</span>
                                </div>
                                <div class="mt-3">
                                    @foreach($structure->components as $comp)
                                        <span class="badge bg-gradient-dark me-2 mb-2">
                                            {{ $comp->name }}: {{ $comp->pivot->amount_type }} {{ $comp->pivot->value }}
                                        </span>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-primary me-2" wire:click="editCompensation({{ $structure->id }})">
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
                                <div class="mb-3">
                                    <label for="structureName" class="form-label">Structure Name</label>
                                    <input type="text" wire:model="name" class="form-control" id="structureName" placeholder="Enter structure name">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="appliesToType" class="form-label">Applies To Type</label>
                                        <select wire:model="applies_to_type" class="form-control" id="appliesToType">
                                            <option value="company">Company</option>
                                            <option value="department">Department</option>
                                            <option value="location">Location</option>
                                            <option value="employee">Employee</option>
                                        </select>
                                        @error('applies_to_type') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="appliesToId" class="form-label">Applies To ID</label>
                                        <input type="text" wire:model="applies_to_id" class="form-control" id="appliesToId" placeholder="Enter ID (e.g. department id)">
                                        @error('applies_to_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h6>Compensation Components</h6>
                                    @foreach($compensations as $index => $compensation)
                                        <div class="row mb-3 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label">Component</label>
                                                <select wire:model="compensations.{{ $index }}.component_id" class="form-control">
                                                    <option value="">Select Component</option>
                                                    @foreach($availableComponents as $component)
                                                        <option value="{{ $component->id }}">{{ $component->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("compensations.{$index}.component_id") <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Amount Type</label>
                                                <select wire:model="compensations.{{ $index }}.amount_type" class="form-control">
                                                    <option value="fixed">Fixed</option>
                                                    <option value="percentage">Percentage</option>
                                                </select>
                                                @error("compensations.{$index}.amount_type") <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Value</label>
                                                <input type="number" wire:model="compensations.{{ $index }}.value" class="form-control" placeholder="Value">
                                                @error("compensations.{$index}.value") <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-1">
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