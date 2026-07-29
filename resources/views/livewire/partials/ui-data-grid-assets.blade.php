@push('styles')
    @once('ui-data-grid-styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/styles/ag-grid.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/styles/ag-theme-alpine.css">
    @endonce
@endpush

@push('scripts')
    @once('ui-data-grid-scripts')
        <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/dist/ag-grid-community.min.js"></script>
        <script src="{{ asset('assets/js/payroll-adjustments-grid.js') }}?v=6"></script>
        <script src="{{ asset('assets/js/attendance-monthly-grid.js') }}?v=3"></script>
    @endonce
@endpush
