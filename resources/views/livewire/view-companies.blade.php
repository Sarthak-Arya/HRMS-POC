<main class="main-content">
    <div class="d-flex justify-content-center py-3">
        <form class="w-100" style="max-width: 420px;" role="search">
            <input wire:keyup="searchCompanies" wire:model.live="searchString" class="form-control" type="search"
                placeholder="Search companies..." aria-label="Search">
        </form>
    </div>
    <div wire:model="searchedCompanies" class="container py-2 pb-5">
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
