<div class="card">
    <div class="card-header pb-0">
        <h6>Generate Salaries</h6>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="generateSalaries">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select class="form-control" id="department" wire:model="selectedDepartment">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <select class="form-control" id="designation" wire:model="selectedDesignation">
                            <option value="">All Designations</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ $designation->designation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fromDate">From Date</label>
                        <input type="date" class="form-control" id="fromDate" wire:model="fromDate">
                        @error('fromDate') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="toDate">To Date</label>
                        <input type="date" class="form-control" id="toDate" wire:model="toDate">
                        @error('toDate') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Available Date Ranges</label>
                        <select class="form-control" wire:model="dateRanges">
                            <option value="">Select a date range</option>
                            @foreach($dateRanges as $range)
                                <option value="{{ $range['value'] }}">{{ $range['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if($batchId)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%">
                                {{ $progress }}%
                            </div>
                        </div>
                        <p class="text-center mt-2">
                            Status: <span class="badge badge-{{ $status === 'completed' ? 'success' : ($status === 'failed' ? 'danger' : 'primary') }}">
                                {{ ucfirst($status) }}
                            </span>
                        </p>
                    </div>
                </div>
            @endif

            <div class="row mt-4">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Generate Salaries</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('dateRangeSelected', function (value) {
            if (value) {
                const [fromDate, toDate] = value.split('|');
                @this.set('fromDate', fromDate);
                @this.set('toDate', toDate);
            }
        });
    });
</script>
@endpush 