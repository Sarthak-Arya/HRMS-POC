<div class="card me-4  overflow-hidden"  :$companies :key="$company->id">
    <div class="card-body">
        <h5 class="card-title overflow-y-hidden">{{ $name }}</h5>
        <p class="card-text overflow-y-hidden" >{{ $address }}</p>
        <button class="btn btn-primary" wire:click="setCompanyId">View Company</button>
        <a href="#" class="btn btn-secondary">Edit</a>
    </div>
</div>
