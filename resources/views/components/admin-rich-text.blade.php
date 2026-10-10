@once
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/vendor/jodit/jodit.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-rich-text.css') }}">
@endpush
@push('sidebar-scripts')
<script src="{{ asset('assets/vendor/jodit/jodit.min.js') }}"></script>
<script src="{{ asset('assets/js/admin-rich-text.js') }}" defer></script>
@endpush
@endonce
