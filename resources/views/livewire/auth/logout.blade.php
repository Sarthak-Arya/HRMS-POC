<button type="button"
    class="btn btn-link nav-logout-btn {{ in_array(request()->route()->getName(), ['profile', 'my-profile']) ? 'text-white' : 'text-body' }}"
    wire:click="logout">
    <i class="fa fa-user" aria-hidden="true"></i>
    <span class="d-none d-sm-inline">Sign Out</span>
</button>
