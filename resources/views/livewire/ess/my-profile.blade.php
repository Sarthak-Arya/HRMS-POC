<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">My profile</h1>
                <p class="ui-page-subtitle">Update contact details HR uses for you. Job fields stay managed by HR.</p>
            </div>
        </section>

        @if($saved)
            <div class="ui-alert-banner mb-3" role="status">
                <span class="ui-alert-banner-icon material-symbols-outlined">check_circle</span>
                <div>
                    <p class="ui-alert-banner-title mb-0">Profile saved</p>
                </div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="ui-panel">
                    <h2 class="ui-panel-title">At a glance</h2>
                    <div class="mb-2"><span class="text-muted">Name</span><br><strong>{{ $employeeName }}</strong></div>
                    <div class="mb-2"><span class="text-muted">Code</span><br>{{ $employeeCode }}</div>
                    <div class="mb-2"><span class="text-muted">Department</span><br>{{ $department }}</div>
                    <div class="mb-2"><span class="text-muted">Designation</span><br>{{ $designation }}</div>
                    <div class="mb-2"><span class="text-muted">Location</span><br>{{ $location }}</div>
                    <div class="mb-2"><span class="text-muted">Manager</span><br>{{ $managerName }}</div>
                    <div class="mb-2"><span class="text-muted">Work email</span><br>{{ $workEmail }}</div>
                    <div class="mb-0"><span class="text-muted">Joined</span><br>{{ $doj }}</div>
                </section>
            </div>
            <div class="col-lg-8">
                <form wire:submit.prevent="save">
                    <section class="ui-panel mb-4">
                        <h2 class="ui-panel-title">Contact</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" wire:model.defer="phone">
                                @error('phone') <div class="text-danger text-sm">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency contact</label>
                                <input type="text" class="form-control" wire:model.defer="emergency_contact_name" placeholder="Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency phone</label>
                                <input type="text" class="form-control" wire:model.defer="emergency_contact_phone">
                            </div>
                        </div>
                    </section>

                    <section class="ui-panel mb-4">
                        <h2 class="ui-panel-title">Present address</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <input type="text" class="form-control" wire:model.defer="present_address_line1" placeholder="Address line 1">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control" wire:model.defer="present_address_line2" placeholder="Address line 2">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="present_city" placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="present_state" placeholder="State">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="present_pincode" placeholder="PIN">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" wire:model.defer="present_country" placeholder="Country">
                            </div>
                        </div>
                    </section>

                    <section class="ui-panel mb-4">
                        <h2 class="ui-panel-title">Permanent address</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <input type="text" class="form-control" wire:model.defer="permanent_address_line1" placeholder="Address line 1">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control" wire:model.defer="permanent_address_line2" placeholder="Address line 2">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="permanent_city" placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="permanent_state" placeholder="State">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" wire:model.defer="permanent_pincode" placeholder="PIN">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" wire:model.defer="permanent_country" placeholder="Country">
                            </div>
                        </div>
                    </section>

                    <button type="submit" class="ui-btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</main>
