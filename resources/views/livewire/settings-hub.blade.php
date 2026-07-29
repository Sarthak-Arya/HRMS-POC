<main class="main-content ui-page">
    <div class="container-fluid py-4">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Settings</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Configure organisation profile, structure, and module preferences.
                </p>
            </div>
        </section>

        <div class="ui-settings-layout">
            <aside class="ui-settings-nav" aria-label="Settings categories">
                <div class="ui-settings-nav-group">
                    <button type="button"
                        class="ui-settings-nav-group-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#settings-org-group"
                        aria-expanded="{{ in_array($category, ['organization-profile', 'tax'], true) ? 'true' : 'false' }}">
                        <span>Organisation Settings</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div id="settings-org-group" class="collapse {{ in_array($category, ['organization-profile', 'tax'], true) ? 'show' : '' }}">
                        <div class="ui-settings-nav-group-body">
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'organization-profile']) }}"
                               class="ui-settings-nav-item {{ $category === 'organization-profile' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">apartment</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Organisation</span>
                                    <span class="ui-settings-nav-desc">Profile, branding & structure</span>
                                </span>
                            </a>
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'tax']) }}"
                               class="ui-settings-nav-item {{ $category === 'tax' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">receipt_long</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Taxes</span>
                                    <span class="ui-settings-nav-desc">PAN, TAN & TDS details</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="ui-settings-nav-group">
                    @php
                        $compensationTab = request()->query('comp_tab');
                        $isCompensationSetupActive = $category === 'compensation'
                            && in_array($compensationTab, ['components', 'structures', 'assignments', 'overrides'], true);
                    @endphp
                    <button type="button"
                        class="ui-settings-nav-group-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#settings-setup-group"
                        aria-expanded="{{ ($category === 'statutory' || $isCompensationSetupActive) ? 'true' : 'false' }}">
                        <span>Setup & Configurations</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div id="settings-setup-group" class="collapse {{ ($category === 'statutory' || $isCompensationSetupActive) ? 'show' : '' }}">
                        <div class="ui-settings-nav-group-body">
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'statutory']) }}"
                               class="ui-settings-nav-item {{ $category === 'statutory' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">balance</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Statutory Components</span>
                                    <span class="ui-settings-nav-desc">EPF, ESI, PT, LWF & Bonus</span>
                                </span>
                            </a>
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'components']) }}"
                               class="ui-settings-nav-item {{ $category === 'compensation' && request()->query('comp_tab') === 'components' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">tune</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Salary Components</span>
                                    <span class="ui-settings-nav-desc">Pay heads: Basic, HRA, benefits</span>
                                </span>
                            </a>
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'structures']) }}"
                               class="ui-settings-nav-item {{ $category === 'compensation' && request()->query('comp_tab') === 'structures' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">schema</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Compensation Structures</span>
                                    <span class="ui-settings-nav-desc">CTC templates from components</span>
                                </span>
                            </a>
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'assignments']) }}"
                               class="ui-settings-nav-item {{ $category === 'compensation' && request()->query('comp_tab') === 'assignments' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">groups</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Structure Assignments</span>
                                    <span class="ui-settings-nav-desc">Who gets which structure</span>
                                </span>
                            </a>
                            <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'overrides']) }}"
                               class="ui-settings-nav-item {{ $category === 'compensation' && request()->query('comp_tab') === 'overrides' ? 'active' : '' }}">
                                <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">rule_settings</span>
                                <span class="ui-settings-nav-copy">
                                    <span class="ui-settings-nav-label">Compensation Overrides</span>
                                    <span class="ui-settings-nav-desc">Exceptions without changing templates</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="ui-settings-nav-group">
                    @php
                        $isModuleCompensationActive = $category === 'compensation' && ! $isCompensationSetupActive;
                        $isModuleSettingsActive = in_array($category, ['attendance', 'reports'], true) || $isModuleCompensationActive;
                    @endphp
                    <button type="button"
                        class="ui-settings-nav-group-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#settings-module-group"
                        aria-expanded="{{ $isModuleSettingsActive ? 'true' : 'false' }}">
                        <span>Module Settings</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div id="settings-module-group" class="collapse {{ $isModuleSettingsActive ? 'show' : '' }}">
                        <div class="ui-settings-nav-group-body">
                            @foreach([
                                'attendance' => ['label' => 'Attendance', 'icon' => 'calendar_month', 'desc' => 'Defaults and locks'],
                                'compensation' => ['label' => 'Compensation', 'icon' => 'account_balance_wallet', 'desc' => 'Pay cycle preferences'],
                                'reports' => ['label' => 'Reports', 'icon' => 'analytics', 'desc' => 'Export defaults'],
                            ] as $navKey => $navItem)
                                @php
                                    $isNavActive = $navKey === 'compensation'
                                        ? $isModuleCompensationActive
                                        : $category === $navKey;
                                @endphp
                                <a href="{{ route('settings', ['company_id' => $companyId, 'category' => $navKey]) }}"
                                   class="ui-settings-nav-item {{ $isNavActive ? 'active' : '' }}">
                                    <span class="ui-settings-nav-icon material-symbols-outlined" aria-hidden="true">{{ $navItem['icon'] }}</span>
                                    <span class="ui-settings-nav-copy">
                                        <span class="ui-settings-nav-label">{{ $navItem['label'] }}</span>
                                        <span class="ui-settings-nav-desc">{{ $navItem['desc'] }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>

            <div class="ui-settings-content">
                @if($category === 'organization-profile')
                    <nav class="ui-tab-bar ui-settings-subtabs" aria-label="Organisation settings sections">
                        @foreach([
                            'profile' => 'Organisation Profile',
                            'branding' => 'Branding',
                            'locations' => 'Work Locations',
                            'departments' => 'Departments',
                            'designations' => 'Designations',
                        ] as $tab => $label)
                            <button type="button"
                                class="ui-tab-btn {{ $orgSubTab === $tab ? 'active' : '' }}"
                                wire:click="setOrgSubTab('{{ $tab }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>

                    @if($orgSubTab === 'profile')
                        <section class="ui-card ui-org-profile-card">
                            <div class="ui-card-header ui-org-profile-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Organisation Profile</h2>
                                    <p class="ui-card-desc mb-0">Registered business details used on payslips and forms.</p>
                                </div>
                                <div class="ui-org-id-chip">Organisation ID: {{ $companyId }}</div>
                            </div>
                            <div class="ui-card-body">
                                <form wire:submit.prevent="saveCompanyProfile" class="ui-org-profile-form">
                                    <div class="ui-org-field">
                                        <label class="form-label">Organisation Logo</label>
                                        <div class="ui-logo-upload">
                                            <label class="ui-logo-dropzone {{ ($logo || $logoUrl) ? 'has-preview' : '' }}">
                                                @if($logo)
                                                    <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="ui-logo-preview">
                                                @elseif($logoUrl)
                                                    <img src="{{ $logoUrl }}" alt="Organisation logo" class="ui-logo-preview">
                                                @else
                                                    <span class="material-symbols-outlined ui-logo-upload-icon" aria-hidden="true">cloud_upload</span>
                                                    <span class="ui-logo-upload-text">UPLOAD LOGO</span>
                                                @endif
                                                <input type="file" class="d-none" wire:model="logo" accept=".png,.jpg,.jpeg,image/png,image/jpeg" @disabled(!$this->canManageSection('company_profile'))>
                                            </label>
                                            <div class="ui-org-help">
                                                This logo will appear on documents like Payslips.
                                                Preferred Image Size: 240 x 240 pixels @ 72 DPI. Maximum size of 1MB.
                                                Supported File Formats: png, jpg, jpeg.
                                            </div>
                                            @error('logo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            @if($this->canManageSection('company_profile') && ($logoPath || $logo))
                                                <button type="button" class="ui-link-btn ui-link-btn--danger mt-2" wire:click="removeLogo">Remove logo</button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="ui-org-field">
                                        <label class="form-label">Organisation Name <span class="text-danger">*</span></label>
                                        <p class="ui-org-help">This is your registered business name which will appear in all the forms and payslips.</p>
                                        <input type="text" class="form-control" wire:model.defer="companyName" @disabled(!$this->canManageSection('company_profile'))>
                                        @error('companyName') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Business Location <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" wire:model.defer="businessLocation" @disabled(!$this->canManageSection('company_profile'))>
                                            @error('businessLocation') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Industry <span class="text-danger">*</span></label>
                                            <select class="form-select" wire:model.defer="industry" @disabled(!$this->canManageSection('company_profile'))>
                                                <option value="">Select industry</option>
                                                @foreach(['Consulting','Information Technology','Manufacturing','Retail','Healthcare','Education','Finance','Other'] as $industryOption)
                                                    <option value="{{ $industryOption }}">{{ $industryOption }}</option>
                                                @endforeach
                                            </select>
                                            @error('industry') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Date Format <span class="text-danger">*</span></label>
                                            <select class="form-select" wire:model.defer="dateFormat" @disabled(!$this->canManageSection('company_profile'))>
                                                <option value="d/m/Y">dd/MM/yyyy ({{ now()->format('d/m/Y') }})</option>
                                                <option value="d-m-Y">dd-MM-yyyy ({{ now()->format('d-m-Y') }})</option>
                                                <option value="d M Y">dd MMM yyyy ({{ now()->format('d M Y') }})</option>
                                                <option value="Y-m-d">yyyy-MM-dd ({{ now()->format('Y-m-d') }})</option>
                                                <option value="m/d/Y">MM/dd/yyyy ({{ now()->format('m/d/Y') }})</option>
                                            </select>
                                            @error('dateFormat') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Field Separator</label>
                                            <select class="form-select" wire:model.defer="fieldSeparator" @disabled(!$this->canManageSection('company_profile'))>
                                                <option value="/">/</option>
                                                <option value="-">-</option>
                                                <option value=".">.</option>
                                            </select>
                                            @error('fieldSeparator') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="ui-org-field">
                                        <label class="form-label">Organisation Address <span class="text-danger">*</span></label>
                                        <p class="ui-org-help">This will be considered as the address of your primary work location.</p>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <input type="text" class="form-control" placeholder="Address Line 1" wire:model.defer="addressLine1" @disabled(!$this->canManageSection('company_profile'))>
                                                @error('addressLine1') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-12">
                                                <input type="text" class="form-control" placeholder="Address Line 2" wire:model.defer="addressLine2" @disabled(!$this->canManageSection('company_profile'))>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" placeholder="State" wire:model.defer="state" @disabled(!$this->canManageSection('company_profile'))>
                                                @error('state') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" placeholder="City" wire:model.defer="city" @disabled(!$this->canManageSection('company_profile'))>
                                                @error('city') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" placeholder="PIN Code" wire:model.defer="zipCode" @disabled(!$this->canManageSection('company_profile'))>
                                                @error('zipCode') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ui-org-field">
                                        <label class="form-label">Filing Address</label>
                                        <p class="ui-org-help">This registered address will be used across all Forms and Payslips.</p>
                                        <div class="ui-filing-card">
                                            <div class="ui-filing-card-header">
                                                <strong>Head Office</strong>
                                                @if($this->canManageSection('company_profile'))
                                                    <button type="button" class="ui-link-btn" wire:click="useOrganisationAddressAsFiling">
                                                        <span class="material-symbols-outlined" style="font-size:1rem;">edit</span>
                                                        Use organisation address
                                                    </button>
                                                @endif
                                            </div>
                                            <p class="ui-filing-address mb-3">
                                                {{ $filingAddress !== '' ? $filingAddress : 'No filing address set yet.' }}
                                            </p>
                                            @if($this->canManageSection('company_profile'))
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Select work location</label>
                                                        <select class="form-select" wire:model="filingLocationId" wire:change="syncFilingAddressFromLocation">
                                                            <option value="">Custom / Organisation address</option>
                                                            @foreach($locations as $location)
                                                                <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Filing address text</label>
                                                        <input type="text" class="form-control" wire:model.defer="filingAddress" placeholder="Enter filing address">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <h3 class="h6 mb-3">Regional & Statutory</h3>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Timezone</label>
                                            <input type="text" class="form-control" wire:model.defer="timezone" @disabled(!$this->canManageSection('company_profile'))>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fiscal Year Start Month</label>
                                            <input type="number" min="1" max="12" class="form-control" wire:model.defer="fiscalYearStartMonth" @disabled(!$this->canManageSection('company_profile'))>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Payroll Currency</label>
                                            <input type="text" class="form-control" wire:model.defer="payrollCurrency" maxlength="3" @disabled(!$this->canManageSection('company_profile'))>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">GST Number</label>
                                            <input type="text" class="form-control" wire:model.defer="gstNumber" @disabled(!$this->canManageSection('company_profile'))>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.defer="isPf" id="settings-is-pf" @disabled(!$this->canManageSection('company_profile'))>
                                                <label class="form-check-label" for="settings-is-pf">PF Enabled</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.defer="isEsi" id="settings-is-esi" @disabled(!$this->canManageSection('company_profile'))>
                                                <label class="form-check-label" for="settings-is-esi">ESI Enabled</label>
                                            </div>
                                        </div>
                                    </div>

                                    @if($this->canManageSection('company_profile'))
                                        <div class="mt-4">
                                            <button type="submit" class="ui-btn-primary">Save Organisation Profile</button>
                                        </div>
                                    @else
                                        <p class="text-muted small mt-4 mb-0">You have read-only access to organisation profile settings.</p>
                                    @endif
                                </form>
                            </div>
                        </section>
                    @endif

                    @if($orgSubTab === 'branding')
                        <section class="ui-card">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Branding</h2>
                                    <p class="ui-card-desc mb-0">Primary colors and typography for company documents.</p>
                                </div>
                            </div>
                            <div class="ui-card-body">
                                <form wire:submit.prevent="saveBranding">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Primary Color</label>
                                            <div class="ui-color-field">
                                                <input type="color" class="form-control form-control-color" wire:model.defer="primaryColor" @disabled(!$this->canManageSection('company_profile'))>
                                                <input type="text" class="form-control" wire:model.defer="primaryColor" @disabled(!$this->canManageSection('company_profile'))>
                                            </div>
                                            @error('primaryColor') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Secondary Color</label>
                                            <div class="ui-color-field">
                                                <input type="color" class="form-control form-control-color" wire:model.defer="secondaryColor" @disabled(!$this->canManageSection('company_profile'))>
                                                <input type="text" class="form-control" wire:model.defer="secondaryColor" @disabled(!$this->canManageSection('company_profile'))>
                                            </div>
                                            @error('secondaryColor') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Font Family</label>
                                            <select class="form-select" wire:model.defer="fontFamily" @disabled(!$this->canManageSection('company_profile'))>
                                                @foreach(['Inter','Roboto','Open Sans','Lato','Poppins','Noto Sans'] as $font)
                                                    <option value="{{ $font }}">{{ $font }}</option>
                                                @endforeach
                                            </select>
                                            @error('fontFamily') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="ui-branding-preview mt-4" style="--brand-primary: {{ $primaryColor }}; --brand-secondary: {{ $secondaryColor }}; font-family: {{ $fontFamily }}, sans-serif;">
                                        <div class="ui-branding-preview-swatch" style="background: var(--brand-primary);"></div>
                                        <div>
                                            <strong style="color: var(--brand-secondary);">{{ $companyName }}</strong>
                                            <p class="mb-0 text-muted small">Sample document heading using your brand font and colors.</p>
                                        </div>
                                    </div>

                                    @if($this->canManageSection('company_profile'))
                                        <div class="mt-4">
                                            <button type="submit" class="ui-btn-primary">Save Branding</button>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </section>
                    @endif

                    @if($orgSubTab === 'locations')
                        <section class="ui-card mb-4">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Work Locations</h2>
                                    <p class="ui-card-desc mb-0">Manage office and branch locations for this company.</p>
                                </div>
                            </div>
                            <div class="ui-card-body">
                                @if($this->canManageSection('organization'))
                                    <form wire:submit.prevent="saveOrganizationPreferences" class="mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="requireLocation" id="require-loc">
                                            <label class="form-check-label" for="require-loc">Require location on employee</label>
                                        </div>
                                        <button type="submit" class="ui-btn-secondary ui-btn-secondary--sm mt-2">Save Preference</button>
                                    </form>
                                @endif

                                @if($this->canManageSection('organization'))
                                    <form wire:submit.prevent="saveLocation" class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Location Name</label>
                                            <input type="text" class="form-control" wire:model.defer="locationName">
                                            @error('locationName') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Code</label>
                                            <input type="text" class="form-control" wire:model.defer="locationCode">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" wire:model.defer="locationCity">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" wire:model.defer="locationAddress">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">State</label>
                                            <input type="text" class="form-control" wire:model.defer="locationState">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">PIN Code</label>
                                            <input type="text" class="form-control" wire:model.defer="locationPincode">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Country</label>
                                            <input type="text" class="form-control" wire:model.defer="locationCountry">
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="ui-btn-primary ui-btn-primary--sm">
                                                {{ $editingLocationId ? 'Update Location' : 'Add Location' }}
                                            </button>
                                        </div>
                                    </form>
                                @endif

                                <div class="ui-data-table-wrap">
                                    <table class="ui-data-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>City</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($locations as $location)
                                                <tr>
                                                    <td>{{ $location->location_name }}</td>
                                                    <td>{{ $location->location_code ?: '—' }}</td>
                                                    <td>{{ $location->location_city ?: '—' }}</td>
                                                    <td class="text-end">
                                                        @if($this->canManageSection('organization'))
                                                            <button class="ui-link-btn" wire:click="editLocation({{ $location->id }})">Edit</button>
                                                            <button class="ui-link-btn ui-link-btn--danger" wire:click="deleteLocation({{ $location->id }})" wire:confirm="Delete this location?">Delete</button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted py-4">No work locations yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    @endif

                    @if($orgSubTab === 'departments')
                        <section class="ui-card">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Departments</h2>
                                    <p class="ui-card-desc mb-0">Organise employees into departments.</p>
                                </div>
                                @if($this->canManageSection('organization'))
                                    <form class="d-flex gap-2 flex-wrap" wire:submit.prevent="saveDepartment">
                                        <input type="text" class="form-control form-control-sm" placeholder="Department name" wire:model.defer="departmentName">
                                        <button type="submit" class="ui-btn-primary ui-btn-primary--sm">
                                            {{ $editingDepartmentId ? 'Update' : 'Add' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="ui-card-body">
                                @if($this->canManageSection('organization'))
                                    <form wire:submit.prevent="saveOrganizationPreferences" class="mb-4">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="requireDepartment" id="require-dept">
                                                    <label class="form-check-label" for="require-dept">Require department on employee</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="allowDuplicateDepartmentNames" id="allow-dup-dept">
                                                    <label class="form-check-label" for="allow-dup-dept">Allow duplicate department names</label>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="ui-btn-secondary ui-btn-secondary--sm mt-2">Save Preferences</button>
                                    </form>
                                @endif
                            </div>
                            <div class="ui-card-body p-0 pt-0">
                                <div class="ui-data-table-wrap">
                                    <table class="ui-data-table">
                                        <thead><tr><th>Name</th><th></th></tr></thead>
                                        <tbody>
                                            @forelse($departments as $department)
                                                <tr>
                                                    <td>{{ $department->department_name }}</td>
                                                    <td class="text-end">
                                                        @if($this->canManageSection('organization'))
                                                            <button class="ui-link-btn" wire:click="editDepartment({{ $department->id }})">Edit</button>
                                                            <button class="ui-link-btn ui-link-btn--danger" wire:click="deleteDepartment({{ $department->id }})" wire:confirm="Delete this department?">Delete</button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="text-center text-muted py-4">No departments yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    @endif

                    @if($orgSubTab === 'designations')
                        <section class="ui-card">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Designations</h2>
                                    <p class="ui-card-desc mb-0">Job titles used across employee records.</p>
                                </div>
                                @if($this->canManageSection('organization'))
                                    <form class="d-flex gap-2 flex-wrap" wire:submit.prevent="saveDesignation">
                                        <input type="text" class="form-control form-control-sm" placeholder="Designation name" wire:model.defer="designationName">
                                        <button type="submit" class="ui-btn-primary ui-btn-primary--sm">
                                            {{ $editingDesignationId ? 'Update' : 'Add' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="ui-card-body">
                                @if($this->canManageSection('organization'))
                                    <form wire:submit.prevent="saveOrganizationPreferences" class="mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="requireDesignation" id="require-desig">
                                            <label class="form-check-label" for="require-desig">Require designation on employee</label>
                                        </div>
                                        <button type="submit" class="ui-btn-secondary ui-btn-secondary--sm mt-2">Save Preference</button>
                                    </form>
                                @endif
                            </div>
                            <div class="ui-card-body p-0 pt-0">
                                <div class="ui-data-table-wrap">
                                    <table class="ui-data-table">
                                        <thead><tr><th>Name</th><th></th></tr></thead>
                                        <tbody>
                                            @forelse($designations as $designation)
                                                <tr>
                                                    <td>{{ $designation->designation_name }}</td>
                                                    <td class="text-end">
                                                        @if($this->canManageSection('organization'))
                                                            <button class="ui-link-btn" wire:click="editDesignation({{ $designation->id }})">Edit</button>
                                                            <button class="ui-link-btn ui-link-btn--danger" wire:click="deleteDesignation({{ $designation->id }})" wire:confirm="Delete this designation?">Delete</button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="text-center text-muted py-4">No designations yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    @endif
                @endif

                @if($category === 'attendance')
                    <section class="ui-card mb-4">
                        <div class="ui-card-header">
                            <div>
                                <h2 class="ui-card-title mb-0">Attendance Preferences</h2>
                                <p class="ui-card-desc mb-0">Default views and operational flags for attendance.</p>
                            </div>
                            <a href="{{ route('attendance', ['company_id' => $companyId]) }}" class="ui-btn-secondary ui-btn-secondary--sm">
                                Open Attendance Hub
                            </a>
                        </div>
                        <div class="ui-card-body">
                            <form wire:submit.prevent="saveAttendancePreferences">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Default View</label>
                                        <select class="form-select" wire:model.defer="attendanceDefaultView" @disabled(!$this->canManageSection('attendance'))>
                                            <option value="monthly_summary">Monthly Summary</option>
                                            <option value="daily">Daily Marking</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Default Policy</label>
                                        <select class="form-select" wire:model.defer="attendanceDefaultPolicyId" @disabled(!$this->canManageSection('attendance'))>
                                            <option value="">None</option>
                                            @foreach($attendancePolicies as $policy)
                                                <option value="{{ $policy->id }}">{{ $policy->policy_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="attendanceMonthLockEnforced" id="month-lock" @disabled(!$this->canManageSection('attendance'))>
                                            <label class="form-check-label" for="month-lock">Enforce month lock</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="attendanceShowDailyMarking" id="show-daily" @disabled(!$this->canManageSection('attendance'))>
                                            <label class="form-check-label" for="show-daily">Show daily marking tab</label>
                                        </div>
                                    </div>
                                </div>
                                @if($this->canManageSection('attendance'))
                                    <div class="mt-4">
                                        <button type="submit" class="ui-btn-primary">Save Attendance Preferences</button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </section>
                @endif

                @if($category === 'compensation')
                    @php
                        $compensationSetupGuide = [
                            [
                                'icon' => 'tune',
                                'label' => 'Components',
                                'summary' => 'Building blocks of pay',
                                'description' => 'Create earnings, deductions, and benefits (Basic, HRA, NPS, etc.) with calculation rules. Components are reused across structures.',
                                'tab' => 'components',
                            ],
                            [
                                'icon' => 'schema',
                                'label' => 'Structures',
                                'summary' => 'CTC templates',
                                'description' => 'Combine components into a salary blueprint—for example, how monthly CTC splits across Basic, allowances, and statutory deductions.',
                                'tab' => 'structures',
                            ],
                            [
                                'icon' => 'groups',
                                'label' => 'Assignments',
                                'summary' => 'Who gets which structure',
                                'description' => 'Apply a structure at company, location, department, or employee level so payroll picks the right template for each person.',
                                'tab' => 'assignments',
                            ],
                            [
                                'icon' => 'rule_settings',
                                'label' => 'Overrides',
                                'summary' => 'Targeted exceptions',
                                'description' => 'Adjust or replace specific component values for a scope without changing the master structure—for one-off or regional differences.',
                                'tab' => 'overrides',
                            ],
                        ];
                    @endphp

                    <section class="ui-card mb-4">
                        <div class="ui-card-header">
                            <div>
                                <h2 class="ui-card-title mb-0">How compensation setup works</h2>
                                <p class="ui-card-desc mb-0">Set up in order: define components, build structures, assign them, then add overrides only when needed.</p>
                            </div>
                        </div>
                        <div class="ui-card-body">
                            <div class="ui-compensation-guide-grid">
                                @foreach($compensationSetupGuide as $guide)
                                    <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => $guide['tab']]) }}"
                                       class="ui-compensation-guide-card {{ request()->query('comp_tab') === $guide['tab'] ? 'active' : '' }}">
                                        <span class="material-symbols-outlined ui-compensation-guide-icon" aria-hidden="true">{{ $guide['icon'] }}</span>
                                        <span class="ui-compensation-guide-label">{{ $guide['label'] }}</span>
                                        <span class="ui-compensation-guide-summary">{{ $guide['summary'] }}</span>
                                        <span class="ui-compensation-guide-text">{{ $guide['description'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="ui-card mb-4">
                        <div class="ui-card-header">
                            <div>
                                <h2 class="ui-card-title mb-0">Compensation Preferences</h2>
                                <p class="ui-card-desc mb-0">Payroll cycle and display defaults.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'components']) }}" class="ui-btn-secondary ui-btn-secondary--sm">Salary Components</a>
                                <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'structures']) }}" class="ui-btn-secondary ui-btn-secondary--sm">Structures</a>
                                <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'assignments']) }}" class="ui-btn-secondary ui-btn-secondary--sm">Assignments</a>
                                <a href="{{ route('settings', ['company_id' => $companyId, 'category' => 'compensation', 'comp_tab' => 'overrides']) }}" class="ui-btn-secondary ui-btn-secondary--sm">Overrides</a>
                            </div>
                        </div>
                        <div class="ui-card-body">
                            <form wire:submit.prevent="saveCompensationPreferences">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Default Pay Cycle</label>
                                        <select class="form-select" wire:model.defer="compensationDefaultPayCycle" @disabled(!$this->canManageSection('compensation'))>
                                            <option value="monthly">Monthly</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Bi-weekly</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Round Payroll To Nearest</label>
                                        <input type="number" min="1" max="1000" class="form-control" wire:model.defer="compensationRoundPayrollToNearest" @disabled(!$this->canManageSection('compensation'))>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="compensationShowInactiveComponents" id="show-inactive" @disabled(!$this->canManageSection('compensation'))>
                                            <label class="form-check-label" for="show-inactive">Show inactive components</label>
                                        </div>
                                    </div>
                                </div>
                                @if($this->canManageSection('compensation'))
                                    <div class="mt-4">
                                        <button type="submit" class="ui-btn-primary">Save Compensation Preferences</button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </section>

                    @if(in_array(request()->query('comp_tab'), ['components', 'structures', 'assignments', 'overrides'], true))
                        <section class="ui-card mb-4">
                            <div class="ui-card-body">
                                <livewire:compensation-hub
                                    :company_id="$companyId"
                                    :tab="request()->query('comp_tab')"
                                    :embedded="true"
                                    :key="'settings-compensation-'.$companyId.'-'.request()->query('comp_tab')" />
                            </div>
                        </section>
                    @endif
                @endif

                @if($category === 'reports')
                    <section class="ui-card mb-4">
                        <div class="ui-card-header">
                            <div>
                                <h2 class="ui-card-title mb-0">Report Preferences</h2>
                                <p class="ui-card-desc mb-0">Default export and template behavior.</p>
                            </div>
                            <a href="{{ route('reports', ['company_id' => $companyId]) }}" class="ui-btn-secondary ui-btn-secondary--sm">
                                Open Reports Hub
                            </a>
                        </div>
                        <div class="ui-card-body">
                            <form wire:submit.prevent="saveReportsPreferences">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Default Export Format</label>
                                        <select class="form-select" wire:model.defer="reportsDefaultExportFormat" @disabled(!$this->canManageSection('reports'))>
                                            <option value="xlsx">Excel (.xlsx)</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Default Template Category</label>
                                        <select class="form-select" wire:model.defer="reportsDefaultTemplateCategory" @disabled(!$this->canManageSection('reports'))>
                                            <option value="all">All</option>
                                            <option value="payroll">Payroll</option>
                                            <option value="attendance">Attendance</option>
                                            <option value="employee">Employee</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="reportsIncludeCompanyLogo" id="include-logo" @disabled(!$this->canManageSection('reports'))>
                                            <label class="form-check-label" for="include-logo">Include company logo in exports</label>
                                        </div>
                                    </div>
                                </div>
                                @if($this->canManageSection('reports'))
                                    <div class="mt-4">
                                        <button type="submit" class="ui-btn-primary">Save Report Preferences</button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </section>
                @endif

                @if($category === 'tax')
                    <section class="ui-page-header mb-3">
                        <div>
                            <h2 class="ui-page-title h4 mb-1">Tax Details</h2>
                            <p class="ui-page-subtitle mb-0">Organisation tax identifiers and deductor information.</p>
                        </div>
                    </section>
                    <form wire:submit.prevent="saveTaxDetails">
                        <section class="ui-card mb-4">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Organisation Tax Details</h2>
                                    <p class="ui-card-desc mb-0">PAN, TAN, TDS circle, and payment frequency for this company.</p>
                                </div>
                            </div>
                            <div class="ui-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">PAN <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control text-uppercase @error('pan') is-invalid @enderror"
                                            placeholder="AAAAA0000A"
                                            maxlength="10"
                                            wire:model.defer="taxPan"
                                            @disabled(!$this->canManageSection('tax'))>
                                        @error('pan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TAN</label>
                                        <input type="text"
                                            class="form-control text-uppercase @error('tan') is-invalid @enderror"
                                            placeholder="AAAA00000A"
                                            maxlength="10"
                                            wire:model.defer="taxTan"
                                            @disabled(!$this->canManageSection('tax'))>
                                        @error('tan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">TDS circle / AO code</label>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <input type="text" class="form-control text-uppercase" style="max-width: 5rem;" maxlength="3" placeholder="AAA" wire:model.defer="taxTdsCircleArea" @disabled(!$this->canManageSection('tax'))>
                                            <span class="text-muted">/</span>
                                            <input type="text" class="form-control text-uppercase" style="max-width: 4rem;" maxlength="2" placeholder="AA" wire:model.defer="taxTdsCircleRange" @disabled(!$this->canManageSection('tax'))>
                                            <span class="text-muted">/</span>
                                            <input type="text" class="form-control text-uppercase" style="max-width: 5rem;" maxlength="3" placeholder="000" wire:model.defer="taxTdsCircleNumber" @disabled(!$this->canManageSection('tax'))>
                                            <span class="text-muted">/</span>
                                            <input type="text" class="form-control text-uppercase" style="max-width: 4rem;" maxlength="2" placeholder="00" wire:model.defer="taxTdsCircleAo" @disabled(!$this->canManageSection('tax'))>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tax Payment Frequency</label>
                                        <select class="form-select" wire:model.defer="taxPaymentFrequency" @disabled(!$this->canManageSection('tax'))>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="ui-card mb-4">
                            <div class="ui-card-header">
                                <div>
                                    <h2 class="ui-card-title mb-0">Tax Deductor Details</h2>
                                    <p class="ui-card-desc mb-0">Person responsible for tax deduction for this organisation.</p>
                                </div>
                            </div>
                            <div class="ui-card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label d-block">Deductor's Type</label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" id="deductor-employee" value="employee" wire:model.defer="taxDeductorType" @disabled(!$this->canManageSection('tax'))>
                                                <label class="form-check-label" for="deductor-employee">Employee</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" id="deductor-non-employee" value="non_employee" wire:model.defer="taxDeductorType" @disabled(!$this->canManageSection('tax'))>
                                                <label class="form-check-label" for="deductor-non-employee">Non-Employee</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Deductor's Name</label>
                                        <input type="text"
                                            class="form-control"
                                            placeholder="Enter tax deductor name"
                                            wire:model.defer="taxDeductorName"
                                            @disabled(!$this->canManageSection('tax'))>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Deductor's Father's Name</label>
                                        <input type="text"
                                            class="form-control"
                                            placeholder="Enter father's name"
                                            wire:model.defer="taxDeductorFatherName"
                                            @disabled(!$this->canManageSection('tax'))>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                                    @if($this->canManageSection('tax'))
                                        <button type="submit" class="ui-btn-primary">Save</button>
                                    @else
                                        <span></span>
                                    @endif
                                    <small class="text-danger mb-0">* indicates mandatory fields</small>
                                </div>
                            </div>
                        </section>
                    </form>
                @endif

                @if($category === 'statutory')
                    <section class="ui-page-header mb-3">
                        <div>
                            <h2 class="ui-page-title h4 mb-1">Statutory Components</h2>
                            <p class="ui-page-subtitle mb-0">Configure EPF, ESI, Professional Tax, Labour Welfare Fund and Statutory Bonus for this company.</p>
                        </div>
                    </section>

                    <nav class="ui-tab-bar ui-settings-subtabs" aria-label="Statutory compliance tabs">
                        @foreach([
                            'epf' => 'EPF',
                            'esi' => 'ESI',
                            'professional_tax' => 'Professional Tax',
                            'lwf' => 'Labour Welfare Fund',
                            'statutory_bonus' => 'Statutory Bonus',
                        ] as $tab => $label)
                            <button type="button"
                                class="ui-tab-btn {{ $statutorySubTab === $tab ? 'active' : '' }}"
                                wire:click="setStatutorySubTab('{{ $tab }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>

                    @if($statutorySubTab === 'epf')
                        @if($epfUiMode === 'splash')
                            <section class="ui-card">
                                <div class="ui-card-body">
                                    <div class="ui-statutory-empty">
                                        <div class="ui-statutory-empty-illustration" aria-hidden="true">
                                            <span class="material-symbols-outlined">account_balance</span>
                                        </div>
                                        <h3 class="h5 mb-2">Are you registered for EPF?</h3>
                                        <p class="text-muted mb-4" style="max-width: 36rem;">
                                            Under the Code on Social Security, 2020 and the Employees’ Provident Funds Scheme, any organisation with 20 or more employees must register for the Employees’ Provident Fund (EPF) — a retirement benefit plan for salaried employees. Employer and employee each contribute 12% of PF wages, with the employer share split between EPS (pension) and EPF.
                                        </p>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-btn-primary" wire:click="beginEnableEpf">Enable EPF</button>
                                        @else
                                            <p class="text-muted small mb-0">You have read-only access to statutory settings.</p>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="ui-statutory-epf-layout">
                                <section class="ui-card">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Employees' Provident Fund</h2>
                                            <p class="ui-card-desc mb-0">EPFO contribution preferences and salary-structure options.</p>
                                        </div>
                                        @if($epfEnabled && $this->canManageSection('statutory'))
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="disableEpf" wire:confirm="Disable EPF for this company?">Disable EPF</button>
                                        @endif
                                    </div>
                                    <div class="ui-card-body">
                                        <form wire:submit.prevent="{{ $epfEnabled ? 'saveEpfSettings' : 'enableEpf' }}">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">EPF Number <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" placeholder="AA/AAA/0000000/000" wire:model.defer="epfNumber" @disabled(!$this->canManageSection('statutory'))>
                                                    @error('epfNumber') <div class="text-danger small">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Deduction Cycle</label>
                                                    <select class="form-select" wire:model.defer="epfDeductionCycle" @disabled(!$this->canManageSection('statutory'))>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Employee Contribution Rate</label>
                                                    <select class="form-select" wire:model="epfEmployeeContributionRate" @disabled(!$this->canManageSection('statutory'))>
                                                        <option value="12_actual">12% of Actual PF Wage</option>
                                                        <option value="12_restricted">12% of Restricted PF Wage (₹{{ number_format($epfWageCeiling, 0) }})</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Employer Contribution Rate</label>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <select class="form-select" wire:model="epfEmployerContributionRate" @disabled(!$this->canManageSection('statutory'))>
                                                            <option value="12_actual">12% of Actual PF Wage</option>
                                                            <option value="12_restricted">12% of Restricted PF Wage (₹{{ number_format($epfWageCeiling, 0) }})</option>
                                                        </select>
                                                        <button type="button" class="ui-link-btn text-nowrap" wire:click="toggleEpfSplitup">{{ $showEpfSplitup ? 'Hide Splitup' : 'View Splitup' }}</button>
                                                    </div>
                                                    @if($showEpfSplitup)
                                                        <div class="ui-statutory-help mt-2">
                                                            Employer 12% splits into <strong>EPS 8.33%</strong> (pension, capped at ₹{{ number_format($epfWageCeiling, 0) }} PF wage) and the balance as <strong>Employer EPF</strong>.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="epf-include-employer" wire:model="epfIncludeEmployerContributionInCtc" @disabled(!$this->canManageSection('statutory'))>
                                                    <label class="form-check-label" for="epf-include-employer">Include employer's contribution in employee's salary structure</label>
                                                </div>
                                                <div class="ms-4 mt-2">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="epf-include-edli" wire:model="epfIncludeEdliInCtc" @disabled(!$this->canManageSection('statutory') || !$epfIncludeEmployerContributionInCtc)>
                                                        <label class="form-check-label" for="epf-include-edli">
                                                            Include employer's EDLI contribution in employee's salary structure
                                                            <span class="ui-statutory-info" title="Employees' Deposit Linked Insurance (EDLI) is a life-insurance benefit linked to EPF. The employer pays 0.50% of PF wages (capped at ₹15,000). It is never deducted from the employee.">?</span>
                                                        </label>
                                                    </div>
                                                    <p class="ui-statutory-help ms-4 mb-3">
                                                        <strong>EDLI (Employees’ Deposit Linked Insurance):</strong> a life cover under EPFO linked to the EPF account. Employer contributes <strong>0.50%</strong> of PF wages (wage ceiling ₹{{ number_format($epfWageCeiling, 0) }}). This amount must not be recovered from the employee.
                                                    </p>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="epf-include-admin" wire:model="epfIncludeAdminChargesInCtc" @disabled(!$this->canManageSection('statutory') || !$epfIncludeEmployerContributionInCtc)>
                                                        <label class="form-check-label" for="epf-include-admin">
                                                            Include admin charges in employee's salary structure
                                                            <span class="ui-statutory-info" title="EPF administrative charges are paid by the employer to EPFO at 0.50% of PF wages (minimum ₹75 per establishment per month).">?</span>
                                                        </label>
                                                    </div>
                                                    <p class="ui-statutory-help ms-4 mb-0">
                                                        <strong>Admin charges:</strong> charges paid by the employer to EPFO for administering the provident fund. Currently <strong>0.50%</strong> of PF wages, with a minimum of <strong>₹75</strong> per establishment per month. These are employer costs and are not deducted from employee salary unless you choose to show them in CTC.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="epf-override" wire:model.defer="epfAllowEmployeeRateOverride" @disabled(!$this->canManageSection('statutory'))>
                                                <label class="form-check-label" for="epf-override">Override PF contribution rate at employee level</label>
                                            </div>

                                            <hr class="my-4">
                                            <h3 class="h6 mb-3">PF Configuration when LOP (Loss of Pay) Applied</h3>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="epf-prorate" wire:model="epfProRateRestrictedPfWage" @disabled(!$this->canManageSection('statutory'))>
                                                <label class="form-check-label" for="epf-prorate">
                                                    <strong>Pro-rate Restricted PF Wage</strong>
                                                    <span class="d-block text-muted small">PF contribution will be pro-rated based on the number of days worked by the employee.</span>
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="epf-lop-components" wire:model.defer="epfConsiderAllComponentsWhenBelowCeilingAfterLop" @disabled(!$this->canManageSection('statutory'))>
                                                <label class="form-check-label" for="epf-lop-components">
                                                    <strong>Consider all applicable salary components if PF wage is less than ₹{{ number_format($epfWageCeiling, 0) }} after Loss of Pay</strong>
                                                    <span class="d-block text-muted small">PF wage will be computed using the salary earned in that particular month (based on LOP) rather than only the amount mentioned in the salary structure.</span>
                                                </label>
                                            </div>

                                            <div class="row g-3 mt-1">
                                                <div class="col-md-6">
                                                    <label class="form-label">Sample PF Wage (preview)</label>
                                                    <input type="number" min="0" step="1" class="form-control" wire:model="epfSamplePfWage" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                            </div>

                                            @if($this->canManageSection('statutory'))
                                                <div class="d-flex gap-2 mt-4">
                                                    <button type="submit" class="ui-btn-primary">{{ $epfEnabled ? 'Save' : 'Enable' }}</button>
                                                    @if(!$epfEnabled)
                                                        <button type="button" class="ui-btn-secondary" wire:click="cancelEnableEpf">Cancel</button>
                                                    @endif
                                                </div>
                                            @endif
                                        </form>
                                    </div>
                                </section>

                                <aside class="ui-card ui-statutory-sample">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Sample EPF Calculation</h2>
                                            <p class="ui-card-desc mb-0">Based on PF wage of ₹ {{ number_format($epfSampleCalculation['pf_wage'], 0) }}</p>
                                        </div>
                                    </div>
                                    <div class="ui-card-body">
                                        <h3 class="h6 text-muted text-uppercase mb-2">Employee's Contribution</h3>
                                        <div class="ui-statutory-sample-row">
                                            <span>{{ $epfSampleCalculation['lines'][0]['label'] ?? 'EPF' }}</span>
                                            <strong>₹ {{ number_format($epfSampleCalculation['employee_epf'], 0) }}</strong>
                                        </div>

                                        <h3 class="h6 text-muted text-uppercase mb-2 mt-4">Employer's Contribution</h3>
                                        <div class="ui-statutory-sample-row">
                                            <span>{{ $epfSampleCalculation['lines'][1]['label'] ?? 'EPS' }}</span>
                                            <strong>₹ {{ number_format($epfSampleCalculation['employer_eps'], 0) }}</strong>
                                        </div>
                                        <div class="ui-statutory-sample-row">
                                            <span>{{ $epfSampleCalculation['lines'][2]['label'] ?? 'EPF' }}</span>
                                            <strong>₹ {{ number_format($epfSampleCalculation['employer_epf'], 0) }}</strong>
                                        </div>
                                        <div class="ui-statutory-sample-row ui-statutory-sample-row--total">
                                            <span>Total</span>
                                            <strong>₹ {{ number_format($epfSampleCalculation['employer_total_12'], 0) }}</strong>
                                        </div>

                                        <h3 class="h6 text-muted text-uppercase mb-2 mt-4">Additional Employer Charges</h3>
                                        @if($epfSampleCalculation['include_edli_in_ctc'])
                                            <div class="ui-statutory-sample-row">
                                                <span>EDLI (0.50% capped)</span>
                                                <strong>₹ {{ number_format($epfSampleCalculation['selected_edli'], 0) }}</strong>
                                            </div>
                                        @endif
                                        @if($epfSampleCalculation['include_admin_in_ctc'])
                                            <div class="ui-statutory-sample-row">
                                                <span>Admin Charges (0.50%)</span>
                                                <strong>₹ {{ number_format($epfSampleCalculation['selected_admin_charges'], 0) }}</strong>
                                            </div>
                                        @endif
                                        @if(!$epfSampleCalculation['has_selected_additional_charges'])
                                            <p class="ui-statutory-help mb-2">No additional employer charges included in salary structure.</p>
                                        @endif
                                        <div class="ui-statutory-sample-row ui-statutory-sample-row--total">
                                            <span>Employer outflow</span>
                                            <strong>₹ {{ number_format($epfSampleCalculation['employer_outflow'], 0) }}</strong>
                                        </div>

                                        <div class="ui-statutory-note mt-4">
                                            <span class="material-symbols-outlined" aria-hidden="true">lightbulb</span>
                                            <div>
                                                Sample updates live as you change contribution and CTC preferences.
                                                @if($epfSampleCalculation['include_employer_in_ctc'])
                                                    CTC includes employer 12%
                                                    @if($epfSampleCalculation['include_edli_in_ctc']) + EDLI @endif
                                                    @if($epfSampleCalculation['include_admin_in_ctc']) + admin charges @endif
                                                    (₹ {{ number_format($epfSampleCalculation['ctc_employer_components'], 0) }}).
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        @endif
                    @endif

                    @if($statutorySubTab === 'esi')
                        @if(!$esiEnabled)
                            <section class="ui-card">
                                <div class="ui-card-body">
                                    <div class="ui-statutory-empty">
                                        <div class="ui-statutory-empty-illustration" aria-hidden="true">
                                            <span class="material-symbols-outlined">health_and_safety</span>
                                        </div>
                                        <h3 class="h5 mb-2">Are you registered for ESI?</h3>
                                        <p class="text-muted mb-4" style="max-width: 36rem;">
                                            Employees’ State Insurance (ESI) is a social security scheme under the ESI Act / Code on Social Security for medical, maternity, disability and dependent benefits. Establishments with 10 or more employees in notified areas must cover employees earning up to ₹21,000/month. Contributions are <strong>0.75%</strong> (employee) and <strong>3.25%</strong> (employer) of gross wages.
                                        </p>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-btn-primary" wire:click="beginEnableEsi">Enable ESI</button>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="ui-statutory-epf-layout">
                                <section class="ui-card">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Employees' State Insurance</h2>
                                            <p class="ui-card-desc mb-0">ESIC registration and contribution rates.</p>
                                        </div>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="disableEsi" wire:confirm="Disable ESI?">Disable ESI</button>
                                        @endif
                                    </div>
                                    <div class="ui-card-body">
                                        <form wire:submit.prevent="saveEsiSettings">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">ESI Number <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" wire:model.defer="esiNumber" @disabled(!$this->canManageSection('statutory'))>
                                                    @error('esiNumber') <div class="text-danger small">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Deduction Cycle</label>
                                                    <select class="form-select" wire:model.defer="esiDeductionCycle" @disabled(!$this->canManageSection('statutory'))>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Employee %</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" wire:model="esiEmployeeContributionPercent" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Employer %</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" wire:model="esiEmployerContributionPercent" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Wage Ceiling</label>
                                                    <input type="number" min="0" class="form-control" wire:model="esiWageCeiling" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Sample Gross Wage</label>
                                                    <input type="number" min="0" class="form-control" wire:model="esiSampleGrossWage" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                            </div>
                                            @if($this->canManageSection('statutory'))
                                                <div class="mt-4"><button type="submit" class="ui-btn-primary">Save ESI</button></div>
                                            @endif
                                        </form>
                                    </div>
                                </section>
                                <aside class="ui-card ui-statutory-sample">
                                    <div class="ui-card-header"><h2 class="ui-card-title mb-0">Sample ESI Calculation</h2></div>
                                    <div class="ui-card-body">
                                        @if(!$esiSampleCalculation['eligible'])
                                            <p class="text-muted mb-0">{{ $esiSampleCalculation['reason'] }}</p>
                                        @else
                                            <div class="ui-statutory-sample-row"><span>Employee ({{ $esiEmployeeContributionPercent }}%)</span><strong>₹ {{ number_format($esiSampleCalculation['employee_esi'], 0) }}</strong></div>
                                            <div class="ui-statutory-sample-row"><span>Employer ({{ $esiEmployerContributionPercent }}%)</span><strong>₹ {{ number_format($esiSampleCalculation['employer_esi'], 0) }}</strong></div>
                                            <div class="ui-statutory-sample-row ui-statutory-sample-row--total"><span>Total</span><strong>₹ {{ number_format($esiSampleCalculation['total'], 0) }}</strong></div>
                                            @if($esiSampleCalculation['reason'])
                                                <p class="ui-statutory-help mt-3 mb-0">{{ $esiSampleCalculation['reason'] }}</p>
                                            @endif
                                        @endif
                                    </div>
                                </aside>
                            </div>
                        @endif
                    @endif

                    @if($statutorySubTab === 'professional_tax')
                        @if(!$ptEnabled)
                            <section class="ui-card">
                                <div class="ui-card-body">
                                    <div class="ui-statutory-empty">
                                        <div class="ui-statutory-empty-illustration" aria-hidden="true">
                                            <span class="material-symbols-outlined">receipt</span>
                                        </div>
                                        <h3 class="h5 mb-2">Are you registered for Professional Tax?</h3>
                                        <p class="text-muted mb-4" style="max-width: 36rem;">
                                            Professional Tax is a state levy on individuals earning income from salary or profession. Rates and slabs differ by state, and the annual tax is nationally capped at ₹2,500. Employers registered in applicable states must deduct PT from employees and remit it to the state authority.
                                        </p>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-btn-primary" wire:click="$set('ptEnabled', true)">Enable Professional Tax</button>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="ui-statutory-epf-layout">
                                <section class="ui-card">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Professional Tax</h2>
                                            <p class="ui-card-desc mb-0">State registration and monthly slabs.</p>
                                        </div>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="disableProfessionalTax" wire:confirm="Disable Professional Tax?">Disable</button>
                                        @endif
                                    </div>
                                    <div class="ui-card-body">
                                        <form wire:submit.prevent="saveProfessionalTaxSettings">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">State Code <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control text-uppercase" placeholder="MH" maxlength="8" wire:model.defer="ptStateCode" @disabled(!$this->canManageSection('statutory'))>
                                                    @error('ptStateCode') <div class="text-danger small">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Registration Number</label>
                                                    <input type="text" class="form-control" wire:model.defer="ptRegistrationNumber" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Deduction Cycle</label>
                                                    <select class="form-select" wire:model.defer="ptDeductionCycle" @disabled(!$this->canManageSection('statutory'))>
                                                        <option value="monthly">Monthly</option>
                                                        <option value="half_yearly">Half-yearly</option>
                                                        <option value="yearly">Yearly</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Sample Gross Wage</label>
                                                    <input type="number" min="0" class="form-control" wire:model="ptSampleGrossWage" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                                                <h3 class="h6 mb-0">Tax Slabs</h3>
                                                @if($this->canManageSection('statutory'))
                                                    <button type="button" class="ui-link-btn" wire:click="addPtSlab">Add slab</button>
                                                @endif
                                            </div>
                                            @forelse($ptSlabs as $index => $slab)
                                                <div class="row g-2 mb-2 align-items-end" wire:key="pt-slab-{{ $index }}">
                                                    <div class="col-md-3">
                                                        <label class="form-label">From</label>
                                                        <input type="number" class="form-control" wire:model.defer="ptSlabs.{{ $index }}.from" @disabled(!$this->canManageSection('statutory'))>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">To (blank = open)</label>
                                                        <input type="number" class="form-control" wire:model.defer="ptSlabs.{{ $index }}.to" @disabled(!$this->canManageSection('statutory'))>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Tax Amount</label>
                                                        <input type="number" class="form-control" wire:model.defer="ptSlabs.{{ $index }}.amount" @disabled(!$this->canManageSection('statutory'))>
                                                    </div>
                                                    <div class="col-md-3">
                                                        @if($this->canManageSection('statutory'))
                                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="removePtSlab({{ $index }})">Remove</button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted small">No slabs yet. Add slabs matching your state PT schedule.</p>
                                            @endforelse

                                            @if($this->canManageSection('statutory'))
                                                <div class="mt-4"><button type="submit" class="ui-btn-primary">Save Professional Tax</button></div>
                                            @endif
                                        </form>
                                    </div>
                                </section>
                                <aside class="ui-card ui-statutory-sample">
                                    <div class="ui-card-header"><h2 class="ui-card-title mb-0">Sample PT</h2></div>
                                    <div class="ui-card-body">
                                        <div class="ui-statutory-sample-row ui-statutory-sample-row--total">
                                            <span>Deduction</span>
                                            <strong>₹ {{ number_format($ptSampleCalculation['amount'], 0) }}</strong>
                                        </div>
                                        @unless($ptSampleCalculation['matched'])
                                            <p class="ui-statutory-help mt-3 mb-0">No matching slab for sample wage. Add slabs and save.</p>
                                        @endunless
                                    </div>
                                </aside>
                            </div>
                        @endif
                    @endif

                    @if($statutorySubTab === 'lwf')
                        @if(!$lwfEnabled)
                            <section class="ui-card">
                                <div class="ui-card-body">
                                    <div class="ui-statutory-empty">
                                        <div class="ui-statutory-empty-illustration" aria-hidden="true">
                                            <span class="material-symbols-outlined">diversity_3</span>
                                        </div>
                                        <h3 class="h5 mb-2">Are you registered for Labour Welfare Fund?</h3>
                                        <p class="text-muted mb-4" style="max-width: 36rem;">
                                            Labour Welfare Fund (LWF) is a state-level contribution used for worker welfare schemes. Employee and employer pay fixed amounts (not a percentage of salary) on a monthly, half-yearly or yearly cycle depending on the state Labour Welfare Board. Many payroll setups miss LWF — enable it if your state mandates it.
                                        </p>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-btn-primary" wire:click="$set('lwfEnabled', true)">Enable LWF</button>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="ui-statutory-epf-layout">
                                <section class="ui-card">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Labour Welfare Fund</h2>
                                            <p class="ui-card-desc mb-0">State contributions per employee.</p>
                                        </div>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="disableLwf" wire:confirm="Disable LWF?">Disable</button>
                                        @endif
                                    </div>
                                    <div class="ui-card-body">
                                        <form wire:submit.prevent="saveLwfSettings">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">State Code <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control text-uppercase" wire:model.defer="lwfStateCode" @disabled(!$this->canManageSection('statutory'))>
                                                    @error('lwfStateCode') <div class="text-danger small">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Deduction Cycle</label>
                                                    <select class="form-select" wire:model.defer="lwfDeductionCycle" @disabled(!$this->canManageSection('statutory'))>
                                                        <option value="monthly">Monthly</option>
                                                        <option value="half_yearly">Half-yearly</option>
                                                        <option value="yearly">Yearly</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Sample Employees</label>
                                                    <input type="number" min="1" class="form-control" wire:model="lwfSampleEmployees" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Employee Contribution (₹)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" wire:model="lwfEmployeeContribution" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Employer Contribution (₹)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" wire:model="lwfEmployerContribution" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                            </div>
                                            @if($this->canManageSection('statutory'))
                                                <div class="mt-4"><button type="submit" class="ui-btn-primary">Save LWF</button></div>
                                            @endif
                                        </form>
                                    </div>
                                </section>
                                <aside class="ui-card ui-statutory-sample">
                                    <div class="ui-card-header"><h2 class="ui-card-title mb-0">Sample LWF</h2></div>
                                    <div class="ui-card-body">
                                        <div class="ui-statutory-sample-row"><span>Employee total</span><strong>₹ {{ number_format($lwfSampleCalculation['employee_total'], 0) }}</strong></div>
                                        <div class="ui-statutory-sample-row"><span>Employer total</span><strong>₹ {{ number_format($lwfSampleCalculation['employer_total'], 0) }}</strong></div>
                                        <div class="ui-statutory-sample-row ui-statutory-sample-row--total"><span>Combined</span><strong>₹ {{ number_format($lwfSampleCalculation['combined_total'], 0) }}</strong></div>
                                    </div>
                                </aside>
                            </div>
                        @endif
                    @endif

                    @if($statutorySubTab === 'statutory_bonus')
                        @if(!$bonusEnabled)
                            <section class="ui-card">
                                <div class="ui-card-body">
                                    <div class="ui-statutory-empty">
                                        <div class="ui-statutory-empty-illustration" aria-hidden="true">
                                            <span class="material-symbols-outlined">celebration</span>
                                        </div>
                                        <h3 class="h5 mb-2">Are you registered for Statutory Bonus?</h3>
                                        <p class="text-muted mb-4" style="max-width: 36rem;">
                                            Under the Payment of Bonus Act (read with the Code on Social Security), eligible employees earning up to ₹21,000/month who have worked at least 30 days in an accounting year are entitled to an annual bonus between <strong>8.33%</strong> and <strong>20%</strong> of wages. Bonus is computed on a maximum of ₹7,000/month or the applicable minimum wage, whichever is higher.
                                        </p>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-btn-primary" wire:click="$set('bonusEnabled', true)">Enable Statutory Bonus</button>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="ui-statutory-epf-layout">
                                <section class="ui-card">
                                    <div class="ui-card-header">
                                        <div>
                                            <h2 class="ui-card-title mb-0">Statutory Bonus</h2>
                                            <p class="ui-card-desc mb-0">Payment of Bonus Act parameters.</p>
                                        </div>
                                        @if($this->canManageSection('statutory'))
                                            <button type="button" class="ui-link-btn ui-link-btn--danger" wire:click="disableBonus" wire:confirm="Disable Statutory Bonus?">Disable</button>
                                        @endif
                                    </div>
                                    <div class="ui-card-body">
                                        <form wire:submit.prevent="saveBonusSettings">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Bonus % (8.33–20)</label>
                                                    <input type="number" step="0.01" min="8.33" max="20" class="form-control" wire:model="bonusPercent" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Eligibility Wage Ceiling</label>
                                                    <input type="number" min="0" class="form-control" wire:model="bonusEligibilityWageCeiling" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Calculation Wage Ceiling</label>
                                                    <input type="number" min="0" class="form-control" wire:model="bonusCalculationWageCeiling" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Minimum Worked Days</label>
                                                    <input type="number" min="1" class="form-control" wire:model.defer="bonusMinimumWorkedDays" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Sample Monthly Wage</label>
                                                    <input type="number" min="0" class="form-control" wire:model="bonusSampleMonthlyWage" @disabled(!$this->canManageSection('statutory'))>
                                                </div>
                                            </div>
                                            @if($this->canManageSection('statutory'))
                                                <div class="mt-4"><button type="submit" class="ui-btn-primary">Save Bonus Settings</button></div>
                                            @endif
                                        </form>
                                    </div>
                                </section>
                                <aside class="ui-card ui-statutory-sample">
                                    <div class="ui-card-header"><h2 class="ui-card-title mb-0">Sample Bonus</h2></div>
                                    <div class="ui-card-body">
                                        @if(!$bonusSampleCalculation['eligible'])
                                            <p class="text-muted mb-0">{{ $bonusSampleCalculation['reason'] }}</p>
                                        @else
                                            <div class="ui-statutory-sample-row"><span>Calculation wage</span><strong>₹ {{ number_format($bonusSampleCalculation['calculation_wage'], 0) }}</strong></div>
                                            <div class="ui-statutory-sample-row"><span>Monthly provision</span><strong>₹ {{ number_format($bonusSampleCalculation['monthly_bonus_provision'], 0) }}</strong></div>
                                            <div class="ui-statutory-sample-row ui-statutory-sample-row--total"><span>Annual bonus</span><strong>₹ {{ number_format($bonusSampleCalculation['annual_bonus'], 0) }}</strong></div>
                                        @endif
                                    </div>
                                </aside>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
</main>
