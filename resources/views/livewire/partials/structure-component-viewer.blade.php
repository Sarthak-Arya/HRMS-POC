@php
    $summary = $summary ?? null;
    $previewCtcProperty = $previewCtcProperty ?? 'assignmentPreviewCtc';
    $components = $components ?? collect();
    $rows = $rows ?? [];
@endphp

<div class="row">
    <div class="col-md-8">
        <h6 class="mt-2">Components</h6>
        <div class="table-responsive">
            <table class="table table-sm align-items-center mb-0">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Type</th>
                        <th>Calculation</th>
                        <th class="text-end">Value</th>
                        <th class="text-end">Monthly</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['lines'] ?? [] as $line)
                        <tr>
                            <td>{{ $line['name'] }}</td>
                            <td>
                                <span class="badge bg-gradient-{{ $line['type'] === 'EARNING' ? 'success' : 'danger' }}">{{ $line['type'] }}</span>
                            </td>
                            <td class="text-sm text-muted">{{ $line['calculation'] }}</td>
                            <td class="text-end text-sm">{{ $line['config_value'] }}</td>
                            <td class="text-end {{ $line['type'] === 'DEDUCTION' ? 'text-danger' : '' }}">
                                {{ $line['type'] === 'DEDUCTION' ? '-' : '' }}{{ number_format($line['amount'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-sm py-3">No components configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6>Monthly Breakdown</h6>
                <div class="mb-3">
                    <label class="form-label text-sm">Annual CTC</label>
                    <input type="number" wire:model="{{ $previewCtcProperty }}" class="form-control form-control-sm">
                </div>
                @if($summary && $summary['lines']->isNotEmpty())
                    <table class="table table-sm mb-0">
                        @foreach($summary['lines']->where('type', 'EARNING') as $line)
                            <tr>
                                <td class="text-sm">{{ $line['name'] }}</td>
                                <td class="text-end text-sm text-success">+ {{ number_format($line['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        @foreach($summary['lines']->where('type', 'DEDUCTION') as $line)
                            <tr>
                                <td class="text-sm">{{ $line['name'] }}</td>
                                <td class="text-end text-sm text-danger">- {{ number_format($line['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @include('livewire.partials.structure-preview-summary', ['summary' => $summary])
                @else
                    <p class="text-muted text-sm mb-0">No components to preview.</p>
                @endif
            </div>
        </div>
    </div>
</div>
