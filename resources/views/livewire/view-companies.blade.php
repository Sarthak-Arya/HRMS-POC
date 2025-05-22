<main class="main-content">
    <div class="d-flex justify-content-center">
        <form class="" role="search">
            <input wire:keyup="searchCompanies" wire:model.live="searchString" class="form-control me-2" type="search"
                placeholder="Search" aria-label="Search">
        </form>
    </div>
    <div wire:model="searchedCompanies" class="container p-4">
        <div class="row g-4">
        @foreach ($searchedCompanies as $company)
            <div class="col-lg-4 col-md-6 col-sm-12 d-flex align-items-stretch">
                <livewire:company-list :company :key="$company->id" :name="$company->company_name" :company_id="$company->company_id" :address="$company->company_address" :company_id_num="$company->id">
            </div>
            @if ($loop->index + 1 % 3 == 0)
            </div> <div class="row g-4">
            @endif
        @endforeach
        {{-- {{ $searchedCompanies->links() }} --}}

</main>
