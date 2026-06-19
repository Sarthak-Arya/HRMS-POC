<div class="card h-100 overflow-hidden" :$companies :key="$company->id">
    <div class="card-body d-flex flex-column">
        <h5 class="card-title overflow-y-hidden">{{ $name }}</h5>
        <p class="card-text overflow-y-hidden flex-grow-1">{{ $address }}</p>
        <div class="d-flex flex-wrap gap-2 mt-auto">
            <button class="btn btn-primary btn-sm" wire:click="setCompanyId">View Company</button>
            <a href="#" class="btn btn-secondary btn-sm">Edit</a>
        </div>
    </div>
</div>
