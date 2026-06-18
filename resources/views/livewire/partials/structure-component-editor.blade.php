@php
    $rows = $rows ?? [];
    $rowsProperty = $rowsProperty ?? 'structureRows';
    $wireKeyPrefix = $wireKeyPrefix ?? 'structure-row';
    $addRowMethod = $addRowMethod ?? 'addStructureRow';
    $removeRowMethod = $removeRowMethod ?? 'removeStructureRow';
    $previewCtcProperty = $previewCtcProperty ?? 'previewAnnualCtc';
    $errorField = $errorField ?? 'components';
    $showPreview = $showPreview ?? true;
@endphp

<div class="row">
    <div class="{{ $showPreview ? 'col-md-8' : 'col-12' }}">
        <h6 class="mt-2">Components</h6>
        @foreach($rows as $index => $row)
            <div class="row align-items-end mb-2" wire:key="{{ $wireKeyPrefix }}-{{ $index }}">
                <div class="col-md-4">
                    <select wire:model="{{ $rowsProperty }}.{{ $index }}.component_id" class="form-control form-control-sm">
                        <option value="">Component</option>
                        @foreach($components->where('is_active', true) as $component)
                            <option value="{{ $component->id }}">{{ $component->component_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="{{ $rowsProperty }}.{{ $index }}.calculation_type" class="form-control form-control-sm">
                        <option value="">Default</option>
                        <option value="FIXED">Fixed</option>
                        <option value="PERCENT_BASIC">% Basic</option>
                        <option value="PERCENT_CTC">% CTC</option>
                        <option value="FORMULA">Formula</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" wire:model="{{ $rowsProperty }}.{{ $index }}.value" class="form-control form-control-sm" placeholder="Value">
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model="{{ $rowsProperty }}.{{ $index }}.is_mandatory">
                        <label class="form-check-label text-sm">Mandatory</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="{{ $removeRowMethod }}({{ $index }})">Remove</button>
                </div>
            </div>
        @endforeach
        <button type="button" class="btn btn-sm btn-outline-dark" wire:click="{{ $addRowMethod }}">Add Row</button>
        @error($errorField) <div class="text-danger text-sm">{{ $message }}</div> @enderror
    </div>
    @if($showPreview)
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6>Preview</h6>
                    <div class="mb-3">
                        <label class="form-label text-sm">Annual CTC</label>
                        <input type="number" wire:model="{{ $previewCtcProperty }}" class="form-control form-control-sm">
                    </div>
                    @if($preview && $preview->isNotEmpty())
                        <table class="table table-sm mb-0">
                            @foreach($preview as $line)
                                <tr>
                                    <td>{{ $line['name'] }}</td>
                                    <td class="text-end {{ ($line['type'] ?? '') === 'DEDUCTION' ? 'text-danger' : '' }}">
                                        {{ ($line['type'] ?? '') === 'DEDUCTION' ? '-' : '' }}{{ number_format($line['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                        @if(!empty($previewSummary))
                            @include('livewire.partials.structure-preview-summary', ['summary' => $previewSummary])
                        @endif
                    @else
                        <p class="text-muted text-sm mb-0">Add components to see preview.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
